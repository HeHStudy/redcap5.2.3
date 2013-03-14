<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

include 'header.php';


// setup database access
$db = new RedCapDB();

// are we looking up an existing user?
$ui_id = empty($_POST['ui_id']) ? null : $_POST['ui_id'];
$user_obj = new StdClass();
if (!empty($ui_id)) $user_obj = $db->getUserInfo($ui_id);
$orig_email = empty($user_obj->user_email) ? '' : $user_obj->user_email;

// save user data to the DB
if (isset($_POST['username']) && isset($_POST['user_firstname']) && isset($_POST['user_lastname']))
{
	// Ensure user doesn't already exist in user_information or auth table for inserts
	$userExists = $db->usernameExists($_POST['username']);
	if ($userExists && !$ui_id)
	{
		print  "<div class='red' style='margin-bottom: 20px;'>
					<img src='" . APP_PATH_IMAGES . "exclamation.png' class='imgfix'>
					{$lang['global_01']}: {$lang['control_center_29']} {$lang['control_center_30']}
					\"<b>" . $_POST['username'] . "</b>\".
				</div>";
	}
	else
	{
		// Unescape posted values
		$_POST['username'] = trim(strip_tags(label_decode($_POST['username'])));
		// Get user info
		$user_info = User::getUserInfo($_POST['username']);
		// Unescape posted values
		$_POST['user_firstname'] = trim(strip_tags(label_decode($_POST['user_firstname'])));
		$_POST['user_lastname'] = trim(strip_tags(label_decode($_POST['user_lastname'])));
		$_POST['user_email'] = trim(strip_tags(label_decode($_POST['user_email'])));
		$_POST['user_email2'] = trim(strip_tags(label_decode($_POST['user_email2'])));
		$_POST['user_email3'] = trim(strip_tags(label_decode($_POST['user_email3'])));
		$_POST['user_inst_id'] = trim(strip_tags(label_decode($_POST['user_inst_id'])));
		// If "domain whitelist for user emails" is enabled and email fails test, then revert it to old value
		if (User::emailInDomainWhitelist($_POST['user_email']) === false)  $_POST['user_email']  = $user_info['user_email'];
		if (User::emailInDomainWhitelist($_POST['user_email2']) === false) $_POST['user_email2'] = $user_info['user_email2'];
		if (User::emailInDomainWhitelist($_POST['user_email3']) === false) $_POST['user_email3'] = $user_info['user_email3'];
	
		// Set value if can create/copy new projects
		$allow_create_db = (isset($_POST['allow_create_db']) && $_POST['allow_create_db'] == "on") ? 1 : 0;
		$pass = generateRandomHash(8);
		$sql = $db->saveUser($ui_id, $_POST['username'], $_POST['user_firstname'],
						$_POST['user_lastname'], $_POST['user_email'], $_POST['user_email2'], $_POST['user_email3'], $_POST['user_inst_id'],
						$allow_create_db, $pass);
		// repopulate with newly saved data
		if (!empty($ui_id)) $user_obj = $db->getUserInfo($ui_id);
		if (count($sql) === 0) {
			// Failure to add user
			print  "<div class='red' style='margin-bottom: 20px;'>
						<img src='" . APP_PATH_IMAGES . "exclamation.png' class='imgfix'>
						{$lang['global_01']}: {$lang['control_center_240']}
					</div>";
		}
		else {
			// Display confirmation message that user was saved successfully
			print  "<div class='darkgreen' style='margin-bottom: 20px;'>
						<img src='" . APP_PATH_IMAGES . "tick.png' class='imgfix'> " .
						$lang['control_center_241'] . ($ui_id ? '' : ' ' . $lang['control_center_242'] . ' ' . $_POST['user_email']) .
					"</div>";
			// Email the user (new users get their login info, existing users get notified if their email changes)
			$email = new Message();
			$email->setTo($_POST['user_email']);
			$email->setToName($_POST['user_firstname'] . " " . $_POST['user_lastname']);
			$email->setFrom($user_email);
			$email->setFromName("$user_firstname $user_lastname");
			if (empty($ui_id)) { 
				// new user
				log_event(implode(";\n", $sql),"redcap_auth","MANAGE",$_POST['username'],"user = '{$_POST['username']}'","Create username");
				$email->setSubject('REDCap '.$lang['control_center_101']);
				$emailContents = $lang['control_center_95'].'<br /><br />
					REDCap - '.APP_PATH_WEBROOT_FULL.' <br /><br />
					'.$lang['control_center_97'].'<br /><br />
					'.$lang['global_11'].$lang['colon'].' '.$_POST['username'].'<br />
					'.$lang['global_32'].$lang['colon'].' '.$pass.'<br /><br />
					'.$lang['control_center_96'];
				$email->setBody($emailContents, true);
				if (!$email->send()) print $email->getSendError ();
			}
			else { 
				// existing user
				log_event(implode(";\n", $sql),"redcap_user_information","MANAGE",$_POST['username'],"username = '{$_POST['username']}'","Edit user");
				// If the user's email address was changed, then send an email to both accounts to notify them of the change.
				if ($_POST['user_email'] != $orig_email) 
				{
					$email->setSubject('REDCap '.$lang['control_center_100'].' '.$_POST['user_email']);
					$emailContents = $lang['control_center_92'].' REDCap ('.$orig_email.') '.$lang['control_center_93'].' '
						.$_POST['user_email'].$lang['period'].' '.$lang['control_center_94'].'<br><br>
						REDCap - '.APP_PATH_WEBROOT_FULL;
					$email->setBody($emailContents, true);
					// first send to the new email address
					if (!$email->send()) {
						print $email->getSendError ();
					} elseif ($user_obj->email_verify_code != '') {
						// If primary email was changed BUT original email had not yet been verified, then remove verification
						$sql = "update redcap_user_information set email_verify_code = null where ui_id = " . $user_obj->ui_id;
						db_query($sql);
					}
					// now send to the old email address
					$email->setTo($orig_email);
					if (!$email->send()) print $email->getSendError ();	
					// Display message that email was changed and that user was emailed about the change
					print 	RCView::div(array('class'=>'yellow','style'=>'margin-bottom:15px;'), 
								RCView::img(array('src'=>'exclamation_orange.png', 'class'=>'imgfix')) .
								RCView::b($lang['global_02'].$lang['colon']) . ' ' .$lang['control_center_373']
							);
				}
			}
		}
	}
}




// Page header, instructions, and tabs
if ($ui_id) {
	// Edit user info
	print RCView::h3(array('style'=>'margin-top:0;'), $lang['control_center_239']) .
		  RCView::p(array(), $lang['control_center_244']);
} else {
	// Add new user
	print 	RCView::h3(array('style' => 'margin-top: 0;'), $lang['control_center_42']) . 
			RCView::p(array('style'=>'margin-bottom:20px;'), $lang['control_center_411']);
	$tabs = array('ControlCenter/create_user.php'=>RCView::img(array('src'=>'user_add2.png','class'=>'imgfix')) . $lang['control_center_409'],
				  'ControlCenter/create_user_bulk.php'=>RCView::img(array('src'=>'xls.gif','class'=>'imgfix')) . $lang['control_center_410']);
	renderTabs($tabs);
	print 	RCView::p(array(), $lang['control_center_43']);
}
?>


<form method='post' action='<?php echo $_SERVER['PHP_SELF'] ?>'>
	<input type="hidden" name="ui_id" value="<?php echo (empty($user_obj->ui_id) ? '' : htmlentities($user_obj->ui_id)); ?>">
	<table border='0' cellpadding='0' cellspacing='8'>
	<tr>
		<td><?php echo $lang['global_11'].$lang['colon'] ?> </td>
		<td>
			<input type='text' class='x-form-text x-form-field' id='username' name='username' maxlength='255' size='20'
				onblur="if (this.value.length > 0) {if(!chk_username(this)) return alertbad(this,'<?php echo $lang['control_center_45'] ?>'); }"
				value="<?php echo (empty($user_obj->username) ? '' : htmlentities($user_obj->username)); ?>"
				<?php echo (empty($user_obj->ui_id) ? '' : 'readonly="readonly"') ?>>
		</td>
	</tr>
	<tr>
		<td><?php echo $lang['pub_023'].$lang['colon'] ?> </td>
		<td>
			<input type='text' class='x-form-text x-form-field' id='user_firstname' name='user_firstname' maxlength='255' size=20
				onkeydown='if(event.keyCode == 13) return false;'
				value="<?php echo (empty($user_obj->user_firstname) ? '' : htmlentities($user_obj->user_firstname)); ?>">
		</td>
	</tr>
	<tr>
		<td><?php echo $lang['pub_024'].$lang['colon'] ?> </td>
		<td>
			<input type='text' class='x-form-text x-form-field' id='user_lastname' name='user_lastname' maxlength='255' size=20
				onkeydown='if(event.keyCode == 13) return false;'
				value="<?php echo (empty($user_obj->user_lastname) ? '' : htmlentities($user_obj->user_lastname)); ?>">
		</td>
	</tr>
	<tr>
		<td><?php echo $lang['user_45'].$lang['colon'] ?> </td>
		<td>
			<input type='text' class='x-form-text x-form-field' id='user_email' name='user_email' maxlength='255' size=35
				onkeydown='if(event.keyCode == 13) return false;'
				onBlur="if (redcap_validate(this,'','','hard','email')) emailInDomainWhitelist(this);"
				value="<?php echo (empty($user_obj->user_email) ? '' : htmlentities($user_obj->user_email)); ?>">
		</td>
	</tr>
	<tr>
		<td><?php echo $lang['user_46'].$lang['colon'] ?> </td>
		<td>
			<input type='text' class='x-form-text x-form-field' id='user_email2' name='user_email2' maxlength='255' size=35
				onkeydown='if(event.keyCode == 13) return false;'
				onBlur="if (redcap_validate(this,'','','hard','email')) emailInDomainWhitelist(this);"
				value="<?php echo (empty($user_obj->user_email2) ? '' : htmlentities($user_obj->user_email2)); ?>">
		</td>
	</tr>
	<tr>
		<td><?php echo $lang['user_55'].$lang['colon'] ?> </td>
		<td>
			<input type='text' class='x-form-text x-form-field' id='user_email3' name='user_email3' maxlength='255' size=35
				onkeydown='if(event.keyCode == 13) return false;'
				onBlur="if (redcap_validate(this,'','','hard','email')) emailInDomainWhitelist(this);"
				value="<?php echo (empty($user_obj->user_email3) ? '' : htmlentities($user_obj->user_email3)); ?>">
		</td>
	</tr>
	<tr>
		<td><?php echo $lang['control_center_236'].$lang['colon'] ?> </td>
		<td>
			<input type='text' class='x-form-text x-form-field' id='user_inst_id' name='user_inst_id' maxlength='255' size=20
				onkeydown='if(event.keyCode == 13) return false;'
				value="<?php echo (empty($user_obj->user_inst_id) ? '' : htmlentities($user_obj->user_inst_id)); ?>">
			<span class="cc_info">(<?php echo $lang['control_center_237'] ?>)</span>

		</td>
	</tr>
	<tr>
		<td colspan='2'>
			<?php
				$allow_checked = '';
				if (isset($user_obj->allow_create_db) && $user_obj->allow_create_db ||
					!isset($user_obj->allow_create_db) && $allow_create_db_default) {
					$allow_checked = "checked";
				}
			?>
			<input type='checkbox' name='allow_create_db' class='imgfix' <?php echo $allow_checked ?>> 
			<?php 
			echo ($superusers_only_create_project 
				? RCView::b($lang['control_center_320']). RCView::div(array('style'=>'margin-left:22px;'), $lang['control_center_321'])
				: RCView::b($lang['control_center_46']) ) 
			?>
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>
			<input name='submit' type='submit' value='Save'
				onclick="if($('#user_email').val().length < 1 || $('#user_firstname').val().length < 1 || $('#user_lastname').val().length < 1){simpleDialog('<?php echo cleanHtml($lang['control_center_428']) ?>');return false;}">
		</td>
	</tr>
	</table>
</form>

<script type='text/javascript'>
	var mess1 = '<?php echo cleanHtml($lang['control_center_47']) ?>';
</script>

<?php 
include 'footer.php';
