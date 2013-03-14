-- Modify docs table --
ALTER TABLE `redcap_docs` ADD `export_file` INT( 1 ) NOT NULL DEFAULT '0';
update redcap_docs set export_file = 1 where docs_name like 'EXPORT\_%\_20__-__-__-__-__-__.R' 
	or docs_name like 'EXPORT\_%\_20__-__-__-__-__-__.SAS' or docs_name like 'EXPORT\_%\_20__-__-__-__-__-__.DO' 
	or docs_name like 'EXPORT\_%\_20__-__-__-__-__-__.SPS' or docs_name like 'DATA\_%\_20__-__-__-__-__-__.CSV';
-- Add log_view table --
CREATE TABLE redcap_log_view (
  log_view_id int(11) NOT NULL auto_increment,
  ts timestamp NULL default NULL,
  `user` varchar(255) collate utf8_unicode_ci default NULL,
  event enum('LOGIN_SUCCESS','LOGIN_FAIL','LOGOUT','PAGE_VIEW') collate utf8_unicode_ci default NULL,
  ip varchar(15) collate utf8_unicode_ci default NULL,
  browser_name varchar(255) collate utf8_unicode_ci default NULL,
  browser_version varchar(255) collate utf8_unicode_ci default NULL,
  full_url text collate utf8_unicode_ci,
  `page` varchar(255) collate utf8_unicode_ci default NULL,
  project_id int(5) default NULL,
  event_id int(10) default NULL,
  record varchar(255) collate utf8_unicode_ci default NULL,
  form_name varchar(255) collate utf8_unicode_ci default NULL,
  miscellaneous  text collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (log_view_id),
  KEY `user` (`user`),
  KEY project_id (project_id),
  KEY ts (ts)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
-- Modify prod_revisions table --
ALTER TABLE `redcap_metadata_prod_revisions` CHANGE `ui_id` `ui_id_requester` INT( 5 ) NULL DEFAULT NULL;
ALTER TABLE `redcap_metadata_prod_revisions` ADD `ui_id_approver` INT( 5 ) NULL AFTER `ui_id_requester`;

-- Add new config fields --
INSERT INTO `redcap_config` (`field_name`, `value`) VALUES 
	('redcap_survey_url', ''),
	('certify_text_create', ''),
	('certify_text_prod', '');