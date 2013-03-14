<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

// If user is not allowed to create/copy projects, then redirect back to Project Setup page
if (!$allow_create_db && !$super_user)
{
	redirect(APP_PATH_WEBROOT . "ProjectSetup/index.php?pid=$project_id");
}

// Count project records
$num_records = db_result(db_query("select count(distinct(record)) from redcap_data where project_id = " . PROJECT_ID . " and field_name = '$table_pk'"),0);
	
include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

renderPageTitle();

 
/**
 * Modify Project Settings FORM
 */
print  "<br><br><div style='max-width:700px;border:1px solid #d0d0d0;padding:0px 15px 15px 15px;background-color:#f5f5f5;'>";	
print  "<h3 style='border-bottom: 1px solid #aaa; padding: 3px; font-weight: bold;color:#800000;'>
			<img src='".APP_PATH_IMAGES."pencil.png' class='imgfix2'> {$lang['copy_project_01']}
		</h3>";
print  "<p>" . $lang['copy_project_02'] . " (\"<b>" . filter_tags(str_replace('<br>',' ',$app_title)) . "</b>\"), " . 
		$lang['copy_project_16'] . "<br>";
if ($superusers_only_create_project && !$super_user) { 
	print  "<p style='color:#800000;padding:5px 0;'>
				<img src='" . APP_PATH_IMAGES . "exclamation.png' class='imgfix'> 
				<b>{$lang['global_02']}:</b><br>
				{$lang['copy_project_05']} ($user_email) {$lang['copy_project_06']}
			</p>
		</p><br>
		<form name='createdb' action='".APP_PATH_WEBROOT."ProjectGeneral/notifications.php?pid=$project_id&type=request_copy' method='post'>";
} else {
	print  "
		</p><br>
		<form name='createdb' action='".APP_PATH_WEBROOT."ProjectGeneral/create_project.php' method='post'>";
}
// If normal user is requesting copy when it must be approved, include name and email as hidden fields here
$btn_text = "Copy Project";
if ($superusers_only_create_project && (!$super_user || (isset($_GET['username']) && $super_user))) {
	if (!$super_user) {
		$btn_text = "Request Copy";
		print  "<input type='hidden' name='user_email' value='$user_email'>
				<input type='hidden' name='username' value='$userid'>";
	} else {
		print  "<input type='hidden' name='user_email' value='{$_GET['user_email']}'>
				<input type='hidden' name='username' value='{$_GET['username']}'>";
	}
}


	
// Prepare a "certification" pop-up message when user clicks Create button if text has been set
$certify_text_js = "if (setFieldsCreateFormChk()) { showProgress(1); document.createdb.submit(); }";
if (trim($certify_text_create) != "" && (!$super_user || ($super_user && !isset($_GET['user_email'])))) 
{
	print "<div id='certify_create' title='".cleanHtml($lang['global_03'])."' style='display:none;text-align:left;'>".filter_tags(nl2br(html_entity_decode($certify_text_create, ENT_QUOTES)))."</div>";
	$certify_text_js = "if (setFieldsCreateFormChk()) {
							$('#certify_create').dialog({ bgiframe: true, modal: true, width: 500, buttons: {
								'".cleanHtml($lang['global_53'])."': function() { $(this).dialog('close'); }, 
								'".cleanHtml($lang['create_project_72'])."': function() {
									$(this).dialog('close');
									showProgress(1);
									document.createdb.submit();
								}
							} });
						}";
}
	
	
print  "<table style='width:100%;font-family:Arial;font-size:12px;' cellpadding=0 cellspacing=0>";
// Include the page with the form
include APP_PATH_DOCROOT . "ProjectGeneral/create_project_form.php";
// Note about projects with surveys
if ($surveys_enabled && !empty($Proj->surveys))
{
	print  "<tr valign='top'>
				<td style=''>
				</td>
				<td>
					<div class='yellow' style='font-family:tahoma;font-size:11px;'>
						<img src='".APP_PATH_IMAGES."exclamation_orange.png' class='imgfix'>
						<b>{$lang['global_03']}{$lang['colong']}</b><br>
						{$lang['copy_project_17']}<br><br>{$lang['copy_project_18']}";
	foreach ($Proj->surveys as $this_survey_id=>$survey_attr) {
		print  "<br> &bull; <b>{$survey_attr['title']}</b>";
	}
	print  "		</div>
				</td>
			</tr>";
}
// Custom copy settings
print  "<tr valign='top'>
			<td style='padding-top:25px;'>
				<b>{$lang['copy_project_07']}</b><br>
				<i>{$lang['global_06']}</i>
			</td>
			<td style='padding-top:25px;vertical-align:middle;'>
				<input type='checkbox' name='copy_records' id='copy_records'> {$lang['copy_project_14']}$num_records {$lang['copy_project_15']}<br>
				<input type='checkbox' name='copy_users' id='copy_users'> {$lang['copy_project_09']}<br>
				<input type='checkbox' name='copy_reports' id='copy_reports'> {$lang['copy_project_10']}
			</td>
		</tr>";
// Submit buttons
print  "<tr valign='top'>
			<td>
			</td>
			<td style='padding:15px 0;'>
				<input type='button' value=' $btn_text ' onclick=\"
					if ($('#currenttitle').val() == $('#app_title').val()) {
						simpleDialog('".cleanHtml($lang['copy_project_11'])."');
						return false;
					}
					$certify_text_js
				\">
				&nbsp;
				<input type='button' value=' ".cleanHtml($lang['global_53'])." ' onclick='history.go(-1)'>
			</td>
		</tr>";
		
print  "</table>";
// Hidden field to denote that we are copying a project
print  "<input type='hidden' name='copyof' value='$project_id'>";
// Hidden field to for checking against to prevent duplicate titles, which may cause confusion
print  "<input type='hidden' id='currenttitle' value='" . cleanHtml(html_entity_decode($app_title, ENT_QUOTES)) . "'>";
print  "</form>";
print  "</div>";

if (isset($_GET['username']) && $superusers_only_create_project && $super_user) 
{ 
	// If only Super Users can copy db and they are responding to request, pre-fill with request info
	print  "<script type='text/javascript'>
			$(function(){
				setTimeout(function(){
					$('#app_title').val('" . cleanHtml(html_entity_decode(filter_tags(html_entity_decode($_GET['app_title'], ENT_QUOTES)), ENT_QUOTES)) . "');
					$('#purpose').val({$_GET['purpose']});
					if ($('#purpose').val() == '1') {
						$('#purpose_other_span').css({'visibility':'visible'}); 
						$('#purpose_other_text').val('" . cleanHtml(html_entity_decode(filter_tags(html_entity_decode($_GET['purpose_other'], ENT_QUOTES)), ENT_QUOTES)) . "');
						$('#purpose_other_text').css('display','');
					}
					if ($('#purpose').val() == '2') {
						$('#purpose_other_span').css({'visibility':'visible'});
						$('#purpose_other_research').css('display','');
						$('#project_pi_irb_div').css('display','');
						$('#project_pi_firstname').val('" . cleanHtml(filter_tags(html_entity_decode($_GET['project_pi_firstname'], ENT_QUOTES))) . "');
						$('#project_pi_mi').val('" . cleanHtml(filter_tags(html_entity_decode($_GET['project_pi_mi'], ENT_QUOTES))) . "');
						$('#project_pi_lastname').val('" . cleanHtml(filter_tags(html_entity_decode($_GET['project_pi_lastname'], ENT_QUOTES))) . "');
						$('#project_pi_email').val('" . cleanHtml(filter_tags(html_entity_decode($_GET['project_pi_email'], ENT_QUOTES))) . "');
						$('#project_pi_alias').val('" . cleanHtml(filter_tags(html_entity_decode($_GET['project_pi_alias'], ENT_QUOTES))) . "');
						$('#project_pi_username').val('" . cleanHtml(filter_tags(html_entity_decode($_GET['project_pi_username'], ENT_QUOTES))) . "');
						$('#project_irb_number').val('" . cleanHtml(filter_tags(html_entity_decode($_GET['project_irb_number'], ENT_QUOTES))) . "');
						$('#project_grant_number').val('" . cleanHtml(filter_tags(html_entity_decode($_GET['project_grant_number'], ENT_QUOTES))) . "');
						var purposeOther = '{$_GET['purpose_other']}';
						var purposeArray = purposeOther.split(',');
						for (i = 0; i < purposeArray.length; i++) {
							if (document.getElementById('purpose_other['+purposeArray[i]+']') != null) {
								document.getElementById('purpose_other['+purposeArray[i]+']').checked = true;
							}
						}
					}
					$('#repeatforms_chk_div').css({'display':'block'});
					$('#datacollect_chk').prop('checked',true);
					$('#projecttype".($_GET['surveys_enabled'] == '1' ? '2' : ($_GET['surveys_enabled'] == '2' ? '0' : '1'))."').prop('checked',true);
					$('#repeatforms_chk".($_GET['repeatforms'] ? '2' : '1')."').prop('checked',true);
					if ({$_GET['scheduling']} == 1) $('#scheduling_chk').prop('checked',true);
					if ({$_GET['randomization']} == 1) $('#randomization_chk').prop('checked',true);
					setFieldsCreateForm();
					//Format table for this view
					$('#row_primary_use').css({'display':'none'});
					$('#row_projecttype_title').css({'display':'none'});
					$('#row_projecttype').css({'display':'none'});
					$('#row_purpose1').css({'padding':'0px'});
					$('#row_purpose2').css({'padding':'0px'});
					//Copy users/reports
					$('#copy_users').prop('checked'," . (($_GET['c_users'] == "on") ? "true" : "false") . ");
					$('#copy_reports').prop('checked'," . (($_GET['c_reports'] == "on") ? "true" : "false") . ");
					$('#copy_records').prop('checked'," . (($_GET['c_records'] == "on") ? "true" : "false") . ");
				},(isIE6 ? 1000 : 1));
			});
			</script>";	
} else {
	// Use javascript to pre-fill form with existing info
	print  "<script type='text/javascript'>
			$(function(){
			setTimeout(function(){
				$('#app_title').val('" . cleanHtml(filter_tags(html_entity_decode($app_title, ENT_QUOTES))) . "');
				$('#purpose').val($purpose);
				if ($('#purpose').val() == '1') {
					$('#purpose_other_span').css({'visibility':'visible'}); 
					$('#purpose_other_text').val('" . cleanHtml(filter_tags(html_entity_decode($purpose_other, ENT_QUOTES))) . "');
					$('#purpose_other_text').css('display','');
				}
				if ($('#purpose').val() == '2') {
					$('#purpose_other_span').css({'visibility':'visible'});
					$('#purpose_other_research').css('display','');
					$('#project_pi_irb_div').css('display','');
					$('#project_pi_firstname').val('" . cleanHtml(filter_tags($project_pi_firstname)) . "');
					$('#project_pi_mi').val('" . cleanHtml(filter_tags($project_pi_mi)) . "');
					$('#project_pi_lastname').val('" . cleanHtml(filter_tags($project_pi_lastname)) . "');
					$('#project_pi_email').val('" . cleanHtml(filter_tags($project_pi_email)) . "');
					$('#project_pi_alias').val('" . cleanHtml(filter_tags($project_pi_alias)) . "');
					$('#project_pi_username').val('" . cleanHtml(filter_tags(html_entity_decode($project_pi_username, ENT_QUOTES))) . "');
					$('#project_irb_number').val('" . cleanHtml(filter_tags(html_entity_decode($project_irb_number, ENT_QUOTES))) . "');
					$('#project_grant_number').val('" . cleanHtml(filter_tags(html_entity_decode($project_grant_number, ENT_QUOTES))) . "');
					var purposeOther = '$purpose_other';
					var purposeArray = purposeOther.split(',');
					for (i = 0; i < purposeArray.length; i++) {
						if (document.getElementById('purpose_other['+purposeArray[i]+']') != null) {
							document.getElementById('purpose_other['+purposeArray[i]+']').checked = true;
						}
					}
				}
				$('#repeatforms_chk_div').css({'display':'block'});
				$('#datacollect_chk').prop('checked',true);
				$('#projecttype".($surveys_enabled ? '2' : '1')."').prop('checked',true);
				$('#repeatforms_chk".($repeatforms ? '2' : '1')."').prop('checked',true);
				if ($scheduling == 1) $('#scheduling_chk').prop('checked',true);
				if ($randomization == 1) $('#randomization_chk').prop('checked',true);
				setFieldsCreateForm();
				//Format table for this view
				$('#row_primary_use').css({'display':'none'});
				$('#row_projecttype_title').css({'display':'none'});
				$('#row_projecttype').css({'display':'none'});
				$('#row_purpose1').css({'padding':'0px'});
				$('#row_purpose2').css({'padding':'0px'});
			},(isIE6 ? 1000 : 1));
			});
			</script>";
}

include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
