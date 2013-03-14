<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


// Config
require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

// Instantiate DataQuality object
$dq = new DataQuality();

// Data quality rules pop-up message for Data Entry page
$dq->displayViolationsSingleRecord(explode(",", $_POST['dq_error_ruleids']), $_POST['record'], 
	$_POST['event_id'], $_POST['page'], $_POST['show_excluded']);