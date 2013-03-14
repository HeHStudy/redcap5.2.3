<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . "/Config/init_global.php";

// Get current user's projects
$sql = "select p.project_id, p.app_title, p.project_name from redcap_user_rights u, redcap_projects p 
		where u.project_id = p.project_id and u.username = '" . USERID . "' and p.status != 3 
		and date_deleted is null order by p.project_id";
$q = db_query($sql);
while ($row = db_fetch_array($q)) 
{		
	$proj[$row['project_name']]['project_id'] = $row['project_id'];
	$proj[$row['project_name']]['app_title'] = strip_tags(str_replace(array("<br>","<br/>","<br />"), array(" "," "," "), html_entity_decode($row['app_title'], ENT_QUOTES)));				
}


// Initialize page display object
$objHtmlPage = new HtmlPage();
$objHtmlPage->addStylesheet("jqtouch.min.css", 'screen,print');
$objHtmlPage->addStylesheet("jqtouch_themes/apple/theme.min.css", 'screen,print');
$objHtmlPage->addExternalJS(APP_PATH_JS . "base.js");
$objHtmlPage->addExternalJS(APP_PATH_JS . "jqtouch.min.js");
$objHtmlPage->PrintHeader();

?>

<style type="text/css">
#footer { display: none; }
</style>

<script type="text/javascript">
var jQT = new $.jQTouch();
</script>

<div id="home" class="current">
	<div class="toolbar">
		<?php if ($auth_meth != "none") { ?>
			<a class="button leftButton" href="javascript:;" onclick="window.location.href='<?php echo APP_PATH_WEBROOT ?>Mobile/?logout=1';"><?php echo $lang['bottom_02'] ?></a>
		<?php } ?>
		<h1>REDCap</h1>
		<a class="button rightButton" href="javascript:;" onclick="window.location.href='<?php echo APP_PATH_WEBROOT_PARENT ?>index.php?action=myprojects';"><?php echo $lang['mobile_site_01'] ?></a>
	</div>
	<h2><?php echo $lang['bottom_03'] ?></h2>
	<ul class="rounded">
		<?php foreach ($proj as $app_name=>$attr) { ?>
		<li class="arrow"><a href="javascript:;" onclick="window.location.href='<?php echo APP_PATH_WEBROOT ?>Mobile/choose_form.php?pid=<?php echo $attr['project_id'] ?>';"><?php echo $attr['app_title'] ?></a></li>
		<?php } ?>
	</ul>
</div>

<?php

$objHtmlPage->PrintFooter();