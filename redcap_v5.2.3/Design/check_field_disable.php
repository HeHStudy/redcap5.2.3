<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

// Render 1 if exists in metadata table, and 0 if not
$sql = "select count(1) from redcap_metadata where project_id = $project_id and field_name = '{$_GET['field_name']}'";
print db_result(db_query($sql), 0);
