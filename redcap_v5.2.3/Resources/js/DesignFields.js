
// Set up pre-check when put focus on validation type in pop-up to catch when user's change date validation format (effects min/max validation values)
var oldValType, newValType;
	
$(function(){

	// Set trigger to open Matrix Group Help pop-up
	$('.mtxgrpHelp').click(function(){
		simpleDialog(langOD25,langOD26);
	});	

	// Enable drag n drop for table
	AddTableDrag();
	
	// Set up pre-check when put focus on validation type in pop-up
	preCheckValType();
	
	// Check if non-numeric MC coded values exist and fix
	$('#element_enum, #element_enum_matrix').blur(function(){
		checkEnumRawVal($(this));
	});
	
	// Field name unique check + enable auto-variable naming (if set)
	field_name_trigger($('#field_name'), $('#field_label'));
	
	// Set trigger for matrix group name check for unique name
	$('#grid_name').blur(function(){
		// When using auto-variable naming, converts field label into variable
		$(this).val( filterMatrixGroupName($(this).val()) );
		// Perform matrix group name check for unique name
		checkMatrixGroupName(false);
	});
	
	// Set click trigger to show more matrix field inputs
	$('#addMoreMatrixFields').click(function(){
		addAnotherRowMatrixPopup(1);
		// Get last pair of input fields in popup
		var field_name_ob = $('#addMatrixPopup .field_name_matrix:last');
		var field_label_ob = $('#addMatrixPopup .field_label_matrix:last');
		// Remove any values from the row just created (problem for IE7-8 when copying a row with value in input)
		field_name_ob.val('');
		field_label_ob.val('');
		// Add unique check trigger to field_name field
		field_name_trigger(field_name_ob, field_label_ob);
		// Enable auto-var naming (if set)
		enableAutoVarSuggest(field_name_ob, field_label_ob);
		// Set drag-n-drop again on matrix fields
		enableMatrixFieldDrag();
		// Highlight new row
		field_name_ob.effect('highlight',{ },1000);
		field_label_ob.effect('highlight',{ },1000);
		$('#addMatrixPopup .field_quesnum_matrix:last').effect('highlight',{ },1000);
		// Put focus on new label field
		field_label_ob.focus();
	});
		
	// When leaving field label box, always go directly to variable name box.
	$('#field_label').blur(function(){
		if (!(status > 0 && $('#sq_id').val().length > 1)) {
			setTimeout(function () { $('#field_name').focus() }, 1);
		}
	});

	// When click to enable auto variable naming
	$('#auto_variable_naming, #auto_variable_naming_matrix').click(function(){
		var auto_variable_naming = ($(this).prop('checked') ? 1 : 0);
		var id = $(this).attr('id');
		if (auto_variable_naming) {			
			$('#auto_variable_naming-popup').dialog({ bgiframe: true, modal: true, width: 550,
				buttons: [
					{ text: langCancel, click: function () { 
						$('#auto_variable_naming, #auto_variable_naming_matrix').prop('checked',false);
						$(this).dialog("close");
					} },
					{ text: langOD1, click: function () { 
						$('#auto_variable_naming, #auto_variable_naming_matrix').prop('checked',true);
						save_auto_variable_naming(auto_variable_naming,id);
						$(this).dialog("close"); 
					} }
				]
			});
		} else {
			save_auto_variable_naming(auto_variable_naming,id);
			// Make sure that both checkboxes (in both dialogs) get unchecked
			$('#auto_variable_naming, #auto_variable_naming_matrix').prop('checked',false);
		}
	});
	
	// Display tooltip popup if user attempts to move a matrix field
	$('.mtxRow').tooltip({
		onBeforeShow: function(event) {
			// Ignore this if we clicked on an action icon, link, or input
			var ignore = new Array('a','input','img','select','button');
			var targetTag = event.target.nodeName.toLowerCase();
			if (in_array(targetTag, ignore)) return false;
		},
		tip: '#tooltipMoveMatrix',
		position: 'center left',
		offset: [0, 10],
		delay: 100,
		events: { def: "mousedown," }
	});
	
	// For survey projects, display message that primary key field will not be displayed on survey page
	showPkNoDisplayMsg();
	
	// Display tooltip popup if user attempts to move the Primary Key field
	enablePkFieldTooltipEtc();
});


// Display tooltip popup if user attempts to move the Primary Key field
function enablePkFieldTooltipEtc() {
	if ($('table#design-'+table_pk).length) {
		// Enable tooltip
		$('table#design-'+table_pk).tooltip({
			onBeforeShow: function(event) {
				// Ignore this if we clicked on an action icon, link, or input
				var ignore = new Array('a','input','img','select','button');
				var targetTag = event.target.nodeName.toLowerCase();
				if (in_array(targetTag, ignore)) return false;
			},
			tip: '#tooltipMovePk',
			position: 'center left',
			offset: [0, 0],
			delay: 100,
			events: { def: "mousedown," }
		});
		// Add note that record ID field cannot be moved
		$('table#design-'+table_pk).after('<div class="frmedit" style="padding:5px 0 10px 5px;margin-bottom:10px;background-color:#DDDDDD;font-size:11px;color:#888;">'+langOD48+'</div>');
	}
}

// For survey projects, display message that primary key field will not be displayed on survey page
function showPkNoDisplayMsg() {
	// If not a survey or if table_pk not in this form, then stop here
	if (surveys_enabled == 0 || !$('table#draggable #'+table_pk+'-tr .pkNoDispMsg').length) return;
	// Hide all first
	$('.pkNoDispMsg').hide();
	// Now display the message for the first row in the table (might not be the primary key field if reordering has been done)
	$('table#draggable tr:first .pkNoDispMsg').html(langPkNoDisplayMsg).show();
}

// When using auto-variable naming, converts field label into variable
function filterMatrixGroupName(name) {
	var name = filterFieldName(trim(name));
	// Force 60 chars or less (and then remove any underscores on end)
	if (name.length > 60) {						
		name = filterFieldName(name.substr(0,60));
	}
	return name;
}

// Display the dialog of matrix example pictures
function showMatrixExamplePopup() {
	$.get(app_path_webroot+'Design/matrix_examples.php?pid='+pid, {  }, function(data){
		$('#matrixExamplePopup').html(data);
		$('#matrixExamplePopup').dialog({ bgiframe: true, modal: true, width: 790, height: 700,
			buttons: {
				Close: function() { $(this).dialog('close'); }
			}
		});
	});					
}
	
// Perform matrix group name check for unique name (save the matrix group via Ajax, if specified)
function checkMatrixGroupName(saveMatrix,current_field,next_field) {
	if (saveMatrix == null) saveMatrix = false;
	var ob = $('#grid_name');
	// Trim it
	ob.val(trim(ob.val()));
	// Now validate the name with regex
	var isError = (ob.val().length > 0 && !matrixGroupNameValidate(ob));
	if (!matrixGroupNameTextBlurError(ob,isError)) return false;	
	// ALSO do unique check on all variable names (if saving matrix group here)
	var checkVarNames = new Array();
	var i = 0;
	if (saveMatrix) {
		var old_matrix_field_names = $('#old_matrix_field_names').val().split(',');
		// Loop through all matrix variable names
		$('#addMatrixPopup .field_name_matrix').each(function(){
			if (!in_array($(this).val(), old_matrix_field_names)) {
				checkVarNames[i] = $(this).val();
				i++;
			}
		});
	}
	// Set vars to know what to check (matrix group name, field names, or both)
	var checkGridName = (ob.val() != $('#old_grid_name').val()) ? 1 : 0;
	var checkFieldNames = (checkVarNames.length > 0) ? 1 : 0;
	// Do AJAX request to do unique check on grid_name OR on variables names (if any are new) OR both
	if (checkGridName || checkFieldNames) {
		// Check if grid_name does not exist already. If so, then return error
		$.get(app_path_webroot+'Design/check_matrix_group_name.php',{ checkFieldNames: checkFieldNames, fieldNames: checkVarNames.join(','), checkGridName: checkGridName, pid: pid, grid_name: ob.val(), old_grid_name: $('#old_grid_name').val() },function(data){
			var json_data = jQuery.parseJSON(data);
			if (json_data.length < 1) {
				alert(woops);
				return false;
			}
			// Determine action
			if (json_data.matrixGroup == '1') {
				// Matrix group already exists
				simpleDialog('"'+ob.val()+'" '+langOD2);
				ob.css({'background-color':'#FFB7BE','font-weight':'bold'});
				return false;
			} else if (json_data.fieldNames != '0' && json_data.fieldNames != '') {
				// Field name(s) already exist
				simpleDialog(langOD3+"<br><br>"+langOD4+"<br> - <b>"+json_data.fieldNames.replace(/,/ig,"</b><br> - <b>")+"</b>");
				return false;
			}
			// Save matrix via Ajax
			if (saveMatrix) {
				saveMatrixAjax(current_field,next_field);
			}
		});
	} else if (saveMatrix) {			
		// Save matrix via Ajax
		saveMatrixAjax(current_field,next_field);
	}
}
	
// Field name unique check + enable auto-variable naming (if set)
function field_name_trigger(field_name_ob, field_label_ob) {
	// Do some variable name checking (for uniqueness and format) when leaving the field
	field_name_ob.blur(function(){
		// Prevent auto variable from overwriting variable name given
		field_label_ob.unbind();
		// Re-enable auto var suggest again if no variable is given 
		if ($(this).val().length < 1) {
			enableAutoVarSuggest(field_name_ob, field_label_ob);
		}
		// Check non-Latin characters
		if (checkIsTwoByte($(this).val())) {
			simpleDialog(twoByteCharMsg);
			$(this).val('');
			return;
		}
		// Check if valid and unique variable name
		if (field_name_ob.attr('id') == 'field_name') {
			checkFieldName($(this).val(),false);
		} else {
			checkMatrixFieldName(field_name_ob);
		}
	});
}
	
// Add a new row in the Add/Edit Matrix popup (set number of rows to be added)
function addAnotherRowMatrixPopup(new_rows) {
	if (new_rows == null) new_rows = 1;
	var originalRow = $('.addFieldMatrixRow:first').html();
	var html = "";
	for (var k = 1; k <= new_rows; k++) {
		html += "<tr class='addFieldMatrixRow'>"+originalRow+"</tr>";
	}
	// Add row to dialog
	$('.addFieldMatrixRowParent').append(html);
	// Reset dialog height (if needed)
	fitDialog($('#addMatrixPopup'));
}
	
// Save auto_variable_naming value via AJAX
function save_auto_variable_naming(val,id) {
	$.post(app_path_webroot+'Design/set_auto_var_naming_ajax.php?pid='+pid, { auto_variable_naming: val }, function(data){
		if (data != '1') {
			alert(woops);
			window.location.reload();
		}
		// Enable auto-var naming again (if set) on appropriate fields
		if (id == 'auto_variable_naming_matrix') {
			// Unbind anything on all the label/variable input
			$('#addMatrixPopup .field_name_matrix').unbind();
			$('#addMatrixPopup .field_label_matrix').unbind();
			// Put focus back on EACH name so it will auto name each, if no var is defined yet
			matrix_field_name_trigger();
			// Loop through each matrix row and put focus on label for any without a var name defined
			for (var k = 0; k < $('#addMatrixPopup .field_label_matrix').length; k++) {
				field_name_ob  = $('#addMatrixPopup .field_name_matrix').eq(k);
				field_label_ob = $('#addMatrixPopup .field_label_matrix').eq(k);
				if (field_name_ob.val().length < 1) {
					field_label_ob.focus();
				}
				field_name_ob.focus();
			}
			// Set object for showing saved status
			var savedStatusOb = $('#auto_variable_naming_matrix_saved');
		} else {
			enableAutoVarSuggest($('#field_name'), $('#field_label'));
			// Put focus back on label so it will auto name, if no var is defined yet
			if (val && $('#field_name').val() == '') {
				$('#field_label').trigger('click').focus();
			}
			// Set object for showing saved status
			var savedStatusOb = $('#auto_variable_naming_saved');
		}
		// Show saved status
		savedStatusOb.css('visibility','visible');
		setTimeout(function(){
			savedStatusOb.css('visibility','hidden');
		},2500);
	});
}

// Remove any illegal characters from REDCap variable names
function filterFieldName(temp) {
	temp = trim(temp);
	temp = temp.toLowerCase();
	temp = temp.replace(/[^a-z0-9]/ig,"_");
	temp = temp.replace(/[_]+/g,"_");
	while (temp.length > 0 && (temp.charAt(0) == "_" || temp.charAt(0)*1 == temp.charAt(0))) {
		temp = temp.substr(1,temp.length);
	}
	while (temp.length > 0 && temp.charAt(temp.length-1) == "_") { 
		temp = temp.substr(0,temp.length-1);
	}
	return temp;
}

// For matrix popup fields, check if field name exists and make any corrections
function checkMatrixFieldName(ob) 
{
	// Reset bg color
	ob.css('background-color','#ffffff');
	// Trim and make sure it's not blank
	ob.val( trim(ob.val()) );
	if (ob.val().length == 0) return false;	
	// Remove any illegal characters
	ob.val( filterFieldName(ob.val()) );
	// Make sure it's not a reserved variable
	var is_reserved = in_array(ob.val(), reserved_field_names);
	if (is_reserved) {
		setTimeout(function(){
			simpleDialog('"'+ob.val()+'" '+langOD5,null,'illegalFieldErrorDialog');
		},50);
		ob.css('background-color','#FFB7BE');
		return false;
	}
	// Check non-Latin characters
	if (checkIsTwoByte(ob.val())) {
		simpleDialog(twoByteCharMsg);
		return false;
	}
	// Make sure the field name doesn't conflict with others in the same matrix dialog popup
	if (!checkFieldUniqueInSameMatrix(ob)) {
		simpleDialog('"'+ob.val()+'" '+duplVarMtxMsg);
		ob.css('background-color','#FFB7BE');
		return false;
	}
	// Loop through all matrix variable names and see which ones are newly added (since we opened the popup)
	if (!in_array(ob.val(), $('#old_matrix_field_names').val().split(','))) {
		// Check if grid_name does not exist already. If so, then return error
		$.get(app_path_webroot+'Design/check_matrix_group_name.php',{ checkFieldNames: 1, fieldNames: ob.val(), pid: pid },function(data){
			var json_data = jQuery.parseJSON(data);
			if (json_data.length < 1) {
				alert(woops);
				return false;
			}
			// Determine action
			if (json_data.fieldNames != '0' && json_data.fieldNames != '') {
				// Field name(s) already exist
				simpleDialog('The variable name "'+json_data.fieldNames+'" '+langOD6,null,'fieldExistsErrorDialog');
				ob.css('background-color','#FFB7BE');
				return false;
			}		
		});
	}
	// Detect if longer than 26 characters. If so, give warning but allow.
	if (ob.val().length > 26) {
		simpleDialog(langOD7);
	}
}

//Check if field name exists (when editing field via AJAX) and make any corrections
function checkFieldName(temp,submitForm) 
{
	// Set save button to show progress spinner
	if (submitForm) {
		var saveBtn = $('#div_add_field').dialog("widget").find(".ui-dialog-buttonpane button").eq(1);
		var saveBtnHtml = saveBtn.html();
		var cancelBtn = $('#div_add_field').dialog("widget").find(".ui-dialog-buttonpane button").eq(0);
		saveBtn.html('<img src="'+app_path_images+'progress_circle.gif" class="imgfix"> '+langOD8);
		cancelBtn.prop('disabled',true);
		saveBtn.prop('disabled',true);
	}
	// If a section header, then ignore field name and just submit, if saving field
	if ($('#field_type').val() == 'section_header' && submitForm) {
		document.addFieldForm.submit();
	}
	// Initialize and get current field name before changed
	old_field_name = $('#sq_id').val();
	document.getElementById('field_name').style.backgroundColor='#FFFFFF';
	//Make sure value is not empty
	temp = trim(temp);
	if (temp.length == 0) return false;		
	//Remove any illegal characters
	document.getElementById('field_name').value = temp = filterFieldName(temp);
	//Detect if an illegal field name ('redcap_event_name' et al)
	if (!(status > 0 && $('#field_name').attr('readonly'))) { // If already in production with a reserved name, do not give alert (it's too late).
		var is_reserved = in_array(temp, reserved_field_names);
		if (is_reserved) {
			setTimeout(function(){
				simpleDialog('"'+temp+'" '+langOD5,null,'illegalFieldErrorDialog',null,"document.getElementById('field_name').focus();");
			},50);
			document.getElementById('field_name').style.backgroundColor='#FFB7BE';
			// Set save button back to normal if clicked Save button
			if (submitForm) {
				saveBtn.html(saveBtnHtml);
				cancelBtn.prop('disabled',false);
				cancelBtn.removeAttr('disabled');
				saveBtn.prop('disabled',false);	
				saveBtn.removeAttr('disabled');
			}
			return false;
		}
	}
	// Make sure this variable name doesn't already exist
	if (temp != old_field_name) {
		// Make ajax call
		$.get(app_path_webroot+'Design/check_field_name.php', { pid: pid, field_name: temp, old_field_name: old_field_name },
			function(data) {
				if (data != '0') {
					// Set save button back to normal if clicked Save button
					if (submitForm) {
						saveBtn.html(saveBtnHtml);
						cancelBtn.prop('disabled',false);
						cancelBtn.removeAttr('disabled');
						saveBtn.prop('disabled',false);	
						saveBtn.removeAttr('disabled');
					}
					simpleDialog(langOD9+' "'+temp+'" '+langOD10+' "'+data+'"'+langPeriod+' '+langOD11,null,'fieldExistsErrorDialog',null,"document.getElementById('field_name').focus();");
					document.getElementById('field_name').style.backgroundColor='#FFB7BE';
				} else {
					//Detect if longer than 26 characters. If so, give warning but allow.
					if (temp.length > 26) {
						simpleDialog(langOD7);
					}
					// No duplicates. Submit form if specified.
					if (submitForm) {
						document.addFieldForm.submit();
					}
				}
			}
		 );
	} else {
		//Detect if longer than 26 characters. If so, give warning but allow.
		if (temp.length > 26) {
			simpleDialog(langOD7);
		}
		// No duplicates. Submit form if specified.
		if (submitForm) {
			document.addFieldForm.submit();
		}
	}
}

// Check if a filename for a field attachment is an image
function checkAttachImg(filename,set_inline_img) {
	var dot = filename.lastIndexOf("."); 
	if( dot == -1 ) return ""; 
	var extension = filename.substr(dot+1,filename.length).toLowerCase();
	var is_img = (extension == "jpeg" || extension == "jpg" || extension == "gif" || extension == "png" || extension == "bmp");
	enableAttachImgOption(is_img,set_inline_img);
}

// Enable/disable the radio button to specify field attachment as an image or link
function enableAttachImgOption(enable,set_inline_img) {
	if (enable) {
		// Image - give choice to display as image or link
		$('#div_img_display_options').fadeTo(0, 1);
		$('#edoc_img_display_link').prop('disabled',false);
		$('#edoc_img_display_image').prop('disabled',false);
		set_inline_img = (set_inline_img != null) ? set_inline_img : '1';
		if (set_inline_img == '1') {
			$('#edoc_img_display_link').prop('checked',false);
			$('#edoc_img_display_image').prop('checked',true);
		} else{
			$('#edoc_img_display_image').prop('checked',false);
			$('#edoc_img_display_link').prop('checked',true);
		}
	} else {
		// File - force it as link only
		$('#div_img_display_options').fadeTo(0, 0.3);
		$('#edoc_img_display_link').prop('checked',true);
		$('#edoc_img_display_image').prop('checked',false);
		$('#edoc_img_display_link').prop('disabled',true);
		$('#edoc_img_display_image').prop('disabled',true);
	}
}

// Select/deselect all stop action checkboxes in popup dialog
function selectAllStopActions(select_all) {
	select_chkbox = (select_all ? true : false);
	$('#stop_actions_checkboxes input[type="checkbox"]').each(function(){
		$(this).prop('checked', select_chkbox);
	});
}

// Set stop actions
function setStopActions(field_name) {
	$.post(app_path_webroot+'Design/stop_actions.php?pid='+pid, { field_name: field_name, action: 'view' }, function(data){
		if (data != '0') {
			$('#stop_action_popup').html(data);
			$('#stop_action_popup').dialog({ bgiframe: true, modal: true, width: 550, open: function(){fitDialog(this)},
				buttons: {
					Close: function() { $(this).dialog('close'); },
					Save: function() {
						var codes = new Array();
						var i = 0;
						$('#stop_actions_checkboxes input[type="checkbox"]').each(function(){
							if ($(this).prop('checked')) {
								codes[i] = $(this).val();
								i++;
							}
						});
						$.post(app_path_webroot+'Design/stop_actions.php?pid='+pid, { field_name: field_name, action: 'save', codes: codes.join(',') }, function(data){
							if (data != '0') {
								$('#stop_action_popup').dialog('close');
								// Reload whole table with new values (easy way)
								reloadDesignTable(getParameterByName('page'));
							} else {
								alert(woops);
							}
						});
					}
				}
			});		
		} else {
			alert(woops);
		}
	});					
}

// Update the Advanced Branching Syntax with drag-n-drop fields
function updateAdvBranchingBox() {
	var advBranching = '';
	var operator = $('input:radio[name=brOper]:checked').val();
	var thisVal = '';
	var selectVal = '';
	$('#dropZone1 li').each(function(){
		var thisString = $(this).attr('val');
		// For text fields, add the user-defined operator and value
		if ($(this).children('input').length) {
			thisString = thisString.substring(0,thisString.indexOf("= "+langOD0));
			selectVal = $(this).children('select').val();
			// If using > or <, then don't add quotes around it
			if (selectVal.indexOf(">") > -1 || selectVal.indexOf("<") > -1) { 
				thisVal = $(this).children('input').val();
			} else {
				thisVal = "'"+$(this).children('input').val()+"'";
			}
			// Add to string
			thisString += selectVal+' '+thisVal;
		}
		// Add and/or first
		if (advBranching != '') {
			advBranching += ' '+operator+' ';
		}
		// Now add equal and value
		advBranching += thisString;
	});
	$('#advBranchingBox').val(advBranching);
	setTimeout(function(){
		$('#advBranchingBox').effect('highlight', { }, 2000);
	},100);
}

// Open the dialog for Logic Builder
function openLogicBuilder(field_name) {
	showProgress(1);
	$.post(app_path_webroot+'Design/branching_logic_builder.php?pid='+pid, { form_name: form_name, field_name: field_name, action: 'view' }, function(data){
		if (data != '0') {
			showProgress(0,1);
			// Add dialog content
			$('#logic_builder').html(data);
			$('#logic_builder').dialog({ bgiframe: true, modal: true, width: 750, open: function(){fitDialog(this)}, 
				buttons: {
					Cancel: function() { $(this).dialog('close'); },
					Save: function() {
						$('#branching_help').dialog('close');
						// Do quick format checking of branching logic for any possible errors
						$('#advBranchingBox').val(trim($('#advBranchingBox').val()));
						var brStr = $('#advBranchingBox').val();
						var branchingErrorsExist = checkBranchingCalcErrors(brStr,true,true);
						if (branchingErrorsExist) {
							return false;
						}
						// Validate fields and save it
						$.post(app_path_webroot+'Design/branching_logic_builder.php?pid='+pid, { branching_logic: $('#advBranchingBox').val(), field_name: field_name, action: 'save' }, function(data){
							if (data.length > 1) {
								simpleDialog(data, langOD13);
							} else if (data == '1' || data == '2' || data == '3' || data == '4') {
								$('#logic_builder').dialog('close');
								$('#bl-label_'+field_name).css('visibility',($('#advBranchingBox').val().length>0 ? 'visible' : 'hidden')); // Show/hide 'branching logic exits' label
								highlightTable('design-'+field_name,2000); // Highlight row
								// If returned 2 or 4, then OK but display msg about disabling auto-question numbering
								if (data == '2' || data == '4') simpleDialog(disabledAutoQuesNumMsg);
								// If returned 3, then OK but display msg to super user that errors existed
								if ((data == '3' || data == '4') && super_user) simpleDialog(langOD49, langOD13);
							} else {
								alert(woops);
							}
						});
					}
				}
			});
			// Enable drag-n-drop
			$('.dragrow').draggable({ helper: 'clone', cursor: 'move', stack: '.dragrow' });		
			//$('.dragrow').bind('dragstart', function(event, ui) {
				// $(ui.helper).addClass("blue");
				// $(this).addClass("blue");
			//});
			$('#dropZone1').droppable({
				drop: function(event, ui) {
					// $(ui.draggable).addClass("darkgreen");
					var innerDisp = $(ui.draggable).html();
					//If for a "text/slider" field, give drop-down and text box to allow user to define value
					if (innerDisp.indexOf("= "+langOD0) > 0) {
						innerDisp = innerDisp.substring(0,innerDisp.indexOf("= "+langOD0));
						innerDisp += ' <select onchange="updateAdvBranchingBox();"><option value=">"> \> </option><option value=">="> \>= </option><option value="=" selected> = </option>'
								  +  '<option value="<="> \<= </option><option value="<"> \< </option></select>'
								  +  ' <input onchange="updateAdvBranchingBox();" type="text" size="5">';
					}				
					$(this).append('<li class="brDrag" style="display:block;" val="'+$(ui.draggable).attr('val')+'">'+innerDisp
								 + ' <a href="javascript:;"><img onclick=\'$(this.parentNode.parentNode).remove();setTimeout(function(){updateAdvBranchingBox()},10);\' class="imgfix" src="'+app_path_images+'cross.png"></a></li>'); 
					// Loop through all choices that have been dragged and build advanced logic syntax
					updateAdvBranchingBox();
				}
			});
			// Now that dialog is loaded, hide/show the choice set as chosen
			chooseBranchType($('input:radio[name=optionBranchType]:checked').val(),false);
		} else {
			showProgress(0,1);
			alert(woops);
		}
	});
}

// User chooses type of branching building option to use, so fade/disable the other option
function chooseBranchType(val,do_highlight) {
	if (val == 'advanced') {
		// Chose advanced syntax
		$('#logic_builder_advanced').fadeTo(0, 1);
		if (do_highlight) $('#logic_builder_advanced').effect('highlight', {}, 1000);
		$('#logic_builder_drag').fadeTo(0, 0.3);
		$('#advBranchingBox').prop('readonly',false);
		$('#advBranchingBox').removeAttr('readonly');
		$('input:radio[name=brOper]').prop('disabled',true);
		$('.dragrow').draggable("option", "disabled", true);
		$('#brFormSelect').prop('disabled',true);
		$('#dropZone1').html('');
		$('#linkClearAdv').css('visibility','visible');
		$('#linkClearDrag').css('visibility','hidden');
	} else {
		// Chose drag-n-drop
		if (convertAdvBranching()) {
			$('#logic_builder_drag').fadeTo(0, 1);
			if (do_highlight) $('#logic_builder_drag').effect('highlight', {}, 1000);
			$('#logic_builder_advanced').fadeTo(0, 0.3);
			$('#advBranchingBox').prop('readonly',true);
			$('input:radio[name=brOper]').prop('disabled',false);
			$('input:radio[name=brOper]').removeAttr('disabled');
			$('.dragrow').draggable("option", "disabled", false);
			$('#brFormSelect').prop('disabled',false);
			$('#brFormSelect').removeAttr('disabled');
			$('#linkClearAdv').css('visibility','hidden');
			$('#linkClearDrag').css('visibility','visible');
		} else {
			// Could not convert from advanced. Give error msg and reselect advanced.
			$('input:radio[name=optionBranchType]').each(function(){
				if ($(this).val() == 'advanced') {
					$(this).prop('checked',true);
				}
			});
			chooseBranchType('advanced',false);
			simpleDialog(langOD16, langOD15);
		}
	}
}

// Convert the advanced branching syntax logic to the drag-n-drop version (might not be compatible)
function convertAdvBranching() {
	$('#advBranchingBox').val( trim($('#advBranchingBox').val()) );
	var logic = $('#advBranchingBox').val();
	// If no logic is defined
	if (logic.length < 1) {
		$('#dropZone1').html('');
		return true;
	}
	// Do basic syntax check first
	if (checkBranchingCalcErrors(logic,false,true)) {
		return false;
	}
	// Check for instances of AND and OR
	var orCount  = logic.split(' or ').length-1;
	var andCount = logic.split(' and ').length-1;
	if (orCount > 0 && andCount > 0) {
		return false;
	}
	// Remove any parentheses inside square brackets so we can check logic for any parentheses (can't have them)
	var logicNoParen = logic, haveLeftSquare = false, haveRightParen = false, leftSquare = 0, bracketField;
	for (var i=0; i<logic.length; i++) {
		if (!haveLeftSquare) {
			if (logic.substr(i,1) == '[') {
				haveLeftSquare = true;
				leftSquare = i;
			}
		} else if (logic.substr(i,1) == ']') {
			// Check if parentheses are inside square brackets
			bracketField = logic.substring(leftSquare,i+1);
			if ((bracketField.split('(').length-1) == 1 && (bracketField.split(')').length-1) == 1) {
				logicNoParen = logicNoParen.replace(/\(/g,'').replace(/\)/g,'');
			}
			// Reset
			haveLeftSquare = false;
		}
	}
	if ((logicNoParen.split('(').length-1) > 0 && (logicNoParen.split(')').length-1) > 0) {
		return false;
	}
	// Go through list of possible choices and make sure all options match
	var allLogicChoices = new Array();
	var allLogicLabelChoices = new Array();
	var i=0;
	$('#nameList li').each(function(){ // Put all <li> elements into an array first for speed
		allLogicChoices[i] = $(this).attr('val');
		allLogicLabelChoices[i] = $(this).text();
		i++;
	});
	// Set operator and check correct operator radio button
	if (orCount > 0) {
		var operator = ' or ';
		$('#brOperOr').prop('checked',true);
	} else if (andCount > 0) {
		var operator = ' and ';
		$('#brOperAnd').prop('checked',true);
	} else {
		var operator = ' and '; //default
	}
	var logicArray = logic.split(operator);
	var thisLogic, foundLogicLabel, foundMatch, thisLogicDC;
	for (var i=0; i<logicArray.length; i++) { 
		// Trim syntax values and trade double quote for single quote and put spaces before and after = sign
		thisLogic = trim(logicArray[i].replace(/"/g,"'").replace("]='","] = '").replace("]=","] =").replace("] ='","] = '"));
		// Loop through all available logic choices to choose from
		var foundMatch = false;
		for (var k=0; k<allLogicChoices.length; k++) {
			if (!foundMatch) {
				if (allLogicChoices[k] == thisLogic) {
					// Find literal match first
					foundMatch = true;
					foundLogicLabel = allLogicLabelChoices[k];
				} else {
					// Try replacing strings so we can append " = (define criteria)" and match it with that string appended
					thisLogicDC = thisLogic.replace(" <= "," = ").replace(" >= "," = ").replace(" < "," = ").replace(" > "," = ");
					thisLogicDC = thisLogicDC.substring(0,thisLogicDC.indexOf(" = ")) + " = "+langOD0;
					if (allLogicChoices[k] == thisLogicDC) {
						foundMatch = true;
						foundLogicLabel = thisLogic.replace(/\[/g,"").replace(/\]/g,"");
					}
				}
			}
		}
		// Did we find it? If not, exit with error.
		if (foundMatch) {
			$('#dropZone1').append('<li class="brDrag" style="display:block;" val="'+thisLogic+'">'+foundLogicLabel
								 + ' <a href="javascript:;"><img onclick=\'$(this.parentNode.parentNode).remove();setTimeout(function(){updateAdvBranchingBox()},10);\' class="imgfix" src="'+app_path_images+'cross.png"></a></li>'); 
		} else {
			return false;
		}
	}		
	// No issues with conversion
	return true;
}

// If form drop-down is displayed in branching logic builder, show only fields for selected form
function displayBranchingFormFields(ob) {
	var form = ob.value;
	$('#nameList li').hide();
	$('#nameList li.br-frm-'+form).show().effect('highlight',{},2500);
}

// Do quick check if branching logic errors exist in string (not very extensive)
function checkBranchingCalcErrors(brStr,display_alert,isBranching) {
	var brErr = false;
	var msg = isBranching ? "Branching logic syntax error(s):\n" : "Syntax error(s) exist in the calculation equation:\n";
	if (display_alert == null) display_alert = false;
	if (brStr.length > 0) {
		// Check symmetry of "
		if ((brStr.split('"').length - 1)%2 > 0) {
			msg += "- Odd number of double quotes exist\n";
			brErr = true;
		}
		// Check symmetry of '
		if ((brStr.split("'").length - 1)%2 > 0) {
			msg += "- Odd number of single quotes exist\n";
			brErr = true;
		}
		// Check symmetry of [ with ]
		if (brStr.split("[").length != brStr.split("]").length) {
			msg += "- Square bracket is missing\n";
			brErr = true;
		}
		// Check symmetry of ( with )
		if (brStr.split("(").length != brStr.split(")").length) {
			msg += "- Parenthesis is missing\n";
			brErr = true;
		}
	}
	// If errors exist, stop and show message
	if (brErr && display_alert) {
		return !alert(msg+"\nYou must fix all errors listed before you can continue.");
	}
	return brErr;
}

function checkMinMaxVal() {
	if (oldValType != newValType && newValType.substring(0,4) == 'date' && oldValType.substring(0,4) == 'date') {
		// Convert date[time][seconds] to other date format for min/max
		$('#val_min').val( trim($('#val_min').val()) );
		$('#val_max').val( trim($('#val_max').val()) );
		var min = $('#val_min').val();
		var max = $('#val_max').val();
		var minOkay = true;
		var maxOkay = true;
		if (min.length > 0) {
			minOkay = convertDateFormat(min,oldValType,newValType,'val_min');
			if (!minOkay) $('#val_min').blur();
		}
		if (minOkay && max.length > 0) {
			maxOkay = convertDateFormat(max,oldValType,newValType,'val_max');
			if (!maxOkay) $('#val_max').blur();
		}
	}
	oldValType = newValType;
	preCheckValType();
}

function convertDateFormat(val,convertFrom,convertTo,id) {
	// Split into time and date (regardless of which date format used)
	var thisdatetime = val.split(' ');
	var thisdate = thisdatetime[0];
	var thistime = (thisdatetime[1] == null) ? '' : thisdatetime[1];
	// If value has no dashes, then it's not in the right format, so force onblur to alert user.
	if (thisdate.split('-').length != 3) {
		return false;
	}
	// Split the date into components
	var dateparts = thisdate.split('-');
	if (/_ymd/.test(convertFrom)) {
		var mm = dateparts[1];
		var dd = dateparts[2];
		var yyyy = dateparts[0];
	} else if (/_mdy/.test(convertFrom)) {
		var mm = dateparts[0];
		var dd = dateparts[1];
		var yyyy = dateparts[2];
	} else if (/_dmy/.test(convertFrom)) {
		var mm = dateparts[1];
		var dd = dateparts[0];
		var yyyy = dateparts[2];
	}
	// Put the date back together in the new format		
	if (/_ymd/.test(convertTo)) {
		thisdate = yyyy+"-"+mm+"-"+dd;
	} else if (/_mdy/.test(convertTo)) {
		thisdate = mm+"-"+dd+"-"+yyyy;
	} else if (/_dmy/.test(convertTo)) {
		thisdate = dd+"-"+mm+"-"+yyyy;
	}
	// Extend the time component into full hh:mm:ss format (even if a date field -> 00:00:00)
	if (thistime.split(':').length == 2) {
		thistime += ':00';
	} else if (thistime == '') {
		thistime = '00:00:00';
	}
	// Now trim down the time component based on which format we're converting to
	if (/datetime_/.test(convertTo) && !/datetime_seconds/.test(convertTo)) {
		thistime = thistime.substring(0,5);
	} else if (/date_/.test(convertTo)) {
		thistime = '';
	}
	// Append time, if exists
	val = trim(thisdate+" "+thistime);
	// Set new value
	$('#'+id).val(val);		
	return true;	
}

function preCheckValType() {		
	$('#val_type').bind('click select', function(){	
		oldValType = $(this).val();	
		$('#val_type').unbind();
		$('#val_type').bind('change', function(){
			newValType = $(this).val();	
			checkMinMaxVal();
		});
	})
}
//Insert row into Draggable table
function insertRow(tblId, current_field, edit_question, is_last, moveToRowAfter, section_header, delete_row) {
		
	// Remove easter egg field types from drop-down, if field was an easter egg field type
	var selectbox = document.getElementById('field_type');
	for (var i=selectbox.options.length-1; i>=0; i--) {
		if (selectbox.options[i].value == 'sql' || selectbox.options[i].value == 'advcheckbox') {
			setTimeout("document.getElementById('field_type').remove("+i+");",10);
		}
	}
	
	var txtIndex = document.getElementById('this_sq_id').value;
	if (section_header) {
		if (edit_question) {
			current_field = document.getElementById('sq_id').value;
			txtIndex = current_field + '-sh';
		} else {
			current_field = document.getElementById('this_sq_id').value;
			txtIndex = current_field;
		}
	}
	var tbl = document.getElementById(tblId);
	var rows = tbl.tBodies[0].rows; //getElementsByTagName("tr")	
	
	//Remove a table row, if needed
	// if (deleteRowIndex != null) {
		// for (var i=0; i<rows.length; i++) {
			// if (rows[i].getAttribute("id") == delete_row+"-tr") {
				// document.getElementById('draggable').deleteRow(i);
			// }
		// }		
	// }
	
	//Determine node index for inserting into table
	if (is_last) {
		//Add as last at very bottom
		var rowIndex = rows.length-1;
	} else {
		//Get index somewhere in middle of table
		for (var i=0; i<rows.length; i++) {
			if (rows[i].getAttribute("id") == txtIndex+"-tr") {
				var rowIndex = i;
			}
		}
		//If flag is set, place the new row after the original (rather than before it)
		if (moveToRowAfter) rowIndex++;
	}
	if (document.getElementById('add_form_name') != null && $('#add_form_name').val() != '') {
		window.location.href = app_path_webroot+"Design/online_designer.php?pid="+pid+"&page="+form_name;
	} else {
		$('#add_form_name').val('');
	}
	//Add cell in row. Obtain html to insert into table for the current field being added/edited.	
	$.get(app_path_webroot+"Design/online_designer_render_fields.php", { pid: pid, page: form_name, field_name: current_field, edit_question: edit_question, section_header: section_header },
		function(data) {
			//If editing existing question, replace it with itself in table
			if (edit_question) {
				document.getElementById('draggable').deleteRow(rowIndex);
			}
			//Add new row
			if (section_header) current_field += '-sh';
			var newRow = tbl.insertRow(rowIndex);
			newRow.setAttribute("id",current_field+"-tr");
			newRow.setAttribute("sq_id",current_field);
			var cell = document.createElement("td");
			cell.innerHTML = "<td>" + data + "</td>";	
			newRow.appendChild(cell);
			//Reset and close popup
			resetAddQuesForm();
			// Initialize all jQuery widgets, etc.
			initWidgets();
			//Highlight table row for emphasis of changes
			if (section_header == 0) highlightTable('design-'+current_field,2000);
			//Set table as draggable again
			AddTableDrag();
		}
	);
}

// Show/hide validation min/max when editing field via AJAX
function hide_val_minmax(ob) {
	var this_val = $(ob).val();
	if (this_val != null) {
		var data_type = $('#val_type option:selected').attr('datatype');
		var minMaxValTypes = new Array('integer', 'number', 'time', 'date', 'datetime', 'datetime_seconds');
		if (in_array(data_type, minMaxValTypes)) {
			document.getElementById("div_val_minmax").style.display = "";		
			// Change onblur depending on if date, time, or number
			document.getElementById('val_min').setAttribute("onblur","redcap_validate(this,'','','hard','"+this_val+"',1);");
			document.getElementById('val_max').setAttribute("onblur","redcap_validate(this,'','','hard','"+this_val+"',1);");
		} else {
			document.getElementById("div_val_minmax").style.display = "none";
		}
	} else {
		document.getElementById("div_val_minmax").style.display = "none";
	}
}

//Open Add Question box
function openAddQuesForm(sq_id,question_type,section_header) {
	//Only super users can add/edit "sql" fields
	if (!super_user && question_type == 'sql') {
		simpleDialog(langOD18, langOD17);
		return;
	}
	
	//Reset the form before we open it
	resetAddQuesForm();
	
	// In case someone changes a Section Header to a normal field, note this in a hidden field
	$('#wasSectionHeader').val(section_header);
	
	//Open Add Question form
	if (question_type == "" && section_header == 0) {
		document.getElementById('sq_id').value = "";
		document.getElementById("field_name").removeAttribute("readonly");
		// Add SQL field type for super users
		if (super_user) {
			if ($('#field_type option[value="sql"]').length < 1) $('#field_type').append('<option value="sql">'+langOD19+'</option>');
		}
		// Make popup visible
		openAddQuesFormVisible(sq_id);
		// If we're adding a field before or after a SH, then remove SH as an option in the drop-down Field Type list
		if (sq_id.indexOf('-sh') > -1 || $('#'+sq_id+'-sh-tr').length) {
			if ($('#field_type').val() == 'section_header') $('#field_type').val('');		
			$('#field_type option[value="section_header"]').hide();
		}
		// For non-obvious reasons, Firefox 4 will sometimes leave old text inside the textarea boxes in the dialog pop-up when opened, 
		// so clear out after it's visible just to be safe.
		document.getElementById('field_label').value = '';
		if (document.getElementById('element_enum') != null) document.getElementById('element_enum').value = '';
		// Customize pop-up window
		$('#div_add_field').dialog('option', 'title', '<span style="color:#800000;font-size:15px;">'+addNewFieldMsg+'</span>');
		if ($('#field_type').val().length < 1) {
			$('#div_add_field').dialog("widget").find(".ui-dialog-buttonpane").hide();
		} else {
			$('#div_add_field').dialog("widget").find(".ui-dialog-buttonpane").show();
		}
		
	//Pre-fill form if editing question
	} else {
	
		// If field types "sql", "advcheckbox" (i.e. Easter eggs) are used for this field, 
		// add it specially to the drop-down, but do not show otherwise.
		if (question_type == 'sql' || super_user) {
			if ($('#field_type option[value="sql"]').length < 1) $('#field_type').append('<option value="sql">'+langOD19+'</option>');
		} else if (question_type == 'advcheckbox') {
			if ($('#field_type option[value="advcheckbox"]').length < 1) $('#field_type').append('<option value="advcheckbox">'+langOD20+'</option>');
		}
		// Show the progress bar
		showProgress(1);
		// Set sq_id and field type
		document.getElementById('sq_id').value = sq_id;
		document.getElementById('field_type').value = question_type;
		// Call function do display/hide items in pop-up based on field type
		selectQuesType();
		// AJAX call to get question values for pre-filling
		$.get(app_path_webroot+"Design/edit_field_prefill.php", { pid: pid, field_name: sq_id },
			function(data) {
				//Eval the javascript passed from AJAX and pre-fill form
				eval(data);
				//If error occurs, stop here
				if (typeof ques == 'undefined') {
					// Close progress
					$('#fade').dialog('destroy');
					document.getElementById('working').style.display = 'none';
					return;
				}
				// Set field type drop-down vlaue
				if ((question_type == "radio" || question_type == "checkbox") && ques['grid_name'] != '') {		
					// If is a Matrix formatted checkbox or radio, set field type drop-down as "in a Matrix/Grid"		
					$('#dd_'+question_type+'grid').attr('selected',true);
					// Call this function again to now catch any Matrix specific setup
					selectQuesType();
				}				
				// Remove all options in matrix group drop-down (except the first blank option)
				$('#grid_name_dd option').each(function(){
					if ($(this).val().length > 0) {
						$(this).remove();
					}
				});
				// Set matrix group name drop-down options (from adjacent fields)
				var grid_names = ques['adjacent_grid_names'].split(',');
				for (var i=0; i<grid_names.length; i++) {	
					$('#grid_name_dd').append(new Option(grid_names[i],grid_names[i]));
				}
				// Set value for either matrix group drop-down or input field (if field's group name exists in drop-down, then select drop-down)
				// if (ques['grid_name'] != '') {
					// $('#grid_name_text').val(ques['grid_name']);
				// }
				// matrixGroupNameTextBlur();
				// Value DOM elements from AJAX response values
				document.getElementById('field_name').value = ques['field_name'];
				document.getElementById('field_note').value = ques['element_note'];				
				document.getElementById('field_req').value = ques['field_req'];
				document.getElementById('field_phi').value = ques['field_phi'];
				document.getElementById('custom_alignment').value = ques['custom_alignment'];
				// Clean-up: Loop through all hidden validation types (listed as not 'visible' from db table) and hide each UNLESS field has this validation (Easter Egg)
				for (i=0;i<valTypesHidden.length;i++) {
					var thisHiddenValType = $('#val_type option[value="'+valTypesHidden[i]+'"]');
					if (valTypesHidden[i].length > 0 && thisHiddenValType.length > 0) {
						thisHiddenValType.remove();
					}
				}
				// Add hidden validation type to drop-down, if applicable
				if (question_type == 'text' && ques['element_validation_type'] != '') {
					if (in_array(ques['element_validation_type'],valTypesHidden)) {
						$('#val_type').append('<option value="'+ques['element_validation_type']+'">'+ques['element_validation_type']+'</option>');
						$('#val_type').val(ques['element_validation_type']);
					}
				}
				//
				if (document.getElementById('question_num') != null) document.getElementById('question_num').value = ques['question_num'];
				if (question_type == "slider") {
					document.getElementById('slider_label_left').value = ques['slider_label_left'];				
					document.getElementById('slider_label_middle').value = ques['slider_label_middle'];
					document.getElementById('slider_label_right').value = ques['slider_label_right'];
					document.getElementById('slider_display_value').checked = (ques['element_validation_type'] == 'number');
				} else {
					if (ques['element_validation_type'] == 'float') {
						ques['element_validation_type'] = 'number';
					} else if (ques['element_validation_type'] == 'int') {
						ques['element_validation_type'] = 'integer';
					}
					document.getElementById('val_type').value = ques['element_validation_type'];				
					document.getElementById('val_min').value = ques['element_validation_min'];
					document.getElementById('val_max').value = ques['element_validation_max'];
				}			
				// If has file attachment
				if (question_type == "descriptive" && ques['edoc_id'].length > 0) {				
					$('#edoc_id').val(ques['edoc_id']);
					$('#div_attach_upload_link').hide();
					$('#div_attach_download_link').show();
					if (ques['attach_download_link'] != null) {
						$('#attach_download_link').html(ques['attach_download_link']);
						checkAttachImg(ques['attach_download_link'],ques['edoc_display_img']);
					}
				}
				// If a section header, close out all other fields except Label
				if (section_header) {
					if (document.getElementById('question_num') != null) document.getElementById('question_num').value = '';
					document.getElementById('field_type').value = "section_header";
					selectQuesType();
					document.getElementById('field_name').value = '';
				}
				// Determine if need to show Min/Max validation text boxes
				hide_val_minmax($('#val_type'));
				// If field is the Primary Key field, then disable certain attributes in Edit Field pop-up
				if (document.getElementById('field_name').value == table_pk) {
					// Disable certain attributes
					$('#field_type').val('text').prop('disabled', true);
					document.getElementById('div_field_req').style.display='none';
					document.getElementById('div_branching').style.display='none';
					document.getElementById('div_field_note').style.display='none';
					document.getElementById('div_custom_alignment').style.display='none';
					document.getElementById('div_pk_field_info').style.display='block';					
					// If record auto-numbering is enabled, then disable validation drop-down
					if (auto_inc_set) $('#val_type').val('').prop('disabled', true);
				}
				// Close progress
				$('#fade').dialog('destroy');
				document.getElementById('working').style.display = 'none';
				//Open Edit Question form			
				if (status != 0) {
					document.getElementById("field_name").setAttribute("readonly", "true"); 
				} else {
					document.getElementById("field_name").removeAttribute("readonly");
				}
				// Open dialog
				openAddQuesFormVisible(sq_id);
				$('#div_add_field').dialog('option', 'title', '<span style="color:#800000;font-size:15px;">'+editFieldMsg+'</span>');
				// For non-obvious reasons, Firefox 4 will sometimes clear out the textarea boxes in the dialog pop-up when opened, 
				// so pre-fill them after it's visible just to be safe.
				if (section_header) {
					document.getElementById('field_label').value = ques['element_preceding_header'].replace(/<br>/gi,"\n");				
				} else {
					document.getElementById('field_label').value = ques['element_label'].replace(/<br>/gi,"\n");
				}
				if (question_type == "slider") {		
					document.getElementById('element_enum').value = '';
				} else {		
					document.getElementById('element_enum').value = ques['element_enum'];
					// Add raw coded enums to hidden field
					document.getElementById('existing_enum').value = ques['existing_enum'];
				}
				// Run the code to select radios after dialog is set because IE will not set radios correctly when done beforehand
				if (ques['field_req'] == '1') {
					document.getElementById('field_req1').checked = true;
					document.getElementById('field_req0').checked = false;
				} else {
					document.getElementById('field_req0').checked = true;
					document.getElementById('field_req1').checked = false;
				}
				if (ques['field_phi'] == '1') {
					document.getElementById('field_phi1').checked = true;
					document.getElementById('field_phi0').checked = false;
				} else {
					document.getElementById('field_phi0').checked = true;
					document.getElementById('field_phi1').checked = false;
				}
				//Disable field_name field if appropriate
				if (status != 0) {
					$.get(app_path_webroot+"Design/check_field_disable.php", { pid: pid, field_name: $('#field_name').val() },
						function(data) {
							if (data == '0') {
								document.getElementById("field_name").removeAttribute("readonly");
							}
						}
					);
				}
			}
		);		
	}
}

//Make Add/Edit Question form visible
function openAddQuesFormVisible(sq_id) {	
	document.getElementById("this_sq_id").value = sq_id;
	$('#div_add_field').dialog({ bgiframe: true, modal: true, width: 750, open: function(){fitDialog(this)},
		buttons: [
			{ text: langCancel, click: function () { $(this).dialog('close'); } },
			{ text: langSave, click: function () {
				// Do quick logic check for calc fields
				if ($('#field_type').val() == 'calc') {
					var eq = $('#element_enum').val();
					var branchingCalcErrorsExist = checkBranchingCalcErrors(eq,true,false);
					if (branchingCalcErrorsExist) {
						return false;
					}
					var fldname = $('#field_name').val();
					// Now validate fields in the equation
					$.post(app_path_webroot+'Design/calculation_equation_validate.php?pid='+pid, { field: fldname, eq: eq }, function(data){
						if (data == '0') {
							alert(woops);
							setTimeout(function(){
								openAddQuesForm(fldname,'calc',0);
							},500);
						} else if (data == '2' && super_user) {
							// Saved the calc, although syntax errors exist, so give super user a message
							simpleDialog(langOD50,langOD21);
						} else if (data.length > 1) {
							// Give error message and reload the Edit Field dialog so they can fix it.
							// Since an error exists, remove calc equation via ajax to prevent users from injecting invalid syntax.
							simpleDialog(data,langOD21,null,null,"openAddQuesForm('"+fldname+"','calc',0);eraseCalcEqn('"+fldname+"');");
						}
					});
				}
				// Validate and fix any issues with MC fields' raw values for their choices
				if (checkEnumRawVal($('#element_enum'))) {
					// Save the field
					addEditFieldSave();
				}
			} }
		]
	})
	.dialog("widget").find(".ui-dialog-buttonpane button").eq(1).css({'font-weight':'bold', 'color':'#333'}).end();
}

// Remove calc equation via ajax to prevent users from injecting invalid syntax
function eraseCalcEqn(field) {
	setTimeout(function(){
		$.post(app_path_webroot+'Design/calculation_equation_validate.php?pid='+pid, { field: field, action: 'erase' }, function(data){ });
	},500);
}

// Do checking of values before adding/editing field on Online Form Editor
function addEditFieldSave() {
	if ($('#field_type').val() != '' && $('#field_type').val() != 'section_header' && $('#field_name').val().length < 1) {
		simpleDialog(langOD23);
		return false;
	}
	// Prevent section headers from having null value (will cause errors)
	if ($('#field_type').val() == 'section_header' && $('#field_label').val().length < 1) {
		$('#field_label').val(' ');
	}
	// Check if valid and unique variable name. Will submit form if no duplicates exist.
	checkFieldName($('#field_name').val(),true); 
}

// Reset values for Add New Field form
function resetAddQuesForm() {
	document.getElementById('field_label').value = '';
	document.getElementById('field_name').value = '';
	document.getElementById('field_note').value = '';
	if (document.getElementById('question_num') != null) document.getElementById('question_num').value = '';
	if (document.getElementById('element_enum') != null) document.getElementById('element_enum').value = '';
	if (document.getElementById('val_type') != null) document.getElementById('val_type').value = '';
	if (document.getElementById('val_min') != null) document.getElementById('val_min').value = '';
	if (document.getElementById('val_max') != null) document.getElementById('val_max').value = '';
	document.getElementById('field_phi0').checked = true;
	document.getElementById('field_phi1').checked = false;
	document.getElementById('field_phi').value = '';
	document.getElementById('field_req0').checked = true;
	document.getElementById('field_req1').checked = false;
	document.getElementById('field_req').value = '';	
	document.getElementById('edoc_id').value = '';
	document.getElementById('slider_label_left').value = '';	
	document.getElementById('slider_label_middle').value = '';
	document.getElementById('slider_label_right').value = '';
	document.getElementById('custom_alignment').value = '';
	document.getElementById('slider_display_value').checked = false;
	if (document.getElementById('grid_name_text') != null) document.getElementById('grid_name_text').value = '';
	if (document.getElementById('grid_name_dd') != null) {
		document.getElementById('grid_name_dd').value = '';
	}
	document.getElementById('existing_enum').value = '';
	//Other operations
	document.getElementById('div_val_minmax').style.display = 'none';
	//document.getElementById('addSubmitBtn').disabled = false;
	$('#div_attach_upload_link').show();
	$('#div_attach_download_link').hide();
	$('#attach_download_link').html('');
	enableAttachImgOption(0);
	enableAutoVarSuggest($('#field_name'), $('#field_label'));
	$('#div_add_field').dialog("widget").find(".ui-dialog-buttonpane").show();
	$('#div_add_field').dialog('destroy');
	// In case SH option was removed from Field Type drop-down, add it back
	$('#field_type option[value="section_header"]').show();
	// Show/hide divs and enable field type and validation type drop-downs if were disabled when editing PK field
	$('#field_type').prop('disabled', false);
	$('#val_type').prop('disabled', false);
	document.getElementById('div_pk_field_info').style.display='none';	
	document.getElementById('div_field_req').style.display='block';
	document.getElementById('div_field_phi').style.display='block';
	document.getElementById('div_field_note').style.display='block';
	document.getElementById('div_custom_alignment').style.display='block';
	document.getElementById('div_branching').style.display='block';
}
				
// Auto fill variable name, if empty, based upon label text.
function enableAutoVarSuggest(field_name_ob, field_label_ob) {
	// Only enable if checkbox is set to allow it (based on project-level value)
	if ($('#auto_variable_naming').prop('checked')) {
		// Enable auto var naming (but not if field already exists)
		field_label_ob.bind('click focus keyup',function(){
			if (field_name_ob.attr('id') == 'field_name' && $('#sq_id').val().length < 1) { // i.e. if we are adding a new field
				// Field name input
				field_name_ob.val(convertLabelToVariable($(this).val()));
			} else if (field_name_ob.attr('id') != 'field_name') {
				// Matrix field name input
				field_name_ob.val(convertLabelToVariable($(this).val()));
			}
		});
	} else {
		// Prevent auto variable from overwriting variable name given
		field_label_ob.unbind();
	}
}

// When using auto-variable naming, converts field label into variable
function convertLabelToVariable(field_label) {
	var field_name = filterFieldName(trim(field_label));
	// Force 26 chars or less (and then remove any underscores on end)
	if (field_name.length > 26) {						
		field_name = filterFieldName(field_name.substr(0,26));
	}
	return field_name;
}

//Select question type from Add/Edit Field box
function selectQuesType() {	
	if (document.getElementById('field_type').value.length < 1) {
		// If no field type is selected
		document.getElementById('quesTextDiv').style.visibility='hidden';
		$('#div_add_field').dialog("widget").find(".ui-dialog-buttonpane").hide();
		document.getElementById('slider_labels').style.display='none';
		document.getElementById('righthand_fields').style.visibility='hidden';
	} else {
		// If field type has been selected
		if (document.getElementById('div_question_num') != null) document.getElementById('div_question_num').style.display = 'block';
		document.getElementById('quesTextDiv').style.visibility='visible';
		$('#div_add_field').dialog("widget").find(".ui-dialog-buttonpane").show();
		document.getElementById('righthand_fields').style.visibility='visible';		
		document.getElementById('div_field_req').style.display='block';
		document.getElementById('div_field_phi').style.display='block';
		document.getElementById('div_field_note').style.display='block';
		document.getElementById('div_custom_alignment').style.display='block';
		document.getElementById('div_val_type').style.display='none';
		document.getElementById('slider_labels').style.display='none';
		document.getElementById('div_element_enum').style.display='none';
		$('#div_img_display_options').fadeTo(0,0.3,function(){
			document.getElementById('div_attachment').style.display='none';
		});		
		document.getElementById('field_req0').disabled = false;
		document.getElementById('field_req1').disabled = false;
		$('#req_disable_text').css('visibility','hidden');
		if (document.getElementById('field_type').value == 'text') {
			document.getElementById('div_val_type').style.display='';
		} else if (document.getElementById('field_type').value == 'advcheckbox' || document.getElementById('field_type').value == 'sql' || document.getElementById('field_type').value == 'radio' || document.getElementById('field_type').value == 'select' || document.getElementById('field_type').value == 'calc' || document.getElementById('field_type').value == 'checkbox') {
			document.getElementById('div_val_type').style.display='none';
			document.getElementById('div_element_enum').style.display='';
			// Advcheckboxes cannot be required (since "unchecked" is technically a real value)
			if (document.getElementById('field_type').value == 'advcheckbox') {
				document.getElementById('field_req0').checked = false;
				document.getElementById('field_req1').checked = false;
				document.getElementById('field_req0').checked = true;
				document.getElementById('field_req0').disabled = true;
				document.getElementById('field_req1').disabled = true;
				$('#req_disable_text').css('visibility','visible');
			}
			// If a calc, change label above choices box
			if (document.getElementById('field_type').value == 'calc') {
				$('#choicebox-label-mc').css('display','none');
				$('#choicebox-label-sql').css('display','none');
				$('#choicebox-label-calc').css('display','block');
				$('#manualcode-label').css('display','none');
				$('#div_manual_code').css('display','none');
			} else if (document.getElementById('field_type').value == 'sql') {
				$('#choicebox-label-mc').css('display','none');
				$('#choicebox-label-sql').css('display','block');
				$('#choicebox-label-calc').css('display','none');
				$('#manualcode-label').css('display','none');
				$('#div_manual_code').css('display','none');
			} else {
				$('#choicebox-label-mc').css('display','block');
				$('#choicebox-label-sql').css('display','none');
				$('#choicebox-label-calc').css('display','none');
				$('#manualcode-label').css('display','block');
			}
		} else if (document.getElementById('field_type').value == 'section_header') {									
			document.getElementById('righthand_fields').style.visibility='hidden';
			if (document.getElementById('div_question_num') != null) document.getElementById('div_question_num').style.display = 'none';
		} else if (document.getElementById('field_type').value == 'slider') {									
			document.getElementById('slider_labels').style.display='block';
		} else if (document.getElementById('field_type').value == 'descriptive') {
			document.getElementById('div_field_req').style.display='none';
			document.getElementById('div_field_phi').style.display='none';
			document.getElementById('div_field_note').style.display='none';
			document.getElementById('div_attachment').style.display='block';
			document.getElementById('div_custom_alignment').style.display='none';
		}
		// Do special things for Matrix radios/checkboxes
		if (($('#field_type').val() == 'radio' || $('#field_type').val() == 'checkbox') && $('#field_type :selected').attr('grid') == '1') {
			// Matrix: Disable custom alignment drop-down
			$('#customalign_disable_text').css('visibility','visible');
			$('#custom_alignment').val('').prop('disabled',true);	
			$('#gridNameDiv').show();
		} else {
			// Non-matrix: Re-enable custom alignment drop-down and reset matrix group name
			$('#customalign_disable_text').css('visibility','hidden');
			$('#custom_alignment').prop('disabled',false);	
			$('#gridNameDiv').hide();	
			$('#grid_name_text').val('');
			$('#grid_name_dd').val('');
		}
	}
}

//Delete field from a form and remove row in Draggable table
function deleteField(this_field,section_header) {
	if (section_header) {
		simpleDialog(delSHMsg,delSHTitle,null,null,null,langCancel,"deleteFieldDo('"+this_field+"',"+section_header+");",langOD24);
	} else {
		simpleDialog(delFieldMsg+' "'+this_field+'"'+langQuestionMark,delFieldTitle,null,null,null,langCancel,"deleteFieldDo('"+this_field+"',"+section_header+");",langOD24);
	}
}

//Delete field from a form and remove row in Draggable table
function deleteFieldDo(this_field,section_header) {
	$.get(app_path_webroot+"Design/delete_field.php", { pid: pid, field_name: this_field, section_header: section_header, form_name: getParameterByName('page') },
		function(data) {
			var chkFldInCalcBranching = true;
			if (data == "1") { // Successfully deleted
				if (section_header) {
					highlightTable('design-'+this_field+'-sh',1500);
					setTimeout(function(){
						$('#'+this_field+'-sh-tr').remove();
					},1000);
				} else {
					highlightTable('design-'+this_field,1500);
					setTimeout(function(){
						$('#'+this_field+'-tr').remove();
					},1000);
				}
				AddTableDrag();
			} else if (data == "3") { // Field is last on page and/or field has section header. Reload table.
				reloadDesignTable(getParameterByName('page'));
			} else if (data == "2") { // All fields were deleted so redirect back to previous page
				alert(langOD37);
				window.location.href = app_path_webroot + "Design/online_designer.php?pid=" + pid;
			} else if (data == "4") { // Table_pk was deleted, so inform user of this
				highlightTable('design-'+this_field,1500);
				setTimeout(function(){
					$('#'+this_field+'-tr').remove();
				},1000);
				update_pk_msg(false,'field');
			} else if (data == "5") { // Field is last on page and has section header, which was then removed. Alert user of SH deletion and reload table.
				alert(langOD39);
				reloadDesignTable(getParameterByName('page'));
			} else if (data == "6") { // Field is being used by randomization. So prevent deletion.
				alert(langOD40);
				chkFldInCalcBranching = false;
			} else {
				alert(woops);
				chkFldInCalcBranching = false;
			}
			// Send AJAX request to check if the deleted field was in any calc equations or branching logic
			if (chkFldInCalcBranching && !section_header) {
				$.post(app_path_webroot+"Design/delete_field_check_calcbranch.php?pid="+pid, { field_name: this_field },function(data) {
					if (data != "0") {
						if (!$('#delFldChkCalcBranchPopup').length) $('body').append('<div id="delFldChkCalcBranchPopup" style="display:none;"></div>');
						$('#delFldChkCalcBranchPopup').html(data);
						$('#delFldChkCalcBranchPopup').dialog({ bgiframe: true, modal: true, width: 650, open: function(){fitDialog(this)}, 
							title: langOD41,
							buttons: {
								Close: function() { $(this).dialog('close'); }
							}
						});
					}
				});
			}
		}
	);
}

//Copy field from a form and add row in Draggable table
function copyField(this_field) {
	simpleDialog(langOD35+' "<b>'+this_field+'</b>"'+langQuestionMark+' '+langOD36,langOD33,null,null,null,langCancel,"copyFieldDo('"+this_field+"')",langOD34);
}
//Copy field from a form and add row in Draggable table
function copyFieldDo(this_field) {
	showProgress(1);
	$.get(app_path_webroot+"Design/copy_field.php", { pid: pid, field_name: this_field },
		function(data) {
			// If we're copying a matrix field, then reload whole table
			if ($('#'+this_field+'-tr table.matrixHdrs').length) {
				reloadDesignTable(getParameterByName('page'));
			} else {
				document.getElementById('this_sq_id').value = this_field; //Set up for insertRow() function
				var is_last = 0; //Is it last field in table? SET MANUALLY HERE - CHANGE LATER!!!
				insertRow("draggable", data, 0, is_last, 1, 0);
				AddTableDrag();
				showProgress(0);
			}
		}
	);
}

// Reloads table via AJAX on Online Form Editor page
function reloadDesignTable(form_name,js) {
	resetAddQuesForm();
	showProgress(1);
	$.get(app_path_webroot+'Design/online_designer_render_fields.php', { pid: pid, page: form_name, ordering: 1 },
		function(data) {
			$('#draggablecontainer_parent').html(data);			
			// Initialize all jQuery widgets, etc.
			initWidgets();
			showPkNoDisplayMsg();
			showProgress(0);
			//Set table as draggable again
			AddTableDrag();
			// Display tooltip popup if user attempts to move the Primary Key field
			enablePkFieldTooltipEtc();
			// Eval some Javascript if provided
			if (js != null) {
				eval(js);
			}
		}
	);
}

// Matrix group name text box validation (via regex)
function matrixGroupNameValidate(this_ob) {
	var ob = (this_ob == null) ? $('#div_add_field #grid_name_text') : $(this_ob);
	// Trim value first
	ob.val( trim(ob.val()) );
	// Regex
	var regex = /^[a-z0-9_]+$/g;
	return regex.test(ob.val());
}
	
// Matrix group name text box (action=mouseout,pageload)
function matrixGroupNameTextBlurError(ob,error) {
	if (error) {
		alert(matrixNameValErrMsg);
		ob.css({'background-color':'#FFB7BE','font-weight':'bold'});
		setTimeout(function(){ob.focus();},10);
		return false;
	} else {
		ob.css({'background-color':'#FFF','font-weight':'normal'});
		return true;
	}
}

// Delete an entire matrix group of fields
function deleteMatrix(current_field,grid_name) {
	// Remove "-sh" from field name (if has a section header)	
	if (current_field.indexOf("-sh") > -1) {
		current_field = current_field.substr(0, current_field.length-3);
	}
	simpleDialog(delMatrixMsg+' "'+grid_name+'"'+langQuestionMark+' '+delMatrixMsg2,delMatrixTitle,null,null,null,langCancel,"deleteMatrixDo('"+current_field+"','"+grid_name+"');",langDelete);
}

// Delete an entire matrix group of fields
function deleteMatrixDo(current_field,grid_name) {
	// Do ajax call to delete the whole matrix group
	$.post(app_path_webroot+'Design/delete_matrix.php?pid='+pid,{ field_name: current_field, grid_name: grid_name}, function(data){
		var json_data = jQuery.parseJSON(data);
		if (json_data.length < 1) {
			alert(woops);
			reloadDesignTable(getParameterByName('page'));	
		} else {
			// Highlight rows
			for (var i = 0; i < json_data.fields.length; i++) {
				highlightTable('design-'+json_data.fields[i],2000);
			}
			setTimeout(function(){					
				reloadDesignTable(getParameterByName('page'));	
			},1000);
			if (json_data.pk_changed == '1') update_pk_msg(false,'field');
		}		
	});
}

// Open dialog for adding matrix of fields (Online Designer)
function openAddMatrix(current_field,next_field) {
	// Set default value
	var sectionHeaderPreFill = '';
	
	if (next_field.indexOf("-sh") > -1) {
		// Remove "-sh" from field name (if adding before a section header)
		next_field = next_field.substr(0, next_field.length-3);
	} else if (next_field.indexOf("{") > -1) {
		// If adding field at end of form, set field name as blank
		next_field = '';
	} else if (current_field == '' && $('#'+next_field+'-sh-tr').length) {
		// Check if field
		sectionHeaderPreFill = trim($('#'+next_field+'-sh-tr').text());
	}
	if (current_field.indexOf("-sh") > -1) {
		// Remove "-sh" from field name (if adding before a section header)
		current_field = current_field.substr(0, current_field.length-3);
	} else if (current_field.indexOf("{") > -1) {
		// If adding field at end of form, set field name as blank
		current_field = '';
	}
	// Erase any values in the popup currently
	resetAddMatrixPopup(sectionHeaderPreFill);
	$('#sq_id').val(current_field);
	$('#this_sq_id').val(next_field);
	// EDIT EXISTING FIELD: If editing a field, then pre-load pop-up with existing values
	if (current_field != '') {
		// Ajax call to get existing matrix fields' values to pre-fill popup
		$.post(app_path_webroot+'Design/edit_matrix_prefill.php?pid='+pid,{ field_name: current_field}, function(data){
			var json_data = jQuery.parseJSON(data);
			if (json_data.length < 1) {
				alert(woops);
				return;
			}
			// Add set number of rows to popup
			addAnotherRowMatrixPopup(json_data.num_fields-1);
			// Preload dialog with values
			$('#element_enum_matrix').val(json_data.choices.replace(/\|/g,"\n"));
			$('#grid_name').val(json_data.grid_name);
			$('#old_grid_name').val(json_data.grid_name);
			$('#old_matrix_field_names').val(json_data.field_names.join(','));
			$('#field_type_matrix').val(json_data.field_type);
			$('#section_header_matrix').val(br2nl(json_data.section_header));
			// Loop through labels
			var counter = 0;
			$('.addFieldMatrixRowParent input.field_label_matrix').each(function(){
				$(this).val(json_data.field_labels[counter]);
				counter++;
			});
			// Loop through variable names
			var counter = 0;
			$('.addFieldMatrixRowParent input.field_name_matrix').each(function(){
				$(this).val(json_data.field_names[counter]);
				counter++;
			});
			// Loop through field reqs
			var counter = 0;
			$('.addFieldMatrixRowParent input.field_req_matrix').each(function(){
				$(this).prop('checked',(json_data.field_reqs[counter] == 1));
				counter++;
			});
			// Loop through ques nums
			var counter = 0;
			$('.addFieldMatrixRowParent input.field_quesnum_matrix').each(function(){
				$(this).val(json_data.question_nums[counter]);
				counter++;
			});
			// Now open dialog
			openAddMatrixPopup(current_field,next_field);
			$('#addMatrixPopup').dialog('option', 'title', '<img class="imgfix" src="'+app_path_images+'table__pencil.png"> <span style="color:#800000;font-size:15px;">'+editMatrixMsg+'</span>');
		});
	} 
	// ADDING NEW FIELD
	else {
		openAddMatrixPopup(current_field,next_field);
		$('#addMatrixPopup').dialog('option', 'title', '<img class="imgfix" src="'+app_path_images+'table.png"> <span style="color:#800000;font-size:15px;">'+addNewMatrixMsg+'</span>');
	}
}

// Open popup to move field to another location on form (or on another form)
function moveField(field,grid_name) {
	// Remove "-sh" from field name (if has a section header)	
	if (field.indexOf("-sh") > -1) {
		field = field.substr(0, field.length-3);
	}
	// Get dialog content via ajax
	$.post(app_path_webroot+'Design/move_field.php?pid='+pid,{ field: field, grid_name: grid_name, action: 'view' },function(data){
		var json_data = jQuery.parseJSON(data);
		if (json_data.length < 1) {
			alert(woops);
			return false;
		}
		// Add dialog content and set dialog title
		$('#move_field_popup').html(json_data.payload);
		// Open the "move field" dialog	
		$('#move_field_popup').dialog({ title: json_data.title, bgiframe: true, modal: true, width: 700, open: function(){fitDialog(this)},
			buttons: [
				{ text: langCancel, click: function () { $(this).dialog('close'); } },
				{ text: langOD42, click: function () { 
					// Make sure we have a field first
					if ($('#move_after_field').val() == '') {
						simpleDialog(pleaseSelectField);
						return;
					}
					// Save new position via ajax,
					$.post(app_path_webroot+'Design/move_field.php?pid='+pid,{ field: field, grid_name: grid_name, action: 'save', move_after_field: $('#move_after_field').val() },function(data){
						if (data == '0') {
							alert(woops);
						} else {
							simpleDialog(data,successfullyMovedMsg);
						}
						$('#move_field_popup').dialog("close");
						reloadDesignTable(getParameterByName('page'));
					});
				} }
			]
		});
	});
}

// Save the matrix group via ajax
function saveMatrixAjax(current_field,next_field) {

	// Trim and check all values on form
	$('#element_enum_matrix').val(trim($('#element_enum_matrix').val()));
	// Check if choices should be auto-coded
	if (!checkEnumRawVal($('#element_enum_matrix'))) return false;
	
	// Get values needed and send ajax request
	var form = getParameterByName('page');
	var sh = $('#section_header_matrix').val();
	var field_type = $('#field_type_matrix').val();
	var choices = $('#element_enum_matrix').val();
	var grid_name = $('#grid_name').val();
	var old_grid_name = $('#old_grid_name').val();
	var labels = '';
	var fields = '';
	var field_reqs = '';
	var ques_nums = '';
	var missing_labelvar = 0;
	$('.addFieldMatrixRowParent .addFieldMatrixRow').each(function(){
		var thislabel = trim($(this).find('.field_label_matrix:first').val());
		var thisvar = trim($(this).find('.field_name_matrix:first').val());
		var thisreq = $(this).find('.field_req_matrix:first').prop('checked') ? '1' : '0';
		var thisquesnum = $(this).find('.field_quesnum_matrix:first').length ? $(this).find('.field_quesnum_matrix:first').val() : '';
		if (thislabel.length < 1 || thisvar.length < 1) {
			missing_labelvar++;
		} else {
			labels += thislabel+"\n";
			fields += thisvar+"\n";
			field_reqs += thisreq+"\n";
			ques_nums += thisquesnum+"\n";
		}
	});
	// If any errors, then stop here
	if (field_type.length < 1) {
		simpleDialog(langOD43);
		return;
	}
	if (choices.length < 1) {
		simpleDialog(langOD44);
		return;
	}
	if (grid_name.length < 1) {
		simpleDialog(langOD45);
		return;
	}
	if (missing_labelvar > 0) {
		simpleDialog(langOD46);
		return;
	}
	// Check if new matrix is adopting an existing SH
	sectionHeaderAdopt = (current_field == '' && $('#'+next_field+'-sh-tr').length) ? next_field : '';
	// Save matrix via ajax
	$.post(app_path_webroot+'Design/edit_matrix.php?pid='+pid,{ sectionHeaderAdopt: sectionHeaderAdopt, ques_nums: ques_nums, 
		field_reqs: field_reqs, section_header: sh, field_type: field_type, current_field: current_field, next_field: next_field, 
		form: form, choices: choices, grid_name: grid_name, old_grid_name: old_grid_name, labels: labels, fields: fields,
		form_name: form_name }, function(data){
		if (data == '1' || data == '2' || data == '3') {
			$('#addMatrixPopup').dialog('close');
			if ($('#add_form_name').length) {
				// Reload page if just created this form
				window.location.href = app_path_webroot+"Design/online_designer.php?pid="+pid+"&page="+form;
			} else {
				// Reload table and highlight newly added rows
				var allfields = fields.split("\n");
				var js= '';
				for (var i = 0; i < allfields.length; i++) {
					js += "highlightTable('design-"+allfields[i]+"',2000);";
				}
				reloadDesignTable(form,js);
				// If changed PK field or disabled auto-question-numbering, give prompts
				if (data == '2') update_pk_msg(false,'field');
				else if (data == '3') alert(disabledAutoQuesNumMsg);
			}
		} else {
			alert(woops);
			reloadDesignTable(form);
		}
	});
}

// Make sure the field name doesn't conflict with others in the same matrix dialog popup (return false if duplication occurs)
function checkFieldUniqueInSameMatrix(ob) {
	var countDupl = 0;
	$('#addMatrixPopup input.field_name_matrix').each(function(){
		if (ob.val() == $(this).val()) countDupl++;
	});
	return (countDupl <= 1);
}

// Make sure ALL field names in a matrix group don't conflict with others (return false if duplication occurs).
// Return alert pop-up on first duplication.
function checkFieldUniqueInSameMatrixAll() {
	var duplErrorField = "";
	$('#addMatrixPopup .field_name_matrix').each(function(){					
		if (!checkFieldUniqueInSameMatrix($(this))) {
			duplErrorField = $(this);
			return false;
		}
	});
	if (duplErrorField != "") {				
		alert(duplVarMtxMsg2+' "'+duplErrorField.val()+'" '+duplVarMtxMsg);
		return false;
	}
	return true;
}

// Modify form order: Enable drag-n-drop on table
function enableMatrixFieldDrag() {
	// Unbind everything since we're likely setting drag-n-drop again
	$('table.addFieldMatrixRowParent').unbind();
	$('table.addFieldMatrixRowParent tr').unbind();
	// Only enable it if there is more than one row
	if ($("table.addFieldMatrixRowParent tr").length > 1) {
		// Set drag-n-drop
		$('table.addFieldMatrixRowParent').tableDnD({
			onDrop: function(table, row) {
				$(row).find('.field_name_matrix').effect('highlight',{ },1000);
				$(row).find('.field_label_matrix').effect('highlight',{ },1000);
				$(row).find('.field_quesnum_matrix').effect('highlight',{ },1000);
			},
			dragHandle: "dragHandle"
		});
		// Create mouseover image for drag-n-drop and enable button fading on row hover
		$("table.addFieldMatrixRowParent tr").hover(function() {
			$(this.cells[0]).css('background','#eee url("'+app_path_images+'updown.gif") no-repeat center');
			$(this.cells[0]).css('cursor','move');
		}, function() {
			$(this.cells[0]).css('background','');
			$(this.cells[0]).css('cursor','');
		});
	}
}


// Hide all rows in matrix field popup that have both label and variable inputs empty
function hideBlankMatrixFields() {
	var k;
	var numMatrixRows = $('#addMatrixPopup .addFieldMatrixRow').length;
	if (numMatrixRows == 1) return;
	for (k = numMatrixRows-1; k >= 0; k--) {
		if (trim($('#addMatrixPopup .field_name_matrix').eq(k).val()) == '' && trim($('#addMatrixPopup .field_label_matrix').eq(k).val()) == '') {
			// Remove this row
			$('#addMatrixPopup .addFieldMatrixRow').eq(k).remove();
			// If there's only one blank row left, then stop
			if ($('#addMatrixPopup .addFieldMatrixRow').length == 1) return;
		}
	}
}

// Open the Add Matrix dialog pop-up
function openAddMatrixPopup(current_field,next_field) {
	// Open the "add matrix" dialog	
	$('#addMatrixPopup').dialog({ bgiframe: true, modal: true, width: 800, open: function(){fitDialog(this)},
		buttons: [
			{ text: langCancel, click: function () { $(this).dialog('close'); } },
			{ text: langSave, click: function () {
				// Hide all rows that have both label and variable inputs empty
				hideBlankMatrixFields();
				// Make sure any field name doesn't conflict with others in the same matrix dialog popup
				if (!checkFieldUniqueInSameMatrixAll()) return false;				
				// Check the uniqueness of the matrix group name AND save the matrix (will be done inside that function)
				checkMatrixGroupName(true,current_field,next_field);
			} }
		]
	})
	.dialog("widget").find(".ui-dialog-buttonpane button").eq(1).css({'font-weight':'bold', 'color':'#333'}).end();
	// Enable field name unique check + auto var naming (if set) for EACH matrix row
	matrix_field_name_trigger();
	// Put temporarily focus on each variable name input to turn off auto-var naming if label has already been set
	$('#addMatrixPopup .field_name_matrix').each(function(){
		$(this).focus();
	});
	// Enable drag-n-drop on field rows
	enableMatrixFieldDrag();
	// Put cursor in first field label
	$('#addMatrixPopup .field_label_matrix:first').focus();
}

// Enable field name unique check + auto var naming (if set) for EACH matrix row
function matrix_field_name_trigger() {
	var field_name_ob, field_label_ob;
	// Count matrix rows
	var num_rows = $('#addMatrixPopup .field_label_matrix').length;
	// Loop through each matrix row and enable each
	for (var k = 0; k < num_rows; k++) {
		field_name_ob  = $('#addMatrixPopup .field_name_matrix').eq(k);
		field_label_ob = $('#addMatrixPopup .field_label_matrix').eq(k);
		// Add unique check trigger to field_name field
		field_name_trigger(field_name_ob, field_label_ob);
		// Enable auto-var naming (if set)
		enableAutoVarSuggest(field_name_ob, field_label_ob);
	}
}

// Reset all input values in Add Matrix dialog pop-up
function resetAddMatrixPopup(sectionHeaderPreFill) {
	$('#element_enum_matrix').val('');
	$('#grid_name').val('');
	$('#old_grid_name').val('');
	$('#old_matrix_field_names').val('');
	$('#field_type_matrix').val('radio');
	$('#section_header_matrix').val(sectionHeaderPreFill);
	$('.field_label_matrix').val('');
	$('.field_name_matrix').val('');
	var labelvar_row_html = $('.addFieldMatrixRowParent .addFieldMatrixRow:first').html();
	$('.addFieldMatrixRowParent').html("<tr class='addFieldMatrixRow'>"+labelvar_row_html+"</tr>");
	fitDialog($('#addMatrixPopup'));
}

// Preview instrument as proper form in Online Designer
function previewInstrument(view_as_form) {
	if (view_as_form) {
		// Alter CSS to look like form
		$('#showpreview1').hide();
		$('#showpreview0').css({'display':''});
		$('.frmedit').hide();
		$('.frmedit_tbl').css({'border-bottom':'0px','border-color':'#ddd'});
		$('.frmedit_row').css({'padding':'0px 0px'});
		$('#draggable').css({'border':'0px'});
		$('.bledit').css
		$('#blcalc-warn').show();
		$('.frmedit_tbl').each(function(){
			var rowid = $(this).attr('id');
			if ($('#'+rowid+' table.matrixHdrs').attr('fmtx')) {
				$('#'+rowid+' tr td.frmedit').show();
				$('#'+rowid+' tr td.frmedit div.frmedit_icons').hide();
			}
		});
		$('.mtxgrpname').css('visibility','hidden');
		$('.designMtxGrpIcons').hide();
		$('table#draggable tr').css('cursor','default');
	} else {
		// Undo and reset back to original look
		$('#showpreview0').css({'display':'none'});
		$('#showpreview1').css({'display':''});
		$('.frmedit').css({'display':''});
		$('.frmedit_tbl').css({'border-bottom':'1px solid #aaa','border-color':'#aaa'});
		$('.frmedit_row').css({'padding':'0px 10px'});
		$('.frmedit_icons').show();
		$('#draggable').css({'border':'1px solid #bbb'});
		$('#blcalc-warn').hide();
		$('.mtxgrpname').css('visibility','visible');
		$('.designMtxGrpIcons').show();
		$('table#draggable tr').css('cursor','move');
	}
}

// Check if non-numeric MC coded values exist and fix
function checkEnumRawVal(ob) {
	var isMatrixEnum = (ob.attr('id') == 'element_enum_matrix');
	var thisval = trim(ob.val());
	// Catch any auto fixes in array
	var choices_fixed = new Array();
	var choices_fixed_labels = new Array();
	var choices_fixed_key = 0;
	var maxExistingEnum = 0;
	// Parse choices and detect if a raw value is missing
	var choices = thisval.split("\n");
	// Check things only for Editing Field popup
	if (!isMatrixEnum) {
		// Do not process if an invalid field type for this or if enum is blank
		var mcfields = new Array('radio','select','checkbox');
		if (thisval.length < 1 || !in_array($('#field_type').val(), mcfields)) {
			return true;
		}
		// Get array of existing enums already saved (returned from ajax request)
		var choices_existing = $('#existing_enum').val().split("|");
		// Get the highest numbered value for the existing choices
		for (var i=0; i<choices_existing.length; i++) {
			var thisexistchoice = trim(choices_existing[i]);
			if (isNumeric(thisexistchoice) && thisexistchoice*1 > maxExistingEnum*1) {
				maxExistingEnum = thisexistchoice;
			}
		}
	}
	// Now get the highest numbered value for choices that were just entered (see if higher than existing saved choices)
	for (var i=0; i<choices.length; i++) {
		var wholeChoiceNumeric = isNumeric(trim(choices[i]));
		if (choices[i].split(",").length > 1 || wholeChoiceNumeric) {
			if (wholeChoiceNumeric) {
				var val = trim(choices[i]);
			} else {
				var valAndLabel = choices[i].split(",", 2);
				var val = trim(valAndLabel[0]);
			}
			if (isNumeric(val) && val*1 > maxExistingEnum*1) {
				maxExistingEnum = val;
			}
		}
	}
	// Loop through each choice in the textbox
	for (var i=0; i<choices.length; i++) {
		if (trim(choices[i]) != "") {
			if (choices[i].split(",").length > 1) {
				// If has one or more commas, then parse it
				var commaPos = choices[i].indexOf(",");
				var val = trim(choices[i].substring(0,commaPos));
				var label = trim(choices[i].substring(commaPos+1));
				// Check if value is numeric, and if not, check if raw value is acceptable as non-numeric, otherwise, prepend new raw value
				if (!isNumeric(val) && !val.match(/^[0-9A-Za-z_]*$/)) {
					// If user added a label w/o a raw value, prepend with raw value
					maxExistingEnum++;
					val = maxExistingEnum;
					label = trim(choices[i]);
					// Add to fixed array
					choices_fixed[choices_fixed_key] = val;
					choices_fixed_labels[choices_fixed_key] = label;
					choices_fixed_key++;
				}
			} else {
				// No comma, so it MUST not have a raw value. Give it one.
				// Check if value is numeric
				if (isNumeric(choices[i])) {
					var val = choices[i];
				} else {
					maxExistingEnum++;
					var val = maxExistingEnum;
				}
				var label = choices[i];
				// Add to fixed array
				choices_fixed[choices_fixed_key] = val;
				choices_fixed_labels[choices_fixed_key] = label;
				choices_fixed_key++;
			}
			// Re-add cleaned line to array
			choices[i] = val+", "+label;
		}
	}
	// Replace the element_enum choices with our newly formatted version
	ob.val(choices.join("\n"));
	// If any choice's raw values were fixed or auto-added, then open pop-up
	if (choices_fixed.length > 0) {
		// Set HTML to display the fixed choices
		var choices_fixed_html = "";
		for (var i=0; i<choices_fixed.length; i++) {
			choices_fixed_html += "<div class='mc_raw_val_fix'><span class='rawVal'>"+choices_fixed[i]+"</span> "
				+rawEnumValMsg+" <b>"+choices_fixed_labels[i].replace(/</g,"&lt;").replace(/>/g,"&gt;")+"</b></div>";
		}
		// Add formatted choices to box
		$('#element_enum_clone').html(choices_fixed_html);
		// Open pop-up
		$('#mc_code_change').dialog({ bgiframe: true, modal: true, width: 500, open: function(){ }, 
			buttons: { Close: function() { $(this).dialog('close'); } }
		});
		return false;
	}
	return true;
}

// If user clicks or focuses on Variable field in Add New Question dialog on Design page, give user warning is field has been disabled and why
function chkVarFldDisabled() {
	if (status > 0 && $('#field_name').attr('readonly')) {
		simpleDialog(langOD47,null,null,null,"document.getElementById('field_label').focus();",null);
	}
}
	
// Remove row of label/var input from Add Matrix dialog
function delMatrixRow(ob) {
	if ($('.addFieldMatrixRow').length > 1) {
		var row = $(ob).parent().parent();
		var removeRow = false;
		// Set delay time (ms)
		var delay = 1000;
		// If label and var name are blank, then remove row without prompt
		if (trim(row.find('.field_name_matrix').val()) == '' && trim(row.find('.field_label_matrix').val()) == '') {
			removeRow = true;
			delay = 600;
		} else if (confirm(delFieldTitle+"\n\n"+delFieldMsg+langQuestionMark)) {
			removeRow = true;
		}
		if (removeRow) {
			// Highlight row for a split second
			row.find('.field_name_matrix').effect('highlight',{ },delay);
			row.find('.field_label_matrix').effect('highlight',{ },delay);
			// Remove row
			setTimeout(function(){
				row.remove();
			},delay-500);
		}
	} else {
		simpleDialog(langOD29,langOD30);
	}
}
	
// Delete the attachment for image/file attachment fields
function deleteAttachment() {
	simpleDialog(langOD27, langOD28,null,null,null,langCancel,
		"$('#div_attach_upload_link').show();$('#div_attach_download_link').hide();$('#edoc_id').val('');enableAttachImgOption(0);",langDelete);
}
	
// Open pop-up for uploading documents as image/file attachments
function openAttachPopup() {			
	$('#div_attach_doc_in_progress').hide();
	$('#div_attach_doc_success').hide();
	$('#div_attach_doc_fail').hide();
	$("#attachFieldUploadForm").show();
	$('#myfile').val('');
	$('#attachment-popup').dialog({ bgiframe: true, modal: true, width: 400, 
		buttons: [
			{ text: langCancel, click: function () { $(this).dialog('close'); } },
			{ text: langOD31, click: function () {
				if ($('#myfile').val().length < 1) {
					alert(langOD32);
					return false;
				}
				$(":button:contains('"+langOD31+"')").css('display','none');
				$('#div_attach_doc_in_progress').show();
				$('#attachFieldUploadForm').hide();
				$("#attachFieldUploadForm").submit();
			} }
		]
	});
}