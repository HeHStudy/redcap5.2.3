<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';


// Get arm num
$arm = getArm();

include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

print  "<p style='text-align:right;'>
			<img src='" . APP_PATH_IMAGES . "video_small.png' class='imgfix'> 
			<a onclick=\"window.open('".CONSORTIUM_WEBSITE."videoplayer.php?video=define_events01.flv&referer=".SERVER_NAME."&title=Define+My+Events','myWin','width=1050, height=800, toolbar=0, menubar=0, location=0, status=0, scrollbars=1, resizable=1');\" href=\"javascript:;\" style=\"font-size:12px;text-decoration:underline;font-weight:normal;\">{$lang['define_events_02']}</a>
		</p>";
		
// Link back to Project Setup
$tabs = array(	"ProjectSetup/index.php"=>"<img src='".APP_PATH_IMAGES."arrow_left.png' class='imgfix'> {$lang['app_17']}",
				"Design/define_events.php".(isset($_GET['arm']) ? "?arm=".$_GET['arm'] : "")=>"<img src='".APP_PATH_IMAGES."clock_frame.png' class='imgfix'> {$lang['global_16']}",
				"Design/designate_forms.php".(isset($_GET['arm']) ? "?arm=".$_GET['arm'] : "")=>"<font id='popupTrigger'><img src='".APP_PATH_IMAGES."table_refresh.png' class='imgfix'> {$lang['global_28']}</font>" );
renderTabs($tabs);

print  "<p>" . $lang['define_events_03'] . ($scheduling ? "{$lang['define_events_04']}  
								<a href='".APP_PATH_WEBROOT."Calendar/index.php?pid=$project_id' style='text-decoration:underline;'>{$lang['app_08']}</a>" : "") . 
		$lang['define_events_05'] . ($scheduling ? $lang['define_events_06'] : $lang['define_events_07']). 
		$lang['define_events_08'] . "</p>";


## STEP 1 and 2
if ($super_user || $status < 1 || ($status > 0 && $enable_edit_prod_events))	
{
	print  "<p>
				<b>{$lang['define_events_14']}</b><br>
				{$lang['define_events_15']} 
				<i style='color:#800000;'>{$lang['define_events_16']}</i> {$lang['define_events_17']} ";
	if ($scheduling) {
		print  "{$lang['define_events_18']} 
				<a href='".APP_PATH_WEBROOT."Calendar/scheduling.php?pid=$project_id' style='text-decoration:underline;'>{$lang['define_events_19']}</a>,
				{$lang['define_events_20']} ";
	}
	print  "	{$lang['define_events_21']}</p>";
			
	print  "<p>
				<b>{$lang['define_events_22']}</b><br>
				{$lang['define_events_23']} 
				<a href='" . APP_PATH_WEBROOT . "Design/designate_forms.php?pid=$project_id' style='text-decoration:underline;'>{$lang['global_28']}</a> 
				{$lang['define_events_25']}
			</p>";
}


// NOTE: If normal users cannot add/edit events in production, then give notice
if (!$super_user && $status > 0 && !$enable_edit_prod_events) 
{
	print  "<div class='yellow' style='margin-bottom:10px;'>
				<b>{$lang['global_02']}:</b><br>
				{$lang['define_events_10']} 
				{$lang['define_events_11']} $project_contact_name {$lang['global_15']}
				<a href='mailto:$project_contact_email' style='font-family:Verdana;text-decoration:underline;'>$project_contact_email</a>.
			</div>";
}


//This page cannot be used if using parent/child project linking
if ($is_child) {
	print  "<br><div class='red'>
			<b>{$lang['global_02']}:</b><br>
			{$lang['define_events_13']}
			{$lang['define_events_11']} $project_contact_name {$lang['global_15']}
			<a href='mailto:$project_contact_email' style='font-family:Verdana;text-decoration:underline;'>$project_contact_email</a>.
			</div></p>";
	//Allow super users to still use the page, but disallow normal users to do so
	if (!$super_user) {
		include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
		exit;
	}
}


/**
 * NEWLY CREATED PROJECT
 * Show message to user with some background info about the already-created Arm and Event
 */
 //Check if there is one arm and one event and they are named "Arm 1" and "Event 1"
$q = db_query("select a.arm_name, m.descrip from redcap_events_arms a, redcap_events_metadata m where a.arm_id = m.arm_id and a.project_id = $project_id");
if (db_num_rows($q) == 1) {
	$row = db_fetch_assoc($q);
	if ($row['arm_name'] == "Arm 1" && $row['descrip'] == "Event 1") {
		print  "<div class='yellow'>
				<img src='".APP_PATH_IMAGES."exclamation_orange.png' class='imgfix'> 
				<b>{$lang['global_02']}:</b> {$lang['define_events_26']}
				</div><br>";	
	}	
}
	
// Div pop-up for month/year/week conversion to days
print  "<div style='display:none;' id='convert' title='{$lang['define_events_33']}'>
			<div style='font-size:11px;color:#666;padding-bottom:12px;'>
				{$lang['define_events_27']}
			</div>
			<table cellpadding=0 cellspacing=3 style='width:100%'>
				<tr>
					<td valign='top'>{$lang['define_events_28']}</td>
					<td valign='top'><input id='calc_year' onclick='this.select()' onkeyup='calcDay(this)' type='text' 
						style='font-size:11px;width:70px;' onblur='redcap_validate(this,\"\",\"\",\"hard\",\"float\")'></td>
				</tr>
				<tr>
					<td valign='top' style='padding-right:10px;'>{$lang['define_events_29']}</td>
					<td valign='top'><input id='calc_month' onclick='this.select()' onkeyup='calcDay(this)' type='text' 
						style='font-size:11px;width:70px;' onblur='redcap_validate(this,\"\",\"\",\"hard\",\"float\")'></td>
				</tr>
				<tr>
					<td valign='top'>{$lang['define_events_30']}</td>
					<td valign='top'><input id='calc_week' onclick='this.select()' onkeyup='calcDay(this)' type='text' 
						style='font-size:11px;width:70px;' onblur='redcap_validate(this,\"\",\"\",\"hard\",\"float\")'></td>
				</tr>
				<tr>
					<td valign='top' style='padding-top:15px;'>{$lang['define_events_31']}</td>
					<td valign='top' style='padding-top:15px;'>
						<input id='calc_day' onkeyup='calcDay(this)' type='text' maxlength='5' 
							style='background-color:#eee;color:red;font-size:11px;width:40px;' 
							onblur='redcap_validate(this,\"-9999\",\"9999\",\"hard\",\"int\")'>
						&nbsp;
						<input id='convTimeBtn' style='cursor:pointer;' type='button' value=' <- Use this value ' onclick=\"
							$('#day_offset').val($('#calc_day').val());
							$('#convert').dialog('destroy');
						\">
						<br>
						<span style='font-size:10px;color:#888;'>{$lang['define_events_32']}</span>
					</td>
				</tr>
			</table>
		</div>";

// Div for pop-up tooltip
?>
<div id="designateTip" class="tooltip4">
	<?php echo $lang['define_events_60'] ?>
</div>
<script type="text/javascript">
var hasShownDesignatePopup = 0;
$(function(){
	$("#popupTrigger").tooltip({ tip: '#designateTip', relative: true, effect: 'fade', position: 'top center' });
});
</script>
<?php


//Div where table where be rendered
print  "<div id='table' style='max-width:700px;'>";
if (!isset($_GET['arm'])) $_GET['arm'] = $arm;
include APP_PATH_DOCROOT . "Design/define_events_ajax.php";
print  "</div>";

print  "<br><br><br>";

include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
