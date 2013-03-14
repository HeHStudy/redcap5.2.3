<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

include_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

// Default response
$response = "0"; 

// Ensure that user has locking rights, and if e-signature required, then verify username/password first
if ($user_rights['lock_record'] > 0 && ($auth_meth == "none" 
	|| (isset($_POST['no_auth_key']) && $_POST['no_auth_key'] == 'q4deAr8s') 
	|| (USERID == $_POST['username'] && checkUserPassword($_POST['username'], $_POST['password']))
	))
{
	$_POST['record'] = urldecode($_POST['record']);
	
	// Alter how records are saved if project is Double Data Entry (i.e. add --# to end of Study ID)
	if ($double_data_entry && $user_rights['double_data'] != 0) {
		$_POST['record'] .= "--" . $user_rights['double_data'];
	}

	// Save as locked
	if ($_POST['action'] == "lock" || $_POST['action'] == "")
	{
		if ($_POST['action'] == "lock")
		{
			$sql = "insert into redcap_locking_data (project_id, record, event_id, form_name, username, timestamp)
					values ($project_id, '" . prep($_POST['record']) . "', " . prep($_POST['event_id']) . ", 
					'" . prep($_POST['form_name']) . "', '" . USERID . "', '".NOW."')";
			$description = "Lock record";
		} else {
			$sql = "select 1"; // Placeholder query
		}
		
		// Check if we're saving the e-signature
		if (isset($_POST['esign_action']) && $_POST['esign_action'] == "save")
		{
			$sqle = "insert into redcap_esignatures (project_id, record, event_id, form_name, username, timestamp)
					 values ($project_id, '" . prep($_POST['record']) . "', " . prep($_POST['event_id']) . ", 
					 '" . prep($_POST['form_name']) . "', '" . USERID . "', '".NOW."')";
			$descriptione = "Save e-signature";
		}
	}
	// Delete lock (unlock)
	elseif ($_POST['action'] == "unlock")
	{
		$sql = "delete from redcap_locking_data where project_id = " . PROJECT_ID . " and record = '" . prep($_POST['record']) . "' 
				and event_id = " . prep($_POST['event_id']) . " and form_name = '" . prep($_POST['form_name']) . "'";
		$description = "Unlock record";
		
		// Regardless of whether the e-signture is shown or not, check first if an e-signature exists in case we need to negate it
		$sqle2 = "select 1 from redcap_esignatures where project_id = $project_id and record = '" . prep($_POST['record']) . "' 
				  and event_id = " . prep($_POST['event_id']) . " and form_name = '" . prep($_POST['form_name']) . "' limit 1";
		if (db_num_rows(db_query($sqle2)) > 0)
		{
			// Negate the e-signature. NOTE: Anyone with locking privileges can negate an e-signature.
			$sqle = "delete from redcap_esignatures where project_id = $project_id and record = '" . prep($_POST['record']) . "' 
					 and event_id = " . prep($_POST['event_id']) . " and form_name = '" . prep($_POST['form_name']) . "'";
			$descriptione = "Negate e-signature";
		}
	}
	
	// Execute query, set response, and log the event
	if (db_query($sql))
	{
		$response = "1";
		$log = "Record: {$_POST['record']}\nForm: " . $Proj->forms[$_POST['form_name']]['menu'];
		// For longitudinal projects where forms are locked for a single event
		if ($longitudinal)
		{
			$q = db_query("select descrip from redcap_events_metadata where event_id = " . $_POST['event_id']);
			$log .= "\nEvent: " . html_entity_decode(db_result($q, 0), ENT_QUOTES);
		}
		$_GET['event_id'] = $_POST['event_id']; // Set for logging only, which looks for event_id in query string
		// Logging (but not if esigning w/o locking)
		if ($_POST['action'] != "")	{
			log_event($sql,"redcap_locking_data","LOCK_RECORD",$_POST['record'],$log,$description);
		}
		// Save and log e-signature action, if required
		if (isset($sqle) && db_query($sqle))
		{
			log_event($sqle,"redcap_esignatures","ESIGNATURE",$_POST['record'],$log,$descriptione);
		}
	}
	else
	{
		$response = "2";
	}
}

// Send response
print $response;
