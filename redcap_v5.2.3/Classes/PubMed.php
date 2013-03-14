<?php

/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

 /**
  * This class serves as an interface to E-utilities used to access PubMed.
  * 
  * @see http://www.ncbi.nlm.nih.gov/books/NBK25501/
  */
 class PubMed {
 
	/**
	 * PubMed requests that users rate-limit their queries to avoid overloading
	 * their servers.
	 * @see http://www.ncbi.nlm.nih.gov/books/NBK25497/
	 */
	const QUERIES_PER_SEC = 3;
	public static $prev_query_microtime = 0;
	
	/**
	 * PubMed requests that users switch to POST requests if asking for over
	 * this many UIDs in a query.
	 * @see http://www.ncbi.nlm.nih.gov/books/NBK25499/#chapter4.EFetch
	 */
	const MAX_GET_UIDS = 200;
	 
  public $pubmed_ids;
  public $article_title;
  public $abstract;
  public $authors;
	public $volume;
	public $issue;
	public $pages;
	public $journal;
	public $journal_abbrev;
  public $grant;
  public $mesh;
  public $epub_date;
  public $pub_date;
 
  private $institutions;
  private $principle_investigators;
 
  public function __construct() {
		$this->init();
    $this->institutions = array();
    $this->principle_investigators = array(); 
  }
  
  public function init() {
    $this->pubmed_ids = array();
    $this->article_title = NULL;
    $this->abstract = NULL;
		$this->volume = NULL;
		$this->issue = NULL;
		$this->pages = NULL;
		$this->journal = NULL;
		$this->journal_abbrev = NULL;
    $this->authors = array();
    $this->grant = array();
    $this->mesh = array();
  }
  
	/**
	 * Do our best to extract a reasonable date out of data contained in an XML object.
	 * @param SimpleXMLElement $xml the parent element containing the date components.
	 * @return string the YYYY-MM-DD date, or null if no date could be determined.
	 */
	public static function extractDate($xml) {
		if (!is_object($xml)) return null;
		// we'll require a year at the minimum
		$year = null;
		if (!empty($xml->Year)) $year = intval((string)$xml->Year);
		elseif (!empty($xml->MedlineDate)) {
			// if we don't have date fields, then sometimes we have "MedlineDate"
			// which can be formatted like "2011 Sep-Oct"
			if (preg_match('/(\d{4})(\s+[a-z]{3}[a-z]*)?/i', (string)$xml->MedlineDate, $matches)) {
				$year = $matches[1];
				$month = 1; // default to January
				if (!empty($matches[2])) {
					$month = date('n', strtotime("$year-$monStr-01"));
				}
				$day = 1;
				if (!checkdate($month, $day, $year)) return null; // ignore bad dates
				return "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' .
					str_pad($day, 2, '0', STR_PAD_LEFT);
			}
			return null;
		}
		else return null;
		if ($year < 100) return null; // ignore Y2K bugs
		$month = 1; // default to January
		if (!empty($xml->Month)) {
			$monStr = (string)$xml->Month;
			// month is sometimes represented as a number and sometimes as text
			if (preg_match('/^\d+$/', $monStr)) $month = intval($monStr);
			elseif (preg_match('/^[a-z]{3}[a-z]*$/i')) {
				$month = date('n', strtotime("$year-$monStr-01"));
			}
		}
		$day = empty($xml->Day) ? 1 : intval((string)$xml->Day);
		if (!checkdate($month, $day, $year)) return null; // ignore bad dates
		return "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' .
			str_pad($day, 2, '0', STR_PAD_LEFT);
	}
	
	/**
	 * All PubMed queries should utilize this function; it enforces PubMed's
	 * wish for rate-limiting requests.
	 * @param string $url the request.
	 * @return mixed the output of simplexml_load_file().
	 */
	public static function request($url) {
		// perform the rate-limiting
		$diff = microtime(true) - self::$prev_query_microtime;
		$microtimePerQuery = 1.0 / self::QUERIES_PER_SEC;
		if ($diff < $microtimePerQuery) {
			usleep(($microtimePerQuery - $diff) / 1.0e6);
		}
		self::$prev_query_microtime = microtime(true);
		return simplexml_load_file($url);
	}
	
  public function search($start_date,$end_date,$max_results,$terms=NULL) {
    $url = 'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=pubmed&retmax='.$max_results.'&mindate='.$start_date.'&maxdate='.$end_date.'&usehistory=y&term=' . urlencode($terms);
    if ($xml = (object)self::request($url)){
      if (is_object($xml->IdList->Id)) {
        foreach($xml->IdList->Id AS $number) {
          $this->pubmed_ids[] = (int)$number[0];
        }
      } else {
        $this->pubmed_ids[] = $xml->IdList[0];
      }
    }
  }
  
	/**
	 * Performs an EFetch to retrieve publication details given PubMed IDs.
	 * @param array $pmids the PubMed IDs to fetch details for.
	 * @return array PubMed objects keyed by PMID.
	 */
  public static function get_publication_details($pmids) {
		$pms = array();
		// query in slices that adhere to PubMed limits
		$start = 0;
		while ($start < count($pmids)) {
			$slice = array_slice($pmids, $start, self::MAX_GET_UIDS);
			$start += count($slice);
			$url = "http://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=pubmed&id=" .
				implode(',', $slice) . "&retmode=xml";
			$xmlSet = self::request($url);
			foreach ($xmlSet->PubmedArticle as $xml) {
				$pm = new PubMed();
				$pm->pubmed_id = (string)$xml->MedlineCitation->PMID;
				$pm->article_title = (string)$xml->MedlineCitation->Article->ArticleTitle;
				$pm->abstract = (string)$xml->MedlineCitation->Article->Abstract->AbstractText;
				foreach($xml->MedlineCitation->Article->AuthorList->Author as $author) {
					$pm->authors[] = array(
						'first_name' => (string)$author->ForeName,
						'last_name' => (string)$author->LastName,
						'initials' => (string)$author->Initials,
					);
				}
				if (is_object($xml->MedlineCitation->MeshHeadingList)) {
					foreach($xml->MedlineCitation->MeshHeadingList->MeshHeading AS $mesh) {
						$qualifier = (isset($mesh->QualifierName)) ? (string)$mesh->QualifierName : NULL;
						$pm->mesh[] = array(
							'descriptor' => (string)$mesh->DescriptorName,
							'qualifier' => $qualifier,
						);
					}
				}
				if (is_object($xml->MedlineCitation->Article->GrantList->Grant)) {
					$pm->grant = array(
						'grant_id' => (string)$xml->MedlineCitation->Article->GrantList->Grant->GrantID,
						'acronym' => (string)$xml->MedlineCitation->Article->GrantList->Grant->Acronym,
						'agency' => (string)$xml->MedlineCitation->Article->GrantList->Grant->Agency,
						'country' => (string)$xml->MedlineCitation->Article->GrantList->Grant->Country,
					);
				}
				$pm->epub_date = self::extractDate($xml->MedlineCitation->Article->ArticleDate);
				$pm->pub_date = self::extractDate($xml->MedlineCitation->Article->Journal->JournalIssue->PubDate);
				if (!empty($xml->MedlineCitation->Article->Journal)) {
					$journal = $xml->MedlineCitation->Article->Journal;
					if (!empty($journal->JournalIssue->Volume))
						$pm->volume = (string)$journal->JournalIssue->Volume;
					if (!empty($journal->JournalIssue->Issue))
						$pm->issue = (string)$journal->JournalIssue->Issue;
					if (!empty($journal->Title))
						$pm->journal = (string)$journal->Title;
					if (!empty($journal->ISOAbbreviation))
						$pm->journal_abbrev = (string)$journal->ISOAbbreviation;
				}
				if (!empty($xml->MedlineCitation->Article->Pagination->MedlinePgn)) {
					$pm->pages = (string)$xml->MedlineCitation->Article->Pagination->MedlinePgn;
				}
				$pms[$pm->pubmed_id] = $pm;
			}
		}
		return $pms;
	}

  private function my_xml2array($__url) { 
    $xml_values = array(); 
    $contents = file_get_contents($__url); 
    $parser = xml_parser_create(''); 
    if(!$parser) 
        return false; 

    xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, 'UTF-8'); 
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0); 
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1); 
    xml_parse_into_struct($parser, trim($contents), $xml_values); 
    xml_parser_free($parser); 
    if (!$xml_values) 
        return array(); 
    
    $xml_array = array(); 
    $last_tag_ar =& $xml_array; 
    $parents = array(); 
    $last_counter_in_tag = array(1=>0); 
    foreach ($xml_values as $data) 
    { 
        switch($data['type']) 
        { 
            case 'open': 
                $last_counter_in_tag[$data['level']+1] = 0; 
                $new_tag = array('name' => $data['tag']); 
                if(isset($data['attributes'])) 
                    $new_tag['attributes'] = $data['attributes']; 
                if(isset($data['value']) && trim($data['value'])) 
                    $new_tag['value'] = trim($data['value']); 
                $last_tag_ar[$last_counter_in_tag[$data['level']]] = $new_tag; 
                $parents[$data['level']] =& $last_tag_ar; 
                $last_tag_ar =& $last_tag_ar[$last_counter_in_tag[$data['level']]++]; 
                break; 
            case 'complete': 
                $new_tag = array('name' => $data['tag']); 
                if(isset($data['attributes'])) 
                    $new_tag['attributes'] = $data['attributes']; 
                if(isset($data['value']) && trim($data['value'])) 
                    $new_tag['value'] = trim($data['value']); 

                $last_count = count($last_tag_ar)-1; 
                $last_tag_ar[$last_counter_in_tag[$data['level']]++] = $new_tag; 
                break; 
            case 'close': 
                $last_tag_ar =& $parents[$data['level']]; 
                break; 
            default: 
                break; 
        }; 
    } 
    return $xml_array; 
  } 
}