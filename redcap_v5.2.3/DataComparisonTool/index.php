<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';


include APP_PATH_DOCROOT  . 'ProjectGeneral/header.php';
include APP_PATH_DOCROOT . 'ProjectGeneral/form_renderer_functions.php';

renderPageTitle("<img src='".APP_PATH_IMAGES."page_copy.png'> " . $lang['app_02'] . " " . ($double_data_entry ? $lang['data_comp_tool_01'] : ""));



// Instructions
if ($double_data_entry) {
	print "<p>" . $lang['data_comp_tool_03'];
} else {
	print "<p>" . $lang['data_comp_tool_04'];
}

//If user is in DAG, only show info from that DAG and give note of that
if ($user_rights['group_id'] != "") {
	print  "<p style='color:#800000;'>{$lang['global_02']}: {$lang['data_comp_tool_05']}</p>";
}
			
// Create array of checkbox fields with field_name as key and default value options of "0" as sub-array values
$chkbox_fields = array();
foreach ($Proj->metadata as $field=>$attr) {
	if (!$Proj->isCheckbox($field)) continue;
	foreach (parseEnum($attr['element_enum']) as $this_value=>$this_label) {
		$chkbox_fields[$field][$this_value] = "0";	
	}
}




###############################################################################################
## If Double Data Entry reviewer is creating a new merged third record...
if (isset($_GET['create_new']) && $_SERVER['REQUEST_METHOD'] == 'POST') 
{
	print "<hr size=1><p>";	
	
	// Get record name and its event_id for new record
	$event_id3 = $_GET['event_id'];
	$record3 = $_POST[$table_pk] = substr($_POST[$table_pk], 0, -3);
	
	// Loop through posted values to clean and remove extra radio logic used (kludgey!)
	foreach ($_POST as $key=>$value) {
		if (substr($key, -11) == "___RADradio") {
			$real_key = substr($key, 0, -11);
			if (isset($_POST[$real_key])) {
				unset($_POST[$real_key."___RAD1"]);
				unset($_POST[$real_key."___RAD2"]);
				unset($_POST[$real_key."___RAD3"]);
				unset($_POST[$real_key."___RADradio"]);
			}
		}
	}
	
	// Get all data shared by both records and add it to $_POST, which currently has the data selected by the user because of discrepancies
	$sql = "select d.field_name, d.value, m.element_type, m.element_enum from redcap_data d, redcap_metadata m where d.project_id = $project_id 
			and d.project_id = m.project_id and d.field_name = m.field_name and d.event_id = $event_id3 and d.record = '".prep($record3)."--1' 
			and (m.element_type = 'checkbox' or d.field_name not in (";
	foreach (array_keys($_POST) as $this_field)
	{
		if (strpos($this_field, "___") !== false) {
			$this_field_real = substr($this_field, 0, strpos($this_field, "___"));
			if (isset($Proj->metadata[$this_field_real])) {
				$this_field = $this_field_real;
			}
		}
		$sql .= "'$this_field',";
	}
	$sql = substr($sql, 0, -1) . "))";
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q)) {
		// Parse checkboxes correctly
		if ($row['element_type'] == "checkbox") {
			// Add default "0" values if they've not been added yet for each checkbox field
			foreach (array_keys(parseEnum($row['element_enum'])) as $key) {
				if (!isset($_POST[$row['field_name']][$key])) $_POST[$row['field_name']][$key] = "0";
			}
			// Now add this single checkbox value from the data table
			$_POST[$row['field_name']][$row['value']] = "1";
		// Normal non-checkbox fields
		} else {			
			// If a date[time][_seconds] field, then reformat data before saving (entering in other formats)
			if ($Proj->metadata[$row['field_name']]['element_type'] == 'text' && substr($Proj->metadata[$row['field_name']]['element_validation_type'], 0, 4) == 'date')
			{
				// Check type
				if (substr($Proj->metadata[$row['field_name']]['element_validation_type'], 0, 8) == 'datetime') {
					list ($thisdate, $thistime) = explode(" ", $row['value']);
				} else {
					$thisdate = $row['value'];
					$thistime = "";
				}
				if (substr($Proj->metadata[$row['field_name']]['element_validation_type'], -4) == '_dmy') {
					$row['value'] = trim(date_ymd2dmy($thisdate) . " " . $thistime);
				} elseif (substr($Proj->metadata[$row['field_name']]['element_validation_type'], -4) == '_mdy') {
					$row['value'] = trim(date_ymd2mdy($thisdate) . " " . $thistime);
				}
			}
			// Add to array
			$_POST[$row['field_name']] = $row['value'];
		}
	}
	
	// Loop through POST again to now overlay any checkbox field values by converting existing POST fields with triple underscore to sub-arrays
	foreach ($_POST as $key=>$value) 
	{
		$pos = strpos($key, "___");
		if ($pos !== false) {
			$this_field = substr($key, 0, $pos);
			if (isset($chkbox_fields[$this_field])) {
				// This is a checkbox field, so convert it
				$code = substr($key, $pos+3);
				$_POST[$this_field][$code] = $value;
				// Now remove the pre-converted keys
				unset($_POST[$key]);
			}
		}
	}
	
	// Loop through posted values to insert values to create new record
	$sql_all = array();
	$display = array();
	$query_failed = false;
	foreach ($_POST as $this_field=>$this_value) 
	{		
		// Checkbox fields only
		if (isset($chkbox_fields[$this_field])) {
			foreach ($this_value as $this_key=>$this_value_sub) {	
				// Only insert if value is "1" (checked)
				if ($this_value_sub) {
					$sql_all[] = $sql = "INSERT INTO redcap_data VALUES ($project_id, $event_id3, '" . prep($record3). "', '$this_field', '" . prep($this_key). "')";
					if (db_query($sql)) {
						$display[] = "$this_field($this_key) = checked";
					} else {
						$query_failed = true;
					}
				}
			}		
		// Regular non-checkbox fields
		} else {
			// If a date[time][_seconds] field, then check if we need to reformat data before saving (entering in other formats)
			if ($Proj->metadata[$this_field]['element_type'] == 'text' && substr($Proj->metadata[$this_field]['element_validation_type'], 0, 4) == 'date')
			{
				// Check type
				if (substr($Proj->metadata[$this_field]['element_validation_type'], 0, 8) == 'datetime') {
					list ($thisdate, $thistime) = explode(" ", $this_value);
				} else {
					$thisdate = $this_value;
					$thistime = "";
				}				
				if (substr($Proj->metadata[$this_field]['element_validation_type'], -4) == '_dmy') {
					$this_value = trim(date_dmy2ymd($thisdate) . " " . $thistime);
				} elseif (substr($Proj->metadata[$this_field]['element_validation_type'], -4) == '_mdy') {
					$this_value = trim(date_mdy2ymd($thisdate) . " " . $thistime);
				}
			}
			// Set sql
			$sql_all[] = $sql = "INSERT INTO redcap_data VALUES ($project_id, $event_id3, '" . prep($record3). "', '$this_field', '" . prep($this_value). "')";
			if (db_query($sql)) {
				$display[] = "$this_field = '$this_value'";
			} else {
				$query_failed = true;
			}
		}
	}
	
	if ($query_failed) {
		print "<p><font color=#800000><b>{$lang['global_09']}. {$lang['data_comp_tool_07']}</font></b>";
		include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
		exit;
	}
	
	// Set event_id for logging and Data Entry Trigger
	$_GET['event_id'] = $event_id3;
	
	//Logging
	log_event(implode(";\n",$sql_all),"redcap_data","insert",$record3,implode(",\n",$display),"Merge records");
	
	// DATA ENTRY TRIGGER: If the Data Entry Trigger is enabled, then send HTTP Post request to specified URL
	launchDataEntryTrigger();
}



// Set flag to compare ALL records/events instead of single pair of records/events
$compareAll = ($double_data_entry && isset($_POST['compare-all']) && $_POST['compare-all']);






//Decide which pulldowns to display for user to choose Study ID
if ($user_rights['group_id'] == "") {
	$group_sql  = ""; 
} else {
	$group_sql  = "and d.record in (" . pre_query("select record from redcap_data where project_id = $project_id and field_name = '__GROUPID__' and value = '".$user_rights['group_id']."'") . ")"; 
}
$rs_ids_sql = "select d.record, d.event_id from redcap_data d, redcap_events_metadata m, redcap_events_arms a 
			   where d.project_id = $project_id and a.project_id = d.project_id and a.arm_id = m.arm_id and d.field_name = '$table_pk' $group_sql 
			   and d.event_id = m.event_id order by abs(d.record), d.record, a.arm_num, m.day_offset, m.descrip";
$q = db_query($rs_ids_sql);
// Collect record names into array
$records  = array();
$alreadyMerged = array(); // Used for DDE only
while ($row = db_fetch_assoc($q)) 
{
	// Add to array
	$records[$row['record']][$row['event_id']] = $Proj->eventInfo[$row['event_id']]['name_ext'];
}
// DDE ONLY: Now loop through array and parse out 
if ($double_data_entry) 
{
	// Temp array
	$records2 = array();
	// Loop through all records
	foreach ($records as $this_record=>$this_event)
	{
		// Get real record name (i.e. w/o the --1 or --2 on the end)
		if (substr($this_record, -3) == '--1' || substr($this_record, -3) == '--2') {
			$this_record_real = substr($this_record, 0, -3);
		} else {
			$this_record_real = $this_record;
		}
		// Loop through the events for this record
		foreach ($this_event as $this_event_id=>$this_event_name)
		{
			// If both --1 and --2 records exist, then replace both with a single real record, else remove both
			if (isset($records[$this_record_real."--1"][$this_event_id]) && isset($records[$this_record_real."--2"][$this_event_id])) 
			{
				// Add to $records2 array
				$records2[$this_record_real][$this_event_id] = $Proj->eventInfo[$this_event_id]['name_ext'];
				// If record was merged, add to $alreadyMerged array
				if (isset($records[$this_record_real][$this_event_id]))
				{
					$alreadyMerged[$this_record_real][$this_event_id] = true;
				}
			}	
		}
	}
	// Swap arrays now
	$records = $records2;
	unset($records2);
}
// Loop through the record list and store as string for drop-down options
$id_dropdown = "";
foreach ($records as $this_record=>$this_event)
{
	foreach ($this_event as $this_event_id=>$this_event_name)
	{
		$id_dropdown .= "<option value='{$this_record}[__EVTID__]{$this_event_id}'>" 
					  . $this_record . ($longitudinal ? " - $this_event_name" : "") 
					  . (isset($alreadyMerged[$this_record][$this_event_id]) ? " - ".$lang['data_comp_tool_47'] : "")
					  . "</option>";
	}
}

// Give option to compare all DDE pairs of records on single page
$compareAllBtn = '';
if ($double_data_entry) 
{
	$disableCompAllBtn = (empty($records)) ? "disabled" : "";
	$compareAllBtn = RCView::div(array('style'=>'padding:5px 0;font-weight:normal;color:#777;'),
						"&mdash; {$lang['global_46']} &mdash;"
					 ) .
					 RCView::div('',
						RCView::input(array('type'=>'submit','name'=>'submit','value'=>$lang['data_comp_tool_45'],$disableCompAllBtn=>$disableCompAllBtn,'onclick'=>"$('#record1').val($('#record1 option:eq(1)').val()); $('input[name=\"compare-all\"]').val('1');"))
					 );
}


// Table to choose record (show ONLY 1 pulldown for true Double Data Entry comparison)
print "<form action=\"".PAGE_FULL."?pid=$project_id\" method=\"post\" enctype=\"multipart/form-data\" name=\"datacomp\" target=\"_self\">";
print "<table class='form_border'>
		<tr>
			<td class='label_header' style='padding:10px;'>
				$table_pk_label
			</td>
			<td class='label_header' style='padding:10px;' rowspan='2'>
				<input name='submit' type='submit' value='".cleanHtml($double_data_entry ? $lang['data_comp_tool_44'] : $lang['data_comp_tool_02'])."' onclick=\"
					if ($('#record1').val().length < 1" . (!$double_data_entry ? " || $('#record2').val().length < 1" : "") . ") {
						simpleDialog('".cleanHtml($lang['data_comp_tool_06'])."');
						return false;
					}
				\">
				$compareAllBtn
				<input type='hidden' name='compare-all' value='0'>
			</td>
		</tr>
		<tr>
			<td class='data' align='center' style='padding:15px;'>
				<select name='record1' id='record1' class='x-form-text x-form-field' style='padding-right:0;height:22px;'>
					<option value=''>--- {$lang['data_comp_tool_43']} ---</option>
					$id_dropdown";						
if (!$double_data_entry) 
{
	print  " 	</select> &nbsp;&nbsp;
				<select name='record2' id='record2' class='x-form-text x-form-field' style='padding-right:0;height:22px;'>
					<option value=''>--- {$lang['data_comp_tool_43']} ---</option>
					$id_dropdown";
}	
print  "		</select></td>";
print  "</tr>
		</table>";
print  "</form><br><br>";	

// If sumbitted values, use javascript to select the dropdown values
if ($_SERVER['REQUEST_METHOD'] == 'POST') 
{
	if (!$compareAll) {
		// pre-select the drop-down(s), but not if user clicked "compare all" button
		print  "<script type='text/javascript'>
				$(function(){
					$('#record1').val('{$_POST['record1']}');
					$('#record2').val('{$_POST['record2']}');
				});
				</script>";
	}	
	if (isset($_GET['create_new'])) 
	{	
		//Conclude with congratulatory text
		print 	"<h3><font color=#800000>{$lang['data_comp_tool_09']}</font></h3>" .
				"<p><b>Record <font color=#800000>$record3</font>"
				.($longitudinal ? " {$lang['data_entry_67']} <font color=#800000>".$Proj->eventInfo[$event_id3]['name_ext']."</font>" : "")."
				{$lang['data_comp_tool_08']} $record3" . "--1 and $record3" . "--2"
				.($longitudinal ? " {$lang['data_entry_67']} ".$Proj->eventInfo[$event_id3]['name_ext'] : "")."{$lang['period']}</b>";
		include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
		exit;
	}
}












###############################################################################################
# When records are selected for comparison, display side-by-side comparison in table
if (isset($_POST['submit']) || isset($_GET['merge'])) 
{
	// Reset some CSS in table for consistent viewing when selecting which record value to merge
	$display_string .= "<style type='text/css'>
						.data { padding: 5px; }
						.header { font-size: 7.5pt; }
						</style>";
	
	// PRINT PAGE button
	print  "<div style='text-align:right;max-width:700px;'>
				<button class='jqbuttonmed' onclick='printpage(this)'><img src='".APP_PATH_IMAGES."printer.png' class='imgfix'> Print page</button>
			</div>";
	
	// If only comparing a single pair of records/events
	if (!$compareAll) {
		list ($record1, $event_id1) = explode("[__EVTID__]", $_POST['record1']);
		$records = array($record1=>array($event_id1=>1));
	}
			
	// Retrieve all validation types
	$valTypes = getValTypes();
	
	// Loop counter
	$loopNum = 0;
	
	//print_array($records);
	
	// Loop through records
	foreach ($records as $record1=>$evts) 
	{
		// Retrieve the submitted record names and their corresponding event_ids
		if ($double_data_entry) {
			$record2 = $record1 . "--2";
			$record1 .= "--1";
		} else {
			list ($record2, $event_id2) = explode("[__EVTID__]", $_POST['record2']);
		}
		
		// Loop through events for this record
		foreach (array_keys($evts) as $event_id1) 
		{
			// Retrieve the submitted record names and their corresponding event_ids
			if ($double_data_entry) {
				$event_id2 = $event_id1;
			}
			
			// Check to make sure the user didn't select the same record twice	
			if ($record1."" === $record2."" && $event_id2 == $event_id1 && $record1 != "" && $record2 != "") {
				print "<hr size=1><p><font color=#800000><b>{$lang['data_comp_tool_10']} ($record1) {$lang['data_comp_tool_11']}</b></font><p><br>";
				if (!$compareAll) {
					include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
					exit;
				}
			}
			
			// Retrieve data values for record 1
			$sql = "select record, field_name, value from redcap_data where record = '".prep($record1)."' 
					and project_id = $project_id and event_id = $event_id1";
			$q = db_query($sql);
			$record1_data = eavDataArray($q, $chkbox_fields);
			
			// Retrieve data values for record 2
			$sql = "select record, field_name, value from redcap_data where record = '".prep($record2)."' 
					and project_id = $project_id and event_id = $event_id2";
			$q = db_query($sql);
			$record2_data = eavDataArray($q, $chkbox_fields);
			
			// Retrieve metadata fields that are only relevent here for data comparison (only get fields that we have data for)
			$metadata_fields_rec1rec2 = array_unique(array_merge(array_keys($record1_data[$record1]), array_keys($record2_data[$record2])));
			$metadata_fields = array();
			foreach ($Proj->metadata as $this_field=>$row) {
				if (!in_array($this_field, $metadata_fields_rec1rec2)) continue;
				$metadata_fields[$this_field] = $row;
			}
			
			// Initialize string to gather HTML for entire table display
			$display_string = "<hr size=1>";
			
			// Display comparison table instructions
			if (!isset($_GET['merge'])) {
				$display_string .= "<p style='color:#000066;'>
										<b>{$lang['data_comp_tool_46']} <span style='color:#800000;font-size:14px;'>$record1</span> {$lang['global_43']} 
										<span style='color:#800000;font-size:14px;'>$record2</span>" .
										($longitudinal ? " {$lang['data_entry_67']} <span style='color:#800000;'>".$Proj->eventInfo[$event_id1]['name_ext']."</span>" : "") . 
										"{$lang['period']}</b><br><br>
										{$lang['data_comp_tool_16']} <b>$record1</b> 
										{$lang['global_43']} <b>$record2</b>".$lang['period']." 
										{$lang['data_comp_tool_17']}
									</p>";
			}
			
			//Default flag to show the merged record, if it exists.
			$show_merged_record = false;
			
			if ($double_data_entry && !isset($_GET['merge'])) {		

				//Check to see if the record has been created before. If so, then stop here.
				$record3 = substr($record1, 0, -3);
				$event_id3 = $event_id1;
				$sql = "select distinct record from redcap_data where project_id = $project_id and record = '".prep($record3)."' and field_name = '$table_pk'";
				if ($longitudinal) $sql .= " and event_id = $event_id3";
				$q = db_query($sql);
				if (db_num_rows($q) > 0) {
					//The merged record already exists. Do not allow a merge here.
					$display_string .= "<p style='color:#000066;'>
											<b>{$lang['global_02']}:</b> {$lang['data_comp_tool_18']} <b>$record3</b>{$lang['data_comp_tool_19']}
										</p>";
					//Set value to display the merged record, since it exists.
					$show_merged_record = true;
					// Retrieve data values for record 2
					$sql = "select record, field_name, value from redcap_data where record = '".prep($record3)."' and project_id = $project_id
							and event_id = $event_id3";
					$q = db_query($sql);
					$record3_data = eavDataArray($q, $chkbox_fields);
					
				} else {			
					
					//Give link for merging the two records
					$display_string .= "<form action='" . PAGE_FULL . "?pid=$project_id&merge=1' method='post' name='merge{$loopNum}'>
										<p style='color:#000066;'>
											<b>{$lang['data_comp_tool_20']}</b><br>
											{$lang['data_comp_tool_21']}
											<a href='javascript:;' style='text-decoration:underline;color:#800000;' onclick=\"
												document.merge{$loopNum}.submit();
												return false;
											\">{$lang['data_comp_tool_22']}</a>".$lang['period']."
											<input type='hidden' name='record1' value='".substr($record1, 0, -3)."[__EVTID__]{$event_id1}'>
										</p>
										</form>";
				}
								  
			} elseif ($double_data_entry && isset($_GET['merge'])) {	
			
				//Give instructions for merging the two records now that the third record column is displayed.
				$display_string .= "<p style='color:#000066;'>
										<b>{$lang['data_comp_tool_23']}</b><br><br>
										{$lang['data_comp_tool_24']}<br><br>
										{$lang['data_comp_tool_25']}
									</p>";
				
			}
			
			//Begin building table and build headers
			$display_string .= "<div style='max-width:700px;'>
								<form action='".PAGE_FULL."?pid=$project_id&event_id=$event_id1&create_new=1' method='post' enctype='multipart/form-data' name='create_new' target='_self'>
								<table class='form_border'>
								<tr>
									<td class='header' style='font-size:8pt;text-align:center;' rowspan=2>{$lang['data_comp_tool_26']} <i>{$lang['data_comp_tool_27']}</i></td>
									<td class='header' style='font-size:8pt;text-align:center;' rowspan=2>{$lang['global_12']}</td>";
			
			//If merging the two records, show different text for the last table header
			if (isset($_GET['merge'])) {
				$display_string .= "<td class='header' style='font-size:8pt;text-align:center;' colspan=3>
										<font color=#800000>&nbsp;<br/>
										{$lang['data_comp_tool_29']}<br/>
										&nbsp;</font>
									</td>
								</tr>";
			} else {
				//Determine if need to show the merged record
				$display_string .= "<td class='header' style='font-size:8pt;text-align:center;' colspan='" . ($show_merged_record ? 3 : 2) . "'>
										<font color=#800000>$table_pk_label</font>
									</td>
								</tr>";
			}
			
			
			$display_string .= "<tr>
									<td class='data' valign='bottom' style='text-align:center;color:#000066;'>
										<b>$record1</b>" . ($longitudinal ? "<div style='font-size:11px;'>".$Proj->eventInfo[$event_id1]['name_ext']."</div>" : "") . "
									</td>
									<td class='data' style='text-align:center;color:#000066;'>
										<b>$record2</b>" . ($longitudinal ? "<div style='font-size:11px;'>".$Proj->eventInfo[$event_id2]['name_ext']."</div>" : "") . "
									</td>";
			
			//Determine if need to show the merged record
			if ($show_merged_record) {
				$display_string .= "<td class='data' valign='bottom' style='text-align:center;color:#000066;'>
										<b>$record3<br>{$lang['data_comp_tool_31']}</b>" . ($longitudinal ? "<div style='font-size:11px;'>".$Proj->eventInfo[$event_id3]['name_ext']."</div>" : "") . "
									</td>";
			}
			
			//Add extra column to table if preparing to merge the two records
			if (isset($_GET['merge'])) {
				$display_string .= "<td class='data' valign='bottom' style='text-align:center;'>
										<font color=#000066><b>{$lang['data_comp_tool_32']}</b></font>
									</td>";
			}
			
			$display_string .= "</tr>";
			
			// Initialize string for capturing table row of HTML
			$diff = "";
			
			// print_array($record1_data);
			// print_array($record2_data);
			// print_array($metadata_fields);
			
			//Render rows: Loop through all fields being compared
			foreach ($metadata_fields as $field_name=>$attr) {
				
				// Skip record id field (not applicable)
				if ($field_name == $table_pk) continue;
				
				// Get field attributes
				$element_label = $attr['element_label'];
				$form_name = $attr['form_name'];
				$element_type = $attr['element_type'];
				$select_choices = $attr['element_enum'];
				
				// Create array for possible sub-looping through multiple values for single field
				$subloop = array();
				
				
				// If field has multiple values associated with a single field (e.g. checkbox), then causing multiple looping for that field
				if ($element_type == "checkbox") {
					// Create array to hold labels for this checkbox field
					$checkbox_labels = parseEnum($Proj->metadata[$field_name]['element_enum']);
					// Loop using record1's values (but doesn't matter because ALL checkboxes are added to record1 and record2 arrays by default (to cover default "0" values)
					foreach (array_keys($record1_data[$record1][$field_name]) as $this_code) {
						// Create new field name with triple underscore + coded value
						$this_field = $field_name . "___" . $this_code;
						// Set with multiple values
						$subloop[1][$this_field] = $record1_data[$record1][$field_name][$this_code];
						$subloop[2][$this_field] = $record2_data[$record2][$field_name][$this_code];	
						if ($show_merged_record) {
							$subloop[3][$this_field] = $record3_data[$record3][$field_name][$this_code];
						}
					}
				// If field only has one data point (normal)
				} else {
					// Set with single values
					$subloop[1][$field_name] = $record1_data[$record1][$field_name];
					$subloop[2][$field_name] = $record2_data[$record2][$field_name];	
					if ($show_merged_record) {
						$subloop[3][$field_name] = $record3_data[$record3][$field_name];
					}
				}
				
				// Loop through all sub-fields, if a checkbox, else it'll just loop once
				foreach (array_keys($subloop[1]) as $sub_field_name) 
				{
					// Set values for this subloop
					$this_val1 = $subloop[1][$sub_field_name];
					$this_val2 = $subloop[2][$sub_field_name];	
					if ($show_merged_record) {
						$this_val3 = $subloop[3][$sub_field_name];
					}
								
					// If field is Text or Notes field type, then remove line breaks and minimize spaces for proper comparison of characters
					if ($element_type == 'text' || $element_type == 'textarea') {
						$this_val1 = remBr($this_val1);
						$this_val2 = remBr($this_val2);	
						if ($show_merged_record) {
							$this_val3 = remBr($this_val3);
						}
						// If a date[time][_seconds] field, then check if we need to reformat data before displaying (entered in other formats)
						if ($Proj->metadata[$sub_field_name]['element_type'] == 'text' && substr($Proj->metadata[$sub_field_name]['element_validation_type'], 0, 4) == 'date')
						{
							// Check type
							if (substr($Proj->metadata[$sub_field_name]['element_validation_type'], 0, 8) == 'datetime') {
								list ($thisdate1, $thistime1) = explode(" ", $this_val1);
								list ($thisdate2, $thistime2) = explode(" ", $this_val2);
								if ($show_merged_record) list ($thisdate3, $thistime3) = explode(" ", $this_val3);
							} else {
								$thisdate1 = $this_val1;
								$thistime1 = "";
								$thisdate2 = $this_val2;
								$thistime2 = "";
								if ($show_merged_record) {
									$thisdate3 = $this_val3;
									$thistime3 = "";
								}
							}				
							if (substr($Proj->metadata[$sub_field_name]['element_validation_type'], -4) == '_dmy') {
								$this_val1 = trim(date_ymd2dmy($thisdate1) . " " . $thistime1);
								$this_val2 = trim(date_ymd2dmy($thisdate2) . " " . $thistime2);
								if ($show_merged_record) $this_val3 = trim(date_ymd2dmy($thisdate3) . " " . $thistime3);
							} elseif (substr($Proj->metadata[$sub_field_name]['element_validation_type'], -4) == '_mdy') {
								$this_val1 = trim(date_ymd2mdy($thisdate1) . " " . $thistime1);
								$this_val2 = trim(date_ymd2mdy($thisdate2) . " " . $thistime2);
								if ($show_merged_record) $this_val3 = trim(date_ymd2mdy($thisdate3) . " " . $thistime3);
							}
						}
					}
					
					//print out values if there is a difference bewteen data for each entered id				
					if ($this_val1 != $this_val2) {
							
						// Remove any illegal characters that can cause javascript to crash
						$this_val1 = $this_val1_orig = htmlspecialchars(html_entity_decode(html_entity_decode($this_val1, ENT_QUOTES), ENT_QUOTES), ENT_QUOTES);
						$this_val2 = $this_val2_orig = htmlspecialchars(html_entity_decode(html_entity_decode($this_val2, ENT_QUOTES), ENT_QUOTES), ENT_QUOTES);
						
						// For checkboxes, convert each choice to advcheckbox enum
						if ($element_type == 'checkbox') {
							$select_choices = $attr['element_enum'] = "0, Unchecked \n 1, Checked";
						}
						// Deal specially with TF and YN fields
						elseif ($element_type == 'truefalse') $attr['element_enum'] = TF_ENUM;
						elseif ($element_type == 'yesno') $attr['element_enum'] = YN_ENUM;
						
						// Process values for SELECT boxes (and ADVCHECKBOXes) to display the text AND the numerical value
						if ($element_type == 'yesno' || $element_type == 'truefalse' || $element_type == 'select' || $element_type == 'checkbox' || $element_type == 'advcheckbox' || $element_type == 'radio') {
							
							// Parse the enum to store as array to pull the labels later
							$select_text = parseEnum($attr['element_enum']);
							// Set newly formatted values for display
							if ($this_val1 != "") $this_val1 = $select_text[$this_val1] . " <i>($this_val1)</i>";				
							if ($this_val2 != "") $this_val2 = $select_text[$this_val2] . " <i>($this_val2)</i>";	
							if ($show_merged_record) {					
								if ($this_val3 != "") $this_val3 = $select_text[$this_val3] . " <i>($this_val3)</i>";
							}				
						}			
						
						// For checkboxes, provide extra label of field_name + triple underscore + coding
						if ($element_type == 'checkbox')
						{
							$disp_field_name = strtolower($field_name . " &raquo; $sub_field_name");
							$disp_choice_code = substr($sub_field_name, strrpos($sub_field_name, "___")+3);
							$disp_element_label = $element_label . " (Choice =  <b>" . $checkbox_labels[$disp_choice_code] . "</b>)";
						}
						else
						{
							$disp_field_name = $field_name;
							$disp_element_label = $element_label;
						}
						
						// Render static row of two values
						if (!isset($_GET['merge'])) {
							
							$diff .=  	"<tr>
											<td class='data' style='padding:2px 5px;'>
												$disp_element_label <i>($disp_field_name)</i>
											</td>
											<td class='data' style='padding:2px 5px;'>
												".$Proj->forms[$form_name]['menu']."
											</td>
											<td valign='top' class='data' style='padding:2px 5px;cursor:pointer;' onclick=\"window.open('" . APP_PATH_WEBROOT . "DataEntry/index.php?pid=$project_id&page=$form_name&id=$record1&event_id=$event_id1&fldfocus=$field_name#$field_name-tr','_blank');\">
												<span class=\"compare\" style='color:#800000;'>$this_val1</span>
											</td>
											<td valign='top' class='data' style='padding:2px 5px;cursor:pointer' onclick=\"window.open('" . APP_PATH_WEBROOT . "DataEntry/index.php?pid=$project_id&page=$form_name&id=$record2&event_id=$event_id2&fldfocus=$field_name#$field_name-tr','_blank');\">
												<span class=\"compare\" style='color:#800000;'>$this_val2</span>
											</td>";
							if ($show_merged_record) {					
								$diff .=   "<td valign='top' class='data' style='padding:2px 5px;cursor:pointer' onclick=\"window.open('" . APP_PATH_WEBROOT . "DataEntry/index.php?pid=$project_id&page=$form_name&id=$record3&event_id=$event_id3&fldfocus=$field_name#$field_name-tr','_blank');\">
												<span class=\"compare\" style='color:#800000;'>$this_val3</span>
											</td>";
							}
						
						// Render row with ability to select values for merge
						} elseif (isset($_GET['merge'])) {				 
							
							// Print out table row (no links)
							$diff .=   "<tr>
											<td class='data' valign='center'>
												$disp_element_label <i>($disp_field_name)</i>
											</td>
											<td class='data'>
												".$Proj->forms[$form_name]['menu']."
											</td>
											<td valign='top' class='header' valign='bottom' id='{$sub_field_name}___RAD1'>
												<p style='text-align:center;'>
													<input name='{$sub_field_name}___RADradio' checked value='' type='radio' onclick=\"
														dataCmpChk(1,'$sub_field_name','$this_val1_orig');
													\">
													<input name='{$sub_field_name}___RAD1' value='$this_val1_orig' type='hidden'>
												</p>
												<span class='compare' style='color:#800000;'>$this_val1</span>
											</td>
											<td valign='top' class='data' valign='bottom' id='{$sub_field_name}___RAD2'>
												<p style='text-align:center;'>
													<input name='{$sub_field_name}___RADradio' value='' type='radio' onclick=\"
														dataCmpChk(2,'$sub_field_name','$this_val2_orig');
													\">
													<input name='{$sub_field_name}___RAD2' value='$this_val2_orig' type='hidden'>
												</p>
												<span class='compare' style='color:#800000;'>$this_val2</span>
											</td>
											<td valign='top' class='data' valign='bottom' id='{$sub_field_name}___RAD3'>";
							
							//Build the field like it would appear normally on the data entry page
							switch($element_type) {
							
								case 'text':
									//Get validation info, if any.
									$element_validation_type = $attr['element_validation_type'];
									$element_validation_min = $attr['element_validation_min'];
									$element_validation_max = $attr['element_validation_max'];
									$element_validation_checktype = $attr['element_validation_checktype'];
									$validation_string = "";							
									if ($element_validation_type != "") 
									{
										$validation_string = "onblur=\"redcap_validate(this,'$element_validation_min','$element_validation_max','$element_validation_checktype','".convertLegacyValidationType($element_validation_type)."',1);document.create_new.$sub_field_name.value=document.create_new.$sub_field_name" . "___RAD3.value;\"";
									}						
									$diff .= "<input name=\"$sub_field_name" . "___RAD3\" size=\"30\" value=\"\" type=\"text\" $validation_string disabled=\"disabled\" onchange=\"document.create_new.$sub_field_name.value=document.create_new.$sub_field_name" . "___RAD3.value\">";
									break;
								
								case 'yesno':
									$select_choices = YN_ENUM;
								case 'truefalse':
									if ($element_type != 'yesno') $select_choices = TF_ENUM;
								case 'select':
								case 'radio':
								case 'checkbox':
								case 'advcheckbox':
									$diff .= "<select name=\"$sub_field_name" . "___RAD3\" disabled=\"disabled\" onchange=\"document.create_new.$sub_field_name.value=document.create_new.$sub_field_name" . "___RAD3.value\"><option value=\"\"></option>";
									foreach (parseEnum($select_choices) as $key=>$value) {
										$diff .= "<option value='$key'>$value</option>";
									}	
									$diff .= "</select>";
									break;
								
								case 'textarea':
									$diff .= "<textarea name=\"$sub_field_name" . "___RAD3\" style='width:180px;height:100px' disabled=\"disabled\" onchange=\"document.create_new.$sub_field_name.value=document.create_new.$sub_field_name" . "___RAD3.value\"></textarea>";
									break;		
									
							}
							
							//Set the real field (which is hidden) with the value of the first record by default
							$diff .=   "<p>
											<input name='$sub_field_name" . "___RADradio' value='' type='radio' onclick=\"
												dataCmpChk(3,'$sub_field_name','$this_val3');
											\">
											<input name='$sub_field_name' value='$this_val1_orig' type='hidden'>
										</p>
									</td>
								</tr>";
									 
						}
						
						$diff .= "</tr>";
					}
				
				}
				
			}
			
			
			// Display table if there were any differences.
			if ($diff != "") {
			
				$display_string .= $diff;
				
				if (isset($_GET['merge'])) {
					$display_string .= "<tr>
											<td class='data' colspan=4>
											</td>
											<td class='data' align='center'>
												<input name='$table_pk' type='hidden' value='$record1'><br>
												<input type='button' value='{$lang['data_comp_tool_33']}' onclick=\"document.create_new.submit();\">
												<a href='".PAGE_FULL."?pid=$project_id' style='margin-left:20px;text-decoration:underline;font-size:11px;'>{$lang['global_53']}</a>
												<br>&nbsp;
											</td>
										</tr>";
				}
				
				$display_string .= "</table></form></div><br><br>";
				
				print $display_string;
			
			
			// If no differences, then give message.
			} else {
			
				print  "<hr size=1><font color=#800000><b>{$lang['data_comp_tool_34']} $record1 {$lang['global_43']} $record2 
						{$lang['data_comp_tool_35']}</b></font> ";
				//Determine if merged file exists
				if (!$show_merged_record) {
					if ($double_data_entry) {
						print "<p>{$lang['data_comp_tool_37']} " . substr($record1, 0, -3) . $lang['data_comp_tool_39'] . "</p>
							  <form action=\"".PAGE_FULL."?pid=$project_id&event_id=$event_id1&create_new=1\" method=\"post\" enctype=\"multipart/form-data\" target=\"_self\">
							  <input name=\"$table_pk\" type=\"hidden\" value=\"$record1\">
							  <input type=\"submit\" value=\"{$lang['data_comp_tool_38']} " . substr($record1, 0, -3) . "\"></form><p><br>";
					}
				} else {
					print "<font color=#800000><b>{$lang['data_comp_tool_41']} " . substr($record1, 0, -3) . " {$lang['data_comp_tool_42']}</b></font>";
				}
				
			}
			// Increment counter
			$loopNum++;
		}
	}
}


include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
