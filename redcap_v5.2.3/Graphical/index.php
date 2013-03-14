<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

include_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
include_once APP_PATH_DOCROOT . 'Graphical/functions.php';
include_once APP_PATH_DOCROOT . 'ProjectGeneral/form_renderer_functions.php';
include_once APP_PATH_DOCROOT . 'ProjectGeneral/math_functions.php';
include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

// Increase memory limit so large data sets do not crash and yield a blank page



// If plot service is not enabled or set up yet, give advertisement to Super Users about it and how to set it up
if ($enable_plotting < 1 && $super_user) 
{
	// Page header
	renderPageTitle("<div>
						<img src='".APP_PATH_IMAGES."chart_curve.png' class='imgfix2'> 
						{$lang['graphical_view_01']} - 
						<span style='color:red;'>{$lang['global_23']}</span>
					 </div>");
	?>
	<div class="yellow" style="margin: 20px 0;">
		<img src="<?php echo APP_PATH_IMAGES ?>exclamation_orange.png" class="imgfix"> 
		<?php echo $lang['graphical_view_02'] ?> 
	</div>
	<p>
		<b><?php echo $lang['graphical_view_03'] ?></b><br>
		<?php echo $lang['graphical_view_57'] ?>
		<?php echo $lang['system_config_167'] ?>
		<a href='https://iwg.devguard.com/trac/redcap/wiki/REDCapPlotService'
			  style='text-decoration:underline;' target='_blank'><?php echo $lang['system_config_25'] ?></a><?php echo $lang['period'] ?>
	</p>
	<p>
		<b><?php echo $lang['graphical_view_05'] ?></b><br>
		<?php echo $lang['graphical_view_58'] ?> 
		<a href="<?php echo APP_PATH_WEBROOT ?>ControlCenter/modules_settings.php" 
			style="text-decoration:underline;"><?php echo $lang['graphical_view_07'] ?></a>
		<?php echo $lang['graphical_view_59'] ?>
		<a href="https://iwg.devguard.com/trac/redcap/wiki/REDCapPlotService" target="_blank" 
			style="text-decoration:underline;"><?php echo $lang['system_config_25'] ?></a><?php echo $lang['period'] ?>
		<?php echo $lang['graphical_view_61'] ?>
	</p>
	<h3 style="color:#800000;max-width:700px;margin-top:35px;"><?php echo $lang['graphical_view_11'] ?></h3>
	<p>		
		<?php echo $lang['graphical_view_12'] ?>
		<br><br> 
		<img src="<?php echo APP_PATH_IMAGES ?>plot_example1.png" style="border:2px dashed #666;padding:10px;">
		<br><br>
		<?php echo $lang['graphical_view_13'] ?>
		<br><br> 
		<img src="<?php echo APP_PATH_IMAGES ?>plot_example2.png" style="border:2px dashed #666;padding:10px;">		
	</p>
	<?php
	include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
	exit;
}




// Set value for delay between the rendering of plots on page (to pace them from top to bottom in order)
$plot_pace = 100; // in milliseconds




// Set the current form
$form = '';
if (isset($_GET['page']) && $_GET['page'] != "") 
{ 
	$form = $_GET['page'];
	// Make sure that the form exists and that the user has access to it, otherwise redirect back to page with no form selected
	if (!isset($Proj->forms[$form]) || (isset($user_rights['forms'][$_GET['page']]) && $user_rights['forms'][$_GET['page']] < 1)) {
		redirect(PAGE_FULL . "?pid=$project_id");
	}
}

// Set the current tab
$tab_type = 'graphvis';
if (isset($_GET['type']))
{
	$tab_type = $_GET['type'];
}

renderPageTitle("<img src='".APP_PATH_IMAGES."chart_curve.png' class='imgfix2'> {$lang['graphical_view_01']}");

// Page instructions
print "<p style='margin-top:20px;'>";
print ($enable_plotting == '1') ? $lang['graphical_view_55'] : $lang['graphical_view_70'];
print "</p>";

// Instructions if have selected a record/event (only for Default Plotting service)
if ($tab_type == 'graphvis' && $enable_plotting == '2' && isset($_GET['record']) && isset($_GET['event_id'])) 
{
	?>
	<p style='margin-bottom:20px;'>
		<?php echo $lang['survey_170'] ?> <span style='font-weight:bold;color:#3366CC;'><?php echo $lang['survey_171'] ?></span><?php echo $lang['graphical_view_62'] ?> 
		<span style='font-weight:bold;color:#D88400;'><?php echo $lang['survey_173'] ?></span><?php echo $lang['period'] ?>
		<?php echo $lang['graphical_view_63'] ?> <span style='font-weight:bold;color:#3366CC;'><?php echo $lang['survey_171'] ?></span><?php echo $lang['survey_175'] ?> 
		<span style='font-weight:bold;color:#DC3912'><?php echo $lang['survey_176'] ?></span><?php echo $lang['graphical_view_64'] ?> 
		<span style='font-weight:bold;color:#D88400;'><?php echo $lang['survey_173'] ?></span><?php echo $lang['period'] ?>
	</p>
	<?php
}

//If user is in DAG, only show info from that DAG and give note of that
if ($user_rights['group_id'] != "") 
{
	print  "<p style='color:#800000;padding-bottom:10px;'>
			{$lang['global_02']}: {$lang['graphical_view_17']}
			</p>";

}


//Check if cURL is installed first. If not, then give error message.
if ($enable_plotting == '1' && !function_exists('curl_init')) 
{ 
	//cURL is not loaded
	curlNotLoadedMsg();	
	include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
	exit;
}


// Calculate Total Records in Project (numbers may differ from form to form for longitudinal projects)
$totalrecs = getRecordCount($project_id, $form);





// Build html for the form drop-down
$formDropdownOptions = "";
foreach ($Proj->forms as $this_form=>$attr) 
{
	// Don't show if user has None rights to this form
	if (isset($user_rights['forms'][$this_form]) && $user_rights['forms'][$this_form] > 0) {
		$formDropdownOptions .= "<option value='$this_form' ";
		if ($form == $this_form) $formDropdownOptions .= "selected";
		$formDropdownOptions .= ">{$attr['menu']}</option>";
	}
}



// Build html for the record/event select drop-down (only for Default Plotting service)
if ($tab_type == 'graphvis' && $enable_plotting == '2') 
{
	if ($user_rights['group_id'] == "") {
		$group_sql  = ""; 
	} else {
		$group_sql  = "and d.record in (" . pre_query("select record from redcap_data where project_id = $project_id and field_name = '__GROUPID__' and value = '".$user_rights['group_id']."'") . ")"; 
	}
	$rs_ids_sql = "select d.record, d.event_id, m.descrip from redcap_data d, redcap_events_metadata m 
				   where d.project_id = $project_id and d.field_name = '$table_pk' $group_sql 
				   and d.event_id = m.event_id order by abs(d.record), d.record, m.day_offset, m.descrip";
	$q = db_query($rs_ids_sql);
	// Collect record names into array
	$records  = array();
	while ($row = db_fetch_assoc($q)) 
	{
		// Add to array
		$records[$row['record']][$row['event_id']] = ($longitudinal ? "({$row['descrip']})" : "");
	}
	// Non-longitudinal ONLY: Show custom record label and secondary unique field in drop-down
	// (Can't show for longitudinal because we're already showing all events for each record in the drop-down - i.e. doesn't make sense to.)
	if (!$longitudinal)
	{
		// Customize the Record ID pulldown menus using the SECONDARY_PK appended on end, if set.
		if ($secondary_pk != '' && !$is_child)
		{
			$sql = "select d.record, d.value from redcap_data d where d.project_id = $project_id and d.field_name = '$secondary_pk' 
					and d.event_id = {$Proj->firstEventId} $group_sql";
			$q = db_query($sql);
			while ($row = db_fetch_assoc($q)) 
			{
				$records[$row['record']][$Proj->firstEventId] .= " (" . $Proj->metadata[$secondary_pk]['element_label'] . " " . RCView::escape($row['value']) . ")";
			}
			db_free_result($q);
		}
			
		// [Retrieval of ALL records] If Custom Record Label is specified (such as "[last_name], [first_name]"), then parse and display
		// ONLY get data from FIRST EVENT
		if (!empty($custom_record_label)) 
		{
			foreach (getCustomRecordLabels($custom_record_label, $Proj->getFirstEventIdArm(getArm())) as $this_record=>$this_custom_record_label)
			{
				$records[$this_record][$Proj->firstEventId] .= " " . $this_custom_record_label;
			}
		}
	}
	// Loop through the record list and store as string for drop-down options
	$id_dropdown = "";
	foreach ($records as $this_record=>$this_event)
	{
		foreach ($this_event as $this_event_id=>$extra_label)
		{
			$id_dropdown .= "<option class='notranslate' value='{$this_record}[__EVTID__]{$this_event_id}'"
						  . (($_GET['record'] == $this_record && $_GET['event_id'] == $this_event_id) ? " selected " : "")
						  . ">$this_record $extra_label</option>";
		}
	}
}
?>



<!-- Select form and/or record/response -->
<div style="max-width:700px;margin:20px 0 30px;">
	<table class="form_border" width=100%>
		<tr>
			<td class="header" colspan="2" style="">
				<?php echo $lang['graphical_view_61'] ?>
			</td>
		</tr>
		<!-- Show option to select data entry form( do not show for Single Survey projects) -->
		<tr>
			<td class="label" style="width:400px;padding:10px 8px;">
				<?php echo $lang['graphical_view_43'] ?>
			</td>
			<td class="data" style="padding:10px 8px;">
				<select id="record_select1" class="x-form-text x-form-field notranslate" style="padding-right:0;height:22px;" onchange="
					var url = app_path_webroot+page+'?pid='+pid+'&page='+this.value+'&type=<?php echo $tab_type ?>';
					if ($('#record_select_single').length) {
						if ($('#record_select_single').val().length > 0) {
							var recevt = $('#record_select_single').val().split('[__EVTID__]');
							url += '&record='+recevt[0]+'&event_id='+recevt[1];
						}
					}
					window.location.href = url;
				">
					<option value=""><?php echo $lang['graphical_view_44'] ?></option>
					<?php echo $formDropdownOptions ?>
				</select>
			</td>
		</tr>
		<?php if ($tab_type == 'graphvis' && $enable_plotting == '2') { ?>
			<!-- Show option to overlay single record values onto plots (only for Default Plot Service) -->
			<tr>
				<td class="label" style="width:400px;font-weight:normal;padding:10px 8px;">
					<?php echo $lang['graphical_view_60'] ?>
				</td>
				<td class="data" style="padding:10px 8px;">
					<select id="record_select_single" <?php if ($form == '') echo 'disabled' ?> class="x-form-text x-form-field notranslate" style="padding-right:0;height:22px;" onchange="
						var url = app_path_webroot+page+'?pid='+pid+'&page=<?php echo $form ?>'+'&type=<?php echo $tab_type ?>';
						if (this.value.length > 0) {
							var recevt = this.value.split('[__EVTID__]');
							url += '&record='+recevt[0]+'&event_id='+recevt[1];
						}
						window.location.href = url;
					">
						<option value=""><?php echo $lang['data_entry_91'] ?></option>
						<?php echo $id_dropdown ?>
					</select>
				</td>
			</tr>
		<?php } ?>
	</table>
</div>



<?php 
## TABS (rApache only since GCT uses a single page)
if ($enable_plotting == '1') 
{ 
	?>
	<ul id="dc-select" style="max-width:700px;">
		<li class="right <?php print ($tab_type == 'graphvis' ? 'active' : '') ?>">
			<a class="<?php print ($tab_type == 'graphvis' ? 'active' : '') ?>" href="<?php print PAGE_FULL."?pid=$project_id&page=$form&type=graphvis" ?>"><img src="<?php print APP_PATH_IMAGES ?>chart_curve.png" 
				class="imgfix"> <?php echo $lang['graphical_view_18'] ?></a>
		</li>
		<li class="right <?php print ($tab_type == 'simple' ? 'active' : '') ?>">
			<a class="<?php print ($tab_type == 'simple' ? 'active' : '') ?>" href="<?php print PAGE_FULL."?pid=$project_id&page=$form&type=simple" ?>"><img src="<?php print APP_PATH_IMAGES ?>application_view_columns.png" 
				class="imgfix"> <?php echo $lang['graphical_view_19'] ?></a>
		</li>
		<?php if ($enable_plotting == '2') { ?>
			<li class="right <?php print ($tab_type == 'expanded' ? 'active' : '') ?>">
				<a class="<?php print ($tab_type == 'expanded' ? 'active' : '') ?>" href="<?php print PAGE_FULL."?pid=$project_id&page=$form&type=expanded" ?>"><img src="<?php print APP_PATH_IMAGES ?>expanded_stats.png" 
					class="imgfix"> <?php echo $lang['graphical_view_54'] ?></a>
			</li>
		<?php } ?>
	</ul>
	<p></p>
	<?php
}


// If a form is not selected yet, then stop here
if ($form == '')
{
	// Note to select a form
	if ($enable_plotting == '1') { 
		// Only show this when tabs are displayed (i.e. rApache)
		print "<p style='margin:20px 0;color:#666;'>{$lang['graphical_view_45']}</p>";
	}
	// GOOGLE CHROME FRAME: If using IE6,7, or 8 (and not using Google Chrome Frame), give message to install GCF
	if ($enable_plotting == '2' && $tab_type == 'graphvis')
	{
		// Show message, if applicable
		chromeFrameInstallMsg();			
	}
	// Footer
	include APP_PATH_DOCROOT . "ProjectGeneral/footer.php";
	exit;
}



//Set text to distinguish actual number of records from the total record-event "records" (i.e. rows of data in export)
if ($longitudinal) {
	// Count real project records
	$num_records_real = db_result(db_query("select count(distinct(record)) from redcap_data where project_id = " . PROJECT_ID . " and field_name = '$table_pk'"),0);
	$recCountText = "<span style='color:#777;font-size:11px;font-family:tahoma,arial;'>
						{$lang['graphical_view_20']} $num_records_real {$lang['graphical_view_21']}
					</span>";
} else {
	$recCountText = "";
}


// Display number or total records in project
print  "<p style='font-size:15px;color:#800000;padding-bottom:10px;'>
			<b>{$lang['graphical_view_22']} $totalrecs</b>&nbsp; $recCountText
		</p>";

		
// Make a call to the Plot Service to determine the RPS version
if ($enable_plotting == '1')
{
	$rps_version = rps_version();
	// If on RPS version 1.0 or higher, then show the PDF export of expanded stats
	if ($rps_version >= 1.0)
	{
		print  '<p style="text-align:right;padding-bottom:5px;">
					<a style="font-size: 11px; color: #800000;" href="'.APP_PATH_WEBROOT.'Graphical/pdf.php/download.pdf?pid='.$project_id.'&form='.$form.'"><img class="imgfix" src="'.APP_PATH_IMAGES.'pdf.gif"/> '.$lang['graphical_view_42'].'</a>
				</p>';
	}
}



















## Tab 2: Descriptive Stats (rApache only)
if ($enable_plotting == '1' && $tab_type == 'simple')
{
	# Grab lists of relevant fields on which to report
	$res = db_query("select element_label from redcap_metadata where form_name = '$form' and project_id = $project_id 
						and element_type != 'descriptive' order by field_order");
	while($ret = db_fetch_assoc($res)){
		$labels[] = strip_tags(label_decode($ret['element_label']));
	}
	if ($rps_version >= 1.0){
		$res = rapache_service('simplestats', NULL, rapache_fields_to_csv($form,$totalrecs,$user_rights['group_id']));
	} else {
		$res = rapache_service('dataCleanerDesc', NULL, rapache_fields_to_csv($form,$totalrecs,$user_rights['group_id']));
	}
	$lines = explode("\n",$res);
	
	if ($rps_version >= 1.0)
	{
		// Table header
		print  "<table id='dataCleanerReport'>
					<tr style='font-weight:bold;font-size:13px;'>
						<td style='text-align:center;'>{$lang['graphical_view_23']}</td>
						<td style='text-align:center;'>{$lang['graphical_view_24']}</td>
						<td style='text-align:center;'>{$lang['graphical_view_25']}</td>
						<td style='text-align:center;'>Q1</td>
						<td style='text-align:center;'>Q2 {$lang['graphical_view_28']}</td>
						<td style='text-align:center;'>Q3</td>
						<td style='text-align:center;'>{$lang['graphical_view_26']}</td>
						<td style='text-align:center;'>{$lang['graphical_view_27']}</td>
						<td style='text-align:center;'>{$lang['graphical_view_29']}</td>
					</tr>";
					
		$i = 0;
		foreach ($lines as $line){
			if (strlen($line) == 0) continue;
			$cols = explode('|', $line);
			if (count($cols) > 1) {
				print "
					<tr class='notranslate'>
						<td>{$labels[$i]}</td>
						<td class='rjust'>" . implode("</td><td class='rjust'>", explode('|', $line)) . "</td>";
				print "</tr>";
			}
			$i++;
		}
	} else {
		// Table header
		print  "<table id='dataCleanerReport'>
					<tr style='font-weight:bold;font-size:13px;'>
						<td style='text-align:center;'>{$lang['graphical_view_23']}</td>
						<td style='text-align:center;'>{$lang['graphical_view_24']}</td>
						<td style='text-align:center;'>{$lang['graphical_view_25']}</td>
						<td style='text-align:center;'>{$lang['graphical_view_26']}</td>
						<td style='text-align:center;'>{$lang['graphical_view_27']}</td>
						<td style='text-align:center;'>{$lang['graphical_view_28']}</td>
						<td style='text-align:center;'>{$lang['graphical_view_29']}</td>
						<td style='text-align:center;'>{$lang['graphical_view_30']}</td>
					</tr>";
					
		$i = 0;
		foreach ($lines as $line){
			if (strlen($line) == 0) continue;
			$cols = explode('|', $line);
			if (count($cols) > 1) {
				print "
					<tr class='notranslate'>
						<td>{$labels[$i]}</td>
						<td class='rjust'>" . implode("</td><td class='rjust'>", explode('|', $line)) . "</td>";
				# Add coefficient of variation: stdev / mean
				if ($cols[5] > 0 && $cols[3] > 0){
					print "<td class='rjust'>" . sprintf('%.2f', $cols[5] / $cols[3]) . "</td>";
				} else {
					print "<td class='rjust'></td>";
				}
				print "</tr>";
			}
			$i++;
		}
	}
	print  "</table>";
	print "<br>";
}











## Tab 1: PLOTS
elseif ($tab_type == 'graphvis') 
{
	?>
	<p style="padding-bottom:10px;">		
		<?php
		if ($enable_plotting == '1') {
			print $lang['graphical_view_72'];
		}
		?>
	</p>
	<?php
	// Obtain the fields to chart
	$fields = getFieldsToChart($project_id, $form);
	// Render charts
	renderCharts($project_id, $fields, $form, $totalrecs);
	
}

print "<br>";

// Do not render left-hand menu and footer if printing page in pop-up
include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
