

DROP TABLE IF EXISTS redcap_external_links_exclude_projects;
CREATE TABLE redcap_external_links_exclude_projects (
  ext_id int(11) NOT NULL AUTO_INCREMENT,
  project_id int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (ext_id,project_id),
  KEY ext_id (ext_id),
  KEY project_id (project_id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Projects to exclude for global external links';
ALTER TABLE `redcap_external_links_exclude_projects`
  ADD CONSTRAINT redcap_external_links_exclude_projects_ibfk_2 FOREIGN KEY (project_id) REFERENCES redcap_projects (project_id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT redcap_external_links_exclude_projects_ibfk_1 FOREIGN KEY (ext_id) REFERENCES redcap_external_links (ext_id) ON DELETE CASCADE ON UPDATE CASCADE;
update redcap_log_event set description = replace(description, 'external link', 'project bookmark') 
	where ts > 20110800000000 and event = 'MANAGE' and description like '%external link%';