-- Add/alter tables for edit survey response functionality
INSERT INTO `redcap_config` (`field_name` ,`value`) VALUES ('enable_edit_survey_response', '0');
CREATE TABLE redcap_surveys_response_users (
  response_id int(10) DEFAULT NULL,
  username varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  UNIQUE KEY response_user (response_id,username),
  KEY response_id (response_id),
  KEY username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE redcap_surveys_response_values (
  response_id int(10) DEFAULT NULL,
  project_id int(5) NOT NULL DEFAULT '0',
  event_id int(10) DEFAULT NULL,
  record varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  field_name varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` text COLLATE utf8_unicode_ci,
  KEY project_id (project_id),
  KEY event_id (event_id),
  KEY record_field (record,field_name),
  KEY project_field (project_id,field_name),
  KEY project_record (project_id,record),
  KEY proj_record_field (project_id,record,field_name),
  KEY response_id (response_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Storage for completed survey responses (archival purposes)';
ALTER TABLE `redcap_surveys_response_users`
  ADD CONSTRAINT redcap_surveys_response_users_ibfk_1 FOREIGN KEY (response_id) REFERENCES redcap_surveys_response (response_id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `redcap_surveys_response_values`
  ADD CONSTRAINT redcap_surveys_response_values_ibfk_1 FOREIGN KEY (response_id) REFERENCES redcap_surveys_response (response_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_surveys_response_values_ibfk_2 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_surveys_response_values_ibfk_3 FOREIGN KEY (event_id) REFERENCES redcap_events_metadata (event_id) ON DELETE CASCADE ON UPDATE CASCADE;
-- Add/alter tables for Data Quality functionality
set foreign_key_checks = 0;
DROP TABLE IF EXISTS redcap_data_quality_status;
set foreign_key_checks = 1;
CREATE TABLE redcap_data_quality_status (
  status_id int(10) NOT NULL AUTO_INCREMENT,
  rule_id int(10) DEFAULT NULL,
  pd_rule_id int(2) DEFAULT NULL COMMENT 'Name of pre-defined rules',
  project_id int(11) DEFAULT NULL,
  record varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  event_id int(10) DEFAULT NULL,
  field_name varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Only used if field-level is required',
  `status` int(2) DEFAULT '0' COMMENT 'Current status of discrepancy',
  exclude int(1) NOT NULL DEFAULT '0' COMMENT 'Hide from results',
  PRIMARY KEY (status_id),
  UNIQUE KEY rule_record_event (rule_id,record,event_id),
  UNIQUE KEY pd_rule_proj_record_event_field (pd_rule_id,record,event_id,field_name,project_id),
  KEY rule_id (rule_id),
  KEY event_id (event_id),
  KEY pd_rule_id (pd_rule_id),
  KEY project_id (project_id),
  KEY pd_rule_proj_record_event (pd_rule_id,record,event_id,project_id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
ALTER TABLE `redcap_data_quality_status`
  ADD CONSTRAINT redcap_data_quality_status_ibfk_1 FOREIGN KEY (rule_id) REFERENCES redcap_data_quality_rules (rule_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_data_quality_status_ibfk_2 FOREIGN KEY (event_id) REFERENCES redcap_events_metadata (event_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_data_quality_status_ibfk_3 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE;
-- If users have Project Design & Setup rights or User Rights page rights, then give them access to Data Quality module
ALTER TABLE  `redcap_user_rights` 
	ADD  `data_quality_design` INT( 1 ) NOT NULL DEFAULT  '0',
	ADD  `data_quality_execute` INT( 1 ) NOT NULL DEFAULT  '0';
update redcap_user_rights set data_quality_design = 1, data_quality_execute = 1 where user_rights = 1 or design = 1;
INSERT INTO `redcap_config` (`field_name` ,`value`) VALUES ('proxy_hostname', '');