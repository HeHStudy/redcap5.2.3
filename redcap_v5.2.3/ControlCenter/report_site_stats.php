<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . "/Config/init_global.php";

// Get server's IP address
$server_ip = getServerIP();

// Set alternative hostname if we know the domain name in the URL is internal (i.e. without dots)
$alt_hostname = (strpos(SERVER_NAME, ".") === false) ? SERVER_NAME : "";





/**
 * SEND BASIC STATS
 */

// Skip this section if sendinging stats manually (page is only needed for sending library via Post)
if ($auto_report_stats) 
{
	// Get project count
	$num_prototypes = 0;
	$num_production = 0;
	$num_inactive   = 0;
	$num_archived   = 0;
	$q = db_query("select status, count(status) as count from redcap_projects where count_project = 1 and (purpose is null or purpose > 0) group by status");
	while ($row = db_fetch_assoc($q)) {
		if ($row['status'] == '0') $num_prototypes = $row['count'];
		if ($row['status'] == '1') $num_production = $row['count'];
		if ($row['status'] == '2') $num_inactive   = $row['count'];
		if ($row['status'] == '3') $num_archived   = $row['count'];
	}
	
	// Get counts of project purposes
	$purpose_other = 0;
	$purpose_research = 0; 
	$purpose_qualimprove = 0;
	$purpose_operational = 0; 
	$q = db_query("select purpose, count(purpose) as count from redcap_projects where count_project = 1 and (purpose is null or purpose > 0) group by purpose");
	while ($row = db_fetch_array($q)) 
	{
		switch ($row['purpose']) 
		{
			case '1': $purpose_other = $row['count']; break;
			case '2': $purpose_research = $row['count']; break;
			case '3': $purpose_qualimprove = $row['count']; break;
			case '4': $purpose_operational = $row['count']; break;
		}	
	}
	
	// DTS: Get count of production projects utilizing DTS
	$dts_count = 0;
	if ($dts_enabled_global)
	{
		$q = db_query("select count(1) from redcap_projects where status > 0 and count_project = 1 and (purpose is null or purpose > 0) and dts_enabled = 1");
		$dts_count = db_result($q, 0);
	}
	
	// Randomization: Get count of production projects using the randomization module (and have a prod alloc table uploaded)
	$rand_count = Stats::randomizationCount();

	// Get user count
	$num_users = db_result(db_query("select count(1) from redcap_user_information"), 0);

	// Send site stats to the REDCap Consortium and get response back
	$url = CONSORTIUM_WEBSITE."collect_stats.php?hostname=".SERVER_NAME."&ip=$server_ip"
		 . "&alt_hostname=$alt_hostname&num_prots=$num_prototypes&num_prods=$num_production&num_archived=$num_archived&rnd982g4078393ae839z1_auto"
		 . "&purposes=$purpose_other,$purpose_research,$purpose_qualimprove,$purpose_operational"
		 . "&num_inactive=$num_inactive&num_users=$num_users&auth_meth=$auth_meth&version=$redcap_version"
		 . "&activeusers1m=".getActiveUsers(30)."&activeusers6m=".getActiveUsers(183)."&activeuserstotal=".getActiveUsers()
		 . "&hostlabel=" . urlencode($institution)
		 . "&homepage_contact=".urlencode($homepage_contact)."&homepage_contact_email=$homepage_contact_email"
		 . "&dts=$dts_count&rand=$rand_count"
		 . "&full_url=".urlencode(APP_PATH_WEBROOT_FULL)."&site_org_type=".urlencode($site_org_type);
	$response = http_get($url);

	// If stats were accepted from approved site, change date for stats last sent in config table
	if ($response == "1") {
		db_query("update redcap_config set value = '" . date("Y-m-d") . "' where field_name = 'auto_report_stats_last_sent'");
	}

	// In order to continue to library stats reporting, make sure cURL is installed and that Library usage is enabled 
	// and that $response above was successful (1).
	if ((!$shared_library_enabled && !$pub_matching_enabled) || $response == "0") {
		exit($response);
	}

}




// SEND LIBRARY STATS (as separate Post request)
$libresponse = "1";
if ($shared_library_enabled) {
	$libresponse = Stats::sendSharedLibraryStats();
	if ($libresponse == "" || $libresponse === false) $libresponse = "0";
}

// SEND PUB MATCHING STATS (as separate Post request)
$pubstats_response = "1";
if ($pub_matching_enabled) {
	$pubstats_response = Stats::sendPubMatchList();
	if ($pubstats_response == "" || $pubstats_response === false) $pubstats_response = "0";
}

// Return response if called asynchronously, else redirect to Control Center
if ($auto_report_stats) {
	print ($libresponse && $pubstats_response) ? "1" : "0";
} else {
	redirect(APP_PATH_WEBROOT . "ControlCenter/index.php?" . $_SERVER['QUERY_STRING']);
}
