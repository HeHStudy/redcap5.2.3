<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

// Config for non-project pages
require_once dirname(dirname(__FILE__)) . "/Config/init_global.php";

// Check if the URL is pointing to the correct version of REDCap specified for this project. If not, redirect to correct version.
check_version();

//If user is not a super user, go back to Home page
if (!$super_user) redirect(APP_PATH_WEBROOT);

// Initialize page display object
$objHtmlPage = new HtmlPage();
$objHtmlPage->addExternalJS(APP_PATH_JS . "base.js");
$objHtmlPage->addExternalJS(APP_PATH_JS . "yui_charts.js");
$objHtmlPage->addExternalJS(APP_PATH_JS . "underscore-min.js");
$objHtmlPage->addExternalJS(APP_PATH_JS . "backbone-min.js");
$objHtmlPage->addExternalJS(APP_PATH_JS . "RedCapUtil.js");
$objHtmlPage->addStylesheet("smoothness/jquery-ui-".JQUERYUI_VERSION.".custom.css", 'screen,print');
$objHtmlPage->addStylesheet("style.css", 'screen,print');
$objHtmlPage->addStylesheet("home.css", 'screen,print');
$objHtmlPage->PrintHeader();

/**
 * IE CSS Hack - Render the following CSS if using IE
 */
if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false)) {
	print '<style type="text/css">input[type="radio"], input[type="checkbox"] {margin: 0}</style>';
}

// Set constant to not display any blank options for drop-downs on this page
define('DROPDOWN_DISABLE_BLANK', true);
?>

<style type='text/css'>
.cc_label {
	padding: 10px; font-weight: bold; vertical-align: top; line-height: 16px; width: 40%;
}
.cc_data {
	padding: 10px; width: 60%; vertical-align: top;
}
.label, .data {
	background:#F0F0F0 url('<?php echo APP_PATH_IMAGES ?>label-bg.gif') repeat-x scroll 0 0;
	border:1px solid #CCCCCC;
	font-size:12px;
	font-weight:bold;
	font-family:arial;
	padding:5px 10px;
}
.label a:link, .label a:visited, .label a:active, .label a:hover { font-size:12px; font-family: Arial; }
.notesbox {
	width: 380px;
}
.form_border { width: 100%;	}
#sub-nav { font-size:60%; }
form#form .imgfix { top:-2px; vertical-align:middle; }
</style>

<?php renderHomeHeaderLinks() ?>

<table cellspacing=0 width=100%">
<tr valign=top>
	<td>
		<img src="<?php echo APP_PATH_IMAGES ?>redcaplogo.gif">
	</td>
	<td valign="bottom">
		<div style="text-align:right;color:#800000;font-size:34px;font-weight:bold;font-family:verdana;"><?php echo $lang['global_07'] ?></div>
		<!-- Hide the Control Center video until a more recent one is recorded
		<div style="text-align:right;">
			<img src="<?php echo APP_PATH_IMAGES ?>video_small.png" class="imgfix">
			<a onclick="popupvid('redcap_control_center01.flv','The REDCap Control Center')" href="javascript:;" style="font-size:11px;text-decoration:underline;font-weight:normal;"
			><?php echo $lang['control_center_103'] ?></a>
		</div>
		-->
	</td>
</tr>
<tr valign=top>
	<td colspan=2>
		<?php include APP_PATH_DOCROOT . 'Home/tabs.php'; ?>

		<table cellspacing=0 width=100% style="table-layout: fixed;">
		<tr>
			<td valign="top" width="170">
				<div style="border:1px solid #ddd;background-color:#fafafa;color:#000;padding:5px;">
					<!-- Control Center Home -->
					<b style="position:relative;"><?php echo $lang['control_center_129'] ?></b><br/>
					<span style="position: relative; float: left; left: 14px;">
						&bull; <a href="index.php"><?php echo $lang['control_center_117'] ?></a><br/>
					</span>
					<div style="clear: both;padding-top:6px;"></div>
					<!-- Dashboard -->
					<b style="position:relative;"><?php echo $lang['control_center_03'] ?></b><br/>
						<span style="position: relative; float: left; left: 14px;">
							&bull; <a href="system_stats.php"><?php echo $lang['dashboard_48'] ?></a><br/>
							&bull; <a href="todays_activity.php"><?php echo $lang['control_center_206'] ?></a><br/>
							&bull; <a href="graphs.php"><?php echo $lang['control_center_130'] ?></a><br/>
							&bull; <a href="google_map_users.php"><?php echo $lang['control_center_386'] ?></a><br/>
						</span>
					<div style="clear: both;padding-top:6px;"></div>
					<!-- System Configuration -->
					<b style="position:relative;"><?php echo $lang['control_center_131'] ?></b><br/>
						<span style="position: relative; float: left; left: 14px;">
							&bull; <a href="general_settings.php"><?php echo $lang['control_center_125'] ?></a><br/>
							&bull; <a href="security_settings.php"><?php echo $lang['control_center_113'] ?></a><br/>
							&bull; <a href="user_settings.php"><?php echo $lang['control_center_315'] ?></a><br/>
							&bull; <a href="file_upload_settings.php"><?php echo $lang['system_config_214'] ?></a><br/>
							&bull; <a href="modules_settings.php"><?php echo $lang['control_center_115'] ?></a><br/>
							&bull; <a href="validation_type_setup.php"><?php echo $lang['control_center_150'] ?></a><br/>
							&bull; <a href="project_templates.php"><?php echo $lang['create_project_79'] ?></a><br/>
							&bull; <a href="project_settings.php"><?php echo $lang['control_center_136'] ?></a><br/>
							&bull; <a href="homepage_settings.php"><?php echo $lang['control_center_124'] ?></a><br/>
							&bull; <a href="footer_settings.php"><?php echo $lang['control_center_137'] ?></a><br/>
							&bull; <a href="external_links_global.php"><?php echo $lang['extres_55'] ?></a><br/>
							&bull; <a href="pub_matching_settings.php"><?php echo $lang['control_center_210'] ?></a><br/>
							&bull; <a href="cron_jobs.php"><?php echo $lang['control_center_287'] ?></a><br/>
						</span>
					<div style="clear: both;padding-top:6px;"></div>
					<!-- Projects -->
					<b style="position:relative;"><?php echo $lang['control_center_134'] ?></b><br/>
						<span style="position: relative; float: left; left: 14px;">
							&bull; <a href="view_projects.php"><?php echo $lang['control_center_110'] ?></a><br/>
							&bull; <a href="edit_project.php"><?php echo $lang['control_center_128'] ?></a><br/>
						</span>
					<div style="clear: both;padding-top:6px;"></div>
					<!-- Users -->
					<b style="position:relative;"><?php echo $lang['control_center_132'] ?></b><br/>
						<span style="position: relative; float: left; left: 14px;">
							&bull; <a href="view_users.php"><?php echo $lang['control_center_109'] ?></a><br/>
							&bull; <a href="create_user.php"><?php echo $lang['control_center_133'] ?><br><span style='margin-left:8px;'><?php echo $lang['control_center_408'] ?></span></a><br/>
							&bull; <a href="user_white_list.php"><?php echo $lang['control_center_162'] ?></a><br/>
							&bull; <a href="superusers.php"><?php echo $lang['control_center_35'] ?></a><br/>
							&bull; <a href="email_users.php"><?php echo $lang['email_users_02'] ?></a><br/>
							&bull; <a href="user_api_tokens.php"><?php echo $lang['control_center_245'] ?></a><br/>
						</span>
					<div style="clear: both;padding-top:6px;"></div>
				</div>
			</td>
			<td valign="top" style="padding-left:20px;">
