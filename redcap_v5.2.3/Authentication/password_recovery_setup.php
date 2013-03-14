<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


require_once dirname(dirname(__FILE__)) . '/Config/init_global.php';

// Get array of recovery questions
$questions = Authentication::getSecurityQuestions();

// Trim email
$_POST['user_email'] = label_decode(trim($_POST['user_email']));

## IF SUBMITTED QUESTION/ANSWER
if (isset($_POST['question']) && isset($_POST['answer']) && is_numeric($_POST['question']) && isset($questions[$_POST['question']]) && isset($_POST['user_email']) && isEmail($_POST['user_email']))
{
	$sql_all = array();
	// Clean answer and hash it
	$answerHash = Authentication::hashSecurityAnswer($_POST['answer']);
	// Set email address (if different)
	if ($user_email != $_POST['user_email'])
	{
		$sql = $sql_all[] = "update redcap_user_information set user_email = '".prep($_POST['user_email'])."' where username = '".prep(USERID)."'";
		db_query($sql);
	}
	// Add to table
	$sql = $sql_all[] = "update redcap_auth set password_question = {$_POST['question']}, password_answer = '$answerHash',
			password_question_reminder = null where username = '".prep(USERID)."'";
	if (db_query($sql))
	{
		// Logging
		log_event(implode(";\n", $sql_all),"redcap_auth","MANAGE",USERID,"username = '" . prep(USERID) . "'","Set up user security question");
		// Display success message
		?>
		<div class="darkgreen" style="margin:20px 0 30px;">
			<table cellspacing=10 width=100%>
				<tr>
					<td style="padding:0 30px 0 40px;">
						<img src="<?php echo APP_PATH_IMAGES ?>check_big.png">
					</td>
					<td style="font-size:14px;font-family:verdana;line-height:22px;padding-right:30px;">
						<?php  print $lang['pwd_reset_39'] ?>
					</td>
				</tr>
			</table>
		</div>
		<button class="jqbutton" style="margin-left:10px;font-family:verdana;line-height:25px;font-size:13px;" onclick="$('#setUpSecurityQuestion').dialog('destroy');"><?php print $lang['calendar_popup_01'] ?></button>
		<div class="space">&nbsp;</div>
		<?php
		exit;
	}
}

## SET TO REMIND USER AGAIN IN 2 DAYS
elseif (isset($_POST['setreminder']))
{
	// Get timestamp 2 days from now
	$in2days = date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s"),date("m"),date("d")+2,date("Y")));
	// Update table
	$sql = "update redcap_auth set password_question_reminder = '$in2days' where username = '".prep(USERID)."'
			and password_question is null";
	$q = db_query($sql);
	print (db_affected_rows() > 0) ? '1' : '0';
	exit;
}

// ERROR
print "ERROR!";