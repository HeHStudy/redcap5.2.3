-- Fix validation error for SSN
UPDATE `redcap_validation_types` SET `regex_php` = '/^\\d{3}-\\d\\d-\\d{4}$/' WHERE `validation_name` =  'ssn';
-- Update metadata tables for any fields containing legacy field validation for date/datetime
update redcap_metadata set element_validation_type = concat(element_validation_type,'_ymd') where element_type = 'text'
	and element_validation_type in ('date','datetime','datetime_seconds');
update redcap_metadata_temp set element_validation_type = concat(element_validation_type,'_ymd') where element_type = 'text'
	and element_validation_type in ('date','datetime','datetime_seconds');
update redcap_metadata_archive set element_validation_type = concat(element_validation_type,'_ymd') where element_type = 'text'
	and element_validation_type in ('date','datetime','datetime_seconds');
