
UPDATE `redcap_validation_types` SET  `regex_js` =  '/^(\\(0[2-8]\\)|0[2-8])\\s*\\d{4}\\s*\\d{4}$/' 
	WHERE `validation_name` =  'phone_australia';