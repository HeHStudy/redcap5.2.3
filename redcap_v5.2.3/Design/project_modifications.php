<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

// Required files
require_once APP_PATH_DOCROOT . 'Design/functions.php';
require_once APP_PATH_DOCROOT . 'ProjectGeneral/form_renderer_functions.php';

// Increase memory limit



// Kick out if project is not in production status yet
if ($status < 1) 
{
	redirect(APP_PATH_WEBROOT . "index.php?pid=$project_id");
	exit;
}

// Header
include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

// Super User Instructions
if ($super_user && $draft_mode == "2") 
{	
	renderPageTitle("<img src='".APP_PATH_IMAGES."find.png'> {$lang['database_mods_01']}");
	?>
	<p style='margin:20px 0 5px;'>
		<b><?php echo $lang['global_24'] . $lang['colon'] ?></b><br>
		<?php echo $lang['database_mods_03'] ?>
	</p>
	<?php
} 
// Normal User Instructions
elseif (!$super_user && $draft_mode == "1") 
{
	renderPageTitle("<img src='".APP_PATH_IMAGES."find.png'> {$lang['database_mods_04']}");
	?>
	<p style='margin:20px 0 5px;'>
		<?php echo $lang['database_mods_05'] ?> 
	</p>
	<?php
}
// Should not be here
elseif ($draft_mode == "0")
{
	renderPageTitle("<img src='".APP_PATH_IMAGES."find.png'> {$lang['database_mods_01']}");
	?>
	<div class="yellow" style="margin:20px 0;">
		<b><?php echo $lang['global_01'] ?>:</b> <?php echo $lang['database_mods_06'] ?>  
	</div>
	<?php
	renderPrevPageBtn('Design/online_designer.php');
	include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
	exit;
}

// Link to return to design page
print "<p style='margin:20px 0;'>";
renderPrevPageBtn();
print "</p>";

// Get counts of fields added/deleted and HTML for metadata diff table
list ($num_records, $fields_added, $field_deleted, $field_with_data_deleted, $count_new, $count_existing) = renderCountFieldsAddDel2();
list ($newFields, $delFields, $fieldsAddDelText) = renderFieldsAddDel();
list ($num_metadata_changes, $num_fields_changed, $num_critical_issues, $metadataDiffTable) = getMetadataDiff();

// Retrieve email address of requestor of changes
$sql = "select i.user_email from redcap_user_information i, redcap_metadata_prod_revisions r 
		where r.project_id = $project_id and r.ui_id_requester = i.ui_id and r.ts_approved is null 
		order by r.pr_id desc limit 1";
$q = db_query($sql);
$requestor_email = ($q && db_num_rows($q)) ? db_result($q, 0) : '';

// See if auto changes can be made (if enabled)
$willBeAutoApproved = (
		// If the ONLY changes are that new fields were added
		($auto_prod_changes == '2' && $num_fields_changed == 0 && $field_deleted == 0 && $num_critical_issues == 0)
		// If the ONLY changes are that new fields were added OR if there is no data
		|| ($auto_prod_changes == '3' && ($num_records == 0 || ($num_fields_changed == 0 && $field_deleted == 0 && $num_critical_issues == 0)))
		// OR if there are no critical issues AND no fields deleted (regardless of whether or not project has data)
		|| ($auto_prod_changes == '4' && $field_with_data_deleted == 0 && $num_critical_issues == 0) 
		// OR if there are (no critical issues AND no fields deleted) OR if there is no data
		|| ($auto_prod_changes == '1' && ($num_records == 0 || ($field_with_data_deleted == 0 && $num_critical_issues == 0))) 
	) 
	? "<span style='color:green;font-size:13px;'>{$lang['design_100']}</span> <img src='".APP_PATH_IMAGES."tick.png' class='imgfix'>" 
	: "<span style='color:red;'>{$lang['design_292']}</span>";

// Render descriptive summary text about field changes
print  "<p>
			<u><b>{$lang['database_mods_131']}</b></u><br>
			&nbsp;&nbsp;&nbsp;&nbsp;&bull; {$lang['index_22']}{$lang['colon']} <b>$num_records</b><br>
			&nbsp;&nbsp;&nbsp;&nbsp;&bull; <span style='color:green;'>{$lang['database_mods_88']} <b>$fields_added</b></span><br>
			&nbsp;&nbsp;&nbsp;&nbsp;&bull; <span style='color:brown;'>{$lang['database_mods_112']} <b>$num_fields_changed</b></span><br>
			&nbsp;&nbsp;&nbsp;&nbsp;&bull; <span style='color:red;'>{$lang['database_mods_130']} <b>".($field_with_data_deleted+$num_critical_issues)."</b></span><br>
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; - <span style='color:red;font-size:11px;'>".$lang['database_mods_134']." <b>$field_with_data_deleted</b></span><br>
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; - <span style='color:red;font-size:11px;'>".$lang['database_mods_135']." <b>$num_critical_issues</b></span><br>
			&nbsp;&nbsp;&nbsp;&nbsp;&bull; {$lang['database_mods_111']} <b>$count_existing</b><br>
			&nbsp;&nbsp;&nbsp;&nbsp;&bull; {$lang['database_mods_110']} <b>$count_new</b><br>";
if ($auto_prod_changes > 0 && $draft_mode == '1') {
	print "	&nbsp;&nbsp;&nbsp;&nbsp;&bull; <b>{$lang['database_mods_114']}&nbsp; $willBeAutoApproved</b>";
	if ($super_user) {
		print "<span style='color:gray;margin-left:10px;'>(<a style='text-decoration:underline;font-size:11px;' href='".APP_PATH_WEBROOT."ControlCenter/user_settings.php#tr-auto_prod_changes' target='_blank'>{$lang['design_438']}</a>)</span>";
	}
}
print  "</p>";

// Display fields to be added and deleted
 print  "<table cellpadding='0' cellspacing='0'>
			<tr>
				<td valign='top'>$fieldsAddDelText</td>";
// Display key for metadata changes
print  "		<td valign='bottom'>";
renderMetadataCompareKey();
print  "		</td>
			</tr>
		</table>";

		
## DTS: Check for any field changes that would cause DTS to break
if ($dts_enabled_global && $dts_enabled) 
{
	// Get fields used by DTS
	$dtsFields = array_keys(getDtsFields());
	// Get fields used by DTS that are being deleted
	$dtsDelFields = array_intersect($dtsFields, $delFields);
	// Get fields used by DTS that have had their field type changed to invalid type (i.e. not text or textarea)
	$dtsFieldsTypeChange = array();
	$sql = "select m.field_name from redcap_metadata m, redcap_metadata_temp t where m.project_id = t.project_id 
			and m.field_name = t.field_name and m.element_type in ('text', 'textarea') 
			and t.element_type not in ('text', 'textarea') and m.project_id = " . PROJECT_ID . "
			and m.field_name in ('" . implode("', '", $dtsFields) . "')";
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q))
	{
		$dtsFieldsTypeChange[] = $row['field_name'];
	}	
	// Give warning message if DTS fields are being deleted or have their field type changed or (if longitudinal) moved to different form
	if (!empty($dtsDelFields) || !empty($dtsFieldsTypeChange))
	{
		?>
		<div class="red" style="margin:20px 0;">
			<img src="<?php echo APP_PATH_IMAGES ?>exclamation.png" class="imgfix"> 
			<b><?php echo $lang['define_events_64'] ?></b><br>
			<?php 
			echo $lang['database_mods_101']; 
			if (!empty($dtsDelFields)) {
				echo "<br><br>" . $lang['database_mods_102'] . " <b>" . implode("</b>, <b>", $dtsDelFields) . "</b>";
			}
			if (!empty($dtsFieldsTypeChange)) {
				echo "<br><br>" . $lang['database_mods_103'] . " <b>" . implode("</b>, <b>", $dtsFieldsTypeChange) . "</b>";
			}
			?>
		</div>
		<?php
	}
}

		
// SURVEY QUESTION NUMBERING (DEV ONLY): Detect if any forms are a survey, and if so, if has any branching logic. 
// If so, disable question auto numbering.
foreach (array_keys($Proj->surveys) as $this_survey_id)
{
	$this_form = $Proj->surveys[$this_survey_id]['form_name'];
	if ($Proj->surveys[$this_survey_id]['question_auto_numbering'] && Design::checkSurveyBranchingExists($this_form,"redcap_metadata_temp"))
	{
		// Give user a prompt as notice of this change
		?>
		<div class="yellow" style="margin:20px 0;">
			<img src="<?php echo APP_PATH_IMAGES ?>exclamation_orange.png" class="imgfix">
			<?php echo "<b>{$lang['survey_08']} \"<span style='color:#800000;'>".strip_tags(label_decode($Proj->surveys[$this_survey_id]['title']))."</span>\"</b><br>{$lang['survey_07']} {$lang['survey_10']}" ?>
		</div>
		<?php
	}
}



// Render table to display metadata changes
print $metadataDiffTable;
 
// Buttons for committing/undoing changes
if ($super_user && $status > 0 && $draft_mode == 2) 
{
	print  "<div class='blue' id='commitBtns' style='margin-bottom:50px;margin-top:20px;padding-bottom:15px;padding-top:5px;'>
				<div style='margin:0 0 20px;font-weight:bold;'>
					<img src='".APP_PATH_IMAGES."gear.png' class='imgfix' style='margin-right:2px;'>
					{$lang['database_mods_149']}
				</div>

				{$lang['database_mods_136']}
				<div style='margin:4px 0 20px;'>
					<button class='jqbuttonmed' onclick=\"
							simpleDialog(null,null,'userConfirmEmail',600,null,'".cleanHtml($lang['global_53'])."',
							'sendSingleEmail($(\'#emailFrom option:selected\').text(),$(\'#emailTo\').val(),$(\'#emailTitle\').val(),$(\'#emailCont\').val(),true)',
							'" . cleanHtml($lang['database_mods_150']). "');
						\"><img src='".APP_PATH_IMAGES."email.png' style='vertical-align:middle;'> 
						<span style='vertical-align:middle;'>{$lang['database_mods_137']}</span></button>
				</div>
				
				{$lang['database_mods_07']}
				<div style='margin:4px 0 20px;'>
					<button class='jqbuttonmed' onclick=\"
						simpleDialog('" . cleanHtml($lang['database_mods_09']) . "<br>" . cleanHtml($lang['database_mods_10']) . "',
							'" . cleanHtml($lang['database_mods_08']). "',null,null,null,
							'".cleanHtml($lang['global_53'])."',
							'$(\'#commitBtns :button\').button(\'disable\');showProgress(1);window.location.href = app_path_webroot+\'Design/draft_mode_approve.php?pid=$project_id\';',
							'".cleanHtml($lang['database_mods_138'])."');
					\"><img src='".APP_PATH_IMAGES."tick.png' style='vertical-align:middle;'> <span style='vertical-align:middle;'>{$lang['database_mods_138']}</span></button>
					&nbsp;&nbsp;
					<button class='jqbuttonmed' onclick=\"
						simpleDialog('" . cleanHtml($lang['database_mods_12']) . "',
							'" . cleanHtml($lang['database_mods_11']). "',null,null,null,
							'".cleanHtml($lang['global_53'])."',
							'$(\'#commitBtns :button\').button(\'disable\');showProgress(1);window.location.href = app_path_webroot+\'Design/draft_mode_reject.php?pid=$project_id\';',
							'".cleanHtml($lang['database_mods_139'])."');
					\"><img src='".APP_PATH_IMAGES."arrow_left.png' style='vertical-align:middle;'> <span style='vertical-align:middle;'>{$lang['database_mods_139']}</span></button>
					&nbsp;&nbsp;
					<button class='jqbuttonmed' onclick=\"
						simpleDialog('" . cleanHtml($lang['database_mods_14']) . "',
							'" . cleanHtml($lang['database_mods_13']). "',null,null,null,
							'".cleanHtml($lang['global_53'])."',
							'$(\'#commitBtns :button\').button(\'disable\');showProgress(1);window.location.href = app_path_webroot+\'Design/draft_mode_reset.php?pid=$project_id\';',
							'".cleanHtml($lang['database_mods_140'])."');
					\"><img src='".APP_PATH_IMAGES."cross.png' style='vertical-align:middle;'> <span style='vertical-align:middle;'>{$lang['database_mods_140']}</span></button>
				</div>
			</div>
			
			<!-- Hidden div for emailing user confirmation email -->
			<div id='userConfirmEmail' class='simpleDialog' style='background-color:#F3F5F5;' title=\"" . cleanHtml2($lang['database_mods_137']). "\">
				<div style='padding-bottom:10px;margin-bottom:15px;border-bottom:1px solid #ccc;'>
					{$lang['database_mods_151']}
				</div>
				<table border=0 cellspacing=0 width=100%>
					<tr>
						<td style='vertical-align:middle;width:60px;font-weight:bold;'>{$lang['global_37']}</td>
						<td style='vertical-align:middle;color:#555;'>
						" . User::emailDropDownList() . "
					</tr>
					<tr>
						<td style='vertical-align:middle;width:60px;padding-top:10px;font-weight:bold;'>{$lang['global_38']}</td>
						<td style='vertical-align:middle;padding-top:10px;color:#555;'>
							<input class='x-form-text x-form-field' style='font-family:arial;width:50%;' type='text' id='emailTo' name='emailTo' onkeydown='if(event.keyCode == 13){return false;}' onblur=\"redcap_validate(this,'','','soft_typed','email')\" value='".cleanHtml($requestor_email)."'>
							&nbsp; {$lang['database_mods_142']}
						</td>
					</tr>
					<tr>
						<td style='vertical-align:middle;width:60px;padding:10px 0;font-weight:bold;'>{$lang['survey_103']}</td>
						<td style='vertical-align:middle;padding:10px 0;'><input class='x-form-text x-form-field' style='font-family:arial;width:80%;' type='text' id='emailTitle' name='emailTitle' onkeydown='if(event.keyCode == 13){return false;}' value='".cleanHtml("[REDCap] ".$lang['database_mods_141'])."'></td>
					</tr>
					<tr>
						<td colspan='2' style='padding:5px 0 10px;'>
							<textarea class='x-form-field notesbox' id='emailCont' name='emailCont' style='font-family:arial;height:310px;width:95%;'>".remBr($lang['database_mods_143'])."\n\n".remBr($lang['database_mods_144'])." \"<b>".strip_tags(label_decode($app_title))."</b>\"{$lang['period']} ".remBr($lang['database_mods_145'])."\n\n".remBr($lang['database_mods_152'])."\n\n".remBr($lang['database_mods_146'])."\n".APP_PATH_WEBROOT_FULL."redcap_v{$redcap_version}/".PAGE."?pid=$project_id\n\n".remBr($lang['database_mods_147'])."\n".remBr($lang['database_mods_148'])."</textarea>
						</td>
					</tr>
					</table>
			</div>";
}


// Link to return to design page (don't show if no changes - real short page doesn't need two buttons to go back)
if ($num_metadata_changes > 0) 
{
	print "<p style='margin:20px 0;'>";
	renderPrevPageBtn();
	print "</p>";
}

include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
