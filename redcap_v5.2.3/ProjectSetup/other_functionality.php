<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';


	
## MOVE TO PROD & SET TO INACTIVE/BACK TO PROD
// Set up status-specific language and actions
$status_change_btn   = "Move to production status";
$status_change_text  = $lang['edit_project_08'];
$status_dialog_title = $lang['edit_project_09'];
$status_dialog_btn 	 = "YES, Move to Production Status";
$status_dialog_btn_action = "doChangeStatus(0,'{$_GET['type']}','{$_GET['user_email']}');";
$status_dialog_text  = $lang['edit_project_11'];
switch ($status) {
	case 0: // Development
		$iconstatus = '<img src="'.APP_PATH_IMAGES.'page_white_edit.png" class="imgfix"> <span style="color:#666;">'.$lang['global_29'].'</span>';
		$status_dialog_text  = "{$lang['edit_project_13']}<br><br>
								<img src='" . APP_PATH_IMAGES . "star.png' class='imgfix'> {$lang['edit_project_55']} 
								<a style='text-decoration:underline;' href='".APP_PATH_WEBROOT."IdentifierCheck/index.php?pid=$project_id'>{$lang['identifier_check_01']}</a> {$lang['edit_project_56']}<br><br>
								<span class='red'>
									<input type='checkbox' id='delete_data' "
										. (($_GET['type'] == "move_to_prod" && $super_user && $_GET['delete_data'] == "0") ? "" : " checked ") // check the box
										. (($_GET['type'] == "move_to_prod" && $super_user) ? " disabled " : "") // disable the box
									. " > 
									<b style='font-size:12px;font-family:verdana;'>{$lang['edit_project_59']}</b>
								</span><br><br>								
								{$lang['edit_project_15']}";
		
		// If only Super Users can move to production, then give different text for normal users
		if (!$super_user && $superusers_only_move_to_prod == '1') 
		{
			$status_dialog_text .= "<br>
									<p style='color:#800000;'>
										<img src='" . APP_PATH_IMAGES . "exclamation.png' class='imgfix'> 
										<b>{$lang['global_02']}:</b><br>
										{$lang['edit_project_17']} ($user_email) {$lang['edit_project_18']}
									</p>";
			$status_dialog_btn = "Yes, Request Admin to Move to Production Status";
			$status_dialog_title = $lang['edit_project_19'];
			// Javascript to send email to REDCap admin for approval to move to production
			$status_dialog_btn_action = "var delete_data = 0;
										if ($('#delete_data:checked').val() !== null) {
											if ($('#delete_data:checked').val() == 'on') delete_data = 1;
										}
										$.get(app_path_webroot+'ProjectGeneral/notifications.php', { pid: pid, type: 'move_to_prod', delete_data: delete_data },
											function(data) {
												$('#status_dialog').dialog('close');
												if (data == '0') {
													alert('".cleanHtml("{$lang['global_01']}{$lang['colon']} {$lang['edit_project_20']}")."');
												} else {
													alert('".cleanHtml($lang['edit_project_21'])."\\n\\n".cleanHtml($lang['edit_project_22'])."');
												}
											}
										);";
		}
		break;
	case 1: // Production
		$iconstatus = '<img src="'.APP_PATH_IMAGES.'accept.png" class="imgfix"> <span style="color:green;">'.$lang['global_30'].'</span>';
		$status_change_btn   = "Move to inactive status";
		$status_change_text  = $lang['edit_project_24'];
		$status_dialog_title = $lang['edit_project_25'];
		$status_dialog_btn 	 = "YES, Move to Inactive Status";
		$status_dialog_text  = $lang['edit_project_26'];
		break;
	case 2: // Inactive
		$iconstatus = '<img src="'.APP_PATH_IMAGES.'delete.png" class="imgfix"> <span style="color:#800000;">'.$lang['global_31'].'</span>';
		break;
	case 3: // Archived
		$iconstatus = '<img src="'.APP_PATH_IMAGES.'bin_closed.png" class="imgfix"> <span style="color:#800000;">'.$lang['global_26'].'</span>';
		break;
}

$otherFuncTable = '';



// API help
if ($api_enabled)
{
	$h = ''; // will hold the HTML to display in API div (all JS is included inline at the bottom)
	$h .= RCView::div(array('class' => 'chklisthdr'), $lang['edit_project_52']);
	$apiHelpLink = RCView::a(array('id' => 'apiHelpBtnId', 'style' => 'text-decoration:underline;', 'href' => '#'),
					$lang['edit_project_57']);
	$h .= RCView::div(array('style' => 'margin:5px 0 0;'),
					$lang['system_config_114'] . ' ' . $apiHelpLink . $lang['period'] . RCView::br() . RCView::br() . $lang['system_config_189']);
	// Display option to erase all API tokens
	if ($super_user) 
	{
		$db = new RedCapDB();
		// JS handler for this button is at the bottom
		$btn = RCView::button(array('id' => 'apiEraseBtnId', 'class' => 'jqbuttonmed'),
						str_replace(' ', RCView::SP, $lang['edit_project_106']));
		$h .= RCView::table(array('cellspacing' => '12', 'width' => '100%', 'style' => 'border-collapse: collapse; margin-top: 10px;'), 
			RCView::tr(array('id' => 'row_token_erase'),
						RCView::td(array('valign' => 'top', 'style' => 'padding: 0px 15px 0px 5px;'), $btn) .
						RCView::td(array('valign' => 'top', 'style' => 'padding: 0px 5px 0px 0px;'),
										$lang['edit_project_107'] . ' ' .
										RCView::b($lang['edit_project_77']) . RCView::br() .
										$lang['edit_project_108'] . ': ' .
										RCView::span(array('id' => 'apiTokenCountId'), $db->countAPITokensByProject($project_id)))));
	}
	$otherFuncTable .= RCView::div(array('class' => 'round chklist', 'style' => 'padding:15px 20px;'), $h);
}

			
## Other functionality (copy, delete, erase, archive)
$otherFuncTable .= "<div class='round chklist' style='padding:15px 20px;'>" .
					RCView::div(array('class' => 'chklisthdr'), $lang['global_65'] . ' ' . $lang['global_70']) .
					"<table cellspacing='12' width='100%'>";


if ($status > 0)
{
	// If Inactive/Archived, set back to production
	$otherFuncTable .= "<tr>
							<td valign='top' style='padding:2px 5px 0 0;'>
								<input class='jqbuttonmed' type='button' value='$status_change_btn' onclick='btnMoveToProd()'> 
							</td>
							<td valign='top'>$status_change_text</td>
						</tr>";
						
	// If in production, MOVE BACK TO DEVELOPMENT (super users only)
	if ($super_user) 
	{
		// Set flag if using DTS. If so, don't allow to move back to dev because it will break DTS mapping 
		$usingDTS = $dts_enabled ? '1' : '0';
		// Display table row
		$otherFuncTable .= "<tr>
								<td valign='top' style='padding:2px 5px 0 0;'>
									<button class='jqbuttonmed' onclick='MoveToDev($draft_mode,$usingDTS)'>{$lang['edit_project_79']}</button> 
								</td>
								<td valign='top'>
									{$lang['edit_project_80']} ";
		if ($draft_mode > 0) {
			$otherFuncTable .= "<span style='color:#800000;'>{$lang['edit_project_81']}</span> ";
		}
		$otherFuncTable .= "		<b>{$lang['edit_project_77']}</b></td>
							</tr>";
	}
}
if ($allow_create_db) 
{
	// COPY project
	$otherFuncTable .= "<tr id='row_copy'>	
							<td valign='top' style='padding:2px 5px 0 0;'>
								<input class='jqbuttonmed' type='button'  value='Copy the project' onclick=\"window.location.href = app_path_webroot+'ProjectGeneral/copy_project_form.php?pid=$project_id'+addGoogTrans()\"> 
							</td>
							<td valign='top'>
								{$lang['edit_project_121']}
							</td>
						</tr>";
}
if ($status < 1 || $super_user) 
{
	// Display option to DELETE the project (ONLY if in development)
	$otherFuncTable .= "<tr id='row_delete_project'>
							<td valign='top' style='padding:2px 5px 0 0;'>
								<input class='jqbuttonmed' type='button' value='{$lang['control_center_105']}' onclick='delete_project(pid,this)'> 
							</td>
							<td valign='top'>
								{$lang['edit_project_50']}";
	if (!$super_user && $status < 1) {
		$otherFuncTable .=  	" {$lang['edit_project_78']}";
	} elseif ($status > 0) {
		$otherFuncTable .=  	" <b>{$lang['edit_project_77']}</b>";
	}
	$otherFuncTable .= "	</td>
						</tr>";
	// Display option to ERASE all data in the project (ONLY if in development)
	$otherFuncTable .= "<tr id='row_erase'>
							<td valign='top' style='padding:2px 5px 0 0;'>
								<input class='jqbuttonmed' type='button' value='Erase all data' onclick=\"
									$('#erase_dialog').dialog('destroy');
									$('#erase_dialog').dialog({ bgiframe: true, modal: true, width: 500, buttons: { 
										Cancel: function() { $(this).dialog('close'); }, 
										'Erase All Data': function() {
											showProgress(1);
											$(':button:contains(\'Cancel\')').html('Please wait...');
											$(':button:contains(\'Erase All Data\')').css('display','none');
											$.get(app_path_webroot+'ProjectGeneral/erase_project_data.php', { pid: pid, action: 'erase_data' },
												function(data) {
													showProgress(0);
													$('#erase_dialog').dialog('close');
													if (data == '1') {
														simpleDialog('".cleanHtml($lang['edit_project_31'])."','".cleanHtml($lang['global_79'])."');
													} else {
														alert(woops);
													}
												}
											);
										}
									} });
								\"> 
							</td>
							<td valign='top'>
								{$lang['edit_project_32']}";
	if (!$super_user && $status < 1) {
		$otherFuncTable .=  	" {$lang['edit_project_78']}";
	} elseif ($status > 0) {
		$otherFuncTable .=  	" <b>{$lang['edit_project_77']}</b>";
	}
	$otherFuncTable .= "	
							</td>
						</tr>";
}
// Display option to archive the project (if not already archived)
if ($status != 3) 
{
	$otherFuncTable .= "<tr id='row_archive'>
							<td valign='top' style='padding:2px 5px 0 0;'>
								<input class='jqbuttonmed' type='button'  value='Archive the project' onclick=\"
									$('#archive_dialog').dialog('destroy');
									$('#archive_dialog').dialog({ bgiframe: true, modal: true, width: 500, buttons: { 
										Cancel: function() { $(this).dialog('close'); }, 
										'Archive the Project': function() { doChangeStatus(1,'','') }
									} });
								\"> 
							</td>
							<td valign='top'>
								{$lang['edit_project_33']}
							</td>
						</tr>";
}
$otherFuncTable .= "</table>
					</div>";

// Header
include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
// Tabs
include APP_PATH_DOCROOT . "ProjectSetup/tabs.php";
?>

<!-- Invisible div for status change -->
<div id='status_dialog' title='<?php echo $status_dialog_title ?>' style='display:none;'>
	<p style='font-family:arial;'><?php echo $status_dialog_text ?></p>
</div>	
<!--  Invisible div for archiving the project -->
<div id='archive_dialog' title='<?php echo $lang['edit_project_34'] ?>' style='display:none;'>
	<p style='font-family:arial;'>
		<?php echo $lang['edit_project_35'] ?>
	</p>
</div>
<!--  Invisible div for erasing all data -->
<div id='erase_dialog' title='<?php echo $lang['edit_project_36'] ?>' style='display:none;'>
	<p style='font-family:arial;'>
		<?php echo $lang['edit_project_37'] ?>
	</p>
</div>
<!--  Invisible div for erasing all API tokens -->
<div id='erase_api_dialog' title='<?php echo $lang['edit_project_109'] ?>' style='display:none;'>
	<p style='font-family:arial;'>
		<?php echo $lang['edit_project_110'] ?>
	</p>
</div>

<script type='text/javascript'>
	$(function() {
		$("#apiHelpBtnId").click(function() {
			window.location.href='<?php echo APP_PATH_WEBROOT_PARENT; ?>api/help/';
		});
		$("#apiEraseBtnId").click(function() {
			if ($('#apiTokenCountId').html() == '0') {
				alert('There are no tokens to delete because no API tokens have been created yet.');
				return;
			}
			$('#erase_api_dialog').dialog('destroy');
			$('#erase_api_dialog').dialog(
				{ bgiframe: true, modal: true, width: 500,
					buttons: { 
						Cancel: function() { $(this).dialog('close'); }, 
						'<?php echo $lang['edit_project_106']; ?>':
						function() {
							$.get(app_path_webroot + 'ControlCenter/user_api_ajax.php',
								{ action: 'deleteProjectTokens', api_pid: '<?php echo $project_id; ?>'},
								function(data) {
									alert(data);
									$.get(app_path_webroot + 'ControlCenter/user_api_ajax.php',
										{ action: 'countProjectTokens', api_pid: '<?php echo $project_id; ?>'},
										function(data) { $("#apiTokenCountId").html(data); }
									);
								}
							);
							$(this).dialog('close');
						}
					}
				}
			);
		});
	});
function btnMoveToProd() {
	$('#status_dialog').dialog('destroy');
	$('#status_dialog').dialog({ bgiframe: true, modal: true, width: 650, buttons: { 
		Cancel: function() { $(this).dialog('close'); }, 
		'<?php echo $status_dialog_btn ?>': function() { <?php echo $status_dialog_btn_action ?> }
	} });
}
function MoveToDev(draft_mode,usingDTS) {
	if (usingDTS) {
		alert('<?php echo cleanHtml($lang['edit_project_84']) . '\n\n' . cleanHtml($lang['edit_project_85']) ?>');
		return;
	}
	var msg = '<?php echo cleanHtml($lang['edit_project_82']) ?>';
	if (draft_mode > 0) {
		msg += ' <?php echo cleanHtml($lang['edit_project_83']) ?>';
	}
	if (confirm(msg)) {
		$.post(app_path_webroot+'ProjectGeneral/change_project_status.php?moveToDev=1&pid='+pid, { }, function(data){
			if (data=='1') {
				window.location.href = app_path_webroot+'ProjectSetup/index.php?msg=movetodev&pid='+pid;
			} else {
				alert(woops);
			}
		});
	}
}
</script>

<?php
// Tables
print $otherFuncTable;

// Footer
include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
