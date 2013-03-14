-- Add columns for later use in Calendar/Scheduling link to external systems
ALTER TABLE  `redcap_events_calendar` ADD  `extra_notes` TEXT NULL;
ALTER TABLE  `redcap_events_metadata` ADD  `external_id` VARCHAR( 255 ) NULL, ADD INDEX (  `external_id` );
-- Ensure that only Text field types have validation settings set
update redcap_metadata set element_validation_type = null,	element_validation_min = null, element_validation_max = null, 
	element_validation_checktype = null where element_type != 'text';
update redcap_metadata_archive set element_validation_type = null,	element_validation_min = null, element_validation_max = null, 
	element_validation_checktype = null where element_type != 'text';