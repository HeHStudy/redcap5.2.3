<?php

function renderLogRow($row, $html_output=true)
{
	global $lang, $longitudinal, $user_rights, $double_data_entry, $multiple_arms, $require_change_reason, $event_ids, $Proj;
	
	if ($row['legacy']) 
	{
		// For v2.1.0 and previous
		switch ($row['event']) 
		{
			case 'UPDATE':
			
				$pos_set = strpos($row['sql_log'],' SET ') + 4;
				$pos_where = strpos($row['sql_log'],' WHERE ') - $pos_set;
				$sql_log = trim(substr($row['sql_log'],$pos_set,$pos_where));
				$sql_log = str_replace(",","{DELIM}",$sql_log);
				
				$pos_id1 = strrpos($row['sql_log']," = '") + 4;
				if (strpos($row['sql_log'],"LIMIT 1") == true) {
					$id = substr($row['sql_log'],$pos_id1,-10);
				} else {
					$id = substr($row['sql_log'],$pos_id1,-1);
				}
				$sql_log_array = explode("{DELIM}",$sql_log);
				$sql_log = '';
				foreach ($sql_log_array as $value) {
					if (substr(trim($value),-4) == 'null') $value = substr($value,0,-4)."''";
					$sql_log .= stripslashes($value) . ",<br>";		
				}
				$sql_log = substr($sql_log,0,-5);
				if (strpos($row['sql_log'],APP_NAME . "_rights ") == true || strpos($row['sql_log']," redcap_auth ") == true) {
					$event = "<font color=#000066>{$lang['reporting_24']}</font>"; //User updated
				} elseif (strpos($row['sql_log'],"INSERT INTO redcap_edocs_metadata ") == true) {
					$event = "<font color=green>{$lang['reporting_39']}</font><br><font color=#000066>{$lang['reporting_25']}</font>"; //Document uploaded
					$id = substr($id,0,strpos($id,"'"));
					$sql_log = substr($sql_log,0,strpos($sql_log,"="));
				} elseif (strpos($row['sql_log'],"UPDATE redcap_edocs_metadata ") == true) {
					$event = "<font color=red>{$lang['reporting_40']}</font><br><font color=#000066>{$lang['reporting_25']}</font>"; //Document uploaded
					$id = substr($id,0,strpos($id,"'"));
					$sql_log = substr($sql_log,0,strpos($sql_log,"="));
				} else {
					$event = "<font color=#000066>{$lang['reporting_25']}</font>"; //Record updated
				}
				break;
				
			case 'INSERT':
			
				$pos1a = strpos($row['sql_log'],' (') + 2;
				$pos1b = strpos($row['sql_log'],') ') - $pos1a;
				$sql_log = trim(substr($row['sql_log'],$pos1a,$pos1b));			
				$pos2a = strpos($row['sql_log'],'VALUES (') + 8;			
				$sql_log2 = trim(substr($row['sql_log'],$pos2a,-1));
				$sql_log2 = str_replace(",","{DELIM}",$sql_log2);
				
				$pos_id1 = strpos($row['sql_log'],") VALUES ('") + 11;
				$id_a = substr($row['sql_log'],$pos_id1,-1);
				$pos_id2 = strpos($id_a,"'");
				$id = substr($row['sql_log'],$pos_id1,$pos_id2);
				
				$sql_log_array = explode(",",$sql_log);
				$sql_log_array2 = explode("{DELIM}",$sql_log2);			
				$sql_log = '';
				for ($k = 0; $k < count($sql_log_array); $k++) {
					if (trim($sql_log_array2[$k]) == 'null') $sql_log_array2[$k] = "''";
					$sql_log .= stripslashes($sql_log_array[$k]) . " = " . stripslashes($sql_log_array2[$k]) . ",<br>";
				}
				$sql_log = substr($sql_log,0,-5);
				if (strpos($row['sql_log'],APP_NAME . "_rights ") == true || strpos($row['sql_log']," redcap_auth ") == true) {
					$event = "<font color=#800000>{$lang['reporting_26']}</font>";
				} elseif (strpos($row['sql_log'],"INSERT INTO redcap_edocs_metadata ") == true) {
					$event = "<font color=green>{$lang['reporting_39']}</font><br><font color=#800000>{$lang['reporting_27']}</font>"; //Document uploaded
					$sql_log1 = explode("=",$sql_log);
					if (count($sql_log1) == 2) {
						$sql_log = substr($sql_log,0,strrpos($sql_log,";")-1);
					} else {
						$sql_log = substr($sql_log,0,strrpos($sql_log,"="));
					}
				} else {
					$event = "<font color=#800000>{$lang['reporting_27']}</font>";
				}
				break;
			
			case 'DATA_EXPORT':
			
				$pos1 = strpos($row['sql_log'],"SELECT ") + 7;
				$pos2 = strpos($row['sql_log']," FROM ") - $pos1;					
				$sql_log = substr($row['sql_log'],$pos1,$pos2);
				$sql_log_array = explode(",",$sql_log);
				$sql_log = '';
				foreach ($sql_log_array as $value) {
					list ($table, $this_field) = explode(".",$value);
					if (strpos($this_field,")") === false) $sql_log .= "$this_field, ";
				}
				$sql_log = substr($sql_log,0,-2);
				$event = "<font color=green>{$lang['reporting_28']}</font>";
				$id = "";
				break;
				
			case 'DELETE':
			
				$pos1 = strpos($row['sql_log'],"'") + 1;
				$pos2 = strrpos($row['sql_log'],"'") - $pos1;
				$id = substr($row['sql_log'],$pos1,$pos2);
				if (strpos($row['sql_log'],APP_NAME . "_rights ") == true) {
					$event = "<font color=red>{$lang['reporting_29']}</font>";
					$sql_log = "user = '$id'";
				} else {			
					$event = "<font color=red>{$lang['reporting_30']}</font>";
					$sql_log = "$table_pk = '$id'";
				}
				break;
				
			case 'OTHER':
			
				$sql_log = "";
				$event = "<font color=gray>{$lang['reporting_31']}</font>";
				$id = "";
				break;
				
		}

	} 
	
	
	
	
	
	
	
	
	// For v2.2.0 and up
	else 
	{
		switch ($row['event']) {
			
			case 'UPDATE':
				//$sql_log = str_replace("\n","<br>",$row['data_values']);
				$sql_log = $row['data_values'];
				$id = $row['pk'];
				//Determine if deleted user or project record
				if ($row['object_type'] == APP_NAME."_data" || $row['object_type'] == "redcap_data") 
				{
					if ($row['user'] == "[survey respondent]") {
						$event = "<font color=#000066>{$lang['reporting_47']}";
					} else {
						$event  = "<font color=#000066>{$lang['reporting_25']}";
						if ($row['page'] == "DTS/index.php") $event .= " (DTS)";
					}
					if (strpos($row['description'], " (import)") !== false) {
						$event .= " (import)";
					}
					elseif (strpos($row['description'], " (API)") !== false) {
						$event .= " (API)";
					}
					$event .= "</font>";
					// DAGs: If assigning to or removing from DAG
					if (strpos($row['description'], "Remove record from Data Access Group") !== false || strpos($row['description'], "Assign record to Data Access Group") !== false)
					{				
						$event  = "<font color=#000066>{$lang['reporting_25']}";
						if ($row['page'] == "DataImport/index.php") {
							$event .= " (import)";
						} elseif (strpos($row['description'], " (API)") !== false) {
							$event .= " (API)";
						}
						$event .= "</font>";
						$sql_log = str_replace(" (API)", "", $row['description'])."\n(" . $row['data_values'] . ")";
					}
				} 
				elseif ($row['object_type'] == APP_NAME."_rights" || $row['object_type'] == "redcap_user_rights") 
				{
					$event = "<font color=#000066>{$lang['reporting_24']}</font>";
				}
				break;
				
			case 'INSERT':
				
				//$sql_log = str_replace("\n","<br>",$row['data_values']);
				$sql_log = $row['data_values'];
				$id = $row['pk'];
				//Determine if deleted user or project record
				if ($row['object_type'] == APP_NAME."_data" || $row['object_type'] == "redcap_data") {
					if ($row['user'] == "[survey respondent]") {
						$event = "<font color=#800000>{$lang['reporting_46']}";
					} else {
						$event = "<font color=#800000>{$lang['reporting_27']}";
					}
					if (strpos($row['description'], " (import)") !== false) {
						$event .= " (import)";
					}
					elseif (strpos($row['description'], '(API)') !== false) {
						$event .= " (API)";
					}
					$event .= "</font>";
				} elseif ($row['object_type'] == APP_NAME."_rights" || $row['object_type'] == "redcap_user_rights") {
					$event = "<font color=#800000>{$lang['reporting_26']}</font>";
				}
				break;
			
			case 'DATA_EXPORT':
				
				$sql_log = $row['data_values'];
				$event = "<font color=green>{$lang['reporting_28']}";
				if (strpos($row['description'], '(API)') !== false) {
					$event .= " (API)";
				}
				$event .= "</font>";
				$id = "";
				break;
			
			case 'DOC_UPLOAD':
				
				$sql_log = $row['data_values'];
				$event = "<font color=green>{$lang['reporting_39']}</font><br><font color=#000066>{$lang['reporting_25']}</font>";
				$id = $row['pk'];
				break;
			
			case 'DOC_DELETE':
				
				$sql_log = $row['data_values'];
				$event = "<font color=red>{$lang['reporting_40']}</font><br><font color=#000066>{$lang['reporting_25']}</font>";
				$id = $row['pk'];
				break;
				
			case 'DELETE':
				
				$sql_log = $row['data_values'];
				$id = $row['pk'];
				//Determine if deleted user or project record
				if ($row['object_type'] == APP_NAME."_data" || $row['object_type'] == "redcap_data") {
					$event = "<font color=red>{$lang['reporting_30']}</font>";
				} elseif ($row['object_type'] == APP_NAME."_rights" || $row['object_type'] == "redcap_user_rights") {
					$event = "<font color=red>{$lang['reporting_29']}</font>";
				}
				break;
				
			case 'OTHER':
				$sql_log = "";
				$event = "<font color=gray>{$lang['reporting_31']}</font>";
				$id = "";
				break;
			
			case 'MANAGE':					
				$sql_log = $row['description'];
				$event = "<font color=#000066>{$lang['reporting_33']}</font>";
				$id = "";
				// Parse activity differently for arms, events, calendar events, and scheduling
				if (in_array($sql_log, array("Create calendar event","Delete calendar event","Edit calendar event","Create event","Edit event",
											 "Delete event","Create arm","Delete arm","Edit arm name/number"))) {
					$sql_log .= "\n(" . $row['data_values'] . ")";
				}
				// Render record name for edoc downloads
				if ($sql_log == "Download uploaded document") {
					$event = "<font color=#000066>$sql_log</font>";
					// Deal with legacy logging, in which the record was not known and data_values contained "doc_id = #"
					if (strpos($row['data_values'], "=") === false) {
						$sql_log = $row['data_values'];
						$id = $row['pk'];
						$event .= "<br>{$lang['global_49']}";
					} else {
						$sql_log = "";
					}
				}
				// Render randomization of records so that it displays the record name
				elseif ($sql_log == "Randomize record") {
					$id = $row['pk'];
					$event = "<font color=#000066>{$lang['random_117']}</font>";
				}
				// Render the email recipient's email if "Send email"
				elseif ($sql_log == "Send email" && $row['pk'] != '') {
					$sql_log .= "\n({$lang['reporting_48']}{$lang['colon']} {$row['pk']})"; 
				}
				// For super user action of viewing another user's API token, add username after description for clarification
				elseif ($sql_log == "View API token of another user") {
					$sql_log .=  "\n(".$row['data_values'].")";
				}
				break;
			
			case 'LOCK_RECORD':					
				$sql_log = $lang['reporting_44'] . $row['description'] . "\n" . $row['data_values'];
				$event = "<font color=#A86700>{$lang['reporting_41']}</font>";
				$id = $row['pk'];
				break;
			
			case 'ESIGNATURE':					
				$sql_log = $lang['reporting_44'] . $row['description'] . "\n" . $row['data_values'];
				$event = "<font color=#008000>{$lang['global_34']}</font>";
				$id = $row['pk'];
				break;
			
			case 'PAGE_VIEW':					
				$sql_log = $lang['reporting_45']."\n" . $row['full_url'];
				// if ($row['record'] != "") $sql_log .= ",<br>record: " . $row['record'];
				// if ($row['event_id'] != "") $sql_log .= ",<br>event_id: " . $row['event_id'];
				$event = "<font color=#000066>{$lang['reporting_43']}</font>";
				$id = "";
				$row['data_values'] = "";
				break;
				
		}

	}

	// Append Event Name (if longitudinal)
	$dataEvents = array("UPDATE","INSERT","DELETE","DOC_UPLOAD","DOC_DELETE","LOCK_RECORD","ESIGNATURE");
	if ($longitudinal && $row['object_type'] == "redcap_data" && $row['legacy'] == '0' && in_array($row['event'], $dataEvents)) 
	{
		if ($row['event_id'] == "") {
			$row['event_id'] = $Proj->firstEventId;
		}
		$id .= " <span style='color:#777;'>(" . strip_tags(label_decode($event_ids[$row['event_id']])) . ")</span>";
	}

	unset($sql_log_array);
	unset($sql_log_array2);	
	
	// Set description
	$description = "$event<br>$id";
	
	// If outputting to non-html format (e.g. csv file), then remove html
	if (!$html_output)
	{
		$row['ts']   = format_ts_excel($row['ts']);
		$description = strip_tags(str_replace("<br>", " ", $description));
		$sql_log 	 = html_entity_decode(strip_tags(str_replace(array("<br>","\n"), array(" "," "), $sql_log)), ENT_QUOTES);
	}
	// html output (i.e. Logging page)
	else
	{
		$row['ts'] = format_ts($row['ts']);
	}
	
	// Set values for this row
	$new_row = array($row['ts'], $row['user'], $description, $sql_log);
	
	// If project-level flag is set, then add "reason changed" to row data
	if ($require_change_reason)
	{
		$new_row[] = $html_output ? nl2br($row['change_reason']) : str_replace("\n", " ", html_entity_decode($row['change_reason'], ENT_QUOTES));
	}
	
	// Return values for this row
	return $new_row;
}



function setEventFilterSql($logtype)
{
	// Set legacy values that may still exist in logging records for very old projects
	$data_table   = APP_NAME . "_data";
	$rights_table = APP_NAME . "_rights";

	switch ($logtype) 
	{
		case 'page_view':
			$filter_logtype =  "AND event = 'PAGE_VIEW'";
			break;
		case 'lock_record':
			$filter_logtype =  "AND event in ('LOCK_RECORD', 'ESIGNATURE')";
			break;
		case 'manage':
			$filter_logtype =  "AND event = 'MANAGE'";
			break;
		case 'export':
			$filter_logtype =  "AND event = 'DATA_EXPORT'";
			break;
		case 'record':
			$filter_logtype =  "AND (
								(
									(
										legacy = '1' 
										AND 
										(
											left(sql_log,".strlen("INSERT INTO $data_table").") = 'INSERT INTO $data_table' 
											OR 
											left(sql_log,".strlen("UPDATE $data_table").") = 'UPDATE $data_table' 
											OR 
											left(sql_log,".strlen("DELETE FROM $data_table").") = 'DELETE FROM $data_table'
											OR
											left(sql_log,".strlen("INSERT INTO redcap_data").") = 'INSERT INTO redcap_data' 
											OR 
											left(sql_log,".strlen("UPDATE redcap_data").") = 'UPDATE redcap_data' 
											OR 
											left(sql_log,".strlen("DELETE FROM redcap_data").") = 'DELETE FROM redcap_data'
										)
									) 
									OR 
									(
										(legacy = '0' AND object_type = '$data_table')
										OR
										(legacy = '0' AND object_type = 'redcap_data')
									)
								) 
								AND 
									(event != 'DATA_EXPORT')
								)";
			break;
		case 'record_add':
			$filter_logtype =  "AND (
									(legacy = '1' AND left(sql_log,".strlen("INSERT INTO $data_table").") = 'INSERT INTO $data_table') 
									OR 
									(legacy = '0' AND object_type = '$data_table' and event = 'INSERT')
									OR
									(legacy = '1' AND left(sql_log,".strlen("INSERT INTO redcap_data").") = 'INSERT INTO redcap_data') 
									OR 
									(legacy = '0' AND object_type = 'redcap_data' and event = 'INSERT')
								)";
			break;
		case 'record_edit':
			$filter_logtype =  "AND (
									(legacy = '1' AND left(sql_log,".strlen("UPDATE $data_table").") = 'UPDATE $data_table') 
									OR 
									(legacy = '0' AND object_type = '$data_table' and event in ('UPDATE','DOC_DELETE','DOC_UPLOAD'))
									OR
									(legacy = '1' AND left(sql_log,".strlen("UPDATE redcap_data").") = 'UPDATE redcap_data') 
									OR 
									(legacy = '0' AND object_type = 'redcap_data' and event in ('UPDATE','DOC_DELETE','DOC_UPLOAD'))
								)";
			break;
		case 'user':
			$filter_logtype =  "AND 
								(
									object_type = '$rights_table'
									OR
									object_type = 'redcap_user_rights'
								)";
			break;
		default:
			$filter_logtype = '';
	}
	
	return $filter_logtype;

}
