<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


## Retrieve project data in Raw CSV format
## NOTE: $chkd_flds and $parent_chkd_flds are comma-delimited lists of fields that we're exporting
## with each field surrounded by single quotes (gets used in query).
function fetchDataCsv($chkd_flds="",$parent_chkd_flds="",$getReturnCodes=false,$do_hash=false,$do_remove_identifiers=false,
	$useStandardCodes=false,$useStandardCodeDataConversion=false,$standardId=-1,$standardCodeLookup=array(),
	$useFieldNames=true,$exportDags=true,$exportSurveyFields=true)
{
	// Global variables needed
	global  $Proj, $longitudinal, $project_id, $user_rights, $is_child, $is_child_of, $project_id_parent,
			$do_date_shift, $date_shift_max, $do_surveytimestamp_shift, $table_pk, $salt, $__SALT__, $reserved_field_names;
	
	// Get DAGs with group_id and unique name, if exist
	$dags = array();
	$dags_labels = array();
	if (is_object($Proj) && !empty($Proj)) {
		$dags = $Proj->getUniqueGroupNames();
		foreach ($Proj->getGroups() as $group_id=>$group_name) {
			$dags_labels[$group_id] = label_decode($group_name);
		}
	}
	
	// Set extra set of reserved field names for survey timestamps
	$reserved_field_names2 = explode(',', implode("_timestamp,", array_keys($Proj->forms)) . "_timestamp");
	
	// If surveys exist, get timestamp and identifier of all responses and place in array
	$timestamp_identifiers = array();
	if ($exportSurveyFields)
	{
		$sql = "select r.record, r.completion_time, p.participant_identifier, s.form_name, p.event_id
				from redcap_surveys s, redcap_surveys_response r, redcap_surveys_participants p, redcap_events_metadata a 
				where p.participant_id = r.participant_id and s.project_id = $project_id and s.survey_id = p.survey_id
				and p.event_id = a.event_id and r.first_submit_time is not null order by r.record, r.completion_time";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q))
		{
			// Replace double quotes with single quotes
			$row['participant_identifier'] = str_replace("\"", "'", label_decode($row['participant_identifier'])); 
			// If response exists but is not completed, note this in the export
			if ($row['completion_time'] == "") $row['completion_time'] = "[not completed]";
			// Add to array
			$timestamp_identifiers[$row['record']][$row['event_id']][$row['form_name']] = array('ts'=>$row['completion_time'], 'id'=>$row['participant_identifier']);
		}
	}
	// If returning the survey Return Codes, obtain them and put in array
	$returnCodes = array();
	if ($getReturnCodes)
	{
		$sql = "select s.survey_id, r.record, e.event_id, r.return_code, r.response_id, s.form_name
				from redcap_surveys s, redcap_surveys_participants p, redcap_surveys_response r, redcap_events_arms a, redcap_events_metadata e 
				where s.project_id = $project_id and s.survey_id = p.survey_id and p.participant_id = r.participant_id 
				and a.project_id = s.project_id and a.arm_id = e.arm_id and s.save_and_return = 1 
				and r.completion_time is null and r.first_submit_time is not null";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q))
		{
			// If this response doesn't have a return code, then create it on the fly
			if ($row['return_code'] == "") {
				$row['return_code'] = getUniqueReturnCode($row['survey_id'], $row['response_id']);
			}
			// Add to array
			$returnCodes[$row['record']][$row['event_id']][$row['form_name']] = $row['return_code'];
		}
	}
	
	## RETRIEVE HEADERS FOR CSV FILES AND SET DEFAULT VALUES FOR EACH DATA ROW
	//Create headers as first row of CSV and get default values for each row. Only need headers when exporting to R (SAS, SPSS, & STATA do not need them)
	$headersArray = array('','');
	$headersLabelsArray = array();
	$field_defaults = array();
	$field_defaults_labels = array();
	$field_type = array();
	$field_val_type = array();
	$field_names = array();
	$field_phi = array();
	$chkbox_choices = array();
	$mc_choices = array();
	$mc_field_types = array("radio", "select", "yesno", "truefalse"); // Don't include "checkbox" because it gets dealt with on its own
	$prev_form = "";
	$prev_field = "";
	//Build query
	if (!$is_child) {
		//Normal
		$sql = "select meta.field_name, meta.element_label, meta.element_enum, meta.element_type, meta.form_name, meta.element_validation_type, meta.field_phi, 
				map.data_conversion, map.data_conversion2
				from redcap_metadata meta left join redcap_standard_map map on meta.project_id = map.project_id and meta.field_name = map.field_name 
				where meta.project_id = $project_id and meta.field_name in ($chkd_flds) and meta.element_type != 'descriptive' order by meta.field_order";
	} else {
		//If parent/child linking exists
		if ($chkd_flds == "") $chkd_flds = "''";
		$sql = "select field_name, element_label, element_enum, element_type, form_name, element_validation_type, field_phi, field_order, tbl, data_conversion, data_conversion2
				from (
					(select meta.field_name, meta.element_label, meta.element_enum, meta.element_type, meta.form_name, meta.element_validation_type, meta.field_phi, meta.field_order, 1 as tbl, map.data_conversion, map.data_conversion2
					from redcap_metadata meta left join redcap_standard_map map on meta.project_id = map.project_id and meta.field_name = map.field_name
					where meta.project_id = $project_id_parent and meta.field_name in ($parent_chkd_flds) and meta.element_type != 'descriptive') 
					UNION 
					(select meta.field_name, meta.element_label, meta.element_enum, meta.element_type, meta.form_name, meta.element_validation_type, meta.field_phi, meta.field_order, 2 as tbl, map.data_conversion, map.data_conversion2 
					from redcap_metadata meta left join redcap_standard_map map on meta.project_id = map.project_id and meta.field_name = map.field_name
					where meta.project_id = $project_id and meta.field_name in ($chkd_flds) and meta.element_type != 'descriptive')
				) as x order by tbl, field_order";	
	}
	$q = db_query($sql);
	while($row = db_fetch_array($q)) 
	{
		// If starting a new form and form is a survey, then add survey timestamp field here
		if ((($prev_form != $row['form_name'] && $row['field_name'] != $table_pk) 
			|| ($prev_form == $row['form_name'] && $prev_field == $table_pk)) 
			&& isset($Proj->forms[$row['form_name']]['survey_id']))
		{		
			// If returning the survey Return Codes and survey has Save&Return enabled, add return_code header
			if ($getReturnCodes && $Proj->surveys[$Proj->forms[$row['form_name']]['survey_id']]['save_and_return'])
			{
				$returnCodeFieldname = $row['form_name'].'_return_code';
				$headersArray[0] .= "$returnCodeFieldname,";
				$headersArray[1] .= ',';
				$field_defaults[$returnCodeFieldname] = '"",';
				$field_defaults_labels[$returnCodeFieldname] = '"",';
			}
			// Add timestamp and identifier, if any surveys exist
			if ($exportSurveyFields)
			{
				// Add timestamp
				$timestampFieldname = $row['form_name'].'_timestamp';
				$headersArray[0] .= "$timestampFieldname,";
				$headersArray[1] .= ',';
				$field_defaults[$timestampFieldname] = '"",';
				$field_defaults_labels[$timestampFieldname] = '"",';
			}
		}		
		
		//Get field_name as column header
		$lookupValue = isset($standardCodeLookup[$row['field_name']]) ? $standardCodeLookup[$row['field_name']] : '';
		if ($row['element_type'] != "checkbox") 
		{
			// REGULAR FIELD (NON-CHECKBOX)
			// Set headers
			if ($useFieldNames) {
				$headersArray[0] .= $row['field_name'] . ',';
				if (trim($lookupValue) != '' && $lookupValue != $row['field_name']) {
					$headersArray[1] .=  $lookupValue . ',';
				}else {
					$headersArray[1] .=  $row['field_name']. ',';
				}
			}else {
				if (trim($lookupValue) != '') {
					$headersArray[0] .=  $lookupValue . ',';
				}else {
					$headersArray[0] .=  $row['field_name'] . ',';
				}
			}
			// Set header labels
			$headersLabelsArray[$row['field_name']] = $row['element_label'];
			// For multiple choice questions, store codes/labels in array for later use
			if (in_array($row['element_type'], $mc_field_types))
			{
				if ($row['element_type'] == "yesno") {
					$mc_choices[$row['field_name']] = parseEnum("1, Yes \\n 0, No");
				} elseif ($row['element_type'] == "truefalse") {
					$mc_choices[$row['field_name']] = parseEnum("1, True \\n 0, False");
				} else {
					foreach (parseEnum($row['element_enum']) as $this_value=>$this_label) 
					{
						// Replace characters that were converted during post (they will have ampersand in them)
						if (strpos($this_label, "&") !== false) {
							$this_label = html_entity_decode($this_label, ENT_QUOTES);
						}
						//Replace double quotes with single quotes
						$this_label = str_replace("\"", "'", $this_label); 
						//Replace line breaks with two spaces
						$this_label = str_replace("\r\n", "  ", $this_label);
						//Add to array
						$mc_choices[$row['field_name']][$this_value] = $this_label;
					}
				}
			}
		} 
		else 
		{
			// CHECKBOX FIELDS: Loop through checkbox elements and append string to variable name
			foreach (parseEnum($row['element_enum']) as $this_value=>$this_label) 
			{
				// Add multiple choice values to array for later use
				$chkbox_choices[$row['field_name']][$this_value] = '0,';
				// If coded value is not numeric, then format to work correct in variable name (no spaces, caps, etc)
				if (!is_numeric($this_value)) {
					$this_value = preg_replace("/[^a-z0-9]/", "", strtolower($this_value));
				}
				// Headers: Append triple underscore + coded value
				$checkboxLookupValue = evalDataConversion($row['field_name'], $row['element_type'], $this_value, $row['data_conversion']);
				if($useFieldNames) {
					$headersArray[0] .= $row['field_name'] . '___' . $this_value. ',';
					if(trim($checkboxLookupValue) != '') {
						$headersArray[1] .=  $checkboxLookupValue . ',';
					}else {
						$headersArray[1] .=  ',';
					}
				} else {
					if(trim($checkboxLookupValue) != '') {
						$headersArray[0] .=  $checkboxLookupValue . ',';
					}else {
						$headersArray[0] .=  $row['field_name'] . '___' . $this_value. ',';
					}
				}
				// Set header labels
				$headersLabelsArray[$row['field_name'] . '___' . $this_value] = $row['element_label'] . " (choice='$this_label')";
			}
		}
		//Get field type of each field to vary the handling of each
		$field_type[$row['field_name']] = $row['element_type'];
		//Set default row values
		switch ($row['element_type']) {
			case "textarea":
			case "text":
				//Get validation type and put into array
				$field_val_type[$row['field_name']] = $row['element_validation_type'];
				switch ($row['element_validation_type']) {
					//Numbers and dates do not need quotes around them
					case "float":
					case "int":
					case "date":
						$field_defaults[$row['field_name']] = ',';
						$field_defaults_labels[$row['field_name']] = ',';
						break;
					//Put quotes around normal text strings
					default:
						$field_defaults[$row['field_name']] = '"",';
						$field_defaults_labels[$row['field_name']] = '"",';
				}
				break;
			case "select":
				if ($row['field_name'] == $row['form_name'] . "_complete") {
					$field_defaults[$row['field_name']] = '0,'; //Form Status gets default of 0
					$field_defaults_labels[$row['field_name']] = '"Incomplete",';
				} else {
					$field_defaults[$row['field_name']] = ','; //Regular dropdowns get null default
					$field_defaults_labels[$row['field_name']] = ',';
				}
				break;
			case "checkbox":
				foreach ($chkbox_choices[$row['field_name']] as $this_value=>$this_label) {
					if ($useStandardCodeDataConversion) {
						$field_defaults[$row['field_name']][$this_value] = evalCheckboxDataConversion(0, $row['data_conversion2']).',';
					} else {
						$field_defaults[$row['field_name']][$this_value] = '0,';
					}
					$field_defaults_labels[$row['field_name']][$this_value] = '"Unchecked",';
				}
				break;
			default:
				$field_defaults[$row['field_name']] = ',';
				$field_defaults_labels[$row['field_name']] = ',';
		}
		
		// Store all field names into array to use for Syntax File code
		if (!isset($chkbox_choices[$row['field_name']])) {
			// Add non-checkbox fields to array
			$field_names[] = $row['field_name'];		
		} else {
			// If field is a checkbox, then expand to create variables for each choice
			foreach ($chkbox_choices[$row['field_name']] as $this_value=>$this_label) {
				// If coded value is not numeric, then format to work correct in variable name (no spaces, caps, etc)
				if (!is_numeric($this_value)) {
					$this_value = preg_replace("/[^a-z0-9]/", "", strtolower($this_value));
				}
				// Append triple underscore + coded value
				$field_names[] = $row['field_name'] . '___' . $this_value;
			}
		}
		
		//Store all fields that are Identifiers into array
		if ($row['field_phi']) {
			$field_phi[] = $row['field_name'];
		}
		
		//Add extra columns (if needed) if we're on the first field
		if ($row['field_name'] == $table_pk)
		{
			// Add event name, if longitudinal
			if ($longitudinal) 
			{
				$headersArray[0] .= 'redcap_event_name,';
				$headersArray[1] .= ',';
				$field_defaults['redcap_event_name'] = '"",';
				$field_defaults_labels['redcap_event_name'] = '"",';
			}
			// Add DAG name, if project has DAGs and user is not in a DAG
			if ($exportDags)
			{
				$headersArray[0] .= 'redcap_data_access_group,';
				$headersArray[1] .= ',';
				$field_defaults['redcap_data_access_group'] = '"",';
				$field_defaults_labels['redcap_data_access_group'] = '"",';
			}
			// Add survey identifier (unless we've set it to remove all identifiers - treat survey identifier same as field identifier)
			if ($exportSurveyFields && !$do_remove_identifiers) {
				$headersArray[0] .= 'redcap_survey_identifier,';
				$headersArray[1] .= ',';
				$field_defaults['redcap_survey_identifier'] = '"",';
				$field_defaults_labels['redcap_survey_identifier'] = '"",';	
			}
		}
		
		// Set values for next loop
		$prev_form = $row['form_name'];
		$prev_field = $row['field_name'];
	}
	
	
	// CREATE ARRAY OF FIELD DEFAULTS SPECIFIC TO EVERY EVENT (BASED ON FORM-EVENT DESIGNATION)
	$field_defaults_events = array();
	$field_defaults_labels_events = array();
	// CLASSIC: Just add $field_defaults array as only array element
	if (!$longitudinal) {
		$field_defaults_events[$Proj->firstEventId] = $field_defaults;
		$field_defaults_labels_events[$Proj->firstEventId] = $field_defaults_labels;
	}
	// LONGITUDINAL: Loop through each event and set defaults based on form-event mapping
	else {
		// Loop through each event
		foreach (array_keys($Proj->eventInfo) as $event_id) {
			// Get $designated_forms from $Proj->eventsForms
			$designated_forms = (isset($Proj->eventsForms[$event_id])) ? $Proj->eventsForms[$event_id] : array();
			// Loop through each default field value and either keep or remove for this event
			foreach ($field_defaults as $field=>$raw_value) {
				// Get default label value
				$label_value = $field_defaults_labels[$field];
				// Check if a checkbox OR a form status field (these are the only 2 we care about because they are the only ones with default values)
				$field_form = $Proj->metadata[$field]['form_name'];
				if ($Proj->isCheckbox($field) || $field == $field_form."_complete") {
					// Is field's form designated for the current event_id?
					if (!in_array($field_form, $designated_forms)) {
						// Set both raw and label value as blank (appended with comma for delimiting purposes)
						if (is_array($raw_value)) {
							// Loop through all checkbox choices and set each individual value
							foreach (array_keys($raw_value) as $code) {
								$raw_value[$code] = $label_value[$code] = ",";
							}
						} else {
							$raw_value = $label_value = ",";
						}
					}
				}
				// Add to field defaults event array
				$field_defaults_events[$event_id][$field] = $raw_value;
				$field_defaults_labels_events[$event_id][$field] = $label_value;
			}
		}
	}
	
	
	## BUILD CSV STRING OF HEADERS
	$headers = '';
	if ($useFieldNames && $useStandardCodes) {
		$headers .= substr($headersArray[0],0,-1) . "\n" . substr($headersArray[1],0,-1) . "\n";
	}else {
		$headers .= substr($headersArray[0],0,-1) . "\n";
	}
	
	
	## BUILD CSV STRING OF HEADER LABELS
	$headers_labels = '';
	//Use for replacing strings
	$orig = array("\"", "\r\n", "\r");
	$repl = array("'", "  ","");
	foreach (explode(",", $headersArray[0]) as $this_field)
	{
		if (trim($this_field) != '')
		{
			if (isset($headersLabelsArray[$this_field])) {
				$this_label = str_replace($orig, $repl, strip_tags(label_decode($headersLabelsArray[$this_field])));
			} elseif (isset($reserved_field_names[$this_field])) {
				$this_label = str_replace($orig, $repl, strip_tags(label_decode($reserved_field_names[$this_field])));
			} elseif (in_array($this_field, $reserved_field_names2)) {
				$this_label = 'Survey Timestamp';
			} else {
				$this_label = "[???????]";
			}
			$headers_labels .= '"' . $this_label . '",';
		}
	}
	$headers_labels = substr($headers_labels, 0, -1) . "\n";
	
	
	###########################################################################
	## RETRIEVE DATA
	//Set defaults
	$data_csv = "";
	$data_csv_labels = "";
	$record_id = "";
	$event_id  = "";
	$group_id  = "";
	$form = "";
	$id = 0;
	// Set array to keep track of which records are in a DAG
	$recordDags = array();
	//Check if any Events have been set up for this project. If so, add new column to list Event in CSV file.
	$event_names = array();
	$event_labels = array();
	if ($longitudinal) {
		$event_names = $Proj->getUniqueEventNames();
		foreach ($Proj->eventInfo as $event_id=>$attr) {
			$event_labels[$event_id] = label_decode($attr['name_ext']);
		}
	}
	//Build query for pulling the data and for building code for syntax files
	if (!$is_child) {
		// NORMAL
		if ($user_rights['group_id'] == "") {
			$group_sql  = "";
			// If DAGS exist, also pull group_id's from data table
			if ($exportDags) {
				$chkd_flds .= ", '__GROUPID__'";
			}
		} else {
			$group_sql  = "AND record IN (" . pre_query("SELECT record FROM redcap_data where project_id = $project_id and field_name = '__GROUPID__' AND value = '".$user_rights['group_id']."'") . ")"; 
		}
		if ($useStandardCodes) {
			// If using Standard Mapping, also retrieve data conversion values
			if (!$longitudinal) {
				$data_sql = "select d.*, m.data_conversion, m.data_conversion2 from redcap_data d, redcap_standard_map m 
							 where d.project_id = m.project_id and d.field_name = m.field_name 
							 and m.standard_code_id in (" . pre_query("select standard_code_id from redcap_standard_code c where c.standard_id = $standardid") . ")
							 and d.project_id = $project_id and d.field_name in ($chkd_flds) and d.record != '' $group_sql 
							 and d.event_id = {$Proj->firstEventId}
							 order by abs(d.record), d.record, d.event_id";
			} else {
				$data_sql = "select d.*, m.data_conversion, m.data_conversion2 
							 from redcap_data d, redcap_standard_map m, redcap_events_metadata e, redcap_events_arms a 
							 where d.project_id = $project_id and d.project_id = a.project_id
							 and a.arm_id = e.arm_id and e.event_id = d.event_id
							 and d.field_name = m.field_name and d.project_id = m.project_id 
							 and m.standard_code_id in (" . pre_query("select standard_code_id from redcap_standard_code c where c.standard_id = $standardid") . ")
							 and d.field_name in ($chkd_flds) and d.record != '' $group_sql 
							 order by abs(d.record), d.record, a.arm_num, e.day_offset, e.descrip";
			}
		} else {
			// Pull data as normal
			if (!$longitudinal) {
				$data_sql = "select d.*, '' as data_conversion, '' as data_conversion2 from redcap_data d
							 where d.project_id = $project_id and d.field_name in ($chkd_flds) 
							 and d.event_id = {$Proj->firstEventId}
							 and d.record != '' $group_sql order by abs(d.record), d.record, d.event_id";
			} else {	 
				$data_sql = "select d.*, '' as data_conversion, '' as data_conversion2 
							 from redcap_data d, redcap_events_metadata e, redcap_events_arms a
							 where d.project_id = $project_id and d.project_id = a.project_id
							 and a.arm_id = e.arm_id and e.event_id = d.event_id 
							 and d.field_name in ($chkd_flds) and d.record != '' $group_sql 
							 order by abs(d.record), d.record, a.arm_num, e.day_offset, e.descrip";
			}
		}
	} else {
		// PARENT/CHILD: If parent/child linking exists
		if ($chkd_flds == "") $chkd_flds = "''";		
		if ($useStandardCodes) {
			// If using Standard Mapping, also retrieve data conversion values
			$data_sql = "(SELECT d.*,m.data_conversion, m.data_conversion2 FROM redcap_data d, redcap_standard_map m 
						 where d.project_id = m.project_id and d.field_name = m.field_name 
						 and m.standard_code_id in (" . pre_query("select standard_code_id from redcap_standard_code c where c.standard_id = $standardId") . ")
						 and d.project_id = $project_id_parent and d.field_name IN ($parent_chkd_flds) AND d.record in 
						 (SELECT DISTINCT record FROM redcap_data where project_id = $project_id)) UNION
						 (SELECT * FROM redcap_data where project_id = $project_id and field_name IN ($chkd_flds)) ORDER BY abs(record), record, event_id";
		} else {
			// Pull data as normal
			$data_sql = "(SELECT *, '' as data_conversion, '' as data_conversion2 FROM redcap_data where project_id = $project_id_parent and field_name IN ($parent_chkd_flds) AND record in 
						 (" . pre_query("SELECT DISTINCT record FROM redcap_data where project_id = $project_id AND record != ''") . ")) 
						 UNION
						 (SELECT *, '' as data_conversion, '' as data_conversion2 FROM redcap_data where project_id = $project_id and field_name IN ($chkd_flds) AND record != '') ORDER BY abs(record), record, event_id";
		}
	}
	//Log this data export event
	if (!$is_child) {
		//Normal
		$log_display = $chkd_flds;
	} else {
		//If parent/child linking exists
		$log_display = ($chkd_flds == "") ? $parent_chkd_flds : "$parent_chkd_flds, $chkd_flds";
	}
	
	## PRE-LOAD DEFAULT VALUES FOR ALL FIELDS AS PLACEHOLDERS
	$firstDataEventId = $Proj->firstEventId;
	if ($longitudinal) {
		// LONGITUDINAL: Since we don't know what event_id will come out first from the data table, get it before we start looping in the data (not ideal but works)
		$q = db_query("$data_sql limit 1");
		if (db_num_rows($q) > 0) $firstDataEventId = db_result($q, 0, "event_id");
	}	
	// Set default answers for first row
	$this_row_answers = $field_defaults_events[$firstDataEventId];
	$this_row_answers_labels = $field_defaults_labels_events[$firstDataEventId];
	
	## QUERY FOR DATA
	$q = db_query($data_sql);
	// If an error occurs for the query, output the error and stop here.
	if (db_error() != "") exit("<b>MySQL error " . db_errno() . ":</b><br>" . db_error() . "<br><br><b>Failed query:</b><br>$data_sql");
	//Loop through each answer, then render each line after all collected
	while ($row = db_fetch_assoc($q)) 
	{
		// Trim record, just in case spaces exist at beginning or end
		$row['record'] = trim($row['record']);
		// Check if need to start new line of data for next record
		if ($record_id !== $row['record'] || $event_id != ($is_child ? $Proj->firstEventId : $row['event_id'])) 
		{
			//Get date shifted day amount for this record
			if ($do_date_shift) {
				$days_to_shift = get_shift_days($row['record'], $date_shift_max);
			}
			//Render this row's answers
			if ($id != 0) 
			{	
				// HASH ID: If the record id is an Identifier and the user has de-id rights access, do an MD5 hash on the record id
				// Also, make sure record name field is not blank (can somehow happen - due to old bug?) by manually setting it here using $record_id.
				if ((($user_rights['data_export_tool'] != '1' && in_array($table_pk, $field_phi)) || $do_hash)) {
					$this_row_answers[$table_pk] = $this_row_answers_labels[$table_pk] = md5($salt . $record_id . $__SALT__) . ',';
				} else {
					$this_row_answers[$table_pk] = $this_row_answers_labels[$table_pk] = '"' . $record_id . '",';
				}
				//If Events exist, add Event Name
				if ($longitudinal) {
					$this_row_answers['redcap_event_name'] = '"' . $event_names[$event_id] . '",';
					$this_row_answers_labels['redcap_event_name'] = '"' . str_replace("\"", "'", $event_labels[$event_id]) . '",';
				}
				// If DAGs exist, add unique DAG name
				if ($exportDags) {
					$this_row_answers['redcap_data_access_group'] = '"' . $dags[$recordDags[$record_id]] . '",';
					$this_row_answers_labels['redcap_data_access_group'] = '"' . str_replace("\"", "'", $dags_labels[$recordDags[$record_id]]) . '",';
				}
				// If we're requesting the return codes, add them here
				if ($getReturnCodes && isset($returnCodes[$record_id][$event_id])) {
					foreach ($returnCodes[$record_id][$event_id] as $this_form=>$this_return_code) {
						$this_row_answers[$this_form.'_return_code'] = $this_row_answers_labels[$this_form.'_return_code']  = '"' . $this_return_code . '",';
					}
				}
				// If project has any surveys, add the survey completion timestamp
				if ($exportSurveyFields && isset($timestamp_identifiers[$record_id][$event_id])) {
					//Get date shifted day amount for this record
					if ($do_surveytimestamp_shift) {
						$days_to_shift_survey_ts = get_shift_days($record_id, $date_shift_max);
					}
					// Add the survey completion timestamp for each survey
					foreach ($timestamp_identifiers[$record_id][$event_id] as $this_form=>$attr) {
						if (isset($this_row_answers[$this_form.'_timestamp'])) {
							// If user set option to date-shift the survey timestamp, then shift it
							if ($do_surveytimestamp_shift) {
								list ($thisdate, $thistime) = explode(" ", $attr['ts']);
								$attr['ts'] = trim(shift_date_format($thisdate, $days_to_shift_survey_ts, "") . " $thistime");
							}
							// Add timestamp to arrays
							$this_row_answers[$this_form.'_timestamp'] = $this_row_answers_labels[$this_form.'_timestamp'] = '"' . $attr['ts'] . '",';
						}
					}
				}
				// If project has any surveys, add the identifier (if exists)
				if ($exportSurveyFields && !$do_remove_identifiers && isset($timestamp_identifiers[$record_id][$Proj->getFirstEventIdInArmByEventId($event_id)][$Proj->firstForm])) {
					$this_row_answers['redcap_survey_identifier'] = $this_row_answers_labels['redcap_survey_identifier'] = '"' . $timestamp_identifiers[$record_id][$Proj->getFirstEventIdInArmByEventId($event_id)][$Proj->firstForm]['id'] . '",';
				}
				// Render row
				$data_csv .= render_row($this_row_answers);	
				$data_csv_labels .= render_row($this_row_answers_labels);	
				// Set default answers for next row of data (specific for current event_id)
				$nextRowEventId = ($is_child ? $Proj->firstEventId : $row['event_id']);
				$this_row_answers = $field_defaults_events[$nextRowEventId];	
				$this_row_answers_labels = $field_defaults_labels_events[$nextRowEventId];			
			}
			$id++;
		}
		// Set values for next loop
		$record_id = $row['record'];
		$event_id  = $is_child ? $Proj->firstEventId : $row['event_id'];
		// Output to array for this row of data. Format if a text field.
		$this_field_type = ($row['field_name'] == '__GROUPID__') ? 'group_id' : $field_type[$row['field_name']];
		switch ($this_field_type) 
		{
			// DAG group_id
			case "group_id":
				$group_id = $row['value'];
				if (isset($dags[$group_id])) {
					$recordDags[$record_id] = $group_id;
				}
				break;
			// Text/notes field
			case "textarea":
			case "text":
				// Replace characters that were converted during post (they will have ampersand in them)
				if (strpos($row['value'], "&") !== false) {
					$row['value'] = html_entity_decode($row['value'], ENT_QUOTES);
				}
				//Replace double quotes with single quotes
				$row['value'] = str_replace("\"", "'", $row['value']); 
				//Replace line breaks with two spaces
				$row['value'] = str_replace("\r\n", "  ", $row['value']);
				// Save this answer in array
				switch ($field_val_type[$row['field_name']]) 
				{
					// Numbers do not need quotes around them
					case "float":
					case "int":
						if (trim($row['data_conversion']) != "") {
							$this_row_answers[$row['field_name']] = evalDataConversion($row['field_name'], $field_type[$row['field_name']], $row['value'], $row['data_conversion']). ',';
						} else {
							$this_row_answers[$row['field_name']] = $row['value'] . ',';
						}
						$this_row_answers_labels[$row['field_name']] = $row['value'] . ',';
						break;
					//Reformat dates from YYYY-MM-DD format to MM/DD/YYYY format
					case "date":
					case "date_ymd":
					case "date_mdy":
					case "date_dmy":
						// Render date
						$dformat = "";
						if($useStandardCodeDataConversion && trim($row['data_conversion']) != "") {
							$dformat = trim($row['data_conversion']);
						}
						// Don't do date shifting
						if (!$do_date_shift) {
							
							$this_row_answers_labels[$row['field_name']] = '"' . $row['value'] . '",';
							if ($dformat == "") {
								$this_row_answers[$row['field_name']] = '"' . $row['value'] . '",';;
							} else {
								$this_row_answers[$row['field_name']] = '"' . format_date($row['value'], $dformat) . '",';
							}
						//Do date shifting
						} else {
							$this_row_answers[$row['field_name']] = '"' . shift_date_format($row['value'], $days_to_shift, $dformat) . '",';
							$this_row_answers_labels[$row['field_name']] = '"' . shift_date_format($row['value'], $days_to_shift, "") . '",'; 
						}
						break;
					//Reformat datetimes from YYYY-MM-DD format to MM/DD/YYYY format
					case "datetime":
					case "datetime_ymd":
					case "datetime_mdy":
					case "datetime_dmy":
					case "datetime_seconds":
					case "datetime_seconds_ymd":
					case "datetime_seconds_mdy":
					case "datetime_seconds_dmy":
						if (trim($row['value']) != '')
						{
							// Don't do date shifting
							if (!$do_date_shift) {
								$this_row_answers[$row['field_name']] = $this_row_answers_labels[$row['field_name']] = '"' . $row['value'] . '",';
							// Do date shifting
							} else {
								// Split up into date and time components
								list ($thisdate, $thistime) = explode(" ", $row['value']);
								$this_row_answers[$row['field_name']] = $this_row_answers_labels[$row['field_name']] = '"' . shift_date_format($thisdate, $days_to_shift, "") . " " . $thistime . '",'; 
							}
						}
						break;
					case "time":
						//Render time
						// Do labels first before data conversion is applied, if applied
						$this_row_answers_labels[$row['field_name']] = '"' . $row['value'] . '",';
						if ($useStandardCodeDataConversion && trim($row['data_conversion']) != "") {
							$dformat = trim($row['data_conversion']);
							$row['value'] = format_time($row['value'], $dformat);
						}
						$this_row_answers[$row['field_name']] = '"' . $row['value'] . '",';
						break;
					//Put quotes around normal text strings
					default:
						$this_row_answers[$row['field_name']] = $this_row_answers_labels[$row['field_name']] = '"' . trim($row['value']) . '",';
				}
				break;
			case "checkbox":
				// Make sure that the data value exists as a coded value for the checkbox. If so, export as 1 (for checked).
				if (isset($this_row_answers[$row['field_name']][$row['value']])) {
					if ($useStandardCodeDataConversion) {
						$this_row_answers[$row['field_name']][$row['value']] = evalCheckboxDataConversion(1, $row['data_conversion2']).',';
					} else {
						$this_row_answers[$row['field_name']][$row['value']] = '1,';
					}
					$this_row_answers_labels[$row['field_name']][$row['value']] = '"Checked",';
				}
				break;
			case "file":
				$this_row_answers[$row['field_name']] = $this_row_answers_labels[$row['field_name']] = '"[document]",';
				break;
			default:
				// For multiple choice questions (excluding checkboxes), add choice labels to answers_labels
				if (in_array($this_field_type, $mc_field_types)) {
					$this_row_answers_labels[$row['field_name']] = '"' . $mc_choices[$row['field_name']][$row['value']] . '",';
				} else {
					if (!is_numeric($row['value'])) {
						$this_row_answers_labels[$row['field_name']] = '"' . $row['value'] . '"' . ',';
					} else {
						$this_row_answers_labels[$row['field_name']] = $row['value'] . ',';
					}
				}
				if (!is_numeric($row['value'])) {
					$row['value'] = '"' . $row['value'] . '"';
				}
				// Standards Mapping data conversion
				if (strpos($row['data_conversion'], "&") !== false) {
					//quotes can be inserted into data conversion for select and radio types
					$row['data_conversion'] = html_entity_decode($row['data_conversion'], ENT_QUOTES);
				}
				//Save this answer in array
				if (trim($row['data_conversion']) != "") {
					$this_row_answers[$row['field_name']] = evalDataConversion($row['field_name'], $field_type[$row['field_name']], $row['value'], $row['data_conversion']) . ',';
				} else {
					$this_row_answers[$row['field_name']] = $row['value'] . ',';
				}
		}
	}
	//Render the last row's answers
	if (db_num_rows($q) > 0) 
	{
		// HASH ID: If the record id is an Identifier and the user has de-id rights access, do an MD5 hash on the record id
		// Also, make sure record name field is not blank (can somehow happen - due to old bug?) by manually setting it here using $record_id.
		if ((($user_rights['data_export_tool'] != '1' && in_array($table_pk, $field_phi)) || $do_hash)) {
			$this_row_answers[$table_pk] = $this_row_answers_labels[$table_pk] = md5($salt . $record_id . $__SALT__) . ',';
		} else {
			$this_row_answers[$table_pk] = $this_row_answers_labels[$table_pk] = '"' . $record_id . '",';	
		}
		//If Events exist, add Event Name
		if ($longitudinal) {
			$this_row_answers['redcap_event_name'] = '"' . $event_names[$event_id] . '",';
			$this_row_answers_labels['redcap_event_name'] = '"' . str_replace("\"", "'", $event_labels[$event_id]) . '",';
		}
		// If DAGs exist, add unique DAG name
		if ($exportDags) {
			$this_row_answers['redcap_data_access_group'] = '"' . $dags[$recordDags[$record_id]] . '",';
			$this_row_answers_labels['redcap_data_access_group'] = '"' . str_replace("\"", "'", $dags_labels[$recordDags[$record_id]]) . '",';
		}
		// If we're requesting the return codes, add them here
		if ($getReturnCodes && isset($returnCodes[$record_id][$event_id])) {
			foreach ($returnCodes[$record_id][$event_id] as $this_form=>$this_return_code) {
				$this_row_answers[$this_form.'_return_code'] = $this_row_answers_labels[$this_form.'_return_code']  = '"' . $this_return_code . '",';
			}
		}
		// If project has any surveys, add the survey completion timestamp
		if ($exportSurveyFields && isset($timestamp_identifiers[$record_id][$event_id])) {
			//Get date shifted day amount for this record
			if ($do_surveytimestamp_shift) {
				$days_to_shift_survey_ts = get_shift_days($record_id, $date_shift_max);
			}
			// Add the survey completion timestamp for each survey
			foreach ($timestamp_identifiers[$record_id][$event_id] as $this_form=>$attr) {
				if (isset($this_row_answers[$this_form.'_timestamp'])) {
					// If user set option to date-shift the survey timestamp, then shift it
					if ($do_surveytimestamp_shift) {
						list ($thisdate, $thistime) = explode(" ", $attr['ts']);
						$attr['ts'] = trim(shift_date_format($thisdate, $days_to_shift_survey_ts, "") . " $thistime");
					}
					// Add timestamp to arrays
					$this_row_answers[$this_form.'_timestamp'] = $this_row_answers_labels[$this_form.'_timestamp'] = '"' . $attr['ts'] . '",';
				}
			}
		}
		// If project has any surveys, add the identifier (if exists)
		if ($exportSurveyFields && !$do_remove_identifiers && isset($timestamp_identifiers[$record_id][$Proj->getFirstEventIdInArmByEventId($event_id)][$Proj->firstForm])) {
			$this_row_answers['redcap_survey_identifier'] = $this_row_answers_labels['redcap_survey_identifier'] = '"' . $timestamp_identifiers[$record_id][$Proj->getFirstEventIdInArmByEventId($event_id)][$Proj->firstForm]['id'] . '",';
		}
		// Render last row
		$data_csv .= render_row($this_row_answers);	
		$data_csv_labels .= render_row($this_row_answers_labels);	
	}
	
	return array($headers, $headers_labels, $data_csv, $data_csv_labels, $field_names);
	###########################################################################
}


//Function for rendering each row of data after collecting in array
function render_row($this_row_answers) {
	$this_line = "";
	foreach ($this_row_answers as $this_answer) {
		if (!is_array($this_answer)) {
			$this_line .= $this_answer;
		} else {
			//Loop through Checkbox choices
			foreach ($this_answer as $chkbox_choice) {
				$this_line .= $chkbox_choice;
			}
		}			
	}
	return substr($this_line,0,-1) . "\n";
}
/**
 * DATE SHIFTING FUNCTIONS
 */
function get_shift_days($idnumber,$date_shift_max) {
	global $salt, $__SALT__;
	if ($date_shift_max == "") {
		$date_shift_max = 0;
	}
	$dec = hexdec(substr(md5($salt . $idnumber . $__SALT__), 10, 8));
	// Set as integer between 0 and $date_shift_max
	$days_to_shift = round($dec / pow(10,strlen($dec)) * $date_shift_max);
	return $days_to_shift;
}
function shift_date($date,$days_to_shift) {
	return shift_date_format($date,$days_to_shift,"");
}
	
function shift_date_format($date,$days_to_shift,$f="") {
	if ($date == "") return $date;
	//$dFormat = "m/d/Y";
	$dFormat = "Y-m-d";
	if (trim($f) != "") {
		$dFormat = $f;
	}
	// Separate date into components
	$mm   = substr($date, 5, 2) + 0;
	$dd   = substr($date, 8, 2) + 0;
	$yyyy = substr($date, 0 , 4) + 0;
	// Shift
	$newdate = date($dFormat, mktime(0, 0, 0, $mm , $dd - $days_to_shift, $yyyy));
	return $newdate;
}

function evalDataConversion($field_name, $field_type, $field_value, $formula) {
	$retVal = "";
	global $is_data_conversion_error;
	global $is_data_conversion_error_msg;
	global $useStandardCodeDataConversion;
	if($useStandardCodeDataConversion && trim($formula) != "") {
		if(($field_type == 'text' || $field_type == 'calc') && is_numeric($field_value)) {
			$actualFormula = str_replace("[".$field_name."]",$field_value,$formula);
		
			if(preg_match('/[\[]\$]/',$actualFormula) == 0) {
				eval('$result = '.$actualFormula.';');
				if(is_numeric($result)) {
					$retVal = $result;
				}else {
					$is_data_conversion_error = true;
					$is_data_conversion_error_msg .= "<p>The following data conversion formula produced an invalid result";
				}
			}else {
				$is_data_conversion_error = true;
				$is_data_conversion_error_msg .= "<p>The following data conversion formula contains invalid characters and cannot be executed";
			}
			if($is_data_conversion_error) {
				$is_data_conversion_error_msg .= "<br/>field:&nbsp;&nbsp;$field_name";
				$is_data_conversion_error_msg .= "<br/>value:&nbsp;&nbsp;$field_value";
				$is_data_conversion_error_msg .= "<br/>formula:&nbsp;&nbsp;$formula";
				$is_data_conversion_error_msg .= "<br/>operation:&nbsp;&nbsp;$actualFormula";
			}
		}else if($field_type == 'select' || $field_type == 'radio' || $field_type == 'checkbox') {
			$formulaArray = explode("\\n",$formula);
			$found = false;
			foreach($formulaArray as $enum) {
				$equalPosition = strpos($enum,'=');
				if($equalPosition !== false) {
					$checkVal = substr($enum,0,$equalPosition);
					if($checkVal == $field_value) {
						$retVal = substr($enum,$equalPosition+1);
						$found = true;
					}
				}
				if($found) {
					break;
				}
			}
		}
		if($is_data_conversion_error) {
			$retVal = "!error";
		}
	}else {
		$retVal = $field_value;
	}
	
	return $retVal;
}

function evalCheckboxDataConversion($field_value, $formula) {
	$retVal = $field_value;
	$arr = explode("\\n",$formula);
	if($field_value == 1 && strpos($arr[0],'checked=') == 0) {
		 $retVal = substr($arr[0], strpos($arr[0],'=')+1);
	}else if($field_value == 0 && strpos($arr[1],'unchecked=') == 0) {
		 $retVal = substr($arr[1], strpos($arr[1],'=')+1);
	}
	return $retVal;
}


// Store the export file after getting the docs_id from redcap_docs
function storeExportFile($original_filename, $file_content, $docs_id, $docs_size)
{
	global $edoc_storage_option;
	## Create the stored name of the file as it wll be stored in the file system
	$stored_name = date('YmdHis') . "_pid" . PROJECT_ID . "_" . generateRandomHash(6) . getFileExt($original_filename, true);
	$file_extension = getFileExt($original_filename);
	$mime_type = (strtolower($file_extension) == 'csv') ? 'application/csv' : 'application/octet-stream';
	// Add file to file system
	if (!$edoc_storage_option) {
		// Store locally
		$fp = fopen(EDOC_PATH . $stored_name, 'w');
		if ($fp !== false && fwrite($fp, $file_content) !== false) {
			// Close connection
			fclose($fp);
		} else {
			// Could not store, so remove from redcap_docs
			db_query("delete from redcap_docs where docs_id = $docs_id");
			// Send error response
			return false;
		}		
	} else {
		// Store using WebDAV
		require_once (APP_PATH_CLASSES . "WebdavClient.php");
		require (APP_PATH_WEBTOOLS . 'webdav/webdav_connection.php');
		$wdc = new WebdavClient();
		$wdc->set_server($webdav_hostname);
		$wdc->set_port($webdav_port); $wdc->set_ssl($webdav_ssl);
		$wdc->set_user($webdav_username);
		$wdc->set_pass($webdav_password);
		$wdc->set_protocol(1); // use HTTP/1.1
		$wdc->set_debug(false); // enable debugging?
		if (!$wdc->open()) {
			// Could not store, so remove from redcap_docs
			db_query("delete from redcap_docs where docs_id = $docs_id");
			// Send error response
			return false;
		}
		if (substr($webdav_path,-1) != '/') {
			$webdav_path .= '/';
		}
		$http_status = $wdc->put($webdav_path . $stored_name, $file_content);
		$wdc->close();
	}
	## Add file info to edocs_metadata table
	$sql = "insert into redcap_edocs_metadata (stored_name, mime_type, doc_name, doc_size, file_extension, project_id, stored_date) 
			values ('" . prep($stored_name) . "', '$mime_type', '" . prep($original_filename) . "', 
			'" . prep($docs_size) . "', '" . prep($file_extension) . "', " . PROJECT_ID . ", '" . NOW . "')";
	if (!db_query($sql)) {
		// Could not store in table, so remove from redcap_docs
		db_query("delete from redcap_docs where docs_id = $docs_id");
		// Send error response
		return false;
	}
	// Get edoc_id
	$edoc_id = db_insert_id();
	## Add to doc_to_edoc table
	$sql = "insert into redcap_docs_to_edocs (docs_id, doc_id) values ($docs_id, $edoc_id)";
	if (!db_query($sql)) {
		// Could not store in table, so remove from redcap_docs and edocs_metadata
		db_query("delete from redcap_docs where docs_id = $docs_id");
		// Could not store in table, so remove from redcap_docs and edocs_metadata
		db_query("delete from redcap_edocs_metadata where doc_id = $edoc_id");
		// Send error response
		return false;
	}
	// Return successful response
	return true;
}