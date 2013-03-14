<?php

function getCrfFormData($form) 
{
	global $table_pk, $status;
	
	$libxml = new SharedLibraryXml();
	$retVal = array();	
	$index = 0;
	
	$sql = "select " .
			"field_name, field_phi, form_name, form_menu_description, field_order, field_units, " .
			"element_preceding_header, element_type, element_label, element_enum, element_note, " .
			"element_validation_type, element_validation_min, element_validation_max, " .
			"element_validation_checktype, branching_logic, field_req, edoc_id, edoc_display_img, " .
			"custom_alignment, stop_actions, question_num, grid_name " .
			"from redcap_metadata " .
			"where " .
				"project_id = " . PROJECT_ID . " " .
				"and form_name = '$form' " .
				"and field_name != concat(form_name,'_complete') " .
				"and field_name != '$table_pk' " . 
			"order by field_order";
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q))
	{
		// For this field, add each attribute
		$retVal[$index] = array();
		foreach ($row as $key => $val) 
		{
			// Ignore certain attributes because we'll add it later
			if (in_array($key, array('edoc_display_img', 'stop_actions'))) continue;
			// Add attribute
			addFormElement($retVal[$index], getMappedElement($key), html_entity_decode($val, ENT_QUOTES));
		}
		// If has a stop action,
		if ($row['stop_actions'] != '')
		{
			foreach (explode(",", $row['stop_actions']) as $this_code)
			{
				$retVal[$index]['ActionExists'][] = array(getMappedElement('action_trigger')=>$this_code, getMappedElement('action')=>1);
			}
		}
		// If has an attachment, then add the file attachment's attributes
		if (is_numeric($row['edoc_id']) && $row['element_type'] == 'descriptive') 
		{
			$retVal[$index]['ImageAttachment'] = getAttachmentInfo($row['edoc_id'], $row['edoc_display_img']);
		}
		// If is a slider
		elseif ($row['element_type'] == 'slider') 
		{
			// Add slider labels
			$this_slider_labels = parseSliderLabels($row['element_enum']);
			addFormElement($retVal[$index], getMappedElement('slider_min'), $this_slider_labels['left']);
			addFormElement($retVal[$index], getMappedElement('slider_mid'), $this_slider_labels['middle']);
			addFormElement($retVal[$index], getMappedElement('slider_max'), $this_slider_labels['right']);
			// If its supposed to display its number value
			addFormElement($retVal[$index], getMappedElement('slider_val_disp'), ($row['element_validation_type'] == 'number' ? '1' : '0'));
		}
		
		// Do check for Standards Mapping
		error_log('starting mapping check');
		$mapCheckSql = "select " .
							"code.standard_code, code.standard_code_desc, " .
					   		"std.standard_name, std.standard_version, std.standard_desc, " .
					   		"map.data_conversion, map.data_conversion2 ".
					   "from redcap_standard_map map, redcap_standard_code code, redcap_standard std ".
					   "where " .
					   		"map.project_id = " . PROJECT_ID . " " .
					   		"and map.field_name = '" . $row['field_name'] . "' " .
					   		"and map.standard_code_id = code.standard_code_id " .
					   		"and code.standard_id = std.standard_id";
		error_log($mapCheckSql);
		$mapCheckQuery = db_query($mapCheckSql);
		$mapIndex = 0;
		if($mapCheckQuery) {
			error_log('mappings have been found');
			$retVal[$index]['StandardsMapping'] = array();
			error_log('created mapping array');
			while($mapRow = db_fetch_array($mapCheckQuery, MYSQL_ASSOC)) {
				$retVal[$index]['StandardsMapping'][$mapIndex] = array();
				error_log('adding values');
				foreach($mapRow as $mapKey => $mapVal) {
					error_log('adding ' . $mapKey . '=' .$mapVal);
					addFormElement($retVal[$index]['StandardsMapping'][$mapIndex],getMappedElement($mapKey),html_entity_decode($mapVal, ENT_QUOTES));
				}
				$mapIndex++;
			}
		}
		error_log('done mapping check');
		// Increment counter
		$index++;
	}
	
	return $retVal;
}


function addFormElement(&$arr,$key,$val){
	if($key != null && trim($val) != '') {
		$arr[$key] = $val;
	}
}

function initMapTable() {
	global $xmlMapTable;
	global $reverseXmlMapTable;
	$xmlMapTable = array(
		// CRF constants
		'field_name'					=> 'FieldName',
		'field_phi'						=> 'FieldPhi',
		'form_name'						=> 'FormName',
		'form_menu_description'			=> 'FormMenuDescription',
		'field_order'					=> 'FieldOrder',
		'field_units'					=> 'FieldUnits',
		'element_preceding_header'		=> 'ElementPreceedingHeader',
		'element_type'					=> 'ElementType',
		'element_label'					=> 'ElementLabel',
		'element_enum'					=> 'ElementEnum',
		'element_note'					=> 'ElementNote',
		'element_validation_type'		=> 'ElementValidationType',
		'element_validation_min'		=> 'ElementValidationMin',
		'element_validation_max'		=> 'ElementValidationMax',
		'element_validation_checktype'	=> 'ElementValidationChecktype',
		'branching_logic'				=> 'BranchingLogic',
		'field_req'						=> 'FieldReq',
		'mapped_codes'					=> 'MappedCodes',
		'standard_code'					=> 'Code',
		'standard_code_desc'			=> 'CodeDescription',
		'standard_name'					=> 'StandardName',
		'standard_version'				=> 'StandardVersion',
		'standard_desc'					=> 'StandardDescription',
		'data_conversion'				=> 'DataConversion',
		'data_conversion2'				=> 'DataConversion2',
		// New stuff in 4.X
		'custom_alignment'				=> 'QuestionLayout',
		'stop_actions'					=> 'Action',
		'question_num'					=> 'QuestionNum',
		// 
		'slider_min'					=> 'ElementSliderMin',
		'slider_mid'					=> 'ElementSliderMid',
		'slider_max'					=> 'ElementSliderMax',
		'slider_val_disp'				=> 'ElementSliderValDisp',
		'action'						=> 'Action',
		'action_exists'					=> 'ActionExists',
		'action_trigger'				=> 'Trigger',
		// Added matrix group names in 4.13
		'grid_name'						=> 'MatrixGroupName',
		// File-related attributes
		'image_attachment'				=> 'ImageAttachment',
		'stored_name'					=> 'StoredName',
		'mime_type'						=> 'MimeType',
		'doc_name'						=> 'DocName',
		'doc_size'						=> 'DocSize',
		'file_extension'				=> 'FileExtension',
		'edoc_display_img'				=> 'FileDownloadImgDisplay'
	);
	$reverseXmlMapTable = array();
	foreach($xmlMapTable as $key=>$val) {
	    $reverseXmlMapTable[$val] = $key;
	}
}

function getAttachmentInfo($doc_id, $edoc_display_img)
{
	$sql = "select stored_name, mime_type, doc_name, doc_size, file_extension from redcap_edocs_metadata 
			where doc_id = $doc_id limit 1";
	$q = db_query($sql);
	$attachmentArray = null;
	if ($row = db_fetch_assoc($q)) 
	{
		$attachmentArray = array();
		$attachmentArray[getMappedElement('stored_name')] = $row['stored_name'];
		$attachmentArray[getMappedElement('mime_type')] = $row['mime_type'];
		$attachmentArray[getMappedElement('doc_name')] = $row['doc_name'];
		$attachmentArray[getMappedElement('doc_size')] = $row['doc_size'];
		$attachmentArray[getMappedElement('file_extension')] = $row['file_extension'];
		$attachmentArray[getMappedElement('edoc_display_img')] = $edoc_display_img;
	}
	//print_r($attachmentArray);
	//print "<br><br>";
	return $attachmentArray;
}

function getMappedElement($str) {
	global $xmlMapTable;
	global $reverseXmlMapTable;
	if(!isset($xmlMapTable)) {
	    initMapTable();
	}
    if(isset($xmlMapTable[$str])) {
       	return $xmlMapTable[$str];
   	}else if(isset($reverseXmlMapTable[$str])) {
   	    return $reverseXmlMapTable[$str];
   	}
   	return null;
}

function getMaxFieldOrderValue($project_id) {
	global $status;
	$metadata_table = ($status > 0) ? "redcap_metadata_temp" : "redcap_metadata";
	$max = -1;
	$sql = "select max(field_order) as field_max from $metadata_table where project_id=$project_id";
	$q = db_query($sql);
	if($row = db_fetch_array($q,MYSQL_ASSOC)) {
		$max = $row['field_max'];
	}
	return $max;
}

function getUniqueFormName($formName) {

	global $status, $Proj;
	// Clean it
	$new_form_name = preg_replace("/[^a-z0-9_]/", "", str_replace(" ", "_", strtolower(html_entity_decode($formName, ENT_QUOTES))));
	// Remove any double underscores, beginning numerals, and beginning/ending underscores
	while (strpos($new_form_name, "__") !== false) 		$new_form_name = str_replace("__", "_", $new_form_name);
	while (substr($new_form_name, 0, 1) == "_") 		$new_form_name = substr($new_form_name, 1);
	while (substr($new_form_name, -1) == "_") 			$new_form_name = substr($new_form_name, 0, -1);
	while (is_numeric(substr($new_form_name, 0, 1))) 	$new_form_name = substr($new_form_name, 1);
	while (substr($new_form_name, 0, 1) == "_") 		$new_form_name = substr($new_form_name, 1);
	// Cannot begin with numeral and cannot be blank
	if (is_numeric(substr($new_form_name, 0, 1)) || $new_form_name == "") {
		$new_form_name = substr(preg_replace("/[0-9]/", "", md5($new_form_name)), 0, 4) . $new_form_name;
	}
	// Make sure it's less than 50 characters long
	$new_form_name = substr($new_form_name, 0, 50);
	while (substr($new_form_name, -1) == "_") $new_form_name = substr($new_form_name, 0, -1);
	// Make sure this form value doesn't already exist
	$formExists = ($status > 0) ? isset($Proj->forms_temp[$new_form_name]) : isset($Proj->forms[$new_form_name]);
	while ($formExists) {
		// Append random value to form_name to prevent duplication
		$new_form_name .= "_" . substr(md5(rand()), 0, 4);
		// Make sure it's less than 64 characters long
		$new_form_name = substr($new_form_name, 0, 50);
		// Try again
		$formExists = ($status > 0) ? isset($Proj->forms_temp[$new_form_name]) : isset($Proj->forms[$new_form_name]);
	}
	// Return form name
	return $new_form_name;
}

function isInFieldArray($fieldArray, $nameIndex, $fieldName) {
    foreach($fieldArray as $field) {
        if($field[$nameIndex] == $fieldName) {
            return true;
        }
    }
    return false;
}

function getUniqueFieldName($fieldArray, $nameIndex, $project_id, $fieldName, $count=1) {
	global $status;
	$metadata_table = ($status > 0) ? "redcap_metadata_temp" : "redcap_metadata";
	$fieldName = html_entity_decode($fieldName, ENT_QUOTES);
	$fieldName = preg_replace("/[^a-z0-9_]/", "", str_replace(" ", "_", strtolower($fieldName)));
    $sql = "select field_name as fn from $metadata_table where project_id = $project_id and field_name = '$fieldName' limit 1";
    $q = db_query($sql);
    if ($row = db_fetch_array($q) || isInFieldArray($fieldArray, $nameIndex, $fieldName)) {
		return str_replace("__", "_", $fieldName . "_" . substr(md5(rand()), 0, 6));
		// $fieldNameNormalized = preg_replace("/(_\d+)$/","", $fieldName);
        // return getUniqueFieldName($fieldArray, $nameIndex, $project_id, $fieldNameNormalized.'_'.$count, $count+1);
    } else {
        return $fieldName;
    }
}
