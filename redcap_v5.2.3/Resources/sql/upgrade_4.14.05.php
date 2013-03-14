<?php

/**
 * Determine if column 'append_pid' exists in redcap_crons table. If not, add it.
 * (to fix bug created in a previous release where some Alter Table statements didn't get added to install.sql)
 */
 
// Get table info
$sql = "SHOW CREATE TABLE redcap_crons";
$q = db_query($sql);
if ($q && db_num_rows($q) == 1)
{
	// Get the 'create table' statement to parse
	$result = db_fetch_array($q);	
	// Set as lower case to prevent case sensitivity issues
	$createTableStatement = strtolower($result[1]);
	// Do regex
	if (!preg_match("/(cron_external_url)/", $createTableStatement))
	{
		// Since column doesn't exist, add it
		print "ALTER TABLE  `redcap_crons` ADD  `cron_external_url` TEXT NULL COMMENT  'URL to call for custom jobs not defined by REDCap';\n"
			. "ALTER TABLE  `redcap_crons` CHANGE  `cron_name`  `cron_name` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT  'Unique name for each job';\n";
	}
}
