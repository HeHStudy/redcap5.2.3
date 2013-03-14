<?php

/*****************************************************************************************
**  REDCap is only available through ACADMEMIC USER LICENSE with Vanderbilt University
******************************************************************************************/

/**
 * Stats is a class of functions used to output general dashboard stats for the REDCap system
 */
class Stats 
{
	// Store comma-delimited list of project_id's that should be ignored in some counts 
	// (not counted or 'just for fun' purpose or 'deleted' projects)
	public $ignored_project_ids = null;
	
	// Get delimited list of project_id's that should be ignored in some counts (not counted or 'just for fun' purpose or 'deleted' projects)
	public function getIgnoredProjectIds()
	{
		if ($this->ignored_project_ids == null) {
			$sql = "select project_id from redcap_projects where purpose = '0' or count_project = 0 or date_deleted is not null";
			$this->ignored_project_ids = pre_query($sql);
		}
		return $this->ignored_project_ids;
	}
	
	// RANDOMIZATION: Get count of production projects using the randomization module (and have a prod alloc table uploaded).
	// Exclude "practice" projects.
	public static function randomizationCount()
	{
		$sql = "select 1 from redcap_projects p, redcap_randomization r, redcap_randomization_allocation a 
				where p.status > 0 and p.count_project = 1 and (p.purpose is null or p.purpose > 0) 
				and r.project_id = p.project_id and r.rid = a.rid and a.project_status = 1 group by p.project_id";
		$q = db_query($sql);
		return db_num_rows($q);
	}
	
	// PUBLICATION MATCHES: Send to consortium the list of pub IDs that have been matched to REDCap projects
	public static function sendPubMatchList()
	{
		// Set alternative hostname if we know the domain name in the URL is internal (i.e. without dots)
		$alt_hostname = (strpos(SERVER_NAME, ".") === false) ? SERVER_NAME : "";
		// Set URL to call
		$url = CONSORTIUM_WEBSITE . "collect_stats_pubs.php?rnd982g45av390p9&app=0&hostname=".SERVER_NAME."&ip=".getServerIP()."&alt_hostname=$alt_hostname";
		// Query table to get matches
		$sql = "select distinct s.pubsrc_name, a.pub_id from 
				redcap_pub_matches m, redcap_pub_articles a, redcap_pub_sources s 
				where m.matched = 1 and m.article_id = a.article_id 
				and a.pubsrc_id = s.pubsrc_id order by s.pubsrc_name, a.pub_id";
		$q = db_query($sql);
		$pubsrc_matches = array();
		while ($row = db_fetch_assoc($q)) 
		{
			$pubsrc_matches[$row['pubsrc_name']][] = $row['pub_id'];
		}
		// Convert sub-array into comma delimited string for each pub src
		foreach ($pubsrc_matches as $src=>$pubids)
		{
			$pubsrc_matches[$src] = implode(",", $pubids);
		}	
		// Send stats via Post request
		$pubstats_response = http_post($url, $pubsrc_matches);
		// Return response status
		return $pubstats_response;
	}
	
	// SEND SHARED LIBRARY STATS: Obtain local library stats to send to consortium
	public static function sendSharedLibraryStats()
	{
		// Set alternative hostname if we know the domain name in the URL is internal (i.e. without dots)
		$alt_hostname = (strpos(SERVER_NAME, ".") === false) ? SERVER_NAME : "";
		// Set URL to call
		$url = CONSORTIUM_WEBSITE . "collect_stats_library.php?rnd982g45av390r1&app=0&hostname=".SERVER_NAME."&ip=".getServerIP()."&alt_hostname=$alt_hostname";
		// Initialize vars
		$params = array("total"=>array("dev_up"=>0, "dev_down"=>0, "prod_up"=>0, "prod_down"=>0));
		// Uploads for dev projects
		$sql = "select l.library_id, count(1) as count from redcap_library_map l, redcap_projects p where p.project_id = l.project_id 
				and p.status = 0 and l.type = 2 group by l.library_id";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q)) {
			$params[$row['library_id']]['dev_up'] = $row['count'];
			$params['total']['dev_up'] += $row['count'];
		}
		// Downloads for dev projects
		$sql = "select l.library_id, count(1) as count from redcap_library_map l, redcap_projects p where p.project_id = l.project_id 
				and p.status = 0 and l.type = 1 group by l.library_id";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q)) {
			$params[$row['library_id']]['dev_down'] = $row['count'];
			$params['total']['dev_down'] += $row['count'];
		}
		// Uploads for prod projects
		$sql = "select l.library_id, count(1) as count from redcap_library_map l, redcap_projects p where p.project_id = l.project_id 
				and p.status = 1 and l.type = 2 group by l.library_id";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q)) {
			$params[$row['library_id']]['prod_up'] = $row['count'];
			$params['total']['prod_up'] += $row['count'];
		}
		// Downloads for prod projects
		$sql = "select l.library_id, count(1) as count from redcap_library_map l, redcap_projects p where p.project_id = l.project_id 
				and p.status = 1 and l.type = 1 group by l.library_id order by l.library_id";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q)) {
			$params[$row['library_id']]['prod_down'] = $row['count'];
			$params['total']['prod_down'] += $row['count'];
		}
		// Convert array to string for passing below
		$params2 = array();
		foreach ($params as $lib_id=>$values) 
		{
			$params2[$lib_id] = (isset($values['dev_up'])    ? $values['dev_up']    : "0") . ","
							  . (isset($values['dev_down'])  ? $values['dev_down']  : "0") . ","
							  . (isset($values['prod_up'])   ? $values['prod_up']   : "0") . ","
							  . (isset($values['prod_down']) ? $values['prod_down'] : "0");
		}
		// Send stats via Post request
		return http_post($url, $params2);
	}
	
}