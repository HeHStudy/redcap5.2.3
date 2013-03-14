<?php

class MetaData
{
	public static function getFields2($projectId, $fields)
	{
		$sql = "SELECT field_name, element_enum, element_type, element_validation_type, element_validation_min, 
					element_validation_max, element_validation_checktype
				FROM redcap_metadata
				WHERE project_id = $projectId AND field_name IN ('". implode("','", $fields) ."')";
		$rsData = db_query($sql);
		
		$metaData = array();
		while ($row = db_fetch_assoc($rsData))
		{	
			if ($row['element_enum'] != "") 
			{
				// Parse MC fields for dropdowns and radios, but retrieve valid enum from "sql" field queries
				if ($row['element_type'] == "sql")
				{
					$row['element_enum'] = getSqlFieldEnum($row['element_enum']);
				}
				$row['enums'] = parseEnum($row['element_enum']);
			}	
			elseif ($row['element_type'] == "yesno")
			{
				$row['element_enum'] = "1, Yes \\n 0, No";
				$row['enums'] = parseEnum($row['element_enum']);
				$row['element_type'] = "radio";
			}
			elseif ($row['element_type'] == "truefalse")
			{
				$row['element_enum'] = "1, True \\n 0, False";
				$row['enums'] = parseEnum($row['element_enum']);
				$row['element_type'] = "radio";
			}
			elseif ($row['element_type'] == "slider")
			{
				$row['element_type'] = "text";
				$row['element_validation_type'] = "int";
				$row['element_validation_min'] = 0;
				$row['element_validation_max'] = 100;
				$row['element_validation_checktype'] = "hard";
			}
			
			$metaData[$row['field_name']] = $row;
		}
		
		return $metaData;
	}
	
	public static function getFields($projectId, $longitudinal, $primaryKey, $isChild, $hasSurveys, $fields = array(), $rawOrLabel='raw', $displayDags=false, $displaySurveyFields=false)
	{
		global $Proj;
	
		$fieldData = array();
		$fieldNames = array();
		$fieldDefaults = array();
		$fieldTypes = array();
		$fieldValidationTypes = array();
		$fieldPhis = array();
		$fieldEnums = array();

		# create list of fields for sql statement
		$fieldList = "'" . implode("','", $fields) . "'";

		# if the primary key field was not passed in, add it
		if (count($fields) > 0)
		{
			$keys = array_flip($fields);
			if ( !array_key_exists($primaryKey, $keys) ) {
				$fieldList = "'".$primaryKey."',".$fieldList;
			}
		}

		#create sql statement for fields
		$fieldSql = (count($fields) > 0) ? "AND field_name IN ($fieldList)" : '';
		
		//Get all Checkbox field choices to use for later looping (so we know how many choices each checkbox question has)
		$checkboxFields = MetaData::getCheckboxFields($projectId, true);
		
		if (!$isChild) // normal
		{
			$sql = "SELECT field_name, element_type, element_enum, form_name, element_validation_type, field_phi 
					FROM redcap_metadata 
					WHERE project_id = $projectId $fieldSql AND element_type != 'descriptive'
					ORDER BY field_order";
		}
		else // parent/child linking exists
		{
			$sql = "SELECT field_name, element_type, form_name, element_validation_type, field_phi, field_order, tbl 
					FROM ((SELECT field_name, element_type, form_name, element_validation_type, field_phi, field_order, 1 as tbl 
						FROM redcap_metadata 
						WHERE project_id = $project_id_parent $fieldSql AND element_type != 'descriptive') 
					UNION (SELECT field_name, element_type, form_name, element_validation_type, field_phi, field_order, 2 as tbl 
						FROM redcap_metadata 
						WHERE project_id = $projectId $fieldSql AND element_type != 'descriptive')) as x 
					ORDER BY tbl, field_order";
		}
		
		$prev_form = "";
		$prev_field = "";
		
		$q = db_query($sql);
		while($row = db_fetch_assoc($q))
		{
			// If starting a new form and form is a survey, then add survey timestamp field here
			if ($displaySurveyFields && $hasSurveys && isset($Proj->forms[$row['form_name']]['survey_id']) 
				&& (($prev_form != $row['form_name'] && $row['field_name'] != $primaryKey) 
				|| ($prev_form == $row['form_name'] && $prev_field == $primaryKey)))
			{
				// Add timestamp field
				$fieldNames[] = $row['form_name'].'_timestamp';
				$fieldDefaults[$row['form_name'].'_timestamp'] = '';
				$fieldTypes[$row['form_name'].'_timestamp'] = 'text';
				$fieldValidationTypes[$row['form_name'].'_timestamp'] = '';		
			}
		
			if ($row['element_type'] != "checkbox")
			{
				$fieldNames[] = $row['field_name'];
				
				# Set Default Values
				if ($row['field_name'] == $row['form_name'] . "_complete") {
					if ($rawOrLabel == 'label') {
						$fieldDefaults[$row['field_name']] = 'Incomplete';
					} elseif ($rawOrLabel == 'both') {
						$fieldDefaults[$row['field_name']] = '0,Incomplete';
					} else {
						$fieldDefaults[$row['field_name']] = '0';
					}
				} else {
					$fieldDefaults[$row['field_name']] = '';
				}
			}
			else
			{
				// Loop through checkbox elements and append string to variable name
				foreach ($checkboxFields[$row['field_name']] as $value => $label)
				{
					// If coded value is not numeric, then format to work correct in variable name (no spaces, caps, etc)
					if (!is_numeric($value))
						$value = preg_replace("/[^a-z0-9]/", "", strtolower($value));
					
					// Append triple underscore + coded value
					$newName = $row['field_name'] . '___' . $value;
					$fieldNames[] = $newName;
					
					# Set Default Values
					$fieldDefaults[$row['field_name']][$value] = ($rawOrLabel == 'raw') ? '0' : '';	# checkbox gets default of 0
				}
			}
			
			# Store enums for fields that have them defined
			if ($row['element_type'] != 'calc' & $row['element_enum'] != "") {
				$fieldEnums[$row['field_name']] = $row['element_enum'];
			}

			# Store Field Types
			$fieldTypes[$row['field_name']] = $row['element_type'];
			
			# Store Validation Type
			if ($row['element_type'] == "text" || $row['element_type'] == "textarea") {
				$fieldValidationTypes[$row['field_name']] = $row['element_validation_type'];
			}
			
			# Store Fields that are Identifiers
			if ($row['field_phi']) $fieldPhis[] = $row['field_name'];
			
			# Add extra columns (if needed) if we're on the first field
			if ($row['field_name'] == $primaryKey)
			{
				# Add event name if project is longitudinal
				if ($longitudinal)
				{
					$fieldNames[] = 'redcap_event_name';
					$fieldDefaults['redcap_event_name'] = '';
					$fieldTypes['redcap_event_name'] = 'text';
					$fieldValidationTypes['redcap_event_name'] = '';
				}
				
				# Add DAG field if specified
				if ($displayDags)
				{
					$fieldNames[] = 'redcap_data_access_group';
					$fieldDefaults['redcap_data_access_group'] = '';
					$fieldTypes['redcap_data_access_group'] = 'text';
					$fieldValidationTypes['redcap_data_access_group'] = '';
				}
				
				# Add timestamp and identifier, if any surveys exist
				if ($hasSurveys && $displaySurveyFields)
				{
					$fieldNames[] = 'redcap_survey_identifier';
					$fieldDefaults['redcap_survey_identifier'] = '';
					$fieldTypes['redcap_survey_identifier'] = 'text';
					$fieldValidationTypes['redcap_survey_identifier'] = '';		
				}
			}
		
			// Set values for next loop
			$prev_form = $row['form_name'];
			$prev_field = $row['field_name'];
		}
		
		$fieldData = array("names" => $fieldNames, "defaults" => $fieldDefaults, "types" => $fieldTypes, 
			"enums" => $fieldEnums, "valTypes" => $fieldValidationTypes, "identifiers" => $fieldPhis);
			
		return $fieldData;
	}
	
	public static function getCheckboxFields($projectId, $addDefaults = false)
	{	
		$sql = "SELECT field_name, element_enum 
				FROM redcap_metadata 
				WHERE project_id = $projectId AND element_type = 'checkbox'";
		$result = db_query($sql);
		
		$checkboxFields = array();
		
		while ($row = db_fetch_assoc($result))
		{
			foreach (parseEnum($row['element_enum']) as $value => $label) {
				$checkboxFields[$row['field_name']][$value] = ($addDefaults ? "0" : html_entity_decode($label, ENT_QUOTES));	
			}
		}
		
		return $checkboxFields;
	}
	
	public static function getFieldNames($projectId)
	{
		$sql = "SELECT field_name, element_type, element_enum 
				FROM redcap_metadata 
				WHERE project_id = $projectId 
				ORDER BY field_order";
		$result = db_query($sql);
		
		$fields = array();
		while($row = db_fetch_assoc($result))
		{
			if ( $row['element_type'] != "checkbox")
			{
				$fields[] = $row['field_name'];
			}
			else
			{
				foreach (parseEnum($row['element_enum']) as $value => $label)
				{
					// If coded value is not numeric, then format to work correct in variable name (no spaces, caps, etc)
					if (!is_numeric($value))
						$value = preg_replace("/[^a-z0-9]/", "", strtolower($value));
					
					// Append triple underscore + coded value
					$fields[] = $row['field_name'] . '___' . $value;
				}
			}
		}
		
		return $fields;
	}
}
