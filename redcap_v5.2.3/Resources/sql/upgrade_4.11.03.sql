-- FIX MISSING TIMESTAMPS IN USER_INFORMATION TABLE
-- Back-fill first activity timestamp from logging
update redcap_user_information u, (select i.username, timestamp(min(e.ts)) as user_firstactivity 
	from redcap_user_information i, redcap_log_event e 
	where i.user_lastactivity is not null and i.user_firstactivity is null 
	and i.user_firstvisit is not null and i.username != '' 
	and e.ts > i.user_firstvisit*1 and e.user = i.username group by i.username) x
	set u.user_firstactivity = x.user_firstactivity
	where x.username = u.username;
-- Back-fill any other missing first activity timestamps from last activity timestamp
update redcap_user_information set user_firstactivity = user_lastactivity
	where user_lastactivity is not null and user_firstactivity is null;
-- Back-fill first visit timestamp (if missing) from first activity timestamp
update redcap_user_information set user_firstvisit = user_firstactivity
	where user_firstactivity is not null and user_firstvisit is null;