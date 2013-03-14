<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
require_once APP_PATH_DOCROOT . "ProjectGeneral/form_renderer_functions.php";


## DATA ENTRY PROGRESS TABLE

// Get form status of all records
$formStatusAllRecords = Records::getFormStatus(PROJECT_ID);
$recordNames = array_keys($formStatusAllRecords);
$numRecords = count($formStatusAllRecords);

// If this is a longitudinal project with multiple arms, then fill array denoting which arm that a record belongs to
$recordsPerArm = array();
if ($multiple_arms) {
	$recordsPerArm = Records::getRecordListPerArm(PROJECT_ID);
}

## CUSTOM RECORD LABEL & SECONDARY UNIQUE FIELD labels
$extra_record_labels = array();
// Customize the Record ID pulldown menus using the SECONDARY_PK appended on end, if set.
if ($secondary_pk != '' && !$is_child)
{
	$sql = "select record, value from redcap_data where project_id = $project_id and field_name = '$secondary_pk' 
			and event_id = " . $Proj->getFirstEventIdArm(getArm());
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q)) 
	{
		$extra_record_labels[$row['record']] = " " . RCView::span(array('class'=>'nowrap','style'=>'font-size:11px;color:#800000;'), 
				"(" . $Proj->metadata[$secondary_pk]['element_label'] . " " . str_replace("\n", " ", $row['value']) . ")"
		);
	}
	db_free_result($q);
}		
// [Retrieval of ALL records] If Custom Record Label is specified (such as "[last_name], [first_name]"), then parse and display
// ONLY get data from FIRST EVENT
if (!empty($custom_record_label)) 
{
	foreach (getCustomRecordLabels($custom_record_label, $Proj->getFirstEventIdArm(getArm())) as $this_record=>$this_custom_record_label)
	{
		if (!isset($extra_record_labels[$this_record])) {
			$extra_record_labels[$this_record] = '';
		}
		$extra_record_labels[$this_record] .= " " . RCView::span(array('class'=>'nowrap','style'=>'font-size:11px;color:#800000;'), 
			str_replace("\n", " ", $this_custom_record_label)
		);;
	}
}

// Remove records from $formStatusAllRecords array based upon page number
$num_per_page = 100;
$limit_begin  = 0;
if (isset($_GET['pagenum']) && is_numeric($_GET['pagenum']) && $_GET['pagenum'] > 1) {
	$limit_begin = ($_GET['pagenum'] - 1) * $num_per_page;
}
$formStatusAllRecords = array_slice($formStatusAllRecords, $limit_begin, $num_per_page, true);
$numRecordsThisPage = count($formStatusAllRecords);
// Build drop-down list of page numbers
$num_pages = ceil($numRecords/$num_per_page);	
$pageNumDropdownOptions = array();
for ($i = 1; $i <= $num_pages; $i++) {
	$end_num   = $i * $num_per_page;
	$begin_num = $end_num - $num_per_page + 1;
	$value_num = $end_num - $num_per_page;
	if ($end_num > $numRecords) $end_num = $numRecords;
	$pageNumDropdownOptions[$i] = "\"{$recordNames[$begin_num-1]}\" through \"{$recordNames[$end_num-1]}\"";
}
if ($num_pages == 0) {
	$pageNumDropdownOptions[0] = "0";
}
$pageNumDropdown =  RCView::div(array('class'=>'chklist','style'=>'padding:8px 15px 7px;margin:5px 0;max-width:670px;'),
						$lang['data_entry_177'] . 
						RCView::select(array('class'=>'x-form-text x-form-field','style'=>'margin-left:8px;margin-right:4px;padding-right:0;height:22px;',
							'onchange'=>"showProgress(1);window.location.href=app_path_webroot+page+'?pid='+pid+'&pagenum='+this.value;"), 
							$pageNumDropdownOptions, $_GET['pagenum']) .
						$lang['survey_133'].
						RCView::span(array('style'=>'font-weight:bold;margin:0 4px;font-size:13px;'), $numRecords) .
						$lang['data_entry_173']
					);

// Determine if records also exist as a survey response for some instruments
$surveyResponses = array();
if ($surveys_enabled) {
	$surveyResponses = Survey::getResponseStatus($project_id, array_keys($formStatusAllRecords));
}

// Obtain a list of all instruments used for all events (used to iterate over header rows and status rows)
$formsEvents = array();
// Loop through each arm
foreach ($Proj->events as $this_arm=>$arm_attr) {
	// Loop through each instrument
	foreach ($Proj->forms as $form_name=>$form_attr) {
		// If user does not have form-level access to this form, then do not display it
		if (!isset($user_rights['forms'][$form_name]) || $user_rights['forms'][$form_name] < 1) continue;
		// Loop through each event and output each where this form is designated
		foreach ($Proj->eventsForms as $this_event_id=>$these_forms) {
			// If event does not belong to the current arm OR the form has not been designated for this event, then go to next loop
			if (!($arm_attr['events'][$this_event_id] && in_array($form_name, $these_forms))) continue; 
			// Add to array
			$formsEvents[] = array('form_name'=>$form_name, 'event_id'=>$this_event_id, 'form_label'=>$form_attr['menu']);
		}
	}
}

// HEADERS: Add all row HTML into $rows. Add header to table first.
$hdrs = RCView::td(array('class'=>'header','style'=>'text-align:center;color:#800000;padding:5px 10px;vertical-align:bottom;'), $table_pk_label);
foreach ($formsEvents as $attr) {
	// Add column
	$hdrs .= RCView::td(array('class'=>'header','style'=>'font-size:11px;text-align:center;width:35px;padding:5px;white-space:normal;vertical-align:bottom;'), 
				$attr['form_label'] .
				(!$longitudinal ? "" : RCView::div(array('style'=>'font-weight:normal;color:#800000;'), $Proj->eventInfo[$attr['event_id']]['name_ext']))
			);
}
$rows = RCView::tr('', $hdrs);


// IF NO RECORDS EXIST, then display a single row noting that
if (empty($formStatusAllRecords))
{
	$rows .= RCView::tr('', 
				RCView::td(array('class'=>'data','colspan'=>count($formsEvents)+1,'style'=>'font-size:12px;padding:10px;color:#555;'), 
					$lang['data_entry_179']
				)
			);
}

// ADD ROWS: Get form status values for all records/events/forms and loop through them
foreach ($formStatusAllRecords as $this_record=>$rec_attr) 
{
	// For each record (i.e. row), loop through all forms/events
	$this_row = RCView::td(array('class'=>'data','style'=>'font-size:12px;padding:0 10px;'), 
					$this_record .
					// Display custom record label or secondary unique field (if applicable)
					(isset($extra_record_labels[$this_record]) ? '&nbsp;' . $extra_record_labels[$this_record] : '')
				);
	// Loop through each column
	foreach ($formsEvents as $attr) 
	{
		// If a longitudinal project with multiple arms, do NOT display the icon if record does NOT belong to this arm
		if ($multiple_arms && !isset($recordsPerArm[$Proj->eventInfo[$attr['event_id']]['arm_num']][$this_record])) {
			$td = '';
		} else {	
			// If it's a survey response, display different icons
			if (isset($surveyResponses[$this_record][$attr['event_id']][$attr['form_name']])) {			
				//Determine color of button based on response status
				switch ($surveyResponses[$this_record][$attr['event_id']][$attr['form_name']]) {
					case '2':
						$img = 'tick_circle_frame.png';
						break;
					default:
						$img = 'circle_orange_tick.png';
				}
			} else {	
				// Set image HTML
				if ($rec_attr[$attr['event_id']][$attr['form_name']] == '2') {
					$img = 'circle_green.png';
				} elseif ($rec_attr[$attr['event_id']][$attr['form_name']] == '1') {
					$img = 'circle_yellow.png';
				} else {
					$img = 'circle_red.gif';
				}
			}
			$td = 	RCView::a(array('href'=>APP_PATH_WEBROOT."DataEntry/index.php?pid=$project_id&id=$this_record&page={$attr['form_name']}&event_id={$attr['event_id']}"),
						RCView::img(array('src'=>$img,'class'=>'imgfix2'))
					);
		}
		// Add column to row
		$this_row .= RCView::td(array('class'=>'data','style'=>'text-align:center;height:20px;'), $td);
	}
	$rows .= RCView::tr('', $this_row);
}





// Page header
include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
// Page title
renderPageTitle("<img src='".APP_PATH_IMAGES."application_view_icons.png' class='imgfix2'> {$lang['global_91']} {$lang['bottom_61']}");
// Instructions and Legend for colored status icons
print	RCView::table(array('style'=>'width:700px;table-layout:fixed;','cellspacing'=>'0'),
			RCView::tr('',
				RCView::td(array('style'=>'padding:10px 30px 10px 0;','valign'=>'top'),
					// Instructions
					$lang['data_entry_176']
				) .
				RCView::td(array('valign'=>'top','style'=>'width:220px;'),
					// Legend
					RCView::div(array('class'=>'chklist','style'=>'background-color:#eee;border:1px solid #ccc;'),
						RCView::b($lang['data_entry_178']) . RCView::br() .
						RCView::img(array('src'=>'circle_red.gif','class'=>'imgfix')) . $lang['global_92'] . RCView::br() .
						RCView::img(array('src'=>'circle_yellow.png','class'=>'imgfix')) . $lang['global_93'] . RCView::br() .
						RCView::img(array('src'=>'circle_green.png','class'=>'imgfix')) . $lang['survey_28'] . 
						(!$surveys_enabled ? "" :
							RCView::br() .
							RCView::img(array('src'=>'circle_orange_tick.png','class'=>'imgfix')) . $lang['global_95'] . RCView::br() .
							RCView::img(array('src'=>'tick_circle_frame.png','class'=>'imgfix')) . $lang['global_94']
						)
					)
				)
			)
		);
// Table of records
print	$pageNumDropdown .
		RCView::table(array('class'=>'form_border','style'=>'margin:20px 0;'), $rows) .
		($numRecordsThisPage > 30 ? $pageNumDropdown : "");

// Page footer
include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';