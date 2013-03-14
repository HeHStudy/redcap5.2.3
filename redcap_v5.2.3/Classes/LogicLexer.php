<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

/**
 * WARNING WARNING WARNING WARNING WARNING WARNING WARNING WARNING WARNING 
 * YOU MUST RUN `phpunit Test/DataQualityTest.php` AFTER YOU CHANGE *ANYTHING*
 * WARNING WARNING WARNING WARNING WARNING WARNING WARNING WARNING WARNING 
 * 
 * A lexer that converts branching logic or Data Quality rules
 * into tokens suitable for consumption by the LogicParser.
 * 
 * @see https://redcap.vanderbilt.edu/index.php?action=help#BranchingLogic
 * @see https://redcap.vanderbilt.edu/index.php?action=help#DataQuality
 */
class LogicLexer {
	
	/**#@+ Constants representing the different tokens. */
	const TOK_IDENT         = 1;
	const TOK_LEFT_BRACE    = 2; // [
	const TOK_RIGHT_BRACE   = 3; // ]
	const TOK_LEFT_PAREN    = 4;
	const TOK_RIGHT_PAREN   = 5;
	const TOK_SINGLE_QUOTE  = 6;
	const TOK_DOUBLE_QUOTE  = 7;
	const TOK_NUM           = 8;
	const TOK_COMMA         = 9;
	const TOK_PLUS          = 10;
	const TOK_MINUS         = 11;
	const TOK_MULTIPLY      = 12;
	const TOK_DIVIDE        = 13;
	const TOK_EQUAL         = 14;
	const TOK_WHITESPACE    = 15;
	const TOK_CARET         = 16;
	const TOK_AND           = 17;
	const TOK_OR            = 18;
	const TOK_NOT_EQUAL     = 19;
	const TOK_GT            = 20;
	const TOK_GTE           = 21;
	const TOK_LT            = 22;
	const TOK_LTE           = 23;
	const TOK_TRUE          = 24;
	const TOK_FALSE         = 25;
	const TOK_STRING        = 26;
	const TOK_NOT           = 27;
	const TOK_EVENT_VAR     = 28;
	const TOK_PROJ_VAR      = 29;
	const TOK_PROJ_CBOX     = 30;
	/**#@-*/
	
	/**
	 * Convert the given string into tokens usable by LogicParser.
	 * @param string $str the branching logic or Data Quality rule to tokenize.
	 * @return array the tokens (see self::createToken).
	 */
	public static function tokenize($str) {
		$tokens = array();
		// WARNING: be careful with the ordering of these if/elseif statements!
		for ($offset = 0; $offset < strlen($str);) {
			// deal with the [event][variable(cbox)] syntax
			if (preg_match('/^(?:\[([a-z0-9][_a-z0-9]*)\])?\[([a-z][_a-z0-9]*)(?:\(((-?\d+)|[a-z_A-Z0-9]+)\))?\]/', substr($str, $offset), $matches)) {
				$eventVar = $matches[1];
				$projVar = $matches[2];
				$cboxChoice = array_key_exists(3, $matches) ? $matches[3] : '';
				if (!empty($eventVar)) $tokens[] = self::createToken(self::TOK_EVENT_VAR, $eventVar);
				$tokens[] = self::createToken(self::TOK_PROJ_VAR, $projVar);
				if (strlen($cboxChoice)) $tokens[] = self::createToken(self::TOK_PROJ_CBOX, $cboxChoice);
				$offset += strlen($matches[0]);
			}
			// single-quoted string
			elseif (preg_match("/^'((\\\\')|[^'])*'/", substr($str, $offset), $matches)) {
				$value = $matches[0];
				$tokens[] = self::createToken(self::TOK_STRING, $value);
				$offset += strlen($value);
			}
			// double-quoted string
			elseif (preg_match('/^"((\\\\")|[^"])*"/', substr($str, $offset), $matches)) {
				$value = $matches[0];
				$tokens[] = self::createToken(self::TOK_STRING, $value);
				$offset += strlen($value);
			}
			elseif (preg_match('/^true([^_a-z0-9]|$)/i', substr($str, $offset), $matches)) {
				$tokens[] = self::createToken(self::TOK_TRUE, 'true');
				$offset += 4;
			}
			elseif (preg_match('/^false([^_a-z0-9]|$)/i', substr($str, $offset), $matches)) {
				$tokens[] = self::createToken(self::TOK_FALSE, 'false');
				$offset += 5;
			}
			elseif (preg_match('/^and[^_a-z0-9]/i', substr($str, $offset), $matches)) {
				$tokens[] = self::createToken(self::TOK_AND, '&&');
				$offset += 3;
			}
			// bitwise and logical AND -> logical AND operator
			elseif (preg_match('/^\&{1,2}/', substr($str, $offset), $matches)) {
				$value = $matches[0];
				$tokens[] = self::createToken(self::TOK_AND, '&&');
				$offset += strlen($value);
			}
			elseif (preg_match('/^or[^_a-z0-9]/i', substr($str, $offset), $matches)) {
				$tokens[] = self::createToken(self::TOK_OR, '||');
				$offset += 2;
			}
			// bitwise and logical OR -> logical OR operator
			elseif (preg_match('/^\|{1,2}/', substr($str, $offset), $matches)) {
				$value = $matches[0];
				$tokens[] = self::createToken(self::TOK_OR, '||');
				$offset += strlen($value);
			}			
			elseif (preg_match('/^not[^_a-z0-9]/i', substr($str, $offset), $matches)) {
				$tokens[] = self::createToken(self::TOK_NOT, '!');
				$offset += 3;
			}
			elseif (preg_match('/^[_a-z][_a-z0-9]*/i', substr($str, $offset), $matches)) {
				$value = $matches[0];
				$tokens[] = self::createToken(self::TOK_IDENT, $value);
				$offset += strlen($value);
			}
			elseif (preg_match('/^(\d*\.)?\d+/', substr($str, $offset), $matches)) {
				$value = $matches[0];
				$tokens[] = self::createToken(self::TOK_NUM, $value);
				$offset += strlen($value);
			}
			elseif (preg_match('/^\s+/', substr($str, $offset), $matches)) {
				$value = $matches[0];
				// the LogicParser doesn't use whitespace tokens so don't include them
				//$tokens[] = self::createToken(self::TOK_WHITESPACE, $value);
				$offset += strlen($value);
			}
			elseif (strpos($str, '!=', $offset) === $offset) {
				$tokens[] = self::createToken(self::TOK_NOT_EQUAL, '!=');
				$offset += 2;
			}
			elseif (strpos($str, '!', $offset) === $offset) {
				$tokens[] = self::createToken(self::TOK_NOT, '!');
				$offset += 1;
			}
			elseif (strpos($str, '<>', $offset) === $offset) {
				$tokens[] = self::createToken(self::TOK_NOT_EQUAL, '!=');
				$offset += 2;
			}
			elseif (preg_match('/^<=+/', substr($str, $offset), $matches)) {
				$value = $matches[0];
				$tokens[] = self::createToken(self::TOK_LTE, '<=');
				$offset += strlen($value);
			}
			elseif (strpos($str, '<', $offset) === $offset) {
				$tokens[] = self::createToken(self::TOK_LT, '<');
				$offset++;
			}
			elseif (preg_match('/^>=+/', substr($str, $offset), $matches)) {
				$value = $matches[0];
				$tokens[] = self::createToken(self::TOK_GTE, '>=');
				$offset += strlen($value);
			}
			elseif (strpos($str, '>', $offset) === $offset) {
				$tokens[] = self::createToken(self::TOK_GT, '>');
				$offset++;
			}
			// all contiguous '=' map to equality operator
			elseif (preg_match('/^=+/', substr($str, $offset), $matches)) {
				$value = $matches[0];
				$tokens[] = self::createToken(self::TOK_EQUAL, '==');
				$offset += strlen($value);
			}
			elseif (strpos($str, '[', $offset) === $offset) {
				$tokens[] = self::createToken(self::TOK_LEFT_BRACE, '[');
				$offset++;
			}
			elseif (strpos($str, ']', $offset) === $offset) {
				$tokens[] = self::createToken(self::TOK_RIGHT_BRACE, ']');
				$offset++;
			}
			elseif (strpos($str, '(', $offset) === $offset) {
				$tokens[] = self::createToken(self::TOK_LEFT_PAREN, '(');
				$offset++;
			}
			elseif (strpos($str, ')', $offset) === $offset) {
				$tokens[] = self::createToken(self::TOK_RIGHT_PAREN, ')');
				$offset++;
			}
			elseif (strpos($str, "'", $offset) === $offset) {
				$tokens[] = self::createToken(self::TOK_SINGLE_QUOTE, "'");
				$offset++;
			}
			elseif (strpos($str, '"', $offset) === $offset) {
				$tokens[] = self::createToken(self::TOK_DOUBLE_QUOTE, '"');
				$offset++;
			}
			elseif (strpos($str, ',', $offset) === $offset) {
				$tokens[] = self::createToken(self::TOK_COMMA, ',');
				$offset++;
			}
			elseif (strpos($str, '+', $offset) === $offset) {
				$tokens[] = self::createToken(self::TOK_PLUS, '+');
				$offset++;
			}
			elseif (strpos($str, '-', $offset) === $offset) {
				$tokens[] = self::createToken(self::TOK_MINUS, '-');
				$offset++;
			}
			elseif (strpos($str, '*', $offset) === $offset) {
				$tokens[] = self::createToken(self::TOK_MULTIPLY, '*');
				$offset++;
			}
			elseif (strpos($str, '/', $offset) === $offset) {
				$tokens[] = self::createToken(self::TOK_DIVIDE, '/');
				$offset++;
			}
			elseif (strpos($str, '^', $offset) === $offset) {
				$tokens[] = self::createToken(self::TOK_CARET, '^');
				$offset++;
			}
			else {
				throw new LogicException("Unable to find next token in: $str\nStopped here: " .
					substr($str, $offset));
			}
		}
		return $tokens;
	}
		
	/**
	 * Creates an object reprenting a token.
	 * @param int $type see self::TOK_*
	 * @param string $value the token itself.
	 * @return object the token object with member variables "type" and "value".
	 */
	private static function createToken($type, $value) {
		$tok = new stdClass();
		$tok->type = $type;
		$tok->value = $value;
		return $tok;
	}
}