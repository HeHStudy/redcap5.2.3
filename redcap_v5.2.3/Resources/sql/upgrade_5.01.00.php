<?php

// Add FKs to metadata tables, if missing
$sql = "SHOW CREATE TABLE redcap_metadata";
$q = db_query($sql);
if ($q && db_num_rows($q) == 1)
{
	// Get the 'create table' statement to parse
	$result = db_fetch_array($q);	
	// Set as lower case to prevent case sensitivity issues
	$createTableStatement = strtolower($result[1]);
	// Do regex
	if (!preg_match("/(redcap_metadata_ibfk)/", $createTableStatement))
	{
		// Since we're missing the FKs, add them
		print "
-- Add missing constraints for metadata tables
set foreign_key_checks = 0;
ALTER TABLE `redcap_metadata`
  ADD CONSTRAINT redcap_metadata_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_metadata_ibfk_2 FOREIGN KEY (edoc_id) REFERENCES redcap_edocs_metadata (doc_id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `redcap_metadata_archive`
  ADD CONSTRAINT redcap_metadata_archive_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_metadata_archive_ibfk_3 FOREIGN KEY (pr_id) REFERENCES redcap_metadata_prod_revisions (pr_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_metadata_archive_ibfk_4 FOREIGN KEY (edoc_id) REFERENCES redcap_edocs_metadata (doc_id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `redcap_metadata_temp`
  ADD CONSTRAINT redcap_metadata_temp_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_metadata_temp_ibfk_2 FOREIGN KEY (edoc_id) REFERENCES redcap_edocs_metadata (doc_id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `redcap_metadata_prod_revisions`
  ADD CONSTRAINT redcap_metadata_prod_revisions_ibfk_3 FOREIGN KEY (ui_id_approver) REFERENCES redcap_user_information (ui_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_metadata_prod_revisions_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_metadata_prod_revisions_ibfk_2 FOREIGN KEY (ui_id_requester) REFERENCES redcap_user_information (ui_id) ON DELETE SET NULL ON UPDATE CASCADE;
set foreign_key_checks = 1;
		";
	}
}

// Add new cron job
print "
INSERT INTO `redcap_crons` (`cron_name`, `cron_description`, `cron_enabled`, `cron_frequency`, `cron_max_run_time`, `cron_instances_max`, `cron_instances_current`, `cron_last_run_start`, `cron_last_run_end`, `cron_times_failed`, `cron_external_url`) VALUES ('DeleteProjects', 'Delete all projects that are scheduled for permanent deletion', 'ENABLED', '300', '1200', '1', '0', NULL, NULL, '0', NULL);
";