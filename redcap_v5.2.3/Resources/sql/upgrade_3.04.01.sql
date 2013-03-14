-- Add config fields
INSERT INTO redcap_config (field_name, value) VALUES
	('logout_fail_limit', '0'),
	('logout_fail_window', '15');
ALTER TABLE  `redcap_projects` ADD  `surveys_enabled` INT( 1 ) NOT NULL DEFAULT  '0' COMMENT  '2 = single survey only' AFTER  `draft_mode`;