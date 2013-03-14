<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
require_once APP_PATH_DOCROOT . "ProjectGeneral/form_renderer_functions.php";
	
	
// Make sure we have all the correct elements needed
if (!(isset($_POST['action']) && isset($_POST['event_id']) && is_numeric($_POST['event_id']) 
	&& isset($_POST['record']) && isset($_POST['field_name']) && preg_match("/[a-z_0-9]/", $_POST['field_name'])))
{
	exit('ERROR!');
}


// Get params
$field		= $_POST['field_name'];
$record		= label_decode($_POST['record']);
$event_id	= $_POST['event_id'];


// Display data cleaner history table of this field
if ($_POST['action'] == 'view')
{
	if (isset($_POST['existing_record']) && !$_POST['existing_record']) {
		// If record has not been saved yet, then give user message to first save the record
		print   RCView::div(array('class'=>'yellow', 'style'=>''),
					RCView::img(array('class'=>'imgfix', 'src'=>'exclamation_orange.png')) .
					RCView::b("{$lang['global_03']}{$lang['colon']} ") . $lang['dataqueries_114']
				);
	} else {
		// Display the full history of this record's field + form for adding more comments/data queries
		print DataCleaner::displayFieldHistory($record, $event_id, $field);
	}
}


// Save new data cleaner values for this field
elseif ($_POST['action'] == 'save')
{
	// Determine the status to set
	if ($_POST['status'] == 'OPEN' || $_POST['status'] == 'CLOSED') {
		$dc_status = $_POST['status'];
	} else {
		$dc_status = (is_numeric($_POST['user_id_next_action'])) ? 'OPEN' : '';
	}
	// Insert into data cleaner table
	$sql = "insert into redcap_data_cleaner (project_id, event_id, record, field_name, status, `high_priority`)
			values ($project_id, $event_id, '".prep($record)."', '".prep($field)."', 
			".checkNull($dc_status).", ".checkNull($_POST['high_priority']).")
			on duplicate key update 
			`high_priority` = if (status is null and ".checkNull($dc_status)." = 'OPEN', ".checkNull($_POST['high_priority']).", `high_priority`),
			status = ".checkNull($dc_status).", cleaner_id = LAST_INSERT_ID(cleaner_id)";
	if (db_query($sql)) 
	{
		// Get cleaner_id
		$cleaner_id = db_insert_id();
		// Get current user's ui_id
		$userInitiator = User::getUserInfo(USERID);
		// Add new row to data_cleaner_log
		$sql = "insert into redcap_data_cleaner_log (cleaner_id, ts, user_id_current, user_id_next_action, response_requested_next_action, 
				responded_to_request, change_performed, change_required_next_action, send_email, comment) 
				values ($cleaner_id, '".NOW."', ".checkNull($userInitiator['ui_id']).", 
				".checkNull($_POST['user_id_next_action']).", ".checkNull($_POST['response_requested_next_action']).", 
				".checkNull($_POST['responded_to_request']).", ".checkNull($_POST['change_performed']).",
				".checkNull($_POST['change_required_next_action']).", ".checkNull($_POST['send_email']).", 
				".checkNull(trim(label_decode($_POST['comment']))).")";
		if (db_query($sql)) {
			// Success, so return content via JSON to redisplay with new changes made
			$clog_id = db_insert_id();
			$content = DataCleaner::displayFieldHistory($record, $event_id, $field);
			// Set balloon icon
			if ($dc_status == 'OPEN') {
				$icon = 'balloon_minus.gif';
			} elseif ($dc_status == 'CLOSED') {
				$icon = 'balloon_tick.gif';
			} else {
				$icon = 'balloon_left.png';
			}
			// Output JSON
			print json_encode(array('clog_id'=>$clog_id, 'content'=>$content, 'icon'=>APP_PATH_IMAGES.$icon));
		} else {
			// ERROR!
			exit('0');
		}
	}
}
