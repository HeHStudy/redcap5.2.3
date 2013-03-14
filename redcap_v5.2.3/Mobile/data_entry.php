<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

include_once dirname(dirname(__FILE__)) . "/Config/init_project.php";
// Class for html page display system
require_once APP_PATH_CLASSES . 'HtmlPage.php';

// Initialize page display object
$objHtmlPage = new HtmlPage();
$objHtmlPage->addStylesheet("smoothness/jquery-ui-".JQUERYUI_VERSION.".custom.css", 'screen,print');
$objHtmlPage->addStylesheet("jqtouch.min.css", 'screen,print');
$objHtmlPage->addStylesheet("jqtouch_themes/apple/theme.min.css", 'screen,print');
$objHtmlPage->addStylesheet("mobile_data_entry.css", 'screen,print');
$objHtmlPage->addExternalJS(APP_PATH_JS . "base.js");
$objHtmlPage->addExternalJS(APP_PATH_JS . "jqtouch.min.js");
$objHtmlPage->PrintHeader();

?>

<script type="text/javascript">
// Enable jQTouch
var jQT = new $.jQTouch();
$(function(){
	// If form is locked, hide Save button
	if ($('#lock_record_msg').length) {
		$('.whiteButton, #del-btn').hide();
		$('#unlock-disable-msg').show();
	}
	// Make section headers into toolbar CSS
	$('.header').addClass('toolbar');
	// Fix slider CSS (won't work in CSS file)
	$('.sldrnum').css({width:'15px', fontSize:'10px'});
});
</script>

<?php


// Include non-Mobile data entry page to execute all logic/queries
include_once APP_PATH_DOCROOT . "DataEntry/index.php";


// Choose a record (non-longitudinal only)
if (!isset($_GET['id']) && !$longitudinal)
{
	// Check if record ID field should have validation
	$text_val_string = "";
	if ($Proj->metadata[$table_pk]['element_type'] == 'text' && $Proj->metadata[$table_pk]['element_validation_type'] != '') 
	{
		// Apply validation function to field
		$text_val_string = "if(redcap_validate(this,'{$Proj->metadata[$table_pk]['element_validation_min']}','{$Proj->metadata[$table_pk]['element_validation_max']}','hard','".convertLegacyValidationType($Proj->metadata[$table_pk]['element_validation_type'])."',1)) ";
	}

	?>
	<div id="home" class="current">
	
		<div class="toolbar">
			<a class="back" style="max-width:40px;" href="javascript:;" onclick="window.location.href='<?php echo APP_PATH_WEBROOT ?>Mobile/choose_form.php?<?php echo (isset($_GET['child']) ? "pnid=".$_GET['child'] : "pid=$project_id") ?>';"><?php echo $lang['global_52'] ?></a>
			<h1>REDCap</h1>
		</div>
		
		<h1 style="font-size:14px;color:#fff;text-shadow:0 1px 1px #000000;"><?php echo $app_title ?></h1>
		
		<h1><?php echo $Proj->forms[$_GET['page']]['menu'] ?></h1>
		
		<?php			
		// Display msg, if exists
		if (isset($context_msg))
		{
			print $context_msg;
		}
		?>
		
		<form>
		
			<h1 style="font-size:14px;"><?php echo $lang['data_entry_24'] . " " . $table_pk_label ?></h1>
			
			<?php if (isset($rs_select1_label)) { ?>
			<ul class="edit rounded">
				<li>
					<select id="records" onchange="if (this.value.length>0) window.location.href='<?php echo APP_PATH_WEBROOT ?>Mobile/data_entry.php?pid=<?php echo "$project_id{$child}&page={$_GET['page']}&id=" ?>'+this.value;">
						<?php echo render_dropdown(implode("\n", $record_dropdown1), "", $rs_select1_label); ?>
					</select>
				</li>
			</ul>
			<?php } ?>
			
			<?php if (isset($rs_select2_label)) { ?>
			<ul class="edit rounded">
				<li>
					<select id="records" onchange="if (this.value.length>0) window.location.href='<?php echo APP_PATH_WEBROOT ?>Mobile/data_entry.php?pid=<?php echo "$project_id{$child}&page={$_GET['page']}&id=" ?>'+this.value;">
						<?php echo render_dropdown(implode("\n", $record_dropdown2), "", $rs_select2_label); ?>
					</select>
				</li>
			</ul>
			<?php } ?>
			
			<?php if (isset($rs_select3_label)) { ?>
			<ul class="edit rounded">
				<li>
					<select id="records" onchange="if (this.value.length>0) window.location.href='<?php echo APP_PATH_WEBROOT ?>Mobile/data_entry.php?pid=<?php echo "$project_id{$child}&page={$_GET['page']}&id=" ?>'+this.value;">
						<?php echo render_dropdown(implode("\n", $record_dropdown3), "", $rs_select3_label); ?>
					</select>
				</li>
			</ul>
			<?php } ?>
			
			<?php if ($_GET['page'] == $Proj->firstForm && $user_rights['record_create'] && ($user_rights['forms'][$Proj->firstForm] == '1' || $user_rights['forms'][$Proj->firstForm] == '3')) { ?>
			<?php if (!$auto_inc_set) { ?>
				<h1 style="font-size:14px;"><?php echo $lang['data_entry_31'] . " " . $table_pk_label ?></h1>
			<?php } ?>
			<ul class="edit rounded">
				<?php if ($auto_inc_set) { ?>
					<li><a class="whiteButton" href="javascript:;" onclick="$(this).css('color','#800000');$(this).css('background','red');window.location.href='<?php echo APP_PATH_WEBROOT ?>Mobile/data_entry.php?pid=<?php echo "$project_id{$child}&page={$_GET['page']}&id=".getAutoId()."&auto" ?>';"><?php echo $lang['data_entry_46'] ?></a></li>
				<?php } else { ?>
					<li><input type="text" id="inputString" name="inputString"></li>
				<?php } ?>
			</ul>
			<?php } ?>
			
		</form>
	</div>
	
	<script type="text/javascript">
	// Enable redirecting if hit Go or change value for text field
	$(function(){
		$('#inputString').keypress(function(e) {
			if (e.which == 13) {
				 $('#inputString').trigger('blur');
				return false;
			}
		});
		$('#inputString').blur(function() {
			var refocus = false;
			var idval = trim($('#inputString').val()); 
			if (idval.length < 1) {
				refocus = true;
				$('#inputString').val('');
			}
			if (idval.length > 50) {
				refocus = true;
				alert('<?php echo cleanHtml($lang['data_entry_44']) ?>'); 
			}
			if (refocus) {
				setTimeout(function(){document.getElementById('inputString').focus();},10);
			} else {
				$('#inputString').val(idval);
				<?php echo $text_val_string ?>
				setTimeout(function(){
					idval = $('#inputString').val();
					idval = idval.replace(/&quot;/g,''); // HTML char code of double quote
					// Don't allow pound signs in record names
					if (/#/g.test(idval)) {
						$('#inputString').val('');
						alert("Pound signs (#) are not allowed in record names! Please enter another record name.");
						$('#inputString').focus();
						return false;
					}
					// Don't allow apostrophes in record names
					if (/'/g.test(idval)) {
						$('#inputString').val('');
						alert("Apostrophes are not allowed in record names! Please enter another record name.");
						$('#inputString').focus();
						return false;
					}
					// Don't allow ampersands in record names
					if (/&/g.test(idval)) {
						$('#inputString').val('');
						alert("Ampersands (&) are not allowed in record names! Please enter another record name.");
						$('#inputString').focus();
						return false;
					}
					// Don't allow plus signs in record names
					if (/\+/g.test(idval)) {
						$('#inputString').val('');
						alert("Plus signs (+) are not allowed in record names! Please enter another record name.");
						$('#inputString').focus();
						return false;
					}
					window.location.href='<?php echo APP_PATH_WEBROOT ?>Mobile/data_entry.php?pid=<?php echo "$project_id{$child}&page={$_GET['page']}&id=" ?>'+idval;
				},200);
			}
		});
	});
	</script>
	<?php
	
}



// Display record's data on form
else if (isset($_GET['id']))
{
	
	// Set page to direct to when cancelled
	$cancel_redirect = APP_PATH_WEBROOT . "Mobile/" 
					 . ($longitudinal 
					 ? "choose_record.php?pid=$project_id&id={$_GET['id']}&event_id={$_GET['event_id']}" . ($multiple_arms ? "&arm=".getArm() : '') 
					 : "data_entry.php?pid=$project_id&page={$_GET['page']}")
					 . $child;

	?>
	
	<div id="home" class="current">

		<div class="toolbar">
			<a class="button leftButton" href="javascript:;" onclick="window.location.href='<?php echo $cancel_redirect ?>';"><?php echo $lang['global_53'] ?></a>
			<h1>REDCap</h1> 
		</div>
		
		<h1 style="font-size:14px;color:#fff;text-shadow:0 1px 1px #000000;"><?php echo $app_title ?></h1>
		
		<h2><?php echo $Proj->forms[$_GET['page']]['menu'] ?></h2>
		
		<?php //echo ($hidden_edit ? render_context_msg($custom_record_label, $context_msg_edit) : render_context_msg("", $context_msg_add)) ?>
		
		<div class="info">  
		<?php	
		// Render form
		form_renderer($elements, $element_data);	
		?>
		</div>

		<div class="info">
			<div>
				<a class="whiteButton" href="javascript:;" onclick="$(this).css({'color':'#800000','background':'red'});dataEntrySubmit(this);">Save Record</a>
			</div>
			<br>
			<div>
				<a class="whiteButton" href="javascript:;" onclick="$(this).css({'color':'#800000','background':'red'});dataEntrySubmit(this);">Save and Continue</a>
			</div>
			<?php if ($_GET['page'] != $last_form) { ?>
				<br>
				<div>
					<a class="whiteButton" href="javascript:;" onclick="$(this).css({'color':'#800000','background':'red'});dataEntrySubmit(this);">Save and go to Next Form</a>
				</div>
			<?php } ?>
			<div id="unlock-disable-msg">
				<?php echo $lang['mobile_site_02'] ?> 
				<a target="_blank" href="<?php echo APP_PATH_WEBROOT . "DataEntry/index.php?pid=$project_id&page={$_GET['page']}&id={$_GET['id']}&event_id={$_GET['event_id']}" ?>"><?php echo $lang['mobile_site_01'] ?></a><?php echo $lang['period'] ?>
			</div>
			<br>
			<div>
				<a class="grayButton" href="javascript:;" onclick="$(this).css('color','red');window.location.href='<?php echo $cancel_redirect ?>';">Cancel</a>
			</div>
			<?php if ($user_rights['record_delete']) { 			
				// Customize prompt message for deleting record button
				$delAlertMsg = $lang['data_entry_49'].' &quot;'.$_GET['id'].'&quot;?\n\n'.$lang['data_entry_50'];
				if ($longitudinal) {
					$delAlertMsg .= $lang['data_entry_51'];
					if ($multiple_arms) {
						$delAlertMsg .= $lang['data_entry_52'];
					}
					$delAlertMsg .= $lang['period'];
				}
				?>
				<br><br>
				<div>
					<a class="grayButton" id="del-btn" style="color:red;" href="javascript:;" onclick="if (confirm('<?php echo remBr($delAlertMsg) ?>')) { $(this).css('color','red');dataEntrySubmit(this); }">Delete Record</a>
				</div>
			<?php } ?>
		</div>
		
	</div>
	<?php

	// Render fields and their values from other events as separate hidden forms
	if ($longitudinal) {
		print addHiddenFieldsOtherEvents();
	}
	
	// Generate JavaScript equations for Calculated Fields and Branching Logic
	print $cp->exportJS();
	print $bl->exportBranchingJS();

	// Print javascript that hides checkbox fields from other forms, which need to be hidden
	print $jsHideOtherFormChkbox;
		
}
	
$objHtmlPage->PrintFooter();