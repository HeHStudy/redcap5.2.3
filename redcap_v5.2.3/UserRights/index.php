<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';


//Required files
require_once(APP_PATH_CLASSES . "Message.php");


// Setup variables
$context_msg = "";


include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

// TABS
include APP_PATH_DOCROOT . "ProjectSetup/tabs.php";

## Add/Edit/Delete User
if (isset($_POST['submit'])) 
{
	$user = trim($_POST['user']);

	/// Set context_msg
	if ($project_language == 'English') {
		// ENGLISH
		$context_msg_update = "<div class='darkgreen'><img src='".APP_PATH_IMAGES."tick.png' class='imgfix'> ".ucwords($lang['global_17'])." \"<b class='notranslate'>$user</b>\" {$lang['rights_05']}</div>";
		$context_msg_insert = "<div class='darkgreen'><img src='".APP_PATH_IMAGES."tick.png' class='imgfix'> ".ucwords($lang['global_17'])." \"<b class='notranslate'>$user</b>\" {$lang['rights_06']}</div>";
		$context_msg_delete = "<div class='red'><img src='".APP_PATH_IMAGES."exclamation.png' class='imgfix'> ".ucwords($lang['global_17'])." \"<b class='notranslate'>$user</b>\" {$lang['rights_07']}</div>";
	} else {
		// NON-ENGLISH
		$context_msg_update = "<div class='darkgreen'><img src='".APP_PATH_IMAGES."tick.png' class='imgfix'> ".$lang['global_17']." \"<b class='notranslate'>$user</b>\" {$lang['rights_05']}</div>";
		$context_msg_insert = "<div class='darkgreen'><img src='".APP_PATH_IMAGES."tick.png' class='imgfix'> ".$lang['global_17']." \"<b class='notranslate'>$user</b>\" {$lang['rights_06']}</div>";
		$context_msg_delete = "<div class='red'><img src='".APP_PATH_IMAGES."exclamation.png' class='imgfix'> ".$lang['global_17']." \"<b class='notranslate'>$user</b>\" {$lang['rights_07']}</div>";
	}
	
	//Switch all checkboxes from 'on' to '1'
	foreach ($_POST as $key => $value) {
		if ($value == 'on') $_POST[$key] = 1;	
	}
	//Fix values for unchecked check boxes
	if ($_POST['data_export_tool'] == '') 		$_POST['data_export_tool'] = 0;
	if ($_POST['data_import_tool'] == '') 		$_POST['data_import_tool'] = 0;
	if ($_POST['data_comparison_tool'] == '') 	$_POST['data_comparison_tool'] = 0;
	if ($_POST['data_logging'] == '') 			$_POST['data_logging'] = 0;
	if ($_POST['file_repository'] == '') 		$_POST['file_repository'] = 0;
	if ($_POST['double_data'] == '') 			$_POST['double_data'] = 0;
	if ($_POST['user_rights'] == '') 			$_POST['user_rights'] = 0;
	if ($_POST['data_access_groups'] == '') 	$_POST['data_access_groups'] = 0;
	if ($_POST['lock_record'] == '') 			$_POST['lock_record'] = 0;
	if ($_POST['lock_record_multiform'] == '') 	$_POST['lock_record_multiform'] = 0;
	if ($_POST['lock_record_customize'] == '') 	$_POST['lock_record_customize'] = 0;
	if ($_POST['design'] == '') 				$_POST['design'] = 0;
	if ($_POST['graphical'] == '') 				$_POST['graphical'] = 0;
	if ($_POST['reports'] == '') 				$_POST['reports'] = 0;
	if ($_POST['calendar'] == '') 				$_POST['calendar'] = 0;
	if ($_POST['record_create'] == '') 			$_POST['record_create'] = 0;
	if ($_POST['record_rename'] == '') 			$_POST['record_rename'] = 0;
	if ($_POST['record_delete'] == '') 			$_POST['record_delete'] = 0;
	if ($_POST['participants'] == '') 			$_POST['participants'] = 0;
	if ($_POST['data_quality_design'] == '') 	$_POST['data_quality_design'] = 0;
	if ($_POST['data_quality_execute'] == '') 	$_POST['data_quality_execute'] = 0;
	if ($_POST['api_export'] == '') $_POST['api_export'] = 0;
	if ($_POST['api_import'] == '') $_POST['api_import'] = 0;
	if ($_POST['expiration'] == '') 			$_POST['expiration'] = 'NULL'; else $_POST['expiration'] = "'".$_POST['expiration']."'";
	if (!isset($_POST['dts']) || (isset($_POST['dts']) && $_POST['dts'] == ''))	$_POST['dts'] = 0;
	if ($_POST['random_setup'] == '') 			$_POST['random_setup'] = 0;
	if ($_POST['random_dashboard'] == '') 		$_POST['random_dashboard'] = 0;
	if ($_POST['random_perform'] == '') 		$_POST['random_perform'] = 0;
	
	//print "<pre>";print_r($_POST);print "</pre>";
	
	// Delete user
	if ($_POST['submit'] == " Delete User ") {	
		
		// Delete user from project rights table
		$sql = "DELETE FROM redcap_user_rights WHERE project_id = $project_id and username = '$user'";
		if (db_query($sql)) 
		{
			// Also delete from project bookmarks users table as well
			$sql2 = "DELETE FROM redcap_external_links_users WHERE username = '$user' and ext_id in 
					(" . implode(",", array_keys($ExtRes->getResources())) . ")";
			db_query($sql2);
			// Set context message
			$context_msg = $context_msg_delete;
			// Logging
			log_event($sql,"redcap_user_rights","delete",$user,"user = '$user'","Delete user");
		}
		
	//Edit existing user
	} elseif ($_POST['submit'] == "Save Changes") {	
	
		//Update project rights table
		$set_values =  "data_export_tool = '{$_POST['data_export_tool']}', data_import_tool = '{$_POST['data_import_tool']}', 
						data_comparison_tool = '{$_POST['data_comparison_tool']}', data_logging = '{$_POST['data_logging']}', 
						file_repository = '{$_POST['file_repository']}', double_data = '{$_POST['double_data']}', 
						user_rights = '{$_POST['user_rights']}', data_access_groups = '{$_POST['data_access_groups']}', 
						lock_record = '{$_POST['lock_record']}', lock_record_multiform = '{$_POST['lock_record_multiform']}', 
						lock_record_customize = '{$_POST['lock_record_customize']}', design = '{$_POST['design']}', 
						expiration = {$_POST['expiration']} , record_create = '{$_POST['record_create']}', 
						record_rename = '{$_POST['record_rename']}', record_delete = '{$_POST['record_delete']}', 
						graphical = '{$_POST['graphical']}', calendar = '{$_POST['calendar']}', reports = '{$_POST['reports']}', 
						dts = '{$_POST['dts']}', participants = '{$_POST['participants']}', 
						data_quality_design = '{$_POST['data_quality_design']}', data_quality_execute = '{$_POST['data_quality_execute']}',
						api_export = '{$_POST['api_export']}', api_import = '{$_POST['api_import']}',
						random_setup = '{$_POST['random_setup']}', random_dashboard = '{$_POST['random_dashboard']}', 
						random_perform = '{$_POST['random_perform']}', 
						data_entry = '";
		foreach (array_keys($Proj->forms) as $form_name)
		{
			// Process each form's radio button value
			$this_field = "form-" . $form_name;
			$this_value = ($_POST[$this_field] == '') ? 0 : $_POST[$this_field];
			// If set survey responses to be editable, then set to value 3
			$editresp_chkbox_name = "form-editresp-" . $form_name;
			if ($this_value == '1' && isset($_POST[$editresp_chkbox_name]) && $_POST[$editresp_chkbox_name])
			{
				$this_value = 3;
			}
			// Set value for this form
			$set_values .= "[$form_name,$this_value]";
		}
		$set_values .= "'";
		$sql = "UPDATE redcap_user_rights SET $set_values WHERE username = '$user' and project_id = $project_id";
		if (db_query($sql)) {
			//Set context message
			$context_msg = $context_msg_update;
			//Logging
			log_event($sql,"redcap_user_rights","update",$user,"user = '$user'","Edit user");
		}
		
	
	//Add new user
	} elseif ($_POST['submit'] == " Add User ") {
		
		//Insert user into user rights table
		$fields = "project_id, username, data_export_tool, data_import_tool, data_comparison_tool, data_logging, file_repository, double_data, " . 
				  "user_rights, design, expiration, lock_record, lock_record_multiform, lock_record_customize, data_access_groups, graphical, reports, calendar, " . 
				  "record_create, record_rename, record_delete, dts, participants, data_quality_design, data_quality_execute, api_export, api_import, 
				  random_setup, random_dashboard, random_perform, 
				  data_entry";
		$values =  "$project_id, '$user', '{$_POST['data_export_tool']}', '{$_POST['data_import_tool']}', '{$_POST['data_comparison_tool']}', 
					'{$_POST['data_logging']}', '{$_POST['file_repository']}', '{$_POST['double_data']}', '{$_POST['user_rights']}', 
					'{$_POST['design']}', {$_POST['expiration']}, '{$_POST['lock_record']}', '{$_POST['lock_record_multiform']}', 
					'{$_POST['lock_record_customize']}', '{$_POST['data_access_groups']}', '{$_POST['graphical']}', '{$_POST['reports']}', 
					'{$_POST['calendar']}', '{$_POST['record_create']}', '{$_POST['record_rename']}', '{$_POST['record_delete']}', 
					'{$_POST['dts']}', '{$_POST['participants']}', '{$_POST['data_quality_design']}', '{$_POST['data_quality_execute']}',
					'{$_POST['api_export']}', '{$_POST['api_import']}', '{$_POST['random_setup']}', '{$_POST['random_dashboard']}', 
					'{$_POST['random_perform']}', '";
		foreach (array_keys($Proj->forms) as $form_name)
		{
			// Process each form's radio button value
			$this_field = "form-" . $form_name;
			$this_value = ($_POST[$this_field] == '') ? 0 : $_POST[$this_field];
			// If set survey responses to be editable, then set to value 3
			$editresp_chkbox_name = "form-editresp-" . $form_name;
			if ($this_value == '1' && isset($_POST[$editresp_chkbox_name]) && $_POST[$editresp_chkbox_name])
			{
				$this_value = 3;
			}
			$values .= "[$form_name,$this_value]";
		}
		$values .= "'";
		// Insert user into user_rights table
		$sql = "INSERT INTO redcap_user_rights ($fields) VALUES ($values)";
		if (db_query($sql)) {			
			// Set context message
			$context_msg = $context_msg_insert;
			// Logging
			log_event($sql,"redcap_user_rights","insert",$user,"user = '$user'","Add user");	
		}
	
	}
	
	//If checkbox was checked to notify new user of their access, send an email (but don't send if one has just been sent)
	if (isset($_POST['notify_email']) && $_POST['notify_email']) 
	{
		$email = new Message ();
		$emailContents = "
			<html><body style='font-family:Arial;font-size:10pt;'>
			{$lang['global_21']}<br /><br />
			{$lang['rights_88']} \"".strip_tags(str_replace("<br>", " ", label_decode($app_title)))."\"{$lang['period']}
			{$lang['rights_89']} \"$user\", {$lang['rights_90']}<br /><br />
			".APP_PATH_WEBROOT_FULL."
			</body>
			</html>";
		//First need to get the email address of the user we're emailing
		$q = db_query("select user_firstname, user_lastname, user_email from redcap_user_information where username = '$user'");
		$row = db_fetch_array($q);
		$email->setTo($row['user_email']);		
		$email->setFrom($user_email);
		$email->setSubject($lang['rights_122']);
		$email->setBody($emailContents);
		if (!$email->send()) {	
			print  "<br><div style='font-family:Arial;font-size:12px;background-color:#F5F5F5;border:1px solid #C0C0C0;padding:10px;'>
					<div style='font-weight:bold;border-bottom:1px solid #aaaaaa;color:#800000;'>
					<img src='".APP_PATH_IMAGES."exclamation.png' style='position:relative;top:3px;'> 
					{$lang['rights_80']}
					</div><br>
					{$lang['global_37']} <span style='color:#666;'>$user_firstname $user_lastname &#60;$user_email&#62;</span><br>
					{$lang['global_38']} <span style='color:#666;'>".$row['user_firstname']." ".$row['user_lastname']." &#60;".$row['user_email']."&#62;</span><br>
					{$lang['rights_83']} <span style='color:#666;'>{$lang['rights_91']}</span><br><br>
					$emailContents<br>
					</div><br>";
		}
	}
	
}


## DISPLAY USER LIST OR ENTER NEW USER NAME
if (!isset($_GET['id'])) 
{	
	//User Rights
	print "<p>{$lang['rights_30']}</p>";	
	
	//If user is in DAG, only show info from that DAG and give note of that
	if ($user_rights['group_id'] != "") {
		print  "<p style='color:#800000;padding-bottom:10px;'>{$lang['global_02']}: {$lang['rights_92']}</p>";

	}	   
	
	print  "<table class='form_border'>";
	if ($context_msg != "")	{
		print  "<tr>
					<td class='context_msg' colspan='2'>$context_msg</td>
				</tr>";
	}
	print  "<tr>
				<td class='label'>{$lang['rights_27']} &nbsp;&nbsp;</td><td class='data'>
					<select class='x-form-text x-form-field notranslate' style='padding-right:0;height:22px;' onchange='window.location.href=\"".PAGE_FULL."?pid=$project_id&id=\"+this.value+addGoogTrans();'>
					<option value=''>{$lang['rights_133']}</option>";
					
	##Display users in dropdown	
	//If user is in a Data Access Group, only allow user to edit rights of someone in that group  
	if ($user_rights['group_id'] == "") {
		$group_sql = "";
	} else {
		$group_sql = "and group_id = '".$user_rights['group_id']."'";
	}
	//Query user list
	$q = db_query("select username from redcap_user_rights where project_id = $project_id $group_sql order by username");
	while ($row = db_fetch_array($q)) {
		print "		<option value='".$row['username']."'>".$row['username']."</option>";
	}
	
	print  "		</select></td>
			</tr>";
	//If user is in a Data Access Group, do not allow them to add a new user
	if ($user_rights['group_id'] == "") {		
		print  "<tr>
					<td class='header' colspan='2'>{$lang['rights_25']}</td>
				</tr>
				<tr>
					<td class='label'>{$lang['rights_28']}</td>
					<td class='data'><input type='text' maxlength='255' onchange=\"if(this.value.length >0) { if(!chk_username(this)) return alertbad(this,'".cleanHtml($lang['rights_35'])."');} window.location.href='".PAGE_FULL."?pid=$project_id&id='+this.value;\"></td>
				</tr>";
	}
	print  "</table>";
	

	/**
	 * COMPREHENSIVE USER RIGHTS VIEW AS GRID (ALL RIGHTS FOR ALL USERS)
	 */
	// Check if DAGs exist and show them
	$dags = $Proj->getGroups();
	// display table grid
	echo '<br/><br/>
		<div id="userRightsGrid" style="padding:20px 15px 20px 0 ;">		
		<h3 style="color:#800000;">
			<img src="'.APP_PATH_IMAGES.'group.png"> '.$lang['rights_93'].'
		</h3>
		<table class="form_border" style="width:100%;font-size:9px;">
		';
	$rightsSetup = array(
			'username' => array('hdr' => $lang['global_11'], 'enabled' => true),
			'expiration' => array('hdr' => $lang['rights_95'], 'enabled' => true),
			'group_id' => array('hdr' => $lang['global_78'], 'enabled' => !empty($dags)),
			'participants' => array('hdr' => $lang['app_22'], 'enabled' => !empty($Proj->surveys)),
			'calendar' => array('hdr' => $lang['app_08'], 'enabled' => true),
			'data_export_tool' => array('hdr' => $lang['app_03'], 'enabled' => true),
			'data_import_tool' => array('hdr' => $lang['app_01'], 'enabled' => true),
			'data_comparison_tool' => array('hdr' => $lang['app_02'], 'enabled' => true),
			'data_logging' => array('hdr' => $lang['app_07'], 'enabled' => true),
			'file_repository' => array('hdr' => $lang['app_04'], 'enabled' => true),
			'double_data' => array('hdr' => $lang['rights_50'], 'enabled' => $double_data_entry),
			'user_rights' => array('hdr' => $lang['app_05'], 'enabled' => true),
			'data_access_groups' => array('hdr' => $lang['global_22'], 'enabled' => true),
			'graphical' => array('hdr' => $lang['app_13'], 'enabled' => $enable_plotting > 0),
			'data_quality_design' => array('hdr' => $lang['dataqueries_38'], 'enabled' => true),
			'data_quality_execute' => array('hdr' => $lang['dataqueries_39'], 'enabled' => true),
			'reports' => array('hdr' => $lang['rights_96'], 'enabled' => true),
			'lock_record_customize' => array('hdr' => $lang['app_11'], 'enabled' => true),
			'lock_record' => array('hdr' => $lang['rights_97'], 'enabled' => true),
			'design' => array('hdr' => $lang['rights_135'], 'enabled' => true),
			'dts' => array('hdr' => $lang['rights_132'], 'enabled' => $dts_enabled_global && $dts_enabled),
			'record_create' => array('hdr' => $lang['rights_99'], 'enabled' => true),
			'record_rename' => array('hdr' => $lang['rights_100'], 'enabled' => true),
			'record_delete' => array('hdr' => $lang['rights_101'], 'enabled' => true),
			'api' => array('hdr' => $lang['setup_77'], 'enabled' => $api_enabled),
			'randomization' => array('hdr' => $lang['app_21'], 'enabled' => $randomization)
	);
	$hdr = '';
	foreach ($rightsSetup as $r) {
		if ($r['enabled'])
			$hdr .= RCView::td(array('class' => 'label', 'style' => 'background-color:#eee;font-size:9px;text-align:center;'), $r['hdr']);
	}
	echo RCView::tr(array(), $hdr);
	$imgYes = RCView::img(array('src' => 'tick.png'));
	$imgNo = RCView::img(array('src' => 'cross.png'));
	$imgShield = RCView::img(array('src' => 'tick_shield.png'));
	
	//If user is in a Data Access Group, only allow user to edit rights of someone in that group  
	if ($user_rights['group_id'] == "") {
		$group_sql = "";
	} else {
		$group_sql = "and group_id = '".$user_rights['group_id']."'";
	}	
	$query = "SELECT * FROM redcap_user_rights WHERE project_id = $project_id $group_sql ORDER BY username";
	$dsRights = db_query($query);
	while ($row = db_fetch_assoc($dsRights))
	{
		$cells = '';
		foreach ($rightsSetup as $rightsKey => $r) 
		{
			if (!$r['enabled']) continue;
			$cellContent = '';
			$cellAttrs = array('class' => 'data', 'style' => 'text-align:center;font-size:9px;');
			if ($rightsKey == 'username') {
				$cellAttrs['class'] = 'data notranslate';
				$cellAttrs['style'] = 'font-size:9px;font-weight:bold;color:#800000;padding:2px 5px;cursor:pointer;cursor:hand;';
				// JS for the click event is handled at the bottom of the table
				$cellAttrs['id'] = 'rightsTableUserLinkId_' . $row['username'];
				$cellContent = $row['username'];
			}
			elseif ($rightsKey == 'expiration') {
				$cellContent = ($row['expiration'] == "") ? '<span style="color:gray;">'.$lang['rights_102'].'</span>' : format_date($row['expiration']);
			}
			elseif ($rightsKey == 'group_id') {
				$cellContent = $dags[$row['group_id']];
			}
			elseif ($rightsKey == 'data_export_tool') {
				if ($row[$rightsKey] == "0") $cellContent = $imgNo;
				elseif ($row[$rightsKey] == "1") $cellContent = $lang['rights_49'];
				else $cellContent = $lang['rights_48'];
			}
			elseif ($rightsKey == 'double_data') {
				$cellContent = ($row[$rightsKey] > 0) ? 'DDE Person #'.$row[$rightsKey] : 'Reviewer';
			}
			elseif ($rightsKey == 'lock_record_customize') {
				$cellContent = ($row[$rightsKey] > 0) ? $imgYes : $imgNo;
			}
			elseif ($rightsKey == 'lock_record') {
				$cellContent = ($row[$rightsKey] > 0) ? (($row[$rightsKey] == 1) ? $imgYes : $imgShield) : $imgNo;
			}
			elseif ($rightsKey == 'api') {
				if ($row['api_export'] == 1 && $row['api_import'] == 1)
					$cellContent = $lang['global_71'] . RCView::br() . $lang['global_72'];
				elseif ($row['api_export'] == 1) $cellContent = $lang['global_71'];
				elseif ($row['api_import'] == 1) $cellContent = $lang['global_72'];
				else $cellContent = $imgNo;
			}
			elseif ($rightsKey == 'randomization') {
				if ($row['random_setup'] == 1) $cellContent .= $lang['rights_142'] . RCView::br();
				if ($row['random_dashboard'] == 1) $cellContent .= $lang['rights_143'] . RCView::br();
				if ($row['random_perform'] == 1) $cellContent .= $lang['rights_144'];
				if ($cellContent == '') $cellContent = $imgNo;
			}
			else {
				$cellContent = ($row[$rightsKey] == 1) ? $imgYes : $imgNo;
			}
			$cells .= RCView::td($cellAttrs, $cellContent);
		}
		echo RCView::tr(array(), $cells);
	}
	echo '</table></div>';
}

?>
	<script type="text/javascript">
	$(function(){
		$("[id^=rightsTableUserLinkId_]").click(function() {
			idUsername = $(this).attr('id').substring("rightsTableUserLinkId_".length);
			window.location.href="<?php echo PAGE_FULL; ?>?pid=<?php echo $project_id; ?>&id=" + idUsername + addGoogTrans();
		});
	});
	</script>
<?php

//Display page with checkboxes when user is selected or is being created
if (isset($_GET['id'])) {
	
	//Remove illegal characters (if somehow posted bypassing javascript)
	$user = $_GET['id'] = preg_replace("/[^a-zA-Z0-9-.@_]/", "", $_GET['id']);
	
	//If the person using this application is in a Data Access Group, do not allow them to add a new user or edit user from another group.
	if ($user_rights['group_id'] != "") {
		//If we are not editing someone in our group, redirect back to previous page
		$is_in_group = db_result(db_query("select count(1) from redcap_user_rights where project_id = $project_id 
												 and username = '$user' and group_id = '".$user_rights['group_id']."'"),0);
		if ($is_in_group == 0) {
			//User not in our group, so redirect back
			print "<script type='text/javascript'>window.location.href='".$_SERVER['PHP_SELF']."?pid=$project_id';</script>";
		}
	}
		
	// Don't allow Table-based auth users to be added if don't already exist in redcap_auth. They must be created in Control Center first.
	if ($auth_meth == "table" && !Authentication::isTableUser($user)) 
	{			
		print  "<div class='red' style='margin:20px 0;'>
					<img src='".APP_PATH_IMAGES."exclamation.png' class='imgfix'> <b>{$lang['global_03']}:</b><br><br>
					{$lang['rights_104']} \"<b>$user</b>\" {$lang['rights_105']} ";
		if (!$super_user) {
			print  $lang['rights_146'];
		} else {
			print  "{$lang['rights_107']}
					<a href='".APP_PATH_WEBROOT."ControlCenter/create_user.php' target='_blank' 
						style='text-decoration:underline;font-family:verdana;'>{$lang['rights_108']}</a>  
					{$lang['rights_109']}";
		}
		print  "</div>";
		renderPrevPageLink("UserRights/index.php");
		include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
		exit;
	}
	
	
	$q = db_query("select * from redcap_user_rights where project_id = $project_id and username = '$user' limit 1");
	if (db_num_rows($q)) {
		//Existing user
		$context_msg = "<img src='".APP_PATH_IMAGES."pencil.png' class='imgfix'> {$lang['rights_09']} {$lang['global_17']} \"<b>$user</b>\"";
		$new_user = false;
		$submit_text = "Save Changes";
		$context_msg_color = "blue";
		//Get variable for pre-filling checkboxes
		$this_user = db_fetch_assoc($q);
		$data_export_tool = $this_user['data_export_tool'];
		$data_import_tool = $this_user['data_import_tool'];
		$data_comparison_tool = $this_user['data_comparison_tool'];
		$data_logging = $this_user['data_logging'];
		$file_repository = $this_user['file_repository'];
		$double_data = $this_user['double_data'];
		$user_rights1 = $this_user['user_rights'];
		$expiration = $this_user['expiration'];
		$group_id = $this_user['group_id'];
		$lock_record = $this_user['lock_record'];
		$lock_record_multiform = $this_user['lock_record_multiform'];
		$lock_record_customize = $this_user['lock_record_customize'];
		$data_access_groups = $this_user['data_access_groups'];
		$graphical = $this_user['graphical'];
		$reports1 = $this_user['reports'];
		$chbx_email_newuser = "";		
		$design = $this_user['design'];	
		$dts = $this_user['dts'];
		$calendar = $this_user['calendar'];
		$record_create = $this_user['record_create'];
		$record_rename = $this_user['record_rename'];
		$record_delete = $this_user['record_delete'];
		$participants = $this_user['participants'];
		$data_quality_design = $this_user['data_quality_design'];
		$data_quality_execute = $this_user['data_quality_execute'];
		$api_export = $this_user['api_export'];
		$api_import = $this_user['api_import'];
		$random_setup = $this_user['random_setup'];
		$random_dashboard = $this_user['random_dashboard'];
		$random_perform = $this_user['random_perform'];
		//Loop through data entry forms and parse their values
		$dataEntryArr = explode("][", substr(trim($this_user['data_entry']), 1, -1));
		foreach ($dataEntryArr as $keyval) 
		{
			list($key, $value) = explode(",", $keyval, 2);
			$this_user["form-".$key] = $value;
		}
		unset($this_user['data_entry']);
		
		
	} else {
		//New user
		$context_msg = "<img src='".APP_PATH_IMAGES."add.png' class='imgfix'> {$lang['rights_11']} {$lang['global_17']} \"<b>$user</b>\"";
		$new_user = true;
		$submit_text = " Add User ";	
		$context_msg_color = "darkgreen";
		//Set variables to default for new user
		$data_export_tool = 2;
		$data_import_tool = 0;
		$data_comparison_tool = 0;
		$data_logging = 0;
		$file_repository = 1;
		$double_data = 0;
		$user_rights1 = 0;		
		$expiration = '';
		$group_id = '';	
		$lock_record = 0;
		$lock_record_multiform = 0;
		$lock_record_customize = 0;
		$data_access_groups = 0;
		$graphical = 1;
		$reports1 = 1;
		$design = 0;
		$dts = 0;
		$calendar = 1;
		$record_create = 1;
		$record_rename = 0;
		$record_delete = 0;
		$participants = 1;
		$data_quality_design = 0;
		$data_quality_execute = 0;
		$api_export = 0;
		$api_import = 0;
		$random_setup = 0;
		$random_dashboard = 0;
		$random_perform = ($randomization ? 1 : 0);
		//If we already have this new user's email address on file, provide the ability to notify them of their project access via email 
		$chbx_email_newuser = db_result(db_query("select user_email from redcap_user_information where username = '$user'"),0);
		if ($chbx_email_newuser != "") {
			$chbx_email_newuser = "<div style='color:#555'>
								   <input type='checkbox' name='notify_email' checked> {$lang['rights_112']}
								   </div>";
		}
	}
	
	print "<p>{$lang['rights_44']} $submit_text {$lang['rights_45']}";
	
	
	//Show message if adding/editing user
	print "<form name='user_rights_form' method='post' action='".PAGE_FULL."?pid=$project_id'>
		<div style='max-width:700px;'>
		<table width='96%' align='center'><tr><td class='$context_msg_color' style='text-align:center;'>$context_msg</td></tr></table>";
	
	//Show tables
	print "<table cellpadding=0 cellspacing=15 align='center' width=100%>
		<tr><td valign='top' style='width:350px;'>
		
		<div align='left' style='width:100%'>
		<div style='position: relative;top:6px;z-index:106;color:#505050;width:140px;font-weight:bold;font-family:Verdana,Arial;font-size:11px;text-align:center;background:#F2F2F2;padding:2px;border:1px solid #808080;border-bottom-width: 0px;'>
		{$lang['rights_46']}
		</div>
		<div style='width:325px;background:#F2F2F2;font-size:12px;padding:5px;border:1px solid #808080;position:relative;font-family:Arial;'>
		<br>
		
		<table cellpadding=0 cellspacing=4 style='font-size:12px;'>";
		
	//Invite Participants rights
	if (!empty($Proj->surveys))
	{
		print "<tr><td valign='top'><img src='".APP_PATH_IMAGES."send.png' class='imgfix'>&nbsp;&nbsp;".$lang['app_22']."</td><td valign='top' style='padding-top:2px;'> <input type='checkbox' name='participants' "; 
		if ($participants == 1) print "checked";
		print "> </td></tr>";
	} else {
		print "<input type='hidden' name='participants' value='$participants'>";
	}
		
	//Calendar rights
	print "<tr><td valign='top'><img src='".APP_PATH_IMAGES."date.png' class='imgfix'>&nbsp;&nbsp;{$lang['app_08']}</td><td valign='top' style='padding-top:2px;'> <input type='checkbox' name='calendar' "; 
	if ($calendar == 1) print "checked";
	print "> </td></tr>";
	
	//Data Export rights
	print "<tr><td valign='top' style='width:180px;'>
			<img src='".APP_PATH_IMAGES."application_go.png'  class='imgfix'>&nbsp;&nbsp;{$lang['app_03']} </td><td style='padding-top:2px;' valign='top' style='font-family:Verdana,Arial;font-size:11px;color:#808080;'>";
	print "<div><input type='radio' name='data_export_tool' value='0' "; if ($data_export_tool == 0) print "checked"; print "> {$lang['rights_47']}</div>";
	print "<div><input type='radio' name='data_export_tool' value='2' "; if ($data_export_tool == 2) print "checked"; print "> {$lang['rights_48']}</div>";
	print "<div><input type='radio' name='data_export_tool' value='1' "; if ($data_export_tool == 1) print "checked"; print "> {$lang['rights_49']}</div>";
	
	print "</td></tr>
		<tr><td valign='top'><img src='".APP_PATH_IMAGES."table_row_insert.png'  class='imgfix'>&nbsp;&nbsp;{$lang['app_01']} </td><td style='padding-top:2px;' valign='top'> <input type='checkbox' name='data_import_tool' ";	if ($data_import_tool == 1) print "checked";
	print "> </td></tr>
		<tr><td valign='top'><img src='".APP_PATH_IMAGES."page_copy.png'  class='imgfix'>&nbsp;&nbsp;{$lang['app_02']} </td><td style='padding-top:2px;' valign='top'> <input type='checkbox' name='data_comparison_tool' ";	if ($data_comparison_tool == 1) print "checked";
	print "> </td></tr>
		<tr><td valign='top'><img src='".APP_PATH_IMAGES."report.png'  class='imgfix'>&nbsp;&nbsp;{$lang['app_07']} </td><td style='padding-top:2px;' valign='top'> <input type='checkbox' name='data_logging' ";	if ($data_logging == 1) print "checked";
	print "> </td></tr>
		<tr><td valign='top'><img src='".APP_PATH_IMAGES."page_white_stack.png'  class='imgfix'>&nbsp;&nbsp;{$lang['app_04']} </td><td style='padding-top:2px;' valign='top'> <input type='checkbox' name='file_repository' "; if ($file_repository == 1) print "checked";
	print "> </td></tr>";
	
	//Only show if a Double Data Entry project
	if ($double_data_entry) {
		print "<tr><td valign='top'><img src='".APP_PATH_IMAGES."group.png'  class='imgfix'>&nbsp;&nbsp;{$lang['rights_50']} </td><td valign='top' style='padding-top:2px;font-family:Verdana,Arial;font-size:11px;color:#808080;'>
				<input type='radio' name='double_data' value='0' "; if ($double_data == 0) print "checked";
		print "> {$lang['rights_51']}<br>";
		//If data entry person #1 or #2 are already designated, do not allow user to designate another person as #1 or #2.
		$q1 = db_query("select 1 from redcap_user_rights where double_data = '1' and project_id = $project_id and username != '$user'");
		if (!db_num_rows($q1)) {
			print "<input type='radio' name='double_data' value='1' "; 
			if ($double_data == 1) print "checked";
			print "> {$lang['rights_52']} #1<br>";
		}		
		$q2 = db_query("select 1 from redcap_user_rights where double_data = '2' and project_id = $project_id and username != '$user'");
		if (!db_num_rows($q2)) {
			print "<input type='radio' name='double_data' value='2' "; if ($double_data == 2) print "checked";
			print "> {$lang['rights_52']} #2</td></tr>";
		}
	}
	
	//User Rights
	print "<tr><td valign='top'><img src='".APP_PATH_IMAGES."user.png' class='imgfix'>&nbsp;&nbsp;{$lang['app_05']}</td><td valign='top' style='padding-top:2px;'> <input type='checkbox' name='user_rights' "; 
	if ($user_rights1 == 1) print "checked";
	print "> </td></tr>";	
	
	//Data Access Groups
	print "<tr><td valign='top'><img src='".APP_PATH_IMAGES."group.png' class='imgfix'>&nbsp;&nbsp;{$lang['global_22']}</td><td valign='top' style='padding-top:2px;'> <input type='checkbox' name='data_access_groups' "; 
	if ($data_access_groups == 1) print "checked";
	print "> </td></tr>";
	
	//Graphical Data View & Stats
	if ($enable_plotting > 0) {
		print "<tr><td valign='top'><img src='".APP_PATH_IMAGES."chart_curve.png' class='imgfix'>&nbsp;&nbsp;{$lang['app_13']}</td><td valign='top' style='padding-top:2px;'> <input type='checkbox' name='graphical' "; 
		if ($graphical == 1) print "checked";
		print "> </td></tr>";
	} else {
		print "<input type='hidden' name='graphical' value='$graphical'>";
	}	
	
	// Data Quality (design & execute rights are separate)
	print  "<tr>
				<td valign='top'>
					<img src='".APP_PATH_IMAGES."checklist.png' class='imgfix'>&nbsp;&nbsp;{$lang['app_20']}
					<div style='padding:0px 0 0px 24px;font-size:11px;color:#777;font-family:tahoma;'>
						<a href='javascript:;' style='font-family:tahoma;font-size:10px;text-decoration:underline;' onclick=\"
							$('#explainDataQuality').dialog({ bgiframe: true, title: '".cleanHtml($lang['dataqueries_100'])."', modal:true, width:550, buttons:{Close:function(){\$(this).dialog('close');}}});
						\">{$lang['dataqueries_100']}</a>
					</div>
				</td>
				<td valign='top' style='padding-top:2px;'> 
					<input type='checkbox' name='data_quality_design' ".($data_quality_design == 1 ? "checked" : "")."> 
					{$lang['dataqueries_40']}<br>
					<input type='checkbox' name='data_quality_execute' ".($data_quality_execute == 1 ? "checked" : "")."> 
					{$lang['dataqueries_41']}</td>
			</tr>";
	
	// Reports & Report Builder
	print "<tr><td valign='top'><img src='".APP_PATH_IMAGES."layout.png' class='imgfix'>&nbsp;&nbsp;{$lang['rights_96']}</td><td valign='top' style='padding-top:2px;'> <input type='checkbox' name='reports' "; 
	if ($reports1 == 1) print "checked";
	print "> </td></tr>";
	
	// Project Setup/Design
	print "<tr><td valign='top'><img src='".APP_PATH_IMAGES."wrench.png' class='imgfix'>&nbsp;&nbsp;{$lang['rights_135']}</td><td valign='top' style='padding-top:2px;'> <input type='checkbox' name='design' "; 
	if ($design == 1) print "checked";
	print "> </td></tr>";
	
	// DTS (only if enabled for whole system AND this project)
	if ($dts_enabled_global && $dts_enabled) 
	{
		?>
		<tr>
			<td valign="top">
				<div style="margin-left:1.8em;text-indent:-1.8em;"> 
					<img src="<?php echo APP_PATH_IMAGES ?>databases_arrow.png" class="imgfix">&nbsp;&nbsp;<?php echo $lang["rights_132"] ?>
				</div>
			</td>
			<td valign="top" style="padding-top:2px;"> 
				<?php if ($super_user) { ?>
					<div style="margin-left:1.8em;text-indent:-1.8em;">
						<input type="checkbox" name="dts" <?php if ($dts == 1) echo 'checked' ?>>
						<span style="font-family:tahoma;color:#800000;font-size:10px;"><?php echo $lang['rights_134'] ?></span> 
					</div>
				<?php } else { ?>
					<div style="margin-left:1.8em;text-indent:-1.8em;">
						<input type="checkbox" <?php if ($dts == 1) echo 'checked' ?> disabled="disabled">
						<input type="hidden" name="dts" value="<?php echo $dts ?>">
						<span style="font-family:tahoma;color:#800000;font-size:10px;"><?php echo $lang['rights_134'] ?></span>
					</div>
				<?php } ?>
			</td>
		</tr>
		<?php
	}
	
	// API
	if ($api_enabled) {
		$apiHelp = RCView::a(array('id' => 'apiHelpLinkId', 'href' => '#', 'style' => 'font-family:tahoma;font-size:10px;text-decoration:underline;'), $lang['rights_141']);
		print  "<tr><td valign='top'>
					<img src='".APP_PATH_IMAGES."computer.png' class='imgfix'>&nbsp;&nbsp;{$lang['setup_77']}
					<div style='padding:0px 0 0px 24px;font-size:11px;color:#777;font-family:tahoma;'>$apiHelp</div>
				</td>
				<td valign='top' style='padding-top:2px;'> <input type='checkbox' name='api_export' "; 
		if ($api_export == 1) print "checked";
		print  "> {$lang['rights_139']}<br/>
				<input type='checkbox' name='api_import' "; 
		if ($api_import == 1) print "checked";
		print "> {$lang['rights_140']}</td></tr>";
	}
	else {
		print RCView::hidden(array('name' => 'api_export', 'value' => $api_export));
		print RCView::hidden(array('name' => 'api_import', 'value' => $api_import));
	}
	
	// Randomization
	if ($randomization) {
		$randHelp = RCView::a(array('id' => 'randHelpLinkId', 'href' => '#', 'style' => 'font-family:tahoma;font-size:10px;text-decoration:underline;'), $lang['rights_145']);
		print  "<tr><td valign='top'>
					<img src='".APP_PATH_IMAGES."arrow_switch.png' class='imgfix'>&nbsp;&nbsp;{$lang['app_21']}
					<div style='padding:0px 0 0px 24px;font-size:11px;color:#777;font-family:tahoma;'>$randHelp</div>
				</td>
				<td valign='top' style='padding-top:2px;'> <input type='checkbox' name='random_setup' "; 
		if ($random_setup == 1) print "checked";
		print  "> {$lang['rights_142']}<br/>
				<input type='checkbox' name='random_dashboard' "; 
		if ($random_dashboard == 1) print "checked";
		print "> {$lang['rights_143']}<br/>
				<input type='checkbox' name='random_perform' "; 
		if ($random_perform == 1) print "checked";
		print  "> {$lang['rights_144']}</td></tr>";
	}
	else {
		print RCView::hidden(array('name' => 'random_setup', 'value' => $random_setup));
		print RCView::hidden(array('name' => 'random_dashboard', 'value' => $random_dashboard));
		print RCView::hidden(array('name' => 'random_perform', 'value' => $random_perform));
	}
	
	// Lock Record
	print  "<tr>
				<td valign='top' colspan='2' style='border-top:1px solid #888;padding-top:4px;color:#555;font-size:11px;'>
					{$lang['rights_130']}
				</td>
			</tr>
			<tr>
				<td valign='top'>
					<div style='margin-left:1.8em;text-indent:-1.8em;'> 
						<img src='".APP_PATH_IMAGES."lock_plus.png' class='imgfix'>&nbsp;&nbsp;{$lang['app_11']}
					</div>
				</td>
				<td valign='top' style='padding-top:2px;'>
					<input type='checkbox' name='lock_record_customize' "; if ($lock_record_customize == 1){print "checked";} print ">
				</td>
			</tr>
			<tr>
				<td valign='top'>
					<img src='".APP_PATH_IMAGES."lock.png' class='imgfix'>&nbsp;&nbsp;{$lang['rights_97']}
					<div style='padding:4px 0 4px 22px;font-size:11px;color:#777;font-family:tahoma;'>
						{$lang['rights_113']}<br>
						<img src='" . APP_PATH_IMAGES . "video_small.png' class='imgfix'> 
						<a onclick=\"popupvid('locking01.flv')\" style='color:#3E72A8;font-size:10px;font-family:tahoma;' href='javascript:;'>{$lang['rights_131']}</a>
					</div>
				</td>
				<td valign='top' style='padding-top:2px;'>
					<div style='margin-left:1.8em;text-indent:-1.8em;'><input type='radio' name='lock_record' value='0' " . ($lock_record == '0' ? "checked" : "") . " onclick=\"document.user_rights_form.lock_record_multiform.checked=false;\"> {$lang['global_23']}</div>
					<div style='margin-left:1.8em;text-indent:-1.8em;'><input type='radio' name='lock_record' value='1' " . ($lock_record == '1' ? "checked" : "") . "> {$lang['rights_115']}</div>
					<div style='margin-left:1.8em;text-indent:-1.8em;'>
						<input type='radio' name='lock_record' value='2' " . ($lock_record == '2' ? "checked" : "") . " onclick=\"
							if (this.checked) {
								simpleDialog('" . cleanHtml($lang['rights_136']) . "','" . cleanHtml($lang['global_03']) . "');
							}
						\"> {$lang['rights_116']}<br>
						<a style='text-decoration:underline;font-size:10px;font-family:tahoma;' href='javascript:;' onclick='esignExplainLink(); return false;'>{$lang['rights_117']}</a>
					</div>
				</td>
			</tr>
			<tr>
				<td valign='top' style='font-size:11px;padding:0 3px 6px 22px;'>
					{$lang['rights_118']}
				</td>
				<td valign='top' style='padding-top:2px;'>
					<div style='margin-left:1.8em;text-indent:-1.8em;'> <input type='checkbox' name='lock_record_multiform' "; if ($lock_record_multiform == '1'){ print "checked"; } print "></div>
				</td>
			</tr>";
	
	// Create/Rename/Delete Records
	print  "<tr>
				<td valign='top' colspan='2' style='border-top:1px solid #888;padding-top:4px;color:#555;font-size:11px;'>
					{$lang['rights_119']}
					&nbsp;&nbsp;
					<a style='text-decoration:underline;font-size:10px;font-family:tahoma;' href='javascript:;' onclick='userRightsRecordsExplain(); return false;'>{$lang['rights_123']}</a>
				</td>
			</tr>
			<tr>
				<td valign='top'>
					<img src='".APP_PATH_IMAGES."blog_plus.png' class='imgfix'>&nbsp;&nbsp;{$lang['rights_99']}
				</td>
				<td valign='top' style='padding-top:2px;'> 
					<input type='checkbox' name='record_create' " . ($record_create == 1 ? "checked" : "") . ">
				</td>
			</tr>
			<tr>
				<td valign='top'>
					<img src='".APP_PATH_IMAGES."blog_pencil.png' class='imgfix'>&nbsp;&nbsp;{$lang['rights_100']}
				</td>
				<td valign='top' style='padding-top:2px;'> 
					<input type='checkbox' name='record_rename' " . ($record_rename == 1 ? "checked" : "") . ">
				</td>
			</tr>
			<tr>
				<td valign='top' style='padding:2px 0 6px;'>
					<img src='".APP_PATH_IMAGES."blog_minus.png' class='imgfix'>&nbsp;&nbsp;{$lang['rights_101']}
				</td>
				<td valign='top' style='padding:2px 0 6px;'> 
					<input type='checkbox' name='record_delete' " . ($record_delete == 1 ? "checked" : "") . ">
				</td>
			</tr>";
	
	// Expiration Date
	print "<tr>
				<td valign='top' colspan='2' style='border-top:1px solid #888;padding-top:2px;line-height:2px;'>&nbsp;</td>
			</tr>
			<tr>
				<td valign='top'>
					<img src='".APP_PATH_IMAGES."clock.png'  class='imgfix'>
					&nbsp;&nbsp;{$lang['rights_54']}
					<div style='font-family:Verdana,Arial;font-size:10px;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<i>{$lang['rights_55']}</i></div>
				</td>
				<td valign='top' style='padding-top:5px;'> 
					<input type='text' value='$expiration' class='x-form-text x-form-field' style='width:70px;' maxlength='10' id='expiration' name='expiration' onchange='redcap_validate(this,\"\",\"\",\"hard\",\"date\")' onkeydown='if(event.keyCode == 13) return false;'>
				</td>
			</tr>";	
	
	//Display "Reset Password" or "Set Temp Password" if using table-based auth (or none auth)
	$submit_onclick = "";
	if ($auth_meth == "none" || $auth_meth == "table" || ($auth_meth == "ldap_table" && !$isLDAP)) {	
		//ONLY Super Users can reset passwords and add users to redcap_auth table
		if (($auth_meth == "none" && $user != "site_admin") || ($user != "site_admin" && ($user == $userid || $super_user))) {
			//print $reset_pwd_string;
			if ($is_table_user) {
				//Existing user - reseting password
				$submit_onclick = "onclick='if(check.checked) { if(trim(reset.value) != trim(reset2.value)) { alertbad(reset,\"{$lang['rights_56']}\"); reset.value=\"\";reset2.value=\"\";setTimeout(function () { reset.focus() }, 1); return false;} if(reset.value.length==0 || reset2.value.length==0) { alertbad(reset,\"{$lang['rights_57']}\"); return false;} } return true;'";
			}
		}
	}	
	
	print "</td>
		</tr>";	
	
	print  "</table>		
			</div>
			<br>";
	
	//Leave double_data as hidden field if not a Double Data Entry project
	if (!$double_data_entry) {
		print "<input type='hidden' name='double_data' value='$double_data'>";
	}	
	
	//BUTTONS
	print  "<table width=100% align=center>
				<tr><td style='text-align:center;padding-bottom:5px'>$chbx_email_newuser</td></tr>
				<tr class='notranslate'><td style='text-align:center;'><input type='submit' value='$submit_text' name='submit' $submit_onclick></td></tr>
				<tr class='notranslate'><td style='text-align:center;'><input type='submit' value=' - Cancel - ' name='cancel'></td></tr>";		
	if (!$new_user) {
		print  "<tr class='notranslate'>
					<td style='text-align:center;'>
						<input type='submit' value=' Delete User ' name='submit' onclick=\"
							return confirm('{$lang['rights_120']} " . (($user == $userid) ? "YOURSELF" : "\'$user\'") . " {$lang['rights_121']}');
						\">
					</td>
				</tr>";	
	}
	print  "</table></div>";
	
	print "</td><td valign='top'>";
	
	
	
	
	
	// Show all FORMS for setting rights level for each
	print "<div align='left' style='width:350px;'>
			<div style='position: relative;top:6px;z-index:106;color:#800000;width:130px;font-weight:bold;font-family:Verdana,Arial;font-size:11px;text-align:center;background:#F2F2F2;padding:2px;border:1px solid #FFA3A3;border-bottom-width: 0px;'>
				{$lang['rights_59']}
			</div>
			<div style='background:#F2F2F2;font-size:12px;border:1px solid #FFA3A3;position:relative;font-family:Arial;'>
			<table id='form_rights' cellpadding=0 cellspacing=0 style='width:100%;font-size:11px;color:#800000;font-family:Verdana,Arial;'>
			<tr>
				<td valign='top' colspan='3' style='padding:10px 12px 8px;'>
					<i>{$lang['rights_147']}</i>
				</td>
			</tr>
			<tr>
				<td valign='top' style='border-right:1px solid #FFA3A3;'>&nbsp;</td>
				<td valign='top' style='font-size:10px;text-align:left;width:205px;'>
					<div style='float:left;padding:2px 5px;white-space:normal;width:35px;'>{$lang['rights_60']}</div>
					<div style='float:left;padding:2px 5px;white-space:normal;width:30px;'>{$lang['rights_61']}</div>
					<div style='float:left;padding:2px 5px;white-space:normal;width:34px;'>{$lang['rights_138']}</div>";
	if ($enable_edit_survey_response && !empty($Proj->surveys))
	{
		print 		"<div style='float:left;padding:2px 5px;white-space:normal;width:60px;'>{$lang['rights_137']}</div>";
	}
	print  "		<div style='clear:both;'></div>			
				</td>
			</tr>";
	
	// Loop through all forms
	foreach ($Proj->forms as $form_name=>$form_attr)
	{
		print  "<tr class='notranslate'>
					<td valign='middle' class='derights1'>
						{$form_attr['menu']}
						" . (isset($form_attr['survey_id']) ? " &nbsp;<span style='color:#666;font-size:10px;font-family:tahoma;'>({$lang['global_59']})</span>" : "") . "
					</td>
					<td valign='middle' class='nobr derights2'>
						<input type='radio' name='form-" . $form_name . "' value='0' ";
		if ($this_user["form-".$form_name] == "0") print "checked";
		print 			">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='radio' name='form-" . $form_name . "' value='2' ";
		if ($this_user["form-".$form_name] == "2") print "checked";
		print 			">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='radio' name='form-" . $form_name . "' value='1' ";
		if (($this_user["form-".$form_name] == "1" || $this_user["form-".$form_name] == "3") || $new_user) print "checked";
		print 			">";
		// If this form is used as a survey, render checkbox for setting edit/delete response rights (value=3)
		if ($enable_edit_survey_response && isset($form_attr['survey_id']))
		{
			print 		"&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='checkbox' id='form-editresp-" . $form_name . "' name='form-editresp-" . $form_name . "' ";
			if ($this_user["form-".$form_name] == "3") print "checked";
			print 		">";
		}
		print "		</td>
				</tr>";
	}
	
	print "</table>
		</div></div>";
	print "</td></tr>
		</table>
		</div>
		<input type='hidden' name='user' value='".$_GET['id']."'>
		</form>";
	
	
	// API explanation pop-up
	echo RCView::div(array('id' => 'apiHelpDialogId', 'title' => $lang['rights_141'], 'style' => 'display: none;'),
				RCView::p(array('style' => 'font-family:arial;'),
								$lang['system_config_114'] . ' ' . $lang['edit_project_57'] . $lang['period'] . '<br/><br/>' .
								RCView::a(array('href' => APP_PATH_WEBROOT_PARENT . 'api/help/', 'style' => 'text-decoration:underline;', 'target' => '_blank'),
								$lang['setup_45'] . ' ' . $lang['edit_project_57'])));
	
	?>
	
	<!-- Data Quality explanation pop-up -->
	<div style="display:none;margin:15px 0;line-height:16px;" id="explainDataQuality"><?php echo $lang['dataqueries_101'] ?></div>
	
	<!-- Randomization explanation pop-up -->
	<div style="display:none;" id="randHelpDialogId" title="<?php echo cleanHtml2($lang['rights_145']) ?>">
		<p><?php echo $lang['random_01'] ?></p>
		<p><?php echo $lang['create_project_63'] ?></p>
	</div>
	
	
	<!-- Custom javascript -->
	<script type="text/javascript">
	$(function(){
		$('#expiration').datepicker({yearRange: '-10:+10', changeMonth: true, changeYear: true, dateFormat: 'yy-mm-dd'});
		// If select "edit response" checkbox, then set form-level rights radio button to View & Edit
		$('table#form_rights input[type="checkbox"]').click(function(){
			if ($(this).prop('checked')) {
				var form = $(this).attr('id').substring(14);
				// Deselect all, then select View & Edit
				$('table#form_rights input[name="form-'+form+'"][value="0"]').prop('checked',false);
				$('table#form_rights input[name="form-'+form+'"][value="2"]').prop('checked',false);
				$('table#form_rights input[name="form-'+form+'"][value="1"]').prop('checked',true);
			}
		});
		$("#apiHelpLinkId").click(function() {
			$("#apiHelpDialogId").dialog({ bgiframe: true, modal: true, width: 500, buttons: { 
				Close: function() { $(this).dialog('close'); }}}
			);
		});
		$("#randHelpLinkId").click(function() {
			$("#randHelpDialogId").dialog({ bgiframe: true, modal: true, width: 500, buttons: { 
				Close: function() { $(this).dialog('close'); }}}
			);
		});
	});
	</script>
	<?php
	
}


include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
