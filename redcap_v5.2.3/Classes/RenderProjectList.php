<?php

/**
 * RENDER PROJECT LIST
 * Display all REDCap projects in table format
 */
class RenderProjectList
{

    /*
    * PUBLIC PROPERTIES
    */
     public $pageTitle;
	 
	 
    /*
    * PUBLIC FUNCTIONS
    */

    // @return void
    // @access public
    function renderprojects($section = "") {
	
		global  $display_nonauth_projects, $auth_meth_global, $dts_enabled_global, $google_translate_enabled, 
				$googleTransAnchor, $isIE, $lang, $rc_connection;
			
		require APP_PATH_DOCROOT . "Classes/DTS.php";
		
		// Place all project info into array
		$proj = array();
		// Are we viewing the list from the Control Center?
		$isControlCenter = (strpos(PAGE_FULL, "/ControlCenter/") !== false);
		
		//First get projects list from User Info and User Rights tables
		if ($isControlCenter && isset($_GET['userid']) && $_GET['userid'] != "") {
			// Show just one user's (not current user, since we are super user in Control Center)
			$sql = "select p.project_id, p.project_name, p.app_title, p.status, p.draft_mode, p.google_translate_default, p.surveys_enabled, p.date_deleted 
					from redcap_user_rights u, redcap_projects p 
					where u.project_id = p.project_id and u.username = '{$_GET['userid']}' order by p.project_id";
		} elseif ($isControlCenter && isset($_GET['view_all'])) {
			// Show all projects
			$sql = "select p.project_id, p.project_name, p.app_title, p.status, p.draft_mode, p.google_translate_default, p.surveys_enabled, p.date_deleted 
					from redcap_projects p order by p.project_id";
		} elseif ($isControlCenter && (!isset($_GET['userid']) || $_GET['userid'] == "")) {
			// Show no projects (default)
			$sql = "select 1 from redcap_projects limit 0";
		} else {
			// Show current user's (ignore "deleted" projects)
			$sql = "select p.project_id, p.project_name, p.app_title, p.status, p.draft_mode, p.google_translate_default, p.surveys_enabled, p.date_deleted 
					from redcap_user_rights u, redcap_projects p 
					where u.project_id = p.project_id and u.username = '" . USERID . "' 
					and p.date_deleted is null order by p.project_id";
		}
		$q = db_query($sql);
		while ($row = db_fetch_array($q)) 
		{		
			$proj[$row['project_name']]['project_id'] = $row['project_id'];
			$proj[$row['project_name']]['status'] = $row['status'];
			$proj[$row['project_name']]['date_deleted'] = $row['date_deleted'];
			$proj[$row['project_name']]['draft_mode'] = $row['draft_mode'];
			$proj[$row['project_name']]['surveys_enabled'] = $row['surveys_enabled'];
			$proj[$row['project_name']]['app_title'] = strip_tags(str_replace(array("<br>","<br/>","<br />"), array(" "," "," "), html_entity_decode($row['app_title'], ENT_QUOTES)));				
			$proj[$row['project_name']]['google_translate_default'] = $row['google_translate_default'];					
			if (isset($_GET['no_counts'])) {
				$proj[$row['project_name']]['count'] = "";			
				$proj[$row['project_name']]['field_num'] = "";
			} else {
				$proj[$row['project_name']]['count'] = 0;			
				$proj[$row['project_name']]['field_num'] = 0;
			}
		}
		
		// If DTS is enabled globally, build list of projects to check to see if adjudication is needed
		if ($dts_enabled_global)
		{
			// Set default
			$dts_rights = array();
			// Get projects with DTS enabled
			if (!$isControlCenter) {
				// Where normal user has DTS rights
				$sql = "select p.project_id from redcap_user_rights u, redcap_projects p where u.username = '" . USERID . "' and 
						p.project_id = u.project_id and p.dts_enabled = 1 and 
						p.project_name in ('" . implode("', '", array_keys($proj)) . "')";
				// Don't query using DTS user rights on project if a super user because they might not have those rights in
				// the user_rights table, although once they access the project, they are automatically given those rights
				// because super users get maximum rights for everything once they're inside a project.
				if (!SUPER_USER) {
					$sql .= " and u.dts = 1";
				}
			} else {
				// Super user in Control Center
				$sql = "select project_id from redcap_projects where dts_enabled = 1 
						and project_name in ('" . implode("', '", array_keys($proj)) . "')";
			}
			$q = db_query($sql);
			while ($row = db_fetch_assoc($q))
			{
				$dts_rights[$row['project_id']] = true;
			}
		}
		
		// Project Templates: Build array of all templates so we can put a star by their title for super users only
		$templates = (SUPER_USER) ? ProjectTemplates::getTemplateList() : array();
		
		//Loop through projects and render each table row
		$row_data = array();
		$all_proj_ids = array();
		foreach ($proj as $app_name => $attr) 
		{
			
			//If project is archived, do not show it (unless in Control Center)
			if (!$isControlCenter && $attr['status'] == '3' && !isset($_GET['show_archived'])) continue;
			
			// Store project_id in array to use in AJAX call on pageload
			$all_proj_ids[] = $attr['project_id'];
			
			//Determine if we need to show if a production project's drafted changes are in review
			$in_review = '';
			if ($attr['draft_mode'] == '2') {
				$in_review = "<br><span class='aGridsub' onclick=\"window.location.href='" . APP_PATH_WEBROOT . "Design/project_modifications.php?pid={$attr['project_id']}';return false;\">({$lang['control_center_104']})</span>"; 
			}
			
			//Determine if we need to show Super User functionality (edit db, delete db)
			$settings_link = '';
			if ($isControlCenter) {
				$settings_link = '<div class="aGridsub" style="padding:0 5px 0;text-align:right;">
									<a style="color:#000;font-family:Tahoma;font-size:10px;" href="'.APP_PATH_WEBROOT.'ControlCenter/edit_project.php?project='.$attr['project_id'].'">'.$lang['control_center_106'].'</a> |
									<a style="font-family:Tahoma;font-size:10px;" href="javascript:;" onclick="revHist('.$attr['project_id'].')">'.$lang['app_18'].'</a> | 
									'.($attr['date_deleted'] == "" 
										? '<a style="color:#800000;font-family:Tahoma;font-size:10px;" href="javascript:;" onclick="delete_project('.$attr['project_id'].',this)">'.$lang['control_center_105'].'</a>'
										: '<a style="color:green;font-family:Tahoma;font-size:10px;" href="javascript:;" onclick="undelete_project('.$attr['project_id'].',this)">'.$lang['control_center_375'].'</a> <br>
										   <img src="'.APP_PATH_IMAGES.'bullet_delete.png"> <span style="color:red;">'.$lang['control_center_380'].' '.format_ts_mysql(date('Y-m-d H:i:s', strtotime($attr['date_deleted'])+3600*24*PROJECT_DELETE_DAY_LAG)).'</span>
										   <span style="color:#666;margin:0 3px;">'.$lang['global_46'].'</span> <a style="text-decoration:underline;color:red;font-family:Tahoma;font-size:10px;" href="javascript:;" onclick="delete_project('.$attr['project_id'].',this,1)">'.$lang['control_center_381'].'</a>'
									).'
								</div>'; 
			}
			
			//Determine if we need to append Google Translate anchor (if project-level default language is set)
			$thisGoogAnchor = ($google_translate_enabled && !$isIE && $attr['google_translate_default'] != '') ? $googleTransAnchor . $attr['google_translate_default'] : '';
			
			// DTS Adjudication notification (only on myProjects page)
			$dtsLink = "";
			// Determine if DTS is enabled globally and also for this user on this project
			if ($dts_enabled_global && isset($dts_rights[$attr['project_id']]))
			{
				// Instantiate new DTS object
				$dts = new DTS();
				// Get count of items that needed adjudication
				$recommendationCount = $dts->getPendingCountByProjectId($attr['project_id']);
				// Render a link if items exist
				if ($recommendationCount > 0) {
					$dtsLink = '<div class="aGridsub" style="padding:0 5px;text-align:right;">
									<a title="'.$lang['home_28'].'" href="'.APP_PATH_WEBROOT . 'DTS/index.php?pid='.$attr['project_id'].'" style="text-decoration:underline;color:green; font-family:Tahoma; font-size:10px;"><img src="'.APP_PATH_IMAGES.'tick_small_circle.png"> '.$lang['home_28'].'</a>
								</div>';
				} else {
					$dtsLink = '<div class="aGridsub" style="color:#aaa;padding:0 5px;text-align:right;">'.$lang['home_29'].'</div>';
				}
			}
			
			// If project is a template, then display a star next to title (for super users only)
			$templateIcon = (isset($templates[$attr['project_id']])) 
				? ($templates[$attr['project_id']]['enabled'] ? RCView::img(array('src'=>'star_small2.png','style'=>'margin-left:5px;')) : RCView::img(array('src'=>'star_small_empty2.png','style'=>'margin-left:5px;')))
				: '';
			
			// Title as link
			if ($attr['status'] < 1) { // Send to setup page if in development still
				$title = '<a title="'.htmlspecialchars(cleanHtml2($lang['control_center_374']), ENT_QUOTES).' &quot;'.htmlspecialchars($attr['app_title'], ENT_QUOTES).'&quot;" href="' . APP_PATH_WEBROOT . 'ProjectSetup/index.php?pid=' . $attr['project_id'] . $thisGoogAnchor . '" class="aGrid">'.$attr['app_title'].$templateIcon.$in_review.$settings_link.$dtsLink.'</a>';
			} else {
				$title = '<a title="'.htmlspecialchars(cleanHtml2($lang['control_center_374']), ENT_QUOTES).' &quot;'.htmlspecialchars($attr['app_title'], ENT_QUOTES).'&quot;" href="' . APP_PATH_WEBROOT . 'index.php?pid=' . $attr['project_id'] . $thisGoogAnchor . '" class="aGrid">'.$attr['app_title'].$templateIcon.$in_review.$settings_link.$dtsLink.'</a>';
			}
			
			// Status
			if ($attr['date_deleted'] != "") {
				// If project is "deleted", display cross icon
				$iconstatus = '<img src="'.APP_PATH_IMAGES.'cross.png" title="'.cleanHtml2($lang['global_106']).'">';
			} else {
				// If typical project, display icon based upon status value
				switch ($attr['status']) {
					case 0: //Development
						$iconstatus = '<img src="'.APP_PATH_IMAGES.'page_white_edit.png" title="'.cleanHtml2($lang['global_29']).'">';
						break;
					case 1: //Production
						$iconstatus = '<img src="'.APP_PATH_IMAGES.'accept.png" title="'.cleanHtml2($lang['global_30']).'">';
						break;
					case 2: //Inactive
						$iconstatus = '<img src="'.APP_PATH_IMAGES.'delete.png" title="'.cleanHtml2($lang['global_31']).'">';
						break;
					case 3: //Archived
						$iconstatus = '<img src="'.APP_PATH_IMAGES.'bin_closed.png" title="'.cleanHtml2($lang['global_31']).'">';
						break;
				}
			}
			
			// Project type (survey, forms, survey+forms, etc.)
			switch ($attr['surveys_enabled']) {
				case 0: // Forms
					$icontype = '<img title="'.cleanHtml2($lang['global_61']).'" src="'.APP_PATH_IMAGES.'blog.png">';
					break;
				default: // Survey(s) + Forms
					$icontype = '<img title="'.cleanHtml2($lang['global_61']).'" src="'.APP_PATH_IMAGES.'blog.png"> <img title="'.cleanHtml2($lang['survey_437']).'" src="'.APP_PATH_IMAGES.'send.png">';
			}
			
			$row_data[] = array($title, 
								"<span class='pid-cnt-p' id='pid-cnt-{$attr['project_id']}'><span class='pid-cnt'>Loading...</span></span>", 
								"<span class='pid-cnt-p' id='pid-cntf-{$attr['project_id']}'><span class='pid-cnt'>Loading...</span></span>", 
								$icontype, 
								$iconstatus);
		}
		
		// If user has access to zero projects
		if (empty($row_data)) {
			$row_data[] = array(($isControlCenter ? $lang['home_37'] : $lang['home_38']),"","","","");
		}
		
		// Render table
		$tableHeader = $isControlCenter ? $lang['home_30'] : $lang['home_22'];
		$width = 750; // Whole table width
		$width2 = 40; // Records
		$width3 = 40; // Fields
		$width5 = 34; // Type
		$width4 = 32; // Status
		if ($section == "control_center") $width = 570;
		$width1 = $width - $width2 - $width3 - $width4 - $width5 - 61; // DB name
		$col_widths_headers[] = array($width1, "<b>$tableHeader</b>");
		$col_widths_headers[] = array($width2, $lang['home_31'], "center", "int");
		$col_widths_headers[] = array($width3, $lang['home_32'], "center", "int");
		$col_widths_headers[] = array($width5, $lang['home_39'], "center");
		$col_widths_headers[] = array($width4, $lang['home_33'], "center");
		renderGrid("proj_table", "", $width, 'auto', $col_widths_headers, $row_data);
		
		// Also send an AJAX request to retrieve the record counts right after the page loads
		?>
		<script type="text/javascript">
		$(function(){
			$.post(app_path_webroot+'ProjectGeneral/project_stats_ajax.php',{ pids: '<?php echo implode(",", $all_proj_ids) ?>' }, function(data){
				if (data=='0') {
					// Set all values to 0
					$('.pid-cnt-p').html('0');
				} else {
					var pidPair;
					var pidPairs = data.split(',');
					// Set values for each project
					for (var i=0; i<pidPairs.length; i++){
						pidPair = pidPairs[i].split(':');
						$('#pid-cnt-'+pidPair[0]).html( pidPair[1] );
						$('#pid-cntf-'+pidPair[0]).html( pidPair[2] );
					}
					// Pick up any projects that were not set because both values are 0
					$('.pid-cnt').html('0').removeClass('pid-cnt');
				}
			});
		});
		</script>
		<?php
		
		
		//Display any Public projects (using "none" auth) if flag is set in config table
		if ($display_nonauth_projects && $auth_meth_global != "none" && !$isControlCenter) {
			
			// Get all public dbs that the user does not already have access to (to prevent duplication in lists)
			$sql = "select project_id, project_name, app_title from redcap_projects where auth_meth = 'none' 
					and status in (1, 0) and project_id not in 
					(0,".pre_query("select project_id from redcap_user_rights where username = '" . prep(USERID) . "'").") 
					order by trim(app_title)";
			$q = db_query($sql, $rc_connection);
			
			// Only show this section if at least one public project exists
			if (db_num_rows($q) > 0) 
			{
				print  "<p style='margin-top:40px;'>{$lang['home_34']}";
				//Give extra note to super user
				if (SUPER_USER) {
					print  "<i>{$lang['home_35']}</i>";
				}
				print  "</p>";
				
				$pubList = array();
				while ($attr = db_fetch_assoc($q)) {				
					//Title
					$pubList[] = array('<a title="'.htmlspecialchars(cleanHtml2($lang['control_center_374']), ENT_QUOTES).' &quot;'.$attr['app_title'].'&quot;" href="' . APP_PATH_WEBROOT . 'index.php?pid=' . $attr['project_id'] . '" class="aGrid">'.$attr['app_title'].'</a>');
				}
				
				$col_widths_headers = array(  
										array(740, "<b>{$lang['home_36']}</b>")
									);
				renderGrid("proj_table_pub", "", 750, 'auto', $col_widths_headers, $pubList);
				
			}
				
		}
		
    }
    
}    
