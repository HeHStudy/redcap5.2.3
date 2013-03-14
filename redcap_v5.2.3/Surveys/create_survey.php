<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . "/Config/init_project.php";
require_once APP_PATH_DOCROOT . 'ProjectGeneral/form_renderer_functions.php';
require_once APP_PATH_DOCROOT . "Surveys/survey_functions.php";

// Determine the instrument
$form = (isset($_GET['page']) && isset($Proj->forms[$_GET['page']])) ? $_GET['page'] : $Proj->firstForm;	

// If survey has already been created (it shouldn't have been), then redirect to edit_info page to edit survey
if (isset($Proj->forms[$form]['survey_id'])) {
	redirect(str_replace(PAGE, 'Surveys/edit_info.php', $_SERVER['REQUEST_URI']));
}


/**
 * PROCESS SUBMITTED CHANGES
 */
if ($_SERVER['REQUEST_METHOD'] == "POST")
{
	// Assign Post array as globals
	foreach ($_POST as $key => $value) $$key = $value;
	// Set values
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
	
	// Save survey info
	$sql = "insert into redcap_surveys (project_id, form_name, acknowledgement, instructions, question_by_section, 
			question_auto_numbering, save_and_return, survey_enabled, title, 
			view_results, min_responses_view_results, check_diversity_view_results, end_survey_redirect_url, survey_expiration,
			end_survey_redirect_url_append_id) 
			values ($project_id, '" . prep($form) . "', 
			'" . prep($acknowledgement) . "', '" . prep($instructions) . "', 
			'" . prep($question_by_section) . "', '" . prep($question_auto_numbering) . "', 
			'" . prep($save_and_return) . "', 1, '" . prep($title) . "',
			'" . prep($view_results) . "', '" . prep($min_responses_view_results) . "', '" . prep($check_diversity_view_results) . "', 
			" . checkNull($end_survey_redirect_url) . ", " . checkNull($survey_expiration) . ", " . prep($end_survey_redirect_url_append_id) . ")";
	$survey_id = (db_query($sql) ? db_insert_id() : exit("An error occurred. Please try again."));
	
	// Upload logo
	$hide_title = ($hide_title == "on" ? "1" : "0");
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
	log_event($sql, "redcap_surveys", "MANAGE", $survey_id, "survey_id = $survey_id", "Set up survey");
	
	// Once the survey is created, redirect to edit survey settings page, and display "saved changes" message
	redirect(str_replace(PAGE, 'Surveys/edit_info.php', $_SERVER['REQUEST_URI']) . "&created=1");
}








// Header
include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

// TABS
include APP_PATH_DOCROOT . "ProjectSetup/tabs.php";

// Instructions
?>
<p style="margin-bottom:20px;">	
	<?php 
	print $lang['survey_271'] . " " . $lang['survey_272'];
	?>
</p>
<?php


// Force user to click button to begin survey-enabling process
if (!isset($_GET['view']))
{
	?>
	<div class="yellow" style="text-align:center;font-weight:bold;padding:10px;">
		<?php echo $lang['survey_151'] ?>
		<br><br>
		<button class="jqbutton" onclick="window.location.href='<?php echo $_SERVER['REQUEST_URI'] ?>&view=showform';"
			><?php echo $lang['survey_152'] ?> "<?php echo $Proj->forms[$form]['menu'] ?>" <?php echo $lang['survey_153'] ?></button>
	</div>
	<?php
}


// Display form to enable survey
elseif (isset($_GET['view']) && $_GET['view'] == "showform")
{
	?>
	<!-- TinyMCE Rich Text Editor -->
	<script type="text/javascript" src="<?php echo APP_PATH_MCE ?>tiny_mce.js"></script>
	<script language="javascript" type="text/javascript">
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
	</script>
	
	
	<div class="darkgreen">
		<div style="float:left;">
			<img src="<?php echo APP_PATH_IMAGES ?>add.png" class="imgfix"> 
			<?php 
			print $lang['setup_24'];
			print " {$lang['setup_89']} \"<b>".RCView::escape($Proj->forms[$form]['menu'])."</b>\"";
			?>
		</div>
		<div style="float:right;">
			<input type="button" onclick="history.go(-1)" value=" <?php echo cleanHtml2($lang['global_53']) ?> ">
		</div>
		<div class="clear"></div>
	</div>
	<div style="background-color:#FAFAFA;border:1px solid #DDDDDD;padding:0 6px;max-width:700px;">
		<?php	
		// Set defaults to pre-fill table		
		$title = empty($Proj->forms[$form]['menu']) ? "My Survey" : $Proj->forms[$form]['menu'];
		$question_auto_numbering = 1;
		$question_by_section = 0;
		$save_and_return = 0;
		$logo = "";
		$hide_title = 0;
		$instructions = '<p><strong>'.$lang['survey_154'].'</strong></p><p>'.$lang['global_83'].'</p>';
		$acknowledgement = '<p><strong>'.$lang['survey_155'].'</strong></p><p>'.$lang['survey_156'].'</p>';
		$view_results = 0;
		$min_responses_view_results = 10;
		$check_diversity_view_results = 1;
		$end_survey_redirect_url = '';
		$end_survey_redirect_url_append_id = 0;
		$survey_expiration = '';
		// Render the create/edit survey table
		include APP_PATH_DOCROOT . "Surveys/survey_info_table.php";		
		?>		
	</div>
	<?php
}


// Footer
include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
	