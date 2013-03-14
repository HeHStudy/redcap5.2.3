

-- Trim all usernames in tables to prevent leading/trailing whitespace
update redcap_user_rights set username = trim(username) where username like '% %';
update redcap_log_event set user = trim(user) where user like '% %';
update redcap_log_view set user = trim(user) where user like '% %';