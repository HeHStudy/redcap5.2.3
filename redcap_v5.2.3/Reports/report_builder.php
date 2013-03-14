<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';


#########################################################################################################
// Post-processing
if (!empty($_POST) && isset($_POST['num_query_fields'])) 
{
	
	foreach ($_POST as $key=>$value) {
		//Remove any backslashes or single- or double-quotes
		$orig = array("'","\"","\\");
		$repl = array("","","");
		$_POST[$key] = str_replace($orig,$repl,$value);
	}
	
	//Get the array # from $_GET['query_id']. This will be used if modifying an already saved query.
	if (isset($_GET['query_id'])) {
		$query_id = $_GET['query_id'];
		$query_array_replace = array();
		foreach ($query_array as $this_query_id=>$this_query) {
			if ($this_query_id != $query_id) {
				$query_array_replace[$this_query_id] = $this_query;
			}
		}
		$query_array = $query_array_replace;
		
	} else {
		//Add this onto the end of $query_array
		//Get the next query_id number
		$query_id = max(array_keys($query_array)) + 1;
	}	
	
	//Grab all fields selected
	$num_query_fields = $_POST['num_query_fields'];
	$query_fields = array();
	//Begin with query title
	$query_array[$query_id]['__TITLE__'] = trim($_POST['__TITLE__']);	
	//Add fields to order query by
	if ($_POST['field_-ORDERBY1'] != "") {
		$query_array[$query_id]['__ORDERBY1__'] = " {$_POST['field_-ORDERBY1']} {$_POST['ORDERBY1_ASCDESC']}";
	}
	if ($_POST['field_-ORDERBY2'] != "") {
		$query_array[$query_id]['__ORDERBY2__'] = " {$_POST['field_-ORDERBY2']} {$_POST['ORDERBY2_ASCDESC']}";
	}
	//Loop through and add fields to query and where clause conditions
	for ($k = 1; $k <= $num_query_fields; $k++) {
		if (isset($_POST['field_'.$k]) && $_POST['field_'.$k] != "") {
			// Set field
			$this_field = $_POST['field_'.$k];
			$this_operator = $_POST['operator_'.$k];
			$this_value = str_replace(array("'","\""), array("",""), html_entity_decode($_POST['condvalue_'.$k], ENT_QUOTES));
			// If field is a date[time] field, then format to YMD format
			$this_valtype = $Proj->metadata[$this_field]['element_validation_type'];
			if ($this_operator != "" && (substr($this_valtype, -4) == '_mdy' || substr($this_valtype, -4) == '_dmy')) {
				$this_dateformat = substr($this_valtype, -3);
				$this_value = datetimeConvert($this_value, $this_dateformat, 'ymd');
			}
			// WHERE clause conditions
			$query_array[$query_id][$this_field] = ($this_operator == "" ? "" : "$this_field $this_operator '$this_value'");
		}
	}
	
	//Now that new query was added, translate $query_array into a string to put in field in redcap_projects table
	ksort($query_array);
	$sql_string = '';
	foreach ($query_array as $this_query_id=>$this_query) {
		foreach ($this_query as $this_field=>$this_where) {
			// Ensure that the field exists before re-adding it
			if (isset($Proj->metadata[$this_field]) || substr($this_field, 0, 2) == "__")
			{
				$sql_string .= '$query_array['.$this_query_id.'][\''.$this_field.'\'] = "'.$this_where.'";'."\n";
			}
		}
	}
	$sql_string .= "\n";
	
	//Update this field in redcap_projects
	$sql = "update redcap_projects set report_builder = '".prep($sql_string)."' where project_id = $project_id";
	$q = db_query($sql);
	
	// Logging
	$log_descrip = isset($_GET['query_id']) ? "Edit report" : "Create report";
	if ($q) log_event($sql,"redcap_projects","MANAGE",$project_id,$sql_string,$log_descrip);
				
	//After adding/editing this report, refresh page to display it.
	
	redirect($_SERVER['PHP_SELF']."?pid=$project_id&newquery=1");
	exit;
	
}
#########################################################################################################





#########################################################################################################
//Delete this query if user clicked "delete"
if (isset($_GET['delete'])) {
	
	$query_id = $_GET['query_id'];
	
	//Loop through and remove this query_id from $query_array
	$sql_string = '';
	foreach ($query_array as $this_query_id=>$this_query) {
		if ($this_query_id != $query_id) {
			foreach ($this_query as $this_field=>$this_where) {
				$sql_string .= '$query_array['.$this_query_id.'][\''.$this_field.'\'] = "'.$this_where.'";'."\n";
			}
		}
	}
	$sql_string .= "\n";
	
	$sql = "update redcap_projects set report_builder = '".prep($sql_string)."' where project_id = $project_id";
	$q = db_query($sql);
		
	// Logging
	if ($q) log_event($sql,"redcap_projects","MANAGE",$project_id,"project_id = $project_id","Delete report");
	
	//After deleting this report, refresh page
	redirect($_SERVER['PHP_SELF']."?pid=$project_id&deleted_query=1");
	exit;
	
}


#########################################################################################################
//Copy this query if user clicked "copy"
if (isset($_GET['rcopy'])) {

	$query_id = $_GET['query_id'];
	
	//Get the next query_id number
	$new_query_id = max(array_keys($query_array)) + 1;
	
	//Append new report onto end of report builder listing and save it
	$sql_string = $report_builder;
	foreach ($query_array[$query_id] as $this_field=>$this_limiter) {
		if ($this_field == "__TITLE__") {
			if (strlen($this_limiter) > 50) $this_limiter = substr($this_limiter,0,50) . "...";
			$this_limiter .= " (copy)"; 
		}
		$sql_string .= '$query_array['.$new_query_id.'][\''.$this_field.'\'] = "'.$this_limiter.'";'."\n";
	}
	
	$sql = "update redcap_projects set report_builder = '".prep($sql_string)."' where project_id = $project_id";
	$q = db_query($sql);
		
	// Logging
	if ($q) log_event($sql,"redcap_projects","MANAGE",$project_id,"project_id = $project_id","Copy report");
				 
	//After copying this report, refresh page
	redirect($_SERVER['PHP_SELF']."?pid=$project_id&rcopied_query=1");
	exit;
	
}
#########################################################################################################

	


#########################################################################################################
//Edit Query set up

if (isset($_GET['query_id'])) {
	
	if (!is_numeric($_GET['query_id'])) {
		redirect(APP_PATH_WEBROOT . "index.php?pid=" . PROJECT_ID);
		exit;
	}
	
	$query_id = $_GET['query_id'];
	$this_query_array = $query_array[$query_id]; //$query_array originates from an eval of $report_builder in Config/init_project.php
}
	

// Header
include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

renderPageTitle("<img src='".APP_PATH_IMAGES."wrench.png'> {$lang['app_14']}");

	
	
#########################################################################################################	
//Show all existing queries and their titles and allow user to create a new query

//Give confirmation of adding new query
if (isset($_GET['newquery'])) {
	print "<div align=center style='max-width:700px;'><span class='darkgreen'><img src='".APP_PATH_IMAGES."tick.png' 
		class='imgfix'> {$lang['report_builder_01']}</span></div><br>";
} elseif (isset($_GET['deleted_query'])) {
	print "<div align=center style='max-width:700px;'><span class='red'><img src='".APP_PATH_IMAGES."exclamation.png' 
		class='imgfix'> {$lang['report_builder_02']}</span></div><br>";
} elseif (isset($_GET['rcopied_query'])) {
	print "<div align=center style='max-width:700px;'><span class='yellow'><img src='".APP_PATH_IMAGES."exclamation_orange.png' 
		class='imgfix'> {$lang['report_builder_03']}</span></div><br>";
}

print "<p>{$lang['report_builder_04']}<br>";
	
if (isset($_GET['query_id'])) {

	print '<br><div id="sub-nav" style="max-width:700px;"><ul>';
	print '<li class="active"><a style="font-size:14px;color:#393733" href="javascript:;">'.$lang['report_builder_05'].'</a></li>';
	print '</ul></div>';
	print "<p>{$lang['report_builder_06']}</p><br>";

} else {

	//My Reports list
	if (!empty($query_array)) 
	{
		print "<div style='max-width:700px;'><table width=100% cellpadding=3 cellspacing=0 style='border:1px solid #D0D0D0;font-family:Verdana,Arial;'>
				<tr><td style='border:1px solid #AAAAAA;font-size:14px;font-weight:bold;padding:5px;text-align:left;background-color:#DDDDDD;' colspan='3'>
					{$lang['report_builder_07']}
				</td></tr>";	
		if ($report_builder != "") {
			$i = 1;
			foreach ($query_array as $key => $this_array) {
				$thisbg = ($i%2) == 0 ? '#FFFFFF' : '#EEEEEE';
				$this_menu_item = $query_array[$key]['__TITLE__'];
				print "<tr style='background-color: $thisbg;'>
					<td style='padding: 3px 0 3px 0;color:#808080;font-size:11px;text-align:right;width:30px;'>$i.)&nbsp;</td>
					<td class='notranslate' style='padding: 3px 0 3px 0;font-size:11px;font-weight:bold;'>$this_menu_item</td>
					<td style='padding: 3px 0 3px 10px;text-align:right;'>
						<span style='color:#C0C0C0;'>
						[<a style='color:#000060;font-size:11px;' href='" . APP_PATH_WEBROOT . "Reports/report.php?pid=$project_id&query_id=$key'>view</a>]
						[<a style='color:#202020;font-size:11px;' href='" . APP_PATH_WEBROOT . "Reports/report_builder.php?pid=$project_id&query_id=$key'>edit</a>]
						[<a style='color:#008000;font-size:11px;' href='javascript:;' 
							onclick=\"if(confirm('".remBr($lang['report_builder_08'])."\\n\\n".remBr($lang['report_builder_09'])."')) 
							window.location.href='" . APP_PATH_WEBROOT . "Reports/report_builder.php?pid=$project_id&query_id=$key&amp;rcopy=1'+addGoogTrans(); return false;\">{$lang['report_builder_10']}</a>]
						[<a style='color:#800000;font-size:11px;' href='javascript:;' 
							onclick=\"if(confirm('".remBr($lang['report_builder_11'])."\\n\\n".remBr($lang['report_builder_12'])."')) 
							window.location.href='" . APP_PATH_WEBROOT . "Reports/report_builder.php?pid=$project_id&query_id=$key&delete=1'+addGoogTrans(); return false;\">{$lang['report_builder_13']}</a>]
						</span>
					</td>
					</tr>";
				$i++;
			}
		}
		print "</table></div>";
	}

	//Create a New Report
	print '<br><br><div id="sub-nav" style="margin:0px;max-width:700px;"><ul>';
	print '<li class="active"><a style="font-size:14px;color:#393733" href="javascript:;">'.$lang['report_builder_14'].'</a></li>';
	print '</ul></div><br><br><br>';
	print "<p>{$lang['report_builder_15']}</p><br>";
}

?>
<script type="text/javascript">
// Add new row when select a field
function selectReportField(fieldnum,ob) {
	fieldPart = ob.value.split('-');
	document.getElementById('field_'+fieldnum).value = fieldPart[0];							
	document.getElementById('allfield_'+fieldnum).value = ob.value;
	addRow(fieldnum);
	selectOperAndVal('span-operator_'+fieldnum,'span-condvalue_'+fieldnum,fieldPart[1],fieldPart[2]);
}

// Report Builder functionality to load correct limiter options for selected field in table			
function selectOperAndVal(thisSpanOper,thisSpanVal,fieldType,fieldVal,ddVal) {
	if (document.getElementById(thisSpanOper) == null) return;
	if (fieldVal == null) return;
	valName = thisSpanVal.split('-');	
	operName = thisSpanOper.split('-');	
	
	// Erase the existing values of the limiter's hidden fields
	document.getElementById(valName[1]).value = '';
	document.getElementById(operName[1]).value = '';
	
	// Set valid options for operator drop-down
	if (fieldType == 'slider' || fieldVal == 'integer' || fieldVal == 'number' || fieldType == 'calc' || fieldVal.substr(0,4) == 'date' ) {
		document.getElementById(thisSpanOper).innerHTML = "<select id='dropdown-"+operName[1]+"' onchange=document.getElementById('"+operName[1]+"').value=this.value;><option value=''></option><option value='='>=</option><option value='!='>not =</option><option value='<'>\<</option><option value='<='>\<=</option><option value='>'>\></option><option value='>='>\>=</option></select>";
	} else {
		document.getElementById(thisSpanOper).innerHTML = "<select id='dropdown-"+operName[1]+"' onchange=document.getElementById('"+operName[1]+"').value=this.value;><option value=''></option><option value='='>=</option><option value='!='>not =</option></select>";
	}
	
	var fieldNumPre = operName[1];
	var fieldNum = fieldNumPre.split('_');
	
	if (fieldType == 'yesno' || fieldType == 'truefalse' || fieldType == 'select' || fieldType == 'radio' || fieldType == 'sql' || fieldType == 'advcheckbox' || fieldType == 'checkbox') {
		// MC fields
		var field_name = document.getElementById('field_'+fieldNum[1]).value;		
		setTimeout(function () {
			$.get(app_path_webroot+'Reports/report_builder_ajax.php', { pid: pid, field_name: field_name, field_num: fieldNum[1] },
				function(data) {
					$('#'+thisSpanVal).html(data);
					// Now set the value of the drop-down we just inserted via innerHTML
					if (ddVal != null) {
						document.getElementById('visible-condvalue_'+fieldNum[1]).value = ddVal;
						document.getElementById('condvalue_'+fieldNum[1]).value = ddVal;
					}
				}
			);
		}, 50);
	} else {
		// Non-MC fields
		fieldValMin = '';
		fieldValMax = '';
		if (fieldType == 'slider') {
			fieldVal = 'integer';
			fieldValMin = '0';
			fieldValMax = '100';
		} else if (fieldType == 'calc') {
			fieldVal = 'number';
		}
		document.getElementById(thisSpanVal).innerHTML = "<input id='visible-"+valName[1]+"' type='text' value='' size='15' maxlength='50' onkeydown='if(event.keyCode == 13) return false;' onblur="+(fieldVal==''?'':"redcap_validate(this,'"+fieldValMin+"','"+fieldValMax+"','soft_typed','"+fieldVal+"',1);")+"document.getElementById('"+valName[1]+"').value=this.value;>";
		if (ddVal != null) {
			document.getElementById('visible-condvalue_'+fieldNum[1]).value = ddVal;
			document.getElementById('condvalue_'+fieldNum[1]).value = ddVal;
		}	
		// For date fields, add date format label to the right of the text field
		if (fieldVal.substr(0,4) == 'date') {
			var fieldValDF = fieldVal.substr(fieldVal.length-3,3);
			if (fieldValDF == 'mdy') {
				fieldValDF = 'M-D-Y';
			} else if (fieldValDF == 'dmy') {
				fieldValDF = 'D-M-Y';
			} else {
				fieldValDF = 'Y-M-D';
			}
			var fieldValTF = '';
			if (fieldVal.substr(0,16) == 'datetime_seconds') {
				fieldValTF = 'H:M:S';
			} else if (fieldVal.substr(0,8) == 'datetime') {
				fieldValTF = 'H:M';
			}
			$('#visible-condvalue_'+fieldNum[1]).after('<span class="df">'+trim(fieldValDF+' '+fieldValTF)+'</span>');
		}
	}					
}
</script>
<?php

//Build a single dropdown with all the field names and labels to be used repeatedly
$field_dropdown =  "</td>
					<td class='label'>
						<input type='hidden' name='field_{__fieldnum__}' id='field_{__fieldnum__}' value=''>
						<input type='hidden' id='allfield_{__fieldnum__}' value=''>
						<select class='x-form-text x-form-field notranslate' style='padding-right:0;height:22px;' onchange='selectReportField(\"{__fieldnum__}\",this)' id='dropdown-field_{__fieldnum__}'>
						<option value=''></option>";
$i = 0;
$field_name_type_validation = array();
foreach ($Proj->metadata as $row)
{
	// Do not include 'descriptive' fields
	if ($row['element_type'] == "descriptive") continue;
	// Build list option
	$this_select_dispval = $row['field_name']." (".strip_tags($row['element_label']).")";
	if (strlen($this_select_dispval) > 40) {
		$this_select_dispval = substr($this_select_dispval,0,37) . "...)";
	}	
	// If field has a legacy validation, then update it to non-legacy on the fly for this $Proj object
	if ($row['element_type'] == 'text') {
		if ($row['element_validation_type'] == 'int') {
			$row['element_validation_type'] = 'integer';
		} elseif ($row['element_validation_type'] == 'float') {
			$row['element_validation_type'] = 'number';
		} elseif (in_array($row['element_validation_type'], array('date', 'datetime', 'datetime_seconds'))) {
			$row['element_validation_type'] .= '_ymd';
		}
	}
	$field_dropdown .= "<option value='".$row['field_name']."-".$row['element_type']."-".$row['element_validation_type']."'>$this_select_dispval</option>";
	$field_name_type_validation[$row['field_name']] = $row['field_name']."-".$row['element_type']."-".$row['element_validation_type'];
	$i++;
}
$field_dropdown .= "</select>";

//Build a single table row with field_name dropdown to be used repeatedly	
$new_row = "<tr>
				<td class='label' style='width:120px;'>
					Field {__fieldnum__} $field_dropdown
				</td>
				<td class='label'>
					<span id='span-operator_{__fieldnum__}'><select class='x-form-text x-form-field notranslate' style='padding-right:0;height:22px;' id='dropdown-operator_{__fieldnum__}' disabled><option value=''></option><option value=''>is not</option></select></span> 
					<input type='hidden' name='operator_{__fieldnum__}' id='operator_{__fieldnum__}' value=''>
					<span id='span-condvalue_{__fieldnum__}'><input id='visible-condvalue_{__fieldnum__}' disabled type='text' value='' size='15' maxlength='50' onkeydown='if(event.keyCode == 13) return false;'></span>
					<input type='hidden' name='condvalue_{__fieldnum__}' id='condvalue_{__fieldnum__}' value=''>
				</td>
			</tr>";



//Append query_id to URL for edited report
$add2url = (isset($_GET['query_id'])) ? "query_id=".$_GET['query_id'] : "";


print "<form method='post' action='".$_SERVER['PHP_SELF']."?pid=$project_id&$add2url' target='_self' enctype='multipart/form-data' name='form'>
		<input type='hidden' id='num_query_fields' name='num_query_fields' value='1'>";

print  "<div id='query_table' style='max-width:700px;'>
		<table class='form_border' width=100%>
		<tr>
			<td class='header' style='color:#800000;width:120px;height:50px;'>
				{$lang['report_builder_16']}
			</td>
			<td class='header' colspan='2' style='height:50px;'>
				<input type='text' id='visible-TITLE' value='' size=60 maxlength=60 style='font-weight:bold;'
					onkeydown='if(event.keyCode == 13) return false;' 
					onchange=\"document.getElementById('__TITLE__').value=document.getElementById('visible-TITLE').value;\">
				<input type='hidden' name='__TITLE__' id='__TITLE__' value=''>
			</td>
		</tr>
		<tr>
			<td class='label'></td>
			<td class='label'>{$lang['report_builder_17']}</td>
			<td class='label'>{$lang['report_builder_18']} {$lang['global_06']}<br><span style='font-weight:normal;'>{$lang['report_builder_19']}</span></td>
		</tr>";
		
// Adding new report
if (!isset($_GET['query_id'])) {

	print  str_replace("{__fieldnum__}","1",$new_row)."
			</table>
			</div>";

// Editing existing report, run javascript to pre-populate some parts
} elseif (isset($_GET['query_id'])) {

	// Loop through all fields in the report and render each table row
	$i = 1;
	foreach (array_keys($this_query_array) as $this_field) 
	{
		if (isset($Proj->metadata[$this_field]) && substr($this_field, 0, 2) != "__") 
		{
			print str_replace("{__fieldnum__}", $i, $new_row);
			$i++;
		}
	}
	// Blank field at bottom
	print str_replace("{__fieldnum__}", $i, $new_row); 
	print  "</table>
			</div>";
	
	print  "<script type='text/javascript'>\n $(function(){\n";
	//Set title
	$this_query_array['__TITLE__'] = strip_tags(html_entity_decode($this_query_array['__TITLE__'], ENT_QUOTES));
	print "document.getElementById('visible-TITLE').value = '".str_replace("'","\'",$this_query_array['__TITLE__'])."';\n";
	print "document.getElementById('__TITLE__').value = '".str_replace("'","\'",$this_query_array['__TITLE__'])."';\n";
	//Set the two ordering fields
	if (isset($this_query_array['__ORDERBY1__'])) {
		list($this_orderby1,$this_orderby_ascdesc1) = explode(" ",trim($this_query_array['__ORDERBY1__']));
		print "document.getElementById('field_-ORDERBY1').value = '$this_orderby1';\n";
		print "document.getElementById('dropdown-field_-ORDERBY1').value = '".$field_name_type_validation[$this_orderby1]."';\n";
		print "document.getElementById('allfield_-ORDERBY1').value = '".$field_name_type_validation[$this_orderby1]."';\n";
		print "document.getElementById('ORDERBY1_ASCDESC').value = '$this_orderby_ascdesc1';\n";
	}
	if (isset($this_query_array['__ORDERBY2__'])) {
		list($this_orderby2,$this_orderby_ascdesc2) = explode(" ",trim($this_query_array['__ORDERBY2__']));
		print "document.getElementById('field_-ORDERBY2').value = '$this_orderby2';\n";
		print "document.getElementById('dropdown-field_-ORDERBY2').value = '".$field_name_type_validation[$this_orderby2]."';\n";
		print "document.getElementById('allfield_-ORDERBY2').value = '".$field_name_type_validation[$this_orderby2]."';\n";
		print "document.getElementById('ORDERBY2_ASCDESC').value = '$this_orderby_ascdesc2';\n";
	}	
	//Loop through each row of the table and pre-populate
	$k = 1;	
	foreach ($this_query_array as $this_field=>$this_where) {
		if ($this_field != "__TITLE__" && $this_field != "__ORDERBY1__" && $this_field != "__ORDERBY2__") {
			//Set the field name dropdowns
			print "if (document.getElementById('field_".$k."') != null) {\n";
			print "document.getElementById('field_".$k."').value = '$this_field';\n";
			print "document.getElementById('allfield_".$k."').value = '".$field_name_type_validation[$this_field]."';\n"; 
			print "document.getElementById('dropdown-field_".$k."').value = '".$field_name_type_validation[$this_field]."';\n";
			//Set all the operator dropdowns and limiter values
			$fieldPart = explode("-",$field_name_type_validation[$this_field]);			
			// If field has a legacy validation, then update it to non-legacy on the fly for this $Proj object
			if ($fieldPart[2] == 'int') {
				$fieldPart[2] = 'integer';
			} elseif ($fieldPart[2] == 'float') {
				$fieldPart[2] = 'number';
			} elseif (in_array($fieldPart[2], array('date', 'datetime', 'datetime_seconds'))) {
				$fieldPart[2] .= '_ymd';
			}
			list($this_field2,$this_oper,$this_value) = explode(" ",trim($this_where),3);	
			$this_oper = html_entity_decode(html_entity_decode($this_oper, ENT_QUOTES), ENT_QUOTES);
			$this_value = html_entity_decode(html_entity_decode($this_value, ENT_QUOTES), ENT_QUOTES);
			$this_value = substr($this_value,1,-1);
			// If field is a date[time] field, then format from YMD format to designated date format
			$this_valtype = $Proj->metadata[$this_field]['element_validation_type'];
			if ($this_oper != "" && (substr($this_valtype, -4) == '_mdy' || substr($this_valtype, -4) == '_dmy')) {
				$this_dateformat = substr($this_valtype, -3);
				$this_value = datetimeConvert($this_value, 'ymd', $this_dateformat);
			}
			// Set other values for field
			print "selectOperAndVal('span-operator_".$k."','span-condvalue_".$k."','".$fieldPart[1]."','".$fieldPart[2]."','".cleanHtml($this_value)."');\n";			
			print "document.getElementById('dropdown-operator_".$k."').value = '$this_oper';\n";
			print "document.getElementById('operator_".$k."').value = '$this_oper';\n";
			print "}\n";
			$k++;
		}
	}
	// Set next row number
	print "document.getElementById('num_query_fields').value = $k;\n";
	print "});\n</script>\n";
	
	
}



//Remove certain charcters from html strings to use in javascript
$repl = array("\n", "\t", "\r", "'", "/");
$orig = array("", "", "", "\'", "\/");
$new_row = str_replace($repl,$orig,$new_row);

//Set up javascript variables and functions to use in innerHTML replacing
print  "<script type='text/javascript'>
		var new_row = '$new_row';
		</script>";

		
//Set up submit/cancel buttons	
$save_button_text = $lang['report_builder_27'];
$cancel_button = "";	
if (isset($_GET['query_id'])) {
	$save_button_text = $lang['report_builder_28'];
	$cancel_button = "<input type='button' value='Cancel' onclick=\"window.location.href='".APP_PATH_WEBROOT."Reports/report_builder.php?pid=$project_id'+addGoogTrans();\">";
}

//Bottom of table with Ordering and Submit button		
print "<div style='max-width:700px;'>
	<table class='form_border' width=100%>
		<tr><td class='label' style='height:30px;color:#800000;' valign='bottom' colspan='3'>
		
			{$lang['report_builder_20']} <span style='font-size:7pt;font-weight:normal;'>{$lang['global_06']}</span>
			
		</td></tr>
		<tr><td class='label' style='width:120px;'>
		
			{$lang['report_builder_25']} ".str_replace("{__fieldnum__}","-ORDERBY1",$field_dropdown)."&nbsp;&nbsp;&nbsp;&nbsp;
			<select class='x-form-text x-form-field notranslate' style='padding-right:0;height:22px;' name='ORDERBY1_ASCDESC' id='ORDERBY1_ASCDESC'>
				<option value='ASC' selected>{$lang['report_builder_22']}</option>
				<option value='DESC'>{$lang['report_builder_23']}</option>
			</select>
			
		</td></tr>			
		<tr><td class='label' style='width:120px;'>
		
			{$lang['report_builder_26']} ".str_replace("{__fieldnum__}","-ORDERBY2",$field_dropdown)."&nbsp;&nbsp;&nbsp;&nbsp;
			<select class='x-form-text x-form-field notranslate' style='padding-right:0;height:22px;' name='ORDERBY2_ASCDESC' id='ORDERBY2_ASCDESC'>
				<option value='ASC' selected>{$lang['report_builder_22']}</option>
				<option value='DESC'>{$lang['report_builder_23']}</option>
			</select>
			
		</td></tr>
	</table>
	</div>
	<br>
	<div align='center' style='max-width:700px;'>		
		<div>
			<input type='submit' name='submit-button' value='$save_button_text' onclick=\"if(document.getElementById('visible-TITLE').value.length==0){
				alertbad(document.getElementById('visible-TITLE'),'{$lang['report_builder_24']}');return false;}\"> 
			$cancel_button
		</div>
	</div>
	</form>";


include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
