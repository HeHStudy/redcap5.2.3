<?php

## BECAUSE DOUBLE DATA ENTRY MIGHT HAVE MESSED UP DATE[TIME] FIELD VALUES, WE NEED TO FIX THEM AND LOG THE CHANGE
// Get all mangled date values and correct them
$sql = "select m.project_id, m.field_name, m.element_validation_type, d.record, d.event_id, d.value 
		from redcap_projects p, redcap_metadata m, redcap_data d 
		where m.project_id = p.project_id and p.double_data_entry = 1 and d.project_id = m.project_id 
		and m.field_name = d.field_name and m.element_type = 'text' and m.element_validation_type like 'date%' 
		and d.value like '00%' and length(d.value) in (12, 18, 21)";
$q = db_query($sql);
if (db_num_rows($q) > 0)
{
	print "-- Fix and log any misformatted dates for merged records in DDE projects --\n";
	while ($row = db_fetch_assoc($q))
	{
		// Make sure the value is definite a date or datetime
		if (substr_count($row['value'], "-") != 2) continue;
		// Determine how to modify the date value and fix it
		if (substr_count($row['value'], " ") < 1) {
			// Date only
			$this_date = $row['value'];
			$this_time = "";
		} else {
			// Datetime or Datetime_seconds
			list ($this_date, $this_time) = explode(" ", $row['value']);
		}
		// Find where the year is located, which will tell us how to fix it
		list ($p1, $p2, $p3) = explode("-", $this_date);
		$p1 = $p1*1;
		$p2 = $p2*1;
		$p3 = $p3*1;
		if (strlen($p3) == 4) {
			$this_date = sprintf("%04d-%02d-%02d", $p3, $p1, $p2);
		} elseif (strlen($p2) == 4) {
			$this_date = sprintf("%04d-%02d-%02d", $p2, $p3, $p1);
		} else {
			$this_date = sprintf("%04d-%02d-%02d", $p1, $p2, $p3);
		}
		// Reset value now that it's formatted
		$row['value'] = trim("$this_date $this_time");
		// Build query to 
		$sql = "update redcap_data set value = '" . prep($row['value']) . "' where project_id = {$row['project_id']} and "
			 . "event_id = {$row['event_id']} and field_name = '{$row['field_name']}' and record = '" . prep($row['record']) . "'";
		// Output the query
		print "$sql;\n";
		// Log the query
		print "insert into redcap_log_event (project_id, ts, user, ip, page, event, object_type, sql_log, pk, event_id, data_values, description) values "
			. "({$row['project_id']}, ".str_replace(array(" ","-",":"), array("","",""), NOW).", 'USERID', '".getIpAddress()."', 'DataEntry/index.php', 'UPDATE', 'redcap_data', ".checkNull($sql).", '".prep($row['record'])."', '{$row['event_id']}', '{$row['field_name']} = \'" . prep($row['value']) . "\'', 'Update record');\n\n";
	}
}
db_free_result($q);