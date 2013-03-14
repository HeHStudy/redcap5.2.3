<?php

/*****************************************************************************************
**  REDCap is only available through ACADMEMIC USER LICENSE with Vanderbilt University
******************************************************************************************/


require_once 'DB.php';

/**
 * A basic DB abstraction layer.
 */
class RedCapDB {
	
	/**#@+ Constants representing project purposes. */
	const PURPOSE_PRACTICE = 0; // Practice / Just for fun
	const PURPOSE_OTHER    = 1; // Other
	const PURPOSE_RESEARCH = 2; // Research
	const PURPOSE_QUALITY  = 3; // Quality Improvement
	const PURPOSE_OPS      = 4; // Operational Support
	/**#@-*/
	
	/**#@+ Constants representing publication sources. */
	const PUBSRC_PUBMED = 1;
	/**#@-*/
	
	/**
	 * A series of characters unlikely to appear in regular text that can be
	 * used as a delimiter for imploding/exploding strings.
	 */
	const DELIM = '<(^.^)>';
	
	/** An alternative to self::DELIM for cases where we want to nest delimiters. */
	const DELIM2 = '=^..^=';
	
	/** A PEAR DB object.*/
	private static $db = null;
	
	/** The ID of the last record that was inserted, i.e., db_insert_id(). */
	public static $lastInsertId = null;
	
	/** Build a new instance of this class. */
	function __construct() {
		if ($this->db === null) {
			include dirname(dirname(dirname(__FILE__))) . '/database.php';
			// the DSN for PEAR DB has a slightly different format for specifying
			// socket connections than mysqli_connect() does - correct for this
			if (preg_match('/:(.*\.sock)$/i', $hostname, $matches))
				$hostname = 'unix(' . $matches[1] . ')';
			$dsn = "mysqli://$username:$password@$hostname/$db";
			$this->db =& DB::connect($dsn);
			if (PEAR::isError($this->db)) throw new Exception($this->db->getMessage());
		}
	}
	
	/**
	 * Queries the DB for an array of objects.
	 * @param string $query the SQL query to execute with '?' placeholders.
	 * @param array $params an array of parameters that will substitute the placeholders.
	 * @return array the results of the query as an array of objects.
	 */
	private function getObjects($query, $params=array()) {
		$objs = array();
		$res =& $this->db->query($query, $params);
		if (PEAR::isError($res)) throw new Exception($res->getMessage());
		while ($obj = $res->fetchRow(DB_FETCHMODE_OBJECT)) $objs[] = $obj;
		return $objs;
	}
	
	/**
	 * Saves (INSERT/UPDATE) a record to a table.
	 * @param string $tablename the name of the table.
	 * @param array $fieldvals column names as keys and column data as values.
	 * @param string $where a WHERE clause for updates; leave empty for INSERTs.
	 * @return string the SQL query that was executed.
	 */
	private function save($tablename, $fieldvals, $where=null) {
		$saveMode = empty($where) ? DB_AUTOQUERY_INSERT : DB_AUTOQUERY_UPDATE;
		$prep =& $this->db->autoPrepare($tablename, array_keys($fieldvals), $saveMode, $where);
		if (PEAR::isError($prep)) throw new Exception($prep->getMessage());
		$res =& $this->db->execute($prep, array_values($fieldvals));
		if (PEAR::isError($res)) throw new Exception($res->getMessage());
		if ($saveMode === DB_AUTOQUERY_INSERT) self::$lastInsertId = db_insert_id();
		return $this->db->last_query;
	}
	
	/**
	 * Runs a DELETE statement.
	 * @param string $query the SQL query or the statement to prepare.
	 * @param mixed $params array, string or numeric data to be added to the
	 * prepared statement. Quantity of items passed must match quantity of
	 * placeholders in the prepared statement: meaning 1 placeholder for
	 * non-array parameters or 1 placeholder per array element.
	 * @return string the SQL query that was executed.
	 */
	private function delete($query, $params=array()) {
		$res =& $this->db->query($query, $params);
		if (PEAR::isError($res)) throw new Exception($res->getMessage());
		return $this->db->last_query;
	}
	
	/**
	 * Updates a single field in the configuration table.
	 * @param string $field_name the field to update.
	 * @param string $value the new value for the field.
	 * @return array the SQL statement(s) that were executed. The array will be empty if error.
	 */
	function updateConfig($field_name, $value) {
		$sql = array();
		try {
			$sql[] = $this->save('redcap_config', array('value' => $value),
				"field_name='" . db_real_escape_string($field_name) . "'");
		}
		catch (Exception $e) { return array(); }
		return $sql;
	}
	
	/////////////////////////////////////////////////////////////////////////////
	//
	// API functions
	//
	/////////////////////////////////////////////////////////////////////////////
	
	/** Sets the API user rights of a given user for a given project.*/
	function saveAPIRights($username, $project_id, $export, $import) {
		$sql = array();
		try {
			$sql[] = $this->save('redcap_user_rights', array('api_export' => $export, 'api_import' => $import),
				"username='" . db_real_escape_string($username) . "' AND project_id=" . intval($project_id));
		}
		catch (Exception $e) { return array(); }
		return $sql;
	}
	
	/**
	 * Gets a user's API token for a given project.
	 * @param type $username
	 * @param type $project_id 
	 * @return string the API token or null if it does not exist.
	 */
	function getAPIToken($username, $project_id) {
		$sql = "SELECT api_token FROM redcap_user_rights WHERE username = ? AND project_id = ?";
		$objs = $this->getObjects($sql, array($username, $project_id));
		if (count($objs) === 0) return null;
		return empty($objs[0]->api_token) ? null : $objs[0]->api_token;
	}
	
	/** Gets all the API tokens for use by superuser administration. */
	function getAPITokens($orderProj=false, $project_id='') {
		$order = $orderProj ? "p.app_title, r.username" : "r.username, p.app_title";
		$where = ''; $params = array();
		if (!empty($project_id)) {
			$where .= " AND r.project_id = ?";
			$params[] = $project_id;
		}
		$sql = "SELECT r.username, r.api_token, r.api_export, r.api_import, r.project_id, p.app_title
			FROM redcap_user_rights r JOIN redcap_projects p ON (r.project_id=p.project_id)
			WHERE CHAR_LENGTH(r.api_token) > 0 $where
			ORDER BY $order";
		return $this->getObjects($sql, $params);
	}
	
	/** Returns a count of the number of API tokens assigned on the given project. */
	function countAPITokensByProject($project_id) {
		$sql = "SELECT COUNT(*) AS TokenCount
			FROM redcap_user_rights
			WHERE project_id = ? AND api_token IS NOT NULL";
		$objs = $this->getObjects($sql, array($project_id));
		return $objs[0]->TokenCount;
	}
	
	/**
	 * Creates a new API token for the given user on a given project.
	 * @param type $username
	 * @param type $project_id 
	 * @return array the SQL statement(s) that were executed. The array will be empty if error.
	 */
	function setAPIToken($username, $project_id) {
		$sql = array();
		try {
			$tok =  strtoupper(md5("$username&$project_id&" . microtime()));
			$sql[] = $this->save('redcap_user_rights', array('api_token' => $tok),
				"username='" . db_real_escape_string($username) . "' AND project_id=" . intval($project_id));
		}
		catch (Exception $e) { return array(); }
		return $sql;
	}
	
	/**
	 * Nulls out the API token for the given user on a given project.
	 * @param type $username
	 * @param type $project_id 
	 * @return array the SQL statement(s) that were executed. The array will be empty if error.
	 */
	function deleteAPIToken($username, $project_id) {
		$sql = array();
		try {
			$sql[] = $this->save('redcap_user_rights', array('api_token' => null),
				"username='" . db_real_escape_string($username) . "' AND project_id=" . intval($project_id));
		}
		catch (Exception $e) { return array(); }
		return $sql;
	}
	
	/**
	 * Nulls out the API tokens for a given project.
	 * @param type $project_id 
	 * @return array the SQL statement(s) that were executed. The array will be empty if error.
	 */
	function deleteAPIProjectTokens($project_id) {
		$sql = array();
		try {
			$sql[] = $this->save('redcap_user_rights', array('api_token' => null),
				"project_id=" . intval($project_id));
		}
		catch (Exception $e) { return array(); }
		return $sql;
	}
	
	/**
	 * Determines when people last used the API.
	 * @param string $username leave empty for all usernames.
	 * @param int $project_id leave empty for all projects.
	 * @return array first key is username, second key is project_id, and the value
	 * is an object with member variable LastTS containing the MySQL timestamp
	 * of when the API key was most recently used.
	 */
	function getLastAPICallDates($username=null, $project_id=null) {
		$params = array(); $where = '';
		if (!empty($username)) {
			$params[] = $username;
			$where .= " AND user = ?";
		}
		if (!empty($project_id)) {
			$params[] = $project_id;
			$where .= " AND project_id = ?";
		}
		$calls = array();
		$sql = "SELECT user, project_id, MAX(ts) AS LastTS
			FROM redcap_log_event
			WHERE description LIKE '%(API)%'$where
			GROUP BY user, project_id";
		$objs = $this->getObjects($sql, $params);
		foreach ($objs as $obj) $calls[$obj->user][$obj->project_id] = $obj;
		return $calls;
	}
	
	/////////////////////////////////////////////////////////////////////////////
	//
	// Project functions
	//
	/////////////////////////////////////////////////////////////////////////////
	
	/** Gets a project given the project ID. */
	function getProject($pid) {
		$sql = "SELECT * FROM redcap_projects WHERE project_id = ?";
		$objs = $this->getObjects($sql, array($pid));
		return $objs[0];
	}
	
	/** Gets all projects. */
	function getProjects() {
		$sql = "SELECT * FROM redcap_projects ORDER BY app_title";
		$objs = $this->getObjects($sql);
		return $objs;
	}
	
	/** Gets all event metadata and arms for a given project. */
	function getEvents($project_id) {
		$sql = "SELECT rem.event_id, rem.descrip, rea.arm_name
			FROM redcap_events_metadata rem
				JOIN redcap_events_arms rea ON rem.arm_id = rea.arm_id
			WHERE rea.project_id = ?";
		return $this->getObjects($sql, array($project_id));
	}
	
	/////////////////////////////////////////////////////////////////////////////
	//
	// Publication functions
	//
	/////////////////////////////////////////////////////////////////////////////
	
	/** Gets PI/publication info from a (possibly external) project given the project ID. */
	function getPubProject($pid, $externalType=null) {
		global $pub_matching_experimental;
		$isExternal = $externalType === null ? 0 : 1;
		$table = !$isExternal ? 'redcap_projects' : 'redcap_projects_external';
		$type = !$isExternal ? 'NULL AS custom_type' : 'custom_type';
		$params = array($pid);
		if ($isExternal) $params[] = $externalType;
		$sql = "SELECT project_id, app_title, project_pi_lastname, project_pi_firstname,
				project_pi_mi, project_pi_alias, project_pi_email, project_pi_pub_exclude,
				project_pub_matching_institution, $isExternal AS isExternal, $type
			FROM $table WHERE project_id = ?" .
				($isExternal ? ' AND custom_type = ?' : '') .
				(($isExternal && !$pub_matching_experimental) ? ' AND 1=0' : '');
		$objs = $this->getObjects($sql, $params);
		return $objs[0];
	}
	
	/** Gets PI/publication info from all projects and external projects. */
	function getPubProjects($selectOnlySearchables=false) {
		global $pub_matching_experimental;
		$extraWhere = $selectOnlySearchables ? 'AND project_pi_pub_exclude = 0' : '';
		$sql = "(SELECT project_id, app_title, project_pi_lastname, project_pi_firstname,
				project_pi_mi, project_pi_alias, project_pi_email, project_pi_pub_exclude,
				project_pub_matching_institution, 0 AS isExternal, NULL AS custom_type,
				creation_time
			FROM redcap_projects
			WHERE purpose = " . self::PURPOSE_RESEARCH . " AND status > 0 $extraWhere)
			UNION
			(SELECT project_id, app_title, project_pi_lastname, project_pi_firstname,
				project_pi_mi, project_pi_alias, project_pi_email, project_pi_pub_exclude,
				project_pub_matching_institution, 1 AS isExternal, custom_type,
				creation_time
			FROM redcap_projects_external
			WHERE " . ($pub_matching_experimental ? '1=1' : '1=0') . " $extraWhere)
			ORDER BY app_title";
		$objs = $this->getObjects($sql);
		return $objs;
	}
	
	/** Updates the PI data for a given project. */
	function updateProjectPI($pid, $fname, $mi, $lname, $email, $alias, $pubExclude,
					$insts, $externalType=null)
	{
		$sqlArr = array();
		if (strlen($pubExclude) === 0) $pubExclude = null;
		$fields = array(
				'project_pi_firstname' => $fname,
				'project_pi_mi' => $mi,
				'project_pi_lastname' => $lname,
				'project_pi_email' => $email,
				'project_pi_alias' => $alias,
				'project_pi_pub_exclude' => $pubExclude,
				'project_pub_matching_institution' => $insts);
		try {
			$table = 'redcap_projects';
			$where = 'project_id=' . intval($pid);
			if ($externalType !== null) {
				$table = 'redcap_projects_external';
				$where = "project_id='" . $this->db->escapeSimple($pid) . "' AND " .
					"custom_type='" . $this->db->escapeSimple($externalType) . "'";
			}
			$sqlArr[] = $this->save($table, $fields, $where);
		}
		catch (Exception $e) { return array(); }
		return $sqlArr;
	}
	
	/**
	 * Gets an article using its PubMed identifier.
	 * @param string $pub_id the PubMed identifier.
	 * @return mixed an article object or null if we don't have an article with
	 * the given PMID.
	 */
	function getArticleByPMID($pub_id) {
		$sql = "SELECT *
			FROM redcap_pub_articles
			WHERE pub_id = ? AND pubsrc_id = ?";
		$objs = $this->getObjects($sql, array($pub_id, self::PUBSRC_PUBMED));
		return count($objs) ? $objs[0] : null;
	}
	
	/**
	 * Gets all articles for a given publication source.
	 * @param int $pubsrc_id the source of the articles; see self::PUBSRC_*
	 * @return array the article objects.
	 */
	function getArticles($pubsrc_id) {
		$query = "SELECT * FROM redcap_pub_articles WHERE pubsrc_id = ?";
		return $this->getObjects($query, array($pubsrc_id));
	}
	
	/**
	 * Gets all articles that are missing some piece of data that we might want
	 * to query a publication source to obtain.
	 * @param int $pubsrc_id the source of the articles; see self::PUBSRC_*
	 * @return array the article objects that have missing data.
	 */
	function getIncompleteArticles($pubsrc_id) {
		$sql = "SELECT *
			FROM redcap_pub_articles
			WHERE (title IS NULL OR CHAR_LENGTH(title) = 0 OR
				volume IS NULL OR CHAR_LENGTH(volume) = 0 OR
				issue IS NULL OR CHAR_LENGTH(issue) = 0 OR
				pages IS NULL OR CHAR_LENGTH(pages) = 0 OR
				journal IS NULL OR CHAR_LENGTH(journal) = 0 OR
				journal_abbrev IS NULL OR CHAR_LENGTH(journal_abbrev) = 0 OR
				pub_date IS NULL OR
				epub_date IS NULL OR
				article_id NOT IN (SELECT article_id FROM redcap_pub_authors)) AND
				pubsrc_id = ?";
		return $this->getObjects($sql, array($pubsrc_id));
	}
	
	/**
	 * Inserts or updates an article.
	 * @param string $pub_id
	 * @param int $pubsrc_id see self::PUBSRC_*
	 * @param string $title
	 * @param string $volume
	 * @param string $issue
	 * @param string $pages
	 * @param string $journal
	 * @param string $journal_abbrev
	 * @param string $pub_date YYYY-MM-DD
	 * @param string $epub_date YYYY-MM-DD
	 * @param int $articleId needed for updates.
	 * @param array $authors if the article currently has no authors, then the
	 * given authors will be added. Each element in the array is an associative
	 * array with the keys 'last_name' and 'first_name'. Note that 'first_name'
	 * can be formatted as "First MI".
	 * @return array the SQL statement(s) that were executed.
	 */
	function saveArticle($pub_id, $pubsrc_id, $title=null, $volume=null, $issue=null, $pages=null,
					$journal=null, $journal_abbrev=null, $pub_date=null, $epub_date=null,
					$articleId=null, $authors=array())
	{
		$sqlArr = array();
		// use substr() to stay within DB field constraints
		$fields = array(
				'pub_id' => $pub_id,
				'pubsrc_id' => $pubsrc_id,
				'title' => $title,
				'volume' => $volume ? substr($volume, 0, 16) : null,
				'issue' => $issue ? substr($issue, 0, 16) : null,
				'pages' => $pages ? substr($pages, 0, 16) : null,
				'journal' => $journal,
				'journal_abbrev' => $journal_abbrev ? substr($journal_abbrev, 0, 64) : null,
				'pub_date' => $pub_date,
				'epub_date' => $epub_date);
		$where = empty($articleId) ? null : 'article_id=' . intval($articleId);
		$sqlArr[] = $this->save('redcap_pub_articles', $fields, $where);
		if (!$articleId) $articleId = self::$lastInsertId;
		// save authors
		if (count($authors) > 0) {
			// only save authors if none already exist because we are not going to
			// currently try to "diff" existing vs incoming authors
			$query = "SELECT COUNT(*) AS AuthorCount FROM redcap_pub_authors WHERE article_id = ?";
			$objs = $this->getObjects($query, array($articleId));
			if ($objs[0]->AuthorCount == 0) {
				foreach ($authors as $author) {
					// Set last name
					$last = $author['last_name'];
					// Check if using middle initial in first_name
					list ($first, $mi) = explode(" ", $author['first_name'], 2);
					// Set name in LAST+space+FIRST INITIAL+MIDDLE INITIAL format
					$name = trim(trim($last) . " " . substr($first, 0, 1) . substr(trim($mi), 0, 1));
					// Add author to table
					if ($name != "") {
						$authorFields = array(
								'article_id' => $articleId,
								'author' => $name
						);
						$sqlArr[] = $this->save('redcap_pub_authors', $authorFields);
					}
				}
			}
		}
		return $sqlArr;
	}
	
	/**
	 * Adds any MeSH terms that an article does not already have.
	 * @param int $articleId the unqiue ID of the article.
	 * @param array $meshTerms the MeSH terms to associate with the article.
	 * @return array the SQL INSERT(s) that were executed.
	 */
	function updateArticleMeshTerms($articleId, $meshTerms) {
		$sqlArr = array();
		// first figure out the mesh terms we already have
		$query = "SELECT * FROM redcap_pub_mesh_terms WHERE article_id = ?";
		$objs = $this->getObjects($query, array($articleId));
		$existingTerms = array();
		foreach ($objs as $obj) $existingTerms[strtoupper($obj->mesh_term)] = true;
		// add any new mesh terms
		foreach ($meshTerms as $term) {
			if (!empty($existingTerms[strtoupper($term)])) continue;
			$fields = array(
				'article_id' => $articleId,
				'mesh_term' => substr($term, 0, 255)); // truncate for column size
			$sqlArr[] = $this->save('redcap_pub_mesh_terms', $fields);
		}
		return $sqlArr;
	}
	
	/**
	 * Gets info needed to email PIs regarding matched publications.
	 * @param boolean $enforceEmailFreq true to ignore results that have met or
	 * exceeded $pub_matching_email_limit, or results that fall within
	 * $pub_matching_email_days.
	 * @return array objects unique PIs that have publication matches that are
	 * pending adjudication, ordered by PI name. The following members will be
	 * added to each object:
	 * ArticleCount - the number of pubs the PI needs to match.
	 * MinEmailCount - the minimum number of times the PI has been emailed about
	 * any one of these matched pubs.
	 * CustomTypes - an array of unique custom project types; REDCap projects
	 * will be given the type "REDCap".
	 * MatchIds - an array of redcap_pub_matches IDs that the PI is being
	 * emailed about.
	 */
	function getPubMatchEmailTargets($enforceEmailFreq=false) {
		global $pub_matching_experimental, $pub_matching_email_limit,
			$pub_matching_email_days;
		// avoid duplicating all this SQL by templating it
		$selTemplate = "(SELECT m.unique_hash, p.project_pi_lastname,
				p.project_pi_firstname, p.project_pi_mi, p.project_pi_email,
				m.article_id, m.email_count, TPL_CUSTOM_TYPE, m.match_id
			FROM redcap_pub_matches m JOIN TPL_TABLE p
				ON (m.TPL_PID_NAME = p.project_id TPL_EXT_JOIN)
			WHERE p.project_pi_pub_exclude IS NOT NULL AND
				p.project_pi_pub_exclude = 0 AND
				" . ($enforceEmailFreq ? "m.email_count < ? AND DATEDIFF(NOW(), IFNULL(m.email_time, '1970-01-01')) >= ? AND" : '') . "
				m.matched IS NULL TPL_PROJ_FILTER)";
		$selRedcap = str_replace(array('TPL_TABLE', 'TPL_PID_NAME', 'TPL_EXT_JOIN', 'TPL_PROJ_FILTER', 'TPL_CUSTOM_TYPE'),
						array('redcap_projects', 'project_id', '', 'AND purpose = ' . self::PURPOSE_RESEARCH . ' AND status > 0', "'REDCap' AS custom_type"), $selTemplate);
		$selExternal = str_replace(array('TPL_TABLE', 'TPL_PID_NAME', 'TPL_EXT_JOIN', 'TPL_PROJ_FILTER', 'TPL_CUSTOM_TYPE'),
						array('redcap_projects_external', 'external_project_id', 'AND m.external_custom_type = p.custom_type', ($pub_matching_experimental ? '' : ' AND 1=0'), 'p.custom_type'), $selTemplate);
		// exclude any matches referencing articles that the PI has previously matched
		$query = "SELECT * FROM ($selRedcap UNION $selExternal) u
			WHERE u.article_id NOT IN
				(SELECT DISTINCT m3.article_id
				FROM redcap_pub_matches m3
				WHERE m3.matched IS NOT NULL)
			ORDER BY project_pi_lastname, project_pi_firstname";
		$targets = array();
		$usedEmails = array(); // [email addr] = object
		$emailArticleMap = array(); // [email addr][article ID] = true
		$emailCounts = array(); // [email addr] = min times the PI was emailed
		$emailTypes = array(); // [email addr][custom type] = custom type
		$emailMatchIds = array(); // [email addr][] = pub_matches IDs
		$params = $enforceEmailFreq ? array($pub_matching_email_limit, $pub_matching_email_days,
				$pub_matching_email_limit, $pub_matching_email_days) : array();
		$objs = $this->getObjects($query, $params);
		foreach ($objs as $obj) {
			$emailArticleMap[$obj->project_pi_email][$obj->article_id] = true;
			if (empty($usedEmails[$obj->project_pi_email])) {
				$usedEmails[$obj->project_pi_email] = $obj;
				$targets[] = $obj;
			}
			if (!array_key_exists($obj->project_pi_email, $emailCounts))
				$emailCounts[$obj->project_pi_email] = $obj->email_count;
			else $emailCounts[$obj->project_pi_email] = min($emailCounts[$obj->project_pi_email], $obj->email_count);
			if (!empty($obj->custom_type)) $emailTypes[$obj->project_pi_email][$obj->custom_type] = $obj->custom_type;
			if (empty($emailMatchIds[$obj->project_pi_email]))
				$emailMatchIds[$obj->project_pi_email] = array();
			$emailMatchIds[$obj->project_pi_email][] = $obj->match_id;
		}
		// add misc aggregate data to the targets
		foreach ($targets as $pi) {
			$pi->ArticleCount = count($emailArticleMap[$pi->project_pi_email]);
			unset($pi->email_count);
			$pi->MinEmailCount = $emailCounts[$pi->project_pi_email];
			$pi->CustomTypes = $emailTypes[$pi->project_pi_email];
			$pi->MatchIds = $emailMatchIds[$pi->project_pi_email];
		}
		return $targets;
	}
	
	/**
	 * Retrieves all the data needed to allow a P.I. to adjudicate their
	 * potentially matched publications.
	 * @param string $hash the secret used by the P.I. to access the publication
	 * adjudication without logging in. This can be the unique_hash of *any* of
	 * the P.I.'s matches - it will be used to lookup all other matches.
	 * @param boolean $allMatches true to obtain *ALL* matches, not just the ones
	 * that are in a TODO state. Defaults to false for only TODOs.
	 * @return array keys are article IDs and values are arrays of project objects
	 * that are potentially related to the article.
	 */
	function getPubMatchTodosByHash($hash, $allMatches=false) {
		global $pub_matching_experimental;
		// NOTE: when calculating ConsensusDate, use pub_date first since that is more
		// likely to correspond with the citation data
		$selTemplate = "(SELECT m.match_id, a.*, x.AuthorList,
				p.app_title, p.project_id, p.project_pi_lastname, p.project_pi_firstname,
				p.project_pi_mi, p.project_pi_alias, TPL_IS_EXTERNAL AS isExternal,
				TPL_CUSTOM_TYPE, IFNULL(a.pub_date, IFNULL(a.epub_date, CURDATE())) AS ConsensusDate
			FROM redcap_pub_matches m JOIN TPL_TABLE p
				ON (m.TPL_PID_NAME = p.project_id TPL_EXT_JOIN) JOIN redcap_pub_articles a
				ON (m.article_id = a.article_id) JOIN
					(SELECT article_id, GROUP_CONCAT(author ORDER BY author_id ASC SEPARATOR '; ') AS AuthorList
					FROM redcap_pub_authors
					GROUP BY article_id) x
				ON (a.article_id = x.article_id)
			WHERE p.project_pi_email = 
					(SELECT IFNULL(p2.project_pi_email, p3.project_pi_email)
					FROM redcap_pub_matches m2
						LEFT JOIN redcap_projects p2
							ON (m2.project_id = p2.project_id)
						LEFT JOIN redcap_projects_external p3
							ON (m2.external_project_id = p3.project_id AND m2.external_custom_type = p3.custom_type)
					WHERE m2.unique_hash = ?) AND
				p.project_pi_email IS NOT NULL AND
				CHAR_LENGTH(TRIM(p.project_pi_email)) <> 0 AND
				p.project_pi_pub_exclude = 0 AND
				" . ($allMatches ? '1=1' : 'm.matched IS NULL') . " TPL_PROJ_FILTER)";
		$selRedcap = str_replace(array('TPL_IS_EXTERNAL', 'TPL_TABLE', 'TPL_PID_NAME', 'TPL_EXT_JOIN', 'TPL_CUSTOM_TYPE', 'TPL_PROJ_FILTER'),
						array('0', 'redcap_projects', 'project_id', '', "'REDCap' AS custom_type", 'AND purpose = ' . self::PURPOSE_RESEARCH . ' AND status > 0'), $selTemplate);
		$selExternal = str_replace(array('TPL_IS_EXTERNAL', 'TPL_TABLE', 'TPL_PID_NAME', 'TPL_EXT_JOIN', 'TPL_CUSTOM_TYPE', 'TPL_PROJ_FILTER'),
						array('1', 'redcap_projects_external', 'external_project_id', 'AND m.external_custom_type = p.custom_type', 'custom_type', ($pub_matching_experimental ? '' : ' AND 1=0')), $selTemplate);
		$where = '';
		// exclude any matches referencing articles that the PI has previously matched
		if (!$allMatches) {
			$where = "WHERE u.article_id NOT IN
				(SELECT DISTINCT m3.article_id
				FROM redcap_pub_matches m3
				WHERE m3.matched IS NOT NULL)";
		}
		$query = "SELECT * FROM ($selRedcap UNION $selExternal) u $where ORDER BY ConsensusDate DESC, app_title";
		$objs = $this->getObjects($query, array($hash, $hash));
		$articleId2Todos = array();
		foreach ($objs as $obj) {
			if (empty($articleId2Todos[$obj->article_id]))
				$articleId2Todos[$obj->article_id] = array();
			$articleId2Todos[$obj->article_id][] = $obj;
		}
		return $articleId2Todos;
	}
	
	/**
	 * Retrieves all the data needed to build stats for matched publications.
	 * @return array keys are article IDs and values are arrays of project objects
	 * that have been matched to the article. REDCap projects will be given the
	 * custom_type "REDCap".
	 */
	function getPubMatchesForStats() {
		global $pub_matching_experimental;
		// NOTE: when calculating ConsensusDate, use pub_date first since that is more
		// likely to correspond with the citation data
		$selTemplate = "(SELECT m.match_id, a.*, x.AuthorList,
				p.app_title, p.project_id, p.project_pi_lastname, p.project_pi_firstname,
				p.project_pi_mi, p.project_pi_alias, TPL_IS_EXTERNAL AS isExternal,
				TPL_CUSTOM_TYPE, IFNULL(a.pub_date, IFNULL(a.epub_date, CURDATE())) AS ConsensusDate
			FROM redcap_pub_matches m JOIN TPL_TABLE p
				ON (m.TPL_PID_NAME = p.project_id TPL_EXT_JOIN) JOIN redcap_pub_articles a
				ON (m.article_id = a.article_id) JOIN
					(SELECT article_id, GROUP_CONCAT(author ORDER BY author_id ASC SEPARATOR '; ') AS AuthorList
					FROM redcap_pub_authors
					GROUP BY article_id) x
				ON (a.article_id = x.article_id)
			WHERE m.matched = 1 TPL_EXPERIMENT)";
		$selRedcap = str_replace(array('TPL_IS_EXTERNAL', 'TPL_TABLE', 'TPL_PID_NAME', 'TPL_EXT_JOIN', 'TPL_CUSTOM_TYPE', 'TPL_EXPERIMENT'),
						array('0', 'redcap_projects', 'project_id', '', "'REDCap' AS custom_type", ''), $selTemplate);
		$selExternal = str_replace(array('TPL_IS_EXTERNAL', 'TPL_TABLE', 'TPL_PID_NAME', 'TPL_EXT_JOIN', 'TPL_CUSTOM_TYPE', 'TPL_EXPERIMENT'),
						array('1', 'redcap_projects_external', 'external_project_id', 'AND m.external_custom_type = p.custom_type', 'custom_type', ($pub_matching_experimental ? '' : ' AND 1=0')), $selTemplate);
		$query = "$selRedcap UNION $selExternal ORDER BY ConsensusDate DESC, app_title";
		$objs = $this->getObjects($query);
		$articleId2Matched = array();
		foreach ($objs as $obj) {
			if (empty($articleId2Matched[$obj->article_id]))
				$articleId2Matched[$obj->article_id] = array();
			$articleId2Matched[$obj->article_id][] = $obj;
		}
		return $articleId2Matched;
	}
	
	/**
	 * Checks whether the secret used by the P.I. to access the publication
	 * adjudication without logging in is valid.
	 * @param string $hash the secret.
	 * @return boolean true if the secret is valid, false if not.
	 */
	function isValidPubMatchHash($hash) {
		// some basic checks before going to the DB
		if (empty($hash) || strlen($hash) !== 32 || !preg_match('/[a-z0-9]/', $hash)) {
			return false;
		}
		$sql = "SELECT COUNT(*) AS MatchCount
			FROM redcap_pub_matches
			WHERE unique_hash = ?";
		$objs = $this->getObjects($sql, array($hash));
		return $objs[0]->MatchCount == 1 ? true : false;
	}
	
	/**
	 * Checks whether the secret used by the P.I. has access to the given matches.
	 * @param array $matchIds the IDs of the matches that are being accessed.
	 * @param string $hash the secret.
	 * @return boolean true if the secret can access the matches, false if not.
	 */
	function canAccessPubMatches($matchIds, $hash) {
		$articleId2Matches = $this->getPubMatchTodosByHash($hash, true);
		$validIds = array();
		foreach ($articleId2Matches as $articleId => $matches)
			foreach ($matches as $m)
				$validIds[$m->match_id] = true;
		foreach ($matchIds as $matchId)
			if (empty($validIds[$matchId])) return false;
		return true;
	}
	
	/**
	 * Determines whether or not a publication has been mapped to a project
	 * for potential matching by the PI.
	 * @param int $article_id the ID of the publication.
	 * @param int $project_id the ID of the project.
	 * @param string $externalType a string if $project_id refers to
	 * redcap_projects_external, NULL if it refers to redcap_projects.
	 * @return boolean true if a potential match exists, false if not.
	 */
	function pubMatchExists($article_id, $project_id, $externalType=null) {
		$where = 'article_id = ?';		
		$params = array($article_id, $project_id);
		if ($externalType === null) {
			$where .= ' AND project_id = ?';
		}
		else {
			$where .= ' AND external_project_id = ? AND external_custom_type = ?';
			$params[] = $externalType;
		}
		$sql = "SELECT COUNT(*) AS MatchCount
			FROM redcap_pub_matches
			WHERE $where";
		$objs = $this->getObjects($sql, $params);
		return $objs[0]->MatchCount > 0 ? true : false;
	}
	
	/**
	 * Creates a mapping between a publication and a project for poential matching by a PI.
	 * @param int $article_id the ID of the publication.
	 * @param int $project_id the ID of the project.
	 * @param string $search_term the search term that was used to query the publication source.
	 * @param string $externalType a string if $project_id refers to
	 * redcap_projects_external, NULL if it refers to redcap_projects.
	 * @return array the SQL statement(s) that were executed. The array will be empty if error.
	 */
	function addPubMatch($article_id, $project_id, $search_term, $externalType=null) {
		$sql = array();
		// guard against exceeding column width
		if (strlen($search_term) > 255) $search_term = substr($search_term, 0, 255);
		// assume that any exception is a violation of the UNIQUE constraint on
		// unique_hash and try again
		try {
			$fields = array(
					'article_id' => $article_id,
					'search_term' => $search_term,
					'unique_hash' => md5(rand())
			);
			if ($externalType === null) {
				$fields['project_id'] = $project_id;
			}
			else {
				$fields['external_project_id'] = $project_id;
				$fields['external_custom_type'] = $externalType;
			}
			$sql[] = $this->save('redcap_pub_matches', $fields);
		}
		// assume that any exception is caused by a unique_hash collision and just
		// keep trying until one works
		catch (Exception $e) { return $this->addPubMatch($article_id, $project_id, $search_term, $externalType); }
		return $sql;
	}
	
	/**
	 * Deletes all pub match records that are invalid because the project they
	 * refer to was created after the publication was published.
	 * @return int the number of matches that were deleted.
	 */
	function deleteInvalidPubMatches() {
		$dawnOfTime = '1970-01-01';
		$ids = array();
		// only use the YEAR because pub dates are a little sketchy
		$sqlTemplate = "SELECT m.match_id
			FROM redcap_pub_matches m
				JOIN redcap_pub_articles a ON (m.article_id = a.article_id)
				JOIN TPL_PROJ_TABLE p ON (m.TPL_PID_COL = p.project_id TPL_EXT_JOIN)
			WHERE m.matched IS NULL AND
				p.creation_time IS NOT NULL AND
				(a.pub_date IS NOT NULL OR a.epub_date IS NOT NULL) AND
				YEAR(p.creation_time) > YEAR(IFNULL(a.pub_date, '$dawnOfTime')) AND
				YEAR(p.creation_time) > YEAR(IFNULL(a.epub_date, '$dawnOfTime'))";
		$sql = str_replace(array('TPL_PROJ_TABLE', 'TPL_PID_COL', 'TPL_EXT_JOIN'),
			array('redcap_projects', 'project_id', ''), $sqlTemplate);
		$objs = $this->getObjects($sql);
		foreach ($objs as $obj) $ids[] = $obj->match_id;
		$sql = str_replace(array('TPL_PROJ_TABLE', 'TPL_PID_COL', 'TPL_EXT_JOIN'),
			array('redcap_projects_external', 'external_project_id', 'AND m.external_custom_type = p.custom_type'), $sqlTemplate);
		$objs = $this->getObjects($sql);
		foreach ($objs as $obj) $ids[] = $obj->match_id;
		$count = 0;
		if (count($ids)) {
			$del = "DELETE FROM redcap_pub_matches WHERE match_id IN (" . implode(',', $ids) . ")";
			$this->delete($del);
			$count = $this->db->affectedRows();
		}
		return $count;
	}
	
	/**
	 * Marks a potential publication match as being either a match, or not a match.
	 * @param array $matchIds the IDs of the redcap_pub_matches records.
	 * @param mixed $isMatch true if the publication matches to the project,
	 * false if not, and null to unset the match.
	 * @return array the SQL statement(s) that were executed. The array will be empty if error.
	 */
	function matchPubMatches($matchIds, $isMatch) {
		$sql = array();
		try {
			// some basic validation and sanitation
			$ids = array();
			foreach ($matchIds as $matchId) $ids[] = intval($matchId);
			if (count($ids) === 0)
				throw new Exception("I wasn't given any match IDs!");
			$where = 'match_id IN (' . implode(',', $ids) . ')';
			$isMatch = $isMatch === null ? null : ($isMatch ? 1 : 0);
			$fields = array(
					'matched' => $isMatch,
					'matched_time' => date('Y-m-d H:i:s')
			);
			$sql[] = $this->save('redcap_pub_matches', $fields, $where);
		}
		catch (Exception $e) { return array(); }
		return $sql;
	}
	
	/**
	 * Update publication match email stats following an email.
	 * @param array $matchIds the IDs of the redcap_pub_matches records.
	 * @return array the SQL statement(s) that were executed. The array will be empty if error.
	 */
	function updatePubMatchesForEmail($matchIds) {
		$sql = array();
		try {
			// some basic validation and sanitation
			$ids = array();
			foreach ($matchIds as $matchId) $ids[] = intval($matchId);
			if (count($ids) === 0) return $sql;
			$where = 'match_id IN (' . implode(',', $ids) . ')';
			$query = "UPDATE redcap_pub_matches
				SET email_count = email_count + 1,
					email_time = NOW()
				WHERE $where";
			$prep =& $this->db->prepare($query);
			if (PEAR::isError($prep)) throw new Exception($prep->getMessage());
			$res =& $this->db->execute($prep);
			if (PEAR::isError($res)) throw new Exception($res->getMessage());
			$sql[] = $this->db->last_query;
		}
		catch (Exception $e) { return array(); }
		return $sql;
	}
	
	/**
	 * The TODO List for publication matching is composed of groups of like-named
	 * authors. This function retreives all of these groups.
	 * @return array each element of the array is an array of REDCap projects.
	 * NOTE: that this array could be a mixture of redcap_projects and
	 * redcap_projects_external.
	 */
	function getPubProjGroupTodos() {
		global $pub_matching_experimental;
		// apparently MySQL returns NULL for comparison: NULL <> 1
		// hence the need for the redundant IS NULL in the query below
		$where = "WHERE ((CHAR_LENGTH(TRIM(project_pi_firstname)) = 0 OR project_pi_firstname IS NULL) OR
					(CHAR_LENGTH(TRIM(project_pi_lastname)) = 0 OR project_pi_lastname IS NULL) OR
					(CHAR_LENGTH(TRIM(project_pi_email)) = 0 OR project_pi_email IS NULL) OR
					project_pi_pub_exclude IS NULL) AND
				(project_pi_pub_exclude IS NULL OR project_pi_pub_exclude <> 1)";
		$query = "(SELECT project_id, app_title, project_pi_lastname, project_pi_firstname,
				project_pi_mi, project_pi_alias, project_pi_email, project_pi_pub_exclude,
				project_pub_matching_institution, 0 AS isExternal, NULL AS custom_type
			FROM redcap_projects
			$where
				AND purpose = " . self::PURPOSE_RESEARCH . " AND status > 0)
			UNION
			(SELECT project_id, app_title, project_pi_lastname, project_pi_firstname,
				project_pi_mi, project_pi_alias, project_pi_email, project_pi_pub_exclude,
				project_pub_matching_institution, 1 AS isExternal, custom_type
			FROM redcap_projects_external
			$where
				" . ($pub_matching_experimental ? '' : ' AND 1=0') . ")";
		$objs = $this->getObjects($query);
		// make a basic attempt to group similar PI names together
		$nameMap = array();
		$emptyMap = array(); // so we can put blank last names at the end
		foreach ($objs as $obj) {
			$lname = strtolower(trim($obj->project_pi_lastname));
			$fname = strtolower(trim($obj->project_pi_firstname));
			$nameKey = "$lname, $fname";
			if (strlen($lname) === 0) {
				if (empty($emptyMap[$nameKey])) $emptyMap[$nameKey] = array();
				$emptyMap[$nameKey][] = $obj;
			}
			else {
				if (empty($nameMap[$nameKey])) $nameMap[$nameKey] = array();
				$nameMap[$nameKey][] = $obj;
			}
		}
		ksort($nameMap, SORT_STRING);
		krsort($emptyMap, SORT_STRING);
		$groups = array_values($nameMap);
		$groups = array_merge($groups, $emptyMap);
		return $groups;
	}
	
	/**
	 * Returns an array of strings used to search for PI by name in an autocomplete box.
	 * NOTE: will only select research projects in production.
	 * Example full format: Davis, Ross P. (Davis, RP) [ross.davis@vanderbilt.edu]
	 * They keys of the array look like: "Last%First%MI%Alias%Email"
	 * @param boolean $includeUsers true to also include strings from the user
	 * info table.
	 */
	function getPISearchStrings($includeUsers=true) {
		global $pub_matching_experimental;
		$query = "SELECT DISTINCT project_pi_lastname, project_pi_firstname, project_pi_mi, project_pi_alias, project_pi_email
			FROM redcap_projects
			WHERE purpose = " . self::PURPOSE_RESEARCH . " AND status > 0
			UNION
			SELECT DISTINCT project_pi_lastname, project_pi_firstname, project_pi_mi, project_pi_alias, project_pi_email
			FROM redcap_projects_external
			WHERE " . ($pub_matching_experimental ? '1=1' : '1=0');
		if ($includeUsers) {
			$query .= " UNION
				SELECT DISTINCT user_lastname, user_firstname, '', '', user_email
				FROM redcap_user_information";
		}
		$objs = $this->getObjects($query);
		$strings = array();
		foreach ($objs as $obj) {
			$lname = trim($obj->project_pi_lastname);
			$fname = trim($obj->project_pi_firstname);
			$mi = trim($obj->project_pi_mi);
			$alias = trim($obj->project_pi_alias);
			$email = trim($obj->project_pi_email);
			$strings[implode('%', array($lname, $fname, $mi, $alias, $email))] = true;
		}
		ksort($strings, SORT_STRING);
		return array_keys($strings);
	}
	
	/** 
	 * Search projects using PI name data. empty() params will be ignored.
	 * NOTE: will only select research projects in production.
	 */
	function searchProjectsByPI($lname, $fname, $mi, $alias, $email, $and=true) {
		global $pub_matching_experimental;
		$likes = array(); $params = array();
		if (!empty($lname)) {
			$likes[] = "project_pi_lastname LIKE ?";
			$params[] = $lname;
		}
		if (!empty($fname)) {
			$likes[] = "project_pi_firstname LIKE ?";
			$params[] = $fname;
		}
		if (!empty($mi)) {
			$likes[] = "project_pi_mi LIKE ?";
			$params[] = $mi;
		}
		if (!empty($alias)) {
			$likes[] = "project_pi_alias LIKE ?";
			$params[] = $alias;
		}
		if (!empty($email)) {
			$likes[] = "project_pi_email LIKE ?";
			$params[] = $email;
		}
		// do it twice b/c of the union
		$params = array_merge($params, $params);
		if (!count($likes)) return array();
		$query = "(SELECT project_id, app_title, project_pi_lastname, project_pi_firstname,
				project_pi_mi, project_pi_alias, project_pi_email, project_pi_pub_exclude,
				project_pub_matching_institution, 0 AS isExternal, NULL AS custom_type
			FROM redcap_projects
			WHERE ";
		$query .= '(' . implode(($and ? ' AND ' : ' OR '), $likes) . ') ';
		$query .= "AND purpose = " . self::PURPOSE_RESEARCH . " AND status > 0" . ') ';
		$query .= 'UNION ';
		$query .= "(SELECT project_id, app_title, project_pi_lastname, project_pi_firstname,
				project_pi_mi, project_pi_alias, project_pi_email, project_pi_pub_exclude,
				project_pub_matching_institution, 1 AS isExternal, custom_type
			FROM redcap_projects_external
			WHERE ";
		$query .= '(' . implode(($and ? ' AND ' : ' OR '), $likes) . ') ';
		$query .= "AND " . ($pub_matching_experimental ? '1=1' : '1=0') . ') ';
		$query .= " ORDER BY project_pi_lastname, project_pi_firstname, project_pi_mi, project_pi_alias, project_pi_email";
		return $this->getObjects($query, $params);
	}
	
	/**
	 * Updates the last crawl time of a publication source with the current time.
	 * @param int $pubsrc_id see self:PUBSRC_*
	 * @return array the SQL statement(s) that were executed. The array will be empty if error.
	 */
	function updatePubCrawlTime($pubsrc_id) {
		$fieldvals = array('pubsrc_last_crawl_time' => NOW);
		return $this->save('redcap_pub_sources', $fieldvals, 'pubsrc_id=' . intval($pubsrc_id));
	}
	
	/**
	 * Enables/disables the PubMed cron job in the crons table.
	 */
	function enablePubCrawlCron($enabled) {
		$enabled_val = ($enabled) ? "ENABLED" : "DISABLED";
		$q = $this->save('redcap_crons', array('cron_enabled' => $enabled_val), "cron_name='PubMed'");
	}
	
	/////////////////////////////////////////////////////////////////////////////
	//
	// User and user rights functions
	//
	/////////////////////////////////////////////////////////////////////////////
	
	/** Returns true if the username exists, false if not. */
	function usernameExists($username) {
		$sql = "SELECT COUNT(username) AS UserCount FROM (
			(SELECT username FROM redcap_auth WHERE username = ?) UNION
			(SELECT username FROM redcap_user_information WHERE username = ?)) AS x";
		$objs = $this->getObjects($sql, array($username, $username));
		return $objs[0]->UserCount > 0 ? true : false;
	}
	
	/**
	 * Save a user; INSERT if $ui_id is empty, UPDATE otherwise.
	 * @return array the SQL statement(s) that were executed. The array will be empty if error.
	 */
	function saveUser($ui_id, $username, $fname, $lname, $email, $email2, $email3, $inst_id, $allow_create_db, $pass='') {
		$sql = array();
		try {
			if (empty($ui_id)) { // insert
				$sql[] = $this->save('redcap_auth', array('username' => $username, 'password' => md5($pass),
						'temp_pwd' => 1));
				$sql[] = $this->save('redcap_auth_history', array('username' => $username, 'password' => md5($pass),
						'timestamp' => NOW));
				$sql[] = $this->save('redcap_user_information', array('username' => $username, 'user_email' => $email, 'user_email2' => $email2, 'user_email3' => $email3,
						'user_firstname' => $fname, 'user_lastname' => $lname, 'user_inst_id' => $inst_id,
						'allow_create_db' => $allow_create_db));
			}
			else { // update
				$sql[] = $this->save('redcap_user_information', array('user_email' => $email, 'user_email2' => $email2, 'user_email3' => $email3,
						'user_firstname' => $fname, 'user_lastname' => $lname, 'user_inst_id' => $inst_id,
						'allow_create_db' => $allow_create_db), 'ui_id=' . intval($ui_id));
			}
		}
		catch (Exception $e) { return array(); }
		return $sql;
	}
	
	/** Gets user information given the user ID. */
	function getUserInfo($ui_id) {
		$sql = "SELECT * FROM redcap_user_information WHERE ui_id = ?";
		$objs = $this->getObjects($sql, array($ui_id));
		return $objs[0];
	}
	
	/** Gets user information given the username. */
	function getUserInfoByUsername($username) {
		$sql = "SELECT * FROM redcap_user_information WHERE username = ?";
		$objs = $this->getObjects($sql, array($username));
		return $objs[0];
	}
	
	/** Gets user information for each given username. Output is keyed by username. */
	function getUserInfoByUsernames($usernames) {
		if (count($usernames) == 0) return array();
		$names = array();
		foreach ($usernames as $username)
			$names[] = "'" . db_real_escape_string($username) . "'";
		$sql = "SELECT * FROM redcap_user_information WHERE username IN (" .
						implode(',', $names) . ")";
		$objs = $this->getObjects($sql);
		$tmp = array();
		foreach ($objs as $obj) $tmp[$obj->username] = $obj;
		return $tmp;
	}
	
	/** Gets the rights of a given user for a given project. */
	function getRights($username, $project_id) {
		$sql = "SELECT r.* FROM redcap_user_rights r
			WHERE username = ? AND project_id = ?";
		$objs =  $this->getObjects($sql, array($username, $project_id));
		return $objs[0];
	}
	
	/** Gets all user rights records given the username (orders by and includes the app_title). */
	function getUserRights($username) {
		if ($username == '') return array();
		$sql = "SELECT r.*, p.app_title
			FROM redcap_user_rights r JOIN redcap_projects p ON (r.project_id=p.project_id)
			WHERE username = ?
			ORDER BY app_title";
		return $this->getObjects($sql, array($username));
	}
	
	/** Gets all user rights records given the project_id (orders by username). */
	function getProjectRights($project_id) {
		if ($project_id == '') return array();
		$sql = "SELECT r.*, p.app_title
			FROM redcap_user_rights r JOIN redcap_projects p ON (r.project_id=p.project_id)
			WHERE r.project_id = ?
			ORDER BY username";
		return $this->getObjects($sql, array($project_id));
	}
	
	/** Gets usernames of all users who have rights to at least one project. */
	function getUsernamesWithProjects() {
		$sql = "SELECT DISTINCT username FROM redcap_user_rights WHERE trim(username) != ''";
		return $this->getObjects($sql, array());
	}
}
