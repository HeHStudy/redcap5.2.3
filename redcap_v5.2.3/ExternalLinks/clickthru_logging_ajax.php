<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


// Config
require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

// Default response
$response = '0';

// Make sure the ext_id is numeric and that the name was sent
if (!isset($_POST['ext_id']) || (isset($_POST['ext_id']) && !is_numeric($_POST['ext_id']))) exit($response);

// Verify that the resource exists
if ($ExtRes->getResource($_POST['ext_id']) !== false)
{
	// Do logging for this click through
	log_event("","redcap_external_links","MANAGE",$_POST['ext_id'],"ext_id = {$_POST['ext_id']}","Click project bookmark");
	// Send back the URL of the resource
	$response = '1';
}

// Send response
print $response;