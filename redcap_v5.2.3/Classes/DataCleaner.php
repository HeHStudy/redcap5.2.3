<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


/**
 * DATA CLEANER
 */
class DataCleaner
{

	// Obtain array of fields that have a history for the data cleaner for a given record/event
	static public function fieldsWithHistory($record, $event_id, $form)
	{
		// Query table for fields that have a history (at least one row in cleaner_log table)
		$fieldsWithHistory = array();
		$sql = "select distinct c.field_name, c.status from redcap_data_cleaner c, redcap_data_cleaner_log l, redcap_metadata m 
				where c.project_id = " . PROJECT_ID . " and c.event_id = $event_id and c.record = '".prep($record)."' 
				and c.cleaner_id = l.cleaner_id and m.project_id = c.project_id and m.field_name = c.field_name 
				and m.form_name = '".prep($form)."'";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q))
		{
			// Add field to array as key with status as value
			$fieldsWithHistory[$row['field_name']] = $row['status'];
		}
		// Return the array
		return $fieldsWithHistory;
	}

	
	// Obtain data cleaner history for a given field/event/record and return as array
	static public function getFieldHistory($record, $event_id, $field)
	{
		// Query table for history
		$dc_history = array();
		$sql = "select l.*, c.status, c.high_priority from redcap_data_cleaner c, redcap_data_cleaner_log l 
				where c.project_id = " . PROJECT_ID . " and c.event_id = $event_id 
				and c.field_name = '".prep($field)."' and c.record = '".prep($record)."' 
				and c.cleaner_id = l.cleaner_id order by l.clog_id";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q))
		{
			// Add clog_id as key of array
			$clog_id = $row['clog_id'];
			unset($row['clog_id']);
			// Add row to array
			$dc_history[$clog_id] = $row;
		}
		// Return the array
		return $dc_history;
	}

	
	// Display data cleaner history in table format
	static public function displayFieldHistory($record, $event_id, $field)
	{
		global $lang;
		
		// Obtain data cleaner history  as array
		$dc_history = self::getFieldHistory($record, $event_id, $field);
		
		// Initialize variables
		$h = $r = $currentStatus = '';
		$prevUserAttr = array();
		$bgColor = $oddBg = '#E3E3E3';
		$evenBg = '#F3F3F3';
		// Build rows of existing items in this thread
		if (!empty($dc_history))
		{
			// Reverse the sorting so it's descending chronologically
			krsort($dc_history);
			$prevUserAttrKey = null;
			foreach ($dc_history as $clog_id=>$attr)
			{
				// Get clog_id of last item in thread (will be first in the array)
				if ($prevUserAttrKey == null) $prevUserAttrKey = $clog_id;
				// Set CSS class for row/section
				$bgColor = ($bgColor == $oddBg) ? $evenBg : $oddBg;
				// Render row/section
				$r .= self::renderFieldHistoryExistingSection($clog_id, $attr, $bgColor);
			}
			// Get value of current status and high priority value
			$prevUserAttr = $dc_history[$prevUserAttrKey];
			$currentStatus = $prevUserAttr['status'];
			$highPriority = $prevUserAttr['high_priority'];
		}
		// Render whole thread as a table
		$h .= RCView::table(array('id'=>'existingDCHistory','class'=>'form_border','cellspacing'=>'0',
				'style'=>'width:100%;'), 
				// Rows for adding NEW COMMENT/ATTRIBUTES (if not Closed)
				($currentStatus == 'CLOSED' ? '' :
					self::renderFieldHistoryNewForm($record, $event_id, $field, $prevUserAttr) .
					// Add spacer row to separate
					RCView::tr('', 
						RCView::td(array('colspan'=>'4', 'style'=>'padding:5px;border-left:1px solid #fff;border-right:1px solid #fff;'), '&nbsp;')
					)
				) .
				// SECTION HEADER (only display if some rows exist already)
				($r == '' ? '' :
					RCView::tr('', 
						RCView::td(array('class'=>'label_header','style'=>'padding:5px 8px;width:110px;'), 
							"Date/Time"
						) .  
						RCView::td(array('class'=>'label_header','style'=>'padding:5px 8px;width:130px;'), 
							$lang['global_17']
						) .  
						RCView::td(array('class'=>'label_header','style'=>'text-align:left;padding:5px 8px 5px 12px;','colspan'=>'2'), 
							($currentStatus == '' ? '' : 
								"Status: " .
								RCView::span(array('style'=>'color:green;'), $currentStatus) .
								// Display "high priority" (if applicable)
								(!$highPriority ? "" : 
									RCView::span(array('style'=>'margin-left:20px;color:#A00000;'), 
										RCView::img(array('src'=>'exclamation.png','class'=>'imgfix')) .
										"High priority"
									)
								)
							)
						)
					)
				) .
				// Rows for EXISTING COMMENTS/ATTRIBUTES
				$r
			  );
		// Output html
		return $h;
	}

	
	// Render single section of data cleaner history table
	static private function renderFieldHistoryExistingSection($clog_id, $attr=array(), $bgColor)
	{
		global $lang;
		// Get username of initiator
		$userInitiator = User::getUserInfoByUiid($attr['user_id_current']);
		// Get username of person assigned
		$userAssignedFull = "";
		if ($attr['user_id_next_action'] != '') {
			$userAssigned = User::getUserInfoByUiid($attr['user_id_next_action']);
			$userAssignedFull = "{$userAssigned['username']} ({$userAssigned['user_firstname']} {$userAssigned['user_lastname']})";
		}
		// Set thread status type
		$assignedUserResponded = ($attr['responded_to_request'] && $attr['response_requested_next_action'] == '');
		$initiatorAssignedUser = ($attr['response_requested_next_action'] != '');
		// Determine rowspan
		$rowspan = ($assignedUserResponded || $initiatorAssignedUser) ? 4 : 1;
		// Render this row or section of rows
		$h = RCView::tr(array('id'=>'dc-clog_id_'.$clog_id), 
				// Date/time
				RCView::td(array('class'=>'data', 'rowspan'=>$rowspan, 'style'=>'border:1px solid #ccc;text-align:center;width:110px;'."background-color:$bgColor;"), 
					format_ts_mysql($attr['ts'])
				) .
				// Current user
				RCView::td(array('class'=>'data', 'rowspan'=>$rowspan, 'style'=>'border:1px solid #ccc;text-align:center;width:130px;'."background-color:$bgColor;"), 
					"{$userInitiator['username']}<br>({$userInitiator['user_firstname']} {$userInitiator['user_lastname']})"
				) .
				// Comment label
				RCView::td(array('class'=>'data', 'style'=>'border:1px solid #ccc;padding:2px 5px;color:#777;width:140px;'."background-color:$bgColor;"), 
					"Comment:"
				) .
				// Comment value
				RCView::td(array('class'=>'data', 'style'=>'border:1px solid #ccc;padding:2px 5px;'."background-color:$bgColor;"), 
					nl2br(RCView::escape(filter_tags(br2nl($attr['comment'])),false))
				)
			);
		## IF ASSIGNED USER RESPONDED TO REQUEST
		if ($assignedUserResponded)
		{
			$h .= 
				// User that initiated thread
				RCView::tr(array('id'=>'dc-clog_id_a'.$clog_id), 
					// Label
					RCView::td(array('class'=>'data', 'style'=>'border:1px solid #ccc;padding:2px 5px;color:#777;width:140px;'."background-color:$bgColor;"), 
						"Responded to user:"
					) .
					// Value
					RCView::td(array('class'=>'data', 'style'=>'border:1px solid #ccc;padding:2px 5px;'."background-color:$bgColor;"), 
						$userAssignedFull
					)
				) . 
				// Data change was performed
				RCView::tr(array('id'=>'dc-clog_id_b'.$clog_id), 
					// Label
					RCView::td(array('class'=>'data', 'style'=>'border:1px solid #ccc;padding:2px 5px;color:#777;width:140px;'."background-color:$bgColor;"), 
						"Data changes were performed:"
					) .
					// Value
					RCView::td(array('class'=>'data', 'style'=>'border:1px solid #ccc;padding:2px 5px;'."background-color:$bgColor;"), 
						($attr['change_performed'] ? $lang['design_100'] : $lang['design_99'])
					)
				) . 
				// Sent email back to user who initiated the thread
				RCView::tr(array('id'=>'dc-clog_id_c'.$clog_id), 
					// Label
					RCView::td(array('class'=>'data', 'style'=>'border:1px solid #ccc;padding:2px 5px;color:#777;width:140px;'."background-color:$bgColor;"), 
						"Sent email:"
					) .
					// Value
					RCView::td(array('class'=>'data', 'style'=>'border:1px solid #ccc;padding:2px 5px;'."background-color:$bgColor;"), 
						($attr['send_email'] ? $lang['design_100'] : $lang['design_99'])
					)
				);
		}
		## IF INITIATOR REQUIRED A RESPONSE FROM ANOTHER USER
		elseif ($initiatorAssignedUser)
		{
			$h .= 
				// User assigned
				RCView::tr(array('id'=>'dc-clog_id_a'.$clog_id), 
					// Label
					RCView::td(array('class'=>'data', 'style'=>'border:1px solid #ccc;padding:2px 5px;color:#777;width:140px;'."background-color:$bgColor;"), 
						"Assigned to user:"
					) .
					// Value
					RCView::td(array('class'=>'data', 'style'=>'border:1px solid #ccc;padding:2px 5px;'."background-color:$bgColor;"), 
						$userAssignedFull
					)
				) . 
				// Requires data change
				RCView::tr(array('id'=>'dc-clog_id_b'.$clog_id), 
					// Label
					RCView::td(array('class'=>'data', 'style'=>'border:1px solid #ccc;padding:2px 5px;color:#777;width:140px;'."background-color:$bgColor;"), 
						"Requires data change:"
					) .
					// Value
					RCView::td(array('class'=>'data', 'style'=>'border:1px solid #ccc;padding:2px 5px;'."background-color:$bgColor;"), 
						($attr['change_required_next_action'] ? $lang['design_100'] : $lang['design_99'])
					)
				) . 
				// Sent email to assigned user
				RCView::tr(array('id'=>'dc-clog_id_c'.$clog_id), 
					// Label
					RCView::td(array('class'=>'data', 'style'=>'border:1px solid #ccc;padding:2px 5px;color:#777;width:140px;'."background-color:$bgColor;"), 
						"Sent email:"
					) .
					// Value
					RCView::td(array('class'=>'data', 'style'=>'border:1px solid #ccc;padding:2px 5px;'."background-color:$bgColor;"), 
						($attr['send_email'] ? $lang['design_100'] : $lang['design_99'])
					)
				);
		}
		// Output html
		return $h;
	}

	
	// Render form to add/modify data cleaner history
	static public function renderFieldHistoryNewForm($record, $event_id, $field, $prevUserAttr=array())
	{
		global $lang;
		// Get username of initiator
		if (isset($prevUserAttr['user_id_current']) && is_numeric($prevUserAttr['user_id_current'])) {
			$userInitiator = User::getUserInfoByUiid($prevUserAttr['user_id_current']);
		}
		// Set "new comment" label
		$commentLabel = ($prevUserAttr['response_requested_next_action'] != '' || $prevUserAttr['change_performed']) 
			? "Response comment:" : "New comment:";
		// Set row span for "save" button cell
		if ($prevUserAttr['response_requested_next_action'] != '') {
			$rowspan = '3';
		} else {
			$rowspan = '2';
		}
		## Rows for adding NEW COMMENT/ATTRIBUTES
		$h = RCView::tr(array(), 
				// Save button
				RCView::td(array('id'=>'tdNewButton','class'=>'data', 'rowspan'=>$rowspan, 'style'=>'background-color:#EFF6E8;border:1px solid #ddd;text-align:center;width:110px;'), 
					RCView::button(array('class'=>'jqbuttonmed','onclick'=>"dataCleanerSave('$field', $event_id, '".cleanHtml($record)."');"), 
						RCView::img(array('src'=>'add.png','style'=>'vertical-align:middle;')) .
						RCView::span(array('style'=>'font-weight:bold;font-family:arial;color:green;vertical-align:middle;'), "Save")
					)
				) .
				// New comment label
				RCView::td(array('class'=>'data', 'style'=>'background-color:#EFF6E8;border:1px solid #ddd;padding:2px 5px;width:130px;'), 
					$commentLabel
				) .
				// Comment value
				RCView::td(array('class'=>'data', 'colspan'=>'2', 'style'=>'background-color:#EFF6E8;border:1px solid #ddd;padding:2px 5px;'), 
					RCView::textarea(array('id'=>'dc-comment','class'=>'x-form-field notesbox','style'=>'height:45px;width:97%;'))
				)
			);
		## IF USER IS CLOSING THREAD OR RETURNING BACK TO ASSIGNED USER
		if ($prevUserAttr['responded_to_request'])
		{
			$h .=
				// Choose thread status: close or return to user
				RCView::tr(array(),
					RCView::td(array('class'=>'data', 'colspan'=>'3', 'style'=>'background-color:#EFF6E8;border:1px solid #ddd;'),
						RCView::radio(array('name'=>'dc-status','id'=>'dc-status1','value'=>'CLOSED','checked'=>'checked','onclick'=>"
							$('tr.dcRespReq').hide();$('#tdNewButton').attr('rowspan', $('#tdNewButton').attr('rowspan')*1-3 );")) . 
						"Close the query" .
						RCView::br() . 
						RCView::radio(array('name'=>'dc-status','id'=>'dc-status0','value'=>'OPEN','onclick'=>"
							$('tr.dcRespReq').show();$('#tdNewButton').attr('rowspan', $('#tdNewButton').attr('rowspan')*1+3 );")) . 
						"Send back for further attention"
					)
				) .
				// Assign this to another user
				RCView::tr(array('id'=>'tr-dc-user_id_next_action','class'=>'dcRespReq'),
					RCView::td(array('class'=>'data', 'style'=>'background-color:#EFF6E8;border:1px solid #ddd;padding:2px 5px;width:130px;'), 
						"Assign to user:") .
					RCView::td(array('class'=>'data', 'colspan'=>'2','style'=>'background-color:#EFF6E8;border:1px solid #ddd;padding:5px 2px;'), 
						// Drop-down list of all users in user_information table (excluding current user)
						User::dropDownListAllUsernames('dc-user_id_next_action', $prevUserAttr['user_id_current'], array(USERID), '', true) .
						// Send email to assigned user (yes/no)
						RCView::div(array('style'=>'color:#555;font-size:11px;'), 
							RCView::checkbox(array('id'=>'dc-send_email','class'=>'imgfix2')) .
							"Send email notification"
						)
					)
				) .
				// Type of response requested
				RCView::tr(array('id'=>'tr-dc-response_requested_next_action','class'=>'dcRespReq'),
					RCView::td(array('class'=>'data', 'style'=>'background-color:#EFF6E8;border:1px solid #ddd;padding:2px 5px;width:130px;'), 
						"Type of response requested:") .
					RCView::td(array('class'=>'data', 'colspan'=>'2', 'style'=>'background-color:#EFF6E8;border:1px solid #ddd;'), 
						RCView::select(array('id'=>'dc-response_requested_next_action','class'=>'x-form-text x-form-field','style'=>'padding-right:0;height:22px;'),
							array(''=>"-- choose response type --", 'ACKNOWLEDGEMENT'=>"Acknowledgement", 
								  'COMMENT'=>"Comment / notes", 'FILEUPLOAD'=>"File upload", 'COMPLETEFIELD'=>"Complete the field")
						)
					)
				) .
				// Require data change to field (yes/no)
				RCView::tr(array('id'=>'tr-dc-change_required_next_action','class'=>'dcRespReq'),
					RCView::td(array('class'=>'data', 'style'=>'background-color:#EFF6E8;border:1px solid #ddd;padding:2px 5px;width:130px;'), 
						"Are data changes required?") .
					RCView::td(array('class'=>'data', 'colspan'=>'2', 'style'=>'background-color:#EFF6E8;border:1px solid #ddd;'), 
						RCView::checkbox(array('id'=>'dc-change_required_next_action'))
					)
				);
		}
		## IF USER IS INITIATING THREAD
		elseif (empty($prevUserAttr) || ($prevUserAttr['user_id_next_action'] == '' && $prevUserAttr['response_requested_next_action'] == ''))
		{
			$h .=
				// Require response from other user?
				RCView::tr(array(),
					RCView::td(array('class'=>'data', 'colspan'=>'3', 'style'=>'background-color:#EFF6E8;border:1px solid #ddd;padding:5px;color:#800000;width:140px;'), 
						RCView::checkbox(array('class'=>'imgfix2', 'onclick'=>"
							if ($(this).prop('checked')) {
								$('tr.dcRespReq').show();
								$('#tdNewButton').attr('rowspan', $('#tdNewButton').attr('rowspan')*1+4 );
							} else {
								$('tr.dcRespReq').hide();
								$('#tdNewButton').attr('rowspan', $('#tdNewButton').attr('rowspan')*1-4 );
							}")
						) .
						"Require response from other user?"
					)
				) .
				// Assign this to another user
				RCView::tr(array('id'=>'tr-dc-user_id_next_action','class'=>'dcRespReq'),
					RCView::td(array('class'=>'data', 'style'=>'background-color:#EFF6E8;border:1px solid #ddd;padding:2px 5px;width:130px;'), 
						"Assign to user:") .
					RCView::td(array('class'=>'data', 'colspan'=>'2','style'=>'background-color:#EFF6E8;border:1px solid #ddd;padding:5px 2px;'), 
						// Drop-down list of all users in user_information table (excluding current user)
						User::dropDownListAllUsernames('dc-user_id_next_action', '', array(USERID), '', true) .
						// Send email to assigned user (yes/no)
						RCView::div(array('style'=>'color:#555;font-size:11px;'), 
							RCView::checkbox(array('id'=>'dc-send_email','class'=>'imgfix2')) .
							"Send email notification"
						)
					)
				) .
				// Type of response requested
				RCView::tr(array('id'=>'tr-dc-response_requested_next_action','class'=>'dcRespReq'),
					RCView::td(array('class'=>'data', 'style'=>'background-color:#EFF6E8;border:1px solid #ddd;padding:2px 5px;width:130px;'), 
						"Type of response requested:") .
					RCView::td(array('class'=>'data', 'colspan'=>'2', 'style'=>'background-color:#EFF6E8;border:1px solid #ddd;'), 
						RCView::select(array('id'=>'dc-response_requested_next_action','class'=>'x-form-text x-form-field','style'=>'padding-right:0;height:22px;'),
							array(''=>"-- choose response type --", 'ACKNOWLEDGEMENT'=>"Acknowledgement", 
								  'COMMENT'=>"Comment / notes", 'FILEUPLOAD'=>"File upload", 'COMPLETEFIELD'=>"Complete the field")
						)
					)
				) .
				// Require data change to field (yes/no)
				RCView::tr(array('id'=>'tr-dc-change_required_next_action','class'=>'dcRespReq'),
					RCView::td(array('class'=>'data', 'style'=>'background-color:#EFF6E8;border:1px solid #ddd;padding:2px 5px;width:130px;'), 
						"Are data changes required?") .
					RCView::td(array('class'=>'data', 'colspan'=>'2', 'style'=>'background-color:#EFF6E8;border:1px solid #ddd;'), 
						RCView::checkbox(array('id'=>'dc-change_required_next_action'))
					)
				) .
				// Mark as high priority (yes/no)
				RCView::tr(array('id'=>'tr-dc-high_priority','class'=>'dcRespReq'),
					RCView::td(array('class'=>'data', 'style'=>'background-color:#EFF6E8;border:1px solid #ddd;padding:2px 5px;width:130px;'), 
						"Is high priority?") .
					RCView::td(array('class'=>'data', 'colspan'=>'2', 'style'=>'background-color:#EFF6E8;border:1px solid #ddd;'), 
						RCView::checkbox(array('id'=>'dc-high_priority'))
					)
				);
		}
		## IF USER IS RESPONDING TO THREAD'S INITIATOR
		elseif ($prevUserAttr['user_id_next_action'] != '' && $prevUserAttr['response_requested_next_action'] != '')
		{
			$h .=
				// Send email notification back to initiator
				RCView::tr(array(),
					RCView::td(array('class'=>'data', 'style'=>'background-color:#EFF6E8;border:1px solid #ddd;padding:2px 5px;width:130px;'), 
						"Were data changes performed?") .
					RCView::td(array('class'=>'data', 'colspan'=>'2', 'style'=>'background-color:#EFF6E8;border:1px solid #ddd;'), 
						RCView::checkbox(array('id'=>'dc-change_performed','class'=>'imgfix2'))
					)
				) .
				// Send email notification
				RCView::tr(array(),
					RCView::td(array('class'=>'data', 'style'=>'background-color:#EFF6E8;border:1px solid #ddd;padding:2px 5px;width:130px;'), 
						"Send email notification back to " .
						RCView::span(array('style'=>'color:#800000;'), 
							"{$userInitiator['username']} ({$userInitiator['user_firstname']} {$userInitiator['user_lastname']})"
						) .
						$lang['questionmark']
					) .
					RCView::td(array('class'=>'data', 'colspan'=>'2', 'style'=>'background-color:#EFF6E8;border:1px solid #ddd;'), 
						RCView::checkbox(array('id'=>'dc-send_email','class'=>'imgfix2'))
					)
				) .
				## Hidden values
				// Set initiator as assigned to next action
				RCView::hidden(array('id'=>'dc-user_id_next_action','value'=>$prevUserAttr['user_id_current'])) .
				// Set that user is responding to the request
				RCView::hidden(array('id'=>'dc-responded_to_request','value'=>'1'));
		}
		// Output html
		return $h;
	}

}
