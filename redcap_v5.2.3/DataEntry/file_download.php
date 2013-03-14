<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


// Check if coming from survey or authenticated form
if (isset($_GET['s']) && !empty($_GET['s']))
{
	// Call config_functions before config file in this case since we need some setup before calling config
	require_once dirname(dirname(__FILE__)) . '/Config/init_functions.php';
	// Survey functions needed
	require_once dirname(dirname(__FILE__)) . "/Surveys/survey_functions.php";
	// Validate and clean the survey hash, while also returning if a legacy hash
	$hash = $_GET['s'] = checkSurveyHash();
	// Set all survey attributes as global variables
	setSurveyVals($hash);
	// Now set $_GET['pid'] before calling config
	$_GET['pid'] = $project_id;
	// Set flag for no authentication for survey pages
	define("NOAUTH", true);
}

// Required files
require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
require_once APP_PATH_DOCROOT . 'ProjectGeneral/form_renderer_functions.php';

// Increase memory for large files


// If ID is not in query_string, then return error
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) exit("{$lang['global_01']}!");

// Surveys only: Perform double checking to make sure the survey participant has rights to this file
if (isset($_GET['s']) && !empty($_GET['s']))
{
	checkSurveyFileRights();
}
// Non-surveys: Check form-level rights and DAGs to ensure user has access to this file
elseif (!isset($_GET['s']) || empty($_GET['s']))
{
	checkFormFileRights();
}

//Download file from the "edocs" web server directory
$sql = "select * from redcap_edocs_metadata where project_id = $project_id and doc_id = ".$_GET['id'];
$q = db_query($sql);
if (!db_num_rows($q)) {
	die("<b>{$lang['global_01']}:</b> {$lang['file_download_03']}");
}
$this_file = db_fetch_array($q);


if (!$edoc_storage_option) {

	//Use custom edocs folder (set in Control Center)
	if (!is_dir(EDOC_PATH)) 
	{
		include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
		print  "<div class='red'>
					<b>{$lang['global_01']}!</b><br>{$lang['file_download_04']} <b>".EDOC_PATH."</b> {$lang['file_download_05']} ";
		if ($super_user) print "{$lang['file_download_06']} <a href='".APP_PATH_WEBROOT."ControlCenter/modules_settings.php' style='text-decoration:underline;font-family:verdana;font-weight:bold;'>{$lang['global_07']}</a>.";
		print  "</div>";
		include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
		exit;
	}
		
	//Download from "edocs" folder (use default or custom path for storage)
	$local_file = EDOC_PATH . $this_file['stored_name'];
	if (file_exists($local_file) && is_file($local_file)) 
	{
		header('Pragma: anytextexeptno-cache', true);
		header('Content-Type: '.$this_file['mime_type'].'; name="'.$this_file['doc_name'].'"');
		//header('Content-Length: '.$this_file['doc_size']);
		header('Content-Disposition: attachment; filename="'.$this_file['doc_name'].'"');
		$fh = fopen($local_file, "rb");
		fpassthru($fh);
	} 
	else 
	{
	    die('<b>'.$lang['global_01'].':</b> '.$lang['file_download_08'].' <b>"'.$local_file.
	    	'"</b> ("'.$this_file['doc_name'].'") '.$lang['file_download_09'].'!');
	}

} else {
	
	//Download using WebDAV
	include APP_PATH_WEBTOOLS . 'webdav/webdav_connection.php';
	$wdc = new WebdavClient();
	$wdc->set_server($webdav_hostname);
	$wdc->set_port($webdav_port); $wdc->set_ssl($webdav_ssl);
	$wdc->set_user($webdav_username);
	$wdc->set_pass($webdav_password);
	$wdc->set_protocol(1); //use HTTP/1.1
	$wdc->set_debug(false);
	if (!$wdc->open()) {
		exit($lang['global_01'].': '.$lang['file_download_11']);
	}
	if (substr($webdav_path,-1) != '/') {
		$webdav_path .= '/';
	}
	$http_status = $wdc->get($webdav_path . $this_file['stored_name'], $contents); //$contents is produced by webdav class
	$wdc->close();
	
	//Send file headers and contents
	header('Pragma: anytextexeptno-cache', true);
	header('Content-Type: '.$this_file['mime_type'].'; name="'.$this_file['doc_name'].'"');
	//header('Content-Length: '.$this_file['doc_size']);
	header('Content-Disposition: attachment; filename="'.$this_file['doc_name'].'"');
	ob_clean();
	flush();			
	print $contents;
	
}
	
// Do logging
if (isset($_GET['type']) && $_GET['type'] == "attachment")
{
	// When downloading field image/file attachments
	defined("NOAUTH") or log_event($sql,"redcap_edocs_metadata","MANAGE",$_GET['record'],$_GET['field_name'],"Download image/file attachment");	
}
else
{
	// When downloading edoc files on a data entry form/survey
	defined("NOAUTH") or log_event($sql,"redcap_edocs_metadata","MANAGE",$_GET['record'],$_GET['field_name'],"Download uploaded document");	
}
