<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/




/**
 * RENDER TABS
 */ 
$projectSetupTabs = array();
// Page-sensitive tabs that appear
if (PAGE == "UserRights/index.php" || PAGE == "DataAccessGroups/index.php")
{
	$projectSetupTabs["ProjectSetup/index.php"] = array("icon"=>"arrow_left.png", "label"=>$lang['app_17']);
	if ($user_rights['user_rights']) {
		$projectSetupTabs["UserRights/index.php"] = array("icon"=>"user.png", "label"=>$lang['app_05']);
	}
	// 
	if ($user_rights['data_access_groups']) {
		$projectSetupTabs["DataAccessGroups/index.php"] = array("icon"=>"group.png", "label"=>$lang['global_22']);
	}
}
elseif (PAGE == "Surveys/edit_info.php" || PAGE == "Surveys/create_survey.php")
{
	$projectSetupTabs["ProjectSetup/index.php"] = array("icon"=>"arrow_left.png", "label"=>$lang['app_17']);
	$projectSetupTabs["Design/online_designer.php"] = array("icon"=>"blog_pencil.png", "label"=>$lang['design_25']);
	if (PAGE == "Surveys/edit_info.php") {
		$projectSetupTabs[PAGE] = array("icon"=>"pencil.png", "label"=>$lang['setup_05']);
	} else {
		$projectSetupTabs[PAGE] = array("icon"=>"add.png", "label"=>$lang['setup_06']);
	}
}
elseif (PAGE == "ProjectGeneral/edit_project_settings.php")
{
	$projectSetupTabs["ProjectSetup/index.php"] = array("icon"=>"arrow_left.png", "label"=>$lang['app_17']);
	$projectSetupTabs["ProjectGeneral/edit_project_settings.php"] = array("icon"=>"pencil.png", "label"=>$lang['edit_project_38']);
}
elseif (PAGE == "Design/online_designer.php" || PAGE == "Design/data_dictionary_upload.php" || PAGE == "SharedLibrary/index.php") {
	$projectSetupTabs["ProjectSetup/index.php"] = array("icon"=>"arrow_left.png", "label"=>$lang['app_17']);
	$projectSetupTabs["Design/online_designer.php"] = array("icon"=>"blog_pencil.png", "label"=>$lang['design_25']);
	$projectSetupTabs["Design/data_dictionary_upload.php"] = array("icon"=>"xlsup.gif", "label"=>$lang['design_108']);
	if ($shared_library_enabled && PAGE == "SharedLibrary/index.php") {
		$projectSetupTabs["SharedLibrary/index.php"] = array("icon"=>"blogs_arrow.png", "label"=>$lang['design_37']);
	}
}
elseif (PAGE == "ExternalLinks/index.php") {
	$projectSetupTabs["index.php"] = array("icon"=>"house.png", "label"=>$lang['bottom_44']);
	$projectSetupTabs["ProjectSetup/index.php"] = array("icon"=>"clipboard_task.png", "label"=>$lang['app_17']);
	$projectSetupTabs["ExternalLinks/index.php"] = array("icon"=>"chain_arrow.png", "label"=>$lang['app_19']);
}
// Default tabs
else {
	$projectSetupTabs["index.php"] = array("icon"=>"house.png", "label"=>$lang['bottom_44']);
	$projectSetupTabs["ProjectSetup/index.php"] = array("icon"=>"clipboard_task.png", "label"=>$lang['app_17']);
	if ($user_rights['design']) {
		$projectSetupTabs["ProjectSetup/other_functionality.php"] = array("icon"=>"wrench.png", "label"=>$lang['setup_68']);
		$projectSetupTabs["ProjectSetup/project_revision_history.php"] = array("icon"=>"gtk_edit.png", "label"=>$lang['app_18']);
	}
}


// Display any warnings for Index or Setup pages
if (PAGE == "index.php" || PAGE == "ProjectSetup/index.php" || PAGE == "ProjectSetup/other_functionality.php")
{
	//Custom index page header note
	if (trim($custom_index_page_note) != '') {
		print "<div class='green notranslate' style='font-size:11px;'>" . nl2br($custom_index_page_note) . "</div>";
	}

	//If system is offline, give message to super users that system is currently offline
	if ($system_offline && $super_user) 
	{
		print  "<div class='red'>
					{$lang['index_38']} 
					<a href='".APP_PATH_WEBROOT."ControlCenter/general_settings.php' 
						style='text-decoration:underline;font-family:verdana;'>{$lang['global_07']}</a>".$lang['period']."
				</div>";
	}

	//If project is offline, give message to super users that project is currently offline
	if (!$online_offline && $super_user) {
		print  "<div class='red'>
					{$lang['index_48']} 
					<a href='".APP_PATH_WEBROOT."ControlCenter/edit_project.php?project=$app_name' 
						style='text-decoration:underline;font-family:verdana;'>{$lang['global_07']}</a>".$lang['period']."
				</div>";
	}

	//If project is linked to a shared demographics project, check if there are any conflicting fields that are duplicated (to prevent export errors)
	if ($is_child) 
	{
		//Get any fields duplicated in both projects
		$duplicate_fields = '';
		$sql = "select field_name from redcap_metadata where project_id = $project_id_parent and field_name != '$table_pk' and field_name in
				(" . pre_query("select field_name from redcap_metadata where project_id = $project_id") . ")
				order by field_order";
		$q = db_query($sql);
		while ($qrow = db_fetch_array($q)){
			$duplicate_fields .= "<div class='notranslate'>{$qrow['field_name']}</div>";
		}
		if ($duplicate_fields != '') {
			print "<table><tr><td class='red'>
					<b>{$lang['global_01']}:</b> {$lang['index_40']} ($is_child_of) {$lang['index_41']}
					<br><br><div style='font-size:10px;'><b>{$lang['index_42']}</b>$duplicate_fields</div>
					</td></tr></table>";
		}
		//Make sure parent is not longitudinal
		if ($longitudinal_parent)
		{
			print  "<div class='red'>
						<b>{$lang['global_01']}{$lang['colon']}</b><br>
						{$lang['index_55']}
					</div>";
		}
	}
	
	// Give warning if beginning survey is used with DDE enabled
	if ($double_data_entry && isset($Proj->forms[$Proj->firstForm]['survey_id']))
	{
		print  "<div class='red'>
					<b>{$lang['global_01']}{$lang['colon']}</b><br>
					{$lang['index_57']}
				</div>";
	}
	
}




?>
<div id="sub-nav" style="margin:30px 0px 15px -20px;padding-left:20px;">
	<ul>
		<?php foreach ($projectSetupTabs as $this_url=>$this_set) { ?>
		<li <?php if ($this_url == PAGE) echo 'class="active"'?>>
			<a href="<?php echo APP_PATH_WEBROOT . $this_url . "?pid=" . PROJECT_ID ?>" style="font-size:13px;color:#393733;padding:4px 9px 7px 10px;">
				<?php if (empty($this_set['icon'])) { ?>
					<img src="<?php echo APP_PATH_IMAGES ?>spacer.gif" style="height:16px;width:1px;">
				<?php } else { ?>
					<img src="<?php echo APP_PATH_IMAGES . $this_set['icon'] ?>" class="imgfix" style="height:16px;width:16px;">
				<?php } ?>
				<?php echo $this_set['label'] ?>
			</a>
		</li>
		<?php } ?>
	</ul>
</div>

<div style="clear:both;"></div>
