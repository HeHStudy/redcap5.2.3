-- Fix email validation
UPDATE `redcap_validation_types` SET 
	`regex_js` =  '/^([_a-z0-9-'']+)(\\.[_a-z0-9-'']+)*@([a-z0-9-]+)(\\.[a-z0-9-]+)*(\\.[a-z]{2,4})$/i',
	`regex_php` =  '/^([_a-z0-9-'']+)(\\.[_a-z0-9-'']+)*@([a-z0-9-]+)(\\.[a-z0-9-]+)*(\\.[a-z]{2,4})$/i' 
	WHERE `validation_name` =  'email';