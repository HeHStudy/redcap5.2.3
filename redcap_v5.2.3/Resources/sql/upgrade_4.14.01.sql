-- Remove old "beta" publication tables and configuration, if exist
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `redcap_pubmed_articles`;
DROP TABLE IF EXISTS `redcap_pubmed_authors`;
DROP TABLE IF EXISTS `redcap_pubmed_match_pi`;
DROP TABLE IF EXISTS `redcap_pubmed_match_project`;
DROP TABLE IF EXISTS `redcap_pubmed_mesh_terms`;
SET FOREIGN_KEY_CHECKS = 1;
DELETE FROM `redcap_config` WHERE field_name LIKE 'pubmed_matching_%';