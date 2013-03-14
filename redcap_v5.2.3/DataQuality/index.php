<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


// Config
require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

// Do user rights check (normally this is done by init_project.php, but we actually have multiple rights
// levels here for a single page (so it's not applicable).
if ($user_rights['data_quality_execute'] + $user_rights['data_quality_design'] < 1)
{
	redirect(APP_PATH_WEBROOT . "index.php?pid=$project_id");
}

// Instantiate DataQuality object
$dq = new DataQuality();

// Get rules
$rules = $dq->getRules();

// Rule_id's in a comma-delimited string
$rule_ids = implode(",", array_keys($rules));

// Rule_id's in a comma-delimited string EXCEPT the first 2 rules (missing values rules)
$rule_ids_excludeAB = implode(",", array_splice(array_keys($rules), 2));

// Rule_id's in a comma-delimited string for ONLY user-defined rules
$rule_ids_user_defined = implode(",", array_splice(array_keys($rules), 7));

// Header
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

// Page title
renderPageTitle("<img src='".APP_PATH_IMAGES."checklist.png' class='imgfix2'> {$lang['app_20']}");
?>

<!-- CSS -->
<style type="text/css">
.edit_active { background: #fafafa url(<?php echo APP_PATH_IMAGES ?>pencil.png) no-repeat right; }
.edit_saved { background: #C1FFC1 url(<?php echo APP_PATH_IMAGES ?>tick.png) no-repeat right; }
.editname, .editlogic, .newname, .newlogic { 
	vertical-align:middle;white-space:normal;word-wrap:normal; 
}
.rulenum, .editname { font-size:12px; }
.flexigrid div.bDiv a:link, .flexigrid div.bDiv a:visited, .flexigrid div.bDiv a:active, .flexigrid div.bDiv a:hover { 
	font: normal 11px Arial, Helvetica, sans-serif; text-decoration:underline; 
}
.flexigrid div.ftitle { font-size: 12px; }
.ui-dialog-content { font-size: 11px; }
.flexigrid div.bDiv td div.editname, .flexigrid div.bDiv td div.editlogic, .flexigrid div.bDiv td div.rulenum { white-space:normal;word-wrap:break-word;padding:0; }
.flexigrid div.hDiv th div.grouphdr { color:#800000;white-space:normal;word-wrap:break-word;padding:0; }
.flexigrid div.bDiv td div.exegroup { margin:0; border:0; font-family:arial; font-size:12px; }
.flexigrid div.bDiv td div.red { font-family:arial;background: #FFE1E1; }
.flexigrid div.bDiv td div.darkgreen { font-family:arial;background: #EFF6E8; }
.pd-rule { padding:0;color:#800000; }
.flexigrid div.bDiv td div.dq_evtlabel { font-family:tahoma;white-space:normal;word-wrap:break-word;font-size:10px;color:#888;padding:0; }
.flexigrid div.bDiv td div.dq_daglabel { font-family:tahoma;white-space:normal;word-wrap:break-word;font-size:10px;color:#3E72A8;;padding:0; }
.flexigrid div.bDiv td div.dq_excludelabel { font-size:10px;color:#800000;padding:2px 0 0;font-family:tahoma;white-space:normal;word-wrap:break-word; }
</style>

<!-- Javascript -->
<script type="text/javascript">
// String of rule_id's to process
var rule_ids = '<?php echo $rule_ids ?>';
// String of rule_id's to process (excluding A and B)
var rule_ids_excludeAB = '<?php echo $rule_ids_excludeAB ?>';
// String of rule_id's to process (only user-defined rules)
var rule_ids_user_defined = '<?php echo $rule_ids_user_defined ?>';
// Variable of whether user can edit table
var allowTableEdit = <?php echo $user_rights['data_quality_design'] == '1' ? 'true' : 'false' ?>;
$(function(){
	// Enable the rules table on pageload
	enableRuleTableEdit();
});
</script>
<script type="text/javascript" src="<?php echo APP_PATH_JS ?>jquery_tablednd.js"></script>
<script type="text/javascript" src="<?php echo APP_PATH_JS ?>DataQuality.js"></script>

<!-- Page instructions -->
<p style="margin-top:20px;">
	<?php echo $lang['dataqueries_20'] ?>
	<a href="javascript:;" onclick="$('#moreInstructions').toggle('fade');" style="text-decoration:underline;"><?php echo $lang['dataqueries_35'] ?></a>
</p>
<p id="moreInstructions" style="display:none;margin-top:20px;">
	<?php echo $lang['dataqueries_21'] ?>
	<br><br>
	<?php echo (isDev() ? $lang['dataqueries_126'] . "<br><br>" : "") ?>	
	<?php echo $lang['dataqueries_36'] ?> 
	<a href="javascript:;" onclick="helpPopup('DataQuality');" style="text-decoration:underline;"><?php echo $lang['bottom_27'] ?></a>
	<?php echo $lang['dataqueries_22'] ?>
	<br><br>	
	<?php echo $lang['dataqueries_37'] ?> 
</p>


<!-- If user is in DAG, only show info from that DAG and give note of that -->
<?php if ($user_rights['group_id'] != "") { ?>
	<p style='color:#800000;padding-bottom:10px;'>
	<?php echo $lang['global_02'] . ": " . $lang['dataqueries_19'] ?>
	</p>
<?php } ?>

<!-- Render the rules table -->
<div id="table-rules-parent"><?php echo $dq->displayRulesTable() ?></div>

<!-- Note about missing values and branching logic -->
<div style="max-width:700px;margin:15px 0;color:#555;font-size:11px;">* <?php echo $lang['dataqueries_24'] ?><br>** <?php echo $lang['dataqueries_27'] ?></div>

<!-- Div container for AJAX results -->
<div id="dq_results" style="display:none;"></div>

<!-- Div container for Com Log -->
<div id="comLog" style="display:none;padding:10px 15px 25px;" title="<?php echo cleanHtml2($lang['dataqueries_04']) ?>">
	<div id="comLogLoading">
		<img src="<?php echo APP_PATH_IMAGES ?>progress_circle.gif" class="imgfix"> 
		<?php echo $lang['data_entry_64'] ?>
	</div>
	<div id="comLogComments"></div>
</div>

<!-- Div container for Com Log's "Add new comment" -->
<div id="comLogAddNew" style="display:none;padding:15px;" title="<?php echo cleanHtml2($lang['dataqueries_08']) ?>">
	<p><?php echo $lang['dataqueries_09'] ?></p>
	<textarea id="newComment" style="width:95%;height:100px;"></textarea>
</div>

<!-- Template div container for spinning progress icon -->
<div id="progressIcon" style="display:none;"><img src="<?php echo APP_PATH_IMAGES ?>progress_circle.gif" style="vertical-align:middle;"></div>

<!-- Div container for "explain Exclude" dialog -->
<div id="explain_exclude" class="simpleDialog" style="font-size:12px;" title="<?php echo cleanHtml2($lang['dataqueries_30']) ?>"><?php echo $lang['dataqueries_31'] ?></div>

<?php
// Div container for "explain real-time execution" dialog
print RCView::div(array('id'=>'explain_rte', 'class'=>'simpleDialog', 'style'=>'font-size:12px;', 
		'title'=>$lang['app_20'].$lang['colon'].' '.$lang['dataqueries_123']), 
		$lang['dataqueries_125'] . RCView::br() . RCView::br() . 
		$lang['dataqueries_126'] . RCView::br() . RCView::br() . 
		$lang['dataqueries_127']
	  );

// Footer
require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';