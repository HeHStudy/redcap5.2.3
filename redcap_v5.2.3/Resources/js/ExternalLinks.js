
$(function(){
	// Enable the table for editing on pageload
	enableResourceTableEdit();
	// Display dialog for "append record info" explanation
	$('#append_rec_info_trigger').click(function(){
		$('#append_rec_info').dialog({ bgiframe: true, modal: true, width: 750, open: function(){fitDialog(this)}, buttons: { Close: function() { $(this).dialog('close'); } } });
	});
	// Display dialog for "append pid" explanation
	$('#append_pid_trigger').click(function(){
		$('#append_pid_info').dialog({ bgiframe: true, modal: true, width: 600, open: function(){fitDialog(this)}, buttons: { Close: function() { $(this).dialog('close'); } } });
	});
	// Display dialog for "adv link info" explanation
	$('#adv_link_info_trigger').click(function(){
		$('#adv_link_info').dialog({ bgiframe: true, modal: true, width: 750, open: function(){fitDialog(this)}, buttons: { Close: function() { $(this).dialog('close'); } } });
	});
	// Enable row drag-n-drop
	enableTableDnD();
});

// Enable row drag-n-drop
function enableTableDnD() {
	// Add dragHangle to each row of the table
	$("#table-resources tr").each(function() {
		this_rid = trim($(this.cells[0]).text());
		if (isNumeric(this_rid)) {
			$(this.cells[0]).addClass('dragHandle');
			$(this).addClass('dragRow');
		}
	});
	// Enable drag-n-drop on table for reordering
	$('#table-resources').tableDnD({
		onDrop: function(table, row) {
			// Loop through table
			var i = 1;
			var rids = "";
			var this_rid;
			var current_rid = trim($(row.cells[0]).text());
			$("#table-resources tr").each(function() {			
				// Restripe table
				$(this).removeClass('erow');
				if (i%2 == 0) $(this).addClass('erow');
				// Gather link_nums
				this_rid = trim($(this.cells[0]).text());
				if (isNumeric(this_rid)) {
					rids += this_rid + ",";
					// Reorder the Link #s
					$('#Resourceorder_'+this_rid).html(i);					
				}
				i++;
			});
			// Save form order
			$.post(app_path_webroot+'ExternalLinks/edit_resource_ajax.php?pid='+pid, { ext_id: 0, action: 'reorder', ext_ids: rids }, function(data){
				if (data != '1') {
					alert(woops);
					window.location.reload();
				} else {
					highlightResourceRow(current_rid);
					updateResourcePanel();
				}
			});
		},
		dragHandle: "dragHandle"
	});
	// Create mouseover image for drag-n-drop and enable button fading on row hover
	$("#table-resources tr.dragRow").hover(function() {
		$(this.cells[0]).css('background','#ffffff url("'+app_path_images+'updown.gif") no-repeat center');
		$(this.cells[0]).css('cursor','move');
	}, function() {
		$(this.cells[0]).css('background','');
		$(this.cells[0]).css('cursor','');
	});
}

// Set new Link Type
function setNewLinkType(linktype) {
	if (linktype == 'REDCAP_PROJECT') {
		displayChooseProjDialog(0,0);
		$('.newproject').show();
		$('.newurl').hide();
	} else {
		$('.newproject').hide();
		$('.newurl').show();
		$('#input_Resourceprojectlink_id_0').val('');
	}
}
// Display the choose-project dialog
function displayChooseProjDialog(resource_id,link_to_project_id) {
	// If project_id is supplied, then pre-select the drop-down when the dialog appears
	if (link_to_project_id > 0) {
		$('#choose_project_select').val(link_to_project_id);
		// Make sure current user has access to the project that is current set (if not, don't allow them to change).
		if ($('#choose_project_select').val() != link_to_project_id) {
			alert('NOTICE: It appears that you do not have access to this particular REDCap project, so you will not be able to modify the link here that points to that project. However, you may delete the link to that project, if you wish.');
			enableResourceTableEdit();
			return;
		}
	}
	enableResourceTableEdit();
	// Open the dialog
	$('#choose_project_div').dialog({ bgiframe: true, modal: true, width: 700, open: function(){fitDialog(this)}, buttons: { 
		Close: function() { $(this).dialog('close'); },
		Save: function() { 
			if ($('#choose_project_select').val().length < 1) {
				alert("Please choose a project");
				return;
			}
			if (resource_id != 0) {
				// Edit existing link
				$.post(app_path_webroot+'ExternalLinks/edit_resource_ajax.php?pid='+pid, { link_to_project_id: $('#choose_project_select').val(), action: 'edit', link_type: 'REDCAP_PROJECT', ext_id: resource_id }, function(data){
					if (data != '0') {
						$('#table-resources-parent').html(data);
						enableResourceTableEdit();
						highlightResourceRow(resource_id);
						updateResourcePanel();
						$('#choose_project_div').dialog('close');
					} else {
						alert(woops);
					}
				});
			} else {
				// Creating new link (will get saved but not here)
				var newprojtitle = $("#choose_project_select option:selected").text();	
				$('#new_projtitle_id_0').html(newprojtitle);
				$('#input_Resourceprojectlink_id_0').val( $("#choose_project_select").val() );
				enableResourceTableEdit();
				$(this).dialog('close');
			}
		} 
	}});
}
// Enable table for editing
function enableResourceTableEdit() {
	// Enable resource name edit mouseover
	$('#table-resources .editname').hover(function(){
		// If already clicked		
		if ($(this).html().indexOf('<input') > -1) { 
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
	// Resource name onclick edit action
	$('#table-resources .editname').click(function(){
		// If already clicked		
		if ($(this).html().indexOf('<input') > -1) { 
			$(this).unbind('click');
			return;
		}
		// Undo css
		$(this).css('cursor','');
		$(this).removeClass('edit_active');
		$(this).removeAttr('title');
		$(this).unbind('click');
		var thisResourceLabel = $(this).text().replace(/"/ig,'&quot;');
		var thisResourceId = $(this).attr('rid');
		$(this).html( '<input id="input_resourcename_id_'+thisResourceId+'" type="text" class="x-form-text x-form-field" style="vertical-align:middle;width:98%;" value="'+thisResourceLabel+'"><br>'
					+ '<button style="vertical-align:middle;font-size:11px;" onclick="saveResourceLabel('+thisResourceId+');">Save</button>');
		// Enable widgets/buttons
	});	
	// Enable resource url edit mouseover
	$('#table-resources .editurl, #table-resources .newproject').hover(function(){
		// If already clicked		
		if ($(this).html().indexOf('<input') > -1 && !$(this).hasClass('newproject')) { 
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
	// Resource url onclick edit action
	$('#table-resources .editurl, #table-resources .newproject').click(function(){
		// If already clicked		
		if ($(this).html().indexOf('<input') > -1 && !$(this).hasClass('newproject')) { 
			$(this).unbind('click');
			return;
		}
		// Undo css
		$(this).css('cursor','');
		$(this).removeClass('edit_active');
		$(this).removeAttr('title');
		$(this).unbind('click');
		var thisResourceurl = trim($(this).text().replace(/"/ig,'&quot;'));
		var thisResourceId = $(this).attr('rid');
		// Decide which input to make appear
		if ($('#input_linktype_id_'+thisResourceId).val() == 'REDCAP_PROJECT') {
			// Show project drop-down list	
			displayChooseProjDialog(thisResourceId,$(this).attr('pid'));			
		} else {
			// Show text input
			$(this).html( '<input id="input_resourceurl_id_'+thisResourceId+'" type="text" class="x-form-text x-form-field" style="vertical-align:middle;width:98%;" value="'+thisResourceurl+'"><br>'
						+ '<button style="vertical-align:middle;font-size:11px;" onclick="saveResourceurl('+thisResourceId+');">Save</button>');
		}
	});	
	// Enable row drag-n-drop
	enableTableDnD();
}
// Save the new resource label via ajax
function saveResourceLabel(thisResourceId) {
	var thisResourceLabel = trim($('#input_resourcename_id_'+thisResourceId).val());
	if (thisResourceLabel.length < 1) {
		alert('Please enter a URL');
		return;
	}
	$.post(app_path_webroot+'ExternalLinks/edit_resource_ajax.php?pid='+pid, { action: 'edit', label: thisResourceLabel, ext_id: thisResourceId }, function(data){
		var data2 = data;
		if (data.length<1) data2 = '&nbsp;';
		$('#Resourcename_'+thisResourceId).html(data2);
		$('#Resourcename_'+thisResourceId).addClass('edit_saved');
		setTimeout(function(){
			$('#Resourcename_'+thisResourceId).removeClass('edit_saved');
		},2000);
		enableResourceTableEdit();
		updateResourcePanel();
	});
}
// Save the new resource url via ajax
function saveResourceurl(thisResourceId) {
	var thisResourceurl = trim($('#input_resourceurl_id_'+thisResourceId).val());
	if (thisResourceurl.length < 1) {
		alert('Please enter a label');
		return;
	}
	$.post(app_path_webroot+'ExternalLinks/edit_resource_ajax.php?pid='+pid, { action: 'edit', url: thisResourceurl, ext_id: thisResourceId }, function(data){
		var data2 = data;
		if (data.length<1) data2 = '&nbsp;';
		$('#Resourceurl_'+thisResourceId).html(data2);
		$('#Resourceurl_'+thisResourceId).addClass('edit_saved');
		setTimeout(function(){
			$('#Resourceurl_'+thisResourceId).removeClass('edit_saved');
		},2000);
		enableResourceTableEdit();
		updateResourcePanel();
	});
}
// Save the new resource link type via ajax
function saveResourceLinkType(thisResourceId) {
	var thisLinkType = trim($('#input_linktype_id_'+thisResourceId).val());
	// If changing to a REDCap project
	if (thisLinkType == 'REDCAP_PROJECT') {
		displayChooseProjDialog(thisResourceId,0);
	} else {
		$.post(app_path_webroot+'ExternalLinks/edit_resource_ajax.php?pid='+pid, { action: 'edit', link_type: thisLinkType, ext_id: thisResourceId }, function(data){
			if (data != '0') {
				$('#table-resources-parent').html(data);
				enableResourceTableEdit();
				$('#linktype_save_'+thisResourceId).css('visibility','visible');
				setTimeout(function(){
					$('#linktype_save_'+thisResourceId).css('visibility','hidden');
				},2000);
				// Make sure there is a URL defined (in case we've changed this from a REDCap project link)
				if (trim($('#Resourceurl_'+thisResourceId).text()) != '') {
					updateResourcePanel();
				} else {
					$('#Resourceurl_'+thisResourceId).click();
					alert("You will now need to enter a URL for the link");
				}
			} else {
				alert(woops);
				window.location.reload();
			}
		});
	}
}
// Save the resource "append project ID" setting via ajax
function saveAppendPid(thisResourceId) {
	// Is the checkbox checked?
	var thisappendpid = ($('#input_appendpid_id_'+thisResourceId).prop('checked') ? 1 : 0);
	$.post(app_path_webroot+'ExternalLinks/edit_resource_ajax.php?pid='+pid, { action: 'edit', append_pid: thisappendpid, ext_id: thisResourceId }, function(data){
		if (data == '1') {
			$('#appendpid_save_'+thisResourceId).css('visibility','visible');
			setTimeout(function(){
				$('#appendpid_save_'+thisResourceId).css('visibility','hidden');
			},2000);
			updateResourcePanel();
		} else {
			alert(woops);
			window.location.reload();
		}
	});
}
// Save the resource "open new window" setting via ajax
function saveResourceNewWin(thisResourceId) {
	var thisNewWin = ($('#input_newwin_id_'+thisResourceId).prop('checked') ? 1 : 0);
	$.post(app_path_webroot+'ExternalLinks/edit_resource_ajax.php?pid='+pid, { action: 'edit', newwin: thisNewWin, ext_id: thisResourceId }, function(data){
		if (data == '1') {
			$('#newwin_save_'+thisResourceId).css('visibility','visible');
			setTimeout(function(){
				$('#newwin_save_'+thisResourceId).css('visibility','hidden');
			},2000);
			updateResourcePanel();
		} else {
			alert(woops);
			window.location.reload();
		}
	});
}
// Save the resource "append record info" setting via ajax
function saveAppendRec(thisResourceId) {
	// Is the checkbox checked?
	var thisappendrec = ($('#input_appendrec_id_'+thisResourceId).prop('checked') ? 1 : 0);
	// Determine if we should show the dialog box
	if (!thisappendrec) {
		// Do ajax call
		saveAppendRecSave(thisResourceId,thisappendrec);
	} else {
		// Open the dialog box
		$('#append_rec_warning').dialog({ bgiframe: true, modal: true, width: 600, 
			close: function(){ 
				if (thisappendrec) {
					$('#input_appendrec_id_'+thisResourceId).prop('checked', false);
				} else {
					$('#input_appendrec_id_'+thisResourceId).prop('checked', true);
				}
			}, 
			open: function(){fitDialog(this)}, 
			buttons: {
				Cancel: function() { $(this).dialog('close'); },
				Save: function() { saveAppendRecSave(thisResourceId,thisappendrec);	}
			}
		});
	}
}
function saveAppendRecSave(thisResourceId,thisappendrec) {
	// Make ajax call
	$.post(app_path_webroot+'ExternalLinks/edit_resource_ajax.php?pid='+pid, { action: 'edit', append_rec: thisappendrec, ext_id: thisResourceId }, function(data){
		if (data == '1') {
			$('#appendrec_save_'+thisResourceId).css('visibility','visible');
			setTimeout(function(){
				$('#appendrec_save_'+thisResourceId).css('visibility','hidden');
			},2000);
			$('#append_rec_warning').dialog('destroy');
			updateResourcePanel();
		} else {
			alert(woops);
			window.location.reload();
		}
	});
}
// Save new resource's name and url via ajax
function addNewResource() {
	// Get values
	var thisResourceLabel = trim($('#input_Resourcename_id_0').val());
	var thisResourceurl = trim($('#input_Resourceurl_id_0').val());
	var thisLinkProjId = $('#input_Resourceprojectlink_id_0').val();
	var thisAppendRec = ($('#input_appendrec_id_0').prop('checked') ? 1 : 0);
	var thisAppendPid = ($('#input_appendpid_id_0').prop('checked') ? 1 : 0);
	if (thisResourceLabel.length < 1 || (thisResourceurl.length < 1 && thisLinkProjId.length < 1)) {
		alert('Please enter both a label and URL/destination for the new resource');
		return;
	}
	if (thisLinkProjId.length < 1 && !isUrl(thisResourceurl)) {
		alert('Sorry, but the web address you entered "'+thisResourceurl+'" does not appear to be a proper URL (e.g. http://google.com). Please fix it and try again.');
		return;
	}
	// For Global Ext Links, loop and get excluded projects checked off
	var exclusions = '';
	$('#choose_project_exclude input:checkbox').each(function(){
		if (this.checked) {
			exclusions += $(this).attr('pid')+',';
		}
	});
	// Determine user access set
	var user_access = $('#input_linkusers_all_id_0').is(':checked') ? 'ALL' : ($('#input_linkusers_selected_id_0').is(':checked') ? 'SELECTED' : 'DAG');
	var userlist = '';
	var daglist  = '';
	if (user_access == 'SELECTED') {
		if ($('#user_current_resource_id').val() == '0') {
			// Loop and get all users checked off
			$('#choose_user_div input:checkbox').each(function(){
				if (this.checked) {
					userlist += $(this).attr('uid')+',';
				}
			});
		} else {
			alert('The users selected for user access for this link have been reset. Please select them again.');
		}	
	} else if (user_access == 'DAG') {
		if ($('#dag_current_resource_id').val() == '0') {
			// Loop and get all users checked off
			$('#choose_dag_div input:checkbox').each(function(){
				if (this.checked) {
					daglist += $(this).attr('gid')+',';
				}
			});
		} else {
			alert('The data access groups selected for user access for this link have been reset. Please select them again.');
		}	
	}
	// Save it via ajax
	$.post(app_path_webroot+'ExternalLinks/edit_resource_ajax.php?pid='+pid, { append_rec: thisAppendRec, append_pid: thisAppendPid, user_access: user_access, daglist: daglist, userlist: userlist, link_to_project_id: thisLinkProjId, newwin: ($('#input_newwin_id_0').prop('checked') ? 1 : 0), linktype: $('#input_linktype_id_0').val(), label: thisResourceLabel, url: thisResourceurl, ext_id: 0, exclusions: exclusions }, function(data){
		var json_data = jQuery.parseJSON(data);
		if (json_data.length < 1) {
			alert(woops);
			return;
		}
		// Set variables
		var new_resource_id = json_data.new_ext_id;
		var html = json_data.payload;
		// Add html to page		
		$('#table-resources-parent').html(html);
		enableResourceTableEdit();
		highlightResourceRow(new_resource_id);
		updateResourcePanel();
	});
}
// Delete the resource via ajax
function deleteResource(thisResourceId) {
	var thisResourceLabel = trim($('#Resourcename_'+thisResourceId).text());
	if (confirm('Are you sure you wish to delete "'+thisResourceLabel+'"?')) {
		$.post(app_path_webroot+'ExternalLinks/edit_resource_ajax.php?pid='+pid, { ext_id: thisResourceId, action: 'delete' }, function(data){
			if (data.length < 1) {
				alert(woops);
			} else {
				highlightResourceRow(thisResourceId);
				setTimeout(function(){
					$('#table-resources-parent').html(data);
					enableResourceTableEdit();
				},800);
				updateResourcePanel();	
				$('#choose_user_div').hide();
				$('#choose_dag_div').hide();
			}
		});
	}
}
// Highlight resource row
function highlightResourceRow(resource_id) {
	//$('#Resourcedrag_'+resource_id).parent().parent().effect('highlight',3000);
	$('#Resourceorder_'+resource_id).parent().parent().effect('highlight',3000);
	$('#Resourcename_'+resource_id).parent().parent().effect('highlight',3000);
	$('#Resourceurl_'+resource_id).parent().parent().effect('highlight',3000);
	$('#Resourcenewwin_'+resource_id).parent().parent().effect('highlight',3000);
	$('#Resourcelinktype_'+resource_id).parent().parent().effect('highlight',3000);
	$('#Resourcelinkusers_'+resource_id).parent().parent().effect('highlight',3000);
	$('#Resourceappendrec_'+resource_id).parent().parent().effect('highlight',3000);
	$('#Resourceappendpid_'+resource_id).parent().parent().effect('highlight',3000);
	$('#Resourcedel_'+resource_id).parent().parent().effect('highlight',3000);
}
// Update left-hand menu panel
function updateResourcePanel() {
	if (pid == 'null') return;
	$.post(app_path_webroot+'ExternalLinks/render_resource_panel_ajax.php?pid='+pid, { }, function(data){
		if (data == '0') {
			alert(woops);
		} else {
			// Update the left-hand menu
			$('#extres_panel').remove();
			if ($('#global_ext_links').length) {
				$('#global_ext_links').after(data);
			} else {
				$('#app_panel').after(data);
			}
		}
	});
}
// Check if user list box is already open
function checkUserAccessVal(resource_id) {
	// If user list box is already open on same row, then close it (i.e. user clicked the link to close it)
	if ($('#input_linkusers_selected_id_'+resource_id).prop('checked') && $('#user_current_resource_id').val() == resource_id && $('#choose_user_div').css('display') != 'none') {
		$('#cancel_user_btn').click();
		return;
	}
	// Now go ahead and check it
	$('#input_linkdags_selected_id_'+resource_id).prop('checked',false);
	$('#input_linkusers_all_id_'+resource_id).prop('checked',false);
	$('#input_linkusers_selected_id_'+resource_id).prop('checked',true);
	// Open the user list box
	selectResourceUsers(resource_id);
}
// Open window for choosing users who can access this resource
function selectResourceUsers(resource_id) {
	$('#choose_dag_div').hide();
	if ($('#input_linkusers_all_id_'+resource_id).is(':checked')) {
		if (resource_id == 0) {
			// New resource that does not exist yet (just close box)
			$('#choose_user_div').hide();
		} else {
			// Save via ajax
			$.post(app_path_webroot+'ExternalLinks/save_resource_users_ajax.php?pid='+pid, { ext_id: resource_id, user_access: 'ALL' }, function(data){
				if (data == '0') {
					alert(woops);
				} else {
					$('#choose_user_div').fadeOut('slow');
					$('#linkusers_save_'+resource_id).css('visibility','visible');
					setTimeout(function(){ 
						$('#linkusers_save_'+resource_id).css('visibility','hidden')  
					},2000);
					updateResourcePanel();				
				}
			});
		}
	} else {
		// Make user pop-up appear
		$('#choose_user_div').hide();
		// Determine where to put the box and then display it
		var cell = $('#Resourcelinkusers_'+resource_id).parent().parent();
		var cellpos = cell.offset();
		$('#choose_user_div').css({ 'left': cellpos.left - ($('#choose_user_div').outerWidth(true) - cell.outerWidth(true))/2, 'top': cellpos.top + cell.outerHeight(true) - 13 });
		$('#choose_user_div').fadeIn('slow');
		if (resource_id == 0 && $('#user_current_resource_id').val() == '0') {
			// Don't reload when adding a new resource if already been opened
			$('#choose_user_div_list').fadeIn();
			$('#choose_user_div_loading').hide();
			$('#save_user_btn').attr('disabled',false);
			$('#cancel_user_btn').attr('disabled',false);
			$('#save_user_progress').hide();
			$('#save_user_saved').hide();
			$('#select_links').css('visibility','visible');
		} else {
			// Set resource_id for user list box
			$('#user_current_resource_id').val(resource_id);
			$('#choose_user_div_loading').show();
			$('#choose_user_div_list').html('').hide();
			// Now perform the ajax call to get the user list
			$.post(app_path_webroot+'ExternalLinks/display_resource_users_ajax.php?pid='+pid, { ext_id: resource_id }, function(data){
				if (data == '0') {
					alert(woops);
				} else {
					$('#choose_user_div_list').html(data).fadeIn();
					$('#choose_user_div_loading').hide();
					$('#save_user_btn').attr('disabled',false);
					$('#cancel_user_btn').attr('disabled',false);
					$('#save_user_progress').hide();
					$('#save_user_saved').hide();
					$('#select_links').css('visibility','visible');
				}
			});
		}
	}
}
// Close user access list and reload the table
function cancelResourceUsers() {
	var resource_id = $('#user_current_resource_id').val();
	// Get user_access value via ajax
	$('#save_user_btn').attr('disabled',true);
	$('#cancel_user_btn').attr('disabled',true);
	$('#select_links').css('visibility','hidden');
	if (resource_id == 0) {
		// New resource that does not exist yet (just close box)
		$('#choose_user_div').hide();
		$('#input_linkusers_selected_id_'+resource_id).prop('checked',false);
		$('#input_linkusers_all_id_'+resource_id).prop('checked',true);
	} else {
		$.post(app_path_webroot+'ExternalLinks/save_resource_users_ajax.php?pid='+pid, { ext_id: resource_id }, function(data){
			if (data == 'ALL' || data == 'SELECTED' || data == 'DAG') {
				$('#save_user_btn').attr('disabled',false);
				$('#cancel_user_btn').attr('disabled',false);
				$('#choose_user_div').fadeOut('fast');
				if (data == 'ALL') {
					$('#input_linkdags_selected_id_'+resource_id).prop('checked',false);
					$('#input_linkusers_selected_id_'+resource_id).prop('checked',false);
					$('#input_linkusers_all_id_'+resource_id).prop('checked',true);
				} else if (data == 'SELECTED') {
					$('#input_linkdags_selected_id_'+resource_id).prop('checked',false);
					$('#input_linkusers_all_id_'+resource_id).prop('checked',false);
					$('#input_linkusers_selected_id_'+resource_id).prop('checked',true);
				} else {
					$('#input_linkusers_all_id_'+resource_id).prop('checked',false);
					$('#input_linkusers_selected_id_'+resource_id).prop('checked',false);
					$('#input_linkdags_selected_id_'+resource_id).prop('checked',true);
				}
			} else {
				alert(woops);
			}
		});
	}
}
// Save users who can access this resource
function saveResourceUsers() {
	var resource_id = $('#user_current_resource_id').val();
	$('#save_user_btn').attr('disabled',true);
	$('#cancel_user_btn').attr('disabled',true);
	$('#save_user_progress').show();
	$('#select_links').css('visibility','hidden');
	// Get usernames of those checked
	var userlist = '';
	$('#choose_user_div input:checkbox').each(function(){
		if (this.checked) {
			userlist += $(this).attr('uid')+',';
		}
	});
	if (resource_id == '0') {
		// New resource that does not exist yet (just close box)
		$('#choose_user_div').hide();
	} else {
		// Save existing resource via ajax
		$.post(app_path_webroot+'ExternalLinks/save_resource_users_ajax.php?pid='+pid, { ext_id: resource_id, userlist: userlist, user_access: 'SELECTED' }, function(data){
			if (data == '0') {
				alert(woops);
			} else {
				// Make checkbox appear
				$('#save_user_progress').hide();
				$('#save_user_saved').show();
				setTimeout(function(){ $('#choose_user_div').fadeOut('slow') },700);
				setTimeout(function(){ $('#linkusers_save_'+resource_id).css('visibility','visible') },1000);
				setTimeout(function(){ $('#linkusers_save_'+resource_id).css('visibility','hidden')  },3000);	
				updateResourcePanel();		
			}
		});
	}
}
// Select or deselect all users in resource user list
function selectAllUsers(select_all) {
	var do_select_all = (select_all == 1);
	$('#choose_user_div input:checkbox').each(function(){
		$(this).prop('checked',do_select_all);
	});
}




// Check if DAG list box is already open
function checkDagAccessVal(resource_id) {
	// If user list box is already open on same row, then close it (i.e. user clicked the link to close it)
	if ($('#input_linkdags_selected_id_'+resource_id).prop('checked') && $('#dag_current_resource_id').val() == resource_id && $('#choose_dag_div').css('display') != 'none') {
		$('#cancel_dag_btn').click();
		return;
	}
	// Now go ahead and check it
	$('#input_linkusers_all_id_'+resource_id).prop('checked',false);
	$('#input_linkusers_selected_id_'+resource_id).prop('checked',false);
	$('#input_linkdags_selected_id_'+resource_id).prop('checked',true);
	// Open the user list box
	selectResourceDags(resource_id);
}
// Open window for choosing DAGs who can access this resource
function selectResourceDags(resource_id) {
	$('#choose_user_div').hide();
	if ($('#input_linkusers_all_id_'+resource_id).is(':checked')) {
		if (resource_id == 0) {
			// New resource that does not exist yet (just close box)
			$('#choose_dag_div').hide();
		} else {
			// Save via ajax
			$.post(app_path_webroot+'ExternalLinks/save_resource_users_ajax.php?pid='+pid, { ext_id: resource_id, user_access: 'ALL' }, function(data){
				if (data == '0') {
					alert(woops);
				} else {
					$('#choose_user_div').fadeOut('slow');
					$('#linkusers_save_'+resource_id).css('visibility','visible');
					setTimeout(function(){ 
						$('#linkusers_save_'+resource_id).css('visibility','hidden')  
					},2000);
					updateResourcePanel();				
				}
			});
		}
	} else {
		// Make DAG pop-up appear
		$('#choose_dag_div').hide();
		// Determine where to put the box and then display it
		var cell = $('#Resourcelinkusers_'+resource_id).parent().parent();
		var cellpos = cell.offset();
		$('#choose_dag_div').css({ 'left': cellpos.left - ($('#choose_dag_div').outerWidth(true) - cell.outerWidth(true))/2, 'top': cellpos.top + cell.outerHeight(true) - 13 });
		$('#choose_dag_div').fadeIn('slow');
		if (resource_id == 0 && $('#dag_current_resource_id').val() == '0') {
			// Don't reload when adding a new resource if already been opened
			$('#choose_dag_div_list').fadeIn();
			$('#choose_dag_div_loading').hide();
			$('#save_dag_btn').attr('disabled',false);
			$('#cancel_dag_btn').attr('disabled',false);
			$('#save_dag_progress').hide();
			$('#save_dag_saved').hide();
			$('#select_links_dags').css('visibility','visible');
		} else {
			// Set resource_id for user list box
			$('#dag_current_resource_id').val(resource_id);
			$('#choose_dag_div_loading').show();
			$('#choose_dag_div_list').html('').hide();
			// Now perform the ajax call to get the user list
			$.post(app_path_webroot+'ExternalLinks/display_resource_dags_ajax.php?pid='+pid, { ext_id: resource_id }, function(data){
				if (data == '0') {
					alert(woops);
				} else {
					$('#choose_dag_div_list').html(data).fadeIn();
					$('#choose_dag_div_loading').hide();
					$('#save_dag_btn').attr('disabled',false);
					$('#cancel_dag_btn').attr('disabled',false);
					$('#save_dag_progress').hide();
					$('#save_dag_saved').hide();
					$('#select_links_dags').css('visibility','visible');
				}
			});
		}
	}
}
// Close user access list and reload the table
function cancelResourceDags() {
	var resource_id = $('#dag_current_resource_id').val();
	// Get user_access value via ajax
	$('#save_dag_btn').attr('disabled',true);
	$('#cancel_dag_btn').attr('disabled',true);
	$('#select_links_dags').css('visibility','hidden');
	if (resource_id == 0) {
		// New resource that does not exist yet (just close box)
		$('#choose_dag_div').hide();
		$('#input_linkusers_selected_id_'+resource_id).prop('checked',false);
		$('#input_linkusers_all_id_'+resource_id).prop('checked',true);
	} else {
		$.post(app_path_webroot+'ExternalLinks/save_resource_users_ajax.php?pid='+pid, { ext_id: resource_id }, function(data){
			if (data == 'ALL' || data == 'SELECTED' || data == 'DAG') {
				$('#save_dag_btn').attr('disabled',false);
				$('#cancel_dag_btn').attr('disabled',false);
				$('#choose_dag_div').fadeOut('fast');
				if (data == 'ALL') {
					$('#input_linkdags_selected_id_'+resource_id).prop('checked',false);
					$('#input_linkusers_selected_id_'+resource_id).prop('checked',false);
					$('#input_linkusers_all_id_'+resource_id).prop('checked',true);
				} else if (data == 'SELECTED') {
					$('#input_linkdags_selected_id_'+resource_id).prop('checked',false);
					$('#input_linkusers_all_id_'+resource_id).prop('checked',false);
					$('#input_linkusers_selected_id_'+resource_id).prop('checked',true);
				} else {
					$('#input_linkusers_all_id_'+resource_id).prop('checked',false);
					$('#input_linkusers_selected_id_'+resource_id).prop('checked',false);
					$('#input_linkdags_selected_id_'+resource_id).prop('checked',true);
				}
			} else {
				alert(woops);
			}
		});
	}
}
// Save users who can access this resource
function saveResourceDags() {
	var resource_id = $('#dag_current_resource_id').val();
	$('#save_dag_btn').attr('disabled',true);
	$('#cancel_dag_btn').attr('disabled',true);
	$('#save_dag_progress').show();
	$('#select_links_dags').css('visibility','hidden');
	// Get DAGs checked
	var daglist = '';
	$('#choose_dag_div input:checkbox').each(function(){
		if (this.checked) {
			daglist += $(this).attr('gid')+',';
		}
	});
	if (resource_id == '0') {
		// New resource that does not exist yet (just close box)
		$('#choose_dag_div').hide();
	} else {
		// Save existing resource via ajax
		$.post(app_path_webroot+'ExternalLinks/save_resource_users_ajax.php?pid='+pid, { ext_id: resource_id, daglist: daglist, user_access: 'DAG' }, function(data){
			if (data == '0') {
				alert(woops);
			} else {
				// Make checkbox appear
				$('#save_dag_progress').hide();
				$('#save_dag_saved').show();
				setTimeout(function(){ $('#choose_dag_div').fadeOut('slow') },700);
				setTimeout(function(){ $('#linkusers_save_'+resource_id).css('visibility','visible') },1000);
				setTimeout(function(){ $('#linkusers_save_'+resource_id).css('visibility','hidden')  },3000);	
				updateResourcePanel();		
			}
		});
	}
}

// Select or deselect all users in resource user list
function selectAllDags(select_all) {
	var do_select_all = (select_all == 1);
	$('#choose_dag_div input:checkbox').each(function(){
		$(this).prop('checked',do_select_all);
	});
}

// Select or deselect all projects in list to exclude
function excludeAllProjects(select_all) {
	var do_select_all = (select_all == 1);
	$('#choose_project_exclude input:checkbox').each(function(){
		$(this).prop('checked',do_select_all);
	});
}


// Open dialog for excluding projects
function openExcludeProjPopup(resource_id) {
	$.post(app_path_webroot+'ExternalLinks/excluded_projects_ajax.php', { ext_id: resource_id, action: 'view' }, function(data){
		if (data == '0') {
			alert(woops);
		} else {
			// Make checkbox appear
			$('#exclude_project-parent').html(data);
			// Inject link label
			$('#linkLabelPrefill').html(trim($('#Resourcename_'+resource_id).text()));
			// If ext_id is 0, then check off excluded projects checkboxes saved when dialog was previously opened
			if (resource_id == 0) {
				var exclPids = $('#prev_excl_proj_0').val().split(',');
				for (i = 0; i < exclPids.length; i++) {
					exclPids[i] = trim(exclPids[i]);
					if (exclPids[i].length > 0) {
						$('#choose_project_exclude input#pid_'+exclPids[i]).prop('checked',true);
					}
				}
			}
			// Open dialog
			$('#exclude_project-parent').dialog({ bgiframe: true, modal: true, width: 600, open: function(){fitDialog(this)}, 
				buttons: { 
					Close: function() { $(this).dialog('close'); },
					Save: function() {
						// Loop and get projects checked off
						var exclusions = '';
						$('#choose_project_exclude input:checkbox').each(function(){
							if (this.checked) {
								exclusions += $(this).attr('pid')+',';
							}
						});
						// If ext_id is 0, then don't save since it doesn't exist yet 
						if (resource_id == 0) {
							$('#prev_excl_proj_0').val(exclusions);
							$('#exclude_project-parent').dialog('close');
						} else {
							$.post(app_path_webroot+'ExternalLinks/excluded_projects_ajax.php', { ext_id: resource_id, action: 'save', exclusions: exclusions }, function(data){
								if (data == '0') {
									alert(woops);
								} else {
									$('#linkusers_save_'+resource_id).css('visibility','visible');
									setTimeout(function(){ 
										$('#linkusers_save_'+resource_id).css('visibility','hidden')  
									},2000);
									$('#exclude_project-parent').dialog('close');
									excludeAllProjects(0);
								}
							});
						}
					}
				} 
			});	
		}
	});
}