<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


//Check if need to start a new page with this question
function new_page_check($num_lines, $pdf, $y_units_per_line, $bottom_of_page, $study_id_event) 
{
	if (($y_units_per_line * $num_lines) + $pdf->GetY() > $bottom_of_page) {
		$pdf->AddPage();
		// Set logo at bottom
		setFooterImage($pdf);
		// Set "Confidential" text at top
		$pdf = confidentialText($pdf);
		// Add page number
		if ($study_id_event != "") {
			$pdf->SetFont(FONT,'BI',8);
			$pdf->Cell(0,2,$study_id_event,0,1,'R');
			$pdf->Ln();
		}
		$pdf->SetFont(FONT,'I',8);
		$pdf->Cell(0,5,'Page '.$pdf->PageNo().' of {nb}',0,1,'R');
		// Line break and reset font
		$pdf->Ln();
		$pdf->SetFont(FONT,'',10);
	}
	return $pdf;
}

// Add survey custom question number, if applicable
function addQuestionNumber($pdf, $row_height, $question_num, $isSurvey, $customQuesNum, $num)
{
	if ($isSurvey)
	{
		if ($customQuesNum && $question_num != "") {	
			// Custom numbered
			$currentXPos = $pdf->GetX();
			$pdf->SetX(2);
			$pdf->Cell(0,$row_height,$question_num);
			$pdf->SetX($currentXPos);
		} elseif (!$customQuesNum && is_numeric($num)) {
			// Auto numbered
			$currentXPos = $pdf->GetX();
			$pdf->SetX(2);
			$pdf->Cell(0,$row_height,$num.")");
			$pdf->SetX($currentXPos);		
		}
	}
	return $pdf;
}

// Set "Confidential" text at top
function confidentialText($pdf)
{
	// Get current position, so we can reset it back later
	$y = $pdf->GetY();
	$x = $pdf->GetX();
	// Set new position
	$pdf->SetY(3);
	$pdf->SetX(0);	
	// Add text
	$pdf->SetFont(FONT,'I',12);
	$pdf->Cell(0,0,'Confidential',0,1,'L');
	// Reset font and positions
	$pdf->SetFont(FONT,'',10);
	$pdf->SetY($y);
	$pdf->SetX($x);	
	return $pdf;
}

//Set the footer with the URL for the consortium website and the REDCap logo
function setFooterImage($pdf) 
{
	// Set REDCap Consortium URL as footer
	$pdf->SetY(-4);
	$pdf->SetFont(FONT,'',8);
	$pdf->Cell(130,0,'');
	$pdf->Cell(0,0,'www.project-redcap.org',0,0,'L',false,'http://www.project-redcap.org');		
	//Set the REDCap logo
	$pdf->Image(LOGO_PATH . "redcaplogo2.jpg", 176, 289, 24, 8);
	//Reset position to begin the page
	$pdf->SetY(6);
}

// Format the min, mid, and max labels for Sliders
function slider_label($this_text,$char_limit_slider) {
	$this_text .= " ";
	$slider_lines = array();
	$start_pos = 0;
	do {
		$this_line = substr($this_text,$start_pos,$char_limit_slider);
		$end_pos = strrpos($this_line," "); 
		$slider_lines[] = substr($this_line,0,$end_pos);
		$start_pos = $start_pos + $end_pos + 1;
	} while ($start_pos < strlen($this_text));
	return $slider_lines;
}

//Format question text for questions with vertically-rendered answers
function qtext_vertical($row, $char_limit_q) {
	$this_string = $row['element_label'];
	$start_pos = 0;
	do {			
		//$indent_this = false;
		//If only one line of text OR on last line of multi-line text
		if ($start_pos + $char_limit_q >= strlen($this_string)) {
			if ($start_pos == 0) {
				$this_line = substr($this_string,$start_pos,$char_limit_q); //if only one line of text
			} else {
				$this_line = substr($this_string,$start_pos,$char_limit_q); //for last line of text
				//$indent_this = true;
			}
			$end_pos = strlen($this_line); 
		} else {
		//For all lines of text except last line
			if ($start_pos == 0) {
				$this_line = substr($this_string,$start_pos,$char_limit_q); 
			} else {
				$this_line = substr($this_string,$start_pos,$char_limit_q); //indent all lines after first line
				//$indent_this = true;
			}
			$end_pos = strrpos($this_line," "); //for all lines of text except last
		}
		// Add this line
		$q_lines[] = trim(substr($this_line,0,$end_pos));
		//if ($indent_this) $end_pos = $end_pos - strlen($indent_q);
		$start_pos = $start_pos + $end_pos + 1;
	} while ($start_pos <= strlen($this_string));
	return $q_lines;
}


function backwardStrpos($haystack, $needle, $offset = 0){
    $length = strlen($haystack);
    $offset = ($offset > 0)?($length - $offset):abs($offset);
    $pos = strpos(strrev($haystack), strrev($needle), $offset);
    return ($pos === false)?false:( $length - $pos - strlen($needle) );
}


function text_vertical($this_string,$char_limit) {
	$this_string = str_replace("\r", "", html_entity_decode($this_string, ENT_QUOTES));
	$lines = explode("\n", $this_string);
	// Go through each line and place \n to break up into segments based on $char_limit value
	foreach ($lines as $key=>$line) {
		$numbreaks = floor(strlen($line)/$char_limit);
		if ($numbreaks >= 1) {
			$start_pos = $char_limit;			
			while (($start_pos = backwardStrpos($line, " ", $start_pos)) !== false) {
				$line = substr($line, 0, $start_pos) . "\n" . substr($line, $start_pos+1);
				$start_pos += $char_limit;
			}
			// Replace original with new line breaks added
			$lines[$key] = $line;			
		}
	}
	return explode("\n", implode("\n", $lines));
}

//Format answer text for questions with vertically-rendered answers
function atext_vertical_mc($row, $Data, $char_limit_a, $indent_a, $project_language, $event_id, $record) {	

	$atext = array();
	$line = array();
	
	$row['element_enum'] = strip_tags(label_decode($row['element_enum']));
	if ($project_language == 'Japanese') $row['element_enum'] = mb_convert_encoding($row['element_enum'], "SJIS", "UTF-8");
	$choices = explode("\\n", $row['element_enum']);
	
	// Loop through each choice for this field
	foreach (parseEnum($row['element_enum']) as $this_code=>$this_choice) 
	{		
		// Default: checkbox is unchecked
		$chosen = false;
		
		// Determine if this row's checkbox needs to be checked (i.e. it has data)
		if (isset($Data[$record][$event_id][$row['field_name']])) {		
			if (is_array($Data[$record][$event_id][$row['field_name']])) {
				// Checkbox fields
				if (isset($Data[$record][$event_id][$row['field_name']][$this_code]) && $Data[$record][$event_id][$row['field_name']][$this_code] == "1") {
					$chosen = true;
				}
			} elseif ($Data[$record][$event_id][$row['field_name']] == $this_code) {
				// Regular fields				
				$chosen = true;
			}
		}
		
		$this_string = trim($this_choice);
		$start_pos = 0;
		do {
			$indent_this = false;
			if ($start_pos + $char_limit_a >= strlen($this_string)) {
				if ($start_pos == 0) {
					$this_line = substr($this_string,$start_pos,$char_limit_a); //if only one line of text
				} else {
					$this_line = $indent_a . substr($this_string,$start_pos,$char_limit_a); //for last line of text
					$indent_this = true;
				}
				$end_pos = strlen($this_line); 
			} else {
				if ($start_pos == 0) {
					$this_line = substr($this_string,$start_pos,$char_limit_a); 
				} else {
					$this_line = $indent_a . substr($this_string,$start_pos,$char_limit_a); //indent all lines after first line
					$indent_this = true;
				}
				$end_pos = strrpos($this_line," "); //for all lines of text except last
			}
			// Set values for this line of text
			$line = array('chosen'=>$chosen, 'sigil'=>true, 'line'=>label_decode(substr($this_line,0,$end_pos)));
			// If secondary line for same choice, then indent and do not display checkbox
			if ($indent_this) {
				$line['sigil'] = false;
				$end_pos = $end_pos - strlen($indent_a);
			}
			// Add line of text to array
			$atext[] = $line;
			// Set start position for next loop
			$start_pos = $start_pos + $end_pos + 1;
		} while ($start_pos <= strlen($this_string));	
	}
	
	return $atext;
}


/**
 * Build and render the PDF
 */
function renderPDF($metadata, $acknowledgement, $project_name = "", $data_export_rights = 1, $Data = array()) 
{	
	global $Proj, $table_pk, $table_pk_label, $longitudinal, $custom_record_label, $surveys_enabled,
		   $salt, $__SALT__, $user_rights, $lang, $ProjMetadata, $ProjForms;
	
	//Set the character limit per line for questions (left column) and answers (right column)
	$char_limit_q = 54; //question char limit per line
	$char_limit_a = 51; //answer char limit per line
	$char_limit_slider = 18; //slider char limit per line
	//Set column width and row height
	$col_width_a = 105; //left column width
	$col_width_b = 75;  //right column width
	$sigil_width = 4;
	$atext_width = 70;
	$row_height = 4;
	//Set other widths
	$page_width = 190;
	$matrix_label_width = 55;
	//Indentation string
	$indent_q = "     ";
	$indent_a = "";
	//Parameters for determining page breaks
	$est_char_per_line = 110;
	$y_units_per_line = 4.5;
	$bottom_of_page = 290;
	// Slider parameters		
	$rect_width = 1.5;
	$num_rect = 50;	
	
	// Create array of event_ids=>event names
	$events = array();
	if (isset($Proj))
	{
		foreach ($Proj->events as $this_arm_num=>$this_arm)
		{
			foreach ($this_arm['events'] as $this_event_id=>$event_attr)
			{
				$events[$this_event_id] = strip_tags(label_decode($event_attr['descrip']));
			}
		}
	}
	else {
		$events[1] = 'Event 1';
	}
	
	// Determine if in Consortium website or REDCap core
	if (!defined("PROJECT_ID"))
	{
		// We are in Consortium website
		$project_language = 'English'; // set as default (English)
		define("LOGO_PATH", APP_PATH_DOCROOT . "resources/img/");
		// Set font constant
		define("FONT", "Arial");
	}
	else 
	{
		// We are in REDCap core
		global $project_language;
		define("LOGO_PATH", APP_PATH_DOCROOT . "Resources/images/");
		// Set font constant
		if ($project_language == 'Japanese')
		{
			define("FONT", GOTHIC); // Japanese
		}
		else
		{
			// If using UTF-8 encoding, include other fonts
			if (USE_UTF8) {
				define("FONT", "DejaVu");
			} else {
				define("FONT", "Arial");
			}
		}
	}
	
	//Begin creating PDF
	if ($project_language == 'Japanese')
	{	
		//Japanese
		$pdf = new MBFPDF();
		$pdf->AddMBFont(GOTHIC ,'SJIS');
		$project_name = mb_convert_encoding($project_name, "SJIS", "UTF-8");
	}
	else
	{
		// Normal
		$pdf = new PDF();
		// If using UTF-8 encoding, include other fonts
		if (USE_UTF8)
		{
			$pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
			$pdf->AddFont('DejaVu','B','DejaVuSansCondensed-Bold.ttf',true);
			$pdf->AddFont('DejaVu','I','DejaVuSansCondensed-Oblique.ttf',true);
			$pdf->AddFont('DejaVu','BI','DejaVuSansCondensed-BoldOblique.ttf',true);
		}
	}
	
	// Set paging settings
	$pdf->SetAutoPageBreak('auto'); # on by default with 2cm margin
	$pdf->AliasNbPages(); # defines a string which is substituted with total number of pages when the
						  # document is closed: '{nb}' by default.	
	
	## LOOP THROUGH ALL EVENTS/RECORDS  
	foreach ($Data as $record=>$event_array)
	{
		// Loop through records within the event
		foreach (array_keys($event_array) as $event_id)
		{
			// Display event name if longitudinal
			$event_name = (isset($events[$event_id]) ? $events[$event_id] : "");
			
			// Get record name to display (top right of pdf), if displaying data for a record
			$study_id_event = "";
			if ($record != '') 
			{
				// Is PK an identifier? If so, then hash it
				$pk_display_val = ($ProjMetadata[$table_pk]['field_phi'] && $user_rights['data_export_tool'] == '2') ? md5($salt . $record . $__SALT__) : $record;
				// Set top-left corner display labels
				$study_id_event = "$table_pk_label $pk_display_val " . strip_tags(parse_context_msg($custom_record_label,"<div></div>",true));
				// Display event name if longitudinal
				if (isset($longitudinal) && $longitudinal) {
					$study_id_event .= " ($event_name)";
				}
				// Add survey identifier to $study_id_event, if identifier exists
				$response_time_text = "";
				if (isset($surveys_enabled) && ($surveys_enabled)) 
				{
					$sql = "select p.participant_identifier, r.first_submit_time, r.completion_time
							from redcap_surveys s, redcap_surveys_participants p, redcap_events_metadata e, 
							redcap_surveys_response r where s.project_id = ".PROJECT_ID." 
							and e.event_id = p.event_id and e.event_id = $event_id and s.survey_id = p.survey_id 
							and p.participant_id = r.participant_id and r.record = '".prep($record)."'";
					$q = db_query($sql);
					if (db_num_rows($q) > 0) {
						// Append identifier
						$participant_identifier = db_result($q, 0, 'participant_identifier');
						if ($participant_identifier != "") {
							$study_id_event .= " - $participant_identifier";
						}
						// Set response time also
						$first_submit_time = db_result($q, 0, 'first_submit_time');
						$completion_time   = db_result($q, 0, 'completion_time');
						if ($completion_time == "" && $first_submit_time != "") {
							// Partial
							$response_time_text = "{$lang['data_entry_101']} {$lang['data_entry_100']} ".format_ts_mysql($first_submit_time).".";
						} elseif ($completion_time != "") {
							// Complete
							$response_time_text = $lang['data_entry_100']." ".format_ts_mysql($completion_time).".";							
						}
					}
				}
			}
			
			// Set max width of an entire line in the PDF
			$max_line_width = 190;
			
			// Loop through each field to create row in PDF
			$num = 1;
			$last_form = "";
			foreach ($metadata as $row) 
			{
				// If longitudinal, make sure this form is designated for this event (if not, skip this loop and continue)
				if ($longitudinal && isset($_GET['id']) && !in_array($row['form_name'], $Proj->eventsForms[$event_id])) 
				{
					continue;
				}
				
				// Check if starting new form
				if ($last_form != $row['form_name'])
				{
					// Set form/survey values
					if (isset($Proj) && is_array($ProjForms) && isset($ProjForms[$row['form_name']]['survey_id'])) {
						// Survey 
						$survey_id = $ProjForms[$row['form_name']]['survey_id'];
						$survey_instructions = strip_tags(str_replace(array('<p>','</p>','<br>','<br />',"&nbsp;"), array("\n","\n","\n","\n"," "), nl2br(label_decode(label_decode($Proj->surveys[$survey_id]['instructions'])))));
						$isSurvey = true;
						$newPageOnSH = $Proj->surveys[$survey_id]['question_by_section'];
						$customQuesNum = !$Proj->surveys[$survey_id]['question_auto_numbering'];
						$form_title = strip_tags(label_decode($Proj->surveys[$survey_id]['title']));
					} elseif (isset($Proj) && is_array($ProjForms)) {
						// Form 
						$survey_instructions = "";
						$isSurvey = false;
						$newPageOnSH = false;
						$customQuesNum = false;
						$form_title = strip_tags(label_decode($ProjForms[$row['form_name']]['menu']));
					} else {
						// Shared Library defaults
						$form_title = $project_name;
						$customQuesNum = false;
						$isSurvey = false;
					}
					
					// For surveys only, skip participant_id field
					if (isset($isSurvey) && $isSurvey && $row['field_name'] == $table_pk) {
						$atSurveyPkField = true;
						continue;	
					}
					
					// Begin new page
					$pdf->AddPage();
					// Set REDCap logo at bottom right
					setFooterImage($pdf);					
					// Set "Confidential" text at top
					$pdf = confidentialText($pdf);					
					//Display project name (top right)
					$pdf->SetFillColor(0,0,0); # Set fill color (when used) to black
					$pdf->SetFont(FONT,'I',8); # retained from page to page. #  'I' for italic, 8 for size in points.
					if (!$isSurvey) {
						$pdf->Cell(0,2,$project_name,0,1,'R');
						$pdf->Ln();
					}
					//Display record name (top right), if displaying data for a record
					if ($study_id_event != "") {
						$pdf->SetFont(FONT,'BI',8);
						$pdf->Cell(0,2,$study_id_event,0,1,'R');
						$pdf->Ln();
						$pdf->SetFont(FONT,'I',8);
					}
					//Initial page number
					$pdf->Cell(0,2,"Page ".$pdf->PageNo()." of {nb}",0,1,'R');					
					//Display form title as page header
					$pdf->SetFont(FONT,'B',18);
					$pdf->MultiCell(0,6,$form_title,0);
					$pdf->Ln(); 
					$pdf->SetFont(FONT,'',10);					
					// Survey instructions, if a survey
					if (isset($isSurvey) && $isSurvey)
					{
						$pdf->MultiCell(0,$row_height,$survey_instructions,0);
						$pdf->Ln();
						// Display timestamp for surveys
						if ($atSurveyPkField && $response_time_text != "") {
							$pdf->SetFont(FONT,'',10);
							$pdf->SetTextColor(255,255,255);
							$pdf->Ln();
							$pdf->MultiCell(0,6,$response_time_text,1,'L',1);
							$pdf->SetTextColor(0,0,0);
							$pdf->SetFont(FONT,'',10);
						}
					}					
					$pdf->Ln();		
					// Set as default for next loop
					$atSurveyPkField = false;
				}
				
				//Set default font
				$pdf->SetFont(FONT,'',10);
				$q_lines = array();
				$a_lines = array();	
		
				## MATRIX QUESTION GROUPS
				$isMatrixField = false; //default
				$matrixGroupPosition = ''; //default
				$grid_name = $row['grid_name'];
				$matrixHeight = null;
				// Just ended a grid, so give a little extra space
				if ($grid_name == "" && $prev_grid_name != $grid_name)
				{
					$pdf->Ln();
				}
				// Beginning a new grid
				elseif ($grid_name != "" && $prev_grid_name != $grid_name)
				{
					// Set flag that this is a matrix field
					$isMatrixField = true;
					// Set that field is the first field in matrix group
					$matrixGroupPosition = '1';
					// Get total matrix group height, including SH, so check if we need a page break invoked below
					$matrixHeight = $row_height * getMatrixHeight($pdf, $row['field_name'], $page_width, $matrix_label_width);
				}
				// Continuing an existing grid
				elseif ($grid_name != "" && $prev_grid_name == $grid_name)
				{
					// Set flag that this is a matrix field
					$isMatrixField = true;
					// Set that field is *not* the first field in matrix group
					$matrixGroupPosition = 'X';
				}
				// Set value for next loop
				$prev_grid_name = $grid_name;
				
				// Remove HTML tags from field labels and field notes
				$row['element_label'] = str_replace(array("\r\n","\n"), array(" "," "), strip_tags(br2nl(label_decode($row['element_label']))));
				if ($project_language == 'Japanese') $row['element_label'] = mb_convert_encoding($row['element_label'], "SJIS", "UTF-8"); //Japanese
				if ($row['element_note'] != "") {
					$row['element_note'] = strip_tags(label_decode($row['element_note']));	
					if ($project_language == 'Japanese') $row['element_note'] = mb_convert_encoding($row['element_note'], "SJIS", "UTF-8"); //Japanese
				}
				
				// Check pagebreak for Section Header OR Matrix
				if (
					// If a Matrx AND whole matrix will exceed length of page
					($matrixGroupPosition == '1' && ($pdf->GetY()+$matrixHeight) > ($bottom_of_page-20) && $pdf->PageNo() > 1) 
					// If Section Header AND (starting new page OR close to the bottom)
					|| ($row['element_preceding_header'] != "" && ((isset($isSurvey) && $isSurvey && $newPageOnSH && $num != 1) || ($pdf->GetY() > $bottom_of_page-50))) 
				) {
					$pdf->AddPage();
					setFooterImage($pdf);
					// Set "Confidential" text at top
					$pdf = confidentialText($pdf);
					//Display record name (top right), if displaying data for a record
					if ($study_id_event != "") {
						$pdf->SetFont(FONT,'BI',8);
						$pdf->Cell(0,2,$study_id_event,0,1,'R');
						$pdf->Ln();
					}
					$pdf->SetFont(FONT,'I',8);
					$pdf->Cell(0,5,'Page '.$pdf->PageNo().' of {nb}',0,1,'R');
				}
				
				// Section header
				if ($row['element_preceding_header'] != "") 
				{
					// Render section header
					$pdf->Ln();
					$pdf->MultiCell(0,1,'','B'); $pdf->Ln();
					$pdf->MultiCell(0,0,'','B'); $pdf->Ln();
					$pdf->SetFont(FONT,'B',11);
					$row['element_preceding_header'] = strip_tags(label_decode($row['element_preceding_header']));
					if ($project_language == 'Japanese') $row['element_preceding_header'] = mb_convert_encoding($row['element_preceding_header'], "SJIS", "UTF-8"); //Japanese
					$pdf->MultiCell(0,6,$row['element_preceding_header'],0);			
					$pdf->Ln();
					$pdf->SetFont(FONT,'',10);
				}
				
				//Drop-downs & Radio buttons
				if ($row['element_type'] == "yesno" || $row['element_type'] == "truefalse" || $row['element_type'] == "radio" || $row['element_type'] == "select" || $row['element_type'] == "advcheckbox" || $row['element_type'] == "checkbox" || $row['element_type'] == "sql") 
				{
					//If SQL field type, execute query to retrieve choices
					if ($row['element_type'] == "sql") {
						if (strtolower(substr(trim($row['element_enum']),0,7)) == "select ") {
							$rs_temp1_sql = db_query($row['element_enum']);
							$first_field  = db_field_name($rs_temp1_sql,0);
							$string_record_select1 = "";
							while ($row = db_fetch_array($rs_temp1_sql)) {
								$string_record_select1 .= "0, " . $row[$first_field] . " \\n ";
							}				
							$row['element_enum'] = substr($string_record_select1,0,-4);
						}
					}
					//If AdvCheckbox, render as Yes/No radio buttons
					elseif ($row['element_type'] == "advcheckbox") {
						$row['element_enum'] = "1, ";
					}
					//If Yes/No, manually set options
					elseif ($row['element_type'] == "yesno") {
						$row['element_enum'] = YN_ENUM;
					}
					//If True/False, manually set options
					elseif ($row['element_type'] == "truefalse") {
						$row['element_enum'] = TF_ENUM;
					}
								
					if ($row['element_note'] != "") $row['element_note'] = "(".$row['element_note'].")";
					
					// If a Matrix formatted field
					if ($row['grid_name'] != '') {
						// Parse choices into an array
						$enum = parseEnum($row['element_enum']);
						// Render this matrix header row
						if ($matrixGroupPosition == '1') {
							$pdf = renderMatrixHeaderRow($pdf, $enum, $page_width, $matrix_label_width);
						}		
						// Determine if this row's checkbox needs to be checked (i.e. it has data)
						$enumData = array();
						if (isset($Data[$record][$event_id][$row['field_name']])) 
						{
							// Field DOES have data, so loop through EVERY choice and put in array
							foreach (array_keys($enum) as $this_code) {
								if (is_array($Data[$record][$event_id][$row['field_name']])) {
									// Checkbox fields
									if (isset($Data[$record][$event_id][$row['field_name']][$this_code]) && $Data[$record][$event_id][$row['field_name']][$this_code] == "1") {
										$enumData[$this_code] = '1';
									}
								} elseif ($Data[$record][$event_id][$row['field_name']] == $this_code) {
									// Regular fields				
									$enumData[$this_code] = '1';
								}
							}
						}					
						// Render the matrix row for this field
						$pdf = renderMatrixRow($pdf, $row['element_label'], $enum, $enumData, $row_height, $sigil_width, $page_width, $matrix_label_width, $bottom_of_page,$study_id_event);
					}
					// LV, LH, RH Alignment
					elseif ($row['custom_alignment'] == 'LV' || $row['custom_alignment'] == 'LH' || $row['custom_alignment'] == 'RH') 
					{
						// Set begin position of new line
						$xStartPos = 10;
						if ($row['custom_alignment'] == 'RH') {
							$xStartPos = 115;
						}

						// Place enums in array while trying to judge general line count of all choices
						$row['element_enum'] = strip_tags(label_decode($row['element_enum']));
						if ($project_language == 'Japanese') $row['element_enum'] = mb_convert_encoding($row['element_enum'], "SJIS", "UTF-8");
						$enum = array();
						foreach (parseEnum($row['element_enum']) as $this_code=>$line)
						{
							// Add to array
							$enum[$this_code] = strip_tags(label_decode($line));
						}
						
						// Field label text
						if ($row['custom_alignment'] == 'RH') {
							// Right-horizontal aligned
							$q_lines = qtext_vertical($row, $char_limit_q);	
							//print_array($q_lines);
							$counter = (count($q_lines) >= count($enum)) ? count($q_lines) : count($enum);
							$pdf = new_page_check($counter, $pdf, $y_units_per_line, $bottom_of_page, $study_id_event);	
							$yStartPos = $pdf->GetY();
							// If a survey and using custom question numbering, then render question number				
							$pdf = addQuestionNumber($pdf, $row_height, $row['question_num'], $isSurvey, $customQuesNum, $num);
							for ($i = 0; $i < count($q_lines); $i++) {
								$pdf->Cell($col_width_a,$row_height,$q_lines[$i],0,1);
							}
							$yPosAfterLabel = $pdf->GetY();
							$pdf->SetY($yStartPos);
						} else {
							// Left aligned
							$counter = ceil($pdf->GetStringWidth($row['element_label']."\n".$row['element_note'])/$max_line_width)+2+count($enum);
							$pdf = new_page_check($counter, $pdf, $y_units_per_line, $bottom_of_page, $study_id_event);
							// If a survey and using custom question numbering, then render question number				
							$pdf = addQuestionNumber($pdf, $row_height, $row['question_num'], $isSurvey, $customQuesNum, $num);
							$pdf->MultiCell(0,$row_height,$row['element_label']."\n".$row['element_note']);
							$pdf->Ln();
						}
						
						// Set initial x-position on line
						$pdf->SetX($xStartPos);
							
						// Render choices
						foreach ($enum as $this_code=>$line)
						{
							// Check if we need to start new line to prevent text run-off
							if ($pdf->GetX() > ($max_line_width - $sigil_width - 30)) {
								$pdf->Ln();
								$pdf->SetX($xStartPos);
							}
							// Draw checkboxes
							$pdf->Cell(1,$row_height,'');
							$pdf->Cell($sigil_width,$row_height,'',0,0,'L',false);
							$x = array($pdf->GetX()-$sigil_width+.5,0); 
							$x[1] = $x[0] + $row_height-1;
							$y = array($pdf->GetY()+.5,0); 
							$y[1] = $y[0] + $row_height-1;
							$pdf->Rect($x[0],$y[0],$row_height-1,$row_height-1);
							// Determine if checkbox needs to be checked (if has data)
							$hasData = false; // Default		
							// Determine if this row's checkbox needs to be checked (i.e. it has data)
							if (isset($Data[$record][$event_id][$row['field_name']])) {		
								if (is_array($Data[$record][$event_id][$row['field_name']])) {
									// Checkbox fields
									if (isset($Data[$record][$event_id][$row['field_name']][$this_code]) && $Data[$record][$event_id][$row['field_name']][$this_code] == "1") {
										$hasData = true;
									}
								} elseif ($Data[$record][$event_id][$row['field_name']] == $this_code) {
									// Regular fields				
									$hasData = true;
								}
							}
							if ($hasData) {
								// X marks the spot
								$pdf->Line($x[0],$y[0],$x[1],$y[1]);
								$pdf->Line($x[0],$y[1],$x[1],$y[0]);
							}
							// Before printing label, first check if we need to start new line to prevent text run-off
							while (strlen($line) > 0) 
							{
								//print "<br>Xpos: ".$pdf->GetX().", Line: $line";
								if (($pdf->GetX() + $pdf->GetStringWidth($line)) >= $max_line_width) 
								{
									// If text will produce run-off, cut off and repeat in next loop to split up onto multiple lines
									$cutoff = $max_line_width - $pdf->GetX();
									// Since cutoff is in FPDF width, we need to find it's length in characters by going one character at a time
									$last_space_pos = 0; // Note the position of last space (for cutting off purposes)
									for ($i = 1; $i <= strlen($line); $i++) {
										// Check length of string segment
										$segment_width = $pdf->GetStringWidth(substr($line, 0, $i));
										// Check if current character is a space
										if (substr($line, $i, 1) == " ") $last_space_pos = $i;
										// If we found the cutoff, get the character count
										if ($segment_width >= $cutoff) {
											// Obtain length of segment and set segment value
											$segment_char_length = ($last_space_pos != 0) ? $last_space_pos : $i;
											$thisline = substr($line, 0, $segment_char_length);
											break;
										} else {
											$segment_char_length = strlen($line);
											$thisline = $line;
										}
									}
									// Print this segment of the line
									$thisline = trim($thisline);
									$pdf->Cell($pdf->GetStringWidth($thisline)+2,$row_height,$thisline);
									// Set text for next loop on next line
									$line = substr($line, $segment_char_length);
									// Now set new line with slight indentation (if another line is needed)
									if (strlen($line) > 0) {
										$pdf->Ln();
										$pdf->SetX($xStartPos+(($row['custom_alignment'] == 'LV') ? $sigil_width : 0));
										$pdf->Cell(1,$row_height,'');
									}
								} else {			
									// Text fits easily on one line
									$line = trim($line);
									$pdf->Cell($pdf->GetStringWidth($line)+4,$row_height,$line);
									// Reset to prevent further looping
									$line = "";
								}
							}
							// Insert line break if left-vertical alignment
							if ($row['custom_alignment'] == 'LV') {
								$pdf->Ln();
							}
						}	
						// For RH aligned with element note... 
						if ($row['custom_alignment'] == 'RH' && $row['element_note']) {
							$a_lines_note = text_vertical($row['element_note'], $char_limit_a);					
							foreach ($a_lines_note as $row2) {
								$pdf->Ln();
								$pdf->SetX($xStartPos);
								$pdf->Cell($col_width_a,$row_height,$row2);
							}
						}
						// For RH aligned, reset y-position if field label has more lines than choices
						if ($row['custom_alignment'] == 'RH' && $yPosAfterLabel > $pdf->GetY()) {
							$pdf->SetY($yPosAfterLabel);	
						}	
						// Insert line break if NOT left-vertical alignment (because was just added on last loop)
						else if ($row['custom_alignment'] != 'LV') {
							$pdf->Ln();
						}
					}
					// RV Alignment
					else
					{
						$q_lines = qtext_vertical($row, $char_limit_q);			
						if (isset($Data[$record][$event_id][$row['field_name']]) && $data_export_rights != '1' && $row['field_phi'] == "1") {
							//Is identifier and had data
							$a_lines = text_vertical("[IDENTIFIER]".$row['element_note'], $char_limit_a);
							$counter = (count($q_lines) >= count($a_lines)) ? count($q_lines) : count($a_lines);
							for ($i = 0; $i < $counter; $i++) {	
								$pdf->Cell($col_width_a,$row_height,$q_lines[$i],0,0);
								$pdf->Cell($col_width_b,$row_height,$a_lines[$i],0,1);			
							}
							$num++;
							$pdf->Ln();
							continue;
						} else {
							//Render choices normally
							$a_lines = atext_vertical_mc($row, $Data, $char_limit_a, $indent_a, $project_language, $event_id, $record);
							if ($row['element_note'] != "") {
								$a_lines_note = text_vertical($row['element_note'], $char_limit_a);					
								foreach ($a_lines_note as $row2) {
									$a_lines[] = $row2;
								}
							}
						}			
						$counter = (count($q_lines) >= count($a_lines)) ? count($q_lines) : count($a_lines);			
						$pdf = new_page_check($counter, $pdf, $y_units_per_line, $bottom_of_page, $study_id_event);	
						// If a survey and using custom question numbering, then render question number
						$pdf = addQuestionNumber($pdf, $row_height, $row['question_num'], (isset($isSurvey) && $isSurvey), $customQuesNum, $num);		
						for ($i = 0; $i < $counter; $i++) {
							$pdf->Cell($col_width_a,$row_height,(isset($q_lines[$i]) ? $q_lines[$i] : ""),0,0,'L',false);
							// Advances X without drawing anything
							if (isset($a_lines[$i]['sigil']) && $a_lines[$i]['sigil'] == "1"){
								$pdf->Cell($sigil_width,$row_height,'',0,0,'L',false);
								$x = array($pdf->GetX()-$sigil_width+.5,0); $x[1] = $x[0] + $row_height-1;
								$y = array($pdf->GetY()+.5,0); $y[1] = $y[0] + $row_height-1;
								$pdf->Rect($x[0],$y[0],$row_height-1,$row_height-1);
								if ($a_lines[$i]['chosen']){
									// X marks the spot
									$pdf->Line($x[0],$y[0],$x[1],$y[1]);
									$pdf->Line($x[0],$y[1],$x[1],$y[0]);
								}
								$pdf->Cell($atext_width,$row_height,$a_lines[$i]['line'],0,0,'L',false);
								$pdf->Ln();
							} else {
								if (isset($a_lines[$i]) && is_array($a_lines[$i])) {
									// If a choice (and not an element note), then indent for checkbox/radio box
									$pdf->Cell($sigil_width,$row_height,'',0,0,'C',false);
								}
								$pdf->Cell($atext_width,$row_height,((isset($a_lines[$i]) && is_array($a_lines[$i])) ? $a_lines[$i]['line'] : (isset($a_lines[$i]) ? $a_lines[$i] : "")),0,0,'L',false);
								$pdf->Ln();
							}		
						}
					}
					//print "<br>{$row['field_name']} \$pdf->GetY() = {$pdf->GetY()}";
					$num++;				
					
				// Descriptive
				} elseif ($row['element_type'] == "descriptive") {
							
					//Show notice of image/attachment
					$this_string = "";
					if (is_numeric($row['edoc_id']) || !defined("PROJECT_ID"))
					{
						if (!defined("PROJECT_ID")) {
							// Shared Library
							if ($row['edoc_display_img'] == '1') {
								$this_string .= "\n\n[Inline Image: {$row['edoc_id']}]";
							} elseif ($row['edoc_display_img'] == '0') {
								$this_string .= "\n\n[Attachment: {$row['edoc_id']}]"; 
							}
						} else {
							// REDCap project
							$sql = "select doc_name from redcap_edocs_metadata where project_id = " . PROJECT_ID . " 
									and delete_date is null and doc_id = ".$row['edoc_id']." limit 1";
							$q = db_query($sql);
							$fname = (db_num_rows($q) < 1) ? "Not found" : "\"".label_decode(db_result($q, 0))."\"";
							if ($row['edoc_display_img']) {
								$this_string .= "\n\n[Inline Image: $fname]";
							} else {
								$this_string .= "\n\n[Attachment: $fname]"; 
							}
						}
					}
					// New page check
					$counter = ceil($pdf->GetStringWidth($row['element_label'])/$max_line_width);
					$pdf = new_page_check($counter, $pdf, $y_units_per_line, $bottom_of_page, $study_id_event);
					// If a survey and using custom question numbering, then render question number				
					$pdf = addQuestionNumber($pdf, $row_height, $row['question_num'], (isset($isSurvey) && $isSurvey), $customQuesNum, "");
					// Label
					$pdf->MultiCell(0,4,$row['element_label'].$this_string,0);
					
				// Slider
				} elseif ($row['element_type'] == "slider") {
					
					// Parse the slider labels
					$slider_labels = parseSliderLabels($row['element_enum']);				
					$slider_min = slider_label($slider_labels['left'], $char_limit_slider);
					$slider_mid = slider_label($slider_labels['middle'], $char_limit_slider);
					$slider_max = slider_label($slider_labels['right'], $char_limit_slider);
				
					if ($row['custom_alignment'] == 'LV' || $row['custom_alignment'] == 'LH') {
						//Display left-aligned
						$this_string = $row['element_label'] . "\n\n";
						$slider_rows = array(count($slider_min), count($slider_mid), count($slider_max));
						$counter = max($slider_rows);
						$pdf = new_page_check($counter, $pdf, $y_units_per_line, $bottom_of_page, $study_id_event);
						// If a survey and using custom question numbering, then render question number				
						$pdf = addQuestionNumber($pdf, $row_height, $row['question_num'], $isSurvey, $customQuesNum, $num);
						while (count($slider_min) < $counter) array_unshift($slider_min,"");
						while (count($slider_mid) < $counter) array_unshift($slider_mid,"");
						while (count($slider_max) < $counter) array_unshift($slider_max,"");
						$pdf->MultiCell(0,4,$this_string,0);
						$pdf->SetFont('Arial','',8);			
						for ($i = 0; $i < $counter; $i++) {	
							$pdf->Cell(6,$row_height,"",0,0);
							$pdf->Cell((($num_rect*$rect_width)/3)+2,$row_height,$slider_min[$i],0,0,'L');
							$pdf->Cell((($num_rect*$rect_width)/3)+2,$row_height,$slider_mid[$i],0,0,'C');
							$pdf->Cell((($num_rect*$rect_width)/3)+2,$row_height,$slider_max[$i],0,1,'R');			
						}
						$x_pos = 20;
						$pdf->MultiCell(0,2,"",0);	
						for ($i = 1; $i <= $num_rect; $i++) {
							$emptyRect = true;
							if (isset($Data[$record][$event_id][$row['field_name']])) 
							{
								// If slider has value 0, fudge it to 1 so that it appears (otherwise looks empty)
								$sliderDisplayVal = ($Data[$record][$event_id][$row['field_name']] < 1) ? 1 : $Data[$record][$event_id][$row['field_name']];
								// Set empty rectangle
								if (round($sliderDisplayVal*$num_rect/100) == $i) {
									$emptyRect = false;
								}
							}
							if ($emptyRect) {
								$pdf->Rect($x_pos,$pdf->GetY(),$rect_width,1);
							} else {
								$pdf->Rect($x_pos,$pdf->GetY(),$rect_width,3,'F');
							}
							$x_pos = $x_pos + $rect_width;
						}
						if ($row['element_validation_type'] == "number" && isset($Data[$record][$event_id][$row['field_name']])) {
							$pdf->SetX($x_pos+2);
							$pdf->Cell(6,$row_height,$Data[$record][$event_id][$row['field_name']],1,0);
						}
						$pdf->MultiCell(0,4,"",0);
						$pdf->SetFont('Arial','I',7);
						if (!isset($Data[$record][$event_id][$row['field_name']])) {	
							$pdf->MultiCell(0,3,"                                                             (Place a mark on the scale above)",0);					
						}
					} else {	
						//Display right-aligned
						$q_lines = qtext_vertical($row, $char_limit_q);				
						$slider_rows = array(count($q_lines), count($slider_min), count($slider_mid), count($slider_max));
						$counter = max($slider_rows);				
						$pdf = new_page_check($counter, $pdf, $y_units_per_line, $bottom_of_page, $study_id_event);	
						// If a survey and using custom question numbering, then render question number				
						$pdf = addQuestionNumber($pdf, $row_height, $row['question_num'], $isSurvey, $customQuesNum, $num);
						while (count($slider_min) < $counter) array_unshift($slider_min,"");
						while (count($slider_mid) < $counter) array_unshift($slider_mid,"");
						while (count($slider_max) < $counter) array_unshift($slider_max,"");
						$x_pos = 120;
						for ($i = 0; $i < $counter; $i++) {
							$pdf->SetFont('Arial','',10);
							$pdf->Cell($col_width_a,$row_height,$q_lines[$i],0,0);
							$pdf->SetFont('Arial','',8);
							$pdf->Cell(1,$row_height,"",0,0);
							$pdf->Cell((($num_rect*$rect_width)/3)+2,$row_height,$slider_min[$i],0,0,'L');
							$pdf->Cell((($num_rect*$rect_width)/3)+2,$row_height,$slider_mid[$i],0,0,'C');
							$pdf->Cell((($num_rect*$rect_width)/3)+2,$row_height,$slider_max[$i],0,1,'R');				
						}						
						$pdf->MultiCell(0,2,"",0);
						for ($i = 1; $i <= $num_rect; $i++) {
							$emptyRect = true;
							if (isset($Data[$record][$event_id][$row['field_name']])) {
								// If slider has value 0, fudge it to 1 so that it appears (otherwise looks empty)
								$sliderDisplayVal = ($Data[$record][$event_id][$row['field_name']] < 1) ? 1 : $Data[$record][$event_id][$row['field_name']];
								// Set empty rectangle
								if (round($sliderDisplayVal*$num_rect/100) == $i) {
									$emptyRect = false;
								}
							}
							if ($emptyRect) {
								$pdf->Rect($x_pos,$pdf->GetY(),$rect_width,1);
							} else {
								$pdf->Rect($x_pos,$pdf->GetY(),$rect_width,3,'F');
							}
							$x_pos = $x_pos + $rect_width;
						}		
						if ($row['element_validation_type'] == "number" && isset($Data[$record][$event_id][$row['field_name']])) {
							$pdf->SetX($x_pos+2);
							$pdf->Cell(6,$row_height,$Data[$record][$event_id][$row['field_name']],1,0);
						}	
						$pdf->MultiCell(0,4,"",0);
						$pdf->SetFont('Arial','I',7);
						if (!isset($Data[$record][$event_id][$row['field_name']])) {
							$pdf->MultiCell(0,3,"(Place a mark on the scale above)           ",0,'R');
						}
					}
					$num++;
					
				// Text, Notes, Calcs, and File Upload fields
				} elseif ($row['element_type'] == "textarea" || $row['element_type'] == "text" 
					|| $row['element_type'] == "calc" || $row['element_type'] == "file") {
					
					// If field note exists, format it first
					if ($row['element_note'] != "") {
						$row['element_note'] = "\n(".$row['element_note'].")";
					}
					
					// If a File Upload field *with* data, just display [document]. If no data, display nothing.
					if ($row['element_type'] == "file") {
						$Data[$record][$event_id][$row['field_name']] = (isset($Data[$record][$event_id][$row['field_name']]))
							? $lang['data_export_tool_148'] : '';
					}
					
					if ($row['custom_alignment'] == 'LV' || $row['custom_alignment'] == 'LH') 
					{
						if ($row['element_type'] == "textarea") {
							$row['element_label'] .= $row['element_note'] . "\n\n";
						} else {
							$row['element_label'] .= "\n\n";
						}
						
						// Left-aligned
						if (isset($Data[$record][$event_id][$row['field_name']])) {					
							// Unescape text for Text and Notes fields (somehow not getting unescaped for left alignment)
							$Data[$record][$event_id][$row['field_name']] = label_decode($Data[$record][$event_id][$row['field_name']]);
							//Has data
							if ($data_export_rights != '1' && $row['field_phi'] == "1") {
								//Is identifier
								$row['element_label'] .= "[IDENTIFIER]";
							} else {
								if ($project_language == 'Japanese') {
									$row['element_label'] .= mb_convert_encoding($Data[$record][$event_id][$row['field_name']], "SJIS", "UTF-8"); // Japanese
								} else {
									$row['element_label'] .= $Data[$record][$event_id][$row['field_name']];
								}
							}
							if ($row['element_type'] != "textarea") {
								$row['element_label'] .= $row['element_note'];
							}
						} else {
							if ($row['element_type'] == "textarea") {
								$row['element_label'] .= "\n\n\n\n\n";
							} else {
								$row['element_label'] .= "__________________________________" . $row['element_note'];
							}
						}
						// New page check
						$counter = ceil($pdf->GetStringWidth($row['element_label'])/$max_line_width);
						$pdf = new_page_check($counter, $pdf, $y_units_per_line, $bottom_of_page, $study_id_event);
						// If a survey and using custom question numbering, then render question number				
						$pdf = addQuestionNumber($pdf, $row_height, $row['question_num'], $isSurvey, $customQuesNum, $num);
						$pdf->MultiCell(0,4,$row['element_label'],0);
					}
					else
					{
						// Right-aligned
						if ($row['element_type'] == "textarea") {
							//$row['element_label'] .= $row['element_note'];
							$this_string = $row['element_label'];
							$q_lines = text_vertical($this_string,$char_limit_q);
						} else {
							$q_lines = qtext_vertical($row, $char_limit_q);
						}
						if (isset($Data[$record][$event_id][$row['field_name']])) {
							//Has data
							if ($data_export_rights != '1' && $row['field_phi'] == "1") {
								//Is identifier
								$a_lines = text_vertical("[IDENTIFIER]".$row['element_note'], $char_limit_a);
							} else {
								$this_textv = $Data[$record][$event_id][$row['field_name']] . $row['element_note'];
								if ($project_language == 'Japanese') $this_textv = mb_convert_encoding($this_textv, "SJIS", "UTF-8"); // Japanese
								$a_lines = text_vertical($this_textv, $char_limit_a);
							}
						} else {
							if ($row['element_type'] == "textarea") {
								$last_line = count($q_lines)-1;
								if (!empty($row['element_note'])) {
									$a_lines[$last_line--] = $row['element_note'];
								}
								$a_lines[$last_line] = "__________________________________";
								// print_array($q_lines);
								// print_array($a_lines);
							} else {
								$a_lines = text_vertical("__________________________________" . $row['element_note'], $char_limit_a);
							}
						}
						// print_array($q_lines);
						// print_array($a_lines);print "<hr>";
						
						$counter = (count($q_lines) >= count($a_lines)) ? count($q_lines) : count($a_lines);		
						$pdf = new_page_check($counter, $pdf, $y_units_per_line, $bottom_of_page, $study_id_event);
						// If a survey and using custom question numbering, then render question number				
						$pdf = addQuestionNumber($pdf, $row_height, $row['question_num'], (isset($isSurvey) && $isSurvey), $customQuesNum, $num);
						for ($i = 0; $i < $counter; $i++) {	
							$pdf->Cell($col_width_a,$row_height,$q_lines[$i],0,0);
							$pdf->Cell($col_width_b,$row_height,$a_lines[$i],0,1);			
						}		
					}
					$num++;
					
				}
				$pdf->Ln();
				// Save this form_name for the next loop
				$last_form = $row['form_name'];
			}
			
			
			
			// LOCKING & E-SIGNATURE: Check if this form has been locked and/or e-signed, when viewing PDF with data
			if ($record != '')
			{		
				// Check if need to display this info at all
				$sql = "select display, display_esignature, label from redcap_locking_labels where project_id = " . PROJECT_ID . " 
						and form_name = '{$_GET['page']}' limit 1";
				$q = db_query($sql);
				// If it is NOT in the table OR if it IS in table with display=1, then show locking/e-signature
				$displayLocking		= (!db_num_rows($q) || (db_num_rows($q) && db_result($q, 0, "display") == "1"));
				$displayEsignature  = (db_num_rows($q) && db_result($q, 0, "display_esignature") == "1");
				
				// LOCKING
				if ($displayLocking)
				{
					// Set customized locking label (i.e affidavit text for e-signatures)
					$custom_lock_label = db_num_rows($q) ? (label_decode(db_result($q, 0, "label")) . "\n\n") : "";
					// Check if locked
					$sql = "select l.username, l.timestamp, u.user_firstname, u.user_lastname from redcap_locking_data l, redcap_user_information u 
							where l.project_id = " . PROJECT_ID . " and l.username = u.username
							and l.record = '" . prep($_GET['id']) . "' and l.event_id = '" . prep($_GET['event_id']) . "' 
							and l.form_name = '" . prep($_GET['page']) . "' limit 1";
					$q = db_query($sql);		
					if (db_num_rows($q)) 
					{			
						$form_locked = db_fetch_assoc($q);
						// Set string to capture lock text
						$lock_string = "Locked ";
						if ($form_locked['username'] != "") {
							$lock_string .= "by {$form_locked['username']} ({$form_locked['user_firstname']} {$form_locked['user_lastname']}) ";
						}
						$lock_string .= "on " . format_ts_mysql($form_locked['timestamp']);
				
						// E-SIGNATURE
						if ($displayEsignature)
						{
							// Check if e-signed
							$sql = "select e.username, e.timestamp, u.user_firstname, u.user_lastname from redcap_esignatures e, redcap_user_information u 
									where e.project_id = " . PROJECT_ID . " and e.username = u.username and e.record = '" . prep($_GET['id']) . "' 
									and e.event_id = '" . prep($_GET['event_id']) . "' and e.form_name = '" . prep($_GET['page']) . "' limit 1";
							$q = db_query($sql);	
							if (db_num_rows($q)) 
							{
								$form_esigned = db_fetch_assoc($q);
								// Set string to capture lock text
								$lock_string = "E-signed by {$form_esigned['username']} ({$form_esigned['user_firstname']} "
											 . "{$form_esigned['user_lastname']}) on " . format_ts_mysql($form_esigned['timestamp']) 
											 . "\n" . $lock_string;					
							}
						}
						
						// Now add custom locking text, if was set (will have blank value if not set)
						$lock_string = $custom_lock_label . $lock_string;
						
						// Render the lock record and e-signature text
						$num_lines = ceil(strlen(strip_tags($lock_string))/$est_char_per_line)+substr_count($lock_string, "\n");
						$pdf = new_page_check($num_lines, $pdf, $y_units_per_line, $bottom_of_page, $study_id_event);		
						$pdf->MultiCell(0,5,'');
						$pdf->MultiCell(0,5,$lock_string,1);
					}
				}
			}
			

			// If form has an Acknowledgement, render it here
			if ($acknowledgement != "") {
				// Calculate how many lines will be needed for text to check if new page is needed
				$num_lines = ceil(strlen(strip_tags($acknowledgement))/$est_char_per_line)+substr_count($acknowledgement, "\n");
				$pdf = new_page_check($num_lines, $pdf, $y_units_per_line, $bottom_of_page, $study_id_event);		
				$pdf->MultiCell(0,20,'');
				$pdf->MultiCell(0,1,'','B');
				$pdf->WriteHTML(nl2br($acknowledgement));
			}
			
		}
	}

	// Remove special characters from title for using as filename
	$filename = "";
	if (isset($_GET['page'])) {
		$filename .= str_replace(" ", "", ucwords(preg_replace("/[^a-zA-Z0-9]/", " ", $form_title))) . "_";
	}
	$filename .= str_replace(" ", "", ucwords(preg_replace("/[^a-zA-Z0-9]/", " ", $project_name)));
	// Make sure filename is not too long
	if (strlen($filename) > 30) {
		$filename = substr($filename, 0, 30);
	}
	// Add timestamp if data in PDF
	if (isset($_GET['id']) || isset($_GET['allrecords'])) {
		$filename .= date("_Y-m-d_Hi");
	}
	
	// Output to file
	$pdf->Output("$filename.pdf",'D');

}


/**
 * Extend FPDF to add WriteHTML function
 */
class PDF extends FPDF {

	function WriteHTML($html) {
		//HTML parser
		$html=str_replace("\n",' ',$html);
		$a=preg_split('/<(.*)>/U',$html,-1,PREG_SPLIT_DELIM_CAPTURE);
		foreach($a as $i=>$e) {
			if($i%2==0) {
				//Text
				// if($this->HREF)
				// $this->PutLink($this->HREF,$e);
				// else
				if (USE_UTF8) {
					$this->Write(8,$e);
				} else {
					$this->Write(5,$e);
				}
			}
			else {
				//Tag
				if($e[0]=='/')
					$this->CloseTag(strtoupper(substr($e,1)));
				else {
					//Extract attributes
					$a2=explode(' ',$e);
					$tag=strtoupper(array_shift($a2));
					$attr=array();
					foreach($a2 as $v) {
						if(preg_match('/([^=]*)=["\']?([^"\']*)/',$v,$a3))
							$attr[strtoupper($a3[1])]=$a3[2];
					}
					$this->OpenTag($tag,$attr);
				}
			}
		}
	}

	function OpenTag($tag,$attr) {
		//Opening tag
		if($tag=='B' || $tag=='I' || $tag=='U')
			$this->SetStyle($tag,true);
		if($tag=='A')
			$this->HREF=$attr['HREF'];
		if($tag=='BR')
			$this->Ln(5);
	}

	function CloseTag($tag) {
		//Closing tag
		if($tag=='B' || $tag=='I' || $tag=='U')
			$this->SetStyle($tag,false);
		if($tag=='A')
			$this->HREF='';
	}

	function SetStyle($tag,$enable) {
		//Modify style and select corresponding font
		$this->$tag+=($enable ? 1 : -1);
		$style='';
		foreach(array('B','I','U') as $s) {
			if($this->$s>0)
				$style.=$s;
		}
		$this->SetFont('',$style);
	}

	function PutLink($URL,$txt) {
		//Put a hyperlink
		$this->SetTextColor(0,0,255);
		$this->SetStyle('U',true);
		if (USE_UTF8) {
			$this->Write(8,$txt,$URL);
		} else {
			$this->Write(5,$txt,$URL);
		}
		$this->SetStyle('U',false);
		$this->SetTextColor(0);
	}
}

//Computes the number of lines a MultiCell of width w will take
function NbLines($pdf, $w, $txt)
{
	return ceil($pdf->GetStringWidth($txt)/$w);
}

// Generate a matrix header row of multicells
function renderMatrixHeaderRow($pdf,$hdrs=array(),$page_width,$matrix_label_width)
{
	$pdf->SetFont(FONT,'',9);
	// Construct row-specific parameters
	$mtx_hdr_width = round(($page_width - $matrix_label_width)/count($hdrs));
	$widths = array($matrix_label_width); // Default for field label
	$data = array("");
	foreach ($hdrs as $hdr) {
		$widths[] = $mtx_hdr_width;
		$data[] = $hdr;
	}
	//Calculate the height of the row
	$nb=0;
	for($i=0;$i<count($data);$i++)
		$nb=max($nb, NbLines($pdf, $widths[$i], $data[$i]));
	$h=5*$nb;
	//If the height h would cause an overflow, add a new page immediately
	if($pdf->GetY()+$h>$pdf->PageBreakTrigger) {
		$pdf->AddPage($pdf->CurOrientation);
	}
	//Draw the cells of the row
	for($i=0;$i<count($data);$i++)
	{
		$w=$widths[$i];
		//$a=isset($pdf->aligns[$i]) ? $pdf->aligns[$i] : 'L';
		$a=$i==0 ? 'L' : 'C';
		//Save the current position
		$x=$pdf->GetX();
		$y=$pdf->GetY();
		//Draw the border
		//$pdf->Rect($x, $y, $w, $h);
		//Print the text
		$pdf->MultiCell($w, 4, strip_tags(label_decode($data[$i])), 0, $a);
		//Put the position to the right of the cell
		$pdf->SetXY($x+$w, $y);
	}
	// Set Y
	$pdf->SetY($pdf->GetY()+$h);
	//Go to the next line
	//$pdf->Ln();
	// Reset font back to earlier value
	$pdf->SetFont(FONT,'',10);
	return $pdf;
}

// Generate a matrix field row of multicells
function renderMatrixRow($pdf,$label,$hdrs=array(),$enumData,$row_height,$sigil_width,$page_width,$matrix_label_width,$bottom_of_page,$study_id_event)
{
	$chkbx_width = $row_height-1;
	// Construct row-specific parameters
	$mtx_hdr_width = round(($page_width - $matrix_label_width)/count($hdrs));
	$widths = array($matrix_label_width); // Default for field label
	$data = array('Label-Key'=>$label);
	foreach ($hdrs as $key=>$hdr) {
		$widths[] = $mtx_hdr_width;
		$data[$key] = (isset($enumData[$key])); // checked value for each checkbox/radio button
	}
	//print_array($data);print "<br>";
	//Calculate the height of the row
	$nb = NbLines($pdf, $matrix_label_width, $label);
	//Issue a page break first if needed
	//$pdf->CheckPageBreak($h);
	//If the height h would cause an overflow, add a new page immediately
	// if($pdf->GetY()+$h>$pdf->PageBreakTrigger) {
		// $pdf->AddPage($pdf->CurOrientation);
	// }
	if ($pdf->GetY()+($nb*$row_height) > ($bottom_of_page-20)) {
		$pdf->AddPage();
		// Set logo at bottom
		setFooterImage($pdf);
		// Set "Confidential" text at top
		$pdf = confidentialText($pdf);
		// Add page number
		if ($study_id_event != "") {
			$pdf->SetFont(FONT,'BI',8);
			$pdf->Cell(0,2,$study_id_event,0,1,'R');
			$pdf->Ln();
		}
		$pdf->SetFont(FONT,'I',8);
		$pdf->Cell(0,5,'Page '.$pdf->PageNo().' of {nb}',0,1,'R');
		// Line break and reset font
		$pdf->Ln();
		$pdf->SetFont(FONT,'',10);
	}
	//Draw the cells of the row
	$i = 0;
	foreach ($data as $key=>$isChecked)
	{
		$w=$widths[$i];
		//Save the current position
		$x=$pdf->GetX();
		$y=$pdf->GetY();
		if($i!=0) {
			// Draw checkbox/radio
			$xboxpos = $x-1+floor($mtx_hdr_width/2);
			$pdf->Rect($xboxpos, $y, $chkbx_width,$chkbx_width);
			// Positions of line 1
			$line1_x0 = $xboxpos;
			$line1_y0 = $y;
			$line1_x1 = $line1_x0+$chkbx_width;
			$line1_y1 = $line1_y0+$chkbx_width;
			// Positions of line 2
			$line2_x0 = $xboxpos;
			$line2_y0 = $y+$chkbx_width;
			$line2_x1 = $line2_x0+$chkbx_width;
			$line2_y1 = $y;
			// If checked, then X marks the spot
			if ($isChecked) {
				$pdf->Line($line1_x0,$line1_y0,$line1_x1,$line1_y1);
				$pdf->Line($line2_x0,$line2_y0,$line2_x1,$line2_y1);
			}			
		} else {
			//Print the label
			$pdf->MultiCell($w, $row_height, strip_tags(label_decode($label)), 0, 'L');
			$yLabel = $y+(($nb-1)*$row_height*1.3);
		}
		//Put the position to the right of the cell
		$pdf->SetXY($x+$w, $y);
		// Increment counter
		$i++;
	}
	//Go to the next line
	if ($nb > 1) {
		// Set Y
		$pdf->SetY($yLabel);
	}
	$pdf->Ln(2);
	return $pdf;
}


// Get total matrix group height, including SH, so check if we need a page break invoked below
function getMatrixHeight($pdf, $field, $page_width, $matrix_label_width)
{
	global $Proj, $ProjMetadata, $ProjMatrixGroupNames;
	// Set initial line count
	$lines = 0;
	// Get count of total lines for SH (adding 2 extra lines for spacing and double lines)
	$SH = $ProjMetadata[$field]['element_preceding_header'];
	$lines += ($SH == '' ? 0 : 2) + NbLines($pdf, $page_width, $SH);
	// Get max line count over all matrix headers
	$hdrs = parseEnum($ProjMetadata[$field]['element_enum']);
	$mtx_hdr_width = round(($page_width - $matrix_label_width)/count($hdrs));
	$widths = array($matrix_label_width); // Default for field label
	$data = array("");
	foreach ($hdrs as $hdr) {
		$widths[] = $mtx_hdr_width;
		$data[] = $hdr;
	}
	$nb=0;
	for($i=0;$i<count($data);$i++)
		$nb=max($nb, NbLines($pdf, $widths[$i], $data[$i]));
	$lines += $nb;
	// Get count of EACH field in the matrix
	$grid_name = $ProjMetadata[$field]['grid_name'];
	foreach ($ProjMatrixGroupNames[$grid_name] as $thisfield) {
		// Get label for each
		$thislabel = $ProjMetadata[$thisfield]['element_label'];
		// Get line count for this field
		$lines += NbLines($pdf, $matrix_label_width, $thislabel);
	}
	// Return height
	return $lines;
}
