-- Add new tables
CREATE TABLE redcap_external_links (
  ext_id int(10) NOT NULL AUTO_INCREMENT,
  project_id int(10) DEFAULT NULL,
  link_order int(5) NOT NULL DEFAULT '1',
  link_url text COLLATE utf8_unicode_ci,
  link_label text COLLATE utf8_unicode_ci,
  open_new_window int(10) NOT NULL DEFAULT '0',
  link_type enum('LINK','POST_AUTHKEY','REDCAP_PROJECT','REDCAP_PLUGIN') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'LINK',
  link_to_project_id int(10) DEFAULT NULL,
  user_access enum('ALL') COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'If not ALL, then check external_pages_users table',
  PRIMARY KEY (ext_id),
  KEY project_id (project_id),
  KEY link_to_project_id (link_to_project_id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
ALTER TABLE `redcap_external_links`
  ADD CONSTRAINT redcap_external_links_ibfk_1 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_external_links_ibfk_2 FOREIGN KEY (link_to_project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE;

CREATE TABLE redcap_external_links_users (
  ext_id int(11) NOT NULL AUTO_INCREMENT,
  username varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (ext_id,username),
  KEY ext_id (ext_id),
  KEY username (username)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
ALTER TABLE `redcap_external_links_users`
  ADD CONSTRAINT redcap_external_links_users_ibfk_1 FOREIGN KEY (ext_id) REFERENCES redcap_external_links (ext_id) ON DELETE CASCADE ON UPDATE CASCADE;
