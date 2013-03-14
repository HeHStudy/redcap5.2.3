<?php
defined("PROJECT_ID") or define("PROJECT_ID", $post['projectid']);

# get project information
$Proj = new ProjectAttributes();
$longitudinal = $Proj->longitudinal;

$project_id = $post['projectid'];
$record = $post['record'];
$fieldName = $post['field'];
$eventName = $post['event'];
$eventId = "";

# if the project is longitudinal, check the event that was passed in and get the id associated with it
if ($longitudinal)
{
	if ($eventName != "") {
		$event = Event::getEventIdByKey($project_id, array($eventName));
		
		if (count($event) > 0 && $event[0] != "") {
			$eventId = $event[0];
		}
		else {
			RestUtility::sendResponse(400, "invalid event");
		}
	}
	else {
		RestUtility::sendResponse(400, "invalid event");
	}
}
else
{
	$sql = "SELECT m.event_id 
			FROM redcap_events_metadata m, redcap_events_arms a 
			WHERE a.project_id = $project_id and a.arm_id = m.arm_id 
			LIMIT 1";
	$eventId = db_result(db_query($sql), 0);
}

# check to make sure the record exists
$sql = "SELECT 1 
		FROM redcap_data 
		WHERE project_id = $project_id  
			AND record = '$record'
			AND event_id = $eventId
			LIMIT 1";
$result = db_query($sql);
if (db_num_rows($result) == 0) {
	RestUtility::sendResponse(400, "The record '$record' does not exist");	
}

# determine if the field exists in the metadata table and if of type 'file'
$sql = "SELECT 1
		FROM redcap_metadata
		WHERE project_id = $project_id 
			AND field_name = '$fieldName'
			AND element_type = 'file'";
$metadataResult = db_query($sql);
if (db_num_rows($metadataResult) == 0) {
	RestUtility::sendResponse(400, "The field '$fieldName' does not exist or is not a 'file' field");
}

# determine if a file exists for this record/field combo
$sql = "SELECT value
	FROM redcap_data 
	WHERE project_id = $project_id 
		AND record = '$record' 
		AND event_id = $eventId
		AND field_name = '$fieldName'";
$result = db_query($sql);
if (db_num_rows($result) == 0 || db_result($result, 0, 0) == "") {
	RestUtility::sendResponse(400, "There is no file to delete for this record");
}

# Set the file as "deleted" in redcap_edocs_metadata table, but don't really delete the file or the table entry
$sql = "UPDATE redcap_edocs_metadata SET delete_date = '".NOW."' WHERE doc_id = $id";
db_query($sql);

# Delete data for this field from data table
$sql = "DELETE
		FROM redcap_data
		WHERE project_id = $project_id
			AND record = '$record'
			AND field_name = '$fieldName'
			AND event_id = $eventId";
db_query($sql);

# Log file deletion
$query = "SELECT username FROM redcap_user_rights WHERE api_token = '" . $post['token'] . "'";
defined("USERID") or define("USERID", db_result(db_query($query), 0));
log_event($sql,"redcap_data","doc_delete",$record,$fieldName,"Delete uploaded document (API)");

# Send the response to the requester
RestUtility::sendResponse(200);
