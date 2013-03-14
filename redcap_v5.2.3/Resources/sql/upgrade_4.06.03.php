<?php

/**
 * Clean up duplicate responses in survey_response table
 */

if ($current_version == '4.6.0' || $current_version == '4.6.1' || $current_version == '4.6.2')
{
	$sql = "select p2.survey_id, r2.participant_id, r2.record, r2.response_id, r2.completion_time, r2.return_code, r2.results_code 
			from (select p.survey_id, r.record, count(r.record) as rec_count from 
			redcap_surveys_participants p, redcap_surveys_response r where p.participant_id = r.participant_id 
			group by p.survey_id, r.record) x,  redcap_surveys_participants p2, redcap_surveys_response r2 
			where p2.participant_id = r2.participant_id and x.rec_count > 1 and x.survey_id = p2.survey_id 
			and r2.record = x.record 
			order by p2.survey_id, p2.participant_id, r2.record, r2.completion_time desc";
	$q = db_query($sql);
	if ($q && db_num_rows($q) > 0)
	{
		print "\n-- Clean up duplicate responses in survey_response table --\n";
		// Set up initial storage arrays and values
		$record = null;
		$participant_id = null;
		$delete_response_ids = array();
		$master_response = array();
		// Loop through all responses
		$i = 1;
		while ($row = db_fetch_assoc($q))
		{
			// Check if starting on next record
			if (($record !== null && $record !== $row['record']) || ($participant_id !== null && $participant_id !== $row['participant_id'])) 
			{
				print "-- survey_id: $survey_id, participant_id: $participant_id, record: $record\n";
				// Update the master response with the best info for all responses (so we don't lose return code, etc.)
				$sql = "update redcap_surveys_response set completion_time = " . checkNull($master_response[$record]['completion_time']) . ", "
					 . "return_code = " . checkNull($master_response[$record]['return_code']) . ", "
					 . "results_code = " . checkNull($master_response[$record]['results_code']) . " "
					 . "where response_id = " . $master_response[$record]['response_id'];
				print "$sql;\n";
				// Set duplicate response_id(s) for deletion
				unset($delete_response_ids[$master_response[$record]['response_id']]); // Remove master response_id (since we're keeping it)
				if (!empty($delete_response_ids)) {
					$sql = "delete from redcap_surveys_response where response_id in (" . implode(", ", array_keys($delete_response_ids)) . ")";
					print "$sql;\n";
				}
				// Clear out the master response
				$master_response = array();
				$delete_response_ids = array();
			}	
			// Set values for next loop
			$record = $row['record'];
			$survey_id = $row['survey_id'];
			$participant_id = $row['participant_id'];
			// Check if we're beginning a new record here or not
			if (!isset($master_response[$record]))
			{
				## Beginning new record here
				$master_response[$record] = array(
					'response_id' => $row['response_id'],
					'completion_time' => $row['completion_time'],
					'return_code' => $row['return_code'],
					'results_code' => $row['results_code']
				);
			}
			else
			{
				## Still on same record
				// Add response_id if higher than current one stored
				if ($row['response_id'] > $master_response[$record]['response_id']) {
					$master_response[$record]['response_id'] = $row['response_id'];
				}
				// Check if has a non-null timestamp higher than the one we already have. If so, add it to master
				if ($master_response[$record]['completion_time'] == "" && $row['completion_time'] != "") {
					$master_response[$record]['completion_time'] = $row['completion_time'];
				}
				// Check if has a non-null return_code. If so, add it to master
				if ($master_response[$record]['return_code'] == "" && $row['return_code'] != "") {
					$master_response[$record]['return_code'] = $row['return_code'];
				}
				if ($master_response[$record]['results_code'] == "" && $row['results_code'] != "") {
					$master_response[$record]['results_code'] = $row['results_code'];
				}
			}
			// Add all response_id's to array as key
			$delete_response_ids[$row['response_id']] = true;
		}
		// Last loop
		if (($record !== null && $record !== $row['record']) || ($participant_id !== null && $participant_id !== $row['participant_id'])) 
		{
			print "-- survey_id: $survey_id, participant_id: $participant_id, record: $record\n";
			// Update the master response with the best info for all responses (so we don't lose return code, etc.)
			$sql = "update redcap_surveys_response set completion_time = " . checkNull($master_response[$record]['completion_time']) . ", "
				 . "return_code = " . checkNull($master_response[$record]['return_code']) . ", "
				 . "results_code = " . checkNull($master_response[$record]['results_code']) . " "
				 . "where response_id = " . $master_response[$record]['response_id'];
			print "$sql;\n";
			// Set duplicate response_id(s) for deletion
			unset($delete_response_ids[$master_response[$record]['response_id']]); // Remove master response_id (since we're keeping it)
			if (!empty($delete_response_ids)) {
				$sql = "delete from redcap_surveys_response where response_id in (" . implode(", ", array_keys($delete_response_ids)) . ")";
			}
			print "$sql;\n";
		}	
	}
	db_free_result($q);
}

print "-- Make index unique --\n";
print "ALTER TABLE `redcap_surveys_response` DROP INDEX  `participant_record` , ADD UNIQUE  `participant_record` (  `participant_id` ,  `record` );\n";
 
 
 
 
 
 
