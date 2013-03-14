<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


// Detect if any branching logic exists in survey. If so, disable question auto numbering.
if (isset($_GET['survey_id']))
{
	$hasBranching = Design::checkSurveyBranchingExists($Proj->surveys[$_GET['survey_id']]['form_name']);
	if ($hasBranching) $question_auto_numbering = false;
}

// Get current time zone, if possible
$timezoneText = "{$lang['survey_296']} <b>".getTimeZone()."</b>{$lang['survey_297']}<br/><b>" . date('m-d-Y H:i') . "</b>{$lang['period']}";

// Reformat $survey_expiration from YMDHS to MDYHS for display purposes
if ($survey_expiration != '') {
	list ($this_date, $this_time) = explode(" ", $survey_expiration);
	$survey_expiration = trim(date_ymd2mdy($this_date) . " " . $this_time);
}

?>

<form action="<?php echo $_SERVER['REQUEST_URI'] . ((isset($_GET['redirectInvite']) && $_GET['redirectInvite']) ? "&redirectInvite=1" : "") ?>" method="post" enctype="multipart/form-data">
	<table cellspacing="3" style="width:100%;">
		<?php if (PAGE == 'Surveys/edit_info.php') { ?>
		<!-- Make survey active or offline (only when editing surveys) -->
		<tr>
			<td colspan="3">
				<div id="survey_enabled_div" class="<?php echo($survey_enabled ? 'darkgreen' : 'red') ?>" style="margin: -5px -10px 0px;font-size:12px;">
					<div style="float:left;width:194px;font-weight:bold;padding:5px 0 0 25px;">
						<?php echo $lang['survey_374'] ?>
					</div>
					<div style="float:left;">
						<img id="survey_enabled_img" class="imgfix" style="margin-right:5px;" src="<?php echo APP_PATH_IMAGES . ($survey_enabled ? "accept.png" : "delete.png") ?>">
						<select name="survey_enabled" class="x-form-text x-form-field" style="padding-right:0;height:22px;margin-bottom:3px;"
							onchange="if ($(this).val()=='1'){ $('#survey_enabled_img').attr('src',app_path_images+'accept.png');$('#survey_enabled_div').removeClass('red').addClass('darkgreen'); } else { $('#survey_enabled_img').attr('src',app_path_images+'delete.png');$('#survey_enabled_div').removeClass('darkgreen').addClass('red'); }">
							<option value="1" <?php echo ( $survey_enabled ? 'selected' : '') ?>><?php echo $lang['survey_376'] ?></option>
							<option value="0" <?php echo (!$survey_enabled ? 'selected' : '') ?>><?php echo $lang['survey_375'] ?></option>
						</select><br>
						<span class="newdbsub" style="margin-left:26px;"><?php echo $lang['survey_377'] ?></span>
					</div>
					<div class="clear"></div>
				</div>
			</td>
		</tr>
		<?php } ?>
		<tr>
			<td colspan="3">
				<div class="header" style="padding:7px 10px 5px;margin:-5px -10px 10px;"><?php echo $lang['survey_291'] ?></div>
			</td>
		</tr>
		<tr>
			<td valign="top" style="width:20px;">
			</td>
			<td valign="top" style="width:200px;font-weight:bold;">
				<?php echo $lang['survey_49'] ?>
			</td>
			<td valign="top" style="padding-left:15px;padding-bottom:5px;">
				<input name="title" type="text" value="<?php echo str_replace('"', '&quot;', label_decode($title)) ?>" class="x-form-text x-form-field" style="width:80%;" onkeydown="if(event.keyCode==13){return false;}">
				<div class="newdbsub">
					<?php echo $lang['survey_50'] ?>
				</div>
			</td>
		</tr>
		<tr>
			<td valign="top" style="width:20px;">
			</td>
			<td valign="top" style="width:200px;font-weight:bold;">
				<?php echo $lang['survey_51'] ?>
			</td>
			<td valign="top" style="padding-left:15px;padding-bottom:15px;">
				<select name="question_auto_numbering" <?php if ($hasBranching) echo "disabled" ?> class="x-form-text x-form-field" style="padding-right:0;height:22px;">
					<option value="1" <?php echo ( $question_auto_numbering ? 'selected' : '') ?>><?php echo $lang['survey_52'] ?></option>
					<option value="0" <?php echo (!$question_auto_numbering ? 'selected' : '') ?>><?php echo $lang['survey_53'] ?></option>
				</select>
				<?php if ($hasBranching) { ?>
					<div style="color:red;font-size:9px;font-family:tahoma;">
						<?php echo $lang['survey_06'] ?>
					</div>
				<?php } ?>
			</td>
		</tr>
		<tr>
			<td valign="top" style="width:20px;">
			</td>
			<td valign="top" style="width:200px;font-weight:bold;">
				<?php echo $lang['survey_54'] ?>
			</td>
			<td valign="top" style="padding-left:15px;padding-bottom:15px;">
				<select name="question_by_section" class="x-form-text x-form-field" style="padding-right:0;height:22px;">
					<option value="0" <?php echo (!$question_by_section ? 'selected' : '') ?>><?php echo $lang['survey_55'] ?></option>
					<option value="1" <?php echo ( $question_by_section ? 'selected' : '') ?>><?php echo $lang['survey_56'] ?></option>
				</select>
			</td>
		</tr>
		
		<!-- View Results -->
		<?php if ($enable_plotting_survey_results) { ?>
		<tr>
			<td valign="top" style="width:20px;">
				<img src="<?php echo APP_PATH_IMAGES ?>chart_curve.png">
			</td>
			<td valign="top" style="width:200px;font-weight:bold;padding:0 0 10px 0;">
				<?php echo $lang['survey_184'] ?>				
				<div style="margin-top:5px;color:#666;font-family:tahoma,arial;font-size:9px;font-weight:normal;">
					<?php echo $lang['survey_185'] ?>
				</div>
			</td>
			<td valign="top" style="padding:0 0 10px 0;padding-left:15px;padding-bottom:25px;">			
				<table cellpadding=0 cellspacing=0>
					<tr>
						<td colspan="2" valign="top" style="padding-bottom:15px;">
							<select id="view_results" name="view_results" class="x-form-text x-form-field" style="padding-right:0;height:22px;"
								onchange="if (this.value != '0' && $('#survey_termination_options_url').prop('checked')){ setTimeout(function(){ $('#view_results').val('0'); },10);simpleDialog('<?php echo cleanHtml2($lang['survey_303']) ?>','<?php echo cleanHtml2($lang['survey_302']) ?>');}">
								<option value="0" <?php echo ($view_results == '0' ? 'selected' : '') ?>><?php echo $lang['global_23'] ?></option>
								<!-- Plots only -->
								<option value="1" <?php echo ($view_results == '1' ? 'selected' : '') ?>><?php echo $lang['survey_203'] ?></option>
								<!-- Stats only -->
								<option value="2" <?php echo ($view_results == '2' ? 'selected' : '') ?>><?php echo $lang['survey_204'] ?></option>
								<!-- Plots + Stats -->
								<option value="3" <?php echo ($view_results == '3' ? 'selected' : '') ?>><?php echo $lang['survey_205'] ?></option>
							</select>
						</td>
					</tr>
					<tr class="view_results_options">
						<td valign="top" colspan="3" style="color:#444;font-weight:bold;padding:2px 0 3px;">
							<?php echo $lang['survey_188'] ?>
						</td>
					</tr>
					<tr class="view_results_options">
						<td valign="top" style="text-align:right;padding:5px 0;">
							<input name="min_responses_view_results" type="text" value="<?php echo $min_responses_view_results ?>" class="x-form-text x-form-field" style="width:20px;" maxlength="4" onkeydown="if(event.keyCode==13){return false;}" onblur="redcap_validate(this,'1','9999','soft_typed','int')">
						</td>
						<td valign="top" style="padding:5px 0;padding-left:15px;color:#444;">
							<?php echo $lang['survey_187'] ?>
						</td>
					</tr>
					<tr class="view_results_options">
						<td valign="top" style="text-align:right;">
							<input type="checkbox" name="check_diversity_view_results" id="check_diversity_view_results" <?php echo ($check_diversity_view_results ? "checked" : "") ?>> 
						</td>
						<td valign="top" style="padding-left:15px;color:#444;">
							<?php echo $lang['survey_186'] ?><br>
							(<a href="javascript:;" style="text-decoration:underline;font-size:10px;font-family:tahoma;" onclick="
								$('#diversity_explain').dialog({ bgiframe: true, modal: true, width: 500, 
									buttons: { Okay: function() { $(this).dialog('close'); } } 
								});
							"><?php echo $lang['survey_189'] ?></a>)
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<?php } ?>
		
		<!-- Logo -->
		<tr>
			<td valign="top" style="width:20px;">
				<img src="<?php echo APP_PATH_IMAGES ?>picture.png">
			</td>
			<td valign="top" style="width:200px;font-weight:bold;padding:0 0 10px 0;">
				<?php echo $lang['survey_59'] ?>
				<div style="font-weight:normal;">
					<i><?php echo $lang['survey_60'] ?></i>
				</div>
			</td>
			<td valign="top" style="padding:0 0 10px 0;padding-left:15px;padding-bottom:25px;">	
				<input type="hidden" name="old_logo" id="old_logo" value="<?php echo $logo ?>">
				<div id="old_logo_div" style="font-family:tahoma;color:#555;font-size:11px;display:<?php echo (!empty($logo) ? "block" : "none") ?>">
					<?php echo $lang['survey_61'] ?> &nbsp;
					<a href="javascript:;" style="font-family:tahoma;font-size:10px;color:#800000;text-decoration:none;" onclick='
						if (confirm("Do you wish to delete this logo?")) {
							$("#new_logo_div").css({"display":"block"});
							$("#old_logo_div").css({"display":"none"});
							$("#old_logo").val("");
						}
					'>[X] <?php echo $lang['survey_62'] ?></a>
					<br>
					<img src="<?php echo APP_PATH_WEBROOT ?>DataEntry/image_view.php?pid=<?php echo $project_id ?>&id=<?php echo $logo ?>" alt="[IMAGE]" title="[IMAGE]" style="max-width:500px; expression(this.width > 500 ? 500 : true);">
				</div>
				<div id="new_logo_div" style="font-family:tahoma;color:#555;font-size:11px;display:<?php echo (empty($logo) ? "block" : "none") ?>">
					<?php echo $lang['survey_63'] ?><br>
					<input type="file" name="logo" id="logo_id" size="50" onchange="checkLogo(this.value);">
					<div style="color:#777;font-family:tahoma;font-size:10px;padding:2px 0 0;">
						<?php echo $lang['design_198'] ?>
					</div>
				</div>
				<div id="hide_title_div" style="font-size:11px;padding-top:2px;">
					<input type="checkbox" name="hide_title" id="hide_title" class="imgfix2" <?php echo ($hide_title ? "checked" : "") ?>> 
					<?php echo $lang['survey_64'] ?>
				</div>
			</td>
		</tr>
		
		<!-- Instructions -->
		<tr>
			<td valign="top" style="width:20px;">
				<img src="<?php echo APP_PATH_IMAGES ?>page_white_text.png">
			</td>
			<td valign="top" style="width:200px;font-weight:bold;">
				<?php echo $lang['survey_65'] ?>
				<div style="font-weight:normal;">
					<i><?php echo $lang['survey_66'] ?></i>
				</div>
			</td>
			<td valign="top" style="padding-left:15px;padding-bottom:15px;">
				<textarea style="width:90%;height:180px;" name="instructions"><?php echo $instructions ?></textarea>
			</td>
		</tr>
		
		<!-- Survey Access -->
		<tr>
			<td colspan="3">
				<div class="header" style="padding:7px 10px 5px;margin:0 -10px 10px;"><?php echo $lang['survey_293'] ?></div>
			</td>
		</tr>
		
		<!-- Survey Expiration -->
		<tr>
			<td valign="top" style="width:20px;">
				<img src="<?php echo APP_PATH_IMAGES ?>calendar_exclamation.png">
			</td>
			<td valign="top" style="width:200px;font-weight:bold;">
				<?php echo $lang['survey_294'] ?>
				<div style="font-weight:normal;">
					<i><?php echo $lang['survey_295'] ?></i> 
					<a href="javascript:;" onclick="simpleDialog('<?php echo cleanHtml($lang['survey_299']) ?>','<?php echo cleanHtml($lang['survey_294']) ?>')"><img src="<?php echo APP_PATH_IMAGES ?>help.png" style="vertical-align:middle;"></a>
				</div>
			</td>
			<td valign="top" style="padding:0 0 5px 15px;">
				<input name="survey_expiration" type="text" style="width:103px;" class="x-form-text x-form-field datetime_mdy" 
					onblur="redcap_validate(this,'','','soft_typed','datetime_mdy',1,0)" 
					value="<?php echo str_replace('"', '&quot;', label_decode($survey_expiration)) ?>" 
					onkeydown="if(event.keyCode==13){return false;}"
					onfocus="this.value=trim(this.value); if(this.value.length == 0 && $('.ui-datepicker:first').css('display')=='none'){$(this).next('img').trigger('click');}">
				<span class='df'>M-D-Y H:M</span>
				<div class="cc_info">
					<?php echo $timezoneText ?>
				</div>
				
			</td>
		</tr>
		
		<!-- Save and Return Later -->
		<tr>
			<td valign="top" style="width:20px;padding:10px 0;">
				<img src="<?php echo APP_PATH_IMAGES ?>arrow_circle_315.png">
			</td>
			<td valign="top" style="width:200px;font-weight:bold;padding:10px 0;">
				<?php echo $lang['survey_57'] ?>
				<div style="font-weight:normal;">
					<i><?php echo $lang['survey_304'] ?></i> 
					<a href="javascript:;" onclick="simpleDialog('<?php echo cleanHtml($lang['survey_58']) ?>','<?php echo cleanHtml($lang['survey_57']) ?>')"><img src="<?php echo APP_PATH_IMAGES ?>help.png" style="vertical-align:middle;"></a>
				</div>
			</td>
			<td valign="top" style="padding:10px 0;padding-left:15px;">
				<select name="save_and_return" class="x-form-text x-form-field" style="padding-right:0;height:22px;">
					<option value="0" <?php echo (!$save_and_return ? 'selected' : '') ?>><?php echo $lang['design_99'] ?></option>
					<option value="1" <?php echo ( $save_and_return ? 'selected' : '') ?>><?php echo $lang['design_100'] ?></option>
				</select>
			</td>
		</tr>
		
		<!-- End Survey Redirect URL -->
		<tr>
			<td colspan="3">
				<div class="header" style="padding:7px 10px 5px;margin:0 -10px 10px;"><?php echo $lang['survey_290'] ?></div>
			</td>
		</tr>
		<tr>
			<td valign="top" style="width:20px;">
				<input type="radio" id="survey_termination_options_url" name="survey_termination_options" value="url" <?php echo ($end_survey_redirect_url != '' ? 'checked' : '') ?>
					onclick="$('#end_survey_redirect_url').focus();">
			</td>
			<td valign="top" style="width:200px;font-weight:bold;padding-bottom:3px;">
				<?php echo $lang['survey_288'] ?>
				<div style="font-weight:normal;">
					<i><?php echo $lang['survey_292'] ?></i>
				</div>
			</td>
			<td valign="top" style="padding-left:15px;">
				<input id="end_survey_redirect_url" name="end_survey_redirect_url" type="text" onblur="isUrlError(this);if(this.value==''){$('#survey_termination_options_text').prop('checked',true);$('#end_survey_redirect_url_append_id').prop('checked',false);}else if($('#view_results').val() != '0'){ $('#view_results').val('0');simpleDialog('<?php echo cleanHtml2($lang['survey_301']) ?>','<?php echo cleanHtml2($lang['survey_300']) ?>','',600); }" onfocus="$('#survey_termination_options_url').prop('checked',true);" value="<?php echo str_replace('"', '&quot;', label_decode($end_survey_redirect_url)) ?>" class="x-form-text x-form-field" style="width:88%;" onkeydown="if(event.keyCode==13){return false;}">
				<div class="cc_info" style="margin:0;color:#777;">
					<?php echo $lang['survey_289'] ?>
				</div>
				<?php 
				/* 
				<!-- Append participant_id to URL? -->
				<div class="cc_info" style="color:#000;margin:7px 0 0;">
					<input type="checkbox" name="end_survey_redirect_url_append_id" id="end_survey_redirect_url_append_id" class="imgfix2" <?php if ($end_survey_redirect_url_append_id) echo "checked"; ?>>
					<?php echo $lang['survey_308'] ?>
				</div>
				<div class="cc_info" style="margin:0 0 2px 22px;color:#777;">
					(e.g. http://www.example.com?participant_id=47)
				</div>
				 */
				?>
				<input type="hidden" name="end_survey_redirect_url_append_id" id="end_survey_redirect_url_append_id" value="<?php echo $end_survey_redirect_url_append_id ?>">
			</td>
		</tr>
		
		<!-- OR -->
		<tr>
			<td valign="top" colspan="3" style="padding:0px 0px 4px 20px;color:#777;">
				&mdash; <?php echo $lang['global_46'] ?> &mdash;
			</td>
		</tr>
		
		<!-- Acknowledgement -->
		<tr>
			<td valign="top" style="width:20px;">
				<input type="radio" id="survey_termination_options_text" name="survey_termination_options" value="text" <?php echo ($end_survey_redirect_url == '' ? 'checked' : '') ?>>
			</td>
			<td valign="top" style="width:200px;font-weight:bold;">
				<?php echo $lang['survey_67'] ?>
				<div style="font-weight:normal;">
					<i><?php echo $lang['survey_68'] ?></i>
				</div>
			</td>
			<td valign="top" style="padding-left:15px;padding-bottom:15px;">
				<textarea style="width:90%;height:180px;" name="acknowledgement"><?php echo $acknowledgement ?></textarea>
			</td>
		</tr>
		
		<!-- Save Button -->
		<tr>
			<td colspan="2"></td>
			<td valign="middle" style="padding:10px 0 20px 15px;">
				<input type="submit" style="font-weight:bold;" value=" <?php echo $lang['report_builder_28'] ?> ">
			</td>
		</tr>
		
		<!-- Cancel/Delete buttons -->
		<tr>
			<td colspan="2" style="border-top:1px solid #ddd;"></td>
			<td valign="middle" style="border-top:1px solid #ddd;padding:10px 0 20px 15px;">
				<input type="button" onclick="history.go(-1)" value=" -- <?php echo cleanHtml2($lang['global_53']) ?>-- "><br>
				<?php if (PAGE == 'Surveys/edit_info.php') { ?>
				<!-- Option to delete the survey (only when editing surveys) -->
				<div style="margin-top:25px;">
					<input type="button" onclick="deleteSurvey(<?php echo $_GET['survey_id'] ?>);" value=" <?php echo cleanHtml2($lang['survey_379']) ?> ">
				</div>
				<!-- Info about what deleting a survey does -->
				<div style="margin-top:7px;font-size:11px;color:#777;line-height:11px;">
					<?php echo RCView::b($lang['survey_379'].$lang['colon']) . ' ' . $lang['survey_381'] ?>
				</div>
				<?php } ?>
			</td>
		</tr>
		
	</table>
</form>

<!-- Hidden div for explaining the graphical diversity restriction setting -->
<div id="diversity_explain" style="display:none;" title="<?php echo cleanHtml2($lang['survey_189']) ?>">
	<p><?php echo "{$lang['survey_190']} <b>{$lang['survey_208']} <i style='color:#666;'>\"{$lang['survey_202']}\"</i></b>" ?></p>
	<p><?php echo $lang['survey_207'] ?></p>
</div>

<!-- Javascript needed -->
<script type="text/javascript">
// Check if need to disable View Survey Results sub-options
$(function(){
	checkViewResults();
	$('#view_results').change(function(){
		checkViewResults();
	});
});
function checkViewResults() {
	if ($('#view_results').val() == '0') {
		$('.view_results_options').fadeTo(0,0.3);
		$('.view_results_options input').attr('disabled', true);
	} else {
		$('.view_results_options').fadeTo(500,1);
		$('.view_results_options input').attr('disabled', false);
		$('.view_results_options input').removeAttr('disabled');
	}
}
// Delete the survey
function deleteSurvey(survey_id) {
	simpleDialog('<?php echo cleanHtml(RCView::div(array('style'=>'font-weight:bold;margin-bottom:10px;'), $lang['survey_381']).RCView::div(array('style'=>'margin-top:10px;color:red;'), RCView::b($lang['global_03'].$lang['colon']) . " " . $lang['survey_382'])) ?>','<?php echo cleanHtml($lang['survey_380']) ?>',null,600,null,"Cancel","deleteSurveySave("+survey_id+");",'<?php echo cleanHtml($lang['survey_379']) ?>');
}
function deleteSurveySave(survey_id) {
	$.post(app_path_webroot+'Surveys/delete_survey.php?pid='+pid+'&survey_id=<?php echo $_GET['survey_id'] ?>',{ },function(data){
		if (data != '1') {
			alert(woops);
		} else {
			simpleDialog('<?php echo cleanHtml($lang['survey_385']) ?>','<?php echo cleanHtml($lang['survey_384']) ?>',null,null,"window.location.href='"+app_path_webroot+"Design/online_designer.php?pid="+pid+"';");
		}
	});
}
</script>