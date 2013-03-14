<?php
global $format, $returnFormat, $post;

defined("PROJECT_ID") or define("PROJECT_ID", $post['projectid']);

# get project information
$Proj = new ProjectAttributes();
$longitudinal = $Proj->longitudinal;
$primaryKey = $Proj->table_pk;
$isChild = ($Proj->project['is_child_of'] != "");
$secondary_pk = $Proj->project['secondary_pk'];
$dags = $Proj->getUniqueGroupNames();
	
// Set extra set of reserved field names for survey timestamps and return codes pseudo-fields
$reserved_field_names2 = explode(',', implode("_timestamp,", array_keys($Proj->forms)) . "_timestamp"
					   . "," . implode("_return_code,", array_keys($Proj->forms)) . "_return_code");

# input the data based on the data structure
switch($format)
{
	case 'json':
		$result = json();
		break;
	case 'xml':
		$result = xml();
		break;
	case 'csv':
		$result = csv();
		break;
}

# format the response
$response = "";
$returnContent = $post['returnContent'];
if ($returnFormat == "json") {
	if ($returnContent == "ids") {
		$response = "[".implode(", ", $result)."]";
	}
	elseif ($returnContent == "count") {
		$response = '{"count": '.$result."}";
	}
}
elseif ($returnFormat == "xml") {
	$response = '<?xml version="1.0" encoding="UTF-8" ?>';
	if ($returnContent == "ids") {
		$response .= '<ids><id>'.implode("</id><id>", $result)."</id></ids>";
	}
	elseif ($returnContent == "count") {
		$response .= '<count>'.$result."</count>";
	}
}
else {
	if ($returnContent == "ids") {
		$response = "id\n";
		$response .= implode("\n", $result);
	}
	elseif ($returnContent == "count") {
		$response = $result;
	}
}

# Send the response to the requester
RestUtility::sendResponse(200, $response);

function json()
{
	global $data, $post, $longitudinal, $primaryKey, $isChild, $checkboxFields, 
		$fullCheckboxFields, $events, $eventList, $fieldList, $newIds, $returnFormat;
		
	$project_id = $post['projectid'];
	$overwriteBehavior = $post['overwriteBehavior'];
	
	# get checkbox field information
	$checkboxFields = MetaData::getCheckboxFields($project_id);
	
	# Create new array with the translated checkbox field names
	$fullCheckboxFields = array();
	foreach ($checkboxFields as $field => $value)
	{
		foreach ($value as $code => $label) {
			$fullCheckboxFields[$field . "___" . strtolower($code)] = $label;
		}
	}

	# get an array of the events with their unique key names and ids
	$events = array_flip(Event::getUniqueKeys($project_id));
	
	// START the transaction
	@db_query("START TRANSACTION");
	
	$counter = 0;
	
	try
	{
		$records = array();
		$newIds = array();
		$idArray = array();
		$duplicateIds = array();
		$illegalCharsInRecordName = array();
		$eventList = array();
		$fieldList = array();
		
		if ($post['type'] == 'eav')
		{
			# add incoming data to array
			foreach($data->getData() as $index => $record)
			{
				$studyId = trim($record['record']);
				$eventName = trim($record['redcap_event_name']);
				$fieldName = trim($record['field_name']);
				$fieldValue = trim($record['value']);
				
				// make sure the primary key and event name are not empty
				if ( $studyId == '' )
					throw new Exception("The record cannot be empty");
				if ( $primaryKey == $fieldName && $fieldValue == '' )
					throw new Exception("The record id, $primaryKey, cannot be empty");
				if ( $longitudinal && $eventName == '' )
					throw new Exception("The record event name cannot be empty");
				
				// Make sure record names do NOT contain a +, &, #, or apostrophe
				if (strpos($studyId, '+') !== false || strpos($studyId, "'") !== false || strpos($studyId, '&') !== false || strpos($studyId, '#') !== false) {
					throw new Exception("The record name ($studyId) cannot contain a plus sign (+), ampersand (&), pound sign (#), or apostrophe.");
				}	
				
				// get unique key for each record
				$key = ($longitudinal) ? $studyId . ' (' . $eventName . ')' : $studyId;
				
				if ( !in_array($studyId, $newIds, true) ) $newIds[] = $studyId;
				
				// set fieldname, new value, and default value
				if ( isset($checkboxFields[$fieldName]) )
				{
					$newFieldName = $fieldName . "___" . $fieldValue;
					$newValue = "1";
					$defaultValue = "0";
				}
				else
				{
					$newFieldName = $fieldName;
					$newValue = str_replace('\"', '"', $fieldValue);
					$defaultValue = "";
				}
				
				// save fieldname in array
				if ( !in_array($newFieldName, $fieldList) ) $fieldList[] = $newFieldName;
				
				// if longitudinal, save the event name in array and add field to global array
				if ($longitudinal)
				{
					if ( !in_array($eventName, $eventList) )
						$eventList[] = $eventName;
						
					$records[$key]['redcap_event_name'] = array('new' => $eventName, 'old' => '', 'status' => '');
				}
				
				// add field and information to global array
				$records[$key][$newFieldName] = array('new' => $newValue, 'old' => $defaultValue, 'status' => '');
				
				// check to see if the primary key is in the array as its own element.  If not add it
				if ( !isset($record[$key][$primaryKey]) ) {
					$records[$key][$primaryKey] = array('new' => $studyId, 'old' => '', 'status' => '');
					
					// save fieldname in array
					if (!in_array($primaryKey, $fieldList)) $fieldList[] = $primaryKey;
				}
			}
		}
		else
		{
			# add incoming data to array
			foreach($data->getData() as $index => $record)
			{
				$studyId = trim($record[$primaryKey]);
				$eventName = trim($record['redcap_event_name']);
				
				// make sure the primary key and event name are not empty
				if ($studyId == '')
					throw new Exception("The record id, $primaryKey, cannot be empty");
				if ($longitudinal && $eventName == '')
					throw new Exception("The record event name cannot be empty");
				// Make sure record names do NOT contain a +, &, #, or apostrophe
				if (strpos($studyId, '+') !== false || strpos($studyId, "'") !== false || strpos($studyId, '&') !== false || strpos($studyId, '#') !== false) {
					throw new Exception("The record name ($studyId) cannot contain a plus sign (+), ampersand (&), pound sign (#), or apostrophe.");
				}	
				
				// get unique key for each record
				$key = ($longitudinal) ? $studyId . ' (' . $eventName . ')' : $studyId;
				
				// check for duplicate ids 
				if (in_array(strtolower($key), array_map('strtolower', $idArray), true) && !in_array($key, $duplicateIds))
				//if (in_array($key, $idArray, true) && !in_array($key, $duplicateIds, true))
					$duplicateIds[] = $key;
				
				// save ID and Key in arrays
				if ( !in_array($studyId, $newIds, true) ) $newIds[] = $studyId;
				if ( !in_array($key, $idArray, true) ) $idArray[] = $key;
				
				foreach($record as $field => $value)
				{
					$fieldName = trim($field);
					$fieldValue = trim($value);
				
					// if longitudinal, save the event name in array
					if ($longitudinal)
					{
						if ( $fieldName == "redcap_event_name" )
							if (!in_array($fieldValue, $eventList)) $eventList[] = $fieldValue;
					}
					
					// format value
					$newValue = str_replace('\"', '"', $fieldValue);
					
					// Checkbox fields get default of 0, all others default of ""
					if (isset($fullCheckboxFields[$fieldName])) {
						$oldValue = "0";
							
						// if overwrite and value is blank, set value to 0 so item will be deleted
						if ($newValue == "" && $overwriteBehavior == "overwrite") $newValue = 0;
					}
					else {
						$oldValue = "";
					}
					
					// save fieldname in array
					if (!in_array($fieldName, $fieldList)) $fieldList[] = $fieldName;
					
					// add field and information to global array
					$records[$key][$fieldName] = array('new' => $newValue, 'old' => $oldValue, 'status' => '');
				}
			}
			
			// throw error if duplicates were found
			if (count($duplicateIds) > 0) {
				if ($returnFormat == "json") {
					$message = '{"error": "The records listed below occur more than once. Please remove any duplicates and try again", ';
					$message .= '"records": [ "'. implode('", "', $duplicateIds) .'" ]}';
				}
				elseif ($returnFormat == "xml") {
					$message = "<error>The records listed below occur more than once. Please remove any duplicates and try again</error><records>";
					foreach ($duplicateIds as $item) {
						$message .= "<record>$item</record>";
					}
					$message .= "</records>";
				}
				else {
					$message = "The records listed below occur more than once. Please remove any duplicates and try again:<br/>";
					$message .= implode(", ", $duplicateIds);
				}

				throw new Exception($message);
			}
		}
		
		$counter = ProcessData($records);
		
		@db_query("COMMIT");
	}
	catch (Exception $e)
	{
		@db_query("ROLLBACK");
		die(RestUtility::sendResponse(400, $e->getMessage()));
	}
	
	return $counter;
}

function xml()
{
	global $data, $post, $longitudinal, $primaryKey, $isChild, $checkboxFields, 
		$fullCheckboxFields, $events, $eventList, $fieldList, $newIds, $returnFormat;
	
	$project_id = $post['projectid'];
	$overwriteBehavior = $post['overwriteBehavior'];
	
	# get checkbox field information
	$checkboxFields = MetaData::getCheckboxFields($project_id);

	# Create new array with the translated checkbox field names
	$fullCheckboxFields = array();
	foreach ($checkboxFields as $field => $value)
	{
		foreach ($value as $code => $label) {
			$fullCheckboxFields[$field . "___" . strtolower($code)] = $label;
		}
	}

	# get an array of the events with their unique key names and ids
	$events = array_flip(Event::getUniqueKeys($project_id));
	
	// START the transaction
	@db_query("START TRANSACTION");
	
	$counter = 0;
	
	try
	{
		$records = array();
		$newIds = array();
		$idArray = array();
		$duplicateIds = array();
		$illegalCharsInRecordName = array();
		$eventList = array();
		$fieldList = array();
		
		$dataArray = $data->getData();
		
		if ($post['type'] == 'eav')
		{
			# if only one item is passed in we need to reformat the array
			$a = array_keys($dataArray['records']['item']);
			$data = $dataArray['records']['item'];
			if ( !is_array($dataArray['records']['item'][$a[0]]) ) {
				unset($dataArray);
				$dataArray['records']['item'][0] = $data;
			}

			# add incoming data to array
			foreach($dataArray['records']['item'] as $index => $record)
			{
				$studyId = trim($record['record']);
				$eventName = trim($record['redcap_event_name']);
				$fieldName = trim($record['field_name']);
				$fieldValue = trim($record['value']);
				
				// make sure the primary key and event name are not empty
				if ( $studyId == '' )
					throw new Exception("The record cannot be empty");
				if ( $primaryKey == $fieldName && $fieldValue == '' )
					throw new Exception("The record id, $primaryKey, cannot be empty");
				if ( $longitudinal && $eventName == '' )
					throw new Exception("The record event name cannot be empty");
				// Make sure record names do NOT contain a +, &, #, or apostrophe
				if (strpos($studyId, '+') !== false || strpos($studyId, "'") !== false || strpos($studyId, '&') !== false || strpos($studyId, '#') !== false) {
					throw new Exception("The record name ($studyId) cannot contain a plus sign (+), ampersand (&), pound sign (#), or apostrophe.");
				}	
				
				// get unique key for each record
				$key = ($longitudinal) ? $studyId . ' (' . $eventName . ')' : $studyId;
				
				if ( !in_array($studyId, $newIds, true) ) $newIds[] = $studyId;
				
				// set fieldname, new value, and default value
				if ( isset($checkboxFields[$fieldName]) )
				{
					$newFieldName = $fieldName . "___" . $fieldValue;
					$newValue = "1";
					$oldValue = "0";
				}
				else
				{
					$newFieldName = $fieldName;
					$newValue = str_replace('\"', '"', ReplaceNewlines($fieldValue));
					$oldValue = "";
				}
				
				// save fieldname in array
				if ( !in_array($newFieldName, $fieldList) ) $fieldList[] = $newFieldName;
				
				// if longitudinal, save the event name in array and add field to global array
				if ($longitudinal)
				{
					if ( !in_array($eventName, $eventList) )
						$eventList[] = $eventName;
					
					$records[$key]['redcap_event_name'] = array('new' => $eventName, 'old' => '', 'status' => '');
				}
				
				// add field and information to global array
				$records[$key][$newFieldName] = array('new' => $newValue, 'old' => $oldValue, 'status' => '');
				
				// check to see if the primary key is in the array as its own element.  If not add it
				if ( !isset($record[$key][$primaryKey]) ) {
					$records[$key][$primaryKey] = array('new' => $studyId, 'old' => '', 'status' => '');
					
					// save fieldname in array
					if (!in_array($primaryKey, $fieldList)) $fieldList[] = $primaryKey;
				}
			}
		}
		else
		{
			# if only one item is passed in we need to reformat the array
			$a = array_keys($dataArray['records']['item']);
			$data = $dataArray['records']['item'];
			if ( !is_array($dataArray['records']['item'][$a[0]]) ) {
				unset($dataArray);
				$dataArray['records']['item'][0] = $data;
			}

			# add incoming data to array
			foreach($dataArray['records']['item'] as $index => $record)
			{
				$studyId = trim($record[$primaryKey]);
				$eventName = trim($record['redcap_event_name']);
				
				// make sure the primary key and event name are not empty
				if ($studyId == '')
					throw new Exception("The record id, $primaryKey, cannot be empty");
				if ($longitudinal && $eventName == '')
					throw new Exception("The record event name cannot be empty");
				// Make sure record names do NOT contain a +, &, #, or apostrophe
				if (strpos($studyId, '+') !== false || strpos($studyId, "'") !== false || strpos($studyId, '&') !== false || strpos($studyId, '#') !== false) {
					throw new Exception("The record name ($studyId) cannot contain a plus sign (+), ampersand (&), pound sign (#), or apostrophe.");
				}	
				
				// get unique key for each record
				$key = ($longitudinal) ? $studyId . ' (' . $eventName . ')' : $studyId;
				
				// check for duplicate ids 
				if (in_array(strtolower($key), array_map('strtolower', $idArray), true) && !in_array($key, $duplicateIds))
				//if (in_array($key, $idArray, true) && !in_array($key, $duplicateIds, true))
					$duplicateIds[] = $key;
				
				// save ID and Key in arrays
				if ( !in_array($studyId, $newIds, true) ) $newIds[] = $studyId;
				if ( !in_array($key, $idArray, true) ) $idArray[] = $key;
				
				foreach($record as $field => $value)
				{
					$fieldName = trim($field);
					$fieldValue = trim($value);
				
					// if longitudinal, save the event name in array
					if ($longitudinal)
					{
						if ($fieldName == "redcap_event_name")
							if ( !in_array($fieldValue, $eventList) ) $eventList[] = $fieldValue;
					}
					
					// format value
					$newValue = str_replace('\"', '"', ReplaceNewlines($fieldValue));
					
					// Checkbox fields get default of 0, all others default of ""
					if (isset($fullCheckboxFields[$fieldName])) {
						$oldValue = "0";
							
						// if overwrite and value is blank, set value to 0 so item will be deleted
						if ($newValue == "" && $overwriteBehavior == "overwrite") $newValue = 0;
					}
					else {
						$oldValue = "";
					}
					
					// save fieldname in array
					if ( !in_array($fieldName, $fieldList) ) $fieldList[] = $fieldName;
					
					// add field and information to global array
					$records[$key][$fieldName] = array('new' => $newValue, 'old' => $oldValue, 'status' => '');
				}
			}
			
			// throw error if duplicates were found
			if (count($duplicateIds) > 0)
			{
				$message = "<error>The records listed below occur more than once. Please remove any duplicates and try again</error><records>";
				foreach ($duplicateIds as $item) {
					$message .= "<record>$item</record>";
				}
				$message .= "</records>";

				if ($returnFormat == "json") {
					$message = '{"error": "The records listed below occur more than once. Please remove any duplicates and try again", ';
					$message .= '"records": [ "'. implode('", "', $duplicateIds) .'" ]}';
				}
				elseif ($returnFormat == "xml") {
					$message = "<error>The records listed below occur more than once. Please remove any duplicates and try again</error><records>";
					foreach ($duplicateIds as $item) {
						$message .= "<record>$item</record>";
					}
					$message .= "</records>";
				}
				else {
					$message = "The records listed below occur more than once. Please remove any duplicates and try again:<br/>";
					$message .= implode(", ", $duplicateIds);
				}

				throw new Exception($message);
			}
		}
		
		$counter = ProcessData($records);
		
		@db_query("COMMIT");
	}
	catch (Exception $e)
	{
		@db_query("ROLLBACK");
		die(RestUtility::sendResponse(400, $e->getMessage()));
	}
	
	return $counter;
}

function csv()
{
	global $data, $post, $longitudinal, $primaryKey, $isChild, $checkboxFields, 
		$fullCheckboxFields, $events, $eventList, $fieldList, $newIds, $returnFormat;

	$project_id = $post['projectid'];
	$overwriteBehavior = $post['overwriteBehavior'];

	# get checkbox field information
	$checkboxFields = MetaData::getCheckboxFields($project_id);
	
	# Create new array with the translated checkbox field names
	$fullCheckboxFields = array();
	foreach ($checkboxFields as $field => $value)
	{
		foreach ($value as $code => $label) {
			$fullCheckboxFields[$field . "___" . strtolower($code)] = $label;
		}
	}

	# get an array of the events with their unique key names and ids
	$events = array_flip(Event::getUniqueKeys($project_id));
	
	// START the transaction
	@db_query("START TRANSACTION");
	
	$counter = 0;
	
	try
	{
		// save the file for easier processing of csv data
		$redcapTempDir = dirname(dirname(dirname(dirname(__FILE__)))) . DS . 'temp';
		$filename = $redcapTempDir . DS . date('YmdHis') . '_' . $project_id . '.csv';
		file_put_contents($filename, html_entity_decode($data->getData(), ENT_QUOTES));
		
		$records = array();
		$newIds = array();
		$idArray = array();
		$duplicateIds = array();
		$illegalCharsInRecordName = array();
		$eventList = array();
		$fieldList = array();
		
		$fh = fopen($filename, "rb");
		
		// Get the first line of the file which should contain the field headers. If first lines are blank, keep checking.
		$fieldList = fgetcsv($fh);
		while (count($fieldList) == 1 && empty($fieldList[0])) {
			$fieldList = fgetcsv($fh);
		}
		
		if ($post['type'] == 'eav')
		{
			# make sure record is in the list
			if ( !in_array('record', $fieldList) )
			{
				throw new Exception("The column \"record\" was not found in the first row. 
					Please correct this and try again.");
			}
			# make sure field_name is in the list
			if ( !in_array('field_name', $fieldList) )
			{
				throw new Exception("The column \"field_name\" was not found in the first row. 
					Please correct this and try again.");
			}
			# make sure value is in the list
			if ( !in_array('value', $fieldList) )
			{
				throw new Exception("The column \"value\" was not found in the first row. 
					Please correct this and try again.");
			}
			# if the project is longitudinal, make sure the event name is in the list
			if ( $longitudinal && !in_array('redcap_event_name', $fieldList) )
			{
				throw new Exception("The column \"redcap_event_name\" was not found in the first row. 
					Please correct this and try again.");
			}
			
			// get position of columns
			$idPos = array_search('record', $fieldList);
			$namePos = array_search('field_name', $fieldList);
			$valuePos = array_search('value', $fieldList);
			$eventPos = array_search('redcap_event_name', $fieldList);
			
			$fieldList = array();
			
			# add incoming data to array
			while ($line = fgetcsv($fh))
			{
				if ( count($line) == 1 && $line[0] == null )
				{
					// do nothing because the line is blank
				}
				else
				{
					$studyId = trim($line[$idPos]);
					$eventName = trim($line[$eventPos]);
					$fieldName = trim($line[$namePos]);
					$fieldValue = trim($line[$valuePos]);
					
					// make sure the primary key and event name are not empty
					if ($studyId == '')
						throw new Exception("The record cannot be empty");
					if ($primaryKey == $fieldName && $fieldValue == '')
						throw new Exception("The record id, $primaryKey, cannot be empty");
					if ($longitudinal && $eventName == '')
						throw new Exception("The record event name cannot be empty");
					// Make sure record names do NOT contain a +, &, #, or apostrophe
					if (strpos($studyId, '+') !== false || strpos($studyId, "'") !== false || strpos($studyId, '&') !== false || strpos($studyId, '#') !== false) {
						throw new Exception("The record name ($studyId) cannot contain a plus sign (+), ampersand (&), pound sign (#), or apostrophe.");
					}	
					
					// get unique key for each record
					$key = ($longitudinal) ? $studyId . ' (' . $eventName . ')' : $studyId;
					
					if ( !in_array($studyId, $newIds, true) ) $newIds[] = $studyId;
					
					// set fieldname, new value, and default value
					if (isset($checkboxFields[$fieldName]))
					{
						$fieldname = $fieldName . "___" . $fieldValue;
						$newValue = "1";
						$oldValue = "0";
					}
					else
					{
						$fieldname = $fieldName;
						$newValue = str_replace('\"', '"', ReplaceNewlines($fieldValue));
						$oldValue = "";
					}

					// save fieldname in array
					if (!in_array($fieldName, $fieldList)) $fieldList[] = $fieldname;
					
					// if longitudinal, save the event name in array and add field to global array
					if ($longitudinal)
					{
						if (!in_array($eventName, $eventList))
							$eventList[] = $eventName;
						
						$records[$key]['redcap_event_name'] = array('new' => $eventName, 'old' => '', 'status' => '');
					}
					
					// add field and information to global array
					$records[$key][$fieldname] = array('new' => $newValue, 'old' => $oldValue, 'status' => '');
					
					// check to see if the primary key is in the array as its own element.  If not add it
					if ( !isset($record[$key][$primaryKey]) ) {
						$records[$key][$primaryKey] = array('new' => $studyId, 'old' => '', 'status' => '');
						
						// save fieldname in array
						if (!in_array($primaryKey, $fieldList)) $fieldList[] = $primaryKey;
					}
				}
			}
		}
		else // type = flat
		{
			# check to make sure field names are the first line and the first one is the record id
			if ($fieldList[0] != $primaryKey)
			{
				throw new Exception("The column \"$primaryKey\" was not found in the first 
					position of the first row. Please correct this and try again.");
			}
			# if the project is longitudinal, make sure the event name is in the list
			if ( $longitudinal && !in_array('redcap_event_name', $fieldList) )
			{
				throw new Exception("The column \"redcap_event_name\" was not found in the first row. 
					Please correct this and try again.");
			}
			
			// get the position of the event_name column
			$eventPos = array_search('redcap_event_name', $fieldList);
			
			# add incoming data to array
			while ($line = fgetcsv($fh))
			{
				if ( count($line) == 1 && $line[0] == null )
				{
					// do nothing because the line is blank
				}
				else
				{
					$studyId = trim($line[0]);
					$eventName = ($longitudinal) ? trim($line[$eventPos]) : '';
					
					// make sure the primary key and event name are not empty
					if ($studyId == '')
						throw new Exception("The record id, $primaryKey, cannot be empty");
					if ($longitudinal && $eventName == '')
						throw new Exception("The record event name cannot be empty");
					// Make sure record names do NOT contain a +, &, #, or apostrophe
					if (strpos($studyId, '+') !== false || strpos($studyId, "'") !== false || strpos($studyId, '&') !== false || strpos($studyId, '#') !== false) {
						throw new Exception("The record name ($studyId) cannot contain a plus sign (+), ampersand (&), pound sign (#), or apostrophe.");
					}	
					
					// get unique key for each record
					$key = ($longitudinal) ? $studyId . ' (' . $eventName . ')' : $studyId;

					// check for duplicate ids 
					if (in_array(strtolower($key), array_map('strtolower', $idArray), true) && !in_array($key, $duplicateIds))
						$duplicateIds[] = $key;
					
					// save ID and Key in arrays
					if (!in_array($studyId, $newIds, true)) $newIds[] = $studyId;
					if (!in_array($key, $idArray, true)) $idArray[] = $key;
					
					$count = count($line);
					for ($i=0; $i<$count; $i++)
					{
						// if longitudinal, save the event name in array
						if ($longitudinal)
						{
							if ($fieldList[$i] == "redcap_event_name")
								if (!in_array($line[$i], $eventList)) $eventList[] = $line[$i];
						}
						
						$newValue = str_replace('\"', '"', ReplaceNewlines($line[$i]));
						// $newValue = $line[$i];
						
						// Checkbox fields get default of 0, all others default of ""
						if (isset($fullCheckboxFields[$fieldList[$i]])) {
							$oldValue = "0";
							
							// if overwrite and value is blank, set value to 0 so item will be deleted
							if ($newValue == "" && $overwriteBehavior == "overwrite") $newValue = 0;
						}
						else {
							$oldValue = "";
						}
						
						// add field and information to global array
						$records[$key][$fieldList[$i]] = array('new' => $newValue, 'old' => $oldValue, 'status' => '');
					}
				}
			}
			
			// throw error if duplicates were found
			if (count($duplicateIds) > 0)
			{
				if ($returnFormat == "json") {
					$message = '{"error": "The records listed below occur more than once. Please remove any duplicates and try again", ';
					$message .= '"records": [ "'. implode('", "', $duplicateIds) .'" ]}';
				}
				elseif ($returnFormat == "xml") {
					$message = "<error>The records listed below occur more than once. Please remove any duplicates and try again</error><records>";
					foreach ($duplicateIds as $item) {
						$message .= "<record>$item</record>";
					}
					$message .= "</records>";
				}
				else {
					$message = "The records listed below occur more than once. Please remove any duplicates and try again:<br/>";
					$message .= implode(", ", $duplicateIds);
				}

				throw new Exception($message);
			}
		}
		
		fclose($fh);
		unlink($filename);

		$counter = ProcessData($records);
		
		@db_query("COMMIT");
	}
	catch (Exception $e)
	{
		@db_query("ROLLBACK");
		
		fclose($fh);
		unlink($filename);
	
		die(RestUtility::sendResponse(400, $e->getMessage()));
	}
	
	return $counter;
}

function ReplaceNewlines($value)
{
	if (strpos($value, "\r\n") !== false)
		$string = $value;
	elseif (strpos($value, "\r") !== false)
		$string = str_replace("\r", "\r\n", $value);
	elseif (strpos($value, "\n") !== false)
		$string = str_replace("\n", "\r\n", $value);
	else
		$string = $value;
		
	return $string;
}

// Check and fix any case sensitivity issues in record names
function checkRecordNameCaseSensitive($records)
{
	global $primaryKey, $events, $longitudinal;

	$eventNames = array_flip($events);
	
	// For case sensitivity issues, check actual record name's case against its value in the back-end. Use MD5 to differentiate.
	// Modify $records accordingly, if different.
	$records_md5 = array();
	foreach ($records as $key => $attr) {
		$id = $attr[$primaryKey]['new'];
		$records_md5[$id] = md5($id);
	}
	
	// Query using MD5 to find values that are different from uploaded values only on the case-level
	$sql = "select * from redcap_data where project_id = " . PROJECT_ID . " and field_name = '$primaryKey'
			and md5(record) not in ('" . implode("', '", $records_md5) . "')
			and record in ('" . implode("', '", array_keys($records_md5)) . "')";
	$q = db_query($sql);
	
	unset($records_md5);
	$records2 = array();
	
	while ($row = db_fetch_assoc($q))
	{
		// Using array_key_exists won't work, so loop through all imported record names for a match.
		foreach ($records as $key => $this_record) {
			// Do case insensitive comparison
			if (strcasecmp($this_record[$primaryKey]['new'], $row['record']) == 0) {
				// Record name exists in two different cases, so modify $records to align with back-end value.				
				// Replace sub-array with sub-array containing other case value.
				//$newKey = ($longitudinal) ? $row['record'] . ' (' . $eventNames[$row['event_id']] . ')' : $row['record'];
				$newKey = ($longitudinal) ? $row['record'] . ' (' . $this_record['redcap_event_name']['new'] . ')' : $row['record'];
				$records2[$newKey] = $records[$key];
				$records2[$newKey][$primaryKey]['new'] = $row['record'];
				unset($records[$key]);
			}
		}
	}
	
	// Merge arrays (don't user array_merge because keys will get lost if numerical)
	foreach ($records2 as $key=>$attr) {
		$records[$key] = $attr;
	}
	unset($records2);
	
	return $records;
}

function ProcessData($records)
{
	global $longitudinal, $checkboxFields, $fullCheckboxFields, $events, $eventList, $fieldList,
		$newIds, $post, $primaryKey, $returnFormat, $lang, $Proj, $isChild, $reserved_field_names, $reserved_field_names2;

	$project_id = $post['projectid'];
	$dataAccessGroupId = $post['dataAccessGroupId'];
	$type = $post['type'];
	$overwriteBehavior = $post['overwriteBehavior'];
	$returnContent = $post['returnContent'];

	## Check if user is allowed to create new records, and if not, prevent from creating new records, if applicable
	// Get list of already existing records
	$existingRecords = array();
	$sql = "select distinct record from redcap_data where project_id = $project_id and field_name = '$primaryKey'
			and record in (" . prep_implode(array_keys($records)) . ")";
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q)) {
		$existingRecords[] = $row['record'];
	}
	// If some records already exist, check if user has "Record Create" rights
	if (!empty($existingRecords)) {
		// Query API user's rights
		$sql = "select r.record_create, (select u.super_user from redcap_user_information u where u.username = r.username) as super_user 
				from redcap_user_rights r where r.project_id = $project_id and r.api_token = '" . prep($post['token']) . "'";
		$q = db_query($sql);
		$canCreateRecords = db_result($q, 0, 'record_create');
		$super_user = db_result($q, 0, 'super_user');
		// If can't create records, give error message
		if (!$canCreateRecords && !$super_user) {
			$msg = "Your user privileges do NOT allow you to create new records. You may only edit existing records. Since your "
				 . "API request contains records that do not yet exist in this project, you will not be allowed to import them. "
				 . "Please remove the following records from your API request and try uploading it again: " 
				 . implode(", ", $existingRecords) . ".";
			die(RestUtility::sendResponse(403, $msg));
		}
	}
	
	// CHILD Project: Make sure than any new records getting created already exist in the parent
	if ($isChild) 
	{
		// Get array of new records being created
		$newRecords = array_diff(array_keys($records), $existingRecords);
		
		// See if new records being added exist in parent
		$existingRecordsParent = array();
		$sql = "select distinct d.record from redcap_data d, redcap_projects p 
				where p.project_id = d.project_id and p.project_name = '{$Proj->project['is_child_of']}' 
				and d.field_name = '$primaryKey'
				and d.record in (" . prep_implode($newRecords) . ")";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q)) {
			$existingRecordsParent[] = $row['record'];
		}
		$nonExistingRecordsParent = array_diff($newRecords, $existingRecordsParent);
		unset($existingRecordsParent,$newRecords);
		
		// throw error if found records being created in child that don't exist in the parent
		if (count($nonExistingRecordsParent) > 0) {
			$message_string = 'Given that this is a Child project linked to a Parent project, you can create new records in the '
				. 'Child only if they already exist in the Parent. The records listed below do not exist in the Parent project. '
				. 'Please either create them in the Parent project or remove them from the import, and then try again';
			if ($returnFormat == "json") {
				$message = '{"error": "'.$message_string.'", ';
				$message .= '"records": [ "'. implode('", "', $nonExistingRecordsParent) .'" ]}';
			}
			elseif ($returnFormat == "xml") {
				$message = "<error>$message_string</error><records>";
				foreach ($nonExistingRecordsParent as $item) {
					$message .= "<record>$item</record>";
				}
				$message .= "</records>";
			}
			else {
				$message = "Error - $message_string:<br/>";
				$message .= implode(", ", $nonExistingRecordsParent);
			}
			throw new Exception($message);
		}
	}
	
	# create array of all form fields
	$fullFieldList = array_merge($fieldList, array_keys($checkboxFields));
	
	// If redcap_data_access_group is in the field list AND user is NOT in a DAG, then set flag
	$importDags = ($dataAccessGroupId == "" && in_array('redcap_data_access_group', $fullFieldList));
	
	# get all metadata information
	$metaData = MetaData::getFields2($project_id, $fullFieldList);

	# create list of fields based off the metadata to determine later if any fields are trying to be 
	# uploaded that are not in the metadata
	$rsMeta = db_query("SELECT field_name FROM redcap_metadata WHERE project_id = $project_id ORDER BY field_order");
	$metadataFields = array();
	while($row = db_fetch_array($rsMeta))
	{
		if (isset($checkboxFields[$row['field_name']]))
		{
			foreach ($checkboxFields[$row['field_name']] as $code => $label) {
				$metadataFields[] = $row['field_name'] . "___" . strtolower($code);
			}
		}
		else
		{
			$metadataFields[] = $row['field_name'];
		}
	}
	
	$unknownFields = array();
	foreach ($fieldList as $field)
	{
		if ( $field != "redcap_event_name" && $field != "redcap_data_access_group" && !in_array($field, $metadataFields)
			&& !in_array($field, $reserved_field_names2) && !isset($reserved_field_names[$field]))
			$unknownFields[] = $field;
	}
	
	if ( count($unknownFields) > 0)
	{
		if ($returnFormat == "json") {
			$message = '{"error": "The following fields were not found in the project", ';
			$message .= '"fields": [ "'. implode('", "', $unknownFields) .'" ]}';
		}
		elseif ($returnFormat == "xml") {
			$message = "<error>The following fields were not found in the project</error><fields>";
			foreach ($unknownFields as $item) {
				$message .= "<field>$item</field>";
			}
			$message .= "</fields>";
		}
		else {
			$message = "The following fields were not found in the project\n";
			$message .= implode("\n", $unknownFields);
		}

		throw new Exception($message);
	}
	
	// Check and fix any case sensitivity issues in record names
	$records = checkRecordNameCaseSensitive($records);
	
	// Set new id's from records array keys
	$newIds = array();
	foreach ($records as $key => $record) {
		if (!in_array($record[$primaryKey]['new'], $newIds, true)) {
			$newIds[] = $record[$primaryKey]['new'];
		}
	}
	
	# if user is in a DAG, filter records accordingly
	$dagIds = array();
	if ($dataAccessGroupId != "") 
	{
		// Get records already in our DAG
		$sql = "SELECT record FROM redcap_data where project_id = $project_id and field_name = '__GROUPID__' 
				AND value = '$dataAccessGroupId'";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q)) {
			$dagIds[] = $row['record'];
		}
	}
	
	# Load any existing values for the records being imported
	$existingIdList = array();
	if ($longitudinal)
	{
		// check the event that was entered to make sure it is valid
		$eventIds = Event::getEventIdByKey($project_id, $eventList);
		foreach($eventIds as $index => $id) {
			if ($id == '') throw new Exception("One or more of the event names you entered was not valid");
		}
		
		$sql = "SELECT * 
				FROM redcap_data 
				WHERE project_id = $project_id 
					AND field_name IN ('" . implode("','", $fullFieldList) . "') 
					AND record IN ('" . implode("','", $newIds) . "')
					AND event_id IN (" . implode(",", $eventIds) . ")";
		$rsExistingData = db_query($sql);

		while ($row = db_fetch_assoc($rsExistingData))
		{
			$key = $row['record'].' ('.Event::getEventNameById($project_id, $row['event_id']).')';
			
			if (isset($records[$key]))
			{
				if ( isset($checkboxFields[$row['field_name']]) )
				{
					$fieldname = $row['field_name']."___".strtolower($row['value']);
					$records[$key][$fieldname]['old'] = "1";
				}
				else
				{
					$fieldname = $row['field_name'];
					$records[$key][$fieldname]['old'] = $row['value'];

                    # if the old value is blank set flag
                    if ($row['value'] == "") {
                        $records[$key][$fieldname]['old_blank'] = true;
                    }
				}
				
				// add id to list if not already in there
				//if ( !in_array(strtolower($row['record']), array_map('strtolower', $existingIdList), true) ) {
				if ( !in_array($row['record'], $existingIdList, true) ) {
					$existingIdList[] = $row['record'];
				}
			}
		}
	}
	else
	{
		$sql = "SELECT * 
				FROM redcap_data 
				WHERE project_id = $project_id 
					AND field_name IN ('" . implode("','", $fullFieldList) . "') 
					AND record IN ('" . implode("','", $newIds) . "')
				ORDER BY abs(record), record, event_id";
		$rsExistingData = db_query($sql);

		while ($row = db_fetch_assoc($rsExistingData))
		{
			if ( isset($checkboxFields[$row['field_name']]) )
			{
				$fieldname = $row['field_name']."___".strtolower($row['value']);
				$records[$row['record']][$fieldname]['old'] = "1";
			}
			else
			{
				$fieldname = $row['field_name'];
				$records[$row['record']][$fieldname]['old'] = $row['value'];

                # if the old value is blank set flag
                if ($row['value'] == "") {
                    $records[$row['record']][$fieldname]['old_blank'] = true;
                }
			}
			
			// add id to list if not already in there
			//if ( !in_array(strtolower($row['record']), array_map('strtolower', $existingIdList), true) ) {
			if ( !in_array($row['record'], $existingIdList, true) ) {
				$existingIdList[] = $row['record'];
			}
		}
	}
	
	// DAGS: If user is in a DAG and is trying to edit a record not in their DAG, return error
	if ($dataAccessGroupId != "") 
	{
		// Get records not in users DAG
		$idsNotInDag = array_diff($existingIdList, $dagIds);
		if (!empty($idsNotInDag)) {
			// ERROR: User is trying to edit records not in their DAG. return error.
			$idsNotInDagErrMsg = "Error: The import could not complete because the records listed here already exist but do not belong to the user's Data Access Group";
			if ($returnFormat == "json") {
				$message = '{"error": "'.$idsNotInDagErrMsg.'",';
				$message .= '"records": [{"record": "'.implode('"},{"record": "', $idsNotInDag).'"}]}';
			} elseif ($returnFormat == "xml") {
				$message = "<error>$idsNotInDagErrMsg</error>";
				$message .= "<records><record>".implode("</record><record>", $idsNotInDag)."</record></records>";
			} elseif ($returnFormat == "csv") {
				$message =  "$idsNotInDagErrMsg\nrecords\n".implode("\n", $idsNotInDag);
			}
			throw new Exception($message);
		}
	}
	// DAGS: If user is not in a DAG but is importing unique DAG names, validate them.
	elseif ($importDags)
	{
		$invalidGroupNames = array();
		// Validate the unique group names submitted. If invalid, return error.
		foreach ($records as $studyId => $record) {
			// Get group name
			$group_name = $record['redcap_data_access_group']['new'];
			// Ignore if blank
			if ($group_name != '' && !$Proj->uniqueGroupNameExists($group_name)) {
				$invalidGroupNames[] = $group_name;
			}
		}
		$invalidGroupNames = array_unique($invalidGroupNames);
		// Check for errors
		if (!empty($invalidGroupNames)) {
			// ERROR: Group name is valid. Return error.
			$invalidGroupNamesErrMsg = "Error: The import could not complete because the Data Access Group names listed here are invalid for the redcap_data_access_group field";
			if ($returnFormat == "json") {
				$message = '{"error": "'.$invalidGroupNamesErrMsg.'",';
				$message .= '"errors": [{"redcap_data_access_group": "'.implode('"},{"redcap_data_access_group": "', $invalidGroupNames).'"}]}';
			} elseif ($returnFormat == "xml") {
				$message = "<error>$invalidGroupNamesErrMsg</error>";
				$message .= "<errors><redcap_data_access_group>".implode("</redcap_data_access_group><redcap_data_access_group>", $invalidGroupNames)."</redcap_data_access_group></errors>";
			} elseif ($returnFormat == "csv") {
				$message =  "$invalidGroupNamesErrMsg\nredcap_data_access_group\n".implode("\n", $invalidGroupNames);
			}
			throw new Exception($message);
		}
		## If no errors exist, then get existing DAG designations and add to $records for each record
		$sql = "select record, event_id, value from redcap_data where project_id = $project_id and field_name = '__GROUPID__'
				and record in ('".implode("', '", $existingIdList)."')";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q)) 
		{
			$key = $row['record'] . " (" . $Proj->getUniqueEventNames($row['event_id']) . ")";
			if (isset($records[$key]) && is_numeric($row['value'])) {
				// Obtain and verify unique group name
				$group_name = $Proj->getUniqueGroupNames($row['value']);
				if (!empty($group_name)) {
					// Add exist group name to $records
					$records[$key]['redcap_data_access_group']['old'] = $group_name;
				}
			}
		}
	}
	
	# compare new and old values and set status
	foreach ($records as $studyId => $record)   // loop through each record
	{
		foreach ($record as $fieldname => $data)    // loop through each field
		{
			if ( isset($data['new']) && $metaData[$fieldname]['element_type'] != 'file' && $fieldname != "redcap_event_name")
			{
				$newValue = $data['new'];
				$oldValue = html_entity_decode($data['old'], ENT_QUOTES);
                $isOldValueBlank = (isset($data['old_blank']));				
				
				## PERFORM SOME PRE-CHECKS FIRST FOR FORMATTING ISSUES OF CERTAIN VALIDATION TYPES		
				// Ensure all dates are in correct format (yyyy-mm-dd hh:mm and yyyy-mm-dd hh:mm:ss)
				if (substr($metaData[$fieldname]['element_validation_type'], 0, 8) == 'datetime')
				{
					if ($newValue != "")
					{
						// Break up into date and time
						list ($thisdate, $thistime) = explode(' ', $newValue, 2);
						if (strpos($records[$studyId][$fieldname]['new'],"/") !== false) {
							if (substr($metaData[$fieldname]['element_validation_type'], 0, 16) == 'datetime_seconds') {
								if (strlen($thistime) < 8) $thistime = "0".$thistime;
							} else {
								if (strlen($thistime) < 5) $thistime = "0".$thistime;
							}
							// Assume American format (mm/dd/yyyy) if contains forward slash
							list ($month, $day, $year) = explode('/', $thisdate);
							// Make sure year is 4 digits
							if (strlen($year) == 2) {
								$year = ($year < (date('y')+10)) ? "20".$year : "19".$year;
							}
							$records[$studyId][$fieldname]['new'] = $newValue = sprintf("%04d-%02d-%02d", $year, $month, $day) . ' ' . $thistime;
						} else {
							// Make sure has correct amount of digits with proper leading zeros
							$records[$studyId][$fieldname]['new'] = $newValue = clean_date_ymd($thisdate) . " " . $thistime;
						}
					}
				}
				// First ensure all dates are in correct format (yyyy-mm-dd)
				elseif (substr($metaData[$fieldname]['element_validation_type'], 0, 4) == 'date')
				{
					if ($newValue != "")
					{
						if (strpos($records[$studyId][$fieldname]['new'],"/") !== false) {
							// Assume American format (mm/dd/yyyy) if contains forward slash
							list ($month, $day, $year) = explode('/', $newValue);
							// Make sure year is 4 digits
							if (strlen($year) == 2) {
								$year = ($year < (date('y')+10)) ? "20".$year : "19".$year;
							}
							$records[$studyId][$fieldname]['new'] = $newValue = sprintf("%04d-%02d-%02d", $year, $month, $day);
						} else {
							// Make sure has correct amount of digits with proper leading zeros
							$records[$studyId][$fieldname]['new'] = $newValue = clean_date_ymd($newValue);
						}
					}
				} 		
				// Ensure all times are in correct format (hh:mm)
				elseif ($metaData[$fieldname]['element_validation_type'] == 'time' && strpos($records[$studyId][$fieldname]['new'],":") !== false) 
				{
					if (strlen($newValue) < 5) {
						$records[$studyId][$fieldname]['new'] = $newValue = "0".$newValue;
					}
				} 
				// Vanderbilt MRN: Remove any non-numerical characters. Add leading zeros, if needed.
				elseif ($metaData[$fieldname]['element_validation_type'] == 'vmrn') 
				{					
					$records[$studyId][$fieldname]['new'] = $newValue = sprintf("%09d", preg_replace("/[^0-9]/", "", $newValue));
				}	
				// Phone: Remove any unneeded characters
				elseif ($metaData[$fieldname]['element_validation_type'] == 'phone') 
				{
					$tempVal = str_replace(array(".","(",")"," "), array("","","",""), $newValue);
					if (strlen($tempVal) >= 10 && is_numeric(substr($tempVal, 0, 10))) {
						// Now add our own formatting
						$records[$studyId][$fieldname]['new'] = $newValue = trim("(" . substr($tempVal, 0, 3) . ") " . substr($tempVal, 3, 3) . "-" . substr($tempVal, 6, 4) . " " . substr($tempVal, 10));
					}
				}
				
				
				# determine the action to take with the data
				if ($oldValue == "" && $newValue != "")
				{
					# if the old value is blank but the new value isn't blank, then this is a new value being imported
                    if ($isOldValueBlank)
					    $records[$studyId][$fieldname]['status'] = 'update';
                    else
                        $records[$studyId][$fieldname]['status'] = 'add';
				}
				elseif ($oldValue != "" && $newValue == "")
                {
                    # if the import action is 'overwrite' and the new value is blank, update the data
                    if ($overwriteBehavior == "overwrite")
                        $records[$studyId][$fieldname]['status'] = 'update';
                    else
                        $records[$studyId][$fieldname]['status'] = 'keep';
                }
				elseif ($newValue == $oldValue)
				{
					# if the new value equals the old value, then nothing is changed
					$records[$studyId][$fieldname]['status'] = 'keep';
				}
				else
				{
					$records[$studyId][$fieldname]['status'] = 'update';
				}
			}
			else
			{
				# do nothing -- there are no values for this field in the import data
				$records[$studyId][$fieldname]['status'] = 'keep';
			}
		}
	}
	
	# Perform validation against the metadata on new and updated data fields
	$errors = 0;
    $warnings = 0;
	$records = ValidateData($records, $metaData, $fullCheckboxFields, $errors, $warnings);

	# if there were any errors, out them and end the process (ignore any warnings, for now)
	if ($errors > 0) // || $warnings > 0)
	{
		if ($returnFormat == "json")
		{
			$message = '{"error": "There were data validation errors",';
			$message .= '"records": [';

			foreach ($records as $studyId => $record)
			{
				foreach ($record as $fieldname => $data)
				{
					if (isset($records[$studyId][$fieldname]['validation']))
					{
						if ($records[$studyId][$fieldname]['validation'] == 'error')
						{
							$message .= '{"record": "'.$studyId.'", "field_name": "'.$fieldname.'",
								"value": "'.$records[$studyId][$fieldname]['new'].'",
								"message": "'.$records[$studyId][$fieldname]['message'].'"},';
						}
						/*elseif ($records[$studyId][$fieldname]['validation'] == 'warning')
						{
							$message .= '{"record": "'.$studyId.'", "field_name": "'.$fieldname.'",
								"value": "'.$records[$studyId][$fieldname]['new'].'",
								"message": "'.$records[$studyId][$fieldname]['message'].'"},';
						}*/
					}

					// Field name does not exist in database
					if ( !in_array($fieldname, $metadataFields) && $fieldname != "redcap_event_name" )
					{
						$message .= '{"record": "'.$studyId.'", "field_name": "'.$fieldname.'",
							"value": "'.$records[$studyId][$fieldname]['new'].'", ';

						if (isset($checkboxFields[$fieldname]))
						{
							$message .= '"message": "CHECKBOX RENAME ERROR: Although this field does exist in the database, it is a \"checkbox\" field type,
								and checkboxes must be converted to a new variable name format to be imported, as follows: field name + triple underscore + coded value.
								For example, if coded value \"2\" (Asian) is checked off for field \"race\", then the field name should be \"race___2\" and
								the value should be \"1\" (1 for checked, 0 for unchecked). Please rename this field."';
						}
						else
						{
							$message .= '"message": "This field name does not exist in the database."';
						}

						$message .= "},";
					}
				}
			}

			$message = substr($message, 0, -1)."]}";
		}
		elseif ($returnFormat == "xml")
		{
			$message = "<error>There were data validation errors</error>";

			foreach ($records as $studyId => $record)
			{
				foreach ($record as $fieldname => $data)
				{
					if (isset($records[$studyId][$fieldname]['validation']))
					{
						if ($records[$studyId][$fieldname]['validation'] == 'error')
						{
							$message .= "<field><record>$studyId</record>";
							$message .= "<field_name>$fieldname</field_name>";
							$message .= "<value>" . $records[$studyId][$fieldname]['new'] . "</value>";
							$message .= "<message>" . $records[$studyId][$fieldname]['message'] . "</message></field>\n";
						}
						/*elseif ($records[$studyId][$fieldname]['validation'] == 'warning')
						{
							$message .= "<field><record>$studyId</record>";
							$message .= "<field_name>$fieldname</field_name>";
							$message .= "<value>" . $records[$studyId][$fieldname]['new'] . "</value>";
							$message .= "<message>" . $records[$studyId][$fieldname]['message'] . "</message></field>\n";
						}*/
					}

					// Field name does not exist in database
					if ( !in_array($fieldname, $metadataFields) && $fieldname != "redcap_event_name" )
					{
						$message .= "<field><record>$studyId</record>\n";
						$message .= "<field_name>$fieldname</field_name>\n";

						if ($records[$studyId][$fieldname]['new'] == "")
							$message .= "<value>&nbsp;</value>\n";
						else
							$message .= "<value>" . $records[$studyId][$fieldname]['new'] . "</value>\n";

						if (isset($checkboxFields[$fieldname]))
						{
							$message .= "<message>CHECKBOX RENAME ERROR: Although this field does exist in the database, it is a \"checkbox\" field type,
								and checkboxes must be converted to a new variable name format to be imported, as follows: field name + triple underscore + coded value.
								For example, if coded value \"2\" (Asian) is checked off for field \"race\", then the field name should be \"race___2\" and
								the value should be \"1\" (1 for checked, 0 for unchecked). Please rename this field.</message>";
						}
						else
						{
							$message .= "<message>This field name does not exist in the database.</message>";
						}

						$message .= "</field>\n";
					}
				}
			}
		}
		else
		{
			$message =  "There were data validation errors\n";
			$message .= "record, field_name, value, message\n";

			foreach ($records as $studyId => $record)
			{
				foreach ($record as $fieldname => $data)
				{
					if (isset($records[$studyId][$fieldname]['validation']))
					{
						if ($records[$studyId][$fieldname]['validation'] == 'error')
						{
							$message .= "$studyId, $fieldname, ";
							$message .= '"' . $records[$studyId][$fieldname]['new'] . '", ';
							$message .= '"' . $records[$studyId][$fieldname]['message'] . "\"\n";
						}
						/*elseif ($records[$studyId][$fieldname]['validation'] == 'warning')
						{
							$message .= "$studyId, $fieldname, ";
							$message .= '"' . $records[$studyId][$fieldname]['new'] . '", ';
							$message .= '"' . $records[$studyId][$fieldname]['message'] . "\"\n";
						}*/
					}

					// Field name does not exist in database
					if ( !in_array($fieldname, $metadataFields) && $fieldname != "redcap_event_name" )
					{
						$message .= "$studyId, $fieldname, ";

						if ($records[$studyId][$fieldname]['new'] == "")
							$message .= ", ";
						else
							$message .= '"' . $records[$studyId][$fieldname]['new'] . '", ';

						if (isset($checkboxFields[$fieldname]))
						{
							$message .= '"CHECKBOX RENAME ERROR: Although this field does exist in the database, it is a \'checkbox\' field type,
								and checkboxes must be converted to a new variable name format to be imported, as follows: field name + triple underscore + coded value.
								For example, if coded value \'2\' (Asian) is checked off for field \'race\', then the field name should be \'race___2\' and
								the value should be \'1\' (1 for checked, 0 for unchecked). Please rename this field."';
						}
						else
						{
							$message .= '"This field name does not exist in the database."';
						}

						$message .= "\n";
					}
				}
			}
		}

		throw new Exception($message);
	} // end if (error/warning)
			
	// If not Longitudinal, get single event_id
	if (!$longitudinal)
	{
		$singleEventId = $Proj->firstEventId;		
	}

	$counter = 0;
	$updatedIds = array();
	
	// Set values needed for logging
	$sqlu = "select username from redcap_user_rights where api_token = '" . prep($post['token']) . "'";
	defined("USERID") or define("USERID", db_result(db_query($sqlu), 0));
	defined("PROJECT_ID") or define("PROJECT_ID", $post['projectid']);
	
	// Regardless of project type, first check to see if any survey responses exist (in case they changed project type in order to do import).
	// Copy any completed responses to surveys_response_values table if not there already
	$sql = "SELECT r.response_id, r.record, e.event_id FROM 
			redcap_surveys s, redcap_surveys_participants p, redcap_surveys_response r, redcap_events_metadata e 
			WHERE s.project_id = ".PROJECT_ID." and s.survey_id = p.survey_id AND p.participant_id = r.participant_id 
			AND r.completion_time is not null and e.event_id = p.event_id";
	$q = db_query($sql);
	$completedResponses = array();
	while ($row = db_fetch_assoc($q)) {
		$completedResponses[$row['record']][$row['event_id']] = $row['response_id'];
	}
	
	## INSTANTIATE SURVEY INVITATION SCHEDULE LISTENER
	// If the form is designated as a survey, check if a survey schedule has been defined for this event.
	// If so, perform check to see if this record/participant is NOT a completed response and needs to be scheduled for the mailer.
	$surveyScheduler = new SurveyScheduler();
	
	// Create array to place record-events to be assigned to a DAG
	$dagRecordEvent = array();	
	
	# import records into database
	foreach ($records as $studyId => $record)
	{
		// Clear array values for this record
		$sql_all = array();
		$display = array();
		
		// get id for record
		$id = prep($records[$studyId][$primaryKey]['new']);

		if (!in_array($id, $updatedIds)) {
			$updatedIds[] = $id;
		}
		
		// Get event id for this record
		$_GET['event_id'] = $thisEventId = ($longitudinal) ? $events[$record['redcap_event_name']['new']] : $singleEventId;
		
		// COPY COMPLETED RESPONSE: If this record-event is a completed survey response, then check if needs to be
		// copied to surveys_response_values table to preserve the pristine completed original response.
		if (isset($completedResponses[$studyId][$thisEventId])) {
			// Copy original response (if not copied already)
			copyCompletedSurveyResponse($completedResponses[$studyId][$thisEventId]);
			// Free up memory
			unset($completedResponses[$studyId][$thisEventId]);
		}
		
		// Loop through all values for this record
		foreach ($record as $fieldname => $data)
		{
			// If importing DAGs, collect their values in an array to perform DAG designation later
			if ($importDags && $fieldname == 'redcap_data_access_group') {
				$dagRecordEvent[$record[$Proj->table_pk]['new']][$thisEventId] = $data['new'];
				continue;
			}
			
			// Ignore pseudo-fields
			if (in_array($fieldname, $reserved_field_names2) || isset($reserved_field_names[$fieldname])) {
				continue;
			}
			
			if ($records[$studyId][$fieldname]['status'] != 'keep')
			{
				// CHECKBOXES
				if (isset($fullCheckboxFields[$fieldname]))
				{
					// Since only checked values are saved in data table, we must ONLY do either Inserts or Deletes. Reconfigure.
					if ($data['new'] == "1" && ($data['old'] == "0" || $data['old'] == ""))
					{
						// If changed from "0" to "1", change to Insert
						$records[$studyId][$fieldname]['status'] = 'add';
					}
					elseif ($data['new'] == "0" && $data['old'] == "1")
					{
						// If changed from "1" to "0", change to Delete
						$records[$studyId][$fieldname]['status'] = 'delete';
					}
					
					// Re-configure checkbox variable name and value
					list ($data['new'], $field) = explode("___", strrev($fieldname), 2);
					$field = strrev($field);
					$data['new'] = strrev($data['new']);
					
					// Since users can designate capital letters as checkbox codings AND because variable names force those codings to lower case,
					// we need to loop through this field's codings to find the matching coding if it's not numerical, otherwise it might get saved 
					// incorrectly because of case insensitivity.
					if (!is_numeric($data['new']))
					{
						foreach (array_keys($checkboxFields[$field]) as $this_code)
						{
							if (strtolower($this_code) == $data['new'])
								$data['new'] = $this_code;
						}
					}
				}
				// NON-CHECKBOXES
				else
				{
					// Regular fields keep same variable name 
					$field = $fieldname;
				}
				
				$value = prep($data['new']);
				
				// insert query
				if ($records[$studyId][$fieldname]['status'] == 'add')
				{
					$sql_all[] = $sql = "INSERT INTO redcap_data 
										 VALUES ($project_id, 
												 $thisEventId, 
												 '$id', 
										 		 '$field', 
										 		 '$value')";
				}
				// update query
				elseif ($records[$studyId][$fieldname]['status'] == 'update')
				{
					$sql_all[] = $sql = "UPDATE redcap_data 
										 SET value = '$value' 
										 WHERE project_id = $project_id 
										 	AND record = '$id' 
										 	AND field_name = '$field' 
										 	AND event_id = $thisEventId";
				}
				// delete query (only for checkboxes)
				elseif ($records[$studyId][$fieldname]['status'] == 'delete')
				{
					$sql_all[] = $sql = "DELETE FROM redcap_data 
										 WHERE project_id = $project_id
											AND record = '$id' 
											AND field_name = '$field' 
											AND event_id = $thisEventId 
											AND value = '$value'";
				}
				
				//echo "$sql<br/>";
				if ( !db_query($sql) )
				{
					throw new Exception("For unknown reasons, the data import failed when trying to update the 
						database with the new information.  Because of that no data was imported, and no changes were made. 
						Please try executing the procedure again.");
				}
				
				if (isset($fullCheckboxFields[$fieldname]))
				{
					// Checkbox logging display
					$display[] = "$field({$data['new']}) = " . (($records[$studyId][$fieldname]['status'] == 'add') ? "checked" : "unchecked");
				}
				else
				{
					// Logging display for normal fields
					$display[] = "$field = '{$data['new']}'";
				}
			} // end if status check
			
			if ($type == "eav") $counter++;
		} //end inside foreach loop
		
		# If user is in a Data Access Group, do insert query for Group ID number so that record will be tied to that group
		if ($dataAccessGroupId != "")
		{
			// If record did not exist previously, then add group_id value for it
			if ( !in_array($id, $existingIdList, true) )
			{
				// Add to data table
				$sql = "INSERT INTO redcap_data VALUES ($project_id, $thisEventId, '".prep($id)."', '__GROUPID__', '$dataAccessGroupId')";
				if (!db_query($sql))
				{
					throw new Exception("For unknown reasons, the data import failed when trying to update the 
						database with the new information.  Because of that no data was imported, and no changes were made. 
						Please try executing the procedure again.");
				}
			}
		}
		
		// Logging - determine if we're updating an existing record or creating a new one
		if (!empty($sql_all))
		{
			if (in_array($id, $existingIdList, true)) {
				$this_event_type  = "update";
				$this_log_descrip = "Update record (API)";
			} else {
				$this_event_type  = "insert";
				$this_log_descrip = "Create record (API)";
			}
			// Log it
			log_event(implode(";\n", $sql_all), "redcap_data", $this_event_type, $id, implode(",\n", $display), $this_log_descrip);
		}
		
		// SURVEY INVITATION SCHEDULER: Return count of invitation scheduled, if any
		if (!empty($Proj->surveys)) {
			$numInvitationsScheduled = $surveyScheduler->checkToScheduleParticipantInvitation($studyId);
		}
		
		// Counter increment
		if ($type == "flat") $counter++;
	} // end outside foreach loop
	
	
	# If importing DAGs by user NOT in a DAG
	if ($importDags) 
	{
		// Loop through each record-event and set DAG designation
		foreach ($dagRecordEvent as $record=>$eventdag) 
		{
			// Set flag to log DAG designation
			$dag_sql_all = array();			
			// Loop through each event in this record
			foreach ($eventdag as $event_id=>$group_name) 
			{
				// Ignore if group name is blank UNLESS special flag is set
				if ($group_name == '' && $overwriteBehavior != 'overwrite') continue;
				// Delete existing values first
				if ($group_name == '' && $overwriteBehavior == 'overwrite') {
					// Clear out existing values for ALL EVENTS if group is blank AND overwrite behavior is "overwrite"
					$sql = $dag_sql_all[] = "DELETE FROM redcap_data WHERE project_id = $project_id AND record = '".prep($record)."' 
							AND field_name = '__GROUPID__'";
				} else {
					// Clear out any existing values for THIS EVENT before adding this one
					$sql = $dag_sql_all[] = "DELETE FROM redcap_data WHERE project_id = $project_id AND record = '".prep($record)."' 
							AND field_name = '__GROUPID__' AND event_id = $event_id";
				}
				db_query($sql);
				// Add to data table if group_id not blank
				if ($group_name != '') {
					// Get group_id
					$group_id = array_search($group_name,  $Proj->getUniqueGroupNames());
					// Update ALL OTHER EVENTS to new group_id (if other events have group_id stored)
					$sql = $dag_sql_all[] = "UPDATE redcap_data SET value = '$group_id' WHERE project_id = $project_id 
							AND record = '".prep($record)."' AND field_name = '__GROUPID__'";
					db_query($sql);
					// Insert group_id for THIS EVENT
					$sql = $dag_sql_all[] = "INSERT INTO redcap_data VALUES ($project_id, $event_id, '".prep($record)."', '__GROUPID__',  '$group_id')";
					db_query($sql);
					// Update any calendar events tied to this group_id
					$sql = $dag_sql_all[] = "UPDATE redcap_events_calendar SET group_id = " . checkNull($group_id) . " 
							WHERE project_id = $project_id AND record = '" . prep($record) . "'";
					db_query($sql);
				}
			}
			// Log DAG designation (if occurred)
			$key = ($longitudinal) ? $record . " (" . $Proj->getUniqueEventNames($event_id) . ")" : $record;
			if ($records[$key]['redcap_data_access_group']['status'] != 'keep' && isset($dag_sql_all) && !empty($dag_sql_all)) 
			{
				$dag_log_descrip = ($group_name == '') ? "Remove record from Data Access Group (API)" : "Assign record to Data Access Group (API)";
				log_event(implode(";\n",$dag_sql_all), "redcap_data", "update", $record, "redcap_data_access_group = '$group_name'", $dag_log_descrip);
			}
		}
	}

	# return appropriate data based off of flag
	if ($returnContent == "ids")
		$response = $updatedIds;
	elseif ($returnContent == "count")
		$response = $counter;
	else
		$response = "";

	return $response;
}

function ValidateData($records, $metaData, $checkboxFields, &$errors, &$warnings)
{
	global $longitudinal, $post, $events, $primaryKey, $lang, $secondary_pk, $Proj, 
		   $reserved_field_names, $reserved_field_names2;

	$overwriteBehavior = $post['overwriteBehavior'];

	$totalErrors = 0;
	$totalWarnings = 0;
	
	## LOCKING CHECK: Get all forms that are locked for the uploaded records
	if ($longitudinal)
	{
		// Collect real names of records for query (w/o event_name appended)
		$records_real = array();
		foreach ($records as $this_one=>$attr) {
			foreach ($attr as $this_field=>$attr2) {
				if ($this_field == $primaryKey) {
					$records_real[] = $attr2['new'];
				}
			}
		}
		$sql = "select l.record, l.event_id, m.field_name, m.element_type, m.element_enum from redcap_locking_data l, redcap_metadata m 
				where m.project_id = ".$post['projectid']." and l.record in ('".implode("', '", $records_real)."') 
				and l.event_id in (".implode(", ", $events).") 
				and m.field_name in ('".implode("', '", array_keys($metaData))."')
				and l.project_id = m.project_id and m.form_name = l.form_name";
	}
	else
	{
		$sql = "select l.record, l.event_id, m.field_name, m.element_type, m.element_enum from redcap_locking_data l, redcap_metadata m 
				where m.project_id = ".$post['projectid']." and l.record in ('".implode("', '", array_keys($records))."') 
				and l.event_id = ".getSingleEvent($post['projectid'])." and m.field_name in ('".implode("', '", array_keys($metaData))."')
				and l.project_id = m.project_id and m.form_name = l.form_name";
	}
	$locked = array();
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q)) 
	{
		if ($row['element_type'] == 'checkbox') {
			foreach (array_keys(parseEnum($row['element_enum'])) as $this_code) {
				$chkbox_field_name = $row['field_name'] . "___" . strtolower($this_code);
				$locked[$row['record']][$row['event_id']][$chkbox_field_name] = "";
			}
		} else {
			$locked[$row['record']][$row['event_id']][$row['field_name']] = "";
		}
	}
	
	// Obtain an array of all Validation Types (from db table)
	$valTypes = getValTypes();
	
	// Force all dates to be validated in YYYY-MM-DD format (any that were imported as M/D/Y will have been reformatted to YYYY-MM-DD)
	foreach ($metaData as $fieldname=>$fieldattr)
	{
		$metaData[$fieldname]['element_validation_type'] = convertLegacyValidationType($fieldattr['element_validation_type']);
	}
		
	// Is randomization enabled and setup?
	$randomizationIsSetUp = Randomization::setupStatus();
	if ($randomizationIsSetUp)
	{
		$randomizationCriteriaFields = Randomization::getRandomizationFields(true);
		$randTargetField = array_shift($randomizationCriteriaFields);
		if ($longitudinal) {
			$randTargetEvent = array_shift($randomizationCriteriaFields);
		} else {
			$randTargetEvent = $event_id = getSingleEvent($post['projectid']);
		}
		$randCritFieldsEvents = array();
		while (!empty($randomizationCriteriaFields)) {
			$field = array_shift($randomizationCriteriaFields);
			if ($longitudinal) {
				$event_id = array_shift($randomizationCriteriaFields);
			}
			$randCritFieldsEvents[$field] = $event_id;
		}
	}
	
	// Create array of records that are survey responses (either partial or completed)
	$responses = array();
	if (!empty($records) && !empty($Proj->surveys)) {
		$sql = "select r.record, p.event_id, p.survey_id from redcap_surveys_participants p, redcap_surveys_response r 
				where p.survey_id in (".prep_implode(array_keys($Proj->surveys)).") and p.participant_id = r.participant_id 
				and r.record in (".prep_implode(array_keys($records)).") and r.first_submit_time is not null";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q)) {
			// Add record-event_id-survey_id to array
			$responses[$row['record']][$row['event_id']][$row['survey_id']] = true;
		}
	}
	
	// If using SECONDARY UNIQUE FIELD, then check for any duplicate values in imported data
	$checkSecondaryPk = ($secondary_pk != '' && isset($metaData[$secondary_pk]));
	
	foreach ($records as $studyId => $record)
	{
				
		// Retrieve the current event_id (used for Locking)
		if ($longitudinal) {
			$this_event_id = $events[$record['redcap_event_name']['new']];
		} else {
			$this_event_id = isset($this_event_id) ? $this_event_id : getSingleEvent($post['projectid']);
		}
		
		foreach ($record as $fieldname => $data)
		{
			//if the field contains new or updated data, then check it against the metadata
			if($records[$studyId][$fieldname]['status'] == 'add' || $records[$studyId][$fieldname]['status'] == 'update')
			{
				$newValue = $records[$studyId][$fieldname]['new'];
				$oldValue = $records[$studyId][$fieldname]['old'];
				
				// Record name cannot be empty
				if (trim($studyId) == "") throw new Exception("study id");
				
				// PREVENT SURVEY COMPLETE STATUS MODIFICATION
				// If this is a form status field for a survey response, then prevent from modifying it
				$fieldForm = $Proj->metadata[$fieldname]['form_name'];
				if ($fieldname == $fieldForm."_complete" && isset($Proj->forms[$fieldForm]['survey_id'])
					&& isset($responses[$studyId][$this_event_id][$Proj->forms[$fieldForm]['survey_id']])) {
					$records[$studyId][$fieldname]['validation'] = 'error'; 
					$records[$studyId][$fieldname]['message'] = cleanHtml2($lang['survey_403']);
					$totalErrors++;
				}
				
				// LOCKING CHECK: Ensure that this field's form is not locked. If so, then give error and force user to unlock form before proceeding.
				if (isset($locked[$record[$primaryKey]['new']][$this_event_id][$fieldname]))
				{
					$records[$studyId][$fieldname]['validation'] = 'error'; 
					$records[$studyId][$fieldname]['message'] = "This field is located on a form that is locked. You must first unlock this form for this record";
					$totalErrors++;
				}
				
				if (isset($metaData[$fieldname]['element_validation_type']))
				{
					if (!empty($newValue))
					{
						## Use RegEx to evaluate the value based upon validation type
						// Set regex pattern to use for this field
						$regex_pattern = $valTypes[$metaData[$fieldname]['element_validation_type']]['regex_php'];
						// Run the value through the regex pattern
						preg_match($regex_pattern, $newValue, $regex_matches);
						// Was it validated? (If so, will have a value in 0 key in array returned.)
						$failed_regex = (!isset($regex_matches[0]));
						// Set error message if failed regex
						if ($failed_regex)
						{
							$records[$studyId][$fieldname]['validation'] = 'error';
							$totalErrors++;
							// Validate the value based upon validation type
							switch ($metaData[$fieldname]['element_validation_type'])
							{
								case "int":
									$records[$studyId][$fieldname]['message'] = "{$lang['data_import_tool_83']} $fieldname {$lang['data_import_tool_84']}";
									break;
								case "float":
									$records[$studyId][$fieldname]['message'] = "{$lang['data_import_tool_83']} $fieldname {$lang['data_import_tool_85']}";
									break;
								case "phone":
									$records[$studyId][$fieldname]['message'] = "$fieldname {$lang['data_import_tool_86']}";
									break;
								case "email":
									$records[$studyId][$fieldname]['message'] = $lang['data_import_tool_87'];
									break;
								case "vmrn":
									$records[$studyId][$fieldname]['message'] = $lang['data_import_tool_138'];
									break;
								case "zipcode":
									$records[$studyId][$fieldname]['message'] = "$fieldname {$lang['data_import_tool_153']}";
									break;
								case "date":
								case "date_ymd":
								case "date_mdy":
								case "date_dmy":
									$records[$studyId][$fieldname]['message'] = $lang['data_import_tool_90'];
									break;
								case "time":
									$records[$studyId][$fieldname]['message'] = $lang['data_import_tool_137'];
									break;
								case "datetime":
								case "datetime_ymd":
								case "datetime_mdy":
								case "datetime_dmy":
									$records[$studyId][$fieldname]['message'] = $lang['data_import_tool_150'];
									break;
								case "datetime_seconds":
								case "datetime_seconds_ymd":
								case "datetime_seconds_mdy":
								case "datetime_seconds_dmy":
									$records[$studyId][$fieldname]['message'] = $lang['data_import_tool_150'];	
									break;
								default:
									// General regex failure message for any new, non-legacy validation types (e.g. postalcode_canada)
									$records[$studyId][$fieldname]['message'] = $lang['config_functions_77'];	
							}
						}
					}
				} //end if for having validation
				
				# If value is an enum, check that it's valid
				if ($metaData[$fieldname]['element_type'] != 'slider' && isset($metaData[$fieldname]['element_enum']) && $metaData[$fieldname]['element_enum'] != "")
				{
					// Make sure the raw value is a coded value in the enum
					if (!isset($metaData[$fieldname]["enums"][$newValue]) && $metaData[$fieldname]['element_type'] != "calc") 
					{
						if ($overwriteBehavior == "overwrite" && $newValue == "") {
							# do nothing (inserting a blank value is fine)
						}
						else {
							$records[$studyId][$fieldname]['validation'] = 'error';
							$records[$studyId][$fieldname]['message'] = "The value is not a valid category for $fieldname";
							$totalErrors++;
						}
					}
					// Calc fields cannot be uploaded
					elseif (!isset($metaData[$fieldname]["enums"][$newValue]) && $metaData[$fieldname]['element_type'] == "calc") 
					{
						$records[$studyId][$fieldname]['validation'] = 'error';
						$records[$studyId][$fieldname]['message'] = "(calc) Calculated fields cannot be imported. They can only be calculated on the data entry form. Please remove this field from the data import file";
						$totalErrors++;				
					}
				}
				
				# Check that value is within range specified in metadata (max/min), if a range is given.
				if ( isset($metaData[$fieldname]['element_validation_min']) || isset($metaData[$fieldname]['element_validation_max']) )
				{
					$elementValidationMin = $metaData[$fieldname]['element_validation_min'];
					$elementValidationMax = $metaData[$fieldname]['element_validation_max'];									
					
					//if lower bound is specified
					if ($metaData[$fieldname]['element_validation_min'] !== "")
					{
						//if new value is smaller than lower bound
						if ($newValue < $elementValidationMin)
						{
							//if hard check
							if ($metaData[$fieldname]['element_validation_checktype'] == 'hard')
							{
								$records[$studyId][$fieldname]['validation'] = 'error';
								$records[$studyId][$fieldname]['message'] = "$fieldname should not be less than the field minimum ($elementValidationMin)";
								$totalErrors++;
							}
							//if not hard check
							elseif ($records[$studyId][$fieldname]['validation'] != 'error')
							{
								$records[$studyId][$fieldname]['validation'] = 'warning';
								$records[$studyId][$fieldname]['message'] = "$fieldname is less than the field minimum ($elementValidationMin)";
								$totalWarnings++;
							}
						}								
					}
					
					//if upper bound is specified
					if ($metaData[$fieldname]['element_validation_max'] !== "")
					{				
						//if new value is greater than upper bound
						if ($newValue > $elementValidationMax)
						{
							//if hard check
							if ($metaData[$fieldname]['element_validation_checktype'] == 'hard')
							{
								$records[$studyId][$fieldname]['validation'] = 'error';
								$records[$studyId][$fieldname]['message'] = "$fieldname should not be greater than the field maximum ($elementValidationMax)";
								$totalErrors++;
							}
							//if not hard check
							elseif ($records[$studyId][$fieldname]['validation'] != 'error')
							{
								$records[$studyId][$fieldname]['validation'] = 'warning';
								$records[$studyId][$fieldname]['message'] = "$fieldname is greater than the field maximum ($elementValidationMax)";
								$totalWarnings++;
							}
						}
					}
				} //end if for range
				
				// If field is a checkbox, make sure value is either 0 or 1
				if (isset($checkboxFields[$fieldname]) && $newValue != "1" && $newValue != "0")
				{
					$records[$studyId][$fieldname]['validation'] = 'error';
					$records[$studyId][$fieldname]['message'] = "$fieldname is a checkbox field and thus can 
						only have a value of \"1\" (checked) or \"0\" (unchecked).";
					$totalErrors++;
				}
				
				// If using SECONDARY UNIQUE FIELD, then check for any duplicate values in imported data
				if ($checkSecondaryPk && $secondary_pk == $fieldname)
				{
					// Check for any duplicated values for the $secondary_pk field (exclude current record name when counting)
					$sql = "select 1 from redcap_data where project_id = ".PROJECT_ID." and field_name = '$secondary_pk' 
							and value = '" . prep($newValue) . "' and record != '" . prep($studyId) . "' limit 1";
					$q = db_query($sql);
					$uniqueValueExists = (db_num_rows($q) > 0);
					// If the value already exists for a record, then throw an error
					if ($uniqueValueExists)
					{ 
						$totalErrors++;
						$records[$studyId][$fieldname]['validation'] = 'error';
						$records[$studyId][$fieldname]['message'] = "{$lang['data_import_tool_154']} (i.e. \"$secondary_pk\"){$lang['period']} {$lang['data_import_tool_155']}";						
					}
				}
			} //end if for status check
			
			// RANDOMIZATION CHECK: Make sure that users cannnot import data into a randomiztion field OR into a criteria field
			// if the record has already been randomized
			if ($randomizationIsSetUp)
			{
				// Check if this is target randomization field, which CANNOT be edited. If so, give error.
				if ($fieldname == $randTargetField) 
				{
					$records[$studyId][$fieldname]['validation'] = 'error'; $totalErrors++;
					$records[$studyId][$fieldname]['message'] = "{$lang['data_import_tool_162']} ('$fieldname') {$lang['data_import_tool_161']}";
				} 
				// Check if this is a criteria field AND is criteria event_id AND if the record has already been randomized
				elseif (isset($randCritFieldsEvents[$fieldname]) && $randCritFieldsEvents[$fieldname] == $this_event_id 
					&& $records[$studyId][$fieldname]['new'] != "" && Randomization::wasRecordRandomized($studyId)) 
				{
					$records[$studyId][$fieldname]['validation'] = 'error'; $totalErrors++;
					$records[$studyId][$fieldname]['message'] = $Proj->table_pk_label." '$studyId' {$lang['data_import_tool_163']} ('$fieldname'){$lang['data_import_tool_164']}";
				}
			}
			
			// Field-Event Mapping Check: Make sure this field exists on a form that is designated to THIS event. If not, then error.
			if ($longitudinal && is_numeric($this_event_id) && $fieldname != 'redcap_event_name' && $fieldname != 'redcap_data_access_group' && $records[$studyId][$fieldname]['new'] != "" && $fieldname != $Proj->table_pk)
			{
				// Check fieldname (in case a modified checkbox fieldname)
				$true_fieldname = $fieldname; // Begin with default
				if (!isset($Proj->metadata[$fieldname]) && strpos($fieldname, '___') !== false) 
				{
					list ($chkbox_fieldname, $this_code) = explode('___', $fieldname);
					if (isset($Proj->metadata[$chkbox_fieldname]) && $Proj->metadata[$chkbox_fieldname]['element_type'] == 'checkbox') {
						// It is a checkbox, so set true fieldname
						$true_fieldname = $chkbox_fieldname;
					}
				}
				// Now check form-event designation
				if (!in_array($Proj->metadata[$true_fieldname]['form_name'], $Proj->eventsForms[$this_event_id])
					&& !in_array($fieldname, $reserved_field_names2) && !isset($reserved_field_names[$fieldname]))
				{
					$records[$studyId][$fieldname]['validation'] = 'error'; $totalErrors++;
					$records[$studyId][$fieldname]['message'] = "{$lang['data_import_tool_162']} ('$fieldname') {$lang['data_import_tool_165']} '{$Proj->eventInfo[$this_event_id]['name_ext']}'{$lang['period']} {$lang['data_import_tool_166']}";
				}
			}
			
			
		} //end foreach
	} //end foreach
	
	$errors = $totalErrors;
	$warnings = $totalWarnings;
	
	return $records;
}