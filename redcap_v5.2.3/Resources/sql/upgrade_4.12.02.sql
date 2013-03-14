-- Fix survey responses marked with Form Status incomplete even though they are really completed responses
update redcap_surveys s, redcap_surveys_participants p, redcap_surveys_response r, redcap_data d 
	set d.value = '2' where s.survey_id = p.survey_id and p.participant_id = r.participant_id 
	and r.completion_time is not null and d.project_id = s.project_id and p.event_id = d.event_id 
	and d.field_name = concat(s.form_name,'_complete') and r.record = d.record and d.value = '0';