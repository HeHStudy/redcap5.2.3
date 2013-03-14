<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


// Config
require_once dirname(dirname(__FILE__)) . '/Config/init_global.php';

$ExtRes = new ExternalLinks();


// Make sure the ext_id is numeric and that the name was sent
if (!$super_user || !isset($_POST['ext_id']) || (isset($_POST['ext_id']) && !is_numeric($_POST['ext_id']))) exit('0');


## DISPLAY LIST OF ALL PROJECTS
if (isset($_POST['action']) && $_POST['action'] == 'view')
{
	print $ExtRes->displayExcludeProjDialog($_POST['ext_id']);
	exit;
}

## SAVE EXCLUSIONS
elseif (isset($_POST['action']) && $_POST['action'] == 'save' && isset($_POST['exclusions']))
{
	// Delete the existing exclusions from the table before adding the new ones
	$sql = "delete from redcap_external_links_exclude_projects where ext_id = {$_POST['ext_id']}";
	$q = db_query($sql);
	if (!$q) exit('0');
	// Loop through all project_ids to exclude
	foreach (explode(",", $_POST['exclusions']) as $pid)
	{
		// Clean the project_id
		$pid = trim($pid);
		if (!is_numeric($pid)) continue;
		// Insert into table
		$sql = "insert into redcap_external_links_exclude_projects (ext_id, project_id) values ({$_POST['ext_id']}, $pid)";
		$q = db_query($sql);
	}
	// Log the event
	log_event($sql,"redcap_external_links","MANAGE",$_POST['ext_id'],"ext_id = {$_POST['ext_id']}","Save excluded projects for global project bookmark");
	exit('1');
}

// ERROR
print '0';
