<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';


//change initial server values to account for a lot of processing
ini_set('max_execution_time', '1200');

include_once APP_PATH_DOCROOT . 'DataImport/functions.php';
include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

renderPageTitle("<img src='".APP_PATH_IMAGES."table_row_insert.png'> ".$lang['app_01']);
	
// Set extra set of reserved field names for survey timestamps and return codes pseudo-fields
$reserved_field_names2 = explode(',', implode("_timestamp,", array_keys($Proj->forms)) . "_timestamp"
					   . "," . implode("_return_code,", array_keys($Proj->forms)) . "_return_code");

################################################################################
#Begin building page
################################################################################

$this_file = PAGE_FULL . "?pid=$project_id";

#Set official upload directory
$upload_dir = dirname(substr(APP_PATH_DOCROOT,0,-1)) . DS . "temp";
if (!is_writeable($upload_dir)) {
	print "<br><br><div class='red'>
		<img src='".APP_PATH_IMAGES."exclamation.png'> <b>{$lang['global_01']}:</b><br>
		{$lang['data_import_tool_104']} <b>$upload_dir</b> {$lang['data_import_tool_105']}</div>";
	include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
	exit();
}

// Display instructions when initially viewing the page but not after uploading a file
if (!isset($_POST['submit']) && !isset($_POST['submit_events']) && !isset($_POST['updaterecs'])) 
{	
	//Print instructions
	print  "<div style='padding-right:10px;line-height:1.4em;max-width:700px;'>
				<p>{$lang['data_import_tool_01']}</p>";
	
	//If user is in DAG, only show info from that DAG and give note of that
	if ($user_rights['group_id'] != "") {
		print  "<p style='color:#800000;'>{$lang['global_02']}: {$lang['data_import_tool_106']}</p>";
	}
	
	if ($status < 1) {
		print  "<div class='yellow' style='font-family:arial;font-size:12px;margin:15px 0 20px;width:650px;'>
					<img src='".APP_PATH_IMAGES."exclamation_orange.png' class='imgfix'> 
					<b style='font-size:12px;'>{$lang['global_03']}{$lang['colon']}</b><br>
					{$lang['data_entry_28']}
				</div>";
	}
	print  "	<p style='font-size:14px;font-weight:bold;'>
					{$lang['global_24']}{$lang['colon']}
				</p>			
				<div style='text-indent:-1.5em;margin-left:2em;'>
					1.) <font color=#800000>{$lang['data_import_tool_02']}</font> {$lang['data_import_tool_03']}<br><br>
					<img src='".APP_PATH_IMAGES."xls.gif' class='imgfix'> 
					<a href='" . APP_PATH_WEBROOT . "DataImport/import_template.php?pid=$project_id&format=rows' style='text-decoration:underline;'>{$lang['data_import_tool_04']}</a> {$lang['data_import_tool_05']}<br>
					&nbsp; &nbsp;OR<br>
					<img src='".APP_PATH_IMAGES."xls.gif' class='imgfix'> 
					<a href='" . APP_PATH_WEBROOT . "DataImport/import_template.php?pid=$project_id&format=cols' style='text-decoration:underline;'>{$lang['data_import_tool_04']}</a> {$lang['data_import_tool_06']}<br>
					<br>
				</div>			
				<div style='text-indent:-1.5em;margin-left:2em;'>
					2.) {$lang['data_import_tool_09']}<br>
						<div style='padding-top:5px;text-indent:-0.8em;margin-left:3em;'>&bull;&nbsp; {$lang['data_import_tool_10']}</div>
						<div style='padding-top:5px;text-indent:-0.8em;margin-left:3em;'>&bull;&nbsp; {$lang['data_import_tool_11']}</div>
						<div style='padding-top:5px;text-indent:-0.8em;margin-left:3em;'>&bull;&nbsp; {$lang['data_import_tool_16']}</div>
						<br>
				</div>			
				<div style='text-indent:-1.5em;margin-left:2em;'>
					3.) <font color=#800000>{$lang['data_import_tool_17']}</font>
						{$lang['data_import_tool_19']}<br><br>
				</div>				
				<div style='text-indent:-1.5em;margin-left:2em;'>
					4.) {$lang['data_import_tool_22']}
				</div>		
			</div>";
	
	// HELP SECTION for using redcap_data_access_group and redcap_event_name
	// If DAGs exist and user is NOT in a DAG, then give instructions on how to use redcap_data_access_group field
	$dags = $Proj->getGroups();
	$canAssignDags = (!empty($dags) && $user_rights['group_id'] == "");
	if ($longitudinal || $canAssignDags)
	{
		$html = "";
		if ($canAssignDags) {
			$html .= RCView::div(array('style'=>'font-weight:bold;font-size:12px;color:#3E72A8;'), 
						RCView::img(array('src'=>'help.png','class'=>'imgfix')) . 
						$lang['data_import_tool_176']
					) . 
					$lang['data_import_tool_177'] . RCView::SP . 
					RCView::a(array('style'=>'text-decoration:underline;','href'=>APP_PATH_WEBROOT."DataAccessGroups/index.php?pid=$project_id"), $lang['global_22']) . $lang['global_14'] . $lang['period'];
		}
		if ($longitudinal && $canAssignDags) {
			$html .= RCView::div(array('class'=>'space'), ''); 
		}
		if ($longitudinal) {
			$html .= RCView::div(array('style'=>'font-weight:bold;font-size:12px;color:#3E72A8;'), 
						RCView::img(array('src'=>'help.png','class'=>'imgfix')) . 
						$lang['data_import_tool_178']
					) . 
					$lang['data_import_tool_179'] . RCView::SP . 
					RCView::a(array('style'=>'text-decoration:underline;','href'=>APP_PATH_WEBROOT."Design/define_events.php?pid=$project_id"), $lang['global_16']) . $lang['global_14'] . $lang['period'] . RCView::SP . 
					$lang['data_import_tool_180'];
		}
		print RCView::div(array('style'=>'color:#333;background-color:#f5f5f5;border:1px solid #ccc;margin-top:15px;font-family:arial;max-width:700px;padding:5px 8px 8px;'), $html);
	}
}

# Display form for user to upload a file
print  "<br><form action='$this_file' method='POST' name='form' enctype='multipart/form-data'>
		<div class='darkgreen' style='padding:20px;'>
			<div id='uploadmain'>
				<div style='padding-bottom:18px;'>
					<b>{$lang['data_import_tool_110']}</b> {$lang['data_import_tool_111']}&nbsp;
					<select name='format'>
						<option value='rows' ".((!isset($_POST['format']) || $_POST['format'] == 'rows') ? "selected" : "").">{$lang['data_import_tool_112']}</option>
						<option value='cols' ".(( isset($_POST['format']) && $_POST['format'] == 'cols') ? "selected" : "").">{$lang['data_import_tool_113']}</option>
					</select>
				</div>
				<div style='font-weight:bold;padding-bottom:5px;'><img src='".APP_PATH_IMAGES."xls.gif' class='imgfix'>
					{$lang['data_import_tool_23']}
				</div>
				<input type='file' name='uploadedfile' size='50'>
				<div style='padding-top:5px;'>
					<input type='submit' id='submit' name='submit' value='{$lang['data_import_tool_20']}' onclick=\"
						if (document.forms['form'].elements['uploadedfile'].value.length < 1) {
							alert('{$lang['data_import_tool_114']}');
							return false;
						}
						var file_ext = getfileextension(trim(document.forms['form'].elements['uploadedfile'].value));
						if (file_ext != 'csv' && file_ext != 'CSV') {
							$('#filetype_mismatch_div').dialog({ bgiframe: true, modal: true, width: 530, zIndex: 3999, buttons: { 
								Close: function() { $(this).dialog('close'); }
							}});
							return false;
						}
						document.getElementById('uploadmain').style.display='none';
						document.getElementById('progress').style.display='block';\">	
				</div>
			</div>
			<div id='progress' style='display:none;background-color:#FFF;width:500px;border:1px solid #A5CC7A;color:#800000;'>
				<table cellpadding=10><tr>
				<td valign=top><img src='" . APP_PATH_IMAGES . "progress.gif'></td>
				<td valign=top style='padding-top:20px;'>
					<b>{$lang['data_import_tool_44']}</b><br>{$lang['data_import_tool_45']}<br>{$lang['data_import_tool_46']}</td>
				</tr></table>
			</div>
		</div>
		</form><br><br>";
	
		// Div for displaying popup dialog for file extension mismatch (i.e. if XLS or other)
		?>
		<div id="filetype_mismatch_div" title="<?php echo cleanHtml2($lang['random_12']) ?>" style="display:none;">
			<p>
				<?php echo $lang['data_import_tool_160'] ?>
				<a href="http://office.microsoft.com/en-us/excel/HP100997251033.aspx#BMexport" target="_blank" 
					style="text-decoration:underline;"><?php echo $lang['data_import_tool_116'] ?></a> 
				<?php echo $lang['data_import_tool_117'] ?>
			</p>
			<p>
				<b style="color:#800000;"><?php echo $lang['data_import_tool_110'] ?></b><br>
				<?php echo $lang['data_import_tool_118'] ?>
			</p>
		</div>
		<?php


###############################################################################
# This page has 3 states:
# (1) plain page shows "browse..." textbox and upload button.
# (2) 'submit' -- user has just uploaded an Excel file. page parses the file, validates the data, and displays an error table or a "Data is okay, do you want to commit?" button
# (3) 'updaterecs' -- user has chosen to update records. page re-parses previously uploaded Excel file (to avoid passing SQL from page to page) and executes  SQL to update the project.
###############################################################################

// Get array of all checkbox fields
$chkbox_fields = getCheckboxFields();

# Check if a file has been submitted
if (isset($_POST['submit']) || isset($_POST['submit_events'])) 
{
	// Uploading first time
	if (isset($_POST['submit'])) {
	
		foreach ($_POST as $key=>$value) {
			$_POST[$key] = prep($value);
		}
		
		# Save the file details that are passed to the page in the _FILES array
		foreach ($_FILES as $fn=>$f) {
			$$fn = $f;
			foreach ($f as $k=>$v) {
				$name = $fn . "_" . $k;
				$$name = $v;
			}
		}
		
		# If filename is blank, reload the page
		if ($uploadedfile_name == "") {
			redirect(PAGE_FULL."?".$_SERVER['QUERY_STRING']);
			exit;
		}

		// Get field extension
		$filetype = strtolower(substr($uploadedfile_name,strrpos($uploadedfile_name,".")+1,strlen($uploadedfile_name)));

		if ($filetype != "csv"){
			// If uploaded as XLSX or CSV, tell user to save as XLS and re-uploade
			if ($filetype == "xls" || $filetype == "xlsx") {
				$msg = $lang['design_135'];
			} else {
				$msg = $lang['data_import_tool_47'];
			}
			// Display error message
			print  '<div class="red" style="margin:30px 0;">
						<img src="'.APP_PATH_IMAGES.'exclamation.png" class="imgfix"> <b>'.$lang['global_01'].':</b><br>'.$msg.'
					</div>';
			include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
			exit;
		}

		# If Excel file, save the uploaded file (copy file from temp to folder) and prefix a timestamp to prevent file conflicts
		$uploadedfile_name = date('YmdHis') . "_" . $app_name . "_import_data." . $filetype;	
		$uploadedfile_name = str_replace("\\", "\\\\", $upload_dir . DS . $uploadedfile_name);

		# If moving or copying the uploaded file fails, print error message and exit
		if (!move_uploaded_file($uploadedfile_tmp_name, $uploadedfile_name)) 
		{
			if (!copy($uploadedfile_tmp_name, $uploadedfile_name)) 
			{
				print '<p><br><table width=100%><tr><td class="comp_new_error"><font color=#800000>' .
					 "<b>{$lang['data_import_tool_48']}</b><br>{$lang['data_import_tool_49']} $project_contact_name " .
					 "{$lang['global_15']} <a href=\"mailto:$project_contact_email\">$project_contact_email</a> {$lang['data_import_tool_50']}</b></font></td></tr></table>";

				include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
				exit;			
			}
		}
	
	## If longitudinal and submitting second time with Events selected
	} elseif (isset($_POST['submit_events'])) {
		
		$uploadedfile_name = $_REQUEST['fname'];
	
	}
	
	# Process uploaded Excel file
	list ($fieldnames_new, $updateitems) = excel_to_array($uploadedfile_name);
	
	### Get metadata for each field name (as recordset) and put in associative array (fewer searches vs. recordset)
	$ar_metadata = get_import_metadata($fieldnames_new);
	
	### Build new data vs. old data comparison table
	$updateitems = compare_new_and_old($updateitems,$ar_metadata);
	
	$updateitems = check_import_data($updateitems, $ar_metadata);
	
	unset($ar_metadata);
	
	### Generate and print Summary of errors
	
	// Put metadata into an array as a check to prevent any extra or mistyped field names in Excel from causing errors
	$metadata_fields = array();
	foreach ($Proj->metadata as $field=>$attr) 
	{
		if ($attr['element_type'] == 'checkbox') {
			// Add all checkbox translated field names
			foreach (parseEnum($attr['element_enum']) as $this_code=>$this_label) {
				$metadata_fields[] = $field . "___" . strtolower($this_code);
			}
		} else {
			// Add field name for regular field
			$metadata_fields[] = $field;
		}
	}
	
	// Get HTML for error table and a count of all errors/warnings
	list($errortable, $errorcount, $warningcount) = make_error_table($updateitems,$metadata_fields);
	
	// If there are any errors or warnings, display the table and message
	if (($errorcount + $warningcount) > 0) 
	{
		// If any errors, automatically delete the uploaded file on the server.
		if ($errorcount > 0) {
			unlink($uploadedfile_name);
		}
		
		$usermsg = "<br>
					<div class='red'>
						<img src='".APP_PATH_IMAGES."exclamation.png' class='imgfix'> 
						<b>{$lang['data_import_tool_51']}</b>";
		
		if ($errorcount + $warningcount > 1){
			$usermsg .= "<br><br>{$lang['data_import_tool_52']} ";
		} else {
			$usermsg .= "<br><br>{$lang['data_import_tool_53']} ";
		}

		if ($errorcount > 1){
			$usermsg .= $errorcount . " {$lang['data_import_tool_54']} {$lang['data_import_tool_56']} "; 
		}else if ($errorcount == 1){
			$usermsg .= $errorcount . " {$lang['data_import_tool_41']} {$lang['data_import_tool_56']} "; 
		}
		
		if (($errorcount > 0)&&($warningcount > 0)){
				$usermsg .= " {$lang['global_43']} ";
		}

		if ($warningcount > 1){
			$usermsg .= $warningcount . " {$lang['data_import_tool_58']} {$lang['data_import_tool_60']} "; 
		}else if ($warningcount == 1){
			$usermsg .= $warningcount . " {$lang['data_import_tool_43']} {$lang['data_import_tool_60']} "; 
		}

			$usermsg .= " {$lang['data_import_tool_61']} ";
			
			if ($errorcount > 0){
				$usermsg .= " {$lang['data_import_tool_62']}";
			} else {
				$usermsg .= " {$lang['data_import_tool_63']}";
			}

		$usermsg .= "</div><br>";
		print $usermsg;
		print $errortable;
		
	} else {
		//Display confirmation that file was uploaded successfully
		print  "<br>
				<div class='green' style='padding:10px 10px 13px;'>
					<img src='".APP_PATH_IMAGES."accept.png' class='imgfix'> 
					<b>{$lang['data_import_tool_24']}</b><br>
					{$lang['data_import_tool_24b']}<br>
				</div>";
	}

	
	### Instructions and Key for Data Display Table
	if ($errorcount == 0) 
	{
		print  "<div class='blue' style='font-size:12px;margin:25px 0;'>
					<b style='font-size:15px;'>{$lang['data_import_tool_102']}</b><br><br>
					{$lang['data_import_tool_25']}<br><br>
					<table style='background-color:#FFF;color:#000;font-size:11px;border:1px;'>
						<tr><th scope='row' class='comp_fieldname' style='background-color:#000;color:#FFF;font-size:11px;'>
							{$lang['data_import_tool_33']}
						</th></tr>
						<tr><td class='comp_update' style='background-color:#FFF;font-size:11px;'>
							{$lang['data_import_tool_35']} = {$lang['data_import_tool_36']}
						</td></tr>
						<tr><td class='comp_old' style='background-color:#FFF;font-size:11px;'>
							{$lang['data_import_tool_37']} = {$lang['data_import_tool_38']}
						</td></tr>
						<tr><td class='comp_old' style='font-size:11px;'>
							<span class='comp_oldval'>{$lang['data_import_tool_27']} = {$lang['data_import_tool_39']}</span>
						</td></tr>
						<tr><td class='comp_new_error' style='font-size:11px;'>
							{$lang['data_import_tool_40']} = {$lang['data_import_tool_41']}
						</td></tr>
						<tr><td class='comp_new_warning' style='font-size:11px;'>
							{$lang['data_import_tool_42']} = {$lang['data_import_tool_43']}
						</td></tr>
					</table>
				</div>";
				
		// Render Data Disply table
		print make_comparison_table($fieldnames_new, $updateitems);
		
		// Using jQuery, manually add "data change reason" text boxes for each record, if option is enabled
		if ($require_change_reason)
		{
			?>
			<script type="text/javascript">
			$(function(){
			
				// Set up functions and variables
				function renderReasonBox(record_count) {
					return "<td class='yellow' style='border:1px solid gray;width:210px;'>"
						 + "<textarea id='reason-"+record_count+"' onblur=\"charLimit('reason-"+record_count+"',200)\" class='change_reason x-form-textarea x-form-field' style='font-family:arial;width:200px;height:60px;'></textarea></td>"
				}
				var reason_hdr = "<th class='yellow' style='color:#800000;border:1px solid gray;font-weight:bold;'><?php echo $lang['data_import_tool_132'] ?></th>";
				var new_rec_td = "<td class='comp_new'> </td>";
				var record_count = 1;
				
			<?php if (isset($_POST['format']) && $_POST['format'] == 'rows') {?>
			
				// Row data format
				$(".comp_recid").each(function() {
					if ($(this).text().indexOf('existing') > -1) { // only for existing records
						$(this).after(renderReasonBox(record_count));
					} else {
						$(this).after(new_rec_td);
					}
					record_count++;
				});
				$("#comptable").find('th').filter(':nth-child(2)').before(reason_hdr);
			
			<?php } else { ?>
			
				// Column data format
				var reasonRow = "";
				$(".comp_recid").each(function() {
					reasonRow += ($(this).text().indexOf('existing') > -1) ? renderReasonBox(record_count) : new_rec_td; // only for existing records
					record_count++;
				});
				var rows = document.getElementById('comptable').tBodies[0].rows;
				$(rows[1]).after("<tr>"+reason_hdr+reasonRow+"</tr>");
			
			<?php } ?>
			
			});
			</script>
			<?php
		}
		
		print  "<br><br>";
		
		// If ALL fields are old, then there's no need to update anything
		$field_counter = 0;
		$old_counter = 0;
		foreach ($updateitems as $studyid => $studyevent) {
			foreach ($studyevent as $event_id => $studyrecord) {
				foreach ($studyrecord as $fieldname => $datapoint){			
					if ($updateitems[$studyid][$event_id][$fieldname]['status'] == 'old') {
						$old_counter++;
					}
					$field_counter++;
				}
			}
		}
		if ($field_counter != $old_counter) {
		
			// Button for committing to import
			print  "<div id='commit_import_div' class='darkgreen' style='padding:20px;'>
						<form action='$this_file' method='post' id='form' name='form' enctype='multipart/form-data'>
						<div id='uploadmain2'>
							<b>{$lang['data_import_tool_66']}</b><br>{$lang['data_import_tool_67']}
							<input type='hidden' name='fname' value='$uploadedfile_name'>
							<input type='hidden' id='event_string' name='event_string' value='{$_POST['event_string']}'>
							<input type='hidden' name='format' value='".((isset($_POST['format']) && $_POST['format'] == 'rows') ? "rows" : "cols")."'>
							<div style='padding-top:5px;'>
								<input type='submit' name='updaterecs' value='{$lang['data_import_tool_29']}' onclick='return importDataSubmit($require_change_reason);'>
							</div>
							<div id='change-reasons-div' style='display:none;'></div>
						</div>
						<div id='progress2' style='display:none;background-color:#FFF;width:500px;border:1px solid #A5CC7A;color:#800000;'>
							<table cellpadding=10><tr>
								<td valign=top>
									<img src='" . APP_PATH_IMAGES . "progress.gif'>
								</td>
								<td valign=top style='padding-top:20px;'>
									<b>{$lang['data_import_tool_64']}<br>{$lang['data_import_tool_65']}</b><br>
									{$lang['data_import_tool_46']}
								</td>
							</tr></table>
						</div>
						</form>
					</div>";
			
		} else {
		
			//Message saying that there are no new records (i.e. all the uploaded records already exist in project)
			//Button for committing to record import
			print  "<div id='commit_import_div' class='red' style='padding:20px;'>
						<img src='" . APP_PATH_IMAGES . "exclamation.png' class='imgfix'> 
						<b>{$lang['data_import_tool_68']}</b><br>
						{$lang['data_import_tool_69']}
					</div>";
			
			//Delete the uploaded file from the server since its data cannot be imported
			unlink($uploadedfile_name);
		}
		
	}

	print "<br><br><br>";
	
}
	

	
	
	
	
	
	
	
/**
 * USER CLICKED "IMPORT DATA" BUTTON
 */
elseif (isset($_REQUEST['updaterecs'])) 
{
	// If submitted "change reason" then reconfigure as array with record as key to add to logging.
	$change_reasons = array();
	if ($require_change_reason && isset($_POST['records']) && isset($_POST['events']) && isset($_POST['reasons']))
	{
		foreach ($_POST['records'] as $this_key=>$this_record)
		{
			$event_id = $_POST['events'][$this_key];
			$change_reasons[$this_record][$event_id] = $_POST['reasons'][$this_key];
		}
		unset($_POST['records'],$_POST['reasons'],$_POST['events']);
	}
	
	// Process uploaded Excel file
	$uploadedfile_name = $_POST['fname'];
	list ($fieldnames_new, $updateitems) = excel_to_array($uploadedfile_name);
	
	// Get metadata for each field name (as recordset) and put in associative array (fewer searches vs. recordset)
	$ar_metadata = get_import_metadata($fieldnames_new);

	// Build new data vs. old data comparison table
	$updateitems = compare_new_and_old($updateitems, $ar_metadata);

	// Run this because Dates need to reformatted from value read by Excel Reader (bug?)
	$updateitems = check_import_data($updateitems, $ar_metadata);
	
	// Import uploaded data to data table
	build_importsql($updateitems,$change_reasons);	
	
	// Delete the uploaded file from the server now that its data has been imported
	unlink($uploadedfile_name);
	
	// Give user message of successful import
	print  "<br><br>
			<div class='green'>
				<img src='".APP_PATH_IMAGES."accept.png' class='imgfix'> <b>{$lang['data_import_tool_133']}</b><br><br>
				{$lang['data_import_tool_70']}
			</div>";

}


include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
