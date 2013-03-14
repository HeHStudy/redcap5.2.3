
--
-- Table structure for table 'redcap_actions'
--

CREATE TABLE redcap_actions (
  action_id int(10) NOT NULL AUTO_INCREMENT,
  project_id int(10) DEFAULT NULL,
  action_trigger enum('MANUAL','ENDOFSURVEY','SURVEYQUESTION') COLLATE utf8_unicode_ci DEFAULT NULL,
  action_response enum('NONE','EMAIL','STOPSURVEY','PROMPT') COLLATE utf8_unicode_ci DEFAULT NULL,
  custom_text text COLLATE utf8_unicode_ci,
  recipient_id int(5) DEFAULT NULL COMMENT 'FK user_information',
  PRIMARY KEY (action_id),
  KEY project_id (project_id),
  KEY recipient_id (recipient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_auth'
--

CREATE TABLE redcap_auth (
  username varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  temp_pwd int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_auth_history'
--

CREATE TABLE redcap_auth_history (
  username varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `timestamp` datetime DEFAULT NULL,
  KEY username (username),
  KEY username_password (username,`password`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores last 5 passwords';

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_config'
--

CREATE TABLE redcap_config (
  field_name varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (field_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores global settings';

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_dashboard_ip_location_cache'
--

CREATE TABLE redcap_dashboard_ip_location_cache (
  ip varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  latitude varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  longitude varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  city varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  region varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  country varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_data'
--

CREATE TABLE redcap_data (
  project_id int(5) NOT NULL DEFAULT '0',
  event_id int(10) DEFAULT NULL,
  record varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  field_name varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` text COLLATE utf8_unicode_ci,
  KEY project_id (project_id),
  KEY event_id (event_id),
  KEY record_field (record,field_name),
  KEY project_field (project_id,field_name),
  KEY project_record (project_id,record),
  KEY proj_record_field (project_id,record,field_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_data_access_groups'
--

CREATE TABLE redcap_data_access_groups (
  group_id int(5) NOT NULL AUTO_INCREMENT,
  project_id int(5) DEFAULT NULL,
  group_name varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (group_id),
  KEY project_id (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_data_quality_status'
--

CREATE TABLE redcap_data_quality_status (
  status_id int(10) NOT NULL AUTO_INCREMENT,
  rule_id int(10) DEFAULT NULL,
  pd_rule_id int(2) DEFAULT NULL COMMENT 'Name of pre-defined rules',
  project_id int(11) DEFAULT NULL,
  record varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  event_id int(10) DEFAULT NULL,
  field_name varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Only used if field-level is required',
  `status` int(2) DEFAULT '0' COMMENT 'Current status of discrepancy',
  exclude int(1) NOT NULL DEFAULT '0' COMMENT 'Hide from results',
  PRIMARY KEY (status_id),
  UNIQUE KEY rule_record_event (rule_id,record,event_id),
  UNIQUE KEY pd_rule_proj_record_event_field (pd_rule_id,record,event_id,field_name,project_id),
  KEY rule_id (rule_id),
  KEY event_id (event_id),
  KEY pd_rule_id (pd_rule_id),
  KEY project_id (project_id),
  KEY pd_rule_proj_record_event (pd_rule_id,record,event_id,project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_docs'
--

CREATE TABLE redcap_docs (
  docs_id int(11) NOT NULL AUTO_INCREMENT,
  project_id int(5) NOT NULL DEFAULT '0',
  docs_date date DEFAULT NULL,
  docs_name text COLLATE utf8_unicode_ci,
  docs_size double DEFAULT NULL,
  docs_type text COLLATE utf8_unicode_ci,
  docs_file longblob,
  docs_comment text COLLATE utf8_unicode_ci,
  docs_rights text COLLATE utf8_unicode_ci,
  export_file int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (docs_id),
  KEY docs_name (docs_name(128)),
  KEY project_id (project_id),
  KEY project_id_export_file (project_id,export_file),
  KEY project_id_comment (project_id,docs_comment(128))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_docs_to_edocs'
--

CREATE TABLE redcap_docs_to_edocs (
  docs_id int(11) NOT NULL COMMENT 'PK redcap_docs',
  doc_id int(11) NOT NULL COMMENT 'PK redcap_edocs_metadata',
  PRIMARY KEY (docs_id,doc_id),
  KEY docs_id (docs_id),
  KEY doc_id (doc_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_edocs_metadata'
--

CREATE TABLE redcap_edocs_metadata (
  doc_id int(10) NOT NULL AUTO_INCREMENT,
  stored_name varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'stored name',
  mime_type varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  doc_name varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  doc_size int(10) DEFAULT NULL,
  file_extension varchar(6) COLLATE utf8_unicode_ci DEFAULT NULL,
  project_id int(5) DEFAULT NULL,
  stored_date datetime DEFAULT NULL COMMENT 'stored date',
  delete_date datetime DEFAULT NULL COMMENT 'date deleted',
  date_deleted_server datetime DEFAULT NULL COMMENT 'When really deleted from server',
  PRIMARY KEY (doc_id),
  KEY project_id (project_id),
  KEY date_deleted (delete_date,date_deleted_server)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_esignatures'
--

CREATE TABLE redcap_esignatures (
  esign_id int(11) NOT NULL AUTO_INCREMENT,
  project_id int(5) DEFAULT NULL,
  record varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  event_id int(10) DEFAULT NULL,
  form_name varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  username varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `timestamp` datetime DEFAULT NULL,
  PRIMARY KEY (esign_id),
  UNIQUE KEY proj_rec_event_form (project_id,record,event_id,form_name),
  KEY username (username),
  KEY proj_rec_event (project_id,record,event_id),
  KEY project_id (project_id),
  KEY event_id (event_id),
  KEY proj_rec (project_id,record)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_events_arms'
--

CREATE TABLE redcap_events_arms (
  arm_id int(10) NOT NULL AUTO_INCREMENT,
  project_id int(5) NOT NULL DEFAULT '0',
  arm_num int(2) NOT NULL DEFAULT '1',
  arm_name varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Arm 1',
  PRIMARY KEY (arm_id),
  UNIQUE KEY proj_arm_num (project_id,arm_num),
  KEY project_id (project_id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_events_calendar'
--

CREATE TABLE redcap_events_calendar (
  cal_id int(10) NOT NULL AUTO_INCREMENT,
  record varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  project_id int(5) DEFAULT NULL,
  event_id int(10) DEFAULT NULL,
  baseline_date date DEFAULT NULL,
  group_id int(5) DEFAULT NULL,
  event_date date DEFAULT NULL,
  event_time varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'HH:MM',
  event_status int(2) DEFAULT NULL COMMENT 'NULL=Ad Hoc, 0=Due Date, 1=Scheduled, 2=Confirmed, 3=Cancelled, 4=No Show',
  note_type int(2) DEFAULT NULL,
  notes text COLLATE utf8_unicode_ci,
  extra_notes text COLLATE utf8_unicode_ci,
  PRIMARY KEY (cal_id),
  KEY project_date (project_id,event_date),
  KEY project_record (project_id,record),
  KEY project_id (project_id),
  KEY event_id (event_id),
  KEY group_id (group_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Calendar Data';

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_events_forms'
--

CREATE TABLE redcap_events_forms (
  event_id int(10) NOT NULL DEFAULT '0',
  form_name varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  UNIQUE KEY event_form (event_id,form_name),
  KEY event_id (event_id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_events_metadata'
--

CREATE TABLE redcap_events_metadata (
  event_id int(10) NOT NULL auto_increment,
  arm_id int(10) NOT NULL default '0' COMMENT 'FK for events_arms',
  day_offset float NOT NULL default '0' COMMENT 'Days from Start Date',
  offset_min float NOT NULL default '0',
  offset_max float NOT NULL default '0',
  descrip varchar(64) collate utf8_unicode_ci NOT NULL default 'Event 1' COMMENT 'Event Name',
  external_id varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (event_id),
  KEY arm_id (arm_id),
  KEY external_id (external_id),
  KEY arm_dayoffset_descrip (arm_id,day_offset,descrip),
  KEY day_offset (day_offset),
  KEY descrip (descrip)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_external_links'
--

CREATE TABLE redcap_external_links (
  ext_id int(10) NOT NULL AUTO_INCREMENT,
  project_id int(10) DEFAULT NULL,
  link_order int(5) NOT NULL DEFAULT '1',
  link_url text COLLATE utf8_unicode_ci,
  link_label text COLLATE utf8_unicode_ci,
  open_new_window int(10) NOT NULL DEFAULT '0',
  link_type enum('LINK','POST_AUTHKEY','REDCAP_PROJECT') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'LINK',
  user_access enum('ALL','DAG','SELECTED') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'ALL',
  append_record_info int(1) NOT NULL DEFAULT '0' COMMENT 'Append record and event to URL',
  link_to_project_id int(10) DEFAULT NULL,
  PRIMARY KEY (ext_id),
  KEY project_id (project_id),
  KEY link_to_project_id (link_to_project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_external_links_dags'
--

CREATE TABLE redcap_external_links_dags (
  ext_id int(11) NOT NULL AUTO_INCREMENT,
  group_id int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (ext_id,group_id),
  KEY ext_id (ext_id),
  KEY group_id (group_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_external_links_users'
--

CREATE TABLE redcap_external_links_users (
  ext_id int(11) NOT NULL AUTO_INCREMENT,
  username varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (ext_id,username),
  KEY ext_id (ext_id),
  KEY username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_library_map'
--

CREATE TABLE redcap_library_map (
  project_id int(5) NOT NULL DEFAULT '0',
  form_name varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT '1 = Downloaded; 2 = Uploaded',
  library_id int(10) NOT NULL DEFAULT '0',
  upload_timestamp datetime DEFAULT NULL,
  acknowledgement text COLLATE utf8_unicode_ci,
  acknowledgement_cache datetime DEFAULT NULL,
  PRIMARY KEY (project_id,form_name,`type`,library_id),
  KEY project_id (project_id),
  KEY library_id (library_id),
  KEY form_name (form_name),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_locking_data'
--

CREATE TABLE redcap_locking_data (
  ld_id int(11) NOT NULL AUTO_INCREMENT,
  project_id int(5) DEFAULT NULL,
  record varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  event_id int(10) DEFAULT NULL,
  form_name varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  username varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `timestamp` datetime DEFAULT NULL,
  PRIMARY KEY (ld_id),
  UNIQUE KEY proj_rec_event_form (project_id,record,event_id,form_name),
  KEY username (username),
  KEY proj_rec_event (project_id,record,event_id),
  KEY project_id (project_id),
  KEY event_id (event_id),
  KEY proj_rec (project_id,record)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_locking_labels'
--

CREATE TABLE redcap_locking_labels (
  ll_id int(11) NOT NULL AUTO_INCREMENT,
  project_id int(11) DEFAULT NULL,
  form_name varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  label text COLLATE utf8_unicode_ci,
  display int(1) NOT NULL DEFAULT '1',
  display_esignature int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (ll_id),
  UNIQUE KEY project_form (project_id,form_name),
  KEY project_id (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_log_event'
--

CREATE TABLE redcap_log_event (
  log_event_id int(11) NOT NULL AUTO_INCREMENT,
  project_id int(5) NOT NULL DEFAULT '0',
  ts bigint(14) DEFAULT NULL,
  `user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  ip varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `page` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `event` enum('UPDATE','INSERT','DELETE','SELECT','ERROR','LOGIN','LOGOUT','OTHER','DATA_EXPORT','DOC_UPLOAD','DOC_DELETE','MANAGE','LOCK_RECORD','ESIGNATURE') COLLATE utf8_unicode_ci DEFAULT NULL,
  object_type varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  sql_log mediumtext COLLATE utf8_unicode_ci,
  pk text COLLATE utf8_unicode_ci,
  event_id int(10) DEFAULT NULL,
  data_values text COLLATE utf8_unicode_ci,
  description text COLLATE utf8_unicode_ci,
  legacy int(1) NOT NULL DEFAULT '0',
  change_reason text COLLATE utf8_unicode_ci,
  PRIMARY KEY (log_event_id),
  KEY `user` (`user`),
  KEY project_id (project_id),
  KEY user_project (project_id,`user`),
  KEY pk (pk(64)),
  KEY object_type (object_type),
  KEY ts (ts),
  KEY `event` (`event`),
  KEY event_project (`event`,project_id),
  KEY description (description(128))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_log_view'
--

CREATE TABLE redcap_log_view (
  log_view_id int(11) NOT NULL AUTO_INCREMENT,
  ts timestamp NULL DEFAULT NULL,
  `user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `event` enum('LOGIN_SUCCESS','LOGIN_FAIL','LOGOUT','PAGE_VIEW') COLLATE utf8_unicode_ci DEFAULT NULL,
  ip varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  browser_name varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  browser_version varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  full_url text COLLATE utf8_unicode_ci,
  `page` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  project_id int(5) DEFAULT NULL,
  event_id int(10) DEFAULT NULL,
  record varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  form_name varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  miscellaneous text COLLATE utf8_unicode_ci,
  session_id varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (log_view_id),
  KEY `user` (`user`),
  KEY project_id (project_id),
  KEY ts (ts),
  KEY ip (ip),
  KEY `event` (`event`),
  KEY browser_name (browser_name),
  KEY browser_version (browser_version),
  KEY `page` (`page`),
  KEY session_id (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_metadata'
--

CREATE TABLE redcap_metadata (
  project_id int(5) NOT NULL DEFAULT '0',
  field_name varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  field_phi varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
  form_name varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  form_menu_description varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  field_order float DEFAULT NULL,
  field_units varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  element_preceding_header mediumtext COLLATE utf8_unicode_ci,
  element_type varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  element_label mediumtext COLLATE utf8_unicode_ci,
  element_enum mediumtext COLLATE utf8_unicode_ci,
  element_note mediumtext COLLATE utf8_unicode_ci,
  element_validation_type varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  element_validation_min varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  element_validation_max varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  element_validation_checktype varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  branching_logic text COLLATE utf8_unicode_ci,
  field_req int(1) NOT NULL DEFAULT '0',
  edoc_id int(10) DEFAULT NULL COMMENT 'image/file attachment',
  edoc_display_img int(1) NOT NULL DEFAULT '0',
  custom_alignment enum('LH','LV','RH','RV') COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'RV = NULL = default',
  stop_actions text COLLATE utf8_unicode_ci,
  question_num varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (project_id,field_name),
  KEY project_id_form (project_id,form_name),
  KEY field_name (field_name),
  KEY project_id (project_id),
  KEY project_id_fieldorder (project_id,field_order),
  KEY edoc_id (edoc_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_metadata_archive'
--

CREATE TABLE redcap_metadata_archive (
  project_id int(5) NOT NULL DEFAULT '0',
  field_name varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  field_phi varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
  form_name varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  form_menu_description varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  field_order float DEFAULT NULL,
  field_units varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  element_preceding_header mediumtext COLLATE utf8_unicode_ci,
  element_type varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  element_label mediumtext COLLATE utf8_unicode_ci,
  element_enum mediumtext COLLATE utf8_unicode_ci,
  element_note mediumtext COLLATE utf8_unicode_ci,
  element_validation_type varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  element_validation_min varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  element_validation_max varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  element_validation_checktype varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  branching_logic text COLLATE utf8_unicode_ci,
  field_req int(1) NOT NULL DEFAULT '0',
  edoc_id int(10) DEFAULT NULL COMMENT 'image/file attachment',
  edoc_display_img int(1) NOT NULL DEFAULT '0',
  custom_alignment enum('LH','LV','RH','RV') COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'RV = NULL = default',
  stop_actions text COLLATE utf8_unicode_ci,
  question_num varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  pr_id int(10) DEFAULT NULL,
  UNIQUE KEY project_field_prid (project_id,field_name,pr_id),
  KEY project_id_form (project_id,form_name),
  KEY field_name (field_name),
  KEY project_id (project_id),
  KEY pr_id (pr_id),
  KEY edoc_id (edoc_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_metadata_prod_revisions'
--

CREATE TABLE redcap_metadata_prod_revisions (
  pr_id int(10) NOT NULL AUTO_INCREMENT,
  project_id int(5) NOT NULL DEFAULT '0',
  ui_id_requester int(5) DEFAULT NULL,
  ui_id_approver int(5) DEFAULT NULL,
  ts_req_approval datetime DEFAULT NULL,
  ts_approved datetime DEFAULT NULL,
  PRIMARY KEY (pr_id),
  KEY project_id (project_id),
  KEY project_user (project_id,ui_id_requester),
  KEY project_approved (project_id,ts_approved),
  KEY ui_id_requester (ui_id_requester),
  KEY ui_id_approver (ui_id_approver)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_metadata_temp'
--

CREATE TABLE redcap_metadata_temp (
  project_id int(5) NOT NULL DEFAULT '0',
  field_name varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  field_phi varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
  form_name varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  form_menu_description varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  field_order float DEFAULT NULL,
  field_units varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  element_preceding_header mediumtext COLLATE utf8_unicode_ci,
  element_type varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  element_label mediumtext COLLATE utf8_unicode_ci,
  element_enum mediumtext COLLATE utf8_unicode_ci,
  element_note mediumtext COLLATE utf8_unicode_ci,
  element_validation_type varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  element_validation_min varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  element_validation_max varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  element_validation_checktype varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  branching_logic text COLLATE utf8_unicode_ci,
  field_req int(1) NOT NULL DEFAULT '0',
  edoc_id int(10) DEFAULT NULL COMMENT 'image/file attachment',
  edoc_display_img int(1) NOT NULL DEFAULT '0',
  custom_alignment enum('LH','LV','RH','RV') COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'RV = NULL = default',
  stop_actions text COLLATE utf8_unicode_ci,
  question_num varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (project_id,field_name),
  KEY project_id_form (project_id,form_name),
  KEY field_name (field_name),
  KEY project_id (project_id),
  KEY edoc_id (edoc_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_migration_script'
--

CREATE TABLE redcap_migration_script (
  id int(5) NOT NULL AUTO_INCREMENT,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  username varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  script longblob,
  PRIMARY KEY (id),
  KEY username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Holds SQL for migrating REDCap Survey surveys';

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_page_hits'
--

CREATE TABLE redcap_page_hits (
  `date` date NOT NULL,
  page_name varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  page_hits float NOT NULL DEFAULT '1',
  UNIQUE KEY `date` (`date`,page_name),
  KEY date2 (`date`),
  KEY page_name (page_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_projects'
--

CREATE TABLE redcap_projects (
  project_id int(5) NOT NULL AUTO_INCREMENT,
  project_name varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  app_title text COLLATE utf8_unicode_ci,
  `status` int(1) NOT NULL DEFAULT '0',
  creation_time datetime DEFAULT NULL,
  production_time datetime DEFAULT NULL,
  inactive_time datetime DEFAULT NULL,
  created_by int(5) DEFAULT NULL COMMENT 'FK from User Info',
  draft_mode int(1) NOT NULL DEFAULT '0',
  surveys_enabled int(1) NOT NULL DEFAULT '0' COMMENT '0 = forms only, 1 = survey+forms, 2 = single survey only',
  repeatforms int(1) NOT NULL DEFAULT '0',
  scheduling int(1) NOT NULL DEFAULT '0',
  purpose int(2) DEFAULT NULL,
  purpose_other text COLLATE utf8_unicode_ci,
  show_which_records int(1) NOT NULL DEFAULT '0',
  __SALT__ varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Alphanumeric hash unique to each project',
  count_project int(1) NOT NULL DEFAULT '1',
  investigators text COLLATE utf8_unicode_ci,
  project_note text COLLATE utf8_unicode_ci,
  online_offline int(1) NOT NULL DEFAULT '1',
  auth_meth varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  double_data_entry int(1) NOT NULL DEFAULT '0',
  project_language varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'English',
  is_child_of varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  date_shift_max int(10) NOT NULL DEFAULT '364',
  institution text COLLATE utf8_unicode_ci,
  site_org_type text COLLATE utf8_unicode_ci,
  grant_cite text COLLATE utf8_unicode_ci,
  project_contact_name text COLLATE utf8_unicode_ci,
  project_contact_email text COLLATE utf8_unicode_ci,
  project_contact_prod_changes_name text COLLATE utf8_unicode_ci,
  project_contact_prod_changes_email text COLLATE utf8_unicode_ci,
  headerlogo text COLLATE utf8_unicode_ci,
  auto_inc_set int(1) NOT NULL DEFAULT '0',
  custom_data_entry_note text COLLATE utf8_unicode_ci,
  custom_index_page_note text COLLATE utf8_unicode_ci,
  order_id_by varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  custom_reports mediumtext COLLATE utf8_unicode_ci COMMENT 'Legacy report builder',
  report_builder mediumtext COLLATE utf8_unicode_ci,
  mobile_project int(1) NOT NULL DEFAULT '0',
  mobile_project_export_flag int(1) NOT NULL DEFAULT '1',
  disable_data_entry int(1) NOT NULL DEFAULT '0',
  google_translate_default varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  require_change_reason int(1) NOT NULL DEFAULT '0',
  dts_enabled int(1) NOT NULL DEFAULT '0',
  project_pi_firstname varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  project_pi_mi varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  project_pi_lastname varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  project_pi_email varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  project_pi_alias varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  project_pi_username varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  project_irb_number varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  project_grant_number varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  history_widget_enabled int(1) NOT NULL DEFAULT '1',
  secondary_pk varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'field_name of seconary identifier',
  custom_record_label text COLLATE utf8_unicode_ci,
  display_project_logo_institution int(1) NOT NULL DEFAULT '1',
  imported_from_rs int(1) NOT NULL DEFAULT '0' COMMENT 'If imported from REDCap Survey',
  display_today_now_button int(1) NOT NULL DEFAULT '1',
  auto_variable_naming int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (project_id),
  UNIQUE KEY project_name (project_name),
  KEY created_by (created_by)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores project-level values';

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_project_checklist'
--

CREATE TABLE redcap_project_checklist (
  list_id int(11) NOT NULL AUTO_INCREMENT,
  project_id int(5) DEFAULT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (list_id),
  UNIQUE KEY project_name (project_id,`name`),
  KEY project_id (project_id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
ALTER TABLE `redcap_project_checklist` ADD CONSTRAINT redcap_project_checklist_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE;


-- --------------------------------------------------------

--
-- Table structure for table 'redcap_randomization'
--

CREATE TABLE redcap_randomization (
  rid int(10) NOT NULL AUTO_INCREMENT,
  project_id int(10) DEFAULT NULL,
  target_field varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  target_event int(10) DEFAULT NULL,
  source_field1 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  source_event1 int(10) DEFAULT NULL,
  source_field2 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  source_event2 int(10) DEFAULT NULL,
  source_field3 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  source_event3 int(10) DEFAULT NULL,
  source_field4 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  source_event4 int(10) DEFAULT NULL,
  source_field5 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  source_event5 int(10) DEFAULT NULL,
  source_field6 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  source_event6 int(10) DEFAULT NULL,
  source_field7 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  source_event7 int(10) DEFAULT NULL,
  source_field8 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  source_event8 int(10) DEFAULT NULL,
  source_field9 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  source_event9 int(10) DEFAULT NULL,
  source_field10 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  source_event10 int(10) DEFAULT NULL,
  source_field11 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  source_event11 int(10) DEFAULT NULL,
  source_field12 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  source_event12 int(10) DEFAULT NULL,
  source_field13 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  source_event13 int(10) DEFAULT NULL,
  source_field14 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  source_event14 int(10) DEFAULT NULL,
  source_field15 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  source_event15 int(10) DEFAULT NULL,
  PRIMARY KEY (rid),
  UNIQUE KEY project_id (project_id),
  KEY target_event (target_event),
  KEY source_event1 (source_event1),
  KEY source_event2 (source_event2),
  KEY source_event3 (source_event3),
  KEY source_event4 (source_event4),
  KEY source_event5 (source_event5),
  KEY source_event6 (source_event6),
  KEY source_event7 (source_event7),
  KEY source_event8 (source_event8),
  KEY source_event9 (source_event9),
  KEY source_event10 (source_event10),
  KEY source_event11 (source_event11),
  KEY source_event12 (source_event12),
  KEY source_event13 (source_event13),
  KEY source_event14 (source_event14),
  KEY source_event15 (source_event15)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_randomization_allocation'
--

CREATE TABLE redcap_randomization_allocation (
  aid int(10) NOT NULL AUTO_INCREMENT,
  rid int(10) NOT NULL DEFAULT '0',
  is_used int(1) NOT NULL DEFAULT '0' COMMENT 'Used by a record?',
  target_field varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Data value',
  source_field1 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Data value',
  source_field2 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Data value',
  source_field3 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Data value',
  source_field4 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Data value',
  source_field5 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Data value',
  source_field6 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Data value',
  source_field7 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Data value',
  source_field8 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Data value',
  source_field9 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Data value',
  source_field10 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Data value',
  source_field11 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Data value',
  source_field12 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Data value',
  source_field13 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Data value',
  source_field14 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Data value',
  source_field15 varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Data value',
  PRIMARY KEY (aid),
  KEY rid (rid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_sendit_docs'
--

CREATE TABLE redcap_sendit_docs (
  document_id int(11) NOT NULL AUTO_INCREMENT,
  doc_name varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  doc_orig_name varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  doc_type varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  doc_size int(11) DEFAULT NULL,
  send_confirmation int(1) NOT NULL DEFAULT '0',
  expire_date datetime DEFAULT NULL,
  username varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  location int(1) NOT NULL DEFAULT '0' COMMENT '1 = Home page; 2 = File Repository; 3 = Form',
  docs_id int(11) NOT NULL DEFAULT '0',
  date_added datetime DEFAULT NULL,
  date_deleted datetime DEFAULT NULL COMMENT 'When really deleted from server (only applicable for location=1)',
  PRIMARY KEY (document_id),
  KEY user_id (username),
  KEY docs_id_location (location,docs_id),
  KEY expire_location_deleted (expire_date,location,date_deleted),
  KEY date_added (date_added)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_sendit_recipients'
--

CREATE TABLE redcap_sendit_recipients (
  recipient_id int(11) NOT NULL AUTO_INCREMENT,
  email_address varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  sent_confirmation int(1) NOT NULL DEFAULT '0',
  download_date datetime DEFAULT NULL,
  download_count int(11) NOT NULL DEFAULT '0',
  document_id int(11) NOT NULL DEFAULT '0' COMMENT 'FK from redcap_sendit_docs',
  guid varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  pwd varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (recipient_id),
  KEY document_id (document_id),
  KEY email_address (email_address),
  KEY guid (guid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_sessions'
--

CREATE TABLE redcap_sessions (
  session_id varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  session_data text COLLATE utf8_unicode_ci,
  session_expiration timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Stores user authentication session data';

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_standard'
--

CREATE TABLE redcap_standard (
  standard_id int(5) NOT NULL AUTO_INCREMENT,
  standard_name varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  standard_version varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  standard_desc varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (standard_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

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
  `timestamp` datetime DEFAULT NULL,
  PRIMARY KEY (audit_id),
  KEY project_id (project_id),
  KEY action_id (action_id),
  KEY standard_code (standard_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_standard_map_audit_action'
--

CREATE TABLE redcap_standard_map_audit_action (
  id int(10) NOT NULL DEFAULT '0',
  `action` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_surveys'
--

CREATE TABLE redcap_surveys (
  survey_id int(10) NOT NULL AUTO_INCREMENT,
  project_id int(10) DEFAULT NULL,
  form_name varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'NULL = assume first form',
  title text COLLATE utf8_unicode_ci COMMENT 'Survey title',
  instructions text COLLATE utf8_unicode_ci COMMENT 'Survey instructions',
  acknowledgement text COLLATE utf8_unicode_ci COMMENT 'Survey acknowledgement',
  question_by_section int(1) NOT NULL DEFAULT '0' COMMENT '0 = one-page survey',
  question_auto_numbering int(1) NOT NULL DEFAULT '1',
  survey_enabled int(1) NOT NULL DEFAULT '1',
  save_and_return int(1) NOT NULL DEFAULT '0',
  logo int(10) DEFAULT NULL COMMENT 'FK for redcap_edocs_metadata',
  hide_title int(1) NOT NULL DEFAULT '0',
  email_field varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Field name that stores participant email',
  view_results int(1) NOT NULL DEFAULT '0',
  min_responses_view_results int(5) NOT NULL DEFAULT '10',
  check_diversity_view_results int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (survey_id),
  UNIQUE KEY logo (logo),
  UNIQUE KEY project_form (project_id,form_name),
  KEY project_id (project_id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Table for survey data';

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_surveys_banned_ips'
--

CREATE TABLE redcap_surveys_banned_ips (
  ip varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  time_of_ban timestamp NULL DEFAULT NULL,
  PRIMARY KEY (ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_surveys_emails'
--

CREATE TABLE redcap_surveys_emails (
  email_id int(10) NOT NULL AUTO_INCREMENT,
  survey_id int(10) DEFAULT NULL,
  email_subject text COLLATE utf8_unicode_ci,
  email_content text COLLATE utf8_unicode_ci,
  email_sender int(10) DEFAULT NULL COMMENT 'FK ui_id from redcap_user_information',
  email_sent datetime DEFAULT NULL,
  PRIMARY KEY (email_id),
  KEY survey_id (survey_id),
  KEY email_sender (email_sender)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Track emails sent out';

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_surveys_emails_recipients'
--

CREATE TABLE redcap_surveys_emails_recipients (
  email_recip_id int(10) NOT NULL AUTO_INCREMENT,
  email_id int(10) DEFAULT NULL COMMENT 'FK redcap_surveys_emails',
  participant_id int(10) DEFAULT NULL COMMENT 'FK redcap_surveys_participants',
  PRIMARY KEY (email_recip_id),
  KEY emt_id (email_id),
  KEY participant_id (participant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Track email recipients';

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_surveys_ip_cache'
--

CREATE TABLE redcap_surveys_ip_cache (
  ip_hash varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` timestamp NULL DEFAULT NULL,
  KEY `timestamp` (`timestamp`),
  KEY ip_hash (ip_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_surveys_participants'
--

CREATE TABLE redcap_surveys_participants (
  participant_id int(10) NOT NULL AUTO_INCREMENT,
  survey_id int(10) DEFAULT NULL,
  event_id int(10) DEFAULT NULL,
  `hash` varchar(6) CHARACTER SET latin1 COLLATE latin1_general_cs DEFAULT NULL,
  legacy_hash varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Migrated from RS',
  participant_email varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'NULL if public survey',
  participant_identifier varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (participant_id),
  UNIQUE KEY `hash` (`hash`),
  UNIQUE KEY legacy_hash (legacy_hash),
  KEY survey_id (survey_id),
  KEY participant_email (participant_email),
  KEY survey_event_email (survey_id,event_id,participant_email),
  KEY event_id (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Table for survey data';

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_surveys_response'
--

CREATE TABLE redcap_surveys_response (
  response_id int(11) NOT NULL AUTO_INCREMENT,
  participant_id int(10) DEFAULT NULL,
  record varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  first_submit_time datetime DEFAULT NULL,
  completion_time datetime DEFAULT NULL,
  return_code varchar(8) COLLATE utf8_unicode_ci DEFAULT NULL,
  results_code varchar(8) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (response_id),
  UNIQUE KEY participant_record (participant_id,record),
  KEY return_code (return_code),
  KEY participant_id (participant_id),
  KEY results_code (results_code),
  KEY first_submit_time (first_submit_time),
  KEY completion_time (completion_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_surveys_response_users'
--

CREATE TABLE redcap_surveys_response_users (
  response_id int(10) DEFAULT NULL,
  username varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  UNIQUE KEY response_user (response_id,username),
  KEY response_id (response_id),
  KEY username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_surveys_response_values'
--

CREATE TABLE redcap_surveys_response_values (
  response_id int(10) DEFAULT NULL,
  project_id int(5) NOT NULL DEFAULT '0',
  event_id int(10) DEFAULT NULL,
  record varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  field_name varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` text COLLATE utf8_unicode_ci,
  KEY project_id (project_id),
  KEY event_id (event_id),
  KEY record_field (record,field_name),
  KEY project_field (project_id,field_name),
  KEY project_record (project_id,record),
  KEY proj_record_field (project_id,record,field_name),
  KEY response_id (response_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Storage for completed survey responses (archival purposes)';

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_user_information'
--

CREATE TABLE redcap_user_information (
  ui_id int(5) NOT NULL AUTO_INCREMENT,
  username varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  user_email varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  user_firstname varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  user_lastname varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  user_inst_id varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  super_user int(1) NOT NULL DEFAULT '0',
  user_firstvisit datetime DEFAULT NULL,
  user_firstactivity datetime DEFAULT NULL,
  user_lastactivity datetime DEFAULT NULL,
  user_suspended_time datetime DEFAULT NULL,
  allow_create_db int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (ui_id),
  UNIQUE KEY username (username)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_user_rights'
--

CREATE TABLE redcap_user_rights (
  project_id int(5) NOT NULL DEFAULT '0',
  username varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  expiration date DEFAULT NULL,
  group_id int(5) DEFAULT NULL,
  lock_record int(1) NOT NULL DEFAULT '0',
  lock_record_multiform int(1) NOT NULL DEFAULT '0',
  lock_record_customize int(1) NOT NULL DEFAULT '0',
  data_export_tool int(1) NOT NULL DEFAULT '1',
  data_import_tool int(1) NOT NULL DEFAULT '1',
  data_comparison_tool int(1) NOT NULL DEFAULT '1',
  data_logging int(1) NOT NULL DEFAULT '1',
  file_repository int(1) NOT NULL DEFAULT '1',
  double_data int(1) NOT NULL DEFAULT '0',
  user_rights int(1) NOT NULL DEFAULT '1',
  data_access_groups int(1) NOT NULL DEFAULT '1',
  graphical int(1) NOT NULL DEFAULT '1',
  reports int(1) NOT NULL DEFAULT '1',
  design int(1) NOT NULL DEFAULT '0',
  calendar int(1) NOT NULL DEFAULT '1',
  data_entry text COLLATE utf8_unicode_ci,
  api_token varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  api_export int(1) NOT NULL DEFAULT '0',
  api_import int(1) NOT NULL DEFAULT '0',
  record_create int(1) NOT NULL DEFAULT '1',
  record_rename int(1) NOT NULL DEFAULT '0',
  record_delete int(1) NOT NULL DEFAULT '0',
  dts int(1) NOT NULL DEFAULT '0' COMMENT 'DTS adjudication page',
  participants int(1) NOT NULL DEFAULT '1',
  data_quality_design int(1) NOT NULL DEFAULT '0',
  data_quality_execute int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (project_id,username),
  UNIQUE KEY api_token (api_token),
  KEY username (username),
  KEY project_id (project_id),
  KEY group_id (group_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_user_whitelist'
--

CREATE TABLE redcap_user_whitelist (
  username varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_validation_types'
--

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

--
-- Constraints for dumped tables
--

--
-- Constraints for table `redcap_actions`
--
ALTER TABLE `redcap_actions`
  ADD CONSTRAINT redcap_actions_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_actions_ibfk_2 FOREIGN KEY (recipient_id) REFERENCES redcap_user_information (ui_id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `redcap_data_access_groups`
--
ALTER TABLE `redcap_data_access_groups`
  ADD CONSTRAINT redcap_data_access_groups_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE;

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
  ADD CONSTRAINT redcap_data_quality_status_ibfk_2 FOREIGN KEY (event_id) REFERENCES redcap_events_metadata (event_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_data_quality_status_ibfk_3 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `redcap_docs`
--
ALTER TABLE `redcap_docs`
  ADD CONSTRAINT redcap_docs_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `redcap_docs_to_edocs`
--
ALTER TABLE `redcap_docs_to_edocs`
  ADD CONSTRAINT redcap_docs_to_edocs_ibfk_2 FOREIGN KEY (doc_id) REFERENCES redcap_edocs_metadata (doc_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_docs_to_edocs_ibfk_1 FOREIGN KEY (docs_id) REFERENCES redcap_docs (docs_id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `redcap_edocs_metadata`
--
ALTER TABLE `redcap_edocs_metadata`
  ADD CONSTRAINT redcap_edocs_metadata_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `redcap_esignatures`
--
ALTER TABLE `redcap_esignatures`
  ADD CONSTRAINT redcap_esignatures_ibfk_2 FOREIGN KEY (event_id) REFERENCES redcap_events_metadata (event_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_esignatures_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `redcap_events_arms`
--
ALTER TABLE `redcap_events_arms`
  ADD CONSTRAINT redcap_events_arms_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `redcap_events_calendar`
--
ALTER TABLE `redcap_events_calendar`
  ADD CONSTRAINT redcap_events_calendar_ibfk_3 FOREIGN KEY (group_id) REFERENCES redcap_data_access_groups (group_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_events_calendar_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_events_calendar_ibfk_2 FOREIGN KEY (event_id) REFERENCES redcap_events_metadata (event_id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `redcap_events_forms`
--
ALTER TABLE `redcap_events_forms`
  ADD CONSTRAINT redcap_events_forms_ibfk_1 FOREIGN KEY (event_id) REFERENCES redcap_events_metadata (event_id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `redcap_events_metadata`
--
ALTER TABLE `redcap_events_metadata`
  ADD CONSTRAINT redcap_events_metadata_ibfk_1 FOREIGN KEY (arm_id) REFERENCES redcap_events_arms (arm_id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `redcap_external_links`
--
ALTER TABLE `redcap_external_links`
  ADD CONSTRAINT redcap_external_links_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_external_links_ibfk_2 FOREIGN KEY (link_to_project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `redcap_external_links_dags`
--
ALTER TABLE `redcap_external_links_dags`
  ADD CONSTRAINT redcap_external_links_dags_ibfk_2 FOREIGN KEY (group_id) REFERENCES redcap_data_access_groups (group_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_external_links_dags_ibfk_1 FOREIGN KEY (ext_id) REFERENCES redcap_external_links (ext_id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `redcap_external_links_users`
--
ALTER TABLE `redcap_external_links_users`
  ADD CONSTRAINT redcap_external_links_users_ibfk_1 FOREIGN KEY (ext_id) REFERENCES redcap_external_links (ext_id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `redcap_library_map`
--
ALTER TABLE `redcap_library_map`
  ADD CONSTRAINT redcap_library_map_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `redcap_locking_data`
--
ALTER TABLE `redcap_locking_data`
  ADD CONSTRAINT redcap_locking_data_ibfk_2 FOREIGN KEY (event_id) REFERENCES redcap_events_metadata (event_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_locking_data_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `redcap_locking_labels`
--
ALTER TABLE `redcap_locking_labels`
  ADD CONSTRAINT redcap_locking_labels_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `redcap_randomization`
--
ALTER TABLE `redcap_randomization`
  ADD CONSTRAINT redcap_randomization_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_randomization_ibfk_2 FOREIGN KEY (source_event1) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_randomization_ibfk_3 FOREIGN KEY (source_event2) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_randomization_ibfk_4 FOREIGN KEY (source_event3) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_randomization_ibfk_5 FOREIGN KEY (source_event4) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_randomization_ibfk_6 FOREIGN KEY (source_event5) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_randomization_ibfk_7 FOREIGN KEY (source_event6) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_randomization_ibfk_8 FOREIGN KEY (source_event7) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_randomization_ibfk_9 FOREIGN KEY (source_event8) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_randomization_ibfk_10 FOREIGN KEY (source_event9) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_randomization_ibfk_11 FOREIGN KEY (source_event10) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_randomization_ibfk_12 FOREIGN KEY (source_event11) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_randomization_ibfk_13 FOREIGN KEY (source_event12) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_randomization_ibfk_14 FOREIGN KEY (source_event13) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_randomization_ibfk_15 FOREIGN KEY (source_event14) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_randomization_ibfk_16 FOREIGN KEY (source_event15) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_randomization_ibfk_17 FOREIGN KEY (target_event) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `redcap_randomization_allocation`
--
ALTER TABLE `redcap_randomization_allocation`
  ADD CONSTRAINT redcap_randomization_allocation_ibfk_1 FOREIGN KEY (rid) REFERENCES redcap_randomization (rid) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `redcap_sendit_recipients`
--
ALTER TABLE `redcap_sendit_recipients`
  ADD CONSTRAINT redcap_sendit_recipients_ibfk_1 FOREIGN KEY (document_id) REFERENCES redcap_sendit_docs (document_id) ON DELETE CASCADE ON UPDATE CASCADE;

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

--
-- Constraints for table `redcap_surveys`
--
ALTER TABLE `redcap_surveys`
  ADD CONSTRAINT redcap_surveys_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_surveys_ibfk_2 FOREIGN KEY (logo) REFERENCES redcap_edocs_metadata (doc_id) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `redcap_surveys_emails`
--
ALTER TABLE `redcap_surveys_emails`
  ADD CONSTRAINT redcap_surveys_emails_ibfk_1 FOREIGN KEY (survey_id) REFERENCES redcap_surveys (survey_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_surveys_emails_ibfk_2 FOREIGN KEY (email_sender) REFERENCES redcap_user_information (ui_id) ON DELETE SET NULL ON UPDATE SET NULL;

--
-- Constraints for table `redcap_surveys_emails_recipients`
--
ALTER TABLE `redcap_surveys_emails_recipients`
  ADD CONSTRAINT redcap_surveys_emails_recipients_ibfk_1 FOREIGN KEY (email_id) REFERENCES redcap_surveys_emails (email_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_surveys_emails_recipients_ibfk_2 FOREIGN KEY (participant_id) REFERENCES redcap_surveys_participants (participant_id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `redcap_surveys_participants`
--
ALTER TABLE `redcap_surveys_participants`
  ADD CONSTRAINT redcap_surveys_participants_ibfk_2 FOREIGN KEY (event_id) REFERENCES redcap_events_metadata (event_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_surveys_participants_ibfk_1 FOREIGN KEY (survey_id) REFERENCES redcap_surveys (survey_id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `redcap_surveys_response`
--
ALTER TABLE `redcap_surveys_response`
  ADD CONSTRAINT redcap_surveys_response_ibfk_1 FOREIGN KEY (participant_id) REFERENCES redcap_surveys_participants (participant_id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `redcap_surveys_response_users`
--
ALTER TABLE `redcap_surveys_response_users`
  ADD CONSTRAINT redcap_surveys_response_users_ibfk_1 FOREIGN KEY (response_id) REFERENCES redcap_surveys_response (response_id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `redcap_surveys_response_values`
--
ALTER TABLE `redcap_surveys_response_values`
  ADD CONSTRAINT redcap_surveys_response_values_ibfk_1 FOREIGN KEY (response_id) REFERENCES redcap_surveys_response (response_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_surveys_response_values_ibfk_2 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_surveys_response_values_ibfk_3 FOREIGN KEY (event_id) REFERENCES redcap_events_metadata (event_id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `redcap_user_rights`
--
ALTER TABLE `redcap_user_rights`
  ADD CONSTRAINT redcap_user_rights_ibfk_2 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_user_rights_ibfk_3 FOREIGN KEY (group_id) REFERENCES redcap_data_access_groups (group_id) ON DELETE SET NULL ON UPDATE CASCADE;


ALTER TABLE  `redcap_external_links` ADD  `append_pid` INT( 1 ) NOT NULL DEFAULT  '0' 
	COMMENT  'Append project_id to URL' AFTER  `append_record_info`;
DROP TABLE IF EXISTS redcap_external_links_exclude_projects;
CREATE TABLE redcap_external_links_exclude_projects (
  ext_id int(11) NOT NULL AUTO_INCREMENT,
  project_id int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (ext_id,project_id),
  KEY ext_id (ext_id),
  KEY project_id (project_id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Projects to exclude for global external links';
ALTER TABLE `redcap_external_links_exclude_projects`
  ADD CONSTRAINT redcap_external_links_exclude_projects_ibfk_2 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_external_links_exclude_projects_ibfk_1 FOREIGN KEY (ext_id) REFERENCES redcap_external_links (ext_id) ON DELETE CASCADE ON UPDATE CASCADE;

-- Add back-end structures for features to be utilized later
ALTER TABLE  `redcap_randomization_allocation` ADD  `project_status` INT( 1 ) NOT NULL DEFAULT  '0' 
	COMMENT  'Used in dev or prod status' AFTER  `rid`;
ALTER TABLE  `redcap_randomization_allocation` ADD INDEX  `rid_status` (  `rid` ,  `project_status` );
ALTER TABLE  `redcap_randomization_allocation` CHANGE  `is_used`  `is_used_by` VARCHAR( 100 ) NULL COMMENT  'Used by a record?';
ALTER TABLE  `redcap_randomization_allocation` ADD UNIQUE  `rid_status_usedby` (  `rid` ,  `project_status` ,  `is_used_by` );
ALTER TABLE  `redcap_projects` ADD  `randomization` INT( 1 ) NOT NULL DEFAULT  '0';
ALTER TABLE  `redcap_user_rights` ADD  `random_setup` INT( 1 ) NOT NULL DEFAULT  '0',
	ADD  `random_dashboard` INT( 1 ) NOT NULL DEFAULT  '0',
	ADD  `random_perform` INT( 1 ) NOT NULL DEFAULT  '0';	
ALTER TABLE  `redcap_randomization` ADD  `stratified` INT( 1 ) NOT NULL DEFAULT  '1' COMMENT  '1=Stratified, 0=Block' AFTER  `project_id`;
ALTER TABLE  `redcap_randomization_allocation` ADD  `group_id` INT( 10 ) NULL COMMENT  'DAG' AFTER  `is_used_by`, ADD INDEX (  `group_id` );
ALTER TABLE  `redcap_randomization_allocation` ADD FOREIGN KEY (  `group_id` ) 
	REFERENCES  `redcap_data_access_groups` (`group_id`) ON DELETE SET NULL ON UPDATE CASCADE ;
ALTER TABLE  `redcap_randomization` ADD  `group_by` ENUM(  'DAG',  'FIELD' ) NULL COMMENT  'Randomize by group?' AFTER  `stratified`;

DROP TABLE IF EXISTS redcap_crons_history;
DROP TABLE IF EXISTS redcap_crons;
CREATE TABLE redcap_crons (
  cron_id int(10) NOT NULL AUTO_INCREMENT,
  cron_name varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Unique name for each job',
  cron_description text COLLATE utf8_unicode_ci,
  cron_enabled enum('ENABLED','DISABLED') COLLATE utf8_unicode_ci DEFAULT 'ENABLED',
  cron_frequency int(10) DEFAULT NULL COMMENT 'seconds',
  cron_max_run_time int(10) DEFAULT NULL COMMENT 'max # seconds a cron should run',
  cron_last_run_end datetime DEFAULT NULL,
  cron_status enum('PROCESSING','COMPLETED','FAILED','NOT YET RUN') COLLATE utf8_unicode_ci DEFAULT 'NOT YET RUN',
  cron_times_failed int(2) NOT NULL DEFAULT '0' COMMENT 'After X failures, set as Disabled',
  cron_external_url text COLLATE utf8_unicode_ci COMMENT 'URL to call for custom jobs not defined by REDCap',
  PRIMARY KEY (cron_id),
  UNIQUE KEY cron_name (cron_name)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='List of all jobs to be run by universal cron job';
CREATE TABLE redcap_crons_history (
  ch_id int(10) NOT NULL AUTO_INCREMENT,
  cron_id int(10) DEFAULT NULL,
  cron_last_run_start datetime DEFAULT NULL,
  cron_last_run_end datetime DEFAULT NULL,
  cron_last_run_status enum('PROCESSING','COMPLETED','FAILED') COLLATE utf8_unicode_ci DEFAULT NULL,
  cron_info text COLLATE utf8_unicode_ci COMMENT 'Any pertinent info that might be logged',
  PRIMARY KEY (ch_id),
  KEY cron_id (cron_id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='History of all jobs run by universal cron job';
ALTER TABLE `redcap_crons_history`
  ADD CONSTRAINT redcap_crons_history_ibfk_1 FOREIGN KEY (cron_id) REFERENCES redcap_crons (cron_id) ON DELETE SET NULL ON UPDATE CASCADE;
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
-- Password reset 
CREATE TABLE redcap_auth_questions (
  qid int(10) NOT NULL AUTO_INCREMENT,
  question text COLLATE utf8_unicode_ci,
  PRIMARY KEY (qid)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
ALTER TABLE  `redcap_auth` ADD  `password_question` INT( 10 ) NULL COMMENT  'PK of question',
	ADD  `password_answer` VARCHAR( 100 ) NULL COMMENT  'MD5 hash of answer to password recovery question',
	CHANGE  `temp_pwd`  `temp_pwd` INT( 1 ) NOT NULL DEFAULT  '0' COMMENT  'Flag to force user to re-enter password',
	CHANGE  `password`  `password` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT  'MD5 hash of user''s password',
	ADD INDEX (  `password_question` );
ALTER TABLE  `redcap_auth` ADD FOREIGN KEY (  `password_question` ) 
	REFERENCES `redcap_auth_questions` (`qid`) ON DELETE SET NULL ON UPDATE CASCADE ;

ALTER TABLE `redcap_auth` ADD `password_question_reminder` DATETIME NULL COMMENT 'When to prompt user to set up security question';


-- ----------------------------------------------------------------------------
--
-- Publication Matching tables
--
-- ----------------------------------------------------------------------------

CREATE TABLE  `redcap_pub_sources` (
`pubsrc_id` INT NOT NULL ,
`pubsrc_name` VARCHAR( 32 ) COLLATE utf8_unicode_ci NOT NULL ,
`pubsrc_last_crawl_time` DATETIME NULL DEFAULT NULL ,
PRIMARY KEY (  `pubsrc_id` )
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT 'The different places where we grab publications from';

CREATE TABLE `redcap_pub_articles` (
  `article_id` int(10) NOT NULL AUTO_INCREMENT,
  `pubsrc_id` int(10) NOT NULL,
  `pub_id` varchar(16) COLLATE utf8_unicode_ci NOT NULL COMMENT 'The publication source''s ID for the article (e.g., a PMID in the case of PubMed)',
  `title` text COLLATE utf8_unicode_ci,
  `volume` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `issue` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `pages` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `journal` text COLLATE utf8_unicode_ci,
  `journal_abbrev` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `pub_date` date DEFAULT NULL,
  `epub_date` date DEFAULT NULL,
  PRIMARY KEY (`article_id`),
  UNIQUE KEY `pubsrc_id` (`pubsrc_id`,`pub_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Articles pulled from a publication source (e.g. PubMed)';

CREATE TABLE redcap_pub_authors (
  author_id int(10) NOT NULL AUTO_INCREMENT,
  article_id int(10) DEFAULT NULL,
  author varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (author_id),
  KEY article_id (article_id),
  KEY author (author)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE redcap_pub_mesh_terms (
  mesh_id int(10) NOT NULL AUTO_INCREMENT,
  article_id int(10) DEFAULT NULL,
  mesh_term varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (mesh_id),
  KEY article_id (article_id),
  KEY mesh_term (mesh_term)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `redcap_pub_matches` (
  `match_id` int(11) NOT NULL AUTO_INCREMENT,
  `article_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `external_project_id` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'FK 1/2 referencing redcap_projects_external (not explicitly defined as FK to allow redcap_projects_external to be blown away)',
  `external_custom_type` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'FK 2/2 referencing redcap_projects_external (not explicitly defined as FK to allow redcap_projects_external to be blown away)',
  `search_term` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `matched` int(1) DEFAULT NULL,
  `matched_time` datetime DEFAULT NULL,
  `email_count` int(11) NOT NULL DEFAULT '0',
  `email_time` datetime DEFAULT NULL,
  `unique_hash` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`match_id`),
  UNIQUE KEY `unique_hash` (`unique_hash`),
  KEY `article_id` (`article_id`),
  KEY `project_id` (`project_id`),
  KEY `external_project_id` (`external_project_id`),
  KEY `external_custom_type` (`external_custom_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `redcap_projects_external` (
  `project_id` varchar(32) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Brief user-defined project identifier unique within custom_type',
  `custom_type` varchar(32) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Brief user-defined name for the resource/category/bucket under which the project falls',
  `app_title` text COLLATE utf8_unicode_ci,
  `creation_time` datetime DEFAULT NULL,
  `project_pi_firstname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `project_pi_mi` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `project_pi_lastname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `project_pi_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `project_pi_alias` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `project_pi_pub_exclude` int(1) DEFAULT NULL,
  `project_pub_matching_institution` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`project_id`,`custom_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- ----------------------------------------------------------------------------
--
-- Publication Matching constaints
--
-- ----------------------------------------------------------------------------

ALTER TABLE `redcap_pub_articles`
  ADD CONSTRAINT `redcap_pub_articles_ibfk_1` FOREIGN KEY (`pubsrc_id`) REFERENCES `redcap_pub_sources` (`pubsrc_id`);

ALTER TABLE `redcap_pub_authors`
  ADD CONSTRAINT redcap_pub_authors_ibfk_1 FOREIGN KEY (article_id) REFERENCES redcap_pub_articles (article_id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `redcap_pub_mesh_terms`
  ADD CONSTRAINT redcap_pub_mesh_terms_ibfk_1 FOREIGN KEY (article_id) REFERENCES redcap_pub_articles (article_id) ON DELETE CASCADE ON UPDATE CASCADE;

-- do not cascade on delete because we always want to retain PI input
ALTER TABLE `redcap_pub_matches`
  ADD CONSTRAINT `redcap_pub_matches_ibfk_8` FOREIGN KEY (`project_id`) REFERENCES `redcap_projects` (`project_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `redcap_pub_matches_ibfk_7` FOREIGN KEY (`article_id`) REFERENCES `redcap_pub_articles` (`article_id`) ON UPDATE CASCADE;


-- ----------------------------------------------------------------------------
--
-- Publication Matching project configuration
--
-- ----------------------------------------------------------------------------

-- whether or not to exclude the PI from matched pubs
ALTER TABLE  `redcap_projects` ADD  `project_pi_pub_exclude` INT( 1 ) NULL DEFAULT NULL AFTER  `project_pi_username`;
-- institutions specific to a project
ALTER TABLE  `redcap_projects` ADD  `project_pub_matching_institution` TEXT NULL DEFAULT NULL AFTER  `project_pi_pub_exclude`;

-- Add option to enable/disable survey participant identifiers
ALTER TABLE  `redcap_projects` ADD  `enable_participant_identifiers` INT( 1 ) NOT NULL DEFAULT  '0';

ALTER TABLE  `redcap_projects` ADD  `survey_email_participant_field` VARCHAR( 255 ) NULL COMMENT  'Field name that stores participant email';
ALTER TABLE  `redcap_surveys` DROP  `email_field`;	

ALTER TABLE  `redcap_surveys` ADD  `end_survey_redirect_url` TEXT NULL COMMENT  'URL to redirect to after completing survey';
ALTER TABLE  `redcap_surveys` ADD  `survey_expiration` VARCHAR( 50 ) NULL COMMENT  'Timestamp when survey expires';
ALTER TABLE  `redcap_surveys` ADD  `end_survey_redirect_url_append_id` INT( 1 ) NOT NULL DEFAULT  '0' COMMENT  'Append participant_id to URL' AFTER  `end_survey_redirect_url`;

-- Add new index to redcap_surveys_emails table
ALTER TABLE  `redcap_surveys_emails` ADD INDEX (  `email_sent` );
ALTER TABLE  `redcap_surveys_emails` ADD UNIQUE  `email_id_sent` (  `email_id` ,  `email_sent` );
ALTER TABLE  `redcap_surveys_emails` ADD INDEX  `survey_id_email_sent` (  `survey_id` ,  `email_sent` );

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
  ADD CONSTRAINT redcap_surveys_scheduler_queue_ibfk_1 FOREIGN KEY (ss_id) REFERENCES redcap_surveys_scheduler (ss_id) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE  `redcap_surveys_emails_recipients` ADD  `static_email` VARCHAR( 255 ) NULL COMMENT  'Static email address of recipient (used when participant has no email)';
-- Add new column to redcap_actions table
ALTER TABLE  `redcap_actions` ADD  `survey_id` INT( 10 ) NULL AFTER  `project_id` , ADD INDEX (  `survey_id` );
ALTER TABLE  `redcap_actions` ADD FOREIGN KEY (  `survey_id` ) REFERENCES `redcap_surveys` (`survey_id`) ON DELETE CASCADE ON UPDATE CASCADE ;
ALTER TABLE  `redcap_actions` ADD UNIQUE  `survey_recipient_id` (  `survey_id` ,  `recipient_id` );
-- Template table to be used in the future
CREATE TABLE redcap_projects_templates (
  project_id int(10) NOT NULL DEFAULT '0',
  title text COLLATE utf8_unicode_ci,
  description text COLLATE utf8_unicode_ci,
  PRIMARY KEY (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Info about which projects are used as templates';
ALTER TABLE `redcap_projects_templates`
  ADD CONSTRAINT redcap_projects_templates_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE;

-- Add new field to template table for 5.0
ALTER TABLE  `redcap_projects_templates` ADD  `enabled` INT( 1 ) NOT NULL DEFAULT  '0' COMMENT  'If enabled, template is visible to users in list.';

-- Modify cron table for 5.0 features
ALTER TABLE  `redcap_crons` ADD  `cron_instances_max` INT( 2 ) NOT NULL DEFAULT  '1' COMMENT  'Number of instances that can run simultaneously' AFTER  `cron_max_run_time`;
ALTER TABLE  `redcap_crons` ADD  `cron_instances_current` INT( 2 ) NOT NULL DEFAULT  '0' COMMENT  'Current number of instances running' AFTER  `cron_instances_max`;
ALTER TABLE  `redcap_crons` DROP  `cron_status`;
ALTER TABLE  `redcap_crons_history` CHANGE  `cron_last_run_start`  `cron_run_start` DATETIME NULL DEFAULT NULL ,
	CHANGE  `cron_last_run_end`  `cron_run_end` DATETIME NULL DEFAULT NULL;
ALTER TABLE  `redcap_crons_history` CHANGE  `cron_last_run_status`  `cron_run_status` 
	ENUM(  'PROCESSING',  'COMPLETED',  'FAILED' ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
ALTER TABLE  `redcap_crons` ADD  `cron_last_run_start` DATETIME NULL AFTER  `cron_instances_current`;
-- Add index
ALTER TABLE `redcap_surveys_scheduler` ADD INDEX  `condition_surveycomplete_survey_event` 
	( `condition_surveycomplete_survey_id` ,  `condition_surveycomplete_event_id` );
-- Add fields to redcap_projects table
ALTER TABLE  `redcap_projects` 
	ADD  `data_entry_trigger_url` TEXT NULL COMMENT  'URL for sending Post request when a record is created or modified',
	ADD  `date_deleted` DATETIME NULL COMMENT  'Time that project was flagged for deletion';
-- 
ALTER TABLE  `redcap_projects` ADD  `template_id` INT( 10 ) NULL COMMENT  'If created from a project template, the project_id of the template' 
	AFTER  `data_entry_trigger_url`, ADD INDEX (  `template_id` );
ALTER TABLE  `redcap_projects` ADD FOREIGN KEY (  `template_id` ) 
	REFERENCES `redcap_projects` (`project_id`) ON DELETE SET NULL ON UPDATE CASCADE ;
	

--
-- Constraints for table `redcap_metadata`
--
ALTER TABLE `redcap_metadata`
  ADD CONSTRAINT redcap_metadata_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_metadata_ibfk_2 FOREIGN KEY (edoc_id) REFERENCES redcap_edocs_metadata (doc_id) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `redcap_metadata_archive`
--
ALTER TABLE `redcap_metadata_archive`
  ADD CONSTRAINT redcap_metadata_archive_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_metadata_archive_ibfk_3 FOREIGN KEY (pr_id) REFERENCES redcap_metadata_prod_revisions (pr_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_metadata_archive_ibfk_4 FOREIGN KEY (edoc_id) REFERENCES redcap_edocs_metadata (doc_id) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `redcap_metadata_temp`
--
ALTER TABLE `redcap_metadata_temp`
  ADD CONSTRAINT redcap_metadata_temp_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_metadata_temp_ibfk_2 FOREIGN KEY (edoc_id) REFERENCES redcap_edocs_metadata (doc_id) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `redcap_metadata_prod_revisions`
  ADD CONSTRAINT redcap_metadata_prod_revisions_ibfk_3 FOREIGN KEY (ui_id_approver) REFERENCES redcap_user_information (ui_id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_metadata_prod_revisions_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_metadata_prod_revisions_ibfk_2 FOREIGN KEY (ui_id_requester) REFERENCES redcap_user_information (ui_id) ON DELETE SET NULL ON UPDATE CASCADE;

-- Add column to collect user's last login time
ALTER TABLE  `redcap_user_information` ADD  `user_lastlogin` DATETIME NULL AFTER  `user_lastactivity`;
-- Add placeholder for upcoming Data Quality feature
ALTER TABLE  `redcap_data_quality_rules` ADD  `real_time_execute` INT( 1 ) NOT NULL DEFAULT  '0' COMMENT  'Run in real-time on data entry forms?';