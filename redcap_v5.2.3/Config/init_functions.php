<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


/** 
 * CALL THE BOOTSTRAPPER FILE
 */
require_once dirname(__FILE__) . "/bootstrap.php";


/**
 * AUTOLOAD CLASSES
 * Function will autoload the proper class file when the class is called
 */
function __autoload($className)
{
	// Get the path where the classes are located
	$classPath = dirname(dirname(__FILE__)) . DS . "Classes" . DS;
	// Do include_once and give error message if fails
	if (!include_once $classPath . $className . ".php")
	{
		exit("ERROR: The class \"$className\" could not be found in $classPath");
	}
}


/**
 * SESSION HANDLING: DATABASE SESSION STORAGE
 * Adjust PHP session configuration to store sessions in database instead of as file on web server
 */
function on_session_start() { }
function on_session_end() 	{ }
function on_session_read($key) 
{
	// Force session_id to only have 32 characters (for compatibility issues)
	$key = substr($key, 0, 32);
    $stmt = "SELECT session_data FROM redcap_sessions WHERE session_id = '" . prep($key). "' AND session_expiration > '" . NOW . "'";
	$sth = db_query($stmt);
    return ($sth ? db_result($sth, 0) : $sth);
}
function on_session_write($key, $val) 
{
	// Force session_id to only have 32 characters (for compatibility issues)
	$key = substr($key, 0, 32);
	if (session_name() == "survey") {
		// For surveys, set expiration time as 1 day (i.e. arbitrary long time)
		$expiration = date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s"),date("m"),date("d")+1,date("Y")));
	} else {
		// For non-survey pages (all else), set expiration time using value defined on System Config page
		global $autologout_timer;
		$expiration = date("Y-m-d H:i:s", mktime(date("H"),date("i")+$autologout_timer,date("s"),date("m"),date("d"),date("Y")));
	}
	// First we try to insert, if that doesn't succeed, it means session is already in the table and we try to update 
	$sql = "INSERT INTO redcap_sessions VALUES ('$key', '" . prep($val) . "', '$expiration') 
			ON DUPLICATE KEY UPDATE session_data = '" . prep($val) . "', session_expiration = '$expiration'";
    db_query($sql);
}
function on_session_destroy($key) 
{
	// Force session_id to only have 32 characters (for compatibility issues)
	$key = substr($key, 0, 32);
    db_query("DELETE FROM redcap_sessions WHERE session_id = '$key'");
}
function on_session_gc($max_lifetime) 
{
	// Delete all sessions more than 1 day old, which is the session expiration time used by surveys (ignore the system setting $max_lifetime)
	$max_session_time = date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s"),date("m"),date("d")-1,date("Y")));
    db_query("DELETE FROM redcap_sessions WHERE session_expiration < '$max_session_time'");
}


/**
 * ERROR HANDLING
 */
function myErrorHandler($code, $message, $file, $line) 
{
	global $lang;	 
	$errorRendered = false;
	
	// Fatal error is code=1
	if ($code == 1)
	{
		// If a Vanderbilt user, send email to admin to troubleshoot (exclude the PDF download page and Data Quality execute page)
		if (SERVER_NAME == 'redcap.vanderbilt.edu' && PAGE != 'PDF/index.php' && PAGE != 'DataQuality/execute_ajax.php')
		{
			require_once dirname(dirname(__FILE__)) . "/Classes/Message.php";	
			$emailContents =   "<html><body style=\"font-family:Arial;font-size:10pt;\">
								PHP Crashed on <b>".SERVER_NAME."</b> at <b>".NOW."</b>!<br><br>
								<b>Page:</b> https://".SERVER_NAME.$_SERVER['REQUEST_URI']."<br>
								<b>User:</b> ".USERID."<br><br>
								<b>{$lang['config_functions_02']}</b> $message <br>
								<b>{$lang['config_functions_03']}</b> $file <br>
								<b>{$lang['config_functions_04']}</b> $line <br>
								</body></html>";
			$email = new Message ();
			$email->setTo('redcap@vanderbilt.edu'); 
			$email->setFrom('redcap@vanderbilt.edu');
			$email->setSubject('[REDCap] PHP Crashed!');
			$email->setBody($emailContents);
			$email->send();
			//print "<div style='padding:10px;border:1px solid #ddd;width:700px;'>$emailContents</div>";
		}
		
		// Custom message for memory overload or script timeout (all pages)
		if (defined('PAGE') && PAGE == "DataQuality/execute_ajax.php")
		{
			// Get current rule_id and the ones following
			list ($rule_id, $rule_ids) = explode(",", $_POST['rule_ids'], 2);
			// Set error message
			if (strpos($message, "Maximum execution time of") !== false) {
				// Script timeout error
				$msg = "<div id='results_table_{$rule_id}'>
							<p class='red' style='max-width:500px;'>
								<b>{$lang['dataqueries_105']}</b> {$lang['dataqueries_106']} 
								".ini_get('max_execution_time')." {$lang['dataqueries_107']}
							</p>";
				// Set main error msg seen in table
				$dqErrMsg = $lang['dataqueries_108'];
			} else {
				// Memory overload error
				$msg = "<div id='results_table_{$rule_id}'>
							<p class='red' style='max-width:500px;'>
								<b>{$lang['global_01']}{$lang['colon']}</b> {$lang['dataqueries_32']} <b>{$_GET['error_rule_name']}</b> {$lang['dataqueries_33']}
								" . (is_numeric($rule_id) ? $lang['dataqueries_34'] : $lang['dataqueries_96']) . " 
							</p>";
				// Set main error msg seen in table
				$dqErrMsg = $lang['global_01'];
			}
			// Provide super users with further context about error
			if (defined('SUPER_USER') && SUPER_USER) {
				$msg .=	"<p class='red' style='max-width:600px;'>
							<b>{$lang['config_functions_01']}</b><br><br>
							<b>{$lang['config_functions_02']}</b> $message<br>
							<b>{$lang['config_functions_03']}</b> $file<br>
							<b>{$lang['config_functions_04']}</b> $line<br>
						 </p>";
			}
			$msg .=	"</div>";
			// Send back JSON
			print '{"rule_id":"' . $rule_id . '",'
				. '"next_rule_ids":"' . $rule_ids . '",'
				. '"discrepancies":"1",'
				. '"discrepancies_formatted":"<span style=\"font-size:12px;\">'.$dqErrMsg.'</span>",'
				. '"dag_discrepancies":[],'
				. '"title":"' . cleanJson($_GET['error_rule_name']) . '",'
				. '"payload":"' . cleanJson($msg)  .'"}';
			exit;
		}
		
		// Render error message to super users only OR user is on Install page and can't get it to load
		if ((defined('SUPER_USER') && SUPER_USER) || (defined('PAGE') && PAGE == "install.php"))
		{
			if (isset($lang) && !empty($lang)) {
				$err1 = $lang['config_functions_01'];
				$err2 = $lang['config_functions_02'];
				$err3 = $lang['config_functions_03'];
				$err4 = $lang['config_functions_04'];
			} else {
				$err1 = "REDCap crashed due to an unexpected PHP fatal error!";
				$err2 = "Error message:";
				$err3 = "File:";
				$err4 = "Line:";
			}
			?>
			<div class="red" style="margin:20px 0px;max-width:700px;">
				<b><?php echo $err1 ?></b><br><br>
				<b><?php echo $err2 ?></b> <?php echo $message ?><br>
				<b><?php echo $err3 ?></b> <?php echo $file ?><br>
				<b><?php echo $err4 ?></b> <?php echo $line ?><br>
			</div>
			<?php
			$errorRendered = true;
		}
		
		// Catch any pages that timeout
		if (strpos($message, "Maximum execution time of") !== false) 
		{
			?>
			<div class="red" style="max-width:700px;">
				<b><?php echo $lang['dataqueries_105'] ?></b> 
				<?php echo $lang['dataqueries_106'] . " " . ini_get('max_execution_time') . " " . $lang['dataqueries_107'] ?>
			</div>
			<?php
			exit;
		}
		
		// Custom message for memory overload (all pages)
		if (defined('PAGE') && strpos($message, "Allowed memory size of") !== false)
		{
			// Specific message for Data Import Tool
			if (PAGE == "DataImport/index.php")
			{
				?>
				<div class="red" style="max-width:700px;">
					<b><?php echo $lang['global_01'] ?>: <?php echo $lang['config_functions_05'] ?></b><br>
					<?php echo $lang['config_functions_06'] ?>
				</div>
				<?php
			}
			// Specific message for PDF export
			elseif (PAGE == "PDF/index.php")
			{
				?>
				<div class="red" style="max-width:700px;">
					<b><?php echo $lang['global_01'] ?>: <?php echo $lang['config_functions_80'] ?></b><br>
					<?php echo $lang['config_functions_81'] ?>
				</div>
				<?php
			}
			// Generic message for "out of memory" error
			else
			{
				?>
				<div class="red" style="max-width:700px;">
					<b>ERROR: REDCap ran out of memory!</b><br>
					The current web page has hit the maximum allowed memory limit (<?php echo ini_get('memory_limit') ?>B).
					<?php if (defined('SUPER_USER') && SUPER_USER) { ?>
						Super user message: You might think about increasing your web server memory used by PHP by 
						changing the value of "memory_limit" in your server's PHP.INI file.
						(Don't forget to reboot the web server after making this change.)
					<?php } else { ?>
						Please contact a REDCap administrator to inform them of this issue.
					<?php } ?>
				</div>
				<?php
			}
			$errorRendered = true;
		}
		// Give general error message to normal user
		elseif (!$errorRendered)
		{		
			?>
			<div class="red" style="margin:20px 0px;max-width:700px;">
				<b><?php echo $lang['config_functions_07'] ?></b><br><br>
				<?php echo $lang['config_functions_08'] ?>
			</div>
			<?php
		
		}
	}
}
function fatalErrorShutdownHandler()
{
	// Must be on PHP 5.2.0 to do this
	if (version_compare(PHP_VERSION, '5.2.0', '<')) return;
	// Get last error
	$last_error = @error_get_last();
	if ($last_error['type'] === E_ERROR) {
		// fatal error
		myErrorHandler(E_ERROR, $last_error['message'], $last_error['file'], $last_error['line']);
	}
}

//Connect to the MySQL project where the REDCap tables are kept
function db_connect()
{
	global $lang, $rc_connection;
	// For install page, do not report errors here (because messes up installation workflow)
	$reportErrors = !(basename($_SERVER['PHP_SELF']) == 'install.php' 
					|| (basename($_SERVER['PHP_SELF']) == 'index.php' && basename(dirname($_SERVER['PHP_SELF'])) == 'Test'));	
	$db_error_msg = "";
	$db_conn_file = dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'database.php';	
	include $db_conn_file;
	if (!isset($hostname) || !isset($db) || !isset($username) || !isset($password)) {
		$db_error_msg = "One or more of your database connection values (\$hostname, \$db, \$username, \$password) 
						 could not be found in your database connection file [$db_conn_file]. Please make sure all four variables are
						 defined with a correct value in that file.";
	}
	// First, check that MySQLi extension is installed
	if (!function_exists('mysqli_connect')) {
		exit("<p style='margin:30px;width:700px;'><b>ERROR: MySQLi extension in PHP is not installed!</b><br>
			  REDCap 5.1.0 and later versions require the MySQLi extension in PHP. You will need to first install PHP's MySQLi 
			  extension on your webserver before you can continue further. 
			  <a target='_blank' href='http://php.net/manual/en/mysqli.setup.php'>Download and install the MySQLi extension</a><br><br>
			  <b>Why has this changed from previous REDCap versions?</b><br>
			  PHP 5.5 and later versions no longer support the MySQL extension, which was used in prior versions of REDCap, thus
			  REDCap now utilizes the MySQLi extension instead.
			  </p>");	
	}
	// Connect to MySQL
	$rc_connection = mysqli_connect($hostname, $username, $password, $db);
    if (!$rc_connection) {
		$db_error_msg = "Your REDCap database connection file [$db_conn_file] could not connect to the database server. 
						 Please check the connection values in that file (\$hostname, \$db, \$username, \$password) 
						 because they may be incorrect."; 
	}
	// If there was a db connection error, then display it
	if ($reportErrors && $db_error_msg != "")
	{
		?>
		<html><body style="font-family:arial;font-size:12px;padding:20px;">
			<div style="font: normal 12px Verdana, Arial;padding:10px;border: 1px solid red;color: #800000;max-width: 600px;background: #FFE1E1;">
				<div style="font-weight:bold;font-size:15px;padding-bottom:5px;">
					CRITICAL ERROR: REDCap server is offline!
				</div>
				<div>
					For unknown reasons, REDCap cannot communicate with its database server, which may be offline. Please contact your 
					local REDCap administrator to inform them of this issue immediately. If you are a REDCap administrator, then please see this 
					<a href="javascript:;" style="color:#000066;" onclick="document.getElementById('db_error_msg').style.display='block';">additional information</a>.
					We are sorry for any inconvenience.
				</div>
				<div id="db_error_msg" style="display:none;color:#333;background:#fff;padding:5px 10px 10px;margin:20px 0;border:1px solid #bbb;">
					<b>Message for REDCap administrators:</b><br/><?php echo $db_error_msg ?>
				</div>
			</div>
		</body></html>
		<?php
		exit;
	}
	// Get the SALT, which is institutional-unique alphanumeric value, and is found in the Control Center db connection file. 
	// It is the first part of the total salt used for Date Shifting and (eventually) Encryption at Rest
	if ($reportErrors && (!isset($salt) || (isset($salt) && empty($salt)))) 
	{
		// Warn user that the SALT was not defined in the connection file and give them new salt		
		exit(  "<div style='font-family:Verdana;font-size:12px;line-height:1.5em;padding:25px;'>
				<b>ERROR:</b><br>
				REDCap could not find the variable <b>\$salt</b> defined in [<font color='#800000'>$db_conn_file</font>].<br><br> 
				Please open the file for editing and add the following code after your database connection variables: 
				<b>\$salt = \"".substr(md5(rand()), 0, 10)."\";</b>
				</div>");
	}
	// Set global variables
	$GLOBALS['hostname'] = $hostname;
	$GLOBALS['username'] = $username;
	$GLOBALS['password'] = $password;
	$GLOBALS['db'] 		 = $db;
	$GLOBALS['salt'] 	 = $salt;
	// DTS connection variables
	$GLOBALS['dtsHostname'] = $dtsHostname;
	$GLOBALS['dtsUsername'] = $dtsUsername;
	$GLOBALS['dtsPassword'] = $dtsPassword;
	$GLOBALS['dtsDb']		= $dtsDb;

}

## ABSTRACTED DATABASE FUNCTIONS
## Replaced mysql_* functions with db_* functions, which are merely abstracted MySQLi functions
// DB: Query the database
function db_query($sql, $conn = null) {
	global $rc_connection;
	// If link identifier is explicitly specified, then use it rather than the default $rc_connection.
	if ($conn == null) $conn = $rc_connection;
	// Return false if failed. Return object if successful.
	return mysqli_query($conn, $sql);
}
// DB: fetch_assoc
function db_fetch_assoc($q) {
	return mysqli_fetch_assoc($q);
}
// DB: fetch_array
function db_fetch_array($q, $resulttype = MYSQLI_BOTH) {
	return mysqli_fetch_array($q, $resulttype);
}
// DB: num_rows
function db_num_rows($q) {
	return mysqli_num_rows($q);
}
// DB: affected_rows
function db_affected_rows() {
	global $rc_connection;
	return mysqli_affected_rows($rc_connection);
}
// insert_id
function db_insert_id() {
	global $rc_connection;
	return mysqli_insert_id($rc_connection);
}
// DB: free_result
function db_free_result($q) {
	return mysqli_free_result($q);
}
// DB: real_escape_string
function db_real_escape_string($str) {
	global $rc_connection;
	return mysqli_real_escape_string($rc_connection, $str);
}
// DB: error
function db_error() {
	global $rc_connection;
	return mysqli_error($rc_connection);
}
// DB: errno
function db_errno() {
	global $rc_connection;
	return mysqli_errno($rc_connection);
}
// DB: field_name
function db_field_name($q, $field_number) {
	$ob = mysqli_fetch_field_direct($q, $field_number);
	$field_name = $ob->name;
	unset($ob);
	return $field_name;
}
// DB: num_fields
function db_num_fields($q) {
	return mysqli_num_fields($q);
}
// DB: fetch_object
function db_fetch_object($q) {
	return mysqli_fetch_object($q);
}
// DB: result
function db_result($q, $pos, $field='') {
    $i = 0;
	// If didn't specify field, assume the field in first position
	if ($field == '') $field = db_field_name($q, 0);
	// Set pointer to beginning (0)
    mysqli_data_seek($q, 0);
	// Loop through fields till we get to the correct field
    while ($row = mysqli_fetch_array($q, MYSQLI_BOTH)) {
        if ($i == $pos) {
			// Set pointer to next field before exiting
			mysqli_data_seek($q, $pos+1);
			// Return the value for our field
			return $row[$field];
		}
        $i++;
    }
    return false;
}


/** 
 * MOBILE DEVICE DETECTION
 */
function mobile_device_detect($iphone=true,$ipad=true,$android=true,$opera=true,$blackberry=true,$palm=true,$windows=true,$mobileredirect=false,$desktopredirect=false){

  $mobile_browser   = false; // set mobile browser as false till we can prove otherwise
  $user_agent       = $_SERVER['HTTP_USER_AGENT']; // get the user agent value - this should be cleaned to ensure no nefarious input gets executed
  $accept           = $_SERVER['HTTP_ACCEPT']; // get the content accept value - this should be cleaned to ensure no nefarious input gets executed

  switch(true){ // using a switch against the following statements which could return true is more efficient than the previous method of using if statements

    case (preg_match('/ipad/i',$user_agent)); // we find the word ipad in the user agent
      $mobile_browser = $ipad; // mobile browser is either true or false depending on the setting of ipad when calling the function
      $status = 'Apple iPad';
      if(substr($ipad,0,4)=='http'){ // does the value of ipad resemble a url
        $mobileredirect = $ipad; // set the mobile redirect url to the url value stored in the ipad value
      } // ends the if for ipad being a url
    break; // break out and skip the rest if we've had a match on the ipad // this goes before the iphone to catch it else it would return on the iphone instead

    case (preg_match('/ipod/i',$user_agent)||preg_match('/iphone/i',$user_agent)); // we find the words iphone or ipod in the user agent
      $mobile_browser = $iphone; // mobile browser is either true or false depending on the setting of iphone when calling the function
      $status = 'Apple';
      if(substr($iphone,0,4)=='http'){ // does the value of iphone resemble a url
        $mobileredirect = $iphone; // set the mobile redirect url to the url value stored in the iphone value
      } // ends the if for iphone being a url
    break; // break out and skip the rest if we've had a match on the iphone or ipod

    case (preg_match('/android/i',$user_agent));  // we find android in the user agent
      $mobile_browser = $android; // mobile browser is either true or false depending on the setting of android when calling the function
      $status = 'Android';
      if(substr($android,0,4)=='http'){ // does the value of android resemble a url
        $mobileredirect = $android; // set the mobile redirect url to the url value stored in the android value
      } // ends the if for android being a url
    break; // break out and skip the rest if we've had a match on android

    case (preg_match('/opera mini/i',$user_agent)); // we find opera mini in the user agent
      $mobile_browser = $opera; // mobile browser is either true or false depending on the setting of opera when calling the function
      $status = 'Opera';
      if(substr($opera,0,4)=='http'){ // does the value of opera resemble a rul
        $mobileredirect = $opera; // set the mobile redirect url to the url value stored in the opera value
      } // ends the if for opera being a url 
    break; // break out and skip the rest if we've had a match on opera

    case (preg_match('/blackberry/i',$user_agent)); // we find blackberry in the user agent
      $mobile_browser = $blackberry; // mobile browser is either true or false depending on the setting of blackberry when calling the function
      $status = 'Blackberry';
      if(substr($blackberry,0,4)=='http'){ // does the value of blackberry resemble a rul
        $mobileredirect = $blackberry; // set the mobile redirect url to the url value stored in the blackberry value
      } // ends the if for blackberry being a url 
    break; // break out and skip the rest if we've had a match on blackberry

    case (preg_match('/(pre\/|palm os|palm|hiptop|avantgo|plucker|xiino|blazer|elaine)/i',$user_agent)); // we find palm os in the user agent - the i at the end makes it case insensitive
      $mobile_browser = $palm; // mobile browser is either true or false depending on the setting of palm when calling the function
      $status = 'Palm';
      if(substr($palm,0,4)=='http'){ // does the value of palm resemble a rul
        $mobileredirect = $palm; // set the mobile redirect url to the url value stored in the palm value
      } // ends the if for palm being a url 
    break; // break out and skip the rest if we've had a match on palm os

    case (preg_match('/(iris|3g_t|windows ce|opera mobi|windows ce; smartphone;|windows ce; iemobile)/i',$user_agent)); // we find windows mobile in the user agent - the i at the end makes it case insensitive
      $mobile_browser = $windows; // mobile browser is either true or false depending on the setting of windows when calling the function
      $status = 'Windows Smartphone';
      if(substr($windows,0,4)=='http'){ // does the value of windows resemble a rul
        $mobileredirect = $windows; // set the mobile redirect url to the url value stored in the windows value
      } // ends the if for windows being a url 
    break; // break out and skip the rest if we've had a match on windows

    case (preg_match('/(mini 9.5|vx1000|lge |m800|e860|u940|ux840|compal|wireless| mobi|ahong|lg380|lgku|lgu900|lg210|lg47|lg920|lg840|lg370|sam-r|mg50|s55|g83|t66|vx400|mk99|d615|d763|el370|sl900|mp500|samu3|samu4|vx10|xda_|samu5|samu6|samu7|samu9|a615|b832|m881|s920|n210|s700|c-810|_h797|mob-x|sk16d|848b|mowser|s580|r800|471x|v120|rim8|c500foma:|160x|x160|480x|x640|t503|w839|i250|sprint|w398samr810|m5252|c7100|mt126|x225|s5330|s820|htil-g1|fly v71|s302|-x113|novarra|k610i|-three|8325rc|8352rc|sanyo|vx54|c888|nx250|n120|mtk |c5588|s710|t880|c5005|i;458x|p404i|s210|c5100|teleca|s940|c500|s590|foma|samsu|vx8|vx9|a1000|_mms|myx|a700|gu1100|bc831|e300|ems100|me701|me702m-three|sd588|s800|8325rc|ac831|mw200|brew |d88|htc\/|htc_touch|355x|m50|km100|d736|p-9521|telco|sl74|ktouch|m4u\/|me702|8325rc|kddi|phone|lg |sonyericsson|samsung|240x|x320|vx10|nokia|sony cmd|motorola|up.browser|up.link|mmp|symbian|smartphone|midp|wap|vodafone|o2|pocket|kindle|mobile|psp|treo)/i',$user_agent)); // check if any of the values listed create a match on the user agent - these are some of the most common terms used in agents to identify them as being mobile devices - the i at the end makes it case insensitive
      $mobile_browser = true; // set mobile browser to true
      $status = 'Mobile matched on piped preg_match';
    break; // break out and skip the rest if we've preg_match on the user agent returned true 

    case ((strpos($accept,'text/vnd.wap.wml')>0)||(strpos($accept,'application/vnd.wap.xhtml+xml')>0)); // is the device showing signs of support for text/vnd.wap.wml or application/vnd.wap.xhtml+xml
      $mobile_browser = true; // set mobile browser to true
      $status = 'Mobile matched on content accept header';
    break; // break out and skip the rest if we've had a match on the content accept headers

    case (isset($_SERVER['HTTP_X_WAP_PROFILE'])||isset($_SERVER['HTTP_PROFILE'])); // is the device giving us a HTTP_X_WAP_PROFILE or HTTP_PROFILE header - only mobile devices would do this
      $mobile_browser = true; // set mobile browser to true
      $status = 'Mobile matched on profile headers being set';
    break; // break out and skip the final step if we've had a return true on the mobile specfic headers

    case (in_array(strtolower(substr($user_agent,0,4)),array('1207'=>'1207','3gso'=>'3gso','4thp'=>'4thp','501i'=>'501i','502i'=>'502i','503i'=>'503i','504i'=>'504i','505i'=>'505i','506i'=>'506i','6310'=>'6310','6590'=>'6590','770s'=>'770s','802s'=>'802s','a wa'=>'a wa','acer'=>'acer','acs-'=>'acs-','airn'=>'airn','alav'=>'alav','asus'=>'asus','attw'=>'attw','au-m'=>'au-m','aur '=>'aur ','aus '=>'aus ','abac'=>'abac','acoo'=>'acoo','aiko'=>'aiko','alco'=>'alco','alca'=>'alca','amoi'=>'amoi','anex'=>'anex','anny'=>'anny','anyw'=>'anyw','aptu'=>'aptu','arch'=>'arch','argo'=>'argo','bell'=>'bell','bird'=>'bird','bw-n'=>'bw-n','bw-u'=>'bw-u','beck'=>'beck','benq'=>'benq','bilb'=>'bilb','blac'=>'blac','c55/'=>'c55/','cdm-'=>'cdm-','chtm'=>'chtm','capi'=>'capi','cond'=>'cond','craw'=>'craw','dall'=>'dall','dbte'=>'dbte','dc-s'=>'dc-s','dica'=>'dica','ds-d'=>'ds-d','ds12'=>'ds12','dait'=>'dait','devi'=>'devi','dmob'=>'dmob','doco'=>'doco','dopo'=>'dopo','el49'=>'el49','erk0'=>'erk0','esl8'=>'esl8','ez40'=>'ez40','ez60'=>'ez60','ez70'=>'ez70','ezos'=>'ezos','ezze'=>'ezze','elai'=>'elai','emul'=>'emul','eric'=>'eric','ezwa'=>'ezwa','fake'=>'fake','fly-'=>'fly-','fly_'=>'fly_','g-mo'=>'g-mo','g1 u'=>'g1 u','g560'=>'g560','gf-5'=>'gf-5','grun'=>'grun','gene'=>'gene','go.w'=>'go.w','good'=>'good','grad'=>'grad','hcit'=>'hcit','hd-m'=>'hd-m','hd-p'=>'hd-p','hd-t'=>'hd-t','hei-'=>'hei-','hp i'=>'hp i','hpip'=>'hpip','hs-c'=>'hs-c','htc '=>'htc ','htc-'=>'htc-','htca'=>'htca','htcg'=>'htcg','htcp'=>'htcp','htcs'=>'htcs','htct'=>'htct','htc_'=>'htc_','haie'=>'haie','hita'=>'hita','huaw'=>'huaw','hutc'=>'hutc','i-20'=>'i-20','i-go'=>'i-go','i-ma'=>'i-ma','i230'=>'i230','iac'=>'iac','iac-'=>'iac-','iac/'=>'iac/','ig01'=>'ig01','im1k'=>'im1k','inno'=>'inno','iris'=>'iris','jata'=>'jata','java'=>'java','kddi'=>'kddi','kgt'=>'kgt','kgt/'=>'kgt/','kpt '=>'kpt ','kwc-'=>'kwc-','klon'=>'klon','lexi'=>'lexi','lg g'=>'lg g','lg-a'=>'lg-a','lg-b'=>'lg-b','lg-c'=>'lg-c','lg-d'=>'lg-d','lg-f'=>'lg-f','lg-g'=>'lg-g','lg-k'=>'lg-k','lg-l'=>'lg-l','lg-m'=>'lg-m','lg-o'=>'lg-o','lg-p'=>'lg-p','lg-s'=>'lg-s','lg-t'=>'lg-t','lg-u'=>'lg-u','lg-w'=>'lg-w','lg/k'=>'lg/k','lg/l'=>'lg/l','lg/u'=>'lg/u','lg50'=>'lg50','lg54'=>'lg54','lge-'=>'lge-','lge/'=>'lge/','lynx'=>'lynx','leno'=>'leno','m1-w'=>'m1-w','m3ga'=>'m3ga','m50/'=>'m50/','maui'=>'maui','mc01'=>'mc01','mc21'=>'mc21','mcca'=>'mcca','medi'=>'medi','meri'=>'meri','mio8'=>'mio8','mioa'=>'mioa','mo01'=>'mo01','mo02'=>'mo02','mode'=>'mode','modo'=>'modo','mot '=>'mot ','mot-'=>'mot-','mt50'=>'mt50','mtp1'=>'mtp1','mtv '=>'mtv ','mate'=>'mate','maxo'=>'maxo','merc'=>'merc','mits'=>'mits','mobi'=>'mobi','motv'=>'motv','mozz'=>'mozz','n100'=>'n100','n101'=>'n101','n102'=>'n102','n202'=>'n202','n203'=>'n203','n300'=>'n300','n302'=>'n302','n500'=>'n500','n502'=>'n502','n505'=>'n505','n700'=>'n700','n701'=>'n701','n710'=>'n710','nec-'=>'nec-','nem-'=>'nem-','newg'=>'newg','neon'=>'neon','netf'=>'netf','noki'=>'noki','nzph'=>'nzph','o2 x'=>'o2 x','o2-x'=>'o2-x','opwv'=>'opwv','owg1'=>'owg1','opti'=>'opti','oran'=>'oran','p800'=>'p800','pand'=>'pand','pg-1'=>'pg-1','pg-2'=>'pg-2','pg-3'=>'pg-3','pg-6'=>'pg-6','pg-8'=>'pg-8','pg-c'=>'pg-c','pg13'=>'pg13','phil'=>'phil','pn-2'=>'pn-2','pt-g'=>'pt-g','palm'=>'palm','pana'=>'pana','pire'=>'pire','pock'=>'pock','pose'=>'pose','psio'=>'psio','qa-a'=>'qa-a','qc-2'=>'qc-2','qc-3'=>'qc-3','qc-5'=>'qc-5','qc-7'=>'qc-7','qc07'=>'qc07','qc12'=>'qc12','qc21'=>'qc21','qc32'=>'qc32','qc60'=>'qc60','qci-'=>'qci-','qwap'=>'qwap','qtek'=>'qtek','r380'=>'r380','r600'=>'r600','raks'=>'raks','rim9'=>'rim9','rove'=>'rove','s55/'=>'s55/','sage'=>'sage','sams'=>'sams','sc01'=>'sc01','sch-'=>'sch-','scp-'=>'scp-','sdk/'=>'sdk/','se47'=>'se47','sec-'=>'sec-','sec0'=>'sec0','sec1'=>'sec1','semc'=>'semc','sgh-'=>'sgh-','shar'=>'shar','sie-'=>'sie-','sk-0'=>'sk-0','sl45'=>'sl45','slid'=>'slid','smb3'=>'smb3','smt5'=>'smt5','sp01'=>'sp01','sph-'=>'sph-','spv '=>'spv ','spv-'=>'spv-','sy01'=>'sy01','samm'=>'samm','sany'=>'sany','sava'=>'sava','scoo'=>'scoo','send'=>'send','siem'=>'siem','smar'=>'smar','smit'=>'smit','soft'=>'soft','sony'=>'sony','t-mo'=>'t-mo','t218'=>'t218','t250'=>'t250','t600'=>'t600','t610'=>'t610','t618'=>'t618','tcl-'=>'tcl-','tdg-'=>'tdg-','telm'=>'telm','tim-'=>'tim-','ts70'=>'ts70','tsm-'=>'tsm-','tsm3'=>'tsm3','tsm5'=>'tsm5','tx-9'=>'tx-9','tagt'=>'tagt','talk'=>'talk','teli'=>'teli','topl'=>'topl','hiba'=>'hiba','up.b'=>'up.b','upg1'=>'upg1','utst'=>'utst','v400'=>'v400','v750'=>'v750','veri'=>'veri','vk-v'=>'vk-v','vk40'=>'vk40','vk50'=>'vk50','vk52'=>'vk52','vk53'=>'vk53','vm40'=>'vm40','vx98'=>'vx98','virg'=>'virg','vite'=>'vite','voda'=>'voda','vulc'=>'vulc','w3c '=>'w3c ','w3c-'=>'w3c-','wapj'=>'wapj','wapp'=>'wapp','wapu'=>'wapu','wapm'=>'wapm','wig '=>'wig ','wapi'=>'wapi','wapr'=>'wapr','wapv'=>'wapv','wapy'=>'wapy','wapa'=>'wapa','waps'=>'waps','wapt'=>'wapt','winc'=>'winc','winw'=>'winw','wonu'=>'wonu','x700'=>'x700','xda2'=>'xda2','xdag'=>'xdag','yas-'=>'yas-','your'=>'your','zte-'=>'zte-','zeto'=>'zeto','acs-'=>'acs-','alav'=>'alav','alca'=>'alca','amoi'=>'amoi','aste'=>'aste','audi'=>'audi','avan'=>'avan','benq'=>'benq','bird'=>'bird','blac'=>'blac','blaz'=>'blaz','brew'=>'brew','brvw'=>'brvw','bumb'=>'bumb','ccwa'=>'ccwa','cell'=>'cell','cldc'=>'cldc','cmd-'=>'cmd-','dang'=>'dang','doco'=>'doco','eml2'=>'eml2','eric'=>'eric','fetc'=>'fetc','hipt'=>'hipt','http'=>'http','ibro'=>'ibro','idea'=>'idea','ikom'=>'ikom','inno'=>'inno','ipaq'=>'ipaq','jbro'=>'jbro','jemu'=>'jemu','java'=>'java','jigs'=>'jigs','kddi'=>'kddi','keji'=>'keji','kyoc'=>'kyoc','kyok'=>'kyok','leno'=>'leno','lg-c'=>'lg-c','lg-d'=>'lg-d','lg-g'=>'lg-g','lge-'=>'lge-','libw'=>'libw','m-cr'=>'m-cr','maui'=>'maui','maxo'=>'maxo','midp'=>'midp','mits'=>'mits','mmef'=>'mmef','mobi'=>'mobi','mot-'=>'mot-','moto'=>'moto','mwbp'=>'mwbp','mywa'=>'mywa','nec-'=>'nec-','newt'=>'newt','nok6'=>'nok6','noki'=>'noki','o2im'=>'o2im','opwv'=>'opwv','palm'=>'palm','pana'=>'pana','pant'=>'pant','pdxg'=>'pdxg','phil'=>'phil','play'=>'play','pluc'=>'pluc','port'=>'port','prox'=>'prox','qtek'=>'qtek','qwap'=>'qwap','rozo'=>'rozo','sage'=>'sage','sama'=>'sama','sams'=>'sams','sany'=>'sany','sch-'=>'sch-','sec-'=>'sec-','send'=>'send','seri'=>'seri','sgh-'=>'sgh-','shar'=>'shar','sie-'=>'sie-','siem'=>'siem','smal'=>'smal','smar'=>'smar','sony'=>'sony','sph-'=>'sph-','symb'=>'symb','t-mo'=>'t-mo','teli'=>'teli','tim-'=>'tim-','tosh'=>'tosh','treo'=>'treo','tsm-'=>'tsm-','upg1'=>'upg1','upsi'=>'upsi','vk-v'=>'vk-v','voda'=>'voda','vx52'=>'vx52','vx53'=>'vx53','vx60'=>'vx60','vx61'=>'vx61','vx70'=>'vx70','vx80'=>'vx80','vx81'=>'vx81','vx83'=>'vx83','vx85'=>'vx85','wap-'=>'wap-','wapa'=>'wapa','wapi'=>'wapi','wapp'=>'wapp','wapr'=>'wapr','webc'=>'webc','whit'=>'whit','winw'=>'winw','wmlb'=>'wmlb','xda-'=>'xda-',))); // check against a list of trimmed user agents to see if we find a match
      $mobile_browser = true; // set mobile browser to true
      $status = 'Mobile matched on in_array';
    break; // break even though it's the last statement in the switch so there's nothing to break away from but it seems better to include it than exclude it

    default;
      $mobile_browser = false; // set mobile browser to false
      $status = 'Desktop / full capability browser';
    break; // break even though it's the last statement in the switch so there's nothing to break away from but it seems better to include it than exclude it

  } // ends the switch 

  // if redirect (either the value of the mobile or desktop redirect depending on the value of $mobile_browser) is true redirect else we return the status of $mobile_browser
  if($redirect = ($mobile_browser==true) ? $mobileredirect : $desktopredirect){
    redirect($redirect); // redirect to the right url for this device
  }else{ 
		// a couple of folkas have asked about the status - that's there to help you debug and understand what the script is doing
		if($mobile_browser==''){
			return $mobile_browser; // will return either true or false 
		}else{
			return array($mobile_browser,$status); // is a mobile so we are returning an array ['0'] is true ['1'] is the $status value
		}
	}

}


// Redirect user to home page from a project-level page
function redirectHome() 
{
	redirect(((strlen(dirname(dirname($_SERVER['PHP_SELF']))) <= 1) ? "/" : dirname(dirname($_SERVER['PHP_SELF']))));
	exit;
}

/**
 * PROMPT USER TO LOG IN
 */
function loginFunction() 
{	
	global $authFail, $project_contact_email, $project_contact_name, $auth_meth, $login_autocomplete_disable,
		   $homepage_contact_email, $homepage_contact, $autologout_timer, $lang, $isMobileDevice, $institution, 
		   $login_logo, $login_custom_text;
	
	if ($authFail && isset($_POST['submitted'])) {
		// If the authentication has failed after submission
		// return to try and authenticate off the next server
		return 0;
	}	
	
	// If using RSA SecurID two-factor authencation, use passcode instead of password in text
	$passwordLabel = $lang['global_32'].$lang['colon'];
	$passwordTextRight = "";
	$rsaLogo = "";
	if ($auth_meth == 'rsa') {
		$rsaLogo =  RCView::tr(array(),
						RCView::td(array('colspan'=>2,'style'=>'text-align:center;padding-bottom:5px;'),
							RCView::img(array('src'=>'securid2.gif'))
						)
					);
		$passwordLabel = $lang['global_82'].$lang['colon'];
		$passwordTextRight = RCView::div(array('style'=>'color:#800000;padding-top:4px;font-size:12px;'),
								"(Your PIN + 6-digit Tokencode)"
							 );
	}
	
	// Set "forgot password?" link
	$forgotPassword = "";
	if ($auth_meth == "table" || $auth_meth == "ldap_table") {
		$forgotPassword = RCView::span(array("style"=>"margin-left:50px;"), 
							RCView::a(array("style"=>"font-size:11px;text-decoration:underline;","href"=>APP_PATH_WEBROOT."Authentication/password_recovery.php"), $lang['pwd_reset_41'])
						  );
	}
	
	// Display the Login Form
	$objHtmlPage = new HtmlPage();
	$objHtmlPage->addExternalJS(APP_PATH_JS . "base.js");
	
	// Adjust layout if on mobile device
	if ($isMobileDevice)
	{
		$objHtmlPage->addStylesheet("jqtouch.min.css", 'screen,print');
		$objHtmlPage->addStylesheet("jqtouch_themes/apple/theme.min.css", 'screen,print');
		$objHtmlPage->addExternalJS(APP_PATH_JS . "jqtouch.min.js");
		$objHtmlPage->PrintHeader();
		?>

		<style type="text/css">
		#footer { display: none; }
		</style>

		<script type="text/javascript">
		var jQT = new $.jQTouch();
		</script>	

		<div id="home" class="current">
			<div class="toolbar">
				<h1>REDCap</h1>
			</div>
			<br>
			<?php
			// Show custom login text (optional)
			if (trim($login_custom_text) != "") 
			{
				print "<div style='font-size:11px;border:1px solid #ccc;background-color:#f5f5f5;margin:0 5px 15px;padding:10px;'>".nl2br(filter_tags(label_decode($login_custom_text)))."</div>";
			}			
			?>
			<h2><?php echo $lang['mobile_site_08'] ?></h2>
			<?php if ($auth_meth == 'rsa') print RCView::table(array('style'=>'padding:0 20px;'), $rsaLogo); ?>
			<form name="form" method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>" <?php echo ($login_autocomplete_disable ? 'autocomplete="off"' : '') ?>>
                <ul class="edit rounded">
                    <li><input type="text" name="username" placeholder="<?php echo cleanHtml2($lang['global_11']) ?>" onkeydown="if(event.keyCode==13){document.form.password.focus();}"></li>
                    <li><input type="password" name="password" placeholder="<?php echo cleanHtml2(strip_tags($passwordLabel)) ?>" onkeydown="if(event.keyCode==13){document.form.submit();}"></li>
				</ul>
				<?php if ($auth_meth == 'rsa') print RCView::div(array('style'=>'padding:0 15px;'), $passwordTextRight); ?>
				<br>
				<a class="whiteButton" href="javascript:;" onclick="$(this).css({'color':'#800000','background':'red'});document.form.submit();">Log in</a>
				<input type="hidden" id="redcap_login_a38us_09i85" name="redcap_login_a38us_09i85" value="">
				<input type="hidden" name="submitted" value="1"> 
			</form>
		</div>
		
		<?php
	}
	
	// Regular non-mobile login form
	else
	{
		$objHtmlPage->addStylesheet("smoothness/jquery-ui-".JQUERYUI_VERSION.".custom.css", 'screen,print');
		$objHtmlPage->addStylesheet("style.css", 'screen,print');
		$objHtmlPage->addStylesheet("home.css", 'screen,print');
		$objHtmlPage->PrintHeader();
		
		print '<style type="text/css">div#container{ background: url("'.APP_PATH_IMAGES.'redcaplogo.gif") no-repeat; }</style>';
		
		print '<div id="left_col">';	
		
		print '<h3 style="margin-top:120px;padding:3px;border-bottom:1px solid #AAAAAA;color:#000000;font-weight:bold;">'.$lang['config_functions_45'].'</h3><br>';
				
		// Institutional logo (optional)
		if (trim($login_logo) != "")
		{
			print  "<div style='margin-bottom:20px;'>
						<img src='$login_logo' title='$institution' alt='$institution' style='max-width:750px; expression(this.width > 750 ? 750 : true);'>
					</div>";
		}

		// Show custom login text (optional)
		if (trim($login_custom_text) != "") 
		{
			print "<div style='border:1px solid #ccc;background-color:#f5f5f5;margin:15px 10px 15px 0;padding:10px;'>".nl2br(filter_tags(label_decode($login_custom_text)))."</div>";
		}
		
		
		// Login instructions
		print  "<p>
					{$lang['config_functions_67']}
					<a style='font-size:12px;text-decoration:underline;' href=\"mailto:$homepage_contact_email\">$homepage_contact</a>{$lang['period']}
				</p>
				<br>";
		
		print  "<center>";
		print  "<form method='post' action='{$_SERVER['REQUEST_URI']}' " . ($login_autocomplete_disable ? "autocomplete='off'" : "") . ">";
		print  "<table border=0 cellspacing=5>
					$rsaLogo
					<tr>
						<td valign='top' style='text-align:left;padding:3px 15px 0 0;font-family:Arial;font-size:12px;'>{$lang['global_11']}{$lang['colon']}</td>
						<td valign='top' style='text-align:left;'> 
							<input type='text' class='x-form-text x-form-field' style='vertical-align:top;' name='username' id='username' tabindex='1' " . ($login_autocomplete_disable ? "autocomplete='off'" : "") . ">
						</td>
					</tr>
					<tr>
						<td valign='top' style='text-align:left;padding:3px 15px 0 0;font-family:Arial;font-size:12px;'>$passwordLabel</td>
						<td valign='top' style='text-align:left;'> 
							<input type='password' class='x-form-text x-form-field' style='vertical-align:top;' name='password' id='password' tabindex='2' " . ($login_autocomplete_disable ? "autocomplete='off'" : "") . ">
							<input type='hidden' id='redcap_login_a38us_09i85' name='redcap_login_a38us_09i85' value=''>
							$passwordTextRight
						</td>
					</tr>
					
					<tr>
						<td></td>
						<td valign='top' style='text-align:left;padding:10px 0;'> 
							<input type='submit' value='{$lang['config_functions_45']}' tabindex='3'> 
							$forgotPassword
						</td>
					</tr>
				</table>
				<input type='hidden' name='submitted' value='1'>";
				
		// FAILSAFE: If user was submitting data on form and somehow the auth session ends before it's supposed to, take posted data, encrypt it, and carry it over after new login
		if ($_SERVER['REQUEST_METHOD'] == 'POST' && PAGE == 'DataEntry/index.php' && isset($_GET['page']) 
			&& isset($_GET['event_id']) && (isset($_POST['submit-action']) || isset($_POST['redcap_login_post_encrypt_e3ai09t0y2'])))
		{
			// Encrypt the submitted values, and if login failed, preserve encrypted value
			$enc_val = isset($_POST['redcap_login_post_encrypt_e3ai09t0y2']) ? $_POST['redcap_login_post_encrypt_e3ai09t0y2'] : encrypt(serialize($_POST));
			print  "<input type='hidden' value='$enc_val' name='redcap_login_post_encrypt_e3ai09t0y2'>
					<p class='green' style='text-align:center;'>
						<img src='" . APP_PATH_IMAGES . "add.png' class='imgfix'> 
						<b>{$lang['global_02']}{$lang['colon']}</b> {$lang['config_functions_68']}
					</p>";
		}
		
		print "</form>";
		print "</center>";	
		print "<br></div><hr size=1>"; 
		
		// Display home page or Traing Resources page below (but without allowing access into projects yet)
		if (isset($_GET['action']) && $_GET['action'] == 'training') {
			include APP_PATH_DOCROOT . "Home/training_resources.php";	
		} else {
			include APP_PATH_DOCROOT . "Home/info.php";
		}
		
	}
	
	// Put focus on username login field
	print "<script type='text/javascript'>document.getElementById('username').focus();</script>";
	
	// Since we're showing the login page, destroy all sessions/cookies, just in case they are left over from previous session.
	if (!session_id()) @session_start();
	$_SESSION = array();
	session_unset();
	session_destroy();
	
	$objHtmlPage->PrintFooter();
	exit;
	
}

// Check if system has been set to Offline. If so, prevent normal users from accessing site.
function checkSystemStatus() {

	global $system_offline, $homepage_contact_email, $homepage_contact, $lang;
	
	$GLOBALS['delay_kickout'] = $delay_kickout = false;
	
	if ($system_offline && PAGE != 'Test/index.php' && PAGE != 'Test/server_ping.php' && (!defined('SUPER_USER') || (defined('SUPER_USER') && !SUPER_USER))) 
	{
		//To prevent loss of data, don't kick the user out until the page has been processed when on data entry page.
		if (PAGE == "DataEntry/index.php") {
			$GLOBALS['delay_kickout'] = true;
			return;
		}
			
		// Initialize page display object
		$objHtmlPage = new HtmlPage();
		$objHtmlPage->addExternalJS(APP_PATH_JS . "base.js");
		$objHtmlPage->addStylesheet("smoothness/jquery-ui-".JQUERYUI_VERSION.".custom.css", 'screen,print');
		$objHtmlPage->addStylesheet("style.css", 'screen,print');
		$objHtmlPage->addStylesheet("home.css", 'screen,print');
		$objHtmlPage->PrintHeader();
		
		print  "<div style='padding:20px 0;'>
					<img src='" . APP_PATH_IMAGES . "redcaplogo.gif'>
				</div>
				<div class='red' style='margin:20px 0;'>
					<img src='" . APP_PATH_IMAGES . "exclamation.png' class='imgfix'>
					{$lang['config_functions_36']}
				</div>
				<p style='padding-bottom:30px;'>
					{$lang['config_functions_37']} 
					<a style='font-size:12px;text-decoration:underline;' href='mailto:$homepage_contact_email'>$homepage_contact</a>.
				</p>";
		
		$objHtmlPage->PrintFooter();
		exit;
		
	}	
}

// Check Online/Offline Status: If project has been marked as OFFLINE in Control Center, then disallow access and give explanatory message.
function checkOnlineStatus() {
	
	global $delay_kickout, $online_offline, $lang, $project_contact_name, $project_contact_email, $lang, $homepage_contact_email, $homepage_contact;
	
	if (!$online_offline && (!defined('SUPER_USER') || (defined('SUPER_USER') && !SUPER_USER))) {
		//To prevent loss of data, don't kick the user out until the page has been processed when on data entry page.
		if (PAGE != "DataEntry/index.php") {
			// Initialize page display object
			$objHtmlPage = new HtmlPage();
			$objHtmlPage->addExternalJS(APP_PATH_JS . "base.js");
			$objHtmlPage->addStylesheet("smoothness/jquery-ui-".JQUERYUI_VERSION.".custom.css", 'screen,print');
			$objHtmlPage->addStylesheet("style.css", 'screen,print');
			$objHtmlPage->addStylesheet("home.css", 'screen,print');
			$objHtmlPage->PrintHeader();
			
			print  "<div style='padding:20px 0;'>
						<img src='" . APP_PATH_IMAGES . "redcaplogo.gif'>
					</div>
					<div class='red' style='margin:20px 0;'>
						<img src='" . APP_PATH_IMAGES . "exclamation.png' class='imgfix'>
						{$lang['config_functions_36']}
					</div>
					<p style='padding-bottom:30px;'>
						{$lang['config_functions_37']} 
						<a style='font-size:12px;text-decoration:underline;' href='mailto:$homepage_contact_email'>$homepage_contact</a>.
					</p>";
			
			$objHtmlPage->PrintFooter();
			exit;
		} else {
			// Delay kickout until user has submitted their data
			$delay_kickout = true;
		}
	}
	$GLOBALS['delay_kickout'] = $delay_kickout;
}


// Check if need to report institutional stats to REDCap consortium 
function checkReportStats() {
	
	global $auto_report_stats, $auto_report_stats_last_sent;
	
	// If auto stat reporting is set, check if more than 7 days have passed in order to report current stats
	// Only do checking when user is on a project's index page
	if ($auto_report_stats && PAGE == "index.php") 
	{
		list ($yyyy, $mm, $dd) = explode("-", $auto_report_stats_last_sent);
		$daydiff = ceil((mktime(0, 0, 0, date("m"), date("d"), date("Y")) - mktime(0, 0, 0, $mm, $dd, $yyyy)) / (3600 * 24));
		// If not reported in 7 days, trigger AJAX call to report them
		if ($daydiff >= 7) {
			// Render javascript for AJAX call
			print "<script type='text/javascript'>\$(function(){\$.get(app_path_webroot+'ControlCenter/report_site_stats.php');});</script>";	
		}
	}
	
}


// Count page hits (but not for specified pages, or for AJAX requests, or for survey passthru pages)
function addPageHit() 
{
	global $noCountPages, $isAjax;
	if (!in_array(PAGE, $noCountPages) && !$isAjax && !(PAGE == 'surveys/index.php' && isset($_GET['__passthru']))) 
	{
		//Add one to daily count
		$ph = db_query("update redcap_page_hits set page_hits = page_hits + 1 where date = CURRENT_DATE and page_name = '" . PAGE . "'");
		//Do insert if previous query fails (in the event of being the first person to hit that page that day)
		if (!$ph || db_affected_rows() != 1) {
			db_query("insert into redcap_page_hits (date, page_name) values (CURRENT_DATE, '" . PAGE . "')");
		}
	}
}

// For SURVEYS ONLY, save IP address as hashed value in cache table to prevent automated attacks
function storeHashedIp($ip)
{
	global $salt, $__SALT__;
	// First delete any rows older than one hour
	$oneHourAgo = date("Y-m-d H:i:s", mktime(date("H")-1,date("i"),date("s"),date("m"),date("d"),date("Y")));
	db_query("delete from redcap_surveys_ip_cache where timestamp < '$oneHourAgo'");
	// Hash the IP
	$ip_hash = md5($salt . $__SALT__ . $ip . $salt);
	// Add this page hit to the table
	db_query("insert into redcap_surveys_ip_cache values ('$ip_hash', '" . NOW . "')");
	// Check if ip is found more than a set threshold of times in the past 1 hour
	$hit_threshold = 2500;
	$q = db_query("select count(1) from redcap_surveys_ip_cache where ip_hash = '$ip_hash' and timestamp > '$oneHourAgo'");
	if (db_result($q, 0) > $hit_threshold) {
		// Threshold reached, so add IP to banned IP table
		db_query("insert into redcap_surveys_banned_ips values ('$ip', '" . NOW . "')");
	}
}

// Log page and user info for page being viewed (but only for specified pages)
function addPageView($event="PAGE_VIEW",$userid=USERID) {
	
	global $noCountPages, $query_array, $custom_report_sql, $Proj;
	
	// If this is the REDCap cron job, then skip this
	if (defined('CRON')) return;
	
	// Set userid as blank if USERID is not defined
	if (!defined("USERID") && $userid == "USERID") $userid = "";
	
	// If current page view is to be logged (i.e. if not set as noCourntPage and is not a survey passthru page)
	if (!in_array(PAGE, $noCountPages) && !(PAGE == 'surveys/index.php' && isset($_GET['__passthru']))) 
	{
		// Obtain browser info
		$browser = new Browser();
		$browser_name = strtolower($browser->getBrowser());
		$browser_version = $browser->getVersion();
		// Do not include more than one decimal point in version
		if (substr_count($browser_version, ".") > 1) {
			$browser_version_array = explode(".", $browser_version);
			$browser_version = $browser_version_array[0] . "." . $browser_version_array[1];
		}
		
		// Obtain other needed values
		$ip 	 	= getIpAddress();
		$page 	  	= (defined("PAGE") ? PAGE : "");
		$event	  	= strtoupper($event);
		$project_id = defined("PROJECT_ID") ? PROJECT_ID : "NULL";
		$full_url	= curPageURL();
		$session_id = (!session_id() ? "" : substr(session_id(), 0, 32));
		
		// Defaults 
		$event_id 	= "NULL";
		$record		= "";
		$form_name 	= "";
		$miscellaneous = "";
		
		// Special logging for certain pages
		switch (PAGE) 
		{
			// Data Quality rule execution
			case "DataQuality/execute_ajax.php":
				$miscellaneous = "// rule_ids = '{$_POST['rule_ids']}'";
				break;
			// External Links clickthru page
			case "ExternalLinks/clickthru_logging_ajax.php":
				$miscellaneous = "// url = " . $_POST['url'];
				break;
			// Survey page
			case "surveys/index.php":
				// For SURVEYS ONLY, save IP address as hashed value in cache table to prevent automated attacks
				storeHashedIp($ip);
				// Set username and erase ip to maintain anonymity survey respondents
				$userid = "[survey respondent]";
				$ip = "";
				// Capture the response_id if we have it
				if (isset($_POST['__response_hash__']) && !empty($_POST['__response_hash__'])) {
					global $participant_id;
					$miscellaneous = "// response_id = " . decryptResponseHash($_POST['__response_hash__'], $participant_id);
				}
				break;
			// API
			case "api/index.php":
				// If downloading file, log it
				if ($_SERVER['REQUEST_METHOD'] == 'POST') {				
					// Set values needed for logging
					if (isset($_POST['token']) && !empty($_POST['token']))
					{
						$q = db_query("select project_id, username from redcap_user_rights where api_token = '" . $_POST['token'] . "'");
						$userid = db_result($q, 0, "username");
						$project_id = db_result($q, 0, "project_id");
					}
					$post = $_POST;
					// Remove data from $_POST for logging
					if (isset($post['data'])) $post['data'] = '[not displayed]';
					$miscellaneous = "// API Request: ";
					foreach ($post as $key=>$value) {
						$miscellaneous .= "$key=>" . ((is_array($value)) ? implode(", ", $value) : $value) . "; ";
					}
					$miscellaneous = substr($miscellaneous, 0, -2);
				}
				break;
			// Data history
			case "DataEntry/data_history_popup.php":
				if (isset($_POST['event_id'])) 
				{
					$form_name = $Proj->metadata[$_POST['field_name']]['form_name'];
					$event_id = $_POST['event_id'];
					$record = $_POST['record'];
					$miscellaneous = "field_name = '" . $_POST['field_name'] . "'";
				}
				break;
			// Send it download
			case "SendIt/download.php":
				// If downloading file, log it
				if ($_SERVER['REQUEST_METHOD'] == 'POST') {
					$miscellaneous = "// Download file (Send-It)";
				}
				break;
			// Send it upload
			case "SendIt/upload.php":
				// Get project_id
				$fileLocation = (isset($_GET['loc']) ? $_GET['loc'] : 1);
				if ($fileLocation != 1) {
					if ($fileLocation == 2) //file repository
						$query = "SELECT project_id FROM redcap_docs WHERE docs_id = " . $_GET['id'];
					else if ($fileLocation == 3) //data entry form
						$query = "SELECT project_id FROM redcap_edocs_metadata WHERE doc_id = " . $_GET['id'];
					$project_id = db_result(db_query($query), 0);
				}
				// If uploading file, log it
				if ($_SERVER['REQUEST_METHOD'] == 'POST') {
					$miscellaneous = "// Upload file (Send-It)";
				}
				break;
			// Data entry page
			case "DataEntry/index.php":
				if (isset($_GET['page'])) {
					$form_name = $_GET['page'];
					$event_id = isset($_GET['event_id']) ? $_GET['event_id'] : getSingleEvent(PROJECT_ID);
					if (isset($_GET['id'])) $record = $_GET['id'];
				}
				break;
			// Page used for reseting user's session
			case "ProjectGeneral/login_reset.php":
				if (isset($_GET['page'])) {
					$form_name = $_GET['page'];
					$event_id = isset($_GET['event_id']) ? $_GET['event_id'] : getSingleEvent(PROJECT_ID);
					if (isset($_GET['id'])) $record = $_GET['id'];
				}
				break;
			// PDF form export
			case "PDF/index.php":
				if (isset($_GET['page'])) $form_name = $_GET['page'];
				$event_id = isset($_GET['event_id']) ? $_GET['event_id'] : getSingleEvent(PROJECT_ID);
				if (isset($_GET['id'])) $record = $_GET['id'];
				break;
			// Longitudinal grid
			case "DataEntry/grid.php":
				if (isset($_GET['id'])) $record = $_GET['id'];
				break;
			// Calendar
			case "Calendar/index.php":
				// Obtain mm, dd, yyyy being viewed
				if (!isset($_GET['year'])) {
					$_GET['year'] = date("Y");
				}
				if (!isset($_GET['month'])) {
					$_GET['month'] = date("n")+1;
				}
				$month = $_GET['month'] - 1;
				$year  = $_GET['year'];
				if (isset($_GET['day']) && $_GET['day'] != "") {
					$day = $_GET['day'];
				} else {
					$day = $_GET['day'] = 1;
				}
				$days_in_month = date("t", mktime(0,0,0,$month,1,$year));
				// Set values
				$view = (!isset($_GET['view']) || $_GET['view'] == "") ? "month" : $_GET['view'];
				$miscellaneous = "view: $view\ndates viewed: ";
				switch ($view) {
					case "day":
						$miscellaneous .= "$month/$day/$year";
						break;
					case "week":
						$miscellaneous .= "week of $month/$day/$year";
						break;
					default:
						$miscellaneous .= "$month/1/$year - $month/$days_in_month/$year";					
				}				
				break;
			// Edoc download
			case "DataEntry/file_download.php":				
				$record    = $_GET['record'];
				$event_id  = $_GET['event_id'];
				$form_name = $_GET['page'];
				break;
			// Calendar pop-up
			case "Calendar/calendar_popup.php":
				// Check if has record or event
				$q = db_query("select record, event_id from redcap_events_calendar where cal_id = {$_GET['cal_id']}");
				$record   = db_result($q, 0, "record");
				$event_id = checkNull(db_result($q, 0, "event_id"));
				break;
			// Scheduling module
			case "Calendar/scheduling.php":
				if (isset($_GET['record'])) {
					$record = $_GET['record'];
				}			
				break;
			// Graphical Data View page
			case "Graphical/index.php":
				if (isset($_GET['page'])) { 
					$form_name = $_GET['page'];
				}			
				break;
			// Graphical Data View highest/lowest/missing value
			case "Graphical/highlowmiss.php":
				$form_name 	= $_GET['form'];
				$miscellaneous = "field_name: '{$_GET['field']}'\n"
							   . "action: '{$_GET['svc']}'\n"
							   . "group_id: " . (($_GET['group_id'] == "undefined") ? "" : $_GET['group_id']);
				break;
			// Viewing a report
			case "Reports/report.php":
			case "Reports/report_export.php":
				// Report Builder reports
				if (isset($_GET['query_id'])) {
					$miscellaneous = "// Report array for \"" . $query_array[$_GET['query_id']]['__TITLE__'] . "\":";
					foreach ($query_array[$_GET['query_id']] as $this_field=>$this_where) {
						$miscellaneous .= "\n\$query_array[{$_GET['query_id']}]['$this_field'] = '$this_where';";
					}
				}
				// Legacy reports
				if (isset($_GET['id'])) {
					$miscellaneous = "// Report array for \"" . $custom_report_menu[$_GET['query_id']] . "\":";
					foreach ($custom_report_sql[$_GET['id']] as $this_field=>$this_where) {
						$miscellaneous .= "\n\$custom_report_sql[{$_GET['id']}]['$this_field'] = '$this_where';";
					}
				}
				break;
			// Data comparison tool
			case "DataComparisonTool/index.php":
				if (isset($_POST['record1'])) {
					list ($record1, $event_id1) = explode("[__EVTID__]", $_POST['record1']);
					if (isset($_POST['record2'])) {
						list ($record2, $event_id2) = explode("[__EVTID__]", $_POST['record2']);
						$record = "$record1 (event_id: $event_id1)\n$record2 (event_id: $event_id2)";
					} else {
						$record = "$record1 (event_id: $event_id1)";
					}
				}
				break;
			// File repository and data export docs
			case "FileRepository/file_download.php":
				if (isset($_GET['id'])) {
					$miscellaneous = "// Download file from redcap_docs (docs_id = {$_GET['id']})";
				}
				break;
			// Logging page
			case "Logging/index.php":
				if (isset($_GET['record']) && $_GET['record'] != '') {
					$record = $_GET['record'];
				}
				if (isset($_GET['usr']) && $_GET['usr'] != '') {
					$miscellaneous = "// Filter by user name ('{$_GET['usr']}')";
				}
				break;
		}
		
		// Do logging
		$sql = "insert into redcap_log_view (ts, user, event, ip, browser_name, browser_version, full_url, page, project_id, event_id, 
				record, form_name, miscellaneous, session_id) values ('".NOW."', '" . prep($userid) . "', '$event', " . checkNull($ip) . ", 
				'" . prep($browser_name) . "', '" . prep($browser_version) . "', 
				'" . prep($full_url) . "', '$page', $project_id, $event_id, " . checkNull($record) . ", 
				" . checkNull($form_name) . ", " . checkNull($miscellaneous) . ", " . checkNull($session_id) . ")";
		db_query($sql);
		
		return;
	}
}

// Get currentl full URL
function curPageURL() 
{
	$pageURL = (SSL ? 'https' : 'http') . '://';
	if (($_SERVER["SERVER_PORT"] == "80" && !SSL) || ($_SERVER["SERVER_PORT"] == "443" && SSL)) {
		$pageURL .= SERVER_NAME.$_SERVER["REQUEST_URI"];
	} else {
		$pageURL .= SERVER_NAME.":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	}
	return $pageURL;
}

// Clean $_GET and $_POST to prevent XSS and SQL injection 
function cleanGetPost() 
{ 
	// Fix vulnerabilities for $_SERVER values
	if (isset($_SERVER['PATH_INFO']) && !empty($_SERVER['PATH_INFO'])) {
		// Make sure we chop off end of URL if using something like .../index.php/database.php
		$_SERVER['PHP_SELF'] = substr($_SERVER['PHP_SELF'], 0, -1 * strlen($_SERVER['PATH_INFO'])); 
	}
	$_SERVER['PHP_SELF']     = str_replace("&amp;", "&", htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES));
	$_SERVER['QUERY_STRING'] = str_replace("&amp;", "&", htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));
	$_SERVER['REQUEST_URI']  = str_replace("&amp;", "&", htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES));
	if (isset($_SERVER['HTTP_REFERER'])) {
		$_SERVER['HTTP_REFERER'] = str_replace("&amp;", "&", htmlspecialchars($_SERVER['HTTP_REFERER'], ENT_QUOTES));
	}
	// Santize $_GET array 
	foreach ($_GET as $key=>$value) 
	{ 
		if (get_magic_quotes_gpc()) $value = stripslashes($value); 
		$_GET[$key] = htmlspecialchars($value, ENT_QUOTES);
		// Remove IE's CSS "style=x:expression(" (used for XSS attacks)
		$_GET[$key] = preg_replace("/(\s+)(style)(\s*)(=)(\s*)(x)(\s*)(:)(\s*)(e)([\/\*\*\/]*)(x)([\/\*\*\/]*)(p)([\/\*\*\/]*)(r)([\/\*\*\/]*)(e)([\/\*\*\/]*)(s)([\/\*\*\/]*)(s)([\/\*\*\/]*)(i)([\/\*\*\/]*)(o)([\/\*\*\/]*)(n)(\s*)(\()/i", ' (', $_GET[$key]);
	}
	
	// Santize $_POST array 
	if ($_SERVER['REQUEST_METHOD'] == 'POST') 
	{ 
		foreach ($_POST as $key=>$value) { 
			if (is_array($value)) { 
				foreach ($value as $innerKey=>$innerValue) { 
					if (get_magic_quotes_gpc()) $innerValue = stripslashes($innerValue); 
					$_POST[$key][$innerKey] = htmlspecialchars($innerValue, ENT_QUOTES); 
				} 
			} else { 
				if (get_magic_quotes_gpc()) $value = stripslashes($value); 
				$_POST[$key] = htmlspecialchars($value, ENT_QUOTES); 
			} 
		} 
	} 
}


//Checks if user has rights to see this page
function check_user_rights($this_app_name) {
	
	global $double_data_entry, $require_change_reason, $Proj, $is_child;
	
	// If accessing a parent via a child project, then replace project_id with parent project_id
	if ($this_app_name != APP_NAME) {
		$this_project_id = db_result(db_query("select project_id from redcap_projects where project_name = '$this_app_name'"), 0);
	} else {
		$this_project_id = PROJECT_ID;
	}
	
	// If a super user, then manually set rights to full/max for all things
	if (SUPER_USER) 
	{
		// Set basic rights (super user cannot be DDE person 1 or 2, nor in a DAG)
		$user_rights = array('project_id'=>$this_project_id, 'username'=>USERID, 'expiration'=>'', 'group_id'=>'', 
							 'lock_record'=>2, 'lock_record_multiform'=>1, 'lock_record_customize'=>1,
							 'data_export_tool'=>1, 'data_import_tool'=>1, 'data_comparison_tool'=>1, 'data_logging'=>1, 'file_repository'=>1,
							 'user_rights'=>1, 'data_access_groups'=>1, 'design'=>1, 'calendar'=>1, 'reports'=>1, 'graphical'=>1, 
							 'double_data'=>0, 'record_create'=>1, 'record_rename'=>1, 'record_delete'=>1, 'api_token'=>'', 'dts'=>1,
							 'participants'=>1, 'data_quality_design'=>1, 'data_quality_execute'=>1, 'api_export'=>1, 'api_import'=>1,
							 'random_setup'=>1, 'random_dashboard'=>1, 'random_perform'=>1);
		// Set form-level rights
		if ($this_app_name != APP_NAME) {
			// Parent/child: If accessing a parent via a child project, then get form-level rights for parent
			$sql = "select distinct m.form_name, s.survey_id from redcap_metadata m left outer join redcap_surveys s 
					on m.form_name = s.form_name and s.project_id = m.project_id where m.project_id = $this_project_id 
					order by m.field_order";
			$q = db_query($sql);
			while ($row = db_fetch_assoc($q)) {
				// If this form is used as a survey, give super user level 3 (survey response editing), else give level 1 for form-level edit rights
				$user_rights['forms'][$row['form_name']] = (!empty($row['survey_id'])) ? '3' : '1';
			}
		} else {
			// Normal project
			foreach ($Proj->forms as $this_form=>$attr) {
				// If this form is used as a survey, give super user level 3 (survey response editing), else give level 1 for form-level edit rights
				$user_rights['forms'][$this_form] = (isset($attr['survey_id'])) ? '3' : '1';
			}
		}
		$GLOBALS['user_rights'] = $user_rights;		
		return true;
	}
	
	//Check if a user for this project
	$sql = "select * from redcap_user_rights where username = '" . prep(USERID) . "' 
			and project_id = $this_project_id limit 1";
	$q = db_query($sql);
	//Kick out if not a user and not a Super User
	if (db_num_rows($q) < 1) {	
		//Still show menu if a user from a child/linked project
		$GLOBALS['no_access'] = (isset($_GET['child']) ? 0 : 1);
		return false;
	}	
	//Set $user_rights array, which will carry all rights for current user
	$user_rights = db_fetch_assoc($q);
	
	//Loop through data entry listings and add each form as a new sub-array element
	$allForms = explode("][", substr(trim($user_rights['data_entry']), 1, -1));
	foreach ($allForms as $forminfo) 
	{
		list($this_form, $this_form_rights) = explode(",", $forminfo, 2);
		$user_rights['forms'][$this_form] = $this_form_rights;
	}
	
	if ($this_app_name == APP_NAME) // Don't peform the next couple procedures if access a parent form via a child project
	{
		// AUTO FIX FORM-LEVEL RIGHTS: Double check to make sure that the form-level rights are all there (old bug would sometimes cause them to go missing, thus disrupting things)
		foreach (array_keys($Proj->forms) as $this_form)
		{
			if (!isset($user_rights['forms'][$this_form])) {
				// Add to user_rights table (give user Full Edit rights to the form as default, if missing)
				$sql = "update redcap_user_rights set data_entry = concat(data_entry,'[$this_form,1]') 
						where project_id = $this_project_id and username = '" . USERID . "'";
				$q = db_query($sql);
				if (db_affected_rows() < 1) {
					// Must have a NULL as data_entry value, so fix it
					$sql = "update redcap_user_rights set data_entry = '[$this_form,1]' 
							where project_id = $this_project_id and username = '" . USERID . "'";
					$q = db_query($sql);
				}
				// Also add to $user_rights array
				$user_rights['forms'][$this_form] = '1';
			}
		}
	}
	
	// No longer needed now that we've parsed it
	unset($user_rights['data_entry']);
	
	// Set as global variable
	$GLOBALS['user_rights'] = $user_rights;
	
	// Check user's expiration date (if exists)
	if ($user_rights['expiration'] != "") 
	{
		$exp_date = str_replace("-", "", $user_rights['expiration']);
		$today_date = date('Ymd');
		if ($exp_date <= $today_date) 
		{
			$GLOBALS['no_access'] = 1;
			// Instead of returning 'false', return '2' specifically so we can note to user that the password has expired
			return '2';
		}
	}
	
	// Check Data Entry page rights (edit/read-only/none), if we're on that page
	if (PAGE == 'DataEntry/index.php' || PAGE == 'Mobile/data_entry.php') 
	{
		// If user does not have rights to this form, then return false
		if (!isset($user_rights['forms'][$_GET['page']])) {
			return false;
		}
		// If user has no access to form, kick out; otherwise set as full access or disabled
		if (isset($user_rights['forms'][$_GET['page']])) {
			return ($user_rights['forms'][$_GET['page']] != "0");
		}	
	}
	
	// Map pages to user_rights table values to determine rights for a given page (e.g. PAGE=>field from user_rights table)
	$page_rights = array(	
		// Export
		"DataExport/data_export_tool.php"=>"data_export_tool",
		"DataExport/data_export_csv.php"=>"data_export_tool",
		// Import
		"DataImport/index.php"=>"data_import_tool",
		"DataImport/import_tool.php"=>"data_import_tool",
		// Data Comparison Tool
		"DataComparisonTool/index.php"=>"data_comparison_tool",
		// Logging
		"Logging/index.php"=>"data_logging",
		"Logging/csv_export.php"=>"data_logging",
		// File Repository
		"FileRepository/index.php"=>"file_repository",
		// User Rights
		"UserRights/index.php"=>"user_rights",
		// DAGs
		"DataAccessGroups/index.php"=>"data_access_groups",
		// Graphical & Stats
		"Graphical/index.php"=>"graphical",
		"Graphical/pdf.php"=>"graphical",
		"Graphical/plot_rapache.php"=>"graphical",
		"Graphical/plot_gct.php"=>"graphical",
		"Graphical/highlowmiss.php"=>"graphical",
		// Reports
		"Reports/report.php"=>"reports",
		"Reports/report_builder.php"=>"reports",
		"Reports/report_export.php"=>"reports",
		// Calendar
		"Calendar/index.php"=>"calendar",
		// Locking records
		"Locking/locking_customization.php"=>"lock_record_customize",
		"Locking/esign_locking_management.php"=>"lock_record",
		// DTS
		"DTS/index.php"=>"dts",
		// Invite survey participants
		"Surveys/add_participants.php"=>"participants",
		"Surveys/invite_participants.php"=>"participants",
		"Surveys/delete_participant.php"=>"participants",
		"Surveys/edit_participant.php"=>"participants",
		"Surveys/participant_export.php"=>"participants",
		"Surveys/shorturl.php"=>"participants",
		"Surveys/participant_list.php"=>"participants",
		"Surveys/participant_list_enable.php"=>"participants",
		"Surveys/view_sent_email.php"=>"participants",
		// Data Quality
		"DataQuality/execute_ajax.php"=>"data_quality_execute",
		"DataQuality/edit_rule_ajax.php"=>"data_quality_design",
		// Randomization
		"Randomization/index.php"=>"random_setup",
		"Randomization/upload_allocation_file.php"=>"random_setup",
		"Randomization/download_allocation_file.php"=>"random_setup",
		"Randomization/download_allocation_file_template.php"=>"random_setup",
		"Randomization/check_randomization_field_data.php"=>"random_setup",
		"Randomization/delete_allocation_file.php"=>"random_setup",
		"Randomization/save_randomization_setup.php"=>"random_setup",
		"Randomization/dashboard.php"=>"random_dashboard",
		"Randomization/dashboard_all.php"=>"random_dashboard",
		"Randomization/randomize_record.php"=>"random_perform",
		// Setup & Design
		"ProjectGeneral/copy_project_form.php"=>"design",
		"Design/define_events.php"=>"design",
		"Design/designate_forms.php"=>"design",
		"Design/data_dictionary_upload.php"=>"design",
		"Design/data_dictionary_download.php"=>"design",
		"ProjectGeneral/edit_project_settings.php"=>"design",
		"ProjectGeneral/modify_project_setting_ajax.php"=>"design",
		"ProjectGeneral/delete_project.php"=>"design",
		"Design/delete_form.php"=>"design",
		"ProjectGeneral/erase_project_data.php"=>"design",
		"ProjectSetup/other_functionality.php"=>"design",
		"ProjectSetup/project_revision_history.php"=>"design",
		"IdentifierCheck/index.php"=>"design",
		"Design/online_designer.php"=>"design",
		"SharedLibrary/index.php"=>"design",
		"SharedLibrary/receiver.php"=>"design",
		"ProjectSetup/checkmark_ajax.php"=>"design",
		"Surveys/edit_info.php"=>"design",
		"Surveys/create_survey.php"=>"design",
		"Surveys/survey_online.php"=>"design",
		"Surveys/delete_survey.php"=>"design",
		"Design/draft_mode_review.php"=>"design",
		"Design/draft_mode_enter.php"=>"design",
		"Design/draft_mode_notified.php"=>"design",
		"Design/draft_mode_cancel.php"=>"design",		
		"ExternalLinks/index.php"=>"design",		
		"ExternalLinks/edit_resource_ajax.php"=>"design",
		"ExternalLinks/save_resource_users_ajax.php"=>"design",	
		"Design/calculation_equation_validate.php"=>"design",	
		"Design/branching_logic_builder.php"=>"design"
	);
	
	// Determine if user has rights to current page
	if (isset($page_rights[PAGE]) && isset($user_rights[$page_rights[PAGE]])) 
	{
		if ($user_rights[$page_rights[PAGE]] > 0) 
		{
			// DDE Person will have no rights to certain pages that display data 
			$doubleDataRestricted = array(	"Calendar/index.php", "DataExport/data_export_tool.php", "DataImport/index.php", "DataComparisonTool/index.php", 
											"data_logging.php", "FileRepository/index.php");
			if ($double_data_entry && $user_rights['double_data'] != 0 && in_array(PAGE, $doubleDataRestricted)) { 
				$GLOBALS['no_access'] = 1;
				return false; 
			} else { 
				// User has access to this page
				return true;
			}
		} else {
			// User does NOT have access to this page
			$GLOBALS['no_access'] = 1;
			return false;
		}
	}
	
	// If you got here, then you're on a page not dictated by rights in the $user_rights array, so allow access
	return true;
}


/**
 * DELETE TEMP FILES AND EXPIRED SEND-IT FILES (RUN ONCE EVERY HOUR)
 */
function remove_temp_deleted_files($forceAction=false) {

	global $temp_files_last_delete, $edoc_storage_option;
	
	// Make sure variable is set
	if ($temp_files_last_delete == "" || !isset($temp_files_last_delete)) return;
	
	// If temp files have not been checked/deleted in the past hour, then run procedure to delete them.
	if ($forceAction || strtotime(NOW)-strtotime($temp_files_last_delete) > 3600) 	// 3600 seconds = 1 hour
	{
		## DELETE ALL FILES IN TEMP DIRECTORY IF OLDER THAN 1 HOUR
		// Make sure temp dir is writable and exists
		if (is_dir(APP_PATH_TEMP) && is_writeable(APP_PATH_TEMP)) 
		{
			// Put temp file names into array
			$dh = opendir(APP_PATH_TEMP); 
			$files = array();
			while (false !== ($filename = readdir($dh))) { 
				$files[] = $filename;
			}
			// Timestamp of one hour ago
			$one_hour_ago = date('YmdHis')-10000;
			// Loop through all filed in temp dir
			foreach ($files as $key => $value) {			
				// Delete ANY files that begin with a 14-digit timestamp
				$file_time = substr($value, 0, 14);
				// If file is more than one hour old, delete it
				if (is_numeric($file_time) && $file_time < $one_hour_ago) {
					// Delete the file
					unlink(APP_PATH_TEMP . $value);
				}
			}
		}
		
		## DELETE ANY SEND-IT OR EDOC FILES THAT ARE FLAGGED FOR DELETION
		$docid_deleted = array();
		// Loop through list of expired Send-It files (only location=1, which excludes edocs and file repository files)
		// and Edoc files that were deleted by user over 30 days ago.
		$sql = "(select 'sendit' as type, document_id, doc_name from redcap_sendit_docs where location = 1 and expire_date < '".NOW."' 
				and date_deleted is null)
				UNION
				(select 'edocs' as type, doc_id as document_id, stored_name as doc_name from redcap_edocs_metadata where 
				delete_date is not null and date_deleted_server is null and delete_date < DATE_ADD('".NOW."', INTERVAL -1 MONTH))";
		$q = db_query($sql);
		// Delete from local web server folder
		if (!$edoc_storage_option) 
		{
			while ($row = db_fetch_assoc($q)) 
			{
				// Delete file, and if successfully deleted, then add to list of files deleted
				if (unlink(EDOC_PATH . $row['doc_name']))
				{
					$docid_deleted[$row['type']][] = $row['document_id'];
				}
			}
		}
		// Delete from external server via webdav
		elseif ($edoc_storage_option) 
		{
			// Call webdav class and open connection to external server
			require APP_PATH_WEBTOOLS . "webdav/webdav_connection.php";
			$wdc = new WebdavClient();
			$wdc->set_server($webdav_hostname);
			$wdc->set_port($webdav_port); $wdc->set_ssl($webdav_ssl);
			$wdc->set_user($webdav_username);
			$wdc->set_pass($webdav_password);
			$wdc->set_protocol(1);  // use HTTP/1.1
			$wdc->set_debug(false); // enable debugging?
			$wdc->open();
			if (substr($webdav_path,-1) != "/" && substr($webdav_path,-1) != "\\") {
				$webdav_path .= '/';
			}
			while ($row = db_fetch_assoc($q)) 
			{
				// Delete file
				$http_status = $wdc->delete($webdav_path . $row['doc_name']);
				// If successfully deleted, then add to list of files deleted
				if ($http_status['status'] != "404")
				{
					$docid_deleted[$row['type']][] = $row['document_id'];
				}
			}
		}
		
		// Initialize counter for number of docs deleted
		$docsDeleted = 0;
		
		// For all Send-It files deleted here, add date_deleted timestamp to table
		if (isset($docid_deleted['sendit'])) 
		{
			db_query("update redcap_sendit_docs set date_deleted = '".NOW."' where document_id in (" . implode(",", $docid_deleted['sendit']) . ")");		
			$docsDeleted += db_affected_rows();
		}
		// For all Edoc files deleted here, add date_deleted_server timestamp to table
		if (isset($docid_deleted['edocs'])) 
		{
			db_query("update redcap_edocs_metadata set date_deleted_server = '".NOW."' where doc_id in (" . implode(",", $docid_deleted['edocs']) . ")");		
			$docsDeleted += db_affected_rows();
		}
		
		## Now that all temp/send-it files have been deleted, reset time flag in config table
		db_query("update redcap_config set value = '".NOW."' where field_name = 'temp_files_last_delete'");
		
		// Return number of docs deleted
		return $docsDeleted;
	}
}


// Version Redirect: Make sure user is on the correct REDCap version for this project.
// Note that $redcap_version is pulled from config table and $redcapdir_version is the version from the folder name
// If they are not equal, then a redirect should occur so that user is accessing correct page in correct version (according to the redcap_projects table)	
function check_version() 
{
	global $redcap_version, $isAjax;
	// Bypass version check for developers who are using the "codebase" directory (instead of redcap_vX.X.X) for SVN purposes
	if (basename(APP_PATH_DOCROOT) == 'codebase') return;	
	// Get version we're currently in from the URL
	$redcapdir_version = substr(basename(APP_PATH_DOCROOT), 8);
	// If URL version does not match version number in redcap_config table, redirect to correct directory.
	// Do NOT redirect if the version number is not in the URL.
	if ($redcap_version != $redcapdir_version && strpos($_SERVER['REQUEST_URI'], "/redcap_v{$redcapdir_version}/") !== false) 
	{
		// Only redirect if version number in redcap_config table is an actual directory
		if (in_array("redcap_v" . $redcap_version, getDirFiles(dirname(APP_PATH_DOCROOT))))
		{
			// Replace version number in URL, then redirect
			$redirectto = str_replace("/redcap_v" . $redcapdir_version . "/", "/redcap_v" . $redcap_version . "/", $_SERVER['REQUEST_URI']);
			// Check if post or get request
			if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$isAjax) {
				// If this was a non-ajax post request, then preserve the submitted values by building
				// an invisible form that posts itself to same page in the new version.
				$postElements = "";
				foreach ($_POST as $key=>$val) {
					$postElements .= "<input type='hidden' name='$key' value=\"".str_replace("\"", "&quot;", $val)."\">";
				}
				?>
				<html><body>
				<form action="<?php echo $redirectto ?>" method="post" name="form" enctype="multipart/form-data">
					<?php echo $postElements ?>
				</form>
				<script type='text/javascript'>
				document.form.submit();
				</script>
				</body>
				</html>
				<?php
				exit;
			} else {
				// Redirect to the same page in the new version
				redirect($redirectto);
			}
		}
	}
}


// Obtain and return server name (i.e. domain), server port, and if using SSL (boolean)
function getServerNamePortSSL()
{
	global $proxy_hostname, $redcap_base_url, $redcap_version;
	// Trim vars
	$redcap_base_url = trim($redcap_base_url);
	$proxy_hostname  = trim($proxy_hostname);
	// Get SAPI name
	$sapi = php_sapi_name();
	// Check if REDCap is being accessed via PHP command line (CLI) by cron
	if ($redcap_base_url != '' && defined("CRON") 
		&& ($sapi == 'cli' || (($sapi == 'cgi' || $sapi == 'cgi-fcgi') && !isset($_COOKIE['server_name'])))) 
	{
		## IN CLI-MODE, so parse $redcap_base_url to get hostname, ssl, and port
		// Make sure $redcap_base_url ends with a /
		$redcap_base_url .= ((substr($redcap_base_url, -1) != "/") ? "/" : "");
		// Determine if uses SSL
		$ssl = (substr($redcap_base_url, 0, 5) == 'https');
		// Remove http[s]:// from the front and also remove subdirectories on the end to get server_name and port
		$hostStartPos = strpos($redcap_base_url, '://') + 3;
		$hostFirstSlash = strpos($redcap_base_url, '/', $hostStartPos);
		$server_name = substr($redcap_base_url, $hostStartPos, $hostFirstSlash - $hostStartPos);
		list ($server_name, $port) = explode(":", $server_name, 2);
		if ($port != '') $port = ":$port";
		// Set relative web path of this webpage
		$page_full = substr($redcap_base_url, $hostFirstSlash) . "redcap_v{$redcap_version}/cron.php";		
	} 
	else 
	{
		## NOT IN CLI-MODE
		// Determine if using SSL
		if ($proxy_hostname != '') {
			// Determine if proxy uses SSL
			$ssl = (substr($proxy_hostname, 0, 5) == 'https');
			// Determine proxy port
			$portPos = strpos($proxy_hostname, ':', 6);
			$port = ($portPos === false) ? '' : substr($proxy_hostname, $portPos);
		} elseif (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
			$port = ($_SERVER['SERVER_PORT'] != 443) ? ":".$_SERVER['SERVER_PORT'] : "";
			$ssl = true;
		} else {
			$port = ($_SERVER['SERVER_PORT'] != 80)  ? ":".$_SERVER['SERVER_PORT'] : "";
			$ssl = false;
		}
		// If port in URL is different from SERVER_PORT from PHP, then use JavaScript one (via saved cookie) to deal with proxy port issues
		if (isset($_COOKIE['server_port']) && !empty($_COOKIE['server_port']) && $_COOKIE['server_port'] != $port && is_numeric($_COOKIE['server_port'])) {
			$port = ":".$_COOKIE['server_port'];
		}
		// Determine web server domain name (and remove any illegal characters)
		$server_name = RCView::escape(str_replace(array("\"", "'", "+"), array("", "", ""), label_decode(((isset($_COOKIE['server_name']) && !empty($_COOKIE['server_name'])) ? $_COOKIE['server_name'] : getServerName()))));
		// Set relative web path of this webpage
		$page_full = $_SERVER['PHP_SELF'];
	}
	// Return values
	return array($server_name, $port, $ssl, $page_full);
}


// Set main directories for REDCap
function define_constants() 
{
	global $redcap_version, $edoc_path;
	// Get server name (i.e. domain), server port, and if using SSL (boolean)
	list ($server_name, $port, $ssl, $page_full) = getServerNamePortSSL();
	define("SERVER_NAME", $server_name);
	define("SSL", $ssl); 
	// Declare current page with full path
	define("PAGE_FULL", $page_full);
	// Declare current page path from the version folder. If in subfolder, include subfolder name.
	if (basename(dirname(PAGE_FULL)) == "redcap_v" . $redcap_version) {
		// Page in version folder
		define("PAGE", basename(PAGE_FULL));
	} elseif (basename(dirname(dirname(PAGE_FULL))) == "redcap_v" . $redcap_version) {
		// Page in subfolder under version folder
		define("PAGE", basename(dirname(PAGE_FULL)) . "/" . basename(PAGE_FULL));
	} else {
		$subfolderPage = basename(dirname(PAGE_FULL)) . "/" . basename(PAGE_FULL);
		if (basename(dirname(dirname(dirname(__FILE__)))) . "/index.php" == $subfolderPage || "surveys/index.php" ==  $subfolderPage) {
			// Only for the index.php page above the version folder OR for survey page
			define("PAGE", $subfolderPage);
		} else {
			// If using a file above the version folder (other than index.php), then PAGE will not be defined
		}
	}
	// Define web path to REDCap version folder (if REDCAP_WEBROOT is defined, then use it to determine APP_PATH_WEBROOT)
	if (defined("REDCAP_WEBROOT")) {
		define("APP_PATH_WEBROOT", REDCAP_WEBROOT . ((substr(REDCAP_WEBROOT, -1) != "/") ? "/" : "") . "redcap_v{$redcap_version}/");
	} else {
		define("APP_PATH_WEBROOT", getVersionFolderWebPath());
	}
	// Define full web address
	define("APP_PATH_WEBROOT_FULL",			(SSL ? "https" : "http") . "://" . SERVER_NAME . $port . ((strlen(dirname(APP_PATH_WEBROOT)) <= 1) ? "" : dirname(APP_PATH_WEBROOT)) . "/");
	// Path to server folder above REDCap webroot
	define("APP_PATH_WEBROOT_PARENT", 		((strlen(dirname(APP_PATH_WEBROOT)) <= 1) ? "" : dirname(APP_PATH_WEBROOT)) . "/");
	// Docroot will be used by php includes
	define("APP_PATH_DOCROOT", 				dirname(dirname(__FILE__)) . DS);	
	// Path to REDCap temp directory
	define("APP_PATH_TEMP",					dirname(APP_PATH_DOCROOT) . DS . "temp" . DS);
	// Webtools folder path
	define("APP_PATH_WEBTOOLS",				dirname(APP_PATH_DOCROOT) . DS . "webtools2" . DS);
	// Path to folder containing uploaded files (default is "edocs", but can be changed in Control Center system config)
	$edoc_path = trim($edoc_path);
	if ($edoc_path == "") {
		define("EDOC_PATH",					dirname(APP_PATH_DOCROOT) . DS . "edocs" . DS);
	} else {
		define("EDOC_PATH",					$edoc_path . ((substr($edoc_path, -1) == "/" || substr($edoc_path, -1) == "\\") ? "" : DS));
	}
	// Object classes
	define("APP_PATH_CLASSES",  			APP_PATH_DOCROOT . "Classes" . DS);
	// Image repository
	define("APP_PATH_IMAGES",				APP_PATH_WEBROOT . "Resources/images/");
	// CSS
	define("APP_PATH_CSS",					APP_PATH_WEBROOT . "Resources/css/");
	// External Javascript
	define("APP_PATH_JS",					APP_PATH_WEBROOT . "Resources/js/");	
	// Tiny MCE (rich text editor) - set current version used and its path
	define("TINYMCE_VERSION",				"3.4.9");
	define("APP_PATH_MCE",      			APP_PATH_WEBROOT_PARENT . "webtools2/tinymce_" . TINYMCE_VERSION . "/jscripts/tiny_mce/");
	// Survey URL
	define("APP_PATH_SURVEY",				APP_PATH_WEBROOT_PARENT . "surveys/");
	// Full survey URL
	define("APP_PATH_SURVEY_FULL",			APP_PATH_WEBROOT_FULL . "surveys/");
	// REDCap Consortium website domain name
	define("CONSORTIUM_WEBSITE_DOMAIN",		"http://project-redcap.org");
	// REDCap Consortium website URL	
	if (isDev()) {
		define("CONSORTIUM_WEBSITE",		"http://10.151.18.250/redcap/consortium/");	
	} else {
		define("CONSORTIUM_WEBSITE",		"https://redcap.vanderbilt.edu/consortium/");
	}
	// REDCap Shared Library URLs
	define("SHARED_LIB_PATH",				CONSORTIUM_WEBSITE 	  . "library/");
	define("SHARED_LIB_BROWSE_URL",			SHARED_LIB_PATH 	  . "login.php");
	define("SHARED_LIB_UPLOAD_URL",			SHARED_LIB_PATH 	  . "upload.php");
	define("SHARED_LIB_UPLOAD_ATTACH_URL",	SHARED_LIB_PATH 	  . "upload_attachment.php");
	define("SHARED_LIB_DOWNLOAD_URL",		SHARED_LIB_PATH 	  . "get.php");
	define("SHARED_LIB_SCHEMA",				SHARED_LIB_PATH 	  . "files/SharedLibrary.xsd");
	define("SHARED_LIB_CALLBACK_URL",		APP_PATH_WEBROOT_FULL . "redcap_v" . $redcap_version . "/SharedLibrary/receiver.php");
	
}


// Pull values from redcap_config table and set as global variables
function getConfigVals()
{
	$vars = array();
	$q = db_query("select * from redcap_config");
	if (!$q && basename($_SERVER['PHP_SELF']) != 'install.php') 
	{
		$installPage = (substr(basename(dirname($_SERVER['PHP_SELF'])), 0, 8) == 'redcap_v' 
						|| substr(basename(dirname(dirname($_SERVER['PHP_SELF']))), 0, 8) == 'redcap_v') 
						? '../install.php' : 'install.php';
		// If table doesn't exist or something is wrong with it, tell to re-install REDCap.
		print  "ERROR: Could not find the correct version of REDCap in the \"redcap_config\" table!<br><br>
				You may need to complete the <a href='$installPage'>installation</a>.";
		exit;
	}
	while ($row = db_fetch_assoc($q)) 
	{
		$vars[$row['field_name']] = $row['value'];
	}
	// If auto logout time is set to "0" (which means 'disabled'), then set to 1 day ("1440") as the upper limit.
	if ($vars['autologout_timer'] == '0')
	{
		$vars['autologout_timer'] = 1440;
	}
	// Return variables
	return $vars;
}
function setConfigVals() 
{
	foreach (getConfigVals() as $field_name=>$value)
	{
		// Set field as global variable
		$GLOBALS[$field_name] = $value;
		// If using a proxy server, set variable as a constant
		if ($field_name == 'proxy_hostname') 
		{
			define(PROXY_HOSTNAME, ($value == "" ? "" : trim($value)));
		}
	}
	// this *EXPERIMENTAL* code can cause *SYSTEM INSTABILITY* if set to true
	if (!array_key_exists('pub_matching_experimental', $GLOBALS))
		$GLOBALS['pub_matching_experimental'] = false;
}


// Pull values from redcap_projects table and set as global variables
function getProjectVals()
{
	$vars = array();
	// Query redcap_projects table for project-level values
	$sql  = "select SQL_CACHE * from redcap_projects where ";
	$sql .= isset($_GET['pnid']) ? "project_name = '" . prep($_GET['pnid']) . "'" : "project_id = " . $_GET['pid'];
	$q = db_query($sql);
	// If project doesn't exist, then redirect to Home page
	if (db_num_rows($q) < 1) return false;
	// Assign all redcap_projects table fields as variables and/or constants
	foreach (db_fetch_assoc($q) as $key => $value) 
	{
		if ($key != 'report_builder') {
			$value = html_entity_decode($value, ENT_QUOTES);
		}
		$vars[$key] = trim($value);
	}
	// Return variables
	return $vars;
}
function setProjectVals()
{
	$projectVals = getProjectVals();
	// If project doesn't exist, then redirect to Home page
	if ($projectVals === false) redirectHome();
	// Loop through all values and set as global variables
	foreach ($projectVals as $field_name=>$value)
	{
		$GLOBALS[$field_name] = $value;
	}
}


//Function uses resource link from query to EAV formatted table and outputs an array 
//with keys as 'record' and sub-arrays with keys as 'field_name' and value as 'value'
function eavDataArray($resource_link, $chkbox_fields = null) {
	// If array with of checkbox fields (with field_name as key and default value options of "0" as sub-array values) is not provided, then build one
	if (!isset($chkbox_fields) || $chkbox_fields == null) {
		$sql = "select field_name from redcap_metadata where project_id = " . PROJECT_ID . " and element_type = 'checkbox'";
		$chkboxq = db_query($sql);
		$chkbox_fields = array();
		while ($row = db_fetch_assoc($chkboxq)) {
			// Add field to list of checkboxes and to each field add checkbox choices
			foreach (parseEnum($row['element_enum']) as $this_value=>$this_label) {
				$chkbox_fields[$row['field_name']][$this_value] = "0";	
			}
		}	
	}
	// Add data from data table to array
	$result = array();
	$chkbox_values = array();
	while ($row = db_fetch_array($resource_link)) {		
		if (!isset($chkbox_fields[$row['field_name']])) {
			// Non-checkbox field
			$result[$row['record']][$row['field_name']] = $row['value'];	
		} else {
			// If a checkbox
			$chkbox_values[$row['record']][$row['field_name']][$row['value']] = "1";
		}
	}
	// Now loop through each record. First add default "0" values for checkboxes, then overlay with any "1"s (actual checks from earlier)
	foreach (array_keys($result) as $this_record) {
		// First add default "0" values to each record
		foreach ($chkbox_fields as $this_fieldname=>$this_choice_array) {
			$result[$this_record][$this_fieldname] = $this_choice_array;
		}
		// Now loop through $chkbox_values to overlay any checked values (i.e. 1's)
		foreach ($chkbox_values[$this_record] as $this_fieldname=>$this_choice_array) {
			foreach ($this_choice_array as $this_value=>$this_data_value) {
				// Make sure it's a real checkbox option and not some random data point that leaked in
				if (isset($chkbox_fields[$this_fieldname][$this_value])) {
					// Add checkbox data to data array
					$result[$this_record][$this_fieldname][$this_value] = $this_data_value;
				}
			}
		}
	}
	return $result;
}



//Function for logging events
function log_event($sql, $table, $event, $record, $display, $descrip="", $change_reason="") 
{
	global $user_firstactivity, $rc_connection;
	
	// Pages that do not have authentication that should have USERID set to [non-user]
	$nonAuthPages = array("SendIt/download.php", "PubMatch/index.php", "PubMatch/index_ajax.php");
	
	// Log the event in the redcap_log_event table
	$ts 	 	= str_replace(array("-",":"," "), array("","",""), NOW);
	$page 	 	= (defined("PAGE") ? PAGE : "");
	$userid		= (in_array(PAGE, $nonAuthPages)) ? "[non-user]" : (defined("USERID") ? USERID : "");
	$ip 	 	= (isset($userid) && $userid == "[survey respondent]") ? "" : getIpAddress(); // Don't log IP for survey respondents
	$event	 	= strtoupper($event);
	$event_id	= (isset($_GET['event_id']) && is_numeric($_GET['event_id'])) ? $_GET['event_id'] : "NULL";
	$project_id = defined("PROJECT_ID") ? PROJECT_ID : 0;
	
	// Query
	$sql = "INSERT INTO redcap_log_event 
			(project_id, ts, user, ip, page, event, object_type, sql_log, pk, event_id, data_values, description, change_reason) 
			VALUES ($project_id, $ts, '".prep($userid)."', ".checkNull($ip).", '$page', '$event', '$table', ".checkNull($sql).", 
			".checkNull($record).", $event_id, ".checkNull($display).", ".checkNull($descrip).", ".checkNull($change_reason).")";
	$q = db_query($sql, $rc_connection);
	
	// FIRST/LAST ACTIVITY TIMESTAMP: Set timestamp of last activity (and first, if applicable)
	if (defined("USERID") && strpos(USERID, "[") === false) 
	{
		// SET FIRST ACTIVITY TIMESTAMP: If this is the user's first activity to be logged in the log_event table, then log the time in the user_information table
		$sql_firstact = "";
		if (PAGE != 'Authentication/password_reset.php' // Make exception for this page where $user_firstactivity is always null (why?)
			&& (!isset($user_firstactivity) || (isset($user_firstactivity) && empty($user_firstactivity)))) 
		{
			$sql_firstact = ", user_firstactivity = '".NOW."'";
		}
		// SET LAST ACTIVITY TIMESTAMP
		$sql = "update redcap_user_information set user_lastactivity = '".NOW."' $sql_firstact 
				where username = '".prep(USERID)."' limit 1";
		db_query($sql, $rc_connection);
	}
	
	// Return true/false success for logged event
	return $q;
}

// Find real IP address of user
function getIpAddress() {
	return (empty($_SERVER['HTTP_CLIENT_IP']) ? (empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['REMOTE_ADDR'] : $_SERVER['HTTP_X_FORWARDED_FOR']) : $_SERVER['HTTP_CLIENT_IP']);
}

//Function for rounding up numbers (used for showing file sizes for File fields and prevents file sizes being "0 MB")
function round_up($value, $precision=2)
{	
	if ( $value < 0.01 )
	{
		return '0.01';
	}
	else
	{
		if (version_compare(PHP_VERSION, '5.3.0', '<')) {
			return round($value, $precision);
		} else {
			return round($value, $precision, PHP_ROUND_HALF_UP);
		}
	}
}



//Function for rendering the data entry form list on the right-hand menu
function renderFormMenuList($app_name,$fetched,$locked_forms,$hidden_edit,$entry_num,$visit_forms,$link_img="",$child_url="") 
{	
	global $surveys_enabled, $Proj, $user_rights, $longitudinal, $userid, $lang, $table_pk_label;
	
	// Collect string of html
	$html = "";
	
	// PARENT/CHILD: If this is a child of a shared parent project, make some accomodations
	if (($app_name != APP_NAME && !isset($_GET['child'])) || ($app_name == APP_NAME && isset($_GET['child']))) 
	{
		check_user_rights($app_name);
		$link_img = " <img src='".APP_PATH_IMAGES."link.png' class='imgfix' title='{$lang['config_functions_26']}' alt='{$lang['config_functions_26']}'>";
		if (isset($_GET['child'])) {
			$child_url = "&child=" . $_GET['child'];
		} else {
			$child_url = "&child=" . $_GET['pnid'];
		}
	}
	
	//Get project_id for this project (may be parent/child project)
	$project_id = ($app_name == APP_NAME) ? PROJECT_ID : db_result(db_query("select project_id from redcap_projects where project_name = '$app_name'"),0);	
	// Determine the current event_id (may change if using Parent/Child linking)
	$event_id   = ($app_name == APP_NAME) ? $_GET['event_id'] : getSingleEvent($project_id);
	
	//Build array with form_name, form_menu_description, and the form status value for this record
	if ($app_name == APP_NAME) {
		$form_names = $form_info = array();
		foreach ($Proj->forms as $form=>$attr) {
			$form_names[] = $form;
			$form_info[$form]['form_menu_description'] = $attr['menu'];	
			$form_info[$form]['form_status'] = 0;		
		}
	} else {
		// Get forms attributes for parent project
		$q = db_query("select form_name, form_menu_description from redcap_metadata where form_menu_description is not null 
						  and form_menu_description != '' and project_id = $project_id order by field_order");
		while ($row = db_fetch_array($q)) 
		{
			$form_names[] = $row['form_name'];
			$form_info[$row['form_name']]['form_menu_description'] = $row['form_menu_description'];
			$form_info[$row['form_name']]['form_status'] = 0;
		}
	}
	
	// Data entry page only
	if ((PAGE == "DataEntry/index.php" && isset($fetched))) 
	{
		// Adapt for Double Data Entry module
		if ($entry_num != "") {
			//This is #1 or #2 Double Data Entry person
			$fetched .= $entry_num;
		}
		// Insert form status values for each form if user is on data entry page
		$sql = "select field_name, value from redcap_data where project_id = $project_id and record = '".prep($fetched)."' and 
				event_id = $event_id and field_name in ('".implode("_complete','",$form_names)."_complete')";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q)) 
		{
			$form_info[substr($row['field_name'],0,-9)]['form_status'] = $row['value'];
		}			
		// Adapt for Double Data Entry module
		if ($entry_num != "") {
			//This is #1 or #2 Double Data Entry person
			$fetchedlink = RCView::escape(substr($fetched, 0, -3));
		} else {
			//Normal
			$fetchedlink = RCView::escape($fetched);
		}
	}
			
	// Determine if record also exists as a survey response for some instruments
	$surveyResponses = array();
	if (PAGE == "DataEntry/index.php" && isset($fetched) && $surveys_enabled) 
	{
		$surveyResponses = Survey::getResponseStatus($project_id, $fetched, $event_id);
	}
	
	//Loop through each form and display text and colored button
	foreach ($form_info as $form_name=>$info) 
	{
	    $menu_text = filter_tags($info['form_menu_description']);
		$menu_form_complete_field = $form_name . "_complete";
		$menu_page = APP_PATH_WEBROOT."DataEntry/index.php?pid=$project_id&page=$form_name";
		
		// Default
		$hold_color_link = "";
		$form_link_style = "style='vertical-align:middle;'";
		$iconTitle = "{$lang['bottom_48']} $table_pk_label $fetched";
		
		//Produce HTML for colored button if an existing record and on data entry page
		if (PAGE == "DataEntry/index.php" && isset($fetched)) 
		{
			// If it's a survey response, display different icons
			if (isset($surveyResponses[$fetched][$event_id][$form_name])) {			
				//Determine color of button based on response status
				switch ($surveyResponses[$fetched][$event_id][$form_name]) {
					case '2':
						$holder_color = APP_PATH_IMAGES . 'tick_circle_frame.png';
						$iconTitle = $lang['global_94'];
						break;
					default:
						$holder_color = APP_PATH_IMAGES . 'circle_orange_tick.png';
						$iconTitle = $lang['global_95'];
				}
			} else {			
				//Determine color of button based on form status value
				switch ($info['form_status']) {
					case '0':
						$holder_color = APP_PATH_IMAGES . 'circle_red.gif';
						$iconTitle = $lang['global_92'];
						break;
					case '1':
						$holder_color = APP_PATH_IMAGES . 'circle_yellow.png';
						$iconTitle = $lang['global_93'];
						break;
					case '2':
						$holder_color = APP_PATH_IMAGES . 'circle_green.png';
						$iconTitle = $lang['survey_28'];
				}
			}
			//HTML for colored button
			if ($hidden_edit) {
				$hold_color_link = "<a title='$iconTitle' href='$menu_page&id=$fetchedlink&event_id=$event_id{$child_url}'><img src='$holder_color' style='height:16px;width:16px;vertical-align:middle;'></a>";
			}
			// Check if this form in the menu is the current form
			if ($form_name == $_GET['page'] && $app_name == APP_NAME) {
				$form_link_style = "class='round' style='vertical-align:middle;background-color:#000066;color:#EEE;padding:1px 6px;'";
			}
		}
		
		// Set lock icon html, if record-event-form is locked
		$show_lock = isset($locked_forms[$form_name]) ? $locked_forms[$form_name] : "";
		
		//Display normal form links ONLY if user has rights to the form
		if (isset($user_rights['forms'][$form_name]) && $user_rights['forms'][$form_name] != "0") {
			//Display longitudinal form links ONLY if user has rights to the form and ONLY if supposed to be shown for this time-point
			if (!$longitudinal || ($longitudinal && isset($visit_forms[$form_name]))) {
				$html .= "<div class='hang' style='line-height:15px;margin-top:2px;margin-bottom:2px;'>$hold_color_link &nbsp;"
					   . "<a id='form[$form_name]' $form_link_style href='{$menu_page}{$child_url}&id={$fetchedlink}&event_id=$event_id'>$menu_text</a>{$show_lock}{$link_img}</div>";	
			}
		}
	}
	
	// Return form count and HTML
	return array(count($form_names), $html);
}



//Function for reformatting dates from YYYY-MM-DD format to MM/DD/YYYY
function format_date($this_date, $f="") {
	$ts = strtotime($this_date);
	if ($this_date == "") {
		return "";
	} elseif($f == "") {
		return substr($this_date,5,2) . "/" . substr($this_date,8,2) . "/" . substr($this_date,0,4);
	} else {
		$formatted_date = date($f,$ts);
		return $formatted_date;
	}
}

//Function for reformatting dates from MM/DD/YYYY format to YYYY-MM-DD
function format_date_dashes($this_date) {
	if ($this_date == "") {
		return "";
	} else {
		return substr($this_date,6,4) . "-" . substr($this_date,0,2) . "-" . substr($this_date,3,2);
	}
}


//Function to reformat Time from military time to am/pm format
function format_time($time, $f="") {
	if (strpos($time,":")) {
		list($hh,$mm) = explode(":",$time);
		if($f == "") {
			$hh += 0;
			if ($hh > 12) {
				$hh -= 12;
				$ampm = "pm";
			} elseif ($hh == 12) {
				$ampm = "pm";
			} else {
				$ampm = "am";
			}
			return (($hh == "0") ? "12" : $hh) . ":" . $mm . $ampm;
		}else {
			return date($f, mktime($hh,$mm));
		}
	} else {
		return "";
	}
}


//Format TS value from log_event table into readable timestamp value (HH:MM MM/DD/YYYY)
function format_ts($val) {
	return ($val == "") ? "" : format_time(substr($val, 8, 2) . ":" . substr($val, 10, 2)) . " " . substr($val, 4, 2) . "/" . substr($val, 6, 2) . "/" . substr($val, 0, 4);
}

//Format TS value from log_event table into readable timestamp value (MM/DD/YYYY HH:MM)
function format_ts2($val) {
	return ($val == "") ? "" : substr($val, 4, 2) . "/" . substr($val, 6, 2) . "/" . substr($val, 0, 4) . " " . format_time(substr($val, 8, 2) . ":" . substr($val, 10, 2));
}

//Format MySQL timestamp format into readable timestamp value (MM/DD/YYYY HH:MM)
function format_ts_mysql($val) {
	if (trim($val) == "") return "";
	return format_ts2(str_replace(array(":"," ","-"), array("","",""), $val));
}

//Format TS value from log_event table into readable timestamp value for Excel (i.e. YYYY-MM-DD HH:MM)
function format_ts_excel($val) {
	return ($val == "") ? "" : substr($val, 0, 4) . "-" . substr($val, 4, 2) . "-" . substr($val, 6, 2) . " " . substr($val, 8, 2) . ":" . substr($val, 10, 2);
}

//Function to return day of week for date in YYYY-MM-DD format
function getDay($YYYY_MM_DD) {
	if ($YYYY_MM_DD != "") {
		$start_month = substr($YYYY_MM_DD,5,2);
		$start_day 	 = substr($YYYY_MM_DD,8,2);
		$start_year	 = substr($YYYY_MM_DD,0,4);
		return date("l", mktime(0, 0, 0, $start_month, $start_day, $start_year));
	} else {
		return "";
	}
}

// Function to obtain current event_id from query string, or if does not exist, get first event_id
function getEventId() 
{
	global $Proj;
	// If we have event_id in URL
	if (isset($_GET['event_id']) && isset($Proj->eventInfo[$_GET['event_id']])) {
		return $_GET['event_id'];
	// If arm_id is in URL
	} elseif (isset($_GET['arm_id']) && is_numeric($_GET['arm_id'])) {
		return $Proj->getFirstEventIdArmId($_GET['arm_id']);
	// If arm is in URL
	} elseif (isset($_GET['arm']) && is_numeric($_GET['arm'])) {
		return $Proj->getFirstEventIdArm($_GET['arm']);
	// We have nothing so use first event_id in project
	} else {
		return $Proj->firstEventId;
	}
}

// Function to obtain current or lowest Arm number
function getArm() 
{
	// If we have event_id in URL
	if (isset($_GET['event_id']) && !isset($_GET['arm']) && is_numeric($_GET['event_id'])) {
		$arm = db_result(db_query("select arm_num from redcap_events_arms a, redcap_events_metadata e where a.arm_id = e.arm_id and e.event_id = " .$_GET['event_id']), 0);
	}
	// If we don't have arm in URL
	elseif ($_GET['arm'] == "" || !isset($_GET['arm']) || !is_numeric($_GET['arm'])) {
		$arm = db_result(db_query("select min(arm_num) from redcap_events_arms where project_id = " . PROJECT_ID), 0);
	} 
	// If arm is in URL
	else {
		$arm = $_GET['arm'];
	}
	// Just in case arm is blank somehow
	if ($arm == "" || !is_numeric($arm)) {
		$arm = 1;
	}
	return $arm;
}

// Function to obtain current arm_id, or if not current, the arm_id of lowest arm number
function getArmId($arm_id = null) 
{
	global $Proj;
	// Set default value
	$armIdValidated = false;
	// Determine arm_id if not provided
	if ($arm_id == null)
	{
		// If we have event_id in URL
		if (isset($_GET['event_id']) && !isset($_GET['arm_id']) && is_numeric($_GET['event_id'])) {
			$sql = "select a.arm_id from redcap_events_arms a, redcap_events_metadata e where a.project_id = " . PROJECT_ID . " 
					and a.arm_id = e.arm_id and e.event_id = " . $_GET['event_id'] . " limit 1";
			$q = db_query($sql);
			if (db_num_rows($q) > 0) {
				$arm_id = db_result($q, 0);
				$armIdValidated = true;
			}
		}
		// If arm is in URL
		elseif (isset($_GET['arm_id']) && is_numeric($_GET['arm_id'])) {
			$arm_id = $_GET['arm_id'];
		}
	}
	// Now validate the arm_id we have. If not valid, get the arm_id of lowest arm number
	if (!$armIdValidated) {
		// If arm_id/event_id is not in URL or arm_id is not numeric, then just return the arm_id of lowest arm number
		if (empty($arm_id) || !is_numeric($arm_id)) {
			$arm_id = $Proj->firstArmId;
		} 
		// Since we have an arm_id now, validate that it belongs to this project
		else {
			$sql = "select arm_id from redcap_events_arms where project_id = " . PROJECT_ID . " and arm_id = $arm_id";
			if (db_num_rows(db_query($sql)) < 1) {
				$arm_id = $Proj->firstArmId;
			}
		}
	}
	return $arm_id;
}

//Remove certain charcters from html strings to use in javascript (assumes will be put inside single quotes)
function cleanHtml($val,$remove_line_breaks=true) 
{
	if ($remove_line_breaks) {
		$repl = array("\r\n", "\r", "\n");
		$orig = array(" ", "", " ");
		$val = str_replace($repl, $orig, $val);
	}
	$repl = array("\t", "'", "  ", "  ");
	$orig = array(" ", "\'", " ", " ");
	return str_replace($repl, $orig, $val);
}

//Remove certain charcters from html strings to use in javascript (assumes will be put inside double quotes)
function cleanHtml2($val,$remove_line_breaks=true) 
{
	if ($remove_line_breaks) {
		$repl = array("\r\n", "\r", "\n");
		$orig = array(" ", "", " ");
		$val = str_replace($repl, $orig, $val);
	}
	$repl = array("\t", '"', "  ", "  ");
	$orig = array(" ", '\"', " ", " ");
	return str_replace($repl, $orig, $val);
}

//Function to render the page title/header for individual pages
function renderPageTitle($val = "") {
	if (isset($val) && $val != "") print  "<h3 style='color:#800000;max-width:700px;'>$val</h3>";	
}

// Function to parse string with fields inside [] brackets and return as array with fields
function getBracketedFields($val, $removeCheckboxBranchingLogicParentheses=true, $returnFieldDotEvent=false, $removeEvent=false) 
{
	global $longitudinal;
	$these_fields = array();
	// Collect all fields in brackets
	foreach (explode("|RCSTART|", preg_replace("/(\[[^\[]*\]\[[^\[]*\]|\[[^\[]*\])/", "|RCSTART|$1|RCEND|", $val)) as $this_section)
	{
		$endpos = strpos($this_section, "|RCEND|");
		if ($endpos === false) continue;
		$this_field = substr($this_section, 1, $endpos-2);
		$this_field = str_replace("][", ".", trim($this_field));
		// Do not include this field if is blank
		if ($this_field == "") continue;
		// Do not include this field if has unique event name in it and should not be returning unique event name
		if ($longitudinal && strpos($this_field, ".") !== false) {
			if (!$returnFieldDotEvent) {
				continue;
			} elseif ($removeEvent) {
				list ($this_event, $this_field) = explode(".", $this_field);
			}
		}
		//Insert field into array as key to store as unique
		$these_fields[$this_field] = "";
	}
	// Compensate for parentheses in checkbox logic
	if ($removeCheckboxBranchingLogicParentheses)
	{
		foreach ($these_fields as $this_field=>$nothing) 
		{
			if (strpos($this_field, "(") !== false) 
			{
				// Replace original with one that lacks parentheses
				list ($this_field2, $nothing2) = explode("(", $this_field, 2);
				unset($these_fields[$this_field]);
				$these_fields[$this_field2] = $nothing;
			}
		}
	}
	return $these_fields;
}


/*
 ** Give null value if equals "" (used inside queries)
 */
function checkNull($value) {
	if ($value != "") {
		return "'" . prep($value) . "'";
	} else {
		return "NULL";
	}
}


// DETERMINE IF SERVER IS A VANDERBILT SERVER
function isVanderbilt() 
{
	return (strpos($_SERVER['SERVER_NAME'], "vanderbilt.edu") !== false 
			// Add exception for C4 REDCap hosted at Vanderbilt
			|| strpos($_SERVER['SERVER_NAME'], ".ctsacentral.org") !== false);
}


/**
 * LINK TO RETURN TO PREVIOUS PAGE
 * $val corresponds to PAGE constant (i.e. relative URL from REDCap's webroot)
 */
function renderPrevPageLink($val) {
	global $lang;	
	if (isset($_GET['ref']) || $val != null) {
		$val = ($val == null) ? $_GET['ref'] : $val;
		if ($val == "") return;
		print  "<p style='margin:0;padding:10px;'>
					<img src='" . APP_PATH_IMAGES . "arrow_skip_180.png' class='imgfix'> 
					<a href='" . APP_PATH_WEBROOT . $val . (defined("PROJECT_ID") ? ((strpos($val, "?") === false ? "?" : "&") . "pid=" . PROJECT_ID) : "") . "' 
						style='color:#2E87D2;font-weight:bold;'>{$lang['config_functions_40']}</a>
				</p>";
	}
}

/**
 * BUTTON TO RETURN TO PREVIOUS PAGE
 * $val corresponds to PAGE constant (i.e. relative URL from REDCap's webroot)
 * If $val is not supplied, will use "ref" in query string.
 */
function renderPrevPageBtn($val,$label,$outputToPage=true,$btnClass='jqbutton') {
	global $lang;
	$button = "";
	if (isset($_GET['ref']) || $val != null) 
	{
		$val = ($val == null) ? cleanHtml(strip_tags(label_decode(urldecode($_GET['ref'])))) : $val;
		if ($val == "") return;
		// Set label
		$label = ($label == null) ? $lang['config_functions_40'] : $label;
		$button =  "<button class='$btnClass' style='font-family:arial;' onclick=\"window.location.href='" . 
						APP_PATH_WEBROOT . $val . (defined("PROJECT_ID") ? ((strpos($val, "?") === false ? "?" : "&") . "pid=" . PROJECT_ID) : "") . "';\">
						<img src='" . APP_PATH_IMAGES . "arrow_left.png' class='imgfix'> $label
					</button>";
	}
	// Render or return
	if ($outputToPage) {
		print $button;
	} else {
		return $button;
	}
}

/**
 * RENDER TABS FROM ARRAY WITH 'PAGE' AS KEY AND LABEL AS VALUE
 */
function renderTabs($tabs=array()) 
{
	// Get request URI
	$request_uri = $_SERVER['REQUEST_URI'];
	// If request URI ends with ".php?", then remove "?"
	if (substr($request_uri, -5) == '.php?') $request_uri = substr($request_uri, 0, -1);
	// Get query string parameters for the current page's URL
	$params = (strpos($request_uri, ".php?") === false) ? array() : explode("&", parse_url($request_uri, PHP_URL_QUERY));
	?>
	<div id="sub-nav" style="margin:5px 0 20px;">
		<ul>
			<?php 
			foreach ($tabs as $this_url=>$this_label) 
			{
				$this_page = parse_url($this_url, PHP_URL_PATH);
				// Parse any querystring params in $this_url and check for match to see if this should be the Active tab
				$these_params = (strpos($this_url, ".php?") === false) ? array() : explode("&", parse_url($this_url, PHP_URL_QUERY));
				// Add project_id if on a project-level page
				if (defined("PROJECT_ID")) {
					$these_params[] = "pid=" . PROJECT_ID;
				}
				// Format query string for the url to add 'pid'
				$this_url = parse_url($this_url, PHP_URL_PATH);
				if (!empty($these_params)) {
					$this_url .= "?" . implode("&", $these_params);
				}
				// Check for Active tab
				$isActive = false;
				if ($this_page == PAGE && count($these_params) == count($params)) {
					// Make sure all params are same. Loop till it finds mismatch.
					$isActive = true;
					foreach ($params as $this_param) {
						if (!in_array($this_param, $these_params)) $isActive = false;
					}
				}
				?>
				<li <?php if ($isActive) echo 'class="active"'?>>
					<a href="<?php echo APP_PATH_WEBROOT . $this_url ?>" style="font-size:13px;color:#393733;padding:6px 9px 5px 10px;"><?php echo $this_label ?></a>
				</li>
				<?php 
			} ?>		
		</ul>
	</div>
	<div class="clear"></div>
	<?php
}



/**
 * Run single-field query and return comma delimited set of values (to be used inside other query for better performance than using subqueries)
 */
function pre_query($sql, $conn=null) 
{
	if (trim($sql) == "" || $sql == null) return "''";
	$sql = html_entity_decode($sql, ENT_QUOTES);
	if ($conn == null) {
		$q = db_query($sql);
	} else {
		$q = db_query($sql, $conn);
	}
	$val = "";
	if ($q) {
		if (db_num_rows($q) > 0) {
			while ($row = db_fetch_array($q)) {
				$val .= "'" . prep($row[0]) . "', ";
			}
			$val = substr($val, 0, -2);
		}
	}
	return ($val == "") ? "''" : $val;
}

/**
 * Display query if query fails
 */
function queryFail($sql) {
	global $lang;
	exit("<p><b>{$lang['config_functions_41']}</b><br>$sql</p>");
}

/**
 * Returns first event_id of a project (if specify arm number, returns first event for that arm)
 */
function getSingleEvent($this_project_id, $arm_num = NULL) {
	if (!is_numeric($this_project_id)) return false;
	$sql = "select m.event_id from redcap_events_metadata m, redcap_events_arms a where a.arm_id = m.arm_id 
			and a.project_id = $this_project_id";
	if (is_numeric($arm_num)) $sql .= " and a.arm_num = $arm_num";
	$sql .= " order by a.arm_num, m.day_offset, m.descrip limit 1";
	return db_result(db_query($sql), 0);
}

/**
 * Retrieve logging-related info when adding/updating/deleting calendar events using the cal_id
 */
function calLogChange($cal_id) {
	if ($cal_id == "" || $cal_id == null || !is_numeric($cal_id)) return "";
	$logtext = array();
	$sql = "select c.*, (select m.descrip from redcap_events_metadata m, redcap_events_arms a where a.project_id = c.project_id 
			and m.event_id = c.event_id and a.arm_id = m.arm_id) as descrip from redcap_events_calendar c where c.cal_id = $cal_id limit 1";
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q)) {
		if ($row['record']     != "") $logtext[] = "Record: ".$row['record'];
		if ($row['descrip']    != "") $logtext[] = "Event: ".$row['descrip'];
		if ($row['event_date'] != "") $logtext[] = "Date: ".format_date($row['event_date']);
		if ($row['event_time'] != "") $logtext[] = "Time: ".format_time($row['event_time']);
		// Only display status change if event was scheduled (status is not listed for ad hoc events)
		if ($row['event_status'] != "" && $row['event_id'] != "") {
			switch ($row['event_status']) {
				case '0': $logtext[] = "Status: Due Date"; break;
				case '1': $logtext[] = "Status: Scheduled"; break;
				case '2': $logtext[] = "Status: Confirmed"; break;
				case '3': $logtext[] = "Status: Cancelled"; break;
				case '4': $logtext[] = "Status: No Show";
			}
		}
	}	
	return implode(", ", $logtext);
}


/**
 * Retrieve logging-related info when adding/updating/deleting Events on Define My Events page using the event_id
 */
function eventLogChange($event_id) {
	if ($event_id == "" || $event_id == null || !is_numeric($event_id)) return "";
	$logtext = array();
	$sql = "select * from redcap_events_metadata m, redcap_events_arms a where m.event_id = $event_id and a.arm_id = m.arm_id limit 1";
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q)) {
		$logtext[] = "Event: ".$row['descrip'];		
		// Display arm name if more than one arm exists
		$armCount = db_result(db_query("select count(1) from redcap_events_arms where project_id = ".PROJECT_ID), 0);
		if ($armCount > 1) $logtext[] = "Arm: ".$row['arm_name'];		
		$logtext[] = "Days Offset: ".$row['day_offset'];		
		$logtext[] = "Offset Range: -{$row['offset_min']}/+{$row['offset_max']}";
	}	
	return implode(", ", $logtext);
}

// Decode limited set of html special chars rather than using html_entity_decode
function label_decode($val) {
	// Static arrays used for character replacing in labels/notes 
	// (user str_replace instead of html_entity_decode because users may use HTML char codes in text for foreign characters)
	$orig_chars = array("&amp;","&#38;","&#34;","&quot;","&#39;","&#039;","&#60;","&lt;","&#62;","&gt;");
	$repl_chars = array("&"    ,"&"    ,"\""   ,"\""    ,"'"    ,"'"     ,"<"    ,"<"   ,">"    ,">"   );
	$val = str_replace($orig_chars, $repl_chars, $val);
	// If < character is followed by a number or equals sign, which PHP will strip out using striptags, add space after < to prevent string truncation.
	if (strpos($val, "<") !== false) {
		if (strpos($val, "<=") !== false) {
			$val = str_replace("<=", "< =", $val);
		}
		$val = preg_replace("/(<)([0-9])/", "< $2", $val);
	}
	return $val;
}

// Gets all dates between two dates (including those two) in YYYY-MM-DD format and returns as an array with 0 as values and dates as keys
function getDatesBetween($date1, $date2) {
	$startMM   = substr($date1, 5, 2);
	$startDD   = substr($date1, 8, 2);
	$startYYYY = substr($date1, 0, 4);
	$startDate = date("Y-m-d", mktime(0, 0, 0, $startMM, $startDD, $startYYYY));
	$endDate   = date("Y-m-d", mktime(0, 0, 0, substr($date2, 5, 2), substr($date2, 8, 2), substr($date2, 0, 4)));
	$all_dates = array();
	$temp = "";
	$i = 0;
	while ($temp != $endDate) {
		$temp = date("Y-m-d", mktime(0, 0, 0, $startMM, $startDD+$i, $startYYYY));
		$all_dates[$temp] = 0;
		$i++;
	};
	return $all_dates;
}

/**
 * Function for rendering a YUI line chart
 */
function yui_chart($id,$title,$width,$height,$query,$base_count=0,$date_limit,$isDateFormat=true,$isCumulative=true) {
	
	// print '
	// <div style="width:'.$width.'px;border:1px solid #000000;background-color:#f7f7f7;margin-bottom:30px;">
		// <div style="text-align:center;font-weight:bold;font-size:14px;padding:5px;">'.$title.'</div>
		// <div id="'.$id.'" style="width:'.$width.'px;height:'.$height.'px;">Requires Flash Player 9.0.45 or higher. <a href="http://www.adobe.com/go/getflashplayer">Download it</a></div>
	// </div>';
	print '
	$(function(){
		YAHOO.widget.Chart.SWFURL = "'.APP_PATH_WEBROOT.'Resources/js/assets/charts.swf";
		YAHOO.example.datelyExpenses =
		[';
	
	//Use counter for cumulative counts
	$ycount_total = $base_count;
	
	//Collect all dates in array where place holders of 0 have already been inserted
	$all_dates = array();
	// If first query field is in date format (YYYY-MM-DD), then prefill the array with zero values for all dates in the range
	if ($isDateFormat) {
		$all_dates = getDatesBetween($date_limit, date("Y-m-d"));
	}
	// Execute the query to pull the data for the chart
	$q = db_query($query);
	$xfieldname = db_field_name($q, 0);
	$yfieldname = db_field_name($q, 1);
	// Put all queried data into array
	while ($row = db_fetch_array($q)) {
		$all_dates[$row[$xfieldname]] = $row[$yfieldname];
	}
	
	//Loop through array to render each date for display
	$prev_count = $ycount_total;
	foreach ($all_dates as $this_date=>$this_count) {
		if ($this_count == 0) continue;
		if ($isCumulative) {
			$this_count += $prev_count;
			$prev_count = $this_count;
		}
		print "\n{ $xfieldname:\"$this_date\",$yfieldname:$this_count },";
	}
	
	//Get minimum to start with (calculate suitable minimum based on current min and max values)
	$decimal_round = pow(10, strlen($ycount_total - $base_count) - 1);
	$minimum = floor($base_count / $decimal_round) * $decimal_round;
	
	print '];
		var myDataSource = new YAHOO.util.DataSource( YAHOO.example.datelyExpenses );
		myDataSource.responseType = YAHOO.util.DataSource.TYPE_JSARRAY;
		myDataSource.responseSchema =
		{
			fields: [ "'.db_field_name($q,0).'", "'.db_field_name($q,1).'"]
		};
		var seriesDef = 
		[
			{ 	
				displayName: "'.db_field_name($q,1).'", 
				yField: "'.db_field_name($q,1).'",
				style: { color: "800000", size: 0 }  
			}
		];		
		YAHOO.example.formatCurrencyAxisLabel = function( value )
		{
			return YAHOO.util.Number.format( value,
			{
				prefix: "",
				thousandsSeparator: ",",
				decimalPlaces: 0
			});
		}		
		YAHOO.example.getDataTipText = function( item, index, series )
		{
			var toolTipText = series.displayName + " for " + item.'.db_field_name($q,0).';
			toolTipText += "\n" + YAHOO.example.formatCurrencyAxisLabel( item[series.yField] );
			return toolTipText;
		}
		var currencyAxis = new YAHOO.widget.NumericAxis();
		currencyAxis.labelFunction = YAHOO.example.formatCurrencyAxisLabel;
		currencyAxis.minimum = '.$minimum.'; 
		var mychart = new YAHOO.widget.LineChart( "'.$id.'", myDataSource,
		{
			series: seriesDef,
			xField: "'.db_field_name($q,0).'",
			yAxis: currencyAxis,
			dataTipFunction: YAHOO.example.getDataTipText,
			style:
			{
				padding: 15,
				background:
				{
					color: "f7f7f7"
				}
			},
			expressInstall: "assets/expressinstall.swf"
		});
	});';

}


/**
 * FOR A STRING, CONVERT ALL LINE BREAKS TO SPACES, THEN REPLACE MULTIPLE SPACES WITH SINGLE SPACES, THEN TRIM
 */
function remBr($val) {
	// Replace line breaks with spaces
	$br_orig = array("\r\n", "\r", "\n");
	$br_repl = array(" ", " ", " ");
	$val = str_replace($br_orig, $br_repl, $val);
	// Replace multiple spaces with single spaces
	$val = preg_replace('/\s+/', ' ', $val);
	// Trim and return
	return trim($val);
}


/**
 * Print an array (for debugging purposes)
 */
function print_array($array) {
	print "<br><pre>\n";print_r($array);print "\n</pre>\n";
}

/**
 * DISPLAY ERROR MESSAGE IF CURL MODULE NOT LOADED IN PHP
 */
function curlNotLoadedMsg() {
	global $lang;
	
	print  "<div class='red'>
				<img src='".APP_PATH_IMAGES."exclamation.png' class='imgfix'> <b>{$lang['global_01']}:</b><br>
				{$lang['config_functions_42']} 
				<a href='http://curl.haxx.se/libcurl/php/' target='_blank' style='text-decoration:underline;'>{$lang['config_functions_43']}</a>. 
				{$lang['config_functions_44']} 
				<a href='http://us.php.net/manual/en/book.curl.php' target='_blank' style='text-decoration:underline;'>http://us.php.net/manual/en/book.curl.php</a>.
			</div>";
}

/**
 * CALCULATES SIZE OF WEB SERVER DIRECTORY (since disk_total_space() function is not always reliable)
 */
function dir_size($dir) {
	$retval = 0;
	$dirhandle = opendir($dir);
	while ($file = readdir($dirhandle)) {
		if ($file != "." && $file != "..") {
			if (is_dir($dir."/".$file)) {
				$retval = $retval + dir_size($dir."/".$file);
			} else {
				$retval = $retval + filesize($dir."/".$file);
			}
		}
	}
	closedir($dirhandle);
	return $retval;
} 


/**
 * CLEAN BRANCHING LOGIC OR CALC FIELD EQUATION OF ANY ERRORS IN FIELD NAME SYNTAX AND RETURN CLEANED STRING
 */
function cleanBranchingOrCalc($val) {
	return preg_replace_callback("/(\[)([^\[]*)(\])/", "branchingCleanerCallback", $val);
}
// Callback function used when cleaning branching logic
function branchingCleanerCallback($matches) {
	return "[" . preg_replace("/[^a-z0-9A-Z\(\)_-]/", "", str_replace(" ", "", $matches[0])) . "]";
}


/**
 * PARSE THE ELEMENT_ENUM COLUMN FROM METADATA TABLE AND RETURN AS ARRAY 
 * (WITH CODED VALUES AS KEY AND LABELS AS ELEMENTS)
 */
function parseEnum($select_choices = "") {
	if (trim($select_choices) == "") return array();
	$array_to_fill = array();
	// Catch any line breaks (mistakenly saved instead of \n literal string)
	$select_choices = str_replace("\n", "\\n", $select_choices);
	$select_array = explode("\\n", $select_choices);
	// Loop through each choice
	foreach ($select_array as $key=>$value) {
		if (strpos($value,",") !== false) {
			$pos = strpos($value, ",");
			$this_value = trim(substr($value,0,$pos));
			$this_text = trim(substr($value,$pos+1));
		} else {
			$this_value = $this_text = trim($value);
		}
		$array_to_fill[$this_value] = $this_text;
	}
	return $array_to_fill;
}

/** 
 * RETRIEVE ALL CHECKBOX FIELDNAMES 
 * (put in array as keys with element_enum as array elements OR set to return with "0" as array elements)
 */
function getCheckboxFields($defaults = false, $metadata_table = "redcap_metadata") {	
	$sql = "select field_name, element_enum from $metadata_table where project_id = " . PROJECT_ID . " and element_type = 'checkbox'";
	$chkboxq = db_query($sql);
	$chkbox_fields = array();
	while ($row = db_fetch_assoc($chkboxq)) {
		// Add field to list of checkboxes and to each field add checkbox choices
		foreach (parseEnum($row['element_enum']) as $this_value=>$this_label) {
			$chkbox_fields[$row['field_name']][$this_value] = ($defaults ? "0" : html_entity_decode($this_label, ENT_QUOTES));	
		}
	}
	return $chkbox_fields;
}

/** 
 * RETRIEVE ACKNOWLEDGEMENT/COPYRIGHT FOR A FORM FROM THE LIBRARY_MAP TABLE (OR CALL IT FROM LIBRARY SERVER IF EXPIRED)
 */
function getAcknowledgement($project_id,$formName) {
	//if necessary, convert the project name to project id
	if (!is_numeric($project_id)) {
		$sqlCheck = "select project_id from redcap_projects where project_name = '$project_id'";
		$resCheck = db_query($sqlCheck);
		if($row = db_fetch_array($resCheck)) {
			$project_id = $row['project_id'];
		}
	}
	//get the acknowledgement form the local project
	$getLibInfo =  "select library_id, acknowledgement, acknowledgement_cache " .
			       "from redcap_library_map " .
			       "where project_id = $project_id and form_name = '$formName' and type = 1";
	$result = db_query($getLibInfo);
	if($row = db_fetch_array($result)) {
		$libId = $row['library_id'];
		$ack = $row['acknowledgement'];
		$ack_cache = strtotime($row['acknowledgement_cache']);
		$now = time();
		$difference = floor(($now - $ack_cache)/(60*60*24));
		//check if local copy is expired (30 days) and update if necessary
		if($difference > 30) {
			$curlAck = curl_init();
			curl_setopt($curlAck, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($curlAck, CURLOPT_VERBOSE, 1);
			curl_setopt($curlAck, CURLOPT_URL, SHARED_LIB_DOWNLOAD_URL.'?attr=acknowledgement&id='.$libId);
			curl_setopt($curlAck, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curlAck, CURLOPT_POST, false);
			curl_setopt($curlAck, CURLOPT_PROXY, PROXY_HOSTNAME); // If using a proxy
			$ack = curl_exec($curlAck);
			$updateSql = "update redcap_library_map " .
					     "set acknowledgement = '$ack', acknowledgement_cache = '".NOW."' " .
			             "where project_id = $project_id and form_name = '$formName' and type = 1";
			db_query($updateSql);
		}
		return filter_tags(label_decode($ack));
	}
	return "";
}


/**
 * PRINT VALUES OF AN EMAIL (OFTEN DISPLAYED WHEN THERE IS ERROR SENDING EMAIL)
 */
function printEmail($to, $from, $subject, $body) {
	?>
	<p>
		<b>To:</b> <?php echo $to ?><br>
		<b>From:</b> <?php echo $from ?><br>
		<b>Subject:</b> <?php echo $subject ?><br>
		<b>Message:</b><br><?php echo $body ?>
	</p>
	<?php
}

/** 
 * DETERMINE MAXIMUM SIZE OF FILES THAT CAN BE UPLOADED TO WEB SERVER (IN MB)
 */
function maxUploadSize() {
	// Get server max (i.e. the lowest of two different server values)
	$max_filesize = (ini_get('upload_max_filesize') != "") ? preg_replace("/[^0-9]/", "", ini_get('upload_max_filesize')) : 1;	
	$max_postsize = (ini_get('post_max_size') 		!= "") ? preg_replace("/[^0-9]/", "", ini_get('post_max_size')) 	  : 1;
	return (($max_filesize > $max_postsize) ? $max_postsize : $max_filesize);
}
function maxUploadSizeFileRespository() {
	global $file_repository_upload_max;
	$file_repository_upload_max = trim($file_repository_upload_max);
	// Get server max (i.e. the lowest of two different server values)
	$server_max = maxUploadSize();
	// Check if we need to use manually set upload max instead
	if ($file_repository_upload_max != "" && is_numeric($file_repository_upload_max) && $file_repository_upload_max < $server_max) {
		return $file_repository_upload_max;
	} else {
		return $server_max;
	}
}
function maxUploadSizeEdoc() {
	global $edoc_upload_max;
	$edoc_upload_max = trim($edoc_upload_max);
	// Get server max (i.e. the lowest of two different server values)
	$server_max = maxUploadSize();
	// Check if we need to use manually set upload max instead
	if ($edoc_upload_max != "" && is_numeric($edoc_upload_max) && $edoc_upload_max < $server_max) {
		return $edoc_upload_max;
	} else {
		return $server_max;
	}
}
function maxUploadSizeSendit() {
	global $sendit_upload_max;
	$sendit_upload_max = trim($sendit_upload_max);
	// Get server max (i.e. the lowest of two different server values)
	$server_max = maxUploadSize();
	// Check if we need to use manually set upload max instead
	if ($sendit_upload_max != "" && is_numeric($sendit_upload_max) && $sendit_upload_max < $server_max) {
		return $sendit_upload_max;
	} else {
		return $server_max;
	}
}

/** 
 * Ensure that the record identifier field has a field order of "1"
 */
function checkTablePkOrder() {

	global $Proj;
	
	// Only perform is first field's order != 1
	if ($Proj->table_pk_order != "1")
	{
		// Set up all actions as a transaction to ensure everything is done here
		db_query("SET AUTOCOMMIT=0");
		db_query("BEGIN");
		// Counters
		$counter = 1;
		$errors = 0;
		// Go through all metadata and reset field_order of all fields, beginning with "1"
		$sql = "select field_name, field_order from redcap_metadata where project_id = " . PROJECT_ID . " order by field_order";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q)) 
		{
			// Set field's new field_order if incorrect
			if ($row['field_order'] != $counter) 
			{
				$q2 = db_query("update redcap_metadata set field_order = $counter where project_id = " . PROJECT_ID . " and field_name = '{$row['field_name']}'");
				if (!$q2)
				{
					$errors++;
				}
			}
			// Increment counter
			$counter++;
		}
		// If errors, do not commit
		$commit = ($errors > 0) ? "ROLLBACK" : "COMMIT";
		db_query($commit);
		// Set back to initial value
		db_query("SET AUTOCOMMIT=1");
	}
	
}


// Retrieve list of all files and folders within a server directory, sorted alphabetically (output as array)
function getDirFiles($dir) {
	if (is_dir($dir)) {
		$dh = opendir($dir); 
		$files = array();
		while (false !== ($filename = readdir($dh))) { 
			if ($filename != "." && $filename != "..") {
				$files[] = $filename;
			}
		}
		sort($files);
		return $files;
	} else {
		return false;
	}
}

// Output the values from a SQL field type query as an enum string
function getSqlFieldEnum($element_enum) 
{
	//If one field in query, then show field as both coded value and displayed text.
	//If two fields in query, then show first as coded value and second as displayed text.
	if (strtolower(substr(trim($element_enum), 0, 7)) == "select ") 
	{
		$element_enum = html_entity_decode($element_enum, ENT_QUOTES);
		$rs_temp1_sql = db_query($element_enum);
		if (!$rs_temp1_sql) return "";
		$first_field  = db_field_name($rs_temp1_sql, 0);
		$second_field = db_field_name($rs_temp1_sql, 1);
		$string_record_select1 = "";
		while ($row = db_fetch_assoc($rs_temp1_sql)) 
		{
			$string_record_select1 .= str_replace(",", "&#44;", $row[$first_field]);
			if ($second_field == "" || $second_field == null) {
				$string_record_select1 .= " \\n ";
			} else {							
				$string_record_select1 .= ", " . str_replace(",", "&#44;", $row[$second_field]) . " \\n ";
			}
		}
		return substr($string_record_select1, 0, -4);
	}
	return "";
}

// Function for encrypting
function encrypt($data) 
{
	if (!mcrypt_loaded()) return false;
	// $salt from db connection file
	global $salt; 
	// Define an encryption/decryption variable beforehand
	defined("MCRYPT_IV") or define("MCRYPT_IV", mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND));
	// Encrypt and return
	return base64_encode(trim(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $salt, $data, MCRYPT_MODE_ECB, MCRYPT_IV)));
}

// Function for decrypting
function decrypt($encrypted_data) 
{
	if (!mcrypt_loaded()) return false;
	// $salt from db connection file
	global $salt; 
	// Define an encryption/decryption variable beforehand
	defined("MCRYPT_IV") or define("MCRYPT_IV", mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND));
	// Decrypt and return
	return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $salt, base64_decode($encrypted_data), MCRYPT_MODE_ECB, MCRYPT_IV));
}

// Function for checking if mcrypt PHP extension is loaded
function mcrypt_loaded($show_error=false) {
    if ((extension_loaded('mcrypt') || (function_exists('dl') && dl(((PHP_SHLIB_SUFFIX === 'dll') ? 'php_' : '') . (null ? null : 'mcrypt') . '.' . PHP_SHLIB_SUFFIX))) != 1) {
		if ($show_error) {
			exit('<div class="red"><b>ERROR:</b><br>The "mcrypt" PHP extension is not loaded but is required for encryption/decryption.<br>
				  Please install the PHP extension "mcrypt" on your server, reboot your server, and then reload this page.</div>');
		} else {
			return false;
		}
	} else {
		return true;
	}
}

// Checks if username and password are valid without disrupting existing REDCap authentication session
function fakeUserLoginForm() { return; }
function checkUserPassword($username, $password, $authSessionName = "login_test")
{					
	global $auth_meth, $mysqldsn, $ldapdsn;
	
	// Get current session_id, which will get inevitably changed if auth is successful
	$old_session_id = substr(session_id(), 0, 32);
	
	// Defaults
	$authenticated = false;
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

	//if ldap and table authentication Loop through the available servers & authentication methods
	foreach ($dsn as $key=>$dsnvalue) 
	{	
		if (isset($a)) unset($a);
		$a = new Auth($dsnvalue['type'], $dsnvalue['dsnstuff'], "fakeUserLoginForm");
		$a->setSessionName($authSessionName);
		$a->start();
		if ($a->getAuth()) {
			$authenticated = true;
		}
	}

	// Now that we're done, remove this part of the session to prevent conflict with REDCap user sessioning
	unset($_SESSION['_auth_'.$authSessionName]);
	
	// Because the session_id inevitably changes with this new auth session, change the session_id in log_view table
	// for all past page views during this session in order to maintain consistency of having one session_id per session.
	$new_session_id = substr(session_id(), 0, 32);
	if ($old_session_id != $new_session_id)
	{
		// Only check within past 24 hours (to reduce query time)
		$oneDayAgo = date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s"),date("m"),date("d")-1,date("Y")));
		$sql = "update redcap_log_view set session_id = '$new_session_id' where user = '".USERID."' 
				and session_id = '$old_session_id' and ts > '$oneDayAgo'";
		db_query($sql);
	}
	
	// Return value as true/false
	return $authenticated;

}

	
// Obtain web path to REDCap version folder
function getVersionFolderWebPath()
{
	global $redcap_version;
	
	// Parse through URL to find version folder path
	$found_version_folder = false;
	$url_array = array();
	foreach (array_reverse(explode("/", PAGE_FULL)) as $this_part)
	{
		if ($this_part == "redcap_v" . $redcap_version) 
		{
			$found_version_folder = true;
		}
		if ($found_version_folder)
		{
			$url_array[] = $this_part;
		}
	}
	// If ABOVE the version folder
	if (empty($url_array))
	{
		// First, make special exception if this is the survey page (i.e. .../[redcap]/surveys/index.php)
		$surveyPage  = "/surveys/index.php";
		$apiHelpPage = "/api/help/index.php";
		if (substr(PAGE_FULL, -1*strlen($surveyPage)) == $surveyPage)
		{
			return ((strlen(dirname(dirname(PAGE_FULL))) <= 1) ? "" : dirname(dirname(PAGE_FULL))) . "/redcap_v" . $redcap_version . "/";
		}
		// Check if this is the API Help file
		elseif (substr(PAGE_FULL, -1*strlen($apiHelpPage)) == $apiHelpPage)
		{
			return ((strlen(dirname(dirname(dirname(PAGE_FULL)))) <= 1) ? "" : dirname(dirname(dirname(PAGE_FULL)))) . "/redcap_v" . $redcap_version . "/";
		}
		// If user is above the version folder (i.e. /redcap/index.php, /redcap/plugins/example.php)
		else
		{
			// If 'redcap' folder is not seen in URL, then the version folder is in the server web root
			if (strlen(dirname(PAGE_FULL)) <= 1) {
				return "/redcap_v" . $redcap_version . "/";
			// This is the index.php page above the version folder
			} elseif (defined('PAGE')) {
				return dirname(PAGE_FULL) . "/redcap_v" . $redcap_version . "/";			
			// Since the version folder is not one or two directories above, find it manually using other methods
			} else {
				// Make sure allow_url_fopen is enabled, else we can't properly find the version folder
				if (ini_get('allow_url_fopen') != '1')
				{
					exit('<p style="font-family:arial;max-width:800px;"><b>Your web server does NOT have the PHP setting "allow_url_fopen" enabled.</b><br> 
						REDCap cannot properly process this page because "allow_url_fopen" is not enabled.
						To enable "allow_url_fopen", simply open your web server\'s PHP.INI file for editing and change the value of "allow_url_fopen" to 
						<b>On</b>. Then reboot your web server and reload this page.</p>');
				}
				// Try to find the file database.php in every directory above the current directory until it's found
				$revUrlArray = array_reverse(explode("/", PAGE_FULL));
				// Remove unneeded array elements
				array_pop($revUrlArray);
				array_shift($revUrlArray);
				// Loop through the array till we find the location of the version folder to return
				foreach ($revUrlArray as $key=>$urlPiece)
				{
					// Set subfolder path
					$subfolderPath = implode("/", array_reverse($revUrlArray));
					// Set the possible path of where to search for database.php
					$dbWebPath = (SSL ? "https" : "http") . "://" . SERVER_NAME . "$port/$subfolderPath/database.php";
					// Try to call database.php to see if it exists
					$dbWebPathContents = file_get_contents($dbWebPath);
					// If we found database.php, then return the proper path of the version folder
					if ($dbWebPathContents !== false) {
						return "/$subfolderPath/redcap_v" . $redcap_version . "/";
					}
					// Unset this array element so it does not get reused in the next loop
					unset($revUrlArray[$key]);
				}
				// Version folder was NOT found
				return "/redcap_v" . $redcap_version . "/";
			}
		}
	}
	// If BELOW the version folder
	else
	{
		return implode("/", array_reverse($url_array)) . "/";
	}	
}

// Render ExtJS-like panel
function renderPanel($title, $html, $id="")
{
	$id = ($id == "") ? "" : " id=\"$id\"";
	return '<div class="x-panel"'.$id.'>'
		 . ((trim($title) == '') ? '' : '<div class="x-panel-header x-panel-header-leftmenu">' . $title .'</div>')
		 . '<div class="x-panel-bwrap"><div class="x-panel-body"><div class="menubox">' . $html . '</div></div></div></div>';
}

// Render ExtJS-like grid/table
function renderGrid($id, $title, $width_px='auto', $height_px='auto', $col_widths_headers=array(), &$row_data=array(), $show_headers=true, $enable_header_sort=true, $outputToPage=true)
{
	## SETTINGS
	// $col_widths_headers = array(  array($width_px, $header_text, $alignment, $data_type), ... );  
	// $data_type = 'string','int','date'
	// $row_data = array(  array($col1, $col2, ...), ... );
	
	// Set dimensions and settings
	$width = is_numeric($width_px) ? "width: " . $width_px . "px;" : "width: 100%;";
	$height = ($height_px == 'auto') ? "" : "height: " . $height_px . "px; overflow-y: auto;";
	if (trim($id) == "") {
		$id = substr(md5(rand()), 0, 8);
	}
	$table_id_js = "table-$id";
	$table_id = "id=\"$table_id_js\"";
	$id = "id=\"$id\"";
	
	// Check column values
	$row_settings = array();
	foreach ($col_widths_headers as $this_key=>$this_col) 
	{ 
		$this_width  = is_numeric($this_col[0]) ? $this_col[0] . "px" : "100%";
		$this_header = $this_col[1];
		$this_align  = isset($this_col[2]) ? $this_col[2] : "left";
		$this_type   = isset($this_col[3]) ? $this_col[3] : "string";
		// Re-assign checked values
		$col_widths_headers[$this_key] = array($this_width, $this_header, $this_align, $this_type);
		// Add width and alignment to other array (used when looping through each row)
		$row_settings[] = array('width'=>$this_width, 'align'=>$this_align);
	}
	
	// Render grid
	$grid = '
	<div class="flexigrid" ' . $id . ' style="' . $width . $height .'">
		<div class="mDiv">
			<div class="ftitle" ' . ((trim($title) != "") ? "" : 'style="display:none;"') . '>'.$title.'</div>
		</div>
		<div class="hDiv" ' . ($show_headers ? "" : 'style="display:none;"') . '>
			<div class="hDivBox">
				<table cellspacing="0">
					<tr>';
	foreach ($col_widths_headers as $col_key=>$this_col) 
	{
		$grid .= 	   '<th' . ($enable_header_sort ? " onclick=\"SortTable('$table_id_js',$col_key,'{$this_col[3]}');\"" : "") . ($this_col[2] == 'left' ? '' : ' align="'.$this_col[2].'"') . '>
							<div style="' . ($this_col[2] == 'left' ? '' : 'text-align:'.$this_col[2].';') . ';width:' . $this_col[0] . '">
								' . $this_col[1] . '
							</div>
						</th>';
	}
	$grid .= 	   '</tr>
				</table>
			</div>
		</div>
		<div class="bDiv">
			<table ' . $table_id . ' cellspacing="0">';
	foreach ($row_data as $row_key=>$this_row) 
	{
		$grid .= '<tr' . ($row_key%2==0 ? '' : ' class="erow"') . '>';
		foreach ($this_row as $col_key=>$this_col) 
		{
			$grid .= '<td' . ($row_settings[$col_key]['align'] == 'left' ? '' : ' align="'.$row_settings[$col_key]['align'].'"') . '>
						<div ';
			if ($row_settings[$col_key]['align'] == 'center') {
				$grid .= 'class="fc" ';
			} elseif ($row_settings[$col_key]['align'] == 'right') {
				$grid .= 'class="fr" ';
			}
			$grid .= 'style="width:' . $row_settings[$col_key]['width'] . ';">' . $this_col . '</div>
					  </td>';
		}
		$grid .= '</tr>';
		// Delete last row to clear up memory as we go
		unset($row_data[$row_key]);
	}
	$grid .= '</table>
		</div>
	</div>
	';
	
	// Render grid (or return as html string)
	if ($outputToPage) {
		print $grid;
		unset($grid);
	} else {
		return $grid;
	}
}

// Returns HTML table from an SQL query (can include title to display)
function queryToTable($sql,$title="",$outputToPage=false,$tableWidth=null)
{	
	global $lang;
	$QQuery = db_query($sql);
	$num_rows = db_num_rows($QQuery);
	$num_cols = db_num_fields($QQuery);
	$failedText = ($QQuery ? "" : "<span style='color:red;'>ERROR - Query failed!</span>");
	$tableWidth = (is_numeric($tableWidth) && $tableWidth > 0) ? "width:{$tableWidth}px;" : "";
	
	$html_string = "<table class='dt2' style='font-family:Verdana;font-size:11px;$tableWidth'>
						<tr class='grp2'><td colspan='$num_cols'>
							<div style='color:#800000;font-size:14px;max-width:700px;'>$title</div>
							<div style='font-size:11px;padding:12px 0 3px;'>
								<b>{$lang['custom_reports_02']}&nbsp; <span style='font-size:13px;color:#800000'>$num_rows</span></b>
								$failedText
							</div>
						</td></tr>
						<tr class='hdr2' style='white-space:normal;'>";
							
	if ($num_rows > 0) {
		
		// Display column names as table headers
		for ($i = 0; $i < $num_cols; $i++) {
			
			$this_fieldname = db_field_name($QQuery,$i);			
			//Display the "fieldname"
			$html_string .= "<td style='padding:5px;'>$this_fieldname</td>";
		}			
		$html_string .= "</tr>";	
		
		// Display each table row
		$j = 1;
		while ($row = db_fetch_array($QQuery)) {
			$class = ($j%2==1) ? "odd" : "even";
			$html_string .= "<tr class='$class notranslate'>";			
			for ($i = 0; $i < $num_cols; $i++) 
			{
				// Escape the value in case of harmful tags
				$this_value = htmlspecialchars(html_entity_decode($row[$i], ENT_QUOTES), ENT_QUOTES);
				$html_string .= "<td style='padding:3px;border-top:1px solid #CCCCCC;font-size:11px;'>$this_value</td>";
			}			
			$html_string .= "</tr>";
			$j++;
		}
		
		$html_string .= "</table>";
		
	} else {
	
		for ($i = 0; $i < $num_cols; $i++) {
				
			$this_fieldname = db_field_name($QQuery,$i);
				
			//Display the Label and Field name
			$html_string .= "<td style='padding:5px;'>$this_fieldname</td>";
		}
		
		$html_string .= "</tr><tr><td colspan='$num_cols' style='font-weight:bold;padding:10px;color:#800000;'>{$lang['custom_reports_06']}</td></tr></table>";
		
	}
	
	if ($outputToPage) {
		// Output table to page
		print $html_string;
	} else {
		// Return table as HTML
		return $html_string;
	}
}

// Write sql query to csv file (in REDCap temp directory). Returns csv file path if successful, else returns false.
function queryToCsv($query, $minutesTillDeletion=60)
{
	// Execute query
	$result = db_query($query);
	if (!$result) return false;
	$num_fields = db_num_fields($result);
	// Set headers
	$headers = array();
	for ($i = 0; $i < $num_fields; $i++) {
		$headers[] = db_field_name($result, $i);
	}
	// Set file name and path
	$filename = APP_PATH_TEMP . date("YmdHis", mktime(date("H"),date("i")+$minutesTillDeletion,date("s"),date("m"),date("d"),date("Y"))) 
			  . '_queryToCsv.csv';
	// Begin writing file from query result
	$fp = fopen($filename, 'w');
	if ($fp && $result) {
		fputcsv($fp, $headers);
		while ($row = db_fetch_array($result, MYSQL_NUM)) {
			fputcsv($fp, $row);
		}
		fclose($fp);
		db_free_result($result);
		return $filename;
	}
	return false;
}

// Converts html line breaks to php line breaks (opposite of PHP's nl2br() function
function br2nl($string){
	return preg_replace('/\<br(\s*)?\/?\>/i', "\n", $string);
}

// Get array of users in current user's DAG, if in a DAG
function getDagUsers($project_id, $group_id)
{
	$dag_users_array = array();
	if ($group_id != "") {
		$sql = "select u.username from redcap_data_access_groups g, redcap_user_rights u where g.group_id = $group_id
				and g.group_id = u.group_id and u.project_id = g.project_id and g.project_id = $project_id";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q)) {
			$dag_users_array[] = $row['username'];
		}
	}
	return $dag_users_array;
}

// Transform a string to camel case formatting (i.e. remove all non-alpha-numerics and spaces) and truncate it
function camelCase($string, $leave_spaces=false, $char_limit=30)
{
	$string = ucwords(preg_replace("/[^a-zA-Z0-9 ]/", "", $string));
	if (!$leave_spaces) {
		$string = str_replace(" ", "", $string);
	}
	return substr($string, 0, $char_limit);
}

// Initialize auto-logout popup timer and logout reset timer listener
function initAutoLogout()
{
	global $auth_meth, $autologout_timer, $autologout_resettime;
	// Only set auto-logout if not using "none" authentication and if timer value is set
	if ($auth_meth != "none" && $autologout_timer != "0" && is_numeric($autologout_timer) && !defined("NOAUTH")) 
	{
		print "
		<script type='text/javascript'>
		$(function(){
			initAutoLogout($autologout_resettime,$autologout_timer);
		});
		</script>";
	}
}

// Filter potentially harmful html tags
function filter_tags($val)
{
	// Remove all but the allowed tags
	$val = strip_tags($val, ALLOWED_TAGS);
	// If any allowed tags contain javascript inside them, then remove javascript due to security issue.
	if (strpos($val, '<') !== false && strpos($val, '>') !== false) 
	{
		$regex = "/(<)([^<]*)(javascript\s*:|onabort\s*=|onclick\s*=|ondblclick\s*=|onblur\s*=|onfocus\s*=|onreset\s*=|onselect\s*=|onsubmit\s*=|onmouseup\s*=|onmouseover\s*=|onmouseout\s*=|onmousemove\s*=|onmousedown\s*=)([^<]*>)/i";
		$val = preg_replace($regex, "$1$2removed=$4", $val);
	}
	return $val;
}

// Filter potentially harmful JavaScript events inside a string
function filter_js_events($val)
{
	$regex = "/(javascript\s*:|onabort\s*=|onclick\s*=|ondblclick\s*=|onblur\s*=|onfocus\s*=|onreset\s*=|onselect\s*=|onsubmit\s*=|onmouseup\s*=|onmouseover\s*=|onmouseout\s*=|onmousemove\s*=|onmousedown\s*=)/i";
	return preg_replace($regex, " removed=", $val);
}

// Render "My Profile" and "Log out" links at top of home pages
function renderHomeHeaderLinks() 
{
	global $auth_meth, $lang, $isMobileDevice;
	?>
	<table border="0" cellpadding="0" cellspacing="0" width="100%">
		<tr><td style="color:#888;font-size:11px;text-align:right;padding-top:10px;">
			<span style='font-weight:bold;color:#000000'><?php echo USERID ?></span>
			&nbsp;|&nbsp; 
			<a href="<?php echo APP_PATH_WEBROOT ?>Profile/user_profile.php" style="font-size:11px;"><?php echo $lang['config_functions_50'] ?></a>
			<?php if (USERID != 'site_admin') { ?>
				&nbsp;|&nbsp; 
				<a href="<?php echo PAGE_FULL . (($_SERVER['QUERY_STRING'] == "") ? "?" : "?" . $_SERVER['QUERY_STRING'] . "&") ?>logout=1" 
					style="font-size:11px;"><?php echo $lang['bottom_02'] ?></a>
			<?php } ?>
			<?php if ($isMobileDevice) { ?>
				&nbsp;|&nbsp; 
				<a href="<?php echo APP_PATH_WEBROOT ?>/Mobile" style="font-weight:bold;color:#800000;font-size:11px;"><?php echo $lang['config_functions_66'] ?></a>
			<?php } ?>
		</td></tr>
	</table>
	<?php
}

// Render divs holding javascript form-validation text (when error occurs), so they get translated on the page
function renderValidationTextDivs()
{
	global $lang;
	?>
	
	<!-- Text used for field validation errors -->
	<div id="valtext_divs">
		<div id="valtext_number"><?php echo $lang['config_functions_52'] ?></div>
		<div id="valtext_integer"><?php echo $lang['config_functions_53'] ?></div>
		<div id="valtext_vmrn"><?php echo $lang['config_functions_54'] ?></div>
		<div id="valtext_rangehard"><?php echo $lang['config_functions_56'] ?></div>
		<div id="valtext_rangesoft1"><?php echo $lang['config_functions_57'] ?></div>
		<div id="valtext_rangesoft2"><?php echo $lang['config_functions_58'] ?></div>
		<div id="valtext_time"><?php echo $lang['config_functions_59'] ?></div>
		<div id="valtext_zipcode"><?php echo $lang['config_functions_60'] ?></div>
		<div id="valtext_phone"><?php echo $lang['config_functions_61'] ?></div>
		<div id="valtext_email"><?php echo $lang['config_functions_62'] ?></div>
		<div id="valtext_regex"><?php echo $lang['config_functions_77'] ?></div>
	</div>
	<!-- Regex used for field validation -->
	<div id="valregex_divs">
	<?php foreach (getValTypes() as $valType=>$attr) { ?>
	<div id="valregex-<?php echo $valType ?>" datatype="<?php echo $attr['data_type'] ?>"><?php echo $attr['regex_js'] ?></div>
	<?php } ?>
	</div>
	<?php
}

// Will convert a legacy field validation type (e.g. int, float, date) into a real value (e.g. integer, number, date_ymd).
// If not a legacy validation type, then will just return as-is.
function convertLegacyValidationType($legacyType)
{	
	if ($legacyType == "int") {
		$realType = "integer";
	} elseif ($legacyType == "float") {
		$realType = "number";
	} elseif (substr($legacyType, 0, 16) == "datetime_seconds") {
		$realType = "datetime_seconds_ymd";
	} elseif (substr($legacyType, 0, 8) == "datetime") {
		$realType = "datetime_ymd";
	} elseif (substr($legacyType, 0, 4) == "date") {
		$realType = "date_ymd";
	} else {
		$realType = $legacyType;
	}
	return $realType;
}

// Render hidden divs used by showProgress() javascript function
function renderShowProgressDivs() 
{
	global $lang;
	print	RCView::div(array('id'=>'working'),
				RCView::img(array('src'=>'progress_circle.gif')) . RCView::SP .
				$lang['design_08']
			) .
			RCView::div(array('id'=>'fade'), '');
}

// Convert an array to a REDCap enum format with keys as coded value and value as lables
function arrayToEnum($array, $delimiter="\\n")
{
	$enum = array();
	foreach ($array as $key=>$val)
	{
		$enum[] = trim($key) . ", " . trim($val);
	}
	return implode(" $delimiter ", $enum);
}

// Delete a form from all database tables EXCEPT metadata tables and user_rights table and surveys table
function deleteFormFromTables($form)
{
	$sql = "delete from redcap_events_forms where form_name = '".prep($form)."' 
			and event_id in (" . pre_query("select m.event_id from redcap_events_arms a, redcap_events_metadata m where a.arm_id = m.arm_id and a.project_id = " . PROJECT_ID . "") . ")";
	db_query($sql);
	$sql = "delete from redcap_library_map where project_id = " . PROJECT_ID . " and form_name = '".prep($form)."'";
	db_query($sql);
	$sql = "delete from redcap_locking_labels where project_id = " . PROJECT_ID . " and form_name = '".prep($form)."'";
	db_query($sql);
	$sql = "delete from redcap_locking_data where project_id = " . PROJECT_ID . " and form_name = '".prep($form)."'";
	db_query($sql);
	$sql = "delete from redcap_esignatures where project_id = " . PROJECT_ID . " and form_name = '".prep($form)."'";
	db_query($sql);
}

// Language: Obtain list of all language files (.ini files) in the languages folder. Return as array with language name as both key and value.
function getLanguageList()
{
	$languages = array('English'=>'English'); // English is always included by default
	foreach (getDirFiles(dirname(dirname(dirname(__FILE__))) . DS . 'languages') as $this_language)
	{
		if (strtolower(substr($this_language, -4)) == '.ini')
		{
			$lang_name = substr($this_language, 0, -4);
			// Set name as both key and value
			$languages[$lang_name] = $lang_name;
		}
	}
	ksort($languages);
	return $languages;
}

// Language: Call the correct language file for this project (default to English)
function callLanguageFile($language = 'English', $show_error = true)
{
	global $lang;
	// Get directory: English is kept in version sub-folder, while others are kept above version folder
	$dir = ($language == 'English') ? (dirname(dirname(__FILE__)) . DS . 'LanguageUpdater') : (dirname(dirname(dirname(__FILE__))) . DS . 'languages');
	// Get path of language file
	$language_file = $dir . DS . "$language.ini";
	// Parse ini file into an array
	$this_lang = parse_ini_file($language_file);
	// If fails, give error message
	if ($show_error && (!$this_lang || !is_dir($dir))) exit($lang['config_functions_63'] . "<br>$language_file");
	// Return array of language text
	return $this_lang;
}

// Language: Create and return array of all abstracted language text
function getLanguage($set_language = 'English')
{
	global $lang;
	// Always call English text first, in case the other language file used is not up to date (prevents empty text on the page)
	$lang = callLanguageFile('English');
	// If set language is not English, then now call that other language file and override all English strings with it
	if ($set_language != 'English')
	{
		$lang2 = callLanguageFile($set_language, false);
		// Merge language file with English language, unless returns False
		if ($lang2 !== false) 
		{
			$lang = array_merge($lang, $lang2);
		}
	}
	// Return array of language
	return $lang;	
}

// Determine web server domain name (take into account if a proxy exists)
function getServerName()
{
	if (isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST'])) return $_SERVER['HTTP_HOST'];
	if (isset($_SERVER['SERVER_NAME']) && !empty($_SERVER['SERVER_NAME'])) return $_SERVER['SERVER_NAME'];
	if (isset($_SERVER['HTTP_X_FORWARDED_HOST']) && !empty($_SERVER['HTTP_X_FORWARDED_HOST'])) return $_SERVER['HTTP_X_FORWARDED_HOST'];
	if (isset($_SERVER['HTTP_X_FORWARDED_SERVER']) && !empty($_SERVER['HTTP_X_FORWARDED_SERVER'])) return $_SERVER['HTTP_X_FORWARDED_SERVER'];
	if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return $_SERVER['HTTP_X_FORWARDED_FOR'];
	return false;
}

// Determing IP address of server (account for proxies also)
function getServerIP()
{
	if (isset($_SERVER['SERVER_ADDR']) && !empty($_SERVER['SERVER_ADDR'])) return $_SERVER['SERVER_ADDR'];
	if (isset($_SERVER['LOCAL_ADDR']) && !empty($_SERVER['LOCAL_ADDR'])) return $_SERVER['LOCAL_ADDR'];
	if (isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR'])) return $_SERVER['REMOTE_ADDR'];
	if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return $_SERVER['HTTP_X_FORWARDED_FOR'];
}

//Returns file extension of an inputted file name
function getFileExt($doc_name,$outputDotIfExists=false) 
{
	$dotpos = strrpos($doc_name, ".");
	if ($dotpos === false) return "";
	return substr($doc_name, $dotpos + ($outputDotIfExists ? 0 : 1), strlen($doc_name));
}

/**
 * UPLOAD FILE INTO EDOCS FOLDER (OR OTHER SERVER VIA WEBDAV) AND RETURN EDOC_ID# (OR "0" IF FAILED)
 */
function uploadFile($file) 
{
	global $edoc_storage_option;

	// Default result of success
	$result = 0;
	
	// Get basic file values
	$doc_name  = str_replace("'", "", html_entity_decode(stripslashes( $file['name']), ENT_QUOTES));
	$mime_type = $file['type'];
	$doc_size  = $file['size'];
	$tmp_name  = $file['tmp_name'];
	$file_extension = getFileExt($doc_name);
	$stored_name = date('YmdHis') . "_pid" . PROJECT_ID . "_" . generateRandomHash(6) . getFileExt($doc_name, true);
	
	if (!$edoc_storage_option) {

		// Upload to "edocs" folder (use default or custom path for storage)
		if (@move_uploaded_file($tmp_name, EDOC_PATH . $stored_name)) {
			$result = 1;
		}
		
	} else {

		// Upload using WebDAV
		require (APP_PATH_WEBTOOLS . 'webdav/webdav_connection.php');
		$wdc = new WebdavClient();
		$wdc->set_server($webdav_hostname);
		$wdc->set_port($webdav_port); $wdc->set_ssl($webdav_ssl);
		$wdc->set_user($webdav_username);
		$wdc->set_pass($webdav_password);
		$wdc->set_protocol(1); // use HTTP/1.1
		$wdc->set_debug(false); // enable debugging?
		if (!$wdc->open()) {
			sleep(1);
			return 0;
		}
		if (substr($webdav_path,-1) != '/') {
			$webdav_path .= '/';
		}
		if ($doc_size > 0) {		
			$fp      = fopen($tmp_name, 'rb');
			$content = fread($fp, filesize($tmp_name));
			fclose($fp);
			if(!get_magic_quotes_gpc()) {
				$doc_name = prep($doc_name);
			}
			$target_path = $webdav_path . $stored_name;
			$http_status = $wdc->put($target_path,$content);
			$result = 1;
		}
		$wdc->close();
	}
	
	// Return doc_id (return "0" if failed)
	if ($result == 0) {
		return 0;
	} else {
		// Add file info the redcap_edocs_metadata table for retrieval later
		$q = db_query("INSERT INTO redcap_edocs_metadata (stored_name, mime_type, doc_name, doc_size, file_extension, project_id, stored_date) 
						  VALUES ('" . prep($stored_name) . "', '" . prep($mime_type) . "', '" . prep($doc_name) . "', 
						  '" . prep($doc_size) . "', '" . prep($file_extension) . "', " . PROJECT_ID . ", '".NOW."')");
		return (!$q ? 0 : db_insert_id());
	}
	
}


// Prevent CSRF attacks by checking a custom token
function checkCsrfToken()
{
	global $isAjax, $lang, $salt, $userid, $fileDownloadPages;
	// Is this an API request?
	$isApi = (PAGE == "api/index.php");
	// Is the page a REDCap plugin?
	$isPlugin = defined("PLUGIN");
	// List of specific pages exempt from creating/updating CSRF tokens
	$pagesExemptFromTokenCreate = array("Design/edit_field.php", "Reports/report_export.php", "DataEntry/file_upload.php", "DataEntry/file_download.php", 
										"Graphical/pdf.php/download.pdf", "PDF/index.php", "DataExport/data_export_csv.php",
										"Design/file_attachment_upload.php", "DataEntry/image_view.php", "SharedLibrary/image_loader.php",
										"DataImport/import_template.php", "Design/data_dictionary_download.php"
									   );
	// List of specific pages exempt from checking CSRF tokens
	$pagesExemptFromTokenCheck = array("DTS/index.php", "Profile/user_info_action.php", "SharedLibrary/image_loader.php", "PubMatch/index_ajax.php");
	// Do not perform token check for non-Post methods, API requests, when logging in, for pages without authentication enabled,
	// or (for LDAP only) when providing user info immediately after logging in the first time.
	$exemptFromTokenCheck  = ( $isPlugin || $isApi || in_array(PAGE, $fileDownloadPages) || in_array(PAGE, $pagesExemptFromTokenCheck) 
							   || $_SERVER['REQUEST_METHOD'] != 'POST' || isset($_POST['redcap_login_a38us_09i85']) || defined("NOAUTH") ||
							   // In case uploading a file and exceeds PHP limits and normal error catching does not catch the error
							   ((PAGE == "SendIt/upload.php" || PAGE == "FileRepository/index.php") && empty($_FILES)) 
							 );
	// Do not create/update token for Head/API/AJAX requests, when logging in, or for pages that produce downloadable files, 
	// non-displayable pages, receive Post data via iframe, or have authentication disabled.
	$exemptFromTokenCreate = ( $isPlugin || $isAjax || $isApi || in_array(PAGE, $pagesExemptFromTokenCreate) || $_SERVER['REQUEST_METHOD'] == 'HEAD' || 
							   isset($_POST['redcap_login_a38us_09i85']) || defined("NOAUTH") );
	// Check for CSRF token
	if (!$exemptFromTokenCheck)
	{
		// Compare Post token with Session token (should be the same)
		if (!isset($_SESSION['redcap_csrf_token']) || !isset($_POST['redcap_csrf_token']) || !in_array($_POST['redcap_csrf_token'], $_SESSION['redcap_csrf_token']))
		{
			// Default
			$displayError = true;
			// FAIL SAFE: Because of strange issues with the last token not getting saved to the session table, 
			// do a check of all possible tokens that could have been created between now
			// and the time of the last token generated. If a match is found, then don't give user the error.
			if (isset($_POST['redcap_csrf_token']) && $_POST['redcap_csrf_token'] != "")
			{
				// Determine number of seconds passed since last token was generated
				$lastTokenTime = end(array_keys($_SESSION['redcap_csrf_token']));
				if (empty($lastTokenTime) || $lastTokenTime == "") {
					$sec_ago = 21600; // 6 hours
				} else {
					$sec_ago = strtotime(NOW) - strtotime($lastTokenTime);
				}
				// Find time when the posted token was generated, if can be found
				for ($this_sec_ago = -10; $this_sec_ago <= $sec_ago; $this_sec_ago++) 
				{
					$this_ts = date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s")-$this_sec_ago,date("m"),date("d"),date("Y")));
					if ($_POST['redcap_csrf_token'] == md5($salt . $this_ts . $userid)) 
					{
						// Found the token's timestamp, so note it and set flag to not display the error message
						$displayError = false;
						break;
					}
				}
			}
			// Display the error to the user
			if ($displayError)
			{
				// Give error message and stop (fatal error)
				$msg = "<p style='margin:20px;background-color:#FAFAFA;border:1px solid #ddd;padding:15px;font-family:arial;font-size:13px;max-width:600px;'>
							<img src='".APP_PATH_IMAGES."exclamation.png' style='position:relative;top:3px;'> 
							<b style='color:#800000;font-size:14px;'>{$lang['config_functions_64']}</b><br><br>{$lang['config_functions_65']}";
				if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
					// Button to go back one page
					$msg .= "<br><br><button onclick=\"window.location.href='{$_SERVER['HTTP_REFERER']}';\">&#60;- {$lang['form_renderer_15']}</button>";
				}
				$msg .= "</p>";
				exit($msg);
			}
		}
	}
	// Generate a new CRSF token, which jquery will add to all forms on the rendered page 
	if (!$exemptFromTokenCreate) 
	{
		// Initialize array if does not exist
		if (!isset($_SESSION['redcap_csrf_token']) || !is_array($_SESSION['redcap_csrf_token'])) {
			$_SESSION['redcap_csrf_token'] = array();
		}
		// If more than X number of elements exist in array, then remove the oldest
		$maxTokens = 20;
		if (count($_SESSION['redcap_csrf_token']) > $maxTokens) {
			array_shift($_SESSION['redcap_csrf_token']);
		}
		// Generate token and put in array
		$_SESSION['redcap_csrf_token'][NOW] = md5($salt . NOW . $userid);
	}
	// Lastly, remove token from Post to prevent any conflict in Post processing
	unset($_POST['redcap_csrf_token']);
}

// Add CSRF token to all forms on the webpage using jQuery
function createCsrfToken()
{
	if (isset($_SESSION['redcap_csrf_token']))
	{
		?>
		<script type="text/javascript">
		// Add CSRF token as javascript variable and add to every form on page
		var redcap_csrf_token = '<?php echo getCsrfToken() ?>';
		$(function(){ appendCsrfTokenToForm(); });
		</script>
		<?php
	}
}

// Retrieve CSRF token from session
function getCsrfToken()
{
	// Make sure the session variable exists first and is an array
	if (!isset($_SESSION['redcap_csrf_token']) || (isset($_SESSION['redcap_csrf_token']) && !is_array($_SESSION['redcap_csrf_token'])))
	{
		return false;
	}
	// Get last token in csrf token array
	$last_token = end($_SESSION['redcap_csrf_token']);
	return $last_token;
	/* 
	// Now get the second to last token, if exists
	$second_to_last_token = prev($_SESSION['redcap_csrf_token']);
	// Return the second to last token, if exists, and if not, return the last.
	// EXPLANATION: This is due to a strange issue where the PHP script is not reaching an end as expected and is thus 
	// not updating the session table before the user submits a form, thus causing the token check to fail. So we cannot trust that the
	// token generated on that page has been stored in the session table yet, but we CAN always know the the token
	// from the previous page has been already stored in the table. So we'll use the second to last token if we have it.
	return ($second_to_last_token === false) ? $last_token : $second_to_last_token;
	*/
}

// Replace any MS Word Style characters with regular characters
function replaceMSchars($str)
{
	// First, we replace any UTF-8 characters that exist.
	$str = str_replace(
			array("\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x93", "\xe2\x80\x94", "\xe2\x80\xa6"),
			array("'", "'", '"', '"', '-', '--', '...'),
			$str);
	/* 
	## THE BLOCK OF CODE BELOW WAS COMMENTED OUT BECAUSE OF ISSUES WITH SAVING TWO-BYTE CHARACTER STRING (E.G. JAPANESE)
	// Next, their Windows-1252 equivalents.  These shouldn't be here since REDCap uses UTF-8, but I'm including this just in case.
	$str = str_replace(
			array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)),
			array("'", "'", '"', '"', '-', '--', '...'),
			$str);
	// Replace all other Microsoft Windows characters		
	$str = str_replace(chr(130), ',', $str);    // baseline single quote
	$str = str_replace(chr(131), 'NLG', $str);  // florin
	$str = str_replace(chr(132), '"', $str);    // baseline double quote
	$str = str_replace(chr(134), '**', $str);   // dagger (a second footnote)
	$str = str_replace(chr(135), '***', $str);  // double dagger (a third footnote)
	$str = str_replace(chr(136), '^', $str);    // circumflex accent
	$str = str_replace(chr(137), 'o/oo', $str); // permile
	$str = str_replace(chr(138), 'Sh', $str);   // S Hacek
	$str = str_replace(chr(139), '<', $str);    // left single guillemet
	$str = str_replace(chr(140), 'OE', $str);   // OE ligature
	$str = str_replace(chr(152), '~', $str);    // tilde accent
	$str = str_replace(chr(153), '(TM)', $str); // trademark ligature
	$str = str_replace(chr(154), 'sh', $str);   // s Hacek
	$str = str_replace(chr(155), '>', $str);    // right single guillemet
	$str = str_replace(chr(156), 'oe', $str);   // oe ligature
	$str = str_replace(chr(159), 'Y', $str);    // Y Dieresis
	 */
	// Return the cleaned string
	return $str;
}

// Sanitize query parameters of an ARRAY and return as a comma-delimited string surrounded by single quotes
function prep_implode($array=array(), $replaceMSchars=true)
{
	// Loop through array
	foreach ($array as &$str) {
		// Replace any MS Word Style characters with regular characters
		if ($replaceMSchars) {
			$str = replaceMSchars($str);
		}
		// Perform escaping and return
		$str = db_real_escape_string($str);
	}
	// Return as a comma-delimited string surrounded by single quotes
	return "'" . implode("','", $array) . "'";
}

// Sanitize query parameters of a STRING
function prep($str, $replaceMSchars=true)
{
	// Replace any MS Word Style characters with regular characters
	if ($replaceMSchars) {
		$str = replaceMSchars($str);
	}
	// Perform escaping and return
	return db_real_escape_string($str);
}

// Render Javascript variables needed on all pages for various JS functions
function renderJsVars()
{
	global $status, $isMobileDevice, $user_rights, $institution, $sendit_enabled, $super_user, $surveys_enabled, 
		   $table_pk, $table_pk_label, $longitudinal, $email_domain_whitelist, $auto_inc_set;
	// Output JavaScript
	?>	
	<script type="text/javascript">
	<?php if (defined('APP_NAME')) { ?>
	var app_name = '<?php echo APP_NAME ?>';
	var pid = <?php echo PROJECT_ID ?>;
	var status = <?php echo $status ?>;
	var table_pk  = '<?php echo $table_pk ?>'; var table_pk_label  = '<?php echo trim(cleanHtml(strip_tags(label_decode($table_pk_label)))) ?>';
	var longitudinal = <?php echo $longitudinal ? 1 : 0 ?>;
	var auto_inc_set = <?php echo $auto_inc_set ? 1 : 0 ?>;
	var lock_record = <?php echo (isset($user_rights) && is_numeric($user_rights['lock_record']) ? $user_rights['lock_record'] : '0') ?>;
	var shared_lib_browse_url = '<?php echo SHARED_LIB_BROWSE_URL . "?callback=" . urlencode(SHARED_LIB_CALLBACK_URL . "?pid=" . PROJECT_ID) . "&institution=" . urlencode($institution) . "&user=" . md5($institution . USERID) ?>';
	<?php } ?>
	var app_path_webroot = '<?php echo APP_PATH_WEBROOT ?>';
	var app_path_webroot_full = '<?php echo APP_PATH_WEBROOT_FULL ?>';
	var app_path_images = '<?php echo APP_PATH_IMAGES ?>';
	var page = '<?php echo PAGE ?>';
	var sendit_enabled = <?php echo (isset($sendit_enabled) && is_numeric($sendit_enabled) ? $sendit_enabled : '0') ?>;
	var super_user = <?php echo (isset($super_user) && is_numeric($super_user) ? $super_user : '0') ?>;
	var surveys_enabled = <?php echo (isset($surveys_enabled) && is_numeric($surveys_enabled) ? $surveys_enabled : '0') ?>;
	var now = '<?php echo NOW ?>'; var today = '<?php echo date("Y-m-d") ?>'; var today_mdy = '<?php echo date("m-d-Y") ?>'; var today_dmy = '<?php echo date("d-m-Y") ?>';
	var isMobileDevice = <?php echo ((isset($isMobileDevice) && $isMobileDevice) ? '1' : '0') ?>;
	var email_domain_whitelist = new Array(<?php echo ($email_domain_whitelist == '' ? '' : prep_implode(explode("\n", str_replace("\r", "", $email_domain_whitelist)))) ?>);
	</script>
	<?php
}

// Redirects to URL provided using PHP, and if 
function redirect($url)
{
	// If contents already output, use javascript to redirect instead
	if (headers_sent())
	{
		exit("<script type=\"text/javascript\">window.location.href=\"$url\";</script>");
	}
	// Redirect using PHP
	else
	{
		header("Location: $url");
		exit;
	}
}

// Check if a record exists in the redcap_data table
function recordExists($project_id, $record)
{
	global $table_pk;
	// Query data table for record
	$sql = "select 1 from redcap_data where project_id = $project_id and field_name = '$table_pk' 
			and record = '" . prep($record) . "' limit 1";
	$q = db_query($sql);
	return (db_num_rows($q) > 0);
}
	
// Pre-fill metadata by getting template fields from prefill_metadata.php
function createMetadata($new_project_id,$type='0')
{
	$metadata = array();
	$form_names = array();
	$metadata['My First Instrument'] = array(
			array("record_id", "text", "Record ID", "", "", "")
	);
	//print_array($metadata);
	$i = 1;
	// Loop through all metadata fields from prefill_metadata.php and add as new project
	foreach ($metadata as $this_form=>$v2) 
	{
		$this_form_menu1 = camelCase($this_form, true);
		$this_form = $form_names[] = preg_replace("/[^a-z0-9_]/", "", str_replace(" ", "_", strtolower($this_form)));
		foreach ($v2 as $j=>$v) 
		{
			$this_form_menu = ($j == 0) ? $this_form_menu1 : "";
			$check_type = ($v[1] == "text") ? "soft_typed" : ""; 
			// Insert fields into metadata table
			$sql = "insert into redcap_metadata 
					(project_id, field_name, form_name, form_menu_description, field_order, element_type, element_label, 
					 element_enum, element_validation_type, element_validation_checktype, element_preceding_header) values 
					($new_project_id, ".checkNull($v[0]).", ".checkNull($this_form).", ".checkNull($this_form_menu).", ".$i++.", ".checkNull($v[1]).", 
					".checkNull($v[2]).", ".checkNull(str_replace("|","\\n",$v[3])).", ".checkNull($v[4]).", ".checkNull($check_type).", ".checkNull($v[5]).")";
			db_query($sql);
		}
		// Form Status field
		$sql = "insert into redcap_metadata (project_id, field_name, form_name, field_order, element_type, 
				element_label, element_enum, element_preceding_header) values ($new_project_id, '{$this_form}_complete', ".checkNull($this_form).", 
				".$i++.", 'select', 'Complete?', '0, Incomplete \\\\n 1, Unverified \\\\n 2, Complete', 'Form Status')";
		db_query($sql);
	}
	// Return array of form_names to use for user_rights
	return $form_names;
}

// Check if a record exists on arms other than the current arm. Return true if so.
function recordExistOtherArms($record, $current_arm)
{
	global $multiple_arms, $table_pk, $table_pk_label;
	
	if (!$multiple_arms || !is_numeric($current_arm)) return false;
	
	// Query if exists on other arms
	$sql = "select 1 from redcap_events_metadata m, redcap_events_arms a, redcap_data d 
			where a.project_id = " . PROJECT_ID . " and a.project_id = d.project_id and a.arm_num != $current_arm 
			and a.arm_id = m.arm_id and d.event_id = m.event_id and d.record = '" . prep($record). "' 
			and d.field_name = '$table_pk' limit 1";
	$q = db_query($sql);
	return (db_num_rows($q) > 0);		
}

// Find difference between two times
function timeDiff($firstTime,$lastTime,$decimalRound=null,$returnFormat='s')
{
	// convert to unix timestamps
	$firstTime = strtotime($firstTime);
	$lastTime = strtotime($lastTime);
	// perform subtraction to get the difference (in seconds) between times
	$timeDiff = $lastTime - $firstTime;
	// return the difference
	switch ($returnFormat)
	{
		case 'm':
			$timeDiff = $timeDiff/60;
			break;
		case 'h':
			$timeDiff = $timeDiff/3600;
			break;
		case 'd':
			$timeDiff = $timeDiff/3600/24;
			break;
		case 'w':
			$timeDiff = $timeDiff/3600/24/7;
			break;
		case 'y':
			$timeDiff = $timeDiff/3600/24/365;
			break;
	}
	if (is_numeric($decimalRound))
	{
		$timeDiff = round($timeDiff, $decimalRound);
	}
	return $timeDiff;
}

// Creates random alphanumeric string
function generateRandomHash($length = 6) {
    $characters = 'abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ23456789';
	$strlen_characters = strlen($characters);
    $string = '';    
    for ($p = 0; $p < $length; $p++) {
        $string .= $characters[mt_rand(0, $strlen_characters-1)];
    }
    return $string;
}

// Outputs drop-down of all text/textarea fields (except on first form) to choose Secondary Identifier field 
function renderSecondIdDropDown($id="", $name="", $outputToPage=true)
{
	global $table_pk, $Proj, $secondary_pk, $lang, $surveys_enabled;
	// Set id and name
	$id   = (trim($id)   == "") ? "" : "id='$id'";
	$name = (trim($name) == "") ? "" : "name='$name'";
	// Staring building drop-down
	$html = "<select $id $name class='x-form-text x-form-field' style='padding-right:0;height:22px;'>
				<option value=''>{$lang['edit_project_60']}</option>";
	// Get list of fields ONLY from follow up forms to add to Select list
	$followUpFldOptions = "";
	$sql = "select field_name, element_label from redcap_metadata where project_id = " . PROJECT_ID . "
			and field_name != concat(form_name,'_complete') and field_name != '$table_pk' 
			and element_type = 'text' order by field_order";
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q))
	{
		$this_field = $row['field_name'];
		$this_label = "$this_field - " . strip_tags(label_decode($row['element_label']));
		// Ensure label is not too long
		if (strlen($this_label) > 57) $this_label = substr($this_label, 0, 40) . "..." . substr($this_label, -15);
		// Add option
		$html .= "<option value='$this_field' " . ($this_field == $secondary_pk ? "selected" : "") . ">$this_label</option>";
	}
	// Finish drop-down
	$html .= "</select>";
	// Render or return
	if ($outputToPage) {
		print $html;
	} else {
		return $html;
	}
}

// Retrieve list of all Events utilized by DTS for a specified project
function getDtsEvents() 
{
	global $Proj, $dtsHostname, $dtsUsername, $dtsPassword, $dtsDb;		
	// Connect to DTS database
	$dts_connection = mysqli_connect($dtsHostname, $dtsUsername, $dtsPassword, $dtsDb);
	if (!$dts_connection) { db_connect(); return array(); }
	// Set default
	$eventIdsDts = array();
	// Get list of all event_ids for this project
	$ids = implode(",",array_keys($Proj->eventsForms));
	// Now get list of all event_ids used by DTS for this project
	$query = "SELECT DISTINCT md.event_id 
			  FROM project_map_definition md
				LEFT JOIN project_transfer_definition td ON md.proj_trans_def_id = td.id
			  WHERE td.redcap_project_id = " . PROJECT_ID . "
				AND event_id IN ($ids)";
	$recommendations = db_query($query);
	while ($row = db_fetch_assoc($recommendations)) 
	{
		// Add event_id as key for quick checking
		$eventIdsDts[$row['event_id']] = true;
	}
	// Set default connection back to REDCap core database
	db_connect();
	// Return the event_ids as array keys
	return $eventIdsDts;
}

// Retrieve list of all Events-Forms utilized by DTS for a specified project
function getDtsEventsForms() 
{
	global $Proj, $dtsHostname, $dtsUsername, $dtsPassword, $dtsDb;		
	// Connect to DTS database
	$dts_connection = mysqli_connect($dtsHostname, $dtsUsername, $dtsPassword, $dtsDb);
	if (!$dts_connection) { db_connect(); return array(); }
	// Set default
	$eventsForms = array();
	// Now get list of all events-forms used by DTS for this project
	$query = "SELECT DISTINCT event_id, target_field, target_temporal_field 
			  FROM project_map_definition md
				LEFT JOIN project_transfer_definition td ON md.proj_trans_def_id = td.id
			  WHERE td.redcap_project_id = " . PROJECT_ID;
	$targets = db_query($query);
	while ($row = db_fetch_assoc($targets)) 
	{
		$eventsForms[$row['event_id']][$Proj->metadata[$row['target_field']]['form_name']] = true;
		$eventsForms[$row['event_id']][$Proj->metadata[$row['target_temporal_field']]['form_name']] = true;
	}
	// Set default connection back to REDCap core database
	db_connect();
	// Return the event_ids as array keys with form_names as sub-array keys
	return $eventsForms;
}

// Retrieve list of all field_names utilized by DTS for a specified project
function getDtsFields() 
{
	global $dtsHostname, $dtsUsername, $dtsPassword, $dtsDb;		
	// Connect to DTS database
	$dts_connection = mysqli_connect($dtsHostname, $dtsUsername, $dtsPassword, $dtsDb);
	if (!$dts_connection) { db_connect(); return array(); }	
	// Set default
	$dtsFields = array();	
	// Now get list of all field_names used by DTS for this project
	$query = "SELECT DISTINCT event_id, target_field, target_temporal_field 
			  FROM project_map_definition md
				LEFT JOIN project_transfer_definition td ON md.proj_trans_def_id = td.id
			  WHERE td.redcap_project_id = " . PROJECT_ID;
	$fields = db_query($query);
	while ($row = db_fetch_assoc($fields)) 
	{
		// Add field_name as key for quick checking
		$dtsFields[$row['target_field']] = true;
		$dtsFields[$row['target_temporal_field']] = true;
	}
	// Set default connection back to REDCap core database
	db_connect();
	// Return the field_names as array keys
	return $dtsFields;
}

// Copy an edoc file on the web server. If fails, fall back to stream_copy().
function file_copy($src, $dest)
{
	if (!copy($src, $dest))
	{
		return stream_copy($src, $dest);
	}
	return true;
}

// Alternative to using copy() function, which can be disabled on some servers.
function stream_copy($src, $dest) 
{ 
	 // Allocate more memory since stream_copy_to_stream() is a memory hog.
	$fsrc  = fopen($src,'rb'); 
	$fdest = fopen($dest,'w+'); 
	$len = stream_copy_to_stream($fsrc, $fdest); 
	fclose($fsrc); 
	fclose($fdest);
	// If entire file was copied (bytes are the same), return as true.
	return ($len == filesize($src)); 
} 

// Copy an edoc_file by providing edoc_id. Returns edoc_id of new file, else False if failed. If desired, set new destination project_id.
function copyFile($edoc_id, $dest_project_id=PROJECT_ID)
{
	global $edoc_storage_option, $rc_connection;
	// Must be numeric
	if (!is_numeric($edoc_id)) return false;
	// Query the file in the edocs table
	$sql = "select * from redcap_edocs_metadata where doc_id = $edoc_id";
	$q = db_query($sql, $rc_connection);
	if (db_num_rows($q) < 1) return false;
	// Get file info
	$edoc_info = db_fetch_assoc($q);
	// Set src and dest filenames
	$src_filename  = $edoc_info['stored_name'];
	$dest_filename = date('YmdHis') . "_pid" . $dest_project_id . "_" . generateRandomHash(6) . getFileExt($edoc_info['doc_name'], true);
	// Default value
	$copy_successful = false;
	// Copy file within defined Edocs folder
	if (!$edoc_storage_option) 
	{
		$copy_successful = file_copy(EDOC_PATH . $src_filename, EDOC_PATH . $dest_filename);
	}
	// Use WebDAV copy methods
	else
	{
		require (APP_PATH_WEBTOOLS . 'webdav/webdav_connection.php');
		$wdc = new WebdavClient();
		$wdc->set_server($webdav_hostname);
		$wdc->set_port($webdav_port); $wdc->set_ssl($webdav_ssl);
		$wdc->set_user($webdav_username);
		$wdc->set_pass($webdav_password);
		$wdc->set_protocol(1); // use HTTP/1.1
		$wdc->set_debug(false); // enable debugging?
		if (!$wdc->open()) {
			sleep(1);
			return false;
		}
		if (substr($webdav_path,-1) != '/') {
			$webdav_path .= '/';
		}				
		// Download source file
		if ($wdc->get($webdav_path . $src_filename, $contents) == '200')
		{
			// Copy to destination file
			$copy_successful = ($wdc->put($webdav_path . $dest_filename, $contents) == '201');
		}
		$wdc->close();
	}
	// If copied successfully, then add new row in edocs_metadata table
	if ($copy_successful)
	{
		//Copy this row in the rs_edocs table and get new doc_id number
		$sql = "insert into redcap_edocs_metadata (stored_name, mime_type, doc_name, doc_size, file_extension, project_id, stored_date) 
				select '$dest_filename', mime_type, doc_name, doc_size, file_extension, '$dest_project_id', '".NOW."' from redcap_edocs_metadata 
				where doc_id = $edoc_id";
		if (db_query($sql, $rc_connection)) 
		{
			return db_insert_id($rc_connection);
		}
	}
	return false;
}


// Returns the contents of an edoc file when given its "stored_name" on the file system (i.e. from the edocs_metadata table)
function getEdocContents($stored_name) 
{
	global $edoc_storage_option;

	if (!$edoc_storage_option) {
		
		//Download from "edocs" folder (use default or custom path for storage)
		$local_file = EDOC_PATH . $stored_name;
		if (file_exists($local_file) && is_file($local_file)) 
		{	
			// Open file for reading and output
			$fp = fopen($local_file, 'rb');
			$contents = fread($fp, filesize($local_file));
			fclose($fp);
		} 
		else 
		{
			## Give error message
			return false;
		}

	} else {
		
		//Download using WebDAV
		include (APP_PATH_WEBTOOLS . 'webdav/webdav_connection.php');
		$wdc = new WebdavClient();
		$wdc->set_server($webdav_hostname);
		$wdc->set_port($webdav_port); $wdc->set_ssl($webdav_ssl);
		$wdc->set_user($webdav_username);
		$wdc->set_pass($webdav_password);
		$wdc->set_protocol(1); //use HTTP/1.1
		$wdc->set_debug(false);
		if (!$wdc->open()) {
			return false;
		}
		$http_status = $wdc->get($webdav_path . $stored_name, $contents); //$contents is produced by webdav class
		$wdc->close();
		
	}
	// Return the file contents
	return $contents;
}


// Make an HTTP GET request
function http_get($url="", $timeout=null)
{
	// Try using cURL first, if installed
	if (function_exists('curl_init'))
	{	
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_VERBOSE, 1);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_HTTPGET, true);
		curl_setopt($curl, CURLOPT_PROXY, PROXY_HOSTNAME); // If using a proxy
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1); // Don't use a cached version of the url
		if (is_numeric($timeout)) {
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout); // Set timeout time in seconds
		}
		$content = curl_exec($curl);
		curl_close($curl);	
	}
	// Try using file_get_contents if allow_url_open is enabled
	elseif (ini_get('allow_url_fopen'))
	{
		// Set http array for file_get_contents
		$http_array = array('method'=>'GET');
		if (is_numeric($timeout)) {
			$http_array['timeout'] = $timeout; // Set timeout time in seconds
		}	
		// Use file_get_contents
		$content = @file_get_contents($url, false, stream_context_create(array('http'=>$http_array)));
	}
	else
	{
		$content = false;
	}
	// Return the response
	return $content;
}

// Send HTTP Post request and receive/return content
function http_post($url="", $params=array(), $timeout=null)
{
    $param_string = http_build_query($params, '', '&');
	
	// Check if cURL is installed first. If so, then use cURL instead of file_get_contents.
	if (function_exists('curl_init')) 
	{
		// Use cURL
		$curlpost = curl_init();
		curl_setopt($curlpost, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curlpost, CURLOPT_VERBOSE, 1);
		curl_setopt($curlpost, CURLOPT_URL, $url);
		curl_setopt($curlpost, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curlpost, CURLOPT_POST, true);
		curl_setopt($curlpost, CURLOPT_POSTFIELDS, $param_string);
		curl_setopt($curlpost, CURLOPT_PROXY, PROXY_HOSTNAME); // If using a proxy
		curl_setopt($curlpost, CURLOPT_FRESH_CONNECT, 1); // Don't use a cached version of the url
		if (is_numeric($timeout)) {
			curl_setopt($curlpost, CURLOPT_CONNECTTIMEOUT, $timeout); // Set timeout time in seconds
		}
		$response = curl_exec($curlpost);
		curl_close($curlpost);
		return $response;
	}
	// Try using file_get_contents if allow_url_open is enabled
	elseif (ini_get('allow_url_fopen'))
	{
		// Set http array for file_get_contents
		$http_array = array('method'=>'POST', 
							'header'=>"Content-type: application/x-www-form-urlencoded", 
							'content'=>$param_string
					  );
		if (is_numeric($timeout)) {
			$http_array['timeout'] = $timeout; // Set timeout time in seconds
		}
		
		// Use file_get_contents
		$content = @file_get_contents($url, false, stream_context_create(array('http'=>$http_array)));
		
		// Return the content
		if ($content !== false) {
			return $content;
		} 
		// If no content, check the headers to see if it's hiding there (why? not sure, but it happens)
		else {
			$content = implode("", $http_response_header);
			// If header is a true header, then return false, else return the content found in the header
			return (substr($content, 0, 5) == 'HTTP/') ? false : $content;
		}
	}
	// Return false
	return false;
}

// Validate if string is a proper URL. Return boolean.
function isURL($url)
{
	$pattern = "/(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/i";      
	return preg_match($pattern, $url);
}

// Retrieve all validation types from table. Return as array.
function getValTypes()
{
	$sql = "select * from redcap_validation_types where validation_name is not null 
			and validation_name != '' order by validation_label";
	$q = db_query($sql);
	if (!$q) return false;
	$valTypes = array();
	while ($row = db_fetch_assoc($q))
	{
		$valTypes[$row['validation_name']] = array( 'validation_label'=>$row['validation_label'], 
													'regex_js'=>$row['regex_js'], 
													'regex_php'=>$row['regex_php'], 
													'data_type'=>$row['data_type'],
													'visible'=>$row['visible']
												   );
	}
	return $valTypes;
}

// Makes sure that a date in format Y-M-D format has 2-digit month and day and has a 4-digit year
function clean_date_ymd($date)
{
	$date = trim($date);
	// Ensure has 2 dashes, and if not 10-digits long, then break apart and reassemble
	if (substr_count($date, "-") == 2 && strlen($date) < 10)
	{
		// Break into components
		list ($year, $month, $day) = explode('-', $date);
		// Make sure year is 4 digits
		if (strlen($year) == 2) {
			$year = ($year < (date('y')+10)) ? "20".$year : "19".$year;
		}
		// Reassemble
		$date = sprintf("%04d-%02d-%02d", $year, $month, $day);
	}
	return $date;
}

// Detect IE version
function vIE()
{
	$match = preg_match('/MSIE ([0-9]\.[0-9])/', $_SERVER['HTTP_USER_AGENT'], $reg);
	return ($match == 0) ? -1 : floatval($reg[1]);
}

// Display a message to the user as a colored div with option to animate and set aesthetics
function displayMsg($msgText=null, $msgId="actionMsg", $msgAlign="center", $msgClass="green", $msgIcon="tick.png", $timeVisible, $msgAnimate=true)
{
	global $lang;
	// Set message text
	if ($msgText == null) {
		$msgText = "<b>{$lang['setup_08']}</b> {$lang['setup_09']}";
	}
	// Check that timeVisible is a positive number (in seconds)
	if (!is_numeric($timeVisible) || (is_numeric($timeVisible) && $timeVisible < 0)) {
		$timeVisible = 7;
	}
	// Display the message
	?>
	<div id="<?php echo $msgId ?>" class="<?php echo $msgClass ?>" style="<?php if ($msgAnimate) echo 'display:none;'; ?>max-width:660px;padding:15px 25px;margin:20px 0;text-align:<?php echo $msgAlign ?>;">
		<img src="<?php echo APP_PATH_IMAGES . $msgIcon ?>" class="imgfix"> <?php echo $msgText ?>
	</div>
	<?php
	// Animate the message to display and hide (if set to do so)
	if ($msgAnimate) 
	{ 
		?>
		<!-- Animate action message -->
		<script type="text/javascript">
		$(function(){
			setTimeout(function(){
				$("#<?php echo $msgId ?>").slideToggle('normal');
			},200);
			setTimeout(function(){
				$("#<?php echo $msgId ?>").slideToggle(1200);
			},<?php echo $timeVisible*1000 ?>);
		});
		</script>
		<?php 
	}
}

// Add a special header to enforce BOM (byte order mark) if the string is UTF-8 encoded file
function addBOMtoUTF8($string)
{
	if (function_exists('mb_detect_encoding') && mb_detect_encoding($string) == "UTF-8")
	{
		$string = "\xEF\xBB\xBF" . $string;
	}
	return $string;
}

// Remove BOM (byte order mark) if the string is UTF-8 encoded file
function removeBOMfromUTF8($string)
{
	$bom = pack("CCC", 0xef,0xbb,0xbf);
	if (function_exists('mb_detect_encoding') && mb_detect_encoding($string) == "UTF-8" && substr($string, 0, 3) == $bom) 
	{
		$string = substr($string, 3);
	}
	return $string;
}

/**
 * RETRIEVE ALL CALENDAR EVENTS
 */
function getCalEvents($month, $year) 
{
	global $user_rights, $Proj;
	
	// Place info into arrays
	$event_info = array();
	$events = array();

	$year_month = (strlen($month) == 2) ? $year . "-" . $month : $year . "-0" . $month;
	$sql = "select * from redcap_events_metadata m right outer join redcap_events_calendar c on c.event_id = m.event_id 
			where c.project_id = " . PROJECT_ID . " and c.event_date like '{$year_month}%' 
			" . (($user_rights['group_id'] != "") ? "and c.group_id = {$user_rights['group_id']}" : "") . " 
			order by c.event_date, c.event_time";
	$query_result = db_query($sql);
	$i = 0;
	while ($info = db_fetch_assoc($query_result)) 
	{
		$thisday = substr($info['event_date'],-2)+0;
		$events[$thisday][] = $event_id = $i;
		$event_info[$event_id]['0'] = $info['descrip'];
		$event_info[$event_id]['1'] = $info['record'];
		$event_info[$event_id]['2'] = $info['event_status'];
		$event_info[$event_id]['3'] = $info['cal_id'];
		$event_info[$event_id]['4'] = $info['notes'];
		$event_info[$event_id]['5'] = $info['event_time'];
		// Add DAG, if exists
		if ($info['group_id'] != "") {
			$event_info[$event_id]['6'] = $Proj->getGroups($info['group_id']);
		}
		$i++;
	}
	
	// Return the two arrays
	return array($event_info, $events);
}
/**
 * Function to render a single calendar event (for agenda or month view)
 */
function renderCalEvent($event_info,$i,$value,$view) 
{	
	//Vary slightly depending if this is agenda view or month view
	if ($view == "month" || $view == "week") {
		// Month/Week view
		$divstyle = "";
		$asize = "10px";
	} else {
		// Agenda/Day view
		$divstyle = "width:430px;line-height:13px;";
		$asize = "11px";
	}
	
	//Alter color of text based on visit status
	switch ($event_info[$value]['2']) {
		case '0': 
			$status    = "#222";	
			$statusimg = "star_small_empty.png";
			$width	   = 800;
			break;
		case '1':
			$status    = "#a86700";	
			$statusimg = "star_small.png";
			$width	   = 800;	
			break;
		case '2': 
			$status    = "green";	
			$statusimg = "tick_small.png";
			$width	   = 800;	
			break;
		case '3': 
			$status    = "red";	
			$statusimg = "cross_small.png";
			$width	   = 800;	
			break;
		case '4': 
			$status    = "#800000";	
			$statusimg = "bullet_delete16.png";
			$width	   = 800;	
			break;
		default:
			if ($event_info[$value]['1'] != "") {
				// If attached to a record
				$status    = "#222";
				$statusimg = "bullet_white.png";
				$width = 800;	
			} else {
				// If a random comment
				$status    = "#573F3F";	
				$statusimg = "balloon_small.png";
				$width = 600;	
			}	
	}
	
	//Render this event
	print  "<div class='numdiv' id='divcal{$event_info[$value]['3']}' style='background-image:url(\"".APP_PATH_IMAGES.$statusimg."\");$divstyle'>
			<a class='notranslate' href='javascript:;' style='font-family:tahoma;font-size:$asize;color:$status;' onmouseover='overCal(this,{$event_info[$value]['3']})' 
				onmouseout='outCal(this)' onclick='popupCal({$event_info[$value]['3']},$width)'>";
	//Display time first, if exists, but only in Month/Week view
	if ($event_info[$value]['5'] != "" && ($_GET['view'] == "month" || $_GET['view'] == "month")) {
		print format_time($event_info[$value]['5']) . " ";
	}
	//Display record name, if calendar event is tied to a record
	if ($event_info[$value]['1'] != "") {
		print $event_info[$value]['1'];
	}
	//Display the Event name, if exists
	if ($event_info[$value]['0'] != "") {
		print " (" . $event_info[$value]['0']  . ")";
	}
	//Display DAG name, if exists
	if (isset($event_info[$value]['6'])) {
		print " [" . $event_info[$value]['6']  . "]";
	}
	//Display any Notes
	if ($event_info[$value]['4'] != "") {	
		if ($event_info[$value]['1'] != "" || $event_info[$value]['0'] != "") {
			print " - ";
		}
		print " " . $event_info[$value]['4'];
	}
	print  "</a></div>";

}

// GOOGLE CHROME FRAME: If using IE6,7, or 8 (and not using Google Chrome Frame), give message to install GCF and return TRUE
function chromeFrameInstallMsg($additional_text="")
{
	global $isIE, $lang;
	if ($isIE && vIE() < 9 && strpos($_SERVER['HTTP_USER_AGENT'], 'chromeframe') === false)
	{
		?>
		<div id="chrome_frame_install_msg" class="yellow" style="padding:10px;">
			<img src="<?php echo APP_PATH_IMAGES ?>exclamation_orange.png" class="imgfix">
			<b><?php echo $lang['graphical_view_47'] ?></b><br><br>
			<?php echo $lang['graphical_view_46'] ?><br><br>
			<div style="text-align:center;">
				<button class="jqbuttonmed" id="chrome_frame_install_btn" onclick="displayChromeFrameInstallPopup();">
					<img src="<?php echo APP_PATH_IMAGES ?>chrome_icon.png" class="imgfix">
					<?php echo $lang['graphical_view_48'] ?>
				</button>
			</div>
			<div>
				<?php echo $additional_text ?>
			</div>
		</div>
		<?php
		return true;
	}
	return false;
}


// Check if a directory is writable (tries to write a file to directory as a definite confirmation)
function isDirWritable($dir)
{
	$is_writable = false; //default
	if (is_dir($dir) && is_writeable($dir)) 
	{
		// Try to write a file to that directory and then delete
		$test_file_path = $dir . DS . date('YmdHis') . '_test.txt';
		$fp = fopen($test_file_path, 'w');
		if ($fp !== false && fwrite($fp, 'test') !== false) 
		{
			// Set as writable
			$is_writable = true;
			// Close connection and delete file
			fclose($fp);
			unlink($test_file_path);
		}
	}
	return $is_writable;
}

// Display table of REDCap variables, constants, and settings (similar to php_info())
function redcap_info()
{
	global $lang;
	// Obtain all REDCap-defined PHP contants
	$all_constants = get_defined_constants(true);
	$redcap_constants = $all_constants['user'];
	// Manually set a list as an array of contants and variables that would be helpful for REDCap developers
	$redcap_variables = array(
		'constants' => array('USERID', 'SUPER_USER', 'NOW', 'SERVER_NAME', 'PAGE_FULL', 'APP_PATH_WEBROOT', 'APP_PATH_SURVEY',
							 'APP_PATH_WEBROOT_PARENT', 'APP_PATH_WEBROOT_FULL', 
							 'APP_PATH_SURVEY_FULL', 'APP_PATH_IMAGES', 'APP_PATH_CSS', 'APP_PATH_JS', 
							 'APP_PATH_DOCROOT', 'APP_PATH_CLASSES', 'APP_PATH_TEMP', 'APP_PATH_WEBTOOLS', 'EDOC_PATH', 
							 'CONSORTIUM_WEBSITE', 'SHARED_LIB_PATH'
		),
		'variables_system' => array_keys(getConfigVals())
	);
	// If authentication is disabled, then remove USERID and SUPER_USER as constants to be displayed
	if (!defined("USERID"))
	{
		$key = array_search('USERID', $redcap_variables['constants']);
		unset($redcap_variables['constants'][$key]);
		$key = array_search('SUPER_USER', $redcap_variables['constants']);
		unset($redcap_variables['constants'][$key]);
	}	
	// Remove all system variables that exist as columns in redcap_projects table (the project-level values override them)
	$q = db_query("SHOW COLUMNS FROM redcap_projects");
	while ($row = db_fetch_assoc($q))
	{
		$col = $row['Field'];
		$key = array_search($col, $redcap_variables['variables_system']);
		if ($key !== false)
		{
			unset($redcap_variables['variables_system'][$key]);
		}
	}
	// Remove some system variables (may cause confusion with developer)
	$key = array_search('edoc_path', $redcap_variables['variables_system']);
	unset($redcap_variables['variables_system'][$key]);
	// Get system variables and add to $redcap_variables array
	$projectVals = getProjectVals();
	if ($projectVals !== false)
	{
		$redcap_variables['variables_project'] = array_keys($projectVals);
	}
	// Get drop-down list options for all projects the current user has access to
	if (SUPER_USER) {
		$sql = "select project_id, app_title from redcap_projects order by trim(app_title), project_id";
	} else {
		$sql = "select p.project_id, trim(p.app_title) from redcap_projects p, redcap_user_rights u
				where p.project_id = u.project_id and u.username = '" . USERID . "' order by trim(p.app_title), p.project_id";
	}
	$q = db_query($sql);
	$projectList = "";
	while ($row = db_fetch_assoc($q))
	{
		$row['app_title'] = strip_tags(label_decode($row['app_title']));
		if (strlen($row['app_title']) > 80) {
			$row['app_title'] = trim(substr($row['app_title'], 0, 70)) . " ... " . trim(substr($row['app_title'], -15));
		}					
		if ($row['app_title'] == "") {
			$row['app_title'] = $lang['create_project_82'];
		}
		$selected = ($_GET['pid'] == $row['project_id']) ? "selected" : "";
		$projectList .= "<option value='{$row['project_id']}' $selected>{$row['app_title']}</option>";
	}
	
	// Display the page
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
	<html>
	<head>
		<style type="text/css">
		body {background-color: #ffffff; color: #000000;}
		body, td, th, h1, h2 {font-family: sans-serif;}
		pre {margin: 0px; font-family: monospace;}
		a:link {color: #000099; text-decoration: none; background-color: #ffffff;}
		a:hover {text-decoration: underline;}
		table {border-collapse: collapse;}
		.center {text-align: center; margin:0 0 100px;}
		.center table { margin-left: auto; margin-right: auto; text-align: left;}
		.center th { text-align: center !important; }
		td, th { border: 1px solid #000000; font-size: 75%; vertical-align: baseline;}
		h1 {font-size: 150%;}
		h2 {font-size: 125%;}
		.p {text-align: left;}
		.e {background-color: #aaa; font-weight: bold; color: #000000;}
		.h {background-color: #eee; font-weight: bold; color: #000000;}
		.v {background-color: #ddd; color: #000000;}
		.vr {background-color: #cccccc; text-align: right; color: #000000;}
		img {float: right; border: 0px;}
		hr {width: 600px; background-color: #cccccc; border: 0px; height: 1px; color: #000000;}
		</style>
		<title>redcap_info()</title>
		<meta name="ROBOTS" content="NOINDEX,NOFOLLOW,NOARCHIVE" />
	</head>
	<body>
	<div class="center">	
	<!-- Title -->
	<table border="0" cellpadding="3" width="600">
		<tr class="h">
			<td>
				<a href="http://project-redcap.org"><img border="0" src="<?php echo APP_PATH_IMAGES ?>redcaplogo_small.gif" alt="REDCap Logo" /></a>
				<h1 class="p">REDCap Version <?php echo $GLOBALS['redcap_version'] ?></h1>
			</td>
		</tr>
	</table>
	<br>
	<!-- Constants -->
	<h2>REDCap PHP Constants</h2>
	<table border="0" cellpadding="3" width="600">
	<?php foreach ($redcap_variables['constants'] as $this_constant) { ?>
		<tr>
			<td class="e"><?php echo $this_constant ?> </td>
			<td class="v"><?php echo $redcap_constants[$this_constant] ?> </td>
		</tr>
	<?php }  ?>
	</table>
	<br>
	<!-- System variables -->
	<h2>REDCap PHP Variables (System-Level)</h2>
	<table border="0" cellpadding="3" width="600">
		<tr>
			<td class="v" colspan="2">
				<b>The variables below are accessible in the global scope.</b>
			</td>
		</tr>
		<?php foreach ($redcap_variables['variables_system'] as $this_var) { ?>
			<tr>
				<td class="e"><?php echo $this_var ?> </td>
				<td class="v"><?php echo ($GLOBALS[$this_var] === false ? '0' : htmlspecialchars(html_entity_decode($GLOBALS[$this_var], ENT_QUOTES), ENT_QUOTES)) ?> </td>
			</tr>
		<?php }  ?>
	</table>
	<br>
	<!-- Project variables -->
	<h2 id="proj_vals">REDCap PHP Variables (Project-Level)</h2>
	<table border="0" cellpadding="3" width="600">
		<tr>
			<td class="v" colspan="2">
				<b>The variables below are accessible in the global scope.</b><br><br>
				Select one of the projects below that you currently
				have access to in order to view its project-level variables/values.<br>
				<select style="max-width:550px;" onchange="var url='<?php echo PAGE_FULL ?>';if(this.value!=''){url+='?pid='+this.value;}window.location.href=url+'#proj_vals';">
					<option value="">-- select project --</option>
					<?php echo $projectList ?>
				</select>
			</td>
		</tr>
		<?php if (isset($redcap_variables['variables_project'])) { ?>
			<?php foreach ($redcap_variables['variables_project'] as $this_var) { ?>
				<?php if ($this_var == 'report_builder' || $this_var == 'custom_reports') continue; ?>
				<tr>
					<td class="e"><?php echo $this_var ?> </td>
					<td class="v"><?php echo ($GLOBALS[$this_var] === false ? '0' : htmlspecialchars(html_entity_decode($GLOBALS[$this_var], ENT_QUOTES), ENT_QUOTES)) ?> </td>
				</tr>
			<?php }  ?>
		<?php }  ?>
	</table>
	</div>
	</body>
	</html>
	<?php
}


## REDCAP PLUGIN FUNCTION: Limit the plugin to specific projects
function allowProjects()
{
	global $lang;
	// Get arguments passed
	$args = func_get_args();
	// If project_id is not defined (i.e. not a project-level page) OR if no project_id's are provided, then return false with no error warning
	if (!defined("PROJECT_ID") || empty($args)) return false;
	// Set flag if the project_id does not exist as a parameter
	$projectIdNotFound = true;
	// Loop through all project_ids as parameter
	foreach ($args as $item) {
		if (is_array($item)) {
			if (empty($item)) return false;
			foreach ($item as $project_id) {
				if ($project_id == PROJECT_ID) {
					$projectIdNotFound = false;
				}
			}
		} else {
			if ($item == PROJECT_ID) {
				$projectIdNotFound = false;
			}
		}
	}
	// Now do a check if the project_id for this project was not set as a parameter
	if ($projectIdNotFound) 
	{
		print  "<div style='background-color:#FFE1E1;border:1px solid red;max-width:700px;padding:6px;color:#800000;'>
					<img src='" . APP_PATH_IMAGES . "exclamation.png' class='imgfix'> 
					<b>{$lang['global_05']}</b> {$lang['config_05']}
				</div>";
		exit;
	}
	// If we made it this far, return true
	return true;

}

## REDCAP PLUGIN FUNCTION: Limit the plugin to specific users
function allowUsers()
{
	global $lang;
	// Get arguments passed
	$args = func_get_args();
	// If userid is not defined OR if authentication has been disabled OR if no userid's were provided, then return false with no error warning
	if (!defined("USERID") || defined("NOAUTH") || empty($args)) return false;
	// Set flag if the userid does not exist as a parameter
	$userIdNotFound = true;
	// Loop through all project_ids as parameter
	foreach ($args as $item) {
		if (is_array($item)) {
			if (empty($item)) return false;
			foreach ($item as $userid) {
				if ($userid == USERID) {
					$userIdNotFound = false;
				}
			}
		} else {
			if ($item == USERID) {
				$userIdNotFound = false;
			}
		}
	}
	// Now do a check if the userid was not set as parameter
	if ($userIdNotFound) 
	{
		print  "<div style='background-color:#FFE1E1;border:1px solid red;max-width:700px;padding:6px;color:#800000;'>
					<img src='" . APP_PATH_IMAGES . "exclamation.png' class='imgfix'> 
					<b>{$lang['global_05']}</b> {$lang['config_05']}
				</div>";
		exit;
	}
	// If we made it this far, return true
	return true;
}

// Convert a string with an IF statement from Excel format - e.g. if(cond, true, false) -
// to PHP ternary operator format - e.g. if(cond ? true : false).
function convertIfStatement($string,$recursions=0) 
{
	// Check if has any IF statements
	if (preg_match("/(if)(\s*)(\()/i", $string) && substr_count($string, ",") >= 2 && $recursions < 1000) 
	{	
		// Remove spaces between "if" and parenthesis so we can more easily parse it downstream
		$string_temp = preg_replace("/(if)(\s*)(\()/i", "if(", $string);
		// Defaults
		$curstr = "";
		$nested_paren_count = 0; // how many nested parentheses we're inside of
		$found_first_comma = false;
		$found_second_comma = false;
		$location_first_comma = null;
		$location_second_comma = null;
		// Only begin parsing at first IF (i.e. only use string_temp)
		list ($cumulative_string, $string_temp) = explode("if(", $string_temp, 2);
		// First, find the first innermost IF in the string and get its location. We'll begin parsing there.			
		$string_array = explode("if(", $string_temp);
		// print_array($string_array);
		foreach ($string_array as $key => $this_string) 
		{
			// Check if we should parse this loop
			if ($this_string != "") 
			{
				// If current string is empty, then set it as this_string, otherwise prepend curstr from last loop to this_string
				$curstr .= $this_string;
				// Check if this string has ALL we need (2 commas, 1 right parenthesis, and num right parens = num left parens+1)
				$num_commas 	 = substr_count($curstr, ",");
				$num_left_paren  = substr_count($curstr, "(");
				$num_right_paren = substr_count($curstr, ")");
				$hasCompleteIfStatement = ($num_commas >= 2 && $num_right_paren > 0 && $num_right_paren > $num_left_paren);
				if ($hasCompleteIfStatement) 
				{
					// The entire IF statement MIGHT be in this_string. Check if it is (commas and parens in correct order).
					$curstr_len = strlen($curstr);
					// Loop through the string letter by letter
					for ($i = 0; $i < $curstr_len; $i++)
					{
						// Get current letter
						$letter = substr($curstr, $i, 1);
						// Perform logic based on current letter and flags already set
						if ($letter == "(") {
							// Increment the count of how many nested parentheses we're inside of
							$nested_paren_count++;
						} elseif ($letter != ")" && $nested_paren_count > 0) {
							if ($i+1 == $curstr_len) {
								// This is the last letter of the string, and we still haven't completed the entire IF statement.
								// So reset curstr and go to next loop, which should have a nested IF (we'll work our way outwards)
								$cumulative_string .= "if($curstr";
								$curstr = "";
							} else {
								// We're inside a nested parenthesis, so there's nothing to do -> keep looping till we get out
							}
						} elseif ($letter == ")" && $nested_paren_count > 0) {
							// We just left a nested parenthesis, so reduce count by 1 and keep looping
							$nested_paren_count--;
						} elseif ($letter == "," && $nested_paren_count == 0 && !$found_first_comma) {
							// Found first valid comma AND not in a nested parenthesis
							$found_first_comma = true;
							$found_second_comma = false;
							$location_first_comma = $i;
							$location_second_comma = null;
						} elseif ($letter == "," && $nested_paren_count == 0 && $found_first_comma && !$found_second_comma) {
							// Found second valid comma AND not in a nested parenthesis
							$found_second_comma = true;
							$location_second_comma = $i;
						} elseif ($letter == ")" && $nested_paren_count == 0 && $found_first_comma && $found_second_comma) {
							// Found closing valid parenthesis of IF statement, so replace the commas with ternary operator format
							$cumulative_string .= "(" . substr($curstr, 0, $location_first_comma) 
												. " ? " . substr($curstr, $location_first_comma+1, $location_second_comma-($location_first_comma+1))
												. " : " . substr($curstr, $location_second_comma+1);
							// Reset values for further processing
							$curstr = "";
							$found_first_comma = false;
							$found_second_comma = false;
							$location_first_comma = null;
							$location_second_comma = null;
						}
					}						
				} else {
					// The entire IF statement is NOT in this_string, therefore there must be a nested IF after this one.
					// Reset curstr and begin anew with next nested IF (we'll work our way outwards from the innermost IFs)
					$cumulative_string .= "if($curstr";
					$curstr = "";
				}
			}
		}
		// If the string still has IFs because of nesting, then do recursively.
		return convertIfStatement($cumulative_string,++$recursions);
	}
	// Now that we're officially done parsing, return the string
	return $string;
}

// Clean and escape text to be sent as JSON
function cleanJson($val)
{
	return cleanHtml2(str_replace('\\', '\\\\', $val));
}

// Unserialize session data from the session table
function unserialize_session($data) 
{
    $vars=preg_split('/([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff^|]*)\|/',
              $data,-1,PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
    for($i=0; $vars[$i]; $i++) $result[$vars[$i++]]=unserialize($vars[$i]);
    return $result;
}

// Copy the completed survey response to the surveys_response_values table as a backup of the completed response
// (includes making a copy of any uploading documents).
function copyCompletedSurveyResponse($response_id)
{
	global $Proj, $table_pk;
	
	// Check type
	if (!is_numeric($response_id)) return false;
	
	// Make sure Project Attribute class has instantiated the $Proj object
	if (!isset($Proj) || empty($Proj)) $Proj = new ProjectAttributes();
	
	// First, check if has been copied already. If so, return as false.
	$sql = "select 1 from redcap_surveys_response_values where response_id = $response_id limit 1";
	$q = db_query($sql);
	if (db_num_rows($q) > 0) return false;
	
	// Use the response_id to get the survey_id, record, form, and event_id
	$sql = "select s.survey_id, s.form_name, r.record, r.completion_time, p.event_id 
			from redcap_surveys_participants p, redcap_surveys_response r, redcap_surveys s 
			where r.response_id = $response_id and p.participant_id = r.participant_id and s.survey_id = p.survey_id 
			and r.completion_time is not null limit 1";
	$q = db_query($sql);
	if (db_num_rows($q) < 1) return false;
	$survey_id = db_result($q, 0, 'survey_id');
	$record    = db_result($q, 0, 'record');
	$form	   = db_result($q, 0, 'form_name');
	$event_id  = db_result($q, 0, 'event_id');
	$survey_completion_time = db_result($q, 0, 'completion_time');

	## COPY DATA: Place a copy of the original survey response values in the surveys_response_values table (for archival purposes)
	$surveyFields = array_keys($Proj->forms[$form]['fields']);
	$sql = "insert into redcap_surveys_response_values 
			select '$response_id', d.* from redcap_data d where d.project_id = " . PROJECT_ID . "
			and d.record = '" . prep($record) . "' and d.event_id = $event_id
			and d.field_name in ('$table_pk', '" . implode("', '", $surveyFields) . "')";
	$q = db_query($sql);
	if ($q)
	{
		## COPY EDOCS: Move the "file" field type values separately (because the docs will have to be copied in the file system)
		$sql = "select distinct d.* from redcap_metadata m, redcap_surveys_response_values d 
				where m.project_id = d.project_id and m.field_name = d.field_name and m.project_id = " . PROJECT_ID . " 
				and d.response_id = $response_id and m.element_type = 'file'";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q)) 
		{
			// Make sure edoc_id is numerical. If so, copy file. If not, fix this corrupt data and don't copy file.
			$edoc_id = $row['value'];
			// Get edoc_id of new file copy
			$new_edoc_id = (is_numeric($edoc_id)) ? copyFile($edoc_id) : '';
			// Set the new edoc_id value in the redcap_surveys_response_values table
			$sql = "update redcap_surveys_response_values set value = '$new_edoc_id' 
					where response_id = $response_id and field_name = '{$row['field_name']}'";
			db_query($sql);
		}
		## COPY USERS WHO EDITED RESPONSE BEFORE IT WAS COMPLETED
		$sql = "select distinct user from redcap_log_event 
				where ts <= " . str_replace(array(':',' ','-'), array('','',''), $survey_completion_time) . " 
				and project_id = " . PROJECT_ID . " and event in ('UPDATE','INSERT') and object_type = 'redcap_data' 
				and pk = '" . prep($record) . "' and event_id = $event_id";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q)) 
		{
			$sql = "insert into redcap_surveys_response_users (response_id, username)
					values ($response_id, '" . prep($row['user']) . "')";
			db_query($sql);
		}
	}
}

// Get count of users that were active in the past X days
function getActiveUsers($days=null)
{
	if (!is_numeric($days) && $days != null) return false;
	// If null, then return ALL active users since beginning (exclude suspended users)
	$sql_interval = ($days == null) ? "" : "and DATE_SUB('".TODAY."', INTERVAL $days DAY) <= user_lastactivity";
	$sql = "select count(1) from redcap_user_information where username != '' $sql_interval 
			and user_lastactivity is not null and user_suspended_time is null order by username";
	$q = db_query($sql);
	return db_result($q, 0);
}

// Take a CSV formatted $_FILE that was uploaded and convert to array
function csv_file_to_array($file) // e.g. $file = $_FILES['allocFile']
{
	global $lang;
	
	// If filename is blank, reload the page
	if ($file['name'] == "") exit($lang['random_13']);

	// Get field extension
	$filetype = strtolower(substr($file['name'],strrpos($file['name'],".")+1,strlen($file['name'])));

	// If not CSV, print message, exit
	if ($filetype != "csv") exit($lang['global_01'] . $lang['colon'] . " " . $lang['design_136']);

	// If CSV file, save the uploaded file (copy file from temp to folder) and prefix a timestamp to prevent file conflicts
	$file['name'] = APP_PATH_TEMP . date('YmdHis') . (defined('PROJECT_ID') ? "_pid" . PROJECT_ID : '') . "_fileupload." . $filetype;
	$file['name'] = str_replace("\\", "\\\\", $file['name']);

	// If moving or copying the uploaded file fails, print error message and exit	
	if (!move_uploaded_file($file['tmp_name'], $file['name'])) {
		if (!copy($file['tmp_name'], $file['name'])) exit($lang['random_13']);
	}
			
	// Now read the stored CSV file into an array
	$csv_array = array();
	if (($handle = fopen($file['name'], "rb")) !== false) {
		// Loop through each row
		while (($row = fgetcsv($handle, 0, ",")) !== false) {
			$csv_array[] = $row;
		}
		fclose($handle);
	}
	
	// Remove the saved file, since it's no longer needed
	unlink($file['name']);
	
	// Return the array
	return $csv_array;	
}

// Determine if being accessed by REDCap developer
function isDev($includeVanderbiltSuperUsers=false)
{
	return ($_SERVER['SERVER_NAME'] == '10.151.18.250' 
			|| ($includeVanderbiltSuperUsers && defined('USERID') && defined('SUPER_USER') && SUPER_USER && isVanderbilt()));
}

// When viewing a record on a data entry form, obtain record info (name, hidden_edit/existing_record, and DDE number)
function getRecordAttributes()
{
	global $double_data_entry, $user_rights, $table_pk, $hidden_edit;
	if ((PAGE == "DataEntry/index.php" || PAGE == "Mobile/data_entry.php") && isset($_GET['page']))
	{
		// Alter how records are saved if project is Double Data Entry (i.e. add --# to end of Study ID)
		$entry_num = ($double_data_entry && $user_rights['double_data'] != '0') ? "--".$user_rights['double_data'] : "";
		// First, define $fetched for use in the data entry form list
		if (isset($_POST['submit-action']) && isset($_POST[$table_pk])) 
		{
			$fetched = $_POST[$table_pk];
			// Rework $fetched for DDE if just posted (will have --1 or --2 on end)
			if ($double_data_entry && $user_rights['double_data'] != '0' && substr($fetched, -3) == $entry_num) {
				$fetched = substr($fetched, 0, -3);
			}
			// This record already exists
			$hidden_edit = 1;
		} 
		elseif (isset($_GET['id'])) 
		{
			$fetched = $_GET['id'];
		}
		// Check if record exists (hidden_edit == 1)
		if (isset($fetched) && (!isset($hidden_edit) || (isset($hidden_edit) && !$hidden_edit))) 
		{	
			$hidden_edit = (recordExists(PROJECT_ID, $fetched . $entry_num) ? 1 : 0);
		}
	}
	// Return values in form of array
	return array($fetched, $hidden_edit, $entry_num);
}

// Renders the home page header and footer with the specified content provided ehre
function renderPage($content)
{
	$objHtmlPage = new HtmlPage();
	$objHtmlPage->addExternalJS(APP_PATH_JS . "base.js");
	$objHtmlPage->addStylesheet("smoothness/jquery-ui-".JQUERYUI_VERSION.".custom.css", 'screen,print');
	$objHtmlPage->addStylesheet("style.css", 'screen,print');
	$objHtmlPage->addStylesheet("home.css", 'screen,print');
	$objHtmlPage->PrintHeader();
	print RCView::div(array('class'=>'space','style'=>'margin:10px 0;'), '&nbsp;') 
		. $content
		. RCView::div(array('class'=>'space','style'=>'margin:5px 0;'), '&nbsp;');
	$objHtmlPage->PrintFooter();
	exit;	
}

// Validate if an email address
function isEmail($email)
{
	return (preg_match("/^([_a-z0-9-']+)(\.[_a-z0-9-']+)*@([a-z0-9-]+)(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i", $email));
}

// Get current timezone name (e.g. America/Chicago). If cannot determine, return text "[could not be determined]".
function getTimeZone()
{
	global $lang;
	$timezone = (function_exists("date_default_timezone_get")) ? date_default_timezone_get() : ini_get('date.timezone');
	if (empty($timezone)) $timezone = $lang['survey_298'];
	return $timezone;
}

// Convert date format from DD-MM-YYYY to YYYY-MM-DD
function date_dmy2ymd($val)
{
	$val = trim($val);
	if ($val == '') return $val;
	list ($day, $month, $year) = explode('-', $val);
	return sprintf("%04d-%02d-%02d", $year, $month, $day);
}

// Convert date format from YYYY-MM-DD to DD-MM-YYYY
function date_ymd2dmy($val)
{
	$val = trim($val);
	if ($val == '') return $val;
	list ($year, $month, $day) = explode('-', $val);
	return sprintf("%02d-%02d-%04d", $day, $month, $year);
}

// Convert date format from MM-DD-YYYY to YYYY-MM-DD
function date_mdy2ymd($val)
{
	$val = trim($val);
	if ($val == '') return $val;
	list ($month, $day, $year) = explode('-', $val);
	return sprintf("%04d-%02d-%02d", $year, $month, $day);
}

// Convert date format from YYYY-MM-DD to MM-DD-YYYY
function date_ymd2mdy($val)
{
	$val = trim($val);
	if ($val == '') return $val;
	list ($year, $month, $day) = explode('-', $val);
	return sprintf("%02d-%02d-%04d", $month, $day, $year);
}

// Convert date, datetime, or datetime_seconds value from YMD/DMY/MDY to YMD/DMY/MDY
// Formats should be provided as ymd, dmy, or mdy (case insensitive).
function datetimeConvert($val, $origFormat, $returnFormat)
{
	// Array of possible formats
	$formats = array('ymd', 'dmy', 'mdy');
	// Trim value
	$val = trim($val);
	// Trim and make formats as lower case
	$origFormat   = strtolower(trim($origFormat));
	$returnFormat = strtolower(trim($returnFormat));
	// Make sure a correct format is given, else return False
	if (!in_array($origFormat,   $formats)) return false;
	if (!in_array($returnFormat, $formats)) return false;
	// If format not changing, then return value given
	if ($origFormat == $returnFormat) return $val;
	// Break up the value into date and time components
	list ($this_date, $this_time) = explode(" ", $val);
	// Convert original value to YMD first, if not already in YMD format
	if ($origFormat == 'mdy') {
		$this_date = date_mdy2ymd($this_date);
	} elseif ($origFormat == 'dmy') {
		$this_date = date_dmy2ymd($this_date);
	}
	// If returning in MDY or DMY format, then convert our date (currently in YMD) to that format.
	if ($returnFormat == 'mdy') {
		$this_date = date_ymd2mdy($this_date);
	} elseif ($returnFormat == 'dmy') {
		$this_date = date_ymd2dmy($this_date);
	}
	// Now combing date and time components, then trim, then return value
	return trim("$this_date $this_time");
}

// Output the script tag for a given JavaScript file
function callJSfile($js_file,$outputToPage=true)
{
	$output = "<script type=\"text/javascript\" src=\"" . APP_PATH_JS . $js_file . "\"></script>\n";
	if ($outputToPage) {
		print $output;
	} else {
		return $output;
	}
}

// Enable GZIP compression for webpages (if Zlib extention is enabled). 
// Return boolean if gzip is enabled for this "page" (i.e. request).
function enableGzipCompression()
{	
	global $fileDownloadPages;
	// Make sure we only enable comprression on webpages (as opposed to file downloads).
	// NOTE: For some reason, DataEntry/search.php gives issues with compression, so make an exception for it.
	if (!defined('PAGE') || (defined('PAGE') && (PAGE == "DataEntry/search.php" || in_array(PAGE, $fileDownloadPages)))) {
		define("GZIP_ENABLED", false);
	} else {
		// Compress the PHP output (uses up to 80% less bandwidth)
		ini_set('zlib.output_compression', 4096);
		ini_set('zlib.output_compression_level', -1);
		// Set flag if gzip is enabled on the web server
		define("GZIP_ENABLED", (function_exists('ob_gzhandler') && ini_get('zlib.output_compression')));
	}
	// Return value if gzip is now enabled
	return GZIP_ENABLED;
}

// Permanently delete the project from all db tables right now (as opposed to flagging it for deletion later)
function deleteProjectNow($project_id)
{
	// Get project title (app_title)
	$q = db_query("select app_title from redcap_projects where project_id = $project_id");
	$app_title = strip_tags(label_decode(db_result($q, 0)));
	// Get list of users with access to project
	$userList = str_replace("'", "", pre_query("select username from redcap_user_rights where project_id = $project_id and username != ''"));
	
	// For uploaded edoc files, set delete_date so they'll later be auto-deleted from the server
	db_query("update redcap_edocs_metadata set delete_date = '".date('Y-m-d H:i:s')."' where project_id = $project_id and delete_date is null");
	// Delete all project data and related info from ALL tables (most will be done by foreign keys automatically)
	db_query("delete from redcap_projects where project_id = $project_id");
	// Do other deletions manually because some tables don't have foreign key cascade deletion set
	db_query("delete from redcap_data where project_id = $project_id");
	// Don't actually delete these because they are logs, but simply remove any data-related info
	db_query("update redcap_log_view set event_id = null, record = null, form_name = null, miscellaneous = null where project_id = $project_id");
	db_query("update redcap_log_event set event_id = null, sql_log = null, data_values = null, pk = null where project_id = $project_id
				 and description != 'Delete project'");
	
	// Log the permanent deletion of the project
	$loggingDescription = "Permanently delete project";
	$loggingDataValues  = "project_id = $project_id,\napp_title = ".prep($app_title).",\nusernames: $userList";
	$loggingTable		= "redcap_projects";
	$loggingEventType	= "MANAGE";
	$loggingPage 		= (defined("CRON")) ? "cron.php" : PAGE;
	$loggingUser 		= (defined("CRON")) ? "SYSTEM"   : USERID;
	db_query("insert into redcap_log_event (project_id, ts, user, page, event, object_type, pk, data_values, description) values 
				($project_id, '".date("YmdHis")."', '$loggingUser', '$loggingPage', '$loggingEventType', '$loggingTable', 
				'$project_id', '$loggingDataValues', '$loggingDescription')");
}

// JSON Encode (for PHP 5.2.X and prior)
if (!function_exists('json_encode')) 
{
    function json_encode($data) 
	{
        switch ($type = gettype($data)) {
            case 'NULL':
                return 'null';
            case 'boolean':
                return ($data ? 'true' : 'false');
            case 'integer':
            case 'double':
            case 'float':
                return $data;
            case 'string':
                return '"' . addslashes($data) . '"';
            case 'object':
                $data = get_object_vars($data);
            case 'array':
                $output_index_count = 0;
                $output_indexed = array();
                $output_associative = array();
                foreach ($data as $key => $value) {
                    $output_indexed[] = json_encode($value);
                    $output_associative[] = json_encode($key) . ':' . json_encode($value);
                    if ($output_index_count !== NULL && $output_index_count++ !== $key) {
                        $output_index_count = NULL;
                    }
                }
                if ($output_index_count !== NULL) {
                    return '[' . implode(',', $output_indexed) . ']';
                } else {
                    return '{' . implode(',', $output_associative) . '}';
                }
            default:
                return ''; // Not supported
        }
    }
}

// JSON Decode (for PHP 5.2.X and prior)
if (!function_exists('json_decode'))
{
    function json_decode($json)
    {
        $comment = false;
        $out = '$x=';
        for ($i=0; $i<strlen($json); $i++)
        {
            if (!$comment)
            {
                if (($json[$i] == '{') || ($json[$i] == '['))
                    $out .= ' array(';
                else if (($json[$i] == '}') || ($json[$i] == ']'))
                    $out .= ')';
                else if ($json[$i] == ':')
                    $out .= '=>';
                else
                    $out .= $json[$i];
            }
            else
                $out .= $json[$i];
            if ($json[$i] == '"' && $json[($i-1)]!="\\")
                $comment = !$comment;
        }
		// Eval to set as array
        eval($out . ';');
		// Convert to object
		$object = new stdClass();
		foreach ($x as $key => $value) {
			$object->$key = $value;
		}
		unset($x);
        return $object;
    }
}

// Browse Shared Library form: Build the hidden form that sets Post values to be submitted to "log in"
// to the REDCap Shared Library.
function renderBrowseLibraryForm()
{
	global $institution, $user_firstname, $user_lastname, $user_email;
	// Check if cURL is loaded
	$onSubmitValidate = "";
	if (!function_exists('curl_init')) {
		// Set unique id
		$errorId = "curl_error_".substr(md5(rand()), 0, 8);
		//cURL is not loaded
		print "<div style='display:none' id='$errorId'>";
		curlNotLoadedMsg();
		print "</div>";		
		$onSubmitValidate = "onSubmit=\"$('#$errorId').show();return false;\"";
	}
	return "<form id='browse_rsl' method='post' $onSubmitValidate action='".SHARED_LIB_BROWSE_URL."'>
				<input type='hidden' name='action' value='browse'>
				<input type='hidden' name='user' value='" . md5($institution . USERID) . "'>
				<input type='hidden' name='first_name' value='".cleanHtml($user_firstname)."'>
				<input type='hidden' name='last_name' value='".cleanHtml($user_lastname)."'>
				<input type='hidden' name='email' value='".cleanHtml($user_email)."'>
				<input type='hidden' name='server_name' value='" . SERVER_NAME . "'>
				<input type='hidden' name='institution' value=\"".cleanHtml2(str_replace('"', '', $institution))."\">
				<input type='hidden' name='callback' value='" . SHARED_LIB_CALLBACK_URL . "?pid=".PROJECT_ID."'>
			</form>";
}
