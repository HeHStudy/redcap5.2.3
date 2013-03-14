-- Add placeholder for upcoming Data Quality feature
ALTER TABLE  `redcap_data_quality_rules` DROP  `real_time_execute`;
ALTER TABLE  `redcap_data_quality_rules` ADD  `real_time_execute` INT( 1 ) NOT NULL DEFAULT  '0' COMMENT  'Run in real-time on data entry forms?';