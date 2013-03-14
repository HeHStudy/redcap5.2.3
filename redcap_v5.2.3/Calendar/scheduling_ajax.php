<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

//Pick up any variables passed by Post
if (isset($_POST['pnid'])) $_GET['pnid'] = $_POST['pnid'];
if (isset($_POST['pid']))  $_GET['pid']  = $_POST['pid'];
if (isset($_POST['arm']))  $_GET['arm']  = $_POST['arm'];


require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

// Santize "record" in query string, if exists
if (isset($_GET['record'])) {
	$_GET['record'] = strip_tags(label_decode($_GET['record']));
}
// Santize "newid" in query string, if exists
if (isset($_GET['newid'])) {
	$_GET['newid'] = ($_GET['newid'] == '1') ? '1' : '0';
}


//Format start date, if needed
if (isset($_GET['startdate'])) {
	if (strpos($_GET['startdate'],"-")) $_GET['startdate'] = format_date($_GET['startdate']);
}

//If action is provided in AJAX request, perform action.
if (isset($_REQUEST['action'])) 
{
	switch ($_REQUEST['action']) 
	{	
		/**
		 * SCHEDULE THIS SUBJECT AND ADD DATES TO CALENDAR
		 */
		case "adddates":
			// Don't actually schedule dates if flag exists in URL
			if (!isset($_GET['display_only'])) {
				//Get group_id (even if null)
				$group_id = $user_rights['group_id'];
				//If a new record is being created, then add the record identifier field to the redcap_data table for FIRST event ONLY (versions previous to 3.4 added it to ALL events)
				if ($_GET['newid']) {
					$firstEvent = getSingleEvent($project_id, (empty($_GET['arm']) ? NULL : $_GET['arm']));
					$sql = "insert into redcap_data values ($project_id, $firstEvent, '".prep($_GET['idnumber'])."', '$table_pk', '".prep($_GET['idnumber'])."')";
					if (db_query($sql)) {
						//Logging
						log_event($sql,"redcap_data","INSERT",$_GET['idnumber'],"$table_pk = '".prep($_GET['idnumber'])."'","Create record");	
					}
					//If user is in DAG, add group_id for the new record also (for ALL events)
					if ($user_rights['group_id'] != "") {
						$sql = "insert into redcap_data values ($project_id, $firstEvent, '".prep($_GET['idnumber'])."', '__GROUPID__', '{$user_rights['group_id']}')";
						db_query($sql);
					}
				//Since this is not a new record, double check to see if record belongs to a DAG. If so, get its group_id number.
				} else {				
					$q = db_query("select value from redcap_data where project_id = $project_id and record = '".prep($_GET['idnumber'])."' and field_name = '__GROUPID__' limit 1");
					if (db_num_rows($q) > 0) {
						$group_id = db_result($q, 0);
					}
				}
				// Make sure the group_id is still a group and not deleted (old bug)
				if ($Proj->getGroups($group_id) === false) {
					$group_id = '';
				}
				
				// Make sure this record/participant has not been scheduled already
				$sub = "select distinct e.arm_id from redcap_events_calendar c, redcap_events_metadata e 
						where c.project_id = $project_id and c.record = '".prep($_GET['idnumber'])."' and c.event_id = e.event_id";
				$sql = "select e.* from redcap_events_calendar c, redcap_events_metadata e 
						where c.project_id = $project_id and c.record = '".prep($_GET['idnumber'])."' and c.event_id = e.event_id 
						and e.arm_id in (" . pre_query($sub) . ") and c.event_id in ({$_POST['event_ids']}) limit 1";
				$already_sched = db_num_rows(db_query($sql));
				if ($already_sched) {
					exit("<br><div><span class='red'><b>{$lang['global_01']}:</b> $table_pk_label \"<b>{$_GET['idnumber']}</b>\" ".($Proj->numArms > 1 ? $lang['scheduling_84'] : $lang['scheduling_28'])."</span></div>");
				}			
				
				//Display confirmation text
				print  "<div class='darkgreen' style='font-family:arial;padding:15px 15px;'>";
				print  "<h3 style='margin:0 0 15px 0;color:green;font-family:verdana;'><img src='".APP_PATH_IMAGES."tick.png'> {$lang['scheduling_29']} \"{$_GET['idnumber']}\"</h3>";
				print  "<p>
							$table_pk_label \"<b>{$_GET['idnumber']}</b>\" {$lang['scheduling_30']} 
							<a href='".APP_PATH_WEBROOT."Calendar/index.php?pid=$project_id' style='text-decoration:underline;'>{$lang['app_08']}</a>. 
							{$lang['scheduling_31']}
						</p>";
				//Loop through values and add to tracking data table
				$dates = explode(",",$_POST['dates']);
				$times = explode(",",$_POST['times']);
				$event_ids = explode(",",$_POST['event_ids']);
				$sql_all = array();
				$sql_errors = array();
				for ($i = 0; $i < count($dates); $i++) {
					//Parse start date into components
					$start_month = substr($dates[$i],0,2);
					$start_day 	 = substr($dates[$i],3,2);
					$start_year	 = substr($dates[$i],6,4);
					//If date is real date, process it
					if ($dates[$i] != "" && checkdate($start_month,$start_day,$start_year)) {
						//Add to table
						$sql = "insert into redcap_events_calendar (record, project_id, group_id, event_id, event_date, event_time, 
								event_status, baseline_date) values ('".prep($_GET['idnumber'])."', $project_id, " . checkNull($group_id) . ", {$event_ids[$i]}, 
								'".format_date_dashes($dates[$i])."', '{$times[$i]}', 0, '".format_date_dashes($_GET['baseline_date'])."')";
						if (db_query($sql)) {
							$sql_all[] = $sql;
						} else {
							$sql_errors[] = $sql;
						}
					} else {
						//Error in date format. Notify user
						print "<div>&nbsp;&nbsp;&nbsp;&bull; <b>{$lang['global_01']}:</b> \"{$dates[$i]}\" {$lang['scheduling_32']}</div>";
					}
				}
				// LOGGING
				log_event(implode(";\n",$sql_all),"redcap_events_calendar","MANAGE",$_GET['idnumber'],"$table_pk = '".prep($_GET['idnumber'])."'","Perform scheduling");
				// Display any SQL errors to super users
				if ($super_user && !empty($sql_errors))
				{
					print "<p class='red'><b>SUPER USER MESSAGE - The following queries failed:</b><br>" . implode(";<br>", $sql_errors) . "</p>";
				}
			}
			
			## TABLE
			// Now display the Agenda for this record that has just been scheduled
			$sql = "select c.cal_id, c.event_date, c.event_time, m.descrip from redcap_events_calendar c, redcap_events_metadata m where c.project_id = $project_id "
				 . "and c.record = '".prep($_GET['idnumber'])."' and c.event_id = m.event_id order by c.event_date";
			$q = db_query($sql);		
			// Render table headers
			print  "<br><table class='form_border' style='color:#000;'>
					<tr>
						<td class='label_header' colspan='3' style='padding:8px 5px 3px 8px;background-color:#d5d5d5;font-size:14px;border:1px solid #bbb;'>
							<div>
								{$lang['scheduling_33']} \"{$_GET['idnumber']}\"
							</div>";
			if (PAGE != "ProjectGeneral/print_page.php") {
				print  "	<div style='text-align:right;font-weight:normal;'>
								<img src='".APP_PATH_IMAGES."pencil.png' class='imgfix'> <a href='".APP_PATH_WEBROOT."Calendar/scheduling.php?pid=$project_id&record={$_GET['idnumber']}&arm=".getArm()."' style='text-decoration:underline;'>{$lang['global_27']}</a> 
								&nbsp;
								<img src='".APP_PATH_IMAGES."printer.png' class='imgfix'> <a href='javascript:;' onclick=\"window.open('".APP_PATH_WEBROOT."ProjectGeneral/print_page.php?pid=$project_id&action=adddates&schedule&display_only&idnumber={$_GET['idnumber']}&arm=".getArm()."','myWin','width=850, height=800, toolbar=0, menubar=1, location=0, status=0, scrollbars=1, resizable=1');\" style='text-decoration:underline;'>{$lang['scheduling_35']}</a>
							</div>";
			}
			print  "		</td>
					<tr>
					<tr>
						<td class='label_header' style='background-color:#eee;padding:8px;'>{$lang['global_13']}</td>
						<td class='label_header' style='background-color:#eee;padding:8px;'>{$lang['global_18']}</td>
						<td class='label_header' style='background-color:#eee;padding:8px;width:250px;'>{$lang['global_10']}</td>
					</tr>";
			// Render table rows
			while ($row = db_fetch_assoc($q)) {//Get day of week for this date
				$start_month = substr($row['event_date'],5,2);
				$start_day 	 = substr($row['event_date'],8,2);
				$start_year	 = substr($row['event_date'],0,4);
				$this_day = date("l", mktime(0, 0, 0, $start_month, $start_day, $start_year));
				print  "<tr>
						<td class='data' style='padding:5px 8px;'>" . format_time($row['event_time']) . "</td>
						<td class='data' style='padding:5px 8px;'>" . format_date($row['event_date']) . "&nbsp; <span style='font-size:10px;'>$this_day</span></td>
						<td class='data' style='padding:5px 8px;'>{$row['descrip']}</td>
						</tr>";
			}
			print  "</table></div>";
			exit;
			break;
		
		
		/**
		 * GENERATE NEW SCHEDULE
		 */		
		case 'generate_sched':
			
			$arm = getArm();

			//Check if record exists in another group, if user is in a DAG
			if ($user_rights['group_id'] != "") {
				//First check if record exists
				$q = db_query("select 1 from redcap_data where project_id = $project_id and record = '".prep($_GET['idnumber'])."' limit 1");
				if (db_num_rows($q) > 0) {
					//Now check if the record is in user's DAG
					$q = db_query("select 1 from redcap_data where project_id = $project_id and record = '".prep($_GET['idnumber'])."' and 
									  field_name = '__GROUPID__' and value = '{$user_rights['group_id']}' limit 1");
					if (db_num_rows($q) < 1) {
						//Record is not in user's DAG
						print  "<div class='red'>
									<img src='".APP_PATH_IMAGES."exclamation.png' class='imgfix'> 
									<b>$table_pk_label ".$_GET['idnumber']." {$lang['scheduling_39']}</b><br><br>
									{$lang['scheduling_40']} $table_pk_label {$lang['scheduling_41']}<br><br>
								</div>";
						exit;
					}
				}
			}
			
			print  "<div class='yellow' style='font-family:arial;padding:10px;'>";
			print  "<div style='font-size:16px;font-weight:bold;margin:0 0 15px 0;font-family:verdana;'>
						{$lang['scheduling_42']} \"{$_GET['idnumber']}\"&nbsp;&nbsp;
						<span style='color:#800000;font-size:11px;font-family:arial;'>({$lang['global_02']}: {$lang['scheduling_43']})</span>
					</div>";

			print  "<p>{$lang['scheduling_44']} <b>$table_pk_label</b> \"<b>{$_GET['idnumber']}</b>\" 
					{$lang['scheduling_45']}";
			//Display the arm name is multiple arms exist (for clarity)
			$num_arms = db_result(db_query("select count(1) from redcap_events_arms where project_id = $project_id"), 0);
			if ($num_arms > 1) {
				print " {$lang['scheduling_46']} <b><i>{$lang['global_08']} $arm</i></b>";
			}
			print  "{$lang['scheduling_48']} <span style='color:red;'>{$lang['scheduling_49']}</span>.
					{$lang['scheduling_50']} <i style='color:#800000;'>{$lang['scheduling_03']}</i> {$lang['scheduling_51']}</p>";

			//Render table headers
			print  "<div style='padding:15px;color:#000;'>";
			print  "<table class='form_border' id='projected_sched'>
					<tr>
						<td class='label_header' style='background-color:#eee;padding:4px;'></td>
						<td class='label_header' style='background-color:#eee;padding:4px;'>
							{$lang['global_13']}<br>
							<span style='color:#777;font-weight:normal;font-size:10px;'>{$lang['global_06']}</span>
						</td>
						<td class='label_header' style='background-color:#eee;width:200px;padding:8px;'>{$lang['scheduling_53']}</td>
						<td class='label_header' style='background-color:#eee;padding:8px;width:200px;'>{$lang['global_10']}</td>
					</tr>";
					
			//Parse start date into components
			$start_month = substr($_GET['startdate'],0,2);
			$start_day 	 = substr($_GET['startdate'],3,2);
			$start_year	 = substr($_GET['startdate'],6,4);

			//Loop through all visits and render
			$q = db_query("select * from redcap_events_metadata m, redcap_events_arms a where a.project_id = $project_id and a.arm_id = m.arm_id
							  and a.arm_num = $arm order by m.day_offset, m.descrip");
			while ($row = db_fetch_assoc($q)) {
				//Set this visit date by adding day_offset to start date
				$this_event_date = date("m/d/Y", mktime(0, 0, 0, $start_month, $start_day + $row['day_offset'], $start_year));
				$this_visit_day  = date("l", 	 mktime(0, 0, 0, $start_month, $start_day + $row['day_offset'], $start_year));
				$this_offset_min = date("m/d/Y", mktime(0, 0, 0, $start_month, $start_day + $row['day_offset'] - $row['offset_min'], $start_year));
				$this_offset_max = date("m/d/Y", mktime(0, 0, 0, $start_month, $start_day + $row['day_offset'] + $row['offset_max'], $start_year));
				//Mark day in red if is on a weekend
				if ($this_visit_day == "Saturday" || $this_visit_day == "Sunday") $this_visit_day = "<span style='color:red;'>$this_visit_day</span>";
				//Display range offset, if exists
				$this_range_text = "";
				if ($row['offset_min'] > 0 || $row['offset_max'] > 0) {
					$this_range_text = "<div class='rangetext' id='rangetext_{$row['event_id']}' style='padding:2px 0 0 10px;color:#777;font-family:tahoma;'>{$lang['scheduling_54']}";
					if ($row['offset_min'] > 0 && $row['offset_max'] > 0) {
						$this_range_text .= ": $this_offset_min - $this_offset_max";
					} elseif ($row['offset_min'] > 0 && $row['offset_max'] == 0) {
						$this_range_text .= " min: $this_offset_min";
					} elseif ($row['offset_min'] == 0 && $row['offset_max'] > 0) {
						$this_range_text .= " max: $this_offset_max";
					}
					$this_range_text .= "</div>";
				}
				//Render row
				print  "<tr id='row_{$row['event_id']}' ev_id='{$row['event_id']}'>
							<td class='data' style='text-align:center;color:#777;padding:0 8px;'>
								<a href='javascript:;' onclick=\"
									if (confirm('{$lang['scheduling_55']}\\n\\n{$lang['scheduling_56']}')) {
										highlightTableRow('row_{$row['event_id']}',700);
										setTimeout(function(){
											$('#row_{$row['event_id']}').css({'display': 'none'});
										},300);
									}
								\"><img src='".APP_PATH_IMAGES."cross.png' class='imgfix2' alt='{$lang['scheduling_57']}' title='{$lang['scheduling_57']}'></a>
							</td>
							<td class='data' style='padding:3px 5px;text-align:center;'>
								<input type='text' id='time_{$row['event_id']}' style='width:35px;' maxlength='5' 
									onblur=\"redcap_validate(this,'','','soft_typed','time')\" class='x-form-text x-form-field time'>
							</td>
							<td class='data' style='padding:3px 5px;font-size:10px;'>&nbsp;
								<input type='text' class='x-form-text x-form-field cal2' id='date_{$row['event_id']}' name='date_{$row['event_id']}' style='width:70px;' 
									maxlength='10' value='$this_event_date' onchange=\"offsetRangeCheck({$row['event_id']},{$row['offset_min']},{$row['offset_max']},'$this_event_date',1)\">
								&nbsp;<span id='weekday_{$row['event_id']}'>$this_visit_day</span>
								$this_range_text
							</td>
							<td class='data' style='padding:3px 8px;'>{$row['descrip']}</td>
						</tr>";
				//Add dates and event_ids to arrays to use when submitting
				$date_list[] = "$('#date_{$row['event_id']}').val()";
				$event_id_list[] = $row['event_id'];
			}

			print  "</table>";

			//Submit and Cancel buttons
			print  "<p>";
			print  "<input type='button' value='Create Schedule' id='createbtn' onclick=\"createSched('".cleanHtml($_GET['idnumber'])."',{$_GET['newid']});\">&nbsp;
					<input type='button' id='cancelbtn' value='Cancel' onclick=\"$('#table').html('');\">
					&nbsp;&nbsp;
					<span id='progress2' style='visibility:hidden;color:#555;'>
						<img src='".APP_PATH_IMAGES."progress_small.gif' class='imgfix'> 
						{$lang['scheduling_58']}
					</span>";
			//If user is adding a new record, give notice of this
			if ($_GET['newid']) {
				print  "<div style='font-size:11px;padding-top:4px;color:#555;'>
						{$lang['global_02']}: {$lang['scheduling_59']} <i style='color:#800000;'>{$lang['scheduling_03']}</i> {$lang['scheduling_60']} \"<b>{$_GET['idnumber']}</b>\" 
						{$lang['scheduling_61']} $table_pk_label.
						</div>";
			}
			print  "</p>";
			print  "</div>";
			print  "</div>";
			exit;
			break;	
		
		
		/**
		 * EDIT A SINGLE EXISTING CALENDAR EVENT
		 */		
		case 'edit_single':
			// Process this single calendar event
			$sql1 = "update redcap_events_calendar set event_date = '".format_date_dashes($_GET['event_date'])."', event_time = '{$_GET['event_time']}',
					 event_status = {$_GET['event_status']}, notes = '".prep($_GET['notes'])."' where cal_id = {$_GET['cal_id']}";
			db_query($sql1);
			//LOGGING for single
			log_event($sql1,"redcap_events_calendar","MANAGE",$_GET['cal_id'],calLogChange($_GET['cal_id']),"Edit calendar event");	
			
			// If other dates have been adjusted, modify them here (excludes unscheduled events)
			if ($_GET['other_rows'] != "") {
				$cal_ids = trim(substr($_GET['other_rows'], 1));
				$sql2 = "update redcap_events_calendar set event_date = date_add(event_date, INTERVAL {$_GET['daydiff']} DAY) 
						 where cal_id in ($cal_ids) and event_status is not NULL";
				db_query($sql2);
				//LOGGING for multiple
				log_event("$sql1;\n$sql2","redcap_events_calendar","MANAGE","{$_GET['cal_id']},$cal_ids","cal_id = {$_GET['cal_id']}, $cal_ids","Update multiple calendar events");	
			}		
			// Reset cal_id to display table normally
			unset($_GET['cal_id']);
			break;	
		
		
		/**
		 * DELETE A SINGLE EXISTING CALENDAR EVENT
		 */		
		case 'del_single':
			//Delete calendar event
			$sql = "delete from redcap_events_calendar where cal_id = {$_GET['cal_id']}";
			//LOGGING
			log_event($sql,"redcap_events_calendar","MANAGE",$_GET['cal_id'],calLogChange($_GET['cal_id']),"Delete calendar event");
			//Run query after logging because values will be deleted
			db_query($sql);			
			// Reset cal_id to display table normally
			unset($_GET['cal_id']);
			break;
			
	}	
}



/**
 * EDIT EXISTING SCHEDULE
 */	
		
$arm = getArm();

print  "<div class='blue' style='max-width:750px;font-family:arial;padding:10px;'>";
print  "<h3 id='view_edit_title' style='margin:0 0 15px 0;font-family:verdana;'>{$lang['scheduling_62']} \"".RCView::escape($_GET['record'])."\"</h3>";
print  "<p id='view_edit_instr' style='line-height:1.4em;'>
			{$lang['scheduling_63']} <b>$table_pk_label</b> \"<b>".RCView::escape($_GET['record'])."</b>\".
			{$lang['scheduling_64']} <span style='color:red;'>{$lang['scheduling_49']}</span>.
			{$lang['scheduling_65']} <img src='".APP_PATH_IMAGES."pencil.png' style='vertical-align:middle;'> 
			{$lang['scheduling_66']} <img src='".APP_PATH_IMAGES."cross.png' style='vertical-align:middle;'> 
			{$lang['scheduling_67']} <img src='".APP_PATH_IMAGES."magnifier.png' style='vertical-align:middle;'> 
			{$lang['scheduling_68']}
		</p>";

//Render table headers
print  "<div style='padding:15px 10px 0 5px;color:#000000;'>";
print  "<table class='form_border' id='edit_sched_table'>
		<tr>
			<td class='label_header' style='background-color:#eee;padding:8px;width:55px;' id='sched_frow'></td>
			<td class='label_header' style='background-color:#eee;padding:8px;'>{$lang['global_13']}</td>
			<td class='label_header' style='background-color:#eee;width:145px;padding:8px;'>{$lang['scheduling_53']}</td>
			<td class='label_header' style='background-color:#eee;padding:8px;width:100px;'>{$lang['global_10']}</td>
			<td class='label_header' style='background-color:#eee;padding:8px;width:90px;'>{$lang['scheduling_69']}</td>
			<td class='label_header' style='background-color:#eee;padding:8px;'>{$lang['scheduling_70']}</td>
		</tr>";


//Loop through all visits and render
$sql = "select * from redcap_events_calendar c left outer join redcap_events_metadata e on e.event_id = c.event_id 
		where c.project_id = $project_id and c.record = '".prep($_GET['record'])."' and (c.event_id is null or c.event_id in 
		(select m.event_id from redcap_events_metadata m, redcap_events_arms a where a.project_id = $project_id and 
		a.arm_id = m.arm_id and a.arm_num = $arm)) order by c.event_date, c.event_time, c.event_status";
$q = db_query($sql);
while ($row = db_fetch_assoc($q)) {	
		
	// Set up variables (date, day of week, etc.)
	list($this_year, $this_month, $this_day) = explode("-", $row['event_date']);
	$this_event_date = format_date($row['event_date']);
	$this_mktime = mktime(0, 0, 0, $this_month, $this_day, $this_year);
	$this_event_day = date("l", $this_mktime);
	$this_event_day_text = ($this_event_day == "Saturday" || $this_event_day == "Sunday") ? "<span style='color:red;'>$this_event_day</span>" : $this_event_day;
	$this_range_text = "";
	
	// Event status
	switch ($row['event_status']) {
		case '0': 
			$status    = "#222";	
			$statusimg = "star_small_empty.png";
			$statustext = $lang['scheduling_71'];
			break;
		case '1': 
			$status    = "#222";	
			$statusimg = "star_small_empty.png";
			$statustext = $lang['scheduling_72'];
			break;
		case '2': 
			$status    = "#a86700";	
			$statusimg = "star_small.png";
			$statustext = $lang['scheduling_73'];
			break;
		case '3': 
			$status    = "red";	
			$statusimg = "cross_small.png";
			$statustext = $lang['scheduling_74'];
			break;
		case '4': 
			$status    = "#800000";	
			$statusimg = "bullet_delete16.png";
			$statustext = $lang['scheduling_75'];
			break;
		default:
			$status    = "";	
			$statusimg = "spacer.gif";
			$statustext = "";
	}
	// If an ad hoc event (rather than scheduled event), display "Ad Hoc"
	if ($row['descrip'] == "") $row['descrip'] = "<span style='color:#999;'>{$lang['scheduling_76']}</span>";
	
	// Scheduled events
	if ($row['event_id'] != "") {
		// Parse baseline date into components to get min/max offset dates
		list($start_year, $start_month, $start_day) = explode("-", $row['baseline_date']);
		$this_target_date = date("m/d/Y", mktime(0, 0, 0, $start_month, $start_day + $row['day_offset'], $start_year));
		$this_offset_min_mktime = mktime(0, 0, 0, $start_month, $start_day + $row['day_offset'] - $row['offset_min'], $start_year);
		$this_offset_min = date("m/d/Y", $this_offset_min_mktime);
		$this_offset_max_mktime = mktime(0, 0, 0, $start_month, $start_day + $row['day_offset'] + $row['offset_max'], $start_year);
		$this_offset_max = date("m/d/Y", $this_offset_max_mktime);
		// Determine if date is out of range and flag as bold red
		$range_style = "color:#888;"; //default
		if (($row['offset_min'] > 0 && $this_mktime < $this_offset_min_mktime) || ($row['offset_max'] > 0 && $this_mktime > $this_offset_max_mktime)) {
			$range_style = "color:red;font-weight:bold;";
		}
		// Display range offset, if exists
		$this_range_text = "";
		if ($row['offset_min'] > 0 || $row['offset_max'] > 0) {
			$this_range_text = "<div class='rangetext' id='rangetext_{$row['cal_id']}' style='padding:2px 2px 1px 2px;font-size:10px;font-family:tahoma;$range_style'>{$lang['scheduling_54']}";
			if ($row['offset_min'] > 0 && $row['offset_max'] > 0) {
				$this_range_text .= ": $this_offset_min - $this_offset_max";
			} elseif ($row['offset_min'] > 0 && $row['offset_max'] == 0) {
				$this_range_text .= " min: $this_offset_min";
			} elseif ($row['offset_min'] == 0 && $row['offset_max'] > 0) {
				$this_range_text .= " max: $this_offset_max";
			}
			$this_range_text .= "</div>";
		}
	// Ad hoc events (have null values so provide some to prevent javascript events upon date change)
	} else {
		$row['offset_min'] = 0;
		$row['offset_max'] = 0;
		$this_target_date = "";
	}
	
	## Render table rows
	// If this row was selected for editing, show input fields and Save button
	if (isset($_GET['cal_id']) && $_GET['cal_id'] == $row['cal_id']) {
		print  "<tr id='row_{$row['cal_id']}' evstat='{$row['event_status']}'>
					<td class='data' style='text-align:center;color:#777;' id='sched_frow'>
						<input type='button' id='btn_{$row['cal_id']}' value='Save' style='font-size:11px;' onclick=\"saveEditCalEv({$row['cal_id']},'{$_GET['record']}',$arm)\">
					</td>
					<td class='data' style='padding:0px 4px;font-size:10px;'>
						<input type='text' id='time_{$row['cal_id']}' style='width:35px;' maxlength='5' value='{$row['event_time']}' 
							onblur=\"redcap_validate(this,'','','soft_typed','time')\" class='x-form-text x-form-field time'>
					</td>
					<td class='data' style='padding:0 0 0 4px;font-size:10px;'>
						<input type='text' id='date_{$row['cal_id']}' class='x-form-text x-form-field cal2' style='width:65px;' maxlength='10' 
							value='$this_event_date' onchange=\"offsetRangeCheck({$row['cal_id']},{$row['offset_min']},{$row['offset_max']},'$this_target_date',1)\"> 
						&nbsp;<span id='weekday_{$row['cal_id']}'>$this_event_day_text</span>
						$this_range_text
						<input type='hidden' id='origdate_{$row['cal_id']}' value='$this_event_date'>
					</td>
					<td class='data' style='padding:0px 8px;'>{$row['descrip']}</td>
					<td class='data' style='padding:0 0 0 4px;'>";
		if ($row['event_status'] == '' ) {
			// If ad hoc event, make hidden field
			print  "	<input type='hidden' id='status_{$row['cal_id']}' value='NULL'>";
		} else {
			// Drop-down of status options
			print  "	<select id='status_{$row['cal_id']}' class='x-form-text x-form-field' style='padding-right:0;height:22px;font-size:11px;'>
							<option value='0' "; 	print ($row['event_status'] == '0') ? "selected" : ""; print ">Due Date</option>
							<option value='1' "; 	print ($row['event_status'] == '1') ? "selected" : ""; print ">Scheduled</option>
							<option value='2' "; 	print ($row['event_status'] == '2') ? "selected" : ""; print ">Confirmed</option>
							<option value='3' "; 	print ($row['event_status'] == '3') ? "selected" : ""; print ">Cancelled</option>
							<option value='4' "; 	print ($row['event_status'] == '4') ? "selected" : ""; print ">No Show</option>
						</select>";
		}				
		print  "	</td>
					<td class='data' style='padding:3px 8px;width:200px;'>
						<textarea class='x-form-textarea x-form-field' id='notes_{$row['cal_id']}' style='height:60px;width:97%;font-size:11px;'>{$row['notes']}</textarea>
						<div id='expand_{$row['cal_id']}' style='text-align:right;'>
							<a href='javascript:;' style='color:#999;font-family:tahoma;font-size:10px;' 
								onclick=\"$('#notes_{$row['cal_id']}').autogrow();\">&#8595; {$lang['scheduling_77']}</a>&nbsp;
						</div>
					</td>
				</tr>";
	// Regular row to display
	} else {
		// Use for line break replaces
		$orig = array("\r\n", "\n", "\r");
		$repl = array("<br>", "<br>", "<br>");
		$notes_vis = str_replace($orig, $repl, $row['notes']);
		$notes_invis = "";
		if (strlen($notes_vis) > 70) {
			$notes_invis = "<span id='notes_ellip_{$row['cal_id']}'>...<br>
								<center>
									<a href='javascript:;' style='font-size:10px;text-decoration:underline;' 
										onclick='showEvNote({$row['cal_id']})'>{$lang['scheduling_78']}</a>
								</center>
							</span>
							<span id='notes_invis_{$row['cal_id']}' style='display:none;'>" . substr($notes_vis, 70) . "</span>";
			$notes_vis = "<span id='notes_{$row['cal_id']}'>" . substr($notes_vis, 0, 70) . "</span>";;
		}
		// Calendar popup width
		$calwidth = ($row['event_status'] == "") ? 600 : 800;
		//Render row
		print  "<tr id='row_{$row['cal_id']}' evstat='{$row['event_status']}'>
					<td class='data' style='text-align:center;color:#777;' id='sched_frow'>
						<a href='javascript:;' onclick=\"beginEditCalEv({$row['cal_id']},'{$_GET['record']}',$arm)\"><img src='".APP_PATH_IMAGES."pencil.png' 
							class='imgfix2' title='{$lang['global_27']}' alt='{$lang['global_27']}'></a>
						<a href='javascript:;' onclick=\"delCalEv({$row['cal_id']},'{$_GET['record']}',$arm)\"><img src='".APP_PATH_IMAGES."cross.png' 
							class='imgfix2' title='{$lang['global_19']}' alt='{$lang['global_19']}'></a>
						<a href='javascript:;' onclick=\"popupCal({$row['cal_id']},$calwidth)\"><img src='".APP_PATH_IMAGES."magnifier.png' 
							class='imgfix' title='{$lang['scheduling_80']}' alt='{$lang['scheduling_80']}'></a>
					</td>
					<td class='data' style='padding:0px 4px;font-size:10px;'>".format_time($row['event_time'])."</td>
					<td class='data' style='padding:0 0 0 8px;'>
						$this_event_date &nbsp;<span style='font-size:10px;'>$this_event_day_text</span>
						$this_range_text
					</td>
					<td class='data notranslate' style='padding:3px 8px;'>{$row['descrip']}</td>
					<td class='data' style='padding:0px 2px 3px 2px;color:$status;'>
						<img src='".APP_PATH_IMAGES."$statusimg' class='imgfix'>$statustext
					</td>
					<td class='data' style='padding:3px 8px;width:200px;font-size:9px;'>
						$notes_vis{$notes_invis}
					</td>
				</tr>";
	}
}
print  "</table>";

// Option to add new unscheduled event
print  "<div id='new_ad_hoc'>
			<div style='color:green;font-size:12px;padding:15px 0 0;'>
				<img src='".APP_PATH_IMAGES."add.png' class='imgfix'>
				{$lang['scheduling_81']}
				&nbsp;<input type='text' id='newCalEv' class='x-form-text x-form-field cal2' style='width:70px;' maxlength='10' value='".date("m/d/Y")."'>
				<input type='button' id='btn_newCalEv' value=' ".cleanHtml($lang['design_171'])." ' class='imgfix' onclick=\"
					var date_arr = document.getElementById('newCalEv').value.split('/');
					popupCalNew(date_arr[1],(date_arr[0]*1+1),date_arr[2],'".cleanHtml(str_replace(array("'","\""),array("","&quot;"),$_GET['record']))."');			
				\">
			</div>
			<div style='padding:0 0 15px;'>
				<img src='".APP_PATH_IMAGES."printer.png' class='imgfix'> 
				<a href='javascript:;' onclick=\"window.open('".APP_PATH_WEBROOT."ProjectGeneral/print_page.php?pid=$project_id&action=edit_sched&schedule&record=".urlencode($_GET['record'])."&arm=".getArm()."','myWin','width=850, height=800, toolbar=0, menubar=1, location=0, status=0, scrollbars=1, resizable=1');\" style='text-decoration:underline;'>{$lang['scheduling_82']}</a>
			</div>
		</div>";

print  "</div>";
print  "</div>";

