<?php
// Static SQL
?>
-- Add new columns
ALTER TABLE  `redcap_surveys_response` 
	ADD  `results_code` VARCHAR( 8 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ,
	ADD INDEX (  `results_code` );
ALTER TABLE  `redcap_surveys` 
	ADD  `view_results` INT( 1 ) NOT NULL DEFAULT  '0',
	ADD  `min_responses_view_results` INT( 5 ) NOT NULL DEFAULT  '5',
	ADD  `check_diversity_view_results` INT( 1 ) NOT NULL DEFAULT  '0';
-- Fix index
ALTER TABLE  `redcap_surveys` 
	DROP INDEX  `project_form` ,
	ADD UNIQUE  `project_form` (  `project_id` ,  `form_name` );
	
<?php
// Get all mangled date values and correct them
$sql = "select m.project_id, m.field_name, m.element_validation_type, d.record, d.event_id, d.value 
		from redcap_surveys s, redcap_metadata m, redcap_data d 
		where m.project_id = s.project_id and d.project_id = m.project_id and m.field_name = d.field_name 
		and m.element_type = 'text' and m.element_validation_type like 'date%' and d.value like '00%' 
		and length(d.value) in (12, 18, 21)";
$q = db_query($sql);
if (db_num_rows($q) > 0)
{
	print "-- Fix and log any misformatted dates on surveys --\n";
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