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

// Make sure the ext_id is numeric and that the name was sent
if (!isset($_POST['ext_id']) || (isset($_POST['ext_id']) && !is_numeric($_POST['ext_id']))) exit('[ERROR!]');


## ADD NEW RESOURCE
if ($_POST['ext_id'] == '0' && !isset($_POST['action']))
{
	// Clean submitted values
	$_POST['label'] = strip_tags(label_decode($_POST['label']));
	$_POST['newwin'] = ($_POST['newwin'] == '1') ? 1 : 0;
	$_POST['append_rec'] = ($_POST['append_rec'] == '1') ? 1 : 0;
	$_POST['append_pid'] = ($_POST['append_pid'] == '1') ? 1 : 0;
	
	// If linking to another REDCap project
	if ($_POST['linktype'] == 'REDCAP_PROJECT' && is_numeric($_POST['link_to_project_id'])) {
		$_POST['url'] = "";	
	} else {
		$_POST['url'] = html_entity_decode($_POST['url'], ENT_QUOTES);
		$_POST['link_to_project_id'] = "";
	}
	
	$sql_all = "";
	
	// Get the next order number
	$sql = "select max(link_order) from redcap_external_links where project_id $project_id_sql";
	$q = db_query($sql);
	$max_link_order = db_result($q, 0);
	$next_link_order = (is_numeric($max_link_order) ? $max_link_order+1 : 1);

	// Insert into table
	$sql = "insert into redcap_external_links (project_id, link_order, link_label, link_url, open_new_window, link_type, 
			link_to_project_id, user_access, append_record_info, append_pid) values 
			(" . (is_numeric(PROJECT_ID) ? PROJECT_ID : "null") . ", $next_link_order, '" . prep($_POST['label']) . "', 
			" . checkNull($_POST['url']) . ", {$_POST['newwin']}, '" . prep($_POST['linktype']) . "', 
			" . checkNull($_POST['link_to_project_id']) . ", '" . prep($_POST['user_access']) . "', {$_POST['append_rec']}, {$_POST['append_pid']})";
	$q = db_query($sql);
	if (!$q) exit('[ERROR!]');
	$new_ext_id = db_insert_id();
	$sql_all .= $sql . ";\n";
	
	// For Global Ext Links, loop through all project_ids to exclude
	if (!is_numeric(PROJECT_ID))
	{
		foreach (explode(",", $_POST['exclusions']) as $pid)
		{
			// Clean the project_id
			$pid = trim($pid);
			if (!is_numeric($pid)) continue;
			// Insert into table
			$sql = "insert into redcap_external_links_exclude_projects (ext_id, project_id) values ($new_ext_id, $pid)";
			$q = db_query($sql);
		}
	}
	
	// Now add users to the users table (if selected users)
	if ($_POST['user_access'] == 'SELECTED')
	{
		// Build array of users passed as CSV string
		$userlist = array();
		foreach (explode(",", trim($_POST['userlist'])) as $user)
		{
			if ($user == '') continue;
			// Clean and escape for query
			$userlist[] = prep(strip_tags(label_decode($user)));
		}
		// First, query the user rights table to ensure these are all real users on the project
		$realuserlist = array();
		if (!is_numeric(PROJECT_ID)) {
			$sql = "(select username from redcap_user_information where username in ('" . implode("', '", $userlist) . "'))
					union (select username from redcap_user_rights where username in ('" . implode("', '", $userlist) . "'))";
		} else {
			$sql = "select username from redcap_user_rights where project_id = $project_id and
					username in ('" . implode("', '", $userlist) . "')";
		}
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q))
		{
			$realuserlist[] = $row['username'];
		}
		// Now add these users that were checked off
		foreach ($realuserlist as $user)
		{
			$sql = "insert into redcap_external_links_users (ext_id, username) values ($new_ext_id, '" . prep($user) . "')";
			$q = db_query($sql);
			if (!$q) exit($response);
			$sql_all .= $sql . ";\n";
		}
	}	
	// Now add DAGs to the DAGs table (if selected DAGs)
	elseif ($_POST['user_access'] == 'DAG')
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
		// Now add these users that were checked off
		foreach ($daglist as $group_id)
		{
			$sql = "insert into redcap_external_links_dags (ext_id, group_id) values ($new_ext_id, $group_id)";
			$q = db_query($sql);
			if (!$q) exit($response);
			$sql_all .= $sql . ";\n";
		}
	}
	
	// Log the event
	log_event($sql_all,"redcap_external_links","MANAGE",$new_ext_id,"ext_id = $new_ext_id","Create project bookmark");
	
	// Get html for the resources table
	$resTableHtml = $ExtRes->displayResourcesTable();
	
	// Send back JSON
	print '{"new_ext_id":' . $new_ext_id . ',"payload":"' . cleanHtml2($resTableHtml) . '"}';
}



## DELETE
elseif (isset($_POST['action']) && $_POST['action'] == 'delete')
{
	// Delete the resource from the table
	$sql = "delete from redcap_external_links where ext_id = {$_POST['ext_id']} and project_id $project_id_sql";
	$q = db_query($sql);
	if (!$q) exit('');
	// Log the event
	log_event($sql,"redcap_external_links","MANAGE",$_POST['ext_id'],"ext_id = {$_POST['ext_id']}","Delete project bookmark");
	// Reorder link orders (in case gets out of order now that one was deleted)
	$ExtRes->reorder();
	// Get html for the resources table
	print $ExtRes->displayResourcesTable();
}



## REORDER
elseif (isset($_POST['action']) && $_POST['action'] == 'reorder')
{	
	// Validation of number of resources submitted
	$resource_ids = array_keys($ExtRes->getResources());
	$num_resources = count($resource_ids);
	// Loop through the submitted ext_ids and validation them
	$resource_ids_submitted = array();
	foreach (explode(",", trim($_POST['ext_ids'])) as $res_id)
	{
		// Ensure it's a real ext_id
		if (in_array($res_id, $resource_ids))
		{
			$resource_ids_submitted[] = $res_id;
		}
	}
	if (count($resource_ids) != count($resource_ids_submitted)) exit('0');
	// Loop through new order of links passed as CSV string, and save the order
	$counter = 1;
	$sql_all = "";
	foreach ($resource_ids_submitted as $res_id)
	{
		// Update the table with the new order value
		$sql = "update redcap_external_links set link_order = $counter
				where ext_id = $res_id and project_id $project_id_sql";
		$q = db_query($sql);
		if (!$q) exit('0');
		$sql_all .= $sql . ";\n";
		$counter++;
	}
	// Log the event
	log_event($sql_all,"redcap_external_links","MANAGE",PROJECT_ID,"project_id = ".PROJECT_ID,"Reorder project bookmarks");
	// Set response
	print '1';
}



## EDIT
elseif (isset($_POST['action']) && $_POST['action'] == 'edit')
{
	## EDIT LINK LABEL
	if (isset($_POST['label']))
	{
		// Clean the rule name submitted
		$_POST['label'] = strip_tags(label_decode($_POST['label']));
		// Update the table with the new name
		$sql = "update redcap_external_links set link_label = '" . prep($_POST['label']) . "'
				where ext_id = {$_POST['ext_id']} and project_id $project_id_sql";
		$q = db_query($sql);
		if (!$q) exit('[ERROR!]');
		// Log the event
		log_event($sql,"redcap_external_links","MANAGE",$_POST['ext_id'],"ext_id = {$_POST['ext_id']}","Edit project bookmark");
		// Get new resource info
		$resource_info = $ExtRes->getResource($_POST['ext_id']);
		// Return the new label that was just saved
		print $resource_info['label'];
	}

	## EDIT LINK URL
	elseif (isset($_POST['url']))
	{
		// Clean the url
		$_POST['url'] = html_entity_decode($_POST['url'], ENT_QUOTES);
		// Update the table with the new url
		$sql = "update redcap_external_links set link_url = '" . prep($_POST['url']) . "'
				where ext_id = {$_POST['ext_id']} and project_id $project_id_sql";
		$q = db_query($sql);
		if (!$q) exit('[ERROR!]');
		// Log the event
		log_event($sql,"redcap_external_links","MANAGE",$_POST['ext_id'],"ext_id = {$_POST['ext_id']}","Edit project bookmark");
		// Get new resource info
		$resource_info = $ExtRes->getResource($_POST['ext_id']);
		// Return the new url that was just saved
		print $resource_info['url'];
	}

	## EDIT LINK TYPE
	elseif (isset($_POST['link_type']))
	{
		// Set flag
		$return_html_table = false;
		// Clean
		$_POST['link_type'] = html_entity_decode($_POST['link_type'], ENT_QUOTES);
		// If linking to another REDCap project, then set the link_to_project_id value
		$append_sql = "null";
		if ($_POST['link_type'] == 'REDCAP_PROJECT' && is_numeric($_POST['link_to_project_id']))
		{
			$append_sql = $_POST['link_to_project_id'] . ", link_url = null";
			// Set flag
			$return_html_table = true;
		}
		// Update the table with the new link_type
		$sql = "update redcap_external_links set link_type = '" . prep($_POST['link_type']) . "', link_to_project_id = $append_sql
				where ext_id = {$_POST['ext_id']} and project_id $project_id_sql";
		$q = db_query($sql);
		if (!$q) exit('0');
		// Log the event
		log_event($sql,"redcap_external_links","MANAGE",$_POST['ext_id'],"ext_id = {$_POST['ext_id']}","Edit project bookmark");
		// Determine response
		print $ExtRes->displayResourcesTable();
	}

	## EDIT "OPEN NEW WINDOW" SETTING
	elseif (isset($_POST['newwin']))
	{
		// Clean
		$_POST['newwin'] = ($_POST['newwin'] == '1') ? 1 : 0;
		// Update the table with the new "open new window" setting
		$sql = "update redcap_external_links set open_new_window = '" . prep($_POST['newwin']) . "'
				where ext_id = {$_POST['ext_id']} and project_id $project_id_sql";
		$q = db_query($sql);
		// Set response
		if ($q) {
			print '1';
			// Log the event
			log_event($sql,"redcap_external_links","MANAGE",$_POST['ext_id'],"ext_id = {$_POST['ext_id']}","Edit project bookmark");
		} else {
			print '0';
		}
	}

	## EDIT "APPEND RECORD INFO" SETTING
	elseif (isset($_POST['append_rec']))
	{
		// Clean
		$_POST['append_rec'] = ($_POST['append_rec'] == '1') ? 1 : 0;
		// Update the table with the new "open new window" setting
		$sql = "update redcap_external_links set append_record_info = {$_POST['append_rec']}
				where ext_id = {$_POST['ext_id']} and project_id $project_id_sql";
		$q = db_query($sql);
		// Set response
		if ($q) {
			print '1';
			// Log the event
			log_event($sql,"redcap_external_links","MANAGE",$_POST['ext_id'],"ext_id = {$_POST['ext_id']}","Edit project bookmark");
		} else {
			print '0';
		}
	}

	## EDIT "APPEND PROJECT ID" SETTING
	elseif (isset($_POST['append_pid']))
	{
		// Clean
		$_POST['append_pid'] = ($_POST['append_pid'] == '1') ? 1 : 0;
		// Update the table with the new "open new window" setting
		$sql = "update redcap_external_links set append_pid = {$_POST['append_pid']}
				where ext_id = {$_POST['ext_id']} and project_id $project_id_sql";
		$q = db_query($sql);
		// Set response
		if ($q) {
			print '1';
			// Log the event
			log_event($sql,"redcap_external_links","MANAGE",$_POST['ext_id'],"ext_id = {$_POST['ext_id']}","Edit project bookmark");
		} else {
			print '0';
		}
	}
}
