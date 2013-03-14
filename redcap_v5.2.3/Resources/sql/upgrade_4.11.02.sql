
ALTER TABLE  `redcap_crons` ADD  `cron_external_url` TEXT NULL COMMENT  'URL to call for custom jobs not defined by REDCap';
ALTER TABLE  `redcap_crons` CHANGE  `cron_name`  `cron_name` VARCHAR( 100 ) CHARACTER SET utf8 
	COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT  'Unique name for each job';