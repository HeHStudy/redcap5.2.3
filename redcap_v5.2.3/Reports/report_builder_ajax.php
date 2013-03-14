<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

if (is_numeric($_GET['field_num']) && isset($Proj->metadata[$_GET['field_name']])) 
{
	$field_name = $_GET['field_name'];

	$row['element_type'] = $Proj->metadata[$field_name]['element_type'];
	$row['element_enum'] = $Proj->metadata[$field_name]['element_enum'];
	
	// For "sql" field type, retrieve query results as enum string
	if ($row['element_type'] == "sql")
	{
		$row['element_enum'] = getSqlFieldEnum($row['element_enum']);
	} 
	elseif ($row['element_type'] == "yesno")
	{
		$row['element_enum'] = YN_ENUM;
	} 
	elseif ($row['element_type'] == "truefalse")
	{
		$row['element_enum'] = TF_ENUM;
	}
	
	// Render select box
	print  "<select class='x-form-text x-form-field notranslate' style='padding-right:0;height:22px;' id='visible-condvalue_{$_GET['field_num']}' onchange=\"document.getElementById('condvalue_{$_GET['field_num']}').value=this.value;\"><option value=''></option>";
	foreach (parseEnum($row['element_enum']) as $key=>$value) 
	{
		$label = strip_tags(label_decode($value));
		if (strlen($label) > 50) $label = substr($label, 0, 31) . " ... " . substr($label, -15);
		print  "<option value=\"$key\">$label</option>";
	}
	print  "</select>";

}