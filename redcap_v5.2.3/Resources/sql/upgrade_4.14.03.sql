-- Modify a certain cron to only run every 12 hours (instead of every 6 hours)
update redcap_crons set cron_frequency = 43200, cron_max_run_time = 7200, 
	cron_last_run_end = concat(DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY),' 06:00:00') where cron_name = 'DbCleanup';