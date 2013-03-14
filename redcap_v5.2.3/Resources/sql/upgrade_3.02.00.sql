-- Fix log_event table where event_id was not recorded for deleted "file" fields
UPDATE redcap_log_event l SET l.event_id = (select x.event_id from 
	(select p.project_id, e.event_id, count(1) as count from redcap_projects p, redcap_events_arms a, redcap_events_metadata e 
	where a.project_id = p.project_id and a.arm_id = e.arm_id group by p.project_id order by e.event_id) 
	as x where x.count = 1 and x.project_id = l.project_id) WHERE l.event = 'DOC_DELETE' and l.event_id is null;
-- Edoc deletion field and index
ALTER TABLE `redcap_edocs_metadata` ADD `date_deleted_server` DATETIME NULL COMMENT 'When really deleted from server';
ALTER TABLE `redcap_edocs_metadata` ADD INDEX `date_deleted` ( `delete_date` , `date_deleted_server` );
ALTER TABLE `redcap_user_information` 
	CHANGE `user_firstvisit` `user_firstvisit` DATETIME NULL DEFAULT NULL ,
	CHANGE `user_firstactivity` `user_firstactivity` DATETIME NULL DEFAULT NULL;
ALTER TABLE redcap_sendit_docs ADD INDEX ( `date_added` ) ;
-- Google Translate
INSERT INTO `redcap_config` (`field_name`,`value`) VALUES ('google_translate_enabled', '0');
ALTER TABLE `redcap_projects` ADD `google_translate_default` VARCHAR( 10 ) NULL;
-- Add "change reason" functionality
ALTER TABLE `redcap_log_event` 
	ADD `change_reason` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
	CHANGE `event` `event` ENUM( 'UPDATE', 'INSERT', 'DELETE', 'SELECT', 'ERROR', 'LOGIN', 'LOGOUT', 'OTHER', 'DATA_EXPORT', 'DOC_UPLOAD', 'DOC_DELETE', 'MANAGE', 'LOCK_RECORD', 'ESIGNATURE' ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL; 
ALTER TABLE redcap_projects ADD require_change_reason INT( 1 ) NOT NULL DEFAULT '0';
-- New locking tables
CREATE TABLE redcap_locking_data (
  ld_id int(11) NOT NULL AUTO_INCREMENT,
  project_id int(5) DEFAULT NULL,
  record varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  event_id int(10) DEFAULT NULL,
  form_name varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  username varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `timestamp` datetime DEFAULT NULL,
  PRIMARY KEY (ld_id),
  UNIQUE KEY proj_rec_event_form (project_id,record,event_id,form_name),
  KEY username (username),
  KEY proj_rec_event (project_id,record,event_id),
  KEY project_id (project_id),
  KEY event_id (event_id),
  KEY proj_rec (project_id,record)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE redcap_locking_labels (
  ll_id int(11) NOT NULL AUTO_INCREMENT,
  project_id int(11) DEFAULT NULL,
  form_name varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  label text COLLATE utf8_unicode_ci,
  display int(1) NOT NULL DEFAULT '1',
  display_esignature int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (ll_id),
  UNIQUE KEY project_form (project_id,form_name),
  KEY project_id (project_id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE redcap_esignatures (
  esign_id int(11) NOT NULL AUTO_INCREMENT,
  project_id int(5) DEFAULT NULL,
  record varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  event_id int(10) DEFAULT NULL,
  form_name varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  username varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `timestamp` datetime DEFAULT NULL,
  PRIMARY KEY (esign_id),
  UNIQUE KEY proj_rec_event_form (project_id,record,event_id,form_name),
  KEY username (username),
  KEY proj_rec_event (project_id,record,event_id),
  KEY project_id (project_id),
  KEY event_id (event_id),
  KEY proj_rec (project_id,record)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
ALTER TABLE `redcap_esignatures`
  ADD CONSTRAINT redcap_esignatures_ibfk_2 FOREIGN KEY (event_id) REFERENCES redcap_events_metadata (event_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_esignatures_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `redcap_locking_data`
  ADD CONSTRAINT redcap_locking_data_ibfk_2 FOREIGN KEY (event_id) REFERENCES redcap_events_metadata (event_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_locking_data_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `redcap_locking_labels`
  ADD CONSTRAINT redcap_locking_labels_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE;
-- Lock records conversion
insert into redcap_locking_data (project_id, event_id, record, form_name, timestamp) 
	select d.project_id, d.event_id, d.record, substring(d.field_name,15) as form_name, 
	timestamp(concat(substring(d.value,7,4),'-',substring(d.value,1,2),'-',substring(d.value,4,2),' ',substring(d.value,12))) as timestamp 
	from redcap_data d, redcap_projects p where p.project_id = d.project_id and d.field_name like '__LOCKRECORD__%' and d.value != '0';
-- Delete lock records and extraneous __SALT__ field from redcap_data
delete from redcap_data where field_name like '__LOCKRECORD__%';
delete from redcap_data where field_name = '__SALT__';
-- User rights changes
ALTER TABLE redcap_user_rights 
	ADD api_token VARCHAR(32) NULL,
	ADD `record_create` INT( 1 ) NOT NULL DEFAULT '1',
	ADD `record_rename` INT( 1 ) NOT NULL DEFAULT '0',
	ADD `record_delete` INT( 1 ) NOT NULL DEFAULT '0',
	ADD UNIQUE (api_token);
ALTER TABLE `redcap_user_rights` ADD `lock_record_multiform` INT( 1 ) NOT NULL DEFAULT '0' AFTER `lock_record`;
update redcap_user_rights set lock_record_multiform = 1 where lock_record > 0;
-- Transfer project-level rename/delete record rights to user-level and delete from redcap_projects
DELETE FROM redcap_user_rights WHERE project_id NOT IN (SELECT project_id FROM redcap_projects);
update redcap_user_rights u set u.record_delete = (select p.record_delete_flag from redcap_projects p where p.project_id = u.project_id limit 1);
update redcap_user_rights u set u.record_rename = (select p.allow_pk_edit from redcap_projects p where p.project_id = u.project_id limit 1);
ALTER TABLE `redcap_projects` DROP `record_delete_flag`, DROP `allow_pk_edit`;
-- DTS 
ALTER TABLE redcap_projects ADD dts_enabled INT( 1 ) NOT NULL DEFAULT '0';
INSERT INTO redcap_config (field_name, value) VALUES ('dts_enabled_global', '0');
INSERT INTO `redcap_config` (`field_name`,`value`) VALUES ('dts_path', '');
-- Fix issue with changing multiple choice to text or textarea
update redcap_metadata set element_enum = null where element_type in ('text', 'textarea', 'file') and element_enum is not null;
update redcap_metadata_archive set element_enum = null where element_type in ('text', 'textarea', 'file') and element_enum is not null;
update redcap_metadata_temp set element_enum = null where element_type in ('text', 'textarea', 'file') and element_enum is not null;
-- Add separate Send-it file upload max size
insert into redcap_config select 'sendit_upload_max', value from redcap_config where field_name = 'file_upload_max';
update redcap_config set field_name = 'edoc_upload_max' where field_name = 'file_upload_max';
update redcap_log_view set browser_name = 'internet explorer' where browser_name = 'msie';
-- Add option to prevent users from changing their first/last name on My Profile page
INSERT INTO `redcap_config` (`field_name`,`value`) VALUES ('my_profile_enable_edit','1');
-- Set up for new language abstraction
update redcap_config set value = (case value when '0' then 'English' when '1' then 'Spanish' when '2' then 'Japanese' else 'English' end) 
	where field_name = 'project_language';
ALTER TABLE `redcap_projects` CHANGE  `project_language`  `project_language` VARCHAR( 255 ) NOT NULL DEFAULT  'English';
update redcap_projects set project_language = (case project_language when '0' then 'English' when '1' then 'Spanish' 
	when '2' then 'Japanese' else 'English' end);
INSERT INTO `redcap_config` (`field_name`,`value`) VALUES ('language_global','English');

CREATE TABLE redcap_sessions (
  session_id varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  session_data text COLLATE utf8_unicode_ci,
  session_expiration timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores user authentication session data';

ALTER TABLE  `redcap_user_rights` ADD  `lock_record_customize` INT( 1 ) NOT NULL DEFAULT  '0' AFTER  `lock_record_multiform`;
