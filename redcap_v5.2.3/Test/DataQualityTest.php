<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once("../Classes/LogicException.php");
require_once("../Classes/LogicLexer.php");
require_once("../Classes/LogicParser.php");
require_once("../ProjectGeneral/math_functions.php");

/**
 * Unit tests for the Data Quality module. 
 */
class DataQualityTest extends PHPUnit_Framework_TestCase {
	
	// lex, parse, and run the given code, returning the output
	public function interpretCode($code, $eventProj=array(), $currEvent=null, $eventNameToId=null) {
		$parser = new LogicParser();
		list($functionName, $argMap) = $parser->parse($code, $eventNameToId);
		$args = array();
		foreach ($argMap as $argData) {
			list($eventVar, $projectVar, $cboxChoice) = $argData;
			if ($eventVar === null) $eventVar = $currEvent;
			if (!array_key_exists($eventVar, $eventProj))
				throw new Exception("Missing event: $eventVar");
			$projFields = $eventProj[$eventVar];
			if (!array_key_exists($projectVar, $projFields))
				throw new Exception("Missing project field: $projectVar");
			$value = $projFields[$projectVar];
			if ($cboxChoice === null && is_array($value) ||
					$cboxChoice !== null && !is_array($value))
				throw new Exception("checkbox/value mismatch! $value " . print_r($value, true));
			if ($cboxChoice !== null && !array_key_exists($cboxChoice, $value))
				throw new Exception("Missing checkbox choice: $cboxChoice");
			if ($cboxChoice !== null) $value = $value[$cboxChoice];
			$args[] = $value;
		}
		
		echo $parser->generatedCode . "\n";
		return call_user_func_array($functionName, $args);
	}
	
	/****************************************************************************
	 * Test some basic boolean expressions
	 ***************************************************************************/
	public function testBool1() {
		$code = '1 = 1';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	public function testBool2() {
		$code = '1 > 1';
		$this->assertEquals(false, $this->interpretCode($code));
	}
	public function testBool3() {
		$code = '1 == 2';
		$this->assertEquals(false, $this->interpretCode($code));
	}
	public function testBool4() {
		$code = '1<=2';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	public function testBool5() {
		$code = '(10.1>10 ) && (   1=2)';
		$this->assertEquals(false, $this->interpretCode($code));
	}
	public function testBool6() {
		$code = '(10.1>10 ) && (   5=5)';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	public function testBool7() {
		$code = '(10.1>10 ) || (   1=5  )  ';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	public function testBool8() {
		$code = '(  10.1 >10 ) ||(   5=5)';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	public function testBool9() {
		$code = '(  10.1 <=10 ) ||(   5!=5)';
		$this->assertEquals(false, $this->interpretCode($code));
	}
	public function testBool10() {
		$code = '(  10.1 <=10 ) ||(   5==5)';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	public function testBool11() {
		$code = '1||2&&3';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	public function testBool12() {
		$code = '0||0&&0';
		$this->assertEquals(false, $this->interpretCode($code));
	}
	public function testBool13() {
		$code = 'true=false';
		$this->assertEquals(false, $this->interpretCode($code));
	}
	public function testBool14() {
		$code = '!true';
		$this->assertEquals(false, $this->interpretCode($code));
	}
	public function testBool15() {
		$code = '!false';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	public function testBool16() {
		$code = 'not true';
		$this->assertEquals(false, $this->interpretCode($code));
	}
	public function testBool17() {
		$code = 'not false';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	
	/****************************************************************************
	 * Test some math/boolean expressions
	 ***************************************************************************/
	public function testMath1() {
		$code = '1 + 1 >= 1';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	public function testMath2() {
		$code = '1 + 1 < 1';
		$this->assertEquals(false, $this->interpretCode($code));
	}
	public function testMath3() {
		$code = '42 = 6 * (7)';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	public function testMath4() {
		$code = '9*9 > (81 + (18-17))';
		$this->assertEquals(false, $this->interpretCode($code));
	}
	public function testMath5() {
		$code = '(18.88 > (18.87))';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	public function testMath6() {
		$code = '(18.88 > (18.87)) || 8/2 > 4';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	public function testMath7() {
		$code = '(18.88 < (18.87)) || 8/2 > 4';
		$this->assertEquals(false, $this->interpretCode($code));
	}
	public function testMath8() {
		$code = '((18.88 < (18.87)) || 8/2 > 4) || 2*3=6';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	public function testMath9() {
		$code = '20 - 6 != 20 and 3 != 4';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	public function testMath10() {
		$code = '5*1/1>6 or 6*1>5/1';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	
	/****************************************************************************
	 * Test the allowed functions
	 ***************************************************************************/
	public function testFunc1() {
		$code = 'datediff("2007-05-31", "2008-05-31", "y", false) >=1';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	public function testFunc2() {
		$code = '3>2 && datediff("2008-05-31", "2008-05-29", "d", true) > -1';
		$this->assertEquals(false, $this->interpretCode($code));
	}
	public function testFunc3() {
		$code = '(round(8.5) = 9) || round(15.15, 1) == "whatever"';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	public function testFunc4() {
		$code = 'true = (roundup(18.947,2) >= 18.94)';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	public function testFunc5() {
		$code = 'rounddown(18.947,2) > 18.95';
		$this->assertEquals(false, $this->interpretCode($code));
	}
	public function testFunc6() {
		$code = 'true=true&&(sqrt(4*4)<5)';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	public function testFunc7() {
		$code = 'abs(-80)!=abs(80)';
		$this->assertEquals(false, $this->interpretCode($code));
	}
	public function testFunc8() {
		$code = 'min(-80,sqrt(80), abs(-80), -1*81) < -80';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	public function testFunc9() {
		$code = 'max(abs(-5), sqrt(16), roundup(5.25, 1), -3*489.239) < 5.2';
		$this->assertEquals(false, $this->interpretCode($code));
	}
	public function testFunc10() {
		$code = '8=mean(7,8,9)';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	public function testFunc11() {
		$code = 'median(1,2,3,4,5,8,8,8,8,8,8,8,8,8,8,8,8,8)<=8';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	public function testFunc12() {
		$code = 'sum(abs(-1*sqrt(5*5*5/5)), 1, 1) = 7';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	public function testFunc13() {
		$code = 'stdev(3,4,5)<2';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	public function testFunc14() {
		$code = 'isnumber("gogators") || isnumber(898.34)';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	public function testFunc15() {
		$code = 'isinteger(4)&&isinteger(69)&&isinteger(rounddown(15.45,1))';
		$this->assertEquals(false, $this->interpretCode($code));
	}
	
	/****************************************************************************
	 * Test "converted" functions
	 ***************************************************************************/
	public function testConvert1() {
		$code = 'if  (1=1   , true  , false   )';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	public function testConvert2() {
		$code = 'if(isnumber("andrea"),true,false)';
		$this->assertEquals(false, $this->interpretCode($code));
	}
	public function testConvert3() {
		$code = 'if((sqrt(81)>9), "\\"air quotes\\"", max(2*3,mean(6,7,8))) > 6';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	public function testConvert4() {
		$code = "(5>6) or if(true&&true&&false,'\\'air\nquotes\\'',isinteger(4.4)) or false";
		$this->assertEquals(false, $this->interpretCode($code));
	}
	public function testConvert5() {
		$code = '(2)^(3) > 8';
		$this->assertEquals(false, $this->interpretCode($code));
	}
	public function testConvert6() {
		$code = '(round(2.2))^(9/3) >= 8';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	public function testConvert7() {
		$code = 'if((2)^(6) === 64, 4, 2) > 3 && true=true';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	
	/****************************************************************************
	 * Test user variables: [event_variable][project_variable(chox_choice)]
	 ***************************************************************************/
	public function testVar1() {
		$code = '[dps] = 128';
		$eventProj = array('event1_battle1' => array(
				'dps' => 128,
				'cold_resist' => 10,
				'fire_resist' => 24
		));
		$this->assertEquals(true, $this->interpretCode($code, $eventProj, 'event1_battle1'));
	}
	public function testVar2() {
		$code = '[dps] < 128';
		$eventProj = array('event1_battle1' => array(
				'dps' => 128,
				'cold_resist' => 10,
				'fire_resist' => 24
		));
		$this->assertEquals(false, $this->interpretCode($code, $eventProj, 'event1_battle1'));
	}
	public function testVar3() {
		$code = '[dps] = 128 && ([cold_resist]>20 ||[fire_resist]>20) && [dps] > 0';
		$eventProj = array('event1_battle1' => array(
				'dps' => 128,
				'cold_resist' => 10,
				'fire_resist' => 24
		));
		$this->assertEquals(true, $this->interpretCode($code, $eventProj, 'event1_battle1'));
	}
	public function testVar4() {
		$code = '[status_effects(3)] == "1" or [status_effects(99)] == "1"';
		$eventProj = array('event1_battle1' => array(
				'dps' => 128,
				'cold_resist' => 10,
				'fire_resist' => 24,
				'status_effects' => array(1 => 1, 3 => 0, 5=> 1, 99 => 0)
		));
		$this->assertEquals(false, $this->interpretCode($code, $eventProj, 'event1_battle1'));
	}
	public function testVar5() {
		$code = '[status_effects(3)] == "1" or [status_effects(99)] == "0"';
		$eventProj = array('event1_battle1' => array(
				'dps' => 128,
				'cold_resist' => 10,
				'fire_resist' => 24,
				'status_effects' => array(1 => 1, 3 => 0, 5=> 1, 99 => 0)
		));
		$this->assertEquals(true, $this->interpretCode($code, $eventProj, 'event1_battle1'));
	}
	public function testVar6() {
		$code = 'if([status_effects(99)], 3.14, max([dps]-[cold_resist],[dps]-[fire_resist])) >= 118';
		$eventProj = array('event1_battle1' => array(
				'dps' => 128,
				'cold_resist' => 10,
				'fire_resist' => 24,
				'status_effects' => array(1 => 1, 3 => 0, 5=> 1, 99 => 0)
		));
		$this->assertEquals(true, $this->interpretCode($code, $eventProj, 'event1_battle1'));
	}
	public function testVar7() {
		$code = 'if([status_effects(99)], 3.14, max([dps]-[cold_resist],[dps]-[fire_resist])) > 118 && [status_effects(1)] = "1"';
		$eventProj = array('event1_battle1' => array(
				'dps' => 128,
				'cold_resist' => 10,
				'fire_resist' => 24,
				'status_effects' => array(1 => 1, 3 => 0, 5=> 1, 99 => 0)
		));
		$this->assertEquals(false, $this->interpretCode($code, $eventProj, 'event1_battle1'));
	}
	public function testVar8() {
		$code = '[dps]<[event1_battle2][dps]';
		$eventProj =
			array('event1_battle1' => array(
				'dps' => 128,
				'cold_resist' => 10,
				'fire_resist' => 24,
				'status_effects' => array(1 => 1, 3 => 0, 5=> 1, 99 => 0)
			),
			'event1_battle2' => array(
				'dps' => 64,
				'cold_resist' => 5,
				'fire_resist' => 12,
				'status_effects' => array(1 => 0, 3 => 1, 5=> 0, 99 => 1)
			),
			'event2_battle1' => array(
				'dps' => 32,
				'cold_resist' => 20,
				'fire_resist' => 48,
				'status_effects' => array(1 => 1, 3 => 1, 5=> 0, 99 => 0)
			)
		);
		$this->assertEquals(true, $this->interpretCode($code, $eventProj, 'event2_battle1'));
	}
	public function testVar9() {
		$code = '[dps]*2+[event1_battle2][dps]!=[event1_battle1][dps]';
		$eventProj =
			array('event1_battle1' => array(
				'dps' => 128,
				'cold_resist' => 10,
				'fire_resist' => 24,
				'status_effects' => array(1 => 1, 3 => 0, 5=> 1, 99 => 0)
			),
			'event1_battle2' => array(
				'dps' => 64,
				'cold_resist' => 5,
				'fire_resist' => 12,
				'status_effects' => array(1 => 0, 3 => 1, 5=> 0, 99 => 1)
			),
			'event2_battle1' => array(
				'dps' => 32,
				'cold_resist' => 20,
				'fire_resist' => 48,
				'status_effects' => array(1 => 1, 3 => 1, 5=> 0, 99 => 0)
			)
		);
		$this->assertEquals(false, $this->interpretCode($code, $eventProj, 'event2_battle1'));
	}
	public function testVar10() {
		$code = 'max([event2_battle1][cold_resist], [event1_battle2][cold_resist], [event1_battle1][cold_resist]) = 20 && (min([event1_battle1][fire_resist],[event2_battle1][fire_resist],[event1_battle2][fire_resist]))=12';
		$eventProj =
			array('event1_battle1' => array(
				'dps' => 128,
				'cold_resist' => 10,
				'fire_resist' => 24,
				'status_effects' => array(1 => 1, 3 => 0, 5=> 1, 99 => 0)
			),
			'event1_battle2' => array(
				'dps' => 64,
				'cold_resist' => 5,
				'fire_resist' => 12,
				'status_effects' => array(1 => 0, 3 => 1, 5=> 0, 99 => 1)
			),
			'event2_battle1' => array(
				'dps' => 32,
				'cold_resist' => 20,
				'fire_resist' => 48,
				'status_effects' => array(1 => 1, 3 => 1, 5=> 0, 99 => 0)
			)
		);
		$this->assertEquals(true, $this->interpretCode($code, $eventProj, 'event2_battle1'));
	}
	public function testVar11() {
		$code = 'true=false || max(if([status_effects(5)],1000000,2*[dps]),
			if([event1_battle1][status_effects(1)],[event1_battle1][dps], 1000001),
			if([event2_battle1][status_effects(3)],[event2_battle1][dps]*4+1, 1000002))
				= 129';
		$eventProj =
			array('event1_battle1' => array(
				'dps' => 128,
				'cold_resist' => 10,
				'fire_resist' => 24,
				'status_effects' => array(1 => 1, 3 => 0, 5=> 1, 99 => 0)
			),
			'event1_battle2' => array(
				'dps' => 64,
				'cold_resist' => 5,
				'fire_resist' => 12,
				'status_effects' => array(1 => 0, 3 => 1, 5=> 0, 99 => 1)
			),
			'event2_battle1' => array(
				'dps' => 32,
				'cold_resist' => 20,
				'fire_resist' => 48,
				'status_effects' => array(1 => 1, 3 => 1, 5=> 0, 99 => 0)
			)
		);
		$this->assertEquals(true, $this->interpretCode($code, $eventProj, 'event1_battle2'));
	}
	
	/****************************************************************************
	 * Test the examples listed in the REDCap help docs
	 ***************************************************************************/
	public function testExample1() {
		$code = '[sex] = "0"';
		$eventProj = array('event1' => array(
				'sex' => 1
		));
		$this->assertEquals(false, $this->interpretCode($code, $eventProj, 'event1'));
	}
	public function testExample2() {
		$code = '[sex] = "0" and [given_birth] = "1"';
		$eventProj = array('event1' => array(
				'sex' => 0,
				'given_birth' => 1
		));
		$this->assertEquals(true, $this->interpretCode($code, $eventProj, 'event1'));
	}
	public function testExample3() {
		$code = '([height] >= 170 or [weight] < 65) and [sex] = "1"';
		$eventProj = array('event1' => array(
				'sex' => 1,
				'height' => 169,
				'weight' => 64
		));
		$this->assertEquals(true, $this->interpretCode($code, $eventProj, 'event1'));
	}
	public function testExample4() {
		$code = '[last_name] <> ""';
		$eventProj = array('event1' => array(
				'last_name' => ""
		));
		$this->assertEquals(false, $this->interpretCode($code, $eventProj, 'event1'));
	}
	public function testExample5() {
		$code = '[race(2)] = "1"';
		$eventProj = array('event1' => array(
				'race' => array(1 => 0, 2 => 0, 3 => 0)
		));
		$this->assertEquals(false, $this->interpretCode($code, $eventProj, 'event1'));
	}
	public function testExample6() {
		$code = '[race(4)] = "0"';
		$eventProj = array('event1' => array(
				'race' => array(1 => 1, 2 => 1, 3 => 1, 4 => 0)
		));
		$this->assertEquals(true, $this->interpretCode($code, $eventProj, 'event1'));
	}
	public function testExample7() {
		$code = '[height] >= 170 and ([race(2)] = "1" or [race(4)] = "1")';
		$eventProj = array('event1' => array(
				'height' => 170,
				'race' => array(1 => 1, 2 => 1, 3 => 1, 4 => 0)
		));
		$this->assertEquals(true, $this->interpretCode($code, $eventProj, 'event1'));
	}
	public function testExample8() {
		$code = '[enrollment_arm_1][weight]/[visit_weight] > 1';
		$eventProj = array('event1' => array(
				'visit_weight' => 4,
				'race' => array(1 => 1, 2 => 1, 3 => 1, 4 => 0)
		), 'enrollment_arm_1' => array(
				'weight' => 5
		));
		$this->assertEquals(true, $this->interpretCode($code, $eventProj, 'event1'));
	}
	public function testExample9() {
		$code = 'round(14.384,1) = 14.4';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	public function testExample10() {
		$code = 'roundup(14.384,1) = 14.4';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	public function testExample11() {
		$code = 'rounddown(14.384,1) = 14.3';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	public function testExample12() {
		$code = 'sqrt(([value1]*34)/98.3) < .58';
		$eventProj = array('event1' => array(
				'value1' => 1
		));
		$this->assertEquals(false, $this->interpretCode($code, $eventProj, 'event1'));
	}
	public function testExample13() {
		$code = '([weight]+43)^(2) < 2000';
		$eventProj = array('event1' => array(
				'weight' => 1
		));
		$this->assertEquals(true, $this->interpretCode($code, $eventProj, 'event1'));
	}
	public function testExample14() {
		$code = 'if([weight] > 100, 44, 11) < [other_field]';
		$eventProj = array('event1' => array(
				'weight' => 100,
				'other_field' => 12
		));
		$this->assertEquals(true, $this->interpretCode($code, $eventProj, 'event1'));
	}
	
	/****************************************************************************
	 * Test our ability to detect illegal programs
	 ***************************************************************************/
	/**
	 * @expectedException LogicException
	 */
	public function testIllegal1() {
		$code = '`echo "HI" > C:\WINDOWS\TEMP\dq_test.txt` OR [diabetes_status] > "5"';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	/**
	 * @expectedException LogicException
	 */
	public function testIllegal2() {
		$code = ')3=3(';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	/**
	 * @expectedException LogicException
	 */
	public function testIllegal3() {
		$code = '1=1 OR (3 > get_class())';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	/**
	 * @expectedException LogicException
	 */
	public function testIllegal4() {
		$code = '$test = 5 || 3>1';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	/**
	 * @expectedException LogicException
	 */
	public function testIllegal5() {
		$code = ']identifier[ > 2 and 1=1';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	/**
	 * @expectedException LogicException
	 */
	public function testIllegal6() {
		$code = "<<<EOD\ntest test test\nEOD;";
		$this->assertEquals(true, $this->interpretCode($code));
	}
	/**
	 * @expectedException LogicException
	 */
	public function testIllegal7() {
		$code = 'include "malicious_script.php"';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	/**
	 * @expectedException LogicException
	 */
	public function testIllegal8() {
		$code = '4<>5 & 2<<6';
		$this->assertEquals(true, $this->interpretCode($code));
	}
	
	/****************************************************************************
	 * Test illegal function detection
	 ***************************************************************************/
	public function testIllegalFunc1() {
		$code = '1=1';
		$illegals = array();
		$parser = new LogicParser();
		try { $parser->parse($code); }
		catch (LogicException $e) {
			$illegals = $parser->illegalFunctionsAttempted;
		}
		$this->assertEquals(array(), $illegals);
	}
	public function testIllegalFunc2() {
		$code = '1=1 or array_unshift(1, 5)';
		$illegals = array();
		$parser = new LogicParser();
		try { $parser->parse($code); }
		catch (LogicException $e) {
			$illegals = $parser->illegalFunctionsAttempted;
		}
		$this->assertEquals(array('array_unshift'), $illegals);
	}
	public function testIllegalFunc3() {
		$code = '1=1 or array_unshift(1, 5) and median(get_class(), 5, 6)';
		$illegals = array();
		$parser = new LogicParser();
		try { $parser->parse($code); }
		catch (LogicException $e) {
			$illegals = $parser->illegalFunctionsAttempted;
		}
		$this->assertEquals(array('array_unshift', 'get_class'), $illegals);
	}
	public function testIllegalFunc4() {
		$code = '1=1 or array_unshift(1, 5) and median(sqrt(exec("./hack.sh")), 5, array())';
		$illegals = array();
		$parser = new LogicParser();
		try { $parser->parse($code); }
		catch (LogicException $e) {
			$illegals = $parser->illegalFunctionsAttempted;
		}
		$this->assertEquals(array('array_unshift', 'exec', 'array'), $illegals);
	}
	
	/****************************************************************************
	 * Test replacing unique event names with event IDs
	 ***************************************************************************/
	public function testEventReplace1() {
		$code = '[dps]<[event1_battle2][dps]';
		$eventProj =
			array(42 => array(
				'dps' => 128,
				'cold_resist' => 10,
				'fire_resist' => 24,
				'status_effects' => array(1 => 1, 3 => 0, 5=> 1, 99 => 0)
			),
			2456760 => array(
				'dps' => 64,
				'cold_resist' => 5,
				'fire_resist' => 12,
				'status_effects' => array(1 => 0, 3 => 1, 5=> 0, 99 => 1)
			),
			234 => array(
				'dps' => 32,
				'cold_resist' => 20,
				'fire_resist' => 48,
				'status_effects' => array(1 => 1, 3 => 1, 5=> 0, 99 => 0)
			)
		);
		$eventNameToId = array(
				'event1_battle1' => 42,
				'event1_battle2' => 2456760,
				'event2_battle1' => 234
		);
		$this->assertEquals(true, $this->interpretCode($code, $eventProj, $eventNameToId['event2_battle1'], $eventNameToId));
	}
	public function testEventReplace2() {
		$code = '[dps]*2+[event1_battle2][dps]!=[event1_battle1][dps]';
		$eventProj =
			array(42 => array(
				'dps' => 128,
				'cold_resist' => 10,
				'fire_resist' => 24,
				'status_effects' => array(1 => 1, 3 => 0, 5=> 1, 99 => 0)
			),
			2456760 => array(
				'dps' => 64,
				'cold_resist' => 5,
				'fire_resist' => 12,
				'status_effects' => array(1 => 0, 3 => 1, 5=> 0, 99 => 1)
			),
			234 => array(
				'dps' => 32,
				'cold_resist' => 20,
				'fire_resist' => 48,
				'status_effects' => array(1 => 1, 3 => 1, 5=> 0, 99 => 0)
			)
		);
		$eventNameToId = array(
				'event1_battle1' => 42,
				'event1_battle2' => 2456760,
				'event2_battle1' => 234
		);
		$this->assertEquals(false, $this->interpretCode($code, $eventProj, $eventNameToId['event2_battle1'], $eventNameToId));
	}
	public function testEventReplace3() {
		$code = 'max([event2_battle1][cold_resist], [event1_battle2][cold_resist], [event1_battle1][cold_resist]) = 20 && (min([event1_battle1][fire_resist],[event2_battle1][fire_resist],[event1_battle2][fire_resist]))=12';
		$eventProj =
			array(42 => array(
				'dps' => 128,
				'cold_resist' => 10,
				'fire_resist' => 24,
				'status_effects' => array(1 => 1, 3 => 0, 5=> 1, 99 => 0)
			),
			2456760 => array(
				'dps' => 64,
				'cold_resist' => 5,
				'fire_resist' => 12,
				'status_effects' => array(1 => 0, 3 => 1, 5=> 0, 99 => 1)
			),
			234 => array(
				'dps' => 32,
				'cold_resist' => 20,
				'fire_resist' => 48,
				'status_effects' => array(1 => 1, 3 => 1, 5=> 0, 99 => 0)
			)
		);
		$eventNameToId = array(
				'event1_battle1' => 42,
				'event1_battle2' => 2456760,
				'event2_battle1' => 234
		);
		$this->assertEquals(true, $this->interpretCode($code, $eventProj, $eventNameToId['event2_battle1'], $eventNameToId));
	}
	public function testEventReplace4() {
		$code = 'true=false || max(if([status_effects(5)],1000000,2*[dps]),
			if([event1_battle1][status_effects(1)],[event1_battle1][dps], 1000001),
			if([event2_battle1][status_effects(3)],[event2_battle1][dps]*4+1, 1000002))
				= 129';
		$eventProj =
			array(42 => array(
				'dps' => 128,
				'cold_resist' => 10,
				'fire_resist' => 24,
				'status_effects' => array(1 => 1, 3 => 0, 5=> 1, 99 => 0)
			),
			2456760 => array(
				'dps' => 64,
				'cold_resist' => 5,
				'fire_resist' => 12,
				'status_effects' => array(1 => 0, 3 => 1, 5=> 0, 99 => 1)
			),
			234 => array(
				'dps' => 32,
				'cold_resist' => 20,
				'fire_resist' => 48,
				'status_effects' => array(1 => 1, 3 => 1, 5=> 0, 99 => 0)
			)
		);
		$eventNameToId = array(
				'event1_battle1' => 42,
				'event1_battle2' => 2456760,
				'event2_battle1' => 234
		);
		$this->assertEquals(true, $this->interpretCode($code, $eventProj, $eventNameToId['event1_battle2'], $eventNameToId));
	}
}