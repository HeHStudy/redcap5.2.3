<?php


require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

//If action is provided in AJAX request, perform action.
if (isset($_GET['action'])) 
{
	switch ($_GET['action']) 
	{
		case "delete":
			//Before deleting, make sure no users are in the group. If there are, don't delete.
			if (!is_numeric($_GET['item'])) exit('ERROR!');
			$gcount = db_result(db_query("select count(1) from redcap_user_rights where project_id = $project_id and group_id = {$_GET['item']}"),0);
			if ($gcount < 1 && $gcount != "") 
			{	
				// Delete from DAG table
				$sql = "delete from redcap_data_access_groups where group_id = ".$_GET['item'];
				$q = db_query($sql);
				// Also delete any instances of records being attributed to the DAG in the data table
				$sql2 = "delete from redcap_data where project_id = $project_id and field_name = '__GROUPID__' 
						and value = '".prep($_GET['item'])."'";
				$q = db_query($sql2);
				// Logging
				if ($q) log_event("$sql;\n$sql2","redcap_data_access_groups","MANAGE",$_GET['item'],"group_id = ".$_GET['item'],"Delete data access group");
				print  "<div align='center' style='max-width:700px;'><span class='yellow'>
						<img src='".APP_PATH_IMAGES."exclamation_orange.png' class='imgfix'> 
						{$lang['data_access_groups_ajax_01']}
						</span></div><br>";
			} else {
				print  "<div class='red'><img src='".APP_PATH_IMAGES."exclamation.png' class='imgfix'> <b>{$lang['global_01']}:</b><br>
						{$lang['data_access_groups_ajax_03']}
						</div><br>";
			}
			## What happens to the associated records that belong to a group that is deleted?
			break;
		case "add":
			$new_group_name = htmlspecialchars(strip_tags(html_entity_decode(trim($_GET['item']), ENT_QUOTES)), ENT_QUOTES);
			if ($new_group_name != "") {
				$sql = "insert into redcap_data_access_groups (project_id, group_name) values ($project_id, '" . prep($new_group_name) . "')";
				$q = db_query($sql);
				// Logging
				if ($q) {
					$dag_id = db_insert_id();
					log_event($sql,"redcap_data_access_groups","MANAGE",$dag_id,"group_id = $dag_id","Create data access group");
				}
				print  "<div align='center' style='max-width:700px;'><span class='yellow'>
						<img src='".APP_PATH_IMAGES."exclamation_orange.png' class='imgfix'> 
						{$lang['data_access_groups_ajax_04']}
						</span></div><br>";
			}
			break;
		case "rename":
			$group_id = substr($_GET['group_id'],4);
			if (!is_numeric($group_id)) exit('ERROR!');
			$new_group_name = htmlspecialchars(strip_tags(html_entity_decode(trim($_GET['item']), ENT_QUOTES)), ENT_QUOTES);
			if ($new_group_name != "") {
				$sql = "update redcap_data_access_groups set group_name = '" . prep($new_group_name) . "' where group_id = $group_id";
				$q = db_query($sql);
				// Logging
				if ($q) log_event($sql,"redcap_data_access_groups","MANAGE",$group_id,"group_id = ".$group_id,"Rename data access group");
			}
			exit($new_group_name);
			break;
		case "add_user":
			if (!is_numeric($_GET['group_id']) && $_GET['group_id'] != '') exit('ERROR!');
			if ($_GET['group_id'] == "") {
				$assigned_msg = "is now not";
				$_GET['group_id'] = "NULL";
			} else {
				$assigned_msg = "has been";
			}
			$sql = "update redcap_user_rights set group_id = {$_GET['group_id']} where username = '".prep($_GET['user'])."' and project_id = $project_id";
			$q = db_query($sql);
			// Logging
			if ($q) log_event($sql,"redcap_user_rights","MANAGE",$_GET['user'],"username = '{$_GET['user']}'","Assign user to data access group");
			print  "<div align='center' style='max-width:700px;'><span class='yellow'>
					<img src='".APP_PATH_IMAGES."exclamation_orange.png' class='imgfix'> 
					{$lang['global_17']} \"<b>".remBr(RCView::escape($_GET['user']))."</b>\" $assigned_msg {$lang['data_access_groups_ajax_06']}
					</span></div><br>";
			break;
		case "select_group":
			$group_id = db_result(db_query("select group_id from redcap_user_rights where username = '".prep($_GET['user'])."' and project_id = $project_id"),0);
			exit($group_id);
			break;
	}

}


//Render groups table
$Proj->resetGroups();
$groups = $Proj->getGroups();
if (!empty($groups))
{
	## DAG RECORD COUNT
	// Determine which records are in which group
	$recordsInDags = array();
	$recordDag = array();
	$sql = "select record, field_name, value from redcap_data where project_id = $project_id 
			and field_name in ('$table_pk', '__GROUPID__') group by record, field_name 
			order by abs(record), record, field_name desc";
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q))
	{
		if (!isset($recordDag[$row['record']]) && $row['field_name'] != '__GROUPID__') {
			$recordDag[$row['record']] = 0;
		} elseif ($row['field_name'] == '__GROUPID__') {
			$recordDag[$row['record']] = (isset($groups[$row['value']]) ? $row['value'] : 0);
		}
	}
	// Get count of all records in each group
	foreach ($recordDag as $record=>$group_id) 
	{
		if (!isset($recordsInDags[$group_id])) {
			$recordsInDags[$group_id] = 1;
		} else {
			$recordsInDags[$group_id]++;
		}
	}
	unset($recordDag);
	
	// Table header
	print  "<table class='form_border'>
				<tr>
					<td class='header' style='padding:5px 8px;'>{$lang['global_22']}</td>
					<td class='header' style='padding:5px 8px;'>{$lang['data_access_groups_ajax_08']}</td>
					<td class='header' style='padding:5px 8px;width:120px;'>{$lang['data_access_groups_ajax_25']}</td>
					<td class='header' style='padding:2px 8px 5px;font-weight:normal;text-align:center;font-size:10px;'>
						{$lang['data_access_groups_ajax_18']}
						<a href='javascript:;' onclick=\"alert('".cleanHtml($lang['data_access_groups_ajax_19'])."');\"><img title=\"".cleanHtml2($lang['form_renderer_02'])."\" src='".APP_PATH_IMAGES."help.png' class='imgfix'></a><br>
						{$lang['define_events_66']}
					</td>
					<td class='header' style='padding:5px 8px;font-weight:normal;font-size:9px;text-align:center'>{$lang['data_access_groups_ajax_09']}</td>
				</tr>";
	// Get array of group users
	$groupUsers = $Proj->getGroupUsers(null,true);
	// Loop through each group
	foreach ($groups as $group_id=>$group_name)
	{
		print  "<tr>
					<td class='label' style='padding:5px 8px;'>
						<span id='gid_{$group_id}' class='editText notranslate'>$group_name</span>
					</td>
					<td class='data notranslate' style='font-size:9px;padding:5px 8px;'>
						".implode(", ", $groupUsers[$group_id])."
					</td>
					<td class='data notranslate' style='padding:5px 8px;'>
						".(isset($recordsInDags[$group_id]) ? $recordsInDags[$group_id] : 0)."
					</td>
					<td id='ugid_$group_id' class='data notranslate' style='font-size:10px;color:#777;padding:5px 8px;'>
						".$Proj->getUniqueGroupNames($group_id)."
					</td>
					<td class='data' style='text-align:center'>
						<a href='javascript:;'><img src='".APP_PATH_IMAGES."cross.png' class='imgfix2' alt='Delete' title='Delete' 
							onclick=\"del_msg('$group_id','".str_replace("&#039;", "\'", $group_name)."','".APP_PATH_WEBROOT."DataAccessGroups/data_access_groups_ajax.php','$app_name')\"></a>
					</td>
				</tr>";
	}
	// Add row for users not in a DAG
	print  "<tr>
				<td class='label' style='padding:5px 8px;font-weight:normal;color:#800000;'>{$lang['data_access_groups_ajax_24']}</td>
				<td class='data notranslate' style='font-size:9px;padding:5px 8px;'>
					".implode(", ", $groupUsers[0])."
					<div style='color:red;'>{$lang['data_access_groups_ajax_26']}</div>
				</td>
				<td class='data notranslate' style='padding:5px 8px;'>
					".$recordsInDags[0]."
				</td>
				<td class='data' style='font-size:10px;color:#777;padding:5px 8px;'></td>
				<td class='data'></td>
			</tr>";
	print  "</table>";
}

//Type new group name
print  "<p><input type='text' value='Type new group name' size='30' maxlength='100' id='new_group' class='x-form-text x-form-field' style='color:#777777;' 
			onclick=\"if(this.value=='Type new group name'){this.value='';this.style.color='#000000';}\"  
			onfocus=\"if(this.value=='Type new group name'){this.value='';this.style.color='#000000';}\"  
			onblur=\"if(this.value==''){this.value='Type new group name';this.style.color='#777777';}\" 
			onkeydown=\"if(event.keyCode==13) add_group('".APP_PATH_WEBROOT."DataAccessGroups/data_access_groups_ajax.php','$app_name')\">&nbsp; 
		<input type='button' value=' Add Group ' class='imgfix' id='new_group_button' onclick=\"add_group('".APP_PATH_WEBROOT."DataAccessGroups/data_access_groups_ajax.php','$app_name')\">&nbsp;
		<span id='progress_img' style='visibility:hidden'><img src='".APP_PATH_IMAGES."progress_small.gif' class='imgfix'></span>
		<br><br></p>";
		


$q = db_query("select group_id, group_name from redcap_data_access_groups where project_id = '$project_id' order by group_name");
if (db_num_rows($q) > 0) {
	print  "<hr size=1><p><b>{$lang['data_access_groups_ajax_10']}</b><br><br>
			{$lang['data_access_groups_ajax_11']}</p>
			<p style='color:#800000;font-size:13px;'>{$lang['data_access_groups_ajax_12']} &nbsp;";	
	print  "<select id='group_users' class='x-form-text x-form-field' style='height:22px;padding-right:0;' 
				onchange=\"select_group(this.value,'".APP_PATH_WEBROOT."DataAccessGroups/data_access_groups_ajax.php','$app_name')\">
				<option value=''>-- {$lang['data_access_groups_ajax_13']} --</option>";
	$q2 = db_query("select distinct username from redcap_user_rights where project_id = '$project_id' and username != '$userid' order by username");
	while ($row = db_fetch_array($q2)) {
		print  "<option class='notranslate' value='".$row['username']."'>".$row['username']."</option>";		
	}	
	print  "</select>&nbsp; {$lang['data_access_groups_ajax_14']} &nbsp;";
	print  "<select id='groups' class='x-form-text x-form-field' style='height:22px;padding-right:0;'>
				<option value=''> -- {$lang['data_access_groups_ajax_15']} -- </option>
				<option value=''>[{$lang['data_access_groups_ajax_16']}]</option>";
	while ($row = db_fetch_array($q)) {
		print  "<option class='notranslate' value='".$row['group_id']."'>".$row['group_name']."</option>";		
	}
	print  "</select>&nbsp; 
			<input type='button' value=' Assign ' id='user_group_button' class='imgfix' onclick=\"
				if (document.getElementById('group_users').value == '') {
					alert('{$lang['data_access_groups_ajax_17']}');
					document.getElementById('group_users').focus();
				} else {
					add_user('".APP_PATH_WEBROOT."DataAccessGroups/data_access_groups_ajax.php','$app_name');
				}
				\">&nbsp;
			<span id='progress_img_user' style='visibility:hidden'><img src='".APP_PATH_IMAGES."progress_small.gif' class='imgfix'></span>
			</p>";
}
