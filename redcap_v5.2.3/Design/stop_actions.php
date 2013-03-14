<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . "/Config/init_project.php";
require_once APP_PATH_DOCROOT . 'ProjectGeneral/form_renderer_functions.php';

// Use correct metadata table depending on status
$metadata_table = ($status > 0) ? "redcap_metadata_temp" : "redcap_metadata";

// Validate field as a multiple choice field
if (!isset($_POST['field_name']) || !isset($_POST['action'])) exit('0');
$sql = "select * from $metadata_table where project_id = $project_id and field_name = '" . prep($_POST['field_name']) . "'
		and element_type in ('radio', 'select', 'yesno', 'truefalse', 'checkbox') limit 1";
$q = db_query($sql);
$field_exists = (db_num_rows($q) > 0);
if (!$field_exists) exit('0');

// Collect field info
$row = db_fetch_assoc($q);
if ($row['element_type'] == 'yesno') {
	$options = parseEnum(YN_ENUM);
} elseif ($row['element_type'] == 'truefalse') {
	$options = parseEnum(TF_ENUM);
} else {
	$options = parseEnum($row['element_enum']);
}


// Render the field's multiple choice options
if ($_POST['action'] == "view")
{
	// Get currently saved stop actions to know if we need to check any checkbox
	$stop_actions = parseStopActions($row['stop_actions']);
	// Render text and checkboxes
	?>
	<p>
		<img src="<?php echo APP_PATH_IMAGES ?>stopsign.gif" class="imgfix">
		<b>Stop Actions (for survey questions only):</b><br>
		The survey participant will be prompted to end the survey when ANY choices checked below are selected for this question
		on the survey. Stop Actions will not be enabled on the form when viewing in REDCap as an authenticated user, 
		but only become enabled when a participant views this data collection instrument as a survey.
	</p>
	<div id="stop_actions_checkboxes" class="chklist" style="padding:5px 10px 10px;margin:10px 0;">
		<div style="font-weight:bold;">
			<?php echo $row['element_label'] ?>
		</div>
		<div style="text-align:right;color:#888;">
			<a href="javascript:;" onclick="selectAllStopActions(1);" style="text-decoration:underline;"><?php echo $lang['data_export_tool_52'] ?></a> 
			&nbsp;|&nbsp;
			<a href="javascript:;" onclick="selectAllStopActions(0);" style="text-decoration:underline;"><?php echo $lang['data_export_tool_53'] ?></a>
		</div>
		<div>
		<?php foreach ($options as $code=>$label) { ?>
			<input type="checkbox" value="<?php echo $code ?>" <?php if (in_array($code, $stop_actions)) echo "checked"; ?>> <?php echo filter_tags(label_decode($label)) ?><br>
		<?php } ?>
		</div>
	</div>
	<?php
}

// Save the options checked off by user
elseif ($_POST['action'] == "save" && isset($_POST['codes']))
{
	// Store valid codes in array
	$validCodes = array();
	$_POST['codes'] = trim($_POST['codes']);
	// Extract submitted codes
	if ($_POST['codes'] != "")
	{
		// Multiple codes submitted
		if (strpos($_POST['codes'], ',') !== false)
		{
			// Loop through each code, validate it, and save it
			foreach (explode(',', $_POST['codes']) as $this_code)
			{
				// Trim code, just in case
				$this_code = trim($this_code);
				// Make sure is valid code for the field
				if (isset($options[$this_code]))
				{
					$validCodes[] = $this_code;
				}
			}
		}
		// Only one option selected
		elseif (isset($options[$_POST['codes']]))
		{
			$validCodes[] = $_POST['codes'];
		}
	} 
	// Delimit the codes with a comma
	$validCodesDelimited = implode(',', $validCodes);
	
	// Now save the valid codes in the field's metadata
	$sql = "update $metadata_table set stop_actions = " . checkNull($validCodesDelimited) . "
			where project_id = $project_id and field_name = '{$_POST['field_name']}'";
	if (db_query($sql))
	{
		// Log the event
		log_event($sql, $metadata_table, "MANAGE", $_POST['field_name'], "field_name = ".$_POST['field_name'], "Add/edit stop actions for survey question");
		// Response
		print '1';
	} 
	else
	{
		// Error
		print '0';
	}

}
