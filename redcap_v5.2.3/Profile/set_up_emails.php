<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_global.php';

// Initialize vars
$popupContent = $popupTitle = $saveBtnTxt = "";
$response = "0";

// Get user info
$user_info = User::getUserInfo($userid);


## RETURN DIALOG CONTENT
if (isset($_POST['action']) && $_POST['action'] == 'view')
{
	// Build html for dialog content
	$popupContent = RCView::div(array('style'=>'padding:0 0 10px;'), $lang['user_43']) .
					// Display primary email
					RCView::div(array('style'=>'padding:5px 0 2px;color:#800000;float:left;width:130px;font-weight:bold;'), $lang['user_45'] . $lang['colon']) .
					RCView::div(array('style'=>'padding:5px 0 2px;color:#800000;float:left;'), $user_email) .
					// Display secondary email (if exists)
					(!($user_info['user_email2'] != '' && $user_info['email2_verify_code'] == '') ? "" :
						RCView::div(array('style'=>'clear:both;padding:0 0 5px;color:#800000;float:left;width:130px;font-weight:bold;'), $lang['user_46'] . $lang['colon']) .
						RCView::div(array('style'=>'padding:0 0 5px;color:#800000;float:left;'), $user_email2)
					).
					// First email input
					RCView::div(array('style'=>'clear:both;padding:20px 0 5px;float:left;width:130px;font-weight:bold;'), $lang['user_44']) .
					RCView::div(array('style'=>'padding:20px 0 5px;float:left;'), 
						RCView::text(array('id'=>'add_new_email','class'=>'x-form-text x-form-field','style'=>'width:200px;','onblur'=>"if (redcap_validate(this,'','','hard','email')) emailInDomainWhitelist(this);"))
					) .
					// Re-enter email input
					RCView::div(array('style'=>'clear:both;padding:5px 0;float:left;width:130px;font-weight:bold;'), $lang['user_62'] . $lang['colon']) .
					RCView::div(array('style'=>'padding:2px 0;float:left;'), 
						RCView::text(array('id'=>'add_new_email_dup','class'=>'x-form-text x-form-field','style'=>'width:200px;','onblur'=>"if(!redcap_validate(this,'','','hard','email') || this.value==''){ return; } if (emailInDomainWhitelist(this) !== false) { validateEmailMatch('add_new_email','add_new_email_dup'); }"))
					) .
					// Hidden inputs that store current emails (for referencing via javascript)
					RCView::hidden(array('id'=>'existing_user_email','value'=>$user_info['user_email'])) .
					RCView::hidden(array('id'=>'existing_user_email2','value'=>(($user_info['user_email2'] != '' && $user_info['email2_verify_code'] == '') ? $user_info['user_email2'] : ""))) .
					RCView::hidden(array('id'=>'existing_user_email3','value'=>(($user_info['user_email3'] != '' && $user_info['email3_verify_code'] == '') ? $user_info['user_email3'] : "")));
	$popupTitle = RCView::img(array('src'=>'email_add.png','style'=>'vertical-align:middle;')) . 
				  RCView::span(array('style'=>'vertical-align:middle;'), $lang['user_41']);
	$saveBtnTxt = $lang['user_42'];
	$response = "1";
}


## PERFORM ACTION TO SAVE NEW EMAIL ADDRESS
elseif (isset($_POST['action']) && $_POST['action'] == 'save' && isset($_POST['add_new_email']) && isEmail($_POST['add_new_email']) 
	// If "domain whitelist for user emails" is enabled and email fails test, then don't add this email
	&& User::emailInDomainWhitelist($_POST['add_new_email']) !== false)
{
	// Make sure there isn't already a pending request for this same email address
	if (($_POST['add_new_email'] == $user_info['user_email2'] && $user_info['email2_verify_code'] != '')
		|| ($_POST['add_new_email'] == $user_info['user_email3'] && $user_info['email3_verify_code'] != ''))
	{
		## EMAIL ADDRESS IS STILL PENDING
		// Return html for dialog
		$popupContent = RCView::div(array('class'=>'red','style'=>'padding:10px;'),
							$lang['user_53'] . RCView::SP .
							RCView::a(array('href'=>"mailto:".$_POST['add_new_email']), $_POST['add_new_email']) . $lang['period'] .
							RCView::SP . $lang['user_54'] . RCView::br() . RCView::br() .
							RCView::b(
								RCView::img(array('src'=>'email_go.png','class'=>'imgfix')) .
								$lang['user_28'] . RCView::SP . 
								RCView::a(array('href'=>"mailto:".$_POST['add_new_email'],'style'=>'font-weight:bold;'), $_POST['add_new_email'])
							)
						);
		$popupTitle = RCView::img(array('src'=>'delete.png','style'=>'vertical-align:middle;')) . 
					  RCView::span(array('style'=>'vertical-align:middle;color:#800000;'), $lang['user_51'] . " " . $_POST['add_new_email'] . " " . $lang['user_52']);
	}
	else
	{
		## ADD NEW EMAIL ADDRESS AND SEND VERIFICATION EMAIL
		// Determine email account (2=secondary or 3=tertiary) - get first empty slot
		$email_account = ($user_info['user_email2'] == '') ? 2 : 3;
		// Save the new email address to their account
		User::setUserEmail($user_info['ui_id'], $_POST['add_new_email'], $email_account);
		// First, save the email address and send verification code
		$verificationCode = User::setUserVerificationCode($user_info['ui_id'], $email_account);
		if ($verificationCode !== false) {
			// Send verification email to user	
			$emailSent = User::sendUserVerificationCode($_POST['add_new_email'], $verificationCode, $email_account);
		}
		// Return html for dialog
		$popupContent = RCView::div(array('class'=>'yellow','style'=>'padding:10px;'),
							$lang['user_47'] . RCView::SP .
							RCView::a(array('href'=>"mailto:".$_POST['add_new_email']), $_POST['add_new_email']) . 
							RCView::SP . $lang['user_48'] . RCView::br() . RCView::br() .
							RCView::b(
								RCView::img(array('src'=>'email_go.png','class'=>'imgfix')) .
								$lang['user_28'] . RCView::SP . 
								RCView::a(array('href'=>"mailto:".$_POST['add_new_email'],'style'=>'font-weight:bold;'), $_POST['add_new_email'])
							)
						);
		$popupTitle = $lang['user_50'] . " " . $_POST['add_new_email'];
	}
	$response = "1";
}


// Send back JSON response
print '{"response":"' . $response . '","popupContent":"' . cleanHtml2($popupContent) 
	. '","popupTitle":"' . cleanHtml2($popupTitle) . '","saveBtnTxt":"' . cleanHtml2($saveBtnTxt) . '"}';