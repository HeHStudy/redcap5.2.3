<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


/**
 * EXTERNAL RESOURCES
 */
class ExternalLinks
{
	// Array with the resources defined by the user
	private $resources = null;
	
	// Load resources defined. If provide ext_id, then only return that Resource.
	public function loadResources($ext_id=null)
	{
		// Reset this array before we fill it (in case we're reloading it)
		$this->resources = array();
		// If ext_id is defined, then add it to sql
		$sql_ext_id = (is_numeric($ext_id) ? "ext_id = $ext_id" : "project_id " . (is_numeric(PROJECT_ID) ? " = ".PROJECT_ID : "is null"));
		// Query to get resources from table
		$sql = "select * from redcap_external_links where $sql_ext_id order by link_order";
		$q = db_query($sql);
		if (db_num_rows($q) > 0)
		{
			while ($row = db_fetch_assoc($q))
			{
				// Add Resource to array
				$this->resources[$row['ext_id']] = array(	
					'label'  => trim(strip_tags(label_decode($row['link_label']))), 
					'url' => trim(html_entity_decode($row['link_url'], ENT_QUOTES)), 
					'order' => $row['link_order'],
					'link_type' => $row['link_type'],
					'open_new_window' => $row['open_new_window'],
					'link_to_project_id' => $row['link_to_project_id'],
					'user_access' => $row['user_access'],
					'append_record_info' => $row['append_record_info'],
					'append_pid' => $row['append_pid']
				);		
			}
			// Do a quick check to make sure the resources are in the right order (if not, will fix it)
			if ($ext_id == null) $this->checkOrder();
		}
	}
	
	// Retrieve all resources defined. Return as array.
	public function getResources()
	{
		// Load the resources
		if ($this->resources == null) $this->loadResources();
		// Return the resources
		return $this->resources;
	}
	
	// Retrieve a single Resource defined. Return as array.
	public function getResource($ext_id)
	{
		// Load the Resource
		if ($this->resources == null) $this->loadResources($ext_id);
		// Return the Resource
		return (isset($this->resources[$ext_id]) ? $this->resources[$ext_id] : false);
	}
		
	// Load the table data for displaying the resources
	private function loadResourcesTable()
	{
		global $lang, $api_enabled, $Proj;
		// Determine if project has DAGs (if so, show the DAG user access option)
		$dags = is_numeric(PROJECT_ID) ? $Proj->getGroups() : array();
		$hasDags = (!empty($dags));
		// Create the table for displaying the resources
		$resourcesTableData = array();
		foreach ($this->getResources() as $ext_id=>$Resource_attr)
		{
			// If link goes directly to a REDCap project, then get the project title to display instead of the URL
			if ($Resource_attr['link_type'] == "REDCAP_PROJECT" && is_numeric($Resource_attr['link_to_project_id']))
			{
				$sql = "select app_title from redcap_projects where project_id = " . $Resource_attr['link_to_project_id'];
				$q = db_query($sql);
				if (db_num_rows($q) > 0)
				{
					// Sanitize the title
					$this_title = db_result($q, 0);
					$this_title = strip_tags(str_replace("\n", " ", br2nl(label_decode($this_title))));
					// Replace URL with title
					$Resource_attr['url'] = "<span style='color:#777;'>{$lang['extres_24']}</span> $this_title";
				}
			}
			
			// Add Resource as row
			$resourcesTableData[] = array(
				"<div id='Resourcedrag_{$ext_id}'><span style='display:none;'>{$ext_id}</span></div>", 
				"<div id='Resourceorder_{$ext_id}' class='Resourcenum'>{$Resource_attr['order']}</div>", 
				"<div id='Resourcename_{$ext_id}' rid='{$ext_id}' class='editname'>{$Resource_attr['label']}</div>", 
				"<div id='Resourceurl_{$ext_id}' rid='{$ext_id}' class='editurl' " . (is_numeric($Resource_attr['link_to_project_id']) ? "pid='{$Resource_attr['link_to_project_id']}'" : "") . ">
					{$Resource_attr['url']}
				</div>", 
				"<div id='Resourcelinktype_{$ext_id}' rid='{$ext_id}' style='padding:5px 0 0;'>
					<select style='font-size:11px;' id='input_linktype_id_{$ext_id}' onchange='saveResourceLinkType({$ext_id})'>
						<option value='LINK' ".($Resource_attr['link_type'] == "LINK" ? "selected" : "").">{$lang['extres_25']}</option>
						".($api_enabled ? "<option value='POST_AUTHKEY' ".($Resource_attr['link_type'] == "POST_AUTHKEY" ? "selected" : "").">{$lang['extres_26']}</option>" : "")."
						<option value='REDCAP_PROJECT' ".($Resource_attr['link_type'] == "REDCAP_PROJECT" ? "selected" : "").">{$lang['extres_27']}</option>
					</select>
					<div id='linktype_save_{$ext_id}' class='hidden_save_div'>{$lang['design_243']}</div>
				</div>",  
				"<div id='Resourcelinkusers_{$ext_id}' style='padding:5px 0 0;text-align:left;'>
					<input type='radio' name='input_linkusers_id_{$ext_id}' id='input_linkusers_all_id_{$ext_id}' ".($Resource_attr['user_access'] == "ALL" ? "checked" : "")." onclick='selectResourceUsers({$ext_id});'>{$lang['reporting_16']}<br />
					<input type='radio' name='input_linkusers_id_{$ext_id}' id='input_linkusers_selected_id_{$ext_id}' ".($Resource_attr['user_access'] == "SELECTED" ? "checked" : "")." onclick='selectResourceUsers({$ext_id});'><a href='javascript:;' onclick=\"checkUserAccessVal({$ext_id});\" style='font-size:11px;'>{$lang['extres_28']}</a>
					".($hasDags ? "<br /><input type='radio' name='input_linkusers_id_{$ext_id}' id='input_linkdags_selected_id_{$ext_id}' ".($Resource_attr['user_access'] == "DAG" ? "checked" : "")." onclick='selectResourceDags({$ext_id});'><a href='javascript:;' onclick=\"checkDagAccessVal({$ext_id});\" style='font-size:11px;'>{$lang['extres_52']}</a>" : "")."
					<div id='exclude_link_{$ext_id}' style='padding-top:5px;".(is_numeric(PROJECT_ID) ? "display:none;" : "")."'>
						<a href='javascript:;' onclick='openExcludeProjPopup({$ext_id})' style='text-decoration:underline;font-size:11px;color:#800000;'>{$lang['extres_61']}</a>
					</div>
					<div id='linkusers_save_{$ext_id}' class='hidden_save_div' style='text-align:center;'>{$lang['design_243']}</div>
				</div>", 
				"<div id='Resourcenewwin_{$ext_id}' rid='{$ext_id}'>
					<input id='input_newwin_id_{$ext_id}' onclick='saveResourceNewWin({$ext_id})' type='checkbox' ".($Resource_attr['open_new_window'] ? "checked" : "").">
					<div id='newwin_save_{$ext_id}' class='hidden_save_div'>{$lang['design_243']}</div>
				</div>", 
				"<div id='Resourceappendrec_{$ext_id}' rid='{$ext_id}'>
					<input id='input_appendrec_id_{$ext_id}' type='checkbox' onclick='saveAppendRec({$ext_id})' ".($Resource_attr['append_record_info'] ? "checked" : "").">
					<div id='appendrec_save_{$ext_id}' class='hidden_save_div'>{$lang['design_243']}</div>
				</div>", 
				"<div id='Resourceappendpid_{$ext_id}' rid='{$ext_id}'>
					<input id='input_appendpid_id_{$ext_id}' type='checkbox' onclick='saveAppendPid({$ext_id})' ".($Resource_attr['append_pid'] ? "checked" : "").">
					<div id='appendpid_save_{$ext_id}' class='hidden_save_div'>{$lang['design_243']}</div>
				</div>",
				"<div id='Resourcedel_{$ext_id}'>
					<a href='javascript:;' onclick='deleteResource({$ext_id})'><img src='".APP_PATH_IMAGES."cross.png'></a>
				</div>"
			);
		}
		// Add extra row for adding new link
		$resourcesTableData[] = array(
			"<div id='Resourcedrag_0'></div>", 
			"<div id='Resourceorder_0' style='padding:0;'><button style='vertical-align:middle;font-size:11px;' onclick='addNewResource();'>{$lang['design_171']}</button></div>", 
			"<div id='Resourcename_0' class='newname'>
				<input id='input_Resourcename_id_0' type='text' class='x-form-text x-form-field' style='vertical-align:middle;width:95%;' value=''><br>
				{$lang['extres_29']}
			</div>", 
			"<div id='Resourceurl_0' class='newurl'>
				<input id='input_Resourceurl_id_0' type='text' class='x-form-text x-form-field' style='width:95%;' value=''><br>
				{$lang['extres_30']}<br>
				(e.g. http://www.mysite.com/mypage.html)
			</div>
			<div class='newproject' style='display:none;' rid='0' >
				 <span style='color:#777;'>{$lang['extres_24']}</span> <span id='new_projtitle_id_0'></span>
				 <input id='input_Resourceprojectlink_id_0' type='hidden' value=''>
			</div>", 
			"<div id='Resourcelinktype_0' style='padding:0;'>
				<select style='font-size:11px;' id='input_linktype_id_0' onchange='setNewLinkType(this.value)'>
					<option value='LINK' selected>{$lang['extres_25']}</option>
					".($api_enabled ? "<option value='POST_AUTHKEY'>{$lang['extres_26']}</option>" : "")."
					<option value='REDCAP_PROJECT'>{$lang['extres_27']}</option>
				</select>
			</div>
			<div>&nbsp;</div>",   
			"<div id='Resourcelinkusers_0' style='padding:5px 0 0;text-align:left;'>
				<input type='radio' name='input_linkusers_id_0' id='input_linkusers_all_id_0' checked onclick='selectResourceUsers(0);'>{$lang['reporting_16']}<br />
				<input type='radio' name='input_linkusers_id_0' id='input_linkusers_selected_id_0' onclick='selectResourceUsers(0);'><a href='javascript:;' onclick=\"checkUserAccessVal(0);\" style='font-size:11px;'>{$lang['extres_28']}</a>
				".($hasDags ? "<br /><input type='radio' name='input_linkusers_id_0' id='input_linkdags_selected_id_0' onclick='selectResourceDags(0);'><a href='javascript:;' onclick=\"checkDagAccessVal(0);\" style='font-size:11px;'>{$lang['extres_52']}</a>" : "")."
				<div id='exclude_link_0' style='padding-top:5px;".((defined('PROJECT_ID') && is_numeric(PROJECT_ID)) ? "display:none;" : "")."'>
					<a href='javascript:;' onclick='openExcludeProjPopup(0)' style='text-decoration:underline;font-size:11px;color:#800000;'>{$lang['extres_61']}</a>
					<input type='hidden' id='prev_excl_proj_0'>
				</div>
				<div id='linkusers_save_0' class='hidden_save_div' style='text-align:center;'>{$lang['design_243']}</div>
			</div>",
			"<div id='Resourcenewwin_0' style='padding:0;'><input id='input_newwin_id_0' type='checkbox'></div>",
			"<div id='Resourceappendrec_0' style='padding:0;'><input id='input_appendrec_id_0' type='checkbox'></div>", 
			"<div id='Resourceappendpid_0' style='padding:0;'><input id='input_appendpid_id_0' type='checkbox'></div>", 
			"<div id='Resourcedel_0' style='padding:0;'></div>"
		);
		// Set up the table headers
		$resourcesTableHeaders = array(
			array(30, "", "center"),
			array(42, "<b>{$lang['extres_31']} #</b>", "center", "int"),
			array(156, "<b>{$lang['extres_32']}</b>"),
			array(212, "<b>{$lang['extres_33']}</b>"),
			array(110, "<b>{$lang['extres_34']}</b>", "center"),
			array(95, "<b>{$lang['extres_35']}</b>", "center"),
			array(55, "<div class='ww' style='padding:0;'><span style='font-size:10px;'>{$lang['extres_36']}</span></div>", "center"),
			array(75, "<div class='ww' style='padding:0;'><span style='font-size:10px;'>{$lang['extres_01']}</span> <a href='javascript:;' id='append_rec_info_trigger' style='font-size:12px;color:red;' title=\"".cleanHtml2($lang['form_renderer_02'])."\"><img src='".APP_PATH_IMAGES."help.png' style='vertical-align:middle;'></a></div>", "center"),
			array(75, "<div class='ww' style='padding:0;'><span style='font-size:10px;'>{$lang['extres_65']}</span> <a href='javascript:;' id='append_pid_trigger' style='font-size:12px;color:red;' title=\"".cleanHtml2($lang['form_renderer_02'])."\"><img src='".APP_PATH_IMAGES."help.png' style='vertical-align:middle;'></a></div>", "center"),
			array(38, "<span style='font-size:10px;'>{$lang['global_19']}</span>", "center")
		);
		// Return the table headers and data
		return array($resourcesTableHeaders, $resourcesTableData);
	}
		
	// Display the table data for displaying the resources
	public function displayresourcesTable()
	{	
		// Load the resources table data
		list ($resourcesTableHeaders, $resourcesTableData) = $this->loadResourcesTable();
		// Get html for the resources table
		$table_html = renderGrid("resources", "", 1009, "auto", $resourcesTableHeaders, $resourcesTableData, true, false, false);		
		// Return the html
		return $table_html;
	}
	
	// Check the order of the resources for link_order to make sure they're not out of order
	public function checkOrder()
	{
		// Store the sum of the link_order's and count of how many there are
		$sum   = 0;
		$count = 0;
		// Loop through existing resources
		foreach ($this->getResources() as $ext_id=>$attr)
		{
			// Add to sum
			$sum += $attr['order']*1;
			// Increment count
			$count++;
		}
		// Now perform check (use simple math method)
		if ($count*($count+1)/2 != $sum)
		{
			// Out of order, so reorder
			$this->reorder();
		}
	}
	
	// Reset the order of the resources for link_order in the table
	public function reorder()
	{
		// Initial value
		$order = 1;
		// Loop through existing resources
		foreach (array_keys($this->getResources()) as $ext_id)
		{
			$sql = "update redcap_external_links set link_order = $order where 
					project_id " . (is_numeric(PROJECT_ID) ? " = ".PROJECT_ID : "is null") . " and ext_id = $ext_id";
			$q = db_query($sql);
			// Increment the order
			$order++;
		}
		// Now reload the resources in the class again
		$this->loadResources();
	}
	
	// Get the HTML for displaying the External Resources as a panel on the left-hand project menu
	public function renderHtmlPanel()
	{
		global $lang, $longitudinal, $Proj, $user_rights, $isAjax;
		// Store html in string
		$externalLinkage = "";
		$appLinkage = "";
		// Check if we're in a plugin OR on a data entry form viewing a record (in case it needs to be appended to URL)
		$appendUrl = "";
		if ((defined("PLUGIN") && isset($_GET['record'])) || (defined("PAGE") && PAGE == "DataEntry/index.php" && isset($_GET['id'])))
		{
			// If longitudinal, get list of all unique event names
			$appendUrlEvent = "";
			if ($longitudinal) 
			{
				// Get current event_id
				$event = (defined("PLUGIN") ? $_GET['event'] : $Proj->getUniqueEventNames($_GET['event_id']));
				// Append this event's unique name
				if (!is_array($event) && !empty($event)) {
					$appendUrlEvent = "&event=$event";
				}
			}
			// Set entire string to append to URL
			$appendUrl = "record=" . (defined("PLUGIN") ? $_GET['record'] : $_GET['id']) . $appendUrlEvent;
		}
		// Determine if project has DAGs (if so, show the DAG user access option)
		$dag_sql = ($user_rights['group_id'] == '') ? "" : "d.group_id = {$user_rights['group_id']} or";
		// Modify query for super users, who should be able to see all project-level links (but not all global level ones - would be too many)
		if (SUPER_USER) {
			$sql_restrict_users = "and (p.project_id is not null or (p.project_id is null 
								   and (u.username = '" . USERID . "' or p.user_access = 'ALL')))";
		} else {
			$sql_restrict_users = "and ($dag_sql u.username = '" . USERID . "' or p.user_access = 'ALL')";
		}
		// Query to get resources that ONLY this user can see
		$sql = "select distinct p.ext_id, p.link_url, p.link_label, p.link_type, p.open_new_window, p.append_pid,
				p.link_to_project_id, p.append_record_info, p.project_id, e.project_id as exclude_project_id
				from redcap_external_links p 
				left join redcap_external_links_users u on p.ext_id = u.ext_id 
				left join redcap_external_links_dags d on d.ext_id = p.ext_id 
				left join redcap_external_links_exclude_projects e on e.ext_id = p.ext_id and e.project_id = " . PROJECT_ID . "
				where (p.project_id = " . PROJECT_ID . " or p.project_id is null) 
				$sql_restrict_users
				order by p.project_id, p.link_order";
		$q = db_query($sql);
		if (db_num_rows($q) > 0)
		{
			// Obtain the authentication key to be sent to the external site
			$authKey = $this->getExternalAuthKey();
			// Loop through all links
			while ($row = db_fetch_assoc($q))
			{
				// If this is a global project bookmark and we're exluding this project, then don't show it
				if (!is_numeric($row['project_id']) && $row['exclude_project_id'] == PROJECT_ID) {
					continue;
				}
				// Santize link label
				$row['link_label'] = strip_tags(label_decode($row['link_label']));
				// Check if we need to append record info for this link if we're on the data entry form
				if ($row['append_record_info'] && $appendUrl != "")
				{
					// Add record info string to query string
					$row['link_url'] .= ((strpos($row['link_url'], '?') === false) ? '?' : '&') . $appendUrl;
				}
				// Check if we need to append project id for this link
				if ($row['append_pid'])
				{
					// Add project_id to query string
					$row['link_url'] .= ((strpos($row['link_url'], '?') === false) ? '?' : '&') . "pid=" . PROJECT_ID;
				}
				// Set image
				if (is_numeric($row['project_id'])) {
					$extLinkImg = "&nbsp;<img src='".APP_PATH_IMAGES."extlink.gif'>";
				} else {
					$extLinkImg = "<img src='".APP_PATH_IMAGES."application_link.png' class='imgfix'>&nbsp;";
				}
				// Check if opening new window
				$openNewWin = ($row['open_new_window'] ? "target='_blank'" : "");
				// Build mini-form for sending authkey via Post
				if ($row['link_type'] == 'POST_AUTHKEY')
				{
					// Make sure URL has a value
					if (empty($row['link_url'])) continue;
					// Set form name
					$extLinkFormName = "redcap_extlink_" . $row['ext_id'];
					// If opening new window, then append Onclick with form post javascript
					$submitForm = $row['open_new_window'] ? " $('#{$extLinkFormName}').submit();" : "";
					// Filter/replace any troublesome characters
					$row['link_url'] = str_replace(array(" ","\""), array("%20","&quot;"), $row['link_url']);
					// Link HTML
					$this_link =   "<form $openNewWin id='$extLinkFormName' action='{$row['link_url']}' method='post' enctype='multipart/form-data'>
										<input type='hidden' name='authkey' value='$authKey'>
										$extLinkImg <a href='javascript:;' onclick=\"ExtLinkClickThru({$row['ext_id']},{$row['open_new_window']},'".cleanHtml($row['link_url'])."','$extLinkFormName'); $submitForm return true;\">{$row['link_label']}</a>
									</form>";
				}
				// Simple Link OR REDCap project
				else
				{
					// REDCap project
					if ($row['link_type'] == 'REDCAP_PROJECT') {
						// Make sure project_id is real number
						if (!is_numeric($row['link_to_project_id'])) continue;
						// Change URL to the project's URL
						$row['link_url'] = APP_PATH_WEBROOT . "index.php?pid=" . $row['link_to_project_id'] . ($appendUrl == "" ? "" : "&".$appendUrl);
					} else {
						// Make sure URL has a value
						if (empty($row['link_url'])) continue;
					}
					// Filter/replace any troublesome characters
					$row['link_url'] = str_replace(array(" ","\""), array("%20","&quot;"), $row['link_url']);
					// If opening new window, then set the URL as the HREF
					$href = $row['open_new_window'] ? $row['link_url'] : 'javascript:;';
					// Link HTML
					$this_link = "$extLinkImg <a href=\"$href\" $openNewWin onclick=\"ExtLinkClickThru({$row['ext_id']},{$row['open_new_window']},'".cleanHtml($row['link_url'])."','');return true;\">{$row['link_label']}</a>";
				}
				// Add link to menu
				if (is_numeric($row['project_id'])) {
					$externalLinkage .= "<div class='hang'>$this_link</div>";
				} else {
					$appLinkage .= "<div class='hang'>$this_link</div>";
				}
			}
		}
		## Return the HTML for the panel
		$output = '';
		// External link apps (global project bookmarks) - don't update these if merely modifying project-level Ext Links
		if ($appLinkage != "" && !$isAjax) 
		{
			$output .= '<div id="global_ext_links" class="menubox" style="border-bottom:1px solid #D0D0D0;padding-left:9px;padding-top:0;margin-top:0;"><div class="menubox" style="padding-top:0;margin-top:0;">'.$appLinkage.'</div></div>';
			// CSS to slight alter Applications menu box to make these links appear seamlessly in that box
			$output .= "<style type='text/css'>
						#app_panel .x-panel-body { border-bottom: 0; }
						#app_panel .menubox { padding-bottom: 0; }
						</style>";
		}
		// External Links (project-level)
		if ($externalLinkage != "")
		{
			$output .= renderPanel($lang['app_19'], $externalLinkage, "extres_panel");
		}
		return $output;
	}
	
	// Display the contents of the dialog for CHOOSING A PROJECT TO LINK TO
	public function displayProjectListDialog($pid=null)
	{
		global $lang;
		if (defined('PROJECT_ID') && is_numeric(PROJECT_ID)) {
			// Obtain list of all projects the user has access to	
			$sql = "select p.project_id, trim(p.app_title) as app_title from redcap_projects p, redcap_user_rights u 
					where p.project_id = u.project_id and u.username = '" . USERID . "' order by trim(p.app_title)";
		} else {
			// Obtain list of ALL projects (exclude archived and inactive projects)
			$sql = "select p.project_id, trim(p.app_title) as app_title from redcap_projects p 
					where p.status in (0,1) order by trim(p.app_title)";
		}
		$q = db_query($sql);
		$projects = array();
		while ($row = db_fetch_assoc($q))
		{
			$row['app_title'] = strip_tags(str_replace('<br>', ' ', $row['app_title']));
			// If title is too long, then shorten it
			if (strlen($row['app_title']) > 100) {
				$row['app_title'] = trim(substr($row['app_title'], 0, 90)) . " ... " . trim(substr($row['app_title'], -15));
			}					
			if ($row['app_title'] == "") {
				$row['app_title'] = $lang['create_project_82'];
			}
			$projects[$row['project_id']] = $row['app_title'];
		}		
		// Display the instructions and drop-down to choose a project
		?>
		<p><?php echo $lang['extres_37'] ?></p>
		<p>
			<b><?php echo $lang['extres_38'] ?></b><br />
			<select id='choose_project_select'>
				<option value=''>-- <?php echo $lang['extres_39'] ?> --</option>
				<?php foreach ($projects as $this_pid=>$this_title) { ?>
					<option value='<?php echo $this_pid ?>' <?php if ($this_pid == $pid) echo "selected" ?>><?php echo $this_title ?></option>
				<?php } ?>
			</select>
		</p>
		<?php
	}	
	
	// Obtain array of all projecs excluded for a given project bookmark
	private function getExcludedProjects($ext_id)
	{
		$sql = "select project_id from redcap_external_links_exclude_projects where ext_id = {$ext_id}";
		$q = db_query($sql);
		$projects = array();
		while ($row = db_fetch_assoc($q))
		{
			$projects[] = $row['project_id'];
		}
		return $projects;
	}
	
	// Display the contents of the dialog for CHOOSING A PROJECT TO EXCLUDE
	public function displayExcludeProjDialog($ext_id)
	{
		global $lang;
		// Obtain list of ALL projects (exclude archived and inactive projects)
		$sql = "select p.project_id, trim(p.app_title) as app_title from redcap_projects p 
				where p.status in (0,1) order by trim(p.app_title)";
		$q = db_query($sql);
		$projects = array();
		while ($row = db_fetch_assoc($q))
		{
			$row['app_title'] = strip_tags(str_replace('<br>', ' ', $row['app_title']));
			// If title is too long, then shorten it
			if (strlen($row['app_title']) > 100) {
				$row['app_title'] = trim(substr($row['app_title'], 0, 90)) . " ... " . trim(substr($row['app_title'], -15));
			}					
			if ($row['app_title'] == "") {
				$row['app_title'] = $lang['create_project_82'];
			}
			$projects[$row['project_id']] = $row['app_title'];
		}
		// Get list of all excluded projects for this project bookmark
		$excluded_projects = ($ext_id == '0') ? array() : $this->getExcludedProjects($ext_id);
		// Display the instructions and drop-down to choose a project
		?>
		<p><?php echo $lang['extres_60'] ?></p>
		<p style="color:#800000;font-size:14px;">
			<?php echo $lang['extres_63'] ?>
			"<span id="linkLabelPrefill" style="font-weight:bold;"></span>"
			<?php echo $lang['extres_64'] ?>
		</p>
		<div id='choose_project_exclude' style="padding:10px;border:1px solid #ddd;background-color:#f5f5f5;overflow-y:auto;height:300px;">
			<div style="padding:0 0 10px;">
				<b><?php echo $lang['extres_59'] ?></b>
				<span id="select_links_dags" style="margin-left:20px;">
					<a href="javascript:;" style="font-size:11px;" onclick="excludeAllProjects(1)"><?php echo $lang['data_export_tool_52'] ?></a> &nbsp;|&nbsp;
					<a href="javascript:;" style="font-size:11px;"onclick="excludeAllProjects(0)"><?php echo $lang['data_export_tool_53'] ?></a>
				</span>
			</div>
			<?php foreach ($projects as $this_pid=>$this_title) { ?>
				<div>
					<input type="checkbox" id="pid_<?php echo $this_pid ?>" pid="<?php echo $this_pid ?>" <?php if (in_array($this_pid, $excluded_projects)) echo "checked" ?>> 
					<?php echo $this_title ?>
				</div>
			<?php } ?>
		</div>
		<?php
	}	

	// Get authkey: For external websites to determine if a REDCap user's session is still active
	private function getExternalAuthKey()
	{
		global $user_rights, $redcap_version, $Proj;
		// Get session id
		$session_id = substr(session_id(), 0, 32);
		/* 
		// Get session expiration time from session table
		$sql = "select session_expiration from redcap_sessions where session_id = '$session_id' limit 1";
		$q = db_query($sql);
		$session_expiration_time = db_result($q, 0);
		*/
		// Get DAG info (if applicable)
		$data_access_group_name = ($user_rights['group_id'] == "") ? "" : $Proj->getGroups($user_rights['group_id']);
		// Set array to encrypt
		$encryptThis = array(
			// 'seconds_till_expiration' => (strtotime($session_expiration_time) - strtotime(NOW)),
			'session_id' => $session_id,
			'project_id' => (is_numeric(PROJECT_ID) ? PROJECT_ID : ""),
			'username' => USERID,
			'data_access_group_id' => $user_rights['group_id'],
			'data_access_group_name' => label_decode($data_access_group_name),
			'callback_url' => APP_PATH_WEBROOT_FULL . "redcap_v{$redcap_version}/index.php?pid=" . PROJECT_ID
		);
		// Serialize string and then encrypt it
		return encrypt(serialize($encryptThis));
	}
	
	// Display list of all DAGs in the project with checkbox for choosing them
	public function displayDagList($checkedDags=array())
	{
		global $lang, $Proj;
		// Check if project has DAGs
		$dags = is_numeric(PROJECT_ID) ? $Proj->getGroups() : array();
		if (!empty($dags)) 
		{
			// Display the DAG list
			?>		
			<div style="padding:3px 3px 6px 0;">
				<button id="save_dag_btn" onclick="saveResourceDags()" style="font-size:11px;"><?php echo $lang['designate_forms_13'] ?></button>
				<button id="cancel_dag_btn" onclick="cancelResourceDags()" style="font-size:11px;"><?php echo $lang['calendar_popup_01'] ?></button>&nbsp;
				<img id="save_dag_progress" src="<?php echo APP_PATH_IMAGES ?>progress_circle.gif" class="imgfix" style="display:none;">
				<img id="save_dag_saved" src="<?php echo APP_PATH_IMAGES ?>accept.png" class="imgfix" style="display:none;">&nbsp;
				<span id="select_links_dags">
					<a href="javascript:;" style="font-size:10px;" onclick="selectAllDags(1)"><?php echo $lang['data_export_tool_52'] ?></a> &nbsp;|&nbsp;
					<a href="javascript:;" style="font-size:10px;"onclick="selectAllDags(0)"><?php echo $lang['data_export_tool_53'] ?></a>
				</span>
			</div>
			<?php
			// Loop through DAGs			
			foreach ($dags as $group_id=>$group_name)
			{
				?><div><input type="checkbox" class="res_group" gid="<?php echo $group_id ?>" <?php if ($checkedDags == 'ALL' || in_array($group_id, $checkedDags)) echo "checked" ?>> <?php echo $group_name ?></div><?php
			}
		}
		
		
	
	}
	
	// Display list of all users in the project with checkbox for choosing them
	public function displayUserList($checkedUsers=array())
	{
		global $lang;
		// First, obtain list of all users with name/email	
		$sql = "select distinct r.username, i.user_firstname, i.user_lastname from redcap_user_rights r left join redcap_user_information i 
				on r.username = i.username ".((defined('PROJECT_ID') && is_numeric(PROJECT_ID)) ? "where r.project_id = " . PROJECT_ID : "") . " 
				order by r.username";
		$q = db_query($sql);
		$users = array();
		while ($row = db_fetch_assoc($q))
		{
			// Get first/last name
			$name = empty($row['user_firstname']) ? "<i style='color:#555;'>{$lang['extres_03']}</i>" : trim("{$row['user_firstname']} {$row['user_lastname']}");
			// Add to array
			$users[$row['username']] = $name;
		}
		// Display the user list
		?>		
		<div style="padding:3px 3px 6px 0;">
			<button id="save_user_btn" onclick="saveResourceUsers()" style="font-size:11px;"><?php echo $lang['designate_forms_13'] ?></button>
			<button id="cancel_user_btn" onclick="cancelResourceUsers()" style="font-size:11px;"><?php echo $lang['calendar_popup_01'] ?></button>&nbsp;
			<img id="save_user_progress" src="<?php echo APP_PATH_IMAGES ?>progress_circle.gif" class="imgfix" style="display:none;">
			<img id="save_user_saved" src="<?php echo APP_PATH_IMAGES ?>accept.png" class="imgfix" style="display:none;">&nbsp;
			<span id="select_links">
				<a href="javascript:;" style="font-size:10px;" onclick="selectAllUsers(1)"><?php echo $lang['data_export_tool_52'] ?></a> &nbsp;|&nbsp;
				<a href="javascript:;" style="font-size:10px;"onclick="selectAllUsers(0)"><?php echo $lang['data_export_tool_53'] ?></a>
			</span>
		</div>
		<?php foreach ($users as $user=>$name) { ?>
			<div><input type="checkbox" class="res_user" uid="<?php echo $user ?>" <?php if ($checkedUsers == 'ALL' || in_array($user, $checkedUsers)) echo "checked" ?>> <?php echo $user ?> (<?php echo $name ?>)</div>
		<?php }
	}
	
}
