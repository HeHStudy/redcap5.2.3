<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


/**
 * USER Class
 * Contains methods used with regard to users
 */
class User
{
	
	// Return HTML for a select drop-down of ALL project users with ui_id as key
	public static function dropDownListAllUsernames($dropdownId, $selectedValue='', $excludeUsernames=array(), $onChangeJS='', $appendFirstLastName=true, $disabled=false)
	{
		global $lang;
		// Set disabled attribute
		$disabled = ($disabled) ? "disabled" : "";
		// Create select list of usernames
		$userOptions = array(''=>$lang['rights_133']);
		// Get email addresses and names from table
		$sql = "select i.ui_id, i.username, trim(concat(i.user_firstname, ' ', i.user_lastname)) as full_name
				from redcap_user_rights u, redcap_user_information i 
				where u.project_id = ".PROJECT_ID." and i.username = u.username order by i.username";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q))
		{
			// Exclude users
			if (in_array($row['username'], $excludeUsernames)) continue;
			// Add user to array
			$userOptions[$row['ui_id']] = $row['username'];
			// Add first/last name to array (if flag is set)
			if ($appendFirstLastName) {
				$userOptions[$row['ui_id']] .= " ({$row['full_name']})";
			}
		}
		// Set select box html
		$userSelect = RCView::select(array('id'=>$dropdownId,'class'=>'x-form-text x-form-field', $disabled=>$disabled,
						'style'=>'padding-right:0;height:22px;', 'onchange'=>$onChangeJS), $userOptions, $selectedValue, 100);
		// Return the HTML
		return $userSelect;
	}
	
	// Return HTML for a select drop-down of the current user's email addresses associated with
	// their REDCap account. If don't have a secondary/tertiary email listed, then let last option (if desired)
	// be a clickable trigger to open dialog for setting up a secondary/tertiary email.
	public static function emailDropDownList($appendAddEmailOption=true,$dropdownId='emailFrom',$dropdownName='emailFrom')
	{
		global $lang, $user_email, $user_email2, $user_email3;
		// Create select list for From email address (do not display any that are still pending approval)
		$fromEmailOptions = array('1'=>$user_email);
		if ($user_email2 != '') {
			$fromEmailOptions['2'] = $user_email2;
		}
		if ($user_email3 != '') {
			$fromEmailOptions['3'] = $user_email3;
		}
		// Add option to add more emails (if designated)
		if ($appendAddEmailOption && ($user_email2 == '' || $user_email3 == '')) {
			$fromEmailOptions['999'] = $lang['survey_349'];
		}
		// Set select box html
		$fromEmailSelect = RCView::select(array('id'=>$dropdownId,'name'=>$dropdownName,'class'=>'x-form-text x-form-field',
			'style'=>'padding-right:0;height:22px;',
			'onchange'=>"if(this.value=='999') { setUpAdditionalEmails(); this.value='1'; }"), $fromEmailOptions, '1', 100);
		// Return the HTML
		return $fromEmailSelect;
	}
	
	// Return HTML for a select drop-down of ALL project users' email addresses associated with
	// their REDCap account. If don't have a secondary/tertiary email listed, then let last option (if desired)
	// be a clickable trigger to open dialog for setting up a secondary/tertiary email.
	public static function emailDropDownListAllUsers($selectedValue=null,$appendAddEmailOption=true,$dropdownId='emailFrom',$dropdownName='emailFrom')
	{
		global $lang, $user_email, $user_email2, $user_email3;
		// Create select list for From email address of ALL project users (do not display any that are still pending approval)
		$fromEmailOptions = array();
		// If selected email doesn't belong to anyone on the project anymore, then keep it as an extra option
		if ($selectedValue != '' && !in_array($selectedValue, $fromEmailOptions)) {
			$fromEmailOptions[$selectedValue] = "??? ($selectedValue)";
		}
		// Get email addresses and names from table
		$sql = "select distinct x.email, trim(concat(x.user_firstname, ' ', x.user_lastname)) as name from (
				(select i.user_email as email, i.user_firstname, i.user_lastname from redcap_user_rights u, redcap_user_information i 
					where u.project_id = ".PROJECT_ID." and i.username = u.username and i.email_verify_code is null) 
				union 
				(select i.user_email2 as email, i.user_firstname, i.user_lastname from redcap_user_rights u, redcap_user_information i 
					where u.project_id = ".PROJECT_ID." and i.username = u.username and i.email2_verify_code is null and i.user_email2 is not null) 
				union 
				(select i.user_email3 as email, i.user_firstname, i.user_lastname from redcap_user_rights u, redcap_user_information i 
					where u.project_id = ".PROJECT_ID." and i.username = u.username and i.email3_verify_code is null and i.user_email3 is not null)) x 
				order by trim(concat(x.user_firstname, ' ', x.user_lastname)), x.email";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q))
		{
			// Clean, just in case
			$row['email'] = label_decode($row['email']);
			$row['name'] = label_decode($row['name']);
			// Add to array
			$fromEmailOptions[$row['email']] = "{$row['name']} ({$row['email']})";
		}
		// Add option to add more emails (if designated)
		if ($appendAddEmailOption && ($user_email2 == '' || $user_email3 == '')) {
			$fromEmailOptions['999'] = $lang['survey_349'];
		}
		// Set the default selected value (if none, then use current user's primary email)
		$selectedValue = ($selectedValue == '') ? $user_email : $selectedValue;
		// Set select box html
		$fromEmailSelect = RCView::select(array('id'=>$dropdownId,'name'=>$dropdownName,'class'=>'x-form-text x-form-field',
			'style'=>'padding-right:0;height:22px;',
			'onchange'=>"if(this.value=='999') { setUpAdditionalEmails(); this.value='".cleanHtml($selectedValue)."'; }"), $fromEmailOptions, $selectedValue, 100);
		// Return the HTML
		return $fromEmailSelect;
	}
	
	// Generate unique user verification code for their email account
	private static function generateUserVerificationCode()
	{
		do {
			// Generate a new random hash
			$code = generateRandomHash(20);
			// Ensure that the hash doesn't already exist in table
			$sql = "select 1 from redcap_user_information where (email_verify_code = '$code' 
					or email2_verify_code = '$code' or email3_verify_code = '$code') limit 1";
			$codeExists = (db_num_rows(db_query($sql)) > 0);
		} while ($codeExists);
		// Code is unique, so return it
		return $code;
	}

	// Set user's email address (primary=1, secondary=2, or tertiary=3)
	// Provide user's ui_id and which email account this is for.
	public static function setUserEmail($ui_id, $email="", $email_account=1)
	{
		// Validate email
		if (!isEmail($email)) return false;
		// Determine which user_email field we're updating based upon $email_account
		$user_email_field = "user_email" . ($email_account > 1 ? $email_account : "");
		// Add code to table (if code already exists for this primary/secondary/tertiary email, then update the code with new value)
		$sql = "update redcap_user_information set $user_email_field = '" . prep($email) . "' 
				where ui_id = '".prep($ui_id)."'";
		return (db_query($sql));
	}

	// Remove a user's secondary=2 or tertiary=3 email address from their account
	public static function removeUserEmail($ui_id, $email_account=null)
	{
		if (!is_numeric($email_account)) return false;
		// Determine which user_email field we're updating based upon $email_account
		$user_email_field = "user_email{$email_account}";
		$user_verify_code_field = "email{$email_account}_verify_code";
		// Remove email from table
		$sql = "update redcap_user_information set $user_email_field = null, $user_verify_code_field = null 
				where ui_id = '".prep($ui_id)."'";
		$q = db_query($sql);
		if (!$q) return false;
		// If secondary email was removed, then if tertiary email exist, make it the secondary email (move value in table)
		if ($email_account == '2')
		{
			// Get user info
			$user_info = User::getUserInfo(USERID);
			// If it has a tertiary email, move to secondary position
			if ($user_info['user_email3'] != '') {
				$sql = "update redcap_user_information set user_email2 = user_email3, email2_verify_code = email3_verify_code,
						user_email3 = null, email3_verify_code = null where ui_id = '".prep($ui_id)."'";
				$q = db_query($sql);
			}
		}
		return true;
	}

	// Get unique user verification code for their email account
	// Provide user's ui_id and which email account this is for.
	public static function setUserVerificationCode($ui_id, $email_account=1)
	{
		// Generate a new random code
		$code = self::generateUserVerificationCode();
		// Determine which user_email field we're updating based upon $email_account
		$user_email_field = "email" . ($email_account > 1 ? $email_account : "") . "_verify_code";
		// Add code to table (if code already exists for this primary/secondary/tertiary email, then update the code with new value)
		$sql = "update redcap_user_information set $user_email_field = '$code' 
				where ui_id = '".prep($ui_id)."'";
		return (db_query($sql) ? $code : false);
	}
	
	// Email the user email verification code to the user
	public static function sendUserVerificationCode($new_email, $code, $email_account=1)
	{
		global $lang, $redcap_version, $user_email;
		// Email the user (new users get their login info, existing users get notified if their email changes)
		$email = new Message();
		// Send the email From the user's primary address
		$email->setTo($new_email);
		$email->setFrom((empty($user_email) ? $new_email : $user_email));
		$email->setSubject('[REDCap] '.$lang['user_19']);
		if ($email_account == 1) {
			// Primary email account
			$emailContents = $lang['user_20'];
			// Set verification url
			$url = APP_PATH_WEBROOT_FULL . "index.php?user_verify=$code";
		} else {
			// Secondary or tertiary email account
			$emailContents = $lang['user_49'];
			// Set verification url
			$url = APP_PATH_WEBROOT_FULL . "redcap_v{$redcap_version}/Profile/additional_email_verify.php?user_verify=$code";
		}
		$emailContents .= '<br /><br /><a href="'.$url.'">'.$lang['user_21'].'</a><br /><br />'
						. $lang['survey_135'].'<br />'.$url.'<br /><br />'.$lang['survey_137'];
		$email->setBody($emailContents, true);
		if ($email->send()) {
			return true;
		}			
		exit($email->getSendError());
	}
	
	// Get all info for specified user from user_information table and return as array
	public static function getUserInfo($userid)
	{
		$sql = "select * from redcap_user_information where username = '".prep($userid)."' limit 1";
		$q = db_query($sql);
		return (($q && db_num_rows($q) > 0) ? db_fetch_assoc($q) : false);
	}
	
	// Get all info for specified user from user_information table by using user's UI_ID and return as array
	public static function getUserInfoByUiid($ui_id)
	{
		if (!is_numeric($ui_id)) return false;
		$sql = "select * from redcap_user_information where ui_id = $ui_id limit 1";
		$q = db_query($sql);
		return (($q && db_num_rows($q) > 0) ? db_fetch_assoc($q) : false);
	}
	
	// Update user_firstvisit value for specified user in user_information table
	public static function updateUserFirstVisit($userid)
	{
		$sql = "update redcap_user_information set user_firstvisit = '".NOW."' 
				where username = '".prep($userid)."' limit 1";
		return db_query($sql);
	}
	
	// Verify a user's email verification code that they received in an email. 
	// Return the email account it corresponds to (1=primary,2=secondary,3=tertiary) or false if failed.
	public static function verifyUserVerificationCode($userid, $code)
	{
		// Query the table
		$sql = "select email_verify_code, email2_verify_code, email3_verify_code 
				from redcap_user_information where username = '".prep($userid)."' 
				and (email_verify_code = '".prep($code)."' or email2_verify_code = '".prep($code)."' 
				or email3_verify_code = '".prep($code)."') limit 1";
		$q = db_query($sql);
		if ($q && db_num_rows($q) > 0) {
			$row = db_fetch_assoc($q);
			// Determine which email account it corresponds to
			if ($row['email_verify_code'] == $code) {
				return '1';
			} elseif ($row['email2_verify_code'] == $code) {
				return '2';			
			} elseif ($row['email3_verify_code'] == $code) {
				return '3';
			}
			return false;
		} else {
			return false;
		}
	}
	
	// Remove a user's email verification code from the user_info table after their account has been verified
	public static function removeUserVerificationCode($userid, $email_account=1)
	{
		// Determine which user_email field we're updating based upon $email_account
		$user_email_field = "email" . ($email_account > 1 ? $email_account : "") . "_verify_code";
		// Query the table
		$sql = "update redcap_user_information set $user_email_field = null 
				where username = '".prep($userid)."' limit 1";
		$q = db_query($sql);
		return ($q && db_affected_rows() > 0);
	}
	
	// Determine if specified username is a Table-based user (i.e. in redcap_auth)
	public static function isTableUser($userid)
	{
		// Query the table
		$sql = "select 1 from redcap_auth where username = '".prep($userid)."' limit 1";
		$q = db_query($sql);
		return ($q && db_num_rows($q) > 0);
	}

	// Check if an email address is acceptable regarding the "domain whitelist for user emails" (if enabled)
	public static function emailInDomainWhitelist($email='') {
		global $email_domain_whitelist;
		$email = trim($email);
		if ($email_domain_whitelist == '' || $email == '') return null;
		$email_domain_whitelist_array = explode("\n", str_replace("\r", "", $email_domain_whitelist));
		list ($emailFirstPart, $emailDomain) = explode('@', $email, 2);
		return (in_array($emailDomain, $email_domain_whitelist_array));
	}
	
}
