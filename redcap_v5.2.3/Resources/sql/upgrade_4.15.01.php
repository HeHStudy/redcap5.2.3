<?php

/**
 * Find all foreign keys from redcap_project_checklist, then delete them, then re-add them
 */

// First, delete all orphaned project_id's from the table
print "delete from redcap_project_checklist where project_id not in (select project_id from redcap_projects);\n"; 
 
// Get table info
$sql = "SHOW CREATE TABLE redcap_project_checklist";
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
	$regex = "/(constraint `)(redcap_project_checklist_ibfk_\d)(`)/";
	// Do regex
	preg_match_all($regex, $createTableStatement, $matches);
	if (isset($matches[0]) && !empty($matches[0]))
	{
		// Parse invididual foreign key names
		foreach ($matches[0] as $this_fk)
		{
			$fk_name = preg_replace($regex, "$2", $this_fk);
			print "ALTER TABLE `redcap_project_checklist` DROP FOREIGN KEY `$fk_name`;\n";
		}
	}
	
	## RE-ADD ALL FOREIGN KEYS		
	print "ALTER TABLE `redcap_project_checklist` ADD CONSTRAINT redcap_project_checklist_ibfk_1 FOREIGN KEY (project_id) "
		. "REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE;\n";
	
	// Finish operation
	print "SET foreign_key_checks = 1;\n";
}

// Now remove duplicates in actions table
$sql = "select action_id from (select max(action_id) as action_id, project_id, recipient_id, count(1) as this_count 
		from redcap_actions group by project_id, recipient_id) x where this_count > 1";
$q = db_query($sql);
if ($q && db_num_rows($q) > 0) {
	$action_ids = array();
	while ($row = db_fetch_assoc($q))
	{
		$action_ids[] = $row['action_id'];
	}
	print "-- Remove duplicates in redcap_actions table\n";
	print "delete from redcap_actions where action_id in (".prep_implode($action_ids).");\n";
}