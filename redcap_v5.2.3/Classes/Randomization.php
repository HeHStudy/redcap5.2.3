<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


/**
 * RANDOMIZATION
 */
class Randomization
{
	// Set max number of source fields possible for randomization (determined by table structure)
	const MAXSOURCEFIELDS = 15;
	
	// Render the tabs at top of Randomization page based upon user rights
	static function renderTabs()
	{
		global $user_rights, $lang;
		$tabs = array();
		if ($user_rights['random_setup']) { 
			$tabs['Randomization/index.php'] = $lang['random_48'];
		}
		if ($user_rights['random_dashboard']) { 
			$tabs['Randomization/dashboard.php'] = $lang['rights_143']; //$lang['random_49'];
			// $tabs['Randomization/dashboard_all.php'] = $lang['random_26'];
		}
		renderTabs($tabs);
	}
	
	// Returns status (T/F) if randomization has been set up yet for this project
	static function setupStatus()
	{
		$sql = "select 1 from redcap_randomization r, redcap_projects p where p.project_id = " . PROJECT_ID . " 
				and p.project_id = r.project_id and p.randomization = 1";
		$q = db_query($sql);
		return ($q && db_num_rows($q) > 0);
	}
	
	// Removes the entire randomization setup (removes all from tables) for this project
	static function eraseRandomizationSetup()
	{
		global $status;
		// Make sure we're in dev or a super user, else fail
		if ($status < 1 || SUPER_USER) {
			// Delete from table
			$sql = "delete from redcap_randomization where project_id = " . PROJECT_ID;
			return db_query($sql);
		}
		return false;
	}
	
	// Return number of records containing data for a given field
	static function getFieldDataCount($field)
	{
		$sql = "select count(distinct(record)) from redcap_data where project_id = " . PROJECT_ID . " 
				and field_name = '".prep($field)."' and value != ''";
		$q = db_query($sql);
		if (!$q) return '';
		return db_result($q, 0);
	}
	
	// When saving randomization model, delete all data for all records containing data for the randomization field selected
	static function deleteSingleFieldData($field)
	{
		// Get required field
		require_once APP_PATH_DOCROOT . 'ProjectGeneral/form_renderer_functions.php';
		// Get global vars
		global $table_pk;
		// Get original $_POST and $_GET so we can resurrect it in the end here
		$originalPost = $_POST;
		$originalGet = $_GET;
		// Get all records/events with data for that field
		$recordsWithRandFieldData = array();
		$sql = "select distinct record, event_id from redcap_data where project_id = " . PROJECT_ID . " 
				and field_name = '".prep($field)."' and value != '' order by abs(record), record, event_id";
		$q = db_query($sql);
		if (db_num_rows($q) > 0)
		{
			while ($row = db_fetch_assoc($q)) {
				$recordsWithRandFieldData[$row['record']][] = $row['event_id'];
			}
			// Loop through each record/event and delete each formally (with logging)
			foreach ($recordsWithRandFieldData as $record=>$events) 
			{
				foreach ($events as $event_id) 
				{
					// Simulate new Post submission (as if submitted via data entry form)
					$_POST = array($table_pk=>$record, $field=>''); 
					// Need event_id in query string for saving properly
					$_GET['event_id'] = $event_id; 
					// Delete randomization field values and log it
					saveRecord($record);
				}
			}
		}
		// Resurrect values
		$_POST = $originalPost;
		$_GET = $originalGet;
	}
	
	// Returns boolean of whether a given record has already been randomized for the project (given the current project status)
	static function wasRecordRandomized($record='')
	{
		global $status;		
		if (!isset($status) || (isset($status) && !is_numeric($status))) {
			// Get project's status
			$sql = "select status from redcap_projects where project_id = " . PROJECT_ID;
			$q = db_query($sql);
			$status = db_result($q, 0);
		}
		$sql = "select 1 from redcap_randomization_allocation a, redcap_randomization r 
				where r.project_id = " . PROJECT_ID . " and a.project_status = $status
				and r.rid = a.rid and a.is_used_by = '".prep($record)."' limit 1";
		$q = db_query($sql);
		return ($q && db_num_rows($q) > 0);
	}
	
	// Returns randomization field name and value as array (or false on failure) for the record's target randomization field's value that was allocated
	static function getRandomizedValue($record='')
	{
		global $status;		
		$sql = "select r.target_field, a.target_field as target_field_value 
				from redcap_randomization_allocation a, redcap_randomization r 
				where r.project_id = " . PROJECT_ID . " and a.project_status = $status
				and r.rid = a.rid and a.is_used_by = '".prep($record)."' limit 1";
		$q = db_query($sql);
		if ($q && db_num_rows($q) > 0) { 
			$ret = db_fetch_assoc($q);
			return array($ret['target_field'], $ret['target_field_value']);
		} else {
			return false;
		}
	}
	
	// Delete the allocation table for given project status. If no allocations are left after this deletion, then remove field designations too.
	static function deleteAllocFile($this_status)
	{
		global $status, $longitudinal, $Proj, $table_pk;
		// First, get list of records already randomized and delete their assigned value IF project is on the 
		// same status (dev or prod) as the status of the alloc file we're deleting.
		if ($status == $this_status)
		{
			// Obtain all allocated values and put into array
			$sql = "select a.is_used_by as record, r.target_field, r.target_event
					from redcap_randomization_allocation a, redcap_randomization r 
					where r.project_id = " . PROJECT_ID . " and a.project_status = $this_status
					and r.rid = a.rid and a.is_used_by is not null 
					order by abs(a.is_used_by), a.is_used_by";
			$q = db_query($sql);
			$valuesToDelete = array();
			while ($row = db_fetch_assoc($q))
			{
				// Put record info into array to delete later
				if (!isset($event_id)) {
					$event_id = (is_numeric($row['target_event'])) ? $row['target_event'] : $Proj->firstEventId;
				}
				$valuesToDelete[$row['record']][$event_id] = $row['target_field'];
			}
			// Delete ALL values for the ranomdization field from data table
			if (!empty($valuesToDelete))
			{
				// Formally delete the values so that they get logged properly (use hack to simulate data entry form submission)
				foreach ($valuesToDelete as $record=>$attr) {
					foreach ($attr as $event_id=>$field) 
					{
						// Simulate new Post submission (as if submitted via data entry form)
						$_POST = array($table_pk=>$record, $field=>''); 
						// Need event_id in query string for saving properly
						$_GET['event_id'] = $event_id; 
						// Delete randomization field values and log it
						saveRecord($record);
					}
				}
			}
		}
		// Now delete all allocations for this status
		$rid = self::getRid();
		$sql = "delete from redcap_randomization_allocation where rid = $rid and project_status = $this_status";
		// Return successful/fail response
		return db_query($sql);
	}
	
	// Determine if any (either dev or prod) allocation files exist for this project. Option to check for specific project status.
	static function allocTableExists($status=null)
	{
		$sql = "select 1 from redcap_randomization_allocation a, redcap_randomization r 
				where r.project_id = " . PROJECT_ID . " and r.rid = a.rid";
		if ($status == '0' || $status == '1') {
			$sql .= " and a.project_status = $status";
		}		
		$sql .= " limit 1";
		$q = db_query($sql);
		return ($q && db_num_rows($q) > 0);
	}
	
	// Check if all fields used in randomization still exist in metadata
	static function randFieldsMissing()
	{
		global $Proj;
		// Collect missing fields in array
		$missingFields = array();
		// Get rand attributes
		$randAttr = self::getRandomizationAttributes();
		// Check target field first
		if (!isset($Proj->metadata[$randAttr['targetField']])) {
			$missingFields[] = $randAttr['targetField'];
		}
		// Check strata
		foreach (array_keys($randAttr['strata']) as $field) {
			if (!isset($Proj->metadata[$field])) {
				$missingFields[] = $field;
			}
		}
		// Return array
		return $missingFields;
	}
	
	// Obtain rid key value (if exists) for this project (assumes that at least one allocation file exists)
	static function getRid()
	{
		$sql = "select rid from redcap_randomization where project_id = " . PROJECT_ID;
		$q = db_query($sql);
		if ($q && db_num_rows($q)) {
			return db_result($q, 0);
		}
		return false;
	}
	
	// Marks record as being randomized in allocation table
	static function randomizeRecord($record='',$fields=array(),$group_id='')
	{
		global $status;
		// Ensure that fields have all correct criteria fields. If not, return false to throw AJAX error msg.
		$criteriaFields = self::getRandomizationFields(false,true);
		if (count($fields) != count($criteriaFields)) return false;
		foreach (array_keys($fields) as $field) {
			if (!in_array($field, $criteriaFields)) return false;
		}
		// Create sql subquery for DAG
		$sqlsub .= "and a.group_id" . (is_numeric($group_id) ? " = $group_id " : " is null ");
		// Create sql subquery for strata critera
		foreach ($criteriaFields as $col=>$field) {
			$sqlsub .= "and a.$col = '".prep($fields[$field])."' ";
		}		
		// Query to get an aid key for these field value combinations
		$sql = "select a.aid from redcap_randomization_allocation a, redcap_randomization r
				where r.project_id = " . PROJECT_ID . " and r.rid = a.rid and a.project_status = $status
				and a.is_used_by is null $sqlsub order by a.aid limit 1";
		$q = db_query($sql);
		if (db_num_rows($q) < 1) {
			// Return as 0 to give error message about being already allocated
			return '0';		
		} else {
			// Get the NEXT aid matching our criteria
			$aid = db_result($q, 0);
			// Set record as randomized
			$sql = "update redcap_randomization_allocation set is_used_by = '".prep($record)."'
					where aid = $aid and project_status = $status";
			$q = db_query($sql);
			if ($q && db_affected_rows() > 0) {
				// Return the aid key
				return $aid;
			} else {
				// Return error
				return false;
			}
		}
	}
	
	// Build table as content of randomization widget dialog
	static function randomizeWidgetTable()
	{
		global $table_pk, $table_pk_label, $Proj, $lang, $longitudinal, $user_rights, $status;
		
		// Get randomization setup values first
		$randAttr = Randomization::getRandomizationAttributes();
		
		// Get randomization fields (and their corresponding events)
		$fields = self::getRandomizationFields(true);
		
		// Get unique list of all forms, then build ALL form elements for those forms, THEN remove ALL but the fields we need (easiest way)
		$distinctForms = array();
		$criteriaFields = array();
		$criteriaEvents = array();
		foreach ($fields as $field_desig=>$field) {
			if ($field_desig != 'target_field' && strpos($field_desig, '_event') === false) {
				// Criteria field
				$form = $Proj->metadata[$field]['form_name'];
				$distinctForms[$form] = true;
				$criteriaFields[] = $field;
				if (!$longitudinal) {
					// Criteria event
					$criteriaEvents[] = $Proj->firstEventId;
				}
			} elseif ($longitudinal && $field_desig != 'target_event' && strpos($field_desig, '_event') !== false) {
				// Criteria event
				$criteriaEvents[] = $field;
			}
		}
		
		// Build form elements
		$elements = array();
		foreach (array_keys($distinctForms) as $form) {		
			list ($elements1, $calc_fields_this_form, $branch_fields_this_form, $chkbox_flds) = buildFormData($form);
			$elements = array_merge($elements, $elements1);
		}
		
		// Loop through all form elements and remove ALL but our criteria fields
		foreach ($elements as $key=>$attr) {
			// Remove the field if not a randomization field
			if (!isset($attr['field']) || (isset($attr['field']) && ($attr['field'] == $fields['target_field'] || !in_array($attr['field'], $fields)))) {
				unset($elements[$key]);
			}
		}
		
		// Add context message header to table
		if (!empty($criteriaFields) || ($randAttr['group_by'] == 'DAG' && $user_rights['group_id'] == '')) 
		{
			$context_msg =  RCView::div(array('class'=>'blue'), 
								"{$lang['random_54']} " . RCView::escape($table_pk_label) . RCView::SP .
								RCView::b(RCView::escape($_POST['record'])) . $lang['random_55']
							);
			$context_msg_array = array(0=>array('rr_type'=>'header', 'css_element_class'=>'context_msg','value'=>$context_msg));
			$elements = array_merge($context_msg_array, $elements);
		}
		
		// DAG: If grouping by DAGS and user is NOT in a DAG, show drop-down of DAGS to choose from.
		if ($randAttr['group_by'] == 'DAG' && $user_rights['group_id'] == '')
		{
			// Format enum
			$dagEnum = array();
			foreach ($Proj->getGroups() as $code=>$label) {
				$dagEnum[] = "$code, $label";
			}
			// Get group_id for this record
			$group_id = '';
			$sql = "select value from redcap_data where project_id = ".PROJECT_ID." and record = '".prep($_POST['record'])."'
					and field_name = '__GROUPID__' limit 1";
			$q = db_query($sql);
			if (db_num_rows($q) > 0) {
				$value = db_result($q, 0);
				// Verify that this DAG belongs to this project
				if ($Proj->getGroups($value) !== false) {
					$group_id = $value;
				}
			}
			// Set DAG element as drop-down
			$dagDropDown = array('rr_type'=>'select','value'=>$group_id,'field'=>'redcap_data_access_group','name'=>'redcap_data_access_group',
								 'label'=>'<span style="color:#800000;">'.$lang['random_107'].'</span>', 'custom_alignment'=>'', 'enum'=>implode(" \\n ", $dagEnum));
			// Add to elements
			$elements = array_merge($elements, array($dagDropDown));
		}
		
		
		// Get field/event pair data from data table
		$datasql_sub = array();
		foreach ($fields as $field_desig=>$field) {
			if (strpos($field_desig, '_event') === false) {
				if ($field_desig == 'target_field') {
					// Randomization field
					$event_id = isset($fields['target_event']) ? $fields['target_event'] : $Proj->firstEventId;
					$datasql_sub[] = "(field_name = '$field' and event_id = $event_id)";
				} else {
					// Criteria field
					list ($nothing, $field_num) = explode("source_field", $field_desig);
					$event_field = "source_event" . $field_num;
					$event_id = isset($fields[$event_field]) ? $fields[$event_field] : $Proj->firstEventId;
					$datasql_sub[] = "(field_name = '$field' and event_id = $event_id)";
				}
			}
		}
		$datasql = "select field_name, value from redcap_data where	project_id = ".PROJECT_ID."	
					and record = '".prep($_POST['record'])."' and (" . implode(" or ", $datasql_sub) . ")";
		//Execute query and put any existing data into an array to display on form
		$q = db_query($datasql);
		$element_data = array();
		while ($row_data = db_fetch_array($q)) {
			//Checkbox: Add data as array
			if ($Proj->metadata[$row_data['field_name']]['element_type'] == 'checkbox') {
				$element_data[$row_data['field_name']][] = $row_data['value'];
			//Non-checkbox fields: Add data as string
			} else {
				$element_data[$row_data['field_name']] = $row_data['value'];
			}
		}
		
		## HTML Output
		print RCView::p(array('style'=>'margin:10px 0 20px;font-family:verdana;font-size:13px;'), 
				$lang['random_52'] . " $table_pk_label \"" . RCView::b($_POST['record']) . "\" " . 
				$lang['random_53'] . " " . RCView::b($Proj->metadata[$fields['target_field']]['element_label']) . 
				" (<i>" . $fields['target_field'] . "</i>)" . $lang['period'] . " " .
				(empty($criteriaFields) ? "" : $lang['random_62'])
			  );
		// Render field table inside the popup
		form_renderer($elements, $element_data);
		// Append a hidden input fields containing the variable names and events of the criteria field s
		// (to be used by javascript for value checks before randomizing)
		print RCView::hidden(array('id'=>'randomCriteriaFields', 'value'=>implode(",", $criteriaFields)));
		print RCView::hidden(array('id'=>'randomCriteriaEvents', 'value'=>implode(",", $criteriaEvents)));
		print RCView::div(array('class'=>'space','style'=>'padding:1px;'), " ");
		// If in development, display reminder that real subjects should not be randomized yet
		if ($status < 1) {
			print RCView::p(array('class'=>'yellow'), $lang['random_123']);
		}
	}
	
	// Returns count of how many criteria fields are utilized for an already-saved allocation table
	static function countCriteriaFields()
	{
		$sql = "select * from redcap_randomization where project_id = " . PROJECT_ID;
		$q = db_query($sql);
		$fields = db_fetch_assoc($q);
		for ($k = 1; $k <= self::MAXSOURCEFIELDS; $k++) {
			if ($fields['source_field'.$k] == "") break;
		}
		return ($k-1);
	}
	
	// Returns attributes about saved randomization setup
	static function getRandomizationAttributes()
	{
		// Store and return attr as array
		$randomizationAttributes = array();
		// First get randomization and strata
		list ($targetField, $targetEvent, $criteriaFields) = self::getRandomizationFieldsParsed();
		// Get other attributes
		$sql = "select stratified, group_by from redcap_randomization where project_id = " . PROJECT_ID;
		$q = db_query($sql);
		if (!($q && db_num_rows($q))) return false;
		$stratified = db_result($q, 0, 'stratified');
		$group_by = db_result($q, 0, 'group_by');
		// Return array of attributes
		return array('stratified'=>$stratified, 'group_by'=>$group_by, 'targetField'=>$targetField, 
					 'targetEvent'=>$targetEvent, 'strata'=>$criteriaFields);
	}
	
	// Returns (inside array) target field, target event_id, and array of criteria fields (with variable as key and event_id as value)
	static function getRandomizationFieldsParsed()
	{
		global $longitudinal, $Proj;
		// Place critera fields into array
		$criteriaFields = array();
		// Get all fields/events
		$randomizationCriteriaFields = self::getRandomizationFields(true);
		// Separate randomization field from criteria fields
		$targetField = array_shift($randomizationCriteriaFields);
		$targetEvent = ($longitudinal) ? array_shift($randomizationCriteriaFields) : $Proj->firstEventId;
		while (!empty($randomizationCriteriaFields)) {
			$field = array_shift($randomizationCriteriaFields);
			if ($longitudinal) {
				$event_id = array_shift($randomizationCriteriaFields);
				$criteriaFields[$field] = $event_id;
			} else {
				$criteriaFields[$field] = $Proj->firstEventId;
			}
		}
		// Return elements
		return array($targetField, $targetEvent, $criteriaFields);
	}
	
	// Returns array of randomization fields (target + all criteria fields)
	static function getRandomizationFields($returnEventIds=false,$returnOnlyCriteriaFields=false)
	{
		global $longitudinal;
		$sql = "select * from redcap_randomization where project_id = " . PROJECT_ID;
		$q = db_query($sql);
		$fields = db_fetch_assoc($q);
		unset($fields['rid'], $fields['project_id'], $fields['stratified'], $fields['group_by']);
		if (!$longitudinal || !$returnEventIds) {
			unset($fields['target_event']);
		}
		for ($k = 1; $k <= self::MAXSOURCEFIELDS; $k++) {
			if ($fields['source_field'.$k] == "") {
				unset($fields['source_field'.$k]);
			}
			if (!($longitudinal && $returnEventIds && $fields['source_event'.$k] != "")) {
				unset($fields['source_event'.$k]);
			}
		}
		if ($returnOnlyCriteriaFields) {
			unset($fields['target_field']);
		}
		return $fields;
	}
	
	// Render the allocation dashboard (all subjects/combinations)
	static function renderDashboardSubjects()
	{
		global $lang, $longitudinal, $status, $Proj, $table_pk, $table_pk_label;
		$html = '';
		
		// Check if it's setup yet. If not, then there's nothing to show.
		if (!self::setupStatus())
		{
			print $lang['random_50'];
			return;
		}
		
		// Instructions
		$html .= RCView::p(array(), 
			$lang['random_45'] . " " . ($status ? $lang['random_36'] : $lang['random_35']) . $lang['period'] .
			" " . $lang['random_41'] . " " . $lang['random_80']
		); 
		
		// Get randomization setup values and make sure allocation table exists for current project status
		$randAttr = self::getRandomizationAttributes();
		if ($randAttr === false || !self::allocTableExists($status)) 
		{
			print RCView::p(array('class'=>'yellow'),
				RCView::img(array('class'=>'imgfix','src'=>'exclamation_orange.png')) . 
				$lang['random_72'] . RCView::SP . ($status > 0 ? $lang['random_36'] : $lang['random_35']) . $lang['period'] . RCView::SP . 
				$lang['random_73'] . RCView::SP . ($status > 0 ? $lang['random_36'] : $lang['random_35']) . $lang['period']
			);
			return;
		}
		
		// Get the form and event that the randomization field is on
		$randomForm  = $Proj->metadata[$randAttr['targetField']]['form_name'];
		$randomEvent = $randAttr['targetEvent'];
		
		// Get allocation table as array and parse into rows to display as table
		$allocTableRows = explode("\n", self::getAllocFileContents($status,true));
		
		// Shift off header fields into their own array
		$hrdFields = array_shift($allocTableRows);
		$hrdFields = explode(",", $hrdFields);
		
		// Get the enum choices for all randomization fields
		$randomizationEnums = array();
		foreach ($hrdFields as $rfld) {
			if ($rfld == $table_pk) continue;
			if ($rfld == 'redcap_data_access_group') {
				$randomizationEnums['redcap_data_access_group'] = $Proj->getGroups();
			} else {
				$randomizationEnums[$rfld] = parseEnum($Proj->metadata[$rfld]['element_enum']);
			}
		}
			
		// Display allocated values as a table
		$tableData = array();
		foreach ($allocTableRows as $delimVals) 
		{
			if ($delimVals == "") continue;
			// Convert delimited values into array
			$row = explode(",", $delimVals);
			// Get allocated record, if any
			$record = array_shift($row);
			// Set completion icon for this row
			if ($record != "") {
				$completed = "<span style='display:none;'>1</span>".RCView::img(array('src'=>'accept.png','style'=>'vertical-align:middle;'));
				$recordLink = "<a style='font-size:11px;text-decoration:underline;' href='".APP_PATH_WEBROOT."DataEntry/index.php?pid=".PROJECT_ID."&page=$randomForm&event_id=$randomEvent&id=$record'>$record</a>";
			} else {
				$completed = "<span style='display:none;'>0</span>".RCView::img(array('src'=>'stop_gray.png','class'=>'opacity50','style'=>'vertical-align:middle;'));
				$recordLink = "";
			}
			// Set data values for each column and prepend with enum label
			$vals = array();
			$fldnum = 1;
			foreach ($row as $keynum=>$val) {
				$field_name = $hrdFields[$fldnum];
				$vals[] = RCView::div(array('class'=>'gridwrap'),
							(isset($randomizationEnums[$field_name][$val]) ? $randomizationEnums[$field_name][$val] : "???")
							. RCView::span(array('style'=>'color:#777;'), " ($val)")
						  );
				$fldnum++;
			}
			// Add values to data array
			$tableData[] = array_merge(array($completed, $recordLink), $vals);
		}
		// print_array($tableData);
		// exit;
		// Set width of each field column
		$colWidth = 150;
		// Set up the table headers
		$tableHeaders = array(
			array(30, "", "center"),			
			array(100, RCView::div(array('class'=>'gridwrap','style'=>'color:#800000;'), 
				RCView::b($table_pk_label) . RCView::SP . RCView::SP . "($table_pk)")
			)
		);
		// Loop through each column
		foreach ($hrdFields as $field) 
		{
			// Skip PK field (already added)
			if ($field == $table_pk) continue;
			// Customize field label for display
			if ($field == 'redcap_data_access_group') {
				$headerText = RCView::b($lang['global_78']);
			} else {
				$label = $Proj->metadata[$field]['element_label'];
				if (strlen($label) > 40) $label = substr($label, 0, 38) . "...";
				$headerText = RCView::b($label) . RCView::SP . RCView::SP . "($field)";
			}
			// Add header
			$header = RCView::div(array('class'=>'gridwrap'), $headerText);
			$tableHeaders[] = array($colWidth, $header);
		}
		// Set the table width
		$width = (130+(2*12)) + (count($tableHeaders)-2)*($colWidth+12);
		// Get html for the resources table
		$html .= renderGrid("randomization_dashboard_all", "", $width, "auto", $tableHeaders, $tableData, true, true, false);
		// Output to page
		print $html;
	}
	
	// Render the allocation dashboard (in groups)
	static function renderDashboardGroups()
	{
		global $lang, $longitudinal, $status, $Proj;
		$html = '';
		
		// Check if it's setup yet. If not, then there's nothing to show.
		if (!self::setupStatus())
		{
			print $lang['random_50'];
			return;
		}
		
		// Instructions
		$html .= RCView::p(array(), 
			$lang['random_45'] . " " . ($status ? $lang['random_36'] : $lang['random_35']) . $lang['period'] .
			" " . $lang['random_43'] . " " . $lang['random_80']
		); 
		
		// Get randomization setup values and make sure allocation table exists for current project status
		$randAttr = self::getRandomizationAttributes();
		if ($randAttr === false || !self::allocTableExists($status)) 
		{
			print RCView::p(array('class'=>'yellow'),
				RCView::img(array('class'=>'imgfix','src'=>'exclamation_orange.png')) . 
				$lang['random_72'] . RCView::SP . ($status > 0 ? $lang['random_36'] : $lang['random_35']) . $lang['period'] . RCView::SP . 
				$lang['random_73'] . RCView::SP . ($status > 0 ? $lang['random_36'] : $lang['random_35']) . $lang['period']
			);
			return;
		}
		
		// Obtain all allocated values and put into array
		$critFldsSql = array();
		for ($k = 1; $k <= count($randAttr['strata']); $k++) {
			$critFldsSql[] = "a.source_field" . $k;
		}
		$critFldsSql = (empty($critFldsSql) ? "" : ", " . implode(", ", $critFldsSql));
		
		// Add group_id to query if grouping by DAG
		$groupIdSql = ($randAttr['group_by'] == 'DAG') ? "a.group_id, " : "";
		
		// Query to get allocated values
		$sql = "select a.is_used_by, $groupIdSql a.target_field $critFldsSql
				from redcap_randomization_allocation a, redcap_randomization r 
				where r.project_id = " . PROJECT_ID . " and a.project_status = $status
				and r.rid = a.rid order by $groupIdSql a.target_field $critFldsSql";
		$q = db_query($sql);
		$allocValues = array();
		$hdrFields = array();
		$records = array();
		if (db_num_rows($q) > 0)
		{
			while ($row = db_fetch_assoc($q))
			{
				// Has it been used yet?
				$is_used_by = $row['is_used_by'];
				// Remove is_used_by in array so we can isolate just target and criteria field values
				unset($row['is_used_by']);			
				// Create array of the fields returned
				if (empty($hdrFields)) {
					$hdrFields = array_keys($row);
				}
				// Put values into string delimited by comma
				$delimVals = implode(",", $row);
				// Now put delimited values into array key to count instances of each
				if (isset($allocValues[$delimVals])) {
					//$allocValues[$delimVals]['count']++;
					if (!empty($is_used_by)) {
						$allocValues[$delimVals]['used']++;
					} else {
						$allocValues[$delimVals]['not_used']++;
					}
				} else {
					//$allocValues[$delimVals]['count'] = 1;
					if (!empty($is_used_by)) {
						$allocValues[$delimVals]['used'] = 1;
						$allocValues[$delimVals]['not_used'] = 0;
					} else {
						$allocValues[$delimVals]['used'] = 0;
						$allocValues[$delimVals]['not_used'] = 1;
					}
				}
				// Add record names for this row
				if ($is_used_by != "") {
					$records[$delimVals][] = $is_used_by;
				}
			}
			
			// Get array of randomization fields (target + criteria fields)
			$randomizationFields = $randomizationFieldsNums = self::getRandomizationFields();
			// Create array of randomization fields with numbers as keys
			$randomizationFieldsNums = array_values($randomizationFieldsNums);
			
			// Get the enum choices for all randomization fields
			$randomizationEnums = array();
			foreach ($randomizationFields as $rfld) {
				$randomizationEnums[$rfld] = parseEnum($Proj->metadata[$rfld]['element_enum']);
			}			
			// If grouping by DAG, then add DAG to rand field enums
			if ($randAttr['group_by'] == 'DAG') {
				array_unshift($randomizationFieldsNums, 'group_id');
				$randomizationEnums['group_id'] = $Proj->getGroups();
			}
		
			// Get the form and event that the randomization field is on
			$randomForm  = $Proj->metadata[$randAttr['targetField']]['form_name'];
			$randomEvent = $randAttr['targetEvent'];
			
			// Display allocated values as a table
			$tableData = array();
			foreach ($allocValues as $delimVals=>$attr) 
			{
				// Add some styling
				$completed = ($attr['not_used'] == 0) ? "<span style='display:none;'>1</span>".RCView::img(array('src'=>'accept.png','style'=>'vertical-align:middle;')) : "<span style='display:none;'>0</span>".RCView::img(array('src'=>'stop_gray.png','class'=>'opacity50','style'=>'vertical-align:middle;'));
				$attr['used'] = RCView::span(array('style'=>'color:green;'), $attr['used']);
				$attr['not_used'] = RCView::span(array('style'=>'color:red;'), $attr['not_used']);
				// Set data values for each column and prepend with enum label
				$vals = array();
				$fldnum = 0;
				foreach (explode(",", $delimVals) as $val) {
					$field_name = $randomizationFieldsNums[$fldnum];
					$vals[] = RCView::div(array('class'=>'gridwrap'),
								(isset($randomizationEnums[$field_name][$val]) ? $randomizationEnums[$field_name][$val] : "???")
								. RCView::span(array('style'=>'color:#777;'), " ($val)")
							  );
					$fldnum++;
				}
				// Set records allocated for this row
				$allocRecs = "";
				if (isset($records[$delimVals])) {
					foreach ($records[$delimVals] as $record) {
						$allocRecs .= "<a style='font-size:11px;text-decoration:underline;' href='".APP_PATH_WEBROOT."DataEntry/index.php?pid=".PROJECT_ID."&page=$randomForm&event_id=$randomEvent&id=$record'>$record</a>, ";
					}
					$allocRecs = substr($allocRecs, 0, -2);
				}
				// Add values to data array
				$tableData[] = array_merge(array($completed, $attr['used'], $attr['not_used'], $allocRecs), $vals);
			}
			// Set width of each field column
			$colWidth = 150;
			// Set up the table headers
			$tableHeaders = array(
				array(30, "", "center"),
				array(50, RCView::b($lang['random_46']), "center", "int"),
				array(50, RCView::b($lang['random_47']), "center", "int"),
				array(100, RCView::b($lang['random_127']))
			);
			// If grouping by DAG, then add DAG as header
			if ($randAttr['group_by'] == 'DAG') {
				// Add header
				$header = RCView::div(array('class'=>'gridwrap'), RCView::span(array('style'=>'color:#800000;font-weight:bold;'), $lang['global_78']));
				$tableHeaders[] = array($colWidth, $header);
			}
			// Set column number of randomization field
			$randFieldColNum = 4 + (($randAttr['group_by'] == 'DAG') ? 1 : 0);
			// Loop through each column
			foreach ($randomizationFields as $field) 
			{
				// Customize field label for display
				$label = $Proj->metadata[$field]['element_label'];
				if (strlen($label) > 40) $label = substr($label, 0, 38) . "...";
				// Set header text
				$headerText = RCView::b($label) . RCView::SP . RCView::SP . "($field)";
				// Make randomization field's font color red
				if (count($tableHeaders) == $randFieldColNum) {
					$headerText = RCView::span(array('style'=>'color:#800000;'), $headerText);
				}
				// Add header
				$header = RCView::div(array('class'=>'gridwrap'), $headerText);
				$tableHeaders[] = array($colWidth, $header);
			}
			// Set the table width
			$width = 279 + (count($tableHeaders)-4)*($colWidth+12);
			// Get html for the resources table
			$html .= renderGrid("randomization_dashboard", "", $width, "auto", $tableHeaders, $tableData, true, true, false);
		}
		// Output to page
		print $html;	
	}
	
	// Return the instructions for top of page
	static function renderInstructions()
	{
		global $lang;
		return	RCView::p(array(), 
					$lang['random_01'] . RCView::SP . 
					RCView::a(array('href'=>'javascript:;','onclick'=>" $('#instrdetails').toggle('fade');",'style'=>"text-decoration:underline;"), $lang['survey_86'])
				) .  
				RCView::div(array('style'=>'display:none;max-width:700px;border:1px solid #ccc;background-color:#eee;padding:0 10px;margin-bottom:20px;','id'=>'instrdetails'),
					RCView::p(array(), RCView::b($lang['random_22']) . RCView::br() .  $lang['random_24']) .
					RCView::p(array(), RCView::b($lang['random_25']) . RCView::br() .  $lang['random_28']) .
					RCView::p(array(), RCView::b($lang['random_16']) . RCView::br() .  $lang['random_101']) . 
					RCView::p(array(), RCView::b($lang['random_102']) . RCView::br() .  $lang['random_103'])
				);
	}
	
	// Render the steps for setting up randomization
	static function renderSetupSteps()
	{
		global $lang, $longitudinal, $status, $Proj;
		$html = '';
		
		// Get list of DAGS (to use in multi-site step)
		$dags = $Proj->getGroups();
						
		// Get criteria fields and target fields (and their events), if already set up
		$randomizationAttributes = self::getRandomizationAttributes();
		if ($randomizationAttributes !== false) {
			$targetField = $randomizationAttributes['targetField'];
			$targetEvent = $randomizationAttributes['targetEvent'];
			$criteriaFields = $randomizationAttributes['strata'];
			$disableSetup = true;
			// If grouping by a field, then remove last strata field to make it the group-by field
			if ($randomizationAttributes['group_by'] == 'FIELD') {
				$group_by_field_name = array_pop(array_keys($criteriaFields));				
				$group_by_field_event = array_pop($criteriaFields);
				unset($randomizationAttributes['strata'][$group_by_field_name]);
			}
		} else {
			$disableSetup = false;
		}
		
		## STEP 1
		// If already uploaded alloc table, then give note about why drop-downs are disabled
		if ($disableSetup) {
			$html .= RCView::p(array('class'=>'yellow','style'=>'font-size:arial;margin:0 0 15px;font-size:10px;'), 
						$lang['random_74'] . RCView::SP . ($status > 0 ? $lang['random_76'] : $lang['random_75'])
					);
		}
			   
		// 1A setup: (Stratified only) Text and field drop-down with button to add another drop-down
		$stratStepHtml = "";
		if (empty($criteriaFields)) {
			$stratStepHtml .= self::renderSingleDropDown();
			// Button to add more criteria fields
			$stratStepHtml .= RCView::div(array('style'=>'margin-left:15px;'),
						RCView::button(array('id'=>'addMoreFields','style'=>'font-size:11px;','onclick'=>'return false;'), $lang['random_03'])
					);
		} else {
			// Render all saved criteria fields and disable the drop-downs with field pre-selected
			foreach ($criteriaFields as $fld=>$evt) {
				$stratStepHtml .= self::renderSingleDropDown('randomVar',null,$fld,$evt,true);
			}
		}
		
		// Set scheme radion button's pre-defined values
		$scheme_selected = '';
		$scheme_disabled = '';
		if ($disableSetup) {
			$scheme_disabled = 'disabled';
			if ($randomizationAttributes['stratified']) {
				$scheme_selected = 'checked';	
			}
		}
		
		// Set pre-fill values for Randomize by Group
		$show_group_by = ($disableSetup && $randomizationAttributes['group_by'] != '');
		$group_by_checked = ($show_group_by) ? 'checked' : '';
		$group_by_disabled = ($disableSetup) ? 'disabled' : '';
		$group_by_dag_disabled = ($show_group_by) ? 'disabled' : '';
		$group_by_field_disabled = ($show_group_by) ? 'disabled' : '';
		$group_by_dag_checked = ($show_group_by && $randomizationAttributes['group_by'] == 'DAG') ? 'checked' : '';
		$group_by_field_checked = ($show_group_by && $randomizationAttributes['group_by'] == 'FIELD') ? 'checked' : '';
		$group_by_field_name = ($show_group_by && $randomizationAttributes['group_by'] == 'FIELD') ? $group_by_field_name : '';
		
		// Save and erase button disable
		$saveSetup = ($disableSetup) ? 'disabled' : '';
		$eraseSetup = ($disableSetup) ? '' : 'disabled';
		
		// Only allow super users to download allocation table when in production
		$downloadAllocTableProd = ($status < 1 || ($status > 0 && SUPER_USER)) ? "" : "disabled";
		
		// 1A: Choose randomization scheme (block vs. stratified)
		$html .= RCView::div(array(), 
					RCView::b($lang['random_78']) . RCView::SP . RCView::SP . 
					RCView::checkbox(array('name'=>'scheme','class'=>'imgfix2',$scheme_selected=>$scheme_selected,$scheme_disabled=>$scheme_disabled)) . 
					RCView::div(array('style'=>'margin-left:15px;padding:3px 0;'), 
						$lang['random_120'] . RCView::SP . 
						RCView::a(array('id'=>'schemeTellMore','href'=>'javascript:;','onclick'=>"$('#schemeExplain').toggle('fade');",'style'=>'text-decoration:underline;color:#800000;font-family:tahoma;font-size:10px;'), $lang['global_58'])
					) . 
					RCView::div(array('id'=>'schemeExplain','style'=>'color:#800000;border:1px solid #bbb;padding:4px;margin:4px 0 4px 15px;display:none;'),  $lang['random_79']) . 
					RCView::div(array('style'=>''), 
						RCView::div(array('id'=>'stratStep','style'=>(($disableSetup && $randomizationAttributes['stratified']) ? '' : 'display:none;').'margin:8px 0 0;color:#800000;'), 
							RCView::div(array('style'=>'margin-left:15px;'), RCView::b($lang['random_06']) . RCView::SP . $lang['random_38']) . 
							$stratStepHtml
						)
					)
				);	
				
		// 1B: Is multi-site study? If so, use DAG or "DAG"-field
		$html .= RCView::div(array('style'=>'margin:10px 0 0;'), 
					RCView::b($lang['random_81']) . RCView::SP . RCView::SP . 
					RCView::checkbox(array('id'=>'multisite_chk','class'=>'imgfix2',$group_by_checked=>$group_by_checked,$group_by_disabled=>$group_by_disabled)) .
					RCView::div(array('style'=>'margin-left:15px;padding:3px 0;'), $lang['random_92']) . 
					RCView::div(array('id'=>'multisite_options','style'=>'color:#800000;margin-left:15px;'.($show_group_by ? '' : 'display:none;')), 
						RCView::radio(array('name'=>'multisite','id'=>'multisite_dag','value'=>'dag',$group_by_dag_checked=>$group_by_dag_checked,$group_by_dag_disabled=>$group_by_dag_disabled,'onclick'=>(count($dags) > 0 ? '' : 'noDagWarning()'))) . $lang['random_83'] . RCView::b(count($dags)) . RCView::SP . $lang['random_84'] . RCView::br() .
						RCView::radio(array('name'=>'multisite','id'=>'multisite_field','value'=>'field',$group_by_field_checked=>$group_by_field_checked,$group_by_field_disabled=>$group_by_field_disabled)) . $lang['random_82'] . 
						self::renderSingleDropDown('randomVar','dagField',$group_by_field_name,$group_by_field_event,$disableSetup)
					)
				 );
		
		// 1C: Render the target randomization variable drop-down
		$html .= RCView::div(array('style'=>'margin:10px 0 0;'), 
					RCView::b($lang['random_04']) . 
					RCView::div(array('style'=>'margin-left:15px;'), $lang['random_37'])
				) . 
			    self::renderSingleDropDown('targetField','targetField',$targetField,$targetEvent,($targetField != ''));
				   
		// 1E: Save and Erase buttons (can only erase in Dev or if a super user)
		$eraseBtn = ($status < 1 || SUPER_USER) ? RCView::submit(array('value'=>$lang['random_94'],$eraseSetup=>$eraseSetup,'onclick'=>'return eraseSetup();')) : '';
		$html .= RCView::div(array('style'=>'margin:20px 0 0;'),
					RCView::submit(array('id'=>'saveModelBtn','value'=>$lang['random_93'],$saveSetup=>$saveSetup,'onclick'=>'return checkVarsSelected();')) . RCView::SP . RCView::SP . 
					$eraseBtn
				 );
		
		// Finalize Step 1: Put all step 1 substeps in a div together
		$html = RCView::div(array('class'=>'round chklist','style'=>'background-color:#eee;border:1px solid #ccc;padding:5px 15px 15px;'), 
					RCView::p(array('style'=>'color:#800000;font-weight:bold;font-size:13px;'), $lang['random_29']) . 
					RCView::p(array('style'=>'margin-bottom:15px;'), $lang['random_31']) . 
					RCView::form(array('name'=>'random_step1','action'=>APP_PATH_WEBROOT."Randomization/save_randomization_setup.php?pid=".PROJECT_ID,'method'=>'post','enctype'=>'multipart/form-data'), $html)
				);
				
		## STEP 2: DOWNLOAD TEMPLATE FILES: Add form with button to generate file
		$html .= RCView::div(array('id'=>'step2div','class'=>'round chklist','style'=>'background-color:#eee;border:1px solid #ccc;padding:5px 15px 15px;'), 
					RCView::p(array('style'=>'color:#800000;font-weight:bold;font-size:13px;'), $lang['random_07']) . 
					RCView::p(array(), 
						$lang['random_33'] . RCView::SP . 
						RCView::a(array('href'=>'javascript:;','onclick'=>" $('#step2details').toggle('fade');",'style'=>"text-decoration:underline;"), $lang['survey_86'])
					) .  
					RCView::p(array('style'=>'display:none;','id'=>'step2details'), $lang['random_100']) .
					RCView::button(array('onclick'=>"window.location.href=app_path_webroot+'Randomization/download_allocation_file_template.php?pid='+pid+'&example_num=1';"), $lang['random_05']) . RCView::SP . 
					RCView::button(array('onclick'=>"window.location.href=app_path_webroot+'Randomization/download_allocation_file_template.php?pid='+pid+'&example_num=2';"), $lang['random_34']) . RCView::SP . 
					RCView::button(array('onclick'=>"window.location.href=app_path_webroot+'Randomization/download_allocation_file_template.php?pid='+pid+'&example_num=3';"), $lang['random_104'])
				 );
		
		## STEP 3: FILE UPLOAD BUTTON
		$devAllocTableExists = self::allocTableExists(0);
		$prodAllocTableExists = self::allocTableExists(1);
		$html .= RCView::div(array('id'=>'step3div','class'=>'round chklist','style'=>'background-color:#eee;border:1px solid #ccc;padding:5px 15px 15px;'), 
					RCView::p(array('style'=>'color:#800000;font-weight:bold;font-size:13px;'), $lang['random_30']) . 
					RCView::p(array('style'=>''), $lang['random_32']) .
					// Reminder bullet list
					RCView::div(array('style'=>'margin-bottom:15px;'),
						RCView::div(array('style'=>'font-weight:bold;'), $lang['random_116']) .
						RCView::div(array('style'=>'margin-left:20px;text-indent:-8px;'), " &bull; " . $lang['random_113']) .
						RCView::div(array('style'=>'margin-left:20px;text-indent:-8px;'), " &bull; " . $lang['random_114']) .
						RCView::div(array('style'=>'margin-left:20px;text-indent:-8px;'), " &bull; " . $lang['random_115'])
					) .
					// Dev alloc file
					RCView::table(array('id'=>'devAllocUploadTable','cellspacing'=>'0','style'=>'border-top:1px dashed #bbb;padding:5px 0;'),
						RCView::tr(array(),
							RCView::td(array('valign'=>'top','style'=>'padding:15px;text-align:center;width:100px;'),
								RCView::img(array('src'=>($devAllocTableExists ? 'checkbox_checked.png' : 'checkbox_cross.png'))) .
								RCView::div(array(), ($devAllocTableExists ? "<span style='color:green;'>{$lang['random_44']}</span>" : "<span style='color:red;'>{$lang['random_39']}</span>"))
							) . 
							RCView::td(array('valign'=>'top','style'=>'padding:15px;'),
								RCView::div(array('style'=>''),
									RCView::div(array('style'=>'margin-bottom:5px;'), RCView::b($lang['random_08'] . RCView::SP . $lang['random_35'])) .
									($devAllocTableExists
										?
										// Already uploaded (with Download button)
										RCView::div(array(), 
											(($status < 1) 
												?
												RCView::SP . RCView::SP . RCView::SP .
												RCView::a(array("href"=>"javascript:;","style"=>"color:#800000;font-family:arial;font-size: 11px;text-decoration:underline;","onclick"=>"delAllocFile(0);"), $lang['random_64']) .
												RCView::button(array('onclick'=>"window.location.href=app_path_webroot+'Randomization/download_allocation_file.php?pid='+pid+'&status=0';"), $lang['random_63'])
												: 
												RCView::button(array('disabled'=>'disabled','onclick'=>"window.location.href=app_path_webroot+'Randomization/download_allocation_file.php?pid='+pid+'&status=0';"), $lang['random_63'])
											)
										)
										:
										// Upload file form
										(($status < 1 || SUPER_USER) 
											?
											RCView::form(array('name'=>'form','action'=>APP_PATH_WEBROOT."Randomization/upload_allocation_file.php?pid=".PROJECT_ID,'method'=>'post','enctype'=>'multipart/form-data'), 
												RCView::div(array(), RCView::file(array('type'=>'file','id'=>'allocFileDev','name'=>'allocFile'))) .
												RCView::div(array(), RCView::submit(array('id'=>'uploadFileBtn','value'=>$lang['random_09'],'onclick'=>'return checkFileUploadExt(0);'))) .  
												RCView::hidden(array('value'=>'0', 'name'=>'alloc_status'))
											)
											: 
											RCView::span(array('style'=>'color:#800000;'), 
												RCView::img(array('src'=>'exclamation.png','class'=>'imgfix')) . $lang['random_77']
											)
										)
									)
								)
							)
						)
					) . 
					// Prod alloc file
					RCView::table(array('cellspacing'=>'0','style'=>'border-top:1px dashed #bbb;padding:5px 0;'),
						RCView::tr(array(),
							RCView::td(array('valign'=>'top','style'=>'padding:15px;text-align:center;width:100px;'),
								RCView::img(array('src'=>($prodAllocTableExists ? 'checkbox_checked.png' : 'checkbox_cross.png'))) .
								RCView::div(array(), ($prodAllocTableExists ? "<span style='color:green;'>{$lang['random_44']}</span>" : "<span style='color:red;'>{$lang['random_39']}</span>"))
							) . 
							RCView::td(array('valign'=>'top','style'=>'padding:15px;'),
								RCView::div(array('style'=>''),
									RCView::div(array('style'=>'margin-bottom:5px;'), RCView::b($lang['random_08'] . RCView::SP . $lang['random_36'])) .
									($prodAllocTableExists
										?
										// Already uploaded (with Download button)
										RCView::div(array(),
											(($status < 1) 
												?
												RCView::SP . RCView::SP . RCView::SP .
												RCView::a(array("href"=>"javascript:;","style"=>"color:#800000;font-family:arial;font-size: 11px;text-decoration:underline;","onclick"=>"delAllocFile(1);"), $lang['random_64']) .
												RCView::button(array('onclick'=>"window.location.href=app_path_webroot+'Randomization/download_allocation_file.php?pid='+pid+'&status=1';"), $lang['random_63'])
												: 
												RCView::button(array($downloadAllocTableProd=>$downloadAllocTableProd,'onclick'=>"window.location.href=app_path_webroot+'Randomization/download_allocation_file.php?pid='+pid+'&status=1';"), $lang['random_63']) .
												RCView::SP . RCView::span(array('style'=>'color:#333;font-size:11px;'), $lang['random_126'])
											)
										)
										:
										""
									) .
									// Upload file form (if in dev status OR allow super users to ADD while in prod)
									((($status < 1 && !$prodAllocTableExists) || SUPER_USER) 
										?
										(($status > 0 && SUPER_USER)
											?
											RCView::div(array('style'=>'padding:5px 0;'), $lang['random_125']) . 
											RCView::div(array('style'=>'padding:5px 0;'),
												RCView::a(array('href'=>'javascript:;','style'=>'color:#800000;text-decoration:underline;','onclick'=>" $('#prodUploadForm').toggle('fade');"), 
													$lang['random_124']
												)
											)
											:
											""
										) . 
										RCView::form(array('style'=>((($status < 1 && $prodAllocTableExists) || ($status > 0 && SUPER_USER)) ? "display:none;" : ""), 'id'=>'prodUploadForm', 'name'=>'form','action'=>APP_PATH_WEBROOT."Randomization/upload_allocation_file.php?pid=".PROJECT_ID,'method'=>'post','enctype'=>'multipart/form-data'), 
											RCView::div(array(), RCView::file(array('type'=>'file','id'=>'allocFileProd','name'=>'allocFile'))) .
											RCView::div(array(), RCView::submit(array('id'=>'uploadFileBtn','value'=>$lang['random_09'],'onclick'=>'return checkFileUploadExt(1);'))) .
											RCView::hidden(array('value'=>'1', 'name'=>'alloc_status'))
										)
										: 
										($status > 0
											?
											RCView::span(array('style'=>'color:#800000;'), 
												RCView::img(array('src'=>'exclamation.png','class'=>'imgfix')) . $lang['random_77'] .
												RCView::SP . $lang['random_125']
											)
											:
											""
										)
									)
								)
							)
						)
					)
				);
		// Output to page
		print $html;		
	}	
	
	// Render single variable drop-downs with either categorical fields or calc fields
	static function renderSingleDropDown($class='randomVar',$idname=null,$selectedFldVal='',$selectedEvtVal='',$disabled=false)
	{
		global $Proj, $table_pk, $lang, $longitudinal;
		// Set list of categorical field types (exclude checkboxes since they can have multiple values)
		$catFields = array('radio', 'select', 'advcheckbox', 'yesno', 'truefalse');
		// Set string for disabled value, if disabled
		$disabled = ($disabled ? 'disabled' : '');
		// Array for collecting fields
		$fields = array(''=>'- '.$lang['random_02'].' -');
		// Get field list with labels
		foreach ($Proj->metadata as $field=>$attr)
		{
			// Exclude first field and Form Status fields
			if ($field == $table_pk || $field == $attr['form_name']."_complete") continue;
			// Is categorical (or calc)?
			if (in_array($attr['element_type'], $catFields)) // || $attr['element_type'] == 'calc')
			{
				$fields[$field] = $field . " (" . $attr['element_label'] . ")";
			}
		}
		// Return the HTML for the drop-down inside a div
		if (empty($idname)) {
			$rand      = rand(0,999999);
			$idname    = 'fld_'.$rand;
			$idnameEvt = 'evt_'.$rand;
		} else {
			$idnameEvt = $idname.'Evt';
		}
		if ($longitudinal) {
			// If longitudinal, also render and event drop-down
			$field_dd = RCView::select(array($disabled=>$disabled,'class'=>$class, 'id'=>$idname, 'name'=>$idname), $fields, $selectedFldVal, 45);
			// Collect event names/ids into array
			$event_dd_info = array();
			foreach ($Proj->eventInfo as $event_id=>$attr) {
				$event_dd_info[$event_id] = $attr['name_ext'];
			}			
			$event_dd = RCView::span(array('style'=>'margin:0 3px;'), $lang['data_entry_67']) 
					  . RCView::select(array($disabled=>$disabled,'class'=>$class.'Evt', 'id'=>$idnameEvt, 'name'=>$idnameEvt), $event_dd_info, $selectedEvtVal, 40);
		} else {
			// Non-longitudinal
			$field_dd = RCView::select(array($disabled=>$disabled,'class'=>$class, 'id'=>$idname, 'name'=>$idname), $fields, $selectedFldVal, 70);
			$event_dd = "";
		}
		return RCView::div(array('class'=>$class.'Parent','style'=>'margin-left:15px;'), $field_dd.$event_dd);
	}
	
	// Check the values from the uploaded allocation file for integrity (accepts array beginning with key 0)
	static function checkAllocFile(&$csv_array)
	{
		global $lang, $Proj, $longitudinal, $status;
		
		// Get randomization setup values first so we know what to check for
		$randAttr = self::getRandomizationAttributes();
		if ($randAttr === false) redirect(APP_PATH_WEBROOT . 'Randomization/index.php?pid=' . PROJECT_ID);
		
		// Array to store any error msgs
		$errorMsg = array();
		
		// Make sure not empty
		if (empty($csv_array)) {
			$errorMsg[] = $lang['random_14'];
			return $errorMsg;
		}
		
		## Determine header fields and compare with headers in uploaded file
		$hdrFields = array();
		// Target field
		$hdrFields[] = $randAttr['targetField'];
		// Strata fields
		foreach ($randAttr['strata'] as $field=>$event_id) {
			$hdrFields[] = $field;
		}
		// DAG pseudo-field
		if ($randAttr['group_by'] == 'DAG') {
			$hdrFields[] = 'redcap_data_access_group';
		}
		
		// If there exist blank cells in row 1 after the variable names, then delete those columns
		$numColsTotal = count($hdrFields);				
		foreach ($csv_array as $row=>$row_values) 
		{
			// If rows has more than $numCols columns, then remove those extra columns
			$numColsThisRow = count($row_values);
			if ($numColsThisRow > $numColsTotal) {
				for ($k=$numColsTotal; $k<$numColsThisRow; $k++) {
					unset($csv_array[$row][$k]);
				}
			}
			// Remove any blank rows
			if (implode("", $csv_array[$row]) == "") {
				unset($csv_array[$row]);
			}
		}
		// Reindex array
		$csv_array = array_values($csv_array);
		
		// Make sure the headers in the first row are correct
		if ($hdrFields !== $csv_array[0]) {
			$errorMsg[] = $lang['random_15'] . ' "' . implode(",", $hdrFields) . '"';
			return $errorMsg;
		}
		
		// Make sure the file contains more than just the first row
		if (count($csv_array) === 1) {
			$errorMsg[] = $lang['random_18'];
			return $errorMsg;
		}
		
		// Check raw values: Make sure that ALL values are not blank and correspond to real choices for each field
		$choices_all = array();
		foreach ($csv_array[0] as $field) 
		{
			if ($randAttr['group_by'] == 'DAG' && $field == 'redcap_data_access_group') {
				// DAG field
				$choices_all[] = $Proj->getGroups();
			} else {
				if ($Proj->metadata[$field]['element_type'] == "yesno") {
					// Load status yesno choices
					$enum = YN_ENUM;
				} elseif ($Proj->metadata[$field]['element_type'] == "truefalse") {
					// Load status truefalse choices
					$enum = TF_ENUM;
				} else {
					// Normally coded MC field
					$enum = $Proj->metadata[$field]['element_enum'];
				}
				$choices_all[] = parseEnum($enum);
			}
		}
		foreach ($csv_array as $key=>$values) {
			// Skip first row
			if ($key == 0) continue;
			// Loop through each col in this row
			foreach ($values as $field_num=>$value) {
				// Check if the value is a real categorical option
				if (!isset($choices_all[$field_num][$value])) {
					$field = $csv_array[0][$field_num];
					if ($longitudinal) {
						// Separate unique event name from field
						list ($unique_evt_name, $field) = explode(":", $field, 2);
					}
					$errorMsg[] = "\"$value\" " . $lang['random_20'] . " \"$field\"";
				}
			}
		}
		
		// Check if allocation table already exists for the given project status (prevent uploading again)
		if (self::allocTableExists($_POST['alloc_status'])) {
			// Allow super users to append to allocation table while in production
			if (!($_POST['alloc_status'] == '1' && $status > 0 && SUPER_USER)) {
				// Add error message
				$errorMsg[] = $lang['random_42'] . " " . ($_POST['alloc_status'] == '1' ? $lang['random_36'] : $lang['random_35']);
			}
		}
		
		// Return errors
		return $errorMsg;
	}
	
	// Store the values from the uploaded allocation file in the database tables
	static function saveAllocFile($csv_array)
	{
		global $lang, $Proj, $longitudinal;	
		// Count query errors
		$errors = 0;	
		// Get randomization setup values first so we know what to check for
		$randAttr = self::getRandomizationAttributes();
		// Get rid key
		$rid = self::getRid();
		// Create sql column string
		$sqlColNamesAlloc = array('target_field');
		for ($k = 1; $k <= count($randAttr['strata']); $k++) {
			$sqlColNamesAlloc[] = 'source_field' . $k;
		}
		if ($randAttr['group_by'] == 'DAG') {
			$sqlColNamesAlloc[] = 'group_id';
		}	
		// print_array($randAttr);
		// print_array($sqlColNamesAlloc);
		// print_array($csv_array);
		// exit;
		// Store allocation file values	
		for ($k = 1; $k < count($csv_array); $k++) 
		{
			// First, escape all values
			foreach ($csv_array[$k] as &$val) {
				$val = prep($val);
			}
			// Add row to table
			$sql = "insert into redcap_randomization_allocation 
					(rid, project_status, " . implode(", ", $sqlColNamesAlloc) . ")
					values ($rid, {$_POST['alloc_status']}, '" . implode("', '", $csv_array[$k]) . "')";
			if (!db_query($sql)) $errors++;
		}
		// Return true if success w/ no errors
		return ($errors === 0);
	}
	
	// Output contents of existing allocation table
	static function getAllocFileContents($status,$returnAllocatedRecordName=false)
	{
		global $longitudinal, $Proj, $table_pk;	
		
		// Get randomization setup values
		$randAttr = self::getRandomizationAttributes();
		if ($randAttr === false) exit($lang['random_11']);
		
		## Create file header 
		$hdrFields = array();
		// Target field
		$hdrFields[] = $randAttr['targetField'];
		// Strata fields
		foreach ($randAttr['strata'] as $field=>$event_id) {
			$hdrFields[] = $field;
		}
		// DAG pseudo-field
		$groupIdSql = "";
		if ($randAttr['group_by'] == 'DAG') {
			$hdrFields[] = 'redcap_data_access_group';
			$groupIdSql = ", a.group_id";
		}
		
		// Build parts of sql query to use to pull allocated values
		$critFldsSql = array();
		$numCritFields = count($randAttr['strata']);
		for ($k = 1; $k <= $numCritFields; $k++) {
			$critFldsSql[$k-1] = "a.source_field" . $k;
		}
		$critFldsSql = ($numCritFields > 0) ? ", ".implode(", ", $critFldsSql) : "";
		
		// Return the record name if a record has been allocated for a given row
		$returnRecordSql = "";
		if ($returnAllocatedRecordName) {
			$returnRecordSql = "a.is_used_by, ";
			array_unshift($hdrFields, $table_pk);
		}
		
		// Obtain all allocated values and put into array
		$sql = "select $returnRecordSql a.target_field $critFldsSql $groupIdSql
				from redcap_randomization_allocation a, redcap_randomization r 
				where r.project_id = " . PROJECT_ID . " and a.project_status = $status
				and r.rid = a.rid order by a.aid";
		$q = db_query($sql);
		$allocValues = array();
		while ($row = db_fetch_assoc($q)) {
			// Save values in array
			$allocValues[] = $row;
		}
		// Output allocation table as CSV rows
		$output = "";
		foreach (array_merge(array($hdrFields), $allocValues) as $col)
		{
			$output .= implode(",", $col) . "\n";
		}
		return $output;
	}
	
	// Save the randomization setup in the randomization table
	static function saveRandomizationSetup($fields)
	{
		global $lang, $Proj, $longitudinal;
		
		// Check the scheme
		$stratified = (isset($fields['scheme']) && $fields['scheme'] == 'on') ? 1 : 0;
		unset($fields['scheme']);
		
		// Check if we're using DAGs as pseudo-field
		$useDags = (isset($fields['multisite']) && $fields['multisite'] == 'dag');
				
		// Make sure we have all the fields we need
		$haveSourceField = false;
		$haveTargetField = false;
		$sourceFields = array();
		$targetField  = null;
		foreach ($fields as $name=>$field)
		{
			// Criteria field(s) or DAG field
			if ($name == 'dagField' || ($stratified && substr($name, 0, 4) == 'fld_')) {
				if ($field != '') {
					$haveSourceField = true;
					// If longitudinal, also get event_id and validate it
					if ($longitudinal) {
						$event_id = ($name == 'dagField') ? $fields[$name.'Evt'] : $fields['evt_'.substr($name, 4)];
						// If not valid, then give it first event_id
						if (!$Proj->validateEventId($event_id)) $event_id = $Proj->firstEventId;
					} else {
						$event_id = $Proj->firstEventId;
					}
					// Add to array
					$sourceFields[$field] =  $event_id;
				}
			} 
			// Target field
			elseif ($name == 'targetField') {
				$haveTargetField = true;
				// Set variable
				$targetField = $field;
				// If longitudinal, also get event_id and validate it
				$targetFieldEvent = ($longitudinal) ? $fields['targetFieldEvt'] : '';
			}
		}
		
		// Do completion check
		if (($stratified && !$haveSourceField) || !$haveTargetField) {
			return false;
		}
		
		// Make sure all fields are real project fields
		$allFieldsAreReal = (isset($Proj->metadata[$targetField]));
		foreach (array_keys($sourceFields) as $field)
		{
			if (!isset($Proj->metadata[$field])) {
				$allFieldsAreReal = false;
			}
		}
		if (!$allFieldsAreReal) return false;
		
		// Create sql string for source fields
		$srcFldSqlNames = $srcFldSqlValues = array();
		$k = 1;
		foreach ($sourceFields as $field=>$event_id) {
			// Add sql field names and their values
			$srcFldSqlNames[] = 'source_field' . $k . ', source_event' . $k;
			$srcFldSqlValues[] = "'$field', ".checkNull($event_id);
			// Increment counter
			$k++;
		}
		$srcFldSqlNames = (empty($srcFldSqlNames) ? "" : ", ") . implode(", ", $srcFldSqlNames);
		$srcFldSqlValues = (empty($srcFldSqlValues) ? "" : ", ") . implode(", ", $srcFldSqlValues);
		
		// Insert into table
		$sql = "insert into redcap_randomization (project_id, stratified, group_by, target_field, target_event $srcFldSqlNames)
				values (" . PROJECT_ID . ", $stratified, " . checkNull(strtoupper($fields['multisite'])) . ", '$targetField', " . checkNull($targetFieldEvent) . " $srcFldSqlValues)";
		if (!db_query($sql)) {
			return false;
		}
		
		// If any data exists for the randomization field, then delete it (user has already been warned about this)
		self::deleteSingleFieldData($targetField);
		
		// Return success
		return true;
	}
	
	// Output contents of allocation template file based upon posted fields selected in setup
	static function getAllocTemplateFileContents($example_num)
	{
		global $lang, $Proj, $longitudinal;
		
		// Get randomization setup values
		$randAttr = self::getRandomizationAttributes();
		if ($randAttr === false) exit($lang['random_11']);
		
		// Get formatted target field/event
		$targetField = $randAttr['targetField'];
		
		// Get formatted strata (criteria source fields)
		$sourceFields = array();
		foreach ($randAttr['strata'] as $field=>$event_id) {
			$sourceFields[] = $field;
		}
		
		// If using DAGs to group by
		$useDags = ($randAttr['group_by'] == 'DAG');
		
		// Return the output string
		return self::generateAllocFileCSV($randAttr['stratified'], $targetField, $sourceFields, $example_num, $useDags);
	}
	
	// Create CSV output for example allocation files
	static function generateAllocFileCSV($stratified=null, $targetField=null, $sourceFields=array(), $example_num, $useDags=false)
	{
		global $lang;
		// Set output string beginning with headers
		$headers = $targetField;
		if (!empty($sourceFields)) {
			$headers .= "," . implode(",", $sourceFields);
		}
		if ($useDags) {
			$headers .= ",redcap_data_access_group";
		}
		// Create combos for all source fields utilized and output them to file
		$output = $headers;
		// Return combinations
		foreach (self::getSourceFieldCombos($sourceFields, $targetField, $example_num, $useDags) as $row) {
			$output .= "\n$row";
		}
		// Add annotations to CSV output as help notes
		$output = self::addAllocFileAnnotations($output, $useDags, $targetField, $sourceFields);
		// Return file contents
		return $output;
	}
	
	// Add annotations to CSV output as help notes
	static function addAllocFileAnnotations($output, $useDags, $targetField, $sourceFields)
	{
		global $lang, $Proj;
		
		## Set all notes text here
		// Use $notes array to place notes in CSV begining with row 2 (skip one column)
		$notes = array("", "", $lang['random_91'], " ".$lang['random_88'], " ".$lang['random_121'], " ".$lang['random_89'], " ".$lang['random_106']);
		// Add all multile choices raw values and their labels for randomization field and strata
		foreach (array_merge(array($targetField), $sourceFields) as $field) {
			// Format field label
			$label = label_decode($Proj->metadata[$field]['element_label']);
			if (strlen($label) > 40) $label = substr($label, 0, 27) . "..." . substr($label, -10);
			// Set initial line
			$notes[] = "";
			$notes[] = $lang['random_90'] . " \"$field\" ($label)" . $lang['colon'];
			// Set lines for all choices
			foreach (parseEnum($Proj->metadata[$field]['element_enum']) as $code=>$label) {
				$notes[] = array($code, label_decode($label));
			}			
		}
		// print_array($sourceFields);
		// print_array($notes);
		// exit;
		// Add notes about DAGs and list them by id/name
		if ($useDags) {
			$notes[] = "";
			$notes[] = $lang['random_90'] . " \"redcap_data_access_group\" " . $lang['random_105'] . $lang['colon'];
			foreach ($Proj->getGroups() as $gid=>$gname) {
				$notes[] = array($gid, label_decode($gname));
			}
		}
		
		## Integrate notes in existing CSV data
		// Get the column number where notes will begin and put in new string
		$outputRows = explode("\n", $output);
		// Reset string
		$output = "";
		// Loop through each row and add any notes
		foreach ($outputRows as $key=>$this_row) 
		{
			// Add row to new string
			$output .= $this_row;
			// Check if note should be added to row
			$rowHasNote = (isset($notes[$key]) && $notes[$key] != "");
			if ($rowHasNote) {
				$output .= ",," . self::convertAllocFileNote($notes[$key]);
			}
			// End line
			$output .= "\n";
		}
		// If $notes array is larger than $outputRows, then finish with the rest of $notes
		$numNotes = count($notes);
		$numOutputRows = count($outputRows);
		if ($numNotes > $numOutputRows)
		{
			// Get number of columns in existing CSV before notes are added
			$numCols = substr_count($outputRows[0], ",")+2;
			// Loop through remaining notes
			for ($k=$numOutputRows; $k<$numNotes; $k++) {
				$output .= str_repeat(",", $numCols) . self::convertAllocFileNote($notes[$k]) . "\n";
			}			
		}
		// Return CSV formatted string
		return $output;
	}
	
	// Parse allocation file notes/annnotations as string or array and return as CSV escaped element 
	static function convertAllocFileNote($string)  
	{
		$noteElement = "";
		if (is_array($string)) {
			foreach ($string as $element) {
				$noteElement .= ',"' . str_replace('"', '""', $element) . '"';
			}
		} else {
			$noteElement = ',"' . str_replace('"', '""', $string) . '"';
		}
		return $noteElement;
	}
	
	// Create array of combinations
	static function getCombinations($elements)  
	{
		// Store resulting value strings as array elements (one element per row output to file)
		$combos = array();
		// Loop through each field's choices
		foreach ($elements as $choices) 
		{
			if (empty($combos)) {
				// If first field, then simply add to array as is
				$combos = $choices;
			} else {
				// If not first field, then append to all existing
				$combos_temp = $combos;
				//Append this field's choices for each section
				for ($k = 0; $k < count($combos_temp); $k++) {
					$combos[$k] = $combos[$k] . "," . $choices[0];
				}
				for ($i = 1; $i < count($choices); $i++) {
					// Add new section for each new choice
					$combos = array_merge($combos, $combos_temp);
					//Append this field's choices for each section
					$countExisting = count($combos);
					$countThisSection = count($combos_temp);
					for ($k = ($countExisting-$countThisSection); $k < $countExisting; $k++) {
						$combos[$k] = $combos[$k] . "," . $choices[$i];
					}
				}		
			}
		}
		// Return array
		return $combos;
	} 
	
	// Create combos for all source fields utilized
	static function getSourceFieldCombos($sourceFields, $targetField, $example_num, $useDags)
	{
		global $Proj, $longitudinal;
		// If outputing "all possible combos", then insert target field
		if ($example_num == 2 || $example_num == 3) {
			$sourceFields = array_merge(array($targetField), $sourceFields);
		}
		// If we're using DAGs as a pseudo-field, add DAG group_ids as enum choices
		if ($useDags) {
			$dags = $Proj->getGroups();
			if (!empty($dags)) {
				$sourceFields = array_merge($sourceFields, array('redcap_data_access_group'));
			}
		}		
		// Store all categories for every source field into array
		$sourceFieldEnum = array();
		foreach ($sourceFields as $field) {
			if ($field == 'redcap_data_access_group') {
				$sourceFieldEnum[] = array_keys($dags);
			} else {
				$element_type = $Proj->metadata[$field]['element_type'];
				$element_enum = $Proj->metadata[$field]['element_enum'];
				// Convert sql field types' query result to an enum format
				if ($element_type == "sql") {
					$element_enum = getSqlFieldEnum($element_enum);
				}
				// Load status yesno choices
				elseif ($element_type == "yesno") {
					$element_enum = YN_ENUM;
				}
				// Load status truefalse choices
				elseif ($element_type == "truefalse") {
					$element_enum = TF_ENUM;
				}
				$sourceFieldEnum[] = array_keys(parseEnum($element_enum));
			}
		}
		// Create array of combinations
		$combos = self::getCombinations($sourceFieldEnum);	
		// If outputing "basic" example, then distribute targetField choices over all existing source combos
		if ($example_num == 1) {
			$targetFieldEnum = array_keys(parseEnum($Proj->metadata[$targetField]['element_enum']));
			$targetFieldEnumCount = count($targetFieldEnum);
			$enumKey = 0;
			if (!empty($sourceFields)) {
				// Stratified scheme
				foreach ($combos as $key=>$combo) {
					// Append target field enum onto existing combo
					$combos[$key] = $targetFieldEnum[$enumKey] . "," . $combo;
					// Increment target field's enum key for next loop
					$enumKey = ($enumKey >= $targetFieldEnumCount-1) ? 0 : $enumKey+1;
				}
			} else {
				// Block scheme
				$combos = $targetFieldEnum;
			}
		}
		// If outputting 5x all possible combos for example 3
		elseif ($example_num == 3) {
			$combos = array_merge($combos, $combos, $combos, $combos, $combos);
		}
		// Return combos
		return $combos;
	}
	
}