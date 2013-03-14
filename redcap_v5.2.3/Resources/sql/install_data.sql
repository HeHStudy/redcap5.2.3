
-- REDCAP INSTALLATION INITIAL DATA --

INSERT INTO redcap_user_information 
(username, user_email, user_firstname, user_lastname, super_user, user_firstvisit) VALUES
('site_admin', 'joe.user@project-redcap.org', 'Joe', 'User', 1, now());

INSERT INTO redcap_standard_map_audit_action (id, action) VALUES
(1, 'add mapped field'),
(2, 'modify mapped field'),
(3, 'remove mapped field');

INSERT INTO redcap_crons (cron_name, cron_description, cron_enabled, cron_frequency, cron_max_run_time, cron_instances_max, cron_instances_current, cron_last_run_end, cron_times_failed, cron_external_url) VALUES
('PubMed', 'Query the PubMed API to find publications associated with PIs in REDCap, and store publication attributes and PI/project info. Emails will then be sent to any PIs that have been found to have publications in PubMed, and (if applicable) will be asked to associate their publication to a REDCap project.', 'DISABLED', 86400, 7200, 1, 0, NULL, 0, NULL),
('RemoveTempAndDeletedFiles', 'Delete all files from the REDCap temp directory, and delete all edoc and Send-It files marked for deletion.', 'ENABLED', 600, 600, 1, 0, NULL, 0, NULL),
('DbCleanup', 'Due to some perplexing issues where things might get "out of sync" on the back-end, run some queries to fix any known issues.', 'DISABLED', 43200, 7200, 1, 0, NULL, 0, NULL),
('ExpireSurveys', 'For any surveys where an expiration timestamp is set, if the timestamp <= NOW, then make the survey inactive.', 'ENABLED', 120, 600, 1, 0, NULL, 0, NULL),
('SurveyInvitationEmailer', 'Mailer that sends any survey invitations that have been scheduled.', 'ENABLED', 60, 1800, 5, 0, NULL, 0, NULL),
('DeleteProjects', 'Delete all projects that are scheduled for permanent deletion', 'ENABLED', 300, 1200, 1, 0, NULL, 0, NULL);

INSERT INTO redcap_auth_questions (qid, question) VALUES
(1, 'What was your childhood nickname?'),
(2, 'In what city did you meet your spouse/significant other?'),
(3, 'What is the name of your favorite childhood friend?'),
(4, 'What street did you live on in third grade?'),
(5, 'What is your oldest sibling''s birthday month and year? (e.g. January 1900)'),
(6, 'What is the middle name of your oldest child?'),
(7, 'What is your oldest sibling''s middle name?'),
(8, 'What school did you attend for sixth grade?'),
(9, 'What was your childhood phone number including area code? (e.g. 000-000-0000)'),
(10, 'What is your oldest cousin''s first and last name?'),
(11, 'What was the name of your first stuffed animal?'),
(12, 'In what city or town did your mother and father meet?'),
(13, 'Where were you when you had your first kiss?'),
(14, 'What is the first name of the boy or girl that you first kissed?'),
(15, 'What was the last name of your third grade teacher?'),
(16, 'In what city does your nearest sibling live?'),
(17, 'What is your oldest brother''s birthday month and year? (e.g. January 1900)'),
(18, 'What is your maternal grandmother''s maiden name?'),
(19, 'In what city or town was your first job?'),
(20, 'What is the name of the place your wedding reception was held?'),
(21, 'What is the name of a college you applied to but didn''t attend?');

INSERT INTO redcap_config (field_name, value) VALUES
('data_entry_trigger_enabled', '1'),
('redcap_base_url_display_error_on_mismatch', '1'),
('email_domain_whitelist', ''),
('helpfaq_custom_text', ''),
('randomization_global', '1'),
('login_custom_text', ''), 
('auto_prod_changes', '2'),
('enable_edit_prod_events', '1'),
('allow_create_db_default', '1'),
('api_enabled', '1'),
('auth_meth_global', 'none'),
('auto_report_stats', '1'),
('auto_report_stats_last_sent', '2000-01-01'),
('autologout_timer', '30'),
('certify_text_create', ''),
('certify_text_prod', ''),
('homepage_custom_text', ''),
('doc_to_edoc_transfer_complete', '1'),
('dts_enabled_global', '0'),
('display_nonauth_projects', '1'),
('display_project_logo_institution', '0'),
('display_today_now_button', '1'),
('edoc_field_option_enabled', '1'),
('edoc_upload_max', ''),
('edoc_storage_option', '0'),
('file_repository_upload_max', ''),
('file_repository_enabled', '1'),
('temp_files_last_delete', now()),
('edoc_path', ''),
('enable_edit_survey_response', '1'),
('enable_plotting', '2'),
('enable_plotting_survey_results', '1'),
('enable_projecttype_singlesurvey', '1'),
('enable_projecttype_forms', '1'),
('enable_projecttype_singlesurveyforms', '1'),
('enable_url_shortener', '1'),
('enable_user_whitelist', '0'),
('logout_fail_limit', '5'),
('logout_fail_window', '15'),
('footer_links', ''),
('footer_text', ''),
('google_translate_enabled', '0'),
('googlemap_key',''),
('grant_cite', ''),
('headerlogo', ''),
('homepage_contact', ''),
('homepage_contact_email', ''),
('homepage_grant_cite', ''),
('identifier_keywords', 'name, street, address, city, county, precinct, zip, postal, date, phone, fax, mail, ssn, social security, mrn, dob, dod, medical, record, id, age'),
('institution', ''),
('language_global','English'),
('login_autocomplete_disable', '0'),
('login_logo', ''),
('my_profile_enable_edit','1'),
('password_history_limit','0'),
('password_reset_duration','0'),
('project_contact_email', ''),
('project_contact_name', ''),
('project_contact_prod_changes_email', ''),
('project_contact_prod_changes_name', ''),
('project_language', 'English'),
('proxy_hostname', ''),
('pub_matching_enabled', '0'),
('redcap_base_url', ''),
('pub_matching_emails', '0'),
('pub_matching_email_days', '7'),
('pub_matching_email_limit', '3'),
('pub_matching_email_text', ''),
('pub_matching_email_subject', ''),
('pub_matching_institution', 'Vanderbilt\nMeharry'),
('redcap_last_install_date', CURRENT_DATE),
('redcap_version', '4.0.0'),
('sendit_enabled', '1'),
('sendit_upload_max', ''),
('shared_library_enabled', '1'),
('shibboleth_logout', ''),
('shibboleth_username_field', 'none'),
('site_org_type', ''),
('superusers_only_create_project', '0'),
('superusers_only_move_to_prod', '1'),
('system_offline', '0');

INSERT INTO `redcap_pub_sources` (`pubsrc_id`, `pubsrc_name`, `pubsrc_last_crawl_time`) VALUES
(1, 'PubMed', NULL);

INSERT INTO redcap_validation_types (validation_name, validation_label, regex_js, regex_php, data_type, legacy_value, visible) VALUES
('alpha_only', 'Letters only', '/^[a-z]+$/i', '/^[a-z]+$/i', 'text', NULL, 0),
('date_dmy', 'Date (D-M-Y)', '/^(0[1-9]|[12][0-9]|3[01])([-\\/.])?(0[1-9]|1[012])\\2?(\\d{4})$/', '/^(0[1-9]|[12][0-9]|3[01])([-\\/.])?(0[1-9]|1[012])\\2?(\\d{4})$/', 'date', NULL, 0),
('date_mdy', 'Date (M-D-Y)', '/^(0[1-9]|1[012])([-\\/.])?(0[1-9]|[12][0-9]|3[01])\\2?(\\d{4})$/', '/^(0[1-9]|1[012])([-\\/.])?(0[1-9]|[12][0-9]|3[01])\\2?(\\d{4})$/', 'date', NULL, 1),
('date_ymd', 'Date (Y-M-D)', '/^(\\d{4})([-\\/.])?(0[1-9]|1[012])\\2?(0[1-9]|[12][0-9]|3[01])$/', '/^(\\d{4})([-\\/.])?(0[1-9]|1[012])\\2?(0[1-9]|[12][0-9]|3[01])$/', 'date', 'date', 1),
('datetime_dmy', 'Datetime (D-M-Y H:M)', '/^(0[1-9]|[12][0-9]|3[01])([-\\/.])?(0[1-9]|1[012])\\2?(\\d{4})\\s([0-9]|[0-1][0-9]|[2][0-3]):([0-5][0-9])$/', '/^(0[1-9]|[12][0-9]|3[01])([-\\/.])?(0[1-9]|1[012])\\2?(\\d{4})\\s([0-9]|[0-1][0-9]|[2][0-3]):([0-5][0-9])$/', 'datetime', NULL, 0),
('datetime_mdy', 'Datetime (M-D-Y H:M)', '/^(0[1-9]|1[012])([-\\/.])?(0[1-9]|[12][0-9]|3[01])\\2?(\\d{4})\\s([0-9]|[0-1][0-9]|[2][0-3]):([0-5][0-9])$/', '/^(0[1-9]|1[012])([-\\/.])?(0[1-9]|[12][0-9]|3[01])\\2?(\\d{4})\\s([0-9]|[0-1][0-9]|[2][0-3]):([0-5][0-9])$/', 'datetime', NULL, 1),
('datetime_seconds_dmy', 'Datetime w/ seconds (D-M-Y H:M:S)', '/^(0[1-9]|[12][0-9]|3[01])([-\\/.])?(0[1-9]|1[012])\\2?(\\d{4})\\s([0-9]|[0-1][0-9]|[2][0-3]):([0-5][0-9]):([0-5][0-9])$/', '/^(0[1-9]|[12][0-9]|3[01])([-\\/.])?(0[1-9]|1[012])\\2?(\\d{4})\\s([0-9]|[0-1][0-9]|[2][0-3]):([0-5][0-9]):([0-5][0-9])$/', 'datetime_seconds', NULL, 0),
('datetime_seconds_mdy', 'Datetime w/ seconds (M-D-Y H:M:S)', '/^(0[1-9]|1[012])([-\\/.])?(0[1-9]|[12][0-9]|3[01])\\2?(\\d{4})\\s([0-9]|[0-1][0-9]|[2][0-3]):([0-5][0-9]):([0-5][0-9])$/', '/^(0[1-9]|1[012])([-\\/.])?(0[1-9]|[12][0-9]|3[01])\\2?(\\d{4})\\s([0-9]|[0-1][0-9]|[2][0-3]):([0-5][0-9]):([0-5][0-9])$/', 'datetime_seconds', NULL, 1),
('datetime_seconds_ymd', 'Datetime w/ seconds (Y-M-D H:M:S)', '/^(\\d{4})([-\\/.])?(0[1-9]|1[012])\\2?(0[1-9]|[12][0-9]|3[01])\\s([0-9]|[0-1][0-9]|[2][0-3]):([0-5][0-9]):([0-5][0-9])$/', '/^(\\d{4})([-\\/.])?(0[1-9]|1[012])\\2?(0[1-9]|[12][0-9]|3[01])\\s([0-9]|[0-1][0-9]|[2][0-3]):([0-5][0-9]):([0-5][0-9])$/', 'datetime_seconds', 'datetime_seconds', 1),
('datetime_ymd', 'Datetime (Y-M-D H:M)', '/^(\\d{4})([-\\/.])?(0[1-9]|1[012])\\2?(0[1-9]|[12][0-9]|3[01])\\s([0-9]|[0-1][0-9]|[2][0-3]):([0-5][0-9])$/', '/^(\\d{4})([-\\/.])?(0[1-9]|1[012])\\2?(0[1-9]|[12][0-9]|3[01])\\s([0-9]|[0-1][0-9]|[2][0-3]):([0-5][0-9])$/', 'datetime', 'datetime', 1),
('email', 'Email', '/^([_a-z0-9-'']+)(\\.[_a-z0-9-'']+)*@([a-z0-9-]+)(\\.[a-z0-9-]+)*(\\.[a-z]{2,4})$/i', '/^([_a-z0-9-'']+)(\\.[_a-z0-9-'']+)*@([a-z0-9-]+)(\\.[a-z0-9-]+)*(\\.[a-z]{2,4})$/i', 'email', NULL, 1),
('integer', 'Integer', '/^[-+]?\\b\\d+\\b$/', '/^[-+]?\\b\\d+\\b$/', 'integer', 'int', 1),
('mrn_10d', 'MRN (10 digits)', '/^\\d{10}$/', '/^\\d{10}$/', 'text', NULL, 0),
('number', 'Number', '/^[-+]?[0-9]*\\.?[0-9]+([eE][-+]?[0-9]+)?$/', '/^[-+]?[0-9]*\\.?[0-9]+([eE][-+]?[0-9]+)?$/', 'number', 'float', 1),
('number_1dp', 'Number (1 decimal place)', '/^-?\\d+\\.\\d$/', '/^-?\\d+\\.\\d$/', 'number', NULL, 0),
('number_2dp', 'Number (2 decimal places)', '/^-?\\d+\\.\\d{2}$/', '/^-?\\d+\\.\\d{2}$/', 'number', NULL, 0),
('number_3dp', 'Number (3 decimal places)', '/^-?\\d+\\.\\d{3}$/', '/^-?\\d+\\.\\d{3}$/', 'number', NULL, 0),
('number_4dp', 'Number (4 decimal places)', '/^-?\\d+\\.\\d{4}$/', '/^-?\\d+\\.\\d{4}$/', 'number', NULL, 0),
('phone', 'Phone (U.S.)', '/^(?:\\(?([2-9]1[02-9]|[2-9][02-8]1|[2-9][02-8][02-9])\\)?)\\s*(?:[.-]\\s*)?([2-9]1[02-9]|[2-9][02-9]1|[2-9][02-9]{2})\\s*(?:[.-]\\s*)?([0-9]{4})(?:\\s*(?:#|x\\.?|ext\\.?|extension)\\s*(\\d+))?$/', '/^(?:\\(?([2-9]1[02-9]|[2-9][02-8]1|[2-9][02-8][02-9])\\)?)\\s*(?:[.-]\\s*)?([2-9]1[02-9]|[2-9][02-9]1|[2-9][02-9]{2})\\s*(?:[.-]\\s*)?([0-9]{4})(?:\\s*(?:#|x\\.?|ext\\.?|extension)\\s*(\\d+))?$/', 'phone', NULL, 1),
('phone_australia', 'Phone (Australia)', '/^(\\(0[2-8]\\)|0[2-8])\\s*\\d{4}\\s*\\d{4}$/', '/^(\\(0[2-8]\\)|0[2-8])\\s*\\d{4}\\s*\\d{4}$/', 'phone', NULL, 0),
('postalcode_australia', 'Postal Code (Australia)', '/^\\d{4}$/', '/^\\d{4}$/', 'postal_code', NULL, 0),
('postalcode_canada', 'Postal Code (Canada)', '/^[ABCEGHJKLMNPRSTVXY]{1}\\d{1}[A-Z]{1}\\s*\\d{1}[A-Z]{1}\\d{1}$/i', '/^[ABCEGHJKLMNPRSTVXY]{1}\\d{1}[A-Z]{1}\\s*\\d{1}[A-Z]{1}\\d{1}$/i', 'postal_code', NULL, 0),
('ssn', 'Social Security Number (U.S.)', '/^\\d{3}-\\d\\d-\\d{4}$/', '/^\\d{3}-\\d\\d-\\d{4}$/', 'ssn', NULL, 0),
('time', 'Time (HH:MM)', '/^([0-9]|[0-1][0-9]|[2][0-3]):([0-5][0-9])$/', '/^([0-9]|[0-1][0-9]|[2][0-3]):([0-5][0-9])$/', 'time', NULL, 1),
('time_mm_ss', 'Time (MM:SS)', '/^[0-5]\\d:[0-5]\\d$/', '/^[0-5]\\d:[0-5]\\d$/', 'time', NULL, 0),
('vmrn', 'Vanderbilt MRN', '/^[0-9]{4,9}$/', '/^[0-9]{4,9}$/', 'mrn', NULL, 0),
('zipcode', 'Zipcode (U.S.)', '/^\\d{5}(-\\d{4})?$/', '/^\\d{5}(-\\d{4})?$/', 'postal_code', NULL, 1);
