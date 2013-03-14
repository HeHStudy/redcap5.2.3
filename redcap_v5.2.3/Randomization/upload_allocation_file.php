<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


// Config
require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

// Make sure is Post request and also that file was uploaded
if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_FILES['allocFile'])) exit($lang['random_13']);

// Convert uploaded file into array 
$csv_array = csv_file_to_array($_FILES['allocFile']);

// Now check the values from the file for integrity
$errors = Randomization::checkAllocFile($csv_array);

// If errors exist, then output them
if (!empty($errors))
{
	include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
	print RCView::errorBox($lang['random_17'] . RCView::br() . RCView::br() . " - " . implode(RCView::br() . " - ", $errors));
	print RCView::div(array('style'=>'padding:10px 0 5px;'), RCView::btnGoBack());
	include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
}
else
{
	// Save allocation file in database table
	if (Randomization::saveAllocFile($csv_array)) {
		// If a super user appending to table while in prod, then give different message
		$msg = ($status > 0 && SUPER_USER) ? "appendtablesuccess" : "uploadtablesuccess";
		// Now check that it doesn't match the other allocation table exactly (if another alloc table exists)
		$this_status = $_POST['alloc_status'];
		$other_status = ($this_status == '1') ? 0 : 1; // Get opposite project status (get prod if dev and vice versa)
		if (Randomization::allocTableExists($other_status)) {
			// The other allocation table does exist, so make sure not exactly the same as ours uploaded here
			if (Randomization::getAllocFileContents($other_status) === Randomization::getAllocFileContents($this_status)) {
				// It's a match, so delete the one we just uploaded and give appropriate error msg
				Randomization::deleteAllocFile($this_status);
				$msg = "errorduplicatetable";
			}
		}		
		// Logging
		if ($msg == "uploadtablesuccess") {
			$statusText = ($this_status == '1') ? "production" : "development";
			log_event("", "redcap_randomization", "MANAGE", PROJECT_ID, "project_id = " . PROJECT_ID, "Upload randomization allocation table ($statusText)");
		}
	} else {
		$msg = "error";
	}	
	// If successful, then give message of success by redirecting back to Setup page
	redirect(APP_PATH_WEBROOT . "Randomization/index.php?pid=$project_id&msg=$msg");
}