-- Fix mistake in some logging records
update redcap_log_event set project_id = if(pk is null,0,pk) where project_id = 0 and page = 'ProjectGeneral/create_project.php'; 
-- Delete unneccessary row in config table and fix redcap_projects-correlated value
delete from redcap_config where field_name = 'surveys_enabled';
update redcap_projects set surveys_enabled = '0';
ALTER TABLE  `redcap_projects` CHANGE  `surveys_enabled`  `surveys_enabled` INT( 1 ) NOT NULL DEFAULT  '0' 
	COMMENT  '0 = forms only, 1 = survey+forms, 2 = single survey only';
-- Set up foreign keys
update redcap_metadata_prod_revisions set ui_id_requester = null where ui_id_requester not in (select ui_id from redcap_user_information);
update redcap_metadata_prod_revisions set ui_id_approver = null where ui_id_approver not in (select ui_id from redcap_user_information);
ALTER TABLE  `redcap_metadata_prod_revisions` ADD INDEX (  `ui_id_requester` );
ALTER TABLE  `redcap_metadata_prod_revisions` ADD INDEX (  `ui_id_approver` );
ALTER TABLE  `redcap_metadata_prod_revisions` ADD FOREIGN KEY (  `ui_id_requester` ) 
	REFERENCES  `redcap_user_information` (`ui_id`) ON DELETE SET NULL ON UPDATE CASCADE ;
ALTER TABLE  `redcap_metadata_prod_revisions` ADD FOREIGN KEY (  `ui_id_approver` ) 
	REFERENCES  `redcap_user_information` (`ui_id`) ON DELETE SET NULL ON UPDATE CASCADE ;
-- Add new DTS user rights
ALTER TABLE  `redcap_user_rights` ADD  `dts` INT( 1 ) NOT NULL DEFAULT  '0' COMMENT  'DTS adjudication page';
-- Grant DTS rights to all users on a project if that project is currently using DTS
update redcap_user_rights set dts = 1 where project_id in (select project_id from redcap_projects where dts_enabled = 1);
-- For key length issues, form_name only needs to be 100 char long
ALTER TABLE  `redcap_esignatures` CHANGE  `form_name`  `form_name` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
ALTER TABLE  `redcap_locking_data` CHANGE  `form_name`  `form_name` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
ALTER TABLE  `redcap_locking_data` CHANGE  `username`  `username` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
ALTER TABLE  `redcap_locking_labels` CHANGE  `form_name`  `form_name` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
ALTER TABLE  `redcap_metadata` CHANGE  `form_name`  `form_name` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
ALTER TABLE  `redcap_metadata_archive` CHANGE  `form_name`  `form_name` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
ALTER TABLE  `redcap_metadata_temp` CHANGE  `form_name`  `form_name` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
ALTER TABLE  `redcap_log_view` CHANGE  `form_name`  `form_name` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
-- Remove any useless orphaned records without a record name (due to old bug?)
delete from redcap_data where record = '';