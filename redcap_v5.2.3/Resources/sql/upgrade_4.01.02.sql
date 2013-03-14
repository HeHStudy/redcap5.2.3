-- Fix DTS log_event descriptions
update redcap_log_event set description = 'Update record (DTS)' 
	where description in ('Create record - Adjudication', 'Update record - Adjudication');