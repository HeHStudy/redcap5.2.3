<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

include_once dirname(dirname(__FILE__)) . '/Config/init_project.php';


$response			= 1; //default successful response
$action	  	   		= $_GET['action'];
$id 				= $_GET['id'];
$do_all_timepoints 	= $longitudinal ? $_GET['grid'] : 0;
$event_id	   		= $_GET['event_id'];
$arm = !is_numeric($_GET['arm']) ? 0 : $_GET['arm'];

// Set up logging details for this event
$log = "Record: $id\nForm: [all forms]";
if ($longitudinal) {
	$log .= "\nEvent: ";
	if ($arm > 0) {
		// all forms for all events for one arm
		$log .= "[all events" . ($multiple_arms ? (" on {$lang['global_08']} $arm: " . $Proj->events[$arm]['name']) : "") . "]";
	} else {
		// all forms for one event
		$q = db_query("select e.descrip, a.arm_num, a.arm_name from redcap_events_metadata e, redcap_events_arms a where e.arm_id = a.arm_id and e.event_id = " . $_GET['event_id']);
		$log .= html_entity_decode(db_result($q, 0, "descrip"), ENT_QUOTES);
		// Show arm name if multiple arms exist
		if ($multiple_arms) {
			$log .= " - {$lang['global_08']} " . db_result($q, 0, "arm_num") . ": " . html_entity_decode(db_result($q, 0, "arm_name"), ENT_QUOTES);
		}
	}
}

//If we are coming from menu, there will only be one event_id, but if coming from Longitudinal grid, we need to include ALL event_ids
$all_event_ids = ($event_id == "" || !is_numeric($event_id)) ? array_keys($Proj->events[$arm]['events']) : array($event_id);


// LOCK ALL FORMS
if ($action == "lock") 
{	
	// Determine which forms/events have already been locked for this record, and collect in array
	$sql = "select event_id, form_name from redcap_locking_data where project_id = $project_id 
			and event_id in (" . implode(", ", $all_event_ids) . ") and record = '" . prep($id). "'";
	$q = db_query($sql);
	$alreadyLocked = array();
	while ($row = db_fetch_assoc($q)) 
	{
		$alreadyLocked[$row['event_id']][$row['form_name']] = "";
	}
	
	// Determine which forms are designated as NOT lockable
	$sql = "select form_name from redcap_locking_labels where project_id = $project_id and display = 0";
	$q = db_query($sql);
	$notLockable = array();
	while ($row = db_fetch_assoc($q)) 
	{
		$notLockable[$row['form_name']] = "";
	}
	
	// Loop through all forms/events and insert into table if not already in table
	if ($longitudinal) {
		$sql = "select e.event_id, f.form_name from redcap_events_forms f, redcap_events_metadata e, redcap_events_arms a 
				where a.project_id = $project_id and a.arm_id = e.arm_id and e.event_id = f.event_id 
				and e.event_id in (" . implode(", ", $all_event_ids) . ")";
	} else {
		$sql = "select '" . getSingleEvent($project_id) . "' as event_id, form_name from redcap_metadata 
				where project_id = $project_id and form_menu_description is not null";
	}
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q)) 
	{
		if (!isset($alreadyLocked[$row['event_id']][$row['form_name']]) && !isset($notLockable[$row['form_name']]))
		{
			$sql = "insert into redcap_locking_data (project_id, record, event_id, form_name, username, timestamp) 
					values ($project_id, '" . prep($id) . "', {$row['event_id']}, '" . prep($row['form_name']) . "', 
					'" . prep($userid) . "', '".NOW."')";
			if (!db_query($sql)) $response = 0;
		}
	}
	// Log the event
	if ($response)
	{
		log_event($sql,"redcap_locking_data","LOCK_RECORD",$id,$log,"Lock record");
	}

}
 
// UNLOCK ALL FORMS
elseif ($action == "unlock") 
{
	// Delete all instances of locked fields in table
	$sql = "delete from redcap_locking_data where project_id = $project_id and event_id in (" . implode(", ", $all_event_ids) . ") 
			and record = '" . prep($id). "'";
	// Execute query, set response, and log the event
	if (db_query($sql))
	{
		// Logging
		log_event($sql,"redcap_locking_data","LOCK_RECORD",$id,$log,"Unlock record");
		
		// ESIGNATURES: Now check for any e-signatures that must now be negated, remove them, and log all
		$sql = "select 1 from redcap_esignatures where project_id = $project_id 
				and event_id in (" . implode(", ", $all_event_ids) . ") and record = '" . prep($id). "' limit 1";
		$esignatures_exist = db_num_rows(db_query($sql));
		if ($esignatures_exist)
		{	
			$sql = "delete from redcap_esignatures where project_id = $project_id 
					and event_id in (" . implode(", ", $all_event_ids) . ") and record = '" . prep($id). "'";
			if (db_query($sql))
			{
				// Logging
				log_event($sql,"redcap_esignatures","ESIGNATURE",$id,$log,"Negate e-signature");
			}
		}
	}
	else
	{
		$response = 0;
	}
	
}

// Should not be here
else
{
	$response = 0;
}

// Send response
print $response;
