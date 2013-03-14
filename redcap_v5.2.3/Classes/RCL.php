<?php

/*****************************************************************************************
**  REDCap is only available through ACADMEMIC USER LICENSE with Vanderbilt University
******************************************************************************************/

/**
 * RCL (RedCap Language) is a class of static functions that provides a
 * "semantic layer" to the numbered items in the global $lang variable. The
 * goal is to assist in code readability by transforming something like
 * $lang['design_100'] into RCL::yes().
 */
class RCL {
	
	/** Returns the value in the global $lang array specified by the given $key. */
	private static function _lang($key) {
		global $lang;
		return $lang[$key];
	}
	
	/**
	 * Used for development as an easy way to see which strings of text need
	 * to be added to English.ini.
	 */
	private static function _todo($str) { return $str; }
	
	/**
	 * Converts all of this class's _todo() functions into _lang() functions
	 * by appending new variables to English.ini and overwriting this file.
	 * @param string $prefix the prefix for the INI variables, e.g, "pub_". If
	 * the prefix already exists in the INI file, then the variables will be
	 * numbered at MAX + 1.
	 */
	public static function convertToINI($prefix, $IKnowWhatImDoing=false) {
		if (!$IKnowWhatImDoing) return false;
		$todos = array(); // full method text as keys and associative as values
		// parse this file to figure out what todos need converting
		$rcl = file_get_contents(__FILE__);
		$nl = "\n"; // assume that UNIX newlines will work for us
		$charsPerLine = 80; // used to format the INI output
		preg_match_all('/static\s+function\s+(\w+)\(\)\s*{\s*return\s+self::_todo\(\'(.*?)\'\);\s*}/', $rcl, $matches);
		for ($i = 0; $i < count($matches[0]); $i++) {
			// format the language string for the INI file
			$str = $matches[2][$i];
			$str = str_replace("\\'", "'", $str); // INI doesn't need apostrophes escaped
			$str = str_replace('"', "'", $str); // INI can't have embedded quotation marks
			$words = explode(' ', $str);
			$lines = array(); $line = '';
			foreach ($words as $word) {
				if (strlen($line) + strlen($word) > $charsPerLine) {
					$lines[] = trim($line);
					$line = '';
				}
				$line .= " $word";
			}
			$lines[] = trim($line); // include the last line
			$todos[$matches[0][$i]]['str'] = implode("$nl\t", $lines);
			$todos[$matches[0][$i]]['methodName'] = $matches[1][$i];
		}
		// determine the default padding and starting variable number
		$pad = max(strlen(count($todos)), 2);
		$varnum = 1;
		// adjust padding and starting variable number depending on INI file
		$iniFilename = dirname(__DIR__) . '/LanguageUpdater/English.ini';
		$lang = parse_ini_file($iniFilename);
		$prefix2Max = array();
		foreach ($lang as $key => $value) {
			if (preg_match('/^(\w+?)(\d+)$/', $key, $matches)) {
				$keyPrefix = $matches[1];
				$keyNum = intval($matches[2]);
				if (!array_key_exists($keyPrefix, $prefix2Max)) $prefix2Max[$keyPrefix] = $keyNum;
				$prefix2Max[$keyPrefix] = max($keyNum, $prefix2Max[$keyPrefix]);
			}
		}
		if (array_key_exists($prefix, $prefix2Max)) {
			if (strlen($prefix2Max[$prefix] + count($todos)) > strlen($prefix2Max[$prefix]))
				throw new Exception("$prefix numbering is increasing in scale!");
			$varnum = $prefix2Max[$prefix] + 1;
			$pad = max(strlen($prefix2Max[$prefix] + count($todos)), 2);
		}
		$ini = fopen($iniFilename, 'a');
		fwrite($ini, "$nl$nl");
		foreach ($todos as $method => $todo) {
			$varname = $prefix . str_pad($varnum, $pad, '0', STR_PAD_LEFT);
			fwrite($ini, "$varname = \"" . $todo['str'] . "\"$nl");
			$newMethod = 'static function ' . $todo['methodName'] . "() { return self::_lang('$varname'); }";
			$rcl = str_replace($method, $newMethod, $rcl);
			$varnum++;
		}
		fclose($ini);
		file_put_contents(__FILE__, $rcl); // rewrite this file
	}
	
	/**#@+ Alphabetized functions with user-friendly names for $lang elements. */
	static function accessDenied() { return self::_lang('pub_001'); }
	static function alias() { return self::_lang('pub_002'); }
	static function allOfThese() { return self::_lang('pub_003'); }
	static function andThenClick() { return self::_lang('pub_004'); }
	static function cancelAllChanges() { return self::_lang('pub_005'); }
	static function customSettings() { return self::_lang('control_center_221'); }
	static function changesSaved() { return self::_lang('edit_project_02'); }
	static function decideLater() { return self::_lang('pub_006'); }
	static function downloadHere() { return self::_lang('pub_007'); }
	static function dr() { return self::_lang('pub_008'); }
	static function copy() { return self::_lang('pub_009'); }
	static function copyPI() { return self::_lang('pub_010'); }
	static function cronDownloaded() { return self::_lang('pub_011'); }
	static function cronContents() { return self::_lang('pub_012'); }
	static function cronSetup() { return self::_lang('pub_013'); }
	static function cronSimulate() { return self::_lang('control_center_214'); }
	static function disabled() { return self::_lang('global_23'); }
	static function email() { return self::_lang('pub_014'); }
	static function enabled() { return self::_lang('index_30'); }
	static function exportExcelCSV() { return self::_lang('pub_015'); }
	static function fileMissingBut() { return self::_lang('pub_016'); }
	static function loading() { return self::_lang('pub_017'); }
	static function manageProjects() { return self::_lang('pub_018'); }
	static function matchedToProjects() { return self::_lang('pub_019'); }
	static function matches() { return self::_lang('pub_020'); }
	static function missingData() { return self::_lang('pub_021'); }
	static function missingNames() { return self::_lang('pub_022'); }
	static function nameFirst() { return self::_lang('pub_023'); }
	static function nameLast() { return self::_lang('pub_024'); }
	static function nameMiddle() { return self::_lang('pub_025'); }
	static function no() { return self::_lang('design_99'); }
	static function noData() { return self::_lang('pub_026'); }
	static function noneOfThese() { return self::_lang('pub_027'); }
	static function other() { return self::_lang('pub_028'); }
	static function pi() { return self::_lang('pub_029'); }
	static function piEmails() { return self::_lang('pub_030'); }
	static function pisToBeEmailed() { return self::_lang('pub_031'); }
	static function projects() { return self::_lang('pub_032'); }
	static function provideAny() { return self::_lang('pub_033'); }
	static function pubAltInstNames() { return self::_lang('pub_034'); }
	static function pubCancelChanges() { return self::_lang('pub_035'); }
	static function pubCopyComplete() { return self::_lang('pub_036'); }
	static function pubCopySelect() { return self::_lang('pub_037'); }
	static function pubCopyTitle() { return self::_lang('pub_038'); }
	static function pubCtrlDisabled() { return self::_lang('pub_039'); }
	static function pubCtrlHelp() { return self::_lang('control_center_213'); }
	static function pubCtrlTitle() { return self::_lang('control_center_210'); }
	static function pubEmailCongrats() { return self::_lang('pub_040'); }
	static function pubEmailExample() { return self::_lang('pub_041'); }
	static function pubEmailHelp() { return self::_lang('pub_042'); }
	static function pubEmailListHelp() { return self::_lang('pub_107'); }
	static function pubEmailIntro() { return self::_lang('pub_043'); }
	static function pubEmailLink() { return self::_lang('pub_044'); }
	static function pubEmailLimitReached() { return self::_lang('pub_045'); }
	static function pubEmailPubCnt() { return self::_lang('pub_046'); }
	static function pubEmailResources() { return self::_lang('pub_047'); }
	static function pubEmailSetupNote() { return self::_lang('pub_048'); }
	static function pubEmailSubject() { return self::_lang('pub_049'); }
	static function pubErrNoTodos() { return self::_lang('pub_050'); }
	static function pubErrNotReady() { return self::_lang('pub_051'); }
	static function pubErrReadyOverwrite() { return self::_lang('pub_052'); }
	static function pubInstLabel() { return self::_lang('control_center_211'); }
	static function pubMatchedIntro() { return self::_lang('pub_053'); }
	static function pubMatchedNone() { return self::_lang('pub_054'); }
	static function pubMatching() { return self::_lang('control_center_210'); }
	static function pubMatchingDisabledWarn() { return self::_lang('pub_055'); }
	static function pubMatchingEmails() { return self::_lang('pub_056'); }
	static function pubMatchingEmailsDisabledWarn() { return self::_lang('pub_057'); }
	static function pubMatchingEmailsHelp() { return self::_lang('pub_058'); }
	static function pubMatchingEmailFreq() { return self::_lang('pub_059'); }
	static function pubMatchingEmailFreqHelp() { return self::_lang('pub_060'); }	
	static function pubMatchingEmailLimit() { return self::_lang('pub_061'); }
	static function pubMatchingEmailLimitHelp() { return self::_lang('pub_062'); }
	static function pubMatchingEmailSubject() { return self::_lang('pub_063'); }
	static function pubMatchingEmailSubjectHelp() { return self::_lang('pub_064'); }
	static function pubMatchingEmailText() { return self::_lang('pub_065'); }
	static function pubMatchingEmailTextHelp() { return self::_lang('pub_066'); }
	static function pubMatchingHelp() { return self::_lang('pub_067'); }
	static function pubMatchingStatsHelp() { return self::_lang('pub_109'); }
	static function pubManageHelp() { return self::_lang('pub_068'); }
	static function pubMatchingURLHelp() { return self::_lang('pub_110'); }
	static function pubMultiInst() { return self::_lang('control_center_212'); }	
	static function pubPICopy() { return self::_lang('pub_069'); }
	static function pubPIEmailLink() { return self::_lang('pub_070'); }
	static function pubPIInstructions() { return self::_lang('pub_071'); }
	static function pubPIInstructionsClose() { return self::_lang('pub_108'); }
	static function pubPIIsYours() { return self::_lang('pub_072'); }
	static function pubPISearchCopy() { return self::_lang('pub_073'); }
	static function pubPITitle() { return self::_lang('pub_074'); }
	static function pubPITodo() { return self::_lang('pub_075'); }
	static function pubPIWhichProjects() { return self::_lang('pub_076'); }
	static function pubs() { return self::_lang('pub_077'); }
	static function pubsPending() { return self::_lang('pub_078'); }
	static function pubSetupHelp() { return self::_lang('pub_079'); }
	static function pubSuggest() { return self::_lang('pub_080'); }
	static function pubTodoHelp() { return self::_lang('pub_081'); }
	static function pubUseExclude() { return self::_lang('pub_082'); }
	static function pubUseReady() { return self::_lang('pub_083'); }
	static function redcapURL() { return self::_lang('pub_105'); }
	static function redcapURLError() { return self::_lang('pub_106'); }
	static function required() { return self::_lang('shared_library_62'); }
	static function reloadThis() { return self::_lang('pub_084'); }
	static function save() { return self::_lang('pub_085'); }
	static function saveAll() { return self::_lang('pub_086'); }
	static function saveChanges() { return self::_lang('report_builder_28'); }
	static function saved() { return self::_lang('control_center_48'); }
	static function saveFail() { return self::_lang('control_center_49'); }
	static function saveInterrupt() { return self::_lang('pub_087'); }
	static function saveRetry() { return self::_lang('pub_088'); }
	static function saving() { return self::_lang('pub_089'); }
	static function searchPIName() { return self::_lang('pub_090'); }
	static function searchPINameCopyLong() { return self::_lang('pub_091'); }
	static function searchPINameCopyShort() { return self::_lang('pub_092'); }
	static function searchPINameHelp() { return self::_lang('pub_093'); }
	static function searchProj() { return self::_lang('pub_094'); }
	static function searchProjHelp() { return self::_lang('pub_095'); }
	static function setup() { return self::_lang('pub_096'); }
	static function stats() { return self::_lang('pub_097'); }
	static function subjectCAPS() { return self::_lang('pub_098'); }
	static function todo() { return self::_lang('pub_099'); }
	static function todoDone() { return self::_lang('pub_100'); }
	static function todoList() { return self::_lang('pub_101'); }
	static function toggleAll() { return self::_lang('pub_102'); }
	static function workingOn() { return self::_lang('pub_103'); }
	static function yes() { return self::_lang('design_100'); }
	/**#@-*/
}