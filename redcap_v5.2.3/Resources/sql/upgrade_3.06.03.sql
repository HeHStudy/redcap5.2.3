-- Increase size of IP column to accommodate proxies and future IPv6 addresses
ALTER TABLE  `redcap_log_event` CHANGE  `ip`  `ip` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
ALTER TABLE  `redcap_log_view` CHANGE  `ip`  `ip` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
