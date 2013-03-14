<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


// Config
require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

// Header
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

// Tabs
require_once APP_PATH_DOCROOT . "ProjectSetup/tabs.php";

// Page title
renderPageTitle("<img src='".APP_PATH_IMAGES."chain_arrow.png' class='imgfix2'> " . $lang['setup_78']);

// Make sure mcrypt extension is loaded, otherwise cannot use Adv Link functionality
mcrypt_loaded(true);

?>

<!-- Page instructions -->
<p style="margin-top:20px;"><?php echo $lang['extres_16'] ?></p>
<p>
	<?php echo $lang['extres_17'] ?>
	<a href="javascript:;" onclick="$('#input_Resourcename_id_0').focus();highlightResourceRow(0);" style="text-decoration:underline;color:green;"><?php echo $lang['extres_69'] ?></a>
	<?php echo $lang['extres_19'] ?>
	<?php if ($api_enabled) { ?>
		<?php echo $lang['extres_51'] ?>
		(<a href="javascript:;" id="adv_link_info_trigger" style="text-decoration:underline;"><?php echo $lang['extres_20'] ?></a>)
	<?php } ?>
</p>

<!-- Advanced Link information (hidden div) -->
<div id="adv_link_info" class="p" style="display:none;" title="<?php echo cleanHtml2($lang['extres_21']) ?>">
	<b><?php echo $lang['extres_05'] ?></b><br />
	<?php echo $lang['extres_22'] ?><br /><br />
	<b><?php echo $lang['extres_40'] ?></b><br />
	<?php echo $lang['extres_41'] ?><br /><br />
	<b><?php echo $lang['extres_42'] ?></b><br />
	<div class="hang">1) <?php echo $lang['extres_44'] ?></div>
	<div class="hang">2) <?php echo $lang['extres_43'] ?> <b><?php echo APP_PATH_WEBROOT_FULL . "api/" ?></b><?php echo $lang['period'] ?> <?php echo $lang['extres_50'] ?></div>
	<div class="hang">3) <?php echo $lang['extres_45'] ?></div>
	<div class="hang">4) <?php echo $lang['extres_46'] ?></div><br />
	<div><b><?php echo $lang['extres_47'] ?></b></div>
	<div class="hang">&nbsp; &bull; <?php echo $lang['extres_48'] . " " . $lang['extres_50'] ?></div>
	<div class="hang">&nbsp; &bull; <?php echo $lang['extres_49'] ?></div>
</div>

<!-- Render the resources table -->
<div id="table-resources-parent" style="margin-top:20px;"><?php echo $ExtRes->displayResourcesTable() ?></div>

<!-- Hidden dialog for choosing a REDCap project to link to -->
<div id="choose_project_div" style="display:none;" title="Choose project to link"><?php echo $ExtRes->displayProjectListDialog() ?></div>

<!-- Hidden dialog for prompting user to confirm if want to append record info to URL -->
<div id="append_rec_warning" style="display:none;" title="<?php echo cleanHtml2($lang['extres_01']) ?>">
	<p><?php echo "<b>{$lang['global_48']}{$lang['colon']}</b><br />{$lang['extres_02']}" ?></p>
</div>

<!-- Hidden dialog for displaying "append record info" explanation -->
<div id="append_rec_info" style="display:none;" title="<?php echo cleanHtml2($lang['extres_01']) ?>">
	<p>
		<b><?php echo $lang['extres_05'] ?></b><br />
		<?php echo $lang['extres_70'] ?><br /><br />
		<b><?php echo $lang['extres_07'] ?></b><br />
		<?php echo $lang['extres_08'] ?> 
		<i style="color:#555;font-family:verdana;">record=RECORDNAME&event=UNIQUEEVENTNAME</i>
		<?php echo $lang['extres_09'] ?><br /><br />
		<b><?php echo $lang['extres_10'] ?></b><br />
		1.) <?php echo $lang['extres_11'] ?> http://www.mysite.com/mypage/ <?php echo $lang['extres_12'] ?> "CAF1023" <?php echo $lang['extres_14'] ?><br />
		<i style="color:#555;font-family:verdana;">http://www.mysite.com/mypage/?record=CAF1023</i><br />
		2.) <?php echo $lang['extres_11'] ?> http://www.mysite.com/mypage.php?otherparameter=42 <?php echo $lang['extres_12'] ?> "CAF1023" 
		<?php echo $lang['extres_13'] ?> "event1_arm1" <?php echo $lang['extres_15'] ?><br />
		<i style="color:#555;font-family:verdana;">http://www.mysite.com/mypage.php?otherparameter=42&record=CAF1023&event=event1_arm1</i>
	</p>
</div>

<!-- Hidden dialog for displaying "append pid" explanation -->
<div id="append_pid_info" style="display:none;" title="<?php echo cleanHtml2($lang['extres_65']) ?>">
	<p>
		<?php echo $lang['extres_66'] ?>
		<i style="color:#555;font-family:verdana;">pid=PROJECTID</i><br /><br />
		<?php echo $lang['extres_67'] ?> http://www.mysite.com/mypage/<?php echo $lang['extres_68'] ?><br />
		<i style="color:#555;font-family:verdana;">http://www.mysite.com/mypage/?pid=749</i>
	</p>
</div>

<!-- Hidden div containing USER LIST for choosing selected users who can access a resource -->
<div id="choose_user_div">
	<div id="choose_user_div_sub">
		<div style="color:#800000;width:280px;min-width:280px;font-weight:bold;font-size:13px;padding:6px 3px 5px;margin-bottom:3px;border-bottom:1px solid #ccc;">
			<?php echo $lang['extres_23'] ?>
		</div>
		<div id="choose_user_div_loading" style="padding:8px 3px;color:#555;">
			<img src="<?php echo APP_PATH_IMAGES ?>progress_circle.gif" class="imgfix">&nbsp;
			<?php echo $lang['data_entry_64'] ?>
		</div>
		<div id="choose_user_div_list" style="display:none;"></div>
		<input type="hidden" value="" id="user_current_resource_id">
	</div>
</div>

<!-- Hidden div containing DAG LIST for choosing selected DAGs who can access a resource -->
<div id="choose_dag_div">
	<div id="choose_dag_div_sub">
		<div style="color:#800000;width:280px;min-width:280px;font-weight:bold;font-size:13px;padding:6px 3px 5px;margin-bottom:3px;border-bottom:1px solid #ccc;">
			<?php echo $lang['extres_53'] ?>
		</div>
		<div id="choose_dag_div_loading" style="padding:8px 3px;color:#555;">
			<img src="<?php echo APP_PATH_IMAGES ?>progress_circle.gif" class="imgfix">&nbsp;
			<?php echo $lang['data_entry_64'] ?>
		</div>
		<div id="choose_dag_div_list" style="display:none;"><?php $ExtRes->displayDagList() ?></div>
		<input type="hidden" value="" id="dag_current_resource_id">
	</div>
</div>

<!-- CSS -->
<style type="text/css">
.hiddenbox { display:none;padding:8px;background-color:#f5f5f5;border:1px solid #ddd; }
.edit_active { background: #fafafa url(<?php echo APP_PATH_IMAGES ?>pencil.png) no-repeat right; }
.edit_saved { background: #C1FFC1 url(<?php echo APP_PATH_IMAGES ?>tick.png) no-repeat right; }
.editname, .editurl, .newname, .newurl { line-height: 12px; vertical-align:middle; }
.editurl, .newproject { width:98%; min-height:22px;word-wrap:break-word; white-space:normal; }
.editname { width:95%; min-height:22px;word-wrap:normal; white-space:normal; font-size:12px;line-height:14px; }
.resourcenum { font-size:12px;height: 22px;line-height:22px; }
.newname, .newurl { font-size:10px; font-family:tahoma; color:#555; width:98%; word-wrap:break-word; white-space:normal;}
.hidden_save_div { visibility:hidden;color:red;font-size:10px;line-height:8px;padding:0;font-weight:bold; }
.ww { width:98%; word-wrap:break-word; white-space:normal; }
#choose_user_div, #choose_dag_div {
	min-width:280px;
	background: transparent url(<?php echo APP_PATH_IMAGES ?>upArrow.png) no-repeat center top;
	position:absolute;
	padding:9px 0 0;
	display: none;
	font-size:11px;
}
#choose_user_div_sub, #choose_dag_div_sub {
	background-color: #fafafa;
	padding:3px 6px 10px;
	border:1px solid #000;
}
</style>

<!-- Javascript -->
<script type="text/javascript" src="<?php echo APP_PATH_JS ?>jquery_tablednd.js"></script>
<script type="text/javascript" src="<?php echo APP_PATH_JS ?>ExternalLinks.js"></script>

<?php

// Footer
require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';