-- Add new tables
DROP TABLE IF EXISTS redcap_external_pages_users;
DROP TABLE IF EXISTS redcap_external_pages;
DROP TABLE IF EXISTS redcap_external_links_dags;
DROP TABLE IF EXISTS redcap_external_links_users;
DROP TABLE IF EXISTS redcap_external_links;
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
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE redcap_external_links_dags (
  ext_id int(11) NOT NULL AUTO_INCREMENT,
  group_id int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (ext_id,group_id),
  KEY ext_id (ext_id),
  KEY group_id (group_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE redcap_external_links_users (
  ext_id int(11) NOT NULL AUTO_INCREMENT,
  username varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (ext_id,username),
  KEY ext_id (ext_id),
  KEY username (username)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
ALTER TABLE `redcap_external_links`
  ADD CONSTRAINT redcap_external_links_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_external_links_ibfk_2 FOREIGN KEY (link_to_project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `redcap_external_links_dags`
  ADD CONSTRAINT redcap_external_links_dags_ibfk_2 FOREIGN KEY (group_id) REFERENCES redcap_data_access_groups (group_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_external_links_dags_ibfk_1 FOREIGN KEY (ext_id) REFERENCES redcap_external_links (ext_id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `redcap_external_links_users`
  ADD CONSTRAINT redcap_external_links_users_ibfk_1 FOREIGN KEY (ext_id) REFERENCES redcap_external_links (ext_id) ON DELETE CASCADE ON UPDATE CASCADE;
-- Add PubMed features
INSERT INTO redcap_config VALUES ('pubmed_matching_enabled', '0'), ('pubmed_matching_institution', 'Vanderbilt OR Meharry');
INSERT INTO redcap_config VALUES ('cron_last_execution', '');
INSERT INTO redcap_config VALUES ('pubmed_matching_last_crawl', '');
INSERT INTO redcap_config VALUES ('doc_to_edoc_transfer_complete', '0');
INSERT INTO redcap_config VALUES ('file_repository_upload_max', '');
INSERT INTO redcap_config VALUES ('file_repository_enabled', '1');
-- Edoc changes
CREATE TABLE redcap_docs_to_edocs (
  docs_id int(11) NOT NULL COMMENT 'PK redcap_docs',
  doc_id int(11) NOT NULL COMMENT 'PK redcap_edocs_metadata',
  PRIMARY KEY (docs_id,doc_id),
  KEY docs_id (docs_id),
  KEY doc_id (doc_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
ALTER TABLE `redcap_docs_to_edocs`
  ADD CONSTRAINT redcap_docs_to_edocs_ibfk_2 FOREIGN KEY (doc_id) REFERENCES redcap_edocs_metadata (doc_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_docs_to_edocs_ibfk_1 FOREIGN KEY (docs_id) REFERENCES redcap_docs (docs_id) ON DELETE CASCADE ON UPDATE CASCADE;
-- Add PI email 
ALTER TABLE  `redcap_projects` ADD  `project_pi_email` VARCHAR( 255 ) NULL AFTER  `project_pi_lastname`;
-- PubMed tables

--
-- Table structure for table 'redcap_pubmed_articles'
--

DROP TABLE IF EXISTS redcap_pubmed_articles;
CREATE TABLE redcap_pubmed_articles (
  article_id int(10) NOT NULL AUTO_INCREMENT,
  pmid int(10) DEFAULT NULL,
  title text COLLATE utf8_unicode_ci,
  pub_date date DEFAULT NULL,
  epub_date date DEFAULT NULL,
  PRIMARY KEY (article_id),
  UNIQUE KEY pmid (pmid)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='PubMed article info';

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_pubmed_authors'
--

DROP TABLE IF EXISTS redcap_pubmed_authors;
CREATE TABLE redcap_pubmed_authors (
  author_id int(10) NOT NULL AUTO_INCREMENT,
  article_id int(10) DEFAULT NULL,
  author varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'AU from PubMed',
  PRIMARY KEY (author_id),
  KEY article_id (article_id),
  KEY author (author)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='PubMed article authors';

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_pubmed_match_pi'
--

DROP TABLE IF EXISTS redcap_pubmed_match_pi;
CREATE TABLE redcap_pubmed_match_pi (
  mpi_id int(10) NOT NULL AUTO_INCREMENT,
  article_id int(10) DEFAULT NULL,
  project_pi varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'PI name from redcap_projects',
  article_pi_match int(1) DEFAULT NULL COMMENT 'Is this the PI''s article?',
  PRIMARY KEY (mpi_id),
  KEY article_id (article_id),
  KEY project_pi (project_pi)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Confirm if article belongs to PI';

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_pubmed_match_project'
--

DROP TABLE IF EXISTS redcap_pubmed_match_project;
CREATE TABLE redcap_pubmed_match_project (
  mpr_id int(10) NOT NULL AUTO_INCREMENT,
  mpi_id int(10) DEFAULT NULL,
  project_id int(10) DEFAULT NULL,
  PRIMARY KEY (mpr_id),
  KEY project_id (project_id),
  KEY mpi_id (mpi_id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Projects matched to article by the PI';

-- --------------------------------------------------------

--
-- Table structure for table 'redcap_pubmed_mesh_terms'
--

DROP TABLE IF EXISTS redcap_pubmed_mesh_terms;
CREATE TABLE redcap_pubmed_mesh_terms (
  mesh_id int(10) NOT NULL AUTO_INCREMENT,
  article_id int(10) DEFAULT NULL,
  mesh_term varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (mesh_id),
  KEY article_id (article_id),
  KEY mesh_term (mesh_term)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `redcap_pubmed_authors`
--
ALTER TABLE `redcap_pubmed_authors`
  ADD CONSTRAINT redcap_pubmed_authors_ibfk_1 FOREIGN KEY (article_id) REFERENCES redcap_pubmed_articles (article_id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `redcap_pubmed_match_pi`
--
ALTER TABLE `redcap_pubmed_match_pi`
  ADD CONSTRAINT redcap_pubmed_match_pi_ibfk_1 FOREIGN KEY (article_id) REFERENCES redcap_pubmed_articles (article_id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `redcap_pubmed_match_project`
--
ALTER TABLE `redcap_pubmed_match_project`
  ADD CONSTRAINT redcap_pubmed_match_project_ibfk_2 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_pubmed_match_project_ibfk_3 FOREIGN KEY (mpi_id) REFERENCES redcap_pubmed_match_pi (mpi_id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `redcap_pubmed_mesh_terms`
--
ALTER TABLE `redcap_pubmed_mesh_terms`
  ADD CONSTRAINT redcap_pubmed_mesh_terms_ibfk_1 FOREIGN KEY (article_id) REFERENCES redcap_pubmed_articles (article_id) ON DELETE CASCADE ON UPDATE CASCADE;

