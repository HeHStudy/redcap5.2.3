<?php
global $format, $returnFormat, $post;

defined("PROJECT_ID") or define("PROJECT_ID", $post['projectid']);

# get all the records to be exported
$result = getRecords();

# structure the output data accordingly
switch($format)
{
	case 'json':
		$content = json($result);
		break;
	case 'xml':
		$content = xml($result);
		break;
	case 'csv':
		$content = csv($result);
		break;
}

/************************** log the event **************************/
$query = "SELECT username FROM redcap_user_rights WHERE api_token = '" . prep($post['token']) . "'";
defined("USERID") or define("USERID", db_result(db_query($query), 0));

# Logging
log_event("", "redcap_events_metadata", "MANAGE", PROJECT_ID, "project_id = " . PROJECT_ID, "Export events (API)");

# Send the response to the requestor
RestUtility::sendResponse(200, $content, $format);

function json($dataset)
{
	$output = "";

	foreach ($dataset as $row)
	{
		$line = '';
		foreach ($row as $item => $value) {
			$line .= '"'.$item.'":"'.str_replace('"', '\"', html_entity_decode($value, ENT_QUOTES)).'",';
		}

		$output .= '{'.substr($line, 0, -1).'},';
	}
	if ($output != "") $output = '['.substr($output, 0, -1).']';

	return $output;
}

function xml($dataset)
{
	$output = '<?xml version="1.0" encoding="UTF-8" ?>';
	$output .= "\n<events>\n";

	foreach ($dataset as $row)
	{
		$line = '';
		foreach ($row as $item => $value)
		{
			if ($value != "")
				$line .= "<$item><![CDATA[" . html_entity_decode($value, ENT_QUOTES) . "]]></$item>";
			else
				$line .= "<$item></$item>";
		}

		$output .= "<item>$line</item>\n";
	}
	$output .= "</events>\n";

	return $output;
}

function csv($dataset)
{
	$output = "";

	foreach ($dataset as $index => $row) {
		$output .= '"'.str_replace('"', '""', html_entity_decode($row['event_name'], ENT_QUOTES)).'",'.$row['arm_num'].','.$row['day_offset'].','.
				   $row['offset_min'].','.$row['offset_max'].',"'.$row['unique_event_name'].'"'."\n";
	}

	$fieldList = "event_name,arm_num,day_offset,offset_min,offset_max,unique_event_name";
	$output = $fieldList . "\n" . $output;

	return $output;
}

function getRecords()
{
	global $post;

	$arms = array();
	$tempArms = array();

	// Determine if these params are arrays.  If not, make them into arrays
	$tempArms = is_array($post['arms']) ? $post['arms'] : explode(",", $post['arms']);

	// Loop through all elements and remove any spaces
	foreach($tempArms as $id => $value) {
		if (trim($value) != "") {
			$arms[] = trim($value);
		}
	}

	# get project information
	$Proj = new ProjectAttributes();
	$eventInfo = $Proj->eventInfo;
	$uniqueNames = $Proj->getUniqueEventNames();

	//This function only works for longitudinal projects
	if (!$Proj->project['repeatforms']) die(RestUtility::sendResponse(400, 'You cannot export events for classic projects'));

	$events = array();
	$addEvents = true;
	$armsEmpty = empty($arms);

	foreach($eventInfo as $id => $data)
	{
		# if filtering by ARMs, determine if current event is in a desired ARM
		$addEvents = ( $armsEmpty || (!$armsEmpty && in_array($data["arm_num"], $arms)) );

		if ($addEvents)
		{
			$events[] = array(	"event_name"		=> $data["name"],
								"arm_num"			=> $data["arm_num"],
								"day_offset" 		=> $data["day_offset"],
								"offset_min" 		=> $data['offset_min'],
								"offset_max" 		=> $data['offset_max'],
								"unique_event_name"	=> $uniqueNames[$id]);
		}
	}

	return $events;
}