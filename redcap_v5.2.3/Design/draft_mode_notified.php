<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';


// Kick out if project is not in production status yet
if ($status < 1) {
	redirect(APP_PATH_WEBROOT . "index.php?pid=$project_id");
	exit;
}

// Header
include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

// Only super users may use this page
if (!$super_user) {
	print  "<div class='red' style='text-align:center;'>
				{$lang['draft_mode_08']}
			</div>";
	exit;
}

// Show text to Super User based upon the action performed on the previous page
switch ($_GET['action']) {

	// Approved
	case 'approve':
		renderPageTitle($lang['draft_mode_09']);
		print  "<p style='padding-top:10px;'>
					{$lang['draft_mode_10']}
					<b class='notranslate'>{$_GET['user_firstname']} {$_GET['user_lastname']}</b> 
					(<a class='notranslate' style='text-decoration:underline;' href='mailto:{$_GET['user_email']}'>{$_GET['user_email']}</a>), 
					{$lang['draft_mode_11']}
				</p>";
		break;

	// Rejected
	case 'reject':
		renderPageTitle($lang['draft_mode_12']);
		print  "<p style='padding-top:10px;'>
					{$lang['draft_mode_13']}
					<b class='notranslate'>{$_GET['user_firstname']} {$_GET['user_lastname']}</b> 
					(<a class='notranslate' style='text-decoration:underline;' href='mailto:{$_GET['user_email']}'>{$_GET['user_email']}</a>), 
					{$lang['draft_mode_11']}
				</p>";
		break;

	// Reset
	case 'reset':
		renderPageTitle($lang['draft_mode_14']);
		print  "<p style='padding-top:10px;'>
					{$lang['draft_mode_15']}
					<b class='notranslate'>{$_GET['user_firstname']} {$_GET['user_lastname']}</b> 
					(<a class='notranslate' style='text-decoration:underline;' href='mailto:{$_GET['user_email']}'>{$_GET['user_email']}</a>), 
					{$lang['draft_mode_11']}
				</p>";
		break;
		
}



include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
