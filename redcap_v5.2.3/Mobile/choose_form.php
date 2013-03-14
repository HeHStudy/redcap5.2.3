<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . "/Config/init_project.php";
// Class for html page display system
require_once APP_PATH_CLASSES . 'HtmlPage.php';

// Skip this page if project is longitudinal (requires different workflow for selecting records)
if ($longitudinal) 
{
	redirect(APP_PATH_WEBROOT . "Mobile/choose_record.php?pid=$project_id");
	exit;
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
        <a class="back" style="max-width:85px;" href="javascript:;" onclick="window.location.href='<?php echo APP_PATH_WEBROOT ?>Mobile/';"><?php echo $lang['mobile_site_03'] ?></a>
		<h1>REDCap</h1>
	</div>
	<h1 style="font-size:14px;color:#fff;text-shadow:0 1px 1px #000000;"><?php echo $app_title ?></h1>
	<h1><?php echo $lang['global_36'] ?></h1>
	<ul class="rounded">
	
		<?php if ($is_child) { 
			$sql = "select form_menu_description, form_name from redcap_metadata where project_id = $project_id_parent and 
					form_menu_description is not null order by field_order";
			$q = db_query($sql);
			while ($row = db_fetch_assoc($q))
			{
				?>
				<li class="arrow"><a href="javascript:;" onclick="window.location.href='<?php echo APP_PATH_WEBROOT ?>Mobile/data_entry.php?pid=<?php echo $project_id_parent . "&page=" . $row['form_name'] . "&child=$app_name" ?>';"><?php echo filter_tags(label_decode($row['form_menu_description'])) ?></a></li>
				<?php
			}
		?>
	
		<?php } ?>
		
		<?php foreach ($Proj->forms as $form_name=>$attr) { ?>
			<?php if ($user_rights['forms'][$form_name] > 0) { ?>
			<li class="arrow"><a href="javascript:;" onclick="window.location.href='<?php echo APP_PATH_WEBROOT ?>Mobile/data_entry.php?pid=<?php echo $project_id . "&page=" . $form_name ?>';"><?php echo $attr['menu'] ?></a></li>
			<?php } ?>
		<?php } ?>
		
	</ul>
</div>

<?php

$objHtmlPage->PrintFooter();