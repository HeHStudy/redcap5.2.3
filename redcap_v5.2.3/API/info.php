<?php
define("ROOT", dirname(dirname(__FILE__)));

// call config file (based upon if we're in a project or not)
if (isset($_GET['pnid']) || isset($_GET['pid'])) {
	include_once ROOT . '/Config/init_project.php';
} else {
	include_once ROOT . '/Config/init_global.php';
}

// Initialize page display object
$objHtmlPage = new HtmlPage();
$objHtmlPage->addExternalJS(APP_PATH_JS . "base.js");
$objHtmlPage->PrintHeaderExt();

# Get all the usernames, if super user
if ($super_user)
{
	$all_users = array();
	$sql = "select distinct username from redcap_user_rights order by username";					
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q)) {
		$row['username'] = trim($row['username']);
		$all_users[strtolower($row['username'])] = array('username'=>$row['username']);
	}
	$sql = "select * from redcap_user_information order by username, user_lastname, user_firstname";					
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q)) {
		$all_users[strtolower($row['username'])] = array('username'=>$row['username'], 'user_firstname'=>$row['user_firstname'], 
			'user_lastname'=>$row['user_lastname'], 'user_email'=>$row['user_email']);
	}
	// Order array
	ksort($all_users, SORT_STRING);
} else {
	// Normal users can only see themselves
	$all_users[$userid] = array('username'=>$userid, 'user_firstname'=>$user_firstname, 
								'user_lastname'=>$user_lastname, 'user_email'=>$user_email);
	$_GET['userid'] = $userid;
}

# Get a list of all the projects
if (isset($_GET['userid']) && $_GET['userid'] == "0") {
	// Show all projects
	$sql = "select project_id, app_title from redcap_projects order by app_title";
}
elseif (isset($_GET['userid']) && $_GET['userid'] != "") {
	// Show just one user's
	$sql = "select p.project_id, p.app_title 
			from redcap_user_rights u, redcap_projects p 
			where u.project_id = p.project_id and u.username = '".prep($_GET['userid'])."' order by p.app_title";
}
elseif (!isset($_GET['userid']) || $_GET['userid'] == "") {
	// Show no projects (default)
	$sql = "select 1 from redcap_projects limit 0";
}

$query = db_query($sql);
$projects = array();
while ($row = db_fetch_assoc($query)) {
	$projects[$row['project_id']] = $row['app_title'];
}

if (isset($_GET['pid']))
{
	$token = "";
	
	// Loop through all users of this project and display username/token
	$q = db_query("SELECT username, api_token FROM redcap_user_rights WHERE project_id = $project_id order by username");
	while ($row = db_fetch_assoc($q))
	{
		$this_user  = $row['username'];
		$this_token = $row['api_token'];
				
		$token .= "$this_user -> ";
		
		// If token not created yet, render link to create it for this user
		if (empty($this_token))
		{
			if ($super_user) {
				$token .= "<a style='text-decoration:underline;' href='create_token.php?pid=$project_id&username=$this_user'>Create token</a>";
			} else {
				$token .= "<span style='color:gray;'>[Token must be created by a REDCap administrator]</span>";
			}
		}
		else
		{
			// Only show if a super user or if this is your token
			if ($super_user || $this_user == $_GET['userid']) {
				$token .= $this_token;
			} elseif ($this_user != $_GET['userid']) {
				$token .= "<span style='color:gray;'>[Token only viewable by this user or a REDCap administrator]</span>";
			}
			if ($super_user) {
				$token .= " &nbsp; (<a style='text-decoration:underline;' onclick=\"return confirm('Are you sure you wish to reset the token value? After resetting it, "
						. "old values will no longer work.')\" href='create_token.php?pid=$project_id&username=$this_user'>Reset token</a>)";
			}
		}
		$token .= "<br>";
	}

	$events = Event::getUniqueKeys($project_id);
}

?>

<html>
<body>

<div style="padding:0px 20px;font-family:Arial;font-size:13px;width:800px;">
	<h1 style="margin:20px 0 0px;font-size:24px;color:#800000">REDCap API - Obtain API Tokens</h1>
	
	<p style="padding-bottom:20px;">
		<a style="text-decoration:underline;" href="<?php echo APP_PATH_WEBROOT_PARENT ?>api/help/">Return to the API Help Page</a> &nbsp;|&nbsp;
		<a style="text-decoration:underline;" href="<?php echo str_replace("?", "", $_SERVER['PHP_SELF']) ?>?logout=1">Logout</a>
	</p>
	
	<p>
		<b><?php echo $lang['control_center_21'] ?></b>&nbsp;
		<select class="x-form-text x-form-field" style="padding-right:0;height:22px;" 
			onchange="window.location.href = '<?php echo PAGE_FULL ?>?userid='+this.value;">
			<?php
				if ($super_user) {
					echo "<option value='' " . (($_GET['userid'] == "") ? "selected" : "") . ">--- {$lang['control_center_22']} ---</option>";
					echo "<option value='0'" . (($_GET['userid'] == "0") ? "selected" : "") . ">-- {$lang['control_center_23']} --</option>";
				}
				foreach ($all_users as $username=>$attr)
				{
					$attr['username'] = trim($attr['username']);
					if ($attr['username'] != "")
					{
						$disp_name = "";
						if ($attr['user_firstname'] != "" && $attr['user_lastname'] != "") {
							$disp_name = "(" . $attr['user_lastname'] . ", " . $attr['user_firstname'] . ")";
						}
						print  "<option value='{$attr['username']}' " . (($attr['username'] == $_GET['userid']) ? "selected" : "") . ">{$attr['username']} $disp_name</option>";
					}
				}
			?>
		</select>
		</p>
	
	<b>Projects:</b>
	<select id="projectid" onchange="if(this.value != '') window.location.href='info.php?userid=<?php echo $_GET['userid'] ?>&pid='+this.value;">
		<option value=''>-- Select a project --</option>
		<?php 
		foreach ($projects as $projectid => $app_title)
		{
			$app_title = strip_tags($app_title);
			if (strlen($app_title) > 70) $app_title = substr($app_title, 0, 58) . "...";
			$selected = ($projectid == $project_id) ? "selected" : "";
			echo "<option value=\"$projectid\" $selected>$app_title</option>";
		}
		?>
	</select>
	<br/><br/><br/>
	<?php
	if (isset($_GET['pnid']) || isset($_GET['pid'])) {
		?>	
		<b><u>Username -> API Token</u></b><br>
		<?php echo $token ?><br/><br/>		
		<?php 
		if ((isset($_GET['pnid']) || isset($_GET['pid'])) && $longitudinal) { 
			?>
			<strong>Unique Event Names:</strong>
			<ul>
			<?php 
			foreach ($events as $index => $value)
			{
				echo "<li>$value</li>";
			}
			?>
			</ul>
		<?php
		}
	}
	?>
</div>

<?php 


$objHtmlPage->PrintFooterExt();
