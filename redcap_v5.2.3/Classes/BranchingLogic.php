<?php

class BranchingLogic
{	
	private $_results;
	private $_equations;
       				
	public function __construct() 
	{
        $this->_results = array();
   		$this->_equations = array();
    }
    
    public function feedBranchingEquation($name, $string) 
	{
		global $longitudinal, $Proj;
		array_push($this->_results, $name);
		// Replace operators in equation with javascript equivalents 
		$orig = array("=" , "===", "====", "> ==", ">==", "< ==", "<==", "<>", " and ", " AND ", " or ", " OR ");
		$repl = array("==", "==" , "=="  , ">="  , ">=" , "<="  , "<=" , "!=", " && " , " && " , " || ", " || ");
		$string = str_replace($orig, $repl, label_decode($string));
		// Replace unique event name+field_name in brackets with javascript equivalent
		if ($longitudinal) {
			$string = preg_replace("/(\[)([^\[]*)(\]\[)([^\[]*)(\])/", "document.form__$2.$4.value", $string);
		}
		// Replace field_name in brackets with javascript equivalent
		$string = preg_replace("/(\[)([^\[]*)(\])/", "document.form.$2.value", $string);
		// Now compensate for unique formatting for checkboxes in javascript, if there are any checkboxes
		if (strpos($string, ").value") !== false) {
			$string = preg_replace("/(document.)([a-z0-9_]+)(.)([a-zA-Z0-9_]+)([\(]{1})([a-zA-Z0-9_-]+)([\)]{1})(.value)([\s]*)(==)([\s]*)([\"|']?)(1|0)([\"|']?)/", 
								   "document.forms['$2'].elements['__chk__$4_RC_$6'].value == ($13==0?'':'$6')", $string);
		}
		// If using unique event name in equation and we're currently on that event, replace the event name in the JS
		if ($longitudinal) {
			// Search for current unique event name in string and replace
			$events = $Proj->getUniqueEventNames();
			$this_event = $events[$_GET['event_id']];
			$string = str_replace("document.form__{$this_event}.", "document.form.", $string);
			$string = str_replace("document.forms['form__{$this_event}'].", "document.form.", $string);
		}
		
		// Add to array
		array_push($this->_equations, $string);
    }
	
	public function exportBranchingJS() 
	{
		$result  = "\n<!-- Branching Logic -->";	
		$result .= "\n<script type=\"text/javascript\">\n";
		$result .= "function doBranching(){\n";
		// Loop through all branching logic fields
		for ($i = 0; $i < sizeof($this->_results); $i++) 
		{
			// Show the field only if the condition is true; Hide it if false. Prompt if about to hide a field with data already entered.
			$this_field = $this->_results[$i];
			// Set string for try/catch
			if (isset($_GET['__showerrors'])) {
				$try = "";
				$catch = "";
			} else {
				$try = "try{";
				$catch = "}catch(e){brErr('$this_field')}";
			}
			// Add line of JS
			$result .= "  $try evalLogic('$this_field',(" . html_entity_decode($this->_equations[$i], ENT_QUOTES) . ")); $catch\n";
		}		
		// Hide any section headers in which all fields in the section have been hidden
		$result .= "  hideSectionHeaders();\n";
		$result .= "  return false;\n";		
		$result .= "}\n";
		
		// Add javascript for form/survey page to show form table right before we execute the branching
		$result .= "document.getElementById('form_table_loading').style.display='none';\n";
		$result .= "document.getElementById('form_table').style.display='block';\n";
		$result .= "if (elementExists(document.getElementById('form_response_header'))) document.getElementById('form_response_header').style.display='block';\n";
		
		$result .= "brErrExist = doBranching();\n";
		$result .= "</script>\n";			
		$result .= "<script type=\"text/javascript\">\n";
		$result .= "if(brErrExist){brErr2()}\n";
		$result .= "</script>\n";
		
		return $result;
	}
}
