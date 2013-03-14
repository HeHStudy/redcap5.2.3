<?php

// Config for non-project pages
require_once dirname(dirname(__FILE__)) . "/Config/init_global.php";

//If user is not a super user, go back to Home page
if (!$super_user) redirect(APP_PATH_WEBROOT);

include APP_PATH_WEBTOOLS . "ldap" . DS . "ldap_config.php";

// Display the header
$HtmlPage = new HtmlPage();
$HtmlPage->PrintHeaderExt();

?>
<div style="font-weight:bold;margin:10px 0 20px;color:#800000;font-size:20px;">
	<div style="float:left;">LDAP Troubleshooter for REDCap</div>
	<div style="float:right;">
		<button class="jqbutton" style="color:#000066;" onclick="window.location.href='<?php echo PAGE_FULL ?>';">Reload page</button>
		<button class="jqbutton" style="color:#800000;" onclick="window.location.href='<?php echo APP_PATH_WEBROOT_FULL ?>';">Go back to REDCap</button>
	</div>
	<div class="clear"> </div>
</div>
<?php

// Require some Pear modules
$pearModules = array("Auth.php", "Log.php", "Log/observer.php");
foreach ($pearModules as $module) {
	if (!include_once $module) {
		// Give error alert that couldn't load
		exit("<b>ERROR:</b> Could not find the PHP Pear file named \"<b>$module</b>\"! 
			  This file is required in order to run the LDAP Connection Troubleshooter. 
			  You may need to install that module. You can download Pear modules at
			  <a href='http://pear.php.net' style='text-decoration:underline;'>http://pear.php.net</a>.
			  Once you have installed this module on your REDCap web server, restart your server and then load this page again.");
	}
}

// Callback function to display login form
function loginFunctionLdap($username = null, $status = null, &$auth = null)
{
	echo   "<p>
				This page can be used to help set up and troubleshoot an LDAP configuration in order to utilize LDAP authentication in REDCap.
				Use the login form below to log in with an LDAP username/password, and it will attempt to bind and authenticate
				with your LDAP server using those credentials. It will provide helpful logging and error messages during this process to
				help you troubleshoot any issues that may occur if it does not successfully authenticate.
				The LDAP configuration in your ldap_config.php file (displayed below on the right) will be used in this process.
				If you are unable to authenticate with your current LDAP config, you may try adjusting the settings in your 
				ldap_config.php file until it works. For some LDAP config examples, see the 
				<a target='_blank' href='https://iwg.devguard.com/trac/redcap/wiki/LdapSetup' style='text-decoration:underline;'>LDAP setup page</a> on the REDCap wiki.
			</p>";
	echo "<form style=\"margin:20px 0;\" method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">";
	echo "Username: <input type=\"text\" autocomplete=\"off\" name=\"username\" value=\"".(isset($_POST['username']) ? cleanHtml2($_POST['username']) : "")."\"><br/>";
	echo "Password:&nbsp; <input type=\"password\" autocomplete=\"off\" name=\"password\" value=\"".(isset($_POST['password']) ? cleanHtml2($_POST['password']) : "")."\"><br/>";
	echo "<input type=\"submit\" value=\"Log In\" class=\"jqbuttonmed\">";
	echo "</form>";
}

class Auth_Log_Observer extends Log_observer {

	var $messages = array();

	function notify($event) {

		$this->messages[] = $event;

	}

}


// Check if ldapdsn is daily-chained
$isDaisyChained = (is_array(end($ldapdsn)));
$daisyChainMsg = "";
if ($isDaisyChained) {
	$dsn = array_shift($ldapdsn);
	if ($_SERVER['REQUEST_METHOD'] != 'POST') {
		$daisyChainMsg =   "<div class='yellow' style='font-size:11px;'><b>NOTE:</b> This page can only use the first LDAP configuration 
							in a daisy-chained LDAP configuration, which you appear to be have set up in your ldap_config.php file. 
							Thus if you have multiple LDAP configurations daisy-chained together, they will have to each be 
							attempted one at a time.</div>";
	}
} else {
	$dsn = $ldapdsn;
}



// Add flag to enable logging
$dsn['enableLogging'] = true;

$a = new Auth("LDAP", $dsn, "loginFunctionLdap");
$a->setSessionName("ldap_troubleshoot");

$infoObserver = new Auth_Log_Observer(AUTH_LOG_INFO);

$a->attachLogObserver($infoObserver);

$debugObserver = new Auth_Log_Observer(AUTH_LOG_DEBUG);

$a->attachLogObserver($debugObserver);

$a->start();

if ($a->checkAuth()) {
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		print "<div class='green' style='margin:10px 0;font-weight:bold;font-family:verdana;'>Authentication Successful!</div>";
	} else {
		// Destroy session and erase userid
		$_SESSION = array();
		session_unset();
		session_destroy();
		// Redirect back to same page
		redirect(PAGE_FULL);
	}	
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
	print "<div class='red' style='margin:10px 0;font-weight:bold;font-family:verdana;'>Authentication Failed!</div>";
}

echo "$daisyChainMsg<hr size=1>";
print "<div style='float:left;width:350px;'>";
print '<h3 style="margin-top:20px;color:#800000;">Logging Output:</h3>'
	.'<b>AUTH_LOG_INFO level messages:</b><br/>';
foreach ($infoObserver->messages as $event) {
	print $event['priority'].': '.$event['message'].'<br/>';
}
print '<br/>'
.'<b>AUTH_LOG_DEBUG level messages:</b><br/>';
foreach ($debugObserver->messages as $event) {
	print $event['priority'].': '.$event['message'].'<br/>';
}
print "</div>";


print "<div style='float:right;width:400px;border-left:1px solid #aaa;padding-left:10px;'>";
print '<h3 style="margin-top:20px;color:#800000;">LDAP Configuration being used:</h3>';
print '<div style="">Using the <b>ldapdsn</b> array from the LDAP config file at<br><b>'.APP_PATH_WEBTOOLS . "ldap" . DS . 'ldap_config.php</b></div>';
print_array($dsn);
print "</div>";
print "<div class='clear'> </div>";

// Display the footer
$HtmlPage->PrintFooterExt();
