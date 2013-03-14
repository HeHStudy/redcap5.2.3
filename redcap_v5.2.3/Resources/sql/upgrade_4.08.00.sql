
-- add rights for API import/export
ALTER TABLE  `redcap_user_rights` ADD  `api_export` INT( 1 ) NOT NULL DEFAULT  '0' AFTER  `api_token` ,
	ADD  `api_import` INT( 1 ) NOT NULL DEFAULT  '0' AFTER  `api_export`;
-- set new API rights based on API token existance
UPDATE `redcap_user_rights` SET api_import = 1, api_export = 1
	WHERE api_token IS NOT NULL AND CHAR_LENGTH(api_token) > 0;
	
-- Set value for PubMed fetching
UPDATE  `redcap_config` SET  `value` =  'Vanderbilt\nMeharry' WHERE `field_name` =  'pubmed_matching_institution';

ALTER TABLE  `redcap_pubmed_match_pi` ADD  `times_emailed` INT( 3 ) NOT NULL DEFAULT  '0';
ALTER TABLE  `redcap_pubmed_match_pi` ADD  `unique_hash` VARCHAR( 32 ) NULL;
ALTER TABLE  `redcap_pubmed_match_pi` ADD UNIQUE (`unique_hash`);

ALTER TABLE  `redcap_docs` ADD INDEX  `project_id_comment` (  `project_id` ,  `docs_comment` ( 128 ) );
ALTER TABLE  `redcap_surveys_participants` 
	DROP INDEX  `survey_arm_email` ,
	ADD INDEX  `survey_event_email` (  `survey_id` ,  `event_id` ,  `participant_email` );
	
	
ALTER TABLE  `redcap_events_metadata` ADD INDEX  `arm_dayoffset_descrip` (  `arm_id` ,  `day_offset` ,  `descrip` ) ;
ALTER TABLE  `redcap_events_metadata` ADD INDEX (  `day_offset` );
ALTER TABLE  `redcap_events_metadata` ADD INDEX (  `descrip` );

-- New field validation types
INSERT INTO `redcap_validation_types` (`validation_name`, `validation_label`, `regex_js`, `regex_php`, `data_type`, `legacy_value`, `visible`) 
VALUES ('ssn', 'Social Security Number (U.S.)','/^\\d{3}-\\d\\d-\\d{4}$/','/^\\d{3}-\\d\d-\\d{4}$/', 'ssn', NULL, 0);
INSERT INTO `redcap_validation_types` (`validation_name`, `validation_label`, `regex_js`, `regex_php`, `data_type`, `legacy_value`, `visible`) 
VALUES ('number_1dp', 'Number (1 decimal place)', '/^-?\\d+\\.\\d$/', '/^-?\\d+\\.\\d$/', 'number_fixeddp', NULL, 0);
INSERT INTO `redcap_validation_types` (`validation_name`, `validation_label`, `regex_js`, `regex_php`, `data_type`, `legacy_value`, `visible`) 
VALUES ('number_2dp', 'Number (2 decimal places)', '/^-?\\d+\\.\\d{2}$/', '/^-?\\d+\\.\\d{2}$/', 'number_fixeddp', NULL, 0);
INSERT INTO `redcap_validation_types` (`validation_name`, `validation_label`, `regex_js`, `regex_php`, `data_type`, `legacy_value`, `visible`) 
VALUES ('number_3dp', 'Number (3 decimal places)', '/^-?\\d+\\.\\d{3}$/', '/^-?\\d+\\.\\d{3}$/', 'number_fixeddp', NULL, 0);
INSERT INTO `redcap_validation_types` (`validation_name`, `validation_label`, `regex_js`, `regex_php`, `data_type`, `legacy_value`, `visible`) 
VALUES ('number_4dp', 'Number (4 decimal places)', '/^-?\\d+\\.\\d{4}$/', '/^-?\\d+\\.\\d{4}$/', 'number_fixeddp', NULL, 0);
INSERT INTO `redcap_validation_types` (`validation_name`, `validation_label`, `regex_js`, `regex_php`, `data_type`, `legacy_value`, `visible`) 
VALUES ('phone_australia', 'Phone (Australia)', '/^(\\(0[2-8]\\)|0[2-8])\\s*\d{4}\\s*\\d{4}$/', '/^(\\(0[2-8]\\)|0[2-8])\\s*\\d{4}\\s*\\d{4}$/', 'phone', NULL, 0);
INSERT INTO `redcap_validation_types` (`validation_name`, `validation_label`, `regex_js`, `regex_php`, `data_type`, `legacy_value`, `visible`) 
VALUES ('postalcode_australia', 'Postal Code (Australia)', '/^\\d{4}$/', '/^\\d{4}$/', 'postal_code', NULL, 0);
INSERT INTO `redcap_validation_types` (`validation_name`, `validation_label`, `regex_js`, `regex_php`, `data_type`, `legacy_value`, `visible`) 
VALUES ('alpha_only', 'Letters only', '/^[a-z]+$/i', '/^[a-z]+$/i', 'text', NULL, 0);
INSERT INTO `redcap_validation_types` (`validation_name`, `validation_label`, `regex_js`, `regex_php`, `data_type`, `legacy_value`, `visible`) 
VALUES ('time_mm_ss', 'Time (MM:SS)', '/^[0-5]\\d:[0-5]\\d$/', '/^[0-5]\\d:[0-5]\\d$/', 'time', NULL, 0);
INSERT INTO `redcap_validation_types` (`validation_name`, `validation_label`, `regex_js`, `regex_php`, `data_type`, `legacy_value`, `visible`) 
VALUES ('mrn_10d', 'MRN (10 digits)', '/^\\d{10}$/', '/^\\d{10}$/', 'text', NULL, 0);
-- Edit existing field val type
UPDATE `redcap_validation_types` SET `validation_label` = 'Time (HH:MM)' WHERE `validation_name` =  'time';
-- Randomization tables
DROP TABLE IF EXISTS redcap_randomization_allocation;
DROP TABLE IF EXISTS redcap_randomization;
CREATE TABLE redcap_randomization (
  rid int(10) NOT NULL AUTO_INCREMENT,
  project_id int(10) DEFAULT NULL,
  target_field varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  target_event int(10) DEFAULT NULL,
  source_field1 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  source_event1 int(10) DEFAULT NULL,
  source_field2 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  source_event2 int(10) DEFAULT NULL,
  source_field3 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  source_event3 int(10) DEFAULT NULL,
  source_field4 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  source_event4 int(10) DEFAULT NULL,
  source_field5 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  source_event5 int(10) DEFAULT NULL,
  source_field6 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  source_event6 int(10) DEFAULT NULL,
  source_field7 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  source_event7 int(10) DEFAULT NULL,
  source_field8 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  source_event8 int(10) DEFAULT NULL,
  source_field9 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  source_event9 int(10) DEFAULT NULL,
  source_field10 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  source_event10 int(10) DEFAULT NULL,
  source_field11 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  source_event11 int(10) DEFAULT NULL,
  source_field12 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  source_event12 int(10) DEFAULT NULL,
  source_field13 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  source_event13 int(10) DEFAULT NULL,
  source_field14 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  source_event14 int(10) DEFAULT NULL,
  source_field15 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  source_event15 int(10) DEFAULT NULL,
  PRIMARY KEY (rid),
  UNIQUE KEY project_id (project_id),
  KEY target_event (target_event),
  KEY source_event1 (source_event1),
  KEY source_event2 (source_event2),
  KEY source_event3 (source_event3),
  KEY source_event4 (source_event4),
  KEY source_event5 (source_event5),
  KEY source_event6 (source_event6),
  KEY source_event7 (source_event7),
  KEY source_event8 (source_event8),
  KEY source_event9 (source_event9),
  KEY source_event10 (source_event10),
  KEY source_event11 (source_event11),
  KEY source_event12 (source_event12),
  KEY source_event13 (source_event13),
  KEY source_event14 (source_event14),
  KEY source_event15 (source_event15)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE redcap_randomization_allocation (
  aid int(10) NOT NULL AUTO_INCREMENT,
  rid int(10) NOT NULL DEFAULT '0',
  is_used int(1) NOT NULL DEFAULT '0' COMMENT 'Used by a record?',
  target_field varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Data value',
  source_field1 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Data value',
  source_field2 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Data value',
  source_field3 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Data value',
  source_field4 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Data value',
  source_field5 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Data value',
  source_field6 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Data value',
  source_field7 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Data value',
  source_field8 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Data value',
  source_field9 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Data value',
  source_field10 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Data value',
  source_field11 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Data value',
  source_field12 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Data value',
  source_field13 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Data value',
  source_field14 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Data value',
  source_field15 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Data value',
  PRIMARY KEY (aid),
  KEY rid (rid)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
ALTER TABLE `redcap_randomization`
  ADD CONSTRAINT redcap_randomization_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_randomization_ibfk_2 FOREIGN KEY (source_event1) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_randomization_ibfk_3 FOREIGN KEY (source_event2) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_randomization_ibfk_4 FOREIGN KEY (source_event3) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_randomization_ibfk_5 FOREIGN KEY (source_event4) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_randomization_ibfk_6 FOREIGN KEY (source_event5) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_randomization_ibfk_7 FOREIGN KEY (source_event6) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_randomization_ibfk_8 FOREIGN KEY (source_event7) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_randomization_ibfk_9 FOREIGN KEY (source_event8) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_randomization_ibfk_10 FOREIGN KEY (source_event9) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_randomization_ibfk_11 FOREIGN KEY (source_event10) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_randomization_ibfk_12 FOREIGN KEY (source_event11) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_randomization_ibfk_13 FOREIGN KEY (source_event12) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_randomization_ibfk_14 FOREIGN KEY (source_event13) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_randomization_ibfk_15 FOREIGN KEY (source_event14) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_randomization_ibfk_16 FOREIGN KEY (source_event15) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_randomization_ibfk_17 FOREIGN KEY (target_event) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `redcap_randomization_allocation`
  ADD CONSTRAINT redcap_randomization_allocation_ibfk_1 FOREIGN KEY (rid) REFERENCES redcap_randomization (rid) ON DELETE CASCADE ON UPDATE CASCADE;

-- Disable by default auto variable naming in Online Designer
ALTER TABLE  `redcap_projects` ADD  `auto_variable_naming` INT( 1 ) NOT NULL DEFAULT  '0';