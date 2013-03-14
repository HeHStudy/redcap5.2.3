<?php

/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

/**
 * A simple controller for AJAX requests related to the REDCap API.
 */

// Config for non-project pages
require_once dirname(dirname(__FILE__)) . "/Config/init_global.php";

// If user is not a super user, go back to Home page
if (!$super_user) { redirect(APP_PATH_WEBROOT); }

function getAPICreateLink($id, $username, $project_id) {
	global $lang;
	return RCView::iconLink($id, APP_PATH_IMAGES . 'add.png',
				$lang['control_center_253'], $_SERVER['PHP_SELF'],
				array('action' => 'createToken', 'api_pid' => $project_id, 'api_username' => $username));
}
function getAPIDelLink($id, $username, $project_id) {
	global $lang;
	return RCView::iconLink($id, APP_PATH_IMAGES . 'cross.png',
				$lang['control_center_247'], $_SERVER['PHP_SELF'],
				array('action' => 'deleteToken', 'api_pid' => $project_id, 'api_username' => $username));
}
function getAPIRegenLink($id, $username, $project_id) {
	global $lang;
	return RCView::iconLink($id, APP_PATH_IMAGES . 'arrow_refresh.png',
				$lang['control_center_249'], $_SERVER['PHP_SELF'],
				array('action' => 'regenToken', 'api_pid' => $project_id, 'api_username' => $username));
}
function getAPIViewLink($id, $username, $project_id) {
	global $lang;
	return RCView::iconLink($id, APP_PATH_IMAGES . 'magnifier.png',
				$lang['control_center_322'], $_SERVER['PHP_SELF'],
				array('action' => 'viewToken', 'api_pid' => $project_id, 'api_username' => $username));
}
function getAPIRightsDisplay($rights) {
	global $lang;
	if ($rights->api_export && $rights->api_import) return $lang['global_74'];
	elseif ($rights->api_export) return $lang['global_71'];
	elseif ($rights->api_import) return $lang['global_72'];
	else return $lang['global_75'];
}
function tsToCallDate($ts) {
	return substr($ts, 4, 2) . '/' . substr($ts, 6, 2) . '/' . substr($ts, 0, 4);
}

$db = new RedCapDB();

$ajaxData = "Invalid AJAX call!"; // holds the data that will be returned by the AJAX call

if ($_GET['action'] == 'tokensByUser') {
	$allUserMode = ($_GET['username'] == "-1");
	$rights = $allUserMode ? $db->getAPITokens() : $db->getUserRights($_GET['username']);
	$usernames = $db->getUsernamesWithProjects();
	$userOpts = array('' => $lang['control_center_278'], '-1' => $lang['control_center_268']);
	foreach ($usernames as $u) $userOpts[$u->username] = $u->username;
	$rows = array();
	// NOTE: JS handler for this select box is on user_api_tokens.php
	$s = '';
	$s .= RCView::b($lang['control_center_266'] . ' ' . $lang['global_17'] . $lang['colon']);
	$s .= RCView::SP . RCView::SP . RCView::SP;
	$s .= RCView::select(array('name' => 'api_username', 'id' => 'apiUserSelId', 'style'=>'max-width:350px;'), $userOpts);
	$rows[] = $s;
	$hdr = array();
	if ($allUserMode) $hdr[] = $lang['global_17'];
	$hdr[] = $lang['global_65'];
	$hdr[] = $lang['global_76'];
	$hdr[] = $lang['global_73'];
	$hdr[] = $lang['global_67'];
	// only add the column headers if we have data to display
	if (count($rights) > 0) $rows[] = $hdr;
	foreach ($rights as $r) {
		$row = array();
		if ($allUserMode) $row[] = RCView::escape($r->username);
		$row[] = RCView::escape($r->app_title);
		$jsId = "userAPICallDateId_" . $r->username . "_" . $r->project_id;
		$row[] = RCView::span(array('id' => $jsId), RCView::font(array('style' => 'font-size: smaller; color: gray;'), $lang['dashboard_39'] . '...'));
		$row[] = RCView::escape(getAPIRightsDisplay($r));
		$cnt = count($rows);
		$c = '';
		// NOTE: JS handlers for these links are on user_api_tokens.php
		if (empty($r->api_token)) {
			$c .= getAPICreateLink("apiCreateId$cnt", $r->username, $r->project_id);
		}
		else {
			$c .= getAPIDelLink("apiDelId$cnt", $r->username, $r->project_id);
			$c .= RCView::SP . RCView::SP;
			$c .= getAPIRegenLink("apiRegenId$cnt", $r->username, $r->project_id);
			$c .= RCView::SP . RCView::SP;
			// Icon to open dialog to view the token
			$c .= getAPIViewLink("apiViewId$cnt", $r->username, $r->project_id);
		}
		$row[] = $c;
		$rows[] = $row;
	}
	$widths = $allUserMode ? array(70, 290, 50, 40, 80) : array(360, 50, 40, 80);
	$ajaxData = RCView::simpleGrid($rows, $widths);
}
elseif ($_GET['action'] == 'tokensByProj') {
	$allProjMode = $_GET['project_id'] == "-1" ? true : false;
	$rights = $allProjMode ? $db->getAPITokens(true) : $db->getProjectRights($_GET['project_id']);
	$projOpts = array('' => $lang['control_center_279'], '-1' => $lang['control_center_269']);
	$projects = $db->getProjects();
	foreach ($projects as $p) $projOpts[$p->project_id] = $p->app_title;
	$app_title = $allProjMode ? $lang['control_center_269'] : current($rights)->app_title;
	$rows = array();
	// Define table header
	$s = '';
	if (isset($_GET['controlCenterView']) && $_GET['controlCenterView']) {
		$s .= RCView::b($lang['control_center_266'] . ' ' . $lang['global_65'] . $lang['colon']);
		$s .= RCView::SP . RCView::SP . RCView::SP;
		$s .= RCView::select(array('name' => 'api_pid', 'id' => 'apiProjSelId', 'style'=>'max-width:350px;'), $projOpts, '', 70);
	} else {
		$s .= RCView::b($lang['control_center_339']);
		$s .= RCView::select(array('name' => 'api_pid', 'id' => 'apiProjSelId', 'style'=>'display:none;'), $projOpts, '', 70);
	}
	$rows[] = $s;
	$hdr = array();
	if ($allProjMode) $hdr[] = $lang['global_65'];
	$hdr[] = $lang['global_17'];
	$hdr[] = $lang['global_76'];
	$hdr[] = $lang['global_73'];
	$hdr[] = $lang['global_67'];
	// only add the column headers if we have data to display
	if (count($rights) > 0) $rows[] = $hdr;
	foreach ($rights as $r) {
		$row = array();
		if ($allProjMode) $row[] = RCView::escape($r->app_title);
		$row[] = RCView::escape($r->username);
		$jsId = "projAPICallDateId_" . $r->username . "_" . $r->project_id;
		$row[] = RCView::span(array('id' => $jsId), RCView::font(array('style' => 'font-size: smaller; color: gray;'), $lang['dashboard_39'] . '...'));
		$row[] = RCView::escape(getAPIRightsDisplay($r));
		$cnt = count($rows);
		$c = '';
		// NOTE: JS handlers for these links are on user_api_tokens.php
		if (empty($r->api_token)) {
			$c .= getAPICreateLink("apiCreateId$cnt", $r->username, $r->project_id);
		}
		else {
			$c .= getAPIDelLink("apiDelId$cnt", $r->username, $r->project_id);
			$c .= RCView::SP . RCView::SP;
			$c .= getAPIRegenLink("apiRegenId$cnt", $r->username, $r->project_id);
			$c .= RCView::SP . RCView::SP;
			// Icon to open dialog to view the token
			$c .= getAPIViewLink("apiViewId$cnt", $r->username, $r->project_id);
		}
		$row[] = $c;
		$rows[] = $row;
	}
	$widths = $allProjMode ? array(290, 70, 50, 40, 80) : array(360, 50, 40, 80);
	$ajaxData = RCView::simpleGrid($rows, $widths);
}
elseif ($_GET['action'] == 'getAPIDateForUserJS') {
	$allUserMode = $_GET['username'] == "-1" ? true : false;
	$rights = $allUserMode ? $db->getAPITokens() : $db->getUserRights($_GET['username']);
	$ajaxData = "1;";
	$callDates = array();
	if (count($rights) > 0)
		$callDates = $allUserMode ? $db->getLastAPICallDates() : $db->getLastAPICallDates($_GET['username']);
	foreach ($rights as $r) {
		$jsId = "userAPICallDateId_" . $r->username . "_" . $r->project_id;
		if (empty($callDates[$r->username][$r->project_id]))
			$ajaxData .= "\$(\"#$jsId\").html(\"" . $lang['index_37'] . "\");";
		else {
			$ts = $callDates[$r->username][$r->project_id]->LastTS;
			$ajaxData .= "\$(\"#$jsId\").html(\"" . tsToCallDate($ts) . "\");";
		}
	}
}
elseif ($_GET['action'] == 'getAPIDateForProjJS') {
	$allProjMode = $_GET['project_id'] == "-1" ? true : false;
	$rights = $allProjMode ? $db->getAPITokens(true) : $db->getProjectRights($_GET['project_id']);
	$ajaxData = "1;";
	$callDates = array();
	if (count($rights) > 0)
		$callDates = $allProjMode ? $db->getLastAPICallDates() : $db->getLastAPICallDates(null, $_GET['project_id']);
	foreach ($rights as $r) {
		$jsId = "projAPICallDateId_" . $r->username . "_" . $r->project_id;
		if (empty($callDates[$r->username][$r->project_id]))
			$ajaxData .= "\$(\"#$jsId\").html(\"" . $lang['index_37'] . "\");";
		else {
			$ts = $callDates[$r->username][$r->project_id]->LastTS;
			$ajaxData .= "\$(\"#$jsId\").html(\"" . tsToCallDate($ts) . "\");";
		}
	}
}
elseif ($_GET['action'] == 'getAPIRights') {
	$rights = $db->getRights($_GET['api_username'], $_GET['api_pid']);
	$projInfo = $db->getProject($_GET['api_pid']);
	$h = RCView::b($lang['control_center_274'] . RCView::SP . RCView::escape('"' . $_GET['api_username'] . '"')) . RCView::br();
	$h .= $lang['control_center_275'] . ' "' . RCView::b(RCView::escape($projInfo->app_title)) . '"';
	$h .= $lang['period'] . ' ' . $lang['control_center_276'];
	$h .= RCView::br() . RCView::br();
	// Export checkbox
	$attrs = array('name' => 'api_export', 'id' => 'api_export','class'=>'imgfix2');
	if ($rights->api_export) $attrs['checked'] = 'checked';
	$i  = RCView::checkbox($attrs);
	$i .= $lang['rights_139'];
	$i .= RCView::br();
	// Import checkbox
	$attrs = array('name' => 'api_import', 'id' => 'api_import','class'=>'imgfix2');
	if ($rights->api_import) $attrs['checked'] = 'checked';
	$i .= RCView::checkbox($attrs);
	$i .= $lang['rights_140'];
	$i .= RCView::hidden(array('id' => 'rightsUsername', 'value' => $_GET['api_username']));
	// Display checkboxes and box with "send email" option
	$h .= 	RCView::div(array('style'=>'vertical-align:top;'),
				RCView::div(array('style'=>'float:left;'), $i) .
				RCView::div(array('class'=>'chklist','style'=>'margin:4px 0 10px;float:right;padding:10px;'), 
					RCView::checkbox(array('name' => 'api_send_email', 'id' => 'api_send_email', 'checked' => 'checked','class'=>'imgfix2')) . RCView::SP . RCView::SP . 
					RCView::img(array('src'=>'email.png','class'=>'imgfix')) . 
					$lang['control_center_337']
				)
			);
	$ajaxData = RCView::div(array('style'=>'padding:10px 5px 5px;line-height:1.4em !important;'), $h);
}
elseif ($_GET['action'] == 'createToken' || $_GET['action'] == 'regenToken' || $_GET['action'] == 'viewToken') {
	if (empty($_GET['api_username'])) {
		$ajaxData = RCView::errorBox($lang['control_center_255'] . ' - ' . $lang['control_center_256'], 'dialogAJAXId');
	}
	elseif (empty($_GET['api_pid'])) {
		$ajaxData = RCView::errorBox($lang['control_center_255'] . ' - ' . $lang['control_center_257'], 'dialogAJAXId');
	}
	elseif ($_GET['action'] == 'createToken' && $db->getAPIToken($_GET['api_username'], $_GET['api_pid'])) {
		$ajaxData = RCView::errorBox($lang['control_center_255'] . ' - ' . $lang['control_center_258'], 'dialogAJAXId');
	}
	elseif ($_GET['action'] == 'viewToken') {
		$projInfo = $db->getProject($_GET['api_pid']);
		$userInfo = $db->getUserInfoByUsername($_GET['api_username']);
		$projectLink = APP_PATH_WEBROOT_FULL . "redcap_v$redcap_version" . '/index.php?pid=' . $projInfo->project_id;
		$apiProjectLink = APP_PATH_WEBROOT_FULL . "redcap_v$redcap_version" . '/API/project_api.php?pid=' . $projInfo->project_id;
		$ajaxData = "{$lang['control_center_325']} <b>{$_GET['api_username']}</b> {$lang['control_center_326']} ".
					RCView::a(array('target'=>'_blank','href'=>$projectLink,'style'=>'text-decoration:underline;'), $projInfo->app_title)."{$lang['period']}".
					// Don't show text that user will be emailed if the user is the current user
					($userid == $userInfo->username ? "" : " {$lang['control_center_327']} <b>{$_GET['api_username']}</b> {$lang['control_center_328']}")."
					<br><br>{$lang['control_center_333']}{$lang['colon']}" . 
					RCView::div(array('style'=>'font-size: 18px; font-weight: bold; color: #347235;margin:5px 0;'),
						$db->getAPIToken($_GET['api_username'], $_GET['api_pid'])
					);
		// If the user is the current user, then don't email self AND don't log this event
		if ($userid != $userInfo->username) 
		{
			// Log the action
			defined("PROJECT_ID") or define("PROJECT_ID", $_GET['api_pid']);
			log_event("", "redcap_user_rights", "MANAGE", $_GET['api_username'], "user = '" . $_GET['api_username'] . "'", "View API token of another user");
			// If don't have an email, then can't send it
			if ($userInfo->user_email != '') {
				// Now email the user to let them know someone just viewed their token
				$email = new Message();
				$email->setFrom($projInfo->project_contact_prod_changes_email);
				$email->setTo($userInfo->user_email);
				$email->setSubject('[REDCap] '.$lang['control_center_329']);
				$msg =  $lang['control_center_330'] . " $user_firstname $user_lastname (<b>$userid</b>, $user_email) {$lang['control_center_331']} 
						<b>{$_GET['api_username']}</b> {$lang['control_center_326']} " . 
						RCView::a(array('href'=>$projectLink), RCView::escape($projInfo->app_title))."{$lang['period']}
						{$lang['control_center_336']}<br \><br \>{$lang['control_center_334']} ".
						RCView::a(array('href'=>$apiProjectLink), $lang['control_center_335']).$lang['period'];
				$email->setBody($msg, true);
				$email->send();
			}
		}
	}
	else {
		$sql = $db->saveAPIRights($_GET['api_username'], $_GET['api_pid'], $_GET['api_export'], $_GET['api_import']);
		if (count($sql) > 0) {
			log_event("", "redcap_user_rights", "MANAGE", $_GET['api_username'], "user = '" . $_GET['api_username'] . "'", "Set API rights for user");
		}
		$rights = $db->getRights($_GET['api_username'], $_GET['api_pid']);
		if (!$rights->api_export && !$rights->api_import) {
			$ajaxData = RCView::errorBox($lang['control_center_255'] . ' - ' . $lang['control_center_277'], 'dialogAJAXId');
		}
		else {
			$sql = $db->setAPIToken($_GET['api_username'], $_GET['api_pid']);
			if (count($sql) === 0) {
				$ajaxData = RCView::errorBox($lang['control_center_255'] . ' - ' . $lang['control_center_259'], 'dialogAJAXId');
			}
			else {
				// Logging
				log_event("", "redcap_user_rights", "MANAGE", $_GET['api_username'], "user = '" . $_GET['api_username'] . "'", "Create API token for user");
				// Get project and user info
				$userInfo = $db->getUserInfoByUsername($_GET['api_username']);
				$projInfo = $db->getProject($_GET['api_pid']);
				// Send email (if specified)
				if (isset($_GET['api_send_email']) && $_GET['api_send_email']) {
					$email = new Message();
					$email->setFrom($projInfo->project_contact_prod_changes_email);
					$email->setTo($userInfo->user_email);
					$email->setSubject('[REDCap] '.$lang['control_center_260']);
					$msg = $lang['control_center_263'] . ' "' . RCView::b(RCView::escape($projInfo->app_title)).'"'.$lang['period'];
					$msg .= "<br><br>\n";
					$retrieveLink = APP_PATH_WEBROOT_FULL . "redcap_v$redcap_version" . '/API/project_api.php?pid=' . $projInfo->project_id;
					$msg .= RCView::a(array('href' => $retrieveLink), $lang['control_center_265']);
					$email->setBody($msg, true);
					if (!$email->send()) {
						$ajaxData = RCView::errorBox($lang['control_center_260'] . ' (' . $lang['global_69'] . ')', 'dialogAJAXId');
					}
					else {
						$ajaxData = RCView::confBox($lang['control_center_260'] . ' ' . $lang['data_entry_67'] .
							' ' . RCView::b(RCView::escape($userInfo->username)) . ' ' . $lang['global_51'] . ' ' .
										RCView::b(RCView::i(RCView::escape($projInfo->app_title))) . ' (' . $lang['global_68'] . ')', 'dialogAJAXId');
					}
				} else {
					$ajaxData = RCView::confBox($lang['control_center_260'] . ' ' . $lang['data_entry_67'] .
							' ' . RCView::b(RCView::escape($userInfo->username)) . ' ' . $lang['global_51'] . ' ' .
										RCView::b(RCView::i(RCView::escape($projInfo->app_title))), 'dialogAJAXId');
				}
			}
		}
	}
}
elseif ($_GET['action'] == 'deleteToken') {
	if (empty($_GET['api_username'])) {
		$ajaxData = RCView::errorBox($lang['control_center_261'] . ' - ' . $lang['control_center_256'], 'dialogAJAXId');
	}
	elseif (empty($_GET['api_pid'])) {
		$ajaxData = RCView::errorBox($lang['control_center_261'] . ' - ' . $lang['control_center_257'], 'dialogAJAXId');
	}
	else {
		$sql = $db->deleteAPIToken($_GET['api_username'], $_GET['api_pid']);
		if (count($sql) === 0) {
			$ajaxData = RCView::errorBox($lang['control_center_261'] . ' - ' . $lang['control_center_259'], 'dialogAJAXId');
		}
		else {
			log_event("", "redcap_user_rights", "MANAGE", $_GET['api_username'], "user = '" . $_GET['api_username'] . "'", "Delete API token for user");
			// notify the user about the deletion
			$userInfo = $db->getUserInfoByUsername($_GET['api_username']);
			$projInfo = $db->getProject($_GET['api_pid']);
			$email = new Message();
			$email->setFrom($projInfo->project_contact_prod_changes_email);
			$email->setTo($userInfo->user_email);
			$email->setSubject('[REDCap] '.$lang['control_center_262']);
			$msg = $lang['control_center_282'] . ' "' . RCView::b(RCView::escape($projInfo->app_title)) . '"' . $lang['period'];
			$email->setBody($msg, true);
			$email->send();
			$ajaxData = RCView::confBox($lang['control_center_262'] . ' ' . $lang['data_entry_67'] .
						' ' . RCView::b(RCView::escape($userInfo->username)) . ' ' . $lang['global_51'] . ' ' .
									RCView::b(RCView::i(RCView::escape($projInfo->app_title))), 'dialogAJAXId');
		}
	}
}
elseif ($_GET['action'] == 'countProjectTokens') {
	if (empty($_GET['api_pid'])) {
		$ajaxData = $lang['global_01'] . ' - ' . $lang['control_center_257'];
	}
	else {
		$ajaxData = $db->countAPITokensByProject($_GET['api_pid']);
	}
}
elseif ($_GET['action'] == 'deleteProjectTokens') {
	if (empty($_GET['api_pid'])) {
		$ajaxData = $lang['global_01'] . ' - ' . $lang['control_center_257'];
	}
	else {
		$rights = $db->getProjectRights($_GET['api_pid']);
		$usernames = array();
		foreach ($rights as $r) if (!empty($r->api_token)) $usernames[] = $r->username;
		$users = $db->getUserInfoByUsernames($usernames);
		$projInfo = $db->getProject($_GET['api_pid']);
		$sql = $db->deleteAPIProjectTokens($_GET['api_pid']);
		if (count($sql) == 0) $ajaxData = $lang['control_center_272'];
		else {
			$ajaxData = $lang['control_center_271'];
			// notify users about the deletion
			foreach ($users as $userInfo) {
				$email = new Message();
				$email->setFrom($projInfo->project_contact_prod_changes_email);
				$email->setTo($userInfo->user_email);
				$email->setSubject('[REDCap] '.$lang['control_center_262']);
				$msg = $lang['control_center_282'] . ' "' . RCView::b(RCView::escape($projInfo->app_title)) . '"' . $lang['period'];
				$email->setBody($msg, true);
				$email->send();
			}
		}
	}
}

exit($ajaxData);