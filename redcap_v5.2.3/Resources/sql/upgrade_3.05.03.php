<?php

// Now that all data is transferred, rename tables
print "-- Delete orphaned values in esignatures table and locking_data table\n";
print "delete from redcap_esignatures where esign_id in (" 
     . pre_query("select distinct e.esign_id from redcap_esignatures e left outer join redcap_data d on d.project_id = e.project_id 
				  and e.event_id = d.event_id and e.record = d.record where d.record is null") . ");\n";
print "delete from redcap_locking_data where ld_id in (" 
     . pre_query("select distinct e.ld_id from redcap_locking_data e left outer join redcap_data d on d.project_id = e.project_id 
				  and e.event_id = d.event_id and e.record = d.record where d.record is null") . ");\n";
?>
-- Fix old logging info for downloaded edoc files
update redcap_log_event set pk = null where description = 'Download uploaded document' and data_values like 'doc_id = %';