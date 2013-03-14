<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . "/Config/init_project.php";
require_once APP_PATH_DOCROOT . 'ProjectGeneral/form_renderer_functions.php';
require_once APP_PATH_DOCROOT  . "Surveys/survey_functions.php";

// Determine the instrument
$form = (isset($_GET['page']) && isset($Proj->forms[$_GET['page']])) ? $_GET['page'] : $Proj->firstForm;

// If no survey id, assume it's the first form and retrieve
if (!isset($_GET['survey_id']))
{	
	$_GET['survey_id'] = getSurveyId($form);
}	


if (checkSurveyProject($_GET['survey_id']))
{
	// Default message
	$msg == "";
	
	// Retrieve survey info
	$q = db_query("select * from redcap_surveys where project_id = $project_id and survey_id = " . $_GET['survey_id']);
	foreach (db_fetch_assoc($q) as $key => $value)
	{
		$$key = trim(label_decode($value));
	}
	
	
	
	/**
	 * PROCESS SUBMITTED CHANGES
	 */
	if ($_SERVER['REQUEST_METHOD'] == "POST")
	{
		// Assign Post array as globals
		foreach ($_POST as $key => $value) $$key = $value;
		// Set checkbox value
		$check_diversity_view_results = (isset($check_diversity_view_results) && $check_diversity_view_results == 'on') ? 1 : 0;
		if (!isset($view_results)) $view_results = 0;
		if (!isset($min_responses_view_results)) $min_responses_view_results = 10;
		if ($survey_termination_options == 'url') {
			$acknowledgement = '';
		} else {
			$end_survey_redirect_url = '';
		}
		$end_survey_redirect_url_append_id = (isset($end_survey_redirect_url_append_id) && ($end_survey_redirect_url_append_id == '1' || $end_survey_redirect_url_append_id == 'on') && $survey_termination_options == 'url') ? 1 : 0;
		// Reformat $survey_expiration from MDYHS to YMDHS for saving purposes
		if ($survey_expiration != '') {
			list ($this_date, $this_time) = explode(" ", $survey_expiration);
			$survey_expiration = trim(date_mdy2ymd($this_date) . " " . $this_time);
		}
		// Set if the survey is active or offline
		if (isset($_POST['survey_enabled'])) {
			$survey_enabled = $_POST['survey_enabled'];
		}
		$survey_enabled = ($survey_enabled == '1') ? '1' : '0';
		
		// Build "go back" button to specific page
		if (isset($_GET['redirectDesigner'])) {
			// Go back to Online Designer
			$goBackBtn = renderPrevPageBtn("Design/online_designer.php",$lang['global_77'],false);
		} else {
			// Go back to Project Setup page
			$goBackBtn = renderPrevPageBtn("ProjectSetup/index.php?&msg=surveymodified",$lang['global_77'],false);
		}
		$msg = RCView::div(array('style'=>'padding:0 0 20px;'), $goBackBtn);
		
		// Save survey info
		$sql = "update redcap_surveys set title = '" . prep($title) . "', acknowledgement = '" . prep($acknowledgement) . "',
				instructions = '" . prep($instructions) . "', question_by_section = '" . prep($question_by_section) . "', 
				question_auto_numbering = '" . prep($question_auto_numbering) . "', save_and_return = '" . prep($save_and_return) . "',
				view_results = '" . prep($view_results) . "', min_responses_view_results = '" . prep($min_responses_view_results) . "',
				check_diversity_view_results = '" . prep($check_diversity_view_results) . "',
				end_survey_redirect_url = " . checkNull($end_survey_redirect_url) . ", survey_expiration = " . checkNull($survey_expiration) . ",
				end_survey_redirect_url_append_id = " . prep($end_survey_redirect_url_append_id) . ",
				survey_enabled = " . prep($survey_enabled) . "
				where survey_id = $survey_id";
		if (db_query($sql))
		{
			$msg .= RCView::div(array('id'=>'saveSurveyMsg','class'=>'darkgreen','style'=>'display:none;vertical-align:middle;text-align:center;margin:0 0 25px;'),
						RCView::img(array('src'=>'tick.png','class'=>'imgfix')) . $lang['control_center_48']
					);
		}
		else
		{
			$msg = 	RCView::div(array('id'=>'saveSurveyMsg','class'=>'red','style'=>'display:none;vertical-align:middle;text-align:center;margin:0 0 25px;'),
						RCView::img(array('src'=>'exclamation.png','class'=>'imgfix')) . $lang['survey_159']
					);
		}
		
		// Upload logo
		$hide_title = ($hide_title == "on") ? "1" : "0";
		if (!empty($_FILES['logo']['name'])) {
			// Check if it is an image file
			$file_ext = getFileExt($_FILES['logo']['name']);
			if (in_array(strtolower($file_ext), array("jpeg", "jpg", "gif", "bmp", "png"))) {
				// Upload the image
				$logo = uploadFile($_FILES['logo']);
				// Add doc_id to redcap_surveys table
				if ($logo != 0) {
					db_query("update redcap_surveys set logo = $logo, hide_title = $hide_title where survey_id = $survey_id");
				}
			}
		} elseif (empty($old_logo)) {
			// Mark existing field for deletion in edocs table, then in redcap_surveys table
			$logo = db_result(db_query("select logo from redcap_surveys where survey_id = $survey_id"), 0);
			if (!empty($logo)) {
				db_query("update redcap_edocs_metadata set delete_date = '".NOW."' where doc_id = $logo");
				db_query("update redcap_surveys set logo = null, hide_title = 0 where survey_id = $survey_id");
			}
			// Set back to default values
			$logo = "";
			$hide_title = "0";
		} elseif (!empty($old_logo)) {
			db_query("update redcap_surveys set hide_title = $hide_title where survey_id = $survey_id");
		}
	
		// Log the event
		log_event($sql, "redcap_surveys", "MANAGE", $survey_id, "survey_id = $survey_id", "Modify survey info");
	}
	
	
	// If was redirected here right after creating the survey, then display the "saved changes" message
	elseif (isset($_GET['created']))
	{
		// Button back to Online Designer
		$msg =  RCView::div(array('style'=>'padding:0 0 20px;'), 
					renderPrevPageBtn("Design/online_designer.php", $lang['global_77'], false)
				) .
				RCView::div(array('id'=>'saveSurveyMsg','class'=>'darkgreen','style'=>'display:none;vertical-align:middle;text-align:center;margin:0 0 25px;'),
					RCView::img(array('src'=>'tick.png','class'=>'imgfix')) . $lang['control_center_48']
				);
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	// Header
	include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

	// TABS
	include APP_PATH_DOCROOT . "ProjectSetup/tabs.php";
	
	?>
	
	<!-- TinyMCE Rich Text Editor -->
	<script type="text/javascript" src="<?php echo APP_PATH_MCE ?>tiny_mce.js"></script>
	<script type="text/javascript">
	tinyMCE.init({
		relative_urls : false,
		mode : "textareas",
		theme : "advanced",
		theme_advanced_buttons1 : "bold,italic,underline,separator,strikethrough,justifyleft,justifycenter,justifyright,justifyfull,hr,undo,redo,link,unlink,code",
		theme_advanced_buttons2 : "",
		theme_advanced_buttons3 : "",
		theme_advanced_toolbar_location : "bottom",
		theme_advanced_toolbar_align : "left",
		extended_valid_elements : "a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name],hr[class|width|size|noshade],font[face|size|color|style],span[class|align|style]"
	});
	// Display "saved changes" message, if just saved survey settings
	$(function(){
		if ($('#saveSurveyMsg').length) {
			setTimeout(function(){
				$('#saveSurveyMsg').slideToggle('normal');
			},200);
			setTimeout(function(){
				$('#saveSurveyMsg').slideToggle(1200);
			},5000);
		}
	});
	</script>
	
	<p style="margin-bottom:20px;"><?php echo $lang['survey_160'] ?></p>
	
	<?php
	// Display error message, if exists
	if (!empty($msg)) print $msg;
	?>
	
	<div class="blue">
		<div style="float:left;">
			<img src="<?php echo APP_PATH_IMAGES ?>pencil.png" class="imgfix"> 
			<?php 
			print $lang['setup_05'];
			print " {$lang['setup_89']} \"<b>".RCView::escape($Proj->forms[$form]['menu'])."</b>\""; 
			?>
		</div>
		<?php if ($_SERVER['REQUEST_METHOD'] != 'POST' && !isset($_GET['created'])) { ?>
		<div style="float:right;">
			<input type="button" onclick="history.go(-1)" value=" <?php echo cleanHtml2($lang['global_53']) ?> ">
		</div>
		<?php } ?>
		<div class="clear"></div>
	</div>
	<div style="background-color:#FAFAFA;border:1px solid #DDDDDD;padding:0 6px;max-width:700px;">
	<?php
	
	// Render the create/edit survey table
	include APP_PATH_DOCROOT . "Surveys/survey_info_table.php";
	
	print "</div>";
	
	// Footer
	include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
}