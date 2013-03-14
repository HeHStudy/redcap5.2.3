-- Add new validation type table
CREATE TABLE redcap_validation_types (
  validation_name varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Unique name for Data Dictionary',
  validation_label varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Label in Online Designer',
  regex_js text COLLATE utf8_unicode_ci,
  regex_php text COLLATE utf8_unicode_ci,
  data_type varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  legacy_value varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  visible int(1) NOT NULL DEFAULT '1' COMMENT 'Show in Online Designer?',
  UNIQUE KEY validation_name (validation_name),
  KEY data_type (data_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
INSERT INTO redcap_validation_types (validation_name, validation_label, regex_js, regex_php, data_type, legacy_value, visible) VALUES
('date_dmy', 'Date (D-M-Y)', '/^(0[1-9]|[12][0-9]|3[01])([-\\/.])?(0[1-9]|1[012])\\2?(19|20)\\d\\d$/', '/^(0[1-9]|[12][0-9]|3[01])([-\\/.])?(0[1-9]|1[012])\\2?(19|20)\\d\\d$/', 'date', NULL, 0),
('date_mdy', 'Date (M-D-Y)', '/^(0[1-9]|1[012])([-\\/.])?(0[1-9]|[12][0-9]|3[01])\\2?(19|20)\\d\\d$/', '/^(0[1-9]|1[012])([-\\/.])?(0[1-9]|[12][0-9]|3[01])\\2?(19|20)\\d\\d$/', 'date', NULL, 0),
('date_ymd', 'Date (Y-M-D)', '/^(19|20)\\d\\d([-\\/.])?(0[1-9]|1[012])\\2?(0[1-9]|[12][0-9]|3[01])$/', '/^(19|20)\\d\\d([-\\/.])?(0[1-9]|1[012])\\2?(0[1-9]|[12][0-9]|3[01])$/', 'date', 'date', 1),
('datetime_dmy', 'Datetime (D-M-Y H:M)', '/^(0[1-9]|[12][0-9]|3[01])([-\\/.])?(0[1-9]|1[012])\\2?(19|20)\\d\\d\\s([0-9]|[0-1][0-9]|[2][0-3]):([0-5][0-9])$/', '/^(0[1-9]|[12][0-9]|3[01])([-\\/.])?(0[1-9]|1[012])\\2?(19|20)\\d\\d\\s([0-9]|[0-1][0-9]|[2][0-3]):([0-5][0-9])$/', 'datetime', NULL, 0),
('datetime_mdy', 'Datetime (M-D-Y H:M)', '/^(0[1-9]|1[012])([-\\/.])?(0[1-9]|[12][0-9]|3[01])\\2?(19|20)\\d\\d\\s([0-9]|[0-1][0-9]|[2][0-3]):([0-5][0-9])$/', '/^(0[1-9]|1[012])([-\\/.])?(0[1-9]|[12][0-9]|3[01])\\2?(19|20)\\d\\d\\s([0-9]|[0-1][0-9]|[2][0-3]):([0-5][0-9])$/', 'datetime', NULL, 0),
('datetime_seconds_dmy', 'Datetime w/ seconds (D-M-Y H:M:S)', '/^(0[1-9]|[12][0-9]|3[01])([-\\/.])?(0[1-9]|1[012])\\2?(19|20)\\d\\d\\s([0-9]|[0-1][0-9]|[2][0-3]):([0-5][0-9]):([0-5][0-9])$/', '/^(0[1-9]|[12][0-9]|3[01])([-\\/.])?(0[1-9]|1[012])\\2?(19|20)\\d\\d\\s([0-9]|[0-1][0-9]|[2][0-3]):([0-5][0-9]):([0-5][0-9])$/', 'datetime_seconds', NULL, 0),
('datetime_seconds_mdy', 'Datetime w/ seconds (M-D-Y H:M:S)', '/^(0[1-9]|1[012])([-\\/.])?(0[1-9]|[12][0-9]|3[01])\\2?(19|20)\\d\\d\\s([0-9]|[0-1][0-9]|[2][0-3]):([0-5][0-9]):([0-5][0-9])$/', '/^(0[1-9]|1[012])([-\\/.])?(0[1-9]|[12][0-9]|3[01])\\2?(19|20)\\d\\d\\s([0-9]|[0-1][0-9]|[2][0-3]):([0-5][0-9]):([0-5][0-9])$/', 'datetime_seconds', NULL, 0),
('datetime_seconds_ymd', 'Datetime w/ seconds (Y-M-D H:M:S)', '/^(19|20)\\d\\d([-\\/.])?(0[1-9]|1[012])\\2?(0[1-9]|[12][0-9]|3[01])\\s([0-9]|[0-1][0-9]|[2][0-3]):([0-5][0-9]):([0-5][0-9])$/', '/^(19|20)\\d\\d([-\\/.])?(0[1-9]|1[012])\\2?(0[1-9]|[12][0-9]|3[01])\\s([0-9]|[0-1][0-9]|[2][0-3]):([0-5][0-9]):([0-5][0-9])$/', 'datetime_seconds', 'datetime_seconds', 1),
('datetime_ymd', 'Datetime (Y-M-D H:M)', '/^(19|20)\\d\\d([-\\/.])?(0[1-9]|1[012])\\2?(0[1-9]|[12][0-9]|3[01])\\s([0-9]|[0-1][0-9]|[2][0-3]):([0-5][0-9])$/', '/^(19|20)\\d\\d([-\\/.])?(0[1-9]|1[012])\\2?(0[1-9]|[12][0-9]|3[01])\\s([0-9]|[0-1][0-9]|[2][0-3]):([0-5][0-9])$/', 'datetime', 'datetime', 1),
('email', 'Email', '/^([_a-z0-9-]+)(\\.[_a-z0-9-]+)*@([a-z0-9-]+)(\\.[a-z0-9-]+)*(\\.[a-z]{2,4})$/i', '/^([_a-z0-9-]+)(\\.[_a-z0-9-]+)*@([a-z0-9-]+)(\\.[a-z0-9-]+)*(\\.[a-z]{2,4})$/i', 'email', NULL, 1),
('integer', 'Integer', '/^[-+]?\\b\\d+\\b$/', '/^[-+]?\\b\\d+\\b$/', 'integer', 'int', 1),
('number', 'Number', '/^[-+]?[0-9]*\\.?[0-9]+([eE][-+]?[0-9]+)?$/', '/^[-+]?[0-9]*\\.?[0-9]+([eE][-+]?[0-9]+)?$/', 'number', 'float', 1),
('phone', 'Phone (U.S.)', '/^(?:\\(?([2-9]1[02-9]|[2-9][02-8]1|[2-9][02-8][02-9])\\)?)\\s*(?:[.-]\\s*)?([2-9]1[02-9]|[2-9][02-9]1|[2-9][02-9]{2})\\s*(?:[.-]\\s*)?([0-9]{4})(?:\\s*(?:#|x\\.?|ext\\.?|extension)\\s*(\\d+))?$/', '/^(?:\\(?([2-9]1[02-9]|[2-9][02-8]1|[2-9][02-8][02-9])\\)?)\\s*(?:[.-]\\s*)?([2-9]1[02-9]|[2-9][02-9]1|[2-9][02-9]{2})\\s*(?:[.-]\\s*)?([0-9]{4})(?:\\s*(?:#|x\\.?|ext\\.?|extension)\\s*(\\d+))?$/', 'phone', NULL, 1),
('postalcode_canada', 'Postal Code (Canada)', '/^[ABCEGHJKLMNPRSTVXY]{1}\\d{1}[A-Z]{1}\\s*\\d{1}[A-Z]{1}\\d{1}$/i', '/^[ABCEGHJKLMNPRSTVXY]{1}\\d{1}[A-Z]{1}\\s*\\d{1}[A-Z]{1}\\d{1}$/i', 'postal_code', NULL, 0),
('time', 'Time (H:S)', '/^([0-9]|[0-1][0-9]|[2][0-3]):([0-5][0-9])$/', '/^([0-9]|[0-1][0-9]|[2][0-3]):([0-5][0-9])$/', 'time', NULL, 1),
('vmrn', 'Vanderbilt MRN', '/^[0-9]{4,9}$/', '/^[0-9]{4,9}$/', 'mrn', NULL, 0),
('zipcode', 'Zipcode (U.S.)', '/^\\d{5}(-\\d{4})?$/', '/^\\d{5}(-\\d{4})?$/', 'postal_code', NULL, 1);
-- Add user white list table
CREATE TABLE redcap_user_whitelist (
  username varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
INSERT INTO redcap_config VALUES ('enable_user_whitelist', '0'), ('allow_create_db_default', '1');
-- Remove survey_enabled table comment
ALTER TABLE  `redcap_surveys` CHANGE  `survey_enabled`  `survey_enabled` INT( 1 ) NOT NULL DEFAULT  '1';
update redcap_surveys set survey_enabled = 1;