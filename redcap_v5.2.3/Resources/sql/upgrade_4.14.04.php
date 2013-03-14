<?php

print "
-- Add base URL config setting and set value
update redcap_config set field_name = 'redcap_base_url' where field_name = 'pub_matching_url';\n";

## Get the base REDCap URL and store in config table to use for cron jobs
$oneWeekAgo = date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s"),date("m"),date("d")-7,date("Y")));
$sql = "select substr(baseurl,1,length(baseurl)-8) as baseurl 
		from (select if(substr(baseurl,-9)='index.php', substr(baseurl, 1, length(baseurl)-9), baseurl) as baseurl 
		from (select substr(full_url, 1, locate('?s=', full_url)-1) as baseurl from redcap_log_view 
		where ts > '$oneWeekAgo' and user = '[survey respondent]' and page = 'surveys/index.php' 
		and full_url like '%?s=______') y) x group by baseurl order by count(baseurl) desc limit 1";
$q = db_query($sql);
if (db_num_rows($q) > 0) {
	$redcap_base_url = db_result($q, 0);
} else {
	// Attempt to construct base url using other methods
	list ($server_name, $port, $ssl, $page_full) = getServerNamePortSSL();
	define("PAGE_FULL", $page_full);
	$app_path_webroot = getVersionFolderWebPath();
	$app_path_webroot_full = ($ssl ? "https" : "http") . "://" . $server_name . $port . ((strlen(dirname($app_path_webroot)) <= 1) ? "" : dirname($app_path_webroot)) . "/";
	$redcap_base_url = $app_path_webroot_full;
}
db_free_result($q);
print "update redcap_config set value = '".prep($redcap_base_url)."' where field_name = 'redcap_base_url';\n";