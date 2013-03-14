<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

// Default response
$response = "0";

if (isset($_POST['field_name']) && preg_match("/[a-z_0-9]/", $_POST['field_name'])) 
{
	//If project is in production, do not allow instant editing (draft the changes using metadata_temp table instead)
	$metadata_table = ($status > 0) ? "redcap_metadata_temp" : "redcap_metadata";
	
	// Check if the table_pk is being deleted. If so, give back different response so as to inform the user of change.
	$sql = "select field_name, element_label from $metadata_table where project_id = $project_id and (
			(element_type = 'calc' and 
				(element_enum like '%[{$_POST['field_name']}]%' or element_enum like '%[{$_POST['field_name']}(%)]%')
			) or 
			(branching_logic like '%[{$_POST['field_name']}]%') or
			(branching_logic like '%[{$_POST['field_name']}(%)]%')
			) order by field_order";
	$q = db_query($sql);
	if (db_num_rows($q) > 0)
	{
		$response = "";
		while ($row = db_fetch_assoc($q))
		{
			$response .= RCView::SP . RCView::SP . "-" . RCView::SP . RCView::b($row['field_name']) . RCView::SP 
					   . "-" . RCView::SP . RCView::escape($row['element_label']) . RCView::br(); 
		}
		// Set message with list of fields
		$response = RCView::p(array(), $lang['design_273'] . RCView::br() . RCView::br(). $lang['design_274'] 
				  . " (<b>{$_POST['field_name']}</b>) " . $lang['design_275'] . RCView::br() . $response);	
	}
}

//Give response back
print $response;