-- Add foreign keys
DELETE FROM redcap_user_rights WHERE project_id NOT IN (SELECT project_id FROM redcap_projects);
ALTER TABLE  `redcap_user_rights` ADD FOREIGN KEY (  `project_id` ) REFERENCES `redcap_projects` (`project_id`) ON DELETE CASCADE ON UPDATE CASCADE ;
DELETE FROM redcap_data_access_groups WHERE project_id NOT IN (SELECT project_id FROM redcap_projects);
ALTER TABLE  `redcap_data_access_groups` ADD FOREIGN KEY (  `project_id` ) REFERENCES  `redcap_projects` (
	`project_id`) ON DELETE CASCADE ON UPDATE CASCADE ;
DELETE FROM redcap_docs WHERE project_id NOT IN (SELECT project_id FROM redcap_projects);
ALTER TABLE  `redcap_docs` ADD FOREIGN KEY (  `project_id` ) REFERENCES `redcap_projects` (
	`project_id`) ON DELETE CASCADE ON UPDATE CASCADE ;
DELETE FROM redcap_edocs_metadata WHERE project_id NOT IN (SELECT project_id FROM redcap_projects);
ALTER TABLE  `redcap_edocs_metadata` ADD FOREIGN KEY (  `project_id` ) REFERENCES  `redcap_projects` (
	`project_id`) ON DELETE CASCADE ON UPDATE CASCADE ;
DELETE FROM redcap_events_arms WHERE project_id NOT IN (SELECT project_id FROM redcap_projects);
ALTER TABLE  `redcap_events_arms` ADD FOREIGN KEY (  `project_id` ) REFERENCES  `redcap_projects` (
	`project_id`) ON DELETE CASCADE ON UPDATE CASCADE ;
DELETE FROM redcap_events_calendar WHERE project_id NOT IN (SELECT project_id FROM redcap_projects);
DELETE FROM redcap_events_calendar WHERE event_id NOT IN (SELECT event_id FROM redcap_events_metadata);
DELETE FROM redcap_events_calendar WHERE group_id NOT IN (SELECT group_id FROM redcap_data_access_groups);
ALTER TABLE  `redcap_events_calendar` ADD INDEX (  `project_id` );
ALTER TABLE  `redcap_events_calendar` ADD INDEX (  `event_id` );
ALTER TABLE  `redcap_events_calendar` ADD INDEX (  `group_id` );
ALTER TABLE  `redcap_events_calendar` ADD FOREIGN KEY (  `project_id` ) REFERENCES  `redcap_projects` (
	`project_id`) ON DELETE CASCADE ON UPDATE CASCADE ;
ALTER TABLE  `redcap_events_calendar` ADD FOREIGN KEY (  `event_id` ) REFERENCES  `redcap_events_metadata` (
	`event_id`) ON DELETE CASCADE ON UPDATE CASCADE ;
ALTER TABLE  `redcap_events_calendar` ADD FOREIGN KEY (  `group_id` ) REFERENCES  `redcap_data_access_groups` (
	`group_id`) ON DELETE SET NULL ON UPDATE CASCADE ;
DELETE FROM redcap_events_forms WHERE event_id NOT IN (SELECT event_id FROM redcap_events_metadata);
ALTER TABLE  `redcap_events_forms` ADD FOREIGN KEY (  `event_id` ) REFERENCES  `redcap_events_metadata` (
	`event_id`) ON DELETE CASCADE ON UPDATE CASCADE ;
DELETE FROM redcap_events_metadata WHERE arm_id NOT IN (SELECT arm_id FROM redcap_events_arms);
ALTER TABLE  `redcap_events_metadata` ADD FOREIGN KEY (  `arm_id` ) REFERENCES  `redcap_events_arms` (
	`arm_id`) ON DELETE CASCADE ON UPDATE CASCADE ;
DELETE FROM redcap_library_map WHERE project_id NOT IN (SELECT project_id FROM redcap_projects);
ALTER TABLE  `redcap_library_map` ADD FOREIGN KEY (  `project_id` ) REFERENCES  `redcap_projects` (
	`project_id`) ON DELETE CASCADE ON UPDATE CASCADE ;
DELETE FROM redcap_metadata WHERE project_id NOT IN (SELECT project_id FROM redcap_projects);
ALTER TABLE  `redcap_metadata` ADD FOREIGN KEY (  `project_id` ) REFERENCES  `redcap_projects` (
	`project_id`) ON DELETE CASCADE ON UPDATE CASCADE ;
DELETE FROM redcap_metadata_temp WHERE project_id NOT IN (SELECT project_id FROM redcap_projects);
ALTER TABLE  `redcap_metadata_temp` ADD FOREIGN KEY (  `project_id` ) REFERENCES  `redcap_projects` (
	`project_id`) ON DELETE CASCADE ON UPDATE CASCADE ;
DELETE FROM redcap_metadata_archive WHERE project_id NOT IN (SELECT project_id FROM redcap_projects);
ALTER TABLE  `redcap_metadata_archive` ADD FOREIGN KEY (  `project_id` ) REFERENCES  `redcap_projects` (
	`project_id`) ON DELETE CASCADE ON UPDATE CASCADE ;
DELETE FROM redcap_metadata_prod_revisions WHERE project_id NOT IN (SELECT project_id FROM redcap_projects);
ALTER TABLE  `redcap_metadata_prod_revisions` ADD FOREIGN KEY (  `project_id` ) REFERENCES  `redcap_projects` (
	`project_id`) ON DELETE CASCADE ON UPDATE CASCADE ;
	
-- New fields to event_forms table
ALTER TABLE  `redcap_events_forms` DROP PRIMARY KEY , ADD UNIQUE  `event_form` (  `event_id` ,  `form_name` );
ALTER TABLE  `redcap_events_forms` ADD  `ef_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST ;
ALTER TABLE  `redcap_events_forms` ADD  `survey_enabled` INT( 1 ) NOT NULL DEFAULT  '0';
-- Ensure that only Text field types have validation settings set
update redcap_metadata set element_validation_type = null,	element_validation_min = null, element_validation_max = null, 
	element_validation_checktype = null where element_type != 'text';
update redcap_metadata_archive set element_validation_type = null,	element_validation_min = null, element_validation_max = null, 
	element_validation_checktype = null where element_type != 'text';
update redcap_metadata_temp set element_validation_type = null,	element_validation_min = null, element_validation_max = null, 
	element_validation_checktype = null where element_type != 'text';
-- Add PI/IRB fields to redcap_projects (to be used later)
ALTER TABLE  `redcap_projects` ADD  `project_pi` VARCHAR( 255 ) NULL, ADD  `project_irb_number` VARCHAR( 255 ) NULL;
-- API flag
insert into redcap_config values ('api_enabled', '0');