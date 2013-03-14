<?php
global $format, $returnFormat, $post;

defined("PROJECT_ID") or define("PROJECT_ID", $post['projectid']);

# get all the records to be exported
$result = getItems();

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
log_event("", "redcap_user_rights", "MANAGE", PROJECT_ID, "project_id = " . PROJECT_ID, "Export users (API)");

# Send the response to the requestor
RestUtility::sendResponse(200, $content, $format);

function json($dataset)
{
	$output = "";

	foreach ($dataset as $row)
	{
		$data = '';
		foreach ($row as $field => $value)
		{
			if ($field != "forms")
			{
				$data .= '"'.$field.'":"'.str_replace('"', '\"', html_entity_decode($value, ENT_QUOTES)).'",';
			}
			else
			{
				$data .= '"forms": [';
				$items = "";
				foreach ($value as $form => $right) {
					$items .= '{"'.$form.'":'.$right.'},';
				}
				if ($items != "") $items = substr($items, 0, -1);
				$data .= "$items],";
			}
		}

		$output .= '{'.substr($data, 0, -1).'},';
	}
	if ($output != "") $output = '['.substr($output, 0, -1).']';

	return $output;
}

function xml($dataset)
{
	$output = '<?xml version="1.0" encoding="UTF-8" ?>';
	$output .= "\n<records>\n";

	foreach ($dataset as $row)
	{
		$data  = ($row['username'] != "") ? "<username>" . html_entity_decode($row['username'], ENT_QUOTES) . "</username>" : "<username/>";
		$data .= ($row['email'] != "") ? "<email>" . html_entity_decode($row['email'], ENT_QUOTES) . "</email>" : "<email/>";
		$data .= ($row['firstname'] != "") ? "<firstname><![CDATA[" . html_entity_decode($row['firstname'], ENT_QUOTES) . "]]></firstname>" : "<firstname/>";
		$data .= ($row['lastname'] != "") ? "<lastname><![CDATA[" . html_entity_decode($row['lastname'], ENT_QUOTES) . "]]></lastname>" : "<lastname/>";
		$data .= ($row['expiration'] != "") ? "<expiration><![CDATA[" . html_entity_decode($row['expiration'], ENT_QUOTES) . "]]></expiration>" : "<expiration/>";
		$data .= ($row['data_access_group'] != "") ? "<data_access_group>" . html_entity_decode($row['data_access_group'], ENT_QUOTES) . "</data_access_group>" : "<data_access_group/>";
		$data .= ($row['data_export'] != "") ? "<data_export>" . html_entity_decode($row['data_export'], ENT_QUOTES) . "</data_export>" : "<data_export/>";

		$data .= "<forms>";
		foreach ($row["forms"] as $form => $right) {
			$data .= "<$form>$right</$form>";
		}
		$data .= "</forms>";

		$output .= "<item>$data</item>\n";
	}
	$output .= "</records>\n";

	return $output;
}

function csv($dataset)
{
	$output = "";
	$firstRun = true;

	foreach ($dataset as $index => $user) {
		$output .= '"'.$user['username'].'","'.$user['email'].'","'.
				   str_replace('"', '""', html_entity_decode($user['firstname'], ENT_QUOTES)).'","'.
				   str_replace('"', '""', html_entity_decode($user['lastname'], ENT_QUOTES)).'","'.
				   $user['expiration'].'",'.$user['data_access_group'].','.$user['data_export'];

		foreach($user["forms"] as $form => $right) {
			$output .= ",$right";
		}
		$output .= "\n";

		if ($firstRun) {
			$fieldList = implode(",", array_keys($user["forms"]));
			$firstRun = false;
		}
	}

	$fieldList = "username,email,firstname,lastname,expiration,data_access_group,data_export,".$fieldList;
	$output = $fieldList . "\n" . $output;

	return $output;
}

function getItems()
{
	global $post;

	# get user information
	$sql = "SELECT ur.*, ui.user_email, ui.user_firstname, ui.user_lastname
			FROM redcap_user_rights ur
			LEFT JOIN redcap_user_information ui ON ur.username = ui.username
			WHERE ur.project_id = ".PROJECT_ID;
	$users = db_query($sql);

	$result = array();
	
	while ($row = db_fetch_assoc($users))
	{
		$dataEntryArr = explode("][", substr(trim($row['data_entry']), 1, -1));
		foreach ($dataEntryArr as $keyval)
		{
			list($key, $value) = explode(",", $keyval, 2);
			$forms[$key] = $value;
		}

		$result[] = array("username" 			=> $row["username"],
						  "email" 				=> $row["user_email"],
						  "firstname" 			=> $row["user_firstname"],
						  "lastname"	 		=> $row["user_lastname"],
						  "expiration" 			=> $row["expiration"],
						  "data_access_group" 	=> $row["group_id"],
						  "data_export" 		=> $row["data_export_tool"],
						  "forms"				=> $forms);
	}

	return $result;
}