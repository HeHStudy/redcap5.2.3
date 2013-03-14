<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

include 'header.php';

$html = '';
$html .= RCView::h3(array('style' => 'margin-top: 0;'), RCL::pubCtrlTitle());
$html .= RCView::p(array('style'=>'margin-bottom:30px;'), RCL::pubCtrlHelp());
$html .= RCView::div(array('id' => 'userMsg', 'style' => 'display: none;'), '');

$db = new RedCapDB();
$projectTodos = count($db->getPubProjGroupTodos());
$todoProjStr = " ($projectTodos)";
// build the navigation tabs
$tabs = array(
		'tabPubSetup' => RCL::setup(),
		'tabPubProjTodo' => RCL::todoList() . $todoProjStr,
		'tabPubManageProj' => RCL::manageProjects(),
		'tabPubPIEmails' => RCL::piEmails(),
		'tabPubMatches' => RCL::matches());
$tabsSelector = array();
$tabList = '';
foreach ($tabs as $tabId => $tabName) {
	$tabsSelector[] = "#$tabId";
	$liAttrs = array('style' => 'list-style: none;');
	// tab selection will be updated by JS
	if (count($tabsSelector) === 1) $liAttrs['class'] = 'active';
	$aAttrs = array('id' => $tabId, 'href' => '#',
			'style' => 'color: #393733; font-size: 13px;');
	$tabList .= RCView::li($liAttrs, RCView::a($aAttrs, $tabName));
}
$tabsSelector = implode(',', $tabsSelector);
$html .= RCView::div(array('id' => 'sub-nav', 'style' => 'margin: 0;'), $tabList);
$html .= RCView::div(array('style' => 'clear: both;'));

// tab content populated via AJAX
$html .= RCView::div(array('id' => 'pubContent', 'style' => 'margin-top: 20px;'), '');

?>

<script type="text/javascript">
	var ajaxScript = app_path_webroot + "ControlCenter/pub_matching_ajax.php";
	var tabsSelector = "<?php echo $tabsSelector; ?>";
	var piSearchHintLong = <?php echo RCView::strToJS(RCL::searchPINameCopyLong()); ?>;
	var piSearchHintShort = <?php echo RCView::strToJS(RCL::searchPINameCopyShort()); ?>;
	var authorTags = new Array(); // will be populated on demand
	var tag2Concat = {}; // concatenated author tag data for AJAX
	var noFireAutoCopy = {}; // don't open copy dialog when these are Ready
	var currTab = null; // the currently selected tab as a jQuery object
	function bookkeepPIElement(elem) {
		// make sure we are working with one of the elements that we are bookkeeping
		elem = elem.filter("[id^=pi_data_email_],[id^=pi_data_firstname_],[id^=pi_data_lastname_]");
		if (elem.length != 1) { return false; }
		// check if the user invalidated the Ready status of the row
		var id = elem.attr("id");
		var recordId = id.substring(id.lastIndexOf("_")+1);
		if ($("#pi_data_exclude_no_" + recordId).is(":checked") && !isReadyPI(recordId)) {
			$("#pi_data_exclude_todo_" + recordId).trigger("click");
		}
		// highlight/un-highlight the field accordingly
		if ($.trim(elem.val()).length == 0 ||
			(id == "pi_data_email_" + recordId && !isEmail($.trim(elem.val()))))
		{
			elem.addClass("redcapMissing");
		}
		else { elem.removeClass("redcapMissing"); }
	}
	function copyToRow(concatData, recordId) {
		var pieces = concatData.split("%");
		var rowIds = [
			"pi_data_lastname_" + recordId,
			"pi_data_firstname_" + recordId,
			"pi_data_mi_" + recordId,
			"pi_data_alias_" + recordId,
			"pi_data_email_" + recordId
		];
		for (var i = 0; i < rowIds.length; i++) {
			$("#" + rowIds[i]).val(pieces[i]);
			bookkeepPIElement($("#" + rowIds[i]));
			$("#" + rowIds[i]).effect("highlight", {}, 3000);
		}
	}
	function populateAuthorTags() {
		$("#pub_author_search,[id^=piSearch_]").attr("disabled", "disabled");
		var includeUsers = currTab.attr("id") == "tabPubManageProj" ? 0 : 1;
		$.ajax({url: ajaxScript,
			data: {action: "getAuthorTagsJSON", includeUsers: includeUsers},
			dataType: "json",
			success: function(data) {
				authorTags = new Array();
				tag2Concat = {};
				for (var i = 0; i < data.length; i++) {
					var concat = data[i];
					try {
						var pieces = concat.split("%");
						var tag = pieces[0]; // last name
						if (pieces[1].length > 0) { tag += ", " + pieces[1]; } // first name
						if (pieces[2].length > 0) { tag += " " + pieces[2] + "."; } // M.I.
						if (pieces[3].length > 0) { tag += " (" + pieces[3] + ")"; } // Alias
						if (pieces[4].length > 0) { tag += " [" + pieces[4] + "]"; } // Email
						tag = $.trim(tag);
						tag2Concat[tag] = concat;
						authorTags.push(tag);
					} catch(e) { }
				}
				// NOTE: making use of jQuery's autocomplete
				$("#pub_author_search").ui_autocomplete({
					source: authorTags,
					delay: 0,
					select: function(event, ui) {
						// set value manually to avoid timing issues
						$("#pub_author_search").val(ui.item.value);
						// make selection the same as hitting Enter on the search box
						// (if they used Enter to select then our keydown delegate will pick it up)
						if (event.which != 13) {
							var enter = $.Event("keydown");
							enter.which = 13;
							$("#pub_author_search").trigger(enter);
						}
					}
				});
				$("[id^=piSearch_]").ui_autocomplete({
					source: authorTags,
					delay: 0,
					select: function(event, ui) {
						var recordId = $(this).attr("id").substring("piSearch_".length);
						if (tag2Concat.hasOwnProperty(ui.item.value)) {
							var concat = tag2Concat[ui.item.value];
							copyToRow(concat, recordId);
						}
					}
				});
				$("#pub_author_search,[id^=piSearch_]").removeAttr("disabled");
			}
		});
	}
	function prepUserMsg(html) { $("#userMsg").html(html); }
	function launchUserMsg() {
		// if we have a msg, display it and then clear it
		if ($.trim($("#userMsg").html()).length > 0) {
			$("#userMsg").slideDown("fast").delay(3000).fadeOut("slow", function() {
				$("#userMsg").html("");
			});
		}
	}
	function updateTodoCount() {
		RedCapUtil.openLoader($("#pubDummyContainer").parent());
		$.get(ajaxScript, {action: "getProjTodoCount"}, function(data) {
			var tabText = $("#tabPubProjTodo").html();
			$("#tabPubProjTodo").html(tabText.substring(0, tabText.lastIndexOf("(")) + "(" + data + ")");
			RedCapUtil.closeLoader($("#pubDummyContainer").parent());
		});
	}
	function isReadyPI(recordId) {
		if ($.trim($("#pi_data_email_" + recordId).val()).length == 0) { return false; }
		else if (!isEmail($.trim($("#pi_data_email_" + recordId).val()))) { return false; }
		else if ($.trim($("#pi_data_firstname_" + recordId).val()).length == 0) { return false; }
		else if ($.trim($("#pi_data_lastname_" + recordId).val()).length == 0) { return false; }
		else return true;
	}
	function buildPIName(recordId) {
		var data = {
			first: $.trim($("#pi_data_firstname_" + recordId).val()),
			last: $.trim($("#pi_data_lastname_" + recordId).val()),
			mi: $.trim($("#pi_data_mi_" + recordId).val()),
			alias: $.trim($("#pi_data_alias_" + recordId).val()),
			email: $.trim($("#pi_data_email_" + recordId).val())
		};
		if (data.first.length == 0) { data.first = "[no data]"; }
		if (data.last.length == 0) { data.last = "[no data]"; }
		var name = data.last + ", " + data.first;
		if (data.mi.length > 0) name += " " + data.mi;
		if (data.alias.length > 0) name += " (" + data.alias + ")";
		if (data.email.length > 0) name += " [" + data.email + "]";
		return name;
	}
	$(function() {
		// handle tab clicks
		$(document).delegate(tabsSelector, "click", function() {
			RedCapUtil.openLoader($("#pubDummyContainer").parent());
			$(tabsSelector).parent().removeClass("active");
			$(this).parent().addClass("active");
			currTab = $(this);
			var getData = {};
			var action = "";
			switch ($(this).attr("id")) {
				case "tabPubSetup": action = "getSetupDisplay"; break;
				case "tabPubProjTodo":
					action = "getProjTodoDisplay";
					updateTodoCount();
					// maintain the current TODO selection
					if ($("#pubTodoProjIds").length > 0) {
						getData['pub_sel_project_ids'] = $("#pubTodoProjIds").val();
						getData['decode_sel_project_ids'] = 1;
					}
					break;
				case "tabPubManageProj":
					action = "getProjDisplay";
					if ($("#selPubPID").length > 0) {
						getData['pub_project_id'] = $("#selPubPID").val();
						getData['decode_project_id'] = 1;
					}
					if ($("#pub_author_search").length > 0) {
						getData['pub_author_search'] = $("#pub_author_search").val();
					}
					break;
				case "tabPubPIEmails": action = "getPIEmailsDisplay"; break;
				case "tabPubMatches": action = "getMatchDisplay"; break;
			}
			getData['action'] = action;
			$.get(ajaxScript, getData, function(data) {
				$("#pubContent").html(data);
				// check for any inconsistent TODO rows
				if (action === "getProjTodoDisplay" || action === "getProjDisplay") {
					noFireAutoCopy = {}; // needs to be reset each go around
					$("[id^=pi_data_exclude_no_]:checked").each(function() {
						var id = $(this).attr("id");
						var recordId = id.substring(id.lastIndexOf("_")+1);
						if (!isReadyPI(recordId)) { $("#pi_data_exclude_todo_" + recordId).trigger("click"); }
					});
					// force a check of any TODO rows
					$("[id^=pi_data_exclude_todo_]:checked").each(function() {
						var recordId = $(this).attr("id").substring("pi_data_exclude_todo_".length);
						$("[id$=_" + recordId + "]").filter("[id^=pi_data_]").each(function() {
							bookkeepPIElement($(this));
						});
					});
					populateAuthorTags();
				}
				$(window).scrollTop($("#sub-nav").position().top);
				RedCapUtil.closeLoader($("#pubDummyContainer").parent());
				launchUserMsg();
				initWidgets(); // REDCap/jQuery widgets
			});
			return false;
		});
		// checks that the pub URL settings is formatted correctly and updates the DOM
		var validatePubURL = function() {
			var valid = true;
			var url = $("#pubURL").val();
			// first perform the standard URL check
			if (!isUrl(url)) valid = false;
			// since we are looking for the base web address, don't allow versioned dirs
			if (/\/redcap_v\d+\.\d+\.\d+/.test(url)) valid = false;
			// we also wouldn't expect the address to end with a common REDCap file extension
			if (/\.(php|html)$/.test(url)) valid = false;
			if (valid) $("#pubURL").removeClass("redcapMissing");
			else $("#pubURL").addClass("redcapMissing");
			return valid;
		};
		// validate the publication URL
		$(document).delegate("#pubURL", "keyup", function() { validatePubURL(); });
		// save global pub settings
		$(document).delegate("#btnSavePubSettings", "click", function() {
			if (!validatePubURL()) {
				alert(<?php echo RCView::strToJS(RCL::redcapURLError()); ?>);
				return false;
			}
			RedCapUtil.openLoader($("#pubDummyContainer").parent());
			$.post(ajaxScript, {
					action: "savePubSettings",
					pub_insts: $("#pubInstNames").val(),
					pub_enabled: $("#pubEnabled").val(),
					pub_url: $("#pubURL").val(),
					pub_emails_enabled: $("#pubEmailsEnabled").val(),
					pub_email_days: $("#pubEmailDays").val(),
					pub_email_limit: $("#pubEmailLimit").val(),
          pub_email_text: $("#pubEmailText").val(),
					pub_email_subject: $("#pubEmailSubject").val()
				},
				function(data) {
					prepUserMsg(data);
					RedCapUtil.closeLoader($("#pubDummyContainer").parent());
					$("#tabPubSetup").trigger("click");
			});
		});
		// REDCap SOP is to disable "enter" key on textboxes
		$(document).delegate("[id^=pi_data_]:text", "keydown", function(e) {
			return e.which != 13;
		});
		// deal with changes of TODO status
		$(document).delegate("[id^=pi_data_exclude_]", "change", function() {
			if (!$(this).is(":checked")) return true; // just in case...
			if ($(this).val().length == 0) { $(this).parent().addClass("redcapMissing"); }
			else { $(this).parent().removeClass("redcapMissing"); }
			// hide/display a row's data depending on the radio selection
			if ($(this).val() === "1") { // exclude
				var id = $(this).attr("id");
				var recordId = id.substring(id.lastIndexOf("_")+1);
				$("#projBoxLeft_" + recordId).children(":not(#projBoxExclude_" + recordId + ")").slideUp(500);
				$("#projBoxRight_" + recordId + ",#piSearchBox_" + recordId + ",#piCopyBox_" + recordId).slideUp(500);
				if ($("#piSearchSuggest_" + recordId).length > 0) $("#piSearchSuggest_" + recordId).slideUp(500);
				$("#btnPICopy_" + recordId).hide();
			}
			else { // row data is either ready or TODO
				var id = $(this).attr("id");
				var recordId = id.substring(id.lastIndexOf("_")+1);
				$("#projBoxLeft_" + recordId).children().show();
				$("#projBoxRight_" + recordId + ",#piSearchBox_" + recordId + ",#piCopyBox_" + recordId).stop().show();
				if ($("#piSearchSuggest_" + recordId).length > 0) $("#piSearchSuggest_" + recordId).stop().show();
				$("#btnPICopy_" + recordId).hide();
				if ($(this).val() === "0") { // ready
					$("#btnPICopy_" + recordId).show();
					$("#piSearchBox_" + recordId).slideUp(500);
					if ($("#piSearchSuggest_" + recordId).length > 0) $("#piSearchSuggest_" + recordId).slideUp(500);
					if (!isReadyPI(recordId)) {
						$("#pi_data_exclude_todo_" + recordId).trigger("click");
						alert(<?php echo RCView::strToJS(RCL::pubErrNotReady()); ?>);
					}
					else {
						// fire off the copy dialog if there is anything to copy to
						if ($("[id^=pi_data_exclude_todo_]:checked").length != 0 &&
							!(noFireAutoCopy.hasOwnProperty(recordId) && noFireAutoCopy[recordId]) &&
							$("#pubTodoProjIds").val() != $("#pubTodoNoDataIds").val())
						{
							$("#btnPICopy_" + recordId).trigger("click");
						}
						// hide the copy functionality when dealing with the missing
						// first/last name case
						if ($("#pubTodoProjIds").val() == $("#pubTodoNoDataIds").val()) {
							$("#btnPICopy_" + recordId).hide();
						}
					}
				}
			}
		});
		$(document).delegate("[id^=pi_data_email_],[id^=pi_data_firstname_],[id^=pi_data_lastname_]", "keyup", function() {
			bookkeepPIElement($(this));
		});
		// clear commas from the alias since our convention is "Harris PA" and
		// *NOT* "Harris, PA"
		$(document).delegate("[id^=pi_data_alias_]", "keyup", function() {
			$(this).val($(this).val().replace(/,/, ""));
		});
		// copy PI data from one row to all others with TODO status
		// launch the dialog for copying "Ready" PI data
		$(document).delegate("[id^=btnPICopy_]", "click", function() {
			// if there are no rows with TODO status, then there is nothing to copy to
			if ($("[id^=pi_data_exclude_todo_]:checked").length == 0) {
				alert(<?php echo RCView::strToJS(RCL::pubErrNoTodos()); ?>);
				return false;
			}
			var recordId = $(this).attr("id").substring("btnPICopy_".length);
			$("#pubCopyDialogName").text(buildPIName(recordId));
			// start by hiding/clearing all the rows in the dialog
			$("[id^=pubCopyRow_]").hide();
			$("[id^=pubCopyChk_]").removeAttr("checked");
			// fill in the dialog row data
			$("[id^=pi_data_exclude_todo_]:checked").each(function() {
				var todoRecordId = $(this).attr("id").substring("pi_data_exclude_todo_".length);
				$("#pubCopyRow_" + todoRecordId).show();
				$("#pubCopyPIName_" + todoRecordId).text(buildPIName(todoRecordId));
			});
			$("#pubCopyDialog").dialog({
				modal: true,
				width: 600,
				open: function() {
					// don't focus on this link
					$("#pubCopyDialogToggle").blur();
				},
				buttons: {
					Cancel: function() {
						$(this).dialog('close');
						$(this).dialog('destroy');
					},
					Copy: function() {
						// must close the dialog first or else the DOM won't register the
						// triggered clicks (jquery picks them up, but not the DOM)
						$(this).dialog('close');
						$(this).dialog('destroy');
						$("[id^=pubCopyChk_]:checked").each(function() {
							var chkRecordId = $(this).attr("id").substring("pubCopyChk_".length);
							var copyField = function(prefix, fromId, toId) {
								$("#" + prefix + toId).val($.trim($("#" + prefix + fromId).val()));
								bookkeepPIElement($("#" + prefix + toId));
							};
							copyField("pi_data_email_", recordId, chkRecordId);
							copyField("pi_data_firstname_", recordId, chkRecordId);
							copyField("pi_data_lastname_", recordId, chkRecordId);
							copyField("pi_data_mi_", recordId, chkRecordId);
							copyField("pi_data_alias_", recordId, chkRecordId);
							noFireAutoCopy[chkRecordId] = true;
							$("#pi_data_exclude_no_" + chkRecordId).trigger("click");
						});
						// give some feedback to the user
						if ($("[id^=pubCopyChk_]:checked").length > 0) {
							alert(<?php echo RCView::strToJS(RCL::pubCopyComplete()); ?>);
						}
					}
				}
			});
			return false;
		});
		// copy PI data from a suggestion
		$(document).delegate("[id^=piSuggestCopy_]", "click", function() {
			var id = $(this).attr("id");
			var recordId = id.substring(id.lastIndexOf("_")+1);
			copyToRow($(this).attr("data-copyme"), recordId);
			return false;
		});
		// toggle all the checkboxes in the copy dialog
		$(document).delegate("#pubCopyDialogToggle", "click", function() {
			$("[id^=pubCopyChk_]").each(function() {
					$(this).prop("checked", !$(this).is(':checked'));
			});
			return false;
		});
		// update the TODO list with a new selection
		$(document).delegate("#pubTodoProjIds", "change", function() {
			$("#tabPubProjTodo").trigger("click");
		});
		// save PI data
		$(document).delegate("#btnSavePITop,#btnSavePIBottom", "click", function() {
			var postData = {action: "savePIData"};
			$("[id^=pi_data_]").each(function() {
				var key = $(this).attr("id");
				var val = $(this).val();
				if (key.indexOf("pi_data_exclude_") == 0) {
					if ($(this).is(':checked')) {
						var recordId = key.substring(key.lastIndexOf("_")+1);
						key = "pi_data_exclude_" + recordId;
					}
				}
				postData[key] = val;
			});
			RedCapUtil.openLoader($("#pubDummyContainer").parent());
			$.post(ajaxScript, postData,
				function(data) {
					prepUserMsg(data);
					RedCapUtil.closeLoader($("#pubDummyContainer").parent());
					// reload the currently-selected tab (could be either TODO list or Manage Projects)
					$(tabsSelector).parent(".active").first().children("a").first().trigger("click");
			});
		});
		// cancel changes to PI data
		$(document).delegate("#btnCancelPITop,#btnCancelPIBottom", "click", function() {
			if (!confirm(<?php echo RCView::strToJS(RCL::pubCancelChanges()); ?>)) { return false; }
			// reload the tab using the current selection
			currTab.trigger("click");
		});
		// handle project selection on Manage Project tab
		$(document).delegate("#selPubPID", "change", function() {
			var pid = $("#selPubPID").val();
			if (pid.length == 0) return false;
			$("#pub_author_search").val(""); // to not confuse this event with PI select
			$("#tabPubManageProj").trigger("click");
		});
		// handle PI search string selection via Enter key on Manage Project tab
		$(document).delegate("#pub_author_search", "keydown", function(e) {
			if (e.which == 13 && $.trim($("#pub_author_search").val()).length > 0) {
				$("#selPubPID").val(""); // to not confuse this event with proj select
				$("#tabPubManageProj").trigger("click");
			}
		});
		// clear hint text from the per-row PI search
		$(document).delegate("[id^=piSearch_]", "focus", function() {
			if ($(this).val() == piSearchHintLong || $(this).val() == piSearchHintShort) {
				$(this).val("");
				$(this).removeClass("redcapGhost");
			}
		});
		// open PI view in a new window/tab
		$(document).delegate(".pi_view_btn", "click", function() {
			var secret = $(this).attr("data-pub-secret");
			window.open(app_path_webroot + "PubMatch/index.php?secret=" + encodeURIComponent(secret));
		});
		// initialze the content
		$("#tabPubSetup").trigger("click");
		$(document).delegate("#cronText", "click", function() { $(this).select(); });
	});
</script>

<?php

echo RCView::div(array('id' => 'pubDummyContainer'), $html);

include 'footer.php';
