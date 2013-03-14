<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


// Config
require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

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
			$dags = 'ALL';		
		}
		// Get list of user in the external users table
		else
		{
			$sql = "select group_id from redcap_external_links_dags where ext_id = " . $_POST['ext_id'];
			$q = db_query($sql);
			$dags = array();
			while ($row = db_fetch_assoc($q))
			{
				$dags[] = $row['group_id'];
			}
		}
		// Send back response
		$ExtRes->displayDagList($dags);
		exit;
	}
}


exit($response);
