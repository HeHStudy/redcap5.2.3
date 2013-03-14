<?php
/*****************************************************************************************
**  REDCap is only available through ACADMEMIC USER LICENSE with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

//change initial server values to account for a lot of processing


//Required files
require_once APP_PATH_DOCROOT . 'ProjectGeneral/form_renderer_functions.php';
require_once APP_PATH_DOCROOT . 'Design/functions.php';

include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
include APP_PATH_DOCROOT . 'StandardsMapping/tool.php';

//print "<script type='text/javascript'>";
//print "$('#fancy, #fancy2').tooltip(";
//print "{track: true,delay: 0,showURL: false,opacity: 1,fixPNG: true,showBody: ' - ',extraClass: 'pretty fancy',top: -15,left: 5 }";
//print ");";
//print "</script>";

print "<div id='main'>";

renderPageTitle("<div style='float:left;'>
					<img src='".APP_PATH_IMAGES."bookmark.png' class='imgfix2'>  Module for Standards Mapping" 
					. ($draft_mode > 0 ? " <span style='color:red;'>(DRAFT MODE)</span>" : "") . "
				 </div><br>");

print  "<div style='width:660px;padding:5px 10px 5px 30px;'>
		<p>
			The Online Field Mapping Tool will allow you to make database modifications to fields and data entry forms very easily
			using only your web browser. Below you have the options to select an existing form to edit, to delete a form, to create a 
			new form, and to reorder your forms as they are displayed.
		</p>
		</div><br/>";
		
//get project info
$projectSql = "select p.app_title, m.form_menu_description
               from redcap_projects p
                 left join redcap_metadata m on p.project_id = m.project_id
               where
                 p.project_id = {$_GET['pid']} 
                 and form_name = '{$_GET['page']}'
                 and form_menu_description is not null";
$projectInfo = db_query($projectSql);
$projectInfoArray = db_fetch_array($projectInfo);
$projectName = $projectInfoArray['app_title'];
$projectForm = $projectInfoArray['form_menu_description'];
//get fields
$fieldSql = "select m.*,p.app_title 
			from redcap_metadata m join redcap_projects p on m.project_id = p.project_id 
			where 
				m.project_id = {$_GET['pid']} 
				and m.form_name = '{$_GET['page']}'
				and m.field_name not like '%_complete'
			order by m.field_order";
//print "<p>$fieldSql</p>";
$fields = db_query($fieldSql);


	
## Online Form Mapping Tool
//print  "<div style='border:1px solid #99B5B7;background-color:#E8ECF0;max-width:700px;margin:20px 0;'>";
//	
//print  "<h3 style='padding:5px 10px;margin:0;border-bottom:1px solid #ccc;color:#222;'><img src='".APP_PATH_IMAGES."blog_pencil.png' 
//		class='imgfix2'> Online Field Mapping Tool</h3>";

print "<div style='max-width:660px;border:1px solid #d0d0d0;padding:0px 5px 5px 5px;background-color:#f5f5f5;'>
						<h3 style='border-bottom: 1px solid #aaa; padding: 3px; font-weight: bold;color:#800000;'>
						<img src='".APP_PATH_IMAGES."pencil.png' class='imgfix2'> Modify Field Mappings</h3>";

print  "<div id='basic-modal' style='width:700px;'>
		<table border='0' width='620px' cellpadding='2' cellspacing='2' align='center'>
		<tr>
			<th colspan='3'>
				<table>
					<tr><th><b>Project:&nbsp;&nbsp;</b></th><th>$projectName</th><tr>
					<tr><th><b>Form: </b></th><th>$projectForm</th><tr>
                </table>
            </th>
        </tr>
		<tr><th colspan='3'>&nbsp;</th><tr>
		<tr>
			<th width='210px'><b>Field Name</b></th>
			<th width='340px'><b>Mapped Codes</b></th>
			<th width='70px'><b>&nbsp;</b></th>
		</tr>
		<tr><td colspan='3'><hr></td></tr>";
		
while($field = db_fetch_array($fields)) {
	
	$fieldType = $field['element_type'];
	if($field['element_type'] == 'text' && ($field['element_validation_type'] == 'int' 
										 || $field['element_validation_type'] == 'float'
										 || $field['element_validation_type'] == 'date'
										 || $field['element_validation_type'] == 'time')) {
		$fieldType = $field['element_validation_type'];
	}
			
	$codeSql ="select map.standard_map_id, code.standard_code_id as code_id, code.standard_code as code, 
					code.standard_code_desc as description, std.standard_name as standard, 
					std.standard_version as version, map.data_conversion as conversion, meta.element_label
				from redcap_standard_map map, redcap_standard_code code, redcap_standard std, redcap_metadata meta
				where
					map.project_id = {$field['project_id']}
					and map.field_name = '{$field['field_name']}'
					and map.standard_code_id = code.standard_code_id
					and code.standard_id = std.standard_id
					and map.project_id = meta.project_id
					and map.field_name = meta.field_name
				order by standard, version, code";
	$codes = db_query($codeSql);
	//print "<tr><td colspan='3'>$codeSql</td></tr>";			
	print "<tr>";
	print "<td valign='top'><span id='pretty' title='Label: {$field['element_label']}'>{$field['field_name']}</span><br/>";
	//print "<a style='font-size:10px' href='javascript:alert(\"detail\");'>show detail</a>";
	print "</td>";
	print "<td valign='top'>";
	print "<table id='map-list-{$field['field_name']}' border='0' width='100%' cellspacing='2' cellpadding='2'>";
	while($code = db_fetch_array($codes)) {
		print "<tr id='stdmapid{$code['standard_map_id']}'>";
		print "<td width='50%'>{$code['standard']} {$code['version']}</td>";
		$codeText = $code['code'];
		$style = '';
		//checkboxes have multiple mappings (one for each value) 
		//distinguished from an actual code by italics
		if($fieldType == 'checkbox') {
			$style = "style='font-style:italic;'";
		}
		print "<td width='50%' $style><a href='javascript:openMapper({$code['standard_map_id']},{$field['project_id']},\"{$field['app_title']}\",\"{$field['form_name']}\",\"{$field['field_name']}\",\"$fieldType\");'>$codeText</a></td>";
		print "</tr>";
	}
	print "</table>";
	print "</td>";
	print "<td style='text-align:right;vertical-align:bottom;'>";
	print "<button onclick=\"openMapper(-1,{$field['project_id']},'{$field['app_title']}','{$field['form_name']}','{$field['field_name']}','$fieldType');\" style='font-size:10px'>add code</button>";
	print "</td>";
	print "</tr>";
	print "<tr><td colspan='3'><hr></td></tr>";
}
					
print "</table>";
print "<br/>";
print "</div>";

print "</div>";

print "</div>";

include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
