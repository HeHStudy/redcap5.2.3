<?php

/**
 * Find all foreign keys from redcap_surveys_participants, then delete them, then re-add them
 * (to fix bug created in 4.7.1)
 */
 
// ONLY do this if currently on 4.7.1 or higher
if (getDecVersion($current_version) >= 40701)
{
	// Get table info
	$sql = "SHOW CREATE TABLE redcap_surveys_participants";
	$q = db_query($sql);
	if ($q && db_num_rows($q) == 1)
	{
		// Get the 'create table' statement to parse
		$result = db_fetch_array($q);
		
		// Set as lower case to prevent case sensitivity issues
		$createTableStatement = strtolower($result[1]);
		
		// Turn off FK checks first
		print "SET foreign_key_checks = 0;\n";
		
		## REMOVE ALL EXISTING FOREIGN KEYS
		// Set regex to pull out strings
		$regex = "/(constraint `)(redcap_surveys_participants_ibfk_\d)(`)/";
		// Do regex
		preg_match_all($regex, $createTableStatement, $matches);
		if (isset($matches[0]) && !empty($matches[0]))
		{
			// Parse invididual foreign key names
			foreach ($matches[0] as $this_fk)
			{
				$fk_name = preg_replace($regex, "$2", $this_fk);
				print "ALTER TABLE `redcap_surveys_participants` DROP FOREIGN KEY `$fk_name`;\n";
			}
		}
		
		## REMOVE ARM_ID FIELD AND INDEX, IF EXISTS (was accidentally added in 4.7.1 install - but not in 4.7.1 upgrade)
		// Set regex to pull out string
		$regex = "/(`arm_id`)/";
		// Do regex
		if (preg_match($regex, $createTableStatement))
		{
			// Drop the field from table, since it shouldn't be there
			print "ALTER TABLE `redcap_surveys_participants` DROP `arm_id`;\n";
		}
		
		## RE-ADD ALL FOREIGN KEYS		
		print "ALTER TABLE `redcap_surveys_participants`\n"
			. "  ADD CONSTRAINT redcap_surveys_participants_ibfk_1 FOREIGN KEY (survey_id) REFERENCES redcap_surveys (survey_id) ON DELETE CASCADE ON UPDATE CASCADE,"
			. "  ADD CONSTRAINT redcap_surveys_participants_ibfk_2 FOREIGN KEY (event_id) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE;\n";
		
		// Finish operation
		print "SET foreign_key_checks = 1;\n";
	}
}
