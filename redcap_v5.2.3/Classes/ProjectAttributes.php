<?php

/**
 * PROJECT ATTRIBUTES
 * Object that holds all basic attributes of a project (metadata fields and forms, events, project-level values)
 */
class ProjectAttributes 
{
	// Maximum length of grid_name string in metadata (i.e. matrix group name)
	const GRID_NAME_MAX_LENGTH = 60;
	// Array with project's basic values
	public $project = array();
	// Array with field_names as keys and other attributes as sub-array
	public $metadata = array();
	// Array of Draft Mode fields with field_names as keys and other attributes as sub-array
	public $metadata_temp = array();
	// Record identifer variable
	public $table_pk;
	public $table_pk_temp;
	// Record identifer variable's status as PHI
	public $table_pk_phi;
	// Record identifer variable's label
	public $table_pk_label;
	// Array of form names with form_position and form menu description
	public $forms = array();
	public $forms_temp = array();
	// Array of events
	public $events = array();
	// Array of events and forms (event_id as array keys)
	public $eventsForms = array();
	// Array of event information (event_id as array keys)
	public $eventInfo = array();
	// Array of unique event names
	public $uniqueEventNames = null;
	// Array of unique Data Access Group names
	public $uniqueGroupNames = null;
	// Array of surveys (survey_id as array keys)
	public $surveys = array();
	// Flag if fields are out of order
	public $fieldsOutOfOrder = false;
	// Number of project fields
	public $numFields = 0;
	public $numFieldsTemp = 0;
	// Number of data entry forms
	public $numForms = 0;
	public $numFormsTemp = 0;
	// Number of arms
	public $numArms = 1;
	// Number of events
	public $numEvents = 0;
	// If project is longitudinal (has multiple events)
	public $longitudinal = false;
	// If project has multiple arms
	public $multiple_arms = false;
	// First form_name
	public $firstForm;
	// First form_menu_description name
	public $firstFormMenu;
	// First arm_id
	public $firstArmId = null;
	// First arm name
	public $firstArmName = null;
	// First arm number
	public $firstArmNum = null;
	// First event_id
	public $firstEventId = null;
	// survey_id of first form
	public $firstFormSurveyId = null;
	// First event name
	public $firstEventName = null;
	// Contains forms downloaded from the REDCap Shared Library
	public $formsFromLibrary = null;	
	// Array of all Data Access Groups
	public $groups = null;	
	// Array of all users in Data Access Groups
	public $groupUsers = null;
	// Array of unique list of grid_names (i.e. matrix group names) in metadata
	public $matrixGroupNames     = null;
	public $matrixGroupNamesTemp = null;
	
	// Constructor
	public function __construct() 
	{
		$this->loadProjectValues();
		$this->loadMetadata();
		$this->loadEvents();
		$this->loadEventsForms();
		$this->loadSurveys();
		$this->checkAutoNumbering();
	}
	
	// Load this project's basic values from redcap_projects
	private function loadProjectValues() 
	{
		$sql = "select SQL_CACHE * from redcap_projects where project_id = " . PROJECT_ID;
		$q = db_query($sql);
		foreach (db_fetch_assoc($q) as $key=>$value) 
		{
			$this->project[$key] = $value;
		}
		db_free_result($q);
	}
	
	// Load this project's metadata into array
	private function loadMetadata() 
	{
		// Initialize
		$this->numFields = 0;
		$this->numForms  = 0;
		$this->metadata = array();
		$this->lastFormName = null;
		
		// Loop through all fields
		$sql = "select SQL_CACHE * from redcap_metadata where project_id = " . PROJECT_ID . " order by field_order";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q))
		{
			// If project somehow has a blank field_name, delete it
			if ($row['field_name'] == "")
			{
				// Delete it
				db_query("delete from redcap_metadata where project_id = " . PROJECT_ID . " and field_name = ''");
				// Now skip to next field
				continue;
			}
			// If form menu is somehow missing for first field of a form (how?), set its value as the form_name
			if ($this->lastFormName != $row['form_name'] && $row['form_menu_description'] == "")
			{
				$row['form_menu_description'] = trim(ucwords(str_replace("_", " ", $row['form_name'])));
				// Now actually fix it in the table to prevent from messing other things up downstream
				$sql = "update redcap_metadata set form_menu_description = '".prep($row['form_menu_description'])."' 
						where project_id = " . PROJECT_ID . " and field_name = '".prep($row['field_name'])."'";
				db_query($sql);
			}
			// Add form names with form_position and form menu description
			if ($this->numFields == 0 || $row['form_menu_description'] != "")
			{
				if (!isset($this->forms[$row['form_name']])) {
					// Add form menu and number
					$this->forms[$row['form_name']] = array( 'form_number'	=> (count($this->forms) + 1), 
															 'menu' 		=> label_decode($row['form_menu_description']) );
				} else {
					// This field should NOT have a form_menu_description, so remove it from the table
					$sql = "update redcap_metadata set form_menu_description = null
							where project_id = ".PROJECT_ID." and field_name = '".prep($row['field_name'])."' limit 1";
					db_query($sql);
				}
				// For first field, set values to be global variables later
				if ($this->numForms == 0) 
				{
					$this->table_pk 	  = $row['field_name'];					
					$this->table_pk_order = $row['field_order'];
					$this->table_pk_phi   = ($row['field_phi'] == "1");
					$this->table_pk_label = htmlspecialchars(filter_tags(label_decode($row['element_label'])), ENT_QUOTES);
					// Set first form variables
					$this->firstForm 	  = $row['form_name'];
					$this->firstFormMenu  = $row['form_menu_description'];
				}
				// Increment form count
				$this->numForms++;
			}
			// If field has a legacy date validation, then update it to non-legacy on the fly for this $Proj object
			if ($row['element_type'] == 'text' && in_array($row['element_validation_type'], array('date', 'datetime', 'datetime_seconds'))) {
				$row['element_validation_type'] .= '_ymd';
			}
			// Add metadata row with field_name as key and other attributes as sub-array
			$this->metadata[$row['field_name']] = $row;	
			// Add this field to the forms array
			$this->forms[$row['form_name']]['fields'][$row['field_name']] = label_decode($row['element_label']);
			// Increment field count
			$this->numFields++;
			// Set for next loop
			$this->lastFormName = $row['form_name'];
			// Save matrix group name, if exists
			if ($row['grid_name'] != '') {
				$this->matrixGroupNames[$row['grid_name']][] = $row['field_name'];
			}
			// Compare current field count with field_order value (if different, then set to renumber field_order for ALL fields)
			if ($this->numFields != $row['field_order'] && !$this->fieldsOutOfOrder)
			{
				$this->fieldsOutOfOrder = true;
			}
		}
		db_free_result($q);
		
		// If fields are out of order, then renumber their order
		if ($this->fieldsOutOfOrder)
		{
			$this->reorderFields();
		}
		
		// If in Draft Mode while in Production, load the drafted field changes as well
		if ($this->project['status'] > 0 && $this->project['draft_mode'] > 0)
		{
			$this->loadMetadataTemp();
		}		
	}
	
	// Load this project's metadata_temp (Draft Mode fields) into array
	private function loadMetadataTemp()
	{
		// Initialize
		$this->numFieldsTemp = 0;
		$this->numFormsTemp  = 0;
		$this->metadata_temp = array();
		
		// Query table
		$sql = "select SQL_CACHE * from redcap_metadata_temp where project_id = " . PROJECT_ID . " order by field_order";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q))
		{
			// If project somehow has a blank field_name, delete it
			if ($row['field_name'] == "")
			{
				// Delete it
				db_query("delete from redcap_metadata_temp where project_id = " . PROJECT_ID . " and field_name = ''");
				// Now skip to next field
				continue;
			}
			// Add form names with form_position and form menu description
			if ($this->numFieldsTemp == 0 || $row['form_menu_description'] != "")
			{
				// Add form menu and number
				$this->forms_temp[$row['form_name']] = array( 'form_number'	=> (count($this->forms_temp) + 1), 
															  'menu' 		=> label_decode($row['form_menu_description']) );
				// If form menu is somehow missing for first field (how?), set its value as the form_name
				if ($this->numFieldsTemp == 0 && $row['form_menu_description'] == "")
				{
					$row['form_menu_description'] = $row['form_name'];
					// Now actually fix it in the table to prevent from messing other things up downstream
					$sql = "update redcap_metadata_temp set form_menu_description = '".prep($row['form_menu_description'])."' 
							where project_id = " . PROJECT_ID . " and field_name = '".prep($row['field_name'])."'";
					db_query($sql);
				}
				if ($this->numFormsTemp == 0) {
					// For first field, set values to be global variables later
					$this->table_pk_temp = $row['field_name'];
				}
				// Increment form count
				$this->numFormsTemp++;
			}
			// If field has a legacy date validation, then update it to non-legacy on the fly for this $Proj object
			if ($row['element_type'] == 'text' && in_array($row['element_validation_type'], array('date', 'datetime', 'datetime_seconds'))) {
				$row['element_validation_type'] .= '_ymd';
			}
			// Add metadata row with field_name as key and other attributes as sub-array
			$this->metadata_temp[$row['field_name']] = $row;
			// Add this field to the forms array
			$this->forms_temp[$row['form_name']]['fields'][$row['field_name']] = label_decode($row['element_label']);
			// Increment field count
			$this->numFieldsTemp++;
			// Save matrix group name, if exists
			if ($row['grid_name'] != '') {
				$this->matrixGroupNamesTemp[$row['grid_name']][] = $row['field_name'];
			}
			// Compare current field count with field_order value (if different, then set to renumber field_order for ALL fields)
			if ($this->numFieldsTemp != $row['field_order'] && !$this->fieldsOutOfOrder)
			{
				$this->fieldsOutOfOrder = true;
			}			
		}
		db_free_result($q);
		
		// If fields are out of order, then renumber their order
		if ($this->fieldsOutOfOrder)
		{
			$this->reorderFields("redcap_metadata_temp");
		}
	}
	
	// AUTO-NUMBERING CHECK: If the first instrument is a survey, make sure the project has auto-numbering enabled
	private function checkAutoNumbering()
	{
		// If has surveys enabled AND first instrument is a survey AND auto-numbering NOT enabled, then enable it
		if ($this->project['surveys_enabled'] > 0 && !$this->project['auto_inc_set'] && isset($this->forms[$this->firstForm]['survey_id']))
		{
			// Set as enabled in table
			$sql = "update redcap_projects set auto_inc_set = 1 where project_id = " . PROJECT_ID;
			db_query($sql);
			// Also set global variable for this pageload instance
			$GLOBALS['auto_inc_set'] = '1';
		}
	}
	
	// Manually check if fields are out of order, and renumber them if so. (Allow manually setting of table name.)
	public function checkReorderFields($table="redcap_metadata")
	{
		// Check table name first
		if (substr($table, 0, 15) != "redcap_metadata") return false;
		// Do a quick compare of the field_order by using Arithmetic Series (not 100% reliable, but highly reliable and quick)
		// and make sure it begins with 1 and ends with field order equal to the total field count.
		$sql = "select sum(field_order) as actual, count(1)*(count(1)+1)/2 as ideal, min(field_order) as min, max(field_order) as max, 
				count(1) as field_count from $table where project_id = " . PROJECT_ID;
		$q = db_query($sql);
		$row = db_fetch_assoc($q);
		db_free_result($q);
		if ( ($row['actual'] != $row['ideal']) || ($row['min'] != '1') || ($row['max'] != $row['field_count']) )
		{
			return $this->reorderFields($table);
		}
		return false;
	}
	
	// If fields are out of order, then renumber their order. (Allow manually setting of table name.)
	public function reorderFields($table="redcap_metadata")
	{
		// Check table name first
		if (substr($table, 0, 15) != "redcap_metadata") return false;
		// Go through all metadata and place into an array according to form (allows us to segregate forms to prevent overlapping)
		$forms_fields = array();
		$forms_menus = array();
		$sql = "select field_name, form_name, form_menu_description from $table where project_id = " . PROJECT_ID . " order by field_order";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q)) 
		{
			// Store field/form
			$forms_fields[$row['form_name']][] = $row['field_name'];
			// Store menu name
			if ($row['form_menu_description'] != "" && !isset($forms_menus[$row['form_name']])) {
				$forms_menus[$row['form_name']] = $row['form_menu_description'];
			}
		}
		db_free_result($q);
		// Counters
		$counter = 1;
		$errors = 0;
		// Set up all actions as a transaction to ensure everything is done here
		db_query("SET AUTOCOMMIT=0");
		db_query("BEGIN");
		// Reset field_order of all fields, beginning with "1"
		foreach ($forms_fields as $this_form=>$field_array)
		{
			// Set form menu for first form field
			$this_form_menu = checkNull($forms_menus[$this_form]);
			// Loop through each field on this form
			foreach ($field_array as $this_field)
			{
				$sql = "update $table set field_order = $counter, form_menu_description = $this_form_menu where project_id = " . PROJECT_ID . " 
						and field_name = '$this_field' limit 1";
				if (!db_query($sql))
				{
					$errors++;
				}
				// Set form menu to null for all other fields on form except the first
				$this_form_menu = "null";
				// Increment counter
				$counter++;
			}
		}
		// If errors, do not commit
		$commit = ($errors > 0) ? "ROLLBACK" : "COMMIT";
		db_query($commit);
		// Set back to initial value
		db_query("SET AUTOCOMMIT=1");
		// Reset value
		$this->fieldsOutOfOrder = false;
		// Unset values
		unset($forms_fields, $field_array, $forms_menus);
		// Return
		return ($errors < 1);
	}
	
	// Load this project's events and arms
	public function loadEvents() 
	{
		// If $this->events is already populated, then wipe it out and build anew
		$this->events = array();
		$this->eventInfo = array();
		$this->firstArmId = null;
		$this->firstEventId = null;
		// Query to obtain arm/event info
		$sql = "select SQL_CACHE * from redcap_events_metadata e, redcap_events_arms a where a.project_id = " . PROJECT_ID . " 
				and a.arm_id = e.arm_id order by a.arm_num, e.day_offset, e.descrip";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q))
		{
			// Arm name
			$this->events[$row['arm_num']]['name'] = $row['arm_name'];
			// Arm id
			$this->events[$row['arm_num']]['id'] = $row['arm_id'];
			// Events for this arm
			$this->events[$row['arm_num']]['events'][$row['event_id']] = array( 'day_offset' => $row['day_offset'],
																				'offset_min' => $row['offset_min'],
																				'offset_max' => $row['offset_max'],
																				'descrip' 	 => $row['descrip'] );
			// Event name
			$this->eventInfo[$row['event_id']] = array( 'arm_id'	 => $row['arm_id'],
														'arm_num'	 => $row['arm_num'],
														'arm_name'	 => $row['arm_name'],
														'day_offset' => $row['day_offset'],
														'offset_min' => $row['offset_min'],
														'offset_max' => $row['offset_max'],
														'name' 	 	 => $row['descrip'],
														// Later in this function, we'll append arm name to name_ext if more than one arm exists
														'name_ext' 	 => $row['descrip'] );
			// Set first arm_id and event_id
			if (empty($this->firstArmId)) {
				$this->firstArmId 	= $row['arm_id'];
				$this->firstArmName	= $row['arm_name'];	
				$this->firstArmNum	= $row['arm_num'];				
			}
			if (empty($this->firstEventId)) {
				$this->firstEventId   = $row['event_id'];
				$this->firstEventName = $row['descrip'];
			}
			// Increment the number of events
			$this->numEvents++;
			// If not longitudinal, then stop looping after first loop (since only one arm and event exists)
			if (!$this->project['repeatforms']) break;
		}
		db_free_result($q);
		
		// Set the number of arms
		$this->numArms = count($this->events);
		
		// Determine if longitudinal (has multiple events) and multiple arms
		if ($this->project['repeatforms'])
		{
			$this->longitudinal  = ($this->numEvents > 1);
			$this->multiple_arms = ($this->numArms   > 1);		
			// If more than one arm exists, then append arm name to name_ext
			if ($this->multiple_arms)
			{
				foreach ($this->eventInfo as $event_id=>$event_info)
				{
					$this->eventInfo[$event_id]['name_ext'] .= " (Arm {$event_info['arm_num']}: {$event_info['arm_name']})";
				}
			}
		}
	}
	
	// Load this project's forms for each event
	public function loadEventsForms() 
	{
		// If $this->eventsForms is already populated, then wipe it out and build anew
		$this->eventsForms = array();
		// If longitudinal...
		if ($this->longitudinal) 
		{
			// Set event_id as array key with all forms ONLY for that event listed
			$sql = "select SQL_CACHE distinct e.event_id, f.form_name from redcap_events_metadata e, redcap_events_forms f, redcap_events_arms a, 
					redcap_metadata m where f.event_id = e.event_id and a.arm_id = e.arm_id and a.project_id = m.project_id 
					and m.form_name = f.form_name and a.project_id = " . PROJECT_ID . " order by a.arm_num, e.day_offset, e.descrip, m.field_order";
			$q = db_query($sql);
			while ($row = db_fetch_assoc($q))
			{
				$this->eventsForms[$row['event_id']][] = $row['form_name'];
			}
		}
		// If not longitudinal...
		else 
		{
			// Obtain the single event_id for this project
			$eventsArray = min($this->events);
			foreach (array_keys($eventsArray['events']) as $event_id)
			{
				// Set event_id as array key with all forms listed
				$this->eventsForms[$event_id] = array_keys($this->forms);
				// Leave now that we have the first one
				return;
			}
		}
	}
	
	// Load this project's surveys
	private function loadSurveys() 
	{
		// Check if surveys are enabled at all
		if ($this->project['surveys_enabled'] > 0)
		{
			// Get survey list
			$sql = "select SQL_CACHE * from redcap_surveys where project_id = " . PROJECT_ID;
			$q = db_query($sql);
			while ($row = db_fetch_assoc($q))
			{
				// If form_name is NULL or blank, manually give it name of first form 
				if ($row['form_name'] == '') {
					$row['form_name'] = $this->firstForm;
					// Also fix this on the back-end to prevent issues
					$q2 = db_query("update redcap_surveys set form_name = '" . prep($this->firstForm) . "' where survey_id = " . $row['survey_id']);
					// If first form is already set for another survey, then do not add it to surveys array
					if (!$q2 && db_errno() == '1062') {
						continue;
					}
				}
				// Add survey information
				foreach ($row as $key=>$value)
				{
					if ($key != 'project_id' && $key != 'survey_id') {
						$this->surveys[$row['survey_id']][$key] = $value;
					}
				}
				// Also add the survey_id to the "forms" array (and also to the "forms_temp" array for consistency)
				$this->forms[$row['form_name']]['survey_id'] = $row['survey_id'];
				if (isset($this->forms_temp[$row['form_name']])) {
					$this->forms_temp[$row['form_name']]['survey_id'] = $row['survey_id'];
				}
			}
			db_free_result($q);
		}
		// Set survey_id of first form
		if (isset($this->forms[$this->firstForm]['survey_id'])) {
			$this->firstFormSurveyId = $this->forms[$this->firstForm]['survey_id'];
		}
	}
	
	// Return boolean if field is a checkbox field type 
	public function isCheckbox($field)
	{
		return (isset($this->metadata[$field]) && $this->metadata[$field]['element_type'] == 'checkbox');		
	}
	
	// Return boolean if field is a multiple choice field ("advcheckbox", "radio", "select", "checkbox", "dropdown", "yesno", "truefalse")
	public function isMultipleChoice($field)
	{
		$mcFieldTypes = array("advcheckbox", "radio", "select", "checkbox", "dropdown", "yesno", "truefalse");
		return (isset($this->metadata[$field]) && in_array($this->metadata[$field]['element_type'], $mcFieldTypes));		
	}
	
	// Return boolean if field is a Form Status field
	public function isFormStatus($field)
	{
		return (isset($this->metadata[$field]) && $field == $this->metadata[$field]['form_name']."_complete");		
	}
	
	// Check if form has been designated for this event
	public function validateFormEvent($form_name,$event_id)
	{
		return ($this->longitudinal ? in_array($form_name, $this->eventsForms[$event_id]) : true);
	}
	
	// Get event_id using unique event name
	public function getEventIdUsingUniqueEventName($unique_event_name)
	{
		return array_search($unique_event_name, $this->getUniqueEventNames());		
	}
	
	// Check if a given unique event name is valid
	public function uniqueEventNameExists($unique_event_name)
	{
		return in_array($unique_event_name, $this->getUniqueEventNames());		
	}
	
	// Get list of unique event names (based upon event name text) with event_id as array key and unique name as element
	public function getUniqueEventNames($event_id=null)
	{
		// If unique names not defined yet
		if ($this->uniqueEventNames == null)
		{
			$this->uniqueEventNames = array();
			// Loop through all events and create unique event names
			$events = array();
			foreach ($this->events as $this_arm_num=>$arm_attr)
			{				
				foreach ($arm_attr['events'] as $this_event_id=>$event_attr)
				{
					// Remove all spaces and non-alphanumeric characters, then make it lower case.
					$text = preg_replace("/[^0-9a-z_ ]/i", '', trim(label_decode($event_attr['descrip'])));
					$text = strtolower(substr(str_replace(" ", "_", $text), 0, 18));
					// Remove any underscores at the end
					if (substr($text, -1, 1) == "_") {
						$text = substr($text, 0, -1);
					}
					// Append arm number
					$text .= '_arm_' . $this_arm_num;
					// If this unique name alread exists, append with "a", "b", "c", etc.
					$count = count(array_keys($events, $text));
					$append_text = '';
					if ($count > 0 && $count < 26) {
						$append_text = chr(97+$count);
					} elseif ($count >= 26 && $count < 702) {
						$append_text = chr(96+floor($count/26)) . chr(97+($count%26));
					} elseif ($count >= 702) {
						$append_text = '??';
					}
					// Collect the original unique name to check for duplicates later
					$events[] = $text;
					if ($event_id == null) {
						// Add unique name to array in object
						$this->uniqueEventNames[$this_event_id] = $text . $append_text;
					} elseif ($event_id == $this_event_id) {
						// Return the unique name for the specified event
						return $text . $append_text;
					}
				}
			}
		}
		// If unique names ARE defined and we are to return ONLY one event
		elseif ($this->uniqueEventNames != null && $event_id != null)
		{
			return $this->uniqueEventNames[$event_id];
		}
		// Return array of unique event names
		return $this->uniqueEventNames;
	}
	
	// Check if any forms have been downloaded from the REDCap Shared Library (return as 1 or 0)
	public function formsFromLibrary()
	{
		if ($this->formsFromLibrary == null) 
		{
			$sql = "select 1 from redcap_library_map where type = 1 
					and project_id = " . PROJECT_ID . " limit 1";
			$q = db_query($sql);
			$this->formsFromLibrary = db_num_rows($q);
		}
		return $this->formsFromLibrary;
	}
	
	// Validate survey_id for this project
	public function validateSurveyId($survey_id)
	{
		return isset($this->surveys[$survey_id]);
	}
	
	// Validate event_id for this project
	public function validateEventId($event_id)
	{
		return ($this->longitudinal ? isset($this->eventInfo[$event_id]) : ($event_id == $this->firstEventId));
	}
	
	// Validate event_id-survey_id pair for this project 
	// (i.e. make sure that this survey's instrument has been designated for this event)
	public function validateEventIdSurveyId($event_id, $survey_id)
	{
		// First, validate both survey_id and event_id individually
		if (!$this->validateSurveyId($survey_id) || !$this->validateEventId($event_id)) return false;
		// Get the instrument name of the survey
		$form_name = $this->surveys[$survey_id]['form_name'];
		// Return true if survey is utilized for this event
		return in_array($form_name, $this->eventsForms[$event_id]);
	}
	
	// Check if this survey_id is a follow-up survey (i.e. a survey not associated with the first instrument). Return boolean.
	public function isFollowUpSurvey($survey_id)
	{
		// Return true if NOT the first instrument's survey_id
		return ($survey_id != $this->firstFormSurveyId);
	}
	
	// Populate array of all Data Access Groups OR return single group's name if group_id is input
	public function getGroups($group_id=null)
	{
		if ($this->groups === null) 
		{
			// Query for group id and name
			$sql = "select * from redcap_data_access_groups where project_id = " . PROJECT_ID . " order by trim(group_name)";
			$q = db_query($sql);
			while ($row = db_fetch_assoc($q))
			{
				$this->groups[$row['group_id']] = $row['group_name'];
			}
		}
		// If requesting single group_id, then return only it
		if ($group_id != null)
		{
			return (is_numeric($group_id) && isset($this->groups[$group_id])) ? $this->groups[$group_id] : false;
		}
		return $this->groups;
	}
	
	// Validate group_id of Data Access Group for this project
	public function validateGroupId($group_id)
	{
		$this->getGroups();
		return isset($this->groups[$group_id]);
	}
	
	// Check if a given unique group name is valid
	public function uniqueGroupNameExists($unique_group_name)
	{
		return in_array($unique_group_name, $this->getUniqueGroupNames());		
	}
	
	// Get list of unique Data Access Group names (based upon group name text) with group_id as array key and unique name as element
	public function getUniqueGroupNames($group_id=null)
	{
		// If unique names not defined yet
		if ($this->uniqueGroupNames == null)
		{
			$this->uniqueGroupNames = array();
			// Loop through all groups and create unique event names
			$groups = array();
			foreach ($this->getGroups() as $this_group_id=>$group_name)
			{
				// Remove all spaces and non-alphanumeric characters, then make it lower case.
				$group_name = preg_replace("/[^0-9a-z_ ]/i", '', trim(label_decode($group_name)));
				$group_name = strtolower(substr(str_replace(" ", "_", $group_name), 0, 18));
				// Remove any underscores at the end
				if (substr($group_name, -1, 1) == "_") {
					$group_name = substr($group_name, 0, -1);
				}
				// If this unique name alread exists, append with "b", "c", "d", etc.
				$count = count(array_keys($groups, $group_name));
				$append_text = '';
				if ($count > 0 && $count < 26) {
					$append_text = chr(97+$count);
				} elseif ($count >= 26 && $count < 702) {
					$append_text = chr(96+floor($count/26)) . chr(97+($count%26));
				} elseif ($count >= 702) {
					$append_text = '??';
				}
				// Collect the original unique name to check for duplicates later
				$groups[] = $group_name;
				if ($group_id == null) {
					// Add unique name to array in object
					$this->uniqueGroupNames[$this_group_id] = $group_name . $append_text;
				} elseif ($group_id == $this_group_id) {
					// Return the unique name for the specified event
					return $group_name . $append_text;
				}
			}
		}
		// If unique names ARE defined and we are to return ONLY one group
		elseif ($this->uniqueGroupNames != null && $group_id != null)
		{
			return $this->uniqueGroupNames[$group_id];
		}
		// Return array of unique group names
		return $this->uniqueGroupNames;
	}
	
	// Clear all groups from object variable and retrieve again (in case some were modified in same script)
	public function resetGroups()
	{
		// Reset array to null
		$this->uniqueGroupNames = null;
		// Re-fill array
		$this->getGroups();
	}
	
	// Populate array of users that are in a Data Access Group (return group_id as array key)
	public function getGroupUsers($group_id=null,$includeUsersNotAssigned=false)
	{
		if ($this->groupsUsers === null) 
		{
			// Query for group id and name
			$sql = "select if(group_id is null,0,group_id) as group_id, username from redcap_user_rights where project_id = " . PROJECT_ID;
			if (!$includeUsersNotAssigned) {
				$sql .= " and group_id is not null";
			}
			$sql .= " order by group_id";
			$q = db_query($sql);
			while ($row = db_fetch_assoc($q))
			{
				$this->groupUsers[$row['group_id']][] = $row['username'];
			}
		}
		// If requesting single group_id, then return only it
		if ($group_id != null)
		{
			return (is_numeric($group_id) && isset($this->groupUsers[$group_id])) ? $this->groupUsers[$group_id] : false;
		}
		return $this->groupUsers;
	}
	
	// Obtain the event_id of the first event on a given arm using the arm number
	public function getFirstEventIdArm($arm_num)
	{
		if (is_numeric($arm_num) && isset($this->events[$arm_num]))
		{
			// Return the first event_id for this arm
			foreach (array_keys($this->events[$arm_num]['events']) as $event_id)
			{
				return $event_id;
			}
		}
		return $this->firstEventId;
	}
	
	// Obtain the event_id of the first event of an arm by providing an event_id from that arm
	public function getFirstEventIdInArmByEventId($event_id)
	{
		if (is_numeric($event_id) && isset($this->eventInfo[$event_id]))
		{
			// Return the first event_id for this arm if the event_id exists in this arm
			foreach ($this->events as $arm=>$arm_attr) {
				if (isset($arm_attr['events'][$event_id])) {
					return array_shift(array_keys($arm_attr['events']));
				}
			}
		}
		return $this->firstEventId;
	}
	
	// Obtain the event_id of the first event on a given arm using the arm_id
	public function getFirstEventIdArmId($arm_id)
	{
		if (is_numeric($arm_id))
		{
			// Return the first event_id for this arm
			foreach ($this->events as $arm=>$arm_attr) {
				if ($arm_attr['id'] == $arm_id) {
					foreach (array_keys($arm_attr['events']) as $event_id) {
						return $event_id;
					}
				}
			}
		}
		return $this->firstEventId;
	}
	
	// Return boolean if the event_id provided is the first event in the arm to which it belongs
	public function isFirstEventIdInArm($event_id)
	{
		if (!is_numeric($event_id)) return false;
		// Return the first event_id for this arm
		foreach ($this->events as $arm=>$arm_attr) {
			// Does this event_id belong to this arm?
			if (isset($arm_attr['events'][$event_id])) {
				// If we are on correct arm, then determine if event_id is the first event in this arm
				return (array_shift(array_keys($arm_attr['events'])) == $event_id);
			}
		}
		// Could not find event, so return false
		return false;
	}

	// Obtains unique matrix group name (grid_name) that does not currently exist in metadata table
	private function generateMatrixGroupName($prependString="")
	{
		global $status;
		//If project is in production, do not allow instant editing (draft the changes using metadata_temp table instead)
		$metadata_table = ($status > 0) ? "redcap_metadata_temp" : "redcap_metadata";
		do {
			// If original group name is too long, truncate it
			$maxLengthPrependString = (self::GRID_NAME_MAX_LENGTH - 5);
			if (strlen($prependString) > $maxLengthPrependString) {
				$prependString = substr($prependString, 0, $maxLengthPrependString);
			}
			// Generate a new random grid name (based on original)
			$grid_name = $prependString . "_" . strtolower(generateRandomHash(4));
			// Ensure that the grid name doesn't already exist
			$sql = "select 1 from $metadata_table where project_id = ".PROJECT_ID." and grid_name = '".$grid_name."' limit 1";
			$gridExists = (db_num_rows(db_query($sql)) > 0);
		} while ($gridExists);
		// Grid name is unique, so return it
		return $grid_name;
	}
	
	// Fix any orphaned matrix-formatted fields by automatically giving them a new grid_name
	public function fixOrphanedMatrixFields($form=null)
	{
		global $status;
		//If project is in production, do not allow instant editing (draft the changes using metadata_temp table instead)
		$metadata_table = ($status > 0) ? "redcap_metadata_temp" : "redcap_metadata";
		// Get fields (all fields or just a single form)
		$fields = array();
		$sql = "select field_name, grid_name from $metadata_table where project_id = ".PROJECT_ID;
		if ($form != null) $sql .= " and form_name = '".prep($form)."'";
		$sql .= " order by field_order";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q)) {
			$fields[$row['field_name']] = $row['grid_name'];
		}
		// Loop through fields and note any duplicate grid names or any orphans
		$last_group = "";
		$groups = array();
		$group_count = 0;
		foreach ($fields as $field=>$group)
		{
			// If has group
			if ($group != "") {
				if ($group != $last_group) {
					$groups[++$group_count] = array('group'=>$group,'fields'=>array($field));
				} else {
					$groups[$group_count]['fields'][] = $field;
				}
			}
			// Set for next loop
			$last_group = $group;
		}
		// Now loop through our groups list and note any duplicates (i.e. non-adjacent groups with same name)
		$groups_existing = array();
		$groups_duplicated = array();
		foreach ($groups as $key=>$attr) {
			// If group already exists previously, then compare it to previous and remove one with lowest count
			if (isset($groups_existing[$attr['group']])) {
				$prev_key = $groups_existing[$attr['group']];
				$prev_group_field_count = count($groups[$prev_key]['fields']);
				if ($prev_group_field_count < count($attr['fields'])) {
					// Add fields from previous instance
					$groups_duplicated[$attr['group']] = $groups[$prev_key]['fields'];
					$groups_existing[$attr['group']] = $key;
				} else {
					$groups_duplicated[$attr['group']] = $attr['fields'];
					$groups_existing[$attr['group']] = $prev_key;
				}
			} else {
				// Add name to existing groups list
				$groups_existing[$attr['group']] = $key;
			}
		}
		// If found orphaned fields, change the grid_name for each field OR each group of fields that were orphaned
		$fields_changed = 0;
		foreach ($groups_duplicated as $old_group=>$fields) {
			// Generate new unique grid_name (use old grid name to prepend)
			$grid_name = $this->generateMatrixGroupName($old_group);
			// Auto-change grid_name of fields to unique value in project's metadata
			$sql = "update $metadata_table set grid_name = '".prep($grid_name)."' 
					where project_id = ".PROJECT_ID." and field_name in ('".implode("', '", $fields)."')";
			if (db_query($sql)) $fields_changed++;
		}
		// Unset arrays
		unset($fields,$groups);
		// Return true if any group names were modified
		return ($fields_changed > 0);
	}
	

	// Function to obtain the previous field right before the one passed as parameter
	public function getPrevField($this_field)
	{
		if (!isset($this->metadata[$this_field])) return false;
		$fields = array_keys($this->metadata);
		$prevFieldIndex = array_search($this_field, $fields)-1;
		return (isset($fields[$prevFieldIndex])) ? $fields[$prevFieldIndex] : false;
	}
	

	// Function to obtain the next field following the one passed as parameter
	public function getNextField($this_field)
	{
		if (!isset($this->metadata[$this_field])) return false;
		$fields = array_keys($this->metadata);
		$prevFieldIndex = array_search($this_field, $fields)+1;
		return (isset($fields[$prevFieldIndex])) ? $fields[$prevFieldIndex] : false;
	}

}