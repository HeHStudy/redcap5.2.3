
-- SQL TO CREATE A REDCAP DEMO PROJECT --
set @project_title = 'Longitudinal Database (1 arm)';


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
(project_name, app_title, repeatforms, status, count_project, auth_meth, creation_time, production_time, institution, site_org_type, grant_cite, project_contact_name, project_contact_email, project_contact_prod_changes_name, project_contact_prod_changes_email, headerlogo, display_project_logo_institution) VALUES
(concat('redcap_demo_',LEFT(MD5(RAND()),6)), @project_title, 1, 1, 0, @auth_meth, now(), now(), @institution, @site_org_type, @grant_cite, @project_contact_name, @project_contact_email, @project_contact_prod_changes_name, @project_contact_prod_changes_email, @headerlogo, 0);
set @project_id = LAST_INSERT_ID();
-- User rights --
INSERT INTO redcap_user_rights (project_id, username, expiration, group_id, lock_record, data_export_tool, data_import_tool, data_comparison_tool, data_logging, file_repository, double_data, user_rights, data_access_groups, graphical, reports, design, calendar, data_entry, `data_quality_execute`) VALUES
(@project_id, 'site_admin', NULL, NULL, 0, 1, 1, 1, 1, 1, 0, 0, 0, 1, 1, 0, 1, '', 1);
-- Create arms --
INSERT INTO redcap_events_arms (project_id, arm_num, arm_name) VALUES(@project_id, 1, 'Drug A');
set @arm_id1 = LAST_INSERT_ID();
-- Create events --
INSERT INTO redcap_events_metadata (arm_id, day_offset, offset_min, offset_max, descrip) VALUES(@arm_id1, 0, 0, 0, 'Enrollment');
set @event_id = LAST_INSERT_ID();
INSERT INTO redcap_events_forms (event_id, form_name) VALUES(@event_id, 'baseline_data');
INSERT INTO redcap_events_forms (event_id, form_name) VALUES(@event_id, 'contact_info');
INSERT INTO redcap_events_forms (event_id, form_name) VALUES(@event_id, 'demographics');
INSERT INTO redcap_events_metadata (arm_id, day_offset, offset_min, offset_max, descrip) VALUES(@arm_id1, 1, 0, 0, 'Dose 1');
set @event_id = LAST_INSERT_ID();
INSERT INTO redcap_events_forms (event_id, form_name) VALUES(@event_id, 'patient_morale_questionnaire');
INSERT INTO redcap_events_metadata (arm_id, day_offset, offset_min, offset_max, descrip) VALUES(@arm_id1, 3, 0, 0, 'Visit 1');
set @event_id = LAST_INSERT_ID();
INSERT INTO redcap_events_forms (event_id, form_name) VALUES(@event_id, 'patient_morale_questionnaire');
INSERT INTO redcap_events_forms (event_id, form_name) VALUES(@event_id, 'visit_blood_workup');
INSERT INTO redcap_events_forms (event_id, form_name) VALUES(@event_id, 'visit_lab_data');
INSERT INTO redcap_events_forms (event_id, form_name) VALUES(@event_id, 'visit_observed_behavior');
INSERT INTO redcap_events_metadata (arm_id, day_offset, offset_min, offset_max, descrip) VALUES(@arm_id1, 8, 0, 0, 'Dose 2');
set @event_id = LAST_INSERT_ID();
INSERT INTO redcap_events_forms (event_id, form_name) VALUES(@event_id, 'patient_morale_questionnaire');
INSERT INTO redcap_events_metadata (arm_id, day_offset, offset_min, offset_max, descrip) VALUES(@arm_id1, 10, 0, 0, 'Visit 2');
set @event_id = LAST_INSERT_ID();
INSERT INTO redcap_events_forms (event_id, form_name) VALUES(@event_id, 'patient_morale_questionnaire');
INSERT INTO redcap_events_forms (event_id, form_name) VALUES(@event_id, 'visit_blood_workup');
INSERT INTO redcap_events_forms (event_id, form_name) VALUES(@event_id, 'visit_lab_data');
INSERT INTO redcap_events_forms (event_id, form_name) VALUES(@event_id, 'visit_observed_behavior');
INSERT INTO redcap_events_metadata (arm_id, day_offset, offset_min, offset_max, descrip) VALUES(@arm_id1, 15, 0, 0, 'Dose 3');
set @event_id = LAST_INSERT_ID();
INSERT INTO redcap_events_forms (event_id, form_name) VALUES(@event_id, 'patient_morale_questionnaire');
INSERT INTO redcap_events_metadata (arm_id, day_offset, offset_min, offset_max, descrip) VALUES(@arm_id1, 17, 0, 0, 'Visit 3');
set @event_id = LAST_INSERT_ID();
INSERT INTO redcap_events_forms (event_id, form_name) VALUES(@event_id, 'patient_morale_questionnaire');
INSERT INTO redcap_events_forms (event_id, form_name) VALUES(@event_id, 'visit_blood_workup');
INSERT INTO redcap_events_forms (event_id, form_name) VALUES(@event_id, 'visit_lab_data');
INSERT INTO redcap_events_forms (event_id, form_name) VALUES(@event_id, 'visit_observed_behavior');
INSERT INTO redcap_events_metadata (arm_id, day_offset, offset_min, offset_max, descrip) VALUES(@arm_id1, 30, 0, 0, 'Final visit');
set @event_id = LAST_INSERT_ID();
INSERT INTO redcap_events_forms (event_id, form_name) VALUES(@event_id, 'completion_data');
INSERT INTO redcap_events_forms (event_id, form_name) VALUES(@event_id, 'completion_project_questionnaire');
INSERT INTO redcap_events_forms (event_id, form_name) VALUES(@event_id, 'patient_morale_questionnaire');
INSERT INTO redcap_events_forms (event_id, form_name) VALUES(@event_id, 'visit_blood_workup');
INSERT INTO redcap_events_forms (event_id, form_name) VALUES(@event_id, 'visit_observed_behavior');
-- Insert into redcap_metadata --

INSERT INTO redcap_metadata (project_id, field_name, field_phi, form_name, form_menu_description, field_order, field_units, element_preceding_header, element_type, element_label, element_enum, element_note, element_validation_type, element_validation_min, element_validation_max, element_validation_checktype, branching_logic, field_req, edoc_id, edoc_display_img, custom_alignment, stop_actions, question_num) VALUES
(@project_id, 'study_id', NULL, 'demographics', 'Demographics', 1, NULL, NULL, 'text', 'Study ID', NULL, NULL, NULL, NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'date_enrolled', NULL, 'demographics', NULL, 2, NULL, 'Consent Information', 'text', 'Date subject signed consent', NULL, 'YYYY-MM-DD', 'date_ymd', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'patient_document', NULL, 'demographics', NULL, 2.1, NULL, NULL, 'file', 'Upload the patient''s consent form', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'first_name', '1', 'demographics', NULL, 3, NULL, 'Contact Information', 'text', 'First Name', NULL, NULL, NULL, NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'last_name', '1', 'demographics', NULL, 4, NULL, NULL, 'text', 'Last Name', NULL, NULL, NULL, NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'address', '1', 'demographics', NULL, 5, NULL, NULL, 'textarea', 'Street, City, State, ZIP', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'telephone_1', '1', 'demographics', NULL, 6, NULL, NULL, 'text', 'Phone number', NULL, 'Include Area Code', 'phone', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'email', '1', 'demographics', NULL, 8, NULL, NULL, 'text', 'E-mail', NULL, NULL, 'email', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'dob', '1', 'demographics', NULL, 8.1, NULL, NULL, 'text', 'Date of birth', NULL, NULL, 'date_ymd', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'age', NULL, 'demographics', NULL, 8.2, NULL, NULL, 'calc', 'Age (years)', 'rounddown(datediff([dob],''today'',''y''))', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'ethnicity', NULL, 'demographics', NULL, 9, NULL, NULL, 'radio', 'Ethnicity', '0, Hispanic or Latino \\n 1, NOT Hispanic or Latino \\n 2, Unknown / Not Reported', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, 'LH', NULL, NULL),
(@project_id, 'race', NULL, 'demographics', NULL, 10, NULL, NULL, 'select', 'Race', '0, American Indian/Alaska Native \\n 1, Asian \\n 2, Native Hawaiian or Other Pacific Islander \\n 3, Black or African American \\n 4, White \\n 5, More Than One Race \\n 6, Unknown / Not Reported', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'sex', NULL, 'demographics', NULL, 11, NULL, NULL, 'radio', 'Gender', '0, Female \\n 1, Male', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'given_birth', NULL, 'demographics', NULL, 12, NULL, NULL, 'yesno', 'Has the patient given birth before?', NULL, NULL, NULL, NULL, NULL, NULL, '[sex] = "0"', 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'num_children', NULL, 'demographics', NULL, 13, NULL, NULL, 'text', 'How many times has the patient given birth?', NULL, NULL, 'int', '0', NULL, 'soft_typed', '[sex] = "0" and [given_birth] = "1"', 0, NULL, 0, NULL, NULL, NULL);

INSERT INTO redcap_metadata (project_id, field_name, field_phi, form_name, form_menu_description, field_order, field_units, element_preceding_header, element_type, element_label, element_enum, element_note, element_validation_type, element_validation_min, element_validation_max, element_validation_checktype, branching_logic, field_req, edoc_id, edoc_display_img, custom_alignment, stop_actions, question_num, grid_name, misc) VALUES
(@project_id, 'gym', NULL, 'demographics', NULL, 14, NULL, 'Please provide the patient''s weekly schedule for the activities below.', 'checkbox', 'Gym (Weight Training)', '0, Monday \\n 1, Tuesday \\n 2, Wednesday \\n 3, Thursday \\n 4, Friday', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL, 'weekly_schedule', NULL),
(@project_id, 'aerobics', NULL, 'demographics', NULL, 15, NULL, NULL, 'checkbox', 'Aerobics', '0, Monday \\n 1, Tuesday \\n 2, Wednesday \\n 3, Thursday \\n 4, Friday', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL, 'weekly_schedule', NULL),
(@project_id, 'eat', NULL, 'demographics', NULL, 16, NULL, NULL, 'checkbox', 'Eat Out (Dinner/Lunch)', '0, Monday \\n 1, Tuesday \\n 2, Wednesday \\n 3, Thursday \\n 4, Friday', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL, 'weekly_schedule', NULL),
(@project_id, 'drink', NULL, 'demographics', NULL, 17, NULL, NULL, 'checkbox', 'Drink (Alcoholic Beverages)', '0, Monday \\n 1, Tuesday \\n 2, Wednesday \\n 3, Thursday \\n 4, Friday', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL, 'weekly_schedule', NULL);

INSERT INTO redcap_metadata (project_id, field_name, field_phi, form_name, form_menu_description, field_order, field_units, element_preceding_header, element_type, element_label, element_enum, element_note, element_validation_type, element_validation_min, element_validation_max, element_validation_checktype, branching_logic, field_req, edoc_id, edoc_display_img, custom_alignment, stop_actions, question_num) VALUES
(@project_id, 'specify_mood', NULL, 'demographics', NULL, 17.1, NULL, 'Other information', 'slider', 'Specify the patient''s mood', 'Very sad | Indifferent | Very happy', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'meds', NULL, 'demographics', NULL, 17.3, NULL, NULL, 'checkbox', 'Is patient taking any of the following medications? (check all that apply)', '1, Lexapro \\n 2, Celexa \\n 3, Prozac \\n 4, Paxil \\n 5, Zoloft', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'height', NULL, 'demographics', NULL, 19, 'cm', NULL, 'text', 'Height (cm)', NULL, NULL, 'float', '130', '215', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'weight', NULL, 'demographics', NULL, 20, 'kilograms', NULL, 'text', 'Weight (kilograms)', NULL, NULL, 'int', '35', '200', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'bmi', NULL, 'demographics', NULL, 21, 'kilograms', NULL, 'calc', 'BMI', 'round(([weight]*10000)/(([height])^(2)),1)', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'comments', NULL, 'demographics', NULL, 22, NULL, 'General Comments', 'textarea', 'Comments', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'demographics_complete', NULL, 'demographics', NULL, 23, NULL, 'Form Status', 'select', 'Complete?', '0, Incomplete \\n 1, Unverified \\n 2, Complete', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'dialysis_unit_name', NULL, 'contact_info', 'Contact Info', 24, NULL, NULL, 'text', 'Emergency Contact Phone Number', NULL, 'Include Area Code', 'phone', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'dialysis_unit_phone', NULL, 'contact_info', NULL, 25, NULL, NULL, 'radio', 'Confirmed?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'dialysis_schedule_days', NULL, 'contact_info', NULL, 26, NULL, NULL, 'text', 'Next of Kin Contact Name', NULL, NULL, NULL, NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'dialysis_schedule_time', NULL, 'contact_info', NULL, 27, NULL, NULL, 'textarea', 'Next of Kin Contact Address', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'etiology_esrd', NULL, 'contact_info', NULL, 28, NULL, NULL, 'text', 'Next of Kin Contact Phone Number', NULL, 'Include Area Code', 'phone', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'subject_comments', NULL, 'contact_info', NULL, 29, NULL, NULL, 'radio', 'Confirmed?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'contact_info_complete', NULL, 'contact_info', NULL, 30, NULL, 'Form Status', 'select', 'Complete?', '0, Incomplete \\n 1, Unverified \\n 2, Complete', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'height2', NULL, 'baseline_data', 'Baseline Data', 31, NULL, NULL, 'text', 'Height (cm)', NULL, NULL, 'float', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'weight2', NULL, 'baseline_data', NULL, 32, NULL, NULL, 'text', 'Weight (kilograms)', NULL, NULL, 'int', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'bmi2', NULL, 'baseline_data', NULL, 33, NULL, NULL, 'calc', 'BMI', 'round(([weight2]*10000)/(([height2])^(2)),1)', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'prealb_b', NULL, 'baseline_data', NULL, 34, NULL, NULL, 'text', 'Serum Prealbumin (mg/dL)', NULL, NULL, 'float', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'creat_b', NULL, 'baseline_data', NULL, 35, NULL, NULL, 'text', 'Creatinine (mg/dL)', NULL, NULL, 'float', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'npcr_b', NULL, 'baseline_data', NULL, 36, NULL, NULL, 'text', 'Normalized Protein Catabolic Rate (g/kg/d)', NULL, NULL, 'float', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'chol_b', NULL, 'baseline_data', NULL, 37, NULL, NULL, 'text', 'Cholesterol (mg/dL)', NULL, NULL, 'float', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'transferrin_b', NULL, 'baseline_data', NULL, 38, NULL, NULL, 'text', 'Transferrin (mg/dL)', NULL, NULL, 'float', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'baseline_data_complete', NULL, 'baseline_data', NULL, 39, NULL, 'Form Status', 'select', 'Complete?', '0, Incomplete \\n 1, Unverified \\n 2, Complete', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'vld1', NULL, 'visit_lab_data', 'Visit Lab Data', 40, NULL, NULL, 'text', 'Serum Prealbumin (mg/dL)', NULL, NULL, 'float', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'vld2', NULL, 'visit_lab_data', NULL, 41, NULL, NULL, 'text', 'Creatinine (mg/dL)', NULL, NULL, 'float', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'vld3', NULL, 'visit_lab_data', NULL, 42, NULL, NULL, 'text', 'Normalized Protein Catabolic Rate (g/kg/d)', NULL, NULL, 'float', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'vld4', NULL, 'visit_lab_data', NULL, 43, NULL, NULL, 'text', 'Cholesterol (mg/dL)', NULL, NULL, 'float', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'vld5', NULL, 'visit_lab_data', NULL, 44, NULL, NULL, 'text', 'Transferrin (mg/dL)', NULL, NULL, 'float', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'visit_lab_data_complete', NULL, 'visit_lab_data', NULL, 45, NULL, 'Form Status', 'select', 'Complete?', '0, Incomplete \\n 1, Unverified \\n 2, Complete', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'pmq1', NULL, 'patient_morale_questionnaire', 'Patient Morale Questionnaire', 46, NULL, NULL, 'select', 'On average, how many pills did you take each day last week?', '0, less than 5 \\n 1, 5-10 \\n 2, 6-15 \\n 3, over 15', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'pmq2', NULL, 'patient_morale_questionnaire', NULL, 47, NULL, NULL, 'select', 'Using the handout, which level of dependence do you feel you are currently at?', '0, 0 \\n 1, 1 \\n 2, 2 \\n 3, 3 \\n 4, 4 \\n 5, 5', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'pmq3', NULL, 'patient_morale_questionnaire', NULL, 48, NULL, NULL, 'radio', 'Would you be willing to discuss your experiences with a psychiatrist?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'pmq4', NULL, 'patient_morale_questionnaire', NULL, 49, NULL, NULL, 'select', 'How open are you to further testing?', '0, not open \\n 1, undecided \\n 2, very open', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'patient_morale_questionnaire_complete', NULL, 'patient_morale_questionnaire', NULL, 50, NULL, 'Form Status', 'select', 'Complete?', '0, Incomplete \\n 1, Unverified \\n 2, Complete', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'vbw1', NULL, 'visit_blood_workup', 'Visit Blood Workup', 51, NULL, NULL, 'text', 'Serum Prealbumin (mg/dL)', NULL, NULL, 'float', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'vbw2', NULL, 'visit_blood_workup', NULL, 52, NULL, NULL, 'text', 'Creatinine (mg/dL)', NULL, NULL, 'float', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'vbw3', NULL, 'visit_blood_workup', NULL, 53, NULL, NULL, 'text', 'Normalized Protein Catabolic Rate (g/kg/d)', NULL, NULL, 'float', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'vbw4', NULL, 'visit_blood_workup', NULL, 54, NULL, NULL, 'text', 'Cholesterol (mg/dL)', NULL, NULL, 'float', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'vbw5', NULL, 'visit_blood_workup', NULL, 55, NULL, NULL, 'text', 'Transferrin (mg/dL)', NULL, NULL, 'float', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'vbw6', NULL, 'visit_blood_workup', NULL, 56, NULL, NULL, 'radio', 'Blood draw shift?', '0. AM \\n 1, PM', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'vbw7', NULL, 'visit_blood_workup', NULL, 57, NULL, NULL, 'radio', 'Blood draw by', '0, RN \\n 1, LPN \\n 2, nurse assistant \\n 3, doctor', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'vbw8', NULL, 'visit_blood_workup', NULL, 58, NULL, NULL, 'select', 'Level of patient anxiety', '0, not anxious \\n 1, undecided \\n 2, very anxious', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'vbw9', NULL, 'visit_blood_workup', NULL, 59, NULL, NULL, 'select', 'Patient scheduled for future draws?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'visit_blood_workup_complete', NULL, 'visit_blood_workup', NULL, 60, NULL, 'Form Status', 'select', 'Complete?', '0, Incomplete \\n 1, Unverified \\n 2, Complete', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'vob1', NULL, 'visit_observed_behavior', 'Visit Observed Behavior', 61, NULL, 'Was the patient...', 'radio', 'nervous?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'vob2', NULL, 'visit_observed_behavior', NULL, 62, NULL, NULL, 'radio', 'worried?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'vob3', NULL, 'visit_observed_behavior', NULL, 63, NULL, NULL, 'radio', 'scared?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'vob4', NULL, 'visit_observed_behavior', NULL, 64, NULL, NULL, 'radio', 'fidgety?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'vob5', NULL, 'visit_observed_behavior', NULL, 65, NULL, NULL, 'radio', 'crying?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'vob6', NULL, 'visit_observed_behavior', NULL, 66, NULL, NULL, 'radio', 'screaming?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'vob7', NULL, 'visit_observed_behavior', NULL, 67, NULL, NULL, 'textarea', 'other', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'vob8', NULL, 'visit_observed_behavior', NULL, 68, NULL, 'Were you...', 'radio', 'nervous?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'vob9', NULL, 'visit_observed_behavior', NULL, 69, NULL, NULL, 'radio', 'worried?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'vob10', NULL, 'visit_observed_behavior', NULL, 70, NULL, NULL, 'radio', 'scared?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'vob11', NULL, 'visit_observed_behavior', NULL, 71, NULL, NULL, 'radio', 'fidgety?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'vob12', NULL, 'visit_observed_behavior', NULL, 72, NULL, NULL, 'radio', 'crying?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'vob13', NULL, 'visit_observed_behavior', NULL, 73, NULL, NULL, 'radio', 'screaming?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'vob14', NULL, 'visit_observed_behavior', NULL, 74, NULL, NULL, 'textarea', 'other', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'visit_observed_behavior_complete', NULL, 'visit_observed_behavior', NULL, 75, NULL, 'Form Status', 'select', 'Complete?', '0, Incomplete \\n 1, Unverified \\n 2, Complete', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'study_comments', NULL, 'completion_data', 'Completion Data', 76, NULL, NULL, 'textarea', 'Comments', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'complete_study', NULL, 'completion_data', NULL, 77, NULL, NULL, 'select', 'Has patient completed study?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'withdraw_date', NULL, 'completion_data', NULL, 78, NULL, NULL, 'text', 'Put a date if patient withdrew study', NULL, NULL, 'date_ymd', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'date_visit_4', NULL, 'completion_data', NULL, 79, NULL, NULL, 'text', 'Date of last visit', NULL, NULL, 'date_ymd', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'alb_4', NULL, 'completion_data', NULL, 80, NULL, NULL, 'text', 'Serum Albumin (g/dL)', NULL, NULL, 'float', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'prealb_4', NULL, 'completion_data', NULL, 81, NULL, NULL, 'text', 'Serum Prealbumin (mg/dL)', NULL, NULL, 'float', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'creat_4', NULL, 'completion_data', NULL, 82, NULL, NULL, 'text', 'Creatinine (mg/dL)', NULL, NULL, 'date_ymd', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'discharge_date_4', NULL, 'completion_data', NULL, 83, NULL, NULL, 'text', 'Date of hospital discharge', NULL, NULL, 'date_ymd', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'discharge_summary_4', NULL, 'completion_data', NULL, 84, NULL, NULL, 'select', 'Discharge summary in patients binder?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'npcr_4', NULL, 'completion_data', NULL, 85, NULL, NULL, 'text', 'Normalized Protein Catabolic Rate (g/kg/d)', NULL, NULL, 'int', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'chol_4', NULL, 'completion_data', NULL, 86, NULL, NULL, 'text', 'Cholesterol (mg/dL)', NULL, NULL, 'int', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'withdraw_reason', NULL, 'completion_data', NULL, 87, NULL, NULL, 'select', 'Reason patient withdrew from study', '0, Non-compliance \\n 1, Did not wish to continue in study \\n 2, Could not tolerate the supplement \\n 3, Hospitalization \\n 4, Other', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'completion_data_complete', NULL, 'completion_data', NULL, 88, NULL, 'Form Status', 'select', 'Complete?', '0, Incomplete \\n 1, Unverified \\n 2, Complete', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'cpq1', NULL, 'completion_project_questionnaire', 'Completion Project Questionnaire', 89, NULL, NULL, 'text', 'Date of study completion', NULL, NULL, 'date_ymd', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'cpq2', NULL, 'completion_project_questionnaire', NULL, 90, NULL, NULL, 'text', 'Transferrin (mg/dL)', NULL, NULL, 'int', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'cpq3', NULL, 'completion_project_questionnaire', NULL, 91, NULL, NULL, 'text', 'Kt/V', NULL, NULL, 'int', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'cpq4', NULL, 'completion_project_questionnaire', NULL, 92, NULL, NULL, 'text', 'Dry weight (kilograms)', NULL, NULL, 'int', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'cpq5', NULL, 'completion_project_questionnaire', NULL, 93, NULL, NULL, 'text', 'Number of treatments missed', NULL, NULL, 'int', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'cpq6', NULL, 'completion_project_questionnaire', NULL, 94, NULL, NULL, 'select', 'How compliant was the patient in drinking the supplement?', '0, 100 percent \\n 1, 99-75 percent \\n 2, 74-50 percent \\n 3, 49-25 percent \\n 4, 0-24 percent', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'cpq7', NULL, 'completion_project_questionnaire', NULL, 95, NULL, NULL, 'select', 'Was patient hospitalized since last visit?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'cpq8', NULL, 'completion_project_questionnaire', NULL, 96, NULL, NULL, 'select', 'What was the cause of hospitalization?', '1, Vascular access related events \\n 2, CVD events \\n 3, Other', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'cpq9', NULL, 'completion_project_questionnaire', NULL, 97, NULL, NULL, 'text', 'Date of hospital admission', NULL, NULL, 'date_ymd', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'cpq10', NULL, 'completion_project_questionnaire', NULL, 98, NULL, NULL, 'select', 'On average, how many pills did you take each day last week?', '0, less than 5 \\n 1, 5-10 \\n 2, 6-15 \\n 3, over 15', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'cpq11', NULL, 'completion_project_questionnaire', NULL, 99, NULL, NULL, 'select', 'Using the handout, which level of dependence do you feel you are currently at?', '0, 0 \\n 1, 1 \\n 2, 2 \\n 3, 3 \\n 4, 4 \\n 5, 5', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'cpq12', NULL, 'completion_project_questionnaire', NULL, 100, NULL, NULL, 'radio', 'Would you be willing to discuss your experiences with a psychiatrist?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'cpq13', NULL, 'completion_project_questionnaire', NULL, 101, NULL, NULL, 'select', 'How open are you to further testing?', '0, not open \\n 1, undecided \\n 2, very open', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'completion_project_questionnaire_complete', NULL, 'completion_project_questionnaire', NULL, 102, NULL, 'Form Status', 'select', 'Complete?', '0, Incomplete \\n 1, Unverified \\n 2, Complete', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL);

INSERT INTO `redcap_projects_templates` (`project_id`, `title`, `description`, `enabled`) 
	VALUES (@project_id,  @project_title,  'Contains nine data entry forms (beginning with a demography form) for collecting data longitudinally over eight different events.',  '1');