-- Add indexes
ALTER TABLE redcap_log_view ADD INDEX (event);
ALTER TABLE redcap_log_view ADD INDEX (browser_name);
ALTER TABLE redcap_log_view ADD INDEX (browser_version);
ALTER TABLE redcap_log_view ADD INDEX (page);
ALTER TABLE `redcap_docs` ADD INDEX `project_id_export_file` ( `project_id` , `export_file` );
-- Add new fields to redcap_projects
ALTER TABLE `redcap_projects` 
	ADD `mobile_project` INT( 1 ) NOT NULL DEFAULT '0', 
	ADD `mobile_project_export_flag` INT( 1 ) NOT NULL DEFAULT '1',
	ADD `disable_data_entry` INT( 1 ) NOT NULL DEFAULT '0';
-- Add to config table
set @enable_sendit = (select value from redcap_config where field_name = 'edoc_field_option_enabled' limit 1);
INSERT INTO `redcap_config` VALUES 
('shared_library_enabled', '1'),
('temp_files_last_delete', now()),
('sendit_enabled', @enable_sendit),
('homepage_custom_text', ''),
('file_upload_max', '');
-- Add comment to field
ALTER TABLE `redcap_library_map` CHANGE `type` `type` INT( 11 ) NOT NULL DEFAULT '0' 
	COMMENT '1 = Downloaded; 2 = Uploaded';
ALTER TABLE `redcap_edocs_metadata` CHANGE `mime_type` `mime_type` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
-- Table structure for table 'redcap_data_temp'
CREATE TABLE redcap_data_temp (
  project_id int(5) NOT NULL default '0',
  event_id int(10) default NULL,
  record varchar(100) collate utf8_unicode_ci default NULL,
  field_name varchar(100) collate utf8_unicode_ci default NULL,
  `value` text collate utf8_unicode_ci,
  KEY project_id (project_id),
  KEY event_id (event_id),
  KEY record_field (record,field_name),
  KEY project_field (project_id,field_name),
  KEY project_record (project_id,record),
  KEY proj_record_field (project_id,record,field_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
-- Send-It tables
CREATE TABLE redcap_sendit_docs (
  document_id int(11) NOT NULL auto_increment,
  doc_name varchar(255) collate utf8_unicode_ci default NULL,
  doc_orig_name varchar(255) collate utf8_unicode_ci default NULL,
  doc_type varchar(255) collate utf8_unicode_ci default NULL,
  doc_size int(11) default NULL,
  send_confirmation int(1) NOT NULL default '0',
  expire_date datetime default NULL,
  username varchar(255) collate utf8_unicode_ci default NULL,
  location int(1) NOT NULL default '0' COMMENT '1 = Home page; 2 = File Repository; 3 = Form',
  docs_id int(11) NOT NULL default '0',
  date_added datetime default NULL,
  date_deleted datetime default NULL COMMENT 'When really deleted from server (only applicable for location=1)',
  PRIMARY KEY  (document_id),
  KEY user_id (username),
  KEY docs_id_location (location,docs_id),
  KEY expire_location_deleted (expire_date,location,date_deleted)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE redcap_sendit_recipients (
  recipient_id int(11) NOT NULL auto_increment,
  email_address varchar(255) collate utf8_unicode_ci default NULL,
  sent_confirmation int(1) NOT NULL default '0',
  download_date datetime default NULL,
  download_count int(11) NOT NULL default '0',
  document_id int(11) NOT NULL default '0' COMMENT 'FK from redcap_sendit_docs',
  guid varchar(100) collate utf8_unicode_ci default NULL,
  pwd varchar(20) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (recipient_id),
  KEY document_id (document_id),
  KEY email_address (email_address),
  KEY guid (guid)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
ALTER TABLE `redcap_sendit_recipients`
  ADD CONSTRAINT redcap_sendit_recipients_ibfk_1 FOREIGN KEY (document_id) REFERENCES redcap_sendit_docs (document_id) ON DELETE CASCADE ON UPDATE CASCADE;
