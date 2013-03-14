<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

// Disable authentication (will catch issues later if not authentic request)
define("NOAUTH", true);
// Call config file
require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
// Ensure field_name belongs to this project
if (!isset($Proj->metadata[$_GET['field_name']])) exit('ERROR!');
// Get field's label and display it
$label = $Proj->metadata[$_GET['field_name']]['element_label'];
print "<b>$label</b> <i>({$_GET['field_name']})</i>";
