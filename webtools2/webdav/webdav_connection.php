<?php

/**********************************************************
 Replace the values inside the single quotes below with 
 the values for your WebDAV configuration. Do not change
 anything else in this file.
**********************************************************/

$webdav_hostname = 'your_webdav_host_name';
$webdav_username = 'your_webdav_username';
$webdav_password = 'your_webdav_password';
$webdav_port 	 = 'your_webdav_port_number'; // '80' is default. If REDCap web server is exposed to the web, you MUST use SSL (default port '443').
$webdav_path	 = '/edocs_folder/path/'; // Set path where REDCap files will be stored. Must begin and end with a back slash "/".
$webdav_ssl		 = '0'; // '0' is default. If REDCap web server is exposed to the web, you MUST use SSL (set to '1').