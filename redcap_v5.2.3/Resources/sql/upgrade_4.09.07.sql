-- Remove unused items
DELETE FROM `redcap_config` WHERE `field_name` = 'pubmed_matching_last_crawl';
DELETE FROM `redcap_config` WHERE `field_name` = 'cron_last_execution';
-- Add back-end structures not used yet
DROP TABLE IF EXISTS redcap_crons_history;
DROP TABLE IF EXISTS redcap_crons;
CREATE TABLE redcap_crons (
  cron_id int(10) NOT NULL AUTO_INCREMENT,
  cron_name varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  cron_description text COLLATE utf8_unicode_ci,
  cron_enabled enum('ENABLED','DISABLED') COLLATE utf8_unicode_ci DEFAULT 'ENABLED',
  cron_frequency int(10) DEFAULT NULL COMMENT 'seconds',
  cron_max_run_time int(10) DEFAULT NULL COMMENT 'max # seconds a cron should run',
  cron_last_run_end datetime DEFAULT NULL,
  cron_status enum('PROCESSING','COMPLETED','FAILED','NOT YET RUN') COLLATE utf8_unicode_ci DEFAULT 'NOT YET RUN',
  cron_times_failed int(2) NOT NULL DEFAULT '0' COMMENT 'After X failures, set as Disabled',
  PRIMARY KEY (cron_id),
  UNIQUE KEY cron_name (cron_name)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='List of all jobs to be run by universal cron job';
CREATE TABLE redcap_crons_history (
  ch_id int(10) NOT NULL AUTO_INCREMENT,
  cron_id int(10) DEFAULT NULL,
  cron_last_run_start datetime DEFAULT NULL,
  cron_last_run_end datetime DEFAULT NULL,
  cron_last_run_status enum('PROCESSING','COMPLETED','FAILED') COLLATE utf8_unicode_ci DEFAULT NULL,
  cron_info text COLLATE utf8_unicode_ci COMMENT 'Any pertinent info that might be logged',
  PRIMARY KEY (ch_id),
  KEY cron_id (cron_id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='History of all jobs run by universal cron job';
ALTER TABLE `redcap_crons_history`
  ADD CONSTRAINT redcap_crons_history_ibfk_1 FOREIGN KEY (cron_id) REFERENCES redcap_crons (cron_id) ON DELETE SET NULL ON UPDATE CASCADE;
-- Add content to crons table
INSERT INTO redcap_crons (cron_name, cron_description, cron_enabled, cron_frequency, cron_max_run_time, cron_last_run_end, cron_status, cron_times_failed) VALUES
('PubMed', 'Query the PubMed API to find publications associated with PIs in REDCap, and store publication attributes and PI/project info. Emails will then be sent to any PIs that have been found to have publications in PubMed, and (if applicable) will be asked to associate their publication to a REDCap project.', 'DISABLED', 86400, 7200, NULL, 'NOT YET RUN', 0),
('RemoveTempAndDeletedFiles', 'Delete all files from the REDCap temp directory, and delete all edoc and Send-It files marked for deletion.', 'ENABLED', 600, 600, NULL, 'NOT YET RUN', 0);
-- Fix survey responses marked with Form Status incomplete even though they are really completed responses
update redcap_surveys s, redcap_surveys_participants p, redcap_surveys_response r, redcap_data d 
	set d.value = '2' where s.survey_id = p.survey_id and p.participant_id = r.participant_id 
	and r.completion_time is not null and d.project_id = s.project_id and p.event_id = d.event_id 
	and d.field_name = concat(s.form_name,'_complete') and r.record = d.record and d.value = '0';