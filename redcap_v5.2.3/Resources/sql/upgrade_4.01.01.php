-- Edit locking label table (reverse default for e-signature visibility)
ALTER TABLE  `redcap_locking_labels` CHANGE  `display_esignature`  `display_esignature` INT( 1 ) NOT NULL DEFAULT  '0';
-- ------------------------------------------
-- Reverse e-signature default values. Ignore projects that have never used e-signatures or modified the Record Locking Customization page

<?php

// Use this to call the file directly for testing purposes
// if (basename($_SERVER['PHP_SELF']) == 'upgrade_4.01.01.php') {
	// require_once dirname(dirname(dirname(__FILE__)))."/Config/init_global.php";
// }

require_once APP_PATH_DOCROOT . 'ProjectGeneral/form_renderer_functions.php';

// If using locking labels or e-signatures at all, then modify the locking_labels table with new defaults
$sql = "(select distinct project_id from redcap_esignatures) union (select distinct project_id from redcap_locking_labels)";
$q = db_query($sql);
while ($row = db_fetch_assoc($q))
{
	$this_project_id = $row['project_id'];
	print "-- Modifying redcap_locking_labels for project_id $this_project_id\n";
	// Loop through all forms
	$sql = "select distinct form_name from redcap_metadata where project_id = $this_project_id";
	$q2 = db_query($sql);
	while ($row2 = db_fetch_assoc($q2))
	{
		$this_form_name = $row2['form_name'];
		// Is this form in redcap_locking_labels?
		$sql = "select label, display, display_esignature from redcap_locking_labels where project_id = $this_project_id and form_name = '".prep($this_form_name)."' limit 1";
		$q3 = db_query($sql);
		if (db_num_rows($q3) > 0)
		{
			// Yes, form is in table. If has new 4.1.1 defaults, then delete the row.
			$row3 = db_fetch_assoc($q3);
			if ($row3['label'] == '' && $row3['display'] == '1' && $row3['display_esignature'] == '0')
			{
				print "delete from redcap_locking_labels where project_id = $this_project_id and form_name = '".prep($this_form_name)."';\n";
			}
		}
		else
		{
			// No, form not in table, so add it with display_esignature=1
			print "insert into redcap_locking_labels (project_id, form_name, display_esignature) values ($this_project_id, '".prep($this_form_name)."', 1);\n";
		}
	}
}
print "-- ------------------------------------------\n";


// Fix 4.1.0 bug: any dates saved using new MDY or DMY formats for date, datetime, and datetime_seconds
if ($current_version == '4.1.0')
{
	print "-- Fix any incorrectly saved dates for new validation types\n";
	// Set last install timestamp to use in querying
	$redcap_last_install_ts = str_replace("-", "", $redcap_last_install_date) . "000000";
	// Get all mdy/dmy fields
	$sql = "SELECT * FROM redcap_metadata WHERE element_type = 'text' and (element_validation_type like '%_mdy' or 
			element_validation_type like '%_dmy') order by project_id";
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q))
	{
		$this_project_id = $row['project_id'];
		$this_field_name = $row['field_name'];
		// For each field, query the log_event table for the period AFTER they upgraded to 4.1.0
		// Just check to see if the field has been save AFTER the upgrade, so we'll know to go change its value.
		$sql = "SELECT 1 FROM redcap_log_event WHERE project_id = $this_project_id and ts > $redcap_last_install_ts and 
				page = 'DataEntry/index.php' and description in ('Update record','Create record') and data_values like '%$this_field_name = \'%' limit 1";
		$hasSavedDateMaybe = (db_num_rows(db_query($sql)) > 0);
		if ($hasSavedDateMaybe)
		{
			// Now check the data table to see if we can find the date and see if it's been saved incorrectly
			$sql = "SELECT * FROM redcap_data WHERE project_id = $this_project_id and field_name = '$this_field_name' and value != ''";
			$q2 = db_query($sql);
			while ($row2 = db_fetch_assoc($q2))
			{
				$this_date = $row2['value'];
				$this_time = "";
				if (substr($row['element_validation_type'], 0, 9) == "datetime_")
				{
					// Datetime and datetime_seconds
					list ($this_date, $this_time) = explode(" ", $this_date, 2);
				}
				// Now evaluate date alone if in YYYY-MM-DD format, which it should be
				preg_match('/^(19|20)\d\d([-\/.])?(0[1-9]|1[012])\2?(0[1-9]|[12][0-9]|3[01])$/', $this_date, $regex_matches);
				// Was it validated? (If so, will have a value in 0 key in array returned.)
				$failed_regex = (!isset($regex_matches[0]));
				if ($failed_regex)
				{
					if (substr($row['element_validation_type'], -4) == "_dmy") 
					{
						// Now check if in correct DMY format. If not, try to fix it.
						if (strlen($this_date) == 12) {
							list ($y, $m, $d) = explode("-", $this_date, 3);
							$d = $d*1;
							$m = $m*1;
							$this_date = trim(clean_date_ymd("$y-$m-$d")." ".$this_time);
						} else {
							// Reformat to YYYY-MM-DD format
							$this_date = trim(date_dmy2ymd($this_date)." ".$this_time);
						}
					} 
					else 
					{
						// Now check if in correct MDY format. If not, try to fix it.
						if (strlen($this_date) == 12) {
							list ($a, $b, $c) = explode("-", $this_date, 3);
							if (strlen($a) == 4 && strlen($c) == 4) {
								$y = $a;
								$m = $b;
								$d = $c;
							} else {
								$d = $a;
								$y = $b;
								$m = $c;
							}
							$d = $d*1;
							$m = $m*1;
							$this_date = trim(clean_date_ymd("$y-$m-$d")." ".$this_time);
						} else {
							// Reformat to YYYY-MM-DD format
							$this_date = trim(date_mdy2ymd($this_date)." ".$this_time);
						}
					}
					// Date[time] is fixed, so save it
					$sql = "update redcap_data set value = '$this_date' WHERE project_id = $this_project_id and field_name = '$this_field_name' "
						. "and event_id = {$row2['event_id']} and record = '".prep($row2['record'])."'";
					print "$sql;\n";
					// Log it too
					print "insert into redcap_log_event (project_id, ts, user, ip, page, event, object_type, sql_log, pk, event_id, data_values, description) values "
						. "($this_project_id, ".str_replace(array(" ","-",":"), array("","",""), NOW).", 'USERID', '".getIpAddress()."', 'DataEntry/index.php', 'UPDATE', 'redcap_data', ".checkNull($sql).", '".prep($row2['record'])."', '{$row2['event_id']}', '$this_field_name = \'$this_date\'', 'Update record');\n";
				}
			}
		}
	}
	
	
}
	