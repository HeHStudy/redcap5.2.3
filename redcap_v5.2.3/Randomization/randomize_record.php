<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


// Config
require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
require_once APP_PATH_DOCROOT . 'ProjectGeneral/form_renderer_functions.php';
// Include CalculateParser class
require_once APP_PATH_CLASSES . "CalculateParser.php";
$cp = new CalculateParser();
// Include BranchingLogic class
require_once APP_PATH_CLASSES . "BranchingLogic.php";
$bl = new BranchingLogic();

// Make sure is Post request and also that record exists
if (!isset($_POST['action']) || $_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['record'])) { // || !recordExists($project_id, $_POST['record'])) {
	exit('0');
}

// Output html form
if ($_POST['action'] == 'view')
{
	// Check if all fields involved still exist
	$missingFields = Randomization::randFieldsMissing();
	// Check if allocation table exists
	$allocTableExists = Randomization::allocTableExists($status);	
	// Determine if we can display it. If not, display errors.
	if ($allocTableExists && empty($missingFields)) {
		// Display the form table
		Randomization::randomizeWidgetTable();
	} elseif (!empty($missingFields)) {
		// Give error message that some fields have been deleted that are needed for randomization
		print RCView::p(array(), 
			RCView::b($lang['global_01'].$lang['colon']).RCView::br().
			$lang['random_122'] . RCView::SP . RCView::b(implode(", ", $missingFields)) . $lang['period']
		);
	} elseif (!$allocTableExists) {
		// Give error message is an allocation file was never uploaded
		print RCView::p(array(), 
			RCView::b($lang['global_01'].$lang['colon']).RCView::br().
			($status > 0 ? $lang['random_71'] : $lang['random_70'])
		);
	}
}


// Randomize the record and return success message
elseif ($_POST['action'] == 'randomize')
{
	// Get randomization setup values first
	$randAttr = Randomization::getRandomizationAttributes();
	// Set values
	$record = $submitted_record = $_POST['record'];
	$existing_record = ($_POST['existing_record'] == '1' && recordExists($project_id, $record));
	// Aggregate the criteria fields and their values into array
	$fields = array();
	if (trim($_POST['fields']) != "") {
		$field_names = explode(",", $_POST['fields']);
		$field_values = explode(",", $_POST['field_values']);
		foreach ($field_names as $key=>$field) {
			$fields[$field] = $field_values[$key];
		}
	}
	// DAG: If grouping by DAG, then get this record's DAG (or get DAG set by/assigned to user)
	$group_id = '';
	if ($randAttr['group_by'] == 'DAG' && $user_rights['group_id'] == '') {
		// If user is NOT in a DAG, assign record to the DAG they designated
		if ($Proj->getGroups($_POST['redcap_data_access_group']) !== false) {
			$group_id = $_POST['redcap_data_access_group'];
		}
	} elseif ($existing_record && $randAttr['group_by'] == 'DAG') {
		// If record has already been assigned to a DAG, get the group_id of the record
		$sql = "select value from redcap_data where project_id = $project_id and record = '".prep($record)."'
				and field_name = '__GROUPID__' limit 1";
		$q = db_query($sql);
		if (db_num_rows($q) > 0) {
			$value = db_result($q, 0);
			// Verify that this DAG belongs to this project
			if ($Proj->getGroups($value) !== false) {
				$group_id = $value;
			}
		}
	} elseif (!$existing_record && $randAttr['group_by'] == 'DAG' && $user_rights['group_id'] != '') {
		// If user is in a DAG and creating a new record, assign record to their DAG
		$group_id = $user_rights['group_id'];
	}
	// If record does NOT exist yet AND we're using auto-numbering, get new record name
	if (!$existing_record && $auto_inc_set) 
	{
		$record = getAutoId();
	}
	// Randomize and return aid key
	$aid = Randomization::randomizeRecord($record,$fields,$group_id);
	if ($aid === false) exit('0');
	// NO ASSIGNMENTS AVAILABLE: If returned 0, then cannot allocate
	if ($aid == '0') {
		?>
		<div class="red" style="margin:20px 0;">
			<table cellspacing=10 width=100%>
				<tr>
					<td style="padding:0 30px 0 40px;">
						<img src="<?php echo APP_PATH_IMAGES ?>cross_big.png">
					</td>
					<td style="font-size:14px;font-family:verdana;line-height:22px;padding-right:30px;">
						<?php  print RCView::b($lang['random_60']). " $table_pk_label \"<b>$record</b>\" {$lang['random_61']}"; ?>
					</td>
				</tr>
			</table>
		</div>
		<!-- Close button -->
		<div style="text-align:right;padding:5px 20px 10px 0;">
			<button class="jqbutton" onclick="$('#randomizeDialog').dialog('close');" style="font-size:15px;">Close</button>
		</div>
		<?php
		exit;
	}
	## SUCCESSFULLY RANDOMIZED!
	// Get the value allocated for the target randomization field
	list ($target_field, $target_field_value) = Randomization::getRandomizedValue($record);
	// Obtain randomization/criteria fields and group by event_id inside array
	$randomizationCriteriaFields = Randomization::getRandomizationFields(true);
	$fieldNamesEvents = array();
	while (!empty($randomizationCriteriaFields)) {
		$field = array_shift($randomizationCriteriaFields);
		if ($longitudinal) {
			$event_id = array_shift($randomizationCriteriaFields);
			$fieldNamesEvents[$event_id][] = $field;
		} else {
			$fieldNamesEvents[$Proj->firstEventId][] = $field;
		}
	}
	// Loop through all events and save data for each for this record
	foreach ($fieldNamesEvents as $event_id=>$fields) {
		// Initialize parameters before performing the Save
		$_GET['event_id'] = $event_id;
		$_POST = array($table_pk=>$record);
		if ($group_id != '') $_POST['__GROUPID__'] = $group_id; 
		// For each field in this event, add to $_POST
		foreach ($fields as $field) {
			if ($field == $target_field) {
				$_POST[$field] = $target_field_value;
				$target_event = $event_id;
			} else {
				$_POST[$field] = $field_values[array_search($field, $field_names)];
			}
		}
		// Save the record data (in case was added or changed)
		saveRecord($record);
	}
	// Log that randomization took place right after we saved the record values
	log_event("", "redcap_data", "MANAGE", $record, "$table_pk = '$record'", "Randomize record");
	## Give message of success
	// Get the field's label and choice label
	$field_label = RCView::escape($Proj->metadata[$target_field]['element_label']);
	$choices = parseEnum($Proj->metadata[$target_field]['element_enum']);
	$field_choice_label = RCView::escape($choices[$target_field_value]);
	// Return confirmation message in pop-up
	?>
	<div class="darkgreen" style="margin:20px 0;">
		<table cellspacing=10 width=100%>
			<tr>
				<?php if (!$isMobileDevice) { ?>
					<td style="padding:0 30px 0 40px;">
						<img src="<?php echo APP_PATH_IMAGES ?>check_big.png">
					</td>
				<?php } ?>
				<td style="font-size:14px;font-family:verdana;line-height:22px;padding-right:30px;">
					<?php 
					print "$table_pk_label \"<b>$record</b>\" {$lang['random_57']} \"<b>$field_label</b>\"
						   {$lang['random_58']} \"<b>$field_choice_label</b>\" ($target_field_value){$lang['period']}" 
					?>
				</td>
			</tr>
		</table>
	</div>
	<!-- Notification msg if record renamed due to auto-numbering -->
	<?php if ($auto_inc_set && $submitted_record != $record) { ?>	
		<div class="yellow" style="margin:0 0 20px;">
			<?php echo RCView::img(array('src'=>'exclamation_orange.png','class'=>'imgfix')) 
					 . "<b>{$lang['global_03']}:</b> {$lang['random_108']} $table_pk_label \"<b>$record</b>\" 
						{$lang['random_109']} $table_pk_label \"<b>$submitted_record</b>\" {$lang['random_110']}" ?>
		</div>
	<?php } ?>
	<!-- Close button -->
	<div style="text-align:right;padding:5px 20px 10px 0;">
		<button class="jqbutton" onclick="$('#randomizeDialog').dialog('close');" style="font-size:15px;">Close</button>
	</div>
	<!-- Hidden element that will be used to replace Randomize button -->
	<div id="alreadyRandomizedTextWidget" style="display:none;"><?php echo $lang['random_56'] ?></div>
	<!-- Hidden element containing raw value and name of randomization field -->
	<input type="hidden" id="randomizationFieldRawVal" value="<?php echo cleanHtml2($target_field_value) ?>">
	<input type="hidden" id="randomizationFieldName" value="<?php echo cleanHtml2($target_field) ?>">
	<input type="hidden" id="randomizationFieldEvent" value="<?php echo cleanHtml2($target_event) ?>">
	<?php if ($randAttr['group_by'] == 'DAG') { ?>
		<input type="hidden" id="redcap_data_access_group" value="<?php echo cleanHtml2($group_id) ?>">
	<?php } ?>
	<input type="hidden" id="record" value="<?php echo cleanHtml2($record) ?>">
	<?php
}
	