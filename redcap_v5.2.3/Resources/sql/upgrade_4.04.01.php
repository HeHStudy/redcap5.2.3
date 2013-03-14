<?php
require_once APP_PATH_DOCROOT . 'ProjectGeneral/form_renderer_functions.php';
// Get all mangled date values and correct them
$sql = "select m.project_id, m.field_name, m.element_validation_type, d.record, d.event_id, d.value 
		from redcap_metadata m, redcap_data d 
		where d.project_id = m.project_id and m.field_name = d.field_name 
		and m.element_type = 'text' and m.element_validation_type = 'date_dmy' and d.value like '00__-__-____'";
$q = db_query($sql);
if (db_num_rows($q) > 0)
{
	print "-- Fix and log any misformatted dates --\n";
	while ($row = db_fetch_assoc($q))
	{
		// Make sure the value is definite a date or datetime
		if (substr_count($row['value'], "-") != 2) continue;
		// Remove the prepended 00's
		$row['value'] = date_dmy2ymd(substr($row['value'], 2));
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