<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

include 'header.php';

// If project values were changed, update redcap_projects table with new values
if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
	// Validate project_id
	if (!isset($_GET['project']) || (isset($_GET['project']) && !is_numeric($_GET['project']))) exit('ERROR!');
	
	// Loop through submitted values to build query
	$sql_set = array();
	foreach ($_POST as $field=>$value) {
		// Don't add apostrophes for NULLs
		$value = ($value == "NULL") ? "NULL" : "'" . prep($value) . "'";
		// Add to array
		$sql_set[] = "$field = $value";
	}
	// Execute query
	$sql = "update redcap_projects set " . implode(", ", $sql_set) . " where project_id = '{$_GET['project']}'";
	$q = db_query($sql);
	// Give confirmation of changes
	if ($q) {
		// Logging
		log_event($sql,"redcap_projects","MANAGE",$_GET['project'],implode(",\n",$sql_set),"Modify settings for single project");
		print  "<div class='yellow' style='margin-bottom:20px;text-align:center;'>
					<img src='".APP_PATH_IMAGES."exclamation_orange.png' class='imgfix'>
					{$lang['control_center_48']}
				</div>";
	} else {
		print  "<div class='red' style='margin-bottom:20px; text-align:center;'>
					<img src='".APP_PATH_IMAGES."exclamation.png' class='imgfix'>
					{$lang['global_01']}: {$lang['control_center_49']}
				</div>";
	}
}

// Retrieve data to pre-fill in form
$element_data = array();
$q = db_query("select * from redcap_projects where project_id = '{$_GET['project']}'");
$num_cols = db_num_fields($q);

while ($row = db_fetch_array($q)) {
	for ($i = 0; $i < $num_cols; $i++) {
		$this_fieldname = db_field_name($q, $i);
		$this_value = $row[$i];
		$element_data[$this_fieldname] = $this_value;
	}
}
?>

<h3 style="margin-top: 0;"><?php echo $lang['control_center_128'] ?></h3>

<p><?php echo $lang['control_center_50'] ?></p>

<p style='padding:15px 0 0;'>
	<b><?php echo $lang['control_center_51'] ?></b><br>
	<select style='padding-right:0;height:22px;' class='x-form-text x-form-field'
		onchange="window.location.href='<?php echo PAGE_FULL ?>?project=' + this.value">
		<option value=''>--- <?php echo $lang['control_center_52'] ?> ---</option>
		<?php
		$q = db_query("select project_id, trim(app_title) as app_title from redcap_projects order by trim(app_title)");
		while ($row = db_fetch_assoc($q))
		{
			$row['app_title'] = strip_tags(str_replace('<br>', ' ', $row['app_title']));
			// If title is too long, then shorten it
			if (strlen($row['app_title']) > 90) {
				$row['app_title'] = trim(substr($row['app_title'], 0, 66)) . " ... " . trim(substr($row['app_title'], -20));
			}
			if ($row['app_title'] == "") {
				$row['app_title'] = $lang['create_project_82'];
			}
			print "<option class='notranslate' value='{$row['project_id']}' ";
			if (isset($_GET['project']) && $row['project_id'] == $_GET['project']) {
				print "selected";
				$this_app_title = $row['app_title'];
			}
			print ">{$row['app_title']}</option>";
		}
		?>
	</select>
</p>

<?php
## Display project values since project has been selected
if (isset($_GET['project']) && $_GET['project'] != "")
{
	// Link to go to project page
	print  "<p style='margin-bottom:30px;font-size:14px;'>
				&gt;&gt; {$lang['control_center_53']} <a class='notranslate' target='_blank' href='" . APP_PATH_WEBROOT . "index.php?pid={$_GET['project']}'
					style='font-weight:bold;color:#800000;font-size:14px;text-decoration:underline;'>$this_app_title</a>
			</p>";
	?>
	
	<form action='<?php echo PAGE_FULL ?>?project=<?php echo $_GET['project'] ?>' enctype='multipart/form-data' target='_self' method='post' name='form' id='form'>
	<?php
	// Go ahead and manually add the CSRF token even though jQuery will automatically add it after DOM loads.
	// (This is done in case the page is very long and user submits form before the DOM has finished loading.)
	print "<input type='hidden' name='redcap_csrf_token' value='".getCsrfToken()."'>";
	?>
	<table style="border: 1px solid #ccc; background-color: #f0f0f0; width: 100%;">
	<tr id="online_offline-tr" sq_id="online_offline">
		<td class="cc_label"><?php echo $lang['project_settings_02'] ?></td>
		<td class="cc_data">
			<select class="x-form-text x-form-field" style="padding-right:0; height:22px;" name="online_offline">
				<option value='0' <?php echo ($element_data['online_offline'] == 0) ? "selected" : "" ?>><?php echo $lang['project_settings_04'] ?></option>
				<option value='1' <?php echo ($element_data['online_offline'] == 1) ? "selected" : "" ?>><?php echo $lang['project_settings_05'] ?></option>
			</select><br/>
			<div class="cc_info">
				<?php echo $lang['project_settings_03'] ?>
			</div>
		</td>
	</tr>
	<tr  id="auth_meth-tr" sq_id="auth_meth">
		<td class="cc_label">
			<?php echo $lang['project_settings_06'] ?>
		</td>
		<td class="cc_data">
			<select class="x-form-text x-form-field" style="padding-right:0; height:22px;" name="auth_meth">
				<option value='none' <?php echo ($element_data['auth_meth'] == "none" ? "selected" : "") ?>><?php echo $lang['system_config_08'] ?></option>
				<option value='table' <?php echo ($element_data['auth_meth'] == "table" ? "selected" : "") ?>><?php echo $lang['system_config_09'] ?></option>
				<option value='ldap' <?php echo ($element_data['auth_meth'] == "ldap" ? "selected" : "") ?>>LDAP</option>
				<option value='ldap_table' <?php echo ($element_data['auth_meth'] == "ldap_table" ? "selected" : "") ?>>LDAP & <?php echo $lang['system_config_09'] ?></option>
				<option value='shibboleth' <?php echo ($element_data['auth_meth'] == "shibboleth" ? "selected" : "") ?>>Shibboleth</option>
				<?php if (isDev(true)) { ?>
					<option value='local' <?php echo ($element_data['auth_meth'] == "local" ? "selected" : "") ?>>Vanderbilt Local (session-based)</option>
					<option value='c4' <?php echo ($element_data['auth_meth'] == "c4" ? "selected" : "") ?>>C4 (cookie-based)</option>
				<?php } ?>
				<option value='rsa' <?php echo ($element_data['auth_meth'] == "rsa" ? "selected" : "") ?>>RSA SecurID (two-factor authentication)</option>
			</select>
			<div class="cc_info" style="font-weight:normal;">
				<?php echo $lang['system_config_222'] ?> 
				<a href="https://iwg.devguard.com/trac/redcap/wiki/ChangingAuthenticationMethod" target="_blank" style="text-decoration:underline;"><?php echo $lang['system_config_223'] ?></a><?php echo $lang['system_config_224'] ?>
			</div>
		</td>
	</tr>
	<tr  id="project_language-tr" sq_id="project_language">
		<td class="cc_label"><?php echo $lang['system_config_90'] ?></td>
		<td class="cc_data">
			<select class="x-form-text x-form-field" style="padding-right:0; height:22px;" name="project_language">
				<?php
				$languages = getLanguageList();
				foreach ($languages as $language) {
					$selected = ($element_data['project_language'] == $language) ? "selected" : "";
					echo "<option value='$language' $selected>$language</option>";
				}
				?>
			</select><br/>
			<div class="cc_info">
				<?php echo $lang['system_config_107'] ?>
				<a href="<?php echo APP_PATH_WEBROOT ?>LanguageUpdater/" target='_blank' style='text-decoration:underline;'>Language File Creator/Updater</a>
				<?php echo $lang['system_config_108'] ?>
				<a href='https://iwg.devguard.com/trac/redcap/wiki/Languages' target='_blank' style='text-decoration:underline;'>REDCap wiki Language Center</a>.
				<br/><br/><?php echo $lang['system_config_109']." ".dirname(APP_PATH_DOCROOT).DS."languages".DS ?>
			</div>
		</td>
	</tr>
	<tr  id="investigators-tr" sq_id="investigators">
		<td class="cc_label"><?php echo $lang['project_settings_09'] ?></td>
		<td class="cc_data">
			<textarea class='x-form-field notesbox' id='investigators' name='investigators'><?php echo $element_data['investigators'] ?></textarea><br/>
			<div id='investigators-expand' style='text-align:right;'>
				<a href='javascript:;' style='font-weight:normal;text-decoration:none;color:#999;font-family:tahoma;font-size:10px;'
					onclick="growTextarea('investigators')">Expand</a>&nbsp;
			</div>
			<div class="cc_info">
				<?php echo $lang['project_settings_10'] ?>
			</div>
		</td>
	</tr>
	<tr  id="project_note-tr" sq_id="project_note">
		<td class="cc_label"><?php echo $lang['project_settings_11'] ?></td>
		<td class="cc_data">
			<textarea class='x-form-field notesbox' id='project_note' name='project_note'><?php echo $element_data['project_note'] ?></textarea><br/>
			<div id='project_note-expand' style='text-align:right;'>
				<a href='javascript:;' style='font-weight:normal;text-decoration:none;color:#999;font-family:tahoma;font-size:10px;'
					onclick="growTextarea('project_note')">Expand</a>&nbsp;
			</div>
			<div class="cc_info">
				<?php echo $lang['project_settings_12'] ?>
			</div>
		</td>
	</tr>
	<tr id="count_project-tr" sq_id="count_project">
		<td class="cc_label"><?php echo $lang['project_settings_13'] ?></td>
		<td class="cc_data">
			<select class="x-form-text x-form-field" style="padding-right:0; height:22px;" name="count_project">
				<option value='0' <?php echo ($element_data['count_project'] == 0) ? "selected" : "" ?>><?php echo $lang['project_settings_14'] ?></option>
				<option value='1' <?php echo ($element_data['count_project'] == 1) ? "selected" : "" ?>><?php echo $lang['project_settings_15'] ?></option>
			</select>
		</td>
	</tr>
	<tr><td colspan="2"><br/><hr></td></tr>
	<tr  id="dts_enabled-tr" sq_id="dts_enabled">
		<td class="cc_label"><img src="<?php echo APP_PATH_IMAGES ?>databases_arrow.png" class="imgfix"> <?php echo $lang['rights_132'] ?></td>
		<td class="cc_data">
			<?php $disabled = (!$dts_enabled_global) ? "disabled" : ""; ?>
			<select class="x-form-text x-form-field" style="padding-right:0; height:22px;" name="dts_enabled" <?php echo $disabled ?>>
				<option value='0' <?php echo ($element_data['dts_enabled'] == 0) ? "selected" : "" ?>><?php echo $lang['global_23'] ?></option>
				<option value='1' <?php echo ($element_data['dts_enabled'] == 1) ? "selected" : "" ?>><?php echo $lang['system_config_27'] ?></option>
			</select><br/>
			<div class="cc_info">
				<?php
				if ($dts_enabled_global)
					echo $lang['system_config_125'];
				else
					echo $lang['system_config_126'];
				?>
			</div>
		</td>
	</tr>
	<tr id="double_data_entry-tr" sq_id="double_data_entry">
		<td class="cc_label"><?php echo $lang['global_04'] ?></td>
		<td class="cc_data">
			<select class="x-form-text x-form-field" style="padding-right:0; height:22px;" name="double_data_entry">
				<option value='0' <?php echo ($element_data['double_data_entry'] == 0) ? "selected" : "" ?>><?php echo $lang['global_23'] ?></option>
				<option value='1' <?php echo ($element_data['double_data_entry'] == 1) ? "selected" : "" ?>><?php echo $lang['system_config_27'] ?></option>
			</select><br/>
			<div class="cc_info">
				<?php echo $lang['project_settings_18'] ?>
			</div>
		</td>
	</tr>
	<tr id="is_child_of-tr" sq_id="is_child_of">
		<td class="cc_label"><img src='<?php echo APP_PATH_IMAGES ?>link.png' class='imgfix'> <?php echo "{$lang['project_settings_22']}<br>{$lang['project_settings_23']}" ?></td>
		<td class="cc_data">
			<select class="x-form-text x-form-field" style="padding-right:0; height:22px;width:380px;" name="is_child_of">
				<option value='NULL' <?php echo ($element_data['is_child_of'] == "NULL") ? "selected" : "" ?>> - <?php echo $lang['project_settings_27'] ?> - </option>
				<?php
				// Child/Parent Linking: Query for list of all projects (except the one selected)
				$query = "select project_id, project_name, trim(app_title) as app_title from redcap_projects 
						  where project_id != {$_GET['project']} order by trim(app_title)";
				$result = db_query($query);
				while ($row = db_fetch_assoc($result))
				{
					$row['app_title'] = strip_tags(str_replace('<br>', ' ', $row['app_title']));

					// If title is too long, then shorten it
					if (strlen($row['app_title']) > 100) {
						$row['app_title'] = trim(substr($row['app_title'], 0, 90)) . " ... " . trim(substr($row['app_title'], -15));
					}					
					if ($row['app_title'] == "") {
						$row['app_title'] = $lang['create_project_82'];
					}

					$selected = ($element_data['is_child_of'] == $row['project_name']) ? "selected" : "";
					echo "<option value='{$row['project_name']}' $selected>{$row['app_title']}</option>";
				}
				?>
			</select><br/>
			<div class="cc_info">
				<?php echo "{$lang['project_settings_24']} <a target='_blank' href='".APP_PATH_WEBROOT_PARENT."index.php?action=help#projectlinking' style='text-decoration:underline;'>{$lang['project_settings_25']}</a>
				{$lang['period']}<br><br>{$lang['project_settings_26']}" ?>
			</div>
		</td>
	</tr>
	<tr><td colspan="2"><br/><hr></td></tr>
	<tr  id="date_shift_max-tr" sq_id="date_shift_max">
		<td class="cc_label"><?php echo $lang['project_settings_29'] ?></td>
		<td class="cc_data">
			<input class='x-form-text x-form-field '  type='text' name='date_shift_max' value='<?php echo $element_data['date_shift_max'] ?>'
				onblur="redcap_validate(this,'0','','soft_typed','int')" size='10' />
			<span style="color: #888;"><?php echo $lang['project_settings_31'] ?></span><br/>
			<div class="cc_info">
				<?php echo $lang['project_settings_30'] ?>
			</div>
		</td>
	</tr>
	<tr><td colspan="2"><br/><hr></td></tr>
	<tr  id="project_contact_name-tr" sq_id="project_contact_name">
		<td class="cc_label"><?php echo $lang['system_config_91'] ?></td>
		<td class="cc_data">
			<input class='x-form-text x-form-field ' type='text' name='project_contact_name' value='<?php echo $element_data['project_contact_name'] ?>' size="40" /><br/>
			<div class="cc_info">
				<?php echo $lang['system_config_92'] ?>
			</div>
		</td>
	</tr>
	<tr  id="project_contact_email-tr" sq_id="project_contact_email">
		<td class="cc_label"><?php echo "{$lang['system_config_93']} {$lang['system_config_91']}" ?></td>
		<td class="cc_data">
			<input class='x-form-text x-form-field '  type='text' name='project_contact_email' value='<?php echo $element_data['project_contact_email'] ?>'
				onblur="redcap_validate(this,'','','soft_typed','email')" size='40' /><br/>
		</td>
	</tr>
	<tr  id="project_contact_prod_changes_name-tr" sq_id="project_contact_prod_changes_name">
		<td class="cc_label"><?php echo $lang['project_settings_36'] ?></td>
		<td class="cc_data">
			<input class='x-form-text x-form-field ' type='text' name='project_contact_prod_changes_name' value='<?php echo $element_data['project_contact_prod_changes_name'] ?>' size="40" /><br/>
			<div class="cc_info">
				<?php echo $lang['project_settings_37'] ?>
			</div>
		</td>
	</tr>
	<tr  id="project_contact_prod_changes_email-tr" sq_id="project_contact_prod_changes_email">
		<td class="cc_label"><?php echo "{$lang['system_config_93']} {$lang['system_config_96']}" ?></td>
		<td class="cc_data">
			<input class='x-form-text x-form-field '  type='text' name='project_contact_prod_changes_email' value='<?php echo $element_data['project_contact_prod_changes_email'] ?>'
				onblur="redcap_validate(this,'','','soft_typed','email')" size='40' /><br/>
		</td>
	</tr>
	<tr><td colspan="2"><br/><hr></td></tr>
	<tr  id="institution-tr" sq_id="institution">
		<td class="cc_label"><?php echo $lang['system_config_97'] ?></td>
		<td class="cc_data">
			<input class='x-form-text x-form-field ' type='text' name='institution' value='<?php echo $element_data['institution'] ?>' size="60" /><br/>
		</td>
	</tr>
	<tr  id="site_org_type-tr" sq_id="site_org_type">
		<td class="cc_label"><?php echo $lang['system_config_98'] ?></td>
		<td class="cc_data">
			<input class='x-form-text x-form-field ' type='text' name='site_org_type' value='<?php echo $element_data['site_org_type'] ?>' size="60" /><br/>
		</td>
	</tr>
	<tr  id="grant_cite-tr" sq_id="grant_cite">
		<td class="cc_label"><?php echo $lang['system_config_99'] ?></td>
		<td class="cc_data">
			<input class='x-form-text x-form-field ' type='text' name='grant_cite' value='<?php echo $element_data['grant_cite'] ?>' size="40" /><br/>
			<div class="cc_info">
				<?php echo $lang['system_config_100'] ?>
			</div>
		</td>
	</tr>
	<tr  id="headerlogo-tr" sq_id="headerlogo">
		<td class="cc_label"><?php echo $lang['system_config_101'] ?></td>
		<td class="cc_data">
			<input class='x-form-text x-form-field ' type='text' name='headerlogo' value='<?php echo $element_data['headerlogo'] ?>' size="60" /><br/>
			<div class="cc_info">
				<?php echo $lang['system_config_102'] ?>
			</div>
		</td>
	</tr>
	<tr>
		<td class="cc_label"><?php echo $lang['system_config_129'] ?></td>
		<td class="cc_data">
			<select class="x-form-text x-form-field" style="padding-right:0; height:22px;" name="display_project_logo_institution">
				<option value='0' <?php echo ($element_data['display_project_logo_institution'] == 0) ? "selected" : "" ?>><?php echo $lang['system_config_231'] ?></option>
				<option value='1' <?php echo ($element_data['display_project_logo_institution'] == 1) ? "selected" : "" ?>><?php echo $lang['system_config_230'] ?></option>
			</select><br/>
		</td>
	</tr>
	<tr><td colspan="2"><br/><hr></td></tr>
	<tr  id="custom_index_page_note-tr" sq_id="custom_index_page_note">
		<td class="cc_label"><?php echo $lang['project_settings_47'] ?></td>
		<td class="cc_data">
			<textarea class='x-form-field notesbox' id='custom_index_page_note' name='custom_index_page_note'><?php echo $element_data['custom_index_page_note'] ?></textarea><br/>
			<div id='custom_index_page_note-expand' style='text-align:right;'>
				<a href='javascript:;' style='font-weight:normal;text-decoration:none;color:#999;font-family:tahoma;font-size:10px;'
					onclick="growTextarea('custom_index_page_note')">Expand</a>&nbsp;
			</div>
		</td>
	</tr>
	<tr  id="custom_data_entry_note-tr" sq_id="custom_data_entry_note">
		<td class="cc_label"><?php echo $lang['project_settings_48'] ?></td>
		<td class="cc_data">
			<textarea class='x-form-field notesbox' id='custom_data_entry_note' name='custom_data_entry_note'><?php echo $element_data['custom_data_entry_note'] ?></textarea><br/>
			<div id='custom_data_entry_note-expand' style='text-align:right;'>
				<a href='javascript:;' style='font-weight:normal;text-decoration:none;color:#999;font-family:tahoma;font-size:10px;'
					onclick="growTextarea('custom_data_entry_note')">Expand</a>&nbsp;
			</div>
		</td>
	</tr>
	</table><br/>
	<div style="text-align: center;"><input type='submit' name='' value='Save Changes' /></div><br/>
	</form>
<?php } ?>

<?php include 'footer.php'; ?>
