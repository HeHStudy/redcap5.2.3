-- Add Secondary and Tertiary email addresses with verification processes
ALTER TABLE  `redcap_user_information` ADD  `user_email2` VARCHAR( 255 ) NULL COMMENT  'Secondary email' AFTER  `user_email` ,
	ADD  `user_email3` VARCHAR( 255 ) NULL COMMENT  'Tertiary email' AFTER  `user_email2`;
ALTER TABLE  `redcap_user_information` CHANGE  `user_email`  `user_email` VARCHAR( 255 ) 
	CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT  'Primary email';
ALTER TABLE `redcap_user_information` ADD  `email_verify_code` VARCHAR( 20 ) NULL COMMENT  'Primary email verification code',
	ADD `email2_verify_code` VARCHAR( 20 ) NULL COMMENT  'Secondary email verification code',
	ADD `email3_verify_code` VARCHAR( 20 ) NULL COMMENT  'Tertiary email verification code',
	ADD UNIQUE (`email_verify_code`),
	ADD UNIQUE (`email2_verify_code`),
	ADD UNIQUE (`email3_verify_code`);
ALTER TABLE  `redcap_surveys_emails` ADD  `email_account` ENUM(  '1',  '2',  '3' ) NULL DEFAULT NULL COMMENT  'Sender''s account (1=Primary, 2=Secondary, 3=Tertiary)' AFTER  `email_sender` ,
	ADD  `email_static` VARCHAR( 255 ) NULL COMMENT  'Sender''s static email address (only for scheduled invitations)' AFTER  `email_account`;
ALTER TABLE  `redcap_surveys_emails` CHANGE  `email_sent`  `email_sent` DATETIME NULL DEFAULT NULL COMMENT  'Null=Not sent yet (scheduled)';
update redcap_surveys_emails  set email_account = '1';
-- Add domain whitelist for user email addresses
INSERT INTO `redcap_config` VALUES ('email_domain_whitelist',  '');

-- Tables to be utilized in the future
CREATE TABLE redcap_surveys_emails_send_rate (
  esr_id int(10) NOT NULL AUTO_INCREMENT,
  sent_begin_time datetime DEFAULT NULL COMMENT 'Time email batch was sent',
  emails_per_batch int(10) DEFAULT NULL COMMENT 'Number of emails sent in this batch',
  emails_per_minute int(6) DEFAULT NULL COMMENT 'Number of emails sent per minute for this batch',
  PRIMARY KEY (esr_id),
  KEY sent_begin_time (sent_begin_time)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Capture the rate that emails are sent per minute by REDCap';

CREATE TABLE redcap_surveys_scheduler (
  ss_id int(10) NOT NULL AUTO_INCREMENT,
  survey_id int(10) DEFAULT NULL,
  event_id int(10) DEFAULT NULL,
  active int(1) NOT NULL DEFAULT '1' COMMENT 'Is it currently active?',
  email_subject text COLLATE utf8_unicode_ci COMMENT 'Survey invitation subject',
  email_content text COLLATE utf8_unicode_ci COMMENT 'Survey invitation text',
  email_sender varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Static email address of sender',
  condition_surveycomplete_survey_id int(10) DEFAULT NULL COMMENT 'survey_id of trigger',
  condition_surveycomplete_event_id int(10) DEFAULT NULL COMMENT 'event_id of trigger',
  condition_andor enum('AND','OR') COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Include survey complete AND/OR logic',
  condition_logic text COLLATE utf8_unicode_ci COMMENT 'Logic using field values',
  condition_send_time_option enum('IMMEDIATELY','TIME_LAG','NEXT_OCCURRENCE','EXACT_TIME') COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'When to send invites after condition is met',
  condition_send_time_lag_days int(3) DEFAULT NULL COMMENT 'Wait X days to send invites after condition is met',
  condition_send_time_lag_hours int(2) DEFAULT NULL COMMENT 'Wait X hours to send invites after condition is met',
  condition_send_time_lag_minutes int(2) DEFAULT NULL COMMENT 'Wait X seconds to send invites after condition is met',
  condition_send_next_day_type enum('DAY','WEEKDAY','WEEKENDDAY','SUNDAY','MONDAY','TUESDAY','WEDNESDAY','THURSDAY','FRIDAY','SATURDAY') COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Wait till specific day/time to send invites after condition is met',
  condition_send_next_time time DEFAULT NULL COMMENT 'Wait till specific day/time to send invites after condition is met',
  condition_send_time_exact datetime DEFAULT NULL COMMENT 'Wait till exact date/time to send invites after condition is met',
  PRIMARY KEY (ss_id),
  UNIQUE KEY survey_event (survey_id,event_id),
  KEY event_id (event_id),
  KEY survey_id (survey_id),
  KEY condition_surveycomplete_event_id (condition_surveycomplete_event_id),
  KEY condition_surveycomplete_survey_id (condition_surveycomplete_survey_id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE redcap_surveys_scheduler_queue (
  ssq_id int(10) NOT NULL AUTO_INCREMENT,
  ss_id int(10) DEFAULT NULL COMMENT 'FK for surveys_scheduler table',
  email_recip_id int(10) DEFAULT NULL COMMENT 'FK for redcap_surveys_emails_recipients table',
  record varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'NULL if record not created yet',
  scheduled_time_to_send datetime DEFAULT NULL COMMENT 'Time invitation will be sent',
  `status` enum('QUEUED','SENDING','SENT','DID NOT SEND') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'QUEUED' COMMENT 'Survey invitation status (default=QUEUED)',
  time_sent datetime DEFAULT NULL COMMENT 'Actual time invitation was sent',
  reason_not_sent enum('EMAIL ADDRESS NOT FOUND','EMAIL ATTEMPT FAILED','UNKNOWN','SURVEY ALREADY COMPLETED') COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Explanation of why invitation did not send, if applicable',
  PRIMARY KEY (ssq_id),
  UNIQUE KEY ss_id_record (ss_id,record),
  UNIQUE KEY email_recip_id_record (email_recip_id,record),
  KEY ss_id (ss_id),
  KEY scheduled_time_to_send (scheduled_time_to_send),
  KEY time_sent (time_sent),
  KEY `status` (`status`),
  KEY send_sent_status (scheduled_time_to_send,time_sent,`status`),
  KEY email_recip_id (email_recip_id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `redcap_surveys_scheduler`
  ADD CONSTRAINT redcap_surveys_scheduler_ibfk_1 FOREIGN KEY (survey_id) REFERENCES redcap_surveys (survey_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_surveys_scheduler_ibfk_2 FOREIGN KEY (event_id) REFERENCES redcap_events_metadata (event_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_surveys_scheduler_ibfk_3 FOREIGN KEY (condition_surveycomplete_survey_id) REFERENCES redcap_surveys (survey_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_surveys_scheduler_ibfk_4 FOREIGN KEY (condition_surveycomplete_event_id) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `redcap_surveys_scheduler_queue`
  ADD CONSTRAINT redcap_surveys_scheduler_queue_ibfk_2 FOREIGN KEY (email_recip_id) REFERENCES redcap_surveys_emails_recipients (email_recip_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_surveys_scheduler_queue_ibfk_1 FOREIGN KEY (ss_id) REFERENCES redcap_surveys_scheduler (ss_id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE  `redcap_surveys_emails_recipients` ADD  `static_email` VARCHAR( 255 ) NULL COMMENT  'Static email address of recipient (used when participant has no email)';