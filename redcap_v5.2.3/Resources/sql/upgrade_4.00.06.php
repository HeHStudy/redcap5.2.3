<?php

if (getDecVersion($current_version) >= 40002)
{
	// Add row to config table, if not exists
	$sql = "select 1 from redcap_config where field_name = 'googlemap_key'";
	if (db_num_rows(db_query($sql)) < 1)
	{
		print "
-- Add place for inserting Google Maps API key
INSERT INTO redcap_config VALUES ('googlemap_key', '');";
	}
}