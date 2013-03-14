ALTER TABLE  `redcap_projects` ADD  `project_pi_username` VARCHAR( 255 ) NULL AFTER  `project_pi`;
ALTER TABLE  `redcap_projects` ADD  `project_grant_number` VARCHAR( 255 ) NULL AFTER  `project_irb_number`;
-- Enable/disable history widget
ALTER TABLE  `redcap_projects` ADD  `history_widget_enabled` INT( 1 ) NOT NULL DEFAULT  '1';
-- Encrypt Send-It passwords
ALTER TABLE  `redcap_sendit_recipients` CHANGE  `pwd`  `pwd` VARCHAR( 32 ) NULL DEFAULT NULL;
update `redcap_sendit_recipients` set pwd = md5(pwd);
-- Removed unused structures
delete from redcap_config where field_name = 'dts_path';
-- Add new table fields
ALTER TABLE `redcap_log_view` ADD  `session_id` VARCHAR( 32 ) NULL , ADD INDEX (  `session_id` );
-- Add option to set key words manually for Identifier Check
insert into redcap_config values ('identifier_keywords', 'name, street, address, city, county, precinct, zip, postal, date, phone, fax, mail, ssn, social security, mrn, dob, dod, medical, record, id, age');