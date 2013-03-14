
-- SQL TO CREATE A REDCAP DEMO PROJECT --
set @project_title = 'Multiple Surveys (classic)';
-- Obtain default values --
set @institution = (select value from redcap_config where field_name = 'institution' limit 1);
set @site_org_type = (select value from redcap_config where field_name = 'site_org_type' limit 1);
set @grant_cite = (select value from redcap_config where field_name = 'grant_cite' limit 1);
set @project_contact_name = (select value from redcap_config where field_name = 'project_contact_name' limit 1);
set @project_contact_email = (select value from redcap_config where field_name = 'project_contact_email' limit 1);
set @project_contact_prod_changes_name = (select value from redcap_config where field_name = 'project_contact_prod_changes_name' limit 1);
set @project_contact_prod_changes_email = (select value from redcap_config where field_name = 'project_contact_prod_changes_email' limit 1);
set @headerlogo = (select value from redcap_config where field_name = 'headerlogo' limit 1);
set @auth_meth = (select value from redcap_config where field_name = 'auth_meth_global' limit 1);
-- Create project --
INSERT INTO `redcap_projects` 
(survey_email_participant_field, project_name, app_title, status, count_project, auth_meth, creation_time, production_time, institution, site_org_type, grant_cite, project_contact_name, project_contact_email, project_contact_prod_changes_name, project_contact_prod_changes_email, headerlogo, surveys_enabled, auto_inc_set, display_project_logo_institution) VALUES
('email',concat('redcap_demo_',LEFT(MD5(RAND()),6)), @project_title, 1, 0, @auth_meth, now(), now(), @institution, @site_org_type, @grant_cite, @project_contact_name, @project_contact_email, @project_contact_prod_changes_name, @project_contact_prod_changes_email, @headerlogo, 1, 1, 0);
set @project_id = LAST_INSERT_ID();
-- User rights --
INSERT INTO `redcap_user_rights` (`project_id`, `username`, `expiration`, `group_id`, `lock_record`, `data_export_tool`, `data_import_tool`, 
`data_comparison_tool`, `data_logging`, `file_repository`, `double_data`, `user_rights`, `data_access_groups`, `graphical`, `reports`, `design`, 
`calendar`, `data_entry`, `participants`, `data_quality_execute`) VALUES
(@project_id, 'site_admin', NULL, NULL, 0, 1, 0, 1, 1, 1, 0, 0, 0, 1, 1, 0, 1, '', 0, 1);
-- Create single arm --
INSERT INTO redcap_events_arms (project_id, arm_num, arm_name) VALUES (@project_id, 1, 'Arm 1');
set @arm_id = LAST_INSERT_ID();
-- Create single event --
INSERT INTO redcap_events_metadata (arm_id, day_offset, offset_min, offset_max, descrip) VALUES (@arm_id, 0, 0, 0, 'Event 1');
set @event_id = LAST_INSERT_ID();
-- Insert into redcap_surveys
INSERT INTO redcap_surveys (project_id, form_name, title, instructions, acknowledgement, question_by_section, question_auto_numbering, survey_enabled, save_and_return, logo, hide_title, view_results, min_responses_view_results, check_diversity_view_results, end_survey_redirect_url, end_survey_redirect_url_append_id, survey_expiration) VALUES
(@project_id, 'participant_info_survey', 'Follow-Up Survey', '&lt;p&gt;&lt;strong&gt;Please complete the survey below.&lt;/strong&gt;&lt;/p&gt;\r\n&lt;p&gt;Thank you!&lt;/p&gt;', '&lt;p&gt;&lt;strong&gt;Thank you for taking the survey.&lt;/strong&gt;&lt;/p&gt;\r\n&lt;p&gt;Have a nice day!&lt;/p&gt;', 0, 1, 1, 1, NULL, 0, 0, 10, 0, NULL, 0, NULL),
(@project_id, 'participant_morale_questionnaire', 'Patient Morale Questionnaire', '&lt;p&gt;&lt;strong&gt;Please complete the survey below.&lt;/strong&gt;&lt;/p&gt;\r\n&lt;p&gt;Thank you!&lt;/p&gt;', '&lt;p&gt;&lt;strong&gt;Thank you for taking the survey.&lt;/strong&gt;&lt;/p&gt;\r\n&lt;p&gt;Have a nice day!&lt;/p&gt;', 0, 1, 1, 1, NULL, 0, 0, 10, 0, NULL, 0, NULL),
(@project_id, 'prescreening_survey', 'Pre-Screening Survey', '&lt;p&gt;&lt;strong&gt;Please complete the survey below.&lt;/strong&gt;&lt;/p&gt;\r\n&lt;p&gt;Thank you!&lt;/p&gt;', '&lt;p&gt;&lt;strong&gt;Thank you for taking the survey.&lt;/strong&gt;&lt;/p&gt;\r\n&lt;p&gt;Have a nice day!&lt;/p&gt;', 0, 1, 1, 0, NULL, 0, 0, 10, 0, NULL, 0, NULL);
-- Insert into redcap_metadata --
INSERT INTO redcap_metadata (project_id, field_name, field_phi, form_name, form_menu_description, field_order, field_units, element_preceding_header, element_type, element_label, element_enum, element_note, element_validation_type, element_validation_min, element_validation_max, element_validation_checktype, branching_logic, field_req, edoc_id, edoc_display_img, custom_alignment, stop_actions, question_num, grid_name, misc) VALUES
(@project_id, 'participant_id', NULL, 'prescreening_survey', 'Pre-Screening Survey', 1, NULL, NULL, 'text', 'Participant ID', NULL, NULL, NULL, NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'dob', NULL, 'prescreening_survey', NULL, 2, NULL, 'Please fill out the information below.', 'text', 'Date of birth', NULL, NULL, 'date_ymd', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'email', '1', 'prescreening_survey', NULL, 2.1, NULL, NULL, 'text', 'E-mail address', NULL, NULL, 'email', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'has_diabetes', NULL, 'prescreening_survey', NULL, 3, NULL, NULL, 'truefalse', 'I currently have Type 2 Diabetes', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'consent', NULL, 'prescreening_survey', NULL, 4, NULL, NULL, 'checkbox', 'By checking this box, I certify that I am at least 18 years old and that I give my consent freely to participant in this study.', '1, I consent', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'prescreening_survey_complete', NULL, 'prescreening_survey', NULL, 5, NULL, 'Form Status', 'select', 'Complete?', '0, Incomplete \\n 1, Unverified \\n 2, Complete', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'first_name', '1', 'participant_info_survey', 'Participant Info Survey', 6, NULL, 'As a participant in this study, please answer the questions below. Thank you!', 'text', 'First Name', NULL, NULL, NULL, NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'last_name', '1', 'participant_info_survey', NULL, 7, NULL, NULL, 'text', 'Last Name', NULL, NULL, NULL, NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'address', '1', 'participant_info_survey', NULL, 8, NULL, NULL, 'textarea', 'Street, City, State, ZIP', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'telephone_1', '1', 'participant_info_survey', NULL, 9, NULL, NULL, 'text', 'Phone number', NULL, 'Include Area Code', 'phone', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'ethnicity', NULL, 'participant_info_survey', NULL, 11, NULL, NULL, 'radio', 'Ethnicity', '0, Hispanic or Latino \\n 1, NOT Hispanic or Latino \\n 2, Unknown / Not Reported', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, 'LH', NULL, NULL, NULL, NULL),
(@project_id, 'race', NULL, 'participant_info_survey', NULL, 12, NULL, NULL, 'select', 'Race', '0, American Indian/Alaska Native \\n 1, Asian \\n 2, Native Hawaiian or Other Pacific Islander \\n 3, Black or African American \\n 4, White \\n 5, More Than One Race \\n 6, Unknown / Not Reported', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'sex', NULL, 'participant_info_survey', NULL, 13, NULL, NULL, 'radio', 'Gender', '0, Female \\n 1, Male', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'height', NULL, 'participant_info_survey', NULL, 14, NULL, NULL, 'text', 'Height (cm)', NULL, NULL, 'float', '130', '215', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'weight', NULL, 'participant_info_survey', NULL, 15, NULL, NULL, 'text', 'Weight (kilograms)', NULL, NULL, 'int', '35', '200', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'participant_info_survey_complete', NULL, 'participant_info_survey', NULL, 16, NULL, 'Form Status', 'select', 'Complete?', '0, Incomplete \\n 1, Unverified \\n 2, Complete', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'pmq1', NULL, 'participant_morale_questionnaire', 'Participant Morale Questionnaire', 17, NULL, 'As a participant in this study, please answer the questions below. Thank you!', 'select', 'On average, how many pills did you take each day last week?', '0, Less than 5 \\n 1, 5-10 \\n 2, 6-15 \\n 3, Over 15', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'pmq2', NULL, 'participant_morale_questionnaire', NULL, 18, NULL, NULL, 'select', 'Using the handout, which level of dependence do you feel you are currently at?', '0, 0 \\n 1, 1 \\n 2, 2 \\n 3, 3 \\n 4, 4 \\n 5, 5', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'pmq3', NULL, 'participant_morale_questionnaire', NULL, 19, NULL, NULL, 'yesno', 'Would you be willing to discuss your experiences with a psychiatrist?', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'pmq4', NULL, 'participant_morale_questionnaire', NULL, 20, NULL, NULL, 'select', 'How open are you to further testing?', '0, Not open \\n 1, Undecided \\n 2, Very open', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'participant_morale_questionnaire_complete', NULL, 'participant_morale_questionnaire', NULL, 21, NULL, 'Form Status', 'select', 'Complete?', '0, Incomplete \\n 1, Unverified \\n 2, Complete', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'complete_study', NULL, 'completion_data', 'Completion Data (to be entered by study personnel only)', 22, NULL, 'This form is to be filled out by study personnel.', 'yesno', 'Has patient completed study?', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'withdraw_date', NULL, 'completion_data', NULL, 23, NULL, NULL, 'text', 'Put a date if patient withdrew study', NULL, NULL, 'date_ymd', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'withdraw_reason', NULL, 'completion_data', NULL, 24, NULL, NULL, 'select', 'Reason patient withdrew from study', '0, Non-compliance \\n 1, Did not wish to continue in study \\n 2, Could not tolerate the supplement \\n 3, Hospitalization \\n 4, Other', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'date_visit_4', NULL, 'completion_data', NULL, 25, NULL, NULL, 'text', 'Date of last visit', NULL, NULL, 'date_ymd', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'discharge_date_4', NULL, 'completion_data', NULL, 26, NULL, NULL, 'text', 'Date of hospital discharge', NULL, NULL, 'date_ymd', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'discharge_summary_4', NULL, 'completion_data', NULL, 27, NULL, NULL, 'select', 'Discharge summary in patients binder?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'study_comments', NULL, 'completion_data', NULL, 28, NULL, NULL, 'textarea', 'Comments', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(@project_id, 'completion_data_complete', NULL, 'completion_data', NULL, 29, NULL, 'Form Status', 'select', 'Complete?', '0, Incomplete \\n 1, Unverified \\n 2, Complete', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, NULL);

INSERT INTO `redcap_projects_templates` (`project_id`, `title`, `description`, `enabled`) 
	VALUES (@project_id,  @project_title,  'Contains three surveys and a data entry form. Includes a pre-screening survey followed by two follow-up surveys to capture information from the participant, and then a data entry form for final data to be entered by the study personnel. The project data is captured in classic data collection format.',  '1');