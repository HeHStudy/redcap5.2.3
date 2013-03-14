<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

include_once APP_PATH_DOCROOT . 'ProjectGeneral/math_functions.php';

// This page displays the survey results in graphical format and/or survey stats

$sql = "select r.response_id, r.record from redcap_surveys_participants p, redcap_surveys_response r 
		where p.participant_id = r.participant_id and p.hash = '".prep($hash)."' 
		and r.results_code = '".prep(trim($_GET['__results']))."' and r.completion_time is not null limit 1";
$q = db_query($sql);
if (db_num_rows($q) > 0)
{
	// Set response_id in session and get record name
	$fetched = $_GET['id'] = db_result($q, 0, 'record');
	
	// Header
	$objHtmlPage->PrintHeader();
	
	?>
	<!-- CSS and Javascript -->
	<style type="text/css">.dc_header {font-size:14px;}</style>
	<script type="text/javascript">
	$(function(){
		// Actions for participant sending themself an email
		$('#sendSelfEmailBtn').click(function(){
			$('#sendSelfEmailSpanNotice').show('slow');
			$('#sendSelfEmailSpan').show('slow');
			document.getElementById('sendSelfEmailInput').focus(); 
		});
		$('#sendSelfEmailBtn2').click(function(){
			$('#sendSelfEmailInput').val(trim($('#sendSelfEmailInput').val()));
			if ($('#sendSelfEmailInput').val().length < 1) {
				alert('Enter an email address');
				return false;
			}
			if (redcap_validate(document.getElementById('sendSelfEmailInput'),'','','soft_typed','email')) {
				$('#progress').show();
				$(this).attr('disabled',true);
				$('#sendSelfEmailInput').attr('disabled',true);
				$.get(app_path_webroot+'Surveys/email_self.php?pid='+pid+'&email='+$('#sendSelfEmailInput').val()+'&url='+escape(window.location.href),{},function(data){
					if (data == '1') {
						$('#sent').show();
						$('#sendSelfEmailSpanNotice').hide();
						setTimeout(function(){
							$('#sent').hide('slow');
							$('#sendSelfEmailSpan').hide('fade');
							$('#sendSelfEmailBtn2').attr('disabled',false);
							$('#sendSelfEmailInput').attr('disabled',false);
							$('#sendSelfEmailInput').val('');
						},3000);
					} else {
						$('#sendSelfEmailBtn2').attr('disabled',false);
						$('#sendSelfEmailInput').attr('disabled',false);
						alert(woops);
					}
					$('#progress').hide();
				});
			}
		});
	});
	</script>
	
	<!-- Title and header -->
	<?php echo $title_logo ?>
	<br><br>
	<p style="font-weight:bold;font-size:16px;color:#800000;">
		<img src="<?php echo APP_PATH_IMAGES ?>chart_curve.png" class="imgfix2">
		<?php echo $lang['survey_167'] ?>
	</p>
	
	<!-- Page instructions -->
	<p>
		<?php echo $lang['survey_169'] ?>
	</p>
	<?php
		
	// Check to make sure we meet the minimum response count before showing plots		
	$sql = "select count(distinct(r.record)) 
			from redcap_surveys_participants p, redcap_surveys_response r, redcap_events_metadata m 
			where survey_id = $survey_id and r.participant_id = p.participant_id and 
			m.event_id = p.event_id and m.event_id = $event_id and r.completion_time is not null
			and r.first_submit_time is not null";
	$q = db_query($sql);
	$num_complete_responses = db_result($q, 0);
	// Determine if we've met the minimum number of responses already
	$metMinResponses = ($num_complete_responses >= $min_responses_view_results);

	// Validate the results code hash, if submitted by participant
	if (isset($_POST['results_code_hash']))
	{
		$resultsHashValid = checkResultsCodeHash($_GET['__results'], $_POST['results_code_hash']);
	}
	
	// If participant just finished survey or entered a correct results code, then display page instructions and email option
	if (isset($resultsHashValid) && $resultsHashValid)
	{
		?>
		<!-- Page instructions -->
		<?php if ($view_results == '1' || $view_results == '3') { ?>
			<p>
				<b><?php echo $lang['survey_209'] ?></b><br>
				<?php echo $lang['survey_170'] ?> <span style='font-weight:bold;color:#3366CC;'><?php echo $lang['survey_171'] ?></span><?php echo $lang['survey_172'] ?> 
				<span style='font-weight:bold;color:#D88400;'><?php echo $lang['survey_173'] ?></span><?php echo $lang['period'] ?>
				<?php echo $lang['survey_174'] ?> <span style='font-weight:bold;color:#3366CC;'><?php echo $lang['survey_171'] ?></span><?php echo $lang['survey_175'] ?> 
				<span style='font-weight:bold;color:#DC3912'><?php echo $lang['survey_176'] ?></span><?php echo $lang['survey_177'] ?> 
				<span style='font-weight:bold;color:#D88400;'><?php echo $lang['survey_173'] ?></span><?php echo $lang['period'] ?>
			</p>
		<?php } ?>
		<?php if ($view_results == '2' || $view_results == '3') { ?>
			<p>
				<b><?php echo $lang['survey_210'] ?></b><br>
				<?php echo $lang['survey_211'] ?>
			</p>
		<?php } ?>		
	
		<!-- "Email this page" option -->
		<div style='margin:30px 0;'>
			<table cellspacing=0>
				<tr>
					<td valign="top" style="width:180px;">
						<button class="jqbutton" id="sendSelfEmailBtn"><img src='<?php echo APP_PATH_IMAGES ?>email.png' class='imgfix'> <?php echo $lang['survey_178'] ?></button>
					</td>
					<td valign="top" style="width:400px;">
						<div>
							<span id="sendSelfEmailSpan" style="display:none;font-weight:bold;">
								<?php echo $lang['survey_179'] ?>&nbsp;
								<input type="text" size="30" class="x-form-text x-form-field" id="sendSelfEmailInput" onblur="redcap_validate(this,'','','soft_typed','email')" value="<?php echo $participant_email ?>">
								<button class="jqbuttonmed" id="sendSelfEmailBtn2"><?php echo $lang['survey_180'] ?></button>
							</span>
							<!-- Spinner icon -->
							<span id="progress" style="display:none;padding-left:10px;"><img src="<?php echo APP_PATH_IMAGES ?>progress_circle.gif" class="imgfix"></span>
							<!-- Sent email icon -->
							<div id="sent" style="display:none;font-weight:bold;color:green;padding:3px;">
								<img src="<?php echo APP_PATH_IMAGES ?>tick.png" class="imgfix">&nbsp; 
								<?php echo $lang['survey_181'] ?>
							</div>
							<!-- Notice that hash not sent in email -->
							<div id="sendSelfEmailSpanNotice" style="display:none;color:#777;font-size:11px;padding-top:8px;">
								<?php echo $lang['survey_201'] ?>
							</div>
						</div>
					</td>
				</tr>
			</table>
		</div>
	
		<!-- Display results code hash, if just finished survey -->
		<div style="display:<?php echo (isset($_POST['hide_results_code']) ? 'none' : 'block') ?>;max-width:700px;margin:20px 0;border:1px solid #ddd;background-color:#fafafa;padding:10px;font-weight:bold;">
			<div style="color:#800000;font-size:14px;padding-bottom:8px;">
				<img src="<?php echo APP_PATH_IMAGES ?>star.png" class="imgfix">
				<?php echo $lang['survey_193'] ?>
			</div>
			<?php echo $lang['survey_194'] ?>&nbsp;
			<input readonly style="font-family:verdana;width:150px;padding:3px;margin-top:4px;background-color:#EDF2FD;border:1px solid #A7C3F1;color:#000066;font-size:13px;" 
				onclick="this.select();" value="<?php echo $_POST['results_code_hash'] ?>"><br>
			<div style="font-weight:normal;padding-top:8px;">
				<?php echo $lang['survey_195'] ?>
			</div>
		</div>
		<?php		
		
		// GOOGLE CHROME FRAME: If using IE6,7, or 8 (and not using Google Chrome Frame), give message to install GCF
		if (($view_results == '1' || $view_results == '3') && !isset($_GET['ignoreGCFwarning']))
		{
			// Set some additional text to display inside the Chrome Frame warning box
			$extra_text =  "<div style='padding:20px 0 10px;'>
								<div style='text-align:center;padding-bottom:15px;font-weight:bold;color:#800000;font-size:14px;'>{$lang['global_46']}</div>
								{$lang['survey_212']} <b>{$lang['global_48']}</b>{$lang['colon']} {$lang['survey_213']}<br><br>
								<div style='text-align:center;'>
									<form id='results_code_form' action='".APP_PATH_SURVEY_FULL."index.php?s={$_GET['s']}&__results={$_GET['__results']}&ignoreGCFwarning=1' method='post' enctype='multipart/form-data'>
										<input type='hidden' name='results_code_hash' value='{$_POST['results_code_hash']}'>						
										<button class='jqbuttonmed' onclick=\"$('#results_code_form').submit();\">{$lang['survey_214']}</button>
									</form>
								</div>
							</div>";
			// Display the warning message, if applicable
			$needGCF = chromeFrameInstallMsg($extra_text);
			// If warning was displayed, then end the page here
			if ($needGCF)
			{
				?>
				<br><br><br>
				<script type="text/javascript">
				// Add onclick event when clicking the GCF install button to remind them to write down their survey results code
				$(function(){
					$('#chrome_frame_install_btn').click(function(){
						setTimeout(function(){
							alert('<?php echo cleanHtml($lang['survey_215'].'\n\n'.$lang['survey_216'].' '.$_POST['results_code_hash']) ?>');
						},300);
					});
				});
				</script>
				<?php
				// Footer
				$objHtmlPage->PrintFooter();
				exit;
			}
		}
	} 
	// Not entered results code yet (or entered an incorrect results code)
	else 
	{ 
		// Display error msg if submitted hash code did not match
		if (isset($resultsHashValid) && !$resultsHashValid) 
		{ 
			echo displayMsg($lang['survey_196']."<br>".$lang['locking_25'], "actionMsg", "left", "red", "exclamation.png", 7, true);
		} 
		?>
		<!-- Display option to enter results code hash, if not entered -->
		<div style="max-width:700px;margin:40px 0;background-color:#EDF2FD;border:1px solid #A7C3F1;padding:10px;font-weight:bold;">
			<form id="results_code_form" action="<?php echo APP_PATH_SURVEY_FULL."index.php?s={$_GET['s']}&__results={$_GET['__results']}" ?>" method="post" enctype="multipart/form-data">
				<div style="color:#800000;font-size:14px;padding-bottom:8px;">
					<?php echo $lang['survey_197'] ?>
				</div>
				<?php echo $lang['survey_198'] ?>&nbsp;
				<input type="hidden" name="hide_results_code">
				<input type="hidden" name="__response_hash__" value="<?php echo getResponseHashFromResultsCode($_GET['__results'], $participant_id) ?>">
				<input type="text" size="10" class="x-form-text x-form-field" name="results_code_hash">						
				<button class="jqbuttonmed" onclick="$('#results_code_form').submit();"><?php echo $lang['survey_200'] ?></button>
			</form>
			<div style="font-weight:normal;padding-top:8px;">
				<?php echo $lang['survey_199'] ?>
			</div>
		</div>
		<?php
		// Footer
		$objHtmlPage->PrintFooter();
		exit;
	}			
	?>	
	
	<?php
	// If we've not met the minimum number of responses, give notice to return later
	if (!$metMinResponses)
	{
		print "<p class='yellow'>{$lang['survey_168']}</p>";
		// Footer
		$objHtmlPage->PrintFooter();
		exit;
	}
	// Obtain the fields to chart
	$fields = getFieldsToChart($project_id, $form_name);
	// Render charts
	renderCharts($project_id, $fields, $form_name, getRecordCount($project_id, $form_name));
	
	// Footer		
	$objHtmlPage->PrintFooter();
	exit;
}