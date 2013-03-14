
-- SQL TO CREATE A REDCAP DEMO PROJECT --
set @project_title = 'Classic Database';


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
(@project_id, 'date_visit_b', NULL, 'baseline_data', 'Baseline Data', 24, NULL, 'Baseline Measurements', 'text', 'Date of baseline visit', NULL, NULL, 'date_ymd', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'date_blood_b', NULL, 'baseline_data', NULL, 25, NULL, NULL, 'text', 'Date blood was drawn', NULL, NULL, 'date_ymd', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'alb_b', NULL, 'baseline_data', NULL, 26, 'g/dL', NULL, 'text', 'Serum Albumin (g/dL)', NULL, NULL, 'int', '3', '5', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'prealb_b', NULL, 'baseline_data', NULL, 27, 'mg/dL', NULL, 'text', 'Serum Prealbumin (mg/dL)', NULL, NULL, 'float', '10', '40', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'creat_b', NULL, 'baseline_data', NULL, 28, 'mg/dL', NULL, 'text', 'Creatinine (mg/dL)', NULL, NULL, 'float', '0.5', '20', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'npcr_b', NULL, 'baseline_data', NULL, 29, 'g/kg/d', NULL, 'text', 'Normalized Protein Catabolic Rate (g/kg/d)', NULL, NULL, 'float', '0.5', '2', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'chol_b', NULL, 'baseline_data', NULL, 30, 'mg/dL', NULL, 'text', 'Cholesterol (mg/dL)', NULL, NULL, 'float', '100', '300', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'transferrin_b', NULL, 'baseline_data', NULL, 31, 'mg/dL', NULL, 'text', 'Transferrin (mg/dL)', NULL, NULL, 'float', '100', '300', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'kt_v_b', NULL, 'baseline_data', NULL, 32, NULL, NULL, 'text', 'Kt/V', NULL, NULL, 'float', '0.9', '3', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'drywt_b', NULL, 'baseline_data', NULL, 33, 'kilograms', NULL, 'text', 'Dry weight (kilograms)', NULL, NULL, 'float', '35', '200', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'plasma1_b', NULL, 'baseline_data', NULL, 34, NULL, NULL, 'select', 'Collected Plasma 1?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'plasma2_b', NULL, 'baseline_data', NULL, 35, NULL, NULL, 'select', 'Collected Plasma 2?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'plasma3_b', NULL, 'baseline_data', NULL, 36, NULL, NULL, 'select', 'Collected Plasma 3?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'serum1_b', NULL, 'baseline_data', NULL, 37, NULL, NULL, 'select', 'Collected Serum 1?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'serum2_b', NULL, 'baseline_data', NULL, 38, NULL, NULL, 'select', 'Collected Serum 2?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'serum3_b', NULL, 'baseline_data', NULL, 39, NULL, NULL, 'select', 'Collected Serum 3?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'sga_b', NULL, 'baseline_data', NULL, 40, NULL, NULL, 'text', 'Subject Global Assessment (score = 1-7)', NULL, NULL, 'float', '0.9', '7.1', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'date_supplement_dispensed', NULL, 'baseline_data', NULL, 41, NULL, NULL, 'text', 'Date patient begins supplement', NULL, NULL, 'date_ymd', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'baseline_data_complete', NULL, 'baseline_data', NULL, 42, NULL, 'Form Status', 'select', 'Complete?', '0, Incomplete \\n 1, Unverified \\n 2, Complete', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'date_visit_1', NULL, 'month_1_data', 'Month 1 Data', 43, NULL, 'Month 1', 'text', 'Date of Month 1 visit', NULL, NULL, 'date_ymd', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'alb_1', NULL, 'month_1_data', NULL, 44, 'g/dL', NULL, 'text', 'Serum Albumin (g/dL)', NULL, NULL, 'float', '3', '5', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'prealb_1', NULL, 'month_1_data', NULL, 45, 'mg/dL', NULL, 'text', 'Serum Prealbumin (mg/dL)', NULL, NULL, 'float', '10', '40', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'creat_1', NULL, 'month_1_data', NULL, 46, 'mg/dL', NULL, 'text', 'Creatinine (mg/dL)', NULL, NULL, 'float', '0.5', '20', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'npcr_1', NULL, 'month_1_data', NULL, 47, 'g/kg/d', NULL, 'text', 'Normalized Protein Catabolic Rate (g/kg/d)', NULL, NULL, 'float', '0.5', '2', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'chol_1', NULL, 'month_1_data', NULL, 48, 'mg/dL', NULL, 'text', 'Cholesterol (mg/dL)', NULL, NULL, 'float', '100', '300', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'transferrin_1', NULL, 'month_1_data', NULL, 49, 'mg/dL', NULL, 'text', 'Transferrin (mg/dL)', NULL, NULL, 'float', '100', '300', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'kt_v_1', NULL, 'month_1_data', NULL, 50, NULL, NULL, 'text', 'Kt/V', NULL, NULL, 'float', '0.9', '3', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'drywt_1', NULL, 'month_1_data', NULL, 51, 'kilograms', NULL, 'text', 'Dry weight (kilograms)', NULL, NULL, 'float', '35', '200', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'no_show_1', NULL, 'month_1_data', NULL, 52, NULL, NULL, 'text', 'Number of treatments missed', NULL, NULL, 'float', '0', '7', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'compliance_1', NULL, 'month_1_data', NULL, 53, NULL, NULL, 'select', 'How compliant was the patient in drinking the supplement?', '0, 100 percent \\n 1, 99-75 percent \\n 2, 74-50 percent \\n 3, 49-25 percent \\n 4, 0-24 percent', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'hospit_1', NULL, 'month_1_data', NULL, 54, NULL, 'Hospitalization Data', 'select', 'Was patient hospitalized since last visit?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'cause_hosp_1', NULL, 'month_1_data', NULL, 55, NULL, NULL, 'select', 'What was the cause of hospitalization?', '1, Vascular access related events \\n 2, CVD events \\n 3, Other', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'admission_date_1', NULL, 'month_1_data', NULL, 56, NULL, NULL, 'text', 'Date of hospital admission', NULL, NULL, 'date_ymd', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'discharge_date_1', NULL, 'month_1_data', NULL, 57, NULL, NULL, 'text', 'Date of hospital discharge', NULL, NULL, 'date_ymd', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'discharge_summary_1', NULL, 'month_1_data', NULL, 58, NULL, NULL, 'select', 'Discharge summary in patients binder?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'death_1', NULL, 'month_1_data', NULL, 59, NULL, 'Mortality Data', 'select', 'Has patient died since last visit?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'date_death_1', NULL, 'month_1_data', NULL, 60, NULL, NULL, 'text', 'Date of death', NULL, NULL, 'date_ymd', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'cause_death_1', NULL, 'month_1_data', NULL, 61, NULL, NULL, 'select', 'What was the cause of death?', '1, All-cause \\n 2, Cardiovascular', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'month_1_data_complete', NULL, 'month_1_data', NULL, 62, NULL, 'Form Status', 'select', 'Complete?', '0, Incomplete \\n 1, Unverified \\n 2, Complete', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'date_visit_2', NULL, 'month_2_data', 'Month 2 Data', 63, NULL, 'Month 2', 'text', 'Date of Month 2 visit', NULL, NULL, 'date_ymd', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'alb_2', NULL, 'month_2_data', NULL, 64, 'g/dL', NULL, 'text', 'Serum Albumin (g/dL)', NULL, NULL, 'float', '3', '5', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'prealb_2', NULL, 'month_2_data', NULL, 65, 'mg/dL', NULL, 'text', 'Serum Prealbumin (mg/dL)', NULL, NULL, 'float', '10', '40', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'creat_2', NULL, 'month_2_data', NULL, 66, 'mg/dL', NULL, 'text', 'Creatinine (mg/dL)', NULL, NULL, 'float', '0.5', '20', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'npcr_2', NULL, 'month_2_data', NULL, 67, 'g/kg/d', NULL, 'text', 'Normalized Protein Catabolic Rate (g/kg/d)', NULL, NULL, 'float', '0.5', '2', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'chol_2', NULL, 'month_2_data', NULL, 68, 'mg/dL', NULL, 'text', 'Cholesterol (mg/dL)', NULL, NULL, 'float', '100', '300', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'transferrin_2', NULL, 'month_2_data', NULL, 69, 'mg/dL', NULL, 'text', 'Transferrin (mg/dL)', NULL, NULL, 'float', '100', '300', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'kt_v_2', NULL, 'month_2_data', NULL, 70, NULL, NULL, 'text', 'Kt/V', NULL, NULL, 'float', '0.9', '3', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'drywt_2', NULL, 'month_2_data', NULL, 71, 'kilograms', NULL, 'text', 'Dry weight (kilograms)', NULL, NULL, 'float', '35', '200', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'no_show_2', NULL, 'month_2_data', NULL, 72, NULL, NULL, 'text', 'Number of treatments missed', NULL, NULL, 'float', '0', '7', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'compliance_2', NULL, 'month_2_data', NULL, 73, NULL, NULL, 'select', 'How compliant was the patient in drinking the supplement?', '0, 100 percent \\n 1, 99-75 percent \\n 2, 74-50 percent \\n 3, 49-25 percent \\n 4, 0-24 percent', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'hospit_2', NULL, 'month_2_data', NULL, 74, NULL, 'Hospitalization Data', 'select', 'Was patient hospitalized since last visit?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'cause_hosp_2', NULL, 'month_2_data', NULL, 75, NULL, NULL, 'select', 'What was the cause of hospitalization?', '1, Vascular access related events \\n 2, CVD events \\n 3, Other', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'admission_date_2', NULL, 'month_2_data', NULL, 76, NULL, NULL, 'text', 'Date of hospital admission', NULL, NULL, 'date_ymd', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'discharge_date_2', NULL, 'month_2_data', NULL, 77, NULL, NULL, 'text', 'Date of hospital discharge', NULL, NULL, 'date_ymd', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'discharge_summary_2', NULL, 'month_2_data', NULL, 78, NULL, NULL, 'select', 'Discharge summary in patients binder?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'death_2', NULL, 'month_2_data', NULL, 79, NULL, 'Mortality Data', 'select', 'Has patient died since last visit?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'date_death_2', NULL, 'month_2_data', NULL, 80, NULL, NULL, 'text', 'Date of death', NULL, NULL, 'date_ymd', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'cause_death_2', NULL, 'month_2_data', NULL, 81, NULL, NULL, 'select', 'What was the cause of death?', '1, All-cause \\n 2, Cardiovascular', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'month_2_data_complete', NULL, 'month_2_data', NULL, 82, NULL, 'Form Status', 'select', 'Complete?', '0, Incomplete \\n 1, Unverified \\n 2, Complete', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'date_visit_3', NULL, 'month_3_data', 'Month 3 Data', 83, NULL, 'Month 3', 'text', 'Date of Month 3 visit', NULL, NULL, 'date_ymd', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'date_blood_3', NULL, 'month_3_data', NULL, 84, NULL, NULL, 'text', 'Date blood was drawn', NULL, NULL, 'date_ymd', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'alb_3', NULL, 'month_3_data', NULL, 85, 'g/dL', NULL, 'text', 'Serum Albumin (g/dL)', NULL, NULL, 'float', '3', '5', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'prealb_3', NULL, 'month_3_data', NULL, 86, 'mg/dL', NULL, 'text', 'Serum Prealbumin (mg/dL)', NULL, NULL, 'float', '10', '40', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'creat_3', NULL, 'month_3_data', NULL, 87, 'mg/dL', NULL, 'text', 'Creatinine (mg/dL)', NULL, NULL, 'float', '0.5', '20', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'npcr_3', NULL, 'month_3_data', NULL, 88, 'g/kg/d', NULL, 'text', 'Normalized Protein Catabolic Rate (g/kg/d)', NULL, NULL, 'float', '0.5', '2', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'chol_3', NULL, 'month_3_data', NULL, 89, 'mg/dL', NULL, 'text', 'Cholesterol (mg/dL)', NULL, NULL, 'float', '100', '300', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'transferrin_3', NULL, 'month_3_data', NULL, 90, 'mg/dL', NULL, 'text', 'Transferrin (mg/dL)', NULL, NULL, 'float', '100', '300', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'kt_v_3', NULL, 'month_3_data', NULL, 91, NULL, NULL, 'text', 'Kt/V', NULL, NULL, 'float', '0.9', '3', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'drywt_3', NULL, 'month_3_data', NULL, 92, 'kilograms', NULL, 'text', 'Dry weight (kilograms)', NULL, NULL, 'float', '35', '200', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'plasma1_3', NULL, 'month_3_data', NULL, 93, NULL, NULL, 'select', 'Collected Plasma 1?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'plasma2_3', NULL, 'month_3_data', NULL, 94, NULL, NULL, 'select', 'Collected Plasma 2?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'plasma3_3', NULL, 'month_3_data', NULL, 95, NULL, NULL, 'select', 'Collected Plasma 3?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'serum1_3', NULL, 'month_3_data', NULL, 96, NULL, NULL, 'select', 'Collected Serum 1?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'serum2_3', NULL, 'month_3_data', NULL, 97, NULL, NULL, 'select', 'Collected Serum 2?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'serum3_3', NULL, 'month_3_data', NULL, 98, NULL, NULL, 'select', 'Collected Serum 3?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'sga_3', NULL, 'month_3_data', NULL, 99, NULL, NULL, 'text', 'Subject Global Assessment (score = 1-7)', NULL, NULL, 'float', '0.9', '7.1', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'no_show_3', NULL, 'month_3_data', NULL, 100, NULL, NULL, 'text', 'Number of treatments missed', NULL, NULL, 'float', '0', '7', 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'compliance_3', NULL, 'month_3_data', NULL, 101, NULL, NULL, 'select', 'How compliant was the patient in drinking the supplement?', '0, 100 percent \\n 1, 99-75 percent \\n 2, 74-50 percent \\n 3, 49-25 percent \\n 4, 0-24 percent', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'hospit_3', NULL, 'month_3_data', NULL, 102, NULL, 'Hospitalization Data', 'select', 'Was patient hospitalized since last visit?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'cause_hosp_3', NULL, 'month_3_data', NULL, 103, NULL, NULL, 'select', 'What was the cause of hospitalization?', '1, Vascular access related events \\n 2, CVD events \\n 3, Other', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'admission_date_3', NULL, 'month_3_data', NULL, 104, NULL, NULL, 'text', 'Date of hospital admission', NULL, NULL, 'date_ymd', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'discharge_date_3', NULL, 'month_3_data', NULL, 105, NULL, NULL, 'text', 'Date of hospital discharge', NULL, NULL, 'date_ymd', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'discharge_summary_3', NULL, 'month_3_data', NULL, 106, NULL, NULL, 'select', 'Discharge summary in patients binder?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'death_3', NULL, 'month_3_data', NULL, 107, NULL, 'Mortality Data', 'select', 'Has patient died since last visit?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'date_death_3', NULL, 'month_3_data', NULL, 108, NULL, NULL, 'text', 'Date of death', NULL, NULL, 'date_ymd', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'cause_death_3', NULL, 'month_3_data', NULL, 109, NULL, NULL, 'select', 'What was the cause of death?', '1, All-cause \\n 2, Cardiovascular', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'month_3_data_complete', NULL, 'month_3_data', NULL, 110, NULL, 'Form Status', 'select', 'Complete?', '0, Incomplete \\n 1, Unverified \\n 2, Complete', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'complete_study', NULL, 'completion_data', 'Completion Data', 111, NULL, 'Study Completion Information', 'select', 'Has patient completed study?', '0, No \\n 1, Yes', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'withdraw_date', NULL, 'completion_data', NULL, 112, NULL, NULL, 'text', 'Put a date if patient withdrew study', NULL, NULL, 'date_ymd', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'withdraw_reason', NULL, 'completion_data', NULL, 113, NULL, NULL, 'select', 'Reason patient withdrew from study', '0, Non-compliance \\n 1, Did not wish to continue in study \\n 2, Could not tolerate the supplement \\n 3, Hospitalization \\n 4, Other', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'complete_study_date', NULL, 'completion_data', NULL, 114, NULL, NULL, 'text', 'Date of study completion', NULL, NULL, 'date_ymd', NULL, NULL, 'soft_typed', NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'study_comments', NULL, 'completion_data', NULL, 115, NULL, 'General Comments', 'textarea', 'Comments', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL),
(@project_id, 'completion_data_complete', NULL, 'completion_data', NULL, 116, NULL, 'Form Status', 'select', 'Complete?', '0, Incomplete \\n 1, Unverified \\n 2, Complete', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL);

INSERT INTO `redcap_projects_templates` (`project_id`, `title`, `description`, `enabled`) 
	VALUES (@project_id,  @project_title,  'Contains six data entry forms, including forms for demography and baseline data, three monthly data forms, and concludes with a completion data form.',  '1');