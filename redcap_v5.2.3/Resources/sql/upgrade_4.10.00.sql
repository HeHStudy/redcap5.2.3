-- Add new system level settings
INSERT INTO `redcap_config` (`field_name` ,`value`) VALUES 
('login_custom_text', ''), 
('auto_prod_changes', '0'),
('enable_edit_prod_events', '0');
-- Change config value name
UPDATE `redcap_config` SET `field_name` =  'edoc_storage_option' WHERE `field_name` =  'edoc_webdav_enabled';