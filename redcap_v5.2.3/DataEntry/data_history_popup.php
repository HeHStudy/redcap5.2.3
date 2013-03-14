<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

include_once dirname(dirname(__FILE__)) . '/Config/init_project.php';


// Determine if string matches REDCap logging format (based upon field type)
function matchLogString($field_name, $field_type, $string)
{
	// If matches checkbox logging
	if ($field_type == "checkbox" && substr($string, 0, strlen("$field_name(")) == "$field_name(") // && preg_match("/^($field_name\()([a-zA-Z_0-9])(\) = )(checked|unchecked)$/", $string))
	{
		return $string;
	}
	// If matches logging for all fields (excluding checkboxes)
	elseif ($field_type != "checkbox" && substr($string, 0, strlen("$field_name = '")) == "$field_name = '")
	{
		// Remove apostrophe from end (if exists)
		if (substr($string, -1) == "'") $string = substr($string, 0, -1);
		return substr($string, strlen("$field_name = '"));
	}
	// Did not match this line
	else
	{
		return false;
	}
}
	
	
// Make sure we have all the correct elements needed
if ($history_widget_enabled && isset($_POST['event_id']) && is_numeric($_POST['event_id']) && isset($_POST['record']) && isset($_POST['field_name']) 
	&& preg_match("/[a-z_0-9]/", $_POST['field_name']))
{
	$field_name = $_POST['field_name'];
	$record		= urldecode($_POST['record']); // decode in case of spaces
	$event_id	= $_POST['event_id'];

	// First, validate that the field_name is authentic
	$q = db_query("select element_label, element_enum, element_type from redcap_metadata where field_name = '$field_name' and project_id = $project_id limit 1");
	if (db_num_rows($q) < 1) exit("0");
	// Set field values
	$field_label = db_result($q, 0, "element_label");
	if (strlen($field_label) > 100) $field_label = substr($field_label, 0, 100) . "...";
	$field_type = db_result($q, 0, "element_type");
	// Determine if a multiple choice field (do not include checkboxes because we'll used their native logging format for display)
	$isMC = (in_array($field_type, array("select", "radio", "advcheckbox", "yesno", "truefalse"))) ? true : false;
	if ($isMC) {
		if ($field_type == "yesno") {
			$field_choices = parseEnum(YN_ENUM);
		} elseif ($field_type == "truefalse") {
			$field_choices = parseEnum(TF_ENUM);
		} else {
			$field_choices = parseEnum(db_result($q, 0, "element_enum"));
		}
	}
	
	// Format the field_name with escaped underscores for the query
	$field_name_q = str_replace("_", "\\_", $field_name);
	// Fashion the LIKE part of the query appropriately for the field type
	$field_name_q = ($field_type == "checkbox") ?  "%$field_name_q(%) = %checked%" : "%$field_name_q = \'%";
	
	// Set the 2nd query field (for "file" fields, it will be different)
	$qfield2 = ($field_type == "file") ? "description" : "data_values";
		
	// Default
	$time_value_array = array();
	
	// Retrieve history and parse field data values to obtain value for specific field
	$sql = "SELECT user, ts, $qfield2 as values1, change_reason FROM redcap_log_event WHERE 
			project_id = $project_id 
			and pk = '$record' 
			and (event_id = $event_id " . ($longitudinal ? "" : "or event_id is null") . ")
			and legacy = 0 
			and 
			(
				(
					event in ('INSERT', 'UPDATE') 
					and description in ('Create record', 'Update record', 'Update record (import)', 
						'Create record (import)', 'Merge records', 'Update record (API)', 'Create record (API)', 
						'Update record (DTS)', 'Erase survey responses and start survey over',
						'Update survey response', 'Create survey response')
					and data_values like '$field_name_q'
				) 
				or 
				(event in ('DOC_UPLOAD', 'DOC_DELETE') and data_values = '$field_name')
			)
			order by ts desc";
	$q = db_query($sql);
	// Loop through each row from log_event table. Each will become a row in the new table displayed.
	while ($row = db_fetch_assoc($q))
	{
		// Get timestamp
		$ts = format_ts($row['ts']);	
		// Get username
		$user = $row['user'];
		// Decode values
		$value = html_entity_decode($row['values1'], ENT_QUOTES);
		// All field types (except "file")
		if ($field_type != "file")
		{
			// Default return string
			$this_value = "";
			// Split each field into lines/array elements.
			// Loop to find the string match
			foreach (explode(",\n", $value) as $this_piece)
			{
				// Does this line match the logging format?
				$matched = matchLogString($field_name, $field_type, $this_piece);
				//print "<div style='text-align:left;'>LINE: $this_piece<br>Matched: $matched</div>";
				if ($matched !== false)
				{
					// Stop looping once we have the value (except for checkboxes)
					if ($field_type != "checkbox") 
					{
						$this_value = $matched;
						break;
					}
					// Checkboxes may have multiple values, so append onto each other if another match occurs
					else
					{
						$this_value .= $matched . "<br>";
					}
				}
			}
			
			// If a multiple choice question, give label AND coding
			if ($isMC && $this_value != "")
			{
				$this_value = filter_tags(label_decode($field_choices[$this_value])) . " ($this_value)";
			}
		}
		// "file" fields
		else
		{
			$this_value = $value;
		}		
		
		// Add to array
		$time_value_array[] = array('ts'=>$ts, 'value'=>nl2br(htmlspecialchars(br2nl(label_decode($this_value)), ENT_QUOTES)), 
									'user'=>$user, 'change_reason'=>nl2br($row['change_reason']));
		
	}
	
	// TABLE DISPLAY	
	?>
	<table class='form_border' style='border:1px solid #ddd;width:<?php echo $isIE ? "95%" : "100%" ?>;text-align:left;'>
		<tr>
			<td class='label_header' style='padding:5px 8px;width:150px;'>
				<?php echo $lang['data_history_01'] ?>
			</td>
			<td class='label_header' style='padding:5px 8px;'>
				<?php echo $lang['global_17'] ?>
			</td>
			<td class='label_header' style='padding:5px 8px;'>
				<?php echo $lang['data_history_03'] ?>
			</td>
			<?php if ($require_change_reason) { ?>
				<td class='label_header' style='padding:5px 8px;'>
					<?php echo $lang['data_history_04'] ?>
				</td>			
			<?php } ?>
		</tr>
	<?php foreach ($time_value_array as $row) { ?>
		<tr>
			<td class='data' style='border:1px solid #ddd;padding:3px 8px;text-align:center;width:150px;'>
				<?php echo $row['ts'] ?>
			</td>
			<td class='data' style='border:1px solid #ddd;padding:3px 8px;text-align:center;'>
				<?php echo $row['user'] ?>
			</td>
			<td class='data' style='border:1px solid #ddd;padding:3px 8px;'>
				<?php echo $row['value'] ?>
			</td>
			<?php if ($require_change_reason) { ?>
				<td class='data' style='border:1px solid #ddd;padding:3px 8px;'>
					<?php echo $row['change_reason'] ?>
				</td>
			<?php } ?>
		</tr>
	<?php } 
	if (empty($time_value_array))
	{
		?>
		<tr>
			<td class='data' colspan='<?php echo ($require_change_reason ? '4' : '3') ?>' style='border-top: 1px #ccc;padding:6px 8px;text-align:center;'>
				<?php echo $lang['data_history_05'] ?>
			</td>
		</tr>
		<?php
	}	
	?>
	</table>
	<?php
	exit;
}

print "Error!";