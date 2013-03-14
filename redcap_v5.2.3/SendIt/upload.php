<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_global.php';
require_once APP_PATH_CLASSES . 'Message.php';





/**
 * FUNCTIONS
 */
function createUniqueFilename($extension)
{
	// explode the IP of the remote client into four parts
	$ipbits = explode(".", $_SERVER["REMOTE_ADDR"]);
	
	// Get both seconds and microseconds parts of the time
	list($usec, $sec) = explode(" ",microtime());
	
	// Fudge the time we just got to create two 16 bit words
	$usec = (integer) ($usec * 65536);
	$sec = ((integer) $sec) & 0xFFFF;
	
	// convert the remote client's IP into a 32 bit hex number, then tag on the time.
	// Result of this operation looks like this: xxxxxxxx-xxxx-xxxx
	$name = sprintf("%08x-%04x-%04x",($ipbits[0] << 24)
			| ($ipbits[1] << 16)
			| ($ipbits[2] << 8)
			| $ipbits[3], $sec, $usec);
	
	// add the extension and return the filename
	return $name.$extension;
}
function getFileExtension($filename)
{
	$pos = strrpos($filename, '.');
	
	if ($pos === false)
		return false;
	else
		return substr($filename, $pos);
}







// User should not have "loc" in the URL unless submitted file, so refresh page if so.
if ($_SERVER['REQUEST_METHOD'] != 'POST' && $_GET['loc'] == "1") {
	redirect(PAGE_FULL);
	exit;
} 


// Set initial variables
$yourName = $user_firstname. ' '. $user_lastname;
$yourEmail = $user_email;
$errors = array();
$fileLocation = (isset($_GET['loc']) ? $_GET['loc'] : 1);
$fileId = ((isset($_GET['id']) && is_numeric($_GET['id'])) ? $_GET['id'] : 0);


// Check if user truly has rights to file AND get filename of file if from file repository or data entry page (i.e. it has already been uploaded)
if ($fileLocation != 1) {

	// Ensure user has rights to a project that this file is attached to
	if ($fileLocation == 2) { //file repository
		$sql = "select 1 from redcap_docs d, redcap_user_rights u where d.docs_id = $fileId and d.project_id = u.project_id 
				and u.username = '$userid' limit 1";
	} elseif ($fileLocation == 3) { //data entry form
		$sql = "select 1 from redcap_edocs_metadata d, redcap_user_rights u where d.doc_id = $fileId and d.project_id = u.project_id 
				and u.username = '$userid' limit 1";
	}
	$q = db_query($sql);
	if (db_num_rows($q) < 1) {
		// User does not have access to this file!
		exit($lang['sendit_01']);
	}
	
	// Get filename
	if ($fileLocation == 2) //file repository
		$query = "SELECT project_id, docs_name as docName, docs_name as storedName, docs_size as docSize, docs_type as docType FROM redcap_docs 
			WHERE docs_id = $fileId";
	else if ($fileLocation == 3) //data entry form
		$query = "SELECT project_id, doc_name as docName, stored_name as storedName, doc_size as docSize, mime_type as docType FROM redcap_edocs_metadata 
			WHERE doc_id = $fileId";		
	$result = db_query($query);
	$row = db_fetch_assoc($result);
	// Get file metadata
	$originalFilename = $row['docName'];	
	$fileSize = $row['docSize'];
	$newFilename = $row['storedName'];
	$fileType = $row['docType'];
	// Get project id for logging purposes
	define("PROJECT_ID", $row['project_id']);
	
}



/**
 * PROCESS THE POSTED FORM ELEMENTS
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST') 
{
	// Form elements
	$formFields = array('recipients', 'subject', 'message', 'confirmation', 'expireDays');
	foreach ($formFields as $field) {
		$$field = (isset($_POST[$field])) ? $_POST[$field] : '';
	}
}



/**
 * CHECK FOR ANY FILE UPLOAD ERRORS
 */
$upload_errors = false;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $fileLocation == 1)
{
	// If file is larger than PHP upload limits
	if (empty($_FILES) || $_FILES['file']['error'] != UPLOAD_ERR_OK)
	{
		// Set error msg
		$errors[] = $lang['sendit_02'] . " " . $lang['docs_63'];
		// Set flag
		$upload_errors = true;
	}
	// If file is larger than REDCap upload limits
	elseif (($_FILES['file']['size']/1024/1024) > maxUploadSizeSendit())
	{
		// Delete uploaded file from server
		unlink($_FILES['file']['tmp_name']);
		// Set error msg
		$errors[] = $lang['sendit_03'] . ' (' . round_up($_FILES['file']['size']/1024/1024) . ' MB) ' . 
					 $lang['sendit_04'] . ' ' . maxUploadSizeSendit() . ' MB ' . $lang['sendit_05'];
		// Set flag
		$upload_errors = true;
	}
	
	// Unset some posted variables to reset the page without having to reload it
	if ($upload_errors)
	{
		$_POST  = array();
		$_FILES = array();
		$fileId = '';
		unset($_GET['id']);
		$subject = trim(str_replace(array("\"","[REDCap Send-It]"), array("&quot;",""), $subject));
	}
}



/**
 * PROCESS AND SAVE FILE IF WAS UPLOADED WITHOUT ERRORS
 */ 
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$upload_errors) 
{	
	// Save file if one was provided
	if (isset($_FILES['file']['tmp_name']) && strlen($_FILES['file']['tmp_name']) > 0 )
	{
		$tempFilename = $_FILES['file']['tmp_name'];
		$originalFilename = str_replace(' ', '', $_FILES['file']['name']);
		$newFilename = date('YmdHis') . "_sendit_" . createUniqueFilename(getFileExtension($originalFilename));
		$fileType = $_FILES['file']['type'];
		$fileSize = $_FILES['file']['size'];			
		
		// Move uploaded file to edocs folder
		if (!$edoc_storage_option) 
		{
			if (!move_uploaded_file($tempFilename, EDOC_PATH . $newFilename)) {
				$errors[] = $lang['sendit_02'];
			}
		} 
		// Move to external server via webdav
		else 
		{
			require_once APP_PATH_CLASSES  . "WebdavClient.php";
			require_once APP_PATH_WEBTOOLS . "webdav/webdav_connection.php";
			$wdc = new WebdavClient();
			$wdc->set_server($webdav_hostname);
			$wdc->set_port($webdav_port); $wdc->set_ssl($webdav_ssl);
			$wdc->set_user($webdav_username);
			$wdc->set_pass($webdav_password);
			$wdc->set_protocol(1); // use HTTP/1.1
			$wdc->set_debug(false); // enable debugging?
			if (!$wdc->open()) {
				$errors[] = $lang['sendit_02'];
			}
			if (substr($webdav_path,-1) != "/" && substr($webdav_path,-1) != "\\") {
				$webdav_path .= '/';
			}
			if ($fileSize > 0) {		
				$fp      = fopen($tempFilename, 'rb');
				$content = fread($fp, filesize($tempFilename));
				fclose($fp);
				$http_status = $wdc->put($webdav_path . $newFilename, $content);
				
			}
		}
	}
	
	// Validate all the email addresses
	$emailAddresses = str_replace(array("\r\n","\n","\r",";"," "), array(',',',',',',',',''), $recipients);
	$emailAddresses = explode(',', $emailAddresses);
	foreach($emailAddresses as $value)
	{
		if (trim($value) != '')
		{
			if (preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i', trim($value)) == 0)
			{
				$errors[] = $lang['sendit_06'];
				break;
			}
		}
	}
	
	// If no errors exist during upload, then process (add to tables, send emails, etc.)
	if (count($errors) == 0)
	{
		$send = (isset($_POST['confirmation'])) ? 1 : 0;
		$expireDate = date('Y-m-d H:i:s', strtotime("+$expireDays days"));
		$expireYear = substr($expireDate, 0, 4);
		$expireMonth = substr($expireDate, 5, 2);
		$expireDay = substr($expireDate, 8, 2);
		$expireHour = substr($expireDate, 11, 2);
		$expireMin = substr($expireDate, 14, 2);
		
		// Add entry to sendit_docs table
		$query = "INSERT INTO redcap_sendit_docs (doc_name, doc_orig_name, doc_type, doc_size, send_confirmation, expire_date, username, 
					location, docs_id, date_added) 
				  VALUES ('$newFilename', '".prep($originalFilename)."', '$fileType', '$fileSize', $send, '$expireDate', '$userid', 
					$fileLocation, $fileId, '".NOW."')";
		db_query($query);
		$newId = db_insert_id();
		
		// Logging
		if ($fileLocation == 1) {
			$logDescrip = "Upload and send file (Send-It)";
		} elseif ($fileLocation == 2) {
			$logDescrip = "Send file from file respository (Send-It)";
		} elseif ($fileLocation == 3) {
			$logDescrip = "Send file from data entry form (Send-It)";
		}
		log_event($query,"redcap_sendit_docs","MANAGE",$newId,"document_id = $newId",$logDescrip);
				
		// Set email subject
		$subject_prefix = "[REDCap Send-It] ";
		if ($subject == '') {
			$subject = $subject_prefix . $lang['sendit_57'] . ' ' . $yourName;
		} else {
			$subject = $subject_prefix . $subject;
		}
		$subject = html_entity_decode($subject, ENT_QUOTES);
		
		// Set email From address
		$fromEmailTemp = 'user_email' . ((isset($_POST['emailFrom']) && $_POST['emailFrom'] > 1) ? $_POST['emailFrom'] : '');
		$fromEmail = $$fromEmailTemp;
		if (!isEmail($fromEmail)) $fromEmail = $user_email;
		
		// Begin set up of email to send to recipients
		$email = new Message();
		$email->setFrom($fromEmail);
		$email->setSubject($subject);
		
		// Loop through each recipient and send email
		$successfulEmails = array();
		$failedEmails = array();
		foreach ($emailAddresses as $value)
		{
			// If a non-blank email address AND not a duplicated email address
			if (trim($value) != '' && !in_array($value, $successfulEmails))
			{
				// create key for unique url
				$key = strtoupper(substr(uniqid(md5(mt_rand())), 0, 25));
				
				// create password
				$pwd = generateRandomHash(8);
		
				$query = "INSERT INTO redcap_sendit_recipients (email_address, sent_confirmation, download_date, download_count, document_id, guid, pwd) 
						  VALUES ('$value', 0, NULL, 0, $newId, '$key', '" . md5($pwd) . "')";
				$q = db_query($query);
				
				// Download URL
				$url = APP_PATH_WEBROOT_FULL. 'redcap_v'. $redcap_version. '/SendIt/download.php?'. $key;
				
				// Message from sender
				$note = "";
				if ($_POST['message'] != "") {
					$note = "$yourName {$lang['sendit_56']}<br>" . nl2br(strip_tags(html_entity_decode($_POST['message'], ENT_QUOTES))) . '<br>';
				}
				
				// Email body
				$body =    "<html><body style=\"font-family:Arial;font-size:10pt;\">
							$yourName {$lang['sendit_51']} \"$originalFilename\" {$lang['sendit_52']} " .
							date('l, F j, Y (g:i A)', mktime( $expireHour, $expireMin, 0, $expireMonth, $expireDay, $expireYear)) . ".
							{$lang['sendit_53']}<br><br>
							{$lang['sendit_54']}<br>
							<a href=\"$url\">$url</a><br><br>
							$note
							<br>-----------------------------------------------<br>
							{$lang['sendit_55']} " . CONSORTIUM_WEBSITE_DOMAIN . ".
							</body></html>";
				
				// Construct email and send
				$email->setTo($value);
				$email->setBody($body);
				if ($email->send()) {
					// Add to list of emails sent
					$successfulEmails[] = "<span class='notranslate'>$value</span>";
					// Now send follow-up email containing password
					$bodypass = "<html><body style=\"font-family:Arial;font-size:10pt;\">
								{$lang['sendit_50']}<br><br>
								$pwd<br><br>
								</body></html>";
					$email->setSubject("Re: $subject");
					$email->setBody($bodypass);
					sleep(2); // Hold for a second so that second email somehow doesn't reach the user first
					$email->send();
				} else {
					// Add emails to array if email didn't send
					$failedEmails[] = "<span class='notranslate'>$value</span>";
					// Display the email to the user on the webpage
					?>
					<div style="max-width:700px;text-align:left;font-size:11px;background-color:#f5f5f5;border:1px solid #ddd;padding:5px;margin:20px;">
						<b style="color:#800000;">Email did NOT send!</b><br>
						<b>Sent to:</b> <?php echo $value ?>
						<hr>
						<?php echo $body ?>
						<hr>
						<?php echo "{$lang['sendit_50']}<br><br>$pwd" ?>
					</div>
					<?php
				}
				
			}
		}
	}
}









// Initialize page display object
$objHtmlPage = new HtmlPage();
$objHtmlPage->addExternalJS(APP_PATH_JS . "base.js");
$objHtmlPage->addStylesheet("smoothness/jquery-ui-".JQUERYUI_VERSION.".custom.css", 'screen,print');
$objHtmlPage->addStylesheet("style.css", 'screen,print');
$objHtmlPage->addStylesheet("home.css", 'screen,print');
$objHtmlPage->PrintHeader();

// If Send-It has not been enabled, then give error message to user
if (($fileLocation == '1' && $sendit_enabled != '1' && $sendit_enabled != '2')
	|| ($fileLocation == '2' && $sendit_enabled != '1' && $sendit_enabled != '3'))
{
	print RCView::div(array('style'=>'margin-top:30px;font-weight:bold;font-size:16px;'), "{$lang['global_01']}{$lang['colon']} {$lang['sendit_59']}");
	$objHtmlPage->PrintFooter();
	exit;
}

// Only show page header/footer when viewing outside of a REDCap project
if ($fileLocation == 1) 
{
	renderHomeHeaderLinks();
	
	?>
	<table border="0" align="center" cellpadding="0" cellspacing="0" width="100%">
	<tr valign="top"><td colspan="2" align="center"><img src="<?php echo APP_PATH_IMAGES ?>redcaplogo.gif"></td></tr>
	<tr valign="top"><td colspan="2" align="center">
	<?php

	// TABS
	include APP_PATH_DOCROOT . 'Home/tabs.php';

} 


?>

<script type='text/javascript'>
$(function() {
	var form = $("#form1");
	var recipients = $("#recipients");
	var file = $("#file");
	var fileLocation = $("#fileLocation");
	
	$('#recipients').autogrow();
	$('#message').autogrow();
	
	form.submit(function() {
		if ( validateRecipients() && validateFile() )
			return true;
		else
			return false;
	});

	function validateRecipients() {
		if (recipients.val().length == 0) {
			recipients.addClass("error");
			return false;
		}
		else {
			recipients.removeClass("error");
			// Parse email addresses and validate each
			var emails = recipients.val();
			emails = emails.replace(";", ",");
			emails = emails.replace(new RegExp( "\\r\\n", "g" ), ",");
			emails = emails.replace(new RegExp( "\\r", "g" ), ",");
			emails = emails.replace(new RegExp( "\\n", "g" ), ",");
			emails = emails.split(' ').join('');
			emails = emails.replace(new RegExp( ",+", "g" ), ",");
			if (emails.substring(0,1) == ",") emails = emails.substring(1);
			if (emails.substring(emails.length-1) == ",") emails = emails.substring(0,emails.length-1);	
			// Loop through all
			emailsArr = emails.split(',');
			badEmails = "";
			for (var i=0; i<emailsArr.length; i++) {
				if (!isEmail(emailsArr[i])) badEmails += "\n" + emailsArr[i];
			}
			if (badEmails == "") {
				return true;
			} else {
				alert("<?php echo $lang['global_01'] ?>: <?php echo remBr($lang['sendit_07']) ?>\n\n<?php echo remBr($lang['sendit_08']) ?>"+badEmails);
				recipients.val(emails.replace(new RegExp( ",", "g"), ", "));
				recipients.addClass("error");
				return false;
			}
		}
	}

	function validateFile() {
		if (getParameterByName('id') == '') {
			if (file.val().length == 0) {
				file.addClass("error");
				return false;
			} else {
				file.removeClass("error");
				return true;
			}
		} else {
			return true;
		}
	}
});
</script>




<!-- Page title -->
<div style="padding-top:8px;font-size: 18px;border-bottom:1px solid #aaa;padding-bottom:2px;">
	<img src='<?php echo APP_PATH_IMAGES ?>mail_arrow.png'> 
	<?php
	// Add filename here if from File Repository or data entry page
	print "Send-It";
	if ($fileLocation != 1) {
		print ": <span style='color:#800000;font-weight: normal;'>{$lang['sendit_09']} \"<b class='notranslate'>$originalFilename</b>\"</span>";
	}
	?>
</div>





<!-- Display message that file was uploaded successfully -->
<?php if ($_SERVER['REQUEST_METHOD'] == 'POST' && count($errors) == 0) { ?>
	
	<p style="color:green;font-weight:bold;font-size:14px;margin:15px 0;">
		<img src="<?php echo APP_PATH_IMAGES ?>tick.png" class="imgfix"> <?php echo $lang['sendit_10'] ?> 
	</p>
	<p>
		<?php echo $lang['sendit_11'] ?> "<b class="notranslate"><?php echo $originalFilename ?></b>" (<?php echo round_up($fileSize/1024/1024) ?> MB) 
		<?php echo $lang['sendit_12'] ?> 
		<?php echo date('l, F j, Y (g:i A)', mktime( $expireHour, $expireMin, 0, $expireMonth, $expireDay, $expireYear)); ?>.
	</p>
	<p>
		<b><?php echo $lang['sendit_13'] ?></b><br>
		<?php echo implode("<br>", $successfulEmails); ?>
	</p>
	<?php if (!empty($failedEmails)) { ?>	
		<p style="color:#800000;">
			<b><?php echo $lang['sendit_14'] ?></b><br>
			<?php echo implode("<br>", $failedEmails); ?>
		</p>
	<?php } ?>
	<?php if ($fileLocation == 1) { ?>
		<p style='border-top:1px solid #AAAAAA;margin-top:40px;padding:5px;'>
			<img src="<?php echo APP_PATH_IMAGES ?>arrow_skip_180.png" class="imgfix">&nbsp; 
			<a href="<?php echo PAGE_FULL ?>" style="color:#2E87D2;font-weight:bold;"><?php echo $lang['sendit_15'] ?></a>
		</p>
	<?php } ?>
	
	
	
	
	

<!-- Render form for uploading file -->
<?php
 } else { 
 
	// Display any errors
	if (count($errors) > 0) {
		?>
		<p class="red" style="margin:20px 0 10px;">
			<b><?php echo $lang['global_01'] ?>:</b><br> &bull; 
			<?php echo implode("<br> &bull; " , $errors) ?>
		</p>
		<?php 
	}
	
	// Do not show paragraph explaining "Send-It" in pop-up window mode
	if ($fileLocation == 1) {
		?>
		<p style="margin:20px 0 10px;">
			<b><?php echo $lang['sendit_17'] ?>  
			<?php echo maxUploadSizeSendit() ?> MB <?php echo $lang['sendit_18'] ?></b> 
			<?php echo $lang['sendit_19'] ?>
		</p>
		<?php
	}
	?>
	<p style="margin:10px 0 25px;">
		<b><?php echo $lang['sendit_20'] ?></b><br>
		<?php echo $lang['sendit_21'] ?>
	</p>
	
	<form action="<?php echo PAGE_FULL."?loc=$fileLocation&id=$fileId" ?>" enctype="multipart/form-data" target="_self" method="post" id="form1" name="form1">
	<div id="senditbox">
		<fieldset>
			
			<div style="margin-bottom: 10px; clear: both;">
				<label for="fromEmail" class="label"><?php echo $lang['global_37'] ?> </label>
				<?php echo User::emailDropDownList() ?>
			</div>
			
			<div style="margin-bottom: 10px; clear: both;">
				<label for="recipients" class="label">
					<?php echo $lang['global_38'] ?><br><span style="font-weight:normal;color:#555;"><?php echo $lang['sendit_23'] ?></span>
				</label>
				<textarea id="recipients" name="recipients" style="width: 400px; height: 80px;"><?php echo $recipients != '' ? $recipients : '' ?></textarea>
				<div style="font-weight:normal;font-size:11px;padding:0 0 10px 130px;color:#555;">
					<?php echo $lang['sendit_24'] ?>
				</div>
			</div>
			
			<div style="color:#555; height: 33px; margin-bottom: 10px; clear: both;">
				<label for="subject" class="label" style="font-weight:normal; "><?php echo $lang['sendit_25'] ?> <br/><?php echo $lang['global_06'] ?></label>
				<input type="text" id="subject" name="subject" value="<?php echo $subject != '' ? $subject : '' ?>" style="width:400px;" />
			</div>
			
			<div style="color:#555; margin-bottom: 15px; clear: both;">
				<label for="message" class="label" style="font-weight:normal;" ><?php echo $lang['sendit_27'] ?> <br><?php echo $lang['global_06'] ?></label>
				<textarea id="message" name="message" style="width: 400px; height: 80px;"><?php echo $message != '' ? $message : '' ?></textarea>
			</div>
			
			<div style="height: 33px; margin-bottom: 10px; clear: both;"><label for="expireDate" class="label"><?php echo $lang['sendit_28'] ?></label>
			<select id="expireDays" name="expireDays">
				<?php 
				$expireDays = $expireDays == '' ? 3 : $expireDays;
				for ($i=1; $i <= 14; $i++) {
					echo '<option value="'.$i.'" '.(($i != $expireDays) ? "" : "selected").'>'.$i.' day'.(($i == 1) ? "" : "s").'</option>';
				}
				?>
			</select>
			<span style="color: #000; margin-left: 10px;"><?php echo $lang['sendit_29'] ?></span></div>
			
			<div style="margin-bottom: 25px; clear: both;font-weight:bold;">
				<label for="file" class="label" id="lblFile">
			<?php if ($fileId == '') { ?>
				<?php echo $lang['sendit_30'] ?> </label>
				<input type="file" id="file" name="file" /> &nbsp;&nbsp;(<?php echo $lang['sendit_31'] ?>: <?php echo maxUploadSizeSendit() ?> MB)
			<?php } else { ?>
				<?php echo $lang['sendit_32'] ?> </label>
				<span style="color:#800000;"><?php echo $originalFilename ?></span> 
				<span style="color:#800000;font-weight:normal;">(<?php echo round_up($fileSize/1024/1024) ?> MB)</span>
			<?php } ?>
			</div>			
			<div style="margin-bottom: 25px; ">
				<label class="label">
					<input type="checkbox" id="confirmation" name="confirmation" value="yes" <?php echo $confirmation != '' ? 'checked' : '' ?> />
				</label>
				<b><?php echo $lang['sendit_33'] ?></b>
				<div style="color:#555;">
					<?php echo $lang['sendit_34'] ?>
				</div>
			</div>
			
			<div style="margin-left: 130px;">
				<input type="submit" id="submit" name="submit" value="Send It!" />
			</div>
		</fieldset>
	</div>
	</form>	
	
<?php 
}


if ($fileLocation == 1) { print "</td></tr></table><br><br>"; }

$objHtmlPage->PrintFooter();
