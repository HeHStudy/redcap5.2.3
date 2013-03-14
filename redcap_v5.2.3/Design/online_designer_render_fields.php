<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

//Get any variables passed by Post
if (isset($_POST['pnid']))  $_GET['pnid'] = $_POST['pnid'];
if (isset($_POST['pid']))   $_GET['pid']  = $_POST['pid'];
if (isset($_POST['page'])) 	$_GET['page'] = $_POST['page'];

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
require_once (APP_PATH_DOCROOT . 'ProjectGeneral/form_renderer_functions.php');



//Get only the LAST field on the form (excluding the Form Status field)
$metadata = array();
if (isset($_GET['field_name'])) {
	$metadata[] = ($status > 0) ? $Proj->metadata_temp[$_GET['field_name']] : $Proj->metadata[$_GET['field_name']];
//Get ALL fields on this form (if any) and render them here as editable table
} else {
	if ($status > 0) {
		foreach ($Proj->forms_temp[$_GET['page']]['fields'] as $this_field=>$this_label) {
			$metadata[] = $Proj->metadata_temp[$this_field];
		}	
	} else {
		foreach ($Proj->forms[$_GET['page']]['fields'] as $this_field=>$this_label) {
			$metadata[] = $Proj->metadata[$this_field];
		}
	}
}

//Render form as editable table
if (count($metadata) > 0) {

	$string_data1 = "";
	
	//Replace any single or double quotes since they cause rendering problems
	$orig_quote = array("'", "\"");
	$repl_quote = array("&#039;", "&quot;");
	
	//Collect any "sql" field types
	$sql_fields = array();
	
	// Set default
	$prev_grid_name = "";

	//Render each table row
	foreach ($metadata as $row)
	{
		$field_name = $row['field_name'];
		$element_preceding_header = $row['element_preceding_header'];
		$element_type = $row['element_type'];
		$element_label = str_replace($orig_quote, $repl_quote, $row['element_label']);
		$element_enum = str_replace($orig_quote, $repl_quote, $row['element_enum']);
		$element_note = str_replace($orig_quote, $repl_quote, $row['element_note']);
		$element_validation_type = $row['element_validation_type'];
		$element_validation_min = $row['element_validation_min'];
		$element_validation_max = $row['element_validation_max'];
		$element_validation_checktype = $row['element_validation_checktype'];
		$branching_logic = trim($row['branching_logic']);
		$field_req = $row['field_req'];
		$field_phi = $row['field_phi'];
		$edoc_id = $row['edoc_id'];
		$edoc_display_img = $row['edoc_display_img'];
		$stop_actions = (isset($Proj->forms[$_GET['page']]['survey_id'])) ? parseStopActions($row['stop_actions']) : "";
		$custom_alignment = $row['custom_alignment'];
		$grid_name = trim($row['grid_name']);		
		
		
		//Do not process the Form Status field
		if ($_GET['page'] . "_complete" == $field_name) {
			continue;
		}
		
		## MATRIX QUESTION GROUPS
		$isMatrixField = false; //default
		// Beginning a new grid
		if ($grid_name != "" && $prev_grid_name != $grid_name)
		{
			// Set flag that this is a matrix field
			$isMatrixField = true;
			// Set that field is the first field in matrix group
			$matrixGroupPosition = '1';
		}
		// Continuing an existing grid
		elseif ($grid_name != "" && $prev_grid_name == $grid_name)
		{
			// Set flag that this is a matrix field
			$isMatrixField = true;
			// Set that field is *not* the first field in matrix group
			$matrixGroupPosition = 'X';
		}
		// Set value for next loop
		$prev_grid_name = $grid_name;
		
		
		//if this data field specifies a 'header' separator - process this first
		$hasSHtag = false;
		if ($element_preceding_header && ($field_name != $table_pk) && (!$_GET['edit_question'] || $_GET['section_header'])) 
		{
			// Tag if this field has a section header attached to it
			$hasSHtag = true;
			// IF a matrix field, then set flag in this element
			if ($isMatrixField) {
				$matrix_string_data1 = "'matrix_field'=>'$matrixGroupPosition', 'grid_name'=>'$grid_name',";
				$shIcons = "";
			} else {
				$matrix_string_data1 = "";
				$shIcons = "<div class=\'frmedit\' style=\'padding-bottom:4px;\'>"
						 . "<a href=\'javascript:;\' onclick=\'openAddQuesForm(\"$field_name\",\"$element_type\",1);\'><img src=\'".APP_PATH_IMAGES."pencil.png\' class=\'imgfix\' ignore=\'Yes\'></a> "
						 . "<a href=\'javascript:;\' onclick=\'deleteField(\"$field_name\",1);\'><img src=\'".APP_PATH_IMAGES."cross.png\' class=\'imgfix\' ignore=\'Yes\'></a> "
						 . "</div>";
			}
			$element_preceding_header = label_decode($element_preceding_header);
			if (strpos($element_preceding_header,"'") !== false) $element_preceding_header = str_replace("'","\'",$element_preceding_header); //Apostrophes cause issues when rendered, so replace with equivalent html character
			$element_preceding_header = nl2br($element_preceding_header);
			$string_data1 .= "\$elements1[] = array('rr_type'=>'header', 
													'css_element_class'=>'header',
													'field'=>'{$field_name}-sh', $matrix_string_data1
													'value'=>'$shIcons<div>$element_preceding_header</div>');\n";
			// If only editing/adding a single section header, stop this loop here 
			if ($_GET['section_header']) continue;
		}

		//process the true data element
		if ($element_type == 'sql') { 
			$string_data1 .= "\$elements1[]=array('rr_type'=>'select', 'field'=>'$field_name', 'name'=>'$field_name',";
			//Add to array of sql field type fields
			$sql_fields[] = $field_name;
		} else {
			$string_data1 .= "\$elements1[]=array('rr_type'=>'$element_type', 'field'=>'$field_name', 'name'=>'$field_name',";
		}
		
		// IF a matrix field, then set flag in this element
		if ($isMatrixField) {
			$string_data1 .= "'matrix_field'=>'$matrixGroupPosition', 'grid_name'=>'$grid_name',";
		}
		
		// Tag if this field has a section header attached to it
		if ($hasSHtag) {
			$string_data1 .= "'hasSH'=>'1',";
		}
		
		 
		//Process required field status (add note underneath field label)
		if ($field_req == '1') {
			$fieldReqClass = ($isMatrixField) ? 'reqlblm' : 'reqlbl'; // make matrix fields more compact
			$element_label .= "<div class='$fieldReqClass'>* {$lang['data_entry_39']}</div>";
		}
		
		//FIELD LABEL
		$string_data1 .= " 'label'=> '" . nl2br(cleanLabel($element_label)) . "',";
		
		// Custom alignment
		$string_data1 .= " 'custom_alignment'=>'$custom_alignment',";

		//For elements of type 'text', we'll handle data validation if details are provided in metadata
		if ($element_type == 'text' || $element_type == 'calc') {
			if($element_validation_type){
				$hold_validation_string = "'validation'=>'$element_validation_type', 'onblur'=>\"redcap_validate(this,'$element_validation_min','$element_validation_max',";
				if($element_validation_checktype){
					$hold_validation_string .= "'$element_validation_checktype','$element_validation_type')\"";
				}else{
					$hold_validation_string .= "'soft_typed','$element_validation_type')\"";
				}
				$string_data1 .= " $hold_validation_string,";
			}								
		}
		
		// Add edoc_id, if a Descriptive field has an attachement
		if ($element_type == 'descriptive' && is_numeric($edoc_id))
		{
			$string_data1 .= "'edoc_id'=>$edoc_id, 'edoc_display_img'=>$edoc_display_img, ";
		}
		
		// Add slider labels & and display value option
		if ($element_type == 'slider') {
			$slider_labels = parseSliderLabels($element_enum);
			$string_data1 .= "  'slider_labels'=>array('" . cleanHtml(emoticon_replace(filter_tags(label_decode($slider_labels['left'])))) . "',
								'" . cleanHtml(emoticon_replace(filter_tags(label_decode($slider_labels['middle'])))) . "',
								'" . cleanHtml(emoticon_replace(filter_tags(label_decode($slider_labels['right'])))) . "',
								'" . remBr(cleanLabel($element_validation_type)) . "'), ";
		}
		
		// Manually set enum for 'yesno' and 'truefalse' 
		if ($element_type == 'yesno') {
			$element_enum = YN_ENUM;			
		} elseif ($element_type == 'truefalse') {
			$element_enum = TF_ENUM;	
		}
				
		//For elements of type 'select', we need to include the $element_enum information
		if ($element_type == 'truefalse' || $element_type == 'yesno' || $element_type == 'select' || $element_type == 'radio' || $element_type == 'checkbox' || $element_type == 'sql') 
		{			
			//Add any checkbox fields to array to use during data pull later to fill form with existing data
			if ($element_type == 'checkbox') $chkbox_flds[$field_name] = "";
			
			//Do normal select/radio/checkbox options
			if ($element_type != 'sql') 
			{
				// Clean the label
				$element_enum = cleanLabel($element_enum);
				// If stop actions exist, add labels to element_enum and set back to original formatting (exclude matrix fields due to matrix header complexity)
				if (!empty($stop_actions) && !$isMatrixField)
				{
					$element_enum_temp = array();
					foreach (parseEnum($element_enum) as $this_key=>$this_choice)
					{
						// Append "end survey" string to choice if a stop action exists
						if (in_array($this_key, $stop_actions)) {
							$this_choice .= " <span class=\"stopnote\">{$lang['design_211']}</span>";
						}
						$element_enum_temp[] = "$this_key, $this_choice";
					}
					// Now set element_enum back again
					$element_enum = implode("\\n", $element_enum_temp);
				}
				// Add to string data
				$string_data1 .= " 'enum'=>'$element_enum',";
			}
			//Do SQL field for dynamic select box (Must be "select" statement)
			else 
			{
				$string_data1 .= ' \'enum\'=>"' . getSqlFieldEnum($element_enum) . '",';
			}	
			
		}

		//If an element_note is specified, we'll utilize here:
		if ($element_note) {
			if (strpos($element_note, "'") !== false) $element_note = str_replace("'", "&#39;", $element_note); //Apostrophes cause issues when rendered, so replace with equivalent html character
			$string_data1 .= " 'note'=>'" . cleanLabel($element_note) . "',";
		}

		//For elements of type 'textarea', we need to specify the number of rows to include
		//Note that we used to use $element_other for this, but probably not necessary
		if ($element_type == 'textarea'){
			$string_data1 .= " 'rows'=>'2', 'style'=>'width:97%;',";
		}
		
		// If branching logic exists, add to element in order to display that it exists on Online Form Editor
		$string_data1 .= " 'branching_logic'=>'<span id=\"bl-label_{$field_name}\" class=\"bledit\" style=\"visibility:" 
					   . ($branching_logic == "" ? "hidden" : "visible") 
					   . ";\">[{$lang['design_162']}]</span>'";
		
		$string_data1 .= " );";
		
	}
	
	//Evaluate the string to produce the $elements1 array
	eval($string_data1);
	
}

// Render table or row
print (PAGE == "Design/online_designer.php" || $_GET['ordering']) ? "<div id='draggablecontainer'>" : "<div>";
form_renderer($elements1, array());
print "</div>";
