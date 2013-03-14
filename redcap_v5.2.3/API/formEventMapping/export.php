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
log_event("", "redcap_events_forms", "MANAGE", PROJECT_ID, "project_id = " . PROJECT_ID, "Export form-event mapping (API)");

# Send the response to the requestor
RestUtility::sendResponse(200, $content, $format);

function json($dataset)
{
	$output = "";

	foreach ($dataset as $arm_num => $event)
	{
		$data = "";
		foreach ($event as $name => $forms) {
			$data .= '{"unique_event_name": "'.$name.'", "form": [';
			$items = "";
			foreach ($forms as $form) {
				$items .= '"'.$form.'",';
			}
			if ($items != "") $items = substr($items, 0, -1);
			$data .= "$items]},";
		}

		if ($data != "") $data = substr($data, 0, -1);
		$output .= '{"arm": {"number": "'.$arm_num.'", "event": ['.$data.']}},';
	}

	if ($output != "") $output = '['.substr($output, 0, -1).']';

	return $output;
}

function xml($dataset)
{
	$output = '<?xml version="1.0" encoding="UTF-8" ?>';
	$output .= "\n<items>\n";

	foreach ($dataset as $arm_num => $event)
	{
		$data = "";
		foreach ($event as $name => $forms) {
			$data .= "<event><unique_event_name>$name</unique_event_name>";
			foreach ($forms as $form) {
				$data .= "<form>$form</form>";
			}
			$data .= "</event>";
		}

		$output .= "<arm><number>$arm_num</number>$data</arm>\n";
	}

	$output .= "</items>\n";

	return $output;
}

function csv($dataset)
{
	$output = "";

	foreach ($dataset as $arm_num => $event) {
		foreach ($event as $name => $forms) {
			foreach ($forms as $form) {
				$output .= $arm_num.',"'.$name.'","'.$form.'"'."\n";
			}
		}
	}

	$fieldList = "arm_num,unique_event_name,form_name";
	$output = $fieldList . "\n" . $output;

	return $output;
}

function getRecords()
{
	global $post;

	$arms = array();

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
	$events = $Proj->events;
	$uniqueNames = $Proj->getUniqueEventNames();
	$eventForms = $Proj->eventsForms;

	//This function only works for longitudinal projects
	if (!$Proj->project['repeatforms']) die(RestUtility::sendResponse(400, 'You cannot export form/event mappings for classic projects'));

	$results = array();
	$addEvents = true;
	$armsEmpty = empty($arms);

	foreach($events as $num => $data)
	{
		# if filtering by ARMs, determine if current event is in a desired ARM
		$addEvents = ( $armsEmpty || (!$armsEmpty && in_array($num, $arms)) );

		if ($addEvents)
		{
			foreach($data['events'] as $id => $events) {
				$results[$num][$uniqueNames[$id]] = $eventForms[$id];
			}
		}
	}

	return $results;
}