-- Add event_id to redcap_surveys_participants table
ALTER TABLE  `redcap_surveys_participants` ADD  `event_id` INT( 10 ) NULL AFTER  `arm_id` , ADD INDEX (  `event_id` );
ALTER TABLE  `redcap_surveys_participants` ADD FOREIGN KEY (  `event_id` ) 
	REFERENCES  `redcap_events_metadata` (`event_id`) ON DELETE SET NULL ON UPDATE CASCADE ;
-- Auto-fix existing participants by adding their event_id
DROP TABLE IF EXISTS redcap_temp_471;
CREATE TABLE redcap_temp_471 (
  arm_id int(10) DEFAULT NULL,
  event_id int(10) DEFAULT NULL,
  UNIQUE KEY arm_id (arm_id),
  KEY event_id (event_id),
  KEY arm_event (arm_id,event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
insert into redcap_temp_471 select x.arm_id, (select e2.event_id from redcap_events_metadata e2, redcap_events_arms a2 
	where a2.project_id = x.project_id and a2.arm_id = e2.arm_id order by a2.arm_num, e2.day_offset, e2.descrip limit 1) as event_id 
	from (select a.project_id, a.arm_id from redcap_events_metadata e, redcap_events_arms a where a.arm_id = e.arm_id group by a.project_id) x 
	order by x.project_id;
update redcap_surveys_participants p, redcap_temp_471 t set p.event_id = t.event_id where t.arm_id = p.arm_id;
DROP TABLE IF EXISTS redcap_temp_471;
-- Now remove arm_id from redcap_surveys_participants table
ALTER TABLE  `redcap_surveys_participants` DROP FOREIGN KEY  `redcap_surveys_participants_ibfk_2` ;
ALTER TABLE `redcap_surveys_participants` DROP `arm_id`;

-- Set form_name in redcap_surveys
DROP TABLE IF EXISTS redcap_temp_471b;
CREATE TABLE redcap_temp_471b (
  project_id int(10) DEFAULT NULL,
  form_name varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  UNIQUE KEY project_id (project_id),
  KEY form_name (form_name),
  KEY project_form (project_id,form_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
delete from redcap_surveys where survey_id in (select survey_id from (select max(s.survey_id) as survey_id, s.project_id, 
	count(s.project_id) as pcount from redcap_surveys s group by s.project_id) x where pcount > 1);
delete from redcap_surveys where survey_id in (select survey_id from (select max(s.survey_id) as survey_id, s.project_id, 
	count(s.project_id) as pcount from redcap_surveys s group by s.project_id) x where pcount > 1);
delete from redcap_surveys where survey_id in (select survey_id from (select max(s.survey_id) as survey_id, s.project_id, 
	count(s.project_id) as pcount from redcap_surveys s group by s.project_id) x where pcount > 1);
delete from redcap_surveys where survey_id in (select survey_id from (select max(s.survey_id) as survey_id, s.project_id, 
	count(s.project_id) as pcount from redcap_surveys s group by s.project_id) x where pcount > 1);
delete from redcap_surveys where survey_id in (select survey_id from (select max(s.survey_id) as survey_id, s.project_id, 
	count(s.project_id) as pcount from redcap_surveys s group by s.project_id) x where pcount > 1);
delete from redcap_surveys where survey_id in (select survey_id from (select max(s.survey_id) as survey_id, s.project_id, 
	count(s.project_id) as pcount from redcap_surveys s group by s.project_id) x where pcount > 1);
insert into redcap_temp_471b select s.project_id, (select m.form_name from redcap_metadata m 
	where m.project_id = s.project_id order by m.field_order limit 1) as form_name from redcap_surveys s group by s.project_id;
update redcap_surveys s, redcap_temp_471b t set s.form_name = t.form_name where t.project_id = s.project_id;
DROP TABLE IF EXISTS redcap_temp_471b;

ALTER TABLE  `redcap_surveys_response` ADD INDEX (  `first_submit_time` );
ALTER TABLE  `redcap_surveys_response` ADD INDEX (  `completion_time` );