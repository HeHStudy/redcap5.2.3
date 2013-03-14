<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


/**
 * PULL DATA FOR REPORT DISPLAY OR REPORT EXPORT (OUTPUT AS ARRAY)
 */
function buildReport($this_query_array) 
{
	global $table_pk, $user_rights, $project_id, $longitudinal, $Proj;
	
	$num_cols = 0;
	$query_fields = array();
	
	// If table_pk is used to order by ASC, then remove from array because will mess up longitudinal sorting with event names
	if (isset($this_query_array['__ORDERBY1__']) && trim($this_query_array['__ORDERBY1__']) == "$table_pk ASC") {
		unset($this_query_array['__ORDERBY1__']);
	}
	if (isset($this_query_array['__ORDERBY2__']) && trim($this_query_array['__ORDERBY2__']) == "$table_pk ASC") {
		unset($this_query_array['__ORDERBY2__']);
	}
	
	//If user is in a Data Access Group, only show records associated with that group
	if ($user_rights['group_id'] != "") {
		$group_sql = "and d.record in (" . pre_query("select record from redcap_data where field_name = '__GROUPID__' and 
					  value = '".$user_rights['group_id']."' and project_id = '$project_id'") . ")";
		$group_sql2 = "where d.record in (" . pre_query("select record from redcap_data where field_name = '__GROUPID__' and 
					  value = '".$user_rights['group_id']."' and project_id = '$project_id'") . ")";
	} else {
		$group_sql = "";
		$group_sql2 = "";
	}
	
	## Build sql for data pull
	// Due to limitations with longitudinal limiting (because of multiple event_ids), also use event_id in subqueries for longitudinal projects
	$sub_sql_field = ($longitudinal) ? "concat(d.event_id,'|',d.record)" : "d.record";
	// Loop through report array to build sub-queries
	$sub_queries = "";
	// Get list of fields used in sql query
	$sql_field_list = array();
	foreach ($this_query_array as $key=>$value) 
	{
		if ($key != '__TITLE__' && $key != '__ORDERBY1__' && $key != '__ORDERBY2__') 
		{
			$sql_field_list[] = $key;
			//If a limiter exists, process it as a subquery
			if ($value != "") {
				$firstspace = strpos($value," ");
				$this_field = trim(substr($value,0,$firstspace));
				$this_limiter = trim(substr($value,$firstspace));
				// Begin constructing subquery for this limiter
				$sub_sql  = "select $sub_sql_field from redcap_data d where d.project_id = $project_id and d.field_name = '$this_field' and d.value ";
				// Add limiter value to subquery, and remove apostrophes for numbers/integers to attain proper numeric filtering in the sub-query
				$sub_sql .= ((in_array($Proj->metadata[$this_field]['element_validation_type'], array("int","float")) || $Proj->metadata[$this_field]['element_type'] == "calc") 
						  ? str_replace("'", "", $this_limiter) 
						  : $this_limiter);
				// Add subquery to final query string
				$sub_queries .= " and $sub_sql_field in (" . pre_query($sub_sql) . ") ";
			}
			$query_fields[] = $key;
			$num_cols++;
		}
	}
	if ($longitudinal) {
		$custom_report_query = "select d.record, d.event_id, d.field_name, d.value
								from redcap_data d, redcap_events_metadata e, redcap_events_arms a
								where d.project_id = $project_id and d.project_id = a.project_id
								and a.arm_id = e.arm_id and e.event_id = d.event_id 
								and d.field_name in ('" . implode("', '", $sql_field_list) . "') 
								$group_sql $sub_queries  and d.record != ''
								order by abs(d.record), d.record, a.arm_num, e.day_offset, e.descrip";
	} else {
		$custom_report_query = "select d.record, d.event_id, d.field_name, d.value 
								from redcap_data d where d.project_id = $project_id and d.event_id = {$Proj->firstEventId}
								and d.field_name in ('" . implode("', '", $sql_field_list) . "') 
								$group_sql $sub_queries  and d.record != ''
								order by abs(d.record), d.record, d.event_id";
	}
		
	// Pull the data
	$eav_arr = eavEventArray($custom_report_query);
	
	// SPECIAL: For longitudinal projects, remove any record-events that have ONLY the study id field with no other field data (false positive)
	if ($longitudinal) 
	{
		// Loop through data array
		foreach ($eav_arr as $this_key=>$this_key_arr) {
			// Remove any record-events (i.e. rows in outputted table) that have ONLY the study id field
			if (isset($this_key_arr[$table_pk])) 
			{
				if (count($this_key_arr) == 1) {
					// Study ID field is the ONLY field for this record-event row, so remove whole record from array
					unset($eav_arr[$this_key]);
				}
			}
		}
	}

	//Sort the results by user-defined values (if defined)
	if (isset($this_query_array['__ORDERBY1__']) || isset($this_query_array['__ORDERBY2__'])) 
	{
		//Get "Order By" fields
		if (isset($this_query_array['__ORDERBY1__'])) {
			list ($orderby1, $asc1) = explode(" ",trim($this_query_array['__ORDERBY1__']));
			//Query to see if field is numeric
			$sql = "select count(1) from redcap_metadata where project_id = $project_id and field_name = '$orderby1' and 
					(element_validation_type in ('int', 'float') or element_type = 'calc')";
			$orderby1_numeric = db_result(db_query($sql), 0);
			$asc1 = $orderby1_numeric ? "NUMERIC, SORT_" . $asc1 : $asc1;
		}
		if (isset($this_query_array['__ORDERBY2__'])) {
			list ($orderby2, $asc2) = explode(" ",trim($this_query_array['__ORDERBY2__']));
			//Query to see if field is numeric
			$sql = "select count(1) from redcap_metadata where project_id = $project_id and field_name = '$orderby2' and 
					(element_validation_type in ('int', 'float') or element_type = 'calc')";
			$orderby2_numeric = db_result(db_query($sql), 0);
			$asc2 = $orderby2_numeric ? "NUMERIC, SORT_" . $asc2 : $asc2;
		}
		//Create sorting arrays and check if "Order By" fields are numeric (in case we need to use SORT_NUMERIC instead)
		$sort1 = array();
		$sort2 = array();
		foreach($eav_arr as $sortarray) {
			if (isset($orderby1)) {
				$sort1[] = ($orderby1_numeric && $sortarray[$orderby1] != "") ? (float)$sortarray[$orderby1] : $sortarray[$orderby1];
			}				
			if (isset($orderby2)) {
				$sort2[] = ($orderby2_numeric && $sortarray[$orderby2] != "") ? (float)$sortarray[$orderby2] : $sortarray[$orderby2];
			}
		}
		$multi_eval1 = isset($orderby1) ? '$sort1, SORT_' . $asc1 : ''; 
		$multi_eval2 = isset($orderby2) ? '$sort2, SORT_' . $asc2 : ''; 
		$multi_eval_comma = (isset($orderby1) && isset($orderby2)) ? ', ' : ''; 
		//Do multisort to sort all the data accordingly
		$multisort_eval = 'array_multisort(' . $multi_eval1 . $multi_eval_comma . $multi_eval2 . ', $eav_arr);';
		eval($multisort_eval);
		unset($sort1);
		unset($sort2);
	}
	
	return array($eav_arr,$query_fields,$num_cols);
}


//Function uses query to build EAV formatted array with "Event|Record" as keys
// and sub-arrays with keys as 'field_name' and value as 'value'
function eavEventArray($query, $chkbox_fields = null) 
{
	global $longitudinal, $Proj;
	// If array with of checkbox fields (with field_name as key and default value options of "0" as sub-array values) is not provided, then build one
	if (!isset($chkbox_fields) || $chkbox_fields == null) {
		$sql = "select field_name, element_enum from redcap_metadata where project_id = " . PROJECT_ID . " and element_type = 'checkbox'";
		$chkboxq = db_query($sql);
		$chkbox_fields = array();
		while ($row = db_fetch_assoc($chkboxq)) {
			// Add field to list of checkboxes and to each field add checkbox choices
			foreach (parseEnum($row['element_enum']) as $this_value=>$this_label) {
				$chkbox_fields[$row['field_name']][$this_value] = "0";	
			}
		}	
	}
	// Add data from data table to array
	$result = array();
	$chkbox_values = array();
	$resource_link = db_query($query);
	while ($row = db_fetch_array($resource_link)) 
	{
		if (!isset($chkbox_fields[$row['field_name']])) {
			// Non-checkbox field
			$result[$row['event_id']."|".$row['record']][$row['field_name']] = $row['value'];	
		} else {
			// If a checkbox
			$chkbox_values[$row['event_id']."|".$row['record']][$row['field_name']][$row['value']] = "1";
		}
	}
	// Now loop through each record. First add default "0" values for checkboxes, then overlay with any "1"s (actual checks from earlier)
	foreach (array_keys($result) as $this_record) {
		// First add default "0" values to each record
		foreach ($chkbox_fields as $this_fieldname=>$this_choice_array) {
			/* 
			// Make sure checkbox is on form that is designated for an event (Longitudinal only)
			if ($longitudinal)
			{
				list ($this_event_id, $this_record_true) = explode("|", $this_record, 2);
				// Is field's form designated for the current event_id?
				if (!in_array($Proj->metadata[$this_fieldname]['form_name'], $Proj->eventsForms[$this_event_id])) {
					// Loop through all checkbox choices and set each individual value
					foreach (array_keys($this_choice_array) as $code) {
						$this_choice_array[$code] = "";
					}
				}
			}
			print "<br>$this_record, $this_field_name";
			print_array($this_choice_array);
			*/
			// Set checkbox option values
			$result[$this_record][$this_fieldname] = $this_choice_array;
		}
		// Now loop through $chkbox_values to overlay any checked values (i.e. 1's)
		foreach ($chkbox_values[$this_record] as $this_fieldname=>$this_choice_array) {
			foreach ($this_choice_array as $this_value=>$this_data_value) {
				// Make sure it's a real checkbox option and not some random data point that leaked in
				if (isset($chkbox_fields[$this_fieldname][$this_value])) {
					// Add checkbox data to data array
					$result[$this_record][$this_fieldname][$this_value] = $this_data_value;
				}
			}
		}
	}
	// Return array of values
	return $result;
}