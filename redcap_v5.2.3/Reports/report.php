<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
require_once APP_PATH_DOCROOT . 'Reports/functions.php';

// Increase memory limit so large data sets do not crash and yield a blank page


if (PAGE != "ProjectGeneral/print_page.php") include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';



if (isset($_GET['query_id'])) {
	if (!is_numeric($_GET['query_id'])) exit("{$lang['global_09']}!");
}
if (isset($_GET['id'])) {
	if (!is_numeric($_GET['id'])) exit("{$lang['global_09']}!");
}




// Display page header and begin building table
$html_string = "";

//Get the Field Labels for each Field Name for substituting as the table headers
//Also get Select Choices for displaying number value and text value
$field_labels = array();
$select_choices = array();	
$is_enum = array();
$field_form = array();
foreach ($Proj->metadata as $row)
{
	$field_labels[$row['field_name']] = filter_tags(label_decode($row['element_label']));
	$field_form[$row['field_name']] = $row['form_name'];
	$form_status_names[$row['form_name'] . "_complete"] = "";
	if ($row['element_type'] == 'yesno' || $row['element_type'] == 'truefalse' || $row['element_type'] == 'sql' || $row['element_type'] == 'select' || $row['element_type'] == 'radio' || $row['element_type'] == 'advcheckbox' || $row['element_type'] == 'checkbox') {
		$is_enum[$row['field_name']] = true;
		// Convert sql field types' query result to an enum format
		if ($row['element_type'] == "sql")
		{
			$row['element_enum'] = getSqlFieldEnum($row['element_enum']);
		} 
		elseif ($row['element_type'] == "yesno")
		{
			$row['element_enum'] = YN_ENUM;
		} 
		elseif ($row['element_type'] == "truefalse")
		{
			$row['element_enum'] = TF_ENUM;
		}		
		$select_choices[$row['field_name']] = parseEnum($row['element_enum']);
	} else {
		$is_enum[$row['field_name']] = false;
	}
}








/** 
 * OLD CUSTOM REPORTS (LEGACY)
 */
if (isset($_GET['id'])) {

	$custom_report_query = $custom_report_sql[$_GET['id']];
	$custom_report_title = $custom_report_menu[$_GET['id']];
	
	$QQuery = db_query($custom_report_query);
	$num_rows = db_num_rows($QQuery);
	$num_cols = db_num_fields($QQuery);			
	
	$html_string .= "<p><b>{$lang['custom_reports_02']}&nbsp; <span style='font-size:15px;color:#800000'>$num_rows</span></b><br>";
	
	$q = db_query("select count(distinct(record)) as count from redcap_data where project_id = $project_id");
	$rowrec = db_fetch_array($q);
	$num_records = $rowrec['count'];
	$html_string .= "{$lang['custom_reports_03']}&nbsp; $num_records</p><br>";
	
	$html_string .= "<table class='dt2' style='font-family:Verdana;font-size:11px;'>
						<tr class='grp2'><td colspan='$num_cols'>$custom_report_title</td></tr>
						<tr class='hdr2' style='white-space:normal;'>";
							
	if ($num_rows > 0) {
		
		// Display column names as table headers
		for ($i = 0; $i < $num_cols; $i++) {
			
			$this_fieldname = db_field_name($QQuery,$i);
			
			//Make sure that this is a real field and isn't a special field (i.e. count())
			if (isset($field_form[$this_fieldname])) {
				
				//Display the Label and Field name
				$html_string .= "<td style='padding:5px;'>".$field_labels[$this_fieldname] . 
					  "<div style='color:#777;font-size:11px;font-weight:normal;'>($this_fieldname)</div></td>";
					  
				//Check to make sure that user has access rights to the form on which that this field is located. If not, do not show table.
				if ($user_rights['forms'][$field_form[$this_fieldname]] == '0') {				
					print  "<h3>$app_title<hr size=1><font color=#800000>$custom_report_title</font></h3><br>
							<div class='red'><img src='".APP_PATH_IMAGES."exclamation.png' class='imgfix'> <b>{$lang['global_05']}</b><br><br>
							{$lang['custom_reports_05']}
							</div>";
					include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
					exit;
				}
			} else {
				//Display the "fieldname" twice
				$html_string .= "<td style='padding:5px;'>".$this_fieldname . 
					  "<div style='color:#777;font-size:11px;font-weight:normal;'>($this_fieldname)</div></td>";
			}
		}			
		$html_string .= "</tr>";	
		
		// Display each table row
		$j = 1;
		while ($row = db_fetch_array($QQuery)) {
			$class = ($j%2==1) ? "odd" : "even";
			$html_string .= "<tr class='$class notranslate'>";			
			for ($i = 0; $i < $num_cols; $i++) {
				$this_fieldname = db_field_name($QQuery,$i);
				$this_value = $row[$i];
				//For a radio, select, or advcheckbox, show both num value and text
				if ($is_enum[$this_fieldname]) { 
					$html_string .= "<td style='padding:3px;border-top:1px solid #CCCCCC;font-size:11px;'>".$select_choices[$this_fieldname][$this_value];
					if (trim($this_value) != "") $html_string .= " <span style='color:#777;'>($this_value)</span>";
					$html_string .= "</td>";				
				//Display normally as raw data
				} else {
					$html_string .= "<td style='padding:3px;border-top:1px solid #CCCCCC;font-size:11px;'>".$row[$i]."</td>";
				}
			}			
			$html_string .= "</tr>";
			$j++;
		}
		
		$html_string .= "</table>";
		
	} else {
	
		for ($i = 0; $i < $num_cols; $i++) {
				
			$this_fieldname = db_field_name($QQuery,$i);
				
			//Display the Label and Field name
			$html_string .= "<td style='padding:5px;'>".$field_labels[$this_fieldname] . 
				"<div style='color:#777;font-size:11px;font-weight:normal;'>($this_fieldname)</div></td>";
		}
		
		$html_string .= "</tr><tr><td colspan='$num_cols' style='padding:10px;color:#800000;'>{$lang['custom_reports_06']}</td></tr></table>";
		
	}



	
	
	
	
	
	
	
	
	
	
	
	
	

/**
 * REPORT BUILDER-TYPE REPORTS
 */
} elseif (isset($_GET['query_id'])) {
	
	// Build array of Event names for storing for later use
	$q = db_query("select m.event_id, m.descrip from redcap_events_metadata m, redcap_events_arms a where a.arm_id = m.arm_id and a.project_id = $project_id");
	$event_names = array();
	while ($row = db_fetch_assoc($q)) {
		$event_names[$row['event_id']] = $row['descrip'];
	}
	
	// Obtain first form name to give link for record identifier fields
	$firstFormArray = array_pop($Proj->eventsForms);
	$firstForm = $firstFormArray[0];
	// For longitudinal, create array for quickly determing arm_num from event_id
	$eventArm = array();
	foreach ($Proj->events as $this_arm_num=>$this_event_info)
	{
		foreach ($this_event_info['events'] as $this_event_id=>$nothing)
		{
			$eventArm[$this_event_id] = $this_arm_num;
		}
	}
	
	
	if (PAGE != "ProjectGeneral/print_page.php") 
	{
		// Give user option to download report in CSV/XML format if user has export rights
		if ($user_rights['data_export_tool'] != '0') 
		{
			$html_string .= "<div style='text-align:right;padding: 0px 10px 0px 10px;font-size:11px;max-width:700px;'>
							<span style='border-bottom:1px solid #aaa;padding-bottom:4px;'>
							<b>{$lang['custom_reports_07']} </b>";
			//CSV link
			$html_string .= "&nbsp; <img src='".APP_PATH_IMAGES."xls.gif' class='imgfix'> 
							<a class='notranslate' href='".APP_PATH_WEBROOT."Reports/report_export.php?pid=$project_id&fileid=report_csv&query_id=".$_GET['query_id']."' 
							style='font-size:11px;color:#004000'>Microsoft Excel (CSV)</a> ";
			//XML link
			$html_string .= "&nbsp; <img src='".APP_PATH_IMAGES."xml.png' class='imgfix'> 
							<a class='notranslate' href='".APP_PATH_WEBROOT."Reports/report_export.php?pid=$project_id&fileid=report_xml&query_id=".$_GET['query_id']."' 
							style='font-size:11px;color:#DD5105'>XML</a>";
			$html_string .= "</span></div>";
		}
		// Give user option to PRINT PAGE or EDIT REPORT, if needed
		$html_string .= "<div style='text-align:right;padding: 7px 10px 0px 10px;max-width:700px;'>
							<img src='".APP_PATH_IMAGES."printer.png' class='imgfix'>
							<a href='javascript:;' style='font-size:11px;' onclick=\"
								window.open(app_path_webroot+'ProjectGeneral/print_page.php?pid=$project_id&query_id=".$_GET['query_id']."','myWin','width=850, height=800, toolbar=0, menubar=1, location=0, status=0, scrollbars=1, resizable=1');
							\">{$lang['graphical_view_15']}</a>
							&nbsp;
							<img src='".APP_PATH_IMAGES."card_pencil.png' class='imgfix'> 
							<a href='".APP_PATH_WEBROOT."Reports/report_builder.php?pid=$project_id&query_id=".$_GET['query_id']."' 
								style='font-size:11px;'>{$lang['custom_reports_08']}</a>
						</div>";
	}
					
	$this_query_array = $query_array[$_GET['query_id']]; //$query_array originates from an eval of $report_builder in Config/init_project.php
	$custom_report_title = $this_query_array['__TITLE__'];
	
	// Ensure that all fields in report still exists, else remove them from being rendered (would cause errors)
	foreach (array_keys($this_query_array) as $this_field)
	{
		if (substr($this_field, 0, 2) != "__" && !isset($Proj->metadata[$this_field]))
		{
			unset($this_query_array[$this_field]);
		}
	}
	
	// Get raw sorted data
	list ($eav_arr, $query_fields, $num_cols) = buildReport($this_query_array);
	
	$html_string .= "<p><b>{$lang['custom_reports_02']}&nbsp; <span style='font-size:15px;color:#800000'>".count($eav_arr)."</span></b><br>";
	
	//If user is in a Data Access Group, only show records associated with that group
	$group_sql = "";
	if ($user_rights['group_id'] != "") {
		$group_sql = "where record in (" . pre_query("select record from redcap_data where field_name = '__GROUPID__' and 
					  value = '".$user_rights['group_id']."' and project_id = '$project_id'") . ")";
	}
	
	// Count total existing records
	$sql = "select count(1) from (select distinct d.record, d.event_id from redcap_data d, redcap_events_metadata m, 
			redcap_events_arms a where d.project_id = $project_id and d.project_id = a.project_id and a.arm_id = m.arm_id and 
			d.event_id = m.event_id and d.field_name = '$table_pk' $group_sql 
			".($longitudinal ? "" : "and d.event_id = ".$Proj->firstEventId).") as x";
	$count_records_queried = db_result(db_query($sql), 0);
	$html_string .= "{$lang['custom_reports_03']}&nbsp; $count_records_queried
					&nbsp;" . ($longitudinal ? "<span style='color:#777;font-size:11px;font-family:tahoma,arial;'>{$lang['custom_reports_09']}</span>" : "") . "
					</p><br>";
	
	// Create array of list of checkbox fields
	if (!isset($chkbox_fields) || $chkbox_fields == null) {
		$sql = "select field_name, element_enum from redcap_metadata where project_id = " . PROJECT_ID . " and element_type = 'checkbox'";
		$chkboxq = db_query($sql);
		$chkbox_fields = array();
		while ($row = db_fetch_assoc($chkboxq)) {
			// Add field to list of checkboxes and to each field add checkbox choices
			foreach (parseEnum($row['element_enum']) as $this_value=>$this_label) {
				$chkbox_fields[$row['field_name']][$this_value] = $this_label;	
				// Increment the count for columns
				$num_cols++;
			}
			// Reduce count for columns by 1 since the field itself was counted originally
			$num_cols--;
		}	
	}
	
	// Begin rendering table
	$html_string .= "<table class='dt2' style='font-family:Verdana;font-size:11px;'>
						<tr class='grp2'>
							<td colspan='" . ($longitudinal ? ($num_cols+1) : $num_cols) . "'>
								$custom_report_title
							</td>
						</tr>";
	
	// RENDER HEADERS
	$html_string .= "	<tr class='hdr2 notranslate' style='white-space:normal;'>";
	foreach ($query_fields as $this_fieldname) 
	{		
		//Display the Label and Field name (non-checkbox fields)
		if (!isset($chkbox_fields[$this_fieldname])) {
			$html_string .= "	<td style='padding:5px;'>
									{$field_labels[$this_fieldname]}
									<div class='rprthdr'>($this_fieldname)</div>
								</td>";
		//Display the Label and Field name (checkbox fields only)
		} else {
			foreach ($chkbox_fields[$this_fieldname] as $this_code=>$this_label) {
				$html_string .= "	<td style='padding:5px;'>
										{$field_labels[$this_fieldname]}<br>({$lang['custom_reports_10']} = '$this_label')
										<div class='rprthdr'>({$this_fieldname}___{$this_code})</div>
									</td>";
			}
		}
							
		// If Longitudinal and if Study ID is used in report (and this header field is Study ID field), 
		// get name of Event tied to this data point and only display
		if ($longitudinal && $this_fieldname == $table_pk) {
			$html_string .= "<td style='padding:5px;'>
								{$lang['global_10']}
								<div class='rprthdr'>(redcap_event_name)</div>
							</td>";
		}
			  
		//Check to make sure that user has access rights to the form on which that this field is located. If not, do not show table.
		if ($user_rights['forms'][$field_form[$this_fieldname]] == "0") {
			print  "<h3><font color=#800000>$custom_report_title</font></h3><br>
					<div class='red'><img src='".APP_PATH_IMAGES."exclamation.png' class='imgfix'> <b>{$lang['global_05']}</b><br><br>
					{$lang['custom_reports_05']}
					</div>";
			include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
			exit;
		}
		
	}			
	$html_string .= "	</tr>";	
	
	// RENDER ROWS
	$j = 1;
	foreach ($eav_arr as $this_key=>$this_key_arr) 
	{
		$class = ($j%2==1) ? "odd" : "even";
		$html_string .= "<tr class='$class notranslate'>";			
		foreach ($query_fields as $this_fieldname) 
		{
			$this_value = isset($this_key_arr[$this_fieldname]) ? $this_key_arr[$this_fieldname] : "";
			
			// MC Fields Only: For a radio, select, or advcheckbox, show both num value and text
			if ($is_enum[$this_fieldname] == "1") 
			{
				//If a form status field and data doesn't exist yet, set value to 0 (incomplete).
				if (isset($form_status_names[$this_fieldname]) && $this_value == '') 
				{
					if ($longitudinal) {
						// Longitudinal: Only set default value IF form is designated to this event
						list ($this_event_id, $this_record) = explode("|", $this_key, 2);
						if (in_array($Proj->metadata[$this_fieldname]['form_name'], $Proj->eventsForms[$this_event_id])) {
							// Since it is designated for this event, set default of 0
							$this_value = 0;
						}
					} elseif ($this_value == "") {
						// Classic
						$this_value = 0;
					}					
				}
				//Render text and numerical value for drop-downs/radios
				if (!is_array($this_key_arr[$this_fieldname])) 
				{
					$html_string .= "<td class='rprt'>";
					$html_string .= filter_tags(label_decode($select_choices[$this_fieldname][$this_value]));
					if (trim($this_value) != "") {
						$html_string .= " <span style='color:#777;'>($this_value)</span>";
					}
					$html_string .= "</td>";
				}
				// CHECKBOX: Render text and numerical values for checkboxes
				else {
					// Set flag for blank checkbox value
					$blankCheckVal = "Unchecked";
					// Longitudinal: Only set default value IF form is designated to this event
					if ($longitudinal) {
						list ($this_event_id, $this_record) = explode("|", $this_key, 2);
						if (!in_array($Proj->metadata[$this_fieldname]['form_name'], $Proj->eventsForms[$this_event_id])) {
							$blankCheckVal = "";
						}
					}
					// Loop through each checkbox choice and render
					foreach ($this_key_arr[$this_fieldname] as $this_code=>$this_checked) 
					{
						$html_string .= "<td class='rprt'><i>"
									 .  ($this_checked ? "Checked" : $blankCheckVal) 
									 .  "</i></td>";
					}
				}
			}
			//Display normally as raw data
			else {
				
				// If line breaks exist, replace with <br>
				if (strpos($this_value, "\n") !== false) $this_value = str_replace(array("\r\n","\n"), array("<br>","<br>"), $this_value);
				
				// If current field is the record identifier field (special case)
				if ($this_fieldname == $table_pk)
				{
					// Convert record identifier into a link that goes to first form
					if (!$longitudinal) 
					{
						$html_string .= "<td class='rprt'><a href='".APP_PATH_WEBROOT."DataEntry/index.php?pid=$project_id&id=$this_value&page=$firstForm' style='font-size:8pt;font-family:Verdana;text-decoration:underline;'>$this_value</a>";
					}
					elseif ($longitudinal) 
					{
						// If Longitudinal and if Study ID is used in report (and this field is Study ID field), 
						// get name of Event tied to this data point and only display
						list ($this_event_id, $nothing) = explode("|", $this_key, 2);
						// Use event_id to get arm_num for putting in URL
						$this_arm_num = $eventArm[$this_event_id];
						// Add to table
						$html_string .= "<td class='rprt'><a title='".remBr($lang['custom_reports_11']." $table_pk_label $this_value")."' href='".APP_PATH_WEBROOT."DataEntry/grid.php?pid=$project_id&id=$this_value&arm=$this_arm_num' style='font-size:8pt;font-family:Verdana;text-decoration:underline;'>$this_value</a>";
						$html_string .= "</td><td class='rprt'>" . $event_names[$this_event_id];
					}
				}
				else
				{
					// Decode (just in case was saved encoded)
					$this_value = label_decode($this_value);
					// If has a line break, then convert to HTML line break to preserver format
					$this_value = br2nl($this_value);
					// Perform HTML escaping
					$this_value = htmlspecialchars($this_value, ENT_QUOTES);
					// Revert line break to HTML line break
					$this_value = nl2br($this_value);
					// Display the value normally
					$html_string .= "<td class='rprt'>$this_value";
				}
				
				$html_string .= "</td>";
			}
			
		}			
		$html_string .= "</tr>";
		$j++;
	}
	
	$html_string .= "</table>";
	
}

//Print the table
print $html_string;


if (PAGE != "ProjectGeneral/print_page.php") include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
