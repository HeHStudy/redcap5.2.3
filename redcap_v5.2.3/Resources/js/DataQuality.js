
// Highlight rule row in table
function highlightRuleRow(rule_id) {
	$('#ruleorder_'+rule_id).parent().parent().effect('highlight',3000);
	$('#rulename_'+rule_id).parent().parent().effect('highlight',3000);
	$('#rulelogic_'+rule_id).parent().parent().effect('highlight',3000);
	$('#ruleexe_'+rule_id).parent().parent().effect('highlight',3000);
	$('#ruledel_'+rule_id).parent().parent().effect('highlight',3000);
	$('#rulerte_'+rule_id).parent().parent().effect('highlight',3000);
	$('.dagr_'+rule_id).parent().parent().effect('highlight',3000);
}

// Enable table for editing
function enableRuleTableEdit() {
	// Set specific padding for the "discrepancies" column and DAG columns
	$('.exebtn').parent().css({'padding':'0','width':'100%'});
	// Determine if we should set the table as editable
	if (!allowTableEdit) return;
	// Enable mouseover for rule real-time execution checkbox
	$('#table-rules .editrte').hover(function(){
		// If checkbox is disabled, then give pencil option to enable it
		var RTEcheckbox = $(this).children('input[type="checkbox"]:first');
		if (RTEcheckbox.length && RTEcheckbox.prop('disabled')) {
			// Activate
			$(this).css('cursor','pointer');
			$(this).addClass('edit_active');
			$(this).prop('title','Click to edit');
		}
	}, function() {
		$(this).css('cursor','');
		$(this).removeClass('edit_active');
		$(this).removeAttr('title');
	});	
	// Enable click event for rule real-time execution pencil
	$('#table-rules .editrte').click(function(){
		var RTEcheckbox = $(this).children('input[type="checkbox"]:first');
		if (RTEcheckbox.length && RTEcheckbox.prop('disabled')) {
			RTEcheckbox.prop('disabled', false);
		}
	});	
	// Enable rule name edit mouseover
	$('#table-rules .editname').hover(function(){
		// If already clicked or is pre-defined rule, then do not enable editing of cell	
		if ($(this).html().indexOf('<textarea ') > -1 || $(this).html().indexOf('pd-rule') > -1) { 
			$(this).unbind('click');
			return;
		}
		// Activate
		$(this).css('cursor','pointer');
		$(this).addClass('edit_active');
		$(this).prop('title','Click to edit');
	}, function() {
		$(this).css('cursor','');
		$(this).removeClass('edit_active');
		$(this).removeAttr('title');
	});	
	// Rule name onclick edit action
	$('#table-rules .editname').click(function(){
		// If already clicked		
		if ($(this).html().indexOf('<textarea ') > -1) { 
			$(this).unbind('click');
			return;
		}
		// Undo css
		$(this).css('cursor','');
		$(this).removeClass('edit_active');
		$(this).removeAttr('title');
		$(this).unbind('click');
		var thisRuleName = $(this).text();
		var thisRuleId = $(this).attr('rid');
		$(this).html( '<textarea id="input_rulename_id_'+thisRuleId+'" class="x-form-field notesbox" style="height:40px;margin:4px 0;width:95%;">'+thisRuleName+'</textarea>'
					+ '<br><button style="vertical-align:middle;" onclick="saveRuleName('+thisRuleId+');">Save</button>');
		// Enable widgets/buttons
		initWidgets();
	});	
	// Enable rule logic edit mouseover
	$('#table-rules .editlogic').hover(function(){
		// If already clicked		
		if ($(this).html().indexOf('<textarea ') > -1 || $(this).html().indexOf('pd-rule') > -1) { 
			$(this).unbind('click');
			return;
		}
		$(this).css('cursor','pointer');
		$(this).addClass('edit_active');
		$(this).prop('title','Click to edit');
	}, function() {
		$(this).css('cursor','');
		$(this).removeClass('edit_active');
		$(this).removeAttr('title');
	});	
	// Rule logic onclick edit action
	$('#table-rules .editlogic').click(function(){
		// If already clicked		
		if ($(this).html().indexOf('<textarea ') > -1) { 
			$(this).unbind('click');
			return;
		}
		// Undo css
		$(this).css('cursor','');
		$(this).removeClass('edit_active');
		$(this).removeAttr('title');
		$(this).unbind('click');
		var thisRuleLogic = $(this).text();
		var thisRuleId = $(this).attr('rid');
		$(this).html( '<textarea id="input_rulelogic_id_'+thisRuleId+'" class="x-form-field notesbox" style="height:40px;margin:4px 0;width:95%;" onblur=\'if(!checkLogicErrors(this.value,1)){validate_logic(this.value,"",0);}\'>'+thisRuleLogic+'</textarea>'
					+ '<br><button style="vertical-align:middle;" onclick=\'if(!checkLogicErrors($("#input_rulelogic_id_'+thisRuleId+'").val(),1)){validate_logic($("#input_rulelogic_id_'+thisRuleId+'").val(),"",2,'+thisRuleId+');}\'>Save</button>');
		// Enable widgets/buttons
		initWidgets();
	});	
	// Add dragHangle to each row of the table
	$("#table-rules tr").each(function() {
		this_rid = trim($(this.cells[0]).text());
		if (isNumeric(this_rid)) {
			$(this.cells[0]).addClass('dragHandle');
			$(this).addClass('dragRow');
		}
	});
	// Enable drag-n-drop on table for reordering
	$('#table-rules').tableDnD({
		onDrop: function(table, row) {
			// Loop through table
			var i = 1;
			var rids = "";
			var this_rid;
			var current_rid = trim($(row.cells[0]).text());
			$("#table-rules tr").each(function() {			
				// Restripe table
				$(this).removeClass('erow');
				if (i%2 == 0) $(this).addClass('erow');
				// Gather link_nums
				this_rid = trim($(this.cells[0]).text());
				if (isNumeric(this_rid)) {
					rids += this_rid + ",";
					// Reorder the rule #s
					$('#ruleorder_'+this_rid).html(i);	
					i++;				
				}
			});
			// Save form order
			$.post(app_path_webroot+'DataQuality/edit_rule_ajax.php?pid='+pid, { rule_id: 0, action: 'reorder', rule_ids: rids }, function(data){
				if (data != '1') {
					alert(woops);
					window.location.reload();
				} else {
					highlightRuleRow(current_rid);
				}
			});
		},
		dragHandle: "dragHandle"
	});
	// Create mouseover image for drag-n-drop and enable button fading on row hover
	$("#table-rules tr.dragRow").hover(function() {
		$(this.cells[0]).css('background','#ffffff url("'+app_path_images+'updown.gif") no-repeat center');
		$(this.cells[0]).css('cursor','move');
	}, function() {
		$(this.cells[0]).css('background','');
		$(this.cells[0]).css('cursor','');
	});
}
// Save the new rule name via ajax
function saveRuleName(thisRuleId) {
	var thisRuleName = trim($('#input_rulename_id_'+thisRuleId).val());
	$.post(app_path_webroot+'DataQuality/edit_rule_ajax.php?pid='+pid, { rule_name: thisRuleName, rule_id: thisRuleId }, function(data){
		var data2 = data;
		if (data.length<1) data2 = '&nbsp;';
		$('#rulename_'+thisRuleId).html(data2);
		$('#rulename_'+thisRuleId).addClass('edit_saved');
		setTimeout(function(){
			$('#rulename_'+thisRuleId).removeClass('edit_saved');
		},2000);
		enableRuleTableEdit();
	});
}
// Save the new rule logic via ajax
function saveRuleLogic(thisRuleId) {
	var thisRuleLogic = trim($('#input_rulelogic_id_'+thisRuleId).val());
	$.post(app_path_webroot+'DataQuality/edit_rule_ajax.php?pid='+pid, { rule_logic: thisRuleLogic, rule_id: thisRuleId }, function(data){
		var data2 = data;
		if (data.length<1) data2 = '&nbsp;';
		$('#rulelogic_'+thisRuleId).html(data2);
		$('#rulelogic_'+thisRuleId).addClass('edit_saved');
		setTimeout(function(){
			$('#rulelogic_'+thisRuleId).removeClass('edit_saved');
		},2000);
		enableRuleTableEdit();
	});
}

// Delete an existing rule
function deleteRule(rule_id) {
	if (confirm("Are you sure you wish to delete rule #"+$('#ruleorder_'+rule_id).text()+"?")) {
		$.post(app_path_webroot+'DataQuality/edit_rule_ajax.php?pid='+pid, { rule_id: rule_id, action: 'delete' }, function(data){
			if (data == '0') {
				alert(woops);
			} else {
				highlightRuleRow(rule_id);
				setTimeout(function(){
					$('#table-rules-parent').html(data);
					enableRuleTableEdit();
				},1000);
			}
			enableRuleTableEdit();
		});
	}
}

// Validate the fields in the user-defined logic as real fields
function validate_logic(thisRuleLogic,thisRuleName,saveIt,thisRuleId) {
	// First, make sure that the logic is not blank
	if (trim(thisRuleLogic).length < 1) return;
	// Make ajax request to check the logic via PHP
	$.post(app_path_webroot+'DataQuality/validate_logic_ajax.php?pid='+pid, { logic: thisRuleLogic }, function(data){
		if (data == '1') {
			// Save new rule's name and logic via ajax
			if (saveIt == 1) {
				// Create new rule
				addNewRuleAjax(thisRuleName,thisRuleLogic);
			} else if (saveIt == 2 && thisRuleId != '') {
				// Edit existing rule
				saveRuleLogic(thisRuleId);
			}
		} else if (data == '0') {
			alert(woops);
			return false;
		} else {
			alert(data);
			return false;
		}
	});
}

// Save new rule's name and logic via ajax (part 1)
function addNewRule() {
	var thisRuleName = trim($('#input_rulename_id_0').val());
	var thisRuleLogic = trim($('#input_rulelogic_id_0').val());
	if (thisRuleName.length < 1 || thisRuleLogic.length < 1) {
		alert('Please enter both a name and logic for the new rule');
		return;
	}
	// Do quick logic check
	if (checkLogicErrors(thisRuleLogic,1)) {
		return;
	}
	// Now validate the fields in the logic, which will also save the name/logic via ajax
	validate_logic(thisRuleLogic,thisRuleName,1,'');	
}

// Save new rule's name and logic via ajax (part 2)
function addNewRuleAjax(thisRuleName,thisRuleLogic) {
	// Get value of real-time execution checkbox
	var rte = ($('#rulerte_id_0').prop('checked')) ? '1' : '0';
	// Do ajax call
	$.post(app_path_webroot+'DataQuality/edit_rule_ajax.php?pid='+pid, { rule_name: thisRuleName, rule_logic: thisRuleLogic, rule_id: 0, real_time_execute: rte }, function(data){
		var json_data = jQuery.parseJSON(data);
		if (json_data.length < 1) {
			alert(woops);
			return;
		}
		// Set variables
		var new_rule_id = json_data.new_rule_id;
		var html = json_data.payload;
		// Add html to page		
		$('#table-rules-parent').html(html);
		enableRuleTableEdit();
		highlightRuleRow(new_rule_id);
		// Add new rule_id to delimited list var of rule_ids
		rule_ids = (rule_ids == '') ? ''+new_rule_id+'' : rule_ids+','+new_rule_id;
	});
}

// Run some things before firing the actual ajax requests
function preExecuteRulesAjax(rule_ids,show_exclusions) {
	if (rule_ids.length < 1) {
		alert('No rule is selected. Select a rule and try again.');
		return;
	}
	// Reset all divs, buttons, etc.
	$('#rule_num_progress').html('0');
	$('#rule_num_total').html( rule_ids.split(",").length );
	$('#execRuleProgress, #execRuleProgress').show();
	$('#execRuleComplete').hide();
	// Loop through rule_id's and set spinning icon for each
	var rule_array = rule_ids.split(',');
	var progressIcon = $('#progressIcon').html();
	var resetDagCounts = ($('.exegroup').length > 0);
	for (k=0; k<rule_array.length; k++) {
		$('#ruleexe_'+rule_array[k]).html(progressIcon).removeClass('red').removeClass('darkgreen');
		if (resetDagCounts) {
			$('.dagr_'+rule_array[k]).html('');
		}
	}
	// Execute rule(s)
	executeRulesAjax(rule_ids,show_exclusions,0);
}
// Reload a single results table
function reloadRuleAjax(rule_id,show_exclusions,replaceRuleTable) {
	$('#reload_dq_'+rule_id).show();
	executeRulesAjax(rule_id,show_exclusions,replaceRuleTable);
}
// Begin series of ajax requests to handle each rule
function executeRulesAjax(rule_ids,show_exclusions,replaceRuleTable) {
	$('#clearBtn').prop('disabled',false);
	// Increment the progress rule count
	var rule_num_progress = $('#rule_num_progress').html()*1 + 1;
	$('#rule_num_progress').html(rule_num_progress);
	// Ajax request
	$.post(app_path_webroot+'DataQuality/execute_ajax.php?pid='+pid, { rule_ids: rule_ids, show_exclusions: show_exclusions }, function(data){
		var json_data = jQuery.parseJSON(data);
		if (json_data.length < 1) {
			alert(woops);
			window.location.reload();
			return;
		}
		// Set variables
		var rule_id = json_data.rule_id;
		var next_rule_ids = json_data.next_rule_ids;
		var html = json_data.payload;
		var title = json_data.title;
		var discrep = json_data.discrepancies*1;
		var discrepf = json_data.discrepancies_formatted;
		var dag_discrep = json_data.dag_discrepancies;
		// Add html to page
		if (replaceRuleTable == 0) {
			// Append to last table
			$('#dq_results').append(html);	
			// Replace spinning icon with number of discrepancies
			if (discrep > 0) {
				var discrepclass = 'red';
				var textColor = 'font-weight:bold;color:red;';
			} else {
				var discrepclass = 'darkgreen';
				var textColor = 'color:green;';
			}
			var discrep_text = "<div style='float:left;font-size:15px;width:50px;text-align:center;"+textColor+"'>"+discrepf+"</div>"
							 + "<div style='float:right;'><a href=\"javascript:;\" onclick=\"viewResults('"+rule_id+"');\" style='font-size:10px;text-decoration:underline;'>view</a></div>"
							 + "<div style='clear:both:height:0;'></div>";
			$('#ruleexe_'+rule_id).html(discrep_text).addClass(discrepclass).css({'margin':'0','border':'0'});
			// If DAG columns exist in table, then add their values	to the table cells		
			if (dag_discrep.length > 0) {
				for (k=0; k<dag_discrep.length; k++) {
					var dag_count = dag_discrep[k][1];
					var group_id = dag_discrep[k][0];
					$('#ruleexe_'+rule_id+'-'+group_id).html(dag_count);
					var dagcolor = (dag_count*1 > 0) ? 'red' : 'green';
					$('#ruleexe_'+rule_id+'-'+group_id).css({'color':dagcolor});
				}
			}
			// Adjust cell height so it has no whitespace around it (looks nicer this way)
			var td_height = $('#ruleexe_'+rule_id).parent().parent().outerHeight(true)-6; // 6 comes from padding top/bottom of div
			if (td_height > $('#ruleexe_'+rule_id).height()) {
				$('#ruleexe_'+rule_id).height(td_height);
			}
			// Add "title" attribute to results table div
			$('#results_table_'+rule_id).attr('title',title);
		} else {
			// Replace existing table
			$('#results_table_'+replaceRuleTable).dialog('destroy');
			$('#results_table_'+replaceRuleTable).remove();
			$('#dq_results').append(html);	
			$('#results_table_'+replaceRuleTable).attr('title',title);
			viewResults(replaceRuleTable);
		}
		// Perform the next ajax request if more rules still need to be processed
		if (next_rule_ids.length > 0) {
			executeRulesAjax(next_rule_ids,show_exclusions,replaceRuleTable);
		} else {
			$('#execRuleComplete').show();
			$('#execRuleProgress').hide();
			// Check if all rules have been run, and if so, make Execute All button disabled
			if ($('.exebtn button').length < 1) {
				$('#execRuleBtn').prop('disabled',true);
			}
		}
	});
}
// Highlight a specific results table
function viewResults(rule_id) {
	$('#results_table_'+rule_id).dialog({ bgiframe: true, modal: true, width: 680, height: 600, 
		buttons: {'Close':function(){$(this).dialog("close");}}
	});	
}

// Change the current status
function changeStatus(rule_id,record,event_id,field_name) {
	// Do ajax call to save status
	$.post(app_path_webroot+'DataQuality/edit_comlog_ajax.php?pid='+pid, { field_name: field_name, status: $('#currentStatusEdit').val(), rule_id: rule_id, record: record, event_id: event_id }, function(data){
		if (data == '1') {								
			// Close this dialog and reload the one underneath it
			$('#comLog').dialog('destroy');
			showComLog(rule_id,record,event_id,field_name);
		} else {
			alert(woops);
		}
	});
}

// Loads Communication Log pop-up for a specific rule-record-event
function showComLog(rule_id,record,event_id,field_name) {
	$('#comLogLoading').show();
	// Show dialog with "loading..."
	$('#comLog').dialog({ bgiframe: true, modal: true, width: 800, height: 500, close: function(){ $('#comLogComments').html(''); },
		buttons: {
			'Close': function() {
				$(this).dialog("close");
			},
			'Add New Comment': function() {
				// Open "add new" pop-up
				$('#newComment').val('');
				$('#comLogAddNew').dialog({ bgiframe: true, modal: true, width: 400,
					buttons: {
						'Close': function() { $(this).dialog("close");},			
						'Add': function() {
							$('#newComment').val(trim($('#newComment').val()));
							var newComment = $('#newComment').val();
							if (newComment.length < 1) {
								alert('Please enter a comment first.');
								return;
							}
							// Do ajax call to save comment
							$.post(app_path_webroot+'DataQuality/edit_comlog_ajax.php?pid='+pid, { field_name: field_name, comment: newComment, rule_id: rule_id, record: record, event_id: event_id }, function(data){
								if (data == '1') {								
									// Close this dialog and reload the one underneath it
									$('#comLogAddNew').dialog('destroy');
									$('#comLog').dialog('destroy');
									showComLog(rule_id,record,event_id,field_name);
								} else {
									alert(woops);
								}
							});
						}
					}
				});	
			}
		}
	});	
	// Do ajax call to get content for dialog
	$.post(app_path_webroot+'DataQuality/communication_log.php?pid='+pid, { field_name: field_name, rule_id: rule_id, record: record, event_id: event_id }, function(data){
		$('#comLogLoading').hide();
		$('#comLogComments').html(data);
	});
}

// Enable/disable the DQ real-time execution
function enableDQRTE(ob, rule_id) {
	var enable = (ob.prop('checked') ? 1 : 0);	
	// Do ajax call to get content for dialog
	$.post(app_path_webroot+'DataQuality/edit_rule_ajax.php?pid='+pid, { action: 'enableRTE', rule_id: rule_id, enable: enable }, function(data){
		// Success!
		$('#rulerte_'+rule_id).addClass('edit_saved');
		setTimeout(function(){
			$('#rulerte_'+rule_id).removeClass('edit_saved');
			$('#rulerte_'+rule_id).children('input[type="checkbox"]:first').prop('disabled',true);
		},2000);
	});
}
// Data Quality: Display the explainRTE dialog
function explainDQRTE() {
	simpleDialog(null,null,'explain_rte',550);
}