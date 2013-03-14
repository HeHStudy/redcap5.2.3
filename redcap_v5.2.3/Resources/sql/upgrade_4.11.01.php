<?php

/**
 * Fix issue with duplicate rows in surveys_response table for the same participant_id (for Participant List participants)
 */
 
// ONLY do this if currently on 4.8.1 or higher
if (getDecVersion($current_version) >= 40801)
{
	// Get all possible instances of the issue
	$sql = "select r2.response_id, r2.participant_id, r2.record, r2.completion_time 
			from redcap_surveys_response r2, (select p.participant_id, count(1) as pcount 
			from redcap_surveys_response r, redcap_surveys_participants p
			where p.participant_id = r.participant_id and p.participant_email is not null 
			and p.participant_email != '' group by p.participant_id) x 
			where r2.participant_id = x.participant_id and x.pcount > 1 
			order by r2.participant_id, r2.completion_time";
	$q = db_query($sql);
	if ($q && db_num_rows($q) > 0)
	{
		print "-- Disassociate some survey responses from Participant List participants because of duplicate linking\n";
		// Set initial vars
		$last_participant_id = null;
		$current_participant_response_times = array();
		// Loop through each participant_id
		while ($row = db_fetch_assoc($q))
		{
			// If a new participant_id, then determine if can delete a row
			if ($row['participant_id'] != $last_participant_id && $last_participant_id != null)
			{
				// Determine if this participant has a non-null completion_time. If so, delete any response_id's for it that are null.
				$responses_to_delete_temp = array();
				$hasNonNullTime = false;
				$hasNullTime = false;
				$maxTimestamp = null;
				// Loop through this participant's responses and collect those that might need to be deleted
				foreach ($current_participant_response_times as $this_response_id=>$timestamp)
				{
					if ($timestamp == "") {
						$responses_to_delete_temp[] = $this_response_id;
						$hasNullTime = true;
					} else {
						$hasNonNullTime = true;
					}
				}
				// Determine if we should delete some responses
				if ($hasNullTime && $hasNonNullTime)
				{
					print "delete from redcap_surveys_response where response_id in (".implode(", ", $responses_to_delete_temp).");\n";
				}
				// Reset array
				$current_participant_response_times = array();
			}
			// Add response_id and completion_time to array
			$current_participant_response_times[$row['response_id']] = $row['completion_time'];
			// Set for next loop
			$last_participant_id = $row['participant_id'];
		}
		## LAST ROW
		// Determine if this participant has a non-null completion_time. If so, delete any response_id's for it that are null.
		$responses_to_delete_temp = array();
		$hasNonNullTime = false;
		$hasNullTime = false;
		$maxTimestamp = null;
		// Loop through this participant's responses and collect those that might need to be deleted
		foreach ($current_participant_response_times as $this_response_id=>$timestamp)
		{
			if ($timestamp == "") {
				$responses_to_delete_temp[] = $this_response_id;
				$hasNullTime = true;
			} else {
				$hasNonNullTime = true;
			}
		}
		// Determine if we should delete some responses
		if ($hasNullTime && $hasNonNullTime)
		{
			print "delete from redcap_surveys_response where response_id in (".implode(", ", $responses_to_delete_temp).");\n";
		}
	}
}