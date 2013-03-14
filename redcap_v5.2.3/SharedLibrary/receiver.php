<?php
/*****************************************************************************************
**  REDCap is only available through ACADMEMIC USER LICENSE with Vanderbilt University
******************************************************************************************/

 
require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
require_once(APP_PATH_CLASSES  . 'SharedLibraryXml.php');
require_once(APP_PATH_DOCROOT . 'SharedLibrary/functions.php');
require_once APP_PATH_DOCROOT . 'Design/functions.php';

function fieldHandler($arr) {
	global $mainArray;
	$mainArray[] = $arr;
}
function fieldCompare($a, $b) {
  return $a['FieldOrder'] > $b['FieldOrder'];
}
function getFieldStrValue($arr, $field) {
    if(isset($arr[$field])) {
    	$temp = str_replace("\\","\\\\",$arr[$field]);
    	$temp = str_replace("'","''",$temp);
        return "'$temp'";
    }
    return 'null';
}
function addStandard($standard, $version, $description) {
	//add standard if it doesn't exist and return the generated id, or just return the id if it already exists
	$id = -1;
	$sqlCheck = "select standard_id from redcap_standard where standard_name = '$standard' and standard_version = '$version'";
	$check = db_query($sqlCheck);
	if($checkRow = db_fetch_array($check)) {
		$id = $checkRow['standard_id'];//standard already exists
	}else {
		$sqlInsert = "insert into redcap_standard(standard_name, standard_version, standard_desc) values('$standard','$version','$description')";
		$result = db_query($sqlInsert);
		if($result) {
			$id = db_insert_id();
		}
	}
	return $id;
}
function addStandardCode($code, $description, $standard_id) {
	//add code if it doesn't exist and return the generated id, or just return the id if it already exists
	$id = -1;
	$sqlCheck = "select standard_code_id from redcap_standard_code where standard_code = '$code' and standard_id = $standard_id";
	$check = db_query($sqlCheck);
	if($checkRow = db_fetch_array($check)) {
		$id = $checkRow['standard_code_id'];//code already exists
	}else {
		$sqlInsert = "insert into redcap_standard_code(standard_code, standard_code_desc, standard_id) values('$code','$description',$standard_id)";
		$result = db_query($sqlInsert);
		if($result) {
			$id = db_insert_id();
		}
	}
	return $id;
}
function addMappedStandard($project, $field, $standard_code_id, $dataConversion, $dataConversion2) {
	$success = false;
	$sqlInsert = "insert into redcap_standard_map(project_id, field_name, standard_code_id, data_conversion, data_conversion2) values($project, '$field', $standard_code_id, '$dataConversion', '$dataConversion2')";
	if($query = db_query($sqlInsert)) {
		$success = true;
	}
	return $success;
}


// Redirect back to Design page if cancelled action
if (isset($_GET['cancel'])) {
	redirect(APP_PATH_WEBROOT.'SharedLibrary/index.php?pid='.$project_id);
}



//If project is in production, do not allow instant editing (draft the changes using metadata_temp table instead)
$metadata_table = ($status > 0) ? "redcap_metadata_temp" : "redcap_metadata";


// User defined new form name, so pull form from Library and load into REDCap
if (isset($_POST['import_id']) && isset($_POST['new_form']) && $_SESSION['import_id'] != $_POST['import_id']) 
{

	$_SESSION['import_id'] = $_POST['import_id'];
	$error = false;
	$error_str = '';
	$curlXml = curl_init();
	curl_setopt($curlXml, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($curlXml, CURLOPT_VERBOSE, 1);
	curl_setopt($curlXml, CURLOPT_URL, SHARED_LIB_DOWNLOAD_URL.'?attr=xml&id='.$_POST['import_id']);
	curl_setopt($curlXml, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curlXml, CURLOPT_POST, false);
	curl_setopt($curlXml, CURLOPT_PROXY, PROXY_HOSTNAME); // If using a proxy
	$xml = curl_exec($curlXml);
	
	// Make sure the XML is truly UTF-8 encoded
	if (function_exists('mb_detect_encoding') && function_exists('mb_convert_encoding') && mb_detect_encoding($xml) != "UTF-8") {
		$xml = mb_convert_encoding($xml, "UTF-8");	
	}
	 // Replace some characters that cause the XML to be invalid
	$xml = str_replace("&#13;\n", "&lt;br&gt;", $xml);
	
	
	
	$before_after_text = 'before';
	if($_POST['add_before_after'] == 1) {
	    $before_after_text = 'after';
	}

	$field_order_max = getMaxFieldOrderValue($project_id);

	$newFormDescription = html_entity_decode($_POST['new_form'], ENT_QUOTES);
	$newFormName = getUniqueFormName($newFormDescription);
	
	try {
		$mainArray = array();
		$parser = new SharedLibraryXml();
		$parser->setXml($xml);
		$parser->setFieldHandler('fieldHandler');
		$successFlag = $parser->parse();
		if($successFlag == 0) {
			$error_code = xml_get_error_code($parser);
			throw new Exception("error parsing xml document please contact server administrator (ERROR CODE: " . $error_code  . ")");
		}
	
		//get mapped names for database columns:
		$map_field_name = getMappedElement('field_name');
		$map_field_phi = getMappedElement('field_phi');
		$map_form_name = getMappedElement('form_name');
		$map_form_menu_description = getMappedElement('form_menu_description');
		$map_field_order = getMappedElement('field_order');
		$map_field_units = getMappedElement('field_units');
		$map_element_preceding_header = getMappedElement('element_preceding_header');
		$map_element_type = getMappedElement('element_type');
		$map_element_label = getMappedElement('element_label');
		$map_element_enum = getMappedElement('element_enum');
		$map_element_note = getMappedElement('element_note');
		$map_element_validation_type = getMappedElement('element_validation_type');
		$map_element_validation_min = getMappedElement('element_validation_min');
		$map_element_validation_max = getMappedElement('element_validation_max');
		$map_element_validation_checktype = getMappedElement('element_validation_checktype');
		$map_branching_logic = getMappedElement('branching_logic');
		$map_field_req = getMappedElement('field_req');		
		
		$map_image_attachment = getMappedElement('image_attachment');
		$map_stored_name = getMappedElement('stored_name');
		$map_mime_type = getMappedElement('mime_type');
		$map_doc_name = getMappedElement('doc_name');
		$map_doc_size = getMappedElement('doc_size');
		$map_file_extension = getMappedElement('file_extension');
		$map_edoc_display_img = getMappedElement('edoc_display_img');
		
		$map_custom_alignment = getMappedElement('custom_alignment');
		$map_stop_actions = getMappedElement('stop_actions');
		$map_question_num = getMappedElement('mime_type');
		$map_matrix_group_name = getMappedElement('grid_name');
		
		
		


		//sort the array by the field_order element
		usort($mainArray, 'fieldCompare');
		$adjustedFieldOrderValue = $field_order_max + 1;
		
		// Adjust field names to be unique ONLY IF they duplicate an existing field (ignore if this is a Single Survey project)
		$modifiedFieldNames = array();
		$existingFieldNames = ($status > 0) ? $Proj->metadata_temp : $Proj->metadata;
		foreach ($mainArray as $key=>$field) 
		{
			// First, check if exists
			if (isset($existingFieldNames[$field[$map_field_name]]))
			{
				// Field already exists, so create new unique name
				$uniqueFieldName = getUniqueFieldName($mainArray, $map_field_name, $project_id, $field[$map_field_name]);
				if ($uniqueFieldName != $field[$map_field_name]) 
				{
					$modifiedFieldNames[$field[$map_field_name]] = $uniqueFieldName;
					$mainArray[$key][$map_field_name] = $uniqueFieldName;
				}
			}
		}

		//modify branching logic and calculated field references for adjusted field names
		//need to do this in a separate loop, because we need to wait for all of the field
		//names to be converted to unique field names first.
		foreach($modifiedFieldNames as $oldName=>$newName) 
		{
			foreach($mainArray as $key=>$field) 
			{
			    if(isset($field[$map_element_enum])) {
			        $mainArray[$key][$map_element_enum] = str_replace('['.$oldName.']','['.$newName.']',$field[$map_element_enum]);
			    }
			    if(isset($field[$map_branching_logic])) {
			        $mainArray[$key][$map_branching_logic] = str_replace('['.$oldName.']','['.$newName.']',$field[$map_branching_logic]);
			    }
			    //data conversion formulas can only reference the current field, but it is convenient to make the check here, and allows
			    //the rule to be modified in the future allowing conversion formulas to act like calculated fields (referencing other fields)
			    if(isset($field['MappedCodes'])) {
			    	foreach($field['MappedCodes'] as $mappedKey => $mappedValue) {
			    		if(isset($mappedValue['DataConversion'])) {
			    			$mainArray[$key]['MappedCodes'][$mappedKey]['DataConversion'] = str_replace('['.$oldName.']','['.$newName.']',$mappedValue['DataConversion']);
			    		}
			    	}
			    }
			}
		}
	
		// Set form_menu_description for first field only
		$formMenuDescription = "'".addslashes($newFormDescription)."'";
		
		// Default value for if form has any attachments
		$has_images = false;
		
		// Loop through the incoming form fields and add as new form
		foreach ($mainArray as $field) 
		{
			// Set "required field" flag, if applicable
			$fieldReqValue = 0;
			if (isset($field[$map_field_req])) {
				$fieldReqValue = $field[$map_field_req];
			}
			
			// Format the stop actions to be comma delimited
			$this_stop_actions = array();
			foreach($field['ActionExists'] as $action) {
				$this_stop_actions[] = $action['Trigger'];
			}
			
			// Build query to insert this field
			$sqlInsert = 'insert into ' . $metadata_table . 
				' (project_id, field_name, field_phi, form_name, form_menu_description, field_order, field_units, ' .
				'element_preceding_header, element_type, element_label, element_enum, element_note, ' .
				'element_validation_type, element_validation_min, element_validation_max, element_validation_checktype, ' .
				'branching_logic, field_req, ' . 
				'custom_alignment, stop_actions, question_num, grid_name ' .
				') values (' .
				$project_id.','.
				'\''.$field[$map_field_name].'\','.								getFieldStrValue($field, $map_field_phi).','.
				'\''.$newFormName.'\','.										$formMenuDescription.','.
				$adjustedFieldOrderValue.','.									getFieldStrValue($field,$map_field_units).','.
				getFieldStrValue($field,$map_element_preceding_header).','.		getFieldStrValue($field,$map_element_type).','.
				getFieldStrValue($field,$map_element_label).','.				getFieldStrValue($field,$map_element_enum).','.
				getFieldStrValue($field,$map_element_note).','.					getFieldStrValue($field,$map_element_validation_type).','.
				getFieldStrValue($field,$map_element_validation_min).','.		getFieldStrValue($field,$map_element_validation_max).','.
				getFieldStrValue($field,$map_element_validation_checktype).','.	getFieldStrValue($field,$map_branching_logic).','.
				$fieldReqValue.','.				
				getFieldStrValue($field,$map_custom_alignment).','.	
				checkNull(implode(",", $this_stop_actions)).','.				
				getFieldStrValue($field,$map_question_num).','.				
				getFieldStrValue($field,$map_matrix_group_name).
				')';
			
			// Increment field order
			$adjustedFieldOrderValue += 1;
			// Set form menu description to null since only used for first field entry
			$formMenuDescription = 'null';
			
			// Insert this field
			if (!db_query($sqlInsert)) {
				$error = true;
				$error_str .= db_error();
			} else {
				if(isset($field['MappedCodes'])) {
					foreach($field['MappedCodes'] as $mappedCode) {
						$standard_id = addStandard($mappedCode['StandardName'],$mappedCode['StandardVersion'],$mappedCode['StandardDescription']);
						if($standard_id >= 0) {
							$standard_code_id = addStandardCode($mappedCode['Code'],$mappedCode['CodeDescription'],$standard_id);
							if($standard_code_id >= 0) {
								$dataConversion = "";
								if(isset($mappedCode['DataConversion']) && trim($mappedCode['DataConversion']) != "") {
									$dataConversion = str_replace("\\","\\\\",$mappedCode['DataConversion']);
								}
								$dataConversion2 = "";
								if(isset($mappedCode['DataConversion2']) && trim($mappedCode['DataConversion2']) != "") {
									$dataConversion2 = str_replace("\\","\\\\",$mappedCode['DataConversion2']);
								}
								$success = addMappedStandard($project_id, $field[$map_field_name], $standard_code_id, $dataConversion, $dataConversion2);
								if(!$success) {
									$error = true;
									$error_str .= "error mapping field to code {$mappedCode['Code']}<br>"; 
									$error_str .= db_error();
								}
							}else {
								$error = true;
								$error_str .= "error adding standard code {$mappedCode['Code']}<br>"; 
								$error_str .= db_error();
							}
						}else {
							$error = true;
							$error_str .= "error adding standard {$mappedCode['StandardName']} {$mappedCode['StandardVersion']}<br>";
							$error_str .= db_error();
						}
					}
				}
			}		
		
			// ATTACHMENT: If field has an attachments, add it to edocs table and update metadata table with doc_id
			if (is_array($field[$map_image_attachment])) 
			{
				$has_images = true;
				$image = $field[$map_image_attachment];
				// Add the attachment to the edocs_metadata table
				$insert_edocs = "insert into redcap_edocs_metadata (stored_name, mime_type, doc_name, doc_size, file_extension, 
								 project_id, stored_date) values ('" . prep($image[$map_stored_name]) . "', " .
								"'" . prep($image[$map_mime_type]) . "', '" . prep($image[$map_doc_name]) . "', " .
								"'" . prep($image[$map_doc_size]) . "', '" . prep($image[$map_file_extension]) . "', " .
								"$project_id, '" . NOW . "')";
				$query_edocs = db_query($insert_edocs);
				$doc_id = db_insert_id();
				// Update the field with edoc_id
				$update_question = "update $metadata_table set edoc_id = $doc_id, edoc_display_img = {$image[$map_edoc_display_img]} 
									where field_name = '{$field[$map_field_name]}' and project_id = $project_id";
				$query_question = db_query($update_question);
			}
		}
	
		// Add the Form Complete field at the end of the form
		$sqlInsert = 'insert into ' . $metadata_table . 
			' (project_id, field_name, form_name, field_order, element_preceding_header, ' .
			'element_type, element_label, element_enum, field_req) ' .
			' values (' .
			$project_id.','.
			'\''.$newFormName.'_complete\',\''.$newFormName.'\','.$adjustedFieldOrderValue.','.
			'\'Form Status\',\'select\',\'Complete?\',\'0, Incomplete \\\\n 1, Unverified \\\\n 2, Complete\',0)';
		if (!db_query($sqlInsert)) 
		{
			$error = true;
			$error_str .= db_error();
		}
		
		// If form has any attachments, then start downloading them now since we've already added them to the edocs_metadata table
		if ($has_images) 
		{
			$params = array('library_id'=>$_POST['import_id'], 'newFormName'=>$newFormName);
			$imgCurl = curl_init();
			curl_setopt($imgCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($imgCurl, CURLOPT_VERBOSE, 1);
			curl_setopt($imgCurl, CURLOPT_URL, APP_PATH_WEBROOT_FULL . "redcap_v{$redcap_version}/SharedLibrary/image_downloader.php?pid=$project_id");
			curl_setopt($imgCurl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($imgCurl, CURLOPT_POST, true);
			curl_setopt($imgCurl, CURLOPT_TIMEOUT, 1000);
			curl_setopt($imgCurl, CURLOPT_POSTFIELDS, $params);
			curl_setopt($imgCurl, CURLOPT_PROXY, PROXY_HOSTNAME); // If using a proxy
			$response = curl_exec($imgCurl);
			curl_close($imgCurl);
		}
		
		// Add new form to rights table for ALL users (development only)
		if ($status < 1) {
			$sql = "update redcap_user_rights set data_entry = concat(data_entry,'[$newFormName,1]') where project_id = " . PROJECT_ID;
			db_query($sql);
		}
				
		
		if (!$error)
		{
			// Log the event
			log_event("", $metadata_table, "MANAGE", PROJECT_ID, "project_id = ".PROJECT_ID, "Download instrument from Shared Library");
		}
	
		if (!$longitudinal && $status < 1) {
		    //enter event info
		    $eventSql = "select e.event_id " .
		    		"from redcap_events_metadata e, redcap_events_arms a " .
		    		"where a.arm_id = e.arm_id and a.project_id = $project_id limit 1";
		    $eventQuery = db_query($eventSql);
		    $eventId = -1;
		    if($eventRow = db_fetch_array($eventQuery)) {
		        $eventId = $eventRow['event_id'];
		        $eventSql = "insert into redcap_events_forms (event_id, form_name) values($eventId,'$newFormName')";
		        db_query($eventSql);
		    }
		}
		
		//mark the form as downloaded from library
		$shareSql = "insert into redcap_library_map (project_id, form_name, type, library_id) " .
					"values($project_id,'$newFormName',1,".$_POST['import_id'].")";
		db_query($shareSql);
		//put a cached copy of the acknowledgement into the database
		$ack = getAcknowledgement($project_id,$newFormName);
	}catch(Exception $e) {
		$error = true;
		$error_str = $e->getMessage();	
	}
	
	include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

    print "<div class='round' style='border:1px solid #99B5B7;background-color:#E8ECF0;max-width:700px;margin:20px 0;'>";
	print "<h3 style='padding:5px 10px;margin:0;border-bottom:1px solid #ccc;color:#222;'>
				<img src='".APP_PATH_IMAGES."blog_pencil.png' class='imgfix2'> 
				{$lang['shared_library_44']}
			</h3>";
	print "<div style='padding:5px 10px 5px 30px;width:600px;'>";	
	
	if ($error) {
		print  '<p>'.$error_str.'</p>';
	    print  "<p style='font-weight:bold;color:red;'>
					<img src='" . APP_PATH_IMAGES . "cross.png' class='imgfix'> 
					{$lang['shared_library_45']}
				</p>";
	} else {
	    print  "<p>
					<img src='" . APP_PATH_IMAGES . "tick.png' class='imgfix'> 
					<span style='color:green;font-weight:bold;'>{$lang['shared_library_46']}</span><br><br>
					" . (($status < 1 && !$longitudinal) ? $lang['shared_library_47'] : "") . "
					" . (($status > 0) ? $lang['shared_library_48'] : "") . "
				</p>";
	}
	// Give user link back to Design page
	renderPrevPageLink("Design/online_designer.php");
	print "</div>";

}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
/** 
 * 	USER MUST GIVE NAME TO FORM, THEN HIT SUBMIT
 */
elseif ($_SESSION['import_id'] != $_GET['importId']) 
{

	//determine if the form was downloaded or uploaded previously
	$shareSql = "select type from redcap_library_map where project_id = $project_id and library_id = " . $_GET['importId'];
	$shareResult = db_query($shareSql);
	$currentShare = 0;
	if(db_num_rows($shareResult) > 0) {
		$shareArray = db_fetch_array($shareResult);
		$currentShare = $shareArray[0];
	}
	

	include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

	## Online Form Editor
	print  "<div class='round' style='border:1px solid #99B5B7;background-color:#E8ECF0;max-width:700px;margin:20px 0;'>";
	
	print  "<h3 style='padding:5px 10px;margin:0;border-bottom:1px solid #ccc;color:#222;'>
				<img src='".APP_PATH_IMAGES."blog_pencil.png' class='imgfix2'> 
				{$lang['shared_library_44']}
			</h3>";
	print  "<div style='padding:5px 10px 5px 30px;width:600px;'>
			<p>
				{$lang['shared_library_49']}
			</p>";
			
	// Note if instrument has been uploaded/downloaded before
	if ($currentShare > 0) 
	{
		$updownText = ($currentShare == 1) ? $lang['shared_library_50'] : $lang['shared_library_51'];	
		print  "<p>
					<b>{$lang['global_02']}: {$lang['shared_library_52']} $updownText {$lang['shared_library_07']}.
					{$lang['shared_library_53']}</b>
				</p>";
	}
	
					
	//Query to get form names to display in drop-down list for choosing
	$form_menu_choices = "";
	$sql = "SELECT form_name, form_menu_description FROM $metadata_table WHERE project_id = $project_id 
			AND form_menu_description IS NOT NULL ORDER BY field_order";
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q)) {
		$form_menu_choices .= "<option value='{$row['form_name']}'>{$row['form_menu_description']}</option>";
	}
	
	//Obtain form name from URL (originates from library) and plug into text box
	if (isset($_GET['formtitle']) && $_GET['formtitle'] != "") {
		$formtitle = filter_tags(label_decode(label_decode(urldecode($_GET['formtitle']))));
		$form_colorstyle = "color:#000000";
	} else {
		$formtitle = "Type new form name";
		$form_colorstyle = "color:#777777";
	}
	
	// Don't show name of form since it's just a survey (not going to see it anyway)
	$formImportText = $lang['shared_library_55'];
	$formStyleVisible = "visible";
	
	print  "<form method='post'>";
	print  "<input type='hidden' name='import_id' value='".$_GET['importId']."'/>";
	print  "<p style='margin:15px 0;'>
				<input type='submit' name='submit' value=' {$lang['design_171']} ' style='font-size:13px;vertical-align:middle;'
					onclick=\"if( $('#new_form').val()=='' || $('#new_form').val()=='Type new form name'){alert('{$lang['shared_library_54']}');return false;}\"
				>
				$formImportText
				<input type='text' value='".str_replace('\'', '&#39;', $formtitle)."' name='new_form' id='new_form' class='x-form-text x-form-field' 
					style='width:300px;margin:0 3px;$form_colorstyle;visibility:$formStyleVisible;' maxlength='200'
					onkeydown=\"if(event.keyCode==13){if( $('#new_form').val()=='' || $('#new_form').val()=='Type new form name'){ $('#new_form').val('Type new form name'); $('#new_form').css('color','#777777'); return false; }else{ $('#new_form').val( $('#new_form').val().replace(/^\s+|\s+$/g,'')); }};\" 
					onblur=\"if( $('#new_form').val()==''){ $('#new_form').val('Type new form name'); $('#new_form').css('color','#777777'); }else{ $('#new_form').val( $('#new_form').val().replace(/^\s+|\s+$/g,'')); }\" 
					onfocus=\"if( $('#new_form').val()=='Type new form name'){ $('#new_form').val(''); $('#new_form').css('color','#000000');}\" 
					onclick=\"if( $('#new_form').val()=='Type new form name'){ $('#new_form').val(''); $('#new_form').css('color','#000000');}\" 
				>
			</p>";
	print  "<p style='margin:30px 0 10px;'>
				{$lang['shared_library_56']} 
				<a style='text-decoration:underline;' href='".SHARED_LIB_PATH."'>{$lang['shared_library_57']}</a>.
			</p>";
//	print "and insert it 
//			<select name='add_before_after' id='add_before_after' class='x-form-text x-form-field' style='height:22px;padding-right:0;margin:0 3px;'>
//				<option value='0'>before</option>
//				<option value='1' selected>after</option>
//			</select>
//			the form 
//			<select name='form_name_reference' id='form_name_reference' class='x-form-text x-form-field' style='height:22px;padding-right:0;margin:0 5px;'>
//				$form_menu_choices
//			</select>";
	print "</form>";
	
	// Give user link back to Design page
	print "<br><button onclick=\"window.location.href = app_path_webroot+'Design/online_designer.php?pid='+pid;\">{$lang['global_53']}</button>";
	print "<br>";
	print "<br>";
				
	print  "</div>";
	print  "</div>";
	
} else {
	
	include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
	
    print "<div class='round' style='border:1px solid #99B5B7;background-color:#E8ECF0;max-width:700px;margin:20px 0;'>";
	print "<h3 style='padding:5px 10px;margin:0;border-bottom:1px solid #ccc;color:#222;'>
				<img src='".APP_PATH_IMAGES."blog_pencil.png' class='imgfix2'> 
				{$lang['shared_library_44']}
			</h3>";
	print "<div style='padding:5px 10px 5px 30px;width:600px;'>";
	print "<p>{$lang['shared_library_58']}</p>";
	// Give user link back to Design page
	renderPrevPageLink("Design/online_designer.php");
	print "</div>";
}

include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
