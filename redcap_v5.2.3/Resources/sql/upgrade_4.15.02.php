<?php

/**
 * Find all instances where __GROUPID__ is the only data point that exists for a record's event
 * in the redcap_data table, and remove it (causes data export issues with blank line - unnecessary).
 */

// Get all longitudinal projects with DAGS (those are the only kind affected)
$sql = "select distinct x.project_id from (select p.project_id from redcap_projects p, redcap_events_arms a, 
		redcap_events_metadata e where a.project_id = p.project_id and a.arm_id = e.arm_id and p.repeatforms = 1 
		group by p.project_id having count(e.event_id) > 1
		) x, redcap_data_access_groups g where g.project_id = x.project_id order by x.project_id";
$q = db_query($sql);
$hdgid = "";
if ($q && db_num_rows($q) > 0) 
{
	// Loop through each project
	while ($row = db_fetch_assoc($q))
	{
		// Get all record-events where the only data point is the GROUPID row
		$sql2 = "select a.event_id, a.record from redcap_data a, redcap_data b 
				where a.project_id = {$row['project_id']} and a.field_name = '__GROUPID__' 
				and a.project_id = b.project_id and a.event_id = b.event_id and a.record = b.record 
				group by b.record, b.event_id having count(b.field_name) = 1";
		$q2 = db_query($sql2);
		if ($q2 && db_num_rows($q2) > 0) 
		{
			while ($row2 = db_fetch_assoc($q2))
			{
				// Delete this single data point since it's the only row for this record-event
				$hdgid .= "delete from redcap_data where project_id = {$row['project_id']} and event_id = {$row2['event_id']} "
						. "and record = '".prep($row2['record'])."' and field_name = '__GROUPID__';\n";
			}
		}
		db_free_result($q2);
	}
	db_free_result($q);
}
// Output results
if ($hdgid == "") {
	print "-- Nothing to do here\n";
} else {
	print "-- Fix errors in data table: Remove rows where the only data point for a record-event is the __GROUPID__ (messes up data exports)\n" 
		. $hdgid;
	unset($hdgid);
}





/**
 * Modify events table to reduce "descrip" field to 64 character length
 */
print " -- Replace html-escaped characters in event description with their true value\n";
print "update redcap_events_metadata set descrip = replace(descrip, '&amp;gt;', '>'), 
	descrip = replace(descrip, '&amp;lt;', '<'), descrip = replace(descrip, '&amp;quot;', '\"'),
	descrip = replace(descrip, '&amp;#039;', '\''), descrip = replace(descrip, '&amp;#39;', '\''), 
	descrip = replace(descrip, '&amp;amp;', '&amp;') where descrip like '%&%';\n";
print " -- Modify events table\n";
print "ALTER TABLE `redcap_events_metadata` CHANGE `descrip` `descrip` VARCHAR(64) CHARACTER SET utf8 
	COLLATE utf8_unicode_ci NOT NULL DEFAULT  'Event 1' COMMENT  'Event Name';\n";
	
/**
 * Add survey scheduler cron (but leave disabled till 5.0)
 */
print " -- Add survey scheduler cron (but leave disabled till 5.0)\n
INSERT INTO `redcap_crons` (`cron_name`, `cron_description`, `cron_enabled`, `cron_frequency`, `cron_max_run_time`, 
	`cron_last_run_end`, `cron_status`, `cron_times_failed`, `cron_external_url`) 
	VALUES ('SurveyInvitationEmailer', 'Mailer that sends any survey invitations that have been scheduled.', 'DISABLED', '30', 
	'7200', NULL, 'NOT YET RUN', '0', NULL);\n";