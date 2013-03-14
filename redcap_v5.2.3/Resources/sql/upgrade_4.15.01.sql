-- Add new column to redcap_actions table
ALTER TABLE  `redcap_actions` ADD  `survey_id` INT( 10 ) NULL AFTER  `project_id` , ADD INDEX (  `survey_id` );
ALTER TABLE  `redcap_actions` ADD FOREIGN KEY (  `survey_id` ) REFERENCES `redcap_surveys` (`survey_id`) ON DELETE CASCADE ON UPDATE CASCADE ;
ALTER TABLE  `redcap_actions` ADD UNIQUE  `survey_recipient_id` (  `survey_id` ,  `recipient_id` );
-- Back-fill survey_id's in actions table
update redcap_actions a set a.survey_id = (select s.survey_id from redcap_surveys s, redcap_metadata m 
	where m.project_id = s.project_id and m.project_id = a.project_id and s.form_name = m.form_name order by m.field_order limit 1);
-- Template table to be used in the future
CREATE TABLE redcap_projects_templates (
  project_id int(10) NOT NULL DEFAULT '0',
  title text COLLATE utf8_unicode_ci,
  description text COLLATE utf8_unicode_ci,
  PRIMARY KEY (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Info about which projects are used as templates';
ALTER TABLE `redcap_projects_templates`
  ADD CONSTRAINT redcap_projects_templates_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE;
  