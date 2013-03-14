<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
require_once APP_PATH_DOCROOT . "Surveys/survey_functions.php";

// Validate request
if (!(isset($_GET['page']) && isset($Proj->forms[$_GET['page']]) 
	&& isset($_GET['survey_id']) && checkSurveyProject($_GET['survey_id']))) exit("ERROR!");

// Output the event list
$chooseEventRows = Design::getEventsAutomatedInvitesForForm($_GET['page']);

// Display each event as a separate row
foreach ($chooseEventRows as $this_event_id=>$attr) 
{
	if ($attr['active'] == '1') {
		// Add check icon with green text
		$img = RCView::img(array('src'=>'tick_small_circle.png','style'=>''));
		$color = 'green';
		$btnTxt = $lang['design_169'];
	} elseif ($attr['active'] == '0') {
		// Add check icon with green text
		$img = RCView::img(array('src'=>'bullet_delete.png','style'=>''));
		$color = '#800000';
		$btnTxt = $lang['design_169'];
	} else {
		// Add + with gray black text
		$img = RCView::span(array('style'=>'margin-right:2px;'), "+");
		$color = '#555';
		$btnTxt = $lang['design_387'];
	}
	// Output event as row
	print RCView::div(array('style'=>'padding:5px 0;font-size:12px;'), 
			RCView::button(array('class'=>'jqbuttonsm','style'=>"margin-right:5px;color:$color;",
				'onclick'=>"setUpConditionalInvites({$_GET['survey_id']}, $this_event_id, '{$_GET['page']}')"), 
				$img . $btnTxt
			) . 
			$attr['name']
		  );
}