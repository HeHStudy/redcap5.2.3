-- Remove unnecessary config field
delete from redcap_config where field_name = 'auth_meth';
-- Add new index to redcap_surveys_emails table
ALTER TABLE  `redcap_surveys_emails` ADD INDEX (  `email_sent` );
ALTER TABLE  `redcap_surveys_emails` ADD UNIQUE  `email_id_sent` (  `email_id` ,  `email_sent` );
ALTER TABLE  `redcap_surveys_emails` ADD INDEX  `survey_id_email_sent` (  `survey_id` ,  `email_sent` );