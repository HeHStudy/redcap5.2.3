
--
-- Table structure for table 'redcap_surveys'
--

CREATE TABLE redcap_surveys (
  survey_id int(10) NOT NULL AUTO_INCREMENT,
  project_id int(10) DEFAULT NULL,
  form_name varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'NULL = assume first form',
  title text COLLATE utf8_unicode_ci COMMENT 'Survey title',
  instructions text COLLATE utf8_unicode_ci COMMENT 'Survey instructions',
  acknowledgement text COLLATE utf8_unicode_ci COMMENT 'Survey acknowledgement',
  question_by_section int(1) NOT NULL DEFAULT '0' COMMENT '0 = one-page survey',
  question_auto_numbering int(1) NOT NULL DEFAULT '1',
  survey_enabled int(1) NOT NULL DEFAULT '1' COMMENT '0 = Form, 1 = Survey, 2 = Both',
  save_and_return int(1) NOT NULL DEFAULT '0',
  logo int(10) DEFAULT NULL COMMENT 'FK for redcap_edocs_metadata',
  hide_title int(1) NOT NULL DEFAULT '0',
  email_field varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Field name that stores participant email',
  PRIMARY KEY (survey_id),
  UNIQUE KEY logo (logo),
  UNIQUE KEY project_form (project_id),
  KEY project_id (project_id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Table for survey data';

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_surveys_emails'
--

CREATE TABLE redcap_surveys_emails (
  email_id int(10) NOT NULL AUTO_INCREMENT,
  survey_id int(10) DEFAULT NULL,
  email_subject text COLLATE utf8_unicode_ci,
  email_content text COLLATE utf8_unicode_ci,
  email_sender int(10) DEFAULT NULL COMMENT 'FK ui_id from redcap_user_information',
  email_sent datetime DEFAULT NULL,
  PRIMARY KEY (email_id),
  KEY survey_id (survey_id),
  KEY email_sender (email_sender)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Track emails sent out';

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_surveys_emails_recipients'
--

CREATE TABLE redcap_surveys_emails_recipients (
  email_recip_id int(10) NOT NULL AUTO_INCREMENT,
  email_id int(10) DEFAULT NULL COMMENT 'FK redcap_surveys_emails',
  participant_id int(10) DEFAULT NULL COMMENT 'FK redcap_surveys_participants',
  PRIMARY KEY (email_recip_id),
  KEY emt_id (email_id),
  KEY participant_id (participant_id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Track email recipients';

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_surveys_participants'
--

CREATE TABLE redcap_surveys_participants (
  participant_id int(10) NOT NULL AUTO_INCREMENT,
  survey_id int(10) DEFAULT NULL,
  arm_id int(10) DEFAULT NULL,
  `hash` varchar(6) CHARACTER SET latin1 COLLATE latin1_general_cs DEFAULT NULL,
  legacy_hash varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Migrated from RS',
  participant_email varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'NULL if public survey',
  participant_identifier varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (participant_id),
  UNIQUE KEY `hash` (`hash`),
  UNIQUE KEY legacy_hash (legacy_hash),
  KEY survey_id (survey_id),
  KEY arm_id (arm_id),
  KEY participant_email (participant_email),
  KEY survey_arm_email (survey_id,arm_id,participant_email)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Table for survey data';

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_surveys_response'
--

CREATE TABLE redcap_surveys_response (
  response_id int(11) NOT NULL AUTO_INCREMENT,
  participant_id int(10) DEFAULT NULL,
  record varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  first_submit_time datetime DEFAULT NULL,
  completion_time datetime DEFAULT NULL,
  return_code varchar(8) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (response_id),
  KEY return_code (return_code),
  KEY participant_id (participant_id),
  KEY participant_record (participant_id,record)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `redcap_surveys`
--
ALTER TABLE `redcap_surveys`
  ADD CONSTRAINT redcap_surveys_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_surveys_ibfk_2 FOREIGN KEY (logo) REFERENCES redcap_edocs_metadata (doc_id) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `redcap_surveys_emails`
--
ALTER TABLE `redcap_surveys_emails`
  ADD CONSTRAINT redcap_surveys_emails_ibfk_1 FOREIGN KEY (survey_id) REFERENCES redcap_surveys (survey_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_surveys_emails_ibfk_2 FOREIGN KEY (email_sender) REFERENCES redcap_user_information (ui_id) ON DELETE SET NULL ON UPDATE SET NULL;

--
-- Constraints for table `redcap_surveys_emails_recipients`
--
ALTER TABLE `redcap_surveys_emails_recipients`
  ADD CONSTRAINT redcap_surveys_emails_recipients_ibfk_1 FOREIGN KEY (email_id) REFERENCES redcap_surveys_emails (email_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_surveys_emails_recipients_ibfk_2 FOREIGN KEY (participant_id) REFERENCES redcap_surveys_participants (participant_id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `redcap_surveys_participants`
--
ALTER TABLE `redcap_surveys_participants`
  ADD CONSTRAINT redcap_surveys_participants_ibfk_1 FOREIGN KEY (survey_id) REFERENCES redcap_surveys (survey_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_surveys_participants_ibfk_2 FOREIGN KEY (arm_id) REFERENCES redcap_events_arms (arm_id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `redcap_surveys_response`
--
ALTER TABLE `redcap_surveys_response`
  ADD CONSTRAINT redcap_surveys_response_ibfk_1 FOREIGN KEY (participant_id) REFERENCES redcap_surveys_participants (participant_id) ON DELETE CASCADE ON UPDATE CASCADE;

CREATE TABLE redcap_project_checklist (
  list_id int(11) NOT NULL AUTO_INCREMENT,
  project_id int(5) DEFAULT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (list_id),
  UNIQUE KEY project_name (project_id,`name`),
  KEY project_id (project_id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `redcap_project_checklist`
  ADD CONSTRAINT redcap_project_checklist_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE;
  
-- Modify existing FK
ALTER TABLE  `redcap_edocs_metadata` DROP FOREIGN KEY  `redcap_edocs_metadata_ibfk_1` ;
ALTER TABLE  `redcap_edocs_metadata` ADD FOREIGN KEY (  `project_id` ) REFERENCES  `redcap_projects` (
`project_id`
) ON DELETE SET NULL ON UPDATE CASCADE ;

-- Add more space for slider label text (255 chars)
ALTER TABLE `redcap_metadata` CHANGE `element_validation_type` `element_validation_type` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ,
	CHANGE `element_validation_min` `element_validation_min` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ,
	CHANGE `element_validation_max` `element_validation_max` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
ALTER TABLE `redcap_metadata_temp` CHANGE `element_validation_type` `element_validation_type` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ,
	CHANGE `element_validation_min` `element_validation_min` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ,
	CHANGE `element_validation_max` `element_validation_max` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
ALTER TABLE `redcap_metadata_archive` CHANGE `element_validation_type` `element_validation_type` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ,
	CHANGE `element_validation_min` `element_validation_min` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ,
	CHANGE `element_validation_max` `element_validation_max` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;

-- Add FK for DAGS in user_rights
update redcap_user_rights set group_id = null where group_id not in (select group_id from redcap_data_access_groups);
ALTER TABLE `redcap_user_rights` ADD INDEX ( `group_id` );
ALTER TABLE `redcap_user_rights` ADD FOREIGN KEY ( `group_id` ) REFERENCES `redcap_data_access_groups` (`group_id`) 
	ON DELETE SET NULL ON UPDATE CASCADE ;

-- Modify language in existing logs (change "database" to "project")
update redcap_log_event set description = replace(description, 'database', 'project') where description like '%database%';

-- Remove fields no longer used
ALTER TABLE `redcap_projects`
  DROP `context_detail`,
  DROP `enable_alter_record_pulldown`,
  DROP `pulldown_concat_item1`,
  DROP `custom_text1`,
  DROP `record_select1`,
  DROP `pulldown_concat_item2`,
  DROP `custom_text2`,
  DROP `record_select2`,
  DROP `pulldown_concat_item3`;
delete from redcap_config where field_name = 'redcap_survey_url';
  
-- Add image/file attachment attribute to metadata
ALTER TABLE `redcap_metadata` 
	ADD `edoc_id` INT( 10 ) NULL COMMENT 'image/file attachment', 
	ADD  `edoc_display_img` INT( 1 ) NOT NULL DEFAULT  '0',
	ADD  `custom_alignment` ENUM(  'LH',  'LV',  'RH',  'RV' ) NULL COMMENT  'RV = NULL = default',
	ADD  `stop_actions` TEXT NULL,
	ADD  `question_num` VARCHAR( 50 ) NULL,
	ADD INDEX ( `edoc_id` );
ALTER TABLE `redcap_metadata_temp` 
	ADD `edoc_id` INT( 10 ) NULL COMMENT 'image/file attachment', 
	ADD  `edoc_display_img` INT( 1 ) NOT NULL DEFAULT  '0',
	ADD  `custom_alignment` ENUM(  'LH',  'LV',  'RH',  'RV' ) NULL COMMENT  'RV = NULL = default',
	ADD  `stop_actions` TEXT NULL,
	ADD  `question_num` VARCHAR( 50 ) NULL,
	ADD INDEX ( `edoc_id` );
ALTER TABLE `redcap_metadata_archive` 
	ADD `edoc_id` INT( 10 ) NULL COMMENT 'image/file attachment' AFTER `field_req` , 
	ADD  `edoc_display_img` INT( 1 ) NOT NULL DEFAULT  '0'  AFTER `edoc_id`,
	ADD  `custom_alignment` ENUM(  'LH',  'LV',  'RH',  'RV' ) NULL COMMENT  'RV = NULL = default' AFTER `edoc_display_img`,
	ADD  `stop_actions` TEXT NULL AFTER `custom_alignment`,
	ADD  `question_num` VARCHAR( 50 ) NULL AFTER `stop_actions`,
	ADD INDEX ( `edoc_id` );
ALTER TABLE `redcap_metadata` ADD FOREIGN KEY ( `edoc_id` ) 
	REFERENCES `redcap_edocs_metadata` (`doc_id`) ON DELETE SET NULL ON UPDATE CASCADE ;
ALTER TABLE `redcap_metadata_temp` ADD FOREIGN KEY ( `edoc_id` ) 
	REFERENCES `redcap_edocs_metadata` (`doc_id`) ON DELETE SET NULL ON UPDATE CASCADE ;
ALTER TABLE `redcap_metadata_archive` ADD FOREIGN KEY ( `edoc_id` ) 
	REFERENCES `redcap_edocs_metadata` (`doc_id`) ON DELETE SET NULL ON UPDATE CASCADE ;

-- Remove all line breaks in field labels in metadata because 4.0 will now convert \n to <br> when displaying on page.
update redcap_metadata set element_label = replace(element_label,'\r',''), element_label = replace(element_label,'\n',' ');
update redcap_metadata_temp set element_label = replace(element_label,'\r',''), element_label = replace(element_label,'\n',' ');
update redcap_metadata_archive set element_label = replace(element_label,'\r',''), element_label = replace(element_label,'\n',' ');


CREATE TABLE redcap_actions (
  action_id int(10) NOT NULL AUTO_INCREMENT,
  project_id int(10) DEFAULT NULL,
  action_trigger enum('MANUAL','ENDOFSURVEY','SURVEYQUESTION') COLLATE utf8_unicode_ci DEFAULT NULL,
  action_response enum('NONE','EMAIL','STOPSURVEY','PROMPT') COLLATE utf8_unicode_ci DEFAULT NULL,
  custom_text text COLLATE utf8_unicode_ci,
  recipient_id int(5) DEFAULT NULL COMMENT 'FK user_information',
  PRIMARY KEY (action_id),
  KEY project_id (project_id),
  KEY recipient_id (recipient_id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `redcap_actions`
  ADD CONSTRAINT redcap_actions_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_actions_ibfk_2 FOREIGN KEY (recipient_id) REFERENCES redcap_user_information (ui_id) ON DELETE CASCADE ON UPDATE CASCADE;

 
--
-- Table structure for table 'redcap_surveys_banned_ips'
--

CREATE TABLE redcap_surveys_banned_ips (
  ip varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  time_of_ban timestamp NULL DEFAULT NULL,
  PRIMARY KEY (ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_surveys_ip_cache'
--

CREATE TABLE redcap_surveys_ip_cache (
  ip_hash varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` timestamp NULL DEFAULT NULL,
  KEY `timestamp` (`timestamp`),
  KEY ip_hash (ip_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Update logging
update redcap_log_event set description = 'Rename data collection instrument' where description = 'Rename data entry form';
update redcap_log_event set description = 'Create data collection instrument' where description = 'Create data entry form';
update redcap_log_event set description = 'Reorder data collection instruments' where description = 'Reorder data entry forms';
update redcap_log_event set description = 'Delete data collection instrument' where description = 'Delete data entry form';

-- New global and project-level values
insert into redcap_config select 'login_logo', value from redcap_config where field_name = 'headerlogo';
insert into redcap_config values ('display_project_logo_institution', '1');
ALTER TABLE  `redcap_projects` ADD  `display_project_logo_institution` INT( 1 ) NOT NULL DEFAULT '1';
insert into redcap_config values ('enable_url_shortener', '1');

-- Field to denote if imported from REDCap Survey
ALTER TABLE  `redcap_projects` ADD  `imported_from_rs` INT( 1 ) NOT NULL DEFAULT  '0' COMMENT  'If imported from REDCap Survey';

-- Other
update redcap_projects set created_by = null where created_by not in (select ui_id from redcap_user_information);
ALTER TABLE  `redcap_projects` ADD INDEX (  `created_by` );
ALTER TABLE  `redcap_projects` ADD FOREIGN KEY (  `created_by` ) REFERENCES  `redcap_user_information` (`ui_id`) 
	ON DELETE SET NULL ON UPDATE CASCADE ;


--
-- Table structure for table 'redcap_migration_script'
--

CREATE TABLE redcap_migration_script (
  id int(5) NOT NULL AUTO_INCREMENT,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  username varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  script longblob,
  PRIMARY KEY (id),
  KEY username (username)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Holds SQL for migrating REDCap Survey surveys';

ALTER TABLE  `redcap_user_rights` ADD  `participants` INT( 1 ) NOT NULL DEFAULT  '1';
INSERT INTO `redcap_config` (`field_name`, `value`) VALUES ('enable_surveys', '1');

-- SQL TO CREATE A REDCAP DEMO SURVEY --
set @project_title = 'Example Survey';
-- Obtain default values --
set @institution = (select value from redcap_config where field_name = 'institution' limit 1);
set @site_org_type = (select value from redcap_config where field_name = 'site_org_type' limit 1);
set @grant_cite = (select value from redcap_config where field_name = 'grant_cite' limit 1);
set @project_contact_name = (select value from redcap_config where field_name = 'project_contact_name' limit 1);
set @project_contact_email = (select value from redcap_config where field_name = 'project_contact_email' limit 1);
set @project_contact_prod_changes_name = (select value from redcap_config where field_name = 'project_contact_prod_changes_name' limit 1);
set @project_contact_prod_changes_email = (select value from redcap_config where field_name = 'project_contact_prod_changes_email' limit 1);
set @headerlogo = (select value from redcap_config where field_name = 'headerlogo' limit 1);
-- Create project --
INSERT INTO `redcap_projects` 
(project_name, app_title, status, count_project, auth_meth, creation_time, production_time, institution, site_org_type, grant_cite, project_contact_name, project_contact_email, project_contact_prod_changes_name, project_contact_prod_changes_email, headerlogo, surveys_enabled, auto_inc_set) VALUES
(concat('redcap_demo_',LEFT(MD5(RAND()),6)), @project_title, 1, 0, 'none', now(), now(), @institution, @site_org_type, @grant_cite, @project_contact_name, @project_contact_email, @project_contact_prod_changes_name, @project_contact_prod_changes_email, @headerlogo, 2, 1);
set @project_id = LAST_INSERT_ID();
-- User rights --
INSERT INTO `redcap_user_rights` (`project_id`, `username`, `expiration`, `group_id`, `lock_record`, `data_export_tool`, `data_import_tool`, 
`data_comparison_tool`, `data_logging`, `file_repository`, `double_data`, `user_rights`, `data_access_groups`, `graphical`, `reports`, 
`design`, `calendar`, `data_entry`, `participants`) VALUES
(@project_id, 'site_admin', NULL, NULL, 0, 1, 0, 1, 1, 1, 0, 0, 0, 1, 1, 0, 0, '[survey,1]', 0);
-- Create single arm --
INSERT INTO redcap_events_arms (project_id, arm_num, arm_name) VALUES (@project_id, 1, 'Arm 1');
set @arm_id = LAST_INSERT_ID();
-- Create single event --
INSERT INTO redcap_events_metadata (arm_id, day_offset, offset_min, offset_max, descrip) VALUES (@arm_id, 0, 0, 0, 'Event 1');
set @event_id = LAST_INSERT_ID();
-- Insert into redcap_event_forms --
INSERT INTO redcap_events_forms (event_id, form_name) VALUES
(@event_id, 'survey');
-- Insert into redcap_surveys
INSERT INTO redcap_surveys (project_id, form_name, title, instructions, acknowledgement, question_by_section, question_auto_numbering, survey_enabled, save_and_return, logo, hide_title, email_field) VALUES
(@project_id, NULL, 'Example Survey', '&lt;p style=&quot;margin-top: 10px; margin-right: 0px; margin-bottom: 10px; margin-left: 0px; font-family: Arial, Verdana, Helvetica, sans-serif; font-size: 12px; text-align: left; line-height: 1.5em; max-width: 700px; clear: both; padding: 0px;&quot;&gt;These are your survey instructions that you would enter for your survey participants. You may put whatever text you like here, which may include information about the purpose of the survey, who is taking the survey, or how to take the survey.&lt;/p&gt;<br>&lt;p style=&quot;margin-top: 10px; margin-right: 0px; margin-bottom: 10px; margin-left: 0px; font-family: Arial, Verdana, Helvetica, sans-serif; font-size: 12px; text-align: left; line-height: 1.5em; max-width: 700px; clear: both; padding: 0px;&quot;&gt;Surveys can use a single survey link for all respondents, which can be posted on a webpage or emailed out from your email application of choice.&amp;nbsp;&lt;strong&gt;By default, all survey responses are collected anonymously&lt;/strong&gt;&amp;nbsp;(that is, unless your survey asks for name, email, or other identifying information).&amp;nbsp;If you wish to track individuals who have taken your survey, you may upload a list of email addresses into a Participant List within REDCap, in which you can have REDCap send them an email invitation, which will track if they have taken the survey and when it was taken. This method still collects responses anonymously, but if you wish to identify an individual respondent\'s answers, you may do so by also providing an Identifier in your Participant List. Of course, in that case you may want to inform your respondents in your survey\'s instructions that their responses are not being collected anonymously and can thus be traced back to them.&lt;/p&gt;', '&lt;p&gt;&lt;strong&gt;Thank you for taking the survey.&lt;/strong&gt;&lt;/p&gt;<br>&lt;p&gt;Have a nice day!&lt;/p&gt;', 0, 0, 2, 1, NULL, 0, NULL);
-- Checklist
insert into redcap_project_checklist (project_id, name) values (@project_id, 'setup_survey');
-- Insert into redcap_metadata --
INSERT INTO redcap_metadata (project_id, field_name, field_phi, form_name, form_menu_description, field_order, field_units, element_preceding_header, element_type, element_label, element_enum, element_note, element_validation_type, element_validation_min, element_validation_max, element_validation_checktype, branching_logic, field_req, edoc_id, edoc_display_img, custom_alignment, stop_actions, question_num) VALUES
(@project_id, 'participant_id', NULL, 'survey', 'Survey', 1, NULL, NULL, 'text', 'Participant ID', NULL, NULL, NULL, NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'radio', NULL, 'survey', NULL, 2, NULL, 'Section 1 (This is a section header with descriptive text. It only provides informational text and is used to divide the survey into sections for organization. If the survey is set to be displayed as "one section per page", then these section headers will begin each new page of the survey.)', 'radio', 'You may create MULTIPLE CHOICE questions and set the answer choices for them. You can have as many answer choices as you need. This multiple choice question is rendered as RADIO buttons.', '1, Choice One \\n 2, Choice Two \\n 3, Choice Three \\n 4, Etc.', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'dropdown', NULL, 'survey', NULL, 3, NULL, NULL, 'select', 'You may also set multiple choice questions as DROP-DOWN MENUs.', '1, Choice One \\n 2, Choice Two \\n 3, Choice Three \\n 4, Etc.', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'textbox', NULL, 'survey', NULL, 4, NULL, NULL, 'text', 'This is a TEXT BOX, which allows respondents to enter a small amount of text. A Text Box can be validated, if needed, as a number, integer, phone number, email, or zipcode. If validated as a number or integer, you may also set the minimum and/or maximum allowable values.\n\nThis question has "number" validation set with a minimum of 1 and a maximum of 10. ', NULL, NULL, 'float', '1', '10', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'ma', NULL, 'survey', NULL, 5, NULL, NULL, 'checkbox', 'This type of multiple choice question, known as CHECKBOXES, allows for more than one answer choice to be selected, whereas radio buttons and drop-downs only allow for one choice.', '1, Choice One \\n 2, Choice Two \\n 3, Choice Three \\n 4, Select as many as you like', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'yn', NULL, 'survey', NULL, 6, NULL, NULL, 'yesno', 'You can create YES-NO questions.<br><br>This question has vertical alignment of choices on the right.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'tf', NULL, 'survey', NULL, 7, NULL, NULL, 'truefalse', 'And you can also create TRUE-FALSE questions.<br><br>This question has horizontal alignment.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, 'RH', NULL, NULL),
(@project_id, 'date', NULL, 'survey', NULL, 8, NULL, NULL, 'text', 'DATE questions are also an option. If you click the calendar icon on the right, a pop-up calendar will appear, thus allowing the respondent to easily select a date. Or it can be simply typed in.', NULL, NULL, 'date', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'file', NULL, 'survey', NULL, 9, NULL, NULL, 'file', 'The FILE UPLOAD question type allows respondents to upload any type of document to the survey that you may afterward download and open when viewing your survey results.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'slider', NULL, 'survey', NULL, 10, NULL, NULL, 'slider', 'A SLIDER is a question type that allows the respondent to choose an answer along a continuum. The respondent''s answer is saved as an integer between 0 (far left) and 100 (far right) with a step of 1.', 'You can provide labels above the slider | Middle label | Right-hand label', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'descriptive', NULL, 'survey', NULL, 11, NULL, NULL, 'descriptive', 'You may also use DESCRIPTIVE TEXT to provide informational text within a survey section. ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'radio_branch', NULL, 'survey', NULL, 12, NULL, 'ADVANCED FEATURES: The questions below will illustrate how some advanced survey features are used.', 'radio', 'BRANCHING LOGIC: The question immediately following this one is using branching logic, which means that the question will stay hidden until defined criteria are specified.\n\nFor example, the following question has been set NOT to appear until the respondent selects the second option to the right.  ', '1, This option does nothing. \\n 2, Clicking this option will trigger the branching logic to reveal the next question. \\n 3, This option also does nothing.', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'hidden_branch', NULL, 'survey', NULL, 13, NULL, NULL, 'text', 'HIDDEN QUESTION: This question will only appear when you select the second option of the question immediately above.', NULL, NULL, NULL, 'undefined', 'undefined', 'soft_typed', '[radio_branch] = "2"', 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'stop_actions', NULL, 'survey', NULL, 14, NULL, NULL, 'checkbox', 'STOP ACTIONS may be used with any multiple choice question. Stop actions can be applied to any (or all) answer choices. When that answer choice is selected by a respondent, their survey responses are then saved, and the survey is immediately ended.\n\nThe third option to the right has a stop action.', '1, This option does nothing. \\n 2, This option also does nothing. \\n 3, Click here to trigger the stop action and end the survey.', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, '3', NULL),
(@project_id, 'comment_box', NULL, 'survey', NULL, 15, NULL, NULL, 'textarea', 'If you need the respondent to enter a large amount of text, you may use a NOTES BOX.<br><br>This question has also been set as a REQUIRED QUESTION, so the respondent cannot fully submit the survey until this question has been answered. ANY question type can be set to be required.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, 0, 'LH', NULL, NULL),
(@project_id, 'survey_complete', NULL, 'survey', NULL, 16, NULL, 'Form Status', 'select', 'Complete?', '0, Incomplete \\n 1, Unverified \\n 2, Complete', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL);
