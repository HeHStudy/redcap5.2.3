<?php

class CalculateParser 
{
	private $_results = array();
	private $_equations = array();
	
    public function feedEquation($name, $string) 
	{
		global $longitudinal, $Proj;
		
		//Add field to calculated field list
		array_push($this->_results, $name);
		
		//Replace operators in equation with javascript equivalents (Strangely, the < character causes issues with str_replace later when it has no spaces around it, so add spaces around it)
		$orig = array("<"  , "=" , "===", "====", "> ==", "< ==", ">==", "<==", "< >", "<>", " and ", " AND ", " or ", " OR ");
		$repl = array(" < ", "==", "==" , "=="  , ">="  , "<="  , ">="  , "<="  , "<>" , "!=", " && " , " && " , " || ", " || ");
		$string = str_replace($orig, $repl, $string);
		
		//Get list of field names used in string		
		$these_fields = getBracketedFields($string, true, true);
		
		// Replace unique event name+field_name in brackets with javascript equivalent
		if ($longitudinal) {
			$string = preg_replace("/(\[)([^\[]*)(\]\[)([^\[]*)(\])/", "document.form__$2.$4.value", $string);
		}
		// Replace field_name in brackets with javascript equivalent
		$string = preg_replace("/(\[)([^\[]*)(\])/", "document.form.$2.value", $string);
		// Now compensate for unique formatting for checkboxes in javascript, if there are any checkboxes
		if (strpos($string, ").value") !== false) {
			$string = preg_replace("/(document.)([a-z0-9_]+)(.)([a-zA-Z0-9_]+)([\(]{1})([a-zA-Z0-9_-]+)([\)]{1})(.value)/", 
								   "(if(document.forms['$2'].elements['__chk__$4_RC_$6'].value=='',0,1)*1)", $string);
		}
		
		// Replace field names with javascript equivalent 
		foreach (array_keys($these_fields) as $this_field) 
		{
			// If using unique event name in equation and we're currently on that event, replace the event name in the JS
			if ($longitudinal && strpos($this_field, ".") !== false) 
			{
				list ($this_event, $this_field) = explode(".", $this_field, 2);
				$this_event_id = array_search($this_event, $Proj->getUniqueEventNames());
				if ($this_event_id == $_GET['event_id']) 
				{
					$string = str_replace("document.form__{$this_event}.", "document.form.", $string);
					$string = str_replace("document.forms['form__{$this_event}'].", "document.form.", $string);
				}
			}
			// If field is not a date[time] field, then wrap the field with chkNull function to 
			// ensure that we get either a numerical value or NaN.
			$fieldValidation = $Proj->metadata[$this_field]['element_validation_type'];
			if (!($Proj->metadata[$this_field]['element_type'] == 'text' 
				&& ($fieldValidation == 'time' || substr($fieldValidation, 0, 4) == 'date'))) 
			{
				// Any field except a date[time] field
				$string = str_replace("document.form.$this_field.value", "chkNull(document.form.$this_field.value)", $string);
			}
		}
		
		// Replace ^ exponential form with javascript equivalent
		$string = $this->replaceExponents($string);
			
		// Temporarily swap out commas in any datediff() functions (so they're not confused in IF statement processing).
		// They will be replaced back at the end.
		$string = $this->replaceDatediff($string);
		
		// Temporarily swap out commas in any round() functions (so they're not confused in IF statement processing).
		// They will be replaced back at the end.
		$string = $this->replaceRound($string);
		
		// If using conditional logic, format any conditional logic to Javascript ternary operator standards
		$string = convertIfStatement($string);
			
		// Now swap datediff() commas back into the equation (was replaced with -DDC-)
		if (strpos($string, "-DDC-") !== false) $string = str_replace("-DDC-", ",", $string);
		
		// Now swap round() commas back into the equation (was replaced with -ROC-)
		if (strpos($string, "-ROC-") !== false) $string = str_replace(array("-ROC-)","-ROC-"), array(")",","), $string);
		
		// Now swap sqrt() or exponential commas back into the equation (was replaced with -DDC-)
		if (strpos($string, "-EXPC-") !== false) $string = str_replace("-EXPC-", ",", $string);
		
		// Now swap all "+" with "*1+1*" in the equation to work around possibility of JavaScript concatenation in some cases
		if (strpos($string, "+") !== false) $string = str_replace("+", "*1+1*", $string);
		
		array_push($this->_equations, $string);
    }
	
	public function exportJS() 
	{
		$result  = "\n<!-- Calculations -->";			
		$result .= "\n<script type=\"text/javascript\">\n";
		$result .= "function calculate(){\n";
		
		for ($i = 0; $i < sizeof($this->_results); $i++) 
		{
			// Set string for try/catch
			if (isset($_GET['__showerrors'])) {
				$try = "";
				$catch = "";
			} else {
				$try = "try{";
				$catch = "}catch(e){calcErr('" . $this->_results[$i] . "')}";
			}
			$result .= "  $try var varCalc_" . $i . "=" . html_entity_decode($this->_equations[$i], ENT_QUOTES) . ";";				
			$result .= "document.form." . $this->_results[$i] . ".value=isNumeric(varCalc_{$i})?varCalc_{$i}:''; $catch\n";
		}
		
		$result .= "  return false;\n";			
		$result .= "}\n";		
		$result .= "calcErrExist = calculate();\n";		
		$result .= "</script>\n";
		
		$result .= "<script type=\"text/javascript\">\n";
		$result .= "if(calcErrExist){calcErr2()}\n";
		$result .= "</script>\n";
		
		return $result;
	}
	
	// Replace datediff()'s comma with -DDC- (to be removed later) so it does not interfere with ternary formatting later
	public function replaceDatediff($string)
	{
		if (strpos($string, "datediff") !== false)
		{
			## Determine which format of datediff() they're using (can include or exclude certain parameters)
			// Include the 'returnSignedValue' parameter
			$regex = "/(datediff)(\s*)(\()([^,\(\)]+)(,)([^,\(\)]+)(,)([^,\(\)]+)(,)([^,\(\)]+)(,)([^,\(\)]+)(\))/";
			if (preg_match($regex, $string)) 
			{
				$string = preg_replace($regex, "datediff($4-DDC-$6-DDC-$8-DDC-$10-DDC-$12)", $string);
			}
			// Include the 'dateformat' parameter
			$regex = "/(datediff)(\s*)(\()([^,\(\)]+)(,)([^,\(\)]+)(,)([^,\(\)]+)(,)([^,\(\)]+)(\))/";
			if (preg_match($regex, $string)) 
			{
				$string = preg_replace($regex, "datediff($4-DDC-$6-DDC-$8-DDC-$10)", $string);
			}
			// Now try pattern without the 'dateformat' parameter (legacy)
			$regex = "/(datediff)(\s*)(\()([^,\(\)]+)(,)([^,\(\)]+)(,)([^,\(\)]+)(\))/";
			if (preg_match($regex, $string)) 
			{
				$string = preg_replace($regex, "datediff($4-DDC-$6-DDC-$8)", $string);
			}
		}
		return $string;
	}
	
	// Replace round()'s comma with -ROC- (to be removed later) so it does not interfere with ternary formatting later
	public function replaceRound($string,$i=0)
	{
		// Deal with round(, if any are present
		if (strpos($string, "round") !== false) 
		{
			$regex = "/(round)(\s*)(\()([^,]+)(,)([^,]+)(\))/";
			// Replace all instances of round() that contain a comma inside so it does not interfere with ternary formatting later
			while (preg_match($regex, $string) && $i++ < 20) 
			{
				$string = preg_replace_callback($regex, "CalculateParser::replaceRoundCallback", $string);
			}
		}
		// Replace back commas that are not used in round()
		$string = str_replace("-REMOVE-", ",", $string);
		return $string;
	}
	
	// Callback function for replacing round()'s comma
	static function replaceRoundCallback($matches)
	{
		// If non-equal number of '(' vs. ')', then send back -REMOVE- to replace as comma to prevent function from 
		// going into infinite loops. Otherwise, assume the comma belongs to this round().
		return ((substr_count($matches[4], "(") != substr_count($matches[4], ")")) ? "round(".$matches[4]."-REMOVE-".$matches[6].")" : "round(".$matches[4]."-ROC-".$matches[6].")");
	}	
		
	//Replace ^ exponential form with javascript equivalent
	public function replaceExponents($string) {

		//First, convert any "sqrt" functions to javascript equivalent	
		$first_loop = true;
		while (preg_match("/(sqrt)(\s*)(\()/", $string)) {
			//Ready the string to location "sqrt(" substring easily
			if ($first_loop) {
				$string = preg_replace("/(sqrt)(\s*)(\()/", "sqrt(", $string);
				$first_loop = false;
			}
			//Loop through each character and find outer parenthesis location
			$last_char = strlen($string);
			$sqrt_pos  = strpos($string, "sqrt(");
			$found_end = false;
			$rpar_count = 0;
			$lpar_count = 0;
			$i = $sqrt_pos;
			//Since there are parentheses inside "sqrt", loop through each letter to localize and replace
			if (!preg_match("/(sqrt)(\()([^\(\)]{1,})(\))/", $string)) {
				while ($i <= $last_char && !$found_end) {
					//Keep count of left/right parentheses
					if (substr($string, $i, 1) == "(") {
						$lpar_count++;
					} elseif (substr($string, $i, 1) == ")") {
						$rpar_count++;
					}
					//If found the parentheses boundary, then end loop
					if ($rpar_count > 0 && $lpar_count > 0 && $rpar_count == $lpar_count) {
						$found_end = true;
					} else {
						$i++;
					}
				}
				$inside = substr($string, $sqrt_pos + 5, $i - $sqrt_pos - 5);
				//Replace this instance of "sqrt"
				$string = substr($string, 0, $sqrt_pos) . "Math.pow($inside-EXPC-0.5)" . substr($string, $i + 1);
			//There are no parentheses inside "sqrt", so do simple preg_replace
			} else {
				$string = preg_replace("/(sqrt)(\()([^\(\)]{1,})(\))/", "Math.pow($3-EXPC-0.5)", $string);
			}
		}

		//Find all ^ and locate outer parenthesis for its number and exponent
		$caret_pos = strpos($string, "^");
		while ($caret_pos !== false) {
		
			//For first half of string
			$found_end = false;
			$rpar_count = 0;
			$lpar_count = 0;
			$i = $caret_pos;
			while ($i >= 0 && !$found_end) {
				$i--;
				//Keep count of left/right parentheses
				if (substr($string, $i, 1) == "(") {
					$lpar_count++;
				} elseif (substr($string, $i, 1) == ")") {
					$rpar_count++;
				}
				//If found the parentheses boundary, then end loop
				if ($rpar_count > 0 && $lpar_count > 0 && $rpar_count == $lpar_count) {
					$found_end = true;
				}
			}
			//Completed first half of string
			$string = substr($string, 0, $i). "Math.pow(" . substr($string, $i);
			$caret_pos += 9; // length of "Math.pow("
			
			//For last half of string
			$last_char = strlen($string);
			$found_end = false;
			$rpar_count = 0;
			$lpar_count = 0;
			$i = $caret_pos;
			while ($i <= $last_char && !$found_end) {
				$i++;
				//Keep count of left/right parentheses
				if (substr($string, $i, 1) == "(") {
					$lpar_count++;
				} elseif (substr($string, $i, 1) == ")") {
					$rpar_count++;
				}
				//If found the parentheses boundary, then end loop
				if ($rpar_count > 0 && $lpar_count > 0 && $rpar_count == $lpar_count) {
					$found_end = true;
				}
			}
			//Completed last half of string
			$string = substr($string, 0, $caret_pos) . "-EXPC-" . substr($string, $caret_pos + 1, $i - $caret_pos) . ")" . substr($string, $i + 1);
			
			//Set again for checking in next loop
			$caret_pos = strpos($string, "^");
			
		}
		return $string;
	}

	
}
