-- Fix issue in validations table
update redcap_validation_types set data_type = 'number' where data_type = 'number_fixeddp';
-- Add new field to template table for 5.0
ALTER TABLE  `redcap_projects_templates` ADD  `enabled` INT( 1 ) NOT NULL DEFAULT  '0' COMMENT  'If enabled, template is visible to users in list.';
-- Modify cron table for 5.0 features
ALTER TABLE  `redcap_crons` ADD  `cron_instances_max` INT( 2 ) NOT NULL DEFAULT  '1' COMMENT  'Number of instances that can run simultaneously' AFTER  `cron_max_run_time`;
ALTER TABLE  `redcap_crons` ADD  `cron_instances_current` INT( 2 ) NOT NULL DEFAULT  '0' COMMENT  'Current number of instances running' AFTER  `cron_instances_max`;
update redcap_crons set cron_instances_current = 1 where cron_status = 'PROCESSING';
update redcap_crons set cron_instances_max = 5, cron_max_run_time = 1800, cron_frequency = 60,
	cron_enabled = 'ENABLED' where cron_name = 'SurveyInvitationEmailer';
UPDATE  `redcap_crons` SET  `cron_enabled` =  'DISABLED' WHERE cron_name = 'DbCleanup';
ALTER TABLE  `redcap_crons` DROP  `cron_status`;
ALTER TABLE  `redcap_crons_history` CHANGE  `cron_last_run_start`  `cron_run_start` DATETIME NULL DEFAULT NULL ,
	CHANGE  `cron_last_run_end`  `cron_run_end` DATETIME NULL DEFAULT NULL;
ALTER TABLE  `redcap_crons_history` CHANGE  `cron_last_run_status`  `cron_run_status` 
	ENUM(  'PROCESSING',  'COMPLETED',  'FAILED' ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
ALTER TABLE  `redcap_crons` ADD  `cron_last_run_start` DATETIME NULL AFTER  `cron_instances_current`;