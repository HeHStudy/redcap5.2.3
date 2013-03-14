-- Add new Help/FAQ page custom text option
INSERT INTO `redcap_config` (`field_name`, `value`) VALUES ('helpfaq_custom_text', '');
-- Add new cron job to list
INSERT INTO `redcap_crons` (`cron_name` ,`cron_description` ,`cron_enabled` ,`cron_frequency` ,`cron_max_run_time` ,`cron_last_run_end` ,`cron_status` ,`cron_times_failed`)
	VALUES ('DbCleanup',  'Due to some perplexing issues where things might get "out of sync" on the back-end, run some queries to fix any known issues.',  'ENABLED',  '21600',  '1200', NULL ,  'NOT YET RUN',  '0');
-- Add placeholders for future features
ALTER TABLE  `redcap_projects` ADD  `survey_email_participant_field` VARCHAR( 255 ) NULL COMMENT  'Field name that stores participant email';
ALTER TABLE  `redcap_surveys` DROP  `email_field`;	