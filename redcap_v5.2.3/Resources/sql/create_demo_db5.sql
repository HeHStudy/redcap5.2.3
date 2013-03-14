
-- SQL TO CREATE A REDCAP DEMO PROJECT --
set @project_title = 'Basic Demography';


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
(project_name, app_title, status, count_project, auth_meth, creation_time, production_time, institution, site_org_type, grant_cite, project_contact_name, project_contact_email, project_contact_prod_changes_name, project_contact_prod_changes_email, headerlogo, display_project_logo_institution) VALUES
(concat('redcap_demo_',LEFT(MD5(RAND()),6)), @project_title, 1, 0, @auth_meth, now(), now(), @institution, @site_org_type, @grant_cite, @project_contact_name, @project_contact_email, @project_contact_prod_changes_name, @project_contact_prod_changes_email, @headerlogo, 0);
set @project_id = LAST_INSERT_ID();
-- User rights --
INSERT INTO `redcap_user_rights` (`project_id`, `username`, `expiration`, `group_id`, `lock_record`, `data_export_tool`, `data_import_tool`, `data_comparison_tool`, `data_logging`, `file_repository`, `double_data`, `user_rights`, `data_access_groups`, `graphical`, `reports`, `design`, `calendar`, `data_entry`, `data_quality_execute`) VALUES
(@project_id, 'site_admin', NULL, NULL, 0, 1, 1, 1, 1, 1, 0, 0, 0, 1, 1, 0, 1, '', 1);
-- Create single arm --
INSERT INTO redcap_events_arms (project_id, arm_num, arm_name) VALUES (@project_id, 1, 'Arm 1');
set @arm_id = LAST_INSERT_ID();
-- Create single event --
INSERT INTO redcap_events_metadata (arm_id, day_offset, offset_min, offset_max, descrip) VALUES (@arm_id, 0, 0, 0, 'Event 1');
set @event_id = LAST_INSERT_ID();
-- Insert into redcap_metadata --
INSERT INTO redcap_metadata (project_id, field_name, field_phi, form_name, form_menu_description, field_order, field_units, element_preceding_header, element_type, element_label, element_enum, element_note, element_validation_type, element_validation_min, element_validation_max, element_validation_checktype, branching_logic, field_req, edoc_id, edoc_display_img, custom_alignment, stop_actions, question_num) VALUES
(@project_id, 'record_id', NULL, 'demographics', 'Basic Demography Form', 1, NULL, NULL, 'text', 'Study ID', NULL, NULL, NULL, NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'first_name', '1', 'demographics', NULL, 3, NULL, 'Contact Information', 'text', 'First Name', NULL, NULL, NULL, NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'last_name', '1', 'demographics', NULL, 4, NULL, NULL, 'text', 'Last Name', NULL, NULL, NULL, NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'address', '1', 'demographics', NULL, 5, NULL, NULL, 'textarea', 'Street, City, State, ZIP', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'telephone', '1', 'demographics', NULL, 6, NULL, NULL, 'text', 'Phone number', NULL, 'Include Area Code', 'phone', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'email', '1', 'demographics', NULL, 8, NULL, NULL, 'text', 'E-mail', NULL, NULL, 'email', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'dob', '1', 'demographics', NULL, 8.1, NULL, NULL, 'text', 'Date of birth', NULL, NULL, 'date_ymd', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'age', NULL, 'demographics', NULL, 8.2, NULL, NULL, 'calc', 'Age (years)', 'rounddown(datediff([dob],''today'',''y''))', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'ethnicity', NULL, 'demographics', NULL, 9, NULL, NULL, 'radio', 'Ethnicity', '0, Hispanic or Latino \\n 1, NOT Hispanic or Latino \\n 2, Unknown / Not Reported', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, 'LH', NULL, NULL),
(@project_id, 'race', NULL, 'demographics', NULL, 10, NULL, NULL, 'select', 'Race', '0, American Indian/Alaska Native \\n 1, Asian \\n 2, Native Hawaiian or Other Pacific Islander \\n 3, Black or African American \\n 4, White \\n 5, More Than One Race \\n 6, Unknown / Not Reported', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'sex', NULL, 'demographics', NULL, 11, NULL, NULL, 'radio', 'Gender', '0, Female \\n 1, Male', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'height', NULL, 'demographics', NULL, 19, 'cm', NULL, 'text', 'Height (cm)', NULL, NULL, 'float', '130', '215', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'weight', NULL, 'demographics', NULL, 20, 'kilograms', NULL, 'text', 'Weight (kilograms)', NULL, NULL, 'int', '35', '200', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'bmi', NULL, 'demographics', NULL, 21, 'kilograms', NULL, 'calc', 'BMI', 'round(([weight]*10000)/(([height])^(2)),1)', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'comments', NULL, 'demographics', NULL, 22, NULL, 'General Comments', 'textarea', 'Comments', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'demographics_complete', NULL, 'demographics', NULL, 23, NULL, 'Form Status', 'select', 'Complete?', '0, Incomplete \\n 1, Unverified \\n 2, Complete', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL);

INSERT INTO `redcap_projects_templates` (`project_id`, `title`, `description`, `enabled`) 
	VALUES (@project_id,  @project_title,  'Contains a single data collection instrument to capture basic demographic information.',  '1');