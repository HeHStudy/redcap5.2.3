<?php

/*****************************************************************************************
**  REDCap is only available through ACADMEMIC USER LICENSE with Vanderbilt University
******************************************************************************************/

$html = '';

if (!defined("PROJECT_ID")) {
	include 'header.php';
	$html .= RCView::h3(array('style' => 'margin-top: 0;'), $lang['control_center_245']);
	$html .= RCView::p(array(), $lang['control_center_280']);
	$html .= RCView::p(array('style' => 'margin-bottom: 20px;'), $lang['control_center_281']);
	if (!$api_enabled) $html .= RCView::warnBox(RCView::disabledAPINote());
}

// Validate $_GET['api_pid']
if (isset($_GET['api_pid']) && !is_numeric($_GET['api_pid'])) exit('ERROR!');

/* NOTE: all JS is included at the bottom */

// set up the dialog that will allow quick access to API user rights
$html .= RCView::div(array('id' => 'rightsDialogId', 'style' => 'display: none;'), '');

// API token tables populated by JS
$dummy = RCView::select(array('name' => 'api_username', 'id' => 'apiUserSelId'), array('' => ''));
$html .= RCView::div(array('id' => 'apiByUserId', 'style' => 'display: none;'), $dummy);
$html .= RCView::br(); RCView::br();
$dummy = RCView::select(array('name' => 'api_pid', 'id' => 'apiProjSelId'), array('' => ''));
$html .= RCView::div(array('id' => 'apiByProjId', 'style' => 'display: none;'), $dummy);

echo RCView::div(array('id' => 'apiDummyContainer'), $html);

?>

<script type="text/javascript">
$(function() 
{
	function apiRightsCheck(url, urlAfterCheck) {
		RedCapUtil.openLoader($("#apiDummyContainer").parent());
		$.ajax({url: url,
			success: function(data) {
				$("#rightsDialogId").html(data);
				$("#rightsDialogId").attr('title', "<?php echo cleanHtml2($lang['control_center_283']) ?> \"" + $("#rightsUsername").val() + "\"");
				RedCapUtil.closeLoader($("#apiDummyContainer").parent());
				$("#rightsDialogId").dialog({ bgiframe: true, modal: true, width: 500, close: function() {$(this).dialog('destroy');}, buttons: { 
					Cancel: function() { $(this).dialog('close'); }, 
					'<?php echo cleanHtml($lang['control_center_273']) ?>': function() {
						RedCapUtil.openLoader($("#apiDummyContainer").parent());
						$(this).dialog('close');
						$.get(urlAfterCheck,
							{ 	api_export: $("#api_export").is(":checked") ? 1 : 0,
								api_import: $("#api_import").is(":checked") ? 1 : 0,
								api_send_email: $("#api_send_email").is(":checked") ? 1 : 0	},
							function (data) {
								redrawAPITables(data);
								RedCapUtil.closeLoader($("#apiDummyContainer").parent());
							}
						);
					}}
				});					
			}
		});			
	}
	$(document).delegate("#apiUserSelId", "change", function() {
		RedCapUtil.openLoader($("#apiDummyContainer").parent());
		var selValue = $("#apiUserSelId").val();
		$.get(app_path_webroot + 'ControlCenter/user_api_ajax.php?action=tokensByUser&username=' + selValue,
			function(data) {
				$("#apiByUserId").html(data);
				$("#apiByUserId").show();
				$("#apiUserSelId").val(selValue);
				// update Used on later since this takes some time
				$.ajax({
					url: app_path_webroot + 'ControlCenter/user_api_ajax.php',
					data: { action: 'getAPIDateForUserJS', username: $("#apiUserSelId").val() },
					success: function(data) { eval(data); },
					global: false // to bypass the loading overlay
				});
				RedCapUtil.closeLoader($("#apiDummyContainer").parent());
			}
		);
	});
	$(document).delegate("#apiProjSelId", "change", function() {
		RedCapUtil.openLoader($("#apiDummyContainer").parent());
		<?php if (defined('PROJECT_ID')) { ?>
		// If in a project, always for it to select the current project
		var selValue = '<?php echo PROJECT_ID ?>';
		// Set if viewing page via Control Center view
		var controlCenterView = 0;
		<?php } else { ?>
		// Set project_id as the one selected in the drop-down
		var selValue = $("#apiProjSelId").val();
		// Set if viewing page via Control Center view
		var controlCenterView = 1;
		<?php } ?>
		$.get(app_path_webroot + 'ControlCenter/user_api_ajax.php?action=tokensByProj&project_id='+selValue+'&controlCenterView='+controlCenterView,
			function(data) {
				$("#apiByProjId").html(data);
				$("#apiByProjId").show();
				$("#apiProjSelId").val(selValue);
				// update Used on later since this takes some time
				$.ajax({
					url: app_path_webroot + 'ControlCenter/user_api_ajax.php',
					data: { action: 'getAPIDateForProjJS', project_id: $("#apiProjSelId").val() },
					success: function(data) { eval(data); },
					global: false // to bypass the loading overlay
				});
				RedCapUtil.closeLoader($("#apiDummyContainer").parent());
			}
		);
	});
	$(document).delegate("a[id^=apiDelId]", "click", function() {
		if (confirm("<?php echo cleanHtml2($lang['control_center_248']) ?>")) {
			RedCapUtil.openLoader($("#apiDummyContainer").parent());
			$.get($(this).attr("href"),
				function (data) {
					redrawAPITables(data);
					RedCapUtil.closeLoader($("#apiDummyContainer").parent());
				}
			);
		}
		return false;
	});
	$(document).delegate("a[id^=apiRegenId]", "click", function() {
		if (confirm("<?php echo cleanHtml2($lang['control_center_250']) ?>")) {
			apiRightsCheck($(this).attr("href").replace(/regenToken/, "getAPIRights"), $(this).attr("href"));
		}
		return false;
	});
	$(document).delegate("a[id^=apiCreateId]", "click", function() {
		apiRightsCheck($(this).attr("href").replace(/createToken/, "getAPIRights"), $(this).attr("href"));
		return false;
	});
	$(document).delegate("a[id^=apiViewId]", "click", function() {
		var url = $(this).attr("href");
		var title1 = "<?php echo cleanHtml2($lang['control_center_322']) ?>";
		var title2 = "<?php echo cleanHtml2($lang['control_center_333']) ?>";
		initDialog("dialogAJAXId");
		$("#dialogAJAXId")
			.html("<?php echo cleanHtml2(RCView::b($lang['control_center_323']).' '.$lang['control_center_332'].RCView::br().RCView::br().RCView::b($lang['control_center_324'])) ?>")
			.addClass("simpleDialog")
			.dialog({ title: title1, bgiframe: true, modal: true, width: 500, close: function() {$(this).dialog('destroy');}, buttons: {
				'Cancel': function(){ $(this).dialog('destroy'); },
				'View Token': function(){
					$.get(url,{  },function(data){
						$("#dialogAJAXId").remove();
						simpleDialog(data,title2,'api_token_dialog');
						$('#api_token_dialog').effect('highlight',{},3000);
					});
				}
			}});
		return false;
	});
	function redrawAPITables(dialogMsg) {
		if (dialogMsg.length > 0) {
			// dialog always comes in an element with id "dialogAJAXId"
			$("#dialogAJAXId").remove(); // remove the existing dialog (if any)
			$("body").append(dialogMsg); // drop in the new dialog
			$("#dialogAJAXId").hide(); // hide it (jquery dialog() will display it)
			var buttons = { Close: function() { $(this).dialog('close'); }}
			<?php if (!empty($_GET['goto_proj'])) { ?>
				buttons["Return to project API page"] = function() {
					window.location = app_path_webroot + 'API/project_api.php?pid=' + <?php echo $_GET['api_pid']; ?>;
				};
			<?php } ?>
			$("#dialogAJAXId").dialog({ bgiframe: true, modal: true, width: 400, close: function() {$(this).dialog('destroy');}, buttons: buttons});
		}
		<?php if (!defined('PROJECT_ID')) { ?>
		// Only display "tokens by user" table in Control Center view
		$("#apiUserSelId").trigger("change");
		<?php } ?>
		// Display "tokens by project" table
		$("#apiProjSelId").trigger("change");
	}
	<?php if ($_GET['action'] == 'deleteToken') { ?>
		RedCapUtil.openLoader($("#apiDummyContainer").parent());
		$.get(app_path_webroot + 'ControlCenter/user_api_ajax.php',
		{action: '<?php echo $_GET['action']; ?>',
			api_username: '<?php echo $_GET['api_username']; ?>',
			api_pid: '<?php echo $_GET['api_pid']; ?>'},
		function(data) {
			redrawAPITables(data);
			RedCapUtil.closeLoader($("#apiDummyContainer").parent());
		}
	);
	<?php } ?>
	<?php if (in_array($_GET['action'], array('createToken', 'regenToken',))) { ?>
	var apiInitURL = app_path_webroot + 'ControlCenter/user_api_ajax.php' + '?action=' +
		'<?php echo $_GET['action']; ?>' + '&api_username=' + '<?php echo $_GET['api_username']; ?>' +
		'&api_pid=' + '<?php echo $_GET['api_pid']; ?>';
	apiRightsCheck(apiInitURL.replace(/createToken|regenToken/, "getAPIRights"), apiInitURL);
	<?php } ?>
	// initialize the API token tables
	redrawAPITables("");
});
</script>

<?php

if (!defined("PROJECT_ID")) {
	include 'footer.php';
}