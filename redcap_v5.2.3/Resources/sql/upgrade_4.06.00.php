<?php

/**
 * Convert the project_pi field into separate Firstname Lastname fields
 */
// Set table
$table = "redcap_projects";
// $table = "aaa"; // testing
// Alter the table to add new fields
print "ALTER TABLE  `$table` 
	ADD  `project_pi_firstname` VARCHAR( 255 ) NULL AFTER  `project_pi` ,
	ADD  `project_pi_mi` VARCHAR( 255 ) NULL AFTER  `project_pi_firstname` ,
	ADD  `project_pi_lastname` VARCHAR( 255 ) NULL AFTER  `project_pi_mi` ,
	ADD  `project_pi_alias` VARCHAR( 255 ) NULL AFTER  `project_pi_lastname`;\n";
// Get all project_pi values
$sql = "SELECT project_id, trim(project_pi) as project_pi FROM $table
		where project_pi is not null and project_pi != '' order by trim(project_pi)";
$q = db_query($sql);
if ($q && db_num_rows($q) > 0)
{
	print "-- Separate first/last names for project_pi field --\n";
	// Set up array of things to remove (titles, degrees, etc.)
	$suffix_remove = array("MD", "M.D.", "M.d.", "Md", "Ph.d.", "Phd.", "Ph.D.", "PhD.", "PhD", "Phd", "RN", "R.N.", 
						   "BA", "B.A.", "BS", "B.S.", "MA", "M.A.", "MS", "M.S.",
						   "MPH", "M.P.H.", "MPP", "M.P.P.", "FACS", "F.A.C.S.", "IV", "III", "II");
	$prefix_remove = array("Dr", "Dr.", "Drs", "Drs.", "Mr", "Mr.", "Mrs", "Mrs.", "Ms", "Ms.", "Co-PI:");
	$delimiters    = array(" and ", "/", "&");
	// Loop through all values and separate
	while ($row = db_fetch_assoc($q))
	{
		// Get project_pi to parse
		$project_pi = str_replace(array("\r\n", "\r", "\n", "\t"), array(" ", " ", " ", " "), strip_tags(label_decode($row['project_pi'])));
		## SPLIT IF MULTIPLE NAMES EXIST
		foreach ($delimiters as $this_delim) {
			if (strpos($project_pi, $this_delim) !== false) {
				list ($pi_delim1, $pi_delim2) = explode($this_delim, $project_pi, 2);
				$project_pi = trim($pi_delim1);
			}
		}
		## REMOVE PREFIXES
		foreach ($prefix_remove as $this_prefix) {		
			if (strpos($project_pi, "$this_prefix ") !== false) {
				$project_pi = trim(str_replace("$this_prefix ", "", $project_pi));
			}
		}
		## REMOVE SUFFIXES
		foreach ($suffix_remove as $this_suffix) {	
			if (strpos($project_pi, ", $this_suffix") !== false) {
				$project_pi = trim(str_replace(", $this_suffix", "", $project_pi));
			}		
			if (strpos($project_pi, ",$this_suffix") !== false) {
				$project_pi = trim(str_replace(",$this_suffix", "", $project_pi));
			}	
			if (strpos($project_pi, " $this_suffix") !== false) {
				$project_pi = trim(str_replace(" $this_suffix", "", $project_pi));
			}
		}
		## REMOVE ANYTHING INSIDE PARENTHESES
		$project_pi = trim(preg_replace("/([\s]*)([\(]{1})([^\)]+)([\)]{1})([\s]*)/", " ", $project_pi));
		## REPLACE ALL DOUBLE SPACES WITH SINGLES
		$loops = 0;
		while ($loops < 10 && strpos($project_pi, "  ") !== false) {
			$project_pi = trim(str_replace("  ", " ", $project_pi));
			$loops++;
		}
		## CHECK IF HAS 2 NAMES LISTED SEPARATED BY COMMA (IF SO, ONLY KEEP FIRST)
		if (strpos($project_pi, ",") !== false) {
			// Get names in format "name1, name2" by adding spaces around comma then remove them
			$project_pi = trim(str_replace(",", " , ", $project_pi));
			// Now remove double spaces again
			$loops = 0;
			while ($loops < 10 && strpos($project_pi, "  ") !== false) {
				$project_pi = trim(str_replace("  ", " ", $project_pi));
				$loops++;
			}
			// Now replace " , " with ", "
			$project_pi = trim(str_replace(" , ", ", ", $project_pi));
			// If we have three " " and one ", ", then we have two names here, so drop the second name
			if (substr_count($project_pi, " ") == 3 && substr_count($project_pi, ", ") == 1) {
				list ($pi_delim1, $pi_delim2) = explode(", ", $project_pi, 2);
				$project_pi = trim($pi_delim1);
			}
		}
		## IF written as Last, First then swap first/last
		if (strpos($project_pi, ",") !== false) {
			list ($pi_delim1, $pi_delim2) = explode(",", $project_pi, 2);
			$pi_delim1 = trim($pi_delim1);
			$pi_delim2 = trim($pi_delim2);
			$project_pi = trim("$pi_delim2 $pi_delim1");
		}
		## BREAK INTO PIECES (1, 2, or 3)
		$first_name = "";
		$mi			= "";
		$last_name 	= "";
		$alias		= "";
		// 3 words
		if (substr_count($project_pi, " ") == 2) {
			list ($first_name, $middle_name, $last_name) = explode(" ", $project_pi, 3);
			// Check if first name is actually initials (e.g. A.J.)
			$first_name = str_replace(" ", "", $first_name);
			if (substr_count($first_name, ".") == 1) {
				// e.g. A.
				$mi = strtoupper(substr($middle_name, 0, 1));
			} elseif (substr_count($first_name, ".") >= 2) {
				// e.g. A.J.
				list ($first_name_delim1, $first_name_delim2) = explode(".", $first_name, 2);
				$first_name = strtoupper(substr($first_name_delim1, 0, 1));
				$mi = strtoupper(substr($first_name_delim2, 0, 1));
			} elseif (strcmp($first_name, strtoupper($first_name)) == 0 && strlen($first_name) >= 2) {
				// e.g. AJ
				$mi = strtoupper(substr($first_name, 1, 1));
			} else {
				$mi = strtoupper(substr($middle_name, 0, 1));
			}
			$alias = $last_name . " " . strtoupper(substr($first_name, 0, 1)) . $mi;
		} 
		// 2 words
		elseif (substr_count($project_pi, " ") == 1) {
			list ($first_name, $last_name) = explode(" ", $project_pi, 2);
			// Check if first name is actually initials (e.g. A.J.)
			$first_name = str_replace(" ", "", $first_name);
			if (substr_count($first_name, ".") >= 2) {
				// e.g. A.J.
				list ($first_name_delim1, $first_name_delim2) = explode(".", $first_name, 2);
				$first_name = strtoupper(substr($first_name_delim1, 0, 1));
				$mi = strtoupper(substr($first_name_delim2, 0, 1));
			} elseif (strcmp($first_name, strtoupper($first_name)) == 0 && strlen($first_name) >= 2) {
				// e.g. AJ
				$mi = strtoupper(substr($first_name, 1, 1));
			}
			$alias = $last_name . " " . strtoupper(substr($first_name, 0, 1)) . $mi;
		} 
		// 1 word
		elseif (substr_count($project_pi, " ") == 0) {
			$last_name = $project_pi;
		}		
		//print "\nORIG: {$row['project_pi']}\nNEW: $project_pi\nALIAS: $alias\n";
		// Build query to display
		$sql = "update $table set project_pi_firstname = " . checkNull($first_name) . ", project_pi_mi = " . checkNull($mi) . ", "
			 . "project_pi_lastname = " . checkNull($last_name) . ", project_pi_alias = " . checkNull($alias) . " "
			 . "where project_id = {$row['project_id']}";
		// Output the query
		print "$sql;\n";
	}
}
db_free_result($q);
print "ALTER TABLE  `$table` DROP `project_pi`;\n";



/**
 * Clean up duplicate responses in survey_response table
 */
// The field results_code was added to the table in 4.3.2, so don't include it in queries/logic if currently on version prior to that.
if (getDecVersion($current_version) < 40302) { 
	$results_code_sql = "";
	$hasResultsCode = false;
} else {
	$results_code_sql = ", r2.results_code";
	$hasResultsCode = true;
}
// Get duplicate responses
$sql = "select p2.survey_id, r2.participant_id, r2.record, r2.response_id, r2.completion_time, r2.return_code $results_code_sql 
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
		// Add if on version prior to 4.3.2
		if (!isset($row['results_code'])) $row['results_code'] = ""; 
		// Check if starting on next record
		if (($record !== null && $record !== $row['record']) || ($participant_id !== null && $participant_id !== $row['participant_id'])) 
		{
			print "-- survey_id: $survey_id, participant_id: $participant_id, record: $record\n";
			// Update the master response with the best info for all responses (so we don't lose return code, etc.)
			$sql = "update redcap_surveys_response set completion_time = " . checkNull($master_response[$record]['completion_time']) . ", "
				 . "return_code = " . checkNull($master_response[$record]['return_code']) . " ";
			if ($hasResultsCode) {
				$sql .= ", results_code = " . checkNull($master_response[$record]['results_code']) . " ";
			}
			$sql .= "where response_id = " . $master_response[$record]['response_id'];
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
			 . "return_code = " . checkNull($master_response[$record]['return_code']) . " ";
		if ($hasResultsCode) {
			$sql .= ", results_code = " . checkNull($master_response[$record]['results_code']) . " ";
		}
		$sql .= "where response_id = " . $master_response[$record]['response_id'];
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

