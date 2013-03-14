
--
-- Table structure for table 'redcap_standard'
--

CREATE TABLE redcap_standard (
  standard_id int(5) NOT NULL AUTO_INCREMENT,
  standard_name varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  standard_version varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  standard_desc varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (standard_id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_standard_code'
--

CREATE TABLE redcap_standard_code (
  standard_code_id int(5) NOT NULL AUTO_INCREMENT,
  standard_code varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  standard_code_desc varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  standard_id int(5) NOT NULL DEFAULT '0',
  PRIMARY KEY (standard_code_id),
  KEY standard_id (standard_id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_standard_map'
--

CREATE TABLE redcap_standard_map (
  standard_map_id int(5) NOT NULL AUTO_INCREMENT,
  project_id int(5) DEFAULT NULL,
  field_name varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  standard_code_id int(5) NOT NULL DEFAULT '0',
  data_conversion mediumtext COLLATE utf8_unicode_ci,
  data_conversion2 mediumtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (standard_map_id),
  KEY standard_code_id (standard_code_id),
  KEY project_id (project_id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_standard_map_audit'
--

CREATE TABLE redcap_standard_map_audit (
  audit_id int(10) NOT NULL AUTO_INCREMENT,
  project_id int(5) DEFAULT NULL,
  field_name varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  standard_code int(5) DEFAULT NULL,
  action_id int(10) DEFAULT NULL,
  `user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (audit_id),
  KEY project_id (project_id),
  KEY action_id (action_id),
  KEY standard_code (standard_code)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_standard_map_audit_action'
--

CREATE TABLE redcap_standard_map_audit_action (
  id int(10) NOT NULL DEFAULT '0',
  `action` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `redcap_standard_code`
--
ALTER TABLE `redcap_standard_code`
  ADD CONSTRAINT redcap_standard_code_ibfk_1 FOREIGN KEY (standard_id) REFERENCES redcap_standard (standard_id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `redcap_standard_map`
--
ALTER TABLE `redcap_standard_map`
  ADD CONSTRAINT redcap_standard_map_ibfk_2 FOREIGN KEY (standard_code_id) REFERENCES redcap_standard_code (standard_code_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_standard_map_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `redcap_standard_map_audit`
--
ALTER TABLE `redcap_standard_map_audit`
  ADD CONSTRAINT redcap_standard_map_audit_ibfk_5 FOREIGN KEY (standard_code) REFERENCES redcap_standard_code (standard_code_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_standard_map_audit_ibfk_2 FOREIGN KEY (action_id) REFERENCES redcap_standard_map_audit_action (id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_standard_map_audit_ibfk_4 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE SET NULL ON UPDATE CASCADE;

INSERT INTO redcap_standard_map_audit_action (id, action) VALUES
(1, 'add mapped field'),
(2, 'modify mapped field'),
(3, 'remove mapped field');

-- Fix DTS logging retroactively
update redcap_log_event set event = 'UPDATE', description = 'Update record (DTS)' where description in ('Update record - Adjudication', 'Create record - Adjudication');
