<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';


// Use ui_id from redcap_user_information to retrieve Username+First+Last
function getUsernameFirstLast($ui_id)
{
	global $ui_ids;
	// Must be numeric
	if (!is_numeric($ui_id))   return false;
	// If already called, retrieve from array instead of querying
	if (isset($ui_ids[$ui_id])) return $ui_ids[$ui_id];
	// Get from table
	$sql = "select concat(username,' (',user_firstname,' ',user_lastname,')') from redcap_user_information where ui_id = $ui_id";
	$q = db_query($sql);
	if (db_num_rows($q) > 0) {
		// Add to array if called again
		$ui_ids[$ui_id] = db_result($q, 0);
		// Return query result
		return $ui_ids[$ui_id];
	}
	return false;
}



// Array for storing username and first/last to reduce number of queries if lots of revisions exist
$ui_ids = array();
// Get username/name of project creator
$creatorName = empty($created_by) ? "" : $lang['rev_history_14'] . " <span style='color:#800000;'>" . getUsernameFirstLast($created_by) . "</span>";
// Create array with times of creation, production, and production revisions
$revision_info = array();
// Get prod time and revisions, if any
if ($status < 1)
{
	// Creation time
	$revision_info[] = array($lang['rev_history_01'], format_ts_mysql($creation_time), "<img src='" . APP_PATH_IMAGES . "xls.gif' class='imgfix'> <a style='color:green;font-size:11px;' href='" . APP_PATH_WEBROOT . "Design/data_dictionary_download.php?pid=$project_id&fileid=data_dictionary'>{$lang['rev_history_04']}</a>", $creatorName);
	// Add production time to table
	$revision_info[] = array($lang['rev_history_02'], "<span style='line-height:19px;'>-</span>", "", "");
}
else
{
	// Creation time
	$revision_info[] = array($lang['rev_history_01'], format_ts_mysql($creation_time), "", $creatorName);
	// Retrieve person who moved to production
	$sql = "select concat(u.username,' (',u.user_firstname,' ',u.user_lastname,')') from redcap_user_information u, redcap_log_event l
			where u.username = l.user and l.description = 'Move project to production status'
			and l.project_id = $project_id order by log_event_id desc limit 1";
	$q = db_query($sql);
	$moveProdName = (db_num_rows($q) > 0) ? $lang['rev_history_18'] . " <span style='color:#800000;'>" . db_result($q, 0) . "</span>" : "";
	// Production time
	$revision_info[] = array($lang['rev_history_02'], format_ts_mysql($production_time), "<span style='line-height:19px;'>-</span>", $moveProdName);
	// Get revisions
	$revnum = 1;
	$revTimes = array($production_time);
	$sql = "select p.pr_id, p.ts_approved, p.ui_id_requester, p.ui_id_approver, 
			if(l.description = 'Approve production project modifications (automatic)',1,0) as automatic 
			from redcap_metadata_prod_revisions p left outer join redcap_log_event l 
			on p.project_id = l.project_id and p.ts_approved*1 = l.ts 
			where p.project_id = $project_id and p.ts_approved is not null order by p.pr_id";
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q))
	{
		// Get username/name of project creator
		$requesterName = getUsernameFirstLast($row['ui_id_requester']);
		if (!empty($requesterName)) $requesterName = $lang['rev_history_15'] . " <span style='color:#800000;'>$requesterName</span>";
		// Get username/name of approver if not approved automatically
		if ($row['automatic']) {
			$approverName = $lang['rev_history_16'];
		} else {
			// Get username/name of approver
			$approverName = getUsernameFirstLast($row['ui_id_approver']);
			if (!empty($approverName)) $approverName = $lang['rev_history_17'] . " <span style='color:#800000;'>$approverName</span>";
		}
		// Add to array
		$revision_info[] = array($lang['rev_history_03']." #".$revnum, format_ts_mysql($row['ts_approved']), 
								 "<img src='" . APP_PATH_IMAGES . "xls.gif' class='imgfix'> <a style='color:green;font-size:11px;' href='" . APP_PATH_WEBROOT . "Design/data_dictionary_download.php?pid=$project_id&rev_id={$row['pr_id']}&fileid=data_dictionary".($revnum > 1 ? "&revnum=".($revnum-1) : "")."'>{$lang['rev_history_04']}</a>",
								 "$requesterName<br>$approverName");
		// Get last rev time for use later
		$revTimes[] = $row['ts_approved'];
		// Increate counter
		$revnum++;
	}
	// Get max array key
	$maxKey = count($revision_info)-1;
	// Push all data dictionary links up one row in table (because each represents when each was archived, so it's off one)
	for ($key = 0; $key < $maxKey; $key++)
	{
		$revision_info[$key][2] = $revision_info[$key+1][2];
	}
	// Now fix the last entry with current DD link and append "current" to current revision label
	$revision_info[$maxKey][0] .= " ".$lang['rev_history_05'];
	$revision_info[$maxKey][2] = "<img src='" . APP_PATH_IMAGES . "xls.gif' class='imgfix'> <a style='color:green;font-size:11px;' href='" . APP_PATH_WEBROOT . "Design/data_dictionary_download.php?pid=$project_id&fileid=data_dictionary'>{$lang['rev_history_04']}</a>";
	// If currently in draft mode, give row to download current
	if ($draft_mode > 0)
	{
		$revision_info[] = array($lang['rev_history_06'], "-",
								 "<img src='" . APP_PATH_IMAGES . "xls.gif' class='imgfix'> <a style='color:green;font-size:11px;' href='" . APP_PATH_WEBROOT . "Design/data_dictionary_download.php?pid=$project_id&fileid=data_dictionary&draft'>{$lang['rev_history_04']}</a>");
	}
}

## Get production revision stats
// Time since creation
$timeSinceCreation = timeDiff($creation_time,NOW,1,'d');
if ($status > 0 && $production_time != "") 
{
	$timeInDevelopment = timeDiff($creation_time,$production_time,1,'d');
	$timeInProduction = timeDiff($production_time,NOW,1,'d');
	if ($revnum > 1)
	{
		$timeSinceLastRev = timeDiff($revTimes[$revnum-1],NOW,1,'d');
		// Average rev time: Create array of times between revisions
		$revTimeDiffs = array();
		$lasttime = "";
		foreach ($revTimes as $thistime)
		{
			if ($lasttime != "") {
				$revTimeDiffs[] = timeDiff($lasttime,$thistime,1,'d');
			}
			$lasttime = $thistime;
		}
		$avgTimeBetweenRevs = round(array_sum($revTimeDiffs) / count($revTimeDiffs), 1);
		// Median rev time
		rsort($revTimeDiffs);
		$mdnTimeBetweenRevs = $revTimeDiffs[round(count($revTimeDiffs) / 2) - 1]; 
	}
}


## HISTORY TABLE
// Table columns
$col_widths_headers = array(
						array(170, "col1"),
						array(110, "col2", "center"),
						array(170, "col3", "center"),
						array(255, "col4")
					);
// Get html for table
$revTable = renderGrid("prodrevisions", $lang['app_18'], 750, "auto", $col_widths_headers, $revision_info, false, false, false);


## STATS TABLE
// Stats data
$revision_stats   = array();
$revision_stats[] = array($lang['rev_history_07'], "$timeSinceCreation days");
if ($status > 0 && $production_time != "") 
{
	$revision_stats[] = array($lang['rev_history_08'], "$timeInDevelopment days");
	$revision_stats[] = array($lang['rev_history_09'], "$timeInProduction days");
	if ($revnum > 1)
	{
		$revision_stats[] = array($lang['rev_history_10'], "$timeSinceLastRev days");
		$revision_stats[] = array($lang['rev_history_11'], "$avgTimeBetweenRevs days / $mdnTimeBetweenRevs days");
	}
}
// Table columns
$col_widths_headers = array(
						array(220, "col1"),
						array(110, "col2", "center")
					);
// Get html for table
$revStats = renderGrid("revstats", $lang['rev_history_12'], 355, "auto", $col_widths_headers, $revision_stats, false, false, false);











// Render page (except don't show headers in ajax mode)
if (!$isAjax)
{
	include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
	// TABS
	include APP_PATH_DOCROOT . "ProjectSetup/tabs.php";
}
// Instructions
print "<p>{$lang['rev_history_13']}</p>";
// Hide project title in hidden div (for ajax only to use in dialog title)
print "<div id='revHistPrTitle' style='display:none;'>$app_title</div>";
// Revision history table and revision stats table
print "$revTable<br>$revStats";
// Footer
if (!$isAjax) include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
