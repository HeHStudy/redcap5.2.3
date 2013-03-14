<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


include_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
// Increase memory limit



// If user is in DAG, then use sub-query to restrict resulting record list to records in their DAG
$group_sql = ($user_rights['group_id'] == "") ? "" : "and d.record in (" . pre_query("select record from redcap_data where project_id = $project_id 
													  and field_name = '__GROUPID__' and value = '".$user_rights['group_id']."'") . ")";


// Get all locked records and put into an array
$sql = "select d.record, d.event_id, d.form_name, d.username, d.timestamp, u.user_firstname, u.user_lastname 
		from redcap_locking_data d left outer join redcap_user_information u on d.username = u.username where d.project_id = $project_id $group_sql";
$q = db_query($sql);
$locked_records = array();
while ($row = db_fetch_assoc($q))
{
	$locked_records[$row['record']][$row['event_id']][$row['form_name']] = format_ts_mysql($row['timestamp'])
																		 . (($row['username'] == '') ? '' :  ("<br>" . $row['username'] . " (" . $row['user_firstname'] . " " . $row['user_lastname'] . ")"));
}


// Get all e-signed records and put into an array
$sql = "select d.record, d.event_id, d.form_name, d.username, d.timestamp, u.user_firstname, u.user_lastname 
		from redcap_esignatures d, redcap_user_information u where d.project_id = $project_id and d.username = u.username $group_sql";
$q = db_query($sql);
$esigned_records = array();
while ($row = db_fetch_assoc($q))
{
	$esigned_records[$row['record']][$row['event_id']][$row['form_name']] = format_ts_mysql($row['timestamp']) 
																		  . "<br>" . $row['username'] . " (" . $row['user_firstname'] . " " . $row['user_lastname'] . ")";
}


// Get all forms that be locked and put into an array
$sql = "select m.form_name, m.form_menu_description, if(f.display_esignature is null, 0, f.display_esignature) as display_esignature 
		from redcap_metadata m left outer join redcap_locking_labels f on f.form_name = m.form_name and m.project_id = f.project_id 
		where m.project_id = $project_id and m.form_menu_description is not null and (f.display = 1 or f.display is null) order by m.field_order";
$q = db_query($sql);
$forms = array();
while ($row = db_fetch_assoc($q))
{
	$forms[$row['form_name']]['menu'] = $row['form_menu_description'];
	$forms[$row['form_name']]['display_esign'] = $row['display_esignature'];
}

// Retrieve all data and put all info into an array to render afterward
$sql = "select d.record, e.descrip, e.event_id from redcap_data d, redcap_events_arms a, redcap_events_metadata e where a.project_id = $project_id 
		and a.project_id = d.project_id and a.arm_id = e.arm_id and e.event_id = d.event_id and d.field_name = '$table_pk'
		and d.record != '' $group_sql order by abs(d.record), d.record, e.day_offset, e.descrip";
$q = db_query($sql);
$all_lock_esign_info = array();
while ($row = db_fetch_assoc($q))
{
	// Loop this record-event through each form
	foreach ($forms as $form_name=>$form_attr)
	{
		// If form is not designated for this event, then don't add to array
		if ($longitudinal && !in_array($form_name, $Proj->eventsForms[$row['event_id']])) continue;
		// Add to array
		$all_lock_esign_info[] = array(	'record' 	=> $row['record'],
										'event'		=> $row['descrip'],
										'event_id'	=> $row['event_id'],
										'form'		=> $form_attr['menu'],
										'form_name'	=> $form_name,
										'locked'	=> ((isset($locked_records[$row['record']][$row['event_id']][$form_name])) ? 1 : 0),
										'locktime'	=> ((isset($locked_records[$row['record']][$row['event_id']][$form_name])) ? $locked_records[$row['record']][$row['event_id']][$form_name] : ''),
										'esigned'	=> ((isset($esigned_records[$row['record']][$row['event_id']][$form_name])) ? 1 : ($forms[$form_name]['display_esign'] ? 0 : '')),
										'esigntime'	=> ((isset($esigned_records[$row['record']][$row['event_id']][$form_name])) ? $esigned_records[$row['record']][$row['event_id']][$form_name] : '')
									);
	}
	

}


## RENDER PAGE WITH TABLE
if (!isset($_GET['csv']))
{

	// Set page header 
	include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
	renderPageTitle("<img src='".APP_PATH_IMAGES."tick_shield_lock.png' class='imgfix2'> {$lang['esignature_01']}");
 
	?>
	<style type="text/css">
	.data { padding:3px 8px; }
	.data a:link, .data a:visited, .data a:hover, .data a:active { font-size:10px; font-family:tahoma; text-decoration:underline; }
	.label_header { padding:5px 10px; }
	.lock   { text-align: center; color: #666; font-size:10px; font-family:tahoma; }
	.esign  { text-align: center; color: #666; font-size:10px; font-family:tahoma; }
	</style>
	
	<!-- Instructions -->
	<p><?php print $lang['esignature_02'] ?></p>
	
	<!-- CSV download -->
	<p style="margin:20px 5px;">
		<img src="<?php echo APP_PATH_IMAGES ?>xls.gif" class="imgfix"> 
		<a href="<?php echo PAGE_FULL . "?pid=$project_id&csv" ?>" 
			style="color:#004000;font-size:11px;text-decoration:underline;font-weight:normal;"><?php print $lang['esignature_03'] ?></a>
	</p>				 
	
	<!-- Actions -->
	<p style="margin-left:5em;text-indent:-4.6em;margin-bottom:20px;color:#777;">
		<b style="color:#000;">Actions:</b> &nbsp; 
		<a href="javascript:;" style="text-decoration:underline;" onclick="$('.row').show();"><?php print $lang['esignature_04'] ?></a> &nbsp;|&nbsp; 
		<a href="javascript:;" style="text-decoration:underline;" onclick="$('.lock div').show();$('.esign div').show();$('.lock img').hide();$('.esign img').hide();"><?php print $lang['esignature_05'] ?></a> &nbsp;|&nbsp; 
		<a href="javascript:;" style="text-decoration:underline;" onclick="$('.lock div').hide();$('.esign div').hide();$('.lock img').show();$('.esign img').show();"><?php print $lang['esignature_06'] ?></a> &nbsp;|&nbsp;
		<a href="javascript:;" style="text-decoration:underline;" onclick="$('.row').show();$('.unlocked').hide();"><?php print $lang['esignature_07'] ?></a> &nbsp;|&nbsp; 
		<a href="javascript:;" style="text-decoration:underline;" onclick="$('.row').show();$('.locked').hide();"><?php print $lang['esignature_08'] ?></a> &nbsp;|&nbsp; <br>
		<a href="javascript:;" style="text-decoration:underline;" onclick="$('.row').show();$('.unesigned').hide();$('.aesigned').hide();"><?php print $lang['esignature_09'] ?></a> &nbsp;|&nbsp; 
		<a href="javascript:;" style="text-decoration:underline;" onclick="$('.row').show();$('.esigned').hide();$('.aesigned').hide();"><?php print $lang['esignature_10'] ?></a> &nbsp;|&nbsp; 
		<a href="javascript:;" style="text-decoration:underline;" onclick="$('.row').hide();$('.locked').show();$('.unesigned').hide();$('.aesigned').hide();"><?php print $lang['esignature_11'] ?></a> &nbsp;|&nbsp; <br>
		<a href="javascript:;" style="text-decoration:underline;" onclick="$('.row').show();$('.locked').hide();$('.esigned').hide();$('.aesigned').hide();"><?php print $lang['esignature_12'] ?></a> &nbsp;|&nbsp; 
		<a href="javascript:;" style="text-decoration:underline;" onclick="$('.row').show();$('.unlocked').hide();$('.esigned').hide();$('.aesigned').hide();"><?php print $lang['esignature_13'] ?></a>
	</p>
	
	<!-- Table -->
	<table id="esignLockList" class="form_border">
		<tr>
			<td style="padding: 7px; border: 1px solid #aaa; background-color: #ddd; font-size: 12px;" colspan="<?php echo ($longitudinal ? '6' : '5') ?>" class="label_header">
				<?php print $lang['esignature_14'] ?>
			</td>
		</tr>
		<tr>
			<td class="label_header"><?php print $lang['global_49'] ?></td>
			<?php if ($longitudinal) { ?><td class="label_header"><?php print $lang['global_10'] ?></td><?php } ?>
			<td class="label_header"><?php print $lang['global_12'] ?></td>
			<td class="label_header"><?php print $lang['esignature_18'] ?></td>
			<td class="label_header"><?php print $lang['esignature_19'] ?></td>
			<td class="label_header">&nbsp;</td>
		</tr>
		<?php foreach ($all_lock_esign_info as $attr) { ?>
		<tr class="row <?php echo ($attr['locked'] ? 'locked' : 'unlocked') ?> <?php echo (($attr['esigned'] == "1") ? 'esigned' : ($attr['esigned'] == "0" ? 'unesigned' : 'aesigned')) ?>">
			<td class="data"><?php echo $attr['record'] ?></td>
			<?php if ($longitudinal) { ?><td class="data"><?php echo $attr['event'] ?></td><?php } ?>
			<td class="data"><?php echo $attr['form'] ?></td>
			<td class="data lock"><?php echo ($attr['locked'] ? '<img src="'.APP_PATH_IMAGES.'lock_small.png"><div style="display:none;">'.$attr['locktime'].'</div>' : '') ?></td>
			<td class="data esign"><?php echo (($attr['esigned'] == "1") ? '<img src="'.APP_PATH_IMAGES.'tick_shield_small.png"><div style="display:none;">'.$attr['esigntime'].'</div>' : ($attr['esigned'] == "0" ? '' : '<span style="color:#999;">N/A</span>')) ?></td>
			<td class="data" style="padding:3px 12px;"><a target="_blank" href="<?php echo APP_PATH_WEBROOT . "DataEntry/index.php?pid=$project_id&id={$attr['record']}&page={$attr['form_name']}&event_id={$attr['event_id']}" ?>"><?php print $lang['esignature_20'] ?></a></td>
		</tr>
		<?php } ?>
	</table>
	<?php
	
	include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';

}



## OUTPUT TABLE AS CSV FILE
else
{
	// Set headers
	$headers = array();
	$headers[] = "Record";
	if ($longitudinal) $headers[] = "Event Name";
	$headers[] = "Form Name";
	$headers[] = "Locked?";
	$headers[] = "E-signed?";

	// Set file name and path
	$filename = APP_PATH_TEMP . date("YmdHis") . '_' . PROJECT_ID . '_EsignLockMgmt.csv';

	// Begin writing file from query result
	$fp = fopen($filename, 'w');
	if ($fp) 
	{
		// Write headers to file
		fputcsv($fp, $headers);
		
		// Set values for this row and write to file
		foreach ($all_lock_esign_info as $attr)
		{
			// Set row values
			$this_row = array();
			$this_row[] = $attr['record'];
			if ($longitudinal) $this_row[] = label_decode($attr['event']);
			$this_row[] = label_decode($attr['form']);
			$this_row[] = ($attr['locked']) ? str_replace("<br>", ", ", $attr['locktime']) : '';
			$this_row[] = ($attr['esigned'] == '1') ? str_replace("<br>", ", ", $attr['esigntime']) : (($attr['esigned'] == '0') ? '' : 'N/A');
			// Write this row to file
			fputcsv($fp, $this_row);
		}
		
		// Close file for writing
		fclose($fp);
		
		// Open file for downloading
		$download_filename = camelCase(html_entity_decode($app_title, ENT_QUOTES)) . "_EsignLockMgmt_" . date("Y-m-d_Hi") . ".csv";
		header('Pragma: anytextexeptno-cache', true);
		header("Content-type: application/csv");
		
		header("Content-Disposition: attachment; filename=$download_filename");
		
		// Open file for reading and output to user
		$fp = fopen($filename, 'rb');
		print fread($fp, filesize($filename));
		
		// Close file and delete it from temp directory
		fclose($fp);
		unlink($filename);	
		
	}
	
}

