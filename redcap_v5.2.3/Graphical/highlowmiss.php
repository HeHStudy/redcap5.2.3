<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

include_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
include_once APP_PATH_DOCROOT . 'Graphical/functions.php';

# Validate form and field names
$field = $_GET['field'];
if (!isset($Proj->metadata[$field])) {
	header("HTTP/1.0 503 Internal Server Error");
	return;
}

# Whether or not to reverse the $data
$reverse = false;

# Limit records pulled only to those in user's Data Access Group
$group_sql  = ""; 
if ($user_rights['group_id'] != "") {
	$group_sql  = "and record in (" . pre_query("select record from redcap_data where project_id = $project_id and field_name = '__GROUPID__' and value = '{$user_rights['group_id']}'") . ")"; 
}

# Calculate lowest values
if ($_GET['svc'] == 'low') {
	$sql = "select record, value, event_id from redcap_data where project_id = $project_id and field_name = '$field' 
			and value is not null and value != '' $group_sql order by (value+0) asc limit 5";

# Calculate highest Values
} elseif ($_GET['svc'] == 'high') {
	$sql = "select record, value, event_id from redcap_data where project_id = $project_id and field_name = '$field' 
			and value is not null and value != '' $group_sql order by (value+0) desc limit 5";
	// Set flag to reverse data points for output
	$reverse = true;

# Calculate missing values
} elseif ($_GET['svc'] == 'miss') {
	$sql = "select distinct record, event_id from redcap_data where project_id = $project_id and field_name = '$table_pk' and 
			concat(event_id,',',record) not in (" . pre_query("select concat(event_id,',',record) from (select distinct event_id, record 
			from redcap_data where value is not null and value != '' and project_id = $project_id and field_name = '$field') 
			as x") . ") $group_sql order by record, event_id";
}


// Execute query to retrieve response
$i = 0;
$data = array();
$res = db_query($sql);
if ($res) {
	// Special conditions apply for missing values in a longitudinal project. 
	// Make sure the event_id here is in the events_forms table (i.e. that the form is even used by that event).
	if ($_GET['svc'] == 'miss' && $longitudinal) 
	{
		$sql = "select m.event_id from redcap_events_arms a, redcap_events_metadata m, redcap_events_forms f, redcap_metadata x 
				where a.project_id = $project_id and x.field_name = '$field' and x.project_id = a.project_id and a.arm_id = m.arm_id 
				and m.event_id = f.event_id and f.form_name = x.form_name";
		$q = db_query($sql);
		// Get event_ids where this form is used
		$eventids = array();
		while ($row = db_fetch_assoc($q)) {
			// Save event_id as key for faster checking
			$eventids[$row['event_id']] = "";
		}
		// Loop through data
		while ($ret = db_fetch_assoc($res)) {
			if (isset($eventids[$ret['event_id']])) {
				// Only add to output if field's form is used for this event
				$data[] = $ret['record'] . ":" . $ret['event_id'];
				// Response count
				$i++;
			}
		}
	} 	
	// Loop through data normally	
	else 
	{
		while ($ret = db_fetch_array($res, MYSQL_NUM)) {
			$data[] = implode(':', $ret);
			// Response count
			$i++;
		}
	}
	// Reverse order of data points, if set
	if ($reverse) 
	{
		$data = array_reverse($data);
	}
}

// Output response
header('Content-type: text/plain');
print $i . '|' . implode('|', $data);
