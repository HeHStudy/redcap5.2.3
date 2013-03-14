<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


// Config
if (isset($_GET['pid']) && $_GET['pid'] != 'null') {
	require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
	$project_id_sql = " = " . PROJECT_ID;
} else {
	require_once dirname(dirname(__FILE__)) . '/Config/init_global.php';
	define('PROJECT_ID', 'NULL');
	$project_id_sql = " IS NULL";
	$ExtRes = new ExternalLinks();
}

// Default response
$response = '0';

// Validate the request
if (isset($_POST['ext_id']) && is_numeric($_POST['ext_id']))
{
	// Get this link resource
	$resource = $ExtRes->getResource($_POST['ext_id']);
	// Validate resource
	if ($resource !== false || $_POST['ext_id'] == '0')
	{
		// If user_access = ALL, then set all users as pre-checked
		if ($resource['user_access'] == 'ALL' || $_POST['ext_id'] == '0')
		{
			$users = 'ALL';		
		}
		// Get list of user in the external users table
		else
		{
			$sql = "select username from redcap_external_links_users where ext_id = " . $_POST['ext_id'];
			$q = db_query($sql);
			$users = array();
			while ($row = db_fetch_assoc($q))
			{
				$users[] = $row['username'];
			}
		}
		// Send back response
		$ExtRes->displayUserList($users);
		exit;
	}
}


exit($response);
