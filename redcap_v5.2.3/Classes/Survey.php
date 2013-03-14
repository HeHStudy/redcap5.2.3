<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


/**
 * SURVEY Class
 * Contains methods used with regard to surveys
 */
class Survey
{
	// Return array of form_name and survey response status (0=partial,2=complete) 
	// for a given project-record-event. $record may be a single record name or array of record names.
	static function getResponseStatus($project_id, $record=null, $event_id=null)
	{
		$surveyResponses = array();
		$sql = "select r.record, p.event_id, s.form_name, if(r.completion_time is null,0,2) as survey_complete 
				from redcap_surveys s, redcap_surveys_participants p, redcap_surveys_response r 
				where s.survey_id = p.survey_id and p.participant_id = r.participant_id 
				and s.project_id = $project_id and r.first_submit_time is not null";
		if ($record != null && is_array($record)) {
			$sql .= " and r.record in (".prep_implode($record).")";
		} elseif ($record != null) {
			$sql .= " and r.record = '".prep($record)."'";
		}
		if (is_numeric($event_id)) 	$sql .= " and p.event_id = $event_id";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q)) {
			$surveyResponses[$row['record']][$row['event_id']][$row['form_name']] = $row['survey_complete'];
		}
		return $surveyResponses;
	}
}
