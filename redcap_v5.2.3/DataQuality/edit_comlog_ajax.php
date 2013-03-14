<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


// Config
require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

// Instantiate DataQuality object
$dq = new DataQuality();

// Check values
if (!isset($_POST['comment'])) $_POST['comment'] = null;
if (!isset($_POST['status']))  $_POST['status']  = null;

// Output the response
print $dq->modifyChangeLog($_POST['rule_id'], $_POST['record'], $_POST['event_id'], $_POST['comment'], $_POST['status'], $_POST['field_name']);