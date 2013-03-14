<?php
/*****************************************************************************************
**  REDCap is only available through ACADMEMIC USER LICENSE with Vanderbilt University
******************************************************************************************/

include_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
include_once APP_PATH_DOCROOT . 'Graphical/functions.php';

$form = '';
if (isset($_POST['form'])){ $form = $_POST['form'];
} elseif (isset($_GET['form'])){ $form = $_GET['form'];}
// Ensure it's a real form for this project
if (!isset($Proj->forms[$form])) exit;

// Form description
$form_desc = $Proj->forms[$form]['menu'];

$pdfname = 'Report_' . camelCase($form_desc) . '.pdf';

# Calculate Total Records
//Limit records pulled only to those in user's Data Access Group
if ($user_rights['group_id'] == "") {
	$group_sql  = ""; 
} else {
	$group_sql  = "and d.record in (" . pre_query("select record from redcap_data where field_name = '__GROUPID__' and value = '{$user_rights['group_id']}' and project_id = $project_id") . ")"; 
}
if ($longitudinal) {
	$sql = "select count(1) from (select distinct m.event_id, d.record from redcap_events_forms f, redcap_events_metadata m, 
			redcap_events_arms a, redcap_data d where a.project_id = $project_id and a.project_id = d.project_id and a.arm_id = m.arm_id 
			and f.event_id = m.event_id and f.form_name = '$form' and d.field_name = '$table_pk' and d.event_id = m.event_id $group_sql) as x";
} else {
	$sql = "select count(distinct(d.record)) from redcap_data d where d.project_id = $project_id $group_sql and d.field_name = '$table_pk'";
}
$totalrecs = db_result(db_query($sql),0);

$pdf = rapache_service('HmiscDescPDF', NULL, "$app_title: $form_desc\n".rapache_fields_to_csv($form,$totalrecs,$user_rights['group_id']));

// Set headers
header('Pragma: anytextexeptno-cache', true);
if ($isIE) {
	header('Content-Type: application/force-download');
	//header('Content-type: application/pdf');
} else {
	header('Content-Type: application/octet-stream');
}
header('Content-Length: '.strlen($pdf));
header('Content-disposition: attachment; filename="'.$pdfname.'"');
// Output
print $pdf;
