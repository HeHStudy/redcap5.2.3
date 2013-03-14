-- Add new setting for enforcing password history
CREATE TABLE redcap_auth_history (
  username varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `timestamp` datetime DEFAULT NULL,
  KEY username (username),
  KEY username_password (username,`password`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores last 5 passwords';
insert into redcap_auth_history select username, password, NOW() from redcap_auth;
insert into redcap_config values ('password_history_limit','0');
insert into redcap_config values ('password_reset_duration','0');