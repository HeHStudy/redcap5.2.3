-- Add back-end structures for features to be utilized later
ALTER TABLE  `redcap_projects` ADD  `randomization` INT( 1 ) NOT NULL DEFAULT  '0';
ALTER TABLE  `redcap_user_rights` ADD  `random_setup` INT( 1 ) NOT NULL DEFAULT  '0',
	ADD  `random_dashboard` INT( 1 ) NOT NULL DEFAULT  '0',
	ADD  `random_perform` INT( 1 ) NOT NULL DEFAULT  '0';
ALTER TABLE  `redcap_randomization` ADD  `stratified` INT( 1 ) NOT NULL DEFAULT  '1' COMMENT  '1=Stratified, 0=Block' AFTER  `project_id`;
ALTER TABLE  `redcap_randomization_allocation` ADD  `group_id` INT( 10 ) NULL COMMENT  'DAG' AFTER  `is_used_by`, ADD INDEX (  `group_id` );
ALTER TABLE  `redcap_randomization_allocation` ADD FOREIGN KEY (  `group_id` ) 
	REFERENCES  `redcap_data_access_groups` (`group_id`) ON DELETE SET NULL ON UPDATE CASCADE ;