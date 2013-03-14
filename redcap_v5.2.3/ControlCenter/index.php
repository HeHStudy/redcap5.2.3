<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

include 'header.php';

/**
 * MESSAGE TO DISPLAY AFTER SUBMITTING STATS MANUALLY
 * Give user message after returning from sending stats to consortium
 */
if (isset($_GET['sentstats']))
{
	//Stats could not be reported
	if ($_GET['sentstats'] == "fail") {
		$sentstats_alert = "ERROR: Your basic site statistics could not be reported to the consortium! If this is your first time "
						 . "reporting your stats, then your stats were likely sent, so please try reporting them again in 24 hours to see if "
						 . "it is then successful, as there is often a lag for first-time stats sending."
						 . "\\n\\nIf this problem persists, try using the alternative reporting method (see link on this page). If your stats still do not show "
						 . "up on the consortium website after several days, please contact Rob Taylor (rob.taylor@vanderbilt.edu).";
		print  "<script type='text/javascript'>$(function(){alert(\"$sentstats_alert\");});</script>";
	}
	//Stats were reported, so display alert msg with what was sent
	else {
		if (isset($_GET['saved'])) {
			// Redirect once saved in order to display the fact that stats were reported when we give the confirmation
			list ($num_prototypes2,$num_production2,$num_inactive2,$num_users2) = explode(",",$_GET['sentstats']);
			$sentstats_alert = "THANK YOU FOR YOUR PARTICIPATION!\\n\\nThe REDCap statistics for your institution "
								 . (isset($_GET['alternative']) ? "will be reported to the REDCap Consortium site within 24 hours." : "were successfully reported to the REDCap Consortium.");
			print  "<script type='text/javascript'>$(function(){alert(\"$sentstats_alert\");});</script>";
		} else {
			// Update date in table that stats were sent
			db_query("update redcap_config set value = '" . date("Y-m-d") . "' where field_name = 'auto_report_stats_last_sent'");
			// Now that we've saved today's date, redirect back to same page to give confirmation
			redirect($_SERVER['REQUEST_URI']."&saved=1");
		}
	}
}

/**
 * BUTTON FOR MANUALLY REPORTING STATS
 */
## Obtain system stats
// Get project count
$status_dev      = 0;
$status_prod     = 0;
$status_inactive = 0;
$status_archived = 0;
$q = db_query("select status, count(status) as count from redcap_projects where count_project = 1 and (purpose is null or purpose > 0) group by status");
while ($row = db_fetch_assoc($q)) {
	if ($row['status'] == '0') $status_dev = $row['count'];
	if ($row['status'] == '1') $status_prod = $row['count'];
	if ($row['status'] == '2') $status_inactive = $row['count'];
	if ($row['status'] == '3') $status_archived = $row['count'];
}
// Get user count
$total_users = db_result(db_query("select count(1) from redcap_user_information"), 0);
// Set up style for STATS reminder, if user has not reported stats in over a week
list($yyyy, $mm, $dd) = explode("-", $auto_report_stats_last_sent);
$days_diff = floor((mktime(0,0,0,date("m"),date("d"),date("Y")) - mktime(0,0,0,$mm,$dd,$yyyy) + 1) / 86400);
if ($days_diff >= 30) {
	$stats_last_style = "color:red;font-weight:bold;";
	$stats_last_img = "delete.png";
} elseif ($days_diff >= 7) {
	$stats_last_style = "color:#BC8900;font-weight:bold;";
	$stats_last_img = "exclamation_frame.png";
} else {
	$stats_last_style = "color:green;";
	$stats_last_img = "tick.png";
}
// Get counts of project purposes
$purpose_operational = 0;
$purpose_research = 0;
$purpose_qualimprove = 0;
$purpose_other = 0;
$q = db_query("select purpose, count(purpose) as count from redcap_projects where count_project = 1 and (purpose is null or purpose > 0) group by purpose");
while ($row = db_fetch_array($q))
{
	switch ($row['purpose'])
	{
		case '1': $purpose_other = $row['count']; break;
		case '2': $purpose_research = $row['count']; break;
		case '3': $purpose_qualimprove = $row['count']; break;
		case '4': $purpose_operational = $row['count']; break;
	}
}
// DTS: Get count of production projects utilizing DTS
$dts_count = 0;
if ($dts_enabled_global)
{
	$q = db_query("select count(1) from redcap_projects where status > 0 and count_project = 1 and (purpose is null or purpose > 0) and dts_enabled = 1");
	$dts_count = db_result($q, 0);
}

// Randomization: Get count of production projects using the randomization module (and have a prod alloc table uploaded)
$rand_count = Stats::randomizationCount();

// Set up display text
$auto_report_stats_last_sent_text = (empty($auto_report_stats_last_sent) || $auto_report_stats_last_sent == "2000-01-01") ? $lang['dashboard_54'] : format_date($auto_report_stats_last_sent);
$stats_method = ($auto_report_stats ? $lang['dashboard_55'] : $lang['dashboard_56']);
// Detect if SSL is on
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
	$port = ($_SERVER['SERVER_PORT'] != 443) ? ":".$_SERVER['SERVER_PORT'] : "";
	$hostssl = 1;
} else {
	$port = ($_SERVER['SERVER_PORT'] != 80)  ? ":".$_SERVER['SERVER_PORT'] : "";
	$hostssl = 0;
}
// Get server's IP address
$server_ip = getServerIP();
// Set alternative hostname if we know the domain name in the URL is internal (i.e. without dots)
$alt_hostname = (strpos(SERVER_NAME, ".") === false) ? SERVER_NAME : "";
// Send site stats to the REDCap Consortium
$url = CONSORTIUM_WEBSITE."collect_stats.php?hostname=".SERVER_NAME."$port"
	 . "&alt_hostname=$alt_hostname&ip=$server_ip&hostssl=$hostssl&num_prots=$status_dev&num_prods=$status_prod&num_archived=$status_archived&rnd982g4078393ae839z1"
	 . "&num_inactive=$status_inactive&num_users=$total_users&auth_meth=$auth_meth&version=$redcap_version&hostlabel=" . urlencode($institution)
	 . "&purposes=$purpose_other,$purpose_research,$purpose_qualimprove,$purpose_operational"
	 . "&activeusers1m=".getActiveUsers(30)."&activeusers6m=".getActiveUsers(183)."&activeuserstotal=".getActiveUsers()
	 . "&homepage_contact=".urlencode($homepage_contact)."&homepage_contact_email=$homepage_contact_email"
	 . "&full_url=".urlencode(APP_PATH_WEBROOT_FULL)."&site_org_type=".urlencode($site_org_type)
	 . "&dts=$dts_count&rand=$rand_count"
	 . "&hostrefer=" . APP_PATH_WEBROOT . ($shared_library_enabled ? "ControlCenter/report_site_stats.php" : "ControlCenter/index.php");
?>









<!-- NOTIFICATIONS AREA -->
<h3 style="margin-top: 0;"><?php echo $lang['control_center_116'] ?></h3>
<p>
	<?php echo $lang['control_center_118'] ?>
</p>
<?php

/**
 * CHECK REDCAP VERSION
 * If new version folder in already on web server, give link to upgrade
 */
if ($_SERVER['REQUEST_METHOD'] != 'POST') 
{
	?>
	<div id="version_check" class="green" style="display:none;margin:0 0 20px;padding:10px;"></div>
	<script type="text/javascript">$(function(){ version_check(); });</script>
	<?php
}

/**
 * CHECK IF USING "NONE" AUTHENTICATION & IF NOT, MAKE SURE SITE_ADMIN IS NOT A SUPER USER
 * Give user instructions on how to change their auth method
 */
if ($auth_meth_global == "none") 
{
	?>
	<div id="version_check" class="red" style="padding-bottom:15px;font-family:arial;">
		<img src="<?php echo APP_PATH_IMAGES ?>exclamation.png" class="imgfix">
		<b><?php echo $lang['control_center_174'] ?></b><br>
		<?php echo $lang['control_center_175'] ?><br><br>
		<a style="font-family:arial;" href="https://iwg.devguard.com/trac/redcap/wiki/ChangingAuthenticationMethod" target="_blank"><?php echo $lang['control_center_176'] ?></a>
	</div>
	<?php
}
// Make sure site_admin is not a super user
else
{
	$sql = "select 1 from redcap_user_information where username = 'site_admin' and super_user = 1";
	$q = db_query($sql);
	if (db_num_rows($q))
	{
		?>
		<div id="version_check" class="red" style="padding-bottom:15px;font-family:arial;">
			<img src="<?php echo APP_PATH_IMAGES ?>exclamation.png" class="imgfix">
			<b>"site_admin" <?php echo $lang['control_center_178'] ?></b><br>
			<?php echo $lang['control_center_179'] ?> "site_admin" <?php echo $lang['control_center_180'] ?>
		</div>
		<?php
	}
}

/** 
 * CHECK IF ALL DOCS FROM REDCAP_DOCS HAVE BEEN TRANSFERRED TO FILE SYSTEM
 * If some files remain (flag will be set), show link to navigate to page to transfer files
 */
if (!$doc_to_edoc_transfer_complete)
{
	$sql = "show table status like 'redcap_docs'";
	$q = db_query($sql);
	$table_size = 0;
	while ($row = db_fetch_assoc($q))
	{
		$table_size += $row['Data_length'];
	}
	$table_size = round($table_size/1024/1024);
	?>
	<div class="yellow" style="padding-bottom:15px;font-family:arial;">
		<img src="<?php echo APP_PATH_IMAGES ?>exclamation_orange.png" class="imgfix">
		<b><?php echo $lang['control_center_233'] ?></b><br>
		<?php echo $lang['control_center_234'] . " <b style='color:#800000;'>$table_size MB</b>" . $lang['period']; ?>
		<?php echo $lang['control_center_235'] ?><br><br>
		<a style="font-family:arial;" href="<?php echo APP_PATH_WEBROOT ?>ControlCenter/transfer_docs.php"><?php echo $lang['control_center_223'] ?></a>
	</div>
	<?php
}

/**
 * CHECK IF CRON JOBS ARE RUNNING
 */
if (!Cron::checkIfCronsActive()) {
	// Display error message
	print Cron::cronsNotRunningErrorMsg();
}

/**
 * CHECK IF REDCAP_BASE_URL IS SET PROPERLY
 */
if ($redcap_base_url == '' || ($redcap_base_url_display_error_on_mismatch && $redcap_base_url != APP_PATH_WEBROOT_FULL))
{
	print 	RCView::div(array('class'=>'red','style'=>'margin-top:5px;'),
				RCView::img(array('class'=>'imgfix','src'=>'exclamation.png')) .
				RCView::b($lang['global_48'].$lang['colon']) . RCView::br() . 
				$lang['control_center_361'] . "\"" . RCView::b($redcap_base_url) . "\"" . $lang['control_center_362'] . RCView::SP . 
				"\"" . RCView::b(APP_PATH_WEBROOT_FULL). "\"" . $lang['period'] . " " .
				$lang['control_center_371'] . RCView::br() . RCView::br() . 
				"'".$lang['pub_105']."' ".$lang['control_center_363'] . RCView::br() . RCView::br() . 	
				// Option 1
				($redcap_base_url == '' ? RCView::b($lang['control_center_369']) : RCView::b($lang['control_center_367'])) . RCView::br() . 
				$lang['setup_45'] . " " . RCView::a(array('href'=>APP_PATH_WEBROOT."ControlCenter/general_settings.php"), $lang['control_center_125']) . " " .
				$lang['control_center_364'] . " " .RCView::b(APP_PATH_WEBROOT_FULL). 
				// Option 2
				($redcap_base_url == '' ? '' :
					RCView::br() . RCView::br() . RCView::b($lang['control_center_368']) . RCView::br() . 
					$lang['control_center_365'] . " " . RCView::b($redcap_base_url) . $lang['period'] . " " . 
					$lang['control_center_370'] . RCView::br() . 
					RCView::button(array('onclick'=>"if (confirm('".cleanHtml($lang['control_center_372'])."')) { setConfigVal('redcap_base_url_display_error_on_mismatch','0',true); }"), $lang['control_center_366'])
				)
			);
} 

/**
 * CHECK IF PEAR DB DOES NOT HAVE AN INCOMPATIBLE VERSION
 */
$pearDBLink = "<a target='_blank' style='text-decoration:underline;' href='http://pear.php.net/package/DB/download'>Download PEAR DB here.</a>";
if (@ include 'DB.php') {
	if (version_compare(DB::apiVersion(), '1.7.14', '<')) {
		print RCView::div(array('class'=>'red','style'=>'margin-top:5px;'),
				"<img src='".APP_PATH_IMAGES."exclamation.png' class='imgfix'>
				<b>CRITICAL ISSUE: PEAR DB version < 1.7.14</b><br>Your version of PHP's PEAR DB package (" . DB::apiVersion() . ") may
				contain bugs. Please upgrade it. <a target='_blank' style='text-decoration:underline;' 
				href='http://pear.php.net/package/DB/download'>Download PEAR DB here.</a>"
			  );
	}
}
?>


















<!-- Reporting Your Stats section -->
<h3 style="margin-top:40px;"><?php echo $lang['control_center_119'] ?></h3>
<p>
	<?php echo $lang['control_center_385'] ?>
</p>

<div style="margin:10px 0;border:1px solid #ccc;background-color:#fafafa;padding:6px 15px;font-family:tahoma;font-size:11px;">
	<!-- Text saying if stats are up to date -->
	<div id="stats_last_submitted" style="font-family:tahoma;font-size:11px;<?php echo $stats_last_style ?>">
		<img src="<?php echo APP_PATH_IMAGES . $stats_last_img ?>" style="position:relative;top:3px;">
		<?php echo $lang['dashboard_52'] ?> <?php echo $auto_report_stats_last_sent_text ?>
	</div>
	<!-- Text saying if sending stats auto or manual -->
	<div style="padding:8px 0;">
		<?php echo $lang['dashboard_57'] ?> 
		<a href="<?php echo APP_PATH_WEBROOT ?>ControlCenter/general_settings.php#auto_report_stats-tr" style="font-family:tahoma;font-size:11px;font-weight:bold;"><?php echo $stats_method ?></a>
		&nbsp;&nbsp;
		<a href="javascript:;" style="padding-left:5px;font-size:10px;font-family:tahoma;text-decoration:underline;" onclick="simpleDialog('<?php echo cleanHtml($lang['dashboard_94']." ".$lang['dashboard_95']) ?>','<?php echo cleanHtml($lang['dashboard_77']) ?>');"><?php echo $lang['dashboard_77'] ?></a>
	</div>	
	<!-- Manual stats report button -->
	<div id="report_btn" style="padding:10px 0;">
		<input type="button" value="<?php echo $lang['dashboard_53'] ?>" onclick="window.location.href='<?php echo $url ?>'">
	</div>
	<!-- Link for alternative manual stats reporting -->
	<div style="padding:5px 0;">
		<div id="report_auto_msg" style="display:none;color:#aaa;font-size:10px;">
			<?php echo $lang['dashboard_58'] ?><br>
			<?php echo $lang['dashboard_59'] ?>
		</div>
		<a id="report_btn_alt_link" href="javascript:;" style="color:#999;text-align:right;font-family:tahoma;font-size:10px;text-decoration:underline;" onclick="
			$('#report_btn').hide();
			$('#report_btn_alt_link').hide();
			$('#report_btn_alt').show();
			$('#report_btn_alt').effect('highlight',{},2500);
		"><?php echo $lang['control_center_121'] ?></a>
		<div id="report_btn_alt" style="display:none;border:1px dashed #ccc;background-color:#fafafa;padding:7px 7px 10px;margin:10px 0;">
			<?php echo $lang['control_center_122'] ?><br><br>
			<form action="<?php echo APP_PATH_WEBROOT ?>ControlCenter/report_site_stats_alternative.php" method="post" name="report_form">
			<input name="url" type="hidden" value="<?php echo $url ?>">
			<input name="sentstats" type="hidden" value="<?php echo "$status_dev,$status_prod,$status_inactive,$total_users" ?>">
			<input type="button" value="Alternative Method: Report site stats" onclick="
				if ('<?php echo $auto_report_stats_last_sent ?>' == '<?php echo date("Y-m-d") ?>') {
					alert('Sorry, but you may only submit your stats once a day.');
				} else {
					document.report_form.submit();
				}
			">
			</form>
		</div>
	</div>
</div>

<?php
// Disable manual reporting button if already reporting automatically
if ($auto_report_stats)
{
	?>
	<script type="text/javascript">
	$(function(){
		$('#report_btn').fadeTo(0,0.5);
		$('#report_btn :input').prop("disabled",true);
		$('#report_btn_alt_link').hide();
		$('#report_auto_msg').show();
	});
	</script>
	<?php
}

include 'footer.php';