<?php

## Get all Form Status fields that somehow don't have a Section Header attached to them and add the SH with text 'Form Status'
print "-- Restore the Section Header of any Form Status fields that are missing due to a bug in 3.0.0 --\n";
$meta_tables = array('redcap_metadata', 'redcap_metadata_temp');
// Loop through all metadata tables
foreach ($meta_tables as $metadata_table) {
	$sql = "select project_id, field_name from $metadata_table where field_name = concat(form_name,'_complete') 
			and element_preceding_header is null";
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q)) {
		print "update $metadata_table set element_preceding_header = 'Form Status' where project_id = {$row['project_id']} "
			. "and field_name = '{$row['field_name']}';\n";
	}
}
