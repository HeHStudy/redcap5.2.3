<?php

// Default
$response = "0";




## If calling this file for a project, return the document space usage, number of records, and most recent logged event
if (isset($_GET['pid']) && is_numeric($_GET['pid'])) 
{
	require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
	// Count file upload usage (edocs and docs, including data export saves)
	$sql = "select round(sum(doc_size)/1024/1024,2) from redcap_edocs_metadata where stored_date > '$creation_time'
			and delete_date is null and project_id = " . PROJECT_ID;
	$edoc_usage = db_result(db_query($sql),0);
	$sql = "select round(sum(docs_size)/1024/1024,2) from redcap_docs where docs_date > '".substr($creation_time, 0, 10)."' 
			and docs_file is not null and project_id = " . PROJECT_ID;
	$doc_usage  = db_result(db_query($sql),0);
	$file_space_usage = $edoc_usage + $doc_usage;
	// Get most recent logged event
	$sql = "select timestamp(ts) from redcap_log_event where ts > ".str_replace(array("-"," ",":"), array("","",""), $creation_time)."
			and project_id = " . PROJECT_ID . " order by log_event_id desc limit 1";
	$most_recent_event = format_ts_mysql(db_result(db_query($sql), 0));
	// Count project records
	$num_records = db_result(db_query("select count(distinct(record)) from redcap_data where project_id = " . PROJECT_ID . " and field_name = '$table_pk'"),0);
	// Get extra record count in user's data access group, if they are in one
	if ($user_rights['group_id'] != "") 
	{
		$sql  = "select count(distinct(record)) from redcap_data where project_id = " . PROJECT_ID . " and field_name = '$table_pk'"
			  . " and record in (" . pre_query("select record from redcap_data where project_id = " . PROJECT_ID 
			  . " and field_name = '__GROUPID__' and value = '{$user_rights['group_id']}'") . ")";
		$num_records_group = db_result(db_query($sql),0);
		$num_records = "{$lang['data_entry_103']} $num_records / {$lang['data_entry_104']} $num_records_group";
	}
	// Send response delimited with line breaks
	$response = str_replace(',', '&#44;', number_format($num_records)) . "\n$most_recent_event\n$file_space_usage MB";
} 




## If calling this file on the My Projects page (or equivalent page in Control Center), 
## then return the number of records and fields for all projects requested
elseif (isset($_POST['pids']))
{
	require_once dirname(dirname(__FILE__)) . '/Config/init_global.php';
	// Parse and validate the project_id's sent
	$pids = explode(",", $_POST['pids']);
	// Make sure uiid's are numeric first
	foreach ($pids as $key=>$this_pid) 
	{
		if (!is_numeric($this_pid)) {
			// Remove pid from original if not numeric
			unset($pids[$key]);
		}
	}
	// If user is not a super user, then make sure they have access to all projects for the project_id's given (for security reasons)
	if (!$super_user)
	{
		$sql = "select project_id from redcap_user_rights where username = '" . prep($userid) . "' 
				and project_id in (" . implode(", ", $pids) . ")";
		$q = db_query($sql);
		// Reset $pids and re-fill with valid project_id's
		$pids = array();
		while ($row = db_fetch_assoc($q))
		{
			$pids[] = $row['project_id'];
		}
	}
	// If there are no project_ids to report, then return failure value
	if (empty($pids)) exit("0");
	// Initialize arrays
	$pid_counts  = array();
	$pid_counts2 = array();
	// Get record count for each project
	$sql = "select m.project_id, count(distinct(d.record)) as count from redcap_data d, redcap_metadata m 
			where d.project_id = m.project_id and m.field_name = d.field_name
			and m.field_order = 1 and m.project_id in (" . implode(", ", $pids) . ") 
			group by m.project_id order by m.project_id";
	$q = db_query($sql);
	while($row = db_fetch_assoc($q)) 
	{			
		$pid_counts[$row['project_id']]['records'] = str_replace(',', '&#44;', number_format($row['count']));
	}
	//Get field count for each project
	$sql = "select project_id, count(field_name) as count from redcap_metadata
			where project_id in (" . implode(", ", $pids) . ") and element_type != 'descriptive' 
			group by project_id order by project_id";
	$q = db_query($sql);
	while($row = db_fetch_assoc($q)) 
	{			
		$pid_counts[$row['project_id']]['fields'] = str_replace(',', '&#44;', number_format($row['count']));
	}
	// If we have some counts to return
	if (!empty($pid_counts))
	{
		foreach ($pid_counts as $this_pid=>$attr)
		{
			if (!isset($attr['records'])) $attr['records'] = 0;
			if (!isset($attr['fields']))  $attr['fields']  = 0;
			$pid_counts2[] = "$this_pid:{$attr['records']}:{$attr['fields']}";
		}
		$response = implode(",", $pid_counts2);
	}
}




// Return the response
print $response;
