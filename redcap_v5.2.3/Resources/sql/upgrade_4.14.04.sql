-- Make sure return_code in redcap_surveys_response table is null if is blank string
update redcap_surveys_response set return_code = null where return_code = '';