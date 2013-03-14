<?php

/**
 * Design
 * This class is used for design-centric methods (e.g. Online Designer).
 */
class Design
{
	// Reset and fix the Form Menu Description of any forms that somehow lost their form menu label (from moving fields, etc.)
	// Use $Proj->forms or $Proj->forms_temp (if in Draft Mode)
	public static function fixFormLabels()
	{
		global $Proj, $status;
		// Get forms array of all form info first
		$forms = ($status > 0) ? $Proj->forms_temp : $Proj->forms;
		$metadata_table = ($status > 0) ? "redcap_metadata_temp" : "redcap_metadata";		
		// First get a list of all forms that are missing a form label for their first field and ONLY fix those
		$sql = "select y.field_name, y.form_name from (select min(field_order) as min_order from $metadata_table 
				where project_id = " . PROJECT_ID . " group by form_name order by field_order) x, $metadata_table y 
				where x.min_order = y.field_order and y.project_id = " . PROJECT_ID . " and y.form_menu_description is null";
		$q = db_query($sql);
		if ($q && db_num_rows($q) > 0) 
		{
			while ($row = db_fetch_assoc($q)) {
				// Set vars
				$field = $row['field_name'];
				$form = $row['form_name'];
				// If can't find current form label, then generate one from the form_name itself
				$formLabel = isset($forms[$form]['menu']) ? $forms[$form]['menu'] : trim(ucwords(str_replace("_", " ", $form)));
				// First set all form menus to null for ALL fields on this form ONLY
				$sql = "update $metadata_table set form_menu_description = null 
						where project_id = " . PROJECT_ID . " and form_name = '" . prep($form) . "'";
				db_query($sql);
				// Now set label for this form
				$sql = "update $metadata_table set form_menu_description = '".prep($formLabel)."' 
						where project_id = " . PROJECT_ID . " and field_name = '".prep($field)."'";
				db_query($sql);
			}
		}
	}
	
	// Get the first field for a given form
	// Set to query the metadata or metadata_temp table (if in Draft Mode)
	public static function getFirstFieldOfForm($form,$metadata_table="redcap_metadata")
	{
		$sql = "select field_name from $metadata_table where project_id = " . PROJECT_ID . " 
				and form_name = '" . prep($form) . "' order by field_order limit 1";
		$q = db_query($sql);
		if ($q && db_num_rows($q)) {
			return db_result($q, 0);
		} else {
			return false;
		}
	}
	
	// Determine if the PK (record identifier) field just changed in the current script 
	// (Assumes we're in Design Mode and NOT in the middle of a transaction)
	public static function recordIdFieldChanged()
	{
		global $Proj, $status;
		// Get PK when this script began (assume we're in Design mode - e.g. use temp if in production)
		$current_table_pk = ($status > 0) ? $Proj->table_pk_temp : $Proj->table_pk;
		// Query metadata table to find current PK right now
		$metadata_table = ($status > 0) ? "redcap_metadata_temp" : "redcap_metadata";
		$sql = "select field_name from $metadata_table where project_id = " . PROJECT_ID . " order by field_order limit 1";
		$new_table_pk = db_result(db_query($sql), 0);
		// Return boolean of whether PK changed (true if changed)
		return ($current_table_pk != $new_table_pk);
	}

	// Determine if survey question auto-numbering should be disabled (because branching logic exists)
	// Return boolean (true = it disabled question auto-numbering)
	public static function checkDisableSurveyQuesAutoNum($form)
	{
		global $status, $Proj;
		
		if ($status < 1 
			&& isset($Proj->forms[$form]['survey_id']) 
			&& $Proj->surveys[$Proj->forms[$form]['survey_id']]['question_auto_numbering'] 
			&& self::checkSurveyBranchingExists($form))
		{
			// Survey is using auto question numbering and has branching, so set to custom numbering
			$sql = "update redcap_surveys set question_auto_numbering = 0 where survey_id = " . $Proj->forms[$form]['survey_id'];
			return (db_query($sql));
		}
		return false;
	}

	// SURVEY QUESTION NUMBERING: Detect if any survey questions have branching logic. Return true/false if so.
	public static function checkSurveyBranchingExists($form,$metadata_table="redcap_metadata")
	{
		global $Proj;
		// Make sure this form is enabled as a survey first
		$survey_id = $Proj->forms[$form]['survey_id'];
		if (!empty($survey_id))
		{
			// Find any fields in survey with branching logic
			$sql = "select 1 from $metadata_table where project_id = " . PROJECT_ID . " and branching_logic is not null
					and form_name = '".prep($form)."' limit 1";
			$q = db_query($sql);
			$hasBranching = db_num_rows($q);
			// Return if has branching or not
			return $hasBranching;
		}
		// Not a survey, so return false
		return false;	
	}
	
	
	// For a given form in a longitudinal project, return a list of events for which a form is designated
	// in order to select one to set up or modify Automated Survey Invitations
	public static function getEventsAutomatedInvitesForForm($form)
	{
		global $Proj;
		// Create array to put events that have automated invites activated for THIS form
		$formEventsWithAutomatedInvites = array();
		$sql = "select ss.event_id, ss.active from redcap_surveys_scheduler ss, redcap_surveys s 
				where s.survey_id = ss.survey_id and s.form_name = '".prep($form)."' and s.project_id = " . PROJECT_ID;
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q)) {
			$formEventsWithAutomatedInvites[$row['event_id']][$form] = $row['active'];
		}
		// Gather event names into separate divs/rows for the hidden div below
		$chooseEventRows = array();
		foreach ($Proj->eventInfo as $this_event_id=>$attr) {
			// Only use this event if form is designation for it
			if (!in_array($form, $Proj->eventsForms[$this_event_id])) continue;
			// Set name of event
			$chooseEventRows[$this_event_id]['name'] = $attr['name_ext'];
			// Set 1 if automated invites are Active for this form/event
			$chooseEventRows[$this_event_id]['active'] = (isset($formEventsWithAutomatedInvites[$this_event_id][$form])) 
				? $formEventsWithAutomatedInvites[$this_event_id][$form] : '';
		}
		// Return array
		return $chooseEventRows;	
	}
	
	
	// Create array of form_names that have automated invitations set for them (not checking more granular at event_id level)
	// Each form will have 0 and 1 subcategory to count number of active(1) and inactive(0) schedules for each.
	public static function formsWithAutomatedInvites()
	{
		global $Proj, $repeatforms;
		// Collect values in an array
		$formsWithAutomatedInvites = array();
		$sql = "select s.form_name, ss.active, ss.event_id from redcap_surveys_scheduler ss, redcap_surveys s 
				where s.survey_id = ss.survey_id and s.project_id = " . PROJECT_ID;
		$q = db_query($sql);
		// Loop through each instrument
		while ($row = db_fetch_assoc($q)) 
		{
			// If project is set to be longitudinal (has repeatforms flag enabled), then make sure the form is designated for this event_id
			if ($repeatforms && !in_array($row['form_name'], $Proj->eventsForms[$row['event_id']])) {
				continue;
			}
			// Pre-fill with default values first
			if (!isset($formsWithAutomatedInvites[$row['form_name']])) {
				$formsWithAutomatedInvites[$row['form_name']] = array('0'=>0, '1'=>0);
			}
			// Increment number of active/inactive counts
			$formsWithAutomatedInvites[$row['form_name']][$row['active']]++;
		}
		return $formsWithAutomatedInvites;
	}


	/* 
	// Determine if a matrix-formatted field got moved during a field reorder 
	// (need to know this in order to reload Online Designer table).
	// Use $Proj->metadata as the "old_order" of fields for the form.
	public static function matrixFieldsMoved($form,$new_order)
	{
		global $Proj, $status;
		// Get array of all metadata attribute (from $Proj based on status)
		$metadata = ($status > 0) ? $Proj->metadata_temp : $Proj->metadata;
		// Get old order
		$old_order = array();
		foreach ($metadata as $field=>$attr) {
			if ($attr['form_name'] == $form && $field != $form."_complete") {
				$old_order[] = $field;
			}
		}
		// Remove all Section Headers and blank values from $new_order
		$new_order_temp = array();
		foreach (explode(",", $new_order) as $field) {
			if ($field != "" && strpos($field, "-sh") === false) {
				$new_order_temp[] = $field;
			}
		}
		$new_order = $new_order_temp;
		// Move through the new order, and if any matrix fields have moved, return true
		foreach ($new_order as $order_num=>$field) {
			// If this field is a matrix field
			if ($metadata[$field]['grid_name'] != "") {
				// Get fields order num in old_order
				$order_num_old = array_search($field, $old_order);
				// If this field is not in same place, return true
				if ($order_num_old !== false && $order_num_old != $order_num) {
					return true;
				}
			}
		}
		// Unset arrays
		unset($metadata,$new_order,$old_order);
		// Return false if found nothing
		return false;
	}
	*/
	
	
}