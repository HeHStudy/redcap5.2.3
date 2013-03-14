<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
require_once APP_PATH_DOCROOT . 'ProjectGeneral/form_renderer_functions.php';


// Retrieve matching records to populate auto-complete box.
// Make sure field is valid and that user has data entry rights to the form this field is on.
if ($isAjax && isset($_GET['query']) && isset($_GET['field']) && isset($Proj->metadata[$_GET['field']])
	&& $user_rights['forms'][$Proj->metadata[$_GET['field']]['form_name']] != '0') 
{
	// Decode the search string
	$queryString = label_decode(urldecode($_GET['query']));
	
	// Retrieve record list (exclude non-DAG records if user is in a DAG)
	$group_sql = ""; 
	if ($user_rights['group_id'] != "") {
		$group_sql = "and record in (" . pre_query("select record from redcap_data where project_id = $project_id and field_name = '__GROUPID__'
					  and value = '" . $user_rights['group_id'] . "'") . ")"; 
	}
	
	// Modify SQL if using double data entry as DDE person
	if ($double_data_entry && $user_rights['double_data'] != "0") {
		$sql_record_field = "substring(record,1,locate('--',record)-1) as record";
		$sql_dde_record_append = "and record like '%--{$user_rights['double_data']}'";
	} else {
		$sql_record_field = "record";
		$entry_num = "";
	}
	
	// Set the LIKE clause for the query
	$sql_value_like = "value like '%".prep($queryString)."%'";
	
	// Check if we should also search for the escaped value of the string
	$queryStringEscaped = htmlspecialchars($queryString, ENT_QUOTES);
	if ($queryString != $queryStringEscaped) {
		$sql_value_like .= " or value like '%".prep($queryStringEscaped)."%'"; 
	}
	
	// If query field is the table_pk and project is longitudinal, then only return a single entry for first event on each arm
	$sql_table_pk = "";
	if ($longitudinal && $_GET['field'] == $table_pk) {
		// Get first event of each arm
		$firstEventInArms = array();
		foreach ($Proj->events as $this_arm=>$attr) {
			$firstEventInArms[] = array_shift(array_keys($attr['events']));
		}
		$sql_table_pk = "and event_id in (" . implode(",", $firstEventInArms) . ")";
	}
	
	// Query the project data
	$sql = "select event_id, field_name, value, $sql_record_field 
			from redcap_data where project_id = $project_id and field_name = '{$_GET['field']}' 
			$group_sql and ($sql_value_like) $sql_dde_record_append $sql_table_pk
			order by abs(value), value, abs(record), record limit 0,15";
	//Execute query
	$q = db_query($sql);
	$rowcount = db_num_rows($q);
	$recs = array();
	$recinfo = array();
	if ($q && $rowcount > 0) 
	{
		// Retrieve all matches
		while ($result = db_fetch_assoc($q)) 
		{
			// Set string to collect any custom labels to return
			$record_custom_labels = "";
			// Append secondary id, if set
			if ($secondary_pk != '')
			{
				$secondary_pk_val = getSecondaryIdVal($result['record']);
				if ($secondary_pk_val != '') {
					$record_custom_labels .= " (" . $Proj->metadata[$secondary_pk]['element_label'] . " <b>$secondary_pk_val</b>)";
				}
			}
			// Append custom_record_label, if set
			if ($custom_record_label != '') 
			{
				$record_custom_labels .= " " . filter_tags(getCustomRecordLabels($custom_record_label, $Proj->getFirstEventIdArm(getArm()), $result['record']));
			}
			// Set variables
			$form = $Proj->metadata[$result['field_name']]['form_name'];
			$record = $result['record'];
			$result['value'] = filter_tags(label_decode($result['value']));
			if (strlen($result['value']) > 30) {
				$result['value'] = substr($result['value'], 0, 28) . "...";
			}
			$value = $result['value'];
			// Set what will be seen by user in auto complete list
			$record_display = $table_pk_label . " <b>$record</b>" . "<i>$record_custom_labels</i>";
			if ($longitudinal) {
				$record_display .= " {$lang['global_108']} <span>" . filter_tags(label_decode($Proj->eventInfo[$result['event_id']]['name_ext'])) . "</span>";
			}
			$recs[] = '"<b>' . $value . '</b>" '.$lang['global_107'].' ' . $record_display;
			// Set the record, event_id, and form (delimited with a pipe)
			$recinfo[] = rawurlencode("$form|{$result['event_id']}|$record");
		}
	}
	
	//Render JSON
	print json_encode(array('query'=>$queryString, 'suggestions'=>$recs, 'data'=>$recinfo));
} 
else 
{
	
	// User should not be here! Redirect to index page.
	redirect(APP_PATH_WEBROOT . "index.php?pid=$project_id");
	
}
