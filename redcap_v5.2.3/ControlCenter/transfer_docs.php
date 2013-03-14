<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

/* there is a problem with including header.php here. it outputs to the screen prior to */
/* processing. this means our progress/wait bar doesn't function/do anything. therefore */
/* we need to do our own initialization and then somehow call header. */
require_once dirname(dirname(__FILE__)) . "/Config/init_global.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') 
{
	global $edoc_storage_option;
	$postback = TRUE; /* for displaying a message at the end */
	/* if we have a webdav setup, then we need to initialize it before we do anything */
	/* otherwise we're saving to the local file system */
	if ($edoc_storage_option) {
		// Upload using WebDAV
		require_once (APP_PATH_CLASSES . "WebdavClient.php");
		require_once (APP_PATH_WEBTOOLS . 'webdav/webdav_connection.php');
		$wdc = new WebdavClient();
		$wdc->set_server($webdav_hostname);
		$wdc->set_port($webdav_port); $wdc->set_ssl($webdav_ssl);
		$wdc->set_user($webdav_username);
		$wdc->set_pass($webdav_password);
		$wdc->set_protocol(1); // use HTTP/1.1
		$wdc->set_debug(FALSE); // enable debugging?
		/* if the webdav opens, then we use it. if not, default to the local */
		/* file system. may need to review what we do in the event of failure */
		if (!$wdc->open()) {
			$error[] = $lang['control_center_222'];
		}
	}

	$current_iteration = 0;
	$errors = array();
	$max_size = (1024 * 1024) * 128; // maximum storage size: 128mb
	$_POST['max_iterations'] = (int)$_POST['max_iterations'];
	$dbQuery = "SELECT d.* FROM redcap_docs d 
	            LEFT JOIN redcap_docs_to_edocs de ON d.docs_id = de.docs_id
				WHERE d.docs_size <= $max_size AND de.docs_id IS NULL ORDER BY d.docs_id LIMIT ".$_POST['max_iterations'];
	$dbResult = db_query($dbQuery);
	while($fdata = db_fetch_object($dbResult)) 
	{
		// if ($current_iteration >= (int)$_POST['max_iterations']) {
			// break;
		// }
		
		$current_iteration++;
		$success = TRUE;
		$file_hash = md5($fdata->docs_file);
		$file_extension = getFileExt($fdata->docs_name);
		$stored_name = date('YmdHis') . "_pid" . $fdata->project_id . "_" . generateRandomHash(6) . getFileExt($fdata->docs_name, true);		
		
		if ($edoc_storage_option) {
			$buffer = NULL;
			$doc_name = prep($fdata->docs_name);
			$target_path = $webdav_path . $stored_name;
			$http_status = $wdc->put($target_path,$fdata->docs_file);
			$wdc->get($target_path,$buffer);
						
			if ($file_hash != md5($buffer)) {
				$errors[] = "File validation error on ".$fdata->docs_name.".";
				$success = FALSE;
			}
		} else {
			if (file_put_contents(EDOC_PATH . $stored_name,$fdata->docs_file) === FALSE) {
				$errors[] = "Could not store ".$fdata->docs_name." to the local file system";
				$success = FALSE;
			} else {
				$buffer = file_get_contents(EDOC_PATH . $stored_name);
				if ($file_hash != md5($buffer)) {
					$errors[] = "File validation error on ".$fdata->docs_name.".";
					$success = FALSE;
				}
			}
		}
		
		if ($success === TRUE) {
			$q1 = db_query("INSERT INTO redcap_edocs_metadata (stored_name, mime_type, doc_name, doc_size, file_extension, project_id, stored_date) 
						      VALUES ('" . prep($stored_name) . "', '" . prep($fdata->docs_type) . "', '" . prep($fdata->docs_name) . "', 
						      '" . prep($fdata->docs_size) . "', '" . prep($file_extension) . "', " . $fdata->project_id . ", '".NOW."')");
			$doc_id = db_insert_id();
		
			/* now that we have the doc_id of the edocs_metadata, we need to update our lookup table */
			/* we also can remove the blob from the docs_file field, but that query needs to be */
			/* run later once we're confident of everything else. we are using replace-into just to */
			/* avoid any complications insert might have. because everything is 1:1 this shouldn't */
			/* be a big problem */
			$q2 = db_query("REPLACE INTO redcap_docs_to_edocs (docs_id,doc_id)
			             VALUES ('".$fdata->docs_id."','".$doc_id."');");	
			// Remove the original file from the redcap_docs table
			if ($q1 && $q2) {
				db_query("UPDATE redcap_docs SET docs_file=NULL WHERE docs_id='".$fdata->docs_id."'");
			}
		}
	}
	
	/* if webdav, then close the webdav connection */
	if ($edoc_storage_option) {
		$wdc->close();
	}
}

/* include the header once we've done everything */
include 'header.php';

?>
<h3 style="margin-top: 0px;"><?php print $lang['control_center_223'];?></h3>
<?php if ($postback === TRUE):?>
<div class="yellow" style="padding-bottonm:15px; font-family: arial;">
	<b><?php print $lang['control_center_224'];?></b><br />
	<?php if (count($errors) > 0):?>
		<?php print $lang['control_center_225'];?>
		<ul>
		<?php
			foreach($errors as $error) {
				print "<li><i>".$error."</i><li>";
			}
		?>
		</ul>
	<?php else:?>
	<?php print $lang['control_center_226'].' '.$current_iteration . ' ' . $lang['control_center_227']; ?>
	<?php endif;?>
</div>
<?php endif;?>

<div style="margin:10px 0px;border:1px solid #ccc;background-color:#fafafa;padding:12px 15px 0px;">
	<?php print $lang['control_center_228'];?>
	<br /><br />
	<?php
	// get the number of documents still stored in the database 
	$dbQuery = "SELECT COUNT(docs_id) FROM redcap_docs
				WHERE docs_id not in (".pre_query("SELECT docs_id FROM redcap_docs_to_edocs").")";
	$dbResult = db_query($dbQuery);
	$number_in_database = db_result($dbResult,0);
	
	// get the number of documents that have been moved to the file system
	$dbQuery = "SELECT COUNT(docs_id) FROM redcap_docs_to_edocs;";
	$dbResult = db_query($dbQuery);
	$number_in_filesystem = db_result($dbResult,0);
	
	// If there are no files in the db table, then set the flag so we don't see the notification anymore
	if (!$doc_to_edoc_transfer_complete && $number_in_database < 1)
	{
		$sql = "update redcap_config set value = '1' where field_name = 'doc_to_edoc_transfer_complete'";
		db_query($sql);
	}	
	// If there are are files in the db table and the flag was already set as Done, then reset the flag to show the notification again
	elseif ($doc_to_edoc_transfer_complete && $number_in_database > 0)
	{
		$sql = "update redcap_config set value = '0' where field_name = 'doc_to_edoc_transfer_complete'";
		db_query($sql);
	}
	
	// the difference is the number to be converted (not currently used)
	$disabled = ($number_in_database > 0) ? NULL : 'disabled';
	?>
	
	<div style="margin: 0px auto; width: 335px;background-color:#fff;">
		<div style="float: left; width: 240px; text-align: left; font-weight: bold; padding: 5px; border: 1px solid #000;">
			<?php print $lang['control_center_229'];?>
		</div>
	
		<div style="float: left; width: 72px; text-align: center; padding: 5px; border-top: 1px solid #000; border-right: 1px solid #000; border-bottom: 1px solid #000;">
			<?php print $number_in_database;?>
		</div>
	
		<div style="clear: both;"></div>
	
		<div style="float: left; width: 240px; text-align: left; font-weight: bold; padding: 5px; border-left: 1px solid #000; border-right: 1px solid #000; border-bottom: 1px solid #000;">
			<?php print $lang['control_center_230'];?>
		</div>
	
		<div style="float: left; width: 72px; text-align: center; padding: 5px; border-right: 1px solid #000; border-bottom: 1px solid #000;">
			<?php print $number_in_filesystem;?>
		</div>
		
		<div style="clear: both;"></div>
	</div>
	
	<br />
	
	<?php print $lang['control_center_231'];?><br /><br />
	
	<div id="conversion-interface" style="display: block; text-align: center; height: 45px;">
		<form action="<?php print $_SERVER['PHP_SELF'];?>" method="post">
			<select name="max_iterations">
				<option value="10">10</option>
				<option value="50">50</option>
				<option value="100">100</option>
				<option value="250">250</option>
				<option value="1000">1000</option>
				<option value="10000">10,000</option>
			</select>
			<input type="submit" id="process-form" name="submit" <?php print $disabled;?> value="<?php print $lang['control_center_232'];?>" />
		</form>
	</div>
	
	<div id="progress-bar" style="text-align: center; display: none; height: 45px;">
		<img src="<?php echo APP_PATH_IMAGES; ?>progress_bar.gif" border="0" />
	</div>
	
	<script language="javascript">
	$(function() {
		$('#process-form').click(function() {
			$('#conversion-interface').hide();
			$('#progress-bar').show();
		});
	});
	</script>
</div>

<?php
include 'footer.php';