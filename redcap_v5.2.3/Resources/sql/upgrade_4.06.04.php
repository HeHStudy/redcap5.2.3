<?php

/**
 * Fix issues where Save & Returns were somehow getting listed as Complete survey responses.
 * For any Incomplete responses that have Form Status of 2, set to 0 instead.
 */

// Create array for collecting each project's first form name
$firstForms = array();
 // Get values
$sql = "select s.project_id, r.record, d.event_id, d.field_name, d.value 
		from redcap_surveys s, redcap_surveys_participants p, redcap_surveys_response r, redcap_events_metadata e, redcap_data d 
		where s.save_and_return = 1 and s.survey_id = p.survey_id and p.participant_id = r.participant_id 
		and r.completion_time is null and r.return_code is not null and e.arm_id = p.arm_id and d.project_id = s.project_id 
		and e.event_id = d.event_id and r.record = d.record and d.field_name like '%\_complete' and d.value = '2'";
$q = db_query($sql);
if ($q && db_num_rows($q) > 0)
{
	print "-- Fix issue with Incomplete survey responses getting listed as having a Complete form status value --\n";
	while ($row = db_fetch_assoc($q))
	{
		// Make sure this field is the form status field ONLY for the first form
		if (isset($firstForms[$row['project_id']])) {
			$firstForm = $firstForms[$row['project_id']];
		} else {
			$sql = "select form_name from redcap_metadata where project_id = {$row['project_id']} order by field_order limit 1";
			$q2 = db_query($sql);		
			$firstForm = $firstForms[$row['project_id']] = db_result($q2, 0);
		}
		// Output query
		if ($row['field_name'] == $firstForm."_complete")
		{
			print "update redcap_data set value = '0' where project_id = {$row['project_id']} and event_id = {$row['event_id']} "
				. "and record = '".prep($row['record'])."' and field_name = '{$firstForm}_complete';\n"; 
		}
	}
}