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
log_event("", "redcap_metadata", "MANAGE", PROJECT_ID, "project_id = " . PROJECT_ID, "Download data dictionary (API)");

# Send the response to the requestor
RestUtility::sendResponse(200, $content, $format);

function json($dataset)
{
	$output = "";
	
	foreach ($dataset as $row)
	{
		$line = '';
		foreach ($row as $item => $value)
		{
			//decode any HTML characters and then escape any quotes
			$value = str_replace('"', '\"', html_entity_decode($value, ENT_QUOTES));
			$value = str_replace("\r\n", "\\r\\n", $value);
			$line .= '"'.$item.'":"'.html_entity_decode($value, ENT_QUOTES).'",';
		}
		
		$output .= '{'.substr($line, 0, -1).'},';
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
	$output .= "</records>\n";
	
	return $output;
}

function csv($dataset)
{
	$output = "";
	
	$fieldArray = array();
	foreach ($dataset as $index => $row)
	{
		$line = '';
		
		foreach ($row as $item => $value)
		{
			//Remove "\n" in Select Choices and replace with "|"
			if ($item == "select_choices_or_calculations") {
				$value = str_replace("\\n", "|", trim($value));
			//Change to user-friendly values for Validation
			} elseif ($item == "text_validation_type_or_show_slider_number") {
				//$value = str_replace(array("int","float"), array("integer","number"), $value);
				if ($value == "date" || $value == "datetime" || $value == "datetime_seconds") {
					$value .= "_ymd";
				}
			} elseif ($item == "section_header") {
				$value = str_replace("\n", " ", trim($value));
				$value = str_replace("\r", " ", trim($value));
			}
			
			if ($value != "") {
				// Fix any formatting
				$value = label_decode($value);
				$value = str_replace(array("&#39;","&#039;"), array("'","'"), $value);
			}
			
			if ($index == 0) $fieldArray[] = $item;
			
			$line .= $line == '' ? '' : ',';
			
			if (is_numeric($value)) {
				$line .= $value;
			}
			else {
				$line .= '"'. str_replace('"', '""', $value) . '"';
			}
		}

		$output .= $line. "\n";
	}
	
	$output = implode(",", $fieldArray) . "\n" . $output; 
	
	return $output;
}

function getRecords()
{
	global $post;
	
	# get project information
	$Proj = new ProjectAttributes();
		
	# get all fields for a set of forms, if provided
	$formList = prep_implode($post['forms']);
	$query = "SELECT field_name FROM redcap_metadata 
			WHERE project_id = ".$post['projectid']." AND form_name IN ($formList)
			ORDER BY field_order";
	$fieldResults = db_query($query);
	
	$fields = $post['fields'];
	$fieldArray = array();
	while ($row = db_fetch_assoc($fieldResults))
	{
		$key = array_search($row['field_name'], $fields);
		
		if ($key != NULL && $key !== false)
			unset($fields[$key]);
		
		$fieldArray[] = $row['field_name'];
	}
	
	$fieldArray = array_merge($fields, $fieldArray);
	
	$fieldList = prep_implode($fieldArray);
	$fieldSql = (count($fieldArray) > 0) ? "AND field_name IN ($fieldList)" : "";
	
	$query = "SELECT field_name, form_name, element_preceding_header as section_header, 
					if(element_type='textarea','notes',if(element_type='select','dropdown',element_type)) as field_type, 
					element_label as field_label, element_enum as select_choices_or_calculations, element_note as field_note,
					if(element_validation_type='int','integer',if(element_validation_type='float','number',element_validation_type)) as text_validation_type_or_show_slider_number, 
					element_validation_min as text_validation_min, 
					element_validation_max as text_validation_max, if(field_phi='1','Y','') as identifier, branching_logic, 
					if(field_req='0','','Y') as required_field, custom_alignment, question_num as question_number, grid_name as matrix_group_name
			  FROM redcap_metadata 
			  WHERE project_id = ".$post['projectid']." AND field_name != concat(form_name,'_complete') $fieldSql
			  ORDER BY field_order";
	$result = db_query($query);
	$records = array();
	
	while ($row = db_fetch_assoc($result)) {
		$records[] = $row;
	}
	
	return $records;
}
