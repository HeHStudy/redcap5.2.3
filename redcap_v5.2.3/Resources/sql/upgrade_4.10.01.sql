-- Add placeholders for upcoming features
ALTER TABLE  `redcap_metadata` 
	ADD  `grid_name` VARCHAR( 100 ) NULL COMMENT  'Unique name of grid group' AFTER  `question_num` ,
	ADD  `misc` TEXT NULL COMMENT  'Miscellaneous field attributes' AFTER  `grid_name`;
ALTER TABLE  `redcap_metadata_temp` 
	ADD  `grid_name` VARCHAR( 100 ) NULL COMMENT  'Unique name of grid group' AFTER  `question_num` ,
	ADD  `misc` TEXT NULL COMMENT  'Miscellaneous field attributes' AFTER  `grid_name`;
ALTER TABLE  `redcap_metadata_archive` 
	ADD  `grid_name` VARCHAR( 100 ) NULL COMMENT  'Unique name of grid group' AFTER  `question_num` ,
	ADD  `misc` TEXT NULL COMMENT  'Miscellaneous field attributes' AFTER  `grid_name`;
-- Table-based password-reset questions
CREATE TABLE redcap_auth_questions (
  qid int(10) NOT NULL AUTO_INCREMENT,
  question text COLLATE utf8_unicode_ci,
  PRIMARY KEY (qid)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
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
ALTER TABLE  `redcap_auth` ADD  `password_question` INT( 10 ) NULL COMMENT  'PK of question',
	ADD  `password_answer` VARCHAR( 100 ) NULL COMMENT  'MD5 hash of answer to password recovery question',
	CHANGE  `temp_pwd`  `temp_pwd` INT( 1 ) NOT NULL DEFAULT  '0' COMMENT  'Flag to force user to re-enter password',
	CHANGE  `password`  `password` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT  'MD5 hash of user''s password',
	ADD INDEX (  `password_question` );
ALTER TABLE  `redcap_auth` ADD FOREIGN KEY (  `password_question` ) 
	REFERENCES `redcap_auth_questions` (`qid`) ON DELETE SET NULL ON UPDATE CASCADE ;
-- Fix issue with API timestamp
update (select distinct username from redcap_user_rights where api_token is not null) u, redcap_user_information i 
	set i.user_firstactivity = i.user_firstvisit where u.username = i.username and i.user_firstactivity is not null 
	and i.user_firstvisit is not null;