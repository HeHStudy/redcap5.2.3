<?php

/**
 * Authentication
 * This class is used for authentication-centric activities.
 */
class Authentication
{	
	// Return array of available security questions with Primary Key as array key.
	// If provide qid key, then only return question text for that one question.
	static function getSecurityQuestions($qid="")
	{
		// Check qid value first
		if ($qid != "" && !is_numeric($qid)) return false;
		$sqlQid = (is_numeric($qid)) ? "where qid = ".prep($qid) : "";
		// Query table to question text
		$sql = "select * from redcap_auth_questions $sqlQid order by qid";
		$q = db_query($sql);
		if (!$q || db_num_rows($q) < 1) {
			return false;
		} elseif (is_numeric($qid)) {
			// Return single question text
			return db_result($q, 0, 'question');
		} else {
			// Return all questions as array
			$questions = array();
			while ($row = db_fetch_assoc($q)) {
				$questions[$row['qid']] = $row['question'];
			}
			// Return array
			return $questions;
		}
	}
	
	// Clean and convert security answer to MD5 hash
	static function hashSecurityAnswer($answer)
	{
		// Trim and remove non-alphanumeric characters (but keep spaces and keep lower-case)
		$answer = strtolower(trim($answer));
		$answer = preg_replace("/[^0-9a-z ]/", "", $answer);
		return md5($answer);	
	}
	
	// Authenticate the user using Vanderbilt's custom C4 cookie-based authentication
	static function authenticateC4Cookie()
	{
		// Include database.php again in order to get secret C4 auth variables
		include dirname(APP_PATH_DOCROOT) . DS . 'database.php';	
		
		// Make sure we have all the requisite variables
		if (!isset($c4_auth_cookiename) || !isset($c4_auth_iv) || !isset($c4_auth_key)) {
			exit("ERROR! Could not find the following variables in your database.php file: \$c4_auth_cookiename, \$c4_auth_iv, \$c4_auth_key
			 $c4_auth_cookiename, $c4_auth_iv, $c4_auth_key");
		}
		
		// Check to make sure that the Mcrypt PHP extension is loaded
		if (!mcrypt_loaded(true)) return false;
		
		// Get cookie value
		if (!isset($_COOKIE[$c4_auth_cookiename])) return false;
		$cookieValue = $_COOKIE[$c4_auth_cookiename];
		
		// Decode cookie value to get username
		$username = rtrim(mcrypt_decrypt(MCRYPT_BLOWFISH, md5($c4_auth_key), base64_decode($cookieValue), MCRYPT_MODE_CBC, base64_decode($c4_auth_iv)));
		
		// Since ALL usernames should be email addresses, make sure it's a valid email
		if (!preg_match("/^([_a-z0-9-']+)(\.[_a-z0-9-']+)*@([a-z0-9-]+)(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i", $username)) return false; 
		
		// If all is well, return decoded cookie value
		return $username;
	}
	

	// Display notice that password will expire soon (if utilizing $password_reset_duration for Table-based authentication)
	static function displayPasswordExpireWarningPopup()
	{
		global $lang;
		// If expiration time is in session, then display pop-up
		if (isset($_SESSION['expire_time']) && !empty($_SESSION['expire_time']))
		{
			?>
			<div id="expire_pwd_msg" style="display:none;" title="<?php echo cleanHtml2($lang['pwd_reset_20']) ?>">
				<p><?php echo "{$lang['pwd_reset_19']} (<b>{$_SESSION['expire_time']}</b>){$lang['period']} {$lang['pwd_reset_21']}" ?></p>
			</div>
			<script type="text/javascript">
			$(function(){
				$('#expire_pwd_msg').dialog({ bgiframe: true, modal: true, width: 450, buttons: {
					'Later': function() { $(this).dialog('destroy'); },
					'Change my password': function() { 
						$.get(app_path_webroot+'ControlCenter/user_controls_ajax.php', { action: 'reset_password_as_temp' }, function(data) { 
							if (data != '0') {
								window.location.reload(); 
							} else { 
								alert(woops); 
							} 
						});
					}
				}});
			});
			</script>
			<?php
			// Remove variable from session so that the user doesn't keep getting prompted
			unset($_SESSION['expire_time']);
		}
	}
	
	
	// Check if need to display pop-up dialog to SET UP SECURITY QUESTION for table-based users
	static function checkSetUpSecurityQuestion()
	{
		global $lang, $user_email;
		// Display pop-up dialog to set up security question
		if (defined("SET_UP_SECURITY_QUESTION")) 
		{
			// Display drop-down of security questions
			$dd_questions = array(""=>RCView::escape(" - ".$lang['pwd_reset_22']." - "));
			foreach (Authentication::getSecurityQuestions() as $qid=>$question) {
				$dd_questions[$qid] = RCView::escape($question);
			}
			$securityQuestionDD = RCView::select(array('id'=>'securityQuestion','class'=>'x-form-text x-form-field','style'=>'padding-right:0;height:22px'), $dd_questions, "", 400);
			// Instructions and form
			$html = RCView::div(array('id'=>'setUpSecurityQuestionDiv'),
						RCView::p(array(), $lang['pwd_reset_37']) . 
						RCView::div(array('id'=>'setUpSecurityQuestionDiv','style'=>'max-width:700px;margin:20px 3px 20px;padding:15px 20px 5px;border:1px solid #ccc;background-color:#f5f5f5;'),
							RCView::div(array('style'=>'font-weight:bold;padding-bottom:10px;'), 
								RCView::span(array(), $lang['pwd_reset_34']) . RCView::SP . RCView::SP . 
								$securityQuestionDD
							) .
							RCView::div(array('style'=>'font-weight:bold;padding-bottom:10px;'), 
								RCView::span(array(), $lang['pwd_reset_35']) . RCView::SP . RCView::SP . 
								"<input type='text' id='securityAnswer' class='x-form-text x-form-field' style='width:200px;' autocomplete='off'>"  . RCView::SP . RCView::SP . 
								RCView::span(array('style'=>'color:#666;font-size:11px;font-family:tahoma;font-weight:normal;'), $lang['pwd_reset_50'])
							) .
							RCView::div(array('style'=>'padding:20px 0 10px;'), 
								RCView::span(array(), $lang['pwd_reset_48']) . RCView::SP . RCView::SP . 
								"<input type='text' id='user_email' class='x-form-text x-form-field' style='width:200px;' value='".cleanHtml($user_email)."' autocomplete='off'>"  . 
								RCView::div(array('style'=>'color:#666;font-size:11px;font-family:tahoma;padding-top:3px;'), $lang['pwd_reset_49'])
							)
						) . 
						RCView::div(array('style'=>'margin:15px 15px 20px;'), 
							RCView::submit(array('class'=>'jqbutton','value'=>$lang['designate_forms_13'],'style'=>'font-family:verdana;line-height:25px;font-size:13px;','onclick'=>'setUpSecurityQuestionAjax();')) .
							RCView::span(array('style'=>'margin-left:30px;'), RCView::a(array('href'=>'javascript:;','style'=>'color:#800000;text-decoration:underline;','onclick'=>"$('#setUpSecurityQuestion').dialog('close');"), $lang['pwd_reset_46']))
						)
					);
			?>
			<!-- Div for dialog content -->
			<div id="setUpSecurityQuestion" style="display:none;" title="<?php echo cleanHtml2($lang['pwd_reset_36']) ?>"><?php echo $html ?></div>
			<!-- Javascript for dialog -->
			<script type="text/javascript">
			$(function(){
				$('#setUpSecurityQuestion').dialog({ bgiframe: true, modal: true, width: 700, 
					close: function() { setSecurityQuestionReminder(); }
				});
			});
			// Remind question/answer in 2 days
			function setSecurityQuestionReminder() {
				// Ajax request
				$.post(app_path_webroot+'Authentication/password_recovery_setup.php',{ setreminder: '1' }, function(data){
					$('#setUpSecurityQuestion').dialog('destroy');
					if (data == '1') {
						alert('<?php echo cleanHtml($lang['pwd_reset_47']) ?>');
					}
				});
			}
			// Submit question/answer
			function setUpSecurityQuestionAjax() {
				// Check values
				$('#securityAnswer').val(trim($('#securityAnswer').val()));
				$('#user_email').val(trim($('#user_email').val()));
				var user_email = $('#user_email').val();
				var answer = $('#securityAnswer').val();
				var question = $('#securityQuestion').val();
				if (answer.length < 1 || question.length < 1 || user_email.length < 1) {
					alert('<?php echo cleanHtml($lang['pwd_reset_38']) ?>');
					return false;
				}
				// Ajax request
				$.post(app_path_webroot+'Authentication/password_recovery_setup.php',{ answer: answer, question: question, user_email: user_email }, function(data){
					$('#setUpSecurityQuestionDiv').html(data);
					initWidgets();
				});
			}
			</script>
			<?php
		}
	}
	
		
	/**
	 * AUTHENTICATE THE USER
	 */
	static function authenticate() 
	{	
		global $auth_meth, $app_name, $username, $password, $hostname, $db, $institution, $double_data_entry, 
			   $project_contact_name, $autologout_timer, $lang, $isMobileDevice, $password_reset_duration, $enable_user_whitelist,
			   $homepage_contact_email, $homepage_contact, $isAjax;

		// Check if authentication was manually disabled for the current page. If so, exit this function.
		if (defined("NOAUTH")) return true;	
		
		// Start the session before PEAR Auth does so we can check if auth session was lost or not (from load balance issues)
		if (!session_id()) @session_start();
		
		// Set default value to determine later if we need to make left-hand menu disappear so user has access to nothing
		$GLOBALS['no_access'] = 0;
		
		// If logging in, trim the username to prevent confusion and accidentally creating a new user
		if (isset($_POST['redcap_login_a38us_09i85']) && $auth_meth != "none")
		{
			$_POST['username'] = trim($_POST['username']);
			// Make sure it's not longer than 255 characters to prevent attacks via hitting upper bounds
			if (strlen($_POST['username']) > 255) {
				$_POST['username'] = substr($_POST['username'], 0, 255);
			}
		}

		// AUTHENTICATE and GET USERNAME: Determine method of authentication
		switch ($auth_meth) 
		{
			// No authentication is used
			case 'none':
				$userid = 'site_admin'; //Default user
				break;
			 // Vanderbilt authentication sessioning
			case 'local':
				$userid = $_SESSION['userid'];
				break;
			 // RSA SecurID two-factor authentication (using PHP Pam extension)
			case 'rsa':
				// If username in session doesn't exist and not on login page, then force login
				if (!isset($_SESSION['rsa_username']) && !isset($_POST['redcap_login_a38us_09i85'])) {
					loginFunction();
				}
				// User is attempting to log in, so try to authenticate them using PAM
				elseif (isset($_POST['redcap_login_a38us_09i85'])) 
				{
					// Make sure RSA password is not longer than 14 characters to prevent attacks via hitting upper bounds 
					// (8 char max for PIN + 6-digit tokencode)
					if (strlen($_POST['password']) > 14) {
						$_POST['password'] = substr($_POST['password'], 0, 14);
					}
					// If PHP PECL package PAM is not installed, then give error message
					if (!function_exists("pam_auth")) {
						if (isDev()) {
							// For development purposes only, allow passthru w/o valid authentication
							$userid = $_SESSION['rsa_username'] = $_POST['username'];
						} else {
							// Display error
							renderPage(
								RCView::div(array('class'=>'red'),
									RCView::div(array('style'=>'font-weight:bold;'), $lang['global_01'].$lang['colon']) .
									"The PECL PAM package in PHP is not installed! The PAM package must be installed in order to use
									the pam_auth() function in PHP to authenticate tokens via RSA SecurID. You can find the offical 
									documentation on PAM at <a href='http://pecl.php.net/package/PAM' target='_blank'>http://pecl.php.net/package/PAM</a>."
								)
							);
						}
					} 
					// If have logged in, then try to authenticate the user
					elseif (pam_auth($_POST['username'], $_POST['password'], $err, false) === true) {
						$userid = $_SESSION['rsa_username'] = $_POST['username'];
					} 
					// Error
					else {
						// Render error message and show login screen again
						print   RCView::div(array('class'=>'red','style'=>'max-width:100%;width:100%;font-weight:bold;'),
									RCView::img(array('src'=>'exclamation.png','class'=>'imgfix')) .
									"{$lang['global_01']}{$lang['colon']} {$lang['config_functions_49']}"
								);
						loginFunction();
					}
				}
				// If already logged in, the just set their username
				elseif (isset($_SESSION['rsa_username'])) {
					$userid = $_SESSION['rsa_username'];
				}
				break;
			 // Vanderbilt custom C4 cookie-based authentication
			case 'c4':
				// Check userid from cookie
				$userid = Authentication::authenticateC4Cookie();
				if ($userid === false) {
					// For no obvious reason, we need to output something first or else EVERYTHING in the /redcap directory will not load. (WHY?)
					print " ";
					// If not logged in yet, then redirect to C4 login page
					redirect("https://".SERVER_NAME."/plugins/auth/?redirectUrl=".urlencode($_SERVER['REQUEST_URI']));
				}
				break;
			// Shibboleth authentication
			case 'shibboleth':
				$userid = $_SERVER['REMOTE_USER']; // Default value
				$GLOBALS['shibboleth_username_field'] = trim($GLOBALS['shibboleth_username_field']);
				if (strlen($GLOBALS['shibboleth_username_field']) > 0) {
					$userid = $_SERVER[$GLOBALS['shibboleth_username_field']];
				}
				break;
			// Error was made in Control Center for authentication
			case '':
				exit("{$lang['config_functions_20']} 
					  <a target='_blank' href='". APP_PATH_WEBROOT . "ControlCenter/edit_project.php?project=".PROJECT_ID."'>REDCap {$lang['global_07']}</a>.");
			// Table-based and/or LDAP authentication
			default:
				// PEAR Auth
				if (!include_once 'Auth.php') {
					exit("{$lang['global_01']}{$lang['colon']} {$lang['config_functions_22']}");
				}
				// Table info for redcap_auth
				$GLOBALS['mysqldsn'] = array(	'table' 	  => 'redcap_auth',
												'usernamecol' => 'username',
												'passwordcol' => 'password',
												'crypType'    => '',
												'debug' 	  => false,
												'dsn' 		  => "mysqli://$username:$password@$hostname/$db");
				// This variable sets the timeout limit if server activity is idle
				$autologout_timer = ($autologout_timer == "") ? 0 : $autologout_timer;
				// In case of users having characters in password that were stripped out earlier, restore them (LDAP only)
				if (isset($_POST['password'])) $_POST['password'] = html_entity_decode($_POST['password'], ENT_QUOTES);
				// LDAP Connection Information for your Institution
				$GLOBALS['ldapdsn'] = array();
				if ($auth_meth == "ldap" || $auth_meth == "ldap_table") {
					include APP_PATH_WEBTOOLS . 'ldap/ldap_config.php';
				}
				// Check if user is logged in
				Authentication::checkLogin("", $auth_meth);
				// Set username variable passed from PEAR Auth
				$userid = $_SESSION['username'];
				// Check if table-based user has a temporary password. If so, direct them to page to set it.
				if ($auth_meth == "table" || $auth_meth == "ldap_table") 
				{
					$q = db_query("select * from redcap_auth where username = '".prep($userid)."'");
					$isTableBasedUser = db_num_rows($q);
					// User is table-based user
					if ($isTableBasedUser) 
					{
						// Get values from auth table
						$temp_pwd 					= db_result($q, 0, 'temp_pwd');
						$password_question 			= db_result($q, 0, 'password_question');
						$password_answer 			= db_result($q, 0, 'password_answer');
						$password_question_reminder = db_result($q, 0, 'password_question_reminder');
						
						// Check if need to trigger setup for SECURITY QUESTION (only on My Projects page or project's Home/Project Setup page)
						$myProjectsUri = "/index.php?action=myprojects";
						$pagePromptSetSecurityQuestion = (substr($_SERVER['REQUEST_URI'], strlen($myProjectsUri)*-1) == $myProjectsUri || PAGE == 'index.php' || PAGE == 'ProjectSetup/index.php');
						$conditionPromptSetSecurityQuestion = (!isset($_POST['redcap_login_a38us_09i85']) && !$isAjax && empty($password_question) && (empty($password_question_reminder) || NOW > $password_question_reminder));
						if ($pagePromptSetSecurityQuestion && $conditionPromptSetSecurityQuestion)
						{
							// Set flag to display pop-up dialog to set up security question
							define("SET_UP_SECURITY_QUESTION", true);
						}						
						
						// If using table-based auth and enforcing password reset after X days, check if need to reset or not
						if (isset($_POST['redcap_login_a38us_09i85']) && !empty($password_reset_duration))
						{
							// Also add to auth_history table
							$sql = "select timestampdiff(MINUTE,timestamp,'".NOW."')/60/24 as daysExpired, 
									timestampadd(DAY,$password_reset_duration,timestamp) as expirationTime from redcap_auth_history 
									where username = '$userid' order by timestamp desc limit 1";
							$q = db_query($sql);
							$daysExpired = db_result($q, 0, "daysExpired");
							$expirationTime = db_result($q, 0, "expirationTime");
								
							// If the number of days expired has passed, then redirect them to the password reset page
							if (db_num_rows($q) < 1 || $daysExpired > $password_reset_duration)
							{
								// Set the temp password flag to prompt them to enter new password
								db_query("UPDATE redcap_auth SET temp_pwd = 1 WHERE username = '$userid'");
								// Redirect to password reset page with flag set
								redirect(APP_PATH_WEBROOT . "Authentication/password_reset.php?msg=expired");
							} 
							// If within 7 days of expiring, then give a notice on next page load.
							elseif ($daysExpired > $password_reset_duration-7)
							{
								// Put expiration time in session in order to prompt user on next page load
								$_SESSION['expire_time'] = format_ts_mysql($expirationTime);
							}
						}
						// If temporary password flag is set, then redirect to allow user to set new password
						if ($temp_pwd == '1' && PAGE != "Authentication/password_reset.php") 
						{
							redirect(APP_PATH_WEBROOT . "Authentication/password_reset.php" . ((isset($app_name) && $app_name != "") ? "?pid=" . PROJECT_ID : ""));
						}
					}
					
				}
		}
		
		// If $userid is somehow blank (e.g. authentication server is down), then prevent from accessing.
		if (trim($userid) == '') 
		{
			// If using Shibboleth authentication and user is on API Help page but somehow lost their username 
			// (or can't be used in /api directory due to Shibboleth setup), then just redirect to the target page itself.
			if ($auth_meth == 'shibboleth' && strpos(PAGE_FULL, '/api/help/index.php') !== false) {
				redirect(APP_PATH_WEBROOT . "API/help.php");
			}
			// Display error message
			$objHtmlPage = new HtmlPage();
			$objHtmlPage->addStylesheet("style.css", 'screen,print');
			$objHtmlPage->addStylesheet("home.css", 'screen,print');
			$objHtmlPage->PrintHeader();
			print RCView::br() . RCView::br()
				. RCView::errorBox($lang['config_functions_82']." <a href='mailto:$homepage_contact_email'>$homepage_contact</a>{$lang['period']}")
				. RCView::button(array('onclick'=>"window.location.href='".APP_PATH_WEBROOT_FULL."index.php?logout=1';"), "Try again");
			$objHtmlPage->PrintFooter();
			exit;
		}
		
		// LOGOUT: Check if need to log out
		Authentication::checkLogout();
		
		// USER WHITELIST: If using external auth and user whitelist is enabled, the validate user as in whitelist
		if ($enable_user_whitelist && $auth_meth != 'none' && $auth_meth != 'table')
		{
			// The user has successfully logged in, so determine if they're an external auth user
			$isExternalUser = ($auth_meth != "ldap_table" || ($auth_meth == "ldap_table" && isset($isTableBasedUser) && !$isTableBasedUser));
			// They're an external auth user, so make sure they're in the whitelist
			if ($isExternalUser)
			{
				$sql = "select 1 from redcap_user_whitelist where username = '" . prep($userid) . "'";
				$inWhitelist = db_num_rows(db_query($sql));
				// If not in whitelist, then give them error page
				if (!$inWhitelist)
				{					
					// Give notice that user cannot access REDCap
					$objHtmlPage = new HtmlPage();
					$objHtmlPage->addStylesheet("style.css", 'screen,print');
					$objHtmlPage->addStylesheet("home.css", 'screen,print');
					$objHtmlPage->PrintHeader();
					print  "<div class='red' style='margin:40px 0 20px;padding:20px;'>
								{$lang['config_functions_78']} \"<b>$userid</b>\"{$lang['period']} 
								{$lang['config_functions_79']} <a href='mailto:$homepage_contact_email'>$homepage_contact</a>{$lang['period']}
							</div>
							<button onclick=\"window.location.href='".APP_PATH_WEBROOT_FULL."index.php?logout=1';\">Go back</button>";
					$objHtmlPage->PrintFooter();
					exit;
				}
			}
		}
		
		// If logging in, update Last Login time in user_information table
		if (isset($_POST['redcap_login_a38us_09i85']))
		{
			$sql = "update redcap_user_information set user_lastlogin = '" . NOW . "'
					where username = '" . prep($userid) . "'";
			db_query($sql);
		}

		// If just logged in, redirect back to same page to avoid $_POST confliction on certain pages.
		// Do NOT simply redirect if user lost their session when saving data so that their data will be resurrected.
		if (isset($_POST['redcap_login_a38us_09i85']) && !isset($_POST['redcap_login_post_encrypt_e3ai09t0y2']))
		{	
			## REDIRECT PAGE
			// Redirect any logins via mobile devices to Mobile directory (unless user is on a plugin page)
			if ($isMobileDevice && !defined("PLUGIN"))
			{
				redirect(APP_PATH_WEBROOT . "Mobile/");
			}
			// Redirect back to this same page
			else
			{
				redirect($_SERVER['REQUEST_URI']);
			}
		}
		
		// CHECK USER INFO: Make sure that we have the user's email address and name in redcap_user_information. If not, prompt user for it.
		if (PAGE != "Profile/user_info_action.php" && PAGE != "Authentication/password_reset.php") {
			// Set super_user default value
			$super_user = 0;
			// Get user info
			$row = User::getUserInfo($userid);
			// If user has no email address or is not in user_info table, then prompt user for their name and email
			if (empty($row) || $row['user_email'] == "" || ($row['user_email'] != "" && $row['email_verify_code'] != "")) {
				// Prompt user for values
				include APP_PATH_DOCROOT . "Profile/user_info.php";
				exit;	
			} else {
				// Define user's name and email address for use throughout the application
				$user_email 	= $row['user_email'];
				$user_firstname = $row['user_firstname'];
				$user_lastname 	= $row['user_lastname'];
				$super_user 	= $row['super_user'];
				$user_firstactivity = $row['user_firstactivity'];
				$allow_create_db 	= $row['allow_create_db'];
				// Do not let the secondary/tertiary emails be set unless they have been verified first
				$user_email2 	= ($row['user_email2'] != '' && $row['email2_verify_code'] == '') ? $row['user_email2'] : "";
				$user_email3 	= ($row['user_email3'] != '' && $row['email3_verify_code'] == '') ? $row['user_email3'] : "";
			}
			// If we have not recorded time of user's first visit, then set it
			if ($row['user_firstvisit'] == "") 
			{
				User::updateUserFirstVisit($userid);
			}
			// Check if user account has been suspended
			if ($row['user_suspended_time'] != "") 
			{
				// Give notice that user cannot access REDCap
				global $homepage_contact_email, $homepage_contact;
				$objHtmlPage = new HtmlPage();
				$objHtmlPage->addStylesheet("style.css", 'screen,print');
				$objHtmlPage->addStylesheet("home.css", 'screen,print');
				$objHtmlPage->PrintHeader();
				$user_firstlast = ($user_firstname == "" && $user_lastname == "") ? "" : " (<b>$user_firstname $user_lastname</b>)";
				print  "<div class='red' style='margin:40px 0 20px;padding:20px;'>
							{$lang['config_functions_75']} \"<b>$userid</b>\"{$user_firstlast}{$lang['period']} 
							{$lang['config_functions_76']} <a href='mailto:$homepage_contact_email'>$homepage_contact</a>{$lang['period']}
						</div>
						<button onclick=\"window.location.href='".APP_PATH_WEBROOT_FULL."index.php?logout=1';\">Go back</button>";
				$objHtmlPage->PrintFooter();
				exit;
			}
			
		}
		
		//Define user variables
		defined("USERID") or define("USERID", $userid);
		define("SUPER_USER", $super_user);
		$GLOBALS['userid'] = $userid;
		$GLOBALS['super_user'] = $super_user;
		$GLOBALS['user_email'] = $user_email;
		$GLOBALS['user_email2'] = $user_email2;
		$GLOBALS['user_email3'] = $user_email3;
		$GLOBALS['user_firstname'] = $user_firstname;
		$GLOBALS['user_lastname'] = $user_lastname;
		$GLOBALS['user_firstactivity'] = $user_firstactivity;
		$GLOBALS['allow_create_db'] = $allow_create_db;
			
		// Stop here if user is on a non-project level page (e.g. My Projects, Home, Control Center)
		if (!isset($_GET['pnid']) && !isset($_GET['pid'])) return true;
		
		// PROJECT-LEVEL AUTHENTICATION: Determine the user's rights for each page/module
		return check_user_rights(APP_NAME);
		
	}
	
		
	/**
	 * RESET USER'S PASSWORD TO A RANDOM TEMPORARY VALUE AND RETURN THE PASSWORD THAT WAS SET
	 */
	static function resetPassword($username,$loggingDescription="Reset user password")
	{
		// Set new temp password valkue
		$pass = substr(md5(rand()), 0, 6);
		// Update table with new password
		$sql = "update redcap_auth set password = '" . md5($pass) . "', temp_pwd = 1 where username = '" . prep($username) . "'";
		$q = db_query($sql);
		if ($q) {
			// For logging purposes, make sure we've got a username to attribute the logging to
			defined("USERID") or define("USERID", $username);
			// Logging
			log_event($sql,"redcap_auth","MANAGE",$username,"username = '" . prep($username) . "'",$loggingDescription);
			// Return password
			return $pass;
		}
		// Return false if failed
		return false;
	}
	
		
	/**
	 * CHECK IF USER IS LOGGED IN
	 */
	static function checkLogin($action="",$auth_meth) 
	{
		global $mysqldsn, $ldapdsn, $autologout_timer, $isMobileDevice, $logout_fail_limit, $logout_fail_window, 
			   $lang, $project_contact_email, $project_contact_name;
		
		// Start the session
		if (!session_id()) @session_start();
		
		// Check to make sure user hasn't had a failed login X times in Y minutes (based upon Control Center values)
		if (isset($_POST['redcap_login_a38us_09i85']) && $auth_meth != "none" && $logout_fail_limit != "0" && $logout_fail_window != "0")
		{
			// Get window of time to query
			$YminAgo = date("Y-m-d H:i:s", mktime(date("H"),date("i")-$logout_fail_window,date("s"),date("m"),date("d"),date("Y")));
			// Get count of failed logins in window of time
			$sql = "select count(1) from redcap_log_view where ts >= '$YminAgo' and user = '" . prep($_POST['username']) . "' and event = 'LOGIN_FAIL'
					and (select log_view_id from redcap_log_view where user = '" . prep($_POST['username']) . "' and event = 'LOGIN_FAIL'    order by log_view_id desc limit 1) 
					  > (select log_view_id from redcap_log_view where user = '" . prep($_POST['username']) . "' and event = 'LOGIN_SUCCESS' order by log_view_id desc limit 1)";
			$failedLogins = db_result(db_query($sql), 0);
			// If failed logins in window of time exceeds set limit
			if ($failedLogins >= $logout_fail_limit)
			{
				// Give user lock-out message
				$objHtmlPage = new HtmlPage();
				$objHtmlPage->addExternalJS(APP_PATH_JS . "base.js");
				$objHtmlPage->addStylesheet("smoothness/jquery-ui-".JQUERYUI_VERSION.".custom.css", 'screen,print');
				$objHtmlPage->addStylesheet("style.css", 'screen,print');
				$objHtmlPage->addStylesheet("home.css", 'screen,print');
				$objHtmlPage->PrintHeader();			
				print  "<div class='red' style='margin:60px 0;'>
							<b>{$lang['global_05']}</b><br><br>
							{$lang['config_functions_69']} (<b>$logout_fail_window {$lang['config_functions_72']}</b>){$lang['period']} 
							{$lang['config_functions_70']}<br><br>
							{$lang['config_functions_71']}
							<a href='mailto:$project_contact_email'>$project_contact_name</a>{$lang['period']}
						</div>";			
				$objHtmlPage->PrintFooter();
				exit;
			}
		}
			
		// Set time for auto-logout
		$auto_logout_minutes = ($autologout_timer == "") ? 0 : $autologout_timer;
		
		// Default
		$dsn = array();
		
		// LDAP with Table-based roll-over
		if ($auth_meth == "ldap_table") 
		{
			$dsn[] = array('type'=>'DB',   'dsnstuff'=>$mysqldsn);
			if (is_array(end($ldapdsn))) {
				// Loop through all LDAP configs and add
				foreach ($ldapdsn as $this_ldapdsn) {
					$dsn[] = array('type'=>'LDAP', 'dsnstuff'=>$this_ldapdsn);
				}
			} else {
				// Add single LDAP config
				$dsn[] = array('type'=>'LDAP', 'dsnstuff'=>$ldapdsn);		
			}
		}
		// LDAP
		elseif ($auth_meth == "ldap") 
		{
			if (is_array(end($ldapdsn))) {
				// Loop through all LDAP configs and add
				foreach ($ldapdsn as $this_ldapdsn) {
					$dsn[] = array('type'=>'LDAP', 'dsnstuff'=>$this_ldapdsn);
				}
			} else {
				// Add single LDAP config
				$dsn[] = array('type'=>'LDAP', 'dsnstuff'=>$ldapdsn);		
			}
		}
		// Table-based
		elseif ($auth_meth == "table") 
		{
			$dsn[] = array('type'=>'DB',   'dsnstuff'=>$mysqldsn);
		}
		
		// Default
		$GLOBALS['authFail'] = 0;
			
		//if ldap and table authentication Loop through the available servers & authentication methods
		foreach ($dsn as $key=>$dsnvalue) 
		{		
			if (isset($a)) unset($a);
			$GLOBALS['authFail'] = 1;
			$a = new Auth($dsnvalue['type'], $dsnvalue['dsnstuff'], "loginFunction");
			
			// Expiration settings
			$oneDay = 86400; // in seconds
			$auto_logout_minutes = ($auto_logout_minutes == 0) ? ($oneDay/60) : $auto_logout_minutes; // if 0, set to 24 hour logout
			
			$a->setExpire($oneDay);
			$a->setIdle(round($auto_logout_minutes * 60));
			
			// DEBUGGING
			// print "<br>Seconds until it would have logged you out: ".($a->idle+$a->session['idle']-time());
			// print "<br> Idle time: ".(time()-$a->session['idle']);
			// print "<br> 2-min warning at: ".date("H:i:s", mktime(date("H"),date("i"),date("s")+$a->idle-120,date("m"),date("d"),date("Y")));
			// print "<div style='text-align:left;'>";print_array($dsnvalue['dsnstuff']);print "</div>";
			
			$a->start();  	// If authentication fails the loginFunction is called and since
							// the global variable $authFail is true the loginFunction will
							// return control to this point again 
			if ($a->getAuth()) 
			{
				//print "<div style='text-align:left;'>";print_array($a);print "</div>";
				$_SESSION['username'] = $a->getUsername();
				// Make sure password is not left blank AND check for logout
				if ($action == "logout" || (isset($_POST['redcap_login_a38us_09i85']) && isset($_POST['password']) && trim($_POST['password']) == ""))
				{
					$GLOBALS['authFail'] = 0;
					$a->logout();
					$a->start();
				} 
				// Log the successful login
				elseif (isset($_POST['redcap_login_a38us_09i85'])) 
				{			
					addPageView("LOGIN_SUCCESS", $_SESSION['username']);
				}
				return 1;
			} else {
				//print  "<div class='red' style='max-width:100%;width:100%;font-weight:bold;'>FAIL</div>";
			}
		}
			
		// The user couldn't be authenticated on any server so set global variable $authFail to false
		// and let the loginFunction be called to display the login form 
		if (!$isMobileDevice) // don't show for mobile devices because it prevents reload of login form
		{
			print   RCView::div(array('class'=>'red','style'=>'max-width:100%;width:100%;font-weight:bold;'),
						RCView::img(array('src'=>'exclamation.png','class'=>'imgfix')) .
						"{$lang['global_01']}{$lang['colon']} {$lang['config_functions_49']}"
					);
		}
		//Log the failed login
		addPageView("LOGIN_FAIL",$_POST['username']);
		
		$GLOBALS['authFail'] = 0;
		$a->start();
		return 1;	
	}


	// If logout variable exists in URL, destroy the session
	// and reset the $userid variable to remove all user context
	static function checkLogout()
	{
		global $auth_meth;
		if (isset($_GET['logout']) && $_GET['logout']) 
		{			
			// Log the logout
			addPageView("LOGOUT", $_SESSION['username']);
			// Destroy session and erase userid
			$_SESSION = array();
			session_unset();
			session_destroy();
			// Default value (remove 'logout' from query string, if exists)
			$logoutRedirect = str_replace(array("logout=1&","&logout=1","logout=1","&amp;"), array("","","","&"), $_SERVER['REQUEST_URI']);
			// If using Shibboleth, redirect to Shibboleth logout page
			if ($auth_meth == 'shibboleth' && strlen($GLOBALS['shibboleth_logout']) > 0) {
				$logoutRedirect = $GLOBALS['shibboleth_logout'];
			}
			// C4 cookie-based authentication
			elseif ($auth_meth == 'c4') {
				// Redirect to C4 logout page
				$logoutRedirect = "https://www.ctsacentral.org/authenticated-user-portal/logout";
			}
			redirect($logoutRedirect);
		}
	}
	

	/**
	 * SEARCH REDCAP_AUTH TABLE FOR USER (return boolean)
	 */
	public static function isTableUser($user) 
	{
		$q = db_query("select 1 from redcap_auth where username = '".prep($user)."' limit 1");
		return ($q && db_num_rows($q) > 0);
	}
	
}