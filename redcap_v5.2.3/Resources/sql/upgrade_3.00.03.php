<?php

## Bug in 3.0.2 affects page counts, so fix page_hits table using info from log_view table
if ($current_version == "3.0.2") {
	// Get date of update to 3.0.2 from redcap_config
	$redcap_last_install_date = db_result(db_query("select value from redcap_config where field_name = 'redcap_last_install_date' limit 1"), 0);
	if ($redcap_last_install_date == "") $redcap_last_install_date = "2009-11-13"; // Use release date of 3.0.2 if cannot find value
	print "-- Fix bug in \"Page Hits\" chart in Control Center --
delete from redcap_page_hits where date >= '$redcap_last_install_date';
insert into redcap_page_hits select left(datepage,10) as date, substring(datepage, 11, length(datepage)-10) as page, countpage as count 
from (select concat(date(ts), page) as datepage, count(1) as countpage from redcap_log_view where page != 'highlowmiss.php' and event = 'PAGE_VIEW'  
and date(ts) >= '$redcap_last_install_date' group by datepage) as x;
";
}


## Convert tables to InnoDB engine. For larger tables, recreate table as empty copy and slowly migrate data
// Append temp tables with string
$tempTableAppend = "_temp303";
// DDL for tables to rebuild (add as _temp table, then rename after data transfer)
print "
-- Convert all tables to InnoDB table engine -- 
DROP TABLE IF EXISTS redcap_library_map;
CREATE TABLE redcap_library_map (
  project_id int(5) NOT NULL default '0',
  form_name varchar(100) collate utf8_unicode_ci NULL default NULL,
  `type` int(11) NOT NULL default '0',
  library_id int(10) NOT NULL default '0',
  upload_timestamp datetime default NULL,
  acknowledgement text default NULL,
  acknowledgement_cache datetime default NULL,
  PRIMARY KEY  (project_id,form_name,`type`,library_id),
  KEY project_id (project_id),
  KEY library_id (library_id),
  KEY form_name (form_name),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
DROP TABLE IF EXISTS redcap_data{$tempTableAppend};
CREATE TABLE redcap_data{$tempTableAppend} (
  project_id int(5) NOT NULL default '0',
  event_id int(10) default NULL,
  record varchar(100) collate utf8_unicode_ci default NULL,
  field_name varchar(100) collate utf8_unicode_ci default NULL,
  `value` text collate utf8_unicode_ci,
  KEY project_id (project_id),
  KEY event_id (event_id),
  KEY record_field (record,field_name),
  KEY project_field (project_id,field_name),
  KEY project_record (project_id,record),
  KEY proj_record_field (project_id,record,field_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
DROP TABLE IF EXISTS redcap_log_event{$tempTableAppend};
CREATE TABLE redcap_log_event{$tempTableAppend} (
  log_event_id int(11) NOT NULL auto_increment,
  project_id int(5) NOT NULL default '0',
  ts bigint(14) default NULL,
  `user` varchar(255) collate utf8_unicode_ci default NULL,
  ip varchar(15) collate utf8_unicode_ci default NULL,
  `page` varchar(255) collate utf8_unicode_ci default NULL,
  event enum('UPDATE','INSERT','DELETE','SELECT','ERROR','LOGIN','LOGOUT','OTHER','DATA_EXPORT','DOC_UPLOAD','DOC_DELETE','MANAGE') collate utf8_unicode_ci default NULL,
  object_type varchar(128) collate utf8_unicode_ci default NULL,
  sql_log mediumtext collate utf8_unicode_ci,
  pk text collate utf8_unicode_ci,
  event_id int(10) default NULL,
  data_values text collate utf8_unicode_ci,
  description text collate utf8_unicode_ci,
  legacy int(1) NOT NULL default '0',
  PRIMARY KEY  (log_event_id),
  KEY `user` (`user`),
  KEY project_id (project_id),
  KEY user_project (project_id,`user`),
  KEY pk (pk(64)),
  KEY object_type (object_type),
  KEY ts (ts),
  KEY event (event),
  KEY event_project (event,project_id),
  KEY description (description(128))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
DROP TABLE IF EXISTS redcap_metadata{$tempTableAppend};
CREATE TABLE redcap_metadata{$tempTableAppend} (
  project_id int(5) NOT NULL default '0',
  field_name varchar(100) collate utf8_unicode_ci NOT NULL default '',
  field_phi varchar(5) collate utf8_unicode_ci default NULL,
  form_name varchar(255) collate utf8_unicode_ci default NULL,
  form_menu_description varchar(255) collate utf8_unicode_ci default NULL,
  field_order float default NULL,
  field_units varchar(50) collate utf8_unicode_ci default NULL,
  element_preceding_header mediumtext collate utf8_unicode_ci,
  element_type varchar(50) collate utf8_unicode_ci default NULL,
  element_label mediumtext collate utf8_unicode_ci,
  element_enum mediumtext collate utf8_unicode_ci,
  element_note mediumtext collate utf8_unicode_ci,
  element_validation_type varchar(50) collate utf8_unicode_ci default NULL,
  element_validation_min varchar(50) collate utf8_unicode_ci default NULL,
  element_validation_max varchar(50) collate utf8_unicode_ci default NULL,
  element_validation_checktype varchar(50) collate utf8_unicode_ci default NULL,
  branching_logic text collate utf8_unicode_ci,
  field_req int(1) NOT NULL default '0',
  PRIMARY KEY  (project_id,field_name),
  KEY project_id_form (project_id,form_name),
  KEY field_name (field_name),
  KEY project_id (project_id),
  KEY project_id_fieldorder (project_id,field_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
DROP TABLE IF EXISTS redcap_metadata_archive{$tempTableAppend};
CREATE TABLE redcap_metadata_archive{$tempTableAppend} (
  project_id int(5) NOT NULL default '0',
  field_name varchar(100) collate utf8_unicode_ci NOT NULL default '',
  field_phi varchar(5) collate utf8_unicode_ci default NULL,
  form_name varchar(255) collate utf8_unicode_ci default NULL,
  form_menu_description varchar(255) collate utf8_unicode_ci default NULL,
  field_order float default NULL,
  field_units varchar(50) collate utf8_unicode_ci default NULL,
  element_preceding_header mediumtext collate utf8_unicode_ci,
  element_type varchar(50) collate utf8_unicode_ci default NULL,
  element_label mediumtext collate utf8_unicode_ci,
  element_enum mediumtext collate utf8_unicode_ci,
  element_note mediumtext collate utf8_unicode_ci,
  element_validation_type varchar(50) collate utf8_unicode_ci default NULL,
  element_validation_min varchar(50) collate utf8_unicode_ci default NULL,
  element_validation_max varchar(50) collate utf8_unicode_ci default NULL,
  element_validation_checktype varchar(50) collate utf8_unicode_ci default NULL,
  branching_logic text collate utf8_unicode_ci,
  field_req int(1) NOT NULL default '0',
  pr_id int(10) NOT NULL default '0',
  PRIMARY KEY  (project_id,field_name,pr_id),
  KEY project_id_form (project_id,form_name),
  KEY field_name (field_name),
  KEY project_id (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
DROP TABLE IF EXISTS redcap_metadata_temp{$tempTableAppend};
CREATE TABLE redcap_metadata_temp{$tempTableAppend} (
  project_id int(5) NOT NULL default '0',
  field_name varchar(100) collate utf8_unicode_ci NOT NULL default '',
  field_phi varchar(5) collate utf8_unicode_ci default NULL,
  form_name varchar(255) collate utf8_unicode_ci default NULL,
  form_menu_description varchar(255) collate utf8_unicode_ci default NULL,
  field_order float default NULL,
  field_units varchar(50) collate utf8_unicode_ci default NULL,
  element_preceding_header mediumtext collate utf8_unicode_ci,
  element_type varchar(50) collate utf8_unicode_ci default NULL,
  element_label mediumtext collate utf8_unicode_ci,
  element_enum mediumtext collate utf8_unicode_ci,
  element_note mediumtext collate utf8_unicode_ci,
  element_validation_type varchar(50) collate utf8_unicode_ci default NULL,
  element_validation_min varchar(50) collate utf8_unicode_ci default NULL,
  element_validation_max varchar(50) collate utf8_unicode_ci default NULL,
  element_validation_checktype varchar(50) collate utf8_unicode_ci default NULL,
  branching_logic text collate utf8_unicode_ci,
  field_req int(1) NOT NULL default '0',
  PRIMARY KEY  (project_id,field_name),
  KEY project_id_form (project_id,form_name),
  KEY field_name (field_name),
  KEY project_id (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
DROP TABLE IF EXISTS redcap_docs{$tempTableAppend};
CREATE TABLE redcap_docs{$tempTableAppend} (
  docs_id int(11) NOT NULL auto_increment,
  project_id int(5) NOT NULL default '0',
  docs_date date default NULL,
  docs_name text collate utf8_unicode_ci,
  docs_size double default NULL,
  docs_type text collate utf8_unicode_ci,
  docs_file longblob,
  docs_comment text collate utf8_unicode_ci,
  docs_rights text collate utf8_unicode_ci,
  export_file int(1) NOT NULL default '0',
  PRIMARY KEY  (docs_id),
  KEY docs_name (docs_name(128)),
  KEY project_id (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
";
// Array with list of tables that will be copied and rebuilt because they're too large to simply change with the data in them
$tableRebuild = array("redcap_data"=>100000, "redcap_metadata"=>10000, "redcap_metadata_temp"=>10000, 
					  "redcap_metadata_archive"=>10000, "redcap_log_event"=>20000, "redcap_docs"=>5000);
// Get row count for all redcap_tables
$q = db_query("SHOW TABLE STATUS from `$db` like 'redcap_%'");
// Array for storing current rows that tables have
$tableRowCount = array();
while ($row = db_fetch_assoc($q)) {
	if (strpos($row['Name'], "_20") === false) { // Ignore timestamped archive tables
		$tableRowCount[$row['Name']] = $row['Rows'];
		if (!isset($tableRebuild[$row['Name']]) || (isset($tableRebuild[$row['Name']]) && $row['Rows'] <= $tableRebuild[$row['Name']])) {
			// Change table engine
			print "ALTER TABLE {$row['Name']} ENGINE = InnoDB;\n";
			// Remove from $tableRebuild array, if in it (for later looping for renaming purposes), and delete temp table
			if (isset($tableRebuild[$row['Name']])) {
				unset($tableRebuild[$row['Name']]);
				print "DROP TABLE {$row['Name']}{$tempTableAppend};\n";
			}
		} else {
			// Rebuild table because has so much data in it to simply change table engine
			$loop_num = floor($row['Rows']/$tableRebuild[$row['Name']])+1;
			for ($i = 0; $i <= $loop_num; $i++) {
				print "INSERT INTO {$row['Name']}{$tempTableAppend} SELECT * FROM {$row['Name']} LIMIT " . ($i*$tableRebuild[$row['Name']]) . ", " . $tableRebuild[$row['Name']] . ";\n";
			}
		}
	}
}
// Now that all data is transferred, rename tables
print " -- Finalize table rebuilding --\n";
foreach (array_keys($tableRebuild) as $this_table) {
	print "RENAME TABLE $this_table TO $this_table{$timestamp};\n";
	print "RENAME TABLE $this_table{$tempTableAppend} TO $this_table;\n";
}
