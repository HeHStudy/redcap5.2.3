-- Add option to enable/disable survey participant identifiers
ALTER TABLE  `redcap_projects` ADD  `enable_participant_identifiers` INT( 1 ) NOT NULL DEFAULT  '0';
update redcap_projects set enable_participant_identifiers = 1;