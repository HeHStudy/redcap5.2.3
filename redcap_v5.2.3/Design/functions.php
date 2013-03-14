<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

######################################################################		
### Process uploaded Excel file, return references to (1) an array of fieldnames and (2) an array of items to be updated

function excel_to_array($excelfilepath) 
{	
	global $lang, $project_language, $surveys_enabled;
	
	// Set up array to switch out Excel column letters
	$cols = array(1 => "A", 2 => "B", 3 => "C", 4 => "D", 5 => "E", 6 => "F", 7 => "G", 8 => "H", 
				  9 => "I", 10 => "J", 11 => "K", 12 => "L", 13 => "M", 14 => "N", 15 => "O", 16 => "P");
				  
	// Extract data from CSV file and rearrange it in a temp array
	$newdata_temp = array();
	$i = 1;
	
	// Set commas as default delimiter (if can't find comma, it will revert to tab delimited)
	$delimiter 	  = ","; 
	$removeQuotes = false;
	
	if (($handle = fopen($excelfilepath, "rb")) !== false) 
	{
		// Loop through each row
		while (($row = fgetcsv($handle, 0, $delimiter)) !== false) 
		{
			// Skip row 1
			if ($i == 1) 
			{
				## CHECK DELIMITER
				// Determine if comma- or tab-delimited (if can't find comma, it will revert to tab delimited)
				$firstLine = implode(",", $row);
				// If we find X number of tab characters, then we can safely assume the file is tab delimited
				$numTabs = 6;
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
				// Increment counter
				$i++;
				// Check if legacy column Field Units exists. If so, tell user to remove it (by returning false). 
				// It is no longer supported but old values defined prior to 4.0 will be preserved.
				if (strpos(strtolower($row[2]), "units") !== false)
				{
					return false;
				}
				continue;
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
				// Add to array
				$newdata_temp[$cols[$j+1]][$i] = $row[$j];
				// Use only for Japanese SJIS encoding
				if ($project_language == 'Japanese') 
				{
					$newdata_temp[$cols[$j+1]][$i] = mb_convert_encoding($newdata_temp[$cols[$j+1]][$i], 'UTF-8',  'sjis');
				}
			}
			$i++;
		}
		fclose($handle);
	}
	
	// If file was tab delimited, then check if it left an empty row on the end (typically happens)
	if ($delimiter == "\t" && $newdata_temp['A'][$i-1] == "")
	{
		// Remove the last row from each column
		foreach (array_keys($newdata_temp) as $this_col)
		{
			unset($newdata_temp[$this_col][$i-1]);
		}
	}
	
	// Return array with data dictionary values
	return $newdata_temp;
	
}




/**
 * Function for checking errors in the data dictionary file
 */
function error_checking($dictionary_array) 
{	
	global $status, $project_language, $lang, $surveys_enabled, $reserved_field_names, $table_pk, $randomization, $Proj;
	
	// Error messages will go in this array
	$errors_array = array();
	// Warning messages will go in this array (they are allowable or correctable errors)
	$warnings_array = array();
	// Get correct table we're using, depending on if in production
	$metadata_table = ($status > 0) ? "redcap_metadata_temp" : "redcap_metadata";
	// Obtain the table_pk from metadata and compare to one in uploaded file to make sure it's not changing
	$sql = "select field_name from $metadata_table where project_id = " . PROJECT_ID . " order by field_order limit 1";
	$current_table_pk = db_result(db_query($sql), 0);
	// Set default value of table_pk from uploaded file (obtain during looping)
	$file_table_pk = "";	
	// Check if any data exists yet. Needed for checking if changing PK.
	$q = db_query("select 1 from redcap_data where project_id = " . PROJECT_ID . " limit 1");
	$noData = (db_num_rows($q) == 0);
	// Set extra set of reserved field names for survey timestamps and return codes pseudo-fields
	$reserved_field_names2 = explode(',', implode("_timestamp,", array_keys($Proj->forms)) . "_timestamp"
						   . "," . implode("_return_code,", array_keys($Proj->forms)) . "_return_code");
	
	## FIELD NAMES
	foreach ($dictionary_array['A'] as $row => $this_field) 
	{
		// Get the record ID field (table_pk for this DD file)
		if (!empty($this_field) && empty($file_table_pk)) {
			// Set file pk value
			$file_table_pk = $this_field;
			// Make sure that record ID field does NOT have a section header (unneccessary)
			$dictionary_array['C'][$row] = "";
		} 
		// If variable name is missing
		elseif (empty($this_field)) {
			$errors_array[2][] = "<b>A{$row}</b>";
		}
		//CHECK FOR BLANK FIELD NAMES AND IF TABLE_PK IS DIFFERENT (IF DATA EXISTS)
		if (!$noData && $file_table_pk == $this_field && $current_table_pk != $file_table_pk) {
			$warnings_array[13] = "<img src='".APP_PATH_IMAGES."exclamation_orange.png' class='imgfix'>
								<b>{$lang['database_mods_92']}</b> {$lang['database_mods_93']} 
								{$lang['update_pk_02']} {$lang['update_pk_05']} {$lang['database_mods_96']}<br>
								&nbsp;&bull; {$lang['database_mods_94']} <b>$current_table_pk</b><br>
								&nbsp;&bull; {$lang['database_mods_95']} <b>$file_table_pk</b>";
		}
		// Check field names if it has two-byte characters (for Japanese)
		if ($project_language == 'Japanese') {
			if (mb_detect_encoding($this_field) != "ASCII") {
				$errors_array[7][] = "<b>A{$row}</b>";
			}
		}
		//FIELD NAMES CANNOT BEGIN WITH NUMERAL
		if (is_numeric(substr($this_field, 0, 1))) {
			$errors_array[3][] = "<b>$this_field (A{$row})</b>";
		}
		//FIELD NAMES CANNOT BEGIN WITH NUMERAL
		if (substr($this_field, 0, 1) == "_") {
			$errors_array[10][] = "<b>$this_field (A{$row})</b>";
		}
		//FIELD NAMES CANNOT HAVE A TRIPE UNDERSCORE (reserved for checkbox variable names when exported)
		if (strpos($this_field, "___") !== false) {
			$errors_array[26][] = "<b>$this_field (A{$row})</b>";
		}
		//FIELD NAMES SHOULD NOT BE LONGER THAN 26 CHARS AND CANNOT BE LONGER THAN 100
		if (strlen($this_field) > 100) {
			$errors_array[11][] = "<b>$this_field (A{$row})</b>";
		} elseif (strlen($this_field) > 26) {
			$warnings_array[3][] = "<b>$this_field (A{$row})</b>";
		}
		//ONLY LOWERCASE LETTERS, NUMBERS, AND UNDERSCORES
		if ($this_field != preg_replace("/[^a-z_0-9]/", "", $this_field) && strpos($this_field, "___") === false) { 
			// Triple underscores would already be caught earlier and listed as an error, so ignore them here
			$dictionary_array['A'][$row] = preg_replace("/[^a-z_0-9]/", "", str_replace(" ", "_", strtolower($this_field)));
			$warnings_array[1][] = "<b>$this_field (A{$row})</b> {$lang['database_mods_15']} <b>" . $dictionary_array['A'][$row] . "</b>";
			$this_field = $dictionary_array['A'][$row];
		}
		//VARIABLE NAME	CANNOT BE A RESERVED FIELD NAME
		if (isset($reserved_field_names[$this_field]) || in_array($this_field, $reserved_field_names2)) {
			$errors_array[22][] = "<b>A{$row}</b>";
		}
		//VARIABLE NAME	CANNOT BE A FORM NAME + "_complete". Flag any possible errors and keep to check later after we've collected the form names.
		if (substr($this_field, -9) == "_complete") {
			$errors_array[23][$row] = substr($this_field, 0, -9);
		}
	}
	// FIELD NAME DUPLICATION (do this last in column 1 since it will break the query)
	$field_diff = array_diff_assoc($dictionary_array['A'], array_unique($dictionary_array['A']));
	if (count($field_diff) > 0) {
		$errors_array[1] = $lang['database_mods_16'];
		foreach ($field_diff as $row => $this_field) {
			$errors_array[1] .= "<br><b>$this_field (A{$row})</b>";
		}
	}
	if (isset($errors_array[2])) {
		$errors_array[2] = $lang['database_mods_17'] . implode(", ", $errors_array[2]) . $lang['period'];
	}
	if (isset($errors_array[3])) {
		$errors_array[3] = "{$lang['database_mods_18']}<br>" . implode("<br>", $errors_array[3]);
	}
	if (isset($errors_array[26])) {
		$errors_array[26] = "{$lang['database_mods_19']}<br>" . implode("<br>", $errors_array[26]);
	}
	if (isset($errors_array[10])) {
		$errors_array[10] = "{$lang['database_mods_20']}<br>" . implode("<br>", $errors_array[10]);
	}
	if (isset($warnings_array[1])) {
		$warnings_array[1] = "{$lang['database_mods_21']}<br>" . implode("<br>", $warnings_array[1]);
	}
	if (isset($warnings_array[3])) {
		$warnings_array[3] = "{$lang['database_mods_22']}<br>" . implode("<br>", $warnings_array[3]);
	}
	if (isset($errors_array[11])) {
		$errors_array[11] = "{$lang['database_mods_23']}<br>" . implode("<br>", $errors_array[11]);
	}
	if (isset($errors_array[22])) {
		$errors_array[22] = $lang['database_mods_24'] . implode(", ", $errors_array[22]);
	}
	if (isset($errors_array[7])) {
		$errors_array[7] = $lang['database_mods_25'] . implode(", ", $errors_array[7]) . $lang['period'];
	}	
	// RANDOMIZATION: Make sure all fields used in randomization exist in the uploaded DD
	if ($randomization && Randomization::setupStatus()) 
	{
		// Get randomization fields
		$randFields = Randomization::getRandomizationFields();
		foreach ($randFields as $this_field) {
			if (!in_array($this_field, $dictionary_array['A'])) {
				$errors_array[30][] = "<b>$this_field (A{$row})</b>";
			}
		}
		if (isset($errors_array[30])) {
			$errors_array[30] = $lang['database_mods_118'] . " " . implode(", ", $errors_array[30]) . $lang['period'];
		}
	}

	
	## FORM NAMES
	$prev_form = "";
	$form_key = array();
	foreach ($dictionary_array['B'] as $row => $this_form) {
		//CHECK FOR BLANK FORM NAMES
		if ($this_form == "") {
			$errors_array[5][] = "<b>B{$row}</b>";
		}
		// Check form names if it has two-byte characters (for Japanese)
		if ($project_language == 'Japanese') {
			if (mb_detect_encoding($this_form) != "ASCII") {
				$errors_array[6][] = "<b>B{$row}</b>";
			}
		}
		// FORM NAMES SHOULD NOT BE LONGER THAN 50 CHARS AND CANNOT BE LONGER THAN 64
		// If the form existed previously and had more than 64 characters, then allow it.
		if (strlen($this_form) > 64 && !isset($Proj->forms[$this_form])) {
			$errors_array[12][] = "<b>$this_form (A{$row})</b>";
		} elseif (strlen($this_form) > 50) {
			$warnings_array[4][] = "<b>$this_form (A{$row})</b>";
		}
		//LOWERCASE LETTERS, NUMBERS, AND UNDERSCORES
		if ($this_form != preg_replace("/[^a-z_0-9]/", "", $this_form) || is_numeric(substr($this_form, 0, 1)) || is_numeric(trim(str_replace(array(" ", "_"), array("", ""), $this_form)))) {
			// Remove illegal characters first
			$dictionary_array['B'][$row] = preg_replace("/[^a-z_0-9]/", "", str_replace(" ", "_", strtolower($this_form)));
			// Remove any double underscores, beginning numerals, and beginning/ending underscores
			while (strpos($dictionary_array['B'][$row], "__") !== false) 	$dictionary_array['B'][$row] = str_replace("__", "_", $dictionary_array['B'][$row]);
			while (substr($dictionary_array['B'][$row], 0, 1) == "_") 		$dictionary_array['B'][$row] = substr($dictionary_array['B'][$row], 1);
			while (substr($dictionary_array['B'][$row], -1) == "_") 		$dictionary_array['B'][$row] = substr($dictionary_array['B'][$row], 0, -1);
			while (is_numeric(substr($dictionary_array['B'][$row], 0, 1))) 	$dictionary_array['B'][$row] = substr($dictionary_array['B'][$row], 1);
			while (substr($dictionary_array['B'][$row], 0, 1) == "_") 		$dictionary_array['B'][$row] = substr($dictionary_array['B'][$row], 1);
			// Cannot begin with numeral
			if (is_numeric(substr($dictionary_array['B'][$row], 0, 1)) || $dictionary_array['B'][$row] == "") {
				$dictionary_array['B'][$row] = substr(preg_replace("/[0-9]/", "", md5($dictionary_array['B'][$row])), 0, 4) . $dictionary_array['B'][$row];
			}
			// Set warning flag
			$warnings_array[2][] = "<b>$this_form (B{$row})</b> {$lang['database_mods_15']} <b>" . $dictionary_array['B'][$row] . "</b>";
			$this_form = $dictionary_array['B'][$row];
		}
		//FORMS MUST BE SEQUENTIAL
		if ($prev_form != "" && $prev_form != $this_form && isset($form_key[$this_form])) {
			$errors_array[8][] = "<b>$this_form (B{$row})</b>";
		}		
		//Collect form names as unique
		$form_key[$this_form] = "";
		//Set for next loop	
		$prev_form = $this_form;	
	}
	
	if (isset($errors_array[5])) {		
		$errors_array[5] = $lang['database_mods_26'] . implode(", ", $errors_array[5]) . ".";
	}
	if (isset($errors_array[6])) {		
		$errors_array[6] = $lang['database_mods_27'] . implode(", ", $errors_array[6]) . ".";
	}
	if (isset($warnings_array[2])) {		
		$warnings_array[2] = "{$lang['database_mods_28']}<br>" . implode("<br>", $warnings_array[2]);
	}
	if (isset($errors_array[8])) {		
		$errors_array[8] = "{$lang['database_mods_29']}<br>" . implode("<br>", $errors_array[8]);
	}
	if (isset($warnings_array[4])) {		
		$warnings_array[4] = "{$lang['database_mods_30']}<br>" . implode("<br>", $warnings_array[4]);
	}
	if (isset($errors_array[12])) {		
		$errors_array[12] = "{$lang['database_mods_31']}<br>" . implode("<br>", $errors_array[12]);
	}
	if (isset($errors_array[23])) {		
		// Loop through possible matches for form_name+"_complete"
		foreach ($errors_array[23] as $this_row=>$form_maybe) {
			if (isset($form_key[$form_maybe])) {
				$errors_array[24][] = "<b>{$form_maybe}_complete (A{$this_row})</b>";
			}
		}
		unset($errors_array[23]);
	}
	if (isset($errors_array[24])) {	
		$errors_array[24] = "{$lang['database_mods_32']}<br>" . implode("<br>", $errors_array[24]);	
	}
	
	// Create array of Form Status field names (to use in allowing in calc fields and branching logic)
	$formStatusFields = array();
	foreach (array_unique($dictionary_array['B']) as $this_form) {
		$formStatusFields[] = $this_form . "_complete";
	}
	
	// MAKE SURE FIELD #1 IS A TEXT FIELD
	if ($dictionary_array['D'][2] != "text") {
		$warnings_array[16][] = "<b>D2</b>";
		$dictionary_array['D'][2] = $this_field_type = "text";
		$warnings_array[16] = $lang['database_mods_109'] . " " . implode(", ", $warnings_array[16]) . ".";
	}

	## FIELD TYPES AND CHOICES/CALCULATIONS
	$types = array("text", "notes", "radio", "dropdown", "calc", "file", "sql", "advcheckbox", "checkbox", "yesno", "truefalse", "descriptive", "attachment", "slider");
	$types_no_easter_eggs = array("text", "notes", "radio", "dropdown", "calc", "file", "checkbox", "yesno", "truefalse", "descriptive", "attachment", "slider");
	$legacy_types = array("textarea", "select");
	foreach ($dictionary_array['D'] as $row => $this_field_type) 
	{
		// CHECK FOR BLANK FIELD TYPES
		if ($this_field_type == "") {
			$errors_array[13][] = "<b>D{$row}</b>";
		}
		// ENSURE CERTAIN FIELDS DO NOT HAVE A "SELECT CHOICE" VALUE
		if (in_array($this_field_type, array("text", "textarea", "notes", "file", "yesno", "truefalse", "descriptive", "attachment"))) {
			$dictionary_array['F'][$row] = "";
		}
		// CHECK IF VALID FIELD TYPE
		if (in_array($this_field_type, $legacy_types)) {
			//Allow legacy values for field types (and reformat to new equivalents)
			if ($this_field_type == "textarea") {
				$dictionary_array['D'][$row] = $this_field_type = "notes";
			} elseif ($this_field_type == "select") {
				$dictionary_array['D'][$row] = $this_field_type = "dropdown";
			}
		} elseif (!in_array($this_field_type, $types)) {
			// Not a valid field type
			$errors_array[9][] = "<b>$this_field_type (D{$row})</b>";
		} elseif ($this_field_type == "calc") {
			// Make sure calc fields have an equation
			if ($dictionary_array['F'][$row] == "") {
				$errors_array[14][] = "<b>F{$row}</b>";
			// Do simple check to see if there are basic errors in calc field equation
			} elseif (substr_count($dictionary_array['F'][$row], "(") != substr_count($dictionary_array['F'][$row], ")") || substr_count($dictionary_array['F'][$row], "[") != substr_count($dictionary_array['F'][$row], "]")) {
				$errors_array[15][] = "<b>F{$row}</b>"
									. substr_count($dictionary_array['F'][$row], "(")."=".substr_count($dictionary_array['F'][$row], ")");
			// Check to make sure there are no spaces or illegal characters within square brackets in calc field equation
			} else {
				$calc_preg = cleanBranchingOrCalc($dictionary_array['F'][$row]);
				if ($dictionary_array['F'][$row] != $calc_preg) {
					$warnings_array[10][] = "<b>{$dictionary_array['F'][$row]} (F{$row})</b> was replaced with <b>$calc_preg</b>";
				}
				$dictionary_array['F'][$row] = $calc_preg;
			}
			// Check to make sure all variables within square brackets are real variables in column A (also allow Form Status fields, which won't be in column A)
			$calcFields = getBracketedFields($dictionary_array['F'][$row], true, true, true);
			foreach (array_keys($calcFields) as $this_field) 
			{
				if (!in_array($this_field, $dictionary_array['A']) && !in_array($this_field, $formStatusFields)) {
					$errors_array[21][] = "<b>$this_field (F{$row})</b>";
				}
			}			
			// Check the equation for illegal functions and syntax errors
			$parser = new LogicParser();
			try {
				$parser->parse($dictionary_array['F'][$row]);
			} catch (LogicException $e) {
				if (count($parser->illegalFunctionsAttempted) !== 0) {
					// Contains illegal functions
					if (SUPER_USER) {
						// For super users, only warn them but allow it
						$warnings_array[23][] = "<b>\"".implode("()\", \"", $parser->illegalFunctionsAttempted)."()\" (F{$row})</b>";
					} else {
						// For normal users, do not allow it (unless the equation is the same as before)
						$old_calc_eqn = ($status == 0 ? $Proj->metadata[$dictionary_array['A'][$row]]['element_enum'] : $Proj->metadata_temp[$dictionary_array['A'][$row]]['element_enum']);
						if ($dictionary_array['F'][$row] == trim(label_decode($old_calc_eqn))) {
							$warnings_array[25][] = "<b>{$dictionary_array['A'][$row]} (F{$row})</b>";
						} else {
							$errors_array[37][] = "<b>\"".implode("()\", \"", $parser->illegalFunctionsAttempted)."()\" (F{$row})</b>";
						}
					}
				} else {
					// Contains invalid syntax
					if (SUPER_USER) {
						// For super users, only warn them but allow it
						$warnings_array[24][] = "<b>{$dictionary_array['A'][$row]} (F{$row})</b>";
					} else {
						// For normal users, do not allow it (unless the equation is the same as before)
						$old_calc_eqn = ($status == 0 ? $Proj->metadata[$dictionary_array['A'][$row]]['element_enum'] : $Proj->metadata_temp[$dictionary_array['A'][$row]]['element_enum']);
						if ($dictionary_array['F'][$row] == trim(label_decode($old_calc_eqn))) {
							$warnings_array[25][] = "<b>{$dictionary_array['A'][$row]} (F{$row})</b>";
						} else {
							$errors_array[38][] = "<b>{$dictionary_array['A'][$row]} (F{$row})</b>";
						}
					}
				}
			}
		// Automatically add choices for advcheckbox
		} elseif ($this_field_type == "advcheckbox") {
			$dictionary_array['F'][$row] = "0, Unchecked \\n 1, Checked";
		// "sql" field types can ONLY be added or edited by Super Users
		} elseif ($this_field_type == "sql") {
			// If user is not super user, then check to make sure that sql field is not new, or if exists, that it is not being changed
			if (!SUPER_USER) {
				// First check if field name already exists and is currently an "sql" field
				$sql = "select element_enum from $metadata_table where project_id = " . PROJECT_ID . " and element_type = 'sql' 
						and field_name = '" . $dictionary_array['A'][$row] . "' limit 1";
				$q = db_query($sql);
				if (db_num_rows($q) < 1) {
					// SQL field does not exist and thus cannot be added
					$errors_array[28][] = "<b>" . $dictionary_array['A'][$row] . " (Row {$row})</b>";
				} else {
					// Field exists and is being edited
					$this_existing_sql = html_entity_decode(trim(db_result($q, 0)), ENT_QUOTES);
					$this_new_sql = html_entity_decode(trim($dictionary_array['F'][$row]), ENT_QUOTES);
					if ($this_existing_sql != $this_new_sql) {
						// SQL field exists, and user is attempting to modify it
						$errors_array[29][] = "<b>" . $dictionary_array['A'][$row] . " (Row {$row})</b>";
					}
				}
			}
		}
		// Make sure multiple choice fields have some choices
		if (in_array($this_field_type, array("select", "radio", "dropdown", "checkbox", "advcheckbox", "sql")) && $dictionary_array['F'][$row] == "") {
			$errors_array[18][] = "<b>F{$row}</b>";
		// Make sure multiple choice fields do not have any options coded with same value
		} elseif (in_array($this_field_type, array("select", "radio", "dropdown", "checkbox")) && $dictionary_array['F'][$row] != "") {
			// Count original choices, then compare to count after parsing each and allocating each choice as a unique key
			$select_array = explode("|", $dictionary_array['F'][$row]);
			$choice_count_start = count($select_array);
			$choice_count_array = array();
			foreach ($select_array as $key=>$value) {
				// Get coded value
				$value = (strpos($value,",") !== false) ? trim(substr($value,0,strpos($value, ","))) : trim($value);
				// Add to array for checking of duplications
				$choice_count_array[$value] = "";
				// Check to make sure that MC fields don't have illegal characters in their raw coded value
				if (!is_numeric($value) && !preg_match("/^([a-zA-Z0-9_]+)$/", $value))
				{
					// If not numeric and also not a valid non-numeric alpha-num, then give error (provide suggestions for replacing)
					$coded_preg = preg_replace("/[^a-zA-Z0-9_]/", "", str_replace(" ", "_", $value));
					if ($coded_preg != $value) {
						if ($coded_preg == "") $coded_preg = "{number value}";
						$errors_array[27][] = "<b>\"$value\"</b> (F{$row})</b> - {$lang['database_mods_33']} <b>\"$coded_preg\"</b>";
					}
				}
			}
			$choice_count_end = count($choice_count_array);
			if ($choice_count_end < $choice_count_start) {
				// Add error flag if a coded value is duplicated
				$errors_array[25][] = "<b>F{$row}</b>";
			}
		}
	}
	if (isset($errors_array[9])) {		
		$errors_array[9] = "{$lang['database_mods_34']} <font color='#800000'><u>" . implode("</u>, <u>", $types_no_easter_eggs) . 
							"</u></font> {$lang['database_mods_35']}<br>" . implode("<br>", $errors_array[9]);
	}
	if (isset($errors_array[13])) {		
		$errors_array[13] = $lang['database_mods_36'] . implode(", ", $errors_array[13]) . ".";
	}
	if (isset($errors_array[14])) {		
		$errors_array[14] = $lang['database_mods_37'] . implode(", ", $errors_array[14]);
	}
	if (isset($errors_array[15])) {		
		$errors_array[15] = $lang['database_mods_38'] . implode(", ", $errors_array[15]);
	}
	if (isset($errors_array[18])) {		
		$errors_array[18] = $lang['database_mods_39'] . implode(", ", $errors_array[18]);
	}
	if (isset($errors_array[25])) {		
		$errors_array[25] = $lang['database_mods_40'] . implode(", ", $errors_array[25]);
	}
	if (isset($errors_array[28])) {		
		$errors_array[28] = "{$lang['database_mods_41']}<br>" . implode("<br>", $errors_array[28]);
	}
	if (isset($errors_array[29])) {		
		$errors_array[29] = "{$lang['database_mods_42']}<br>" . implode("<br>", $errors_array[29]);
	}
	if (isset($errors_array[27])) {		
		$errors_array[27] = "{$lang['database_mods_154']} <br>" . implode("<br>", $errors_array[27]);
	}
	if (isset($warnings_array[10])) {		
		$warnings_array[10] = "{$lang['database_mods_44']}<br>" . implode("<br>", $warnings_array[10]);
	}
	if (isset($errors_array[21])) {		
		$errors_array[21] = "{$lang['database_mods_45']}<br>" . implode("<br>", $errors_array[21]);
	}
	if (isset($errors_array[37])) {		
		$errors_array[37] = "{$lang['design_447']}<br>" . implode("<br>", $errors_array[37]);
	}
	if (isset($warnings_array[23])) {		
		$warnings_array[23] = "{$lang['design_448']}<br>" . implode("<br>", $warnings_array[23]);
	}
	if (isset($errors_array[38])) {		
		$errors_array[38] = "{$lang['design_449']}<br>" . implode("<br>", $errors_array[38]);
	}
	if (isset($warnings_array[24])) {		
		$warnings_array[24] = "{$lang['design_450']}<br>" . implode("<br>", $warnings_array[24]);
	}
	if (isset($warnings_array[25])) {		
		$warnings_array[25] = "{$lang['design_451']}<br>" . implode("<br>", $warnings_array[25]);
	}
	
	## FIELD LABELS
	foreach ($dictionary_array['E'] as $row => $this_field_label) {
		//CHECK FOR BLANK FIELD LABELS
		if ($this_field_label == "") {
			$warnings_array[5][] = "<b>E{$row}</b>";
		}
	}
	if (isset($warnings_array[5])) {		
		$warnings_array[5] = $lang['database_mods_46'] . implode(", ", $warnings_array[5]) . ".";
	}
	
	## CHOICES OR CALCULATIONS
	foreach ($dictionary_array['F'] as $row => $this_field_choices) 
	{
		if ($this_field_choices != "") 
		{
			//CHECK FOR | OR \n (don't warn for checkboxes because it may be useful to only have a single checkbox option)
			if ($dictionary_array['D'][$row] != "checkbox" && $dictionary_array['D'][$row] != "slider" && $dictionary_array['D'][$row] != "sql" && $dictionary_array['D'][$row] != "calc" && strpos($this_field_choices, "|") === false && 
				strpos($this_field_choices, "\\n") === false && strpos($this_field_choices, "\n") === false) 
			{
				$warnings_array[6][] = "<b>F{$row}</b>";
			}
			/* COMMENT OUT BECAUSE OF ISSUES (TOO MANY FALSE POSITIVES)
			// CHECK if any choices need to be auto-coded (if no raw value provided). If so, display warning
			$reformatted_field_choices = str_replace("\\n", "|", autoCodeEnum(str_replace("|", "\n", $this_field_choices)));
			$autoCodeExcludeFieldTypes = array("slider", "sql", "calc");
			if (!in_array($dictionary_array['D'][$row], $autoCodeExcludeFieldTypes) && $this_field_choices != $reformatted_field_choices) 
			{
				$warnings_array[17][] = "<b>F{$row}</b><br><b style='color:#800000;'>{$lang['database_mods_116']}</b> $this_field_choices<br>
										 <b style='color:#800000;'>{$lang['database_mods_117']}</b> $reformatted_field_choices";
				// Fix
				$dictionary_array['F'][$row] = $reformatted_field_choices;
			}
			*/
		}
	}
	if (isset($warnings_array[6])) {		
		$warnings_array[6] = $lang['database_mods_47'] . implode(", ", $warnings_array[6]) . ".";
	}
	if (isset($warnings_array[17])) {		
		$warnings_array[17] = $lang['database_mods_115'] . "<br><br>" . implode("<br><br>", $warnings_array[17]);
	}
	
	## VALIDATION TYPES
	$val_types = array("date", "datetime", "datetime_seconds", "int", "float"); // seed array with legacy values
	$visible_val_types = array();
	foreach (getValTypes() as $valType=>$valAttr) {
		$val_types[] = $valType;
		// Differentiate between exposed validation types and hidden ones (i.e Easter Eggs)
		if ($valAttr['visible']) {
			$visible_val_types[] = $valType;
		}
	}
	foreach ($dictionary_array['H'] as $row => $this_val_type) {
		if ($this_val_type != "") {			
			// CHECK IF A TEXT OR SLIDER FIELD
			if ($dictionary_array['D'][$row] != "text" && $dictionary_array['D'][$row] != "slider") 
			{
				$errors_array[17][] = "<b>H{$row}</b>";
			}
			elseif ($dictionary_array['D'][$row] == "text") 
			{
				// IF USING DATE VALIDATION, REFORMAT MIN/MAX RANGE FROM DD/MM/YYYY TO MM/DD/YYYY
				// Datetime and Datetime w/ seconds formats
				if (substr($this_val_type, 0, 8) == "datetime") {
					if ($dictionary_array['I'][$row] != "" && strpos($dictionary_array['I'][$row], "/")) {
						list ($thisdate, $thistime) = explode(" ", $dictionary_array['I'][$row], 2);
						list ($mm, $dd, $yyyy) = explode("/", $thisdate);
						$mm = sprintf("%02d", $mm); 
						$dd = sprintf("%02d", $dd);
						if (substr($this_val_type, 0, 16) == "datetime_seconds") {
							if (strlen($thistime) <= 5 && strpos($thistime, ":") !== false) {
								// If Excel cut off the seconds from the end of the time component, append ":00"
								$thistime .= ":00";
							}
							if (strlen($thistime) < 8 && strpos($thistime, ":") !== false) {							
								// Add leading zeroes where needed for time
								$thistime = "0".$thistime;
							}
						} else {
							if (strlen($thistime) < 5) $thistime = "0".$thistime;
						}
						$dictionary_array['I'][$row] = "$yyyy-$mm-$dd $thistime";
					}
					if ($dictionary_array['J'][$row] != "" && strpos($dictionary_array['J'][$row], "/")) {
						list ($thisdate, $thistime) = explode(" ", $dictionary_array['J'][$row], 2);
						list ($mm, $dd, $yyyy) = explode("/", $thisdate);
						$mm = sprintf("%02d", $mm); 
						$dd = sprintf("%02d", $dd);
						if (substr($this_val_type, 0, 16) == "datetime_seconds") {
							if (strlen($thistime) <= 5 && strpos($thistime, ":") !== false) {
								// If Excel cut off the seconds from the end of the time component, append ":00"
								$thistime .= ":00";
							}
							if (strlen($thistime) < 8 && strpos($thistime, ":") !== false) {							
								// Add leading zeroes where needed for time
								$thistime = "0".$thistime;
							}
						} else {
							if (strlen($thistime) < 5) $thistime = "0".$thistime;
						}
						$dictionary_array['J'][$row] = "$yyyy-$mm-$dd $thistime";
					}
				}
				// Date formats
				elseif (substr($this_val_type, 0, 4) == "date") {
					if ($dictionary_array['I'][$row] != "" && strpos($dictionary_array['I'][$row], "/")) {
						list ($mm, $dd, $yyyy) = explode("/", $dictionary_array['I'][$row]);
						$mm = sprintf("%02d", $mm); 
						$dd = sprintf("%02d", $dd); 
						$dictionary_array['I'][$row] = "$yyyy-$mm-$dd";
					}
					if ($dictionary_array['J'][$row] != "" && strpos($dictionary_array['J'][$row], "/")) {
						list ($mm, $dd, $yyyy) = explode("/", $dictionary_array['J'][$row]);
						$mm = sprintf("%02d", $mm); 
						$dd = sprintf("%02d", $dd); 
						$dictionary_array['J'][$row] = "$yyyy-$mm-$dd";			
					}
				} 
				// Time
				elseif ($this_val_type == "time") {
					if ($dictionary_array['I'][$row] != "" && strpos($dictionary_array['I'][$row], ":")) {
						if (strlen($dictionary_array['I'][$row]) < 5) $dictionary_array['I'][$row] = "0".$dictionary_array['I'][$row];
					}
					if ($dictionary_array['J'][$row] != "" && strpos($dictionary_array['J'][$row], ":")) {
						if (strlen($dictionary_array['J'][$row]) < 5) $dictionary_array['J'][$row] = "0".$dictionary_array['J'][$row];
					}
				}
				// LOWERCASE LETTERS
				if ($this_val_type != strtolower($this_val_type)) {
					$warnings_array[7][] = "<b>$this_val_type (H{$row})</b> {$lang['database_mods_15']} <b>" . strtolower($this_val_type) . "</b>";
					$dictionary_array['H'][$row] = $this_val_type = strtolower($this_val_type);
				}
				// CHECK IF VALID VALIDATION TYPE
				if (in_array($this_val_type, $val_types)) {
					// Allow non-legacy values for validation types (and reformat to new equivalents)
					if ($this_val_type == "int") {
						$dictionary_array['H'][$row] = $this_val_type = "integer";
					} elseif ($this_val_type == "float") {
						$dictionary_array['H'][$row] = $this_val_type = "number";
					} elseif ($this_val_type == "date") {
						$dictionary_array['H'][$row] = $this_val_type = "date_ymd";
					} elseif ($this_val_type == "datetime") {
						$dictionary_array['H'][$row] = $this_val_type = "datetime_ymd";
					} elseif ($this_val_type == "datetime_seconds") {
						$dictionary_array['H'][$row] = $this_val_type = "datetime_seconds_ymd";
					}
				} else {
					// Not a valid validation type
					$errors_array[16][] = "<b>$this_val_type (H{$row})</b>";
				}
			}
		}
	}
	if (isset($errors_array[16])) {			
		$errors_array[16] = "{$lang['database_mods_48']} <font color='#800000'><u>" . implode("</u>, <u>", $visible_val_types) . 
							"</u></font> {$lang['database_mods_49']}<br>" . implode("<br>", $errors_array[16]);
	}
	if (isset($errors_array[17])) {			
		$errors_array[17] = $lang['database_mods_50'] . implode(", ", $errors_array[17]);
	}
	if (isset($warnings_array[7])) {		
		$warnings_array[7] = "{$lang['database_mods_51']}<br>" . implode("<br>", $warnings_array[7]);
	}
	
	## IDENTIFIERS
	foreach ($dictionary_array['K'] as $row => $this_identifier) {
		if ($this_identifier != "") {
			if (trim(strtolower($this_identifier)) != "y") {
				$warnings_array[8][] = "<b>$this_identifier (K{$row})</b>";
				$dictionary_array['K'][$row] = $this_identifier = trim(strtolower($this_identifier));
			}
		}
	}
	if (isset($warnings_array[8])) {			
		$warnings_array[8] = "{$lang['database_mods_52']}<br>" . implode("<br>", $warnings_array[8]);
	}
	
	## BRANCHING LOGIC
	foreach ($dictionary_array['L'] as $row => $this_branching) {
		if ($this_branching != "") {
			// Check for any stray spaces
			if (trim($this_branching) == "") {
				$this_branching = $dictionary_array['L'][$row] = trim($this_branching);
				continue;
			}
			// Do simple check to see if there are basic errors in branching
			if (substr_count($this_branching, "(") != substr_count($this_branching, ")") 
				|| substr_count($this_branching, "[") != substr_count($this_branching, "]") 
				|| substr_count($this_branching, "'")%2 != 0
				|| substr_count($this_branching, "\"")%2 != 0
				) {
				$errors_array[19][] = "<b>L{$row}</b>";
			// Check to make sure there are no spaces or illegal characters within square brackets in logic
			} else {
				$branch_preg = cleanBranchingOrCalc($this_branching);
				if ($this_branching != $branch_preg) {
					$warnings_array[11][] = "<b>{$dictionary_array['L'][$row]} (L{$row})</b> {$lang['database_mods_15']} <b>$branch_preg</b>";
				}
				$dictionary_array['L'][$row] = $this_branching = $branch_preg;
			}
			// Check to make sure all variables within square brackets are real variables in column A (or if they are a Form Status field)
			// If any fieldnames have parenthesis inside brackets (used for checkbox logic), then strip out parethesis
			$branchFields = array_keys(getBracketedFields(cleanBranchingOrCalc($this_branching), true, true, true));
			foreach ($branchFields as $this_field) {
				if (!in_array($this_field, $dictionary_array['A']) && !in_array($this_field, $formStatusFields)) {
					$errors_array[20][] = "<b>$this_field (L{$row})</b>";
				}
			}
			// Check the logic for illegal functions
			$parser = new LogicParser();
			try {
				$parser->parse($this_branching);
			} catch (LogicException $e) {
				if (count($parser->illegalFunctionsAttempted) !== 0) {
					// Contains illegal functions
					if (SUPER_USER) {
						// For super users, only warn them but allow it
						$warnings_array[20][] = "<b>\"".implode("()\", \"", $parser->illegalFunctionsAttempted)."()\" (L{$row})</b>";
					} else {
						// For normal users, do not allow it (unless the branching is the same as before)
						$old_branching = ($status == 0 ? $Proj->metadata[$dictionary_array['A'][$row]]['branching_logic'] : $Proj->metadata_temp[$dictionary_array['A'][$row]]['branching_logic']);
						if ($this_branching == trim(label_decode($old_branching))) {
							$warnings_array[22][] = "<b>{$dictionary_array['A'][$row]} (L{$row})</b>";
						} else {
							$errors_array[35][] = "<b>\"".implode("()\", \"", $parser->illegalFunctionsAttempted)."()\" (L{$row})</b>";
						}
					}
				} else {
					// Contains invalid syntax
					if (SUPER_USER) {
						// For super users, only warn them but allow it
						$warnings_array[21][] = "<b>{$dictionary_array['A'][$row]} (L{$row})</b>";
					} else {
						// For normal users, do not allow it (unless the branching is the same as before)
						$old_branching = ($status == 0 ? $Proj->metadata[$dictionary_array['A'][$row]]['branching_logic'] : $Proj->metadata_temp[$dictionary_array['A'][$row]]['branching_logic']);
						if ($this_branching == trim(label_decode($old_branching))) {
							$warnings_array[22][] = "<b>{$dictionary_array['A'][$row]} (L{$row})</b>";
						} else {
							$errors_array[36][] = "<b>{$dictionary_array['A'][$row]} (L{$row})</b>";
						}
					}
				}
			}
		}
	}
	if (isset($errors_array[19])) {		
		$errors_array[19] = $lang['database_mods_53'] . implode(", ", $errors_array[19]);
	}
	if (isset($warnings_array[11])) {		
		$warnings_array[11] = "{$lang['database_mods_54']}<br>" . implode("<br>", $warnings_array[11]);
	}
	if (isset($errors_array[20])) {		
		$errors_array[20] = "{$lang['database_mods_55']}<br>" . implode("<br>", $errors_array[20]);
	}
	if (isset($errors_array[35])) {		
		$errors_array[35] = "{$lang['design_442']}<br>" . implode("<br>", $errors_array[35]);
	}
	if (isset($warnings_array[20])) {		
		$warnings_array[20] = "{$lang['design_443']}<br>" . implode("<br>", $warnings_array[20]);
	}
	if (isset($errors_array[36])) {		
		$errors_array[36] = "{$lang['design_444']}<br>" . implode("<br>", $errors_array[36]);
	}
	if (isset($warnings_array[21])) {		
		$warnings_array[21] = "{$lang['design_445']}<br>" . implode("<br>", $warnings_array[21]);
	}
	if (isset($warnings_array[22])) {		
		$warnings_array[22] = "{$lang['design_446']}<br>" . implode("<br>", $warnings_array[22]);
	}
	
	## REQUIRED FIELDS
	foreach ($dictionary_array['M'] as $row => $this_req_field) {
		if ($this_req_field != "") {
			// If illegal formatting for "y"
			if (trim(strtolower($this_req_field)) != "y") {
				$warnings_array[9][] = "<b>$this_req_field (M{$row})</b>";
				$dictionary_array['M'][$row] = $this_req_field = trim(strtolower($this_req_field));
			// Make sure advcheckbox and descriptive fields are not "required" (since "unchecked" is technically a real value)
			} elseif ($dictionary_array['D'][$row] == "descriptive" || $dictionary_array['D'][$row] == "advcheckbox") {
				$dictionary_array['M'][$row] = $this_req_field = "";
				$warnings_array[12][] = "<b>F{$row} {$lang['database_mods_56']} \"{$dictionary_array['A'][$row]}\"</b>";
			}
		}
	}
	if (isset($warnings_array[9])) {			
		$warnings_array[9] = "{$lang['database_mods_57']}<br>" . implode("<br>", $warnings_array[9]);
	}
	if (isset($warnings_array[12])) {			
		$warnings_array[12] = "{$lang['database_mods_128']}<br>" . implode("<br>", $warnings_array[12]);
	}
	
	## CUSTOM ALIGNMENT
	foreach ($dictionary_array['N'] as $row => $this_align) {
		if ($this_align != "") {
			// Allowable alignments
			$align_options = array('LV', 'LH', 'RV', 'RH');
			// If illegal formatting, then warn and set to blank (default)
			if (!in_array($this_align, $align_options)) {
				$warnings_array[15][] = "<b>$this_align (N{$row})</b>";
				$dictionary_array['N'][$row] = "";
			}
		}
	}
	if (isset($warnings_array[15])) {			
		$warnings_array[15] = $lang['database_mods_106'] . " '" . implode("', '", $align_options) . "'" . $lang['period'] 
							. " " . $lang['database_mods_107'] . " " . implode(", ", $warnings_array[15]);
	}

	
	## MATRIX GROUP NAMES
	$prev_group = "";
	$group_key = array();
	$group_enum_check = array();
	$group_fieldtype_check = array();
	foreach ($dictionary_array['P'] as $row => $this_group) 
	{
		// Trim it
		$this_group = trim($this_group);
		//GROUP NAMES SHOULD NOT BE LONGER THAN 60 CHARS
		if (strlen($this_group) > 60) {
			$errors_array[31][] = "<b>$this_group (A{$row})</b>";
		}
		//LOWERCASE LETTERS, NUMBERS, AND UNDERSCORES
		if ($this_group != preg_replace("/[^a-z_0-9]/", "", $this_group)) {
			// Remove illegal characters first
			$dictionary_array['P'][$row] = preg_replace("/[^a-z_0-9]/", "", str_replace(" ", "_", strtolower($this_group)));
			// Remove any double underscores, beginning numerals, and beginning/ending underscores
			while (strpos($dictionary_array['P'][$row], "__") !== false) 	$dictionary_array['P'][$row] = str_replace("__", "_", $dictionary_array['P'][$row]);
			while (substr($dictionary_array['P'][$row], 0, 1) == "_") 		$dictionary_array['P'][$row] = substr($dictionary_array['P'][$row], 1);
			while (substr($dictionary_array['P'][$row], -1) == "_") 		$dictionary_array['P'][$row] = substr($dictionary_array['P'][$row], 0, -1);
			// Set warning flag
			$warnings_array[18][] = "<b>$this_group (P{$row})</b> {$lang['database_mods_15']} <b>" . $dictionary_array['P'][$row] . "</b>";
			$this_group = $dictionary_array['P'][$row];
		}
		//GROUPS MUST BE SEQUENTIAL
		if ($prev_group != $this_group && isset($group_key[$this_group])) {
			$errors_array[32][] = "<b>$this_group (P{$row})</b>";
		}
		if ($this_group != '') {
			// Collect form names as unique
			$group_key[$this_group] = "";
			// Make sure only checkboxes/radios are in a matrix group
			if ($dictionary_array['D'][$row] != 'radio' && $dictionary_array['D'][$row] != 'checkbox') {
				$errors_array[34][] = "<b>{$dictionary_array['D'][$row]} (D{$row})</b>";
			} else {
				// Make sure all fields in a single matrix group are either all radios or all checkboxes
				if (isset($group_fieldtype_check[$this_group])) {
					if ($group_fieldtype_check[$this_group] != $dictionary_array['D'][$row]) {
						$warnings_array[19][] = "<b>{$dictionary_array['D'][$row]} (D{$row})</b> {$lang['database_mods_127']} <b>{$group_fieldtype_check[$this_group]}</b>";
						// Change the field type
						$dictionary_array['D'][$row] = $group_fieldtype_check[$this_group];
					}
				} else {
					// Add field type to array to track later
					$group_fieldtype_check[$this_group] = $dictionary_array['D'][$row];
				}
				// Collect matrix group's choices as unique to make sure all field in a matrix have same choices
				if (isset($group_enum_check[$this_group])) {
					// Check to see if has same choices for this group
					if ($group_enum_check[$this_group] !== parseEnum(str_replace("|", "\\n", $dictionary_array['F'][$row]))) {
						// Convert back to DD format choice string
						$choices_string = array();
						foreach ($group_enum_check[$this_group] as $code=>$label) {
							$choices_string[] = "$code, $label";
						}
						$errors_array[33][] = "<b>F{$row}</b> - {$lang['database_mods_124']} <b>".implode(" | ", $choices_string)."</b>";
					}
				} else {
					// First field in group, so add to array
					$group_enum_check[$this_group] = parseEnum(str_replace("|", "\\n", $dictionary_array['F'][$row]));
				}
			}
		}
		//Set for next loop	
		$prev_group = $this_group;	
	}
	if (isset($warnings_array[18])) {			
		$warnings_array[18] = "{$lang['database_mods_120']}<br>" . implode("<br>", $warnings_array[18]);
	}
	if (isset($warnings_array[19])) {			
		$warnings_array[19] = "{$lang['database_mods_126']}<br>" . implode("<br>", $warnings_array[19]);
	}
	if (isset($errors_array[31])) {			
		$errors_array[31] = "{$lang['database_mods_121']}<br>" . implode("<br>", $errors_array[31]);
	}
	if (isset($errors_array[32])) {			
		$errors_array[32] = "{$lang['database_mods_122']}<br>" . implode("<br>", $errors_array[32]);
	}
	if (isset($errors_array[33])) {			
		$errors_array[33] = "{$lang['database_mods_123']}<br>" . implode("<br>", $errors_array[33]);
	}
	if (isset($errors_array[34])) {			
		$errors_array[34] = "{$lang['database_mods_125']}<br>" . implode("<br>", $errors_array[34]);
	}
	
	// Return the cleaned data dictionary and any errors or warnings
	return array($errors_array, $warnings_array, $dictionary_array);
}


/**
 * RENDER DATA DICTIONARY ERRORS
 */
function renderErrors($errors_array) {
	global $lang;
	
	print 	"<div class='red' style='margin-top:10px;'>
				<img src='".APP_PATH_IMAGES."exclamation.png' class='imgfix'> 
				<b>{$lang['database_mods_59']}</b><br><br>
				<p style='border-bottom:1px solid #aaa;font-weight:bold;font-size:16px;color:#800000;'>{$lang['database_mods_60']}</p>
				<p>" . implode("</p><p style='padding-top:5px;border-top:1px solid #aaa;'>", $errors_array) . "</p>
			</div>";
}


/**
 * RENDER DATA DICTIONARY WARNINGS
 */
function renderWarnings($warnings_array) {
	global $lang;
	
	//Display warnings
	if (count($warnings_array) > 0) {
		print "<div class='yellow' style='margin-top:15px;'>";
		print "<p style='border-bottom:1px solid #aaa;font-weight:bold;font-size:16px;color:#800000;'>{$lang['database_mods_61']}</p>";
		print "<p>" . implode("</p><p style='padding-top:5px;border-top:1px solid #aaa;'>", $warnings_array) . "</p>";
		print "</div>";
	}
}


/**
 * SAVE METADATA TO TABLE
 */
function save_metadata($dictionary_array) 
{
	global $status, $longitudinal, $Proj;
	
	// If project is in production, do not allow instant editing (draft the changes using metadata_temp table instead)
	$metadata_table = ($status > 0) ? "redcap_metadata_temp" : "redcap_metadata";
	
	// DEV ONLY: Only run the following actions (change rights level, events designation) if in Development
	if ($status < 1) 
	{
		// If new forms are being added, give all users "read-write" access to this new form
		$sql = "select distinct form_name from $metadata_table where project_id = " . PROJECT_ID;
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q)) {
			$existing_form_names[] = $row['form_name'];
		}
		$newforms = array();
		foreach (array_unique($dictionary_array['B']) as $new_form) {
			if (!in_array($new_form, $existing_form_names)) {
				//Add rights for EVERY user for this new form
				$newforms[] = $new_form;
				//Add all new forms to redcap_events_forms table
				if (!$longitudinal) {
					$sql = "insert into redcap_events_forms (event_id, form_name) select m.event_id, '$new_form' from redcap_events_arms a, redcap_events_metadata m "
						 . "where a.project_id = " . PROJECT_ID . " and a.arm_id = m.arm_id";
					db_query($sql);
				}
			}
		}
		
		//Add new forms to rights table
		$sql = "update redcap_user_rights set data_entry = concat(data_entry,'[".implode(",1][", $newforms).",1]') where project_id = " . PROJECT_ID;
		db_query($sql);	
		//Also delete form-level user rights for any forms deleted (as clean-up)
		foreach (array_diff($existing_form_names, array_unique($dictionary_array['B'])) as $deleted_form) {
			//Loop through all 3 data_entry rights level states to catch all instances
			for ($i = 0; $i <= 2; $i++) {
				$sql = "update redcap_user_rights set data_entry = replace(data_entry,'[$deleted_form,$i]','') where project_id = " . PROJECT_ID;
				db_query($sql);
			}
			//Delete all instances in redcap_events_forms
			$sql = "delete from redcap_events_forms where event_id in 
					(select m.event_id from redcap_events_arms a, redcap_events_metadata m, redcap_projects p where a.arm_id = m.arm_id 
					and p.project_id = a.project_id and p.project_id = " . PROJECT_ID . ") and form_name = '$deleted_form';";
			db_query($sql);
		}
		
		## CHANGE FOR MULTIPLE SURVEYS????? (Should we ALWAYS assume that if first form is a survey that we should preserve first form as survey?)
		// If using first form as survey and form is renamed in DD, then change form_name in redcap_surveys table to the new form name
		if (isset($Proj->forms[$Proj->firstForm]['survey_id']))
		{
			$columnB = $dictionary_array['B'];
			$newFirstForm = array_shift(array_unique($columnB));
			unset($columnB);
			// Do not rename in table if the new first form is ALSO a survey (assuming it even exists)
			if ($newFirstForm != '' && $Proj->firstForm != $newFirstForm && !isset($Proj->forms[$newFirstForm]['survey_id']))
			{
				// Change form_name of survey to the new first form name
				$sql = "update redcap_surveys set form_name = '$newFirstForm' where survey_id = ".$Proj->forms[$Proj->firstForm]['survey_id'];
				db_query($sql);
			}
		}
	}
	
	// Build array of existing form names and their menu names to try and preserve any existing menu names
	$q = db_query("select form_name, form_menu_description from $metadata_table where project_id = " . PROJECT_ID . " and form_menu_description is not null");
	$existing_form_menus = array();
	while ($row = db_fetch_assoc($q)) {
		$existing_form_menus[$row['form_name']] = $row['form_menu_description'];
	}
	
	// Before wiping out current metadata, obtain values in table not contained in data dictionary to preserve during carryover (e.g. edoc_id)
	$sql = "select field_name, edoc_id, edoc_display_img, stop_actions, field_units from $metadata_table where project_id = " . PROJECT_ID . "
			and (edoc_id is not null or stop_actions is not null or field_units is not null)";
	$q = db_query($sql);
	$extra_values = array();
	while ($row = db_fetch_assoc($q)) 
	{
		if (!empty($row['edoc_id'])) {
			// Preserve edoc values
			$extra_values[$row['field_name']]['edoc_id'] = $row['edoc_id'];
			$extra_values[$row['field_name']]['edoc_display_img'] = $row['edoc_display_img'];
		} 
		if ($row['stop_actions'] != "") {
			// Preserve stop_actions value
			$extra_values[$row['field_name']]['stop_actions'] = $row['stop_actions'];
		} 
		if ($row['field_units'] != "") {
			// Preserve field_units value (no longer included in data dictionary but will be preserved if defined before 4.0)
			$extra_values[$row['field_name']]['field_units'] = $row['field_units'];
		}
	}
	
	// Delete all instances of metadata for this project to clean out before adding new
	db_query("delete from $metadata_table where project_id = " . PROJECT_ID);
	
	// Capture any SQL errors
	$sql_errors = array();	
	// Create array to keep track of form names for building form_menu_description logic
	$form_names = array();	
	// Default field order value
	$field_order = 1;			
	// Set up exchange values for replacing legacy back-end values
	$convertValType = array("integer"=>"int", "number"=>"float");
	$convertFldType = array("notes"=>"textarea", "dropdown"=>"select", "drop-down"=>"select");
	
	// Loop through data dictionary array and save into metadata table
	foreach (array_keys($dictionary_array['A']) as $i)
	{
		// If this is the first field of a form, generate form menu description for upcoming form
		// If form menu description already exists, it may have been customized, so keep old value
		$form_menu = "";
		if (!in_array($dictionary_array['B'][$i], $form_names)) {
			if (isset($existing_form_menus[$dictionary_array['B'][$i]])) {
				// Use existing value if form existed previously
				$form_menu = $existing_form_menus[$dictionary_array['B'][$i]];
			} else {
				// Create menu name on the fly
				$form_menu = ucwords(str_replace("_", " ", $dictionary_array['B'][$i]));
			}
		}
		// Deal with hard/soft validation checktype for text fields
		$valchecktype = ($dictionary_array['D'][$i] == "text") ? "'soft_typed'" : "NULL";
		// Swap out Identifier "y" with "1"		
		$dictionary_array['K'][$i] = (strtolower(trim($dictionary_array['K'][$i])) == "y") ? "'1'" : "NULL";
		// Swap out Required Field "y" with "1"	(else "0")
		$dictionary_array['M'][$i] = (strtolower(trim($dictionary_array['M'][$i])) == "y") ? "'1'" : "'0'";
		// Format multiple choices
		if ($dictionary_array['F'][$i] != "" && $dictionary_array['D'][$i] != "calc" && $dictionary_array['D'][$i] != "slider" && $dictionary_array['D'][$i] != "sql") {
			$dictionary_array['F'][$i] = str_replace(array("|","\n"), array("\\n"," \\n "), $dictionary_array['F'][$i]);
		}
		// Do replacement of front-end values with back-end equivalents
		if (isset($convertFldType[$dictionary_array['D'][$i]])) {
			$dictionary_array['D'][$i] = $convertFldType[$dictionary_array['D'][$i]];
		}		
		if ($dictionary_array['H'][$i] != "" && $dictionary_array['D'][$i] != "slider") {
			// Replace with legacy/back-end values
			if (isset($convertValType[$dictionary_array['H'][$i]])) {
				$dictionary_array['H'][$i] = $convertValType[$dictionary_array['H'][$i]];
			}
		} elseif ($dictionary_array['D'][$i] == "slider" && $dictionary_array['H'][$i] != "" && $dictionary_array['H'][$i] != "number") {
			// Ensure sliders only have validation type of "" or "number" (to display number value or not)
			$dictionary_array['H'][$i] = "";
		}
		// Make sure question_num is 10 characters or less
		if (strlen($dictionary_array['O'][$i]) > 10) $dictionary_array['O'][$i] = substr($dictionary_array['O'][$i], 0, 10);
		// Insert edoc_id and slider display values that should be preserved
		$edoc_id 		  = isset($extra_values[$dictionary_array['A'][$i]]['edoc_id']) ? $extra_values[$dictionary_array['A'][$i]]['edoc_id'] : "NULL";
		$edoc_display_img = isset($extra_values[$dictionary_array['A'][$i]]['edoc_display_img']) ? $extra_values[$dictionary_array['A'][$i]]['edoc_display_img'] : "0";
		$stop_actions 	  = isset($extra_values[$dictionary_array['A'][$i]]['stop_actions']) ? $extra_values[$dictionary_array['A'][$i]]['stop_actions'] : "";
		$field_units	  = isset($extra_values[$dictionary_array['A'][$i]]['field_units']) ? $extra_values[$dictionary_array['A'][$i]]['field_units'] : "";
		
		// Build query for inserting field
		$sql = "insert into $metadata_table (project_id, field_name, form_name, field_units, element_preceding_header, "
			 . "element_type, element_label, element_enum, element_note, element_validation_type, element_validation_min, "
			 . "element_validation_max, field_phi, branching_logic, element_validation_checktype, form_menu_description, "
			 . "field_order, field_req, edoc_id, edoc_display_img, custom_alignment, stop_actions, question_num, grid_name, misc) values (" 
			 . PROJECT_ID . ", " 
			 . checkNull($dictionary_array['A'][$i]) . ", " 
			 . checkNull($dictionary_array['B'][$i]) . ", " 
			 . checkNull($field_units) . ", " 
			 . checkNull($dictionary_array['C'][$i]) . ", " 
			 . checkNull($dictionary_array['D'][$i]) . ", " 
			 . checkNull($dictionary_array['E'][$i]) . ", " 
			 . checkNull($dictionary_array['F'][$i]) . ", " 
			 . checkNull($dictionary_array['G'][$i]) . ", " 
			 . checkNull($dictionary_array['H'][$i]) . ", " 
			 . checkNull($dictionary_array['I'][$i]) . ", " 
			 . checkNull($dictionary_array['J'][$i]) . ", " 
			 . $dictionary_array['K'][$i] . ", " 
			 . checkNull($dictionary_array['L'][$i]) . ", "
			 . "$valchecktype, " 
			 . checkNull($form_menu). ", "
			 . "$field_order, " 
			 . $dictionary_array['M'][$i] . ", "
			 . "$edoc_id, "
			 . "$edoc_display_img, "
			 . checkNull($dictionary_array['N'][$i]) . ", " 
			 . checkNull($stop_actions) . ", " 
			 . checkNull($dictionary_array['O'][$i]) . ", " 
			 . checkNull($dictionary_array['P'][$i]) . ", " 
			 . checkNull('')
			 . ")";
			 
		//Insert into table
		if (db_query($sql)) {
			// Increment field order
			$field_order++;
		} else {
			//Log this error
			$sql_errors[] = $sql;
		}
		
		//Add Form Status field if we're on the last field of a form
		if ($dictionary_array['B'][$i] != $dictionary_array['B'][$i+1]) {
			//Insert new Form Status field
			$sql = "insert into $metadata_table (project_id, field_name, form_name, field_order, element_type, "
				 . "element_label, element_enum, element_preceding_header) values (" . PROJECT_ID . ", " 
				 . "'" . $dictionary_array['B'][$i] . "_complete', '" . $dictionary_array['B'][$i] . "', "
				 . "'$field_order', 'select', 'Complete?', '0, Incomplete \\\\n 1, Unverified \\\\n 2, Complete', 'Form Status')";		
			//Insert into table
			if (db_query($sql)) {
				// Increment field order
				$field_order++;
			} else {
				//Log this error
				$sql_errors[] = $sql;
			}
		}
		
		//Add form name to array for later checking for form_menu_description
		$form_names[] = $dictionary_array['B'][$i];

	}
	
	// Logging
	log_event("",$metadata_table,"MANAGE",PROJECT_ID,"project_id = ".PROJECT_ID,"Upload data dictionary");

	return $sql_errors;
	
}


/**
 * RENDER TABLE TO DISPLAY METADATA CHANGES
 */
function getMetadataDiff() 
{
	global $lang;
	
	$html = "";
	
	// Build arrays with all old values, drafted values, and changes
	$metadata_new = array();
	$metadata_old = array();
	$metadata_changes = array();
	$fieldsCriticalIssues = array(); // Capture fields with critical issues
	
	// Metadata columns that need html decoding
	$metadataDecode = array("element_preceding_header", "element_label", "element_enum", "element_note", "branching_logic", "question_num");
	
	// Get existing field values
	$sql = "select field_name, element_preceding_header, element_type, element_label, element_enum,
			element_note, element_validation_type, element_validation_min, element_validation_max, field_phi, 
			branching_logic, field_req, edoc_id, custom_alignment, stop_actions, question_num, grid_name 
			from redcap_metadata where project_id = " . PROJECT_ID . " order by field_order";
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q)) 
	{
		// Do html decoding for certain fields
		foreach ($metadataDecode as $col)
		{
			$row[$col] = label_decode($row[$col]);
		}
		// Add to array
		$metadata_old[$row['field_name']] = $row;
	}
	
	// Get new field values and store changes in array
	$sql = "select field_name, element_preceding_header, element_type, element_label, element_enum,
			element_note, element_validation_type, element_validation_min, element_validation_max, field_phi, 
			branching_logic, field_req, edoc_id, custom_alignment, stop_actions, question_num, grid_name 
			from redcap_metadata_temp where project_id = " . PROJECT_ID . " order by field_order";
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q)) 
	{
		// Do html decoding for certain fields
		foreach ($metadataDecode as $col)
		{
			$row[$col] = label_decode($row[$col]);
		}
		$metadata_new[$row['field_name']] = $row;
		// Check to see if values are different from existing field. If they are, don't include in new array.
		if (!isset($metadata_old[$row['field_name']]) || $row !== $metadata_old[$row['field_name']]) {
			$metadata_changes[$row['field_name']] = $row;
		}
	}
	
	// Query to find fields with data
	$sql = "select distinct field_name from redcap_data where project_id = ".PROJECT_ID." 
			and field_name in (".prep_implode(array_keys($metadata_changes)).") and value != ''";
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q)) 
	{
		// Add field to array as key
		$fieldsWithData[$row['field_name']] = true;
	}
	
	$html .= "<div id='tableChangesPretext' style='font-weight:bold;padding:30px 4px 8px;'>
				{$lang['database_mods_62']}
			</div>";
	
	// Now loop through changes and new fields and render table
	$html .= "<table id='tableChanges' class='metachanges' border='1' cellspacing='0' cellpadding='10' style='width:100%;border:1px solid gray;font-family:Verdana,Arial;font-size:10px;'>
				<tr style='background-color:#a0a0a0;font-weight:bold;'>
					<td>{$lang['global_44']}</td>
					<td>{$lang['database_mods_65']}</td>
					<td>{$lang['database_mods_66']}</td>
					<td>{$lang['global_40']}</td>
					<td>{$lang['database_mods_68']}</td>
					<td>{$lang['database_mods_69']}</td>
					<td>{$lang['database_mods_70']}</td>
					<td>{$lang['database_mods_71']}</td>
					<td>{$lang['database_mods_72']}</td>
					<td>{$lang['database_mods_73']}</td>
					<td>{$lang['database_mods_74']}</td>
					<td>{$lang['database_mods_75']}</td>
					<td>{$lang['database_mods_105']}</td>
					<td>{$lang['design_212']}</td>
					<td>{$lang['database_mods_108']}</td>
					<td>{$lang['design_221']}</td>
					<td>{$lang['database_mods_132']}</td>
				</tr>";
	// Collect names of fields being modified
	$fieldsModified = array();
	// Render each table row
	foreach ($metadata_changes as $field_name=>$attr) 
	{
		// If a new field, set bgcolor to green, otherwise set as null
		$bgcolor_row = !isset($metadata_old[$field_name]) ? "style='background-color:#7BED7B;'" : "";
		// Begin row
		$html .= "<tr class='notranslate' $bgcolor_row>";
		// Loop through each cell in row
		foreach ($attr as $key=>$value) {
			// Set default bgcolor for cell as null
			$bgcolor = "";
			// Tranform any raw legacy values to user-readable values 
			$value = transformMetaVals($value, $key); 
			// Analyze changes for existing field
			if ($bgcolor_row == "") {
				// Retrieve existing value
				$old_value = $metadata_old[$field_name][$key];
				// Tranform any raw legacy values to user-readable values 
				$old_value = transformMetaVals($old_value, $key);
				// If new and existing values are different...
				if ($old_value != $value) {
					// Set bgcolor as yellow to denote changes
					$bgcolor = "style='background-color:#ffff80;'";
					// Append existing value in gray text
					$value .= "<div style='color:#aaa;'>$old_value</div>";
					// Check if field has data
					$fieldHasData = (isset($fieldsWithData[$field_name]));
					// Add any other info that may be helpful to prevent against data loss and other issues.
					// Allow $fieldHasData to be modified for MC fields that have options deleted where the option has NO data.
					list ($metadataChangeComment, $fieldHasData) = metadataChangeComment($metadata_old[$field_name], $metadata_new[$field_name], $key, $fieldHasData);
					if ($metadataChangeComment != "") {
						$value .= $metadataChangeComment;
						 // If the field has a critical issue AND has data, add to array
						if ($fieldHasData) {
							$fieldsCriticalIssues[] = $field_name;
						}
					}
					// Place field_name in array
					$fieldsModified[$field_name] = true;
				}
			}
			$html .= "<td $bgcolor>$value</td>";
		}
		// Finish row
		$html .= "</tr>";
	}
	// Finish table
	$html .= "</table>";
	$html .= "<br>";
	
	// Count number of changes
	$num_metadata_changes = count($metadata_changes);
	
	// Give message if there are no differences and hide table and other things that don't need to be shown
	if ($num_metadata_changes == 0) {
		// Message to user
		$html .= "<div class='yellow' style='font-weight:bold;font-size:14px;'>
					<img src='" . APP_PATH_IMAGES . "exclamation_orange.png' class='imgfix'>
					{$lang['database_mods_76']}
				</div>";
		//Javascript to hide some elements
		$html .= "<script type='text/javascript'>
				$('#tableChanges').css({'display':'none'});
				$('#metadataCompareKey').css({'display':'none'});
				$('#tableChangesPretext').css({'display':'none'});
				</script>";
	}
	
	$html .= "<br>";	
	
	// If have fields with critical issues, then check data to see if they have data. If no data, remove as critical issue.
	$numCriticalIssues = count($fieldsCriticalIssues);
	
	// Return number of changes and HTML of modifications table
	return array($num_metadata_changes, count($fieldsModified), $numCriticalIssues, $html);
}


/**
 * CHANGE RAW METADATA VALUES INTO USER-READABLE VALUES
 */
 function transformMetaVals($value, $meta_field) {
	// Choose action based upon which metadata field we're on
	switch ($meta_field) {
		// Select Choices / Calculations
		case 'element_enum':
			// For fields with Choices, replace \n with line break for viewing
			$value = preg_replace("/(\s*)(\\\\n)(\s*)/", "<br>", $value);
			break;
		case 'edoc_id':
			if (is_numeric($value)) {
				$value = "doc_id=".$value;
			}
			break;
	}
	// Translater array with old/new values to translate for all metadata field types
	$translator = array('element_type'				=> array('textarea'=>'notes', 'select'=>'dropdown'),
						'element_validation_type' 	=> array('int'=>'integer', 'float'=>'number'),
						'field_phi'					=> array('1'=>'Y'),
						'field_req'					=> array('1'=>'Y', '0'=>'')
						);
	// Do any direct replacing of value, if required
	if (isset($translator[$meta_field][$value])) {
		$value = $translator[$meta_field][$value];
	}
	// Return transformed values
	return $value;
}


/**
 * COMPARE ELEMENT_ENUM CHOICES TO DETECT NEW OR CHANGED CHOICES
 */
function compareChoices($draft_choices, $current_choices) 
{
	// Set regex to replace non-alphnumeric characters in label when comparing the two
	$regex = "/[^a-z0-9 ]/";
	// Convert choices to array format
	$draft_choices   = parseEnum($draft_choices);
	$current_choices = parseEnum($current_choices);
	// Set initial count of labels changed
	$labels_changed = array();
	// Get count of MC choices that were removed
	$codes_removed  = array_keys(array_diff_key($current_choices, $draft_choices));
	// Loop through each choice shared by both fields and check if label has changed
	foreach (array_keys(array_intersect_key($current_choices, $draft_choices)) as $code)
	{
		// Clean each label to minimize false positives (e.g. if only change case of letters or add apostrophe)
		$draft_choices[$code] = preg_replace($regex, "", strtolower(trim(strip_tags(label_decode($draft_choices[$code])))));
		$current_choices[$code] = preg_replace($regex, "", strtolower(trim(strip_tags(label_decode($current_choices[$code])))));
		// If option text was changed, count it
		if ($draft_choices[$code] != $current_choices[$code]) {
			$labels_changed[] = $code;
		}
	}
	// Return counts
	return array($codes_removed, $labels_changed);
}


/**
 * RENDER METADATA CHANGE COMMENT IN RED TEXT
 */
function renderChangeComment($text) {
	return "<div style='color:red;padding:8px 0 3px;font-weight:bold;'>$text</div>";
}

/**
 * RENDER METADATA CHANGE COMMENT IN GREEN TEXT
 */
function renderChangeCommentOkay($text) {
	return "<div style='color:green;padding:8px 0 3px;font-weight:bold;'>$text</div>";
}


/**
 * ADD HELPFUL COMMENTS FOR CHANGES IN A TABLE CELL
 */
function metadataChangeComment($old_field, $new_field, $meta_field, $fieldHasData=true) 
{
	global $lang;
	
	// Set array of allowable field type changes (original type => only allowable types to change to)
	$allowedFieldTypeChanges = array(
		"text" => array("textarea"),
		"textarea" => array("text"),
		"calc" => array("text", "textarea"),
		"radio" => array("text", "textarea", "select", "checkbox"),
		"select" => array("text", "textarea", "radio", "checkbox"),
		"yesno" => array("text", "textarea", "truefalse"),
		"truefalse" => array("text", "textarea", "yesno"),
		"slider" => array("text", "textarea")
	);
	
	// Default string value
	$msg = "";	
	// Choose action based upon which metadata field we're on
	switch ($meta_field) {
		// Field Type
		case 'element_type':
			$oldType = $old_field[$meta_field];
			$newType = $new_field[$meta_field];
			// If field type is changing AND it is changing to an incompatible type, then give error msg.
			// Exclude "descriptive" fields because they have no data, so they're harmless to change into another field type.
			if ($oldType != "descriptive" && $oldType != $newType 
				&& (!isset($allowedFieldTypeChanges[$oldType]) || (isset($allowedFieldTypeChanges[$oldType]) && !in_array($newType, $allowedFieldTypeChanges[$oldType])))) 
			{
				if ($fieldHasData) {
					$msg .= renderChangeComment($lang['database_mods_77']);
				} else {
					$msg .= renderChangeCommentOkay($lang['database_mods_133']);
				}
			}
			break;
		// Select Choices / Calculations
		case 'element_enum':
			// For fields with Choices, compare choice values and codings
			if (in_array($new_field['element_type'], array("advcheckbox", "radio", "select", "checkbox", "dropdown"))) 
			{
				list($codes_removed, $labels_changed) = compareChoices($new_field['element_enum'], $old_field['element_enum']);
				$num_codes_removed = count($codes_removed);
				$num_labels_changed = count($labels_changed);
				if ($num_codes_removed + $num_labels_changed > 0) 
				{
					// Set defaults
					$fieldHasDataForRemovedOptions = $fieldHasDataForChangedOptions = false;
					// Highlight any data loss if option was RELABELED
					if ($num_labels_changed > 0) {
						// If field has data, query the data table to see if it has data for the options being deleted
						if ($fieldHasData) {
							$sql = "select 1 from redcap_data where project_id = ".PROJECT_ID." and field_name = '{$new_field['field_name']}'
									and value in (".prep_implode($labels_changed).") and value != '' limit 1";
							$q = db_query($sql);
							$fieldHasDataForChangedOptions = (db_num_rows($q) > 0);
						}
						if ($fieldHasDataForChangedOptions) {
							$msg .= renderChangeComment($lang['database_mods_79']);
						} else {
							$msg .= renderChangeCommentOkay($lang['database_mods_153']);
						}
					}
					// Highlight any data loss if option was DELETED
					if ($num_codes_removed > 0) 
					{
						// If field has data, query the data table to see if it has data for the options being deleted
						if ($fieldHasData) {
							$sql = "select 1 from redcap_data where project_id = ".PROJECT_ID." and field_name = '{$new_field['field_name']}'
									and value in (".prep_implode($codes_removed).") and value != '' limit 1";
							$q = db_query($sql);
							$fieldHasDataForRemovedOptions = (db_num_rows($q) > 0);
						}
						if ($fieldHasDataForRemovedOptions) {
							$msg .= renderChangeComment($lang['database_mods_78']);
						} else {
							$msg .= renderChangeCommentOkay($lang['database_mods_133']);
						}
					}
					// If no options with data were removed or had their label changed, then we can flag this field as not
					// having data (effectively), and thus it will NOT be considered a critical issue.
					if ($fieldHasData && !$fieldHasDataForChangedOptions && !$fieldHasDataForRemovedOptions) {
						$fieldHasData = false;
					}
				}
			}
			break;
	}
	// Return msg, if any, and $fieldHasData (would be modified if MC field's option was deleted but has no data for that option)
	return array($msg, $fieldHasData);
}


/**
 * GET FIELDS/FORMS TO BE ADDED AND DELETED
 */
function renderFieldsAddDel() 
{
	global $lang, $Proj;
	
	$html = "";
	
	$html .= "<div style='font-size:12px;'>";
	
	// Array for collecting new/deleted field names
	$newFields = array();
	$delFields = array();
	
	//List all new fields to be added
	$newFields = array_diff(array_keys($Proj->metadata_temp), array_keys($Proj->metadata));
	$html .= "	<div style='color:green;padding:5px;'>
					<b><u>{$lang['database_mods_80']}</u></b>";
	foreach ($newFields as $field) {
		$html .= "	<div class='notranslate' style='max-width:800px;text-overflow:ellipsis;overflow:hidden;white-space:nowrap;'>&nbsp;&nbsp;&nbsp;&nbsp;&bull; " . 
					$field . " &nbsp;<span style='font-size:11px;font-family:tahoma;'>\"" . 
					$Proj->metadata_temp[$field]['element_label'] . "</span>\"</div>";
	}
	if (empty($newFields)) {
		$html .= "	<i>{$lang['database_mods_81']}</i>";
	}	
	$html .= "	</div>";
	
	//List all new forms to be added
	$newForms = array_diff(array_keys($Proj->forms_temp), array_keys($Proj->forms));
	$html .= "	<div style='color:green;padding:5px;'>
					<b><u>{$lang['database_mods_98']}</u></b>";
	foreach ($newForms as $form) {
		$html .= "	<div class='notranslate' style='max-width:800px;text-overflow:ellipsis;overflow:hidden;white-space:nowrap;'>&nbsp;&nbsp;&nbsp;&nbsp;&bull; " . 
					$form . " &nbsp;<span style='font-size:11px;font-family:tahoma;'>\"" . 
					$Proj->forms_temp[$form]['menu'] . "</span>\"</div>";
	}
	if (empty($newForms)) {
		$html .= "	<i>{$lang['database_mods_81']}</i>";
	}	
	$html .= "	</div>";
	
	//List all fields to be deleted	
	$delFields = array_diff(array_keys($Proj->metadata), array_keys($Proj->metadata_temp));
	$html .= "	<div style='color:red;padding:5px;'>
					<b><u>{$lang['database_mods_82']}</u></b>";
	foreach ($delFields as $field) {
		$html .= "	<div class='notranslate' style='max-width:800px;text-overflow:ellipsis;overflow:hidden;white-space:nowrap;'>&nbsp;&nbsp;&nbsp;&nbsp;&bull; " . 
					$field . " &nbsp;<span style='font-size:11px;font-family:tahoma;'>\"" . 
					$Proj->metadata[$field]['element_label'] . "</span>\"</div>";
	}
	if (empty($delFields)) {
		$html .= "	<i>{$lang['database_mods_81']}</i>";
	}	
	$html .= "	</div>";
	
	//List all forms to be deleted (in case renamed/deleted form in DD)
	$delForms = array_diff(array_keys($Proj->forms), array_keys($Proj->forms_temp));
	$html .= "	<div style='color:red;padding:5px;'>
					<b><u>{$lang['database_mods_97']}</u></b>";
	foreach ($delForms as $form) {
		$html .= "	<div class='notranslate' style='max-width:800px;text-overflow:ellipsis;overflow:hidden;white-space:nowrap;'>&nbsp;&nbsp;&nbsp;&nbsp;&bull; " . 
					$form . " &nbsp;<span style='font-size:11px;font-family:tahoma;'>\"" . 
					$Proj->forms[$form]['menu'] . "</span>\"</div>";
	}
	if (empty($delForms)) {
		$html .= "	<i>{$lang['database_mods_81']}</i>";
	}	
	$html .= "	</div>";	
	
	$html .= "</div>";
	
	return array($newFields, $delFields, $html);
	
}


/**
 * DISPLAY KEY FOR METADATA CHANGES
 */
function renderMetadataCompareKey() {
	global $lang;
	?>
	<div id="metadataCompareKey" style="padding-left:25px;">
		<table cellspacing="0" cellpadding="0" border="1">
			<tr><td style="padding: 5px; text-align: left; background-color: black; color: white; font-weight: bold;">
				<?php echo $lang['database_mods_83'] ?>
			</td></tr>
			<tr><td style="padding: 5px; text-align: left;">
				<?php echo $lang['database_mods_84'] ?>
			</td></tr>
			<tr><td style="padding: 5px; text-align: left; background-color: #FFFF80;">
				<?php echo $lang['database_mods_85'] ?> 
				<font color="#909090"><?php echo $lang['database_mods_86'] ?></font>)
			</td></tr>
			<tr><td style="padding: 5px; text-align: left; background-color: #7BED7B;">
				<?php echo $lang['database_mods_87'] ?>
			</td></tr>
		</table>
	</div>
	<?php
}

/**
 * Display number of fields added/deleted during Draft Mode
 */
function renderCountFieldsAddDel() {
	global $lang;
	
	// Number of fields added
	$sql = "select count(1) from redcap_metadata_temp where project_id = " . PROJECT_ID . " and field_name 
			not in (" . pre_query("select field_name from redcap_metadata where project_id = " . PROJECT_ID) . ")";
	$fields_added = db_result(db_query($sql), 0);
	// Number of fields deleted
	$sql = "select count(1) from redcap_metadata where project_id = " . PROJECT_ID . " and field_name 
			not in (" . pre_query("select field_name from redcap_metadata_temp where project_id = " . PROJECT_ID) . ")";
	$field_deleted = db_result(db_query($sql), 0);
	// Field count of new metadata
	$sql = "select count(1) from redcap_metadata_temp where project_id = " . PROJECT_ID;
	$count_new = db_result(db_query($sql), 0);
	// Field count of existing metadata
	$sql = "select count(1) from redcap_metadata where project_id = " . PROJECT_ID;
	$count_existing = db_result(db_query($sql), 0);
	// Render text
	return "<p>
				{$lang['database_mods_88']} <b>$fields_added</b>
				&nbsp;/&nbsp;
				{$lang['database_mods_89']} <b>$count_new</b><br>
				{$lang['database_mods_90']} <b>$field_deleted</b>
				&nbsp;/&nbsp;
				{$lang['database_mods_91']} <b>$count_existing</b>
			</p>";
}

/**
 * Display number of fields added/deleted during Draft Mode
 */
function renderCountFieldsAddDel2() 
{
	global $Proj;
	
	// Count project records
	$num_records = Records::getCount(PROJECT_ID);
	// Number of fields added
	$fields_added = count(array_diff(array_keys($Proj->metadata_temp), array_keys($Proj->metadata)));
	// Fields deleted
	$field_name_deleted = array_diff(array_keys($Proj->metadata), array_keys($Proj->metadata_temp));
	// Number of fields deleted
	$field_deleted = count($field_name_deleted);
	// Field count of new metadata
	$count_new = count($Proj->metadata_temp);
	// Field count of existing metadata
	$count_existing = count($Proj->metadata);
	// Query to find fields deleted that have data
	$sql = "select count(distinct(field_name)) from redcap_data where project_id = ".PROJECT_ID." 
			and field_name in (".prep_implode($field_name_deleted).") and value != ''";
	$q = db_query($sql);
	$field_with_data_deleted = db_result($q, 0);
	
	// Return values inside array
	return array($num_records, $fields_added, $field_deleted, $field_with_data_deleted, $count_new, $count_existing);
}

## Validate and clean all fields used in branching logic string. Return array of variables that are not real fields.
function validateBranchingCalc($string, $forceMetadataTable=false)
{
	global $status;
	
	// Use correct metadata table depending on status
	if ($forceMetadataTable) {
		$metadata_table = "redcap_metadata";
	} else {
		$metadata_table = ($status > 0) ? "redcap_metadata_temp" : "redcap_metadata";
	}
	
	## Clean branching logic syntax
	// Removes trailing spaces and line breaks
	$br_orig = array("\r\n", "\r", "\n");
	$br_repl = array(" ", " ", " ");		
	if ($string != "") 
	{
		$string = trim(str_replace($br_orig, $br_repl, $string));
		// Remove any illegal characters inside the variable name brackets
		$string = preg_replace_callback("/(\[)([^\[]*)(\])/", "branchingCleanerCallback", html_entity_decode($string, ENT_QUOTES));
	}

	## Validate all fields used in branching logic
	// Create array with fields from submitted branching logic
	$branching_fields = array_keys(getBracketedFields(cleanBranchingOrCalc($string), true, true, true));
	
	// Create array with braching logic fields that actually exist in metadata
	$sql = "select field_name from $metadata_table where project_id = " . PROJECT_ID . " and field_name 
			in ('" . (implode("','", $branching_fields)) . "')";
	$q = db_query($sql);
	$branching_fields_exist = array();
	while ($row = db_fetch_assoc($q)) {
		$branching_fields_exist[] = $row['field_name'];
	}
	
	// Compare real fields and submitted fields
	$error_fields = array_diff($branching_fields, $branching_fields_exist);
	return $error_fields;
}


// Retrieve name of first form (check metadata or metadata_temp depending on if in development)
function getFirstForm()
{
	global $status;
	$metadata_table = ($status > 0) ? "redcap_metadata_temp" : "redcap_metadata";
	$sql = "select form_name from $metadata_table where project_id = " . PROJECT_ID . " order by field_order limit 1";
	return db_result(db_query($sql), 0);
}

// CHECK IF FIRST EVENT CHANGED. IF SO, GIVING WARNING ABOUT THE PUBLIC SURVEY LINK CHANGING
function checkFirstEventChange($arm)
{
	global $Proj, $lang;
	if (!is_numeric($arm)) return false;
	// Get first event after making the edit (to compare with previous first event)
	$sql = "select e.event_id from redcap_events_metadata e, redcap_events_arms a 
			where a.project_id = ".PROJECT_ID." and a.arm_num = $arm and a.arm_id = e.arm_id 
			order by e.day_offset, e.descrip limit 1";
	$q = db_query($sql);
	$newFirstEventId = db_result($q, 0);
	$oldFirstEventId = $Proj->getFirstEventIdArm($arm);
	// Check if first event has changed position AND if a public survey exists (i.e. a survey for the first form)
	$firstEventChanged = ($newFirstEventId != $oldFirstEventId && isset($Proj->forms[$Proj->firstForm]['survey_id']));
	if ($firstEventChanged)
	{
		// Give warning
		?>
		<div class="red" style="margin:10px 0;">
			<b><?php echo $lang['survey_226'] ?></b><br/>
			<?php echo $lang['survey_415'] ?> <b><?php echo $Proj->eventInfo[$oldFirstEventId]['name'] ?></b>
			<?php echo $lang['survey_228'] ?> <b><?php echo $Proj->eventInfo[$newFirstEventId]['name'] ?></b><?php echo $lang['period'] ?>
		</div>
		<?php
	}
}