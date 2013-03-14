-- Add new global and project-level value
INSERT INTO redcap_config VALUES ('display_today_now_button', '1');
ALTER TABLE  `redcap_projects` ADD  `display_today_now_button` INT( 1 ) NOT NULL DEFAULT  '1';
-- Add ability to enable/disable each project type
set @enable_surveys = (select value from redcap_config where field_name = 'enable_surveys' limit 1);
INSERT INTO redcap_config VALUES ('enable_projecttype_singlesurvey', @enable_surveys);
INSERT INTO redcap_config VALUES ('enable_projecttype_forms', '1');
INSERT INTO redcap_config VALUES ('enable_projecttype_singlesurveyforms', @enable_surveys);
delete from redcap_config where field_name = 'enable_surveys';