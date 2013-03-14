<?php
defined("PROJECT_ID") or define("PROJECT_ID", $post['projectid']);

# get project information
$Proj = new ProjectAttributes();
$longitudinal = $Proj->longitudinal;
$primaryKey = $Proj->table_pk;

$project_id = $post['projectid'];
$record = $post['record'];
$fieldName = $post['field'];
$eventName = $post['event'];
$eventId = "";

# check to see if a file was uploaded
if (count($_FILES) == 0) RestUtility::sendResponse(400, "No valid file was uploaded");

# make sure there were no errors associated with the uploaded file
if ($_FILES['file']['error'] != 0) RestUtility::sendResponse(400, "There was a problem with the uploaded file");

# get file information
$fileData = $_FILES['file'];

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

$docName = str_replace("'", "", html_entity_decode(stripslashes($fileData['name']), ENT_QUOTES));
$docSize = $fileData['size'];

# Check if file is larger than max file upload limit
if (($docSize/1024/1024) > maxUploadSizeEdoc() || $fileData['error'] != UPLOAD_ERR_OK) {
	RestUtility::sendResponse(400, "The uploaded file exceeded the maximum file size limit of ".maxUploadSize()." MB");
}

# Upload the file and return the doc_id from the edocs table
$docId = uploadFile($fileData);

# Update tables if file was successfully uploaded
if ($docId != 0)
{
	# check to make sure the record exists
	$sql = "SELECT 1 
			FROM redcap_data 
			WHERE project_id = $project_id  
				AND record = '$record'
				AND event_id = $eventId
				LIMIT 1";
	$result = db_query($sql);
	if (db_num_rows($result) == 0) {
		RestUtility::sendResponse(400, "The record '$record' does not exist. It must exist to upload a file");
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
	
	# Now see if field has had a previous value. If so, update; if not, insert.
	$sql = "SELECT value
		FROM redcap_data 
		WHERE project_id = $project_id 
			AND record = '$record'
			AND event_id = $eventId
			AND field_name = '$fieldName'";
	$result = db_query($sql);
	
	if (db_num_rows($result) > 0) // row exists 
	{
		# Set the file as "deleted" in redcap_edocs_metadata table, but don't really delete the file or the table entry
		$id = db_result($result, 0, 0);
		$sql = "UPDATE redcap_edocs_metadata SET delete_date = '".NOW."' WHERE doc_id = $id";
		db_query($sql);
		
		$sql = "UPDATE redcap_data 
				SET value = '$docId' 
				WHERE project_id = $project_id 
					AND record = '$record' 
					AND event_id = $eventId 
					AND field_name = '$fieldName'";
		db_query($sql);
	}
	else // row did not exist
	{
		$sql = "INSERT INTO redcap_data VALUES ($project_id, $eventId, '$record', '$fieldName', '$docId')";
		db_query($sql);
	}
	
	# Log file upload
	$query = "SELECT username FROM redcap_user_rights WHERE api_token = '" . $post['token'] . "'";
	defined("USERID") or define("USERID", db_result(db_query($query), 0));
	log_event($sql,"redcap_data","doc_upload",$record,$fieldName,"Upload document (API)");
}
else {
	RestUtility::sendResponse(400, "A problem occurred while trying to save the uploaded file");
}

# Send the response to the requester
RestUtility::sendResponse(200);
