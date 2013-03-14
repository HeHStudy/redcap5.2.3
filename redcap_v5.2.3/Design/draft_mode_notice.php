<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


// Catch if user somehow gets to form editor page in production before enabling Draft Mode
if ($draft_mode == 0 && $status > 0 && isset($_GET['page'])) 
{	
	redirect(PAGE_FULL . "?pid=$project_id");
}
//Pre-draft mode: Prompt user to enter draft mode
elseif ($draft_mode == 0 && $status > 0) 
{
	// If user just canceled out of Draft Mode, give confirmation that it's no longer in Draft Mode
	if (isset($_GET['msg']) && $_GET['msg'] == 'cancel_draft_mode') {
		// Display message
		displayMsg("<b>{$lang['setup_08']}</b><br>{$lang['design_264']}", "actionMsg", "left", "green", "tick.png", 7, true);
	}

	// If using DTS, then give extra warning about entering Draft Mode because of synchronicity issues
	$dtsWarn = "true"; // default true value
	if ($dts_enabled_global && $dts_enabled) {
		$dtsWarn = "confirm('" . cleanHtml($lang['define_events_64']) . '\n\n' . cleanHtml($lang['design_206']) 
				 . '\n\n' . cleanHtml($lang['design_207']) . "')";
	}
	
	print  "<div style='' class='yellow'>
				<b>{$lang['global_02']}{$lang['colon']}</b> {$lang['design_10']}
				<div style='text-align:center;font-weight:bold;margin:25px 0 10px;'>
					{$lang['design_11']}<br><br>
					<input type='button' value='" . cleanHtml($lang['design_376']) . "' onclick=\"
						if ($dtsWarn) {
							window.location.href='".APP_PATH_WEBROOT."Design/draft_mode_enter.php?pid='+pid;
						}
					\">
				</div>
			</div>";

//Draft mode (show changes)
} elseif ($draft_mode == 1 && $status > 0 && $_SERVER['REQUEST_METHOD'] != 'POST') 
{
	// If user just canceled out of Draft Mode, give confirmation that it's no longer in Draft Mode
	if (isset($_GET['msg']) && $_GET['msg'] == 'enabled_draft_mode') {
		// Display message
		displayMsg("<b>{$lang['setup_08']}</b><br>{$lang['design_385']}", "actionMsg", "left", "green", "tick.png", 13, true);
	}
	print  "<div class='yellow' style='font-family:arial;'>
				<b>{$lang['design_14']}</b> 
				<a href='javascript:;' style='font-family:arial;margin-left:10px;color:#800000;font-size:11px;' onclick=\"
					$('#draftChangeInstr').toggle('blind','fast');
				\">{$lang['global_58']}</a>
				<div id='draftChangeInstr' style='display:none;'>{$lang['design_177']} {$lang['design_175']}</div>
				<table cellpadding='0' cellspacing='0'>
					<tr>
						<td valign='top' style='padding:10px 40px 10px 10px;'>
							<input type='button' class='jqbutton' value='".cleanHtml($lang['design_255'])."' onclick=\"
								$('#confirm-review').dialog({ bgiframe: true, modal: true, width: 600, buttons: { 
									Cancel: function() { $(this).dialog('close'); },
									Submit: function() {
										$(':button:contains(\'Submit\')').html('Please wait...');
										$(':button:contains(\'Cancel\')').css('display','none');
										showProgress(1);
										window.location.href=app_path_webroot+'Design/draft_mode_review.php?pid='+pid;
									}
								} });
							\">
							<div style='text-align:right;margin-top:20px;'>
								<a href='javascript:;' style='color:#800000;font-size:10px;font-family:tahoma;' onclick=\"
									$('#draft-cancel').dialog({ bgiframe: true, modal: true, width: 600, buttons: { 
										Cancel: function() { $(this).dialog('close'); },
										'".cleanHtml($lang['design_256'])."': function() {
											window.location.href=app_path_webroot+'Design/draft_mode_cancel.php?pid='+pid;
										}
									} });
								\">".cleanHtml($lang['design_256'])."</a>
							</div>
						</td>
						<td valign='top' style='padding-bottom:5px;'>
							" . renderCountFieldsAddDel() . "
							<img src='".APP_PATH_IMAGES."zoom.png' class='imgfix'>
							<a href='".APP_PATH_WEBROOT."Design/project_modifications.php?pid=$project_id&ref=".PAGE."' 
								style='font-family:arial;'>{$lang['design_436']}</a>
						</td>
					</tr>
				</table>
			</div>
			<br>
			
			<!-- Hidden Dialogs -->
			<div id='draft-cancel' title=\"".cleanHtml2($lang['design_265'])."\" style='display:none;'>
				<p>{$lang['design_257']}</p>
			</div>
			<div id='confirm-review' title=\"".cleanHtml2($lang['design_16'])."\" style='display:none;'>
				<p>
					{$lang['design_17']} 
					".($auto_prod_changes > 0 ? "<div style='border:1px solid #ddd;padding:4px;'>{$lang['design_287']}</div>" : "<br>")."
					<br>
					<img src='" . APP_PATH_IMAGES . "star.png' class='imgfix'> {$lang['edit_project_55']} 
					<a style='text-decoration:underline;' href='".APP_PATH_WEBROOT."IdentifierCheck/index.php?pid=$project_id'>{$lang['identifier_check_01']}</a> {$lang['edit_project_56']}
				</p>
			</div>";

//Post-draft mode: Waiting approval from administrator
} elseif ($draft_mode == 2 && $status > 0) 
{
	## Give special notification to Super User for reviewing changes
	if ($super_user) {
		print  "<div class='red' style='margin:20px 0;padding-bottom:15px;'>
					<img src='" . APP_PATH_IMAGES . "exclamation.png' class='imgfix'>
					<b>{$lang['design_19']}</b><br><br>
					{$lang['design_20']} {$lang['design_21']}{$lang['period']}
					<br><br>
					<button onclick=\"window.location.href='" . APP_PATH_WEBROOT . "Design/project_modifications.php?pid=$project_id';\">{$lang['design_21']}</button>
				</div>";
	}
	
	## Give note to normal user
	// If using auto prod changes, then give user explanation of why their changes weren't approved automatically
	$explainText = "";
	if ($auto_prod_changes > 0) {
		if ($auto_prod_changes == '1') {
			$explainText = $lang['design_284'];
		} elseif ($auto_prod_changes == '2') {
			$explainText = $lang['design_285'];
		} elseif ($auto_prod_changes == '3') {
			$explainText = $lang['design_290'];
		} elseif ($auto_prod_changes == '4') {
			$explainText = $lang['design_291'];
		}
		$explainText .= " " . $lang['design_286'];
		$explainText = "<div style='space'>&nbsp;</div>
						<a href='javascript:;' onclick=\"$('#explainNoAutoChanges').toggle('fade');\" style='color:#800000;font-family:arial;'>{$lang['design_283']}</a>
						<div style='display:none;margin-top:10px;border:1px solid #ccc;padding:8px;font-family:arial;' id='explainNoAutoChanges'>$explainText</div>
						";
	}
	// Display message
	print  "<div class='yellow' style='padding:10px;'>
				<img src='" . APP_PATH_IMAGES . "clock_frame.png' class='imgfix'>
				<b>{$lang['design_22']}</b><br><br>
				{$lang['design_23']}<br><br>
				{$lang['design_24']} <b class='notranslate'>$project_contact_prod_changes_name</b> 
				(<a class='notranslate' href='mailto:$project_contact_prod_changes_email' style='text-decoration:underline;'>$project_contact_prod_changes_email</a>). 
				$explainText
				<div style='margin:20px 0 5px;'>
					<img src='".APP_PATH_IMAGES."zoom.png' class='imgfix'>
					{$lang['design_437']}
					<a href='".APP_PATH_WEBROOT."Design/project_modifications.php?pid=$project_id&ref=".PAGE."' 
						style='font-family:arial;'>{$lang['design_18']}</a>{$lang['period']}
				</div>
			</div>";

}
