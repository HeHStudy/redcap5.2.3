<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

// Config for non-project pages
require_once dirname(dirname(__FILE__)) . '/Config/init_global.php';

// Class to render user's project list
require APP_PATH_DOCROOT . "Classes/RenderProjectList.php";


// This file can ONLY be accessed via the main index.php that sits above the version folders
if (PAGE == "home.php") {
	redirect(APP_PATH_WEBROOT_PARENT . "index.php?action=myprojects");
}


// Initialize page display object
$objHtmlPage = new HtmlPage();
$objHtmlPage->addExternalJS(APP_PATH_JS . "base.js");
$objHtmlPage->addStylesheet("smoothness/jquery-ui-".JQUERYUI_VERSION.".custom.css", 'screen,print');
$objHtmlPage->addStylesheet("style.css", 'screen,print');
$objHtmlPage->addStylesheet("home.css", 'screen,print');
$objHtmlPage->PrintHeader();


renderHomeHeaderLinks();


?>
<table border=0 align=center cellpadding=0 cellspacing=0 width=100%>
<tr valign=top><td colspan=2 align=center><img src="<?php echo APP_PATH_IMAGES ?>redcaplogo.gif"></td></tr>
<tr valign=top><td colspan=2 align=center>	
<?php

// TABS
include APP_PATH_DOCROOT . 'Home/tabs.php';


//If system is offline, give message to super users that system is currently offline
if ($system_offline && $super_user) 
{
	print  "<div class='red'>
				{$lang['home_01']}
				<a href='".APP_PATH_WEBROOT."ControlCenter/general_settings.php' 
					style='text-decoration:underline;font-family:verdana;'>{$lang['global_07']}</a>.
			</div>";
}


/**
 * CREATE NEW PROJECT
 * Give form to create new REDCap project, if user selected it
 */
if (isset($_GET['action']) && $_GET['action'] == 'create') 
{	
	print  "<div style='width:95%;border:1px solid #d0d0d0;padding:0px 15px 15px 15px;background-color:#f5f5f5;'>";
	
	print  "<h3 style='border-bottom: 1px solid #aaa; padding: 3px; font-weight: bold;'>{$lang['home_03']}</h3>";
	print  "<p>{$lang['home_04']} ";	
	// If only super users are allowed to create new projects, then normal users will have email request sent to contact person for approval
	if ($superusers_only_create_project && !$super_user) {	
		print  " {$lang['home_05']}<br><br></p>";
		print  "<form name='createdb' action='".APP_PATH_WEBROOT."ProjectGeneral/notifications.php?type=request_new' method='post'>";
		$btn_text = "Send Request";
	} else {	
		print  "<br><br></p>";
		print  "<form name='createdb' action='".APP_PATH_WEBROOT."ProjectGeneral/create_project.php' method='post'>";
		$btn_text = "Create Project";
	}
	
	// Prepare a "certification" pop-up message when user clicks Create button if text has been set
	$certify_text_js = "if (setFieldsCreateFormChk()) { document.createdb.submit(); }";
	if (trim($certify_text_create) != "" && (!$super_user || ($super_user && !isset($_GET['user_email'])))) 
	{
		print "<div id='certify_create' class='notranslate' title='Notice' style='display:none;text-align:left;'>".nl2br(html_entity_decode($certify_text_create, ENT_QUOTES))."</div>";
		$certify_text_js = "if (setFieldsCreateFormChk()) {
								$('#certify_create').dialog({ bgiframe: true, modal: true, width: 500, buttons: {
									'".cleanHtml($lang['global_53'])."': function() { $(this).dialog('close'); }, 
									'".cleanHtml($lang['create_project_72'])."': function() {
										$(this).dialog('close');
										document.createdb.submit();
									}
								} });
							}";
	}
	
	//FORM
	print  "<table style='width:100%;font-family:Arial;font-size:12px;' cellpadding=0 cellspacing=0>";
	
	// Include the page with the form
	include APP_PATH_DOCROOT . "ProjectGeneral/create_project_form.php";
	
	// Output table row for option to start from scratch or choose a project template
	print 	RCView::tr(array('valign'=>'top'),
				RCView::td(array('style'=>'padding-top:18px;padding-right:10px;font-weight:bold;'),
					$lang['create_project_75'] . RCView::br() . $lang['create_project_76']
				) .
				RCView::td(array('style'=>'padding-top:15px;'),
					RCView::div(array('style'=>''),
						RCView::radio(array('name'=>'project_template_radio','value'=>'0','checked'=>'checked')) . 
						$lang['create_project_67']
					) .
					RCView::div(array('style'=>''),
						RCView::radio(array('name'=>'project_template_radio','value'=>'1')) . 
						$lang['create_project_68']
					)
				)
			);
	// Display table of project templates
	print 	RCView::tr(array('valign'=>'top'),
				RCView::td(array('colspan'=>'2','style'=>'padding-top:20px;padding-bottom:10px;'),
					ProjectTemplates::buildTemplateTable()
				)
			);
	
	// "Create Project"/Cancel buttons
	print  "<tr valign='top'>
				<td></td>
				<td style='padding:15px 0 15px 0;'>
					<input type='button' value=' $btn_text ' onclick=\"$certify_text_js\">
					&nbsp;
					<input type='button' value=' Cancel ' onclick=\"window.location.href='{$_SERVER['PHP_SELF']}'\">
				</td>
			</tr>";
	
	// End of table
	print  "</table>";
	
	// If Super User is filling out for normal user request, use javascript to pre-fill form with existing info
	if (isset($_GET['type']) && $superusers_only_create_project && $super_user) 
	{
		print  "<input type='hidden' name='user_email' value='{$_GET['user_email']}'>
				<input type='hidden' name='username' value='{$_GET['username']}'>
				<script type='text/javascript'>
				$(function(){
				setTimeout(function(){
					$('#app_title').val('" . cleanHtml(html_entity_decode(html_entity_decode($_GET['app_title'], ENT_QUOTES), ENT_QUOTES)) . "');
					$('#purpose').val('{$_GET['purpose']}');
					if ($('#purpose').val() == '1') {
						$('#purpose_other_span').css({'visibility':'visible'}); 
						$('#purpose_other_text').val('" . cleanHtml(html_entity_decode(html_entity_decode($_GET['purpose_other'], ENT_QUOTES), ENT_QUOTES)) . "');
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
							document.getElementById('purpose_other['+purposeArray[i]+']').checked = true;
						}
					}
					$('#repeatforms_chk_div').css({'display':'block'});
					$('#datacollect_chk').prop('checked',true);
					$('#projecttype".($_GET['surveys_enabled'] == '1' ? '2' : ($_GET['surveys_enabled'] == '2' ? '0' : '1'))."').prop('checked',true);
					$('#repeatforms_chk".($_GET['repeatforms'] ? '2' : '1')."').prop('checked',true);
					if ({$_GET['scheduling']} == 1) $('#scheduling_chk').prop('checked',true);
					if ({$_GET['randomization']} == 1) $('#randomization_chk').prop('checked',true);
					setFieldsCreateForm();
					// If template was selected, select it
					if (isNumeric('{$_GET['template']}')) {
						$('#template_projects_list').fadeTo(0,1);
						$('#template_projects_list button, #template_projects_list input').prop('disabled',false);
						$('input[name=\"project_template_radio\"][value=\"1\"]').attr('checked',true);
						$('input[name=\"copyof\"][value=\"{$_GET['template']}\"]').attr('checked',true);
					}
				},(isIE6 ? 1000 : 10));
				});
				</script>";
	}
	
	//Finish bigger div
	print  "</form>";
	print "</div>";	
	
	## Hide step 1 and 2 (legacy options from version 4.X)
	?>
	<script type="text/javascript">
	$(function(){
		// Select data entry forms project type option
		$('#projecttype1').prop('checked',true);
		// Select classic project option
		$('#repeatforms_chk1').prop('checked',true);
		// Run function to set all values in place
		setFieldsCreateForm();
		
		// Disable the template list
		$('#template_projects_list').fadeTo(0,0.4);
		$('#template_projects_list button, #template_projects_list input').prop('disabled',true);
		// If choose to use a template, then enable the tempate drop-down
		$('input[name="project_template_radio"]').change(function(){
			if ($('input[name="project_template_radio"]:checked').val() == '1') {
				// Enable drop-down and description box
				$('#template_projects_list button, #template_projects_list input').prop('disabled',false);
				$('#template_projects_list').fadeTo('fast',1);
			} else {
				// Disable the drop-down and reset its value
				$('input[name="copyof"]').prop('checked',false);
				$('#template_projects_list button, #template_projects_list input').prop('disabled',true);
				$('#template_projects_list').fadeTo('fast',0.4);
			}
		});
		// Template table: If click row, have it select the radio
		$('#table-template_projects_list tr').click(function(){
			if (!$('input[name="project_template_radio"]').length || ($('input[name="project_template_radio"]').length && $('input[name="project_template_radio"]:checked').val() == '1')) {
				$(this).find('input[name="copyof"]').prop('checked',true);
			}
		});
	});
	</script>
	<?php
}




/**
 * MY PROJECTS LIST
 */
elseif (isset($_GET['action']) && $_GET['action'] == 'myprojects') 
{
	print  "	</td>
			</tr>
			<tr valign='top'>
				<td>
					<p style='margin-top:0;padding-bottom:5px;'>
						{$lang['home_06']} 
						<img src='".APP_PATH_IMAGES."page_white_edit.png' style='vertical-align:middle;'> 
						{$lang['home_07']} 
						<img src='".APP_PATH_IMAGES."accept.png' style='vertical-align:middle;'> 
						{$lang['home_08']} 
						<img src='".APP_PATH_IMAGES."delete.png' style='vertical-align:middle;'>{$lang['home_09']}
						{$lang['home_40']}
						<img src='".APP_PATH_IMAGES."send.png' style='vertical-align:middle;'>{$lang['home_41']}
						<img src='".APP_PATH_IMAGES."blog.png' style='vertical-align:middle;'>{$lang['home_42']} 
						<img src='".APP_PATH_IMAGES."blog.png' style='vertical-align:middle;'> <img src='".APP_PATH_IMAGES."send.png' style='vertical-align:middle;'>{$lang['period']}
						".(SUPER_USER ? " {$lang['home_44']} <img src='".APP_PATH_IMAGES."star_small2.png' style='vertical-align:middle;'>{$lang['period']}" : "")."
					</p>";
	
	$projects = new RenderProjectList ();
	$projects->renderprojects();
	
	// Check if user has any Archived projects. If so, show link to display them, if desired.
	print  "		<p style='padding-top:30px;'>&nbsp;";	
	$sql = "select count(1) from redcap_user_rights u, redcap_projects p where u.project_id = p.project_id and 
			u.username = '$userid' and p.status = 3";
	$num_archived = db_result(db_query($sql), 0);
	if ($num_archived > 0) {
		print  "		<img src='".APP_PATH_IMAGES."bin_closed.png' class='imgfix'> ";
		if (!isset($_GET['show_archived'])) {
			print  "	<a style='font-size:11px;color:#666;' href='index.php?action=myprojects&show_archived'>{$lang['home_10']}</a>";
		} else {
			print  "	<a style='font-size:11px;color:#666;' href='index.php?action=myprojects'>{$lang['home_11']}</a>";
		}		
	}
	print  "		</p>";
}


	
/**
 * GIVE USER CONFIRMATION IF REQUESTED NEW PROJECT
 */
elseif (isset($_GET['action']) && $_GET['action'] == 'requested_new' && $superusers_only_create_project) 
{
	//print  "<br><div style='width:95%;border:1px solid #d0d0d0;padding:0px 15px 15px 15px;background-color:#f5f5f5;'>";	
	print  "<h3 style='padding:3px; font-weight: bold;'>{$lang['home_12']}</h3>";
	print  "<p style='padding-bottom:50px;'>
				{$lang['home_13']} {$lang['home_14']} (<a href='mailto:$user_email' style='text-decoration:underline;'>$user_email</a>) 
				{$lang['home_15']} 
			</p>";
}


	
/**
 * GIVE USER CONFIRMATION IF REQUESTED TO COPY PROJECT
 */
elseif (isset($_GET['action']) && $_GET['action'] == 'requested_copy' && $superusers_only_create_project) 
{
	//print  "<br><div style='width:95%;border:1px solid #d0d0d0;padding:0px 15px 15px 15px;background-color:#f5f5f5;'>";	
	print  "<h3 style='padding:3px; font-weight: bold;'>{$lang['home_12']}</h3>";
	print  "<p style='padding-bottom:50px;'>
				{$lang['home_16']} 
				<b>" . strip_tags(html_entity_decode($_GET['app_title'], ENT_QUOTES)) . "</b>.
				{$lang['home_14']} (<a href='mailto:$user_email' style='text-decoration:underline;'>$user_email</a>) 
				{$lang['home_15']} 
			</p>";	
}


	
/**
 * GIVE SUPER USER CONFIRMATION WHEN APPROVING NEW PROJECT
 */
elseif (isset($_GET['action']) && $_GET['action'] == 'approved_new' && $superusers_only_create_project & $super_user) 
{
	print  "<h3 style='padding:3px; font-weight: bold;'>{$lang['home_17']}</h3>";
	print  "<p style='padding-bottom:50px;'>
				{$lang['home_18']} (<a href='mailto:{$_GET['user_email']}' style='text-decoration:underline;'>{$_GET['user_email']}</a>). 
			</p>";	
}


	
/**
 * GIVE SUPER USER CONFIRMATION WHEN COPYING PROJECT
 */
elseif (isset($_GET['action']) && $_GET['action'] == 'approved_copy' && $superusers_only_create_project & $super_user) 
{	
	print  "<h3 style='padding:3px; font-weight: bold;'>{$lang['home_17']}</h3>";
	print  "<p style='padding-bottom:50px;'>
				{$lang['home_19']} (<a href='mailto:{$_GET['user_email']}' style='text-decoration:underline;'>{$_GET['user_email']}</a>). 
			</p>";
}


	
/**
 * GIVE SUPER USER CONFIRMATION WHEN MOVING PROJECT TO PRODUCTION (USER REQUESTED)
 */
elseif (isset($_GET['action']) && $_GET['action'] == 'approved_movetoprod' && $superusers_only_move_to_prod & $super_user) 
{
	print  "<h3 style='padding:3px; font-weight: bold;'>{$lang['home_17']}</h3>";
	print  "<p style='padding-bottom:50px;'>
				{$lang['home_43']} (<a href='mailto:{$_GET['user_email']}' style='text-decoration:underline;'>{$_GET['user_email']}</a>). 
			</p>";
}


	
/**
 * TRAINING RESOURCES (VIDEOS, ETC.)
 */
elseif (isset($_GET['action']) && $_GET['action'] == 'training') 
{
	include APP_PATH_DOCROOT . "Home/training_resources.php";
}


	
/**
 * HELP & FAQ
 */
elseif (isset($_GET['action']) && $_GET['action'] == 'help') 
{
	include APP_PATH_DOCROOT . "Help/index.php";
}




/**
 * HOME PAGE WITH GENERAL INFO
 */
else 
{
	include APP_PATH_DOCROOT . "Home/info.php";
}


print "</td></tr></table><br><br>";

// Check if need to report institutional stats to REDCap consortium 
checkReportStats();

$objHtmlPage->PrintFooter();
