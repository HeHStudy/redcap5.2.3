-- Add tables for Data Quality module

--
-- Table structure for table 'redcap_data_quality_changelog'
--

CREATE TABLE redcap_data_quality_changelog (
  com_id int(10) NOT NULL AUTO_INCREMENT,
  status_id int(10) DEFAULT NULL,
  user_id int(10) DEFAULT NULL,
  change_time datetime NOT NULL,
  `comment` text COLLATE utf8_unicode_ci COMMENT 'Only if comment was left',
  new_status int(2) DEFAULT NULL COMMENT 'Only if status changed',
  PRIMARY KEY (com_id),
  KEY user_id (user_id),
  KEY status_id (status_id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_data_quality_rules'
--

CREATE TABLE redcap_data_quality_rules (
  rule_id int(10) NOT NULL AUTO_INCREMENT,
  project_id int(10) DEFAULT NULL,
  rule_order int(3) DEFAULT '1',
  rule_name text COLLATE utf8_unicode_ci,
  rule_logic text COLLATE utf8_unicode_ci,
  PRIMARY KEY (rule_id),
  KEY project_id (project_id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_data_quality_status'
--

CREATE TABLE redcap_data_quality_status (
  status_id int(10) NOT NULL AUTO_INCREMENT,
  rule_id int(10) DEFAULT NULL,
  pd_rule_id int(2) DEFAULT NULL COMMENT 'Name of pre-defined rules',
  record varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  event_id int(10) DEFAULT NULL,
  field_name varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Only used if field-level is required',
  `status` int(2) DEFAULT '0' COMMENT 'Current status of discrepancy',
  exclude int(1) NOT NULL DEFAULT '0' COMMENT 'Hide from results',
  PRIMARY KEY (status_id),
  UNIQUE KEY rule_record_event (rule_id,record,event_id),
  UNIQUE KEY pd_rule_record_event_field (pd_rule_id,record,event_id,field_name),
  KEY rule_id (rule_id),
  KEY event_id (event_id),
  KEY pd_rule_id (pd_rule_id),
  KEY pd_rule_record_event (pd_rule_id,record,event_id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `redcap_data_quality_changelog`
--
ALTER TABLE `redcap_data_quality_changelog`
  ADD CONSTRAINT redcap_data_quality_changelog_ibfk_1 FOREIGN KEY (status_id) REFERENCES redcap_data_quality_status (status_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_data_quality_changelog_ibfk_2 FOREIGN KEY (user_id) REFERENCES redcap_user_information (ui_id) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `redcap_data_quality_rules`
--
ALTER TABLE `redcap_data_quality_rules`
  ADD CONSTRAINT redcap_data_quality_rules_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `redcap_data_quality_status`
--
ALTER TABLE `redcap_data_quality_status`
  ADD CONSTRAINT redcap_data_quality_status_ibfk_1 FOREIGN KEY (rule_id) REFERENCES redcap_data_quality_rules (rule_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_data_quality_status_ibfk_2 FOREIGN KEY (event_id) REFERENCES redcap_events_metadata (event_id) ON DELETE CASCADE ON UPDATE CASCADE;
