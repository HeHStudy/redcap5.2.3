<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

//Pick up any variables passed by Post
if (isset($_POST['pnid'])) $_GET['pnid'] = $_POST['pnid'];
if (isset($_POST['pid']))  $_GET['pid']  = $_POST['pid'];
if (isset($_POST['arm']))  $_GET['arm']  = $_POST['arm'];

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

//If action is provided in AJAX request, perform action.
if (isset($_REQUEST['action'])) {

	switch ($_REQUEST['action']) {	
		//Save grid values
		case "save_grid":	
			$sql_all = array();	
			//Get arm number
			$arm = getArm();
			//Delete all previous form-event combos first in order to replace with new (only delete form-event info for this Arm)
			$sql_all[] = $sql = "delete from redcap_events_forms where event_id in 
								(select m.event_id from redcap_events_metadata m, redcap_events_arms a where a.project_id = $project_id 
								and a.arm_num = $arm and a.arm_id = m.arm_id)";
			db_query($sql);
			//Loop through posted elements and insert new event-form info
			$grid_array = explode(",", $_POST['grid_values']);
			foreach ($grid_array as $value) {
				list($this_form, $this_event_id, $this_tf) = explode("--", $value);
				if ($this_tf == "true") {
					$sql_all[] = $sql = "insert into redcap_events_forms (event_id, form_name) 
										 values (".checkNull($this_event_id).", '".prep($this_form)."')";
					db_query($sql);
				}
			}
			// If surveys exist and have Automated Invitations set up, then set any AI to inactive status if
			// the user undesignates a form for an event in which the form/survey+event have AI set up as active.
			// This prevents AI from sending survey invites if the form is now undesignated.
			if ($surveys_enabled && !empty($Proj->surveys))
			{
				$sub = pre_query("select ss.ss_id from redcap_surveys_scheduler ss, redcap_surveys s, redcap_events_forms f 
						where s.survey_id = ss.survey_id and ss.active = 1 and s.project_id = $project_id 
						and f.form_name = s.form_name and f.event_id = ss.event_id");
				$sql_all[] = $sql = "update redcap_surveys_scheduler ss2, redcap_surveys s2 set ss2.active = 0 
						where s2.survey_id = ss2.survey_id and ss2.active = 1 and s2.project_id = $project_id 
						and ss2.ss_id not in ($sub)";
				db_query($sql);
			}
			// Logging
			log_event(implode(";\n", $sql_all),"redcap_events_forms","MANAGE",$arm,"arm_num = $arm","Designate data collection instruments for events");
			break;
	}	
	
}


## DTS: Check for any events-forms are being used by DTS
$dtsEventsForms = ($dts_enabled_global && $dts_enabled) ? getDtsEventsForms() : array();
if (!empty($dtsEventsForms)) {
	?>
	<div class="red" style="margin:10px 0;">
		<img src="<?php echo APP_PATH_IMAGES ?>exclamation.png" class="imgfix"> 
		<b><?php echo $lang['define_events_64'] ?></b><br>
		<?php echo $lang['designate_forms_22'] ?>
	</div>
	<?php
}


/***************************************************************
** ARM TABS
***************************************************************/
//Display Arm number tab
//Loop through each ARM and display as a tab
$q = db_query("select arm_id, arm_num, arm_name from redcap_events_arms where project_id = $project_id order by arm_num");
if (db_num_rows($q) > 1) {
	print '<div id="sub-nav" style="margin-bottom:0;"><ul>';
	while ($row = db_fetch_assoc($q)) {
		//Render tab
		print  '<li';
		//If this tab is the current arm, make it selected
		if ($row['arm_num'] == $arm) {
			print  ' class="active"';
			//Get current Arm ID
			$arm_id = $row['arm_id'];
			//Get current Arm Name
			$arm_name = $row['arm_name'];
		}
		print  '><a style="font-size:12px;color:#393733;padding:5px 5px 0px 11px;" href="'.APP_PATH_WEBROOT.'Design/designate_forms.php?pid='.$project_id.'&arm='.$row['arm_num'].'">'.$lang['global_08'].' '.$row['arm_num'].'</a></li>';
	}
	print  '</ul></div>&nbsp;<br>';
	//If more than one arm exists, the display arm name for clarity
	print  "<p>{$lang['designate_forms_18']} <b class='notranslate' style='color:#800000;'>$arm_name</b></p>";
} else {
	$arm_id = db_result($q,0);
}




/***************************************************************
** EVENT-FORM GRID
***************************************************************/

//Determine if any forms have been assigned to events and display grid
$q = db_query("select m.event_id, m.descrip, f.form_name from redcap_events_metadata m, redcap_events_forms f 
				  where m.event_id = f.event_id and m.arm_id = $arm_id order by m.day_offset, m.descrip");
	
while ($row = db_fetch_assoc($q)) 
{
	//Add form-event info to array
	$form_events[$row['event_id']][$row['form_name']] = "";
}
//print "<Pre>";print_r($event_descrip);print_r($form_events);print "</pre>";

//Determine if any visits have been defined yet
$q = db_query("select * from redcap_events_metadata where arm_id = $arm_id order by day_offset, descrip");
$num_events = db_num_rows($q);
while ($row = db_fetch_assoc($q)) {
	//Collect event description to render as labels in grid at bottom
	$event_descrip[$row['event_id']] = $row['descrip'];
}


//Render Grid
$grid_disp_change = "";
$grid_string  =  "<table class='form_border'>";
$grid_string .=  "<tr>
			<td class='header' rowspan='2' style='text-align:center;padding:5px;'>{$lang['global_35']}</td>
			<td class='header' colspan='".count($event_descrip)."' style='text-align:center;padding:5px;'>{$lang['global_45']}</td>
		</tr>
		<tr class='notranslate'>";
//Render table headers
$i = 1;
foreach ($event_descrip as $this_event) {
	$grid_string .= "<td class='header' style='text-align:center;width:25px;color:#800000;padding:5px;white-space:normal;vertical-align:bottom;'>
					 <div style='font-family:Arial;'>$this_event</div>
					 <div style='font-weight:normal;font-size:10px;'>($i)</div>
					 </td>";
	$i++;
}
$grid_string .= "</tr>";
//Render table rows
$sql = "select e.event_id, e.descrip, m.form_name, m.form_menu_description from redcap_events_metadata e, redcap_metadata m 
		where m.project_id = $project_id and m.form_menu_description is not null and e.arm_id = $arm_id 
		order by m.field_order, e.day_offset, e.descrip";
$q = db_query($sql);
$this_form = "";
$grid_values = array();
while ($row = db_fetch_assoc($q)) 
{
	//Deterine if we are starting new row	
	if ($this_form != $row['form_name']) {
		if ($this_form != "") $grid_string .= "</tr>";	
		$grid_string .= "<tr class='notranslate'><td class='data'>{$row['form_menu_description']}";
		// Show the label "survey" if first instrument is a survey
		if ($surveys_enabled && isset($Proj->forms[$row['form_name']]['survey_id'])) {
			$grid_string .= "<span style='margin:0 4px;color:#888;font-size:10px;font-family:tahoma;'>{$lang['grid_39']}</span>";
		}
		$grid_string .= "</td>";
	}
	//Render cell
	$grid_string .= "<td class='data' style='text-align:center;height:20px;'>";
	$grid_string .= "<input type='checkbox' id='{$row['form_name']}--{$row['event_id']}' style='display:none;' ";
	// If event-form has been stored, then display check mark.
	// Also, for a survey+forms-type project, do not allow the first form to be repeated
	if (isset($form_events[$row['event_id']][$row['form_name']])) 
	{
		//Display check images
		$grid_string .= "checked ><img src='".APP_PATH_IMAGES."tick.png' id='img--{$row['form_name']}--{$row['event_id']}'";
		//Gather javascript to hide check images to begin editing
		$grid_disp_change .= "document.getElementById('img--{$row['form_name']}--{$row['event_id']}').style.display='none';";
	}
	// Give warning label if used by DTS
	if (isset($dtsEventsForms[$row['event_id']][$row['form_name']])) {
		$grid_string .= "><div class='dtswarn' style='font-size:9px;'>{$lang['define_events_62']}</div";
	}
	$grid_string .= "></td>";
	//Collect checkbox values for submitting
	$grid_values[] = "'{$row['form_name']}--{$row['event_id']}--'+document.getElementById('{$row['form_name']}--{$row['event_id']}').checked";
	//Gather javascript to display checkboxes to begin editing
	$grid_disp_change .= "document.getElementById('{$row['form_name']}--{$row['event_id']}').style.display='';";
	//Set for next loop
	$this_form = $row['form_name'];
}
$grid_string .=  "</tr>
		</table>
		<br>";



// Render Edit and Save buttons at top of section
if ($super_user || $status < 1 || ($status > 0 && $enable_edit_prod_events))
{
	print  "<p>";
	print  "<input type='button' value=' Begin Editing ' style='font-size:11px;' onclick=\"
				this.disabled = true;
				document.getElementById('save_btn').disabled = false;
				$grid_disp_change
			\"> &nbsp;
			<input type='button' value=' Save ' id='save_btn' style='font-size:11px;' disabled onclick=\"";
	if (count($grid_values) > 0 && isset($grid_values)) {
		print  "this.disabled = true;
				document.getElementById('progress_save').style.visibility = 'visible';
				var g='';";
		foreach ($grid_values as $grid_value) {
			print "g+=$grid_value+',';";
		}
		print  "$.post('".APP_PATH_WEBROOT."Design/designate_forms_ajax.php', { pid: pid, arm: $arm, action: 'save_grid', grid_values: g }, function(data){ 
					$('#table').html(data);
				});";
	}
	print "	\"> &nbsp;&nbsp;
			<span id='progress_save' style='color:#555;visibility:hidden;'>
				<img src='".APP_PATH_IMAGES."progress_small.gif' class='imgfix'>
				{$lang['designate_forms_21']}
			</span>";
	print  "</p>";
}

//Render table
print $grid_string;

