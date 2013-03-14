<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


?>
<!-- Hidden fields -->
<input type="hidden" id="surveys_enabled" name="surveys_enabled" value="0">
<input type="hidden" id="repeatforms" name="repeatforms" value="0">
<input type="hidden" id="scheduling" name="scheduling" value="0">
<input type="hidden" id="randomization" name="randomization" value="0">

<!-- Gray out design steps on page load -->
<script type="text/javascript">
$(function(){
	$('#step2').fadeTo(0, 0.2);
	$('#step3').fadeTo(0, 0.2);
	$('#additional_options').fadeTo(0, 0.2);
	<?php if (!$enable_projecttype_singlesurvey) { ?>
		// Disable Single Survey project type
		$('#projecttype0_div').hide();
	<?php } ?>
	<?php if ($enable_projecttype_singlesurvey && !$enable_projecttype_singlesurveyforms && !$enable_projecttype_forms) { ?>
		// Auto select the Single Survey project type
		$('#projecttype0').prop('checked',true);
		$('#projecttype0').click();
	<?php } ?>
	<?php if (!$enable_projecttype_singlesurveyforms) { ?>
		// Disable Single Survey + Forms project type
		$('#projecttype2_div').hide();
	<?php } ?>
	<?php if (!$enable_projecttype_singlesurvey && $enable_projecttype_singlesurveyforms && !$enable_projecttype_forms) { ?>
		// Auto select the Single Survey + Forms project type
		$('#projecttype2').prop('checked',true);
		$('#projecttype2').click();
	<?php } ?>
	<?php if (!$enable_projecttype_forms) { ?>
		// Disable Data Entry Forms project type
		$('#projecttype1_div').hide();
	<?php } ?>
	<?php if (!$enable_projecttype_singlesurvey && !$enable_projecttype_singlesurveyforms && $enable_projecttype_forms) { ?>
		// Auto select the Data Entry Forms project type
		$('#projecttype1').prop('checked',true);
		$('#projecttype1').click();
	<?php } ?>
	<?php if (!$enable_projecttype_singlesurvey && !$enable_projecttype_singlesurveyforms && !$enable_projecttype_forms) { ?>
		// All project types have been disabled
		$('#step1').append('<span style="color:#800000;"><img src="'+app_path_images+'exclamation.png" class="imgfix"> <?php echo cleanHtml($lang['system_config_155']) ?></span>');
		$('#step2').hide();
		$('input[type="button"]').prop('disabled',true);
	<?php } ?>
});
</script>

		
<!-- Table rows for Create/Edit Project form -->
<tr valign="top">
	<td style="padding-right:20px;width:200px;">
		<b><?php echo $lang["create_project_01"] ?></b>
	</td>
	<td>
		<input name="app_title" id="app_title" type="text" class="x-form-text x-form-field" onkeydown="if(event.keyCode==13){return false;}" size="60">
		<div class="newdbsub">
			<?php echo $lang["create_project_02"] ?>
		</div>
	</td>
</tr>

<tr id="row_purpose" valign="top">
	<td style="padding-top:10px;" id="row_purpose1">
		<b><?php echo $lang["create_project_12"] ?></b><br>
		<i><?php echo $lang["create_project_13"] ?></i>
	</td>
	<td style="padding-top:10px;" id="row_purpose2">
		<select id="purpose" name="purpose" class="x-form-text x-form-field" style="padding-right:0;height:22px;" onchange='
			if (this.value == "1" || this.value == "2") {
				$("#purpose_other_span").css("visibility","visible");
			} else{
				$("#purpose_other_span").css("visibility","hidden");
			}
			$("#project_pi_irb_div").hide();
			if (this.value == "1") { 
				$("#purpose_other_text").show();
			} else {
				$("#purpose_other_text, #project_pi_firstname, #project_pi_mi, #project_pi_lastname, #project_pi_email, #project_pi_alias, #project_irb_number, #project_grant_number, #project_pi_username").val("");
				$("#vunetid-namecheck").html("");
				$("#purpose_other_text").hide();
			}
			if (this.value == "2") { 
				$("#purpose_other_select, #project_pi_irb_div, #purpose_other_research").show();
			} else {
				$("#purpose_other_select").val("");
				$("#purpose_other_select, #purpose_other_research").hide();
			}
		'>
			<option value=""> ---- <?php echo $lang["create_project_14"] ?> ---- </option>
			<option value="<?php echo RedCapDB::PURPOSE_PRACTICE ?>"><?php echo $lang["create_project_15"] ?></option>
			<option value="<?php echo RedCapDB::PURPOSE_OPS ?>"><?php echo $lang["create_project_16"] ?></option>
			<option value="<?php echo RedCapDB::PURPOSE_RESEARCH ?>"><?php echo $lang["create_project_17"] ?></option>
			<option value="<?php echo RedCapDB::PURPOSE_QUALITY ?>"><?php echo $lang["create_project_18"] ?></option>
			<option value="<?php echo RedCapDB::PURPOSE_OTHER ?>"><?php echo $lang["create_project_19"] ?></option>
		</select>&nbsp;&nbsp;&nbsp;
		<div id="purpose_other_span" style="visibility:hidden;padding-top:5px;">
			<div id="project_pi_irb_div" style="display:none;padding:0 0 5px;">
				<!-- Project PI -->
				<div style="padding:3px 0;">
					<div style="float:left;"><b><?php echo $lang["create_project_34"] ?></b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
					<div style="float:left;color:#555;font-size:11px;">
						<input type="text" maxlength="100" name="project_pi_firstname" id="project_pi_firstname" onkeydown="if(event.keyCode==13){return false;}" class="x-form-text x-form-field" style="width:70px;">
						<br/><?php echo $lang['create_project_55'] ?>
					</div>
					<div style="float:left;color:#555;font-size:11px;">
						<input type="text" maxlength="1" name="project_pi_mi" id="project_pi_mi" onkeydown="if(event.keyCode==13){return false;}" class="x-form-text x-form-field" style="width:14px;">
						<br/><?php echo $lang['create_project_56'] ?>
					</div>
					<div style="float:left;color:#555;font-size:11px;">
						<input type="text" maxlength="100" name="project_pi_lastname" id="project_pi_lastname" onkeydown="if(event.keyCode==13){return false;}" class="x-form-text x-form-field" style="width:80px;">
						<br/><?php echo $lang['create_project_57'] ?>
					</div>
					<div style="clear:both;"></div>
				</div>
				<div style="padding:3px 0;">
					<b><?php echo $lang["create_project_59"] ?></b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					<input type="text" maxlength="200" name="project_pi_email" id="project_pi_email" size="25" onkeydown="if(event.keyCode==13){return false;}" class="x-form-text x-form-field" onblur="redcap_validate(this,'','','hard','email');">
				</div>
				<div style="padding:3px 0;">
					<b><?php echo $lang["create_project_58"] ?></b>&nbsp;&nbsp;&nbsp;
					<input type="text" maxlength="100" name="project_pi_alias" id="project_pi_alias" onkeydown="if(event.keyCode==13){return false;}" class="x-form-text x-form-field" style="width:80px;">
					<span style="color:#555;font-size:11px;">&nbsp; (e.g. Harris PA)</span>
				</div>
<?php if (isVanderbilt() || isDev()) { // Only ask for VUnetID for Vanderbilt users. Use AJAX call to verify name for VUnetID. ?>
				<div style="padding:3px 0;">
					<b><?php echo $lang["create_project_36"] ?></b>&nbsp; 
					<input type="text" maxlength="10" size="15" name="project_pi_username" id="project_pi_username" onkeydown="if(event.keyCode==13){return false;}" class="x-form-text x-form-field" onblur='
						$(this).val(trim( $(this).val() ));
						$("#vunetid-namecheck").html("");
						if ( $(this).val().length < 1 ) return;
						$("#vunetid-namecheck").html("<img src=\""+app_path_images+"progress_circle.gif\" class=\"imgfix\"> <span style=\"color:#444;\">Verifying...</span>");
						$.get("/vunetid_check.php", { vunetid: $(this).val() }, function(data){
							if (data.length > 0 && data != "0") {
								$("#vunetid-namecheck").html("<img src=\""+app_path_images+"tick.png\" class=\"imgfix\"> <span style=\"color:green;\">"+data+"</span>");
							} else {
								$("#vunetid-namecheck").html("<span style=\"color:red;\">Could not verify! May be incorrect.</span>");
							}
						});
					'>
					&nbsp; <span id="vunetid-namecheck"></span>
				</div>
<?php } ?>
				<div style="padding:3px 0;">
					<b><?php echo $lang["create_project_35"] ?></b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					<input type="text" maxlength="100" size="15" name="project_irb_number" id="project_irb_number" onkeydown="if(event.keyCode==13){return false;}" class="x-form-text x-form-field">
				</div>
<?php if (isVanderbilt() || isDev()) { // Only ask for grant number for Vanderbilt users (may add for all sites later) ?>
				<div style="padding:3px 0;">
					<b><?php echo $lang["create_project_37"] ?></b>&nbsp; 
					<input type="text" maxlength="255" size="15" name="project_grant_number" id="project_grant_number" onkeydown="if(event.keyCode==13){return false;}" class="x-form-text x-form-field">
				</div>
<?php } ?>
			</div>
			<b><?php echo $lang["create_project_20"] ?></b>&nbsp;&nbsp;&nbsp;
			<input type="text" maxlength="100" size="40" name="purpose_other" id="purpose_other_text" onkeydown="if(event.keyCode==13){return false;}" class="x-form-text x-form-field" style="display:none;">
			<div id="purpose_other_research" style="display:none;">
				<div style="text-indent:-1.9em;padding-left:1.9em;"><input type="checkbox" name="purpose_other[0]" id="purpose_other[0]" value="0"> <?php echo $lang["create_project_21"] ?> </div>
				<div style="text-indent:-1.9em;padding-left:1.9em;"><input type="checkbox" name="purpose_other[1]" id="purpose_other[1]" value="1"> <?php echo $lang["create_project_22"] ?> </div>
				<div style="text-indent:-1.9em;padding-left:1.9em;"><input type="checkbox" name="purpose_other[2]" id="purpose_other[2]" value="2"> <?php echo $lang["create_project_23"] ?> </div>
				<div style="text-indent:-1.9em;padding-left:1.9em;"><input type="checkbox" name="purpose_other[3]" id="purpose_other[3]" value="3"> <?php echo $lang["create_project_24"] ?> </div>
				<div style="text-indent:-1.9em;padding-left:1.9em;"><input type="checkbox" name="purpose_other[4]" id="purpose_other[4]" value="4"> <?php echo $lang["create_project_25"] ?> </div>
				<div style="text-indent:-1.9em;padding-left:1.9em;"><input type="checkbox" name="purpose_other[5]" id="purpose_other[5]" value="5"> <?php echo $lang["create_project_26"] ?> </div>
				<div style="text-indent:-1.9em;padding-left:1.9em;"><input type="checkbox" name="purpose_other[6]" id="purpose_other[6]" value="6"> <?php echo $lang["create_project_27"] ?> </div>
				<div style="text-indent:-1.9em;padding-left:1.9em;"><input type="checkbox" name="purpose_other[7]" id="purpose_other[7]" value="7"> <?php echo $lang["create_project_19"] ?> </div>
			</div>
		</div>
	</td>
</tr>

<tr valign="top" id="row_projecttype_title" style="display:none;">
	<td colspan="2" valign="top" style="padding-top:10px;">
		<div id="primary_use_disable" class="yellow" style="display:none;font-family:tahoma;font-size:10px;margin-bottom:10px;" valign="top">
			<b><?php echo $lang['global_02'] ?>:</b> <?php echo $lang['create_project_41'] ?>
		</div>
		<b><?php echo $lang['create_project_42'] ?></b>
	</td>
</tr>
			
<tr valign="top" id="row_projecttype" style="display:none;">
	<td colspan="2">					
		<input type="checkbox" id="datacollect_chk" checked style="display:none;">		
		<div style="margin-left:50px;">
						
			<!-- STEP 1 -->
			<div id="step1" class="blue" style="margin:10px 0;font-family:arial;">
				<b><?php echo $lang['create_project_43'] ?></b> &nbsp;
				<a href="javascript:;" style="color:#800000;font-family:tahoma;font-size:10px;" onclick="$(this).next('div').toggle('blind','fast');"><?php echo $lang['global_58'] ?></a>
				<div id="projTypeExplain" style="display:none;padding:5px 0;color:#800000;">
					<?php echo $lang['create_project_44'] ?>
				</div>
				<div style="padding:5px 0;margin-left:30px;">
					<div id="projecttype0_div">
						<input type="radio" name="projecttype" id="projecttype0" onclick="setFieldsCreateForm()"> 
						<?php echo $lang['global_60'] ?>
					</div>
					<div id="projecttype1_div">
						<input type="radio" name="projecttype" id="projecttype1" onclick="setFieldsCreateForm()"> 
						<?php echo $lang['global_61'] ?>
						<span style="color:#63648D;font-family:tahoma;font-size:11px;padding-left:5px;">
							<?php echo $lang['create_project_45'] ?>
						</span>
					</div>
					<div id="projecttype2_div">
						<input type="radio" name="projecttype" id="projecttype2" onclick="setFieldsCreateForm()"> 
						<?php echo $lang['global_86'] ?>
						<div style="color:#63648D;font-family:tahoma;font-size:11px;margin-left:25px;">
							<?php echo $lang['create_project_64'] ?>
						</div>
					</div>
				</div>
			</div>	
			
			<!-- STEP 2 -->
			<div id="step2" class="blue" style="margin:10px 0;font-family:arial;">
				<b><?php echo $lang['create_project_47'] ?></b> &nbsp;
				<a href="javascript:;" style="color:#800000;font-family:tahoma;font-size:10px;" onclick="$(this).next('div').toggle('blind','fast');"><?php echo $lang['global_58'] ?></a>
				<div id="collFormatExplain" style="display:none;padding:5px 0;color:#800000;">
					<?php echo $lang['create_project_48'] ?>
				</div>
				<div style="padding:5px 0;margin-left:30px;">
					<div class="hang" style="padding-bottom:4px;">
						<input type="radio" name="repeatforms_chk" id="repeatforms_chk1" onclick="setFieldsCreateForm()" disabled>
						<b><?php echo $lang['create_project_49'] ?></b> <?php echo $lang['create_project_50'] ?><br>
					</div>
					<div class="hang">
						<input type="radio" name="repeatforms_chk" id="repeatforms_chk2" onclick="setFieldsCreateForm()" disabled>
						<b><?php echo $lang['create_project_51'] ?></b> <?php echo $lang['create_project_52'] ?><br>
						<!-- STEP 3: Scheduling -->
						<div id="step3" style="padding:2px 0 0 20px;">
							<input type="checkbox" id="scheduling_chk" class="imgfix2" onclick="setFieldsCreateForm()" disabled> 
							<?php echo $lang['create_project_53'] ?>
							<a href="javascript:;" style="color:#800000;font-family:tahoma;font-size:10px;" onclick="$(this).next('div').toggle('blind','fast');"><?php echo $lang['global_58'] ?></a>
							<div style="margin-left:-15px;text-indent:-2px;display:none;padding:5px 0;color:#800000;">
								<?php echo $lang['create_project_54'] ?>
							</div>
						</div>
					</div>
				</div>
			</div>
			
			<!-- Additional Options -->
			<?php if ($randomization_global) { ?>
			<div id="additional_options" class="blue" style="margin:10px 0;font-family:arial;">
				<b><?php echo $lang['create_project_60'] ?></b> &nbsp;
				<div id="addopt1" style="padding:5px 0;margin-left:30px;">
					<input type="checkbox" id="randomization_chk" class="imgfix2" onclick="setFieldsCreateForm()" disabled> 
					<?php echo $lang['create_project_61'] ?>
					<a href="javascript:;" style="color:#800000;font-family:tahoma;font-size:10px;" onclick="$(this).next('div').toggle('blind','fast');"><?php echo $lang['global_58'] ?></a>
					<div style="margin-left:0px;display:none;padding:5px 0;color:#800000;">
						<div><?php echo $lang['random_01'] ?></div>
						<div style="padding-top:6px;"><?php echo $lang['create_project_63'] ?></div>
					</div>
				</div>
			</div>
			<?php } ?>
		
		</div>		
	</td>
</tr>
