-- Fix config value (if wrong value)
update redcap_config set value = '0' where field_name = 'superusers_only_move_to_prod' and value = '2';
-- Fix user_firstactivity timestamp in redcap_user_information that got messed up if user reset their own password
update (select x.*, timestamp(min(y.ts)) as ts from (select distinct l.user from redcap_log_event l 
	where l.project_id = 0 and l.event = 'MANAGE' and l.description = 'Change own password' and l.object_type = 'redcap_auth' 
	and l.page = 'Authentication/password_reset.php') x, redcap_log_event y where y.user = x.user group by y.user) a, 
	redcap_user_information u set u.user_firstactivity = a.ts where a.user = u.username;
-- Empty the redcap_dashboard_ip_location_cache table since the service has been updated for more accuracy. 
-- (REDCap will rebuild the contents of this table automatically.)
truncate table redcap_dashboard_ip_location_cache;