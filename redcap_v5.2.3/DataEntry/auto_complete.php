<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require dirname(dirname(__FILE__)) . '/Config/init_project.php';

//Retrieve matching records to populate auto-complete box
if (isset($_GET['query'])) {
	
	$queryString = prep(urldecode($_GET['query']));
	
	// Retrieve record list (exclude non-DAG records if user is in a DAG)
	$group_sql = ""; 
	if ($user_rights['group_id'] != "") {
		$group_sql = "and record in (" . pre_query("select record from redcap_data where project_id = $project_id and field_name = '__GROUPID__'
					  and value = '" . $user_rights['group_id'] . "'") . ")"; 
	}	
	
	//Double data entry as DDE person
	if ($double_data_entry && $user_rights['double_data'] != "0") {
		$sql = "select distinct substring(record,1,locate('--',record)-1) as record from redcap_data where project_id = $project_id and 
				record like '$queryString%--{$user_rights['double_data']}' and field_name = '$table_pk' and event_id in 
				(" . pre_query("select m.event_id from redcap_events_metadata m, redcap_events_arms a 
				where a.project_id = $project_id and a.arm_num = {$_GET['arm']} and a.arm_id = m.arm_id") . ")
				$group_sql order by abs(record), record limit 0,15";
	//Normal project
	} else {
		$sql = "select distinct record from redcap_data where project_id = $project_id and record like '$queryString%' 
				and field_name = '$table_pk' and event_id in (" . pre_query("select m.event_id from redcap_events_metadata m, redcap_events_arms a 
				where a.project_id = $project_id and a.arm_num = {$_GET['arm']} and a.arm_id = m.arm_id") . ") 
				$group_sql order by abs(record), record limit 0,15";
	}
	
	//Execute query
	$q = db_query($sql);
	$rowcount = db_num_rows($q);
	$recs = array();
	if ($q && $rowcount > 0) {
		// Retrieve all matches
		while ($result = db_fetch_assoc($q)) {
			$recs[] = str_replace("'", '\'', $result['record']);
		}
	}		
	//Render JSON
	print "{query:'$queryString',suggestions:['" . implode("','", $recs) . "']}";
	
} else {
	
	// User should not be here! Redirect to index page.
	redirect(APP_PATH_WEBROOT . "index.php?pid=$project_id");
	
}
