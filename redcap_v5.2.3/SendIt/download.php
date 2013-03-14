<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

// Disable authentication
define("NOAUTH", true);
// Call config file
require_once dirname(dirname(__FILE__)) . '/Config/init_global.php';
require_once APP_PATH_CLASSES . 'Message.php';



// Check if the URL is pointing to the correct version of REDCap specified for this project. If not, redirect to correct version.
check_version();

// Initial variables
$key = trim(substr(trim($_SERVER['QUERY_STRING']), 0 , 25));
$error = '';


// Check file's expiration and if key is valid
if (strlen($key) > 0)
{
	$query = "SELECT * FROM redcap_sendit_recipients rsr
		JOIN redcap_sendit_docs rsd ON rsr.document_id = rsd.document_id
		WHERE guid = '$key'";
	$result = db_query($query);
	
	if (db_num_rows($result))
	{
		$row = db_fetch_assoc($result);
		$expireDate = $row['expire_date'];
		
		if ($expireDate < NOW)
			$error = $lang['sendit_36'];
	}
	else
	{
		$error = $lang['sendit_37']; //invalid key
	}
}
else
{
	$error = $lang['sendit_37']; //no key was provided
}


// Obtain the size of the file in MB
$doc_size = round_up($row['doc_size']/1024/1024);



// Process the password submitted and begin file download
if ( isset($_POST['submit']) )
{
	if ( $row['pwd'] == md5(trim($_POST['pwd'])) )
	{
		
		// If user requested confirmation, then send them email (but only the initial time it was downloaded, to avoid multiple emails)
		if ($row['send_confirmation'] == 1 && $row['sent_confirmation'] == 0)
		{		
			// Get the uploader's email address
			$sql = "SELECT user_email FROM redcap_user_information WHERE username = '{$row['username']}' limit 1";
			$uploader_email = db_result(db_query($sql), 0);
			
			// Send confirmation email to the uploader
			$body =    "<html><body style=\"font-family:Arial;font-size:10pt;\">
						{$lang['sendit_46']} \"{$row['doc_orig_name']}\" ($doc_size MB){$lang['sendit_47']} {$row['email_address']} {$lang['global_51']} 
						" . date('l, F j, Y (g:i A)') . ".<br><br><br>
						{$lang['sendit_48']} <a href=\"" . APP_PATH_WEBROOT_FULL . "\">REDCap Send-It</a>!
						</body></html>";
			$email = new Message();
			$email->setFrom($uploader_email); // Send it from themselves
			$email->setTo($uploader_email);
			$email->setBody($body);
			$email->setSubject('[REDCap Send-It] '.$lang['sendit_49']);
			$email->send();
		}		
		
		// Log this download event in the table
		$recipientId = $row['recipient_id'];
		$querylog = "UPDATE redcap_sendit_recipients SET download_date = '".NOW."', download_count = (download_count+1),
					 sent_confirmation = 1 WHERE recipient_id = $recipientId";
		db_query($querylog);
		
		// Set flag to determine if we're pulling the file from the file system or redcap_docs table (legacy storage for File Repository)
		$pullFromFileSystem = ($row['location'] == '3' || $row['location'] == '1');
				
		// If file is in File Repository, retrieve it from redcap_docs table (UNLESS we determine that it's in the file system)
		if ($row['location'] == '2')
		{
			// Determine if in redcap_docs table or file system and then download it
			$query = "SELECT d.*, e.doc_id as edoc_id FROM redcap_docs d LEFT JOIN redcap_docs_to_edocs e 
					  ON e.docs_id = d.docs_id LEFT JOIN redcap_edocs_metadata m ON m.doc_id = e.doc_id 
					  WHERE d.docs_id = " . $row['docs_id'];
			$result = db_query($query);
			$row = db_fetch_assoc($result);
			// Check location
			if ($row['edoc_id'] === NULL) {
				// Download file from redcap_docs table
				header('Pragma: anytextexeptno-cache', true);
				header('Content-Type: '. $row['docs_type']);
				
				header('Content-Disposition: attachment; filename='. str_replace(' ', '', $row['docs_name']));
				ob_clean();
				flush();			
				print $row['docs_file'];
			} else {
				// Set flag to pull the file from the file system instead
				$pullFromFileSystem = true;
				// Reset values that were overwritten
				$row['docs_id'] = $row['edoc_id'];
				$row['location'] = '2';
			}
		}
		// If file stored on form or uploaded from Home page Send-It location, retrieve it from edocs location
		if ($pullFromFileSystem)
		{
			// Retrieve values for loc=3 (since loc=1 values are already stored in $row) or for loc=2 if stored in file system
			if ($row['location'] == '3' || $row['location'] == '2') 
			{
				$query = "SELECT project_id, mime_type as doc_type, doc_name as doc_orig_name, stored_name as doc_name 
						  FROM redcap_edocs_metadata WHERE doc_id = " . $row['docs_id'];
				$result = db_query($query);
				$row = db_fetch_assoc($result);
			}
			
			// Retrieve from EDOC_PATH location
			if (!$edoc_storage_option) 
			{			
				// Download file
				header('Pragma: anytextexeptno-cache', true);
				header('Content-Type: '. $row['doc_type']);
				
				header('Content-Disposition: attachment; filename=' . str_replace(' ', '', $row['doc_orig_name']));
				ob_clean();
				flush();
				readfile(EDOC_PATH . $row['doc_name']);
			}
			// Retrieve from external server via webdav
			elseif ($edoc_storage_option) 
			{
				//Download using WebDAV
				include APP_PATH_WEBTOOLS . 'webdav/webdav_connection.php';
				$wdc = new WebdavClient();
				$wdc->set_server($webdav_hostname);
				$wdc->set_port($webdav_port); $wdc->set_ssl($webdav_ssl);
				$wdc->set_user($webdav_username);
				$wdc->set_pass($webdav_password);
				$wdc->set_protocol(1); //use HTTP/1.1
				$wdc->set_debug(false);
				if (!$wdc->open()) {
					exit("{$lang['global_01']}: {$lang['sendit_39']}");
				}
				$http_status = $wdc->get($webdav_path . $row['doc_name'], $contents); //$contents is produced by webdav class
				$wdc->close();	
				
				// Download file
				header('Pragma: anytextexeptno-cache', true);
				header('Content-Type: '. $row['doc_type']);
				
				header('Content-Disposition: attachment; filename=' . str_replace(' ', '', $row['doc_orig_name']));
				ob_clean();
				flush();			
				print $contents;				
			}
		
		}
				
				
		## Logging
		if ($row['project_id'] != "" && $row['project_id'] != "0") {
			// Get project id if file is existing project file
			define("PROJECT_ID", $row['project_id']); 
		}
		log_event($querylog,"redcap_sendit_recipients","MANAGE",$recipientId,"recipient_id = $recipientId","Download file (Send-It)");
				
		// Stop here now that file has been downloaded
		exit;
		
	}
	else
	{
		$error = $lang['sendit_40'];
	}
}


// Initialize page display object
$objHtmlPage = new HtmlPage();
$objHtmlPage->addExternalJS(APP_PATH_JS . "base.js");
$objHtmlPage->addStylesheet("smoothness/jquery-ui-".JQUERYUI_VERSION.".custom.css", 'screen,print');
$objHtmlPage->addStylesheet("style.css", 'screen,print');
$objHtmlPage->addStylesheet("home.css", 'screen,print');
$objHtmlPage->PrintHeader();


?>

<div style="padding:20px 0;">
	<a href="<?php echo APP_PATH_WEBROOT_FULL ?>"><img src="<?php echo APP_PATH_IMAGES . 'redcaplogo.gif' ?>" title="REDCap" alt="REDCap"></a>
</div>

<div style="padding-top:8px;font-size: 18px;border-bottom:1px solid #aaa;padding-bottom:2px;">
	<img src='<?php echo APP_PATH_IMAGES ?>mail_arrow.png'> Send-It: <span style="color:#777;"><?php echo $lang['sendit_41'] ?></span>
</div>

<?php 
if ($error == '' || $error == 'Password incorrect') { 
	?>
	<p style="padding:15px 0 20px;">
		<b><?php echo $lang['global_24'] . $lang['colon'] ?></b><br>
		<?php echo $lang['sendit_43'] ?> <b><?php echo $doc_size ?> MB</b>.
	</p>
	
	<div id="formdiv">
		<form action="<?php echo PAGE_FULL.'?'.$_SERVER['QUERY_STRING'] ?>" method="post" id="Form1" name="Form1">
		<?php echo $lang['sendit_44'] ?> &nbsp; 
		<input type="password" name="pwd" value="" /> &nbsp; 
		<input type="submit" name="submit" value="Download File" onclick="
			document.getElementById('formdiv').style.visibility='hidden'; 
			if (document.getElementById('errormsg') != null) document.getElementById('errormsg').style.visibility='hidden'; 
			setTimeout(function(){
				$('#progress').toggle('blind','fast');
			},1000);
			return true;
		"/>
		</form>
	</div>
	
	<div id="progress" class="darkgreen" style="display:none;font-weight:bold;">
		<img src="<?php echo APP_PATH_IMAGES ?>tick.png" class="imgfix"> <?php echo $lang['sendit_58'] ?>
	</div>
	<?php
}

// Display error message, if error occurs
if ($error != '') {
	?>
	<p id="errormsg" style='padding-top:5px;font-weight:bold;color:#800000;'>
		<img src="<?php echo APP_PATH_IMAGES ?>exclamation.png" class="imgfix"> 
		<?php echo $error ?>.
	</p>
	<?php
}

print "<br><br><br>";

$objHtmlPage->PrintFooter();
