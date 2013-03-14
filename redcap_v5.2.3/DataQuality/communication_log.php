<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


// Config
require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

// Instantiate DataQuality object
$dq = new DataQuality();

// Output the html for the communication log
$dq->displayComLog($_POST['rule_id'], $_POST['record'], $_POST['event_id'], $_POST['field_name']);