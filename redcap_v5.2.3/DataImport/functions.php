<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

######################################################################		
### Process uploaded Excel file, return references to (1) an array of fieldnames and (2) an array of items to be updated

function excel_to_array($excelfilepath) 
{
	global  $lang, $app_name, $table_pk, $longitudinal, $chkbox_fields, $project_language, $Proj, $user_rights;
	
	// Extract data from CSV file and rearrange it in a temp array
	$newdata_temp = array();
	$found_pk = false;
	$i = 0;
	// Set commas as default delimiter (if can't find comma, it will revert to tab delimited)
	$delimiter 	  = ","; 
	$removeQuotes = false;
	$resetKeys = false; // Set flag to reset array keys if any headers are blank
	
	if (($handle = fopen($excelfilepath, "rb")) !== false) 
	{
		// Loop through each row
		while (($row = fgetcsv($handle, 0, $delimiter)) !== false) 
		{
			if ($i == 0) 
			{
				## CHECK DELIMITER
				// Determine if comma- or tab-delimited (if can't find comma, it will revert to tab delimited)
				$firstLine = implode(",", $row);
				// If we find X number of tab characters, then we can safely assume the file is tab delimited
				$numTabs = 0;
				if (substr_count($firstLine, "\t") > $numTabs) 
				{
					// Set new delimiter
					$delimiter = "\t";
					// Fix the $row array with new delimiter
					$row = explode($delimiter, $firstLine);
					// Check if quotes need to be replaced (added via CSV convention) by checking for quotes in the first line
					// If quotes exist in the first line, then remove surrounding quotes and convert double double quotes with just a double quote
					$removeQuotes = (substr_count($firstLine, '"') > 0);
				}
			}
		
			// Find record identifier field
			if (!$found_pk)
			{
				if ($i == 0 && preg_replace("/[^a-z_0-9]/", "", $row[0]) == $table_pk) {
					$found_pk = true;
				} elseif ($i == 1 && preg_replace("/[^a-z_0-9]/", "", $row[0]) == $table_pk && $_POST['format'] == 'cols') {
					$found_pk = true;
					$newdata_temp = array(); // Wipe out the headers that already got added to array
					$i = 0; // Reset
				}
			}	
			// Loop through each column in this row
			for ($j = 0; $j < count($row); $j++) 
			{
				// If tab delimited, compensate sightly
				if ($delimiter == "\t")
				{
					// Replace characters
					$row[$j] = str_replace("\0", "", $row[$j]);
					// If first column, remove new line character from beginning
					if ($j == 0) {
						$row[$j] = str_replace("\n", "", ($row[$j]));
					}
					// If the string is UTF-8, force convert it to UTF-8 anyway, which will fix some of the characters
					if (function_exists('mb_detect_encoding') && mb_detect_encoding($row[$j]) == "UTF-8")
					{
						$row[$j] = utf8_encode($row[$j]);
					}
					// Check if any double quotes need to be removed due to CSV convention
					if ($removeQuotes)
					{
						// Remove surrounding quotes, if exist
						if (substr($row[$j], 0, 1) == '"' && substr($row[$j], -1) == '"') {
							$row[$j] = substr($row[$j], 1, -1);
						}
						// Remove any double double quotes
						$row[$j] = str_replace("\"\"", "\"", $row[$j]);
					}
				}
				// Reads as records in rows (default)
				if ($_POST['format'] == 'rows')
				{
					// Santize the variable name
					if ($i == 0) {
						$row[$j] = preg_replace("/[^a-z_0-9]/", "", $row[$j]);
						if ($row[$j] == '') {
							$resetKeys = true;
							continue;
						}
					} elseif ($newdata_temp[0][$j] == '') {
						continue;
					}
					$newdata_temp[$i][$j] = $row[$j];
					if ($project_language == 'Japanese') 
					{ // Use only for Japanese SJIS encoding
						$newdata_temp[$i][$j] = mb_convert_encoding($newdata_temp[$i][$j], 'UTF-8',  'sjis');
					}
				}
				// Reads as records in columns
				else
				{
					// Santize the variable name
					if ($j == 0) {
						$row[$j] = preg_replace("/[^a-z_0-9]/", "", $row[$j]);
						if ($row[$j] == '') {
							$resetKeys = true;
							continue;
						}
					} elseif ($newdata_temp[0][$i] == '') {
						continue;
					}
					$newdata_temp[$j][$i] = $row[$j];				
					if ($project_language == 'Japanese') 
					{ // Use only for Japanese SJIS encoding
						$newdata_temp[$j][$i] = mb_convert_encoding($newdata_temp[$j][$i], 'UTF-8',  'sjis');
					}
				}
			}
			$i++;
		}
		fclose($handle);
	}
	
	// Give error message if record identifier variable name could not be found in expected places
	if (!$found_pk)
	{
		if ($_POST['format'] == 'rows') {
			$found_pk_msg = "{$lang['data_import_tool_134']} (\"$table_pk\") {$lang['data_import_tool_135']}";
		} else {
			$found_pk_msg = "{$lang['data_import_tool_134']} (\"$table_pk\") {$lang['data_import_tool_136']}";
		}
		print  "<div class='red' style='margin-bottom:15px;'>
					<b>{$lang['global_01']}:</b><br>
					$found_pk_msg<br><br>
					{$lang['data_import_tool_76']}
				</div>";
		renderPrevPageLink("DataImport/index.php");
		exit;
	}
	
	
	
	# All the worksheet data is now in $newdata_temp
	# Shift the fieldnames  into a separate array called $fieldnames_new

	$fieldnames_new = array_shift($newdata_temp);

	// If any columns were removed, reindex the arrays so that none are missing
	if ($resetKeys) {
		// Reindex the header array
		$fieldnames_new = array_values($fieldnames_new);
		// Loop through ALL records and reindex each
		foreach ($newdata_temp as $key=>&$vals) {
			$vals = array_values($vals);
		}
	}
	
	// If longitudinal, get array key of redcap_event_name field
	if ($longitudinal) {
		$eventNameKey = array_search('redcap_event_name', $fieldnames_new);
	}
	
	// Check if DAGs exist
	$groups = $Proj->getGroups();
	
	// If has DAGs, try to find DAG field	
	if (!empty($groups)) {
		$groupNameKey = array_search('redcap_data_access_group', $fieldnames_new);
	}
	
	## PUT ALL UPLOADED DATA INTO $updateitems
	$updateitems = array();
	$id_duplicates = '';
	$temp_id = array();
	$invalidEventNames = array();
	foreach ($newdata_temp as $i => $element) 
	{
		// Trim the record name, just in case
		$newdata_temp[$i][0] = $element[0] = trim($element[0]);
		// CHECK DUPLICATES: Check for any duplicate records trying to be imported, which will cause problems
		if (!$longitudinal && $element[0] != '') {
			// Catch records being imported with record name in different cases ("aaa" vs. "AAA") and treat as same record.
			// Allow numbers with leading zeroes to be differentiated from the same number w/o leading zero.
			if (in_array(strtolower($element[0]), array_map('strtolower', $temp_id), true)) {
				$id_duplicates .= "<br>".$element[0]; 
			} else {
				$temp_id[] = $element[0];
			}
		}
		// Get event_id to add as subkey for record
		$event_id = ($longitudinal) ? $Proj->getEventIdUsingUniqueEventName($element[$eventNameKey]) : $Proj->firstEventId;
		// If user submitted an invalid unique event name, add to array to display the error below
		if ($longitudinal && !is_numeric($event_id)) {
			$invalidEventNames[$element[0]][] = $element[$eventNameKey];
		}
		// Loop through data array and add each record values to $updateitems
		for ($j = 0; $j < count($fieldnames_new); $j++) {
			// Skip if blank
			if (trim($fieldnames_new[$j]) == "" || trim($element[$j] == "")){
				continue;
			}
			// Add record value to data array
			$updateitems[$element[0]][$event_id][$fieldnames_new[$j]]['newvalue'] = $element[$j];
		}
	}
	
	// If user submitted any invalid unique event names, display errors
	if (!empty($invalidEventNames))
	{
		print  "<div class='red'>
					<b>{$lang['global_01']}:</b><br>
					{$lang['data_import_tool_181']}<br>";
		foreach ($invalidEventNames as $rec=>$uniqevts) {
			foreach ($uniqevts as $evt) {
				print "<br>$rec (<span style='color:red;'>$evt</span>)";
			}	
		}			
		print  "</div>";
		exit;
	}
	
	// Display DUPLICATES if duplicate records are trying to be imported (Classic only)
	if (!$longitudinal && $id_duplicates != '') 
	{
		print  "<div class='red'>
					<b>{$lang['global_01']}:</b><br>
					{$lang['data_import_tool_77']}<br><br>
					<b>{$lang['data_import_tool_78']}</b>
					$id_duplicates
				</div>";
		exit;
	}
	
	// Longitudinal check for 'redcap_event_name' column
	if ($longitudinal && !in_array('redcap_event_name', $fieldnames_new))
	{
		print  "<div class='red' style='margin-bottom:15px;'>
					<b>{$lang['global_01']}{$lang['colon']} \"redcap_event_name\" {$lang['data_import_tool_168']}</b><br>
					{$lang['data_import_tool_169']}<br><br>
					<b>{$lang['data_import_tool_170']}</b><br>
					".implode("<br>", $Proj->getUniqueEventNames())."
				</div>";
		renderPrevPageLink("DataImport/index.php");
		exit;
	}
	
	// If project has DAGs and redcap_data_access_group column is included and user is IN a DAG, then tell them they must remove the column
	if ($user_rights['group_id'] != '' && !empty($groups) && in_array('redcap_data_access_group', $fieldnames_new))
	{
		print  "<div class='red' style='margin-bottom:15px;'>
					<b>{$lang['global_01']}{$lang['colon']} {$lang['data_import_tool_171']}</b><br>
					{$lang['data_import_tool_172']}
				</div>";
		renderPrevPageLink("DataImport/index.php");
		exit;
	}	
	// DAG check to make sure that a single record doesn't have multiple values for 'redcap_data_access_group'
	elseif ($user_rights['group_id'] == '' && !empty($groups) && $groupNameKey !== false)
	{
		// Creat array to collect all DAG designations for each record (each should only have one DAG listed)
		$dagPerRecord = array();
		foreach ($newdata_temp as $thisrow) {
			// Get record name
			$record = $thisrow[0];
			// Get DAG name for this row/record
			$dag = $thisrow[$groupNameKey];
			// Add to array
			$dagPerRecord[$record][$dag] = true;
		}
		unset($thisrow);
		// Now loop through all records and remove all BUT those with duplicates
		foreach ($dagPerRecord as $record=>$dags) {
			if (count($dags) <= 1) {
				unset($dagPerRecord[$record]);
			}
		}
		// If there records with multiple DAG designations, then stop here and throw error.
		if (!empty($dagPerRecord)) 
		{
			print  "<div class='red' style='margin-bottom:15px;'>
						<b>{$lang['global_01']}{$lang['colon']} {$lang['data_import_tool_173']}</b><br>
						{$lang['data_import_tool_174']} <b>".implode("</b>, <b>", array_keys($dagPerRecord))."</b>{$lang['period']}
					</div>";
			renderPrevPageLink("DataImport/index.php");
			exit;
		}
	}
	
	// For case sensitivity issues, check actual record name's case against its value in the back-end. Use MD5 to differentiate.
	// Modify $updateitems accordingly, if different.
	$updateitems_md5 = array();
	foreach ($updateitems as $key=>$attr) {
		$updateitems_md5[$key] = md5($key);
	}
	// Query using MD5 to find values that are different from uploaded values only on the case-level
	$sql = "select record from redcap_data where project_id = " . PROJECT_ID . " and field_name = '$table_pk' 
			and md5(record) not in ('" . implode("', '", $updateitems_md5) . "')
			and record in ('" . implode("', '", array_keys($updateitems_md5)) . "')";
	$q = db_query($sql);
	unset($updateitems_md5);
	while ($row = db_fetch_assoc($q))
	{
		// Using array_key_exists won't work, so loop through all imported record names for a match.
		foreach ($updateitems as $this_record=>$this_event_data) {
			// Do case insensitive comparison
			if (strcasecmp($this_record, $row['record']) == 0) {
				// Record name exists in two different cases, so modify $updateitems to align with back-end value.				
				// Replace sub-array with sub-array containing other case value.
				$updateitems[$row['record']] = $updateitems[$this_record];
				// Loop through all events to modify the PK value in each event
				foreach (array_keys($this_event_data) as $event_id) {
					$updateitems[$row['record']][$event_id][$table_pk]['newvalue'] = $row['record'];
				}
				// Remove old values that were just replaced
				unset($updateitems[$this_record]);
			}
		}
	}
	
	// Create new array with the translated checkbox field names
	$chkbox_fields_new = array();
	foreach ($chkbox_fields as $this_field=>$this_array) {
		foreach ($this_array as $this_code=>$this_label) {
			$chkbox_fields_new[$this_field . "___" . strtolower($this_code)] = $this_label;
		}
	}
	// Set $chkbox_fields_new as global variable to use later
	$GLOBALS['chkbox_fields_new'] = $chkbox_fields_new;
	
	## Fetch from project: old record to match new record
	
	// SET DEFAULTS: Set default of oldvalue for each field as blank (or 0 for checkboxes and Form Status fields)
	$illegalCharsInRecordName = array();
	foreach ($updateitems as $studyid=>$this_event_data) 
	{
		// Make sure record names do NOT contain a +, &, #, or apostrophe
		if (strpos($studyid, '+') !== false || strpos($studyid, "'") !== false || strpos($studyid, '&') !== false || strpos($studyid, '#') !== false) {
			$illegalCharsInRecordName[] = $studyid;
		}
		// Add default values for all existing values
		foreach (array_keys($this_event_data) as $event_id) {
			foreach ($fieldnames_new as $fieldname){
				if (isset($chkbox_fields_new[$fieldname])) {
					// Checkbox fields get default of 0
					$updateitems[$studyid][$event_id][$fieldname]['oldvalue'] = "0";
				} else {
					// Regular fields get default of ""
					$updateitems[$studyid][$event_id][$fieldname]['oldvalue'] = "";
				}
			}
		}
	}
	
	// If any record names contain a +, &, #, or apostrophe, then stop here with an error
	if (!empty($illegalCharsInRecordName))
	{
		print  "<div class='red'>
					<b>{$lang['global_01']}:</b><br>
					{$lang['data_import_tool_157']}<br><br>
					<b>{$lang['data_import_tool_158']}</b><br>&nbsp; &bull; 
					".implode("<br>&nbsp; &bull; ", $illegalCharsInRecordName)."
				</div>";
		exit;
	}
	
	## ADD EXISTING DATA VALUES TO $updateitems FROM DATA TABLE
	// Load any existing values for the records being imported (non-Longitudinal)
	if (!$longitudinal) 
	{
		// Query to build array of existing values
		$sql = "select * from redcap_data where project_id = " . PROJECT_ID . " 
				and field_name in ('" . implode("','", array_merge($fieldnames_new, array_keys($chkbox_fields))) . "') 
				and record in ('" . implode("','", array_keys($updateitems)) . "') and event_id = " . $Proj->firstEventId . "
				and record != '' and value != ''";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q)) 
		{
			if ($Proj->isCheckbox($row['field_name'])) {
				// Make sure this value still exists as an option (may have been deleted)
				$chkbox_field_fullname = $row['field_name']."___".strtolower($row['value']);
				if (!isset($chkbox_fields_new[$chkbox_field_fullname])) continue;
				// Checkbox fields (if exists at all in data table, then is "1")
				$updateitems[$row['record']][$row['event_id']][$chkbox_field_fullname]['oldvalue'] = "1";				
			} else {
				// Regular non-checkbox fields
				$updateitems[$row['record']][$row['event_id']][$row['field_name']]['oldvalue'] = $row['value'];
				// If the old value is blank (rather than non-existent in the table), then set a flag
				if ($row['value'] == "") {
					$updateitems[$row['record']][$row['event_id']][$row['field_name']]['old_blank'] = true;
				}
			}
		}
	}	
	// If Longitudinal and Event ids were submitted, load existing values and parse to match event_ids correctly
	else
	{
		// Get event_ids for each unique event name and add to array
		$event_array = array();
		foreach ($updateitems as $record=>$recattr) {
			$event_array = array_merge($event_array, array_keys($recattr));
		}
		// Query and parse to build array of existing values
		$sql = "select * from redcap_data where project_id = " . PROJECT_ID . " 
				and field_name in ('" . implode("','", array_merge($fieldnames_new, array_keys($chkbox_fields))) . "') 
				and record in ('" . implode("','", array_keys($updateitems)) . "') 
				and event_id in (" . implode(",", array_unique($event_array)) . ")
				and record != '' and value != ''";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q)) {
			// Only add to oldvalue data array if this is the correct event_id for this record
			if (isset($updateitems[$row['record']][$row['event_id']])) 
			{
				if ($Proj->isCheckbox($row['field_name'])) {
					// Make sure this value still exists as an option (may have been deleted)
					$chkbox_field_fullname = $row['field_name']."___".strtolower($row['value']);
					if (!isset($chkbox_fields_new[$chkbox_field_fullname])) continue;
					// Checkbox fields (if exists at all in data table, then is "1")
					$updateitems[$row['record']][$row['event_id']][$row['field_name']."___".strtolower($row['value'])]['oldvalue'] = "1";				
				} else {
					// Regular non-checkbox fields
					$updateitems[$row['record']][$row['event_id']][$row['field_name']]['oldvalue'] = $row['value'];
					// If the old value is blank (rather than non-existent in the table), then set a flag
					if ($row['value'] == "") {
						$updateitems[$row['record']][$row['event_id']][$row['field_name']]['old_blank'] = true;
					}
				}
				// Add unique event name to $updateitems
				if (isset($updateitems[$row['record']][$row['event_id']]['redcap_event_name']) && $updateitems[$row['record']][$row['event_id']]['redcap_event_name']['oldvalue'] == '') {
					$updateitems[$row['record']][$row['event_id']]['redcap_event_name']['oldvalue'] = $Proj->getUniqueEventNames($row['event_id']);
				}
			}
		}
	}
	
	// GET EXISTING DAG DESIGNATIONS
	if ($user_rights['group_id'] == '' && !empty($groups))
	{
		$sql = "select * from redcap_data where project_id = " . PROJECT_ID . " 
				and field_name = '__GROUPID__' and record in ('" . implode("','", array_keys($updateitems)) . "')";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q)) 
		{
			if (isset($updateitems[$row['record']][$row['event_id']]['redcap_data_access_group'])) {
				// Make sure group_id is valid
				$group_id = $row['value'];
				if (!isset($groups[$group_id])) continue;
				// Add unique DAG name to $updateitems
				$updateitems[$row['record']][$row['event_id']]['redcap_data_access_group']['oldvalue'] = $Proj->getUniqueGroupNames($group_id);
			}
		}
	}
	
	// Unset variables to save server memory
	unset($newdata_temp, $exceldata);
	
	return array($fieldnames_new, $updateitems);
	
}


######################################################################		
### Get metadata for each field name (as recordset) and put in associative array (fewer searches vs. recordset?)

function get_import_metadata($fieldnames_new) 
{
	global $Proj;
	
	// Put field info into array
	$ar_metadata = array();
	
	// Remove triple underscore formatting from checkbox fields
	$fieldnames_new2 = $fieldnames_new;
	foreach ($fieldnames_new2 as $key=>$field)
	{
		$pos_triple = strpos($field, '___');
		if ($pos_triple !== false)
		{
			// Make sure it's a real field
			$field_orig = substr($field, 0, $pos_triple);
			if (isset($Proj->metadata[$field_orig])) {
				// Replace it with the original field name
				$fieldnames_new2[$key] = $field_orig;
			}
		}
	}
	array_unique($fieldnames_new2);

	# Build SQL query to get metadata for given fieldnames
	$rs_metadata_sql = "SELECT field_name, element_enum, element_validation_type, element_validation_min, element_validation_max, 
						element_validation_checktype, element_type FROM redcap_metadata WHERE project_id = ".PROJECT_ID." 
						AND field_name IN ('" . implode("', '", $fieldnames_new2) ."')";
	unset($fieldnames_new2, $fieldnames_new);
	$q = db_query($rs_metadata_sql);
	while ($row = db_fetch_assoc($q)) 
	{
		$metadatafn = $row['field_name'];
		$ar_metadata[$metadatafn] = $row;
		
		if ($ar_metadata[$metadatafn]['element_enum'] != "") 
		{
			// Parse MC fields for dropdowns and radios, but retrieve valid enum from "sql" field queries
			if ($row['element_type'] == "sql")
			{
				$row['element_enum'] = getSqlFieldEnum($row['element_enum']);
			}
			$ar_metadata[$metadatafn]['enums'] = parseEnum($row['element_enum']);
		}	
		elseif ($row['element_type'] == "yesno")
		{
			$ar_metadata[$metadatafn]['element_enum'] = YN_ENUM;
			$ar_metadata[$metadatafn]['enums'] = parseEnum(YN_ENUM);
			$ar_metadata[$metadatafn]['element_type'] = "radio";
		}
		elseif ($row['element_type'] == "truefalse")
		{
			$ar_metadata[$metadatafn]['element_enum'] = TF_ENUM;
			$ar_metadata[$metadatafn]['enums'] = parseEnum(TF_ENUM);
			$ar_metadata[$metadatafn]['element_type'] = "radio";
		}
		elseif ($row['element_type'] == "slider")
		{
			$ar_metadata[$metadatafn]['element_type'] = "text";
			$ar_metadata[$metadatafn]['element_validation_type'] = "int";
			$ar_metadata[$metadatafn]['element_validation_min'] = 0;
			$ar_metadata[$metadatafn]['element_validation_max'] = 100;
			$ar_metadata[$metadatafn]['element_validation_checktype'] = "hard";
		}
	}
	
	return $ar_metadata;	
}



######################################################################		
### Compare existing (old) data and import (new) data 

function compare_new_and_old(&$updateitems,&$ar_metadata) 
{
	global $Proj;
	
	// First get list of all 'file' field types to make sure they are not updated but ignored 
	// (don't want to overwrite a document with a text value)
	foreach ($ar_metadata as $field=>$attr)
	{
		if ($attr['element_type'] == 'file') {
			$file_fields[$field] = true;
		}
	}
	
	// Loop through all uploaded elements
	foreach ($updateitems as $studyid => $recordevent) 
	{
		foreach ($recordevent as $event_id=>$studyrecord) 
		{
			foreach ($studyrecord as $fieldname=>$datapoint) 
			{
				if (isset($datapoint['newvalue'])) 
				{
					## PERFORM SOME PRE-CHECKS FIRST FOR FORMATTING ISSUES OF CERTAIN VALIDATION TYPES		
					// Ensure all dates are in correct format (yyyy-mm-dd hh:mm and yyyy-mm-dd hh:mm:ss)
					if (substr($ar_metadata[$fieldname]['element_validation_type'], 0, 8) == 'datetime')
					{
						// Break up into date and time
						list ($thisdate, $thistime) = explode(' ', $datapoint['newvalue'], 2);
						if (strpos($updateitems[$studyid][$event_id][$fieldname]['newvalue'],"/") !== false) {
							if (substr($ar_metadata[$fieldname]['element_validation_type'], 0, 16) == 'datetime_seconds') {
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
							$updateitems[$studyid][$event_id][$fieldname]['newvalue'] = $datapoint['newvalue'] = sprintf("%04d-%02d-%02d", $year, $month, $day) . ' ' . $thistime;
						} else {
							// Make sure has correct amount of digits with proper leading zeros
							$updateitems[$studyid][$event_id][$fieldname]['newvalue'] = $datapoint['newvalue'] = clean_date_ymd($thisdate) . " " . $thistime;
						}
					} 		
					// First ensure all dates are in correct format (yyyy-mm-dd)
					elseif (substr($ar_metadata[$fieldname]['element_validation_type'], 0, 4) == 'date')
					{
						if (strpos($updateitems[$studyid][$event_id][$fieldname]['newvalue'],"/") !== false) {
							// Assume American format (mm/dd/yyyy) if contains forward slash
							list ($month, $day, $year) = explode('/', $datapoint['newvalue']);
							// Make sure year is 4 digits
							if (strlen($year) == 2) {
								$year = ($year < (date('y')+10)) ? "20".$year : "19".$year;
							}
							$updateitems[$studyid][$event_id][$fieldname]['newvalue'] = $datapoint['newvalue'] = sprintf("%04d-%02d-%02d", $year, $month, $day);
						} else {
							// Make sure has correct amount of digits with proper leading zeros
							$updateitems[$studyid][$event_id][$fieldname]['newvalue'] = $datapoint['newvalue'] = clean_date_ymd($datapoint['newvalue']);
						}
					} 		
					// Ensure all times are in correct format (hh:mm)
					elseif ($ar_metadata[$fieldname]['element_validation_type'] == 'time' && strpos($updateitems[$studyid][$event_id][$fieldname]['newvalue'],":") !== false) 
					{
						if (strlen($datapoint['newvalue']) < 5) {
							$updateitems[$studyid][$event_id][$fieldname]['newvalue'] = $datapoint['newvalue'] = "0".$datapoint['newvalue'];
						}
					} 
					// Vanderbilt MRN: Remove any non-numerical characters. Add leading zeros, if needed.
					elseif ($ar_metadata[$fieldname]['element_validation_type'] == 'vmrn') 
					{					
						$updateitems[$studyid][$event_id][$fieldname]['newvalue'] = $datapoint['newvalue'] = sprintf("%09d", preg_replace("/[^0-9]/", "", $datapoint['newvalue']));
					}	
					// Phone: Remove any unneeded characters
					elseif ($ar_metadata[$fieldname]['element_validation_type'] == 'phone') 
					{
						$tempVal = str_replace(array(".","(",")"," "), array("","","",""), $datapoint['newvalue']);
						if (strlen($tempVal) >= 10 && is_numeric(substr($tempVal, 0, 10))) {
							// Now add our own formatting
							$updateitems[$studyid][$event_id][$fieldname]['newvalue'] = $datapoint['newvalue'] = trim("(" . substr($tempVal, 0, 3) . ") " . substr($tempVal, 3, 3) . "-" . substr($tempVal, 6, 4) . " " . substr($tempVal, 10));
						}
					}	
					
					## CHECK IF NEW OR EXISTING VALUE
					//if the old value is blank but the new value isn't blank, then this is a new value being imported
					if ($datapoint['oldvalue'] == "" && $datapoint['newvalue'] != "") 
					{
						# if the old value is blank but the new value isn't blank, then this is a new value being imported
						if (isset($datapoint['old_blank'])) {
							$updateitems[$studyid][$event_id][$fieldname]['status'] = 'update';
						} else {
							$updateitems[$studyid][$event_id][$fieldname]['status'] = 'new';
						}
					} 					
					//if the new value equals the old value, then nothing is changing
					elseif($datapoint['newvalue'] == $datapoint['oldvalue']) 
					{
						$updateitems[$studyid][$event_id][$fieldname]['status'] = 'old';
					} 				
					//if the old value isn't blank, but the new value doesn't match, then this field will be overwritten/updated
					else 
					{
						$updateitems[$studyid][$event_id][$fieldname]['status'] = 'update';
					}
				}
				//do nothing -- there are no values for this field in the import CSV file
				else 
				{
					$updateitems[$studyid][$event_id][$fieldname]['status'] = 'old';
				}
				
				// FILE UPLOAD FIELDS: Make sure we're not overwriting an uploaded edoc 'file' field
				if ($file_fields[$fieldname])
				{
					// Reset to 'old' status so it doesn't overwrite
					$updateitems[$studyid][$event_id][$fieldname]['status'] = 'old';
					// Set display value to [document] to prevent confusion by user IF doc exists, who would otherwise see a number value (edoc_id).
					if (is_numeric($datapoint['oldvalue'])) {
						$updateitems[$studyid][$event_id][$fieldname]['oldvalue'] = $datapoint['newvalue'] = $datapoint['oldvalue'] = "[document]";
					} else {
						$updateitems[$studyid][$event_id][$fieldname]['oldvalue'] = $datapoint['newvalue'] = $datapoint['oldvalue'] = "";
					}
				}
			}
		}
	}
		
	unset($studyrecord,$recordevent);
	
	return $updateitems;
}

######################################################################		
### Perform metadata checks on new and updated data fields

function check_import_data(&$updateitems, &$ar_metadata) 
{
	global $randomization, $longitudinal, $table_pk, $lang, $table_pk_label, $Proj, 
		   $user_rights, $app_name, $chkbox_fields, $chkbox_fields_new, $secondary_pk, 
		   $reserved_field_names, $reserved_field_names2;
	
	// RANDOMIZATION: Is randomization enabled and setup?
	$randomizationIsSetUp = ($randomization && Randomization::setupStatus());
	if ($randomizationIsSetUp)
	{
		$randomizationCriteriaFields = Randomization::getRandomizationFields(true);
		$randTargetField = array_shift($randomizationCriteriaFields);
		$randTargetEvent = ($longitudinal) ? array_shift($randomizationCriteriaFields) : $Proj->firstEventId;
		$randCritFieldsEvents = array();
		while (!empty($randomizationCriteriaFields)) {
			$field = array_shift($randomizationCriteriaFields);
			if ($longitudinal) {
				$event_id = array_shift($randomizationCriteriaFields);
				$randCritFieldsEvents[$field] = $event_id;
			} else {
				$randCritFieldsEvents[$field] = $Proj->firstEventId;
			}
		}
	}
	
	//If user is in a Data Access Group, get a listing of all record ids and put in array to check against
	if ($user_rights['group_id'] != "") {
		//Set all records with default of 0
		$q = db_query("select distinct record from redcap_data where project_id = ".PROJECT_ID." and field_name = '$table_pk'");
		while ($row = db_fetch_assoc($q)) {
			$record_dag[$row['record']] = 0;
		}
		//Now set records in user's group as 1
		$q = db_query("select record from redcap_data where project_id = ".PROJECT_ID." and field_name = '__GROUPID__' and value = '".$user_rights['group_id']."'");
		while ($row = db_fetch_assoc($q)) {
			$record_dag[$row['record']] = 1;
		}
	}
		
	## LOCKING CHECK: Get all forms that are locked for the uploaded records
	$locked = array();
	// Place all records/events into $event_record_array
	if ($longitudinal) 
	{	
		// Get event_ids for each unique event name and add to array
		$event_array = array();
		foreach ($updateitems as $record=>$recattr) {
			$event_array = array_merge($event_array, array_keys($recattr));
		}
		// Get list of ALL record/forms locked for record/event/field
		$sql = "select l.record, l.event_id, m.field_name, m.element_type, m.element_enum 
				from redcap_locking_data l, redcap_metadata m 
				where m.project_id = ".PROJECT_ID." and l.record in ('".implode("', '", array_keys($updateitems))."') 
				and l.event_id in ('".implode("', '", array_unique($event_array))."') 
				and m.field_name in ('".implode("', '", array_keys($ar_metadata))."')
				and l.project_id = m.project_id and m.form_name = l.form_name";
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
	}
	else
	{
		// CLASSIC: Get list of ALL record/forms locked for record/event/field
		$sql = "select l.record, l.event_id, m.field_name, m.element_type, m.element_enum from redcap_locking_data l, redcap_metadata m 
				where m.project_id = ".PROJECT_ID." and l.record in ('".implode("', '", array_keys($updateitems))."') 
				and l.event_id = ".getSingleEvent(PROJECT_ID)." and m.field_name in ('".implode("', '", array_keys($ar_metadata))."')
				and l.project_id = m.project_id and m.form_name = l.form_name";
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
	}
	
	// Obtain an array of all Validation Types (from db table)
	$valTypes = getValTypes();
	
	// Force all dates to be validated in YYYY-MM-DD format (any that were imported as M/D/Y will have been reformatted to YYYY-MM-DD)
	foreach ($ar_metadata as $fieldname=>$fieldattr)
	{
		if ($fieldattr['element_validation_type'] == "int") {
			$ar_metadata[$fieldname]['element_validation_type'] = "integer";
		} elseif ($fieldattr['element_validation_type'] == "float") {
			$ar_metadata[$fieldname]['element_validation_type'] = "number";
		} elseif (substr($fieldattr['element_validation_type'], 0, 16) == "datetime_seconds") {
			$ar_metadata[$fieldname]['element_validation_type'] = "datetime_seconds_ymd";
		} elseif (substr($fieldattr['element_validation_type'], 0, 8) == "datetime") {
			$ar_metadata[$fieldname]['element_validation_type'] = "datetime_ymd";
		} elseif (substr($fieldattr['element_validation_type'], 0, 4) == "date") {
			$ar_metadata[$fieldname]['element_validation_type'] = "date_ymd";
		}
	}
	
	// Create array of records that are survey responses (either partial or completed)
	$responses = array();
	if (!empty($updateitems) && !empty($Proj->surveys)) {
		$sql = "select r.record, p.event_id, p.survey_id from redcap_surveys_participants p, redcap_surveys_response r 
				where p.survey_id in (".prep_implode(array_keys($Proj->surveys)).") and p.participant_id = r.participant_id 
				and r.record in (".prep_implode(array_keys($updateitems)).") and r.first_submit_time is not null";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q)) {
			// Add record-event_id-survey_id to array
			$responses[$row['record']][$row['event_id']][$row['survey_id']] = true;
		}
	}
	
	// If using SECONDARY UNIQUE FIELD, then check for any duplicate values in imported data
	$checkSecondaryPk = ($secondary_pk != '' && isset($ar_metadata[$secondary_pk]));
	
	// Count the errors
	$total_errors = 0;
	
	// Loop through each record in uploaded data file
	foreach ($updateitems as $studyid => $studyevent) 
	{
		// Loop through each event in this record
		foreach ($studyevent as $event_id => $studyrecord) 
		{		
			// Loop through each data point in each record
			foreach ($studyrecord as $fieldname => $datapoint)
			{
				// if the field contains new or updated data, then check it against the metadata
				if( $updateitems[$studyid][$event_id][$fieldname]['status'] == 'new' || $updateitems[$studyid][$event_id][$fieldname]['status'] == 'update')
				{
					$newvalue = $updateitems[$studyid][$event_id][$fieldname]['newvalue'];
					$oldvalue = $updateitems[$studyid][$event_id][$fieldname]['oldvalue'];
					
					// Display error if the Study ID is left blank or is only spaces
					if (trim($studyid) == "") {
						$updateitems[$studyid][$event_id][$fieldname]['check'] = 'error'; 
						$updateitems[$studyid][$event_id][$fieldname]['message'] = $lang['data_import_tool_82'];
						$total_errors++;
					}
				
					// PREVENT SURVEY COMPLETE STATUS MODIFICATION
					// If this is a form status field for a survey response, then prevent from modifying it
					$fieldForm = $Proj->metadata[$fieldname]['form_name'];
					if ($fieldname == $fieldForm."_complete" && isset($Proj->forms[$fieldForm]['survey_id'])
						&& isset($responses[$studyid][$event_id][$Proj->forms[$fieldForm]['survey_id']])) 
					{
						$updateitems[$studyid][$event_id][$fieldname]['check'] = 'error'; 
						$updateitems[$studyid][$event_id][$fieldname]['message'] = $lang['survey_403'];
						$total_errors++;
					}
					
					// LOCKING CHECK: Ensure that this field's form is not locked. If so, then give error and force user to unlock form before proceeding.
					if (isset($locked[$studyid][$event_id][$fieldname]))
					{
						$updateitems[$studyid][$event_id][$fieldname]['check'] = 'error'; 
						$updateitems[$studyid][$event_id][$fieldname]['message'] = $lang['data_import_tool_149'];
						$total_errors++;
					}				
					
					// Validation checking (for int, float, phone, email, date) meets REDCap standards, and that all types are represented.
					if (isset($ar_metadata[$fieldname]['element_validation_type']))
					{
						## Use RegEx to evaluate the value based upon validation type
						// Set regex pattern to use for this field
						$regex_pattern = $valTypes[$ar_metadata[$fieldname]['element_validation_type']]['regex_php'];
						// Run the value through the regex pattern
						preg_match($regex_pattern, $newvalue, $regex_matches);
						// Was it validated? (If so, will have a value in 0 key in array returned.)
						$failed_regex = (!isset($regex_matches[0]));
						// Set error message if failed regex
						if ($failed_regex)
						{
							$updateitems[$studyid][$event_id][$fieldname]['check'] = 'error'; 
							$total_errors++;
							// Validate the value based upon validation type
							switch ($ar_metadata[$fieldname]['element_validation_type'])
							{
								case "int":
									$updateitems[$studyid][$event_id][$fieldname]['message'] = "{$lang['data_import_tool_83']} <i>$fieldname</i> {$lang['data_import_tool_84']}";
									break;
								case "float":
									$updateitems[$studyid][$event_id][$fieldname]['message'] = "{$lang['data_import_tool_83']} <i>$fieldname </i> {$lang['data_import_tool_85']}";
									break;
								case "phone":
									$updateitems[$studyid][$event_id][$fieldname]['message'] = "$fieldname {$lang['data_import_tool_86']}";
									break;
								case "email":
									$updateitems[$studyid][$event_id][$fieldname]['message'] = $lang['data_import_tool_87'];
									break;
								case "vmrn":
									$updateitems[$studyid][$event_id][$fieldname]['message'] = $lang['data_import_tool_138'];
									break;
								case "zipcode":
									$updateitems[$studyid][$event_id][$fieldname]['message'] = "$fieldname {$lang['data_import_tool_153']}";
									break;
								case "date":
								case "date_ymd":
								case "date_mdy":
								case "date_dmy":
									$updateitems[$studyid][$event_id][$fieldname]['message'] = $lang['data_import_tool_90'];
									break;
								case "time":
									$updateitems[$studyid][$event_id][$fieldname]['message'] = $lang['data_import_tool_137'];
									break;
								case "datetime":
								case "datetime_ymd":
								case "datetime_mdy":
								case "datetime_dmy":
									$updateitems[$studyid][$event_id][$fieldname]['message'] = $lang['data_import_tool_150'];
									break;
								case "datetime_seconds":
								case "datetime_seconds_ymd":
								case "datetime_seconds_mdy":
								case "datetime_seconds_dmy":
									$updateitems[$studyid][$event_id][$fieldname]['message'] = $lang['data_import_tool_150'];	
									break;
								default:
									// General regex failure message for any new, non-legacy validation types (e.g. postalcode_canada)
									$updateitems[$studyid][$event_id][$fieldname]['message'] = $lang['config_functions_77'];	
							}
						}
					}
					
					# If value is an enum, check that it's valid
					if ($ar_metadata[$fieldname]['element_type'] != 'slider' && isset($ar_metadata[$fieldname]['element_enum']) && $ar_metadata[$fieldname]['element_enum'] != "")
					{
						// Make sure the raw value is a coded value in the enum
						if (!isset($ar_metadata[$fieldname]["enums"][$newvalue]) && $ar_metadata[$fieldname]['element_type'] != "calc") 
						{
							$updateitems[$studyid][$event_id][$fieldname]['check'] = 'error'; $total_errors++;
							$updateitems[$studyid][$event_id][$fieldname]['message'] = "{$lang['data_import_tool_91']} <i>$fieldname</i>.";
						}
						// Calc fields cannot be uploaded
						elseif (!isset($ar_metadata[$fieldname]["enums"][$newvalue]) && $ar_metadata[$fieldname]['element_type'] == "calc") 
						{
							$updateitems[$studyid][$event_id][$fieldname]['check'] = 'error'; $total_errors++;
							$updateitems[$studyid][$event_id][$fieldname]['message'] = "(calc) " . $lang['data_import_tool_148'];					
						}
					}
					# Check that value is within range specified in metadata (max/min), if a range is given.
					if((isset($ar_metadata[$fieldname]['element_validation_min']) || isset($ar_metadata[$fieldname]['element_validation_max']))) {
						
						// DATETIME and DATETIME_SECONDS: Ensure all (min, max, and uploaded value) are in same format for comparison (YYYY-MM-DD HH:SS)
						if (substr($ar_metadata[$fieldname]['element_validation_type'], 0, 8) == 'datetime') 
						{
							// If date from project min range is in format MM/DD/YYYY
							if (strpos($ar_metadata[$fieldname]['element_validation_min'], "/") !== false) 
							{
								// Break up into date and time
								list ($thisdate, $thistime) = explode(' ', $ar_metadata[$fieldname]['element_validation_min'], 2);
								if (strlen($thistime) == 4 || strlen($thistime) == 7) $thistime = "0".$thistime;
								list ($month, $day, $year) = explode('/', $thisdate);
								$ar_metadata[$fieldname]['element_validation_min'] = sprintf("%04d-%02d-%02d", $year, $month, $day) . ' ' . $thistime;
							}
							// If date from project max range is in format MM/DD/YYYY
							if (strpos($ar_metadata[$fieldname]['element_validation_max'], "/") !== false) 
							{
								// Break up into date and time
								list ($thisdate, $thistime) = explode(' ', $ar_metadata[$fieldname]['element_validation_max'], 2);
								if (strlen($thistime) == 4 || strlen($thistime) == 7) $thistime = "0".$thistime;
								list ($month, $day, $year) = explode('/', $thisdate);
								$ar_metadata[$fieldname]['element_validation_max'] = sprintf("%04d-%02d-%02d", $year, $month, $day) . ' ' . $thistime;
							}
						}
						// DATE: Ensure all (min, max, and uploaded value) are in same format for comparison (YYYY-MM-DD)
						elseif (substr($ar_metadata[$fieldname]['element_validation_type'], 0, 4) == 'date') 
						{
							// If date from project min range is in format MM/DD/YYYY
							if (strpos($ar_metadata[$fieldname]['element_validation_min'], "/") !== false) 
							{
								list ($month, $day, $year) = explode('/', $ar_metadata[$fieldname]['element_validation_min']);
								$ar_metadata[$fieldname]['element_validation_min'] = sprintf("%04d-%02d-%02d", $year, $month, $day);
							}
							// If date from project max range is in format MM/DD/YYYY
							if (strpos($ar_metadata[$fieldname]['element_validation_max'], "/") !== false) 
							{
								list ($month, $day, $year) = explode('/', $ar_metadata[$fieldname]['element_validation_max']);
								$ar_metadata[$fieldname]['element_validation_max'] = sprintf("%04d-%02d-%02d", $year, $month, $day);
							}
						}
						
						$element_validation_min = $ar_metadata[$fieldname]['element_validation_min'];
						$element_validation_max = $ar_metadata[$fieldname]['element_validation_max'];
						
						//if lower bound is specified
						if ($element_validation_min !== "" && $element_validation_min !== null) {
							//if new value is smaller than lower bound
							if ($newvalue < $element_validation_min){
								//if hard check
								if ($ar_metadata[$fieldname]['element_validation_checktype'] == 'hard'){
									$updateitems[$studyid][$event_id][$fieldname]['check'] = 'error'; $total_errors++;
									$updateitems[$studyid][$event_id][$fieldname]['message'] = "$fieldname {$lang['data_import_tool_92']} ($element_validation_min).";
								//if not hard check
								} elseif ($updateitems[$studyid][$event_id][$fieldname]['check'] != 'error') {
									$updateitems[$studyid][$event_id][$fieldname]['check'] = 'warning';
									$updateitems[$studyid][$event_id][$fieldname]['message'] = "$fieldname {$lang['data_import_tool_93']} ($element_validation_min).";
								}
							}								
						} 
						
						//if upper bound is specified
						if ($element_validation_max !== "" && $element_validation_max !== null) {	
							//if new value is greater than upper bound
							if ($newvalue > $element_validation_max){
								//if hard check
								if ($ar_metadata[$fieldname]['element_validation_checktype'] == 'hard'){
									$updateitems[$studyid][$event_id][$fieldname]['check'] = 'error'; $total_errors++;
									$updateitems[$studyid][$event_id][$fieldname]['message'] = "$fieldname {$lang['data_import_tool_94']} ($element_validation_max).";
								//if not hard check
								} elseif ($updateitems[$studyid][$event_id][$fieldname]['check'] != 'error') {
									$updateitems[$studyid][$event_id][$fieldname]['check'] = 'warning';
									$updateitems[$studyid][$event_id][$fieldname]['message'] = "$fieldname {$lang['data_import_tool_95']} ($element_validation_max).";
								}
							}
						}
					}
					
					// If field is a checkbox, make sure value is either 0 or 1
					if (isset($chkbox_fields_new[$fieldname]) && $newvalue != "1" && $newvalue != "0") 
					{
						$updateitems[$studyid][$event_id][$fieldname]['check'] = 'error'; $total_errors++;
						$updateitems[$studyid][$event_id][$fieldname]['message'] = "$fieldname {$lang['data_import_tool_139']}";
					}
				
					// If using SECONDARY UNIQUE FIELD, then check for any duplicate values in imported data
					if ($checkSecondaryPk && $secondary_pk == $fieldname)
					{
						// Check for any duplicated values for the $secondary_pk field (exclude current record name when counting)
						$sql = "select 1 from redcap_data where project_id = ".PROJECT_ID." and field_name = '$secondary_pk' 
								and value = '" . prep($newvalue) . "' and record != '" . prep($studyid) . "' limit 1";
						$q = db_query($sql);
						$uniqueValueExists = (db_num_rows($q) > 0);
						// If the value already exists for a record, then throw an error
						if ($uniqueValueExists)
						{ 
							$total_errors++;
							$updateitems[$studyid][$event_id][$fieldname]['check'] = 'error';
							$updateitems[$studyid][$event_id][$fieldname]['message'] = "{$lang['data_import_tool_154']} (i.e. \"$secondary_pk\"){$lang['period']} {$lang['data_import_tool_155']}";						
						}
					}

				} //end "if the field contains new or updated data, check it against the metadata" loop
				
				// DAG: If record exists but is NOT in user's Data Access Group (if in a group at all), mark as an error with message
				if ($user_rights['group_id'] != "" && isset($record_dag[$studyid]) && $record_dag[$studyid] == "0") {
					$updateitems[$studyid][$event_id][$fieldname]['check'] = 'error'; $total_errors++;
					$updateitems[$studyid][$event_id][$fieldname]['message'] = "$table_pk_label \"<b>$studyid</b>\" {$lang['data_import_tool_140']}";
				}
				
				// RANDOMIZATION CHECK: Make sure that users cannnot import data into a randomiztion field OR into a criteria field
				// if the record has already been randomized
				if ($randomizationIsSetUp)
				{
					// Check if this is target randomization field, which CANNOT be edited. If so, give error.
					if ($fieldname == $randTargetField) 
					{
						$updateitems[$studyid][$event_id][$fieldname]['check'] = 'error'; $total_errors++;
						$updateitems[$studyid][$event_id][$fieldname]['message'] = "{$lang['data_import_tool_162']} (\"<b>$fieldname</b>\") {$lang['data_import_tool_161']}";
					} 
					// Check if this is a criteria field AND is criteria event_id AND if the record has already been randomized
					elseif (isset($randCritFieldsEvents[$fieldname]) && $randCritFieldsEvents[$fieldname] == $event_id 
						&& $updateitems[$studyid][$event_id][$fieldname]['newvalue'] != "" && Randomization::wasRecordRandomized($studyid)) 
					{
						$updateitems[$studyid][$event_id][$fieldname]['check'] = 'error'; $total_errors++;
						$updateitems[$studyid][$event_id][$fieldname]['message'] = "$table_pk_label \"<b>$studyid</b>\" {$lang['data_import_tool_163']} (\"<b>$fieldname</b>\"){$lang['data_import_tool_164']}";
					}
				}
				
				// Field-Event Mapping Check: Make sure this field exists on a form that is designated to THIS event. If not, then error.
				if ($longitudinal && is_numeric($event_id) && $updateitems[$studyid][$event_id][$fieldname]['newvalue'] != "" && $fieldname != $table_pk)
				{
					// Skip if this is the redcap_event_name
					if ($fieldname == 'redcap_event_name' || $fieldname == 'redcap_data_access_group') continue;
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
					if (!empty($event_id) && !in_array($fieldname, $reserved_field_names2) && !isset($reserved_field_names[$fieldname]) 
						&& !in_array($Proj->metadata[$true_fieldname]['form_name'], $Proj->eventsForms[$event_id]))
					{
						$updateitems[$studyid][$event_id][$fieldname]['check'] = 'error'; $total_errors++;
						$updateitems[$studyid][$event_id][$fieldname]['message'] = "33 {$lang['data_import_tool_162']} (\"<b>$fieldname</b>\") {$lang['data_import_tool_165']} \"<b>{$Proj->eventInfo[$event_id]['name_ext']}</b>\"{$lang['period']} {$lang['data_import_tool_166']}";
					}
				}
			}
		}
	}
	
	if (isset($_REQUEST['updaterecs']) && $total_errors > 0) 
	{
		//If the Import Data button has been pressed, but some errors exist, then the Excel file must've been changed locally and now it has errors.
		print '<table width=100%><tr><td class="comp_new_error">';
		print '<p><b>'.$lang['global_01'].':</b><br>'.$lang['data_import_tool_96'].'<p>';
		print '</td></tr></table>';
		exit ('</td></tr></table></td></tr></table></body></html>');
	}
	
	// Free up memory
	unset($studyrecord, $ar_metadata, $locked, $record_dag, $event_array, $studyevent);
	
	return $updateitems;
}

######################################################################		
### Build error display table

function make_error_table($updateitems,$metadata_fields) 
{	
	global $table_pk_label, $longitudinal, $chkbox_fields, $lang, $Proj, $reserved_field_names2;
						   
	$errorcount = 0;
	$warningcount = 0;
	$altrow = 1;
	
	$errortable =  "<br><table id='errortable'><tr><th scope=\"row\" class=\"comp_fieldname\" bgcolor=\"black\" colspan=4>
					<font color=\"white\">{$lang['data_import_tool_97']}</th></tr>\n";
	
	$errortable .= "<tr><th scope='col'>$table_pk_label</th><th scope='col'>{$lang['data_import_tool_98']}</th>
					<th scope='col'>{$lang['data_import_tool_99']}</th><th scope='col'>{$lang['data_import_tool_100']}</th></tr>";
					
	foreach ($updateitems as $studyid => $studyevent) 
	{
		foreach ($studyevent as $event_id => $studyrecord) 
		{
			foreach ($studyrecord as $fieldname=>$datapoint)
			{
				$altrow = $altrow ? 0 : 1;

				if (isset($updateitems[$studyid][$event_id][$fieldname]['check'])){

					$errortable .= $altrow ? "<tr class='alt'>" : "<tr>";

					if ($updateitems[$studyid][$event_id][$fieldname]['check'] == 'error'){
						$errortable .= "<th>$studyid</th>";
						$errortable .= "<td class='comp_new'>$fieldname</td>";
						$errortable .= "<td class='comp_new_error'>" . $updateitems[$studyid][$event_id][$fieldname]['newvalue'] . "</td>";
						$errortable .= "<td class='comp_new'>" . $updateitems[$studyid][$event_id][$fieldname]['message'] . "</td>";
						$errorcount++;
					}
					if ($updateitems[$studyid][$event_id][$fieldname]['check'] == 'warning'){
						$errortable .= "<th>$studyid</th>\n";
						$errortable .= "<td class='comp_new'>$fieldname</td>\n";
						$errortable .= "<td class='comp_new_warning'>" . $updateitems[$studyid][$event_id][$fieldname]['newvalue'] . "</td>\n";
						$errortable .= "<td class='comp_new'>" . $updateitems[$studyid][$event_id][$fieldname]['message'] . "</td>\n";
						$warningcount++;
					}
					
					$errortable .= "</tr>";
					
				}
				
				//Add extra row here if the field_name in the Excel file does not exist in the project (give warning since will not hurt data)
				if (!in_array($fieldname, $metadata_fields)) 
				{
					// Allow unique event names for redcap_event_name column
					if ($fieldname == "redcap_event_name" && $Proj->uniqueEventNameExists($updateitems[$studyid][$event_id][$fieldname]['newvalue'])) {
						continue;
					}
					// Allow unique event names for redcap_data_access_group column
					elseif ($fieldname == "redcap_data_access_group" && ($updateitems[$studyid][$event_id][$fieldname]['newvalue'] == "" || $Proj->uniqueGroupNameExists($updateitems[$studyid][$event_id][$fieldname]['newvalue']))) {
						continue;
					}
					//Field name does not exist in project
					$errortable .= "<tr><th>$studyid</th>\n";
					$errortable .= "<td class='comp_new'>$fieldname</td>\n";
					if ($updateitems[$studyid][$event_id][$fieldname]['newvalue'] == "") {
						$errortable .= "<td class='comp_new'>&nbsp;</td>\n";
					} else {
						$errortable .= "<td class='comp_new'>" . $updateitems[$studyid][$event_id][$fieldname]['newvalue'] . "</td>\n";
					}
					if (isset($chkbox_fields[$fieldname])) {
						// If original checkbox field name is in Excel file instead of translated checkbox field name (with triple underscore), then notify
						$errortable .= "<td class='comp_new_error'>{$lang['data_import_tool_141']}";
						// Although normally mismatching field names are ignored as warnings, change these to errors to ensure that the user knows about this formate.
						$errorcount++;
						$warningcount--;
					} elseif (in_array($fieldname, $reserved_field_names2) || $fieldname == "redcap_survey_timestamp" 
						|| $fieldname == "redcap_survey_identifier") {						
						// Give error if uploaded with reserved field names
						$errortable .= "<td class='comp_new_warning'>{$lang['data_import_tool_182']}";
						// Don't count the warnings here because they are counted below
					} elseif ($fieldname == "redcap_event_name") {
						// Give error that unique event name is not valid
						$errortable .= "<td class='comp_new_error'>{$lang['data_import_tool_167']}";
						$errorcount++;
					} elseif ($fieldname == "redcap_data_access_group") {
						// Give error that unique event name is not valid
						$errortable .= "<td class='comp_new_error'>{$lang['data_import_tool_175']}";
						$errorcount++;
					} else {
						// Give normal error that field does not exist in project
						$errortable .= "<td class='comp_new_error'>{$lang['data_import_tool_101']}";
						$errorcount++;
					}
					$errortable .= "</td></tr>\n";
					$warningcount++;
				}
			}
		}
	}

	$errortable .= "</table>";
	
	unset($studyevent,$studyrecord);
	
	return array ($errortable, $errorcount, $warningcount);
}


######################################################################		
### Build data comparison table

function make_comparison_table($fieldnames_new, $updateitems)
{
	global $lang, $table_pk, $user_rights;
	
	// Determine if imported values are a new or existing record by gathering all existing records into an array for reference
	$existing_records = array();
	$q = db_query("select distinct record from redcap_data where field_name = '$table_pk' and project_id = ".PROJECT_ID);
	while ($row = db_fetch_assoc($q)) {
		$existing_records[] = $row['record'];
	}
	
	$comparisontable = array();
	$rowcounter = 0;
	$columncounter = 0;
	
	//make "header" column (leftmost column) with fieldnames 
	foreach ($fieldnames_new as $fieldname){
		$comparisontable[$rowcounter][$columncounter] = "<th scope='row' class='comp_fieldname'>$fieldname</th>";
		$rowcounter++;
	}
	
	// Create array of all new records
	$newRecords = array();

	foreach ($updateitems as $studyid=>$studyevent) 
	{
		foreach ($studyevent as $event_id=>$studyrecord) 
		{
			$rowcounter = 0;
			$columncounter++;
			// Check if a new record or not
			$newrecord = !in_array($studyid."", $existing_records, true);
			// Increment new record count
			if ($newrecord) $newRecords[] = $studyid;
			// Loop through all fields for this record
			foreach ($fieldnames_new as $fieldname)
			{
				if ($rowcounter == 0){ //case of column header (cells contain the record id)
					// Check if a new record or not
					if (!$newrecord) {
						$existing_status = "<div class='exist_impt_rec'>({$lang['data_import_tool_144']})</div>";
					} else {
						$existing_status = "<div class='new_impt_rec'>({$lang['data_import_tool_145']})</div>";
					}
					// Render record number as table header
					$comparisontable[$rowcounter][$columncounter] = "<th scope='col' class='comp_recid'><span id='record-{$columncounter}'>$studyid</span><span style='display:none;' id='event-{$columncounter}'>$event_id</span>$existing_status</th>";
				
				} else {
				//3 cases: new (+ errors or warnings), old, and update (+ errors or warnings)
					
					// Display redcap event name normally
					if (!(isset($updateitems[$studyid][$event_id][$fieldname]))){
						$comparisontable[$rowcounter][$columncounter] = "<td class='comp_old'>&nbsp;</td>";
					} else {
						if ($updateitems[$studyid][$event_id][$fieldname]['status'] == 'new'){
							if (isset($updateitems[$studyid][$event_id][$fieldname]['check'])){
								//if error
								if ($updateitems[$studyid][$event_id][$fieldname]['check'] == 'error'){
									$comparisontable[$rowcounter][$columncounter] = "<td class='comp_new_error'>" . $updateitems[$studyid][$event_id][$fieldname]['newvalue'] . "</td>";
								} 
								elseif ($updateitems[$studyid][$event_id][$fieldname]['check'] == 'warning'){ //if warning
									$comparisontable[$rowcounter][$columncounter] = "<td class='comp_new_warning'>" . $updateitems[$studyid][$event_id][$fieldname]['newvalue'] . "</td>";
								} 
								else {
									//shouldn't be a case of this
									$comparisontable[$rowcounter][$columncounter] = "<td class='comp_new'>problem!</td>";
								}
							} 
							else{
								$comparisontable[$rowcounter][$columncounter] = "<td class='comp_new'>" . $updateitems[$studyid][$event_id][$fieldname]['newvalue'] . "</td>";
							}
						}
						elseif ($updateitems[$studyid][$event_id][$fieldname]['status'] == 'old'){
							if ($updateitems[$studyid][$event_id][$fieldname]['oldvalue'] != ""){
								$comparisontable[$rowcounter][$columncounter] = "<td class='comp_old'>" . $updateitems[$studyid][$event_id][$fieldname]['oldvalue'] . "</td>";
							} else {
								$comparisontable[$rowcounter][$columncounter] = "<td class='comp_old'>&nbsp;</td>";
							}
						}
						elseif ($updateitems[$studyid][$event_id][$fieldname]['status'] == 'update'){
							if (isset($updateitems[$studyid][$event_id][$fieldname]['check'])){
								//if error
								if ($updateitems[$studyid][$event_id][$fieldname]['check'] == 'error'){
									$comparisontable[$rowcounter][$columncounter] = "<td class='comp_update_error'>" . $updateitems[$studyid][$event_id][$fieldname]['newvalue'] . "</td>";
								} elseif ($updateitems[$studyid][$event_id][$fieldname]['check'] == 'warning'){ //if warning
									$comparisontable[$rowcounter][$columncounter] = "<td class='comp_update_warning'>" . $updateitems[$studyid][$event_id][$fieldname]['newvalue'] . "</td>";
								} else {
									//shouldn't be a case of this
									$comparisontable[$rowcounter][$columncounter] = "<td class='comp_new'>problem!</td>";
								}
							} else {
								// Show new and old value
								$comparisontable[$rowcounter][$columncounter] = "<td class='comp_update'>" 
																			  . $updateitems[$studyid][$event_id][$fieldname]['newvalue'];
								if (!$newrecord) {
									$comparisontable[$rowcounter][$columncounter] .= "<br><span class='comp_oldval'>(" 
																				  . $updateitems[$studyid][$event_id][$fieldname]['oldvalue'] 
																				  . ")</span>";
								}
								$comparisontable[$rowcounter][$columncounter] .= "</td>";
							}
						}
					}
				}
				$rowcounter++;
			}
		}
	}
		
	// Build table (format as ROWS)
	if (isset($_POST['format']) && $_POST['format'] == 'rows')
	{
		$comparisonstring = "<table id='comptable' class='notranslate'><tr><th scope='row' class='comp_fieldname' colspan='$rowcounter' bgcolor='black'><font color='white'><b>{$lang['data_import_tool_28']}</b></font></th></tr>";
		for ($rowi = 0; $rowi <= $columncounter; $rowi++)
		{
			$comparisonstring .= "<tr>";
			for ($colj = 0; $colj < $rowcounter; $colj++)
			{
				$comparisonstring .= $comparisontable[$colj][$rowi];
			}
			$comparisonstring .= "</tr>";
		}
		$comparisonstring .= "</table>";
	}
	// Build table (format as COLUMNS)
	else
	{
		$comparisonstring = "<table id='comptable'><tr><th scope='row' class='comp_fieldname' colspan='" . ($columncounter+1) . "' bgcolor='black'><font color='white'><b>{$lang['data_import_tool_28']}</b></font></th></tr>";
		foreach ($comparisontable as $rowi => $rowrecord) 
		{	
			$comparisonstring .= "<tr>";
			foreach ($rowrecord as $colj =>$cellpoint)
			{
				$comparisonstring .= $comparisontable[$rowi][$colj];
			}
			$comparisonstring .= "</tr>";
		}	
		$comparisonstring .= "</table>";
	}
	
	// If user is not allowed to create new records, then stop here if new records exist in uploaded file
	if (!$user_rights['record_create'] && !empty($newRecords))
	{
		print  "<div class='red' style='margin-bottom:15px;'>
					<b>{$lang['global_01']}{$lang['colon']}</b><br>
					{$lang['data_import_tool_159']} <b>
					".implode("</b>, <b>", $newRecords)."</b>{$lang['period']}
				</div>";
		renderPrevPageLink("DataImport/index.php");
		exit;	
	}
	
	return $comparisonstring;
	
}



######################################################################		
### Create the sql for updating records

function build_importsql($updateitems,$change_reasons) 
{	
	global $app_name, $user_rights, $longitudinal, $table_pk, $chkbox_fields_new, $chkbox_fields, 
		   $lang, $Proj, $reserved_field_names, $reserved_field_names2;
	
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
	
	// Determine if imported values are a new or existing record by gathering all existing records into an array for reference
	$existing_records = array();
	$q = db_query("select distinct record from redcap_data where field_name = '$table_pk' and project_id = ".PROJECT_ID);
	while ($row = db_fetch_assoc($q)) {
		$existing_records[] = $row['record'];
	}
	
	// Set up all actions as a transaction to ensure everything is done here
	$q_errors = 0;
	$recordDags = array();
	db_query("SET AUTOCOMMIT=0");
	db_query("BEGIN");
	
	## INSTANTIATE SURVEY INVITATION SCHEDULE LISTENER
	// If the form is designated as a survey, check if a survey schedule has been defined for this event.
	// If so, perform check to see if this record/participant is NOT a completed response and needs to be scheduled for the mailer.
	$surveyScheduler = new SurveyScheduler();
	
	// Generate UPDATE/INSERT statements for every updated field
	foreach ($updateitems as $studyid=>$studyevent) 
	{
		// Loop through all events for this record
		foreach ($studyevent as $event_id=>$studyrecord) 
		{
			// Clear array values for this record
			$sql_all = array();
			$display = array();
			// COPY COMPLETED RESPONSE: If this record-event is a completed survey response, then check if needs to be
			// copied to surveys_response_values table to preserve the pristine completed original response.
			if (isset($completedResponses[$studyid][$event_id])) {
				// Copy original response (if not copied already)
				copyCompletedSurveyResponse($completedResponses[$studyid][$event_id]);
				// Free up memory
				unset($completedResponses[$studyid][$event_id]);
			}
			// Loop through all values for this record
			foreach ($studyrecord as $fieldname=>$datapoint) 
			{
				// Skip over redcap_event_name and other pseudo-fields
				if (isset($reserved_field_names[$fieldname]) || in_array($fieldname, $reserved_field_names2)) 
				{
					// Skip over redcap_data_access_group pseudo-field BUT use it later to add to data table
					if ($fieldname == 'redcap_data_access_group') {
						$recordDags[$studyid][$event_id] = $datapoint['newvalue'];
					}
					// Stop here and begin next loop
					continue;
				}
				
				if ($updateitems[$studyid][$event_id][$fieldname]['status'] != 'old') {	
					// CHECKBOXES
					if (isset($chkbox_fields_new[$fieldname])) {
						// Since only checked values are saved in data table, we must ONLY do either Inserts or Deletes. Reconfigure.
						if ($datapoint['newvalue'] == "1" && ($datapoint['oldvalue'] == "0" || $datapoint['oldvalue'] == "")) {
							// If changed from "0" to "1", change to Insert
							$updateitems[$studyid][$event_id][$fieldname]['status'] = 'new';
						} else {
							// If changed from "1" to "0", change to Delete
							$updateitems[$studyid][$event_id][$fieldname]['status'] = 'delete';
						}
						// Re-configure checkbox variable name and value
						list ($datapoint['newvalue'], $fieldname_orig) = explode("___", strrev($fieldname), 2);
						$fieldname_orig = strrev($fieldname_orig);
						$datapoint['newvalue'] = strrev($datapoint['newvalue']);
						// Since users can designate capital letters as checkbox codings AND because variable names force those codings to lower case,
						// we need to loop through this field's codings to find the matching coding if it's not numerical, otherwise it might get saved 
						// incorrectly because of case insensitivity.
						if (!is_numeric($datapoint['newvalue'])) {
							foreach (array_keys($chkbox_fields[$fieldname_orig]) as $this_code) {
								if (strtolower($this_code) == $datapoint['newvalue']) {
									$datapoint['newvalue'] = $this_code;
								}
							}
						}
					// NON-CHECKBOXES
					} else {
						// Regular fields keep same variable name 
						$fieldname_orig = $fieldname;
					}
					// Do insert query
					if ($updateitems[$studyid][$event_id][$fieldname]['status'] == 'new') {
						$sql_all[] = $sql = "INSERT INTO redcap_data VALUES (".PROJECT_ID.", $event_id, '".prep($studyid)."', "
										  . "'$fieldname_orig', '".prep($datapoint['newvalue'])."')";
					// Do update query
					} elseif ($updateitems[$studyid][$event_id][$fieldname]['status'] == 'update') {
						$sql_all[] = $sql = "UPDATE redcap_data SET value = '".prep($datapoint['newvalue'])."' WHERE "
										  . "project_id = ".PROJECT_ID." AND record = '".prep($studyid)."' AND "
										  . "field_name = '$fieldname_orig' AND event_id = $event_id";
					// Do delete query
					} elseif ($updateitems[$studyid][$event_id][$fieldname]['status'] == 'delete') {
						$sql_all[] = $sql = "DELETE FROM redcap_data WHERE project_id = ".PROJECT_ID." "
										  . "AND record = '".prep($studyid)."' AND "
										  . "field_name = '$fieldname_orig' AND event_id = $event_id "
										  . "AND value = '".prep($datapoint['newvalue'])."'";
					}
					// Execute query
					if (!db_query($sql)) {
						$q_errors++;
					}
					if (isset($chkbox_fields_new[$fieldname])) {
						// Checkbox logging display
						$display[] = "$fieldname_orig({$datapoint['newvalue']}) = " . (($updateitems[$studyid][$event_id][$fieldname]['status'] == 'new') ? "checked" : "unchecked");
					} else {
						// Logging display for normal fields
						$display[] = "$fieldname_orig = '{$datapoint['newvalue']}'";
					}
				}
			}	
			
			## LOGGING (do separate logging for each record-event)
			// Determine if we're updating an existing record or creating a new one	
			if (in_array($studyid, $existing_records)) {
				$this_event_type  = "update";
				$this_log_descrip = "Update record (import)";
			} else {
				$this_event_type  = "insert";
				$this_log_descrip = "Create record (import)";
			}
			// Log it
			$_GET['event_id'] = $event_id; // set for logging
			$this_change_reason = isset($change_reasons[$studyid][$event_id]) ? $change_reasons[$studyid][$event_id] : "";
			log_event(implode(";\n", $sql_all), "redcap_data", $this_event_type, $studyid, implode(",\n", $display), $this_log_descrip, $this_change_reason);
		}
		
		// SURVEY INVITATION SCHEDULER: Return count of invitation scheduled, if any
		if (!empty($Proj->surveys)) {
			$numInvitationsScheduled = $surveyScheduler->checkToScheduleParticipantInvitation($studyid);
		}
	}
	
	## DAGS: Save to data table
	//If user is in a Data Access Group, do insert query for Group ID number so that record will be tied to that group
	if ($user_rights['group_id'] != "") {
		foreach ($updateitems as $studyid=>$studyevent) {
			foreach (array_keys($studyevent) as $event_id) {
				// Clear out any existing values first before adding this one
				$sql = "DELETE FROM redcap_data WHERE project_id = ".PROJECT_ID." AND record = '".prep($studyid)."' 
						AND field_name = '__GROUPID__' AND event_id = $event_id";
				db_query($sql);
				// Add group_id to data table for this event for this record so that it gets tagged appropriately for this event
				$sql = "INSERT INTO redcap_data VALUES (".PROJECT_ID.", $event_id, '".prep($studyid)."', '__GROUPID__', '".$user_rights['group_id']."')";
				db_query($sql);
			}
		}
	} else {
		// Loop through each record-event-DAG
		foreach ($recordDags as $studyid=>$studyevent) 
		{
			// Set flag to log DAG designation
			$dag_sql_all = array();			
			// Loop through each event in this record
			foreach ($studyevent as $event_id=>$group_name) 
			{
				// Ignore if group name is blank
				if ($group_name == '') continue;
				// Get group_id
				$group_id = array_search($group_name,  $Proj->getUniqueGroupNames());
				if (!is_numeric($group_id)) continue;
				// Clear out any existing values for THIS EVENT before adding this one
				$sql = $dag_sql_all[] = "DELETE FROM redcap_data WHERE project_id = ".PROJECT_ID." AND record = '".prep($studyid)."' 
						AND field_name = '__GROUPID__' AND event_id = $event_id";
				db_query($sql);
				// Update ALL OTHER EVENTS to new group_id (if other events have group_id stored)
				$sql = $dag_sql_all[] = "UPDATE redcap_data SET value = '$group_id' WHERE project_id = ".PROJECT_ID." 
						AND record = '".prep($studyid)."' AND field_name = '__GROUPID__'";
				db_query($sql);
				// Insert group_id for THIS EVENT
				$sql = $dag_sql_all[] = "INSERT INTO redcap_data VALUES (".PROJECT_ID.", $event_id, '".prep($studyid)."', '__GROUPID__', '$group_id')";
				db_query($sql);
				// Update any calendar events tied to this group_id
				$sql = $dag_sql_all[] = "UPDATE redcap_events_calendar SET group_id = " . checkNull($group_id) . " 
						WHERE project_id = ".PROJECT_ID." AND record = '" . prep($studyid) . "'";
				db_query($sql);
			}	
			// Log DAG designation (if occurred)
			if ($updateitems[$studyid][$event_id]['redcap_data_access_group']['status'] != 'old' && isset($dag_sql_all) && !empty($dag_sql_all)) 
			{
				log_event(implode(";\n",$dag_sql_all), "redcap_data", "update", $studyid, "redcap_data_access_group = '$group_name'", "Assign record to Data Access Group");
			}
		}
	}
	
	// If errors occurred, rollback all changes made thus far
	if ($q_errors > 0) {
		db_query("ROLLBACK");
		print  "<br><br>
				<div class='red'>
					<img src='".APP_PATH_IMAGES."exclamation.png' class='imgfix'> <b>{$lang['data_import_tool_146']}</b><br><br>
					{$lang['data_import_tool_147']}
				</div>";
		exit;
	} else {
		db_query("COMMIT");
		db_query("SET AUTOCOMMIT=1");
	}
	
	return;
	
}
