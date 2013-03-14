<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


// Config
require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

if (!$randomization) redirectHome();

// Header
include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
renderPageTitle(RCView::img(array('src'=>'arrow_switch.png','class'=>'imgfix2')) . $lang['app_21']);

// Instructions
print Randomization::renderInstructions();

// Page tabs
Randomization::renderTabs();

// Display action messages when 'msg' in URL
if (isset($_GET['msg']) && !empty($_GET['msg']))
{
	// Defaults
	$msgAlign = "center";
	$msgClass = "green";
	$msgText  = "<b>{$lang['setup_08']}</b> {$lang['setup_09']}";
	$msgIcon  = "tick.png";
	$timeVisible = 7; //seconds
	// Determine which message to display
	switch ($_GET['msg'])
	{
		// Saved randomization setup
		case "saved":
			$msgText  = "<b>{$lang['setup_08']}</b> {$lang['random_99']}";
			break;
		// Saved allocation table
		case "uploadtablesuccess":
			$msgText  = "<b>{$lang['setup_08']}</b> {$lang['random_21']}";
			break;
		// Append to allocation table (ONLY for super users in production)
		case "appendtablesuccess":
			$msgText  = "<b>{$lang['setup_08']}</b> {$lang['random_40']}";
			break;
		// Deleted allocation table
		case "deletetablesuccess":
			$msgText  = "<b>{$lang['setup_08']}</b> {$lang['random_66']}";
			break;
		// Erased randomization setup
		case "erased":
			$msgText  = "<b>{$lang['setup_08']}</b> {$lang['random_96']}";
			break;
		// Error: The uploaded allocation table matches the other exist one (the two cannot be the same)
		case "errorduplicatetable":
			$msgText  = "<b>{$lang['random_112']}</b><br>{$lang['random_111']}";
			$msgClass = "red";
			$msgIcon  = "exclamation.png";
			$msgAlign = "left";
			$timeVisible = 120;
			break;
		// Error (general)
		case "error":
			$msgText  = "<b>{$lang['global_64']}</b>";
			$msgClass = "red";
			$msgIcon  = "exclamation.png";
			break;
	}
	// Display message
	displayMsg($msgText, "actionMsg", $msgAlign, $msgClass, $msgIcon, $timeVisible, true);
}


// DASHBOARD view
if (isset($_GET['view']) && $_GET['view'] == 'dashboard')
{
	// Render the dashboard
	Randomization::renderDashboardGroups();
}

// SETUP view
else
{
	// Render the variable drop-downs
	Randomization::renderSetupSteps();

	?>
	<script type="text/javascript">
	// Max number of source fields to be used (strata + 1 possible grouping field)
	var maxSourceFields = <?php echo (Randomization::MAXSOURCEFIELDS-1) ?>;
	// During setup, keep track if any data already exists for the chosen randomization field (if so, warn that will be deleted when save model)
	var randFieldDataCount = 0;
	$(function(){
		// Set trigger for button to add more drop-downs
		$('#addMoreFields').click(function(){
			// Make sure we don't exceed max number of drop-downs
			if ($('#stratStep .randomVar').length >= maxSourceFields) {
				alert('<?php echo cleanHtml($lang['random_23']) ?> '+maxSourceFields);
				return;
			}
			var lastDD = $('#stratStep .randomVar:last');
			var lastDDhtml = lastDD.parent().html();
			lastDD.parent().after("<div class='randomVarParent'>"+lastDDhtml+"</div>");
			// Reset value of new drop-down (just in case) and change id/name
			var rand = Math.floor(Math.random()*1000000);
			$('#stratStep .randomVar:last').val('').attr('id', 'fld_'+rand).attr('name', 'fld_'+rand);
			$('#stratStep .randomVarEvt:last').attr('id', 'evt_'+rand).attr('name', 'evt_'+rand);
			// Re-run trigger for newly added select
			onchangeDropDowns();
		});
		// Make sure vars chosen aren't duplicated
		onchangeDropDowns();
		// Display options for multisite setup if multisite_chk is checked
		$('#multisite_chk').click(function(){
			$('#multisite_options').toggle('fade');
			if (!$(this).prop('checked')) {
				$('#multisite_options input').prop('checked',false);				
				disableDagFieldDropdown();
			}
		});
		// Disable criteria field step if Block scheme is chosen
		$('input[name="scheme"]').click(function(){
			disableStratStep('slow');
		});
		// Disable DAG field drop-down (if option not selected)
		$('input[name="multisite"]').click(function(){
			disableDagFieldDropdown();
		});	
		disableDagFieldDropdown();
		// If the randomization model has not yet been saved, then disable the steps after first step
		if (!$('#saveModelBtn').prop('disabled')) {
			$('#step2div').fadeTo(0,0.5);
			$('#step2div input, #step2div button').prop('disabled',true);
			$('#step3div').fadeTo(0,0.5);
			$('#step3div input, #step3div button').prop('disabled',true);
		}
		// When a user selects a field as the randomization field, use AJAX to get count of records containing data for that field
		$('#targetField').change(function(){
			var val = $(this).val();
			if (val.length < 1) return;
			$.post(app_path_webroot+'Randomization/check_randomization_field_data.php?pid='+pid,{field:val},function(data){
				if (data != '') {
					// Set global variable to be caught on clicking Save Model button
					randFieldDataCount = data;
				}
			});
		});
		// If in production, disable/gray out the development allocation table form (to signify it's no longer needed)
		if (status > 0) {
			$('#devAllocUploadTable').fadeTo(0,0.4);
		}
	});
	// Disable criteria field step if Block scheme is chosen
	function disableStratStep() {
		if ($('input[name="scheme"]').prop('checked')) {
			// Enable stratified fields step
			$('div#stratStep').show('fade');
		} else {
			// Disable stratified fields step
			$('div#stratStep').hide('fade');
		}
	}
	// Disable DAG field drop-down (if option not selected)
	function disableDagFieldDropdown() {
		if ($('#multisite_field').prop('checked')) {
			if (!$('#saveModelBtn').prop('disabled')) {
				$('#dagField, #dagFieldEvt').prop('disabled',false);
			}
		} else {
			$('#dagField, #dagFieldEvt').val('').prop('disabled',true);
		}
	}
	// Erase randomization setup
	function eraseSetup() {
		if (confirm('<?php echo cleanHtml($lang['random_94']) ?>\n\n<?php echo cleanHtml($lang['random_95']) ?>')) {
			$('form[name="random_step1"]').append("<input type='hidden' name='action' value='erase'>");
			return true;
		}
		return false;
	}
	// Make sure vars chosen aren't duplicated
	function onchangeDropDowns() {
		$('select.randomVar, select.targetField').change(function(){
			var newval = $(this).val();
			var id = $(this).attr('id');
			// Loop through all drop-downs
			$('select.randomVar, select.targetField').each(function(){
				if (newval != '' && id != $(this).attr('id') && newval == $(this).val()) {
					// Selected value already exists
					$('#'+id).val('');
					alert('<?php echo cleanHtml($lang['random_10']) ?>');
					return;
				}
			});	
		});
	}
	// Check if drop-down fields are selected before downloading template file
	function checkVarsSelected() {		
		var haveSourceFld = false;
		var haveTargetFld = false;
		$('select.randomVar').each(function(){
			if ($(this).val().length > 0) {
				haveSourceFld = true;
			}
		});
		if ($('input[name="scheme"]').prop('checked') && !haveSourceFld) {
			alert('<?php echo cleanHtml($lang['random_27']) ?>');
			return false;
		}
		$('select.targetField').each(function(){
			if ($(this).val().length > 0) {
				haveTargetFld = true;
			}
		});	
		if (!haveTargetFld) {
			alert('<?php echo cleanHtml($lang['random_87']) ?>');
			return false;
		}
		if ($('#multisite_chk').prop('checked')) {
			if (!$('#multisite_dag').prop('checked') && !$('#multisite_field').prop('checked')) {
				alert('<?php echo cleanHtml($lang['random_97']) ?>');
				return false;
			} else if ($('#multisite_field').prop('checked') && $('#dagField').val().length < 1) {
				alert('<?php echo cleanHtml($lang['random_98']) ?>');
				return false;
			}
		}
		// If randomization field that was chosen already has data, warn the user that it will be deleted upon setup
		if (randFieldDataCount > 0) {
			if (!confirm('<?php echo cleanHtml($lang['random_118']) ?> '+randFieldDataCount+' <?php echo cleanHtml($lang['random_119']) ?>')) {
				return false;
			}
		}		
		return true;
	}
	// Check file upload extension
	function checkFileUploadExt(this_status) {
		var fileFieldId = (this_status == 1) ? 'allocFileProd' : 'allocFileDev';
		var fileName = trim($('#'+fileFieldId).val());
		if (fileName.length < 1) { 
			alert('Please select a file first'); 
			return false; 
		} 
		var file_ext = getfileextension(fileName); 
		if (file_ext != 'csv' && file_ext != 'CSV') { 
			$('#filetype_mismatch_div').dialog({ bgiframe: true, modal: true, width: 530, buttons: { Close: function() { $(this).dialog('close'); } }}); 
			return false; 
		}
		return true;
	}
	// Delete the allocation file (for dev only)
	function delAllocFile(this_status) {
		var msg = '<?php echo cleanHtml($lang['random_64']) ?>\n\n<?php echo cleanHtml($lang['random_65']) ?>\n\n';
		if (this_status == status) {
			msg += '<?php echo cleanHtml($lang['random_67']) ?>\n\n';
		}
		msg += '<?php echo cleanHtml($lang['random_68']) ?>\n\n';
		if (confirm(msg)) {
			$.get(app_path_webroot+'Randomization/delete_allocation_file.php', { pid: pid, status: this_status }, function(data) {
				if (data != '1') {
					alert(woops);
				} else {
					window.location.href = app_path_webroot+page+'?pid='+pid+'&msg=deletetablesuccess';
				}
			});
		}
	}
	// Give pop-up warning that no DAGs exist yet
	function noDagWarning() {
		$('#multisite_dag').prop('checked',false);
		alert('<?php echo cleanHtml($lang['random_85']) ?>');
	}
	</script>

	<style type="text/css">
	.randomVarParent { padding: 3px 0; margin-left: 15px; }
	</style>

	<!-- Div for displaying popup dialog for file extension mismatch (i.e. if XLS or other) -->
	<div id="filetype_mismatch_div" title="<?php echo cleanHtml2($lang['random_12']) ?>" style="display:none;">
		<p>
			<?php echo $lang['data_import_tool_160'] ?>
			<a href="http://office.microsoft.com/en-us/excel/HP100997251033.aspx#BMexport" target="_blank" 
				style="text-decoration:underline;"><?php echo $lang['data_import_tool_116'] ?></a> 
			<?php echo $lang['data_import_tool_117'] ?>
		</p>
	</div>
	<?php
}

// Footer
include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';