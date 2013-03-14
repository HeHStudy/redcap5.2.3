<?php

/**
 * Determine if column 'ef_id' and 'survey_enabled' exist in redcap_events_forms. If do, remove them.
 */
 
// Get table info
$sql = "SHOW CREATE TABLE redcap_events_forms";
$q = db_query($sql);
if ($q && db_num_rows($q) == 1)
{
	// Get the 'create table' statement to parse
	$result = db_fetch_array($q);	
	// Set as lower case to prevent case sensitivity issues
	$createTableStatement = strtolower($result[1]);
	// Do regex
	if (preg_match("/(survey_enabled)/", $createTableStatement))
	{
		// If has fields, delete them
		print "ALTER TABLE `redcap_events_forms` DROP `ef_id`, DROP `survey_enabled`;\n";
	}
}
