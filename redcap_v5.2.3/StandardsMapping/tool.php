<?php
/*****************************************************************************************
**  REDCap is only available through ACADMEMIC USER LICENSE with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';


$clientDebug = false;//used to enable client side logging

print "<link type='text/css' href='".APP_PATH_WEBROOT."Resources/css/basic.css' rel='stylesheet' media='screen' />";

//change initial server values to account for a lot of processing


if(isset($_POST['map_submission'])) 
{	
	//process the mapping fields and insert into db here	
	redirect(APP_PATH_WEBROOT . "StandardsMapping/index.php");
}
?>

<div id='mapper' style='display:none;background-color:#f5f5f5;'>
	<div id='mapper-form-content' style='width:500px;margin-left:auto;margin-right:auto;'>
		<br/>
		<span style='display:none' id='mapper-pid'></span>
		<table border='0' cellpadding='2' cellspacing='2' width='500px'>
			<tr>
				<td width='150px'><b>REDCap Project:</b>&nbsp;&nbsp;&nbsp;&nbsp;</td><td><span id='mapper-project'></span></td>
			</tr>
			<tr>
				<td><b>REDCap Form:</b></td><td><span id='mapper-form'></span></td>
			</tr>
			<tr>
				<td><b>REDCap Field:</b></td><td><span id='mapper-field'></span> (<span id='mapper-field-type'></span>)<span id='mapper-map-id' style='display:none'></span></td>
			</tr>
			
			<tr><td colspan='2' style='height:40px'><hr/></td></tr>

			<tr>
				<td>Mapped Standard:&nbsp;&nbsp;&nbsp;&nbsp;</td>
				<td>
					<select name='mapper-standard_select_box' id='mapper-standard_select_box' style='width:238px'>
						<?php
						$standardsQuery = db_query("select * from redcap_standard");
						while($standard = db_fetch_array($standardsQuery)) {
							print "<option value='{$standard['standard_id']}'>{$standard['standard_name']} {$standard['standard_version']}</option>";
						}
						?>
					</select>
					<button name='launchNewStandard' onclick='openNewStandard();return false;'>add</button>
				</td>
			</tr>
			<tr>
				<td id='mapper-standard-code-label'>Mapped Code:</td>
				<td><input type='text' style='width:280px' id='mapper-standard-code' name='mapper-standard-code' value=''/></td>
			</tr>
			<tr>
				<td id='mapper-standard-code-conversion-label' valign='top'>Data Conversion:<br>(optional)</td>
				<td>
					<textarea id='mapper-standard-code-conversion' name='mapper-standard-code-conversion' style='height:60px;width:279px;'></textarea>
					<span id='mapper-standard-code-conversion-buttons' name='mapper-standard-code-conversion-buttons'>
						<br/>
						<input type='button' value=' initialize ' id='initDataConversionBtn' onclick='initDataConversion();'>
						&nbsp;
						<input type='button' value=' show labels ' id='showEnumLabels' onclick='openInfoBox("enum",document.getElementById("mapper-field").innerHTML);'>
						&nbsp;
						<input type='button' value=' clear ' id='addMappingBtn' onclick='clearDataConversion();'>
					</span>
					<span id='mapper-standard-code-conversion-checks' name='mapper-standard-code-conversion-checks' style='display:hidden'>
						<br/><br/>
						<table>
						<tr>
						<td>checked value</td><td><input type='text' name='mapper-standard-code-conversion-checks-checked' id='mapper-standard-code-conversion-checks-checked' value='1' style='width:60px'/></td>
						</tr><tr>
						<td>unchecked value:</td><td><input type='text' name='mapper-standard-code-conversion-checks-unchecked' id='mapper-standard-code-conversion-checks-unchecked' value='0' style='width:60px'/></td>
						</tr>
						</table>
					</span>
				</td>
			</tr>
		<tr>
			<td colspan='2'><span style='color:red' id='mapper-message'></span></td>
		</tr>
		<tr><td colspan='2' style='height:20px'></td></tr>
			<tr>
				<td colspan='2'>
					<input type='button' style='font-weight:bold;' value=' Save ' id='addMappingBtn' onclick='if(validateDataConversion()) addMappedField();'>
					<input id='deleteMappedFieldButton' type='button' style='font-weight:bold;display:none;' value=' Delete ' id='addMappingBtn' onclick='removeMappedField();'>
					<input type='button' style='font-weight:bold;' value=' Cancel ' id='addMappingBtn' onclick='clearMapper();closeMapper();'>

				</td>
			</tr>
		</table>
	</div>
</div>

<div id='mapper-add-standard' style='display:none;background-color:#f5f5f5;'>
	<div id='mapper-add-standard-content' style='width:400px;margin-left:auto;margin-right:auto;'>
	<table border='0' cellpadding='2' cellspacing='2' width='100%'>
		<tr>
			<td>Title: </td> 
			<td><input id='newStandard' type='text' style='width:240px' name='newStandard' value=''/></td>
		</tr>
		<tr>
			<td>Version: </td>
			<td><input id='newStandardVersion' type='text' style='width:120px' name='newStandardVersion' value=''/></td>
		</tr>
		<tr>
			<td>Description: </td>
			<td valign='top'><textarea id='newStandardDescription' name='newStandardDescription' style='height:60px;width:240px;'></textarea></td>
		</tr>
		<tr>
			<td colspan='2'><span style='color:red' id='mapper-add-standard-message'></span></td>
		</tr>
		<tr><td colspan='2' style='height:20px'></td></tr>
		<tr>
			<td colspan='2'>
				<input type='button' style='font-weight:bold;' value=' Add ' id='addStandardBtn' onclick='addStandard();'>
				<input type='button' style='font-weight:bold;' value=' Cancel ' id='cancelStandardBtn' onclick='clearNewStandard();closeNewStandard();'>
			</td>
		</tr>
	</table>
	</div>
</div>

<div id='mapper-infobox' style='display:none;background-color:#f5f5f5;'>
	<div id='mapper-infobox-content' style='width:400px;max-height:600px;overflow:auto;margin-left:auto;margin-right:auto;'>
	</div>
</div>

<script type='text/javascript'>
function openMapper(mapId, pid, projectTitle, formName, fieldName, fieldType) {
	clearMapper();
	if(mapId != -1) {
		$.getJSON(app_path_webroot+"StandardsMapping/async.php", { uuid: getUUID(), operation: "getFieldData", pid: pid, mapId: mapId },
			function(response){
				if(response.success == true) {
					document.getElementById('mapper-standard-code').value = response.data.standard_code;
					document.getElementById('mapper-standard-code-conversion').value = response.data.data_conversion;
					document.getElementById('mapper-map-id').innerHTML = mapId;
					document.getElementById('mapper-standard-code-conversion-checks-checked').value = response.data.checked_value;
					document.getElementById('mapper-standard-code-conversion-checks-unchecked').value = response.data.unchecked_value;
					//standard should already be in select box, but this function call will cause it to be selected
					addToSelect(document.getElementById('mapper-standard_select_box'),response.data.standard_id,response.data.standard_name+" "+response.data.standard_version,true);
					document.getElementById('deleteMappedFieldButton').style.display = 'inline';
				}else {
					alert('unknown error occurred retrieving details for mapped code');
				}
				$('#mapper').dialog({title:'Standards Mapping Utility',width:'600px',close: function(e, u) { clearMapper(); }});
				$('#mapper').dialog();
			}
		);
	}
	document.getElementById('mapper-pid').innerHTML = pid;
	document.getElementById('mapper-project').innerHTML = projectTitle;
	document.getElementById('mapper-form').innerHTML = formName;
	document.getElementById('mapper-field').innerHTML = fieldName;
	document.getElementById('mapper-field-type').innerHTML = fieldType;
	
	if(fieldType == 'int' || fieldType == 'float' || fieldType == 'calc' || fieldType == 'select' || fieldType == 'radio' || fieldType == 'checkbox' || fieldType == 'date' || fieldType == 'time') {
		document.getElementById('mapper-standard-code-conversion-label').style.display = 'inline';
		document.getElementById('mapper-standard-code-conversion').style.display = 'inline';
		document.getElementById('mapper-standard-code-conversion-buttons').style.display = 'inline';
		document.getElementById('showEnumLabels').style.display = 'none';
		if(fieldType == 'int' || fieldType == 'float' || fieldType == 'calc') {
			document.getElementById('initDataConversionBtn').value = ' insert field ';
		}else if(fieldType == 'select' || fieldType == 'radio' || fieldType == 'checkbox') {
			document.getElementById('initDataConversionBtn').value = ' initialize ';
			document.getElementById('showEnumLabels').style.display = 'inline';
		}else if(fieldType == 'date' || fieldType == 'time') {
			document.getElementById('initDataConversionBtn').value = ' format codes ';
		}
		if(fieldType == 'checkbox') {
			document.getElementById('mapper-standard-code-conversion-checks').style.display = 'inline';
			document.getElementById('mapper-standard-code-conversion-label').innerHTML = 'Mapped Code(s):';
			document.getElementById('mapper-standard-code-label').style.display = 'none';
			document.getElementById('mapper-standard-code').style.display = 'none';
		}else {
			document.getElementById('mapper-standard-code-conversion-label').innerHTML = 'Data Conversion:<br>(optional):';
			document.getElementById('mapper-standard-code-label').style.display = 'inline';
			document.getElementById('mapper-standard-code').style.display = 'inline';
		}
	}else {
		document.getElementById('mapper-standard-code-label').style.display = 'inline';
		document.getElementById('mapper-standard-code').style.display = 'inline';
		document.getElementById('mapper-standard-code-conversion-label').style.display = 'none';
		document.getElementById('mapper-standard-code-conversion').style.display = 'none';
		document.getElementById('mapper-standard-code-conversion-buttons').style.display = 'none';
	}

	if(mapId == -1) {
		$('#mapper').dialog({title:'Standards Mapping Utility',width:'600px',close: function(e, u) { clearMapper(); }});
		$('#mapper').dialog();
	}
}
function initDataConversion() {
	pid = document.getElementById('mapper-pid').innerHTML;
	fieldName = document.getElementById('mapper-field').innerHTML;
	fieldType = document.getElementById('mapper-field-type').innerHTML;
	if(fieldType == 'select' || fieldType == 'radio' || fieldType == 'checkbox') {
		$.getJSON(app_path_webroot+"StandardsMapping/async.php", { uuid: getUUID(), operation: "getEnumeratedValues", pid: pid, fieldName: fieldName },
			function(response){
				if(response.success == true) {
					var theValue = response.data.enumerated_values.replace(/\s*\r?\n\s*/gi,"=\n");
					document.getElementById('mapper-standard-code-conversion').value = theValue;
				}
			}
		);
	}else if(fieldType == 'int' || fieldType == 'float' || fieldType == 'calc') {
		insertAtCursor(document.getElementById('mapper-standard-code-conversion'),'[' + fieldName + ']');
		//document.getElementById('mapper-standard-code-conversion').value = '[' + fieldName + ']';
	}else if(fieldType == 'date') {
		openInfoBox('date');
	}else if(fieldType == 'time') {
		openInfoBox('time');
	}
}

function validateDataConversion() {
	fieldType = document.getElementById('mapper-field-type').innerHTML;
	dataConversion = document.getElementById('mapper-standard-code-conversion').value;
	var errorMessage = '';
	var test = true;
	if(fieldType == 'checkbox' || fieldType == 'select' || fieldType == 'radio') {
		var checkRE = /^(\d+=[^\n=]*\n)*(\d+=[^\n=]*)?$/;
		if(!checkRE.test(dataConversion)) {
			test = false;
			errorMessage = 'data conversion contains invalid characters';
		}
	}else if(fieldType == 'date' || fieldType == 'time') {
		//don't have a good way to validate this since pretty much all characters are allowed although some have special meaning
	}else if(fieldType == 'int' || fieldType == 'float' || fieldType == 'calc') {
		var fieldName = document.getElementById('mapper-field').innerHTML;
		var checkRE = new RegExp("\\[(?!"+fieldName+"\\])", "g");//finds field references that are not this field
		if(checkRE.test(dataConversion)) {
			test = false;
			errorMessage = 'formula cannot contain references to other fields';
		}
		
		var checkRE2 = new RegExp("\\[(?!"+fieldName+"\\])[^\d\*\+]");
		if(checkRE2.test(dataConversion)) {
			test = false;
			errorMessage = 'formula contains invalid characters';
		}
	}
	if(!test) {
		document.getElementById('mapper-message').innerHTML = errorMessage;
	}
	return test;
}

function clearDataConversion() {
	document.getElementById("mapper-standard-code-conversion").value = "";
}
function clearMapper() {
	document.getElementById('mapper-pid').innerHTML = "";
	document.getElementById('mapper-project').innerHTML = "";
	document.getElementById('mapper-form').innerHTML = "";
	document.getElementById('mapper-field').innerHTML = "";
	document.getElementById('mapper-field-type').innerHTML = "";
	document.getElementById('mapper-map-id').innerHTML = "";
	
	document.getElementById('mapper-standard-code').value = "";
	document.getElementById('mapper-standard-code-conversion').value = "";
	document.getElementById('mapper-field').value = "";
	document.getElementById('mapper-message').innerHTML = "";
	document.getElementById('deleteMappedFieldButton').style.display = 'none';
	document.getElementById('mapper-standard-code-conversion-checks').style.display = 'none';
	document.getElementById('mapper-standard-code-conversion-checks-checked').value = "1";
	document.getElementById('mapper-standard-code-conversion-checks-unchecked').value = "0";
}
function closeMapper() {
	$('#mapper').dialog('close');
}
$('#mapper').bind('dialogclose',function(event) {
	closeNewStandard();
	closeInfoBox();
});

function openNewStandard() {
	$('#mapper-add-standard').dialog({title:'Add new Standard',width:'500px',close: function(e, u) { clearNewStandard(); }});
	$('#mapper-add-standard').dialog();
}
function clearNewStandard() {
	document.getElementById('newStandard').value = "";
	document.getElementById('newStandardVersion').value = "";
	document.getElementById('newStandardDescription').value = "";
	document.getElementById('mapper-add-standard-message').value = "";
}
function closeNewStandard() {
	$('#mapper-add-standard').dialog('close');
}

function openInfoBox(mode, field) {
	operation = 'none';
	if(mode == 'date') {
		operation = 'getDateFormatInfo';
	}else if(mode == 'time') {
		operation = 'getTimeFormatInfo';
	}else if(mode == 'enum') {
		operation = 'getEnumeratedValues';
	}
	$.getJSON(app_path_webroot+"StandardsMapping/async.php", { uuid: getUUID(), operation: operation, pid: pid, fieldName: field },
		function(response){
			if(response.success == true) {
				if(mode == 'enum') {
					var infoBoxText = 'The following list shows how the values are currently encoded for this field:<br><br>';
					var responseText = response.data.enumerated_values_full.replace(/\s*\r?\n\s*/gi,"<br>");
					document.getElementById('mapper-infobox-content').innerHTML = infoBoxText+responseText;
				}else {
					document.getElementById('mapper-infobox-content').innerHTML = response.data.text;
				}
			}else {
				document.getElementById('mapper-infobox-content').innerHTML = 'no data';
			}
			$('#mapper-infobox').dialog({title:'Information',width:'500px',close: function(e, u) { clearInfoBox(response); }});
			$('#mapper-infobox').dialog();
		}
	);
}
function clearInfoBox(response) {
	document.getElementById('mapper-infobox-content').innerHTML = response;
}
function closeInfoBox() {
	$('#mapper-infobox').dialog('close');
}

function addStandard() {
	var mapId = ((document.getElementById("mapper-map-id").innerHTML == "")?-1:document.getElementById("mapper-map-id").innerHTML);
	var newStandard = document.getElementById("newStandard").value;
	var newStandardVersion = document.getElementById("newStandardVersion").value;
	var newStandardDescription = document.getElementById("newStandardDescription").value;
   	$.getJSON(app_path_webroot+"StandardsMapping/async.php", { uuid: getUUID(), pid: pid, operation: "addStandard", mapId: mapId, standard: newStandard, version: newStandardVersion, description: newStandardDescription },
   		function(response){
     		if(response.success == true) {
     			closeNewStandard();
     			clearNewStandard();
     			addToSelect(document.getElementById('mapper-standard_select_box'),response.data.id,newStandard+" "+newStandardVersion,true);
     		}else {
     			document.getElementById('mapper-add-standard-message').innerHTML = response.data.message;
     		}
   		}
   	);
}
   
function addMappedField() {
	var pid = document.getElementById("mapper-pid").innerHTML;
	var project = document.getElementById("mapper-project").innerHTML;
	var form = document.getElementById("mapper-form").innerHTML;
	var field = document.getElementById("mapper-field").innerHTML;
	var fieldType = document.getElementById('mapper-field-type').innerHTML;
	var standard = document.getElementById("mapper-standard_select_box").value;
	var code = document.getElementById("mapper-standard-code").value;
	var mapId = document.getElementById('mapper-map-id').innerHTML;
	var conversion = document.getElementById("mapper-standard-code-conversion").value;
	standard = standard.replace(/['"]/g,"");
	code = code.replace(/['"]/g,"");
	if(fieldType == 'checkbox') {
		//checkbox conversion values become column headers in export, so no quotes allowed (much like field names)
		conversion = conversion.replace(/['"]/g,"");
	}
	conversion = conversion.replace(/\\/gi, '////////');
	var checked = "";
	var unchecked = "";
	if(fieldType == 'checkbox') {
		code = 'multiple';
		checked = document.getElementById("mapper-standard-code-conversion-checks-checked").value;
		unchecked = document.getElementById("mapper-standard-code-conversion-checks-unchecked").value;
	}
	
   	$.getJSON(app_path_webroot+"StandardsMapping/async.php", { uuid: getUUID(), pid: pid, operation: "addMappedField", mapId: mapId, field: field, standard: standard, code: code, conversion: conversion, checked: checked, unchecked: unchecked },
   		function(response){
     		if(response.success == true) {
     			mapId = response.data.map_id;
     			var editLink = "javascript:openMapper("+response.data.map_id+","+pid+",\""+project+"\",\""+form+"\",\""+field+"\",\""+fieldType+"\","+response.data.id+");";
     			closeMapper();
     			clearMapper();
     			addToStandardList(document.getElementById("map-list-"+field), mapId, response.data.standard_name, response.data.standard_version, code, editLink);
     		}else {
     			document.getElementById('mapper-message').innerHTML = response.data.message;
     		}
   		}
   	);
}

function removeMappedField() {
	var mapId = document.getElementById('mapper-map-id').innerHTML;
   	$.getJSON(app_path_webroot+"StandardsMapping/async.php", { uuid: getUUID(), pid: pid, operation: "removeMappedField", mapId: mapId },
   		function(response){
     		if(response.success == true) {
     			var field = document.getElementById("mapper-field").innerHTML;
     			removeFromStandardList(document.getElementById("map-list-"+field), mapId);
     			closeMapper();
     			clearMapper();
     		}else {
     			document.getElementById('mapper-message').innerHTML = response.data.message;
     		}
   		}
   	);
	
}

function addToSelect(selectBox,value,text,setSelect) {
	var found = false;
	for(idx = 0; idx < selectBox.options.length; idx++) {
		if(typeof(selectBox.options[idx]) != 'undefined' && selectBox.options[idx].value == value) {
			if(setSelect) {
				selectBox.options[idx].selected = true;
			}
			found = true;
			break;
		}
	}
	if(!found) {
		var newOption = document.createElement("OPTION");
		selectBox.options.add(newOption);
		newOption.text = text;
		newOption.value = value;
		if(setSelect) {
			newOption.selected = true;
		}
	}
}
/*******************************************************************************************************************
 * addToStandardList
 * 
 * adds a new mapped standard code to the main list complete with hyperlink to edit the mapping
 */
function addToStandardList(tbl, mapId, standard, version, code, editLink) {
	try {
		var standardFound = false;
		var compareStandard = (standard + ' ' + version).toLowerCase();
		var compareCode = code.toLowerCase();
		var row = null;
		if(mapId > 0) {
			removeFromStandardList(tbl, mapId);
		}
		for(var rowIndex=0; rowIndex < tbl.rows.length; rowIndex++) {
			var cell0 = tbl.rows[rowIndex].childNodes[0].childNodes[0].nodeValue.toLowerCase();
			var cell1 = tbl.rows[rowIndex].childNodes[1].childNodes[0].childNodes[0].nodeValue.toLowerCase();

			if(compareStandard == cell0) {
				standardFound = true;
				if(compareCode < cell1) {
					break;
				}
			}else if(compareStandard < cell0 || standardFound) {
				break;
			}
		}
		var row = tbl.insertRow(rowIndex);
		row.id = 'stdmapid'+mapId;
		var standardCell = row.insertCell(0);
		standardCell.width = "50%";
		standardCell.appendChild(document.createTextNode(standard + " " + version));
		var codeCell = row.insertCell(1);
		codeCell.width = "50%";
		if(code == 'multiple') {
			codeCell.style.fontStyle = "italic";
		}
		var anchorTag = document.createElement('a');
		anchorTag.href = editLink;
		var codeElement = document.createTextNode(code);
		anchorTag.appendChild(codeElement);
		codeCell.appendChild(anchorTag);	
	}catch(err) {
		alert('caught error adding to code list: ' + err.description );
	}
}

function removeFromStandardList(tbl, mapId) {
	for(var rowIndex=0; rowIndex < tbl.rows.length; rowIndex++) {
		if(tbl.rows[rowIndex].id == 'stdmapid'+mapId) {
			tbl.deleteRow(rowIndex);
			break;
		}
	}
}

function insertAtCursor(field, value) { 
	//IE support 
	if (document.selection) { 
		field.focus(); 
		sel = document.selection.createRange(); 
		sel.text = value; 
	} 
	//Mozilla/Firefox/Netscape 7+ support 
	else if (field.selectionStart || field.selectionStart == '0') {
		var startPos = field.selectionStart; 
		var endPos = field.selectionEnd; 
		field.value = field.value.substring(0, startPos)+ value+ field.value.substring(endPos, field.value.length); 
	} else { 
		field.value += value; 
	} 
	field.focus();
}

function URLEncode( s ) {
	return encodeURIComponent( s ).replace( /\%20/g, '+' ).replace( /!/g, '%21' ).replace( /'/g, '%27' ).replace( /\(/g, '%28' ).replace( /\)/g, '%29' ).replace( /\*/g, '%2A' ).replace( /\~/g, '%7E' );
}


function getUUID() {
	var d = new Date();
	return d.valueOf();
}
function log(msg) {
	if(<?php echo ($clientDebug?"true":"false") ?>) {
		var curr = document.getElementById('log').innerHTML;
		curr += msg+"<br/>";
		document.getElementById('log').innerHTML = curr;
	}
}
</script>

<?php
if ($clientDebug) 
{
	print "<div id='logContainer' style='display:none;height:720px;'>";
	print "<div id='log' style='border:1px solid #CCC;height:700px;overflow:auto;'>";
	print "</div>";
	//print "<input type='button' value=' Map Check ' id='testAddStandardBtn' onclick='mapNewStandard(\"test standard\",\"1.3\");'>";
	print "</div>";
	print "<script type='text/javascript'>";
	print "$('#logContainer').dialog({title:'Client Log',width:'525px', position: ['right','top']});";
	print "$('#logContainer').dialog();";
	print "log('client log initialized')";
	print "</script>";
}
