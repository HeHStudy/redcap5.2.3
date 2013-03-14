
ALTER TABLE  `redcap_randomization_allocation` ADD  `project_status` INT( 1 ) NOT NULL DEFAULT  '0' 
	COMMENT  'Used in dev or prod status' AFTER  `rid`;
ALTER TABLE  `redcap_randomization_allocation` ADD INDEX  `rid_status` (  `rid` ,  `project_status` );
ALTER TABLE  `redcap_randomization_allocation` CHANGE  `is_used`  `is_used_by` VARCHAR( 100 ) NULL COMMENT  'Used by a record?';
ALTER TABLE  `redcap_randomization_allocation` ADD UNIQUE  `rid_status_usedby` (  `rid` ,  `project_status` ,  `is_used_by` );