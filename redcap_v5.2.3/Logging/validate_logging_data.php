<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

include_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

if (!$super_user) {
	exit('ERROR! Only super users may access this page');
}


// Determine if string matches REDCap logging format (based upon field type)
function matchLogString($field_name, $field_type, $string)
{
	// If matches checkbox logging
	if ($field_type == "checkbox" && substr($string, 0, strlen("$field_name(")) == "$field_name(") // && preg_match("/^($field_name\()([a-zA-Z_0-9])(\) = )(checked|unchecked)$/", $string))
	{
		$first_paren_position = strpos($string, "(")+1;
		if (substr($string, strpos($string, ")")) != ") = unchecked") {
			// An option that is checked
			return substr($string, $first_paren_position, strpos($string, ") = checked")-$first_paren_position) . "|1";
		} else {
			// An option that is UNchecked
			return substr($string, $first_paren_position, strpos($string, ") = unchecked")-$first_paren_position) . "|0";
		}
		return false;
	}
	// If matches logging for all fields (excluding checkboxes)
	elseif ($field_type != "checkbox" && substr($string, 0, strlen("$field_name = '")) == "$field_name = '")
	{
		// Remove apostrophe from end (if exists)
		if (substr($string, -1) == "'") {
			$string = substr($string, 0, -1);
		}
		$return_val = substr($string, strlen("$field_name = '"));
		return ($return_val === false ? '' : $return_val);
	}
	// Did not match this line
	else
	{
		return false;
	}
}


// Evaluate this record-event's data
function evaluateRecordData($this_record, $this_event_id, $this_record_data)
{
	global $longitudinal, $Proj;
	// Defaults
	$data_values_like = array();
	$file_field_names = array();
	$logging_data = array();
	$checkboxesCompleted = array();
	foreach ($this_record_data as $this_field_name=>$this_value)
	{
		// Add all data table values to logging data array for comparison later
		$logging_data[$this_field_name]['data'] = $this_value;
		// Get field type
		$this_field_type = $Proj->metadata[$this_field_name]['element_type'];
		// Format the field_name with escaped underscores for the query
		$field_name_q = str_replace("_", "\\_", $this_field_name);
		// Fashion the LIKE part of the query appropriately for the field type
		$field_name_q = ($this_field_type == "checkbox") ?  "%$field_name_q(%) = %checked%" : "%$field_name_q = \'%";
		// For checkboxes, add their coded values to $checkboxesCompleted array to track when we find a value for each choice
		if ($this_field_type == "checkbox") {
			foreach (array_keys(parseEnum($Proj->metadata[$this_field_name]['element_enum'])) as $this_code) {
				$checkboxesCompleted[$this_field_name][$this_code] = "";
			}
		}		
		// Set string to use in query
		$data_values_like[] = "data_values like '$field_name_q'";		
		// Collect "file" field names to use in query
		if ($this_field_type == "file") {
			$file_field_names[] = $this_field_name;
		}		
	}
	// Retrieve logging history and parse field data values to obtain value for specific field
	$sql = "SELECT ts, data_values, description FROM redcap_log_event WHERE 
			project_id = " . PROJECT_ID . " 
			and pk = '" . prep($this_record) . "' 
			and (event_id = '" . prep($this_event_id) . "' " . ($longitudinal ? "" : "or event_id is null") . ")
			and legacy = 0 
			and 
			(
				(
					event in ('INSERT', 'UPDATE') 
					and description in ('Create record', 'Update record', 'Update record (import)', 
						'Create record (import)', 'Merge records', 'Update record (API)', 'Create record (API)', 
						'Update record (DTS)', 'Erase survey responses and start survey over',
						'Update survey response', 'Create survey response')
					and (" . implode(" or ", $data_values_like) . ")
				) 
				or 
				(event in ('DOC_UPLOAD', 'DOC_DELETE') and data_values in ('" . implode("', '", $file_field_names) . "'))
			)
			order by ts desc";
	//print "<br><br>$sql;";
	$q = db_query($sql);
	// Loop through each row from log_event table. Compare data from logging with current real data.
	while ($row = db_fetch_assoc($q))
	{
		// Unescape the logging data
		$data_values = label_decode($row['data_values']);
		// Split each field into lines/array elements.
		// Loop to find the string match
		foreach (explode(",\n", $data_values) as $this_piece)
		{
			// Default return string
			$this_value = "";
			// Determine the field name first
			$first_equals_sign_position = strpos($this_piece, " = ");
			if ($first_equals_sign_position === false) {
				// File upload field
				$this_field_name = $this_piece;
			} else {
				// Determine if a checkbox field
				$first_parethesis_position = strpos($this_piece, "(");
				$isCheckbox = ($first_parethesis_position !== false && $first_parethesis_position < $first_equals_sign_position && strpos($this_piece, ")") < $first_equals_sign_position);
				if ($isCheckbox) {
					// Checkbox field
					$this_field_name = substr($this_piece, 0, $first_parethesis_position);
				} else {
					// Regular field
					$this_field_name = substr($this_piece, 0, $first_equals_sign_position);
				}
			}
			// Get field type
			$this_field_type = $Proj->metadata[$this_field_name]['element_type'];
			// Check if we already have a data point for this record-event-field (exclude checkboxes from this check). If so, start next loop
			if (!isset($logging_data[$this_field_name]['complete']))
			{
				// Does this line match the logging format?
				$matched = matchLogString($this_field_name, $this_field_type, $this_piece);
				if ($matched !== false)
				{
					//print "<br>$this_field_name = $this_value";
					// Stop looping once we have the value (except for checkboxes)
					if ($this_field_type != "checkbox") 
					{
						$this_value = $matched;
						$logging_data[$this_field_name]['log_event'] = $this_value;
						// Add timestamp of logging data and mark as complete
						$logging_data[$this_field_name]['ts'] = $row['ts'];
						$logging_data[$this_field_name]['complete'] = '1';
					}
					// Checkboxes may have multiple values, so append onto each other if another match occurs
					else
					{
						// Split value from checked/unchecked marker
						list ($this_value, $this_checked) = explode("|", $matched, 2);
						if (!isset($logging_data[$this_field_name]['log_event'][$this_value]))
						{
							$logging_data[$this_field_name]['log_event'][$this_value] = $this_checked;
							// Add timestamp of logging data
							$logging_data[$this_field_name]['ts'][$this_value] = $row['ts'];
							// Now that we have a value for the checkbox (whether checked or unchecked), remove option for $checkboxesCompleted
							unset($checkboxesCompleted[$this_field_name][$this_value]);
							// If we have all choices' values accounted for, then set as completed
							if (empty($checkboxesCompleted[$this_field_name])) {
								$logging_data[$this_field_name]['complete'] = '1';
							}
						}
					}
				}
			}
			// Set for next loop
			$last_field_name = $this_field_name;
			$last_field_type = $this_field_type;
		}
	}
	db_free_result($q);
	// CHECKBOX CLEAN-UP: Loop through all checkbox fields to reorder their keys (for comparison later) and remove 0 values (not needed)
	foreach ($Proj->metadata as $this_field_name=>$field_attr)
	{
		if ($field_attr['element_type'] != 'checkbox') continue;
		ksort($logging_data[$this_field_name]['data']);
		foreach ($logging_data[$this_field_name]['log_event'] as $this_code=>$this_value) {
			if ($this_value == '0') {
				unset($logging_data[$this_field_name]['log_event'][$this_code]);
				unset($logging_data[$this_field_name]['ts'][$this_code]);
			}
		}
		ksort($logging_data[$this_field_name]['log_event']);
	}
	//print_array($logging_data);exit;
	print "<div style='padding:2px 0;'>";
	print "Record <b>$this_record</b>";
	if ($longitudinal) {
		print " (Event_id <b>$this_event_id</b>)";
	}
	$errorDiv = "";
	// Now compare the data table data with the log_event table data
	foreach ($logging_data as $this_field_name=>$attr)
	{
		$isCheckbox = ($Proj->metadata[$this_field_name]['element_type'] == 'checkbox');
		// Compensate if value is missing
		if (!isset($attr['log_event'])) {
			$attr['log_event'] = ($isCheckbox ? array() : '');
		}
		if (!isset($attr['data'])) {
			$attr['data'] = ($isCheckbox ? array() : '');
		}
		if (!$isCheckbox) {
			$attr['log_event'] = trim($attr['log_event']);
			$attr['data'] = trim($attr['data']);
		}
		// Loop through each field for this record-event and compare the data values		
		if ($attr['log_event'] !== $attr['data'])
		{
			if (!$isCheckbox) {
				$attr['log_event'] = htmlspecialchars($attr['log_event'], ENT_QUOTES);
				$attr['data'] = htmlspecialchars($attr['data'], ENT_QUOTES);
			}
			$errorDiv .= "  <div> - variable: <b>$this_field_name</b>, 
								data table value: <b>".var_export($attr['data'], true)."</b>,
								logging table value: <b>".var_export($attr['log_event'], true)."</b>,
								time logged: <b>" . $attr['ts'] . "</b>&nbsp; 
								(<a style='text-decoration:underline;' target='_blank' href='".APP_PATH_WEBROOT."DataEntry/index.php?pid=".PROJECT_ID."&page=".$Proj->metadata[$this_field_name]['form_name']."&event_id=$this_event_id&id=$this_record&fldfocus=$this_field_name#$this_field_name-tr'>View field on form</a>)
								<a href='javascript:;' class='dataHist' onclick=\"dataHist('$this_field_name',$this_event_id,'$this_record');\"><img title='View data history' onmouseover='dh1(this)' onmouseout='dh2(this)' src='".APP_PATH_IMAGES."history.png' class='imgfix'></a>&nbsp; 
							</div>";
		}
	}
	if (strlen($errorDiv) > 0) {
		$randId = "rdc-" . md5(rand());
		print  ": <span style='color:red;font-weight:bold;'>DISCREPANCIES FOUND!</span>&nbsp;
				(<a href='javascript:;' style='text-decoration:underline;' onclick=\"$('#$randId').toggle('blind');\">view</a>) 
				<div style='display:none;' id='$randId'>$errorDiv</div>";
	} else {
		print ": <span style='color:green;'>No discrepancies</span>";
	}
	print "</div>";
}







## AJAX CALL FOR EACH RECORD-EVENT THAT RETURNS ANY DISCREPANCIES
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $isAjax)
{
	// Get record and event_id
	if (!isset($_POST['record']) || !isset($_POST['event_id']) || !is_numeric($_POST['event_id'])) {
		exit('ERROR!');
	}
	$event_id  = $_POST['event_id'];
	$record_id = urldecode($_POST['record']);
	// Query all data and loop through it, checking the logging data for each record
	$sql = "SELECT d.*, m.element_type FROM redcap_data d, redcap_metadata m 
			where d.project_id = $project_id and d.project_id = m.project_id 
			and d.field_name = m.field_name and d.field_name IN ('" . implode("', '", array_keys($Proj->metadata)) . "') 
			and d.record = '".prep($record_id)."' and m.element_type != 'file' and d.event_id = $event_id 
			ORDER BY m.field_order";
	$q = db_query($sql);
	$this_record_data = array();
	while ($row = db_fetch_assoc($q)) 
	{
		// Unescape data
		$value = label_decode($row['value']);
		// Place current record's data in array
		if ($row['element_type'] == "checkbox") {
			$this_record_data[$row['field_name']][$value] = '1';
		} else {
			$this_record_data[$row['field_name']] = $value;
		}
	}
	db_free_result($q);
	// Now parse the logging data and compare to real data
	evaluateRecordData($record_id, $event_id, $this_record_data);
	exit;
}



## NORMAL PAGE DISPLAY
include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
// Get list of all records-events
$sql = "select distinct record, event_id from redcap_data where project_id = $project_id 
		and field_name = '$table_pk' and record != '' order by abs(record), record, event_id";
$q = db_query($sql);
$all_records = array();
$all_events  = array();
while ($row = db_fetch_assoc($q)) 
{
	$row['record'] = str_replace("'", "\'", label_decode($row['record']));
	$all_records[] = $row['record'];
	$all_events[]  = $row['event_id'];
}
?>

<p>
	This page checks for any discrepancies between current data values and the most recent logged value for all records
	to ensure that all project data has been logged correctly.
</p>

<div id="ajax_return_div"></div>
<div id="record_progress_div" style="border-top:1px solid #ddd;padding-top:5px;margin-top:5px;">
	<img src="<?php echo APP_PATH_IMAGES ?>progress_circle.gif" class="imgfix"> Checking <?php echo $table_pk_label ?> 
	<span id="rec_name_current" style="font-weight:bold;"></span> 
	<?php if ($longitudinal) { ?>
		(Event_id <span id="event_id_current" style="font-weight:bold;"></span>) 
	<?php } ?>
	...
</div>
	
<!-- Data history dialog pop-up -->
<div id="data_history" style="display:none;">
	<p>
		<?php echo $lang['data_entry_66'] ?> "<b id="dh_var"></b>" <?php echo $lang['data_entry_67'] ?>
		<?php echo "<span class='notranslate'>$table_pk_label</span>" ?>.
	</p>
	<div id="data_history2" style="padding:2px;margin:15px 0px 30px;height:300px;overflow:auto;text-align:center;"></div>	
</div>

<script type="text/javascript">
var events  = new Array('<?php echo implode("', '", $all_events) ?>');
var records = new Array('<?php echo implode("', '", $all_records) ?>');
function doAjaxCheckRecord(current_loop,total_loops) {
	var this_record   = records[current_loop];
	var this_event_id = events[current_loop];
	$('#rec_name_current').html(this_record);
	$('#event_id_current').html(this_event_id);
	// Do ajax call
	$.post(app_path_webroot+page+'?pid='+pid, { record: this_record, event_id: this_event_id }, function(data){
		$('#ajax_return_div').append(data);
		current_loop++;
		if (current_loop < total_loops) {
			// Call function again
			doAjaxCheckRecord(current_loop,total_loops);
		} else {
			// Done!
			$('#record_progress_div').html("<b style='color:green;'>DONE CHECKING!</b>");
		}
	});

}
$(function(){
	doAjaxCheckRecord(0,records.length);
});
</script>

<?php
include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
