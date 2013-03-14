-- Modify language in existing logs
update redcap_log_event set description = 'Designate data collection instruments for events' 
	where description = 'Designate data entry forms for events';
