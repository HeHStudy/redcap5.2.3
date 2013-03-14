<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

/**
 * WARNING WARNING WARNING WARNING WARNING WARNING WARNING WARNING WARNING 
 * YOU MUST RUN `phpunit Test/DataQualityTest.php` AFTER YOU CHANGE *ANYTHING*
 * WARNING WARNING WARNING WARNING WARNING WARNING WARNING WARNING WARNING 
 * 
 * A parser that converts tokens from LogicLexer into an anonymous PHP
 * function, along with a description of parameters to pass to said function.
 * The grammar to be parsed is as follows in EBNF-like notation:
 * 
 * NOTE1: the ordering of "|" terms is *EXTREMELY* important; they should
 * match the actual code.
 * 
 * NOTE2: at present this grammar is probably not sufficient to build a
 * compilier upon because operator precedence has not been considered.
 * However, for verifying program correctness should be fine.
 * 
 * identifier = TOK_IDENT
 * number = ["+" | "-"], TOK_NUM
 * boolean =
 *   TOK_TRUE |
 *   TOK_FALSE
 * string = TOK_STRING
 * unary_operator =
 *   "!"
 * infix_operator =
 *   "+" |
 *   "-" |
 *   "*" |
 *   "/" |
 *   "==" |
 *   "<" |
 *   ">" |
 *   "<=" |
 *   ">=" |
 *   "!=" |
 *   "&&" |
 *   "||"
 * user_variable = [TOK_EVENT_VAR,] TOK_PROJ_VAR [, (TOK_PROJ_CBOX)]
 * operand =
 *   user_variable |
 *   function |
 *   number |
 *   boolean |
 *   string
 * operation =
 *   [unary_operator,] "(", operation, ")", "^", "(", operation, ")" [, operation_chain] |
 *   [unary_operator,] "(", operation, ")" [, operation_chain] |
 *   [unary_operator,] operand, [, operation_chain] |
 * operation_chain = {infix_operator, operation}
 * function = ("allowedFunc1" | "allowedFunc2" | ...), "(" [operation, [{",", operation}]] ")"
 * program = operation
 * 
 * @see https://redcap.vanderbilt.edu/index.php?action=help#BranchingLogic
 * @see https://redcap.vanderbilt.edu/index.php?action=help#DataQuality
 */
class LogicParser {
	
	/** The tokens produced by LogicLexer. */
	private $tokens = array();
	
	/** Index of the next element in $tokens to be examined. */
	private $tokenIdx = 0;
	
	/** Stack used to track generated code snippets during parsing. */
	private $codeStack = array();
	
	/**
	 * Every user variable encountered by the parser is placed on the heap; they
	 * will stay there regardless of backtracking and get analyzed at the end.
	 */
	private $argHeap = array();
	
	/** The code that the parser ultimately generated. */
	public $generatedCode = null;
	
	/** An array of any illegal functions found in the code. */
	public $illegalFunctionsAttempted = array();
	
	/** The only functions that the user is allowed to specify. */
	public static $allowedFunctions = array(
			'if' => true,        // translate from if(X,A,B) -> X ? A : B
			'datediff' => true,  // custom REDCap function
			'round' => true,     // built-in PHP function
			'roundup' => true,   // custom REDCap function
			'rounddown' => true, // custom REDCap function
			'sqrt' => true,      // built-in PHP function
			'abs' => true,       // built-in PHP function
			'min' => true,       // built-in PHP function
			'max' => true,       // built-in PHP function
			'mean' => true,      // custom REDCap function
			'median' => true,    // custom REDCap function
			'sum' => true,       // custom REDCap function
			'stdev' => true,     // custom REDCap function
			'isnumber' => true,  // custom REDCap function
			'isinteger' => true // custom REDCap function
	);
	
	/**
	 * Builds an anonymous function based on user-defined code.
	 * @param string $str the user-defined Data Quality rule or branching logic.
	 * @param array $eventNameToId an optional array specifying the mapping of
	 * unique event names to event IDs. If given, the output will reference
	 * event IDs instead of unqiue event names.
	 * @param boolean $createFunction Flag that determines if function should be created.
	 * Set to false if merely testing if logic string is valid.
	 * @return array two elements:
	 * [0] = the name of the anonymous function that runs the user's code.
	 * WARNING: although the name is a valid string, attempting to print it will
	 * result in a blank line because the first character in the name is set
	 * to something "weird".
	 * [1] = an array of argument mappings where the key is the argument index
	 * (e.g., 0 is the first argument) and the values are arrays like this:
	 *     [0] = unqiue event name, or NULL if referencing whatever event is
	 *           being currently iterated over. If $eventNameToId is given, then
	 *           this will be the event ID instead of the unqiue event name.
	 *     [1] = the variable/field name.
	 *     [2] = the checkbox code (or NULL if the argument is not a checkbox)
	 * @throws LogicException on any error.
	 */
	public function parse($str, $eventNameToId=null, $createFunction=true) {
		$this->tokens = LogicLexer::tokenize($str);
		$this->tokenIdx = 0;
		$this->codeStack = array();
		$this->argHeap = array();
		$this->generatedCode = null;
		$this->illegalFunctionsAttempted = array();
		if ($this->consume_program() === null) {
			throw new LogicException("Parse error in input: $str");
		}
		elseif ($this->tokenIdx < count($this->tokens)) {
			throw new LogicException("Parser did not consume all tokens! tokenIdx: $this->tokenIdx, tokens: " .
				print_r($this->tokens, true));
		}
		elseif (count($this->illegalFunctionsAttempted) > 0) {
			throw new LogicException("Parse error due to illegal functions: " .
				implode(', ', $this->illegalFunctionsAttempted));
		}
		// in theory the user is always specifying a single, boolean expression -
		// though we are currently not restricting programs to boolean returns
		if (count($this->codeStack) === 1) {
			// Don't go and create the anonymous function if the $createFunction 
			// flag has been manually set to false.
			if ($createFunction) {
				$code = array_pop($this->codeStack);
				// determine the arguments that actually made it in the code
				$argMap = array(); $argList = array();
				preg_match_all('/\$arg(\d+)/', $code, $matches);
				foreach ($matches[0] as $idx => $arg) {
					$argList[] = $arg;
					$argHeapIdx = $matches[1][$idx];
					$argData = $this->argHeap[$argHeapIdx];
					// use event IDs instead of unique event names
					if (is_array($eventNameToId) && $argData[0] !== null) {
						$eventName = $argData[0];
						if (!array_key_exists($eventName, $eventNameToId)) {
							throw new LogicException("Cannot find ID of this event name: $eventName");
						}
						$argData[0] = $eventNameToId[$eventName];
					}
					$argMap[] = $argData;
				}
				$code = ' return (' . $code . ');';
				$this->generatedCode = $code;
				return array(create_function(implode(',', $argList), $code), $argMap);
			} else {
				return true;
			}
		}
		else {
			throw new LogicException("Invalid generated code: " . print_r($this->codeStack, true));
		}
	}
	
	private function nextToken() {
		return $this->tokenIdx >= count($this->tokens) ?
			null : $this->tokens[$this->tokenIdx++];
	}
	
	private function prevToken() {
		return $this->tokenIdx === 0 ?
			null : $this->tokens[--$this->tokenIdx];
	}
	
	private function consume_tokens($num) {
		$tokens = array();
		for ($i = 0; $i < $num; $i++) {
			$tok = $this->nextToken();
			// not enough tokens
			if ($tok === null) {
				$this->replace_tokens($tokens); // put back the ones we consumed
				return null;
			}
			$tokens[] = $tok;
		}
		return $tokens;
	}
	
	/**
	 * Replaces tokens that have been consumed (effectively rewinds the token pointer).
	 * @param array $tokens the tokens to replace.
	 * @param boolean $popCodeStack true if the tokens being replaced represent
	 * code that was placed on $this->codeStack.
	 */
	private function replace_tokens($tokens, $popCodeStack=false) {
		for ($i = count($tokens)-1; $i >= 0; $i--) {
			$tok = $this->prevToken();
			if ($tok !== $tokens[$i]) {
				throw new LogicException("Out of order token replacement! Trying to put back: " .
					print_r($tokens[$i], true) . "Should be putting back: " . print_r($tok, true));
			}
		}
		if ($popCodeStack) array_pop($this->codeStack);
	}
	
	private function consume_enclosed($consumptionFunction) {
		$leftTokens = $this->consume_tokens(1);
		if ($leftTokens === null) {
			return null;
		}
		elseif ($leftTokens[0]->type !== LogicLexer::TOK_LEFT_PAREN) {
			$this->replace_tokens($leftTokens);
			return null;
		}
		else {
			$funcTokens = $this->{$consumptionFunction}();
			if ($funcTokens === null) {
				$this->replace_tokens($leftTokens);
				return null;
			}
			else {
				$rightTokens = $this->consume_tokens(1);
				if ($rightTokens === null) {
					$this->replace_tokens($funcTokens, true);
					$this->replace_tokens($leftTokens);
					return null;
				}
				elseif ($rightTokens[0]->type !== LogicLexer::TOK_RIGHT_PAREN) {
					$this->replace_tokens($rightTokens);
					$this->replace_tokens($funcTokens, true);
					$this->replace_tokens($leftTokens);
					return null;
				}
				else {
					$funcCode = array_pop($this->codeStack);
					array_push($this->codeStack, '(' . $funcCode . ')');
					return array_merge($leftTokens, $funcTokens, $rightTokens);
				}
			}
		}
	}
	
	/** Print out some debug info. */
	private function debug() {
		echo "TOKENS:\n";
		print_r($this->tokens);
		echo "TOKEN INDEX: $this->tokenIdx\n";
		echo "CODE STACK:\n";
		print_r($this->codeStack);
	}
	
	private function consume_number() {
		$tokens = $this->consume_tokens(1);
		if ($tokens === null) return null;
		if ($tokens[0]->type === LogicLexer::TOK_PLUS ||
				$tokens[0]->type === LogicLexer::TOK_MINUS)
		{
			$numTokens = $this->consume_tokens(1);
			if ($numTokens === null) {
				$this->replace_tokens($tokens);
				return null;
			}
			elseif ($numTokens[0]->type !== LogicLexer::TOK_NUM) {
				$this->replace_tokens(array_merge($tokens, $numTokens));
				return null;
			}
			else {
				array_push($this->codeStack, $tokens[0]->value . $numTokens[0]->value);
				return array_merge($tokens, $numTokens);
			}
		}
		elseif ($tokens[0]->type === LogicLexer::TOK_NUM) {
			array_push($this->codeStack, $tokens[0]->value);
			return $tokens;
		}
		else {
			$this->replace_tokens($tokens);
			return null;
		}
	}
	
	private function consume_boolean() {
		$tokens = $this->consume_tokens(1);
		if ($tokens === null) {
			return null;
		}
		elseif ($tokens[0]->type !== LogicLexer::TOK_TRUE &&
						$tokens[0]->type !== LogicLexer::TOK_FALSE)
		{
			$this->replace_tokens($tokens);
			return null;
		}
		else {
			array_push($this->codeStack, $tokens[0]->value);
			return $tokens;
		}
	}
	
	private function consume_string() {
		$tokens = $this->consume_tokens(1);
		if ($tokens === null) {
			return null;
		}
		elseif ($tokens[0]->type !== LogicLexer::TOK_STRING)
		{
			$this->replace_tokens($tokens);
			return null;
		}
		else {
			array_push($this->codeStack, $tokens[0]->value);
			return $tokens;
		}
	}
	
	private function consume_unary_operator() {
		$tokens = $this->consume_tokens(1);
		if ($tokens === null) {
			return null;
		}
		elseif ($tokens[0]->type !== LogicLexer::TOK_NOT)
		{
			$this->replace_tokens($tokens);
			return null;
		}
		else {
			array_push($this->codeStack, $tokens[0]->value);
			return $tokens;
		}
	}
	
	private function consume_infix_operator() {
		$tokens = $this->consume_tokens(1);
		if ($tokens === null) return null;
		if ($tokens[0]->type === LogicLexer::TOK_PLUS ||
			  $tokens[0]->type === LogicLexer::TOK_MINUS ||
				$tokens[0]->type === LogicLexer::TOK_MULTIPLY ||
				$tokens[0]->type === LogicLexer::TOK_DIVIDE ||
				$tokens[0]->type === LogicLexer::TOK_EQUAL ||
				$tokens[0]->type === LogicLexer::TOK_CARET ||
				$tokens[0]->type === LogicLexer::TOK_LT ||
				$tokens[0]->type === LogicLexer::TOK_GT ||
				$tokens[0]->type === LogicLexer::TOK_LTE ||
				$tokens[0]->type === LogicLexer::TOK_GTE ||
				$tokens[0]->type === LogicLexer::TOK_NOT_EQUAL ||
				$tokens[0]->type === LogicLexer::TOK_NOT ||
				$tokens[0]->type === LogicLexer::TOK_AND ||
				$tokens[0]->type === LogicLexer::TOK_OR)
		{
			array_push($this->codeStack, $tokens[0]->value);
			return $tokens;
		}
		else {
			$this->replace_tokens($tokens);
			return null;
		}
	}
	
	private function consume_user_variable() {
		$argData = array();
		// look for the optional event variable
		$eventTokens = $this->consume_tokens(1);
		if ($eventTokens === null) {
			$eventTokens = array();
			$argData[] = null;
		}
		elseif ($eventTokens[0]->type !== LogicLexer::TOK_EVENT_VAR) {
			$this->replace_tokens($eventTokens);
			$eventTokens = array();
			$argData[] = null;
		}
		else {
			$argData[] = $eventTokens[0]->value;
		}
		// look for the mandatory project variable
		$projTokens = $this->consume_tokens(1);
		if ($projTokens === null) {
			if (count($eventTokens)) $this->replace_tokens($eventTokens);
			return null;
		}
		elseif ($projTokens[0]->type !== LogicLexer::TOK_PROJ_VAR) {
			$this->replace_tokens(array_merge($eventTokens, $projTokens));
			return null;
		}
		else {
			$argData[] = $projTokens[0]->value;
			// look for the optional checkbox choice
			$cboxTokens = $this->consume_tokens(1);
			if ($cboxTokens === null) {
				$cboxTokens = array();
				$argData[] = null;
			}
			elseif ($cboxTokens[0]->type !== LogicLexer::TOK_PROJ_CBOX) {
				$this->replace_tokens($cboxTokens);
				$cboxTokens = array();
				$argData[] = null;
			}
			else {
				$argData[] = $cboxTokens[0]->value;
			}
			$arg = '$arg' . count($this->argHeap);
			array_push($this->argHeap, $argData);
			array_push($this->codeStack, "($arg)");
			return array_merge($eventTokens, $projTokens, $cboxTokens);
		}
	}
		
	private function consume_operand() {
		$tokens = $this->consume_user_variable();
		if ($tokens !== null) return $tokens;
		$tokens = $this->consume_function();
		if ($tokens !== null) return $tokens;
		$tokens = $this->consume_number();
		if ($tokens !== null) return $tokens;
		$tokens = $this->consume_boolean();
		if ($tokens !== null) return $tokens;
		$tokens = $this->consume_string();
		if ($tokens !== null) return $tokens;
		return null;
	}
	
	private function consume_operation() {
		// all operations can start with a unary
		$unaryTokens = $this->consume_unary_operator();
		$unaryCode = '';
		if ($unaryTokens === null) { $unaryTokens = array(); }
		else { $unaryCode = array_pop($this->codeStack); }
		// RULE: "(", operation, ")", "^", "(", operation, ")" [, operation_chain]
		$opTokens1 = $this->consume_enclosed('consume_operation');
		if ($opTokens1 !== null) {
			$infixTokens = $this->consume_infix_operator();
			if ($infixTokens !== null && $infixTokens[0]->type !== LogicLexer::TOK_CARET) {
				$this->replace_tokens($infixTokens, true);
				$this->replace_tokens($opTokens1, true);
			}
			elseif ($infixTokens !== null) {
				$opTokens2 = $this->consume_enclosed('consume_operation');
				if ($opTokens2 !== null) {
					$opCode2 = array_pop($this->codeStack);
					$infixCode = array_pop($this->codeStack);
					$opCode1 = array_pop($this->codeStack);
					array_push($this->codeStack, $unaryCode . 'pow((' . $opCode1 . '),(' . $opCode2 . '))');
					$chainTokens = $this->consume_operation_chain();
					if ($chainTokens !== null) {
						$chainCode = array_pop($this->codeStack);
						$powCode = array_pop($this->codeStack);
						array_push($this->codeStack, $powCode . $chainCode);
						return array_merge($unaryTokens, $opTokens1, $infixTokens, $opTokens2, $chainTokens);
					}
					else {
						return array_merge($unaryTokens, $opTokens1, $infixTokens, $opTokens2);
					}
				}
				else {
					$this->replace_tokens($infixTokens, true);
					$this->replace_tokens($opTokens1, true);
				}
			}
			else {
				$this->replace_tokens($opTokens1, true);
			}
		}		
		// RULE: "(", operation, ")" [, operation_chain]
		$opTokens = $this->consume_enclosed('consume_operation');
		if ($opTokens !== null) {
			$chainTokens = $this->consume_operation_chain();
			if ($chainTokens !== null) {
				$chainCode = array_pop($this->codeStack);
				$opCode = array_pop($this->codeStack);
				array_push($this->codeStack, $unaryCode . $opCode . $chainCode);
				return array_merge($unaryTokens, $opTokens, $chainTokens);
			}
			else {
				$opCode = array_pop($this->codeStack);
				array_push($this->codeStack, $unaryCode . $opCode);
				return array_merge($unaryTokens, $opTokens);
			}
		}
		// RULE: operand [, operation_chain]
		$opTokens = $this->consume_operand();
		if ($opTokens !== null) {
			$chainTokens = $this->consume_operation_chain();
			if ($chainTokens !== null) {
				$chainCode = array_pop($this->codeStack);
				$opCode = array_pop($this->codeStack);
				array_push($this->codeStack, $unaryCode . $opCode . $chainCode);
				return array_merge($unaryTokens, $opTokens, $chainTokens);
			}
			else {
				$opCode = array_pop($this->codeStack);
				array_push($this->codeStack, $unaryCode . $opCode);
				return array_merge($unaryTokens, $opTokens);
			}
		}
		// make sure to put back any unary tokens if no rules passed
		if (count($unaryTokens)) $this->replace_tokens($unaryTokens);
		return null;
	}
	
	private function consume_operation_chain() {
		$tokens = array();
		$code = '';
		while (true) {
			$infixTokens = $this->consume_infix_operator();
			if ($infixTokens === null) {
				break;
			}
			else {
				$opTokens = $this->consume_operation();
				if ($opTokens === null) {
					$this->replace_tokens($infixTokens, true);
					break;
				}
				else {
					$opCode = array_pop($this->codeStack);
					$infixCode = array_pop($this->codeStack);
					$code .= $infixCode . $opCode;
					$tokens = array_merge($tokens, $infixTokens, $opTokens);
				}
			}
		}
		if (strlen($code)) array_push($this->codeStack, $code);
		return count($tokens) ? $tokens : null;
	}
	
	private function consume_function() {
		$tokens = $this->consume_tokens(2);
		if ($tokens === null) return null;
		if ($tokens[0]->type === LogicLexer::TOK_IDENT &&
				$tokens[1]->type === LogicLexer::TOK_LEFT_PAREN)
		{
			$arguments = array();
			// RULE: [operation, [{",", operation}]]
			$argTokens = $this->consume_operation();
			if ($argTokens !== null) {
				$arguments[] = $argTokens; // keep track of individual arguments
				$tokens = array_merge($tokens, $argTokens);
				// consume additional arguments
				while (true) {
					$argSepTokens = $this->consume_tokens(1);
					if ($argSepTokens === null) {
						break;
					}
					elseif ($argSepTokens[0]->type !== LogicLexer::TOK_COMMA) {
						$this->replace_tokens($argSepTokens);
						break;
					}
					else {
						$nextArgTokens = $this->consume_operation();
						if ($nextArgTokens === null) {
							$this->replace_tokens($argSepTokens);
							break;
						}
						else {
							$arguments[] = $nextArgTokens; // keep track of individual arguments
							$tokens = array_merge($tokens, $argSepTokens, $nextArgTokens);
						}
					}
				}
			}
			$rightTokens = $this->consume_tokens(1);
			if ($rightTokens === null) {
				foreach ($arguments as $a) array_pop($this->codeStack); // retract any arguments
				$this->replace_tokens($tokens);
				return null;
			}
			elseif ($rightTokens[0]->type !== LogicLexer::TOK_RIGHT_PAREN) {
				foreach ($arguments as $a) array_pop($this->codeStack); // retract any arguments
				$this->replace_tokens(array_merge($tokens, $rightTokens));
				return null;
			}
			else {
				$codeArgs = array();
				foreach ($arguments as $a) $codeArgs[] = array_pop($this->codeStack);
				$codeArgs = array_reverse($codeArgs); // since we popped them in FILO order
				$functionName = strtolower($tokens[0]->value);
				if ($functionName === "if") { // special function converted to the ternary operator
					if (count($codeArgs) !== 3) {
						throw new LogicException("Bad arguments for IF-statement!");
					}
					array_push($this->codeStack, '((' . $codeArgs[0] . ')?(' . $codeArgs[1] . '):(' . $codeArgs[2] . '))');
				}
				else {
					array_push($this->codeStack, $functionName . '(' . implode(',', $codeArgs) . ')');
					// track illegal functions attempted (we'll fail when parsing is complete)
					if (!array_key_exists(strtolower($functionName), self::$allowedFunctions))
						$this->illegalFunctionsAttempted[] = $functionName;
				}
				return array_merge($tokens, $rightTokens);
			}
		}
		else {
			$this->replace_tokens($tokens);
			return null;
		}
	}
	
	private function consume_program() {
		return $this->consume_operation();
	}
}