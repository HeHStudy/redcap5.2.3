<?php

/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

// Include the PubMed class
require_once APP_PATH_CLASSES . "PubMed.php";

/**
 * PUBMED REDCap
 * This class is used by the PubMed class for REDCap-specific things.
 * 
 * NOTE: all DB operations are defined in RedCapDB.
 */
class PubMedRedcap
{
	/**#@+ Some counters for reporting purposes. */
	public $articlesAdded = 0;
	public $matchesAdded = 0;
	public $meshTermsAdded = 0;
	/**#@-*/
	
	/** An instance of RedCapDB. */
	private $db;
	
	/** A hash of all the PMIDs we have already fetched (to avoid duplicate fetches). */
	private $pmidsFetched = array();
	
	/**
	 * Populated as a side-effect of calling getProjectPiInfo(). Maps search term
	 * strings to arrays of project IDs. Note that IDs that come from
	 * the external projects table will be a concatenation of project_id and
	 * custom_type delimited by RedCapDB::DELIM.
	 */
	public static $searchToProjectIds = array();
	
	/** Create a new instance of this class. */
	function __construct() {
		$this->db = new RedCapDB();
	}
	
	// Convert date with slashes into number for numerical comparison
	static function dateAsNum($date)
	{
		return str_replace("/", "", $date)*1;
	}
	
	/** Converts a newline-delimited institution string into an array of institutions. */
	static function parseInstStr($instStr) {
		$insts = array();
		$instStr = str_replace(array("\r\n", "\r"), array("\n" ,"\n"), $instStr);
		foreach (explode("\n", $instStr) as $inst) {
			$inst = trim($inst);
			if (strlen($inst) > 0) $insts[] = $inst;
		}
		return $insts;
	}
	
	/**
	 * Builds the body of the email to send to the PI.
	 * @param string $lname the last name of the PI.
	 * @param int $pubCount the number of publications the PI needs to match.
	 * @param array $types strings describing the different services/programs
	 * that are related to the matched projects.
	 * @param string $secret the code the PI can use to access to the publication
	 * matching without a password.
	 * @return string the HTML of the email body to send the PI.
	 */
	static function getPIEmailBody($lname, $pubCount, $types, $secret) {
		global $redcap_version, $pub_matching_email_text, $pub_matching_experimental,
			$redcap_base_url;
		$escapedTypes = array();
		foreach ($types as $type) $escapedTypes[] = RCView::escape($type);
		$b = RCL::dr() . ' ' . RCView::escape($lname) . ',';
		$b .= RCView::br() . RCView::br();
		$b .= RCView::escape(empty($pub_matching_email_text) ? RCL::pubEmailIntro() : $pub_matching_email_text);
		$b .= RCView::br() . RCView::br();
		$b .= RCL::pubEmailPubCnt() . ' ' . $pubCount;
		if ($pub_matching_experimental)
			$b .= RCView::br() . RCL::pubEmailResources() . ' ' . implode(', ', $escapedTypes);
		$b .= RCView::br() . RCView::br();
		$b .= RCL::pubEmailLink() . RCView::br();
		$url = $redcap_base_url;
		if (substr($url, -1) !== '/') $url .= '/';
		$url = $redcap_base_url . "redcap_v$redcap_version" . '/PubMatch/index.php?secret=' . $secret;
		$b .= RCView::a(array('href' => $url, 'style' => 'text-decoration:underline;'), $url);
		$b .= RCView::br() . RCView::br();
		$b .= RCL::pubEmailCongrats();
		return $b;
	}
	
	/**
	 * Emails all PIs who have matched pubs awaiting adjudication.
	 * NOTE: remember to check $pub_matching_emails if executing this from
	 * a cron job.
	 * @return int the number of emails that were sent.
	 */
	static function emailPIs() {
		global $pub_matching_email_subject, $project_contact_prod_changes_email;
		$count = 0;
		$db = new RedCapDB();
		$targets = $db->getPubMatchEmailTargets(true);
		foreach ($targets as $pi) {
			$email = new Message();
			$email->setFrom($project_contact_prod_changes_email);
			$email->setTo($pi->project_pi_email);
			$email->setBody(self::getPIEmailBody($pi->project_pi_lastname, $pi->ArticleCount, $pi->CustomTypes, $pi->unique_hash));
			$email->setSubject(empty($pub_matching_email_subject) ? RCL::pubEmailSubject() : $pub_matching_email_subject);
			if ($email->send()) {
				$db->updatePubMatchesForEmail($pi->MatchIds);
				$count++;
			}
		}
		return $count;
	}
	
	/**
	 * Construct the "term" string that will be passed to PubMed.
	 * @param string $author e.g., "Harris PA"
	 * @param array $insts institution names associated with this author.
	 * @return string the search term.
	 * @see http://www.ncbi.nlm.nih.gov/books/NBK3827/#pubmedhelp.Search_Field_Descrip
	 */
	static function buildSearchTerm($author, $insts) {
		$term = $author . "[AU]";
		if (count($insts) > 0)
			$term .= ' AND (' . implode('[AD] OR ', $insts) . '[AD])';
		return $term;
	}
	
	/**
	 * Queries the DB to build the search terms used for PubMed searches.
	 * NOTE: this has the side effect of setting self::$searchToProjectIds.
	 * @return array PubMed search terms as keys and YYYY/MM/DD dates representing
	 * min PubMed query dates as values.
	 */
	static function getSearches() {
		// the global institutions will be used in every search
		global $pub_matching_institution;
		$globalInsts = self::parseInstStr($pub_matching_institution);
		$db = new RedCapDB();
		$projects = $db->getPubProjects(true);
		$searches = array(); self::$searchToProjectIds = array();
		foreach ($projects as $p) {
			// set and process the vars needed for searching
			$vars = array('firstname', 'lastname', 'alias');
			foreach ($vars as $var) {
				$varname = "project_pi_$var";
				$data = $p->$varname;
				if (empty($data)) $data = '';
				$data = trim(label_decode($data));
				$data = ucfirst($data);
				$$var = $data;
			}
			if (strlen($firstname) > 0) $firstname = substr($firstname, 0, 1);
			$name = "";
			if (strlen($lastname) > 0) $name = trim("$lastname $firstname");
			// clear any commas the user may have entered in the alias because
			// expected format is like "Harris PA" and *NOT* like "Harris, PA"
			$alias = preg_replace('/,\s*/', ' ', $alias);
			$project_id = $p->isExternal ?
				$p->project_id . RedCapDB::DELIM . $p->custom_type : $p->project_id;
			// If creation_time is null, set min date to Aug 2006 (i.e. beginning of REDCap consortium)
			$creation = empty($p->creation_time) ? '2006/08/01' : date('Y/m/d', strtotime($p->creation_time));
			$myInsts = self::parseInstStr($p->project_pub_matching_institution);
			$myInsts = array_merge($myInsts, $globalInsts);
			$authors = array();
			if (strlen($name)) $authors[] = $name;
			if ($alias != $name && strlen($alias)) $authors[] = $alias;
			foreach ($authors as $author) {
				$searchTerm = self::buildSearchTerm($author, $myInsts);
				// always use the earlier of the creation dates to make sure we capture everything
				if (empty($searches[$searchTerm]) ||
						self::dateAsNum($creation) < self::dateAsNum($searches[$searchTerm]))
				{
					$searches[$searchTerm] = $creation;
				}
				if (empty(self::$searchToProjectIds[$searchTerm]))
					self::$searchToProjectIds[$searchTerm] = array();
				self::$searchToProjectIds[$searchTerm][] = $project_id;
			}
		}
		ksort($searches);
		return $searches;
	}
	
	// Search PubMed using ALL authors (i.e. project PIs) in REDCap
	public function searchPubMedByAuthors()
	{
		// Loop through all project PIs
		foreach (self::getSearches() as $searchTerm=>$minDate)
		{
			// Make single web service call to PubMed for this author/institution(s)
			$this->searchPubMedBySingleAuthor($searchTerm, $minDate);			
		}
	}
	
	/**
	 * Queries PubMed for information about all our articles which are missing
	 * some piece of useful citation information, and updates the DB with the
	 * results of the queries.
	 */
	public function updateArticleDetails() {
		$articles = $this->db->getIncompleteArticles(RedCapDB::PUBSRC_PUBMED);
		$pmids = array(); $pmid2ArticleId = array();
		foreach ($articles as $a) {
			$pmids[] = $a->pub_id;
			$pmid2ArticleId[$a->pub_id] = $a->article_id;
		}
		$pms = PubMed::get_publication_details($pmids);
		foreach ($pms as $pmid => $pm) {
			$this->pmidsFetched[$pmid] = $pmid;
			$article_id = $pmid2ArticleId[$pmid];
			// update pub_articles table with article info and authors
			$this->db->saveArticle($pmid, RedCapDB::PUBSRC_PUBMED, $pm->article_title, $pm->volume, $pm->issue,
				$pm->pages, $pm->journal, $pm->journal_abbrev, $pm->pub_date, $pm->epub_date,
				$article_id, $pm->authors);
			// update MeSH terms for the article
			$this->updateMeshTerms($article_id, $pm->mesh);
		}
		// clean up invalid matches using our new article data
		$this->matchesAdded -= $this->db->deleteInvalidPubMatches();
	}
	
	/**
	 * Update the MeSH terms for a given article.
	 * @param int $article_id the ID of the article.
	 * @param PubMed->mesh
	 */
	public function updateMeshTerms($article_id, $meshes) {
		$terms = array();
		foreach ($meshes as $mesh) $terms[] = $mesh['descriptor'];
		if (count($terms)) {
			$sqlArr = $this->db->updateArticleMeshTerms($article_id, $terms);
			$this->meshTermsAdded += count($sqlArr);
		}
	}
	
	/**
	 * MeSH terms are continually being added so query PubMed for *all* our
	 * PubMed articles, and add any ones we don't have.
	 */
	public function updateAllMeshTerms() {
		$articles = $this->db->getArticles(RedCapDB::PUBSRC_PUBMED);
		$pmids = array(); $pmid2ArticleId = array();
		foreach ($articles as $a) {
			if (!empty($this->pmidsFetched[$a->pub_id])) continue;
			$pmids[] = $a->pub_id;
			$pmid2ArticleId[$a->pub_id] = $a->article_id;
		}
		$pms = PubMed::get_publication_details($pmids);
		foreach ($pms as $pmid => $pm) {
			$this->pmidsFetched[$pmid] = $pmid;
			$article_id = $pmid2ArticleId[$pmid];
			$this->updateMeshTerms($article_id, $pm->mesh);
		}
	}
	
	// Make single web service call to PubMed for an author
	public function searchPubMedBySingleAuthor($searchTerm, $minDate, $maxResults=100)
	{
		// Query pubmed using PubMed class
		$pm = new PubMed();
		$pm->search($minDate, date('Y/m/d'), $maxResults, $searchTerm);
		## OUTPUT for testing
		// print "<br/>GOT PUBLICATIONS FOR \"<b>$searchTerm</b>\" (begin date = $minDate)!";
		// print_array($pm->pubmed_ids);
		// Store returned author-pub_id info into db tables
		$this->saveAllAuthorArticles($searchTerm, $pm->pubmed_ids);
	}
	
	// Store returned author-pub_id info into db tables
	private function saveAllAuthorArticles($searchTerm, $pubmed_ids)
	{
		// Loop through all articles and store them in pubmed_articles table
		foreach ($pubmed_ids as $pub_id)
		{
			// Add only the PMID to the table right now (we'll fill the title and pub dates later)
			$article = $this->db->getArticleByPMID($pub_id);
			if ($article) $article_id = $article->article_id;
			else {
				$this->db->saveArticle($pub_id, RedCapDB::PUBSRC_PUBMED);
				$article_id = RedCapDB::$lastInsertId;
				$this->articlesAdded++;
			}
			// Store the mappings between article and PI(s)
			foreach (self::$searchToProjectIds[$searchTerm] as $project_id) {
				$custom_type = null;
				if (strpos($project_id, RedCapDB::DELIM) !== false)
					list($project_id, $custom_type) = explode(RedCapDB::DELIM, $project_id);
				if (!$this->db->pubMatchExists($article_id, $project_id, $custom_type)) {
					$this->db->addPubMatch($article_id, $project_id, $searchTerm, $custom_type);
					$this->matchesAdded++;
				}
			}
		}
	}

}
