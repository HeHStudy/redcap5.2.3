-- New tables for Experimental page
CREATE TABLE IF NOT EXISTS redcap_dashboard_ip_location_cache (
  ip varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  latitude varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  longitude varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  city varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  region varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  country varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE IF NOT EXISTS redcap_dashboard_concept_codes (
  project_id int(5) NOT NULL DEFAULT '0',
  cui varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (project_id,cui),
  KEY cui (cui),
  KEY project_id (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
ALTER TABLE `redcap_dashboard_concept_codes`
  ADD CONSTRAINT redcap_dashboard_concept_codes_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE;
-- Fix incorrectly saved validation types
update redcap_metadata set element_validation_type = 'int' where element_type = 'text' and element_validation_type = 'integer';
update redcap_metadata set element_validation_type = 'float' where element_type = 'text' and element_validation_type = 'number';
update redcap_metadata_temp set element_validation_type = 'int' where element_type = 'text' and element_validation_type = 'integer';
update redcap_metadata_temp set element_validation_type = 'float' where element_type = 'text' and element_validation_type = 'number';
update redcap_metadata_archive set element_validation_type = 'int' where element_type = 'text' and element_validation_type = 'integer';
update redcap_metadata_archive set element_validation_type = 'float' where element_type = 'text' and element_validation_type = 'number';