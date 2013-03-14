<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


// Config
require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

// Instantiate DataQuality object
$dq = new DataQuality();

// Output the response
print $dq->modifyChangeLog($_POST['rule_id'], $_POST['record'], $_POST['event_id'], null, null, $_POST['field_name'], $_POST['exclude']);

// Log the event
$logmsg = $_POST['exclude'] ? "Exclude result from data quality rule" : "Include result for data quality rule";
$logdata = "rule_id = '{$_POST['rule_id']}'\nrecord = '{$_POST['record']}'\nevent_id = {$_POST['event_id']}\n";
if ($_POST['field_name'] != '') {
	$logdata .= "field_name = '{$_POST['field_name']}'\n";
}
$logdata .= "exclude = {$_POST['exclude']}";
log_event("","redcap_data_quality_status","MANAGE",$_POST['rule_id'],$logdata,$logmsg);