<?php

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
require_once APP_PATH_DOCROOT . 'ProjectGeneral/form_renderer_functions.php';

// Put all info for this field and get variables of all fields in this matrix group
$field_info = ($status > 0) ? $Proj->metadata_temp[$_POST['field_name']] : $Proj->metadata[$_POST['field_name']];
if (!is_array($field_info)) exit('[]');
$matrix_field_names = ($status > 0) ? $Proj->matrixGroupNamesTemp[$field_info['grid_name']] : $Proj->matrixGroupNames[$field_info['grid_name']];
$firstMatrixField = $matrix_field_names[0];

// Modify return values
$choices = array();
foreach (parseEnum($field_info['element_enum']) as $code=>$label) {
	$choices[] = "$code, $label";
}
$choices = label_decode(implode("|", $choices));

// Get field info of ALL fields in this matrix group
$field_labels = array();
$field_names = array();
$field_reqs = array();
$field_quesnums = array();
$section_header = "";
foreach ($matrix_field_names as $thisfield) {
	// Get metadata info for this field
	$thisfield_info = ($status > 0) ? $Proj->metadata_temp[$thisfield] : $Proj->metadata[$thisfield];
	// Set section header value if this is the first field in the matrix group
	if ($thisfield == $firstMatrixField) {
		$section_header = str_replace(array("\r","\n"), array("",""), nl2br(label_decode($thisfield_info['element_preceding_header'])));
	}
	// Add info to separate arrays
	$field_labels[] = '"'.cleanHtml2(label_decode($thisfield_info['element_label'])).'"';
	$field_names[] = '"'.$thisfield_info['field_name'].'"';
	$field_reqs[] = $thisfield_info['field_req'];
	$field_quesnums[] = '"'.cleanHtml2(label_decode($thisfield_info['question_num'])).'"';
}

// Output JSON
print '{"num_fields":'.count($matrix_field_names).',"field_labels":['.implode(',', $field_labels).'],'
	. '"field_names":['.implode(',', $field_names).'],"field_reqs":['.implode(',', $field_reqs).'],'
	. '"field_type":"'.$field_info['element_type'].'","choices":"'.cleanHtml2($choices).'",'
	. '"grid_name":"'.$field_info['grid_name'].'","section_header":"'.cleanHtml2($section_header).'",'
	. '"question_nums":['.implode(',', $field_quesnums).']}';