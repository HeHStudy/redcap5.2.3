-- ----------------------------------------------------------------------------
--
-- Remove old "beta" publication tables and configuration
--
-- ----------------------------------------------------------------------------

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `redcap_pubmed_articles`;
DROP TABLE IF EXISTS `redcap_pubmed_authors`;
DROP TABLE IF EXISTS `redcap_pubmed_match_pi`;
DROP TABLE IF EXISTS `redcap_pubmed_match_project`;
DROP TABLE IF EXISTS `redcap_pubmed_mesh_terms`;
SET FOREIGN_KEY_CHECKS = 1;

DELETE FROM `redcap_config` WHERE field_name LIKE 'pubmed_matching_%';

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


-- ----------------------------------------------------------------------------
--
-- Publication Matching global configuration
--
-- ----------------------------------------------------------------------------

INSERT INTO redcap_config (field_name, value) VALUES
('pub_matching_enabled', '0'),
('pub_matching_url', NULL),
('pub_matching_emails', '0'),
('pub_matching_email_days', '7'),
('pub_matching_email_limit', '3'),
('pub_matching_email_text', NULL),
('pub_matching_email_subject', NULL),
('pub_matching_institution', 'Vanderbilt\nMeharry');


-- ----------------------------------------------------------------------------
--
-- Publication Matching publication sources
--
-- ----------------------------------------------------------------------------

INSERT INTO `redcap_pub_sources` (`pubsrc_id`, `pubsrc_name`, `pubsrc_last_crawl_time`) VALUES
(1, 'PubMed', NULL);

-- Add system-level option to enable/disable the randomization module
INSERT INTO `redcap_config` (`field_name`, `value`) VALUES ('randomization_global', '1');