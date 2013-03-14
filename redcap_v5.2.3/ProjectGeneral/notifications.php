<?php


if (isset($_GET['pnid']) || isset($_GET['pid'])) {
	require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
} else {
	require_once dirname(dirname(__FILE__)) . '/Config/init_global.php';
}
require_once APP_PATH_CLASSES . 'Message.php';


// Set up email to be sent
$email = new Message();


// Determine which type of email to send
if (isset($_GET['type'])) 
{
	// Catch if user selected multiple Research options for Purpose
	if (isset($_POST['purpose_other'])) 
	{
		if (is_array($_POST['purpose_other'])) {
			$_POST['purpose_other'] = implode(",", $_POST['purpose_other']);
		} elseif ($_POST['purpose_other'] != '1' && $_POST['purpose_other'] != '2') {
			$_POST['purpose_other'] == "";
		}
	}

	switch ($_GET['type']) 
	{
		// Send email request to super user to move project to PRODUCTION
		case 'move_to_prod':
			$email->setFrom($user_email);
			$email->setTo($project_contact_prod_changes_email);
			$emailSubject  =   "[REDCap] {$lang['email_admin_01']}";
			$emailContents =   "{$lang['global_21']}<br><br>
								{$lang['email_admin_03']} <b>" . html_entity_decode("$user_firstname $user_lastname", ENT_QUOTES) . "</b> 
								(<a href='mailto:$user_email'>$user_email</a>) 
								{$lang['email_admin_04']} 
								<b>" . strip_tags(html_entity_decode($app_title, ENT_QUOTES)) . "</b>{$lang['period']}<br><br>
								{$lang['email_admin_05']}<br>
								<a href='" . APP_PATH_WEBROOT_FULL . "redcap_v{$redcap_version}/ProjectSetup/index.php?pid=$project_id&type={$_GET['type']}&delete_data={$_GET['delete_data']}&user_email=$user_email'>{$lang['email_admin_06']}</a>";
			// Finalize email
			$email->setBody("<html><head><title>$emailSubject</title></head><body style='font-family:arial;font-size:10pt;'>$emailContents</body></html>");
			$email->setSubject($emailSubject);
			// Send email and notify with "0" if does not send
			print $email->send() ? "1" : "0";
			// Log this event
			log_event("","redcap_data","MANAGE",$project_id,"project_id = $project_id","Send request to move project to production status");
			exit;

		// Send email confirmation to user that project was moved to PRODUCTION
		case 'move_to_prod_user':
			$_GET['this_user_email'] = html_entity_decode($_GET['this_user_email'], ENT_QUOTES);
			$email->setFrom($project_contact_prod_changes_email);
			$email->setTo($_GET['this_user_email']);
			$emailSubject  =   "[REDCap] {$lang['email_admin_07']}";
			$emailContents =   "{$lang['global_21']}<br><br>
								{$lang['email_admin_08']} 
								<b>" . strip_tags(html_entity_decode($app_title, ENT_QUOTES)) . "</b>.<br><br>
								<a href='" . APP_PATH_WEBROOT_FULL . "redcap_v{$redcap_version}/index.php?pid=$project_id'>{$lang['email_admin_09']}</a>";
			// Finalize email
			$email->setBody("<html><head><title>$emailSubject</title></head><body style='font-family:arial;font-size:10pt;'>$emailContents</body></html>");
			$email->setSubject($emailSubject);
			// Send email and notify with "0" if does not send
			print $email->send() ? "1" : "0";					
			exit;

		// Send email request to super user to CREATE NEW project
		case 'request_new':
			$email->setFrom($user_email);
			$email->setTo($project_contact_prod_changes_email);
			$emailSubject  =   "[REDCap] {$lang['email_admin_10']}";
			$emailContents =   "{$lang['global_21']}<br><br>
								{$lang['email_admin_03']} <b>" . html_entity_decode("$user_firstname $user_lastname", ENT_QUOTES) . "</b> 
								(<a href='mailto:$user_email'>$user_email</a>) 
								{$lang['email_admin_11']} 
								<b>" . strip_tags(html_entity_decode($_POST['app_title'], ENT_QUOTES)) . "</b>.<br><br>
								{$lang['email_admin_05']}<br>
								<a href='" . APP_PATH_WEBROOT_FULL . "index.php?action=create&type={$_GET['type']}"
							 . "&username=$userid&user_email=$user_email&scheduling={$_POST['scheduling']}&repeatforms={$_POST['repeatforms']}"
							 . "&purpose={$_POST['purpose']}&purpose_other=".urlencode($_POST['purpose_other'])
							 . "&surveys_enabled={$_POST['surveys_enabled']}"
							 . "&randomization={$_POST['randomization']}"
							 . "&project_pi_firstname=".urlencode($_POST['project_pi_firstname'])
							 . "&project_pi_mi=".urlencode($_POST['project_pi_mi'])
							 . "&project_pi_lastname=".urlencode($_POST['project_pi_lastname'])
							 . "&project_pi_email=".urlencode($_POST['project_pi_email'])
							 . "&project_pi_alias=".urlencode($_POST['project_pi_alias'])
							 . "&project_pi_username=".urlencode($_POST['project_pi_username'])
							 . "&project_irb_number=".urlencode($_POST['project_irb_number'])
							 . "&project_grant_number=".urlencode($_POST['project_grant_number'])
							 . "&template=".urlencode($_POST['copyof'])
							 . "&app_title=".urlencode($_POST['app_title'])."'>{$lang['email_admin_12']}</a>";
			// Finalize email
			$email->setBody("<html><head><title>$emailSubject</title></head><body style='font-family:arial;font-size:10pt;'>$emailContents</body></html>");
			$email->setSubject($emailSubject);
			if ($email->send()) {
				log_event("","redcap_data","MANAGE",$project_id,"project_id = $project_id","Send request to create project");
			} else {
				exit($emailContents);
			}
			// Redirect user to a confirmation page
			redirect(APP_PATH_WEBROOT_FULL . "index.php?action=requested_new");
			exit;

		// Send email request to admin to COPY project
		case 'request_copy':
			$email->setFrom($user_email);
			$email->setTo($project_contact_prod_changes_email);
			$emailSubject  =   "[REDCap] {$lang['email_admin_13']}";
			$emailContents =   "{$lang['global_21']}<br><br>
								{$lang['email_admin_03']} <b>" . html_entity_decode("$user_firstname $user_lastname", ENT_QUOTES) . "</b> 
								(<a href='mailto:$user_email'>$user_email</a>) 
								{$lang['email_admin_14']} 
								<b>" . strip_tags(html_entity_decode($app_title, ENT_QUOTES)) . "</b>{$lang['period']}<br><br>
								{$lang['email_admin_05']}<br>
								<a href='" . APP_PATH_WEBROOT_FULL . "redcap_v{$redcap_version}/ProjectGeneral/copy_project_form.php?pid=$project_id"
								 . "&username=$userid&user_email=$user_email&scheduling={$_POST['scheduling']}&repeatforms={$_POST['repeatforms']}"
								 . "&purpose={$_POST['purpose']}&purpose_other=".urlencode($_POST['purpose_other'])
								 . "&surveys_enabled={$_POST['surveys_enabled']}"
								 . "&randomization={$_POST['randomization']}"
								 . "&project_pi_firstname=".urlencode($_POST['project_pi_firstname'])
								 . "&project_pi_mi=".urlencode($_POST['project_pi_mi'])
								 . "&project_pi_lastname=".urlencode($_POST['project_pi_lastname'])
								 . "&project_pi_email=".urlencode($_POST['project_pi_email'])
								 . "&project_pi_alias=".urlencode($_POST['project_pi_alias'])
								 . "&project_pi_username=".urlencode($_POST['project_pi_username'])
								 . "&project_irb_number=".urlencode($_POST['project_irb_number'])
								 . "&project_grant_number=".urlencode($_POST['project_grant_number'])
								 . "&c_users={$_POST['copy_users']}&c_reports={$_POST['copy_reports']}&c_records={$_POST['copy_records']}&app_title=".urlencode($_POST['app_title'])
								 . "'>{$lang['email_admin_15']}</a>";
			// Finalize email
			$email->setBody("<html><head><title>$emailSubject</title></head><body style='font-family:arial;font-size:10pt;'>$emailContents</body></html>");
			$email->setSubject($emailSubject);
			if ($email->send()) {
				log_event("","redcap_data","MANAGE",$project_id,"project_id = $project_id","Send request to copy project");
			} else {
				exit($emailContents);
			}
			// Redirect user to a confirmation page
			redirect(APP_PATH_WEBROOT_FULL . "index.php?action=requested_copy&app_title=$app_title");
			exit;
		
	}
}

