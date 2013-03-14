-- For single survey projects whose form_menu_description is still the default 'survey' value, 
-- set its form_menu_description as the survey title in order to provide easier navigation on left-hand menu.
update redcap_projects p, redcap_metadata m, redcap_surveys s 
	set m.form_menu_description = if (length(s.title)>100,concat(substring(s.title,1,97),'...'),s.title) 
	where m.project_id = p.project_id and p.surveys_enabled = '2' and m.field_order = 1 and m.form_menu_description = 'survey' 
	and s.project_id = p.project_id and m.form_name = s.form_name;
-- Since "single survey project" no longer exists in 5.0, convert them all to new 5.0 equivalent
-- Set all projects with surveys_enabled=2 to a value of 1
update redcap_projects set surveys_enabled = 1 where surveys_enabled > 0;
-- Modify user setting that used to allow/disallow single survey projects to be created by users specifically
update redcap_config set value = '0' where field_name = 'superusers_only_move_to_prod' and value = '2';
-- Modify user setting to make sure that users can always create "Data Entry Forms" projects (doesn't make sense in 5.0 otherwise)
update redcap_config set value = '1' where field_name = 'enable_projecttype_forms';
-- Add flag for base URL mismatch warning message
INSERT INTO `redcap_config` VALUES ('redcap_base_url_display_error_on_mismatch', '1');
-- Change FK cascade
ALTER TABLE  `redcap_surveys_scheduler_queue` DROP FOREIGN KEY  `redcap_surveys_scheduler_queue_ibfk_1` ;
ALTER TABLE  `redcap_surveys_scheduler_queue` ADD FOREIGN KEY (  `ss_id` ) 
	REFERENCES  `redcap_surveys_scheduler` (`ss_id`) ON DELETE SET NULL ON UPDATE CASCADE ;
-- Add index
ALTER TABLE `redcap_surveys_scheduler` ADD INDEX  `condition_surveycomplete_survey_event` 
	( `condition_surveycomplete_survey_id` ,  `condition_surveycomplete_event_id` );
-- Add fields to redcap_projects table
ALTER TABLE  `redcap_projects` 
	ADD  `data_entry_trigger_url` TEXT NULL COMMENT  'URL for sending Post request when a record is created or modified',
	ADD  `date_deleted` DATETIME NULL COMMENT  'Time that project was flagged for deletion';
-- 
ALTER TABLE  `redcap_projects` ADD  `template_id` INT( 10 ) NULL COMMENT  'If created from a project template, the project_id of the template' 
	AFTER  `data_entry_trigger_url`, ADD INDEX (  `template_id` );
ALTER TABLE  `redcap_projects` ADD FOREIGN KEY (  `template_id` ) 
	REFERENCES `redcap_projects` (`project_id`) ON DELETE SET NULL ON UPDATE CASCADE ;
-- Add config setting
INSERT INTO `redcap_config` VALUES ('data_entry_trigger_enabled', '0');
