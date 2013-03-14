<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

// Display header and call config file
require_once dirname(dirname(__FILE__)) . '/Config/init_global.php';

// Initialize page display object
$objHtmlPage = new HtmlPage();
$objHtmlPage->addExternalJS(APP_PATH_JS . "base.js");
$objHtmlPage->addStylesheet("smoothness/jquery-ui-".JQUERYUI_VERSION.".custom.css", 'screen,print');
$objHtmlPage->addStylesheet("style.css", 'screen,print');
$objHtmlPage->addStylesheet("home.css", 'screen,print');
$objHtmlPage->PrintHeader();
print "<br><br><h3><img src='".APP_PATH_IMAGES."user_edit.png'> <span style='color:#800000;'>{$lang['user_08']}</span></h3>";



## DISPLAY PAGE
?>
<style type="text/css">
table#userProfileTable { border:1px solid #ddd; }
table#userProfileTable td { background-color:#f5f5f5;padding: 5px 20px; }
</style>

<script type='text/javascript'>
function validateUserInfoForm() {
	if ($('#user_email').val().length < 1 || $('#user_firstname').val().length < 1 || $('#user_lastname').val().length < 1) {
		simpleDialog('<?php echo cleanHtml($lang['user_17']) ?>');
		return false;
	}
	return emailChange(document.getElementById('user_email'));
}
function emailChange(ob) {
	$(ob).val( trim($(ob).val()) );
	$('#reenterPrimary').hide();
	$('#reenterPrimary2').hide();
	if (!redcap_validate(ob,'','','hard','email')) return false;
	var id = $(ob).attr('id');
	// Make sure the new primary email isn't already a secondary/tertiary email
	if ($(ob).val() != '' && (($('#user_email2-span').text() != '' && $(ob).val() == $('#user_email2-span').text()) 
		|| ($('#user_email3-span').text() != '' && $(ob).val() == $('#user_email3-span').text()))) {
		simpleDialog('<b>'+$(ob).val()+'</b> <?php echo cleanHtml($lang['user_35']) ?>',null,null,null,"$('#"+id+"').val( $('#"+id+"').attr('oldval') ).focus();");
		return false;
	}
	// If email_domain_whitelist is enabled, then check the email against it
	if (emailInDomainWhitelist(ob) === false) return false;
	// Display "re-enter email" field if email is changing
	if ($(ob).val() != '' && $(ob).attr('oldval') != null && $(ob).val() != $(ob).attr('oldval') && $('#user_email_dup').val() != $(ob).val()) {
		$('#reenterPrimary').show('fade',function(){ $('#user_email_dup').focus() });
		$('#reenterPrimary2').show('fade');
		return false;
	}
	return true;
}
</script>
<?php

// Instructions
print  "<p>{$lang['user_11']}</p>";


// If posted, show message showing that changes have been made
if ($_SERVER['REQUEST_METHOD'] == 'POST') 
{
	// Sanitize inputs
	foreach ($_POST as &$val) $val = strip_tags(html_entity_decode($val, ENT_QUOTES));	
	// Get user info
	$user_info = User::getUserInfo($userid);
	// If "domain whitelist for user emails" is enabled and email fails test, then revert it to old value
	if (User::emailInDomainWhitelist($_POST['user_email']) === false) {
		$_POST['user_email'] = $user_info['user_email'];
	}
	//Make changes to user's info
	$sql = "update redcap_user_information set user_email = '".prep($_POST['user_email'])."', 
			user_firstname = '".prep($_POST['user_firstname'])."', 
			user_lastname = '".prep($_POST['user_lastname'])."' where username = '$userid'";
	if (db_query($sql)) {
		print '<div class="darkgreen" style="text-align:center;">
				<img src="'.APP_PATH_IMAGES.'tick.png" class="imgfix"> '.$lang['user_09'].' 
			   </div><br>';
	} else {
		print '<div class="red" style="text-align:center;">
				<img src="'.APP_PATH_IMAGES.'exclamation.png" class="imgfix"> '.$lang['global_01'].$lang['colon'].' '.$lang['user_10'].' 
			   </div><br>';
	}
	print "<script type='text/javascript'>$(function(){ setTimeout(function(){ $('.red').hide('blind');$('.darkgreen').hide('blind'); },3000); });</script>";
	//Set new values for display
	$user_firstname = $_POST['user_firstname'];
	$user_lastname  = $_POST['user_lastname'];
	$user_email 	= $_POST['user_email'];
	// Logging
	log_event($sql,"redcap_user_information","MANAGE",$userid,"username = '".prep($userid)."'","Update user info");
	// If the user changed their email address, then send them a verification email so they can confirm that email account
	if ($user_info['user_email'] != $_POST['user_email'])
	{
		// Now send an email to their account so they can verify their email 
		$verificationCode = User::setUserVerificationCode($user_info['ui_id'], 1);
		if ($verificationCode !== false) {
			// Send verification email to user	
			$emailSent = User::sendUserVerificationCode($_POST['user_email'], $verificationCode);
			if ($emailSent) {
				// Redirect back to previous page to display confirmation message and notify user that they were sent an email
				redirect(APP_PATH_WEBROOT . "Profile/user_profile.php?verify_email_sent=1");
			}
		}
	}
}


print "<div style='text-align:center;max-width:700px;padding:10px 20px;' align='center'>";
print "<form id='form' method=\"post\" action=\"" . PAGE_FULL . ((isset($_GET['pnid']) && $_GET['pnid'] != "") ? "?pid=$project_id" : "") . "\">";
print "<center>";

// TABLE
print  "<table id='userProfileTable' cellspacing='0' style='font-size:13px;width:500px;'>";
// Header
print  "<tr><td colspan='2' style='padding:10px 8px 5px;color:#800000;font-weight:bold;font-size:14px;'>
		{$lang['user_58']}
		</td></tr>";
// First name
print  "<tr><td>{$lang['pub_023']}{$lang['colon']} </td><td>";
// If global setting is set to restrict editing of first/last name, then display as hidden field that is not editable
if ($my_profile_enable_edit || $super_user) {
	print "<input type=\"text\" class=\"x-form-text x-form-field\" id=\"user_firstname\" name=\"user_firstname\" value=\"".str_replace("\"","&quot;",$user_firstname)."\" size=20 onkeydown='if(event.keyCode == 13) return false;'>";
} else {
	print  "<b>$user_firstname</b>
			<input type=\"hidden\" id=\"user_firstname\" name=\"user_firstname\" value=\"".str_replace("\"","&quot;",$user_firstname)."\">";
}
print "</td></tr>";
// Last name
print "<tr><td>{$lang['pub_024']}{$lang['colon']} </td><td>";
if ($my_profile_enable_edit || $super_user) {
	print "<input type=\"text\" class=\"x-form-text x-form-field\" id=\"user_lastname\" name=\"user_lastname\" value=\"".str_replace("\"","&quot;",$user_lastname)."\" size=20 onkeydown='if(event.keyCode == 13) return false;'>";
} else {
	print  "<b>$user_lastname</b>
			<input type=\"hidden\" id=\"user_lastname\" name=\"user_lastname\" value=\"".str_replace("\"","&quot;",$user_lastname)."\">";
}
print "</td></tr>";
// Primary email
print 	"<tr>
			<td>{$lang['user_45']}{$lang['colon']} </td>
			<td> 
				<input type=\"text\" class=\"x-form-text x-form-field\" value=\"$user_email\" oldval=\"$user_email\" id=\"user_email\" name=\"user_email\" size=35 onkeydown='if(event.keyCode == 13) return false;' onBlur=\"emailChange(this)\"> 
		   </td>
		  </tr>";
// Primary email (re-enter)
print 	"<tr id='reenterPrimary' style='display:none;'>
			<td valign='top' class='yellow' style='color:red;border-bottom:0;border-right:0;background-color:#FFF7D2;'>{$lang['user_15']}{$lang['colon']} </td>
			<td valign='top' class='yellow' style='border-bottom:0;border-left:0;background-color:#FFF7D2;'> 
				<input type=\"text\" class=\"x-form-text x-form-field\" id=\"user_email_dup\" size=35 onkeydown='if(event.keyCode == 13) return false;' onBlur=\"this.value=trim(this.value);if(this.value.length<1){return false;} if (!redcap_validate(this,'','','hard','email')) { return false; } validateEmailMatch('user_email','user_email_dup');\"> 
				<div style='max-width:300px;font-size:11px;color:red;'>{$lang['user_33']}</div>
			</td>
		  </tr>
		  <tr id='reenterPrimary2' style='display:none;'>
			<td colspan='2' class='yellow' style='line-height:11px;background-image:url();border-top:0;border-right:0;background-color:#FFF7D2;font-size:11px;color:#800000;'>
				<img src='".APP_PATH_IMAGES."mail_small2.png'>
				<b>{$lang['global_02']}{$lang['colon']}</b> {$lang['user_34']}
			</td>
		  </tr>";
// Submit button (and Reset Password button, if applicable)
print 	"<tr>
			<td></td>
			<td style='white-space:nowrap;color:#800000;padding-bottom:20px;'>
				<div style='float:left;'>
					<button class='jqbutton' style='font-weight:bold;font-family:arial;' onclick=\"if(validateUserInfoForm()){ $('#form').submit(); } else { return false; }\">{$lang['user_60']}</button>
				</div>";
// Reset Password: If user is a table-based user (i.e. in redcap_auth table), then give option to reset password
if (($auth_meth_global == "table" || $auth_meth_global == "ldap_table") && User::isTableUser($userid)) 
{
	print 	"	<div style='float:right;'>
					<button class='jqbuttonmed' style='font-size:11px;' onclick=\"
						simpleDialog('".cleanHtml($lang['user_13'])."','".cleanHtml($lang['user_12'])."',null,null,null,'".cleanHtml($lang['global_53'])."','$.get(app_path_webroot+\'ControlCenter/user_controls_ajax.php\',{action:\'reset_password_as_temp\'},function(data){if(data==\'0\'){alert(woops);return;}window.location.reload();});','Reset');
						return false;
					\">{$lang['control_center_140']}</button>
				</div>";
}
print 	"	</td>
		</tr>";
// Additional Info Header
print  "<tr><td colspan='2' style='border-top:1px solid #ddd;padding:15px 8px 5px;'>
			<div style='color:#800000;font-weight:bold;font-size:14px;'>{$lang['user_59']}</div>
			<div style='color:#555;font-size:11px;line-height:11px;padding:6px 0 3px;'>{$lang['user_61']}</div>
		</td></tr>";
// Secondary email
print 	"<tr>
			<td>{$lang['user_46']}{$lang['colon']} </td>
			<td style='white-space:nowrap;color:#800000;'>";
if ($user_email2 != '') {
	print  "<span id='user_email2-span'>$user_email2</span> &nbsp;
			<a href='javascript:;' style='text-decoration:underline;font-size:10px;font-family:tahoma;' onclick=\"removeAdditionalEmail(2);return false;\">{$lang['scheduling_57']}</a>";
} else {
	print  "<button class='jqbuttonmed' style='color:green;' onclick=\"setUpAdditionalEmails();return false;\">{$lang['user_42']}</button>";
}
print " 	</td>
		</tr>";
// Tertiary email
print 	"<tr>
			<td>{$lang['user_55']}{$lang['colon']} </td>
			<td style='white-space:nowrap;color:#800000;'>";
if ($user_email3 != '') {
	print  "<span id='user_email3-span'>$user_email3</span> &nbsp;
			<a href='javascript:;' style='text-decoration:underline;font-size:10px;font-family:tahoma;' onclick=\"removeAdditionalEmail(3);return false;\">{$lang['scheduling_57']}</a>";
} else {
	print  "<button class='jqbuttonmed' style='color:green;' onclick=\"setUpAdditionalEmails();return false;\">{$lang['user_42']}</button>";
}
print  " 	</td>
		</tr>";
// Spacer row
print 	"<tr>
			<td style='padding-bottom:10px;'> </td>
			<td style='padding-bottom:10px;'> </td>
		</tr>";
		
print  "</table>";
				   
print  "</center>";
print "</form>";

print  "<div style='text-align:left;padding:30px;'>
			<button class='jqbuttonmed' onclick=\"window.location.href=app_path_webroot;\"><img src='".APP_PATH_IMAGES."arrow_left.png' style='vertical-align:middle;'> <span style='vertical-align:middle;'>{$lang['global_77']}</span></button>
		</div>";

print "</div>";


// Hidden dialog to confirm removal of secondary/tertiary email address
print RCView::simpleDialog($lang['user_57'].RCView::div(array('id'=>'user-email-dialog','style'=>'font-weight:bold;padding-top:15px;'), ""),$lang['user_56'],"removeAdditionalEmail");

// Display footer
$objHtmlPage->PrintFooter();
