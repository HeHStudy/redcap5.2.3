<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


// Config
require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
require_once APP_PATH_DOCROOT . 'ProjectGeneral/form_renderer_functions.php';

// Make sure all parameters are good
if (!$randomization || ($status > 0 && !$super_user) || !isset($Proj->metadata[$_POST['field']])) exit('');

// Return number of records containing data for a given field
print Randomization::getFieldDataCount($_POST['field']);
