-- Add index for IP in log_view table
ALTER TABLE `redcap_log_view` ADD INDEX ( `ip` );