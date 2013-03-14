<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/
	
require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
require_once APP_PATH_DOCROOT . 'Reports/functions.php';

// Increase memory limit so large data sets do not crash and yield a blank page


// Check for URL variable 'fileid'
if (!isset($_GET['fileid']) || $_GET['fileid'] == "") exit("{$lang['global_09']}!");
$fileid = $_GET['fileid'];

// Check URL variable 'query_id'
if (isset($_GET['query_id']) && !is_numeric($_GET['query_id'])) {
	exit("{$lang['global_09']}!");
}


// Check if temp directory is writable
$upload_dir = dirname(substr(APP_PATH_DOCROOT,0,-1)) . DS . "temp";
if (!isDirWritable($upload_dir))
{
	include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
	print "<br><br><div class='red'>
		<img src='".APP_PATH_IMAGES."exclamation.png'> <b>{$lang['global_01']}:</b><br>
		{$lang['multitype_download_03']} <b>$upload_dir</b> {$lang['multitype_download_04']}</div>";
	include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
	exit;
}


## MAKE CSV/XML DATA FILE FROM A REPORT
		
//If user somehow gets here when they don't have data export rights, stop them.
if ($user_rights['data_export_tool'] == 0) {
	exit($lang['multitype_download_05']);
}

//Clear headers (needed for some server configurations)
ob_clean();

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

//Get the Field Labels for each Field Name for substituting as the table headers
//Also get Select Choices for displaying number value and text value
$q = db_query("select field_name, form_name, element_label, element_enum, element_type, field_phi from redcap_metadata 
				  where project_id = $project_id order by field_order");
$field_labels = array();
$select_choices = array();	
$is_enum = array();	
$phi_fields = array();
$field_form = array();
while ($row = db_fetch_array($q)) {
	$field_labels[$row['field_name']] = $row['element_label'];
	$field_form[$row['field_name']] = $row['form_name'];
	$form_status_names[$row['form_name'] . "_complete"] = "";
	if ($row['field_phi'] == '1') $phi_fields[] = $row['field_name'];
	if ($row['element_type'] == 'sql' || $row['element_type'] == 'select' || $row['element_type'] == 'radio' || $row['element_type'] == 'advcheckbox' || $row['element_type'] == 'checkbox') {
		$is_enum[$row['field_name']] = true;
		// Convert sql field types' query result to an enum format
		if ($row['element_type'] == 'sql') {
			$row['element_enum'] = getSqlFieldEnum($row['element_enum']);
		}
		$select_choices[$row['field_name']] = parseEnum($row['element_enum']);
	} else {
		$is_enum[$row['field_name']] = false;
	}
}

// Create array of list of checkbox fields
if (!isset($chkbox_fields) || $chkbox_fields == null) {
	$sql = "select field_name, element_enum from redcap_metadata where project_id = " . PROJECT_ID . " and element_type = 'checkbox'";
	$chkboxq = db_query($sql);
	$chkbox_fields = array();
	while ($row = db_fetch_assoc($chkboxq)) {
		// Add field to list of checkboxes and to each field add checkbox choices
		foreach (parseEnum($row['element_enum']) as $this_value=>$this_label) {
			$chkbox_fields[$row['field_name']][$this_value] = $this_label;	
		}
	}	
}

// Get raw sorted data
list ($eav_arr, $query_fields, $num_cols) = buildReport($this_query_array);

//Display column names as table headers
$header = "";
foreach ($query_fields as $this_fieldname) {			
	//Display the label and field name (non-checkbox fields)
	if (!isset($chkbox_fields[$this_fieldname])) {
		$header .= '"' . str_replace("\"","'",label_decode($field_labels[$this_fieldname])) . ' (' . $this_fieldname . ')",';
	//Display the label and field name (checkbox fields only)
	} else {
		foreach ($chkbox_fields[$this_fieldname] as $this_code=>$this_label) 
		{
			$this_label = label_decode($this_label);
			$header .= "\"" . str_replace("\"","'",label_decode($field_labels[$this_fieldname]))
					.  " (Choice = '$this_label') ({$this_fieldname}___{$this_code})\",";
		}
	}
	// If Longitudinal and the current field is the Record ID field, show also the header text for the Event name
	if ($longitudinal && $this_fieldname == $table_pk) {
		$header .= '"Event Name (redcap_event_name)",';
	}
	// Check to make sure that user has access rights to the form on which that this field is located. If not, do not show table.
	if ($user_rights['forms'][$field_form[$this_fieldname]] == '0') 
	{
		renderPageTitle($custom_report_title);
		print  "<div class='red'><img src='".APP_PATH_IMAGES."exclamation.png' class='imgfix'> <b>{$lang['global_05']}</b><br><br>
				{$lang['custom_reports_05']}
				</div>";
		include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
		exit;
	}
	
}

// Build array of Event names for storing for later use
$q = db_query("select m.event_id, m.descrip from redcap_events_metadata m, redcap_events_arms a where a.arm_id = m.arm_id and a.project_id = $project_id");
$event_names = array();
while ($row = db_fetch_assoc($q)) {
	$event_names[$row['event_id']] = $row['descrip'];
}	


// CSV Report Export
if ($fileid == "report_csv") 
{
	// Logging
	log_event("","redcap_data","MANAGE",$project_id,"project_id = $project_id","Download report (CSV)");

	$data = "$header\n";
	
	// Display each table row
	$j = 1;
	foreach ($eav_arr as $this_key=>$this_key_arr) {
		foreach ($query_fields as $this_fieldname) {
		
			$this_value = isset($this_key_arr[$this_fieldname]) ? $this_key_arr[$this_fieldname] : "";
			
			//For a radio, select, or advcheckbox, show label only
			if ($is_enum[$this_fieldname]) { 
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
				//Render numerical value						
				if (!isset($chkbox_fields[$this_fieldname])) { 
					// Normal fields (non-checkboxes)
					$data .= '"' . str_replace("\"","'",label_decode($select_choices[$this_fieldname][$this_value])) . '",';
				} else { 
					// Checkbox fields only
					foreach ($chkbox_fields[$this_fieldname] as $this_code=>$this_label) {
						//print "\n\t\t<field>\n\t\t\t<fieldname>{$this_fieldname}___{$this_code}</fieldname>\n\t\t\t<fielddata>{$this_key_arr[$this_fieldname][$this_code]}</fielddata>\n\t\t</field>";
						$data .= '"' . $this_key_arr[$this_fieldname][$this_code] . '",';
					}					
				}
				
			//Display normally as raw data
			} else {
			
				//Do not display if user has de-id export rights and field is Identifier
				if ($user_rights['data_export_tool'] == 2 && in_array($this_fieldname,$phi_fields)) {
					if ($this_fieldname == $table_pk) {
						$data .= '"' . md5($salt . $this_value . $__SALT__) . '",';							
					} else {
						$data .= '"[IDENTIFIER]",';
					}
					
				//Display
				} else {
				
					//Replace characters that were converted during post (they will have ampersand in them)
					if (strpos($this_value, "&") !== false) {
						$this_value = html_entity_decode($this_value, ENT_QUOTES);
					}
					
					// If line breaks exist, replace with space
					if (strpos($this_value, "\n") !== false) $this_value = str_replace(array("\r\n","\n"), array(" "," "), $this_value);
					
					//Render data point
					$data .= '"' . str_replace("\"", "'", $this_value) . '",';
					
					// If Longitudinal and Study ID field is used in report, show also the Event name
					if ($longitudinal && $this_fieldname == $table_pk) {
						list ($this_event_id, $nothing) = explode("|", $this_key, 2);
						$data .= '"' . str_replace("\"", "'", html_entity_decode($event_names[$this_event_id], ENT_QUOTES)) . '",';
					}
				}
			}
			
		}			
		$data .= "\n";
		$j++;
	}
	
	// Set filename (remove spaces and special characters)
	$filename = "Report_" . str_replace(" ", "", ucwords(preg_replace("/[^a-zA-Z0-9]/", " ", $custom_report_title)));
	
	header('Pragma: anytextexeptno-cache', true);
	header("Content-type: application/csv");
	
	header("Content-Disposition: attachment; filename={$filename}_".date("Y-m-d_Hi").".csv");
	if ($project_language == 'Japanese' && mb_detect_encoding($data) == "UTF-8") {
		print mb_convert_encoding($data, "SJIS", "UTF-8");
	} else {	
		print addBOMtoUTF8($data);
	}

}


// XML Report Export
elseif ($fileid == "report_xml") 
{
	// Logging
	log_event("","redcap_data","MANAGE",$project_id,"project_id = $project_id","Download report (XML)");
	
	//Arrays to remove bad characters that will crash XML
	$search  = array(chr(145),chr(146),chr(147),chr(148),chr(151),"“","”");  
	$replace = array("'","'",'"','"','-','"','"');			
	//Header
	header("Content-type: text/xml; charset=UTF-8");
	print "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<records>";			
	// Display each table row
	$j = 1;
	//Loop through all records
	foreach ($eav_arr as $this_key=>$this_key_arr) {
		// Separate event_id from actual record name (using both together as array key)
		list ($this_event_id, $this_key) = explode("|", $this_key, 2);
		// Render this record
		print "\n<record>\n\t<recordname>$this_key</recordname>\n\t<fields>";
		//Loop through all fields for this record
		foreach ($query_fields as $this_fieldname) {
			//Get the saved data value for this field for this record (escape any XML chars with htmlentities())
			$this_value = htmlspecialchars(str_replace($search, $replace, $this_key_arr[$this_fieldname]));
			$this_value = preg_replace('/[^[:print:]|\n|\r|\t]/', '', $this_value);
			//If a form status field and data doesn't exist yet, set value to 0 (incomplete).
			if (isset($form_status_names[$this_fieldname])) {
				if ($this_value == "") $this_value = 0;
			//Do not display if user has de-id export rights and field is Identifier
			} else {
				if ($user_rights['data_export_tool'] == 2 && in_array($this_fieldname,$phi_fields)) {
					if ($this_fieldname == $table_pk) {
						$this_value = md5($salt . $this_value . $__SALT__);
					} else {
						$this_value = "[IDENTIFIER]";
					}
				}
			}
			//Render field name and data
			if (!isset($chkbox_fields[$this_fieldname])) { 
				// Normal fields (non-checkboxes)
				print "\n\t\t<field>\n\t\t\t<fieldname>$this_fieldname</fieldname>\n\t\t\t<fielddata>$this_value</fielddata>\n\t\t</field>";
			} else { 
				// Checkbox fields only
				foreach ($chkbox_fields[$this_fieldname] as $this_code=>$this_label) {
					print "\n\t\t<field>\n\t\t\t<fieldname>{$this_fieldname}___{$this_code}</fieldname>\n\t\t\t<fielddata>{$this_key_arr[$this_fieldname][$this_code]}</fielddata>\n\t\t</field>";
				}					
			}
			// If Longitudinal and current field is Record ID field, then render Event name as its own field also
			if ($longitudinal && $this_fieldname == $table_pk) {
				print "\n\t\t<field>\n\t\t\t<fieldname>redcap_event_name</fieldname>\n\t\t\t<fielddata>{$event_names[$this_event_id]}</fielddata>\n\t\t</field>";
			}
		}				
		print "\n\t</fields>\n</record>";
		$j++;
	}			
	print "\n</records>";
	
}
