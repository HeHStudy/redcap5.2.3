<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

// Check if variable current exists in project
$metadata_table = ($status > 0) ? "redcap_metadata_temp" : "redcap_metadata";
$fieldAttr = ($status > 0) ? $Proj->metadata_temp[$_GET['field_name']] : $Proj->metadata[$_GET['field_name']];

if (isset($fieldAttr) && is_array($fieldAttr)) {
	// Variable exists. Query to get form menu label
	$sql = "select form_menu_description from $metadata_table where project_id = $project_id 
			and form_name = '".prep($fieldAttr['form_name'])."' and form_menu_description is not null 
			order by field_order limit 1";
	$q = db_query($sql);
	print (db_num_rows($q) > 0) ? strip_tags(label_decode(db_result($q, 0))) : '0';
} else {
	// Variable does not exist
	print '0';
}