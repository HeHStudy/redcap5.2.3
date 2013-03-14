<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


/********************************************************************************************
This file is used for doing a fresh installation of REDCap.
The page will guide you through the install process for getting everything setup.
********************************************************************************************/

error_reporting(0);

header("Expires: 0");
header("cache-control: no-store, no-cache, must-revalidate"); 
header("Pragma: no-cache");
// Declare current page with full path
define("PAGE_FULL", $_SERVER['PHP_SELF']);
// Declare current page
define("PAGE", basename(PAGE_FULL));
// Set constant to not display any blank options for drop-downs on this page
define('DROPDOWN_DISABLE_BLANK', true);
// Define DIRECTORY_SEPARATOR as DS for less typing
define("DS", DIRECTORY_SEPARATOR);
define('APP_PATH_DOCROOT', dirname(__FILE__) . DS);

//Get install version number and set the file paths correctly
if (isset($install_version)) {
	$css_path = "redcap_v" . $install_version . "/Resources/css/";
	$js_path  = "redcap_v" . $install_version . "/Resources/js/";
	$maindir  = "redcap_v" . $install_version . "/";	
} else {
	if (basename(dirname(__FILE__)) == "codebase") {
		// If this is a developer with 'codebase' folder instead of version folder, then use JavaScript to get version from query string instead
		if (isset($_GET['version'])) {
			$install_version = $_GET['version'];
		} else {
			// Redirect via JavaScript
			?>
			<script type="text/javascript">
			var urlChunks = window.location.href.split('/').reverse();
			window.location.href = window.location.href+'?version='+urlChunks[1].substring(8);
			</script>
			<?php
			exit;
		}
	} else {
		//Get the current version number to upgrade to from the folder name of "redcap_vX.X.X".
		$temp = explode("redcap_v", basename(dirname(__FILE__)));	
		$install_version = $temp[1];
	}
	$css_path = "Resources/css/";
	$js_path  = "Resources/js/";	
	$maindir  = "";
}
// Set constants for paths
define('APP_PATH_WEBROOT',  $maindir);
define('APP_PATH_IMAGES',   (($maindir == "") ? "" : "$maindir/") . "Resources/images/");
	

// Files with necessary functions
require_once dirname(__FILE__) . '/Config/init_functions.php';
require_once dirname(__FILE__) . "/ProjectGeneral/form_renderer_functions.php";


// Attempt to construct base url using other methods
list ($server_name, $port, $ssl, $page_full) = getServerNamePortSSL();
$app_path_webroot = getVersionFolderWebPath();
$app_path_webroot_full = ($ssl ? "https" : "http") . "://" . $server_name . $port . ((strlen(dirname($app_path_webroot)) <= 1) ? "" : dirname($app_path_webroot)) . "/";
// Make sure $app_path_webroot_full is correct. Remove version directory if ends with version directory.
if (substr($app_path_webroot_full, -15) == "/redcap_v{$install_version}/") {
	$app_path_webroot_full = dirname($app_path_webroot_full) . '/';
}

// Language: Call the correct language file for this project (default to English)
$lang = getLanguage('English');

?>
<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\" \"http://www.w3.org/TR/html4/strict.dtd\">
<HTML>

<HEAD>
	<TITLE>REDCap <?php print $install_version ?> Installation Module</TITLE>
	<meta name="googlebot" content="noindex, noarchive, nofollow, nosnippet">
	<meta name="robots" content="noindex, noarchive, nofollow">
	<meta name="slurp" content="noindex, noarchive, nofollow, noodp, noydir">
	<meta name="msnbot" content="noindex, noarchive, nofollow, noodp">
	<meta http-equiv="Cache-Control" content="no-cache">
	<meta http-equiv="Pragma" content="no-cache">
	<meta http-equiv="expires" content="0">
	<link rel="stylesheet" type="text/css" media="screen,print" href="<?php print $css_path ?>smoothness/jquery-ui-<?php echo JQUERYUI_VERSION ?>.custom.css"/>
	<link rel="stylesheet" type="text/css" media="screen,print" href="<?php print $css_path ?>style.css"/>
	<link rel="stylesheet" type="text/css" media="screen,print" href="<?php print $css_path ?>home.css"/>
	<script type="text/javascript" src="<?php print $js_path ?>base.js"></script>
</HEAD>
<BODY bgcolor="#F0F0F0">


<script type="text/javascript">
var app_path_webroot = '<?php echo APP_PATH_WEBROOT ?>';
var app_path_images = '<?php echo APP_PATH_IMAGES ?>';
$(function(){
	// Test HTTP request to REDCap consortium server (to see if can report stats automatically)
	var thisAjax = $.get(app_path_webroot+'Test/server_ping.php', { }, function(data) {
		if (data.length == 0 || data != "1") {
			$('form#form input[name="auto_report_stats"]').val('0');
		}
	});
	// If does not finish after X seconds, then set stats reporting to "manual"
	var maxAjaxTime = 8; // seconds
	setTimeout(function(){
		if (thisAjax.readyState == 1) {
			thisAjax.abort();
			$('form#form input[name="auto_report_stats"]').val('0');
		}
	},maxAjaxTime*1000);	
});
</script>

<style type='text/css'>
.label, .data {
	background:#F0F0F0 url('<?php echo $maindir ?>Resources/images/label-bg.gif') repeat-x scroll 0 0;
	border:1px solid #CCCCCC;
	font-size:12px;
	font-weight:bold;
	font-family:arial;
	padding:5px 10px;
}
.label a:link, .label a:visited, .label a:active, .label a:hover { font-size:12px; font-family: Arial; }
.form_border { width: 100%;	}
#sub-nav { font-size:60%; }
/* Weird issue with Firefox/Chrome with textboxes on this page */
.x-form-text { height: 22px; }
</style>

<?php
//PAGE HEADER	
print "<table align=center><tr><td style='border:2px solid #D0D0D0;width:700px;padding: 10px 15px 5px 15px;background:#FFFFFF;'>";
print "<table width=100% cellpadding=0 cellspacing=0><tr><td valign=top align=left>
			<h3 style='font-size:20px;color:#800000;font-family:Arial;'>REDCap " . "$install_version Installation Module</h3>
	   </td><td valign=top style='text-align:right;'>
			<img src='" . $maindir . "Resources/images/redcaplogo_small.png'>
	   </td></tr></table>";

	   

/**
 * GENERATE INSTALLATION SQL
 */ 
if ($_SERVER['REQUEST_METHOD'] == 'POST') {	

	// print "<pre>";print_r($_POST);print "</pre>";	
	
	// Render textbox with SQL
	print  "<hr size=1>
			<p>
				<b>STEP 4: Create the REDCap database tables</b><br><br>
				Copy the SQL in the box below and execute it in phpMyAdmin (or another MySQL client) in the MySQL database where
				you wish to place the REDCap database tables.
			</p>";			
	// Render textarea box for putting SQL
	print  "<p><textarea style='font-family:Arial;font-size:11px;width:90%;height:150px;' readonly='readonly' onclick='this.select();'>\n"
		.  "-- ----------------------------------------- --\n-- REDCap Installation SQL --\n-- ----------------------------------------- --\n";
	// Include SQL from install.sql and install_data.sql files as the base table structure and initial values needed
	include dirname(__FILE__) . "/Resources/sql/install.sql";
	include dirname(__FILE__) . "/Resources/sql/install_data.sql";
	// Now add the custom site values
	print "\n\n-- Add custom site configuration values --\n";
	foreach ($_POST as $this_field=>$this_value) {
		print "UPDATE redcap_config SET value = '".prep($this_value)."' WHERE field_name = '$this_field';\n";
	}
	// Add new version number
	print  "UPDATE redcap_config SET value = '$install_version' WHERE field_name = 'redcap_version';\n";
	// Include SQL to auto-create demo project(s)
	include dirname(__FILE__) . "/Resources/sql/create_demo_db1.sql"; 
	include dirname(__FILE__) . "/Resources/sql/create_demo_db4.sql";
	include dirname(__FILE__) . "/Resources/sql/create_demo_db2.sql";
	include dirname(__FILE__) . "/Resources/sql/create_demo_db3.sql";
	include dirname(__FILE__) . "/Resources/sql/create_demo_db5.sql";
	include dirname(__FILE__) . "/Resources/sql/create_demo_db6.sql";
	include dirname(__FILE__) . "/Resources/sql/create_demo_db7.sql";
	include dirname(__FILE__) . "/Resources/sql/create_demo_db8.sql";
	include dirname(__FILE__) . "/Resources/sql/create_demo_db9.sql";
	include dirname(__FILE__) . "/Resources/sql/create_demo_db10.sql";
	print  "</textarea></p>";	
	
	// Configuration Test
	print  "<br><hr size=1>
			<p>
				<b>STEP 5: Configuration test</b><br><br>
				After you have successfully executed the SQL in the box above, the installation process is almost complete. 
				The only thing left to do is to navigate to the 
				<a href='{$maindir}Test/index.php' style='text-decoration:underline;font-weight:bold;'>REDCap Configuration Test</a> 
				page to ensure that all of REDCap's essential components are in place. If all the test's are successful, 
				it will give you the link for accessing REDCap. 
			</p>";	
	
	
	
	
	print  "<br><br><br>
		</td></tr></table>
		</BODY>
		</HTML>";
	exit;

}	   

//Introduction
print  "<hr size=1>
		<p style='margin-top:25px;'>
			This page will guide you through the process of installing <font color=#800000>REDCap version ".$install_version."</font> on your system. 
			At this point, you must have a MySQL database server running in order to continue with the installation process. If you do not and 
			require help, go to the 
			<a href='https://iwg.devguard.com/trac/redcap/wiki' target='_blank' style='text-decoration:underline;'>REDCap Wiki</a> and click on <font color=#800000>
			'Third-party software required for REDCap'</font>. Please return to this page when you are done. We recommend that you use 
			<a href='http://www.phpmyadmin.net' target='_blank' style='text-decoration:underline;'>phpMyAdmin</a> (a third-party open source web
			application) to interface with the MySQL server.
		</p>
		<hr size=1>";

print  "<p>
			<b>STEP 1: Create a MySQL database/schema and user (using phpMyAdmin)</b><br><br>
			<i>NOTE: Depending in the version of phpMyAdmin that you are using, the location of some of the menu items may be different than described in the
			following sections.</i><br><br>
			
			To begin, you will first need to create a MySQL database (i.e. schema) in which to place the REDCap tables. 
			Open the main page of phpMyAdmin. In the field under the heading 'Create new database' 
			type in the name of the MySQL database/schema you wish to create, and click the CREATE button. 
			Record the name of the database for later use in this process. For simplicity's sake, it is often best to name it 'redcap'. 
			A message saying that the database has been created will appear.<br><br>
			
			Whenever you create a MySQL database, you will want to create a MySQL user for that database. This user has nothing to do with the REDCap users who will be 
			set up later in the installation process. It is only used to communicate with the MySQL database server. All requests made by the REDCap users 
			will be performed using the authority granted by the MySQL database server to this user. On the main page of phpMyAdmin click the 'Privileges' 
			link, and then near the middle of the page click the 'Add a new User' link. Enter the username and password of your choice and then the MySQL server hostname for your user, then click 
			the GO button at the bottom right hand corner of the screen. A message saying that the new user has been added will appear. (Record the hostname, 
			username, and password information for later use in this process.) On the next page you will define that user's privileges. In the section 
			'database-specific privileges', choose the database that you wish this user to have rights to by selecting it in the pulldown menu. The next page 
			will then come up for you to set the user's privileges for that database. Select everything in the 'Data' box (check SELECT, INSERT, UPDATE, DELETE) 
			and click the GO button. A message saying that the privileges for the new user have been updated will appear. You have now successfully set up a 
			MySQL database with a user.
		</p>
		<hr size=1>";

print  "<p>
			<b>STEP 2: Add MySQL connection values to 'database.php'</b><br><br>
			You now need to set up the database connection file that will allow REDCap to connect to the MySQL database you just created. 
			This database connection file will store the hostname, username, password, and database/schema name for that MySQL database.
			Find the file 'database.php' (which sits under your main REDCap directory of your web server) and open it for editing in a text editor
			of your choice. Add your MySQL database connection values (hostname, database name, username, password) to that file by replacing the
			placeholder values in single quotes. Also, while still in the 'database.php' file, add a random value of your choosing 
			for the \$salt variable at the bottom of the page, preferably an alpha-numeric string with 8 characters or more. 
			(This value wll be used for de-identification hashing in the Data Export module. Do NOT change the \$salt value once it has been 
			set initially.) If you have not yet performed this step, you will likely see an error below. Once you have added the values to
			'database.php', reload this page to test it.<br><br>";
	
// Path to database.php
$cxn_file_path = dirname(dirname(__FILE__)) . DS . "database.php";

// Could not find database.php
if (!include $cxn_file_path) {
	exit("<b style='color:red;'>ERROR:</b> REDCap could not find 'database.php'. Please find it and place it at the following 
		  location on your web server, and then reload this page: <b>$cxn_file_path</b>");
// Found database.php
} else {
	//Check to see if all variables are accounted for inside the cxn file. If not, ask to fix file and try again.
	if (!isset($username) || !isset($password) || !isset($db) || !isset($hostname)) {
		print  "<b style='color:red;'>ERROR:</b> REDCap could not find all the following variables in 'database.php' that are necessary
				for a database connection: <b>\$hostname</b>, <b>\$username</b>, <b>\$password</b>, <b>\$db</b>. All four (4) variables are needed. Please add
				any that are missing and reload this page.";
		exit;
	// Found connection values
	} else {
		print "<b>Now attempting connection to database server...</b><br>";			
		$conn = mysqli_connect($hostname, $username, $password, $db);
		if (!$conn) {
			exit("<b style='color:red;'>ERROR:</b> The hostname ($hostname) / username ($username) / password (XXXXXX) combination in your database connection file [$cxn_file_path] could not connect to the server. Please check their values."); 
		}
		print  "<b style='color:green;'>Connection to the MySQL database '$db' was successful!</b><br><br>";
		// Get the SALT variable, which is institutional-unique alphanumeric value. 
		if (!isset($salt) || $salt == "") {
			// Warn user that the SALT was not defined in the connection file and give them new salt		
			exit(  "<b style='color:red;'>ERROR:</b> REDCap could not find the variable <b>\$salt</b> defined in [$cxn_file_path].<br><br> 
					Please open the file for editing and add code below after your database connection variables and then
					reload this page. (The value was auto-generated, but you may use any random value of your choosing, 
					preferably an alpha-numeric string with 8 characters or more.<br><br>
					<b>\$salt = '".substr(md5(rand()), 0, 10)."';</b>");
		}
	}
}

print  "</p>
		<hr size=1>";


print  "<p>
			<b>STEP 3: Customize values for your server and institution</b><br><br>
			Set the values below for your site's initial configuration. You will be able to change these after this installation process
			by going to the System Config tab in the Control Center. (For the initial installation, we highly recommend that you set the 
			global and default project-level authentication methods as \"None (Public)\" and then modify them afterward once you have
			REDCap fully up and running.) When you have set all the values, click the SUBMIT button at the bottom of the page.
		</p>
		<p style='color:#800000;'>
			<i>NOTE: All the settings below can be easily modified, if needed, after you have completed the installation process.</i>
		</p>";
// Get files for rendering form
include dirname(__FILE__) . "/ControlCenter/system_config_metadata.php";
// Pre-fill form with default values
$form_data = array( "system_offline" => "0",
					"auth_meth_global" => "none",
					"auth_meth" => "none",
					"login_autocomplete_disable" => "0",
					"superusers_only_create_project" => "0",
					"superusers_only_move_to_prod" => (isDev() ? "0" : "1"),
					"autologout_timer" => "30",
					"enable_plotting" => "0",
					"auto_report_stats" => (isDev() ? "0" : "1"),
					"shibboleth_username_field" => "none",
					"display_nonauth_projects" => "1",
					"homepage_contact" => "Rob Taylor (343-9024)",
					"homepage_contact_email" => (isDev() ? "rob.taylor@vanderbilt.edu" : "email@yoursite.edu"),
					"edoc_field_option_enabled" => "1",
					"edoc_storage_option" => "0",
					"footer_links" => "http://www.mc.vanderbilt.edu/, VUMC\nhttp://www.mc.vanderbilt.edu/diglib/, DigLib\nhttps://phonedirectory.vanderbilt.edu/cdb/, People Finder\nhttps://www.mc.vanderbilt.edu/gcrc/, GCRC",
					"footer_text" => "Vanderbilt University | 1211 22nd Ave S, Nashville, TN 37232 (615) 322-5000",
					"project_language" => "English",
					"project_contact_name" => "Rob Taylor (343-9024)",
					"project_contact_email" => (isDev() ? "rob.taylor@vanderbilt.edu" : "email@yoursite.edu"),
					"project_contact_prod_changes_name" => "Rob Taylor (343-9024)",
					"project_contact_prod_changes_email" => (isDev() ? "rob.taylor@vanderbilt.edu" : "email@yoursite.edu"),
					"institution" => "Vanderbilt University",
					"site_org_type" => "Vanderbilt Institute for Clinical and Translational Research",
					"login_logo" => "https://www.mc.vanderbilt.edu/victr/secure/redcap/vumc.png",
					"shared_library_enabled" => "1",
					"sendit_enabled" => "1",
					"google_translate_enabled" => "1",
					"language_global" => "English",
					"api_enabled" => "1",
					"identifier_keywords" => $identifier_keywords_default,
					"logout_fail_limit" => '5',
					'logout_fail_window' => '15',
					'display_project_logo_institution' => '0',
					'enable_url_shortener' => '1'
				  );
// Render form
form_renderer($elements, $form_data);


print  "
<br><br><br><br></td></tr></table>
</BODY>
</HTML>";
