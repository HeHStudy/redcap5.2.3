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
if (isset($_POST['ext_id']) && is_numeric($_POST['ext_id']) && $ExtRes->getResource($_POST['ext_id']) !== false)
{
	// If CANCELING and closing the user access list, get the current user access (so we'll know which radio to check)
	if (!isset($_POST['user_access']))
	{
		$response = $ExtRes->getResource($_POST['ext_id']);
		print $response['user_access'];
		exit;
	}

	// Set ALL users with access
	elseif ($_POST['user_access'] == 'ALL')
	{
		// Clear out any existing users already in the external users table
		$sql1 = "delete from redcap_external_links_users where ext_id = " . $_POST['ext_id'];
		$q1 = db_query($sql1);
		// Set value in table to ALL for user_access
		$sql2 = "update redcap_external_links set user_access = 'ALL' where ext_id = " . $_POST['ext_id'];
		$q2 = db_query($sql2);
		// Send response
		if ($q1 && $q2) {
			$response = '1';
			// Log the event
			log_event("$sql1;\n$sql2","redcap_external_links","MANAGE",$_POST['ext_id'],"ext_id = {$_POST['ext_id']}","Edit project bookmark user access");
		}
	}

	// Set SELECTED users with access
	elseif ($_POST['user_access'] == 'SELECTED' && isset($_POST['userlist']))
	{
		// Build array of users passed as CSV string
		$userlist = array();
		foreach (explode(",", $_POST['userlist']) as $user)
		{
			if ($user == '') continue;
			// Clean and escape for query
			$userlist[] = prep(strip_tags(label_decode($user)));
		}
		// First, query the user rights table to ensure these are all real users on the project
		$realuserlist = array();
		$sql = "select distinct username from redcap_user_rights where 
				".(is_numeric('PROJECT_ID') ? "project_id = ".PROJECT_ID." and" : "")."
				username in ('" . implode("', '", $userlist) . "')";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q))
		{
			$realuserlist[] = $row['username'];
		}
		$sql_all = "";
		// Clear out any existing users and DAGs already in tables
		$sql = "delete from redcap_external_links_users where ext_id = " . $_POST['ext_id'];
		$q = db_query($sql);
		$sql_all .= $sql . ";\n";
		$sql = "delete from redcap_external_links_dags where ext_id = " . $_POST['ext_id'];
		$q = db_query($sql);
		$sql_all .= $sql . ";\n";
		// Set value in table to NULL for user_access
		$sql = "update redcap_external_links set user_access = 'SELECTED' where ext_id = " . $_POST['ext_id'];
		$q = db_query($sql);
		$sql_all .= $sql . ";\n";
		// Now add these users that were checked off
		foreach ($realuserlist as $user)
		{
			$sql = "insert into redcap_external_links_users (ext_id, username) values ({$_POST['ext_id']}, '" . prep($user) . "')";
			$q = db_query($sql);
			if (!$q) exit($response);
			$sql_all .= $sql . ";\n";
		}
		// Send back successful response
		$response = '1';
		// Log the event
		log_event($sql_all,"redcap_external_links","MANAGE",$_POST['ext_id'],"ext_id = {$_POST['ext_id']}","Edit project bookmark user access");
	}

	// Set SELECTED DAGs with access
	elseif ($_POST['user_access'] == 'DAG' && isset($_POST['daglist']))
	{
		// Get all group_ids for this project
		$dags = $Proj->getGroups();
		// Build array of DAGs passed as CSV string
		$daglist = array();
		foreach (explode(",", $_POST['daglist']) as $group_id)
		{
			if (!isset($dags[$group_id])) continue;
			$daglist[] = $group_id;
		}
		$sql_all = "";
		// Clear out any existing users and DAGs already in tables
		$sql = "delete from redcap_external_links_users where ext_id = " . $_POST['ext_id'];
		$q = db_query($sql);
		$sql_all .= $sql . ";\n";
		$sql = "delete from redcap_external_links_dags where ext_id = " . $_POST['ext_id'];
		$q = db_query($sql);
		$sql_all .= $sql . ";\n";
		// Set value in table to NULL for user_access
		$sql = "update redcap_external_links set user_access = 'DAG' where ext_id = " . $_POST['ext_id'];
		$q = db_query($sql);
		$sql_all .= $sql . ";\n";
		// Now add these users that were checked off
		foreach ($daglist as $group_id)
		{
			$sql = "insert into redcap_external_links_dags (ext_id, group_id) values ({$_POST['ext_id']}, $group_id)";
			$q = db_query($sql);
			if (!$q) exit($response);
			$sql_all .= $sql . ";\n";
		}
		// Send back successful response
		$response = '1';
		// Log the event
		log_event($sql_all,"redcap_external_links","MANAGE",$_POST['ext_id'],"ext_id = {$_POST['ext_id']}","Edit project bookmark user access");
	}

}


exit($response);
