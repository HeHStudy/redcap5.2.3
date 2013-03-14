<?php

/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

/**
 * A simple controller for AJAX requests related to the project API page.
 */

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

if (!$api_enabled) exit("API is disabled!");

$db = new RedCapDB();

$ajaxData = "Invalid AJAX call!"; // holds the data that will be returned by the AJAX call

if ($_POST['action'] == 'requestToken' && $api_enabled) {
	if (empty($project_id)) {
		$ajaxData = RCView::errorBox($lang['edit_project_89'] . ' - ' . $lang['control_center_257'], 'apiDialogId');
	}
	elseif ($db->getAPIToken($userid, $project_id)) {
		$ajaxData = RCView::errorBox($lang['edit_project_89'] . ' - ' . $lang['control_center_258'], 'apiDialogId');
	}
	else {
		$userInfo = $db->getUserInfoByUsername($userid);
		$projInfo = $db->getProject($project_id);
		$email = new Message();
		$email->setFrom($userInfo->user_email);
		$email->setTo($projInfo->project_contact_prod_changes_email);
		$email->setSubject('[REDCap] "'.$userid . '" ' . $lang['edit_project_91']);
		$msg = RCView::escape("$userInfo->user_firstname $userInfo->user_lastname ($userid) ");
		$msg .= $lang['edit_project_91'] . ' ';
		$msg .= $lang['edit_project_92'];
		$msg .= ' "'.RCView::b(RCView::escape($projInfo->app_title)).'"'.$lang['period'];
		$msg .= "<br><br>\n";
		$approveLink = APP_PATH_WEBROOT_FULL . "redcap_v$redcap_version" . '/ControlCenter/user_api_tokens.php?action=createToken&api_username=' . $userid . '&api_pid=' . $project_id;
		$msg .= RCView::a(array('href' => $approveLink), $lang['edit_project_93']);
		$email->setBody($msg, true);
		if ($email->send()) {
			$ajaxData = RCView::confBox($lang['edit_project_90'], 'apiDialogId');
			// Logging
			log_event("", "redcap_user_rights", "MANAGE", $userid, "user = '$userid'", "Request API token");
		}
		else {
			$ajaxData = RCView::errorBox($lang['edit_project_89'] . ' - ' . $lang['global_66'], 'apiDialogId');
		}
	}
}
elseif ($_POST['action'] == 'deleteToken' && $api_enabled) {
	if (empty($project_id)) {
		$ajaxData = RCView::errorBox($lang['edit_project_98'] . ' - ' . $lang['control_center_257'], 'apiDialogId');
	}
	elseif (!$db->getAPIToken($userid, $project_id)) {
		$ajaxData = RCView::errorBox($lang['edit_project_98'] . ' - ' . $lang['control_center_270'], 'apiDialogId');
	}
	else {
		$sql = $db->deleteAPIToken($userid, $project_id);
		if (count($sql) === 0) {
			$ajaxData = RCView::errorBox($lang['edit_project_98'] . ' - ' . $lang['control_center_259'], 'apiDialogId');
		}
		else {
			log_event("", "redcap_user_rights", "MANAGE", $userid, "user = '" . $userid . "'", "User delete own API token");
			$ajaxData = RCView::confBox($lang['edit_project_99'], 'apiDialogId');
		}
	}
}
elseif ($_POST['action'] == 'regenToken' && $api_enabled) {
	if (empty($project_id)) {
		$ajaxData = RCView::errorBox($lang['edit_project_100'] . ' - ' . $lang['control_center_257'], 'apiDialogId');
	}
	elseif (!$db->getAPIToken($userid, $project_id)) {
		$ajaxData = RCView::errorBox($lang['edit_project_100'] . ' - ' . $lang['control_center_270'], 'apiDialogId');
	}
	else {
		$sql = $db->setAPIToken($userid, $project_id);
		if (count($sql) === 0) {
			$ajaxData = RCView::errorBox($lang['edit_project_100'] . ' - ' . $lang['control_center_259'], 'apiDialogId');
		}
		else {
			log_event("", "redcap_user_rights", "MANAGE", $userid, "user = '" . $userid . "'", "User regenerate own API token");
			$ajaxData = RCView::confBox($lang['edit_project_101'], 'apiDialogId');
		}
	}
}
elseif ($_GET['action'] == 'getToken' && $api_enabled) {
	$ajaxData = $db->getAPIToken($userid, $project_id);
}
elseif ($_GET['action'] == 'getTokens' && !empty($project_id) && $api_enabled) {
	$usernames = array();
	$toks = $db->getAPITokens(false, $project_id);
	foreach ($toks as $t) $usernames[] = $t->username;
	$ajaxData = RCView::escape(implode(', ', $usernames));
}

exit($ajaxData);