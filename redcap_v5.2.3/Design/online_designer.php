<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

//change initial server values to account for a lot of processing


//Required files
require_once APP_PATH_DOCROOT . 'ProjectGeneral/form_renderer_functions.php';
require_once APP_PATH_DOCROOT . 'Design/functions.php';
require_once APP_PATH_DOCROOT . 'Surveys/survey_functions.php';

include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

// Shared Library flag to avoid duplicate loading is reset here for the user to load a form
$_SESSION['import_id'] = '';

//If project is in production, do not allow instant editing (draft the changes using metadata_temp table instead)
$metadata_table = ($status > 0) ? "redcap_metadata_temp" : "redcap_metadata";


## AUTO PROD CHANGES (SUCCESS MESSAGE DIALOG)
if (isset($_GET['msg']) && $_GET['msg'] == "autochangessaved" && $auto_prod_changes > 0 && $status > 0 && $draft_mode == 0)
{
	// Set text to explain why changes were made automatically
	if ($auto_prod_changes == '1') {
		$explainText = $lang['design_279'];
	} elseif ($auto_prod_changes == '2') {
		$explainText = $lang['design_281'];
	} elseif ($auto_prod_changes == '3') {
		$explainText = $lang['design_288'];
	} elseif ($auto_prod_changes == '4') {
		$explainText = $lang['design_289'];
	}
	$explainText .= " " . $lang['design_282'];
	// Render hidden dialog div
	?>
	<div id="autochangessaved" style="display:none;" title="<?php echo cleanHtml2($lang['design_276']) ?>">
		<div class="darkgreen" style="margin:20px 0;">
			<table cellspacing=8 width=100%>
				<tr>
					<td valign="top" style="padding:15px 30px 0 20px;">
						<img src="<?php echo APP_PATH_IMAGES ?>check_big.png">
					</td>
					<td valign="top" style="font-size:13px;font-family:verdana;padding-right:30px;">
						<?php echo "<b>{$lang['global_79']} {$lang['design_277']}</b><br>{$lang['design_280']}" ?>
						<div style="padding:20px 0 0;">
							<a href="javascript:;" onclick="$('#explainAutoChanges').toggle('fade');" style="font-family:arial;"><?php echo $lang['design_278'] ?></a>
						</div>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<div style="display:none;margin-top:5px;border:1px solid #ccc;padding:8px;font-family:arial;" id="explainAutoChanges"><?php echo $explainText ?></div>
					</td>
				</tr>
			</table>
		</div>	
	</div>
	<script type="text/javascript">
	$(function(){
		$('#autochangessaved').dialog({ bgiframe: true, modal: true, width: 750,  
			buttons: { Close: function() {$(this).dialog('close'); } }
		});
	});
	</script>
	<?php
}

// TABS
include APP_PATH_DOCROOT . "ProjectSetup/tabs.php";

// Check if any notices need to be displayed regarding Draft Mode
include APP_PATH_DOCROOT . "Design/draft_mode_notice.php";


## VIDEO LINK AND SHARED LIBRARY LINK	
// Share instruments to Shared Library (if in Prod and NOT in Draft Mode yet)
$sharedLibLink = "";
if ($shared_library_enabled && $draft_mode == 0 && $status > 0) 
{
	// Create drop-down options
	$sharedLibForms = "";
	foreach ($Proj->forms as $form=>$attr) {
		$sharedLibForms .= "<option value='$form'>{$attr['menu']}";
		if (isset($formStyleVisible[$form])) {
			$sharedLibForms .= " " . $lang['shared_library_69'];
		}
		$sharedLibForms .= "</option>";
	}
	$sharedLibBtnDisabled = (($draft_mode == 0 || (isVanderbilt() && $super_user)) ? "" : "disabled");
	// Output link to page
	$sharedLibLink = RCView::div(array('style'=>'float:left;'), 
						RCView::img(array('src'=>'help.png','style'=>'vertical-align:middle;')) . 
						RCView::a(array('href'=>'javascript:;','style'=>'vertical-align:middle;text-decoration:underline;color:#3E72A8;','onclick'=>"\$('#shareToLibDiv').toggle('fade');"), $lang['setup_69'])
					 );
}
// Display link(s)
print 	RCView::div(array('style'=>'max-width:700px;margin:10px 0;'), 
			$sharedLibLink . 
			RCView::div(array('style'=>'float:right;'), 
				RCView::img(array('src'=>'video_small.png','style'=>'vertical-align:middle;')) . 
				RCView::a(array('href'=>'javascript:;','style'=>'vertical-align:middle;font-size:12px;text-decoration:underline;font-weight:normal;','onclick'=>"window.open('".CONSORTIUM_WEBSITE."videoplayer.php?video=" . (isset($_GET['page']) ? "form_editor_fields01.flv" : "form_editor_upload_dd02.flv") . "&referer=".SERVER_NAME."&title=The Online Designer','myWin','width=1050, height=800, toolbar=0, menubar=0, location=0, status=0, scrollbars=1, resizable=1');"), $lang['design_02'])
			) .
			RCView::div(array('class'=>'clear'), "")
		);

			
// Hidden div containing drop-down list of forms to share to Shared Library -->
print  "<div id='shareToLibDiv' style='display:none;max-width:700px;margin:20px 0;padding:8px;border:1px solid #ccc;background-color:#f5f5f5;'>
			<b>{$lang['setup_69']}</b><br>
			{$lang['setup_70']}
			<a href='javascript:;' style='text-decoration:underline;' onclick=\"openLibInfoPopup('download')\">{$lang['design_250']}</a>
			<div style='padding:5px 0;'>
				<select id='form_names' class='x-form-text x-form-field notranslate' style='padding-right:0;height:22px;'>
					<option value=''>-- {$lang['shared_library_59']} --</option>
					$sharedLibForms
				</select> 
				<button onclick=\"
					if ($('#form_names').val().length < 1){ 
						alert('Please select an instrument'); 
					} else {
						window.location.href = app_path_webroot+'SharedLibrary/index.php?pid='+pid+'&page='+$('#form_names').val(); 
					}
				\">{$lang['design_174']}</button> 
			</div>
		</div>";
			
// 'READY TO ADD QUESTIONS' BOX: For single survey projects, if no questions have been added yet (or if the participant_id is hidden), 
// then give big instructional box to get started.
if (isset($_GET['page']) && $_GET['page'] != "" && count($Proj->metadata) == 2 && $table_pk == "record_id")
{
	?>
	<div id="ready_to_add_questions" class="green" style="margin-top:20px;padding:10px 10px 15px;">
		<div style="text-align:center;font-size:20px;font-weight:bold;padding-bottom:5px;"><?php echo $lang['design_394'] ?></div>
		<div><?php echo $lang['design_393'] ?></div>
	</div>
	<p><?php echo $lang['design_07'] ?></p>
	<script type="text/javascript">
	$(function(){
		setTimeout(function(){
			$('#ready_to_add_questions').hide('blind',1500);
		},20000);
	});
	</script>
	<?php
}






//If user has not selected which form to edit, give them list of forms to choose from

// For Single-survey projects, hide Online Form Editor section where multiple form table is rendered

?>
<!-- jQuery drag and drop script -->
<script type="text/javascript" src="<?php echo APP_PATH_JS ?>jquery_tablednd.js"></script>
<!-- custom script -->
<script type="text/javascript">
// Language vars
var form_moved_msg = (getParameterByName('page') == '') 
	? '<?php echo cleanHtml($lang['design_371']) ?>\n\n<?php echo cleanHtml($lang['design_373']) ?>'
	: '<?php echo cleanHtml($lang['design_372']) ?>\n\n';
var langRecIdFldChanged = '<?php echo cleanHtml($lang['design_400']) ?>';
var langOkay = '<?php echo cleanHtml($lang['design_401']) ?>';
var langCancel = '<?php echo cleanHtml($lang['global_53']) ?>';
var langQuestionMark = '<?php echo cleanHtml($lang['questionmark']) ?>';
var langPeriod = '<?php echo cleanHtml($lang['period']) ?>';
var langSave = '<?php echo cleanHtml($lang['designate_forms_13']) ?>';
var langDelete = '<?php echo cleanHtml($lang['global_19']) ?>';
</script>
<?php





/**
 * CHOOSE A FORM TO EDIT OR ENTER NEW FORM TO CREATE
 */
if (!isset($_GET['page'])) 
{
	// If redirected here from Invite Participants when no surveys have been enabled yet, then display dialog for instructions
	// on how to enable surveys.
	if (isset($_GET['dialog']) && $_GET['dialog'] == 'enable_surveys')
	{
		?>
		<script type="text/javascript">
		$(function(){
			simpleDialog('<?php echo cleanHtml(RCView::b($lang['global_03'].$lang['colon'])." ".$lang['survey_357']) ?>','<?php echo cleanHtml($lang['setup_84']) ?>','how_to_enable_surveys-dialog');
		});
		</script>
		<?php
	}

	// Set flag if some parts of the instrument list table should be disabled to prevent editing because it's not in draft mode yet
	$disableTable = ($draft_mode != '1' && $status > 0);
	
	?>
	<style type="text/css">
	.edit_saved  { background: #C1FFC1 url(<?php echo APP_PATH_IMAGES ?>tick.png) no-repeat right; }
	</style>
	
	<!-- JS for Online Designer (Forms) -->
	<script type="text/javascript">
	// Set vars and functions
	var disable_instrument_table = <?php echo $disableTable ? 1 : 0 ?>;
	// Function to give error message if try to click on form names when not editable
	function cannotEditForm() {
		simpleDialog('<?php echo cleanHtml($lang['design_374']) ?>','<?php echo cleanHtml($lang['design_375']) ?>');
	}
	// Language vars
	var langDrag = '<?php echo cleanHtml($lang['design_366']) ?>';
	var langModSurvey = '<?php echo cleanHtml($lang['survey_315']) ?>';
	var langClickRowMod = '<?php echo cleanHtml($lang['design_367']) ?>';
	var langAddNewFlds = '<?php echo cleanHtml($lang['design_368']) ?>';
	var langDownloadPdf = '<?php echo cleanHtml($lang['design_369']) ?>';
	var langAddInstHere = '<?php echo cleanHtml($lang['design_380']) ?>';
	var langNewInstName = '<?php echo cleanHtml($lang['design_381']) ?>';
	var langCreate = '<?php echo cleanHtml($lang['design_248']) ?>';
	var langYesDelete = '<?php echo cleanHtml($lang['design_397']) ?>';
	var langDeleteFormSuccess = '<?php echo cleanHtml($lang['design_398']) ?>';
	var langDeleted = '<?php echo cleanHtml($lang['create_project_102']) ?>';
	var langNotDeletedRand = '<?php echo cleanHtml($lang['design_399']) ?>';
	var langNo = '<?php echo cleanHtml($lang['design_99']) ?>';
	var langRemove2Bchar = '<?php echo cleanHtml($lang['design_79']) ?>';
	var langProvideInstName = '<?php echo cleanHtml($lang['design_382']) ?>';
	var langInstrCannotBeginNum = '<?php echo cleanHtml($lang['design_383']) ?>';
	var langInstrCreated1 = '<?php echo cleanHtml($lang['design_395']) ?>';
	var langInstrCreated2 = '<?php echo cleanHtml($lang['design_396']) ?>';
	var langSetSurveyTitleAsForm1 = '<?php echo cleanHtml($lang['design_402']) ?>';
	var langSetSurveyTitleAsForm2 = '<?php echo cleanHtml($lang['design_403']) ?>';
	var langSetSurveyTitleAsForm3 = '<?php echo cleanHtml($lang['design_404']) ?>';
	var langSetSurveyTitleAsForm4 = '<?php echo cleanHtml($lang['design_405']) ?>';
	var langSetSurveyTitleAsForm5 = '<?php echo cleanHtml($lang['design_406']) ?>';
	var langSetSurveyTitleAsForm6 = '<?php echo cleanHtml($lang['design_407']) ?>';
	var langAutoInvite1 = '<?php echo cleanHtml($lang['design_408']) ?>';
	var langAutoInvite2 = '<?php echo cleanHtml($lang['design_409']) ?>';
	var langAutoInvite3 = '<?php echo cleanHtml($lang['design_410']) ?>';	
	var langAutoInvite4 = '<?php echo cleanHtml($lang['email_users_01']) ?>';	
	var langAutoInvite5 = '<?php echo cleanHtml($lang['survey_451']) ?>';
	var langAutoInvite6 = '<?php echo cleanHtml($lang['survey_452']) ?>';
	var langAutoInvite7 = '<?php echo cleanHtml($lang['survey_453']) ?>';
	var langAutoInvite8 = '<?php echo cleanHtml($lang['survey_454']) ?>';
	var langAutoInvite9 = '<?php echo cleanHtml($lang['survey_455']) ?>';
	var langAutoInvite10 = '<?php echo cleanHtml($lang['survey_456']) ?>';
	var langAutoInvite11 = '<?php echo cleanHtml($lang['survey_457']) ?>';
	var langAutoInvite12 = '<?php echo cleanHtml($lang['survey_458']) ?>';
	</script>
	<script type="text/javascript" src="<?php echo APP_PATH_JS ?>DesignForms.js"></script>
	
	<!-- Instructions -->
	<p>
		<?php 
		print "{$lang['design_377']} ";
		if ($status < 1) {
			print "<u>{$lang['global_02']}{$lang['colon']} {$lang['design_27']}</u>{$lang['period']}";
		} else {
			print ($draft_mode == '1') ? $lang['design_378'] : $lang['design_379'];
			if ($surveys_enabled) {
				print " " . $lang['design_384'];
			}
		}
		?>
	</p>
	
	<?php
		
	// Check if event_id exists in URL. If not, then this is not "longitudinal" and has one event, so retrieve event_id.
	if (!$longitudinal && (!isset($_GET['event_id']) || $_GET['event_id'] == "" || !is_numeric($_GET['event_id']))) 
	{
		$_GET['event_id'] = getSingleEvent($project_id);
	}
	
	## INSTRUMENT TABLE
	// Initialize vars
	$row_data = array();
	$stdmap_btn = ""; //default
	$row_num = 0; // loop counter
	// Create array of form_names that have automated invitations set for them (not checking more granular at event_id level)
	// Each form will have 0 and 1 subcategory to count number of active(1) and inactive(0) schedules for each.
	$formsWithAutomatedInvites = Design::formsWithAutomatedInvites();
	// Query to get form names to display in table
	$sql = "select form_name, max(form_menu_description) as form_menu_description, count(1)-1 as field_count 
			from redcap_metadata".(($draft_mode > 0 && $status > 0) ? "_temp" : "")." where project_id = $project_id 
			group by form_name order by field_order";
	$q = db_query($sql);
	// Loop through each instrument
	while ($row = db_fetch_assoc($q)) 
	{
		$row['form_menu_description'] = strip_tags(label_decode($row['form_menu_description']));
		// Give question mark if form menu name is somehow lost and set to ""
		if ($row['form_menu_description'] == "") $row['form_menu_description'] = "[ ? ]";
		// If survey exists, see if it's offline or active to determine the image to display
		if (isset($Proj->forms[$row['form_name']]['survey_id'])) {
			$enabledSurveyImg = ($Proj->surveys[$Proj->forms[$row['form_name']]['survey_id']]['survey_enabled']) ? "tick_small_circle.png" : "bullet_delete.png";
		}
		// Show survey options (render but hide for all rows, then show only for first row)
		$enabledSurvey = (!isset($Proj->forms[$row['form_name']]['survey_id']))
						? 	"<button class='jqbuttonsm' style='color:green;' onclick=\"window.location.href=app_path_webroot+'Surveys/create_survey.php?pid='+pid+'&view=showform&page={$row['form_name']}&redirectDesigner=1';\">{$lang['survey_152']}</button>" 
						:	"<a class='modsurvstg' href='".APP_PATH_WEBROOT."Surveys/edit_info.php?pid=$project_id&view=showform&page={$row['form_name']}&redirectDesigner=1' style='display:block;text-align:center;'><img src='".APP_PATH_IMAGES."tick_shield_small.png' class='imgfix1'></a>";
		$modifySurveyBtn = (!isset($Proj->forms[$row['form_name']]['survey_id'])) 
						? 	"" 
						: 	"<button class='jqbuttonsm' onclick=\"window.location.href=app_path_webroot+'Surveys/edit_info.php?pid='+pid+'&view=showform&page={$row['form_name']}&redirectDesigner=1';\"><img src='".APP_PATH_IMAGES."$enabledSurveyImg' class='imgfix1'> {$lang['survey_314']}</button>" .
							" <button class='jqbuttonsm' onclick=\"displayTrigNotifyPopup({$Proj->forms[$row['form_name']]['survey_id']});\"><img src='".APP_PATH_IMAGES."mail_small2.png'> {$lang['control_center_116']}</button>";
		// AUTO INVITES BTN: Show button to define conditions for automated invitations (but only for surveys and not for first instrument)
		$defineSurveyConditionsBtn = "";
		if (isset($Proj->forms[$row['form_name']]['survey_id'])) {
			// Set event_id (set as 0 for longitudinal so we can prompt user to select event after clicking button here)
			$surveyCondBtnEventId = ($repeatforms) ? '0' : $Proj->firstEventId;
			// Set image of checkmark if already enabled
			$automatedInvitesEnabledImg = '';
			$automatedInvitesEnabledClr = '';
			if (isset($formsWithAutomatedInvites[$row['form_name']])) {
				if ($formsWithAutomatedInvites[$row['form_name']]['1'] > 0) {
					$automatedInvitesEnabledImg .= RCView::img(array('src'=>'tick_small_circle.png','class'=>'imgfix1'));
					$automatedInvitesEnabledClr = 'color:green;';
				}
				if ($formsWithAutomatedInvites[$row['form_name']]['0'] > 0) {
					$automatedInvitesEnabledImg .= RCView::img(array('src'=>'bullet_delete.png','class'=>'imgfix1'));
					if (!$longitudinal || ($longitudinal && $formsWithAutomatedInvites[$row['form_name']]['1'] == 0)) {
						$automatedInvitesEnabledClr = 'color:#800000;';
					}
				}
			} else {
				$automatedInvitesEnabledImg = RCView::span(array('style'=>'margin-right:2px;'), "+");
			}
			// Set button html
			$defineSurveyConditionsBtn = "<button id='autoInviteBtn-{$row['form_name']}' class='jqbuttonsm' style='$automatedInvitesEnabledClr' onclick=\"setUpConditionalInvites({$Proj->forms[$row['form_name']]['survey_id']},$surveyCondBtnEventId,'{$row['form_name']}');\">{$automatedInvitesEnabledImg}{$lang['survey_342']}</button>";
		}
		// Invisible 'saved!' tag that only shows when update form order (dragged it)
		$saveMoveTag = "<span id='savedMove-{$row['form_name']}' style='display:none;margin-left:20px;color:red;'>{$lang['design_243']}</span>";
		// Invisible 'pencil/edit' icon to appear next to instrument name when mouseover
		$instrEditIcon = "<span class='instrEdtIcon' style='display:none;margin-left:6px;'><img src='".APP_PATH_IMAGES."pencil_small2.png'></span>";
		// Set HTML for rename and delete form buttons
		if ($disableTable) {
			$formActionBtns = "";
		} else {
			$formActionBtns =  "<button class='jqbuttonsm' onclick=\"setupRenameForm('{$row['form_name']}');\"><img src='".APP_PATH_IMAGES."redo.png' class='imgfix1'> {$lang['design_241']}</button>
								<button class='jqbuttonsm' onclick=\"deleteForm('{$row['form_name']}');\"><img src='".APP_PATH_IMAGES."cross_small2.png' class='imgfix1'> {$lang['design_242']}</button>";
		}
		// STANDARDS MAPPING BUTTON (currently not used)
		// if (isDev(true)) {			
			// $stdmap_btn = "<button class='jqbuttonsm' style='margin:3px 0 1px;' onclick=\"window.location.href=app_path_webroot+'StandardsMapping/index.php?pid='+pid+'&page={$row['form_name']}';\"><img src='".APP_PATH_IMAGES."bookmark_small.png' class='imgfix1'> {$lang['design_228']}</button>";
		// }
		// Add this form
		$row_data[$row_num][] = "<span style='display:none;'>{$row['form_name']}</span>";
		if ($disableTable) {
			// Display form name as simple text
			$row_data[$row_num][] = RCView::div(array('onclick'=>"cannotEditForm()"),
										RCView::escape($row['form_menu_description'])
									);
		} else {
			// Display form name as link with hidden input for renaming
			$row_data[$row_num][] = "<div id='form_menu_description_input_span-{$row['form_name']}' style='display:none;'>
										<input type='text' value='".htmlspecialchars($row['form_menu_description'], ENT_QUOTES)."' maxlength='200'
											onblur='this.value=trim(this.value);'
											onkeydown=\"if(event.keyCode==13){
												this.value = trim(this.value);
												if (this.value.length < 1 || checkIsTwoByte(this.value)) return false;
												setFormMenuDescription('{$row['form_name']}',".(isset($Proj->forms[$row['form_name']]['survey_id']) ? 1 : 0).");
												}\" 
											id='form_menu_description_input-{$row['form_name']}' class='x-form-text x-form-field' style='width:230px;'
										>&nbsp;
										<input type='button' value=' ".cleanHtml($lang['designate_forms_13'])." ' style='font-size:11px;' id='form_menu_save_btn-{$row['form_name']}' onclick=\"
											setFormMenuDescription('{$row['form_name']}',".(isset($Proj->forms[$row['form_name']]['survey_id']) ? 1 : 0).");
										\">	&nbsp;&nbsp;
										<img src='".APP_PATH_IMAGES."progress_small.gif' class='imgfix' style='visibility:hidden;' id='progress-{$row['form_name']}'>
									</div>								
									<div class='notranslate' style='font-size:12px;padding:0;'>
										<a class='formLink' style='padding:3px;display:block;' href='".PAGE_FULL."?pid=$project_id&page={$row['form_name']}'
											><span id='formlabel-{$row['form_name']}'>{$row['form_menu_description']}</span>{$instrEditIcon}{$saveMoveTag}</a>
									</div>";
		}
		$row_data[$row_num][] = $row['field_count'];
		$row_data[$row_num][] = "<a href='".APP_PATH_WEBROOT."PDF/index.php?pid=$project_id&page={$row['form_name']}".(($status > 0 && $draft_mode == 1) ? "&draftmode=1" : "")."'><img class='pdficon' src='".APP_PATH_IMAGES."pdf.gif' class='imgfix1'></a>";
		// Display "enabled as survey" column
		if ($surveys_enabled) {
			$row_data[$row_num][] = $enabledSurvey;
		}
		// Instrument actions column
		$row_data[$row_num][] = "<span class='formActions'>
									$formActionBtns
									$stdmap_btn
								 </span>";
		// Display survey-related options
		if ($surveys_enabled) {
			$row_data[$row_num][] = "<span id='{$row['form_name']}-btns' class='formActions'>
										$modifySurveyBtn
										$defineSurveyConditionsBtn
									 </span>";
		}
		// Increment counter
		$row_num++;
	}
	
	// Set table headers and attributes
	$col_widths_headers = array();
	$col_widths_headers[] = array(15, "", "center");
	$col_widths_headers[] = array(($surveys_enabled ? 300 : 495), RCView::SP . RCView::b($lang['design_244']));
	$col_widths_headers[] = array(34,  $lang['home_32'], "center");
	$col_widths_headers[] = array(25,  RCView::div(array('style'=>'line-height:11px;padding:2px 0;'), $lang['global_84'].RCView::br().$lang['global_85']), "center");
	if ($surveys_enabled) {
		$col_widths_headers[] = array(54, RCView::div(array('style'=>'line-height:11px;padding:2px 0;'), $lang['design_365'].RCView::br().$lang['global_59']), "center");
	}
	$col_widths_headers[] = array(120, RCView::div(array('style'=>'line-height:11px;padding:2px 0;'), $lang['design_389']), "center");
	if ($surveys_enabled) {
		$col_widths_headers[] = array(320, RCView::div(array('style'=>'line-height:11px;padding:2px 0;'), $lang['design_390']));
	}
	
	// Set table width
	$instTableWidth = ($surveys_enabled ? 930 : 750);
	
	// Set table title display
	$instTableTitle = " <table cellspacing=0 width=100%>
							<tr>
								<td>
									<span style='color:#000;font-size:14px;'>{$lang['global_36']}</span>
								</td>
								<td style='width:150px;text-align:right;padding:5px 5px 0 60px;color:#666;visibility:" . ($disableTable ? "hidden" : "visible") . ";' valign='top'>
									{$lang['design_199']}
								</td>
								<td style='font-weight:normal;visibility:" . ($disableTable ? "hidden" : "visible") . ";' valign='top'>
									<div style='padding:1px;'>
										<input type='button' class='jqbuttonmed' onclick='showAddForm()' style='font-size:11px;color:green;' value=' {$lang['design_248']} '>
										{$lang['design_249']}
									</div>
									<div style='padding:1px;display:" . ($shared_library_enabled ? "block" : "none") . ";'>
										".renderBrowseLibraryForm()."
										<input type='button' class='jqbuttonmed' value=' {$lang['design_173']} ' style='font-size:11px;color:green;' onclick=\"$('form#browse_rsl').submit();\"> 
										{$lang['design_40']}
										<a href='javascript:;' onclick=\"openLibInfoPopup('download')\"><img src='".APP_PATH_IMAGES."help.png' class='imgfix' title='{$lang['design_250']}'></a>
									</div>
								</td>
							</tr>
						</table>";
	renderGrid("forms_surveys", $instTableTitle, $instTableWidth, 'auto', $col_widths_headers, $row_data, true, false);
	
	
	// Invisible div used for Deleting a form dialog
	print  "<div style='display:none;' id='delete_form_dialog' title='".cleanHtml($lang['design_44'])."'>
			{$lang['design_42']} \"<b id='del_dialog_form_name'></b>\" {$lang['design_43']}
			</div>";
			
	// AUTOMATED INVITATIONS: Hidden div containing list of events for user to choose from when setting up Automated Invitations (longitudinal only)
	if ($repeatforms) 
	{
		// Display hidden div
		print 	RCView::div(array('id'=>'choose_event_div'), 
					RCView::div(array('id'=>'choose_event_div_sub'), 
						RCView::div(array('style'=>'float:left;color:#800000;width:280px;min-width:280px;font-weight:bold;font-size:13px;padding:6px 3px 5px;margin-bottom:3px;border-bottom:1px solid #ccc;'), 
							$lang['survey_342'] . 
							RCView::div(array('style'=>'padding:3px 0;color:#555;font-size:12px;font-weight:normal;'), 
								$lang['design_386']
							)
						) .
						RCView::div(array('style'=>'float:right;width:20px;padding:3px 0 0 3px;'), 
							RCView::a(array('onclick'=>"$('#choose_event_div').fadeOut('fast');",'href'=>'javascript:;'),
								RCView::img(array('src'=>'delete_box.gif'))
							)
						) .
						RCView::div(array('class'=>'clear'), '') .
						RCView::div(array('id'=>'choose_event_div_loading','style'=>'padding:8px 3px;color:#555;'), 
							RCView::img(array('src'=>'progress_circle.gif', 'class'=>'imgfix')) . RCView::SP . 
							$lang['data_entry_64']
						) . 
						RCView::div(array('id'=>'choose_event_div_list','style'=>'padding:3px 6px;display:none;'), "")
					)
				);
	}
}















/**
 * FORM WAS SELECTED - SHOW FIELDS
 */
elseif (isset($_GET['page']) && $_GET['page'] != "") 
{
	// Instructions
	print  "<p style='margin:0;'>
				{$lang['design_45']} <span style='color:#800000;'>{$lang['design_309']}</span> 
				{$lang['design_47']} <img src='".APP_PATH_IMAGES."pencil.png' style='vertical-align:middle;'> 
				{$lang['design_48']} <img src='".APP_PATH_IMAGES."cross.png' style='vertical-align:middle;'> 
				{$lang['design_49']}
				" . (($status < 1) ? "<u>{$lang['global_02']}: {$lang['design_27']}</u>." : "") . "
			</p>";
	
	// Show "previous page" link if editing a form
	print "<p style='margin:20px 0 10px;'>";
	print "<button class='jqbutton' onclick=\"window.location.href=app_path_webroot+page+'?pid='+pid;\"><img src='".APP_PATH_IMAGES."arrow_left.png' style='vertical-align:middle;'> <span style='vertical-align:middle;'>{$lang['design_247']}</span></button>";
	print "</p>";
	
	?>
	<!-- Hidden pop-up div to display tooltip when mistakenly trying to drag a matrix field (which should not occur) -->
	<div id='tooltipMoveMatrix' class='tooltip' style='max-width:250px;padding:0px 6px 3px;z-index:9999;'>
		<div style="float:left;font-weight:bold;padding:10px 0 4px;vertical-align:bottom;font-size:13px;">
			<img src="<?php echo APP_PATH_IMAGES ?>exclamation_frame.png" style="vertical-align:bottom;">
			<?php echo $lang['design_431'] ?>
		</div>
		<div style="float:right;"><a href="javascript:;" onclick="$('#tooltipMoveMatrix').hide();" style="text-decoration:underline;font-size:10px;">[Close]</a></div>
		<div style='clear:both;'><?php echo $lang['design_323'] ?></div>
		<div style='padding-top:8px;'><?php echo $lang['design_354'] ?></div>
	</div>
	<!-- Hidden pop-up div to display tooltip when mistakenly trying to drag the PK field (which should not occur) -->
	<div id='tooltipMovePk' class='tooltip' style='max-width:250px;padding:0px 6px 10px;z-index:9999;'>
		<div style="float:left;font-weight:bold;padding:10px 0 4px;vertical-align:bottom;font-size:13px;">
			<img src="<?php echo APP_PATH_IMAGES ?>exclamation_frame.png" style="vertical-align:bottom;">
			<?php echo $lang['design_431'] ?>
		</div>
		<div style="float:right;"><a href="javascript:;" onclick="$('#tooltipMovePk').hide();" style="text-decoration:underline;font-size:10px;">[Close]</a></div>
		<div style='clear:both;'><?php echo $lang['design_430'] ?></div>
	</div>
	<?php
		
	// Render javascript putting all form names in an array to prevent users from creating form+"_complete" field name, which is illegal
	print  "<script type='text/javascript'>
			var allForms = new Array('" . implode("','", array_keys($Proj->forms)) . "');
			</script>";
	
	//Get descriptive form name of selected form
	if (isset($_GET['newform'])) {
		$this_form_menu_description = filter_tags($_GET['newform']);
		$editFormMenu = "<div style='color:#800000;font-size:10px;font-family:tahoma;'>
							({$lang['global_02']}: {$lang['design_51']})
						 </div>";
	} else {
		$sql = "select form_menu_description from $metadata_table where project_id = $project_id and form_name = '{$_GET['page']}' "
			 . "and form_menu_description is not null limit 1";
		$this_form_menu_description = filter_tags(db_result(db_query($sql), 0));
		if ($this_form_menu_description == "") $this_form_menu_description = "[{$lang['global_01']}: {$lang['design_52']}]";
		$editFormMenu = "";
	}
	
				
	print  "<div style='padding:20px 0 10px 0;max-width:700px;'>
			<table cellspacing=0 width=100%>
			<tr>
				<td valign='top'>
					<span style='color:#666;font-size:14px;'>{$lang['design_54']} </span>
					<span id='form_menu_description_label' class='notranslate' 
						style='display:;color:#800000;font-size:16px;font-weight:bold;'>$this_form_menu_description</span>
					$editFormMenu
				</td>";
	// Show buttons to preview instrument/survey (but not if instrument does not exist yet)
	if (!isset($_GET['newform'])) 
	{
		print  "<td valign='top' style='text-align:right;'>
					<button class='jqbuttonmed' id='showpreview1' href='javascript:;' onclick='previewInstrument(1)'>{$lang['design_55']}</button>
					<button class='jqbuttonmed' id='showpreview0' href='javascript:;' style='display:none;' onclick='previewInstrument(0)'>{$lang['design_56']}</button>
				</td>";
	}			
	print  "
		</tr>
		<tr id='blcalc-warn' style='display:none;'>
			<td valign='top' colspan='2' class='yellow' style='font-family:arial;'>
				{$lang['design_246']}
			</td>
		</tr>
		</table>
		</div>";
	
	?>
	<style type="text/css">
	.label, .label_matrix, .data, .data_matrix { 
		border:0; background:#f3f3f3; padding:2px 5px 6px 5px;
	}
	.data  { max-width:400px; width:340px; }
	.header{ border:0; }
	</style>
	<?php
	
	// Render the table of fields
	print  "<div id='draggablecontainer_parent'>";
	include APP_PATH_DOCROOT . "Design/online_designer_render_fields.php";
	print  "</div>";
	
	
	/**
	 * ADD/EDIT MATRIX OF FIELDS POP-UP
	 */
	// For single survey or survey+forms project, see if custom question numbering is enabled for this survey
	$matrixQuesNumHdr = "";
	$matrixQuesNumRow = "";
	if (($surveys_enabled) && isset($Proj->forms[$_GET['page']]['survey_id']) 
		&& !$Proj->surveys[$Proj->forms[$_GET['page']]['survey_id']]['question_auto_numbering'])
	{
		$matrixQuesNumHdr = "<td valign='bottom' class='addFieldMatrixRowQuesNum'>
								{$lang['design_342']}
								<div style='color:#888;font-size:10px;font-weight:normal;font-family:tahoma;'>{$lang['survey_251']}</div>
							</td>";
		$matrixQuesNumRow = "<td class='addFieldMatrixRowQuesNum'>
								<input type='text' class='x-form-text x-form-field field_quesnum_matrix' style='width:35px;' maxlength='10'>
							</td>";
	}
	// Iframe for catching post data when adding Matrix fields
	print  "<iframe id='addMatrixFrame' name='addMatrixFrame' src='".APP_PATH_WEBROOT."DataEntry/empty.php' style='width:0;height:0;border:0px solid #fff;'></iframe>";
	// Hidden div for adding/editing Matrix fields dialog
	print  "<div id='addMatrixPopup' title='".cleanHtml($lang['design_307'])."' style='display:none;background-color:#f5f5f5;'>
				<div style='margin:10px 0 15px;'>
					{$lang['design_310']}
					<a href='javascript:;' style='text-decoration:underline;' onclick=\"showMatrixExamplePopup();\">{$lang['design_355']}</a> {$lang['global_47']}
					<a href='javascript:;' style='text-decoration:underline;' onclick=\"helpPopup('matrix');\">{$lang['design_358']}</a>
				</div>
				<div style='background:#FFFFE0;border: 1px solid #d3d3d3;padding:5px 8px 8px; margin-top: 10px;'>
					<!-- Section Header -->
					<div class='addFieldMatrixRowHdr' style='margin-bottom:6px;'>{$lang['design_322']}</div>
					<textarea id='section_header_matrix' class='x-form-textarea x-form-field' style='height:34px;width:95%;position:relative;'></textarea>
				</div>
				<div style='border: 1px solid #d3d3d3; background-color: #eee; padding:5px 8px 8px; margin-top: 10px;'>
					<!-- Headers -->
					<div>
						<div class='addFieldMatrixRowHdr' style='float:left;margin:0;'>
							{$lang['design_316']}
						</div>						
						<div style='float:right;padding-right:2px;'>
							<span id='auto_variable_naming_matrix_saved' style='visibility:hidden;text-align:center;font-size:9px;color:red;font-weight:bold;'>{$lang['design_243']}</span>
							<input type='checkbox' id='auto_variable_naming_matrix' " . ($auto_variable_naming ? "checked" : "") . ">
							<span style='line-height:11px;color:#800000;font-family:tahoma;font-size:10px;font-weight:normal;' class='opacity75'>{$lang['design_267']}</span>
						</div>	
						<div class='clear'></div>	
						<div style='color:#777;font-size:11px;font-weight:normal;'>{$lang['design_341']}</div>				
						<table cellspacing=0 style='width:100%;table-layout:fixed;'>
							<tr>
								<td valign='bottom' class='addFieldMatrixRowDrag'>&nbsp;</td>
								<td valign='bottom'  class='addFieldMatrixRowLabel'>{$lang['global_40']}</td> 
								<td valign='bottom'  class='addFieldMatrixRowVar'>
									{$lang['global_44']}
									<div style='color:#888;font-size:10px;font-weight:normal;font-family:tahoma;'>{$lang['design_80']}</div>
								</td>
								$matrixQuesNumHdr
								<td valign='bottom' class='addFieldMatrixRowFieldReq'>{$lang['design_98']}</td>
								<td valign='bottom' class='addFieldMatrixRowDel'></td>
							</tr>
						</table>
					</div>
					
					<!-- Row with Label/Variable inputs -->		
					<table class='addFieldMatrixRowParent' cellspacing=0 style='width:100%;table-layout:fixed;'>
						<tr class='addFieldMatrixRow'>
							<td class='addFieldMatrixRowDrag dragHandle'></td>
							<td class='addFieldMatrixRowLabel'>
								<input class='x-form-text x-form-field field_label_matrix' autocomplete='off' onkeydown='if(event.keyCode==13) return false;'>
							</td> 
							<td class='addFieldMatrixRowVar'>
								<input class='x-form-text x-form-field field_name_matrix' autocomplete='off' maxlength='100' onkeydown='if(event.keyCode==13) return false;'>
							</td>
							$matrixQuesNumRow							
							<td class='addFieldMatrixRowFieldReq'>
								<input type='checkbox' class='field_req_matrix'>
							</td>
							<td class='addFieldMatrixRowDel'>
								<a href='javascript:;' style='text-decoration:underline;font-size:10px;font-family:tahoma;' onclick='delMatrixRow(this)'><img src='".APP_PATH_IMAGES."cross.png' style='vertical-align:middle;' title='Delete Field'></a>
							</td>
						</tr>
					</table>
					
					<div style='padding:5px 0 0 30px;'>
						<button id='addMoreMatrixFields' style='font-size:11px;font-family:arial;' onclick='return false;'>{$lang['design_314']}</button>
					</div>
				</div>
				<div>
					<!-- Choices -->	
					<div style='background-color: #eee; float:left;width:350px;border: 1px solid #d3d3d3; padding:5px 8px 8px; margin:10px 10px 0 0;'>
						<div class='addFieldMatrixRowHdr'>{$lang['design_317']}</div>						
						<div style='font-weight:bold;'>{$lang['design_71']}</div>
						<textarea class='x-form-textarea x-form-field' style='height:120px;width:95%;position:relative;' id='element_enum_matrix' 
							name='element_enum_matrix'/></textarea>
						<div id='manualcode-label' style='padding-right:25px;'>
							<a href='javascript:;' style='color:#3089D4;font-size:11px;' onclick=\"
								$('#div_manual_code_matrix').toggle('blind',{},500);
							\">{$lang['design_72']}</a>
						</div>
						<div id='div_manual_code_matrix' style='border:1px solid #ddd;font-size:11px;padding:5px 15px 5px 5px;display:none;'>
							{$lang['design_73']} {$lang['design_296']} 
							<div style='color:#800000;'>
								0, {$lang['design_311']}<br>
								1, {$lang['design_312']}<br>
								2, {$lang['design_313']}
							</div>
						</div>										
					</div>
					<!-- Matrix Info -->
					<div style='background-color: #eee; float:left;font-weight:bold;border: 1px solid #d3d3d3; padding:5px 15px 8px 8px; margin-top: 10px;'>	
						<div class='addFieldMatrixRowHdr''>{$lang['design_318']}</div>
						<div>
							<div>{$lang['design_340']}</div>
							<select id='field_type_matrix' class='x-form-text x-form-field' 
								style='padding-right:0;height:22px;'>
								<option value='radio'>{$lang['design_319']}</option>
								<option value='checkbox'>{$lang['design_339']}</option>
							</select>
						</div>
						<!-- Matrix group name -->
						<div style='margin:20px 0 0;'>
							<div>{$lang['design_300']} <span style='margin-left:10px;color:#777;font-size:11px;font-weight:normal;'>{$lang['design_80']}</span></div>
							<input type='text' class='x-form-text x-form-field' style='width:160px;' maxlength='60' id='grid_name'>
							<a href='javascript:;' class='mtxgrpHelp'>{$lang['design_303']}</a>
						</div>
					</div>
					<!-- Hidden fields -->
					<input type='hidden' id='old_grid_name' value=''>
					<input type='hidden' id='old_matrix_field_names' value=''>
					<div class='clear'></div>
				</div>
			</div>";
	
	/**
	 * ADD/EDIT FIELD POP-UP
	 */
	// Iframe for catching post data when adding/editing fields
	print  "<iframe id='addFieldFrame' name='addFieldFrame' src='".APP_PATH_WEBROOT."DataEntry/empty.php' style='width:0;height:0;border:0px solid #fff;'></iframe>";
	// Hidden div for adding/editing fields dialog
	print  "<div id='div_add_field' title='".cleanHtml($lang['design_57'])."' style='display:none;background-color:#f5f5f5;'>
			<div id='div_add_field2'>
				<form enctype='multipart/form-data' target='addFieldFrame' method='post' action='".APP_PATH_WEBROOT."Design/edit_field.php?pid=$project_id&page={$_GET['page']}' name='addFieldForm' id='addFieldForm'>
					<input type='hidden' id='wasSectionHeader' name='wasSectionHeader' value='0'>
					<p>
						{$lang['design_58']}
						<img src='" . APP_PATH_IMAGES . "video_small.png' style='vertical-align:middle;'> 
						<a onclick=\"popupvid('field_types02.flv','REDCap Project Field Types');\" href=\"javascript:;\" style=\"font-size:12px;text-decoration:underline;font-weight:normal;\">{$lang['design_59']}</a>.
					</p>
					<div id='add_field_settings' style='font-family:arial;padding-top:5px;'>
					
						<b>{$lang['design_61']}</b>&nbsp;
						<select name='field_type' id='field_type' onchange='selectQuesType()' class='x-form-text x-form-field' 
							style='padding-right:0;height:22px;'>
							<option value=''> ---- {$lang['design_60']} ---- </option>
							<option value='text'>{$lang['design_62']}</option>
							<option value='textarea'>{$lang['design_63']}</option>
							<option value='calc'>{$lang['design_64']}</option>
							<option value='select'>{$lang['design_66']}</option>
							<option value='radio' grid='0'>{$lang['design_65']}</option>
							<option value='checkbox' grid='0'>{$lang['design_67']}</option>
							<option value='yesno'>{$lang['design_184']}</option>
							<option value='truefalse'>{$lang['design_185']}</option>
							<option value='slider'>{$lang['design_181']}</option>
							<option value='file'>{$lang['design_68']}</option>
							<option value='descriptive'>{$lang['design_189']}</option>
							<option value='section_header'>{$lang['design_69']}</option>
						</select>
						
						<div id='quesTextDiv' style='visibility: hidden;' class='quesDivClass'>
							<table>
							<tr>
								<td valign='top' style='width: 50%;'>";
	// For single survey or survey+forms project, see if custom question numbering is enabled for this survey
	if (($surveys_enabled) && isset($Proj->forms[$_GET['page']]['survey_id']) 
		&& !$Proj->surveys[$Proj->forms[$_GET['page']]['survey_id']]['question_auto_numbering'])
	{
		// Render text box for question auto numbering
		print  "					<div id='div_question_num' style='padding-top:15px;'>
										<b>{$lang['design_221']}</b>
										<span style='color:#505050;font-size:11px;'>{$lang['global_06']}</span>&nbsp;
										<input type='text' class='x-form-text x-form-field' style='width:60px;' maxlength='10' id='question_num' name='question_num'>
										<div style='padding-left:2px;color:#808080;font-size:10px;font-family:tahoma;position:relative;top:-6px;'>
											{$lang['design_222']}
										</div>
									</div>";
	}					
	print  "						<div>
										<div style='font-weight: bold; padding-top: 10px;'>{$lang['global_40']}</div>
										<textarea class='x-form-textarea x-form-field' style='height:120px;width:320px;' id='field_label' 
											name='field_label'/></textarea>
									</div>
						
									<div id='slider_labels' style='display:none;margin-top:20px;'>
										<b>{$lang['design_190']}</b>
										<table cellspacing='3' width='100%'>
											<tr>
												<td>
													{$lang['design_191']} 
												</td>
												<td>
													<input type='text' class='x-form-text x-form-field' style='width:120px;' maxlength='200' id='slider_label_left' name='slider_label_left'>
												</td>
											</tr>
											<tr>
												<td>
													{$lang['design_192']} 
												</td>
												<td>
													<input type='text' class='x-form-text x-form-field' style='width:120px;' maxlength='200' id='slider_label_middle' name='slider_label_middle'>
												</td>
											</tr>
											<tr>
												<td>
													{$lang['design_193']} 
												</td>
												<td>
													<input type='text' class='x-form-text x-form-field' style='width:120px;' maxlength='200' id='slider_label_right' name='slider_label_right'>
												</td>
											</tr>
											<tr>
												<td>
													{$lang['design_194']} 
												</td>
												<td>
													<input type='checkbox' valign='middle' id='slider_display_value' name='slider_display_value'>
												</td>
											</tr>
										</table>
									</div>
									
									<div id='div_pk_field_info' style='display:none;color:#C00000;font-size:11px;line-height:12px;padding:5px 20px 0 5px;'>	
										<b>{$lang['global_02']}{$lang['colon']}</b> {$lang['design_434']} 
										".($auto_inc_set ? $lang['design_435'] : "")."
									</div>
									
									<div id='div_element_enum' style='display:none;'>									
										<div style='padding-top:15px;font-weight:bold;'>
											<span id='choicebox-label-mc' style='display:none;'>{$lang['design_71']}</span>
											<span id='choicebox-label-calc' style='display:none;'>
												{$lang['design_163']} &nbsp;&nbsp;
												<a href='javascript:;' onclick=\"helpPopup('CalculatedFields');\" style='font-weight:normal;color:#3089D4;font-size:11px;'>{$lang['design_165']}</a>
											</span>
											<span id='choicebox-label-sql' style='display:none;'>
												{$lang['design_164']}&nbsp;&nbsp;
												<a href='https://iwg.devguard.com/trac/redcap/wiki/SQLFieldType' target='_blank' style='color:#3089D4;font-size:11px;' >{$lang['form_renderer_02']}</a>
											</span>
										</div>
										<textarea class='x-form-textarea x-form-field' style='height:120px;width:320px;position:relative;' id='element_enum' 
											name='element_enum'/></textarea>
										<div id='manualcode-label' style='text-align:right;padding-right:25px;'>
											<a href='javascript:;' style='color:#3089D4;font-size:11px;' onclick=\"
												$('#div_manual_code').toggle('blind',{},500);
											\">{$lang['design_72']}</a>
										</div>
										<div id='div_manual_code' style='border:1px solid #ddd;font-size:11px;padding:5px 15px 5px 5px;display:none;'>
											{$lang['design_73']} {$lang['design_296']} 
											<div style='color:#800000;'>
												0, {$lang['design_74']}<br>
												1, {$lang['design_75']}<br>
												2, {$lang['design_76']}
											</div>
										</div>	
										<br>											
									</div>
								</td><td valign='top' style='width: 50%;'>
									<div id='righthand_fields'>
									
										<div id='div_var_name' style='border: 1px solid #d3d3d3; padding: 4px 4px 2px 8px; margin-top: 20px;'>
											<b>{$lang['global_44']}</b> <span style='color: #777; font-size: 11px;'>{$lang['design_78']}</span><br/>
											<table cellspacing=0 width=100%>
												<tr>
													<td valign='top' style='color: #888; font-size: 10px;'>
														<input class='x-form-text x-form-field' autocomplete='off' maxlength='100' size='25' 
															id='field_name' name='field_name' 
															onkeydown='if(event.keyCode==13) return false;'
															onfocus='chkVarFldDisabled()' onclick='chkVarFldDisabled()'><br/>
														{$lang['design_80']}
													</td>
													<td valign='top' style='text-align:right;padding:2px 4px 0px 8px;'>
														<input type='checkbox' id='auto_variable_naming' " . ($auto_variable_naming ? "checked" : "") . ">
														<div id='auto_variable_naming_saved' style='padding-top:2px;visibility:hidden;font-weight:bold;text-align:center;font-size:9px;color:red;'>{$lang['design_243']}</div>
													</td>
													<td valign='top' style='line-height:11px;padding:2px 0 0;color:#800000;font-family:tahoma;font-size:10px;' class='opacity75'>
														{$lang['design_267']}
													</td>
												</tr>
											</table>
										</div>
										
										<div id='div_val_type' style='border: 1px solid #d3d3d3; padding: 4px 8px; margin-top: 5px;'>
											<b>{$lang['design_81']}</b> <span style='color: #505050; font-size: 11px;'>{$lang['global_06']}</span> &nbsp; 
											<select onchange=\"hide_val_minmax($(this));\" id='val_type' name='val_type' class='x-form-text x-form-field' style='padding-right:0;height:22px;'>
												<option value=''> ---- {$lang['design_83']} ---- </option>";
	// Get list of all valid field validation types from table
	$valTypesHidden = array();
	foreach (getValTypes() as $valType=>$valAttr)
	{
		if ($valAttr['visible']) {
			// Only display those listed as "visible"
			print "		<option value='$valType' datatype=\"".cleanHtml2($valAttr['data_type'])."\">{$valAttr['validation_label']}</option>";
		} else {
			// Add to list of hidden val types
			$valTypesHidden[] = $valType;
		}
	}
	print "									</select>									
											<div id='div_val_minmax' style='padding:10px 15px 0 20px;text-align:right;display:none;'>
												<b>{$lang['design_96']}</b>&nbsp; 
												<input type='text' name='val_min' id='val_min' maxlength='20' size='18' 
													onkeydown='if(event.keyCode==13) return false;' class='x-form-text x-form-field' style='font-size:12px;'><br>
												<b>{$lang['design_97']}</b>
												<input type='text' name='val_max' id='val_max' maxlength='20' size='18' 
													onkeydown='if(event.keyCode==13) return false;' class='x-form-text x-form-field' style='font-size:12px;'>												
											</div>										
										</div>
										
										<div id='div_attachment' style='display:none;border: 1px solid #d3d3d3; padding: 4px 8px; margin-top: 5px;'>
											<div>
												<b>{$lang['design_188']}</b> <span style='color: #505050; font-size: 11px;'>{$lang['global_06']}</span>
											</div>
											<div id='div_attach_upload_link'>
												<img src='".APP_PATH_IMAGES."add.png' class='imgfix'> 
												<a href='javascript:;' onclick='openAttachPopup();' style='text-decoration:underline;color:green;'>{$lang['form_renderer_23']}</a>
											</div>
											<div id='div_attach_download_link' style='display:none;padding:3px 0;'>
												<a id='attach_download_link' href='javascript:;' onclick=\"window.location.href='".APP_PATH_WEBROOT."DataEntry/file_download.php?pid='+pid+'&type=attachment&id='+\$('#edoc_id').val();\" style='text-decoration:underline;'>filename goes here.doc</a>
												&nbsp;&nbsp;
												<a href='javascript:;' onclick='deleteAttachment();' style='color:#800000;font-family:tahoma;font-size:10px;'>[X] Remove</a> 
											</div>
											<input type='hidden' id='edoc_id' name='edoc_id' value=''>
											<div id='div_img_display_options' style='padding-top:15px;'>
												<b>{$lang['design_195']}</b><br>
												<input disabled='disabled' id='edoc_img_display_link' name='edoc_display_img' value='0' checked='checked' type='radio'> {$lang['design_196']}<br>
												<input disabled='disabled' id='edoc_img_display_image' name='edoc_display_img' value='1' type='radio'> {$lang['design_197']}
												<div style='font-family: tahoma; font-size: 10px; padding-top: 15px;'>
													{$lang['design_198']}
												</div>
											</div>
										</div>
										
										<div id='div_field_req' style='border: 1px solid #d3d3d3; padding: 2px 8px; margin-top: 5px;'>
											<b>{$lang['design_98']}</b>
											<input type='radio' id='field_req0' name='field_req2' class='imgfix' 
												onclick=\"document.getElementById('field_req').value='0';\" checked>{$lang['design_99']}&nbsp;
											<input type='radio' id='field_req1' name='field_req2' class='imgfix' 
												onclick=\"document.getElementById('field_req').value='1';\">{$lang['design_100']}
											<input type='hidden' name='field_req' id='field_req' class='imgfix' value='0'>
											<span id='req_disable_text' style='visibility:hidden;padding-left:10px;color:#800000;font-family:tahoma;'>
												{$lang['design_101']}
											</span>
											<div style='color:#808080;font-size:10px;font-family:tahoma;padding-top:2px;'>
												{$lang['design_102']}
											</div>
										</div>	
										
										<div id='div_field_phi' style='color:#800000;border: 1px solid #d3d3d3; padding: 2px 8px 4px; margin-top: 5px;'>
											<b>{$lang['design_103']}</b>
											<input type='radio' id='field_phi0' name='field_phi2' class='imgfix' 
												onclick=\"document.getElementById('field_phi').value='';\" checked>{$lang['design_99']}&nbsp;
											<input type='radio' id='field_phi1' name='field_phi2' class='imgfix' 
												onclick=\"document.getElementById('field_phi').value='1';\">{$lang['design_100']}
											<input type='hidden' name='field_phi' id='field_phi' class='imgfix' value=''>
											<div style='color:#808080;font-size:10px;font-family:tahoma;padding-top:2px;'>
												{$lang['design_166']}
											</div>
										</div>
										
										<div id='div_custom_alignment' style='border: 1px solid #d3d3d3; padding: 4px 8px; margin-top: 5px;'>
											<b>{$lang['design_212']}</b> &nbsp; 
											<select id='custom_alignment' name='custom_alignment' class='x-form-text x-form-field' style='padding-right:0;height:22px;'>
												<option value=''>{$lang['design_213']} (RV)</option>
												<option value='RH'>{$lang['design_214']} (RH)</option>
												<option value='LV'>{$lang['design_215']} (LV)</option>
												<option value='LH'>{$lang['design_216']} (LH)</option>
											</select>
											<div style='color:#808080;font-size:10px;font-family:tahoma;padding-top:2px;'>
												{$lang['design_218']}
												<span id='customalign_disable_text' style='visibility:hidden;font-size:11px;padding-left:10px;color:#800000;font-family:tahoma;'>
													{$lang['design_101']}
												</span>
											</div>
										</div>
										
										<div id='div_field_note' style='border: 1px solid #d3d3d3; padding: 4px 8px; margin-top: 5px;'>
											<b>{$lang['design_104']}</b> <span style='color: #505050; font-size: 11px;'>{$lang['global_06']}</span>
											<input class='x-form-text x-form-field' type='text' size='30' id='field_note' name='field_note' 
												onkeydown='if(event.keyCode==13) return false;'>
											<div style='color:#808080;font-size:10px;font-family:tahoma;padding-top:2px;'>
												{$lang['design_217']}
											</div>
										</div>
										
										<div id='div_branching' style='color:#666;padding:2px 4px 0;font-family:tahoma;font-size:11px;'>
											".$lang['design_223']." 
											<img src='".APP_PATH_IMAGES."arrow_branch_side.png' class='imgfix'>
											".$lang['design_224']." 
										</div>
										
										<!-- Hidden pop-up to note any non-numerical MC field fixes -->
										<div id='mc_code_change' style='display:none;padding:10px;' title='".cleanHtml($lang['design_294'])."'>
											{$lang['design_293']}
											<div id='element_enum_clone' style='padding:5px 8px;margin:15px 0 10px;width:90%;color:#444;border:1px solid #ccc;'></div>
										</div>
										<input type='hidden' id='existing_enum' value=''>
										
									</div>
								</td>
							</tr>
							</table>
						</div>
					</div>
					<input type='hidden' name='form_name' value='{$_GET['page']}'>
					<input type='hidden' name='this_sq_id' id='this_sq_id' value=''>
					<input type='hidden' name='sq_id' id='sq_id' value=''>";
	// Provide extra hidden fields if we're creating the first form on a brand new form (page will be refreshed after it is added)
	if (isset($_GET['newform'])) {
		print  "	<input type='hidden' name='add_before_after' id='add_before_after' value='{$_GET['formlocation']}'>
					<input type='hidden' name='add_form_place' id='add_form_place' value='{$_GET['formplace']}'>
					<input type='hidden' name='add_form_name' id='add_form_name' value='{$_GET['newform']}'>";
		// Also remove the Section Header from the drop-down list of field types for this first instance 
		// (because they are not a real metadata field and will cause a problem if added first)
		print  "	<script type='text/javascript'>
					$(function(){
						var selectbox = document.getElementById('field_type');
						for (var i=selectbox.options.length-1; i>=0; i--) {
							if (selectbox.options[i].value == 'section_header') selectbox.remove(i);
						}
					});
					</script>";
	}
	print  "	</form>
			</div>
			</div>
			<br><br>";
	?>
	
	<!-- IMAGE/FILE ATTACHMENT DIALOG POP-UP -->
	<div id="attachment-popup" title="<?php echo cleanHtml2($lang['design_188']) ?>" class="round chklist" style="display:none;">
		<!-- Upload form -->
		<form id="attachFieldUploadForm" target="upload_target" enctype="multipart/form-data" method="post" 
			action="<?php echo APP_PATH_WEBROOT ?>Design/file_attachment_upload.php?pid=<?php echo $project_id ?>">
			<div style="font-size:13px;padding-bottom:5px;">
				<?php echo $lang['data_entry_62'] ?>
			</div>
			<input type="file" id="myfile" name="myfile" style="font-size:13px;">
			<div style="color:#555;font-size:13px;">(<?php echo $lang["data_entry_63"] . " " . maxUploadSizeEdoc() ?>MB)</div>
		</form>
		<iframe style="width:0;height:0;border:0px solid #ffffff;" src="<?php echo APP_PATH_WEBROOT ?>DataEntry/empty.php" name="upload_target" id="upload_target"></iframe>
		<!-- Response message: Success -->
		<div id="div_attach_doc_success" style="display:none;font-weight:bold;font-size:14px;text-align:center;color:green;">
			<img class="imgfix" src="<?php echo APP_PATH_IMAGES ?>tick.png"> 
			<?php echo $lang['design_200'] ?>
		</div>
		<!-- Response message: Failure -->
		<div id="div_attach_doc_fail" style="display:none;font-weight:bold;font-size:14px;text-align:center;color:red;">
			<img class="imgfix" src="<?php echo APP_PATH_IMAGES ?>exclamation.png"> 
			<?php echo $lang['design_137'] ?>
		</div>
		<!-- Upload in progress -->
		<div id="div_attach_doc_in_progress" style="display:none;font-weight:bold;font-size:14px;text-align:center;">
			<?php echo $lang['data_entry_65'] ?><br>
			<img src="<?php echo APP_PATH_IMAGES ?>loader.gif">
		</div>
	</div>
	
	<!-- DISABLE AUTO VARIABLE NAMING DIALOG POP-UP -->
	<div id="auto_variable_naming-popup" title="<?php echo cleanHtml2($lang['design_268']) ?>" class="round chklist" style="display:none;">
		<div class="yellow">
			<table cellspacing=5 width=100%><tr>
				<td valign='top' style='padding:10px 20px 0 10px;'><img src="<?php echo APP_PATH_IMAGES ?>warning.png"></td>
				<td valign='top'>
					<p style="color:#800000;font-size:13px;font-family:verdana;"><b><?php echo $lang['design_268'] ?></b></p>
					<p><?php echo $lang['design_269'] ?></p>
					<p><?php echo $lang['design_270'] ?></p>
					<p><?php echo $lang['design_271'] ?></p>
				</td>
			</tr></table>
		</div>
	</div>
	
							
	
	<!-- STOP ACTIONS DIALOG POP-UP -->
	<div id="stop_action_popup" title="<?php echo cleanHtml2($lang['design_210']) ?>" style="display:none;"></div>
	
	<!-- LOGIC BUILDER DIALOG POP-UP -->
	<div id="logic_builder" title="<img src='<?php echo APP_PATH_IMAGES ?>arrow_branch_side.png' class='imgfix'> <span style='color:#008000;'><?php echo $lang['design_225'] ?></span>" style="display:none;"></div>				
		
	<!-- BRANCHING LOGIC HELP DIALOG POP-UP -->
	<div id="branching_help" title="<img src='<?php echo APP_PATH_IMAGES ?>help.png' class='imgfix'> <span style='color:#3E72A8;'><?php echo $lang['help_11'] ?></span>" style="display:none;"></div>				
		
	<!-- CALCULATIONS HELP DIALOG POP-UP -->
	<div id="calc_help" title="<img src='<?php echo APP_PATH_IMAGES ?>help.png' class='imgfix'> <span style='color:#3E72A8;'><?php echo $lang['help_10'] ?></span>" style="display:none;"></div>				
	
	<!-- Load drag-n-drop javascript and make customizations for this page -->
	<script type="text/javascript" src="<?php echo APP_PATH_JS ?>tablednd.js"></script>
	
	<!-- Tooltip when Choices textbox is pre-filled with matrix group name choices -->
	<div id="prefillChoicesTip" class="tooltip4" style="z-index:9999;"><?php echo $lang['design_305'] ?></div>
	
	<!-- MOVE FIELD DIALOG POP-UP -->
	<div id="move_field_popup" title="<?php echo cleanHtml2($lang['design_333']) ?>" style="display:none;"></div>
	
	<!-- MOVE MATRIX DIALOG POP-UP -->
	<div id="move_matrix_popup" title="<?php echo cleanHtml2($lang['design_334']) ?>" style="display:none;"></div>
	
	<!-- MATRIX EXAMPLES DIALOG POP-UP -->
	<div id="matrixExamplePopup" title="<?php echo cleanHtml2($lang['design_356']) ?>" style="display:none;"></div>

	<!-- Set variables and static msgs -->
	<script type="text/javascript">	
	var prefillgridnametext = '<?php echo cleanHtml($lang['design_297']) ?>';
	var form_name = '<?php echo $_GET['page'] ?>';
	var edit_mode = '<?php echo $_GET['edit_mode'] ?>';
	var valTypesHidden = new Array('<?php echo implode("', '", $valTypesHidden) ?>');
	var hide_pk = <?php echo (($surveys_enabled) && isset($_GET['page']) && $_GET['page'] == $Proj->firstForm) ? 'true' : 'false' ?>; // Hide first field for Single Survey projects only
	var matrixNameValErrMsg = '<?php echo cleanHtml($lang['design_298']) ?>';
	var addNewFieldMsg = '<?php echo cleanHtml($lang['design_57']) ?>';
	var editFieldMsg = '<?php echo cleanHtml($lang['design_320']) ?>';
	var addNewMatrixMsg = '<?php echo cleanHtml($lang['design_307']) ?>';
	var editMatrixMsg = '<?php echo cleanHtml($lang['design_321']) ?>';
	var rawEnumValMsg = '<?php echo cleanHtml2($lang['design_295']) ?>';
	var twoByteCharMsg = '<?php echo cleanHtml($lang['design_79']) ?>';
	var delMatrixTitle = '<?php echo cleanHtml($lang['design_324']) ?>';
	var delMatrixMsg = '<?php echo cleanHtml($lang['design_325']) ?>';
	var delMatrixMsg2 = '<?php echo cleanHtml($lang['design_326']) ?>';
	var delSHMsg = '<?php echo cleanHtml($lang['design_330']) ?>';
	var delSHTitle = '<?php echo cleanHtml($lang['design_415']) ?>';
	var delFieldMsg = '<?php echo cleanHtml($lang['design_328']) ?>';
	var delFieldTitle = '<?php echo cleanHtml($lang['design_327']) ?>';
	var duplVarMtxMsg = '<?php echo cleanHtml($lang['design_331']) ?>';
	var duplVarMtxMsg2 = '<?php echo cleanHtml($lang['design_332']) ?>';
	var disabledAutoQuesNumMsg = '<?php echo cleanHtml($lang['global_03'].$lang['colon'])."\\n".cleanHtml($lang['survey_07']." ".$lang['survey_09']) ?>';
	var pleaseSelectField = '<?php echo cleanHtml($lang['design_338']) ?>';
	var successfullyMovedMsg = '<?php echo cleanHtml($lang['design_346']) ?>';
	var langPkNoDisplayMsg = '<?php echo cleanHtml($lang['design_392']) ?>';	
	var langOD0 = '<?php echo cleanHtml($lang['design_411']) ?>';
	var langOD1 = '<?php echo cleanHtml($lang['survey_459']) ?>';
	var langOD2 = '<?php echo cleanHtml($lang['survey_460']) ?>';
	var langOD3 = '<?php echo cleanHtml($lang['survey_461']) ?>';
	var langOD4 = '<?php echo cleanHtml($lang['survey_462']) ?>';
	var langOD5 = '<?php echo cleanHtml($lang['survey_463']) ?>';
	var langOD6 = '<?php echo cleanHtml($lang['survey_464']) ?>';
	var langOD7 = '<?php echo cleanHtml($lang['survey_465']) ?>';
	var langOD8 = '<?php echo cleanHtml($lang['survey_466']) ?>';
	var langOD9 = '<?php echo cleanHtml($lang['survey_467']) ?>';
	var langOD10 = '<?php echo cleanHtml($lang['survey_468']) ?>';
	var langOD11 = '<?php echo cleanHtml($lang['survey_469']) ?>';
	var langOD13 = '<?php echo cleanHtml($lang['survey_471']) ?>';
	var langOD15 = '<?php echo cleanHtml($lang['survey_473']) ?>';
	var langOD16 = '<?php echo cleanHtml($lang['survey_474']) ?>';
	var langOD17 = '<?php echo cleanHtml($lang['survey_475']) ?>';
	var langOD18 = '<?php echo cleanHtml($lang['survey_476']) ?>';
	var langOD19 = '<?php echo cleanHtml($lang['survey_477']) ?>';
	var langOD20 = '<?php echo cleanHtml($lang['survey_478']) ?>';	
	var langOD21 = '<?php echo cleanHtml($lang['design_412']) ?>';
	var langOD23 = '<?php echo cleanHtml($lang['design_414']) ?>';	
	var langOD24 = '<?php echo cleanHtml($lang['global_19']) ?>';	
	var langOD25 = '<?php echo cleanHtml($lang['design_304']) ?>';
	var langOD26 = '<?php echo cleanHtml($lang['design_303']) ?>';	
	var langOD27 = '<?php echo cleanHtml($lang['design_203']) ?>';
	var langOD28 = '<?php echo cleanHtml($lang['design_202']) ?>';	
	var langOD29 = '<?php echo cleanHtml($lang['design_315']) ?>';
	var langOD30 = '<?php echo cleanHtml($lang['global_03']) ?>';
	var langOD31 = '<?php echo cleanHtml($lang['form_renderer_23']) ?>';
	var langOD32 = '<?php echo cleanHtml($lang['design_128']) ?>';
	var langOD33 = '<?php echo cleanHtml($lang['design_416']) ?>';	
	var langOD34 = '<?php echo cleanHtml($lang['design_417']) ?>';	
	var langOD35 = '<?php echo cleanHtml($lang['design_418']) ?>';		
	var langOD36 = '<?php echo cleanHtml($lang['design_419']) ?>';		
	var langOD37 = '<?php echo cleanHtml($lang['design_420']) ?>';
	var langOD39 = '<?php echo cleanHtml($lang['design_421']) ?>';
	var langOD40 = '<?php echo cleanHtml($lang['design_422']) ?>';
	var langOD41 = '<?php echo cleanHtml($lang['design_423']) ?>';
	var langOD42 = '<?php echo cleanHtml($lang['design_424']) ?>';
	var langOD43 = '<?php echo cleanHtml($lang['design_425']) ?>';
	var langOD44 = '<?php echo cleanHtml($lang['design_426']) ?>';
	var langOD45 = '<?php echo cleanHtml($lang['design_427']) ?>';
	var langOD46 = '<?php echo cleanHtml($lang['design_428']) ?>';
	var langOD47 = '<?php echo cleanHtml($lang['design_429']) ?>';	
	var langOD48 = '<?php echo cleanHtml($lang['global_02'].$lang['colon'].' '.$lang['design_432']) ?>';
	var langOD49 = '<?php echo cleanHtml($lang['design_441']) ?>';	
	var langOD50 = '<?php echo cleanHtml($lang['design_453']) ?>';	
	// Put all reserved variable names into an array for checking later
	var reserved_field_names = new Array(<?php 
		echo prep_implode(array_keys($reserved_field_names)) 
			. ",'" . implode("_timestamp','", array_keys($Proj->forms)) . "_timestamp'"
			. ",'" . implode("_return_code','", array_keys($Proj->forms)) . "_return_code'" 
	?>);
	</script>
	<!-- JS for Online Designer (Forms) -->
	<script type="text/javascript" src="<?php echo APP_PATH_JS ?>DesignFields.js"></script>
	<?php	
}


include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
