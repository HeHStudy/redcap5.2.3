<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . "/Config/init_project.php";

// Check if any data exists yet. If not, then stop here because changing PK won't affect anything.
$q = db_query("select 1 from redcap_data where project_id = $project_id limit 1");
if (db_num_rows($q) == 0) exit;

// Check if the table_pk has changed during the recording. If so, give back different response so as to inform the user of change.
$metadata_table = ($status > 0) ? "redcap_metadata_temp" : "redcap_metadata";
$sql = "select field_name from $metadata_table where project_id = $project_id order by field_order limit 1";
$current_table_pk = db_result(db_query($sql), 0);

// Text that will go into pop-up whenever someone changes the location of the first form/field
if ($_GET['moved_source'] == 'form')
{
	// If moved form
	print  "<p>
				<b>{$lang['update_pk_01']}</b> {$lang['update_pk_02']} {$lang['update_pk_04']} \"<b>$current_table_pk</b>\"{$lang['period']} 
				{$lang['update_pk_05']}<br><br>
				<b>{$lang['update_pk_03']}</b><br>" . ($status < 1 ? $lang['update_pk_06'] : $lang['update_pk_09']) . "
			</p>";
}
elseif ($_GET['moved_source'] == 'field')
{
	// If moved the PK field (via drag-n-drop)
	print  "<p>
				<b>{$lang['update_pk_07']}</b> {$lang['update_pk_02']} {$lang['update_pk_08']} \"<b>$current_table_pk</b>\"{$lang['period']} 
				{$lang['update_pk_05']}<br><br>
				<b>{$lang['update_pk_03']}</b><br>" . ($status < 1 ? $lang['update_pk_10'] : $lang['update_pk_11']) . "
			</p>";
}