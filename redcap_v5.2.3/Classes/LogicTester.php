<?php

// Require math functions in case special functions are used in the conditional logic
require_once APP_PATH_DOCROOT . 'ProjectGeneral/math_functions.php';


/**
 * LogicTester
 * This class is used for execution/testing of logic used in Data Quality, branching logic, Automated Invitations, etc.
 */
class LogicTester
{
	/**
	 * Tests the logic with existing data and returns boolean as TRUE if all variables have values 
	 * and the logic evaluates as true. Otherwise, return FALSE.
	 * @param string $logic is the raw logic provided by the user in the branching logic/Data Quality logic format.
	 * @param array $record_data holds the record data with event_id as first key, field name as second key,
	 * and value as data value (if checkbox, third key is raw coded value with value as 0/1).
	 */
	static public function apply($logic, $record_data=array())
	{
		global $Proj;
		// Get unique event names (with event_id as key)
		$events = $Proj->getUniqueEventNames();	
		// If there is an issue in the logic, then return an error message and stop processing
		$funcName = null;
		try {
			// Instantiate logic parser
			$parser = new LogicParser();
			list ($funcName, $argMap) = $parser->parse($logic, array_flip($events));
			// print $parser->generatedCode;
		}
		catch (LogicException $e) {
			// print "Error: ".$e->getMessage();
			return false;
		}
		// Execute the logic to return boolean (return TRUE if is 1 and not 0 or FALSE)
		return (self::applyLogic($funcName, $argMap, $record_data) === 1);
	}
	
	
	/**
	 * Check if the logic is syntactically valid
	 */
	static public function isValid($logic)
	{
		$parser = new LogicParser();
		try {
			$parser->parse($logic, null, false);
			return true;
		} catch (LogicException $e) {
			return false;
		}
	}
	
	
	/**
	 * Evaluate a logic string for a given record
	 */
	static public function evaluateLogicSingleRecord($raw_logic, $record) 
	{
		global $Proj;
		// Check the logic to see if it's syntactically valid
		if (!self::isValid($raw_logic)) {
			return false;
		}
		// Get unique event names (with event_id as key)
		$events = $Proj->getUniqueEventNames();
		// Array to collect list of all fields used in the logic
		$fields = array();
		// Loop through fields used in the logic. Also, parse out any unique event names, if applicable
		foreach (array_keys(getBracketedFields($raw_logic, true, true, false)) as $this_field)
		{
			// Check if has dot (i.e. has event name included)
			if (strpos($this_field, ".") !== false) {
				list ($this_event_name, $this_field) = explode(".", $this_field, 2);
			}
			// Verify that the field really exists (may have been deleted). If not, stop here with an error.
			if (!isset($Proj->metadata[$this_field])) return false;
			// Add field to array
			$fields[] = $this_field;
		}
		// Get default values for all records (all fields get value '', except Form Status and checkbox fields get value 0)
		$default_values = array();
		foreach ($fields as $this_field)
		{
			// Loop through all designated events so that each event
			foreach (array_keys($Proj->eventInfo) as $this_event_id)
			{
				// Check a checkbox or Form Status field
				if ($Proj->metadata[$this_field]['element_type'] == 'checkbox') {
					// Loop through all choices and set each as 0
					foreach (array_keys(parseEnum($Proj->metadata[$this_field]['element_enum'])) as $choice) {
						$default_values[$this_event_id][$this_field][$choice] = '0';
					}
				} elseif ($this_field == $Proj->metadata[$this_field]['form_name'] . "_complete") {
					// Set as 0
					$default_values[$this_event_id][$this_field] = '0';
				} else {
					// Set as ''
					$default_values[$this_event_id][$this_field] = '';
				}
			}
		}
		// Query the values of the logic fields for ALL records
		$sql = "select record, event_id, field_name, value from redcap_data where project_id = " . PROJECT_ID 
			 . " and field_name in ('" . implode("', '", $fields) . "') and value != '' and record = '" . prep($record) . "'"
			 . " order by event_id";
		$q = db_query($sql);
		// Set intial values
		$event_id = 0;
		$record = "";
		$record_data = array();
		// Loop through data one record at a time
		while ($row = db_fetch_assoc($q))
		{
			// Add initial default data for first loop
			if ($event_id === 0 || $row['event_id'] !== $event_id) {
				$record_data[$row['event_id']] = $default_values[$row['event_id']];
			}
			// Decode the value
			$row['value'] = label_decode($row['value']);
			// Set values for this loop
			$event_id = $row['event_id'];
			$record   = $row['record'];	
			// Add the value into the array (double check to make sure the event_id still exists)
			if (isset($events[$event_id])) // && in_array($row['field_name'], $fields))
			{
				if ($Proj->metadata[$row['field_name']]['element_type'] == 'checkbox') {
					// Add checkbox value
					$record_data[$event_id][$row['field_name']][$row['value']] = 1;
				} else {
					// Non-checkbox value
					$record_data[$event_id][$row['field_name']] = $row['value'];
				}
			}
		}
		// Apply the logic and return the result (TRUE = all conditions are true)
		return self::apply($raw_logic, $record_data);
	}
	
	
	/**
	 * Runs the logic function and returns the *COMPLEMENT* of the result;
	 * sets a $_GET variable as a side effect to create an error message.
	 * @param string $funcName the name of the function to execute.
	 * @param array $recordData first key is the event name, second key is the
	 * field name, and third key is either the field value, or if the field is
	 * a checkbox, it will be an array of checkbox codes => values.
	 * @param string $currEventId the event ID of the current record being examined.
	 * @param array $rule_attr a description of the Data Quality rule.
	 * @param array $args used to inform the caller of the arguments that were
	 * actually used in the rule logic function.
	 * @param array $useArgs if given, this function will use these arguments
	 * instead of running $this->buildLogicArgs().
	 * @return 0 if the function returned false, 1 if the result is non-false, and
	 * false if an exception was thrown.
	 */
	static private function applyLogic($funcName, $argMap=array(), $recordData=array())
	{
		$args = array();
		try {
			if (!self::buildLogicArgs($argMap, $recordData, $args)) {
				throw new Exception("recordData does not contain the parameters we need");
			}
			$logicCheckResult = call_user_func_array($funcName, $args);
			return ($logicCheckResult === false ? 0 : 1);
		}
		catch (Exception $e) {
			return false;
		}
	}
	
	/**
	 * Builds the arguments to an anonymous function given record data.
	 * @param string $funcName the name of the function to build args for.
	 * @param array $recordData first key is the event name, second key is the
	 * field name, and third key is either the field value, or if the field is
	 * a checkbox, it will be an array of checkbox codes => values.
	 * @param array $args used to inform the caller of the arguments that were
	 * actually used in the rule logic function.
	 * @return boolean true if $recordData contained all data necessary to
	 * populate the function parameters, false if not.
	 */
	static private function buildLogicArgs($argMap=array(), $recordData=array(), &$args)
	{
		global $Proj;
		$isValid = true;
		try {
			$args = array();
			foreach ($argMap as $argData) 
			{
				// Get event_id, variable, and (if a checkbox) checkbox choice
				list ($eventVar, $projectVar, $cboxChoice) = $argData;
				// If missing the event_id, assume the first event_id in the project
				if (!is_numeric($eventVar)) $eventVar = $Proj->firstEventId;
				// Check event key
				if (!isset($recordData[$eventVar])) {
					throw new Exception("Missing event: $eventVar");
				}
				$projFields = $recordData[$eventVar];
				// Check field key
				if (!isset($projFields[$projectVar])) {
					throw new Exception("Missing project field: $projectVar");
				}
				// Set value, then validate it based on field type
				$value = $projFields[$projectVar];
				if ($cboxChoice === null && is_array($value) || $cboxChoice !== null && !is_array($value))
					throw new Exception("checkbox/value mismatch! $value " . print_r($value, true));
				if ($cboxChoice !== null && !isset($value[$cboxChoice]))
					throw new Exception("Missing checkbox choice: $cboxChoice");
				if ($cboxChoice !== null) {
					$value = $value[$cboxChoice];
				}
				// Add value to args array
				$args[] = $value;
			}
		}
		catch (Exception $e) {
			$isValid = false;
		}
		// Return if all arguments are valid and accounted for
		return $isValid;
	}
	
	/* 
	// When parsing DQ rules or branching logic (for missing values rule), replace ^ exponential form with PHP equivalent
	static public function replaceExponents($string) 
	{
		// Set max loops that we'll do = max nested parentheses
		$max_loops = 1000;
		//Find all ^ and locate outer parenthesis for its number and exponent
		$caret_pos = strpos($string, "^");
		// Make sure it has at least 2 left and right parentheses (otherwise not in correct format)
		$num_paren_left = substr_count($string, "(");
		$num_paren_right = substr_count($string, ")");
		// Loop through string
		$num_loops = 0;
		while ($caret_pos !== false && $num_paren_left >= 2 && $num_paren_right >= 2 && $num_loops < $max_loops) 
		{
			$num_loops++;
			//For first half of string
			$found_end = false;
			$rpar_count = 0;
			$lpar_count = 0;
			$i = $caret_pos;
			while ($i >= 0 && !$found_end) 
			{
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
			$string = substr($string, 0, $i). "pow(" . substr($string, $i);
			$caret_pos += 4; // length of "pow("
			
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
			$string = substr($string, 0, $caret_pos) . "," . substr($string, $caret_pos + 1, $i - $caret_pos) . ")" . substr($string, $i + 1);
			
			//Set again for checking in next loop
			$caret_pos = strpos($string, "^");
			
		}
		return $string;
	}
	*/
	
	
	/**
	 * For a general logic string, prepend all variables with a unique event name provided if the
	 * variable is not already prepended with a unique event name. 
	 * (Used to define an event explicityly before being evaluated for a record.)
	 */
	static public function logicPrependEventName($logic, $unique_event_name)
	{
		// First, prepend fields with unique event name
		$logic = preg_replace("/([^\]]\[)/", " [$unique_event_name][", " " . $logic);
		// Lastly, remove instances of double event names in logic
		$logic = preg_replace("/(\[)([^\[]*)(\]\[)([^\[]*)(\]\[)([^\[]*)(\])/", "[$4][$6]", $logic);
		// Return the formated logic
		return $logic;
	}
}