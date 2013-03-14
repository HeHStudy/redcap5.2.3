<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . "/Config/init_project.php";
require_once APP_PATH_DOCROOT . 'Design/functions.php';

// Use correct metadata table depending on status
$metadata_table = ($status > 0) ? "redcap_metadata_temp" : "redcap_metadata";

// Validate field as a multiple choice field and get value of branching logic and label
if (!isset($_POST['field_name'])) exit('0');
$sql = "select branching_logic, element_label from $metadata_table where project_id = $project_id 
		and field_name = '" . prep($_POST['field_name']) . "' limit 1";
$q = db_query($sql);
$field_exists = (db_num_rows($q) > 0);
if (!$field_exists) {
	exit('0');
} else {
	$branching_logic = trim(html_entity_decode(db_result($q, 0, "branching_logic"), ENT_QUOTES));
	$field_label = strip_tags(label_decode(db_result($q, 0, "element_label")));
}


## Display logic builder popup
if ($_POST['action'] == 'view' && isset($_POST['form_name']))
{
	// Loop through all fields and collect info to place in Logic Build pane
	$fields = array();
	$fields_raw = array();
	$fields_form = array();
	$counter = 1;
	$sql = "select * from $metadata_table where project_id = $project_id and field_name != '".prep($_POST['field_name'])."' 
			order by field_order";
	$q = db_query($sql);
	while ($attr = db_fetch_assoc($q))
	{
		$field_name = $attr['field_name'];
		// Treat different field types differently
		switch ($attr['element_type']) {
			case "checkbox":
			case "select":
			case "radio":
				foreach (parseEnum($attr['element_enum']) as $code=>$label)
				{
					// Remove all html and other bad characters
					$label = strip_tags(label_decode($label));
					$varAndLabel = "$field_name = $label";
					if (strlen($varAndLabel) > 70) {
						$varAndLabel = substr($varAndLabel, 0, 68) . "...";
					}
					if ($attr['element_type'] == "checkbox") {
						$fields_raw[$counter] = "[$field_name($code)] = '1'";
					} else {
						$fields_raw[$counter] = "[$field_name] = '$code'";
					}
					$fields_form[$counter] = $attr['form_name'];
					$fields[$counter++] = "$varAndLabel ($code)";					
				}
				break;
			case "truefalse":
				$fields_raw[$counter] = "[$field_name] = '1'";
				$fields_form[$counter] = $attr['form_name'];
				$fields[$counter++] = $field_name.' = '.$lang['design_186'].' (1)';
				$fields_raw[$counter] = "[$field_name] = '0'";
				$fields_form[$counter] = $attr['form_name'];
				$fields[$counter++] = $field_name.' = '.$lang['design_187'].' (0)';
				break;
			case "yesno":
				$fields_raw[$counter] = "[$field_name] = '1'";
				$fields_form[$counter] = $attr['form_name'];
				$fields[$counter++] = $field_name.' = '.$lang['design_100'].' (1)';
				$fields_raw[$counter] = "[$field_name] = '0'";
				$fields_form[$counter] = $attr['form_name'];
				$fields[$counter++] = $field_name.' = '.$lang['design_99'].' (0)';
				break;
			case "text":
			case "textarea":
			case "slider":
			case "calc":
				$fields_raw[$counter] = "[$field_name] = ".cleanHtml($lang['design_411']);
				$fields_form[$counter] = $attr['form_name'];
				$fields[$counter++] = $field_name." = ".cleanHtml($lang['design_411']);
				break;
		}
	}
	
	// If project has more than one form, then display drop-down with form list, which will show/hide fields from that form
	$instrumentDropdown = "";
	if (count($Proj->forms) > 1)
	{
		// Render drop-down
		$instrumentDropdown .= "<div style=';overflow:hidden;font-weight:bold;'>
									{$lang['design_229']}<br>
									<select id='brFormSelect' onchange=\"displayBranchingFormFields(this);\">";
		foreach ($Proj->forms as $form_name=>$attr)
		{
			// Decide which form to pre-select
			$isSelected = ($_POST['form_name'] == $form_name) ? "selected" : "";
			// Render option
			$instrumentDropdown .= "<option value='$form_name' $isSelected>".strip_tags(label_decode($attr['menu']))."</option>";
		}
		$instrumentDropdown .= "	</select>
								</div>";
	}
	

	?>
	<!-- Instructions -->
	<p style="font-size:11px;font-family:tahoma;border-bottom:1px solid #ccc;padding-bottom:10px;margin-bottom:0;">
		<?php echo $lang['design_226'] ?>
	</p>		
		
	<div style="padding-top:10px;">
		<table cellspacing="0" width="100%">
		
			<tr>
				<td valign="top" colspan="2" style="padding-bottom:4px;font-family:verdana;color:#777;font-weight:bold;">
					<div style="width:700px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
						<?php echo $lang['design_230'] ?> 
						<span style="color:#008000;padding-left:4px;"><?php echo $_POST['field_name'] ?></span>
						<span style="color:#008000;font-weight:normal;font-family:arial;">- <i><?php echo $field_label ?></i></span>
					</div>
				</td>
			</tr>
			
			<!-- Advanced Branching Logic text box -->
			<tr>
				<td valign="top" style="padding:15px 20px 0 5px;">
					<input <?php if ($branching_logic != "") echo "checked"; ?> type="radio" name="optionBranchType" onclick="chooseBranchType(this.value,true);" value="advanced">
				</td>
				<td valign="top">
					<div style="font-weight:bold;padding:15px 20px 0 0;color:#800000;font-family:verdana;">
						<?php echo $lang['design_231'] ?>
						<!-- Help link -->
						<a href="javascript:;" style="margin-left:80px;font-weight:normal;text-decoration:underline;font-size:11px;" onclick="helpPopup('BranchingLogic');"><?php echo $lang['design_93'] ?></a>
					</div>
					<div id="logic_builder_advanced" class="chklist" style="border:1px solid #ccc;padding:8px 10px 2px;margin:5px 0 15px;">
						<div style="padding-bottom:2px;">
							<?php echo $lang['design_227'] ?>
						</div>
						<textarea id="advBranchingBox" style="padding:1px;width:98%;height:45px;"><?php echo $branching_logic ?></textarea>
						<div style="text-align:right;width:98%;">
							<a id="linkClearAdv" style="font-family:tahoma;font-size:11px;text-decoration:underline;" href="javascript:;" onclick="$('#advBranchingBox').val('');"><?php echo $lang['design_232'] ?></a>
						</div>
					</div>
					
				</td>
			</tr>
			
			<!-- OR -->
			<tr>
				<td valign="top" colspan="2" style="padding:8px 15px 8px 0px;font-weight:bold;color:#777;">
					&#8212; <?php echo $lang['global_46'] ?> &#8212;
				</td>
			</tr>
						
			<!-- Drag-n-drop -->
			<tr>
				<td valign="top" style="padding:15px 20px 0 5px;">
					<input <?php if ($branching_logic == "") echo "checked"; ?> type="radio" name="optionBranchType" onclick="chooseBranchType(this.value,true);" value="drag">
				</td>
				<td valign="top">	
					<div style="font-weight:bold;padding:15px 20px 0 0;font-family:verdana;color:#800000;"><?php echo $lang['design_233'] ?></div>
					<div id="logic_builder_drag" class="chklist" style="border:1px solid #ccc;padding:10px 10px 2px;margin:5px 0;">
						<!-- Drop-down for choosing instruments, if applicable -->
						<?php echo $instrumentDropdown ?>
						<table cellspacing="0">
							<tr>
								<td valign="bottom" style="width:290px;padding:20px 2px 2px;">
									<!-- Div containing options to drag over -->
									<b><?php echo $lang['design_234'] ?></b><br>
									<?php echo $lang['design_235'] ?><br>
									<div class="listBox" id="nameList" style="height:150px;overflow:auto;cursor:move;">
										<ul id="ulnameList">
										<?php foreach ($fields as $count=>$this_field) { ?>
											<li <?php if ($_POST['form_name'] != $fields_form[$count]) echo 'style="display:none;"'; ?> class="dragrow brDrag br-frm-<?php echo $fields_form[$count] ?>" val="<?php echo $fields_raw[$count] ?>"><?php echo $this_field ?></li>
										<?php } ?>
										</ul>
									</div>
									<div style="font-size:11px;">&nbsp;</div>
								</td>
								<td valign="middle" style="text-align:center;font-weight:bold;font-size:11px;color:green;padding:0px 20px;">
									<img src="<?php echo APP_PATH_IMAGES ?>arrow_right.png"><br><br>
									<?php echo $lang['design_236'] ?><br>
									<?php echo $lang['global_43'] ?><br>
									<?php echo $lang['design_237'] ?><br><br>
									<img src="<?php echo APP_PATH_IMAGES ?>arrow_right.png">
								</td>
								<td valign="bottom" style="width:290px;padding:0px 2px 2px;">				
									<!-- Div where options will be dragged to -->
									<b><?php echo $lang['design_227'] ?></b><br>
									<input type="radio" name="brOper" id="brOperAnd" value="and" onclick="updateAdvBranchingBox();" checked> <?php echo $lang['design_238'] ?><br>
									<input type="radio" name="brOper" id="brOperOr" value="or" onclick="updateAdvBranchingBox();"> <?php echo $lang['design_239'] ?><br>
									<div class="listBox" id="dropZone1" style="height:150px;overflow:auto;">
										<ul id="mylist" style="list-style:none;">
										</ul>
									</div>
									<div style="text-align:right;">
										<a id="linkClearDrag" style="font-family:tahoma;font-size:11px;text-decoration:underline;" href="javascript:;" onclick="
											$('#dropZone1').html('');
											updateAdvBranchingBox();
										"><?php echo $lang['design_232'] ?></a>
									</div>
								</td>
							</tr>
						</table>
						
					</div>
					
				</td>
			</tr>
		</table>
		
	<?php
}



## Save branching logic to field
elseif ($_POST['action'] == 'save' && isset($_POST['branching_logic']))
{
	// Demangle post
	$_POST['branching_logic'] = trim(html_entity_decode($_POST['branching_logic'], ENT_QUOTES));
	
	// Obtain array of error fields that are not real fields
	$error_fields = validateBranchingCalc($_POST['branching_logic']);
	
	// Return list of fields that do not exist (i.e. were entered incorrectly), else continue.
	if (!empty($error_fields))
	{
		print $lang['survey_470'] . RCView::br() . RCView::br() . RCView::b($lang['survey_472']) . 
			  RCView::br() . "- " . implode( RCView::br() . "- ", $error_fields);
		exit;
	}
	
	// Check if branching logic is valid
	$newBranchingIsValid = LogicTester::isValid($_POST['branching_logic']);
	
	// NON-SUPER USERS: Perform deeper inspection of syntax to make sure nothing malicious gets through
	if (!$super_user && $_POST['branching_logic'] != "" && !$newBranchingIsValid) 
	{
		// Default: Contains syntax errors (general)
		$response = "<b>{$lang['dataqueries_47']}{$lang['colon']}</b><br>{$lang['dataqueries_99']}";
		// Check the logic for illegal functions
		$parser = new LogicParser();
		try {
			$parser->parse($_POST['branching_logic']);
		} catch (LogicException $e) {
			if (count($parser->illegalFunctionsAttempted) !== 0) {
				// Contains illegal functions
				$response = "<b>{$lang['dataqueries_47']}{$lang['colon']}</b><br>{$lang['dataqueries_109']}<br><br><b>{$lang['dataqueries_48']}</b><br>- " 
						  . implode("<br>- ", $parser->illegalFunctionsAttempted);
				exit($response);
			}
		}
		// Check if the previous branching logic was valid (if existed) 
		$response2 = "";
		if ($branching_logic != "") 
		{
			$response_text = RCView::b($lang['global_02'].$lang['colon']) . RCView::SP;
			if ($branching_logic == $_POST['branching_logic']) {
				// Branching logic has NOT changed, but it is NOW considered invalid because of security measures.
				// User can keep it as is or remove the branching.
				$response_text .= $lang['design_439'];
			} else {
				// Branching HAS changed but has incorrect syntax.
				$response_text .= $lang['design_440'];
			}
			$response_text .= " <a href='javascript:;' onclick=\"helpPopup('BranchingLogic');\">{$lang['bottom_27']}</a>".$lang['period'];
			$response2 = RCView::div(array('class'=>'yellow','style'=>'margin-top:10px;'), $response_text);
		}
		// Return error message
		exit($response . $response2);
	}
	
	## Save the branching logic
	$sql = "update $metadata_table set branching_logic = " . checkNull($_POST['branching_logic']) . " 
			where project_id = $project_id and field_name = '" . prep($_POST['field_name']) . "'";
	if (db_query($sql)) {
		$response = '1';		
		// SURVEY QUESTION NUMBERING (DEV ONLY): Detect if form is a survey, and if so, if has any branching logic. If so, disable question auto numbering.
		$form_name = ($status > 0) ? $Proj->metadata_temp[$_POST['field_name']]['form_name'] : $Proj->metadata[$_POST['field_name']]['form_name'];
		if (Design::checkDisableSurveyQuesAutoNum($form_name)) {
			$response = '2';
		}
		// If a super user and there is an allowable syntax error in the logic (e.g. custom javascript), then give special msg.
		if ($super_user && $_POST['branching_logic'] != "" && !$newBranchingIsValid) {
			$response = ($response == '2') ? '4' : '3';
		}
		// Return response
		print $response;
		// Log the data change
		log_event($sql, $metadata_table, "MANAGE", $_POST['field_name'], "field_name = '{$_POST['field_name']}'", "Add/edit branching logic");
	} else {
		print '0';
	}

}



// ERROR
else
{
	exit('0');
}
