<?php
/*****************************************************************************************
**  REDCap is only available through ACADMEMIC USER LICENSE with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

function getDateFormatStringInfo() {
	global $dateData;
	$text = "<p>Using the codes below, construct a formatting string to display a date</p>";
	$text .= "<p>Example: Using the format string <b>m/d/Y</b> would cause the current date to be displayed as <b>".date("m/d/Y")."</b></p>";
	$text .= createFormatStringTable($dateData);
	return $text;
}

function getTimeFormatStringInfo() {
	global $timeData;
	$text = "<p>Using the codes below, construct a formatting string to display a time</p>";
	$text .= "<p>Example: Using the format string <b>H:i</b> would cause the current time to be displayed as <b>".date("H:i")."</b></p>";
	$text .= createFormatStringTable($timeData);
	return $text;
}

function createFormatStringTable($data) {
	$table = "<table border='0' cellpadding='20' cellspacing='0'>";
	$table .= "<tr>";
	$table .= "<th style='width:60px;margin:5px;border:1px solid #888888'><b>Code</b></th>";
	$table .= "<th style='width:540px;margin:5px;border-top:1px solid #888888;border-right:1px solid #888888;border-bottom:1px solid #888888'><b>Description</b></th>";
	$table .= "</tr>";
	foreach($data as $val) {
		$table .= "<tr>";
		$table .= "<td style='margin:5px;text-align:center;border-bottom:1px solid #888888;border-left:1px solid #888888;border-right:1px solid #888888'>{$val[0]}</td>";
		$table .= "<td style='margin:5px;border-bottom:1px solid #888888;border-right:1px solid #888888'>{$val[1]}</td>";
		$table .= "</tr>";
	}
	$table .= "</table>";
	
	return $table;
}


$dateData = array();
$dateData[0] = array();
$dateData[0][0] = 'd';
$dateData[0][1] = 'Day of the month, 2 digits with leading zeros';
$dateData[0][2] = '01 to 31';

$dateData[1] = array();
$dateData[1][0] = 'D';
$dateData[1][1] = 'A textual representation of a day, three letters';
$dateData[1][2] = 'Mon through Sun';

$dateData[2] = array();
$dateData[2][0] = 'j';
$dateData[2][1] = 'Day of the month without leading zeros';
$dateData[2][2] = '1 to 31';

$dateData[3] = array();
$dateData[3][0] = 'l';
$dateData[3][1] = 'A full textual representation of the day of the week';
$dateData[3][2] = 'Sunday through Saturday';

$dateData[4] = array();
$dateData[4][0] = 'N';
$dateData[4][1] = 'ISO-8601 numeric representation of the day of the week';
$dateData[4][2] = '1 (for Monday) through 7 (for Sunday)';

$dateData[5] = array();
$dateData[5][0] = 'S';
$dateData[5][1] = 'English ordinal suffix for the day of the month, 2 characters';
$dateData[5][2] = 'st, nd, rd or th';

$dateData[6] = array();
$dateData[6][0] = 'w';
$dateData[6][1] = 'Numeric representation of the day of the week';
$dateData[6][2] = '0 (for Sunday) through 6 (for Saturday)';

$dateData[7] = array();
$dateData[7][0] = 'z';
$dateData[7][1] = 'The day of the year (starting from 0)';
$dateData[7][2] = '0 through 365';

$dateData[8] = array();
$dateData[8][0] = 'W';
$dateData[8][1] = 'ISO-8601 week number of year, weeks starting on Monday';
$dateData[8][2] = 'Example: 42 (the 42nd week in the year)';

$dateData[9] = array();
$dateData[9][0] = 'F';
$dateData[9][1] = 'A full textual representation of a month, such as January or March';
$dateData[9][2] = 'January through December';

$dateData[10] = array();
$dateData[10][0] = 'm';
$dateData[10][1] = 'Numeric representation of a month, with leading zeros';
$dateData[10][2] = '01 through 12';

$dateData[11] = array();
$dateData[11][0] = 'M';
$dateData[11][1] = 'A short textual representation of a month, three letters';
$dateData[11][2] = 'Jan through Dec';

$dateData[12] = array();
$dateData[12][0] = 'n';
$dateData[12][1] = 'Numeric representation of a month, without leading zeros';
$dateData[12][2] = '1 through 12';

$dateData[13] = array();
$dateData[13][0] = 't';
$dateData[13][1] = 'Number of days in the given month';
$dateData[13][2] = '28 through 31';

$dateData[14] = array();
$dateData[14][0] = 'Y';
$dateData[14][1] = 'A full numeric representation of a year, 4 digits';
$dateData[14][2] = 'Examples: 1999 or 2003';

$dateData[15] = array();
$dateData[15][0] = 'y';
$dateData[15][1] = 'A two digit representation of a year';
$dateData[15][2] = 'Examples: 99 or 03';

$timeData = array();
$timeData[0] = array();
$timeData[0][0] = 'a';
$timeData[0][1] = 'Lowercase Ante meridiem and Post meridiem';
$timeData[0][2] = 'am or pm';

$timeData[1] = array();
$timeData[1][0] = 'A';
$timeData[1][1] = 'Uppercase Ante meridiem and Post meridiem';
$timeData[1][2] = 'AM or PM';

$timeData[2] = array();
$timeData[2][0] = 'B';
$timeData[2][1] = 'Swatch Internet time';
$timeData[2][2] = '000 through 999';

$timeData[3] = array();
$timeData[3][0] = 'g';
$timeData[3][1] = '12-hour format of an hour without leading zeros';
$timeData[3][2] = '1 through 12';

$timeData[4] = array();
$timeData[4][0] = 'G';
$timeData[4][1] = '24-hour format of an hour without leading zeros';
$timeData[4][2] = '0 through 23';

$timeData[5] = array();
$timeData[5][0] = 'h';
$timeData[5][1] = '12-hour format of an hour with leading zeros';
$timeData[5][2] = '01 through 12';

$timeData[6] = array();
$timeData[6][0] = 'H';
$timeData[6][1] = '24-hour format of an hour with leading zeros';
$timeData[6][2] = '00 through 23';

$timeData[7] = array();
$timeData[7][0] = 'i';
$timeData[7][1] = 'Minutes with leading zeros';
$timeData[7][2] = '00 to 59';

$timeData[8] = array();
$timeData[8][0] = 's';
$timeData[8][1] = 'Seconds, with leading zeros';
$timeData[8][2] = '00 through 59';

$timeData[9] = array();
$timeData[9][0] = 'u';
$timeData[9][1] = 'Microseconds';
$timeData[9][2] = 'Example: 654321';

$timeData[10] = array();
$timeData[10][0] = 'e';
$timeData[10][1] = 'Timezone identifier';
$timeData[10][2] = 'Examples: UTC, GMT, Atlantic/Azores';

$timeData[11] = array();
$timeData[11][0] = 'O';
$timeData[11][1] = 'Difference to Greenwich time (GMT) in hours';
$timeData[11][2] = 'Example: +0200';

$timeData[12] = array();
$timeData[12][0] = 'P';
$timeData[12][1] = 'Difference to Greenwich time (GMT) with colon between hours and minutes';
$timeData[12][2] = 'Example: +02:00';

$timeData[13] = array();
$timeData[13][0] = 'T';
$timeData[13][1] = 'Timezone abbreviation';
$timeData[13][2] = 'Examples: EST, MDT';

$timeData[14] = array();
$timeData[14][0] = '';
$timeData[14][1] = '';
$timeData[14][2] = '';

/****************************************************************
 * Standard Mapping Operations
 * 
 * performs the various mapping operations and returns a JSON response.  If the
 * operation was successful, then specific data based on the request is returned
 * in the data object of the response.  If the operation fails, then the data
 * object returned contains one member, a message string.
 */

if(!isset($_GET["operation"])) {
	print getJsonResponse(false,array("message"=>"operation not set"));
	exit();
}
$operation = $_GET["operation"];
	
if($operation == "addStandard") {
	print addStandard($_GET['standard'],$_GET['version'],$_GET['description']);
}else if($operation == "addMappedField") {
	print addMappedField($_GET['mapId'], $_GET['field'], $_GET['standard'], $_GET['code'], $_GET['conversion'], $_GET['checked'], $_GET['unchecked']);
}else if($operation == "getFieldData") {
	print getFieldData($_GET['mapId']);
}else if($operation == "getEnumeratedValues") {
	print getEnumeratedValues($_GET['pid'], $_GET['fieldName']);
}else if($operation == "removeMappedField") {
	print removeMappedField($_GET['mapId']);
}else if($operation == "getDateFormatInfo") {
	$text = getDateFormatStringInfo();
	$returnData = array();
	$returnData['text'] = $text;
	print getJsonResponse(true,$returnData);
}else if($operation == "getTimeFormatInfo") {
	$text = getTimeFormatStringInfo();
	$returnData = array();
	$returnData['text'] = $text;
	print getJsonResponse(true,$returnData);
}else {
	print getJsonResponse(false,array("message"=>"an unknown standard mapping operation was requested"));
}

/****************************************************************
 * Standard Mapping Functions
 */
function addStandard($standard, $version, $description) {
	$success = false;
	$returnData = array();
	$standard = html_entity_decode($standard);
	$version = html_entity_decode($version);
	$description = html_entity_decode($description);
	if(isset($standard) && trim($standard) != "" && isset($version) && trim($version) != "") {	
		try {
			$checkSql = "select standard_id from redcap_standard where standard_name = '$standard' and standard_version = '$version'";
			$checkQuery = queryRecords($checkSql);
			if($checkArray = db_fetch_array($checkQuery)) {
				$success = false;
				$returnData["message"] = "standard already exists";
				$returnData["id"] = $checkArray["standard_id"];
			}else {
				$insertSql = "insert into redcap_standard(standard_name, standard_version, standard_desc) values('$standard','$version','$description')";
				$newId = insertRecords($insertSql);
				$success = true;
				$returnData["message"] = "standard added";
				$returnData['id'] = $newId;
			}
		}catch(Exception $e) {
			$success = false;
			$returnData['message'] = 'exception occurred adding the standard: ' . $e->getMessage();
		}
	}else {
		$success = false;
		$returnData['message'] = "missing necessary parameters for creating new standard";
	}
	return getJsonResponse($success, $returnData);
}

function addMappedField($mapId, $field, $standardId, $code, $conversion, $checkedVal, $uncheckedVal) {
	$success = false;
	$returnData = array();
	$field = html_entity_decode($field);
	$code = html_entity_decode($code);
	$checkedVal = html_entity_decode($checkedVal);
	$uncheckedVal = html_entity_decode($uncheckedVal);
	$conversion = html_entity_decode($conversion);
	$orig = array("\r", "\n");
	$repl = array(""  , "\\\\n");
	$conversion = str_replace($orig, $repl, $conversion);
	if(trim($mapId) == "") {
		$mapId = -1;
	}
	$dataConversion2Update = "";
	$dataConversion2Insert = "null";
	$codeOptional = false;
	if(isset($checkedVal) && trim($checkedVal) != "" && isset($uncheckedVal) && trim($uncheckedVal) != "") {
		$dataConversion2Update = ", data_conversion2 = 'checked=$checkedVal\\\\nunchecked=$uncheckedVal'";
		$dataConversion2Insert = "'checked=$checkedVal\\\\nunchecked=$uncheckedVal'";
		$codeOptional = true;
	}
	
	if(isset($field) && trim($field) != "" && isset($standardId) && trim($standardId) != "" && ($codeOptional || (isset($code) && trim($code) != ""))) {	
		try {
			//check if it is already mapped to the standard (if a positive mapId is provided, then this is an update)
			$checkStandard = queryRecords("select standard_code 
                                           from redcap_standard_map m, redcap_standard_code c 
                                           where m.project_id = {$_GET['pid']} 
                                               and m.field_name = '$field' 
                                               and c.standard_id = $standardId 
                                               and m.standard_code_id = c.standard_code_id
                                               and m.standard_map_id <> $mapId");
			if($checkStandardArray = db_fetch_array($checkStandard)) {
				throw new Exception("this field has already been mapped to the either the code or standard specified");
			}
			
			$code_id = -1;
			//check if code already exists, and add it if necessary
			$checkQuery = queryRecords("select standard_code_id from redcap_standard_code where standard_id = $standardId and standard_code = '$code'");
			if($checkArray = db_fetch_array($checkQuery)) {
				$code_id = $checkArray['standard_code_id'];
			}else {
				$insertSql = "insert into redcap_standard_code(standard_code, standard_id) values('$code',$standardId)";
				$code_id = insertRecords($insertSql);
				logEvent($_GET['pid'],$field,$code_id,1);
			}
			
			$standardInfo = getStandardInfo($standardId);
			if($mapId > 0) {
				//get current mapping
				$codeInfo = getCodeInfo($mapId);
				//this is an edit, so update the existing map id instead of creating a new one
				$updateSql = "update redcap_standard_map set project_id = {$_GET['pid']}, field_name = '$field', standard_code_id = $code_id, data_conversion = '$conversion' $dataConversion2Update where standard_map_id = $mapId";
				$updateDone = updateRecords($updateSql);
				logEvent($_GET['pid'], $field, $code_id, 2);
				if($updateDone) {
//					//don't remove unused codes so that the log table can continue to reference it
//					if($code_id != $codeInfo['code_id'] && $codeInfo['code_usage'] == 1) {
//						deleteRecords("delete from redcap_standard_code where standard_code_id = {$codeInfo['code_id']}");
//						
//					}
					$success = true;
					$returnData["id"] = $code_id;
					$returnData["map_id"] = $mapId;
					$returnData["standard_name"] = $standardInfo["name"];
					$returnData["standard_version"] = $standardInfo["version"];
					$returnData["mode"] = "update";
				}else {
					$success = false;
					$returnData['message'] = "field mapping update failed";
				}
			}else {
				//this is not an edit, so check to make sure the mapping doesn't already exisit
				$checkSql = "select * from redcap_standard_map where project_id = {$_GET['pid']} and field_name = '$field' and standard_code_id = $code_id";
				if(checkRecords($checkSql)) {
					$success = false;
					$returnData['message'] = "field mapping already exists";
				}else {
					$insertSql = "insert into redcap_standard_map(project_id, field_name, standard_code_id, data_conversion, data_conversion2) values({$_GET['pid']},'$field',$code_id,'$conversion', $dataConversion2Insert)";
					$mapId = insertRecords($insertSql);
					logEvent($_GET['pid'], $field, $code_id, 1);
					$success = true;
					$returnData["id"] = $code_id;
					$returnData["map_id"] = $mapId;
					$returnData["standard_name"] = $standardInfo["name"];
					$returnData["standard_version"] = $standardInfo["version"];
					$returnData["mode"] = "new";
				}
			}
			
		}catch(Exception $e) {
			$success = false;
			$returnData['message'] = 'exception occurred adding a field mapping: ' . $e->getMessage();
		}
	}else {
		$success = false;
		if(!isset($field) || trim($field) == "") {
			$returnData['message'] = "REDCap field name was not specified for the add mapped field request";
		}else if(!isset($standardId) || trim($standardId) == "") {
			$returnData['message'] = "There was no standard selected for the add mapped field request";
		}else if(!isset($code) || trim($code) == "") {
			$returnData['message'] = "There was no standard code specified for the add mapped field request";
		}
	}
	return getJsonResponse($success, $returnData);
}

function getFieldData($mapId) {
	$success = false;
	$returnData = array();
	if(isset($mapId) && trim($mapId) != "") {	
		try {
			$getCodeData = queryRecords("select map.data_conversion, map.data_conversion2, code.standard_code, std.standard_id, std.standard_name, std.standard_version,
											map.project_id, map.field_name
										from redcap_standard_map map, redcap_standard_code code, redcap_standard std 
										where map.standard_map_id = $mapId 
											and map.standard_code_id = code.standard_code_id and code.standard_id = std.standard_id");
			if($codeData = db_fetch_array($getCodeData)) {
				$returnData["checked_value"] = "1";
				$returnData["unchecked_value"] = "0";
				if(trim($codeData["data_conversion2"]) != "") {
					$tempSplit = explode("\\n",html_entity_decode($codeData["data_conversion2"], ENT_QUOTES));
					$returnData["checked_value"] = substr($tempSplit[0],strpos($tempSplit[0],"checked=")+8);
					$returnData["unchecked_value"] = substr($tempSplit[1],strpos($tempSplit[1],"unchecked=")+10);
				}
				$returnData["project_id"] = $codeData["project_id"];
				$returnData["field_name"] = html_entity_decode($codeData["field_name"], ENT_QUOTES);
				$returnData["data_conversion"] = html_entity_decode(str_replace('"','\\"',$codeData["data_conversion"]), ENT_QUOTES);
				$returnData["standard_code"] = html_entity_decode(str_replace('"','\\"',$codeData["standard_code"]), ENT_QUOTES);
				$returnData["standard_id"] = $codeData["standard_id"];
				$returnData["standard_name"] = html_entity_decode($codeData["standard_name"], ENT_QUOTES);
				$returnData["standard_version"] = html_entity_decode($codeData["standard_version"], ENT_QUOTES);
				$success = true;
			}else {
				$success = false;
				$returnData["message"] = "no mappings found for field";
			}			
		}catch(Exception $e) {
			$success = false;
			$returnData['message'] = 'exception occurred retrieving a field mapping: ' . $e->getMessage();
		}
	}else {
		$success = false;
		$returnData['message'] = "missing necessary parameters for retrieving a field mapping ($mapId)";
	}
	return getJsonResponse($success, $returnData);
}

function getEnumeratedValues($pid, $field) {
	$success = false;
	$returnData = array();
	if(isset($pid) && trim($pid) != "" && isset($field) && trim($field) != "") {	
		try {
			$getCodeData = queryRecords("select element_enum 
										from redcap_metadata
										where project_id = $pid and field_name = '$field'");
			if($codeData = db_fetch_array($getCodeData)) {
				$enumValue = "";
				$arr = explode('\n',$codeData['element_enum']);
				foreach($arr as $entry) {
					$enumValue .= trim(substr($entry,0,strpos($entry,","))) . '\n';
				}	
				$returnData['enumerated_values'] = $enumValue;
				$returnData['enumerated_values_full'] = $codeData['element_enum'];
				$success = true;
			}else {
				$success = false;
				$returnData["message"] = "no enumerations found";
			}			
		}catch(Exception $e) {
			$success = false;
			$returnData['message'] = 'exception occurred retrieving enumerated values: ' . $e->getMessage();
		}
	}else {
		$success = false;
		$returnData['message'] = "missing necessary parameters for retrieving enumerated values ($pid|$field)";
	}
	return getJsonResponse($success, $returnData);
}

function removeMappedField($mapId) {
	$success = false;
	$returnData = array();
	if(isset($mapId) && is_numeric($mapId) && $mapId > 0) {
		$code = getCodeInfo($mapId);
//		//don't remove unused codes so that the log table can continue to reference it
//		if($code["code_usage"] == 1) {
//			if($code["standard_usage"] == 1) {
//				deleteRecords("delete from redcap_standard where standard_id = {$code['standard_id']}");
//			}
//			deleteRecords("delete from redcap_standard_code where standard_code_id = {$code['code_id']}");
//		}
		deleteRecords("delete from redcap_standard_map where standard_map_id = $mapId");
		logEvent($code['project_id'], $code['field_name'], $code['code_id'], 3);
		$success = true;
		$returnData['message'] = "mapped field was successfully removed";
	}else {
		$success = false;
		$returnData['message'] = "missing necessary parameters for removing a mapped field";
	}
	return getJsonResponse($success, $returnData);
}

/****************************************************************
 * Standard Mapping Utility Functions
 */

function getStandardInfo($standardId) {
	$standard = array();
	$standard["name"] = "";
	$standard["version"] = "";
	$getStandardQuery = queryRecords("select * from redcap_standard where standard_id = $standardId");
	if($getStandardArray = db_fetch_array($getStandardQuery)) {
		$standard["name"] = $getStandardArray["standard_name"];
		$standard["version"] = $getStandardArray["standard_version"];
	}else {
		throw new Exception("the specified standard does not exist");
	}
	return $standard;
}

function getCodeInfo($mapId) {
	$code = array();
	$getCodeQuery = queryRecords("select map.project_id, map.field_name, code.standard_code_id, code.standard_code, std.standard_id, std.standard_name, std.standard_version
                                  from redcap_standard_map map, redcap_standard_code code, redcap_standard std 
                                  where map.standard_code_id = code.standard_code_id
                                        and code.standard_id = std.standard_id
                                        and map.standard_map_id = $mapId");
	if($getCodeArray = db_fetch_array($getCodeQuery)) {
		$code["project_id"] = $getCodeArray["project_id"];
		$code["field_name"] = $getCodeArray["field_name"];
		$code["code_id"] = $getCodeArray["standard_code_id"];
		$code["code"] = $getCodeArray["standard_code"];
		$code["standard_id"] = $getCodeArray["standard_id"];
		$code["standard_name"] = $getCodeArray["standard_name"];
		$code["standard_version"] = $getCodeArray["standard_version"];
		$getCodeCountQuery = queryRecords("select count(*) as c from redcap_standard_map where standard_code_id = {$getCodeArray['standard_code_id']}");
		if($getCodeUsageArray = db_fetch_array($getCodeCountQuery)) {
			$code["code_usage"] = $getCodeUsageArray["c"];
		}
		$getStdCountQuery = queryRecords("select count(*) as c from redcap_standard_code where standard_id = {$getCodeArray['standard_id']}");
		if($getStdUsageArray = db_fetch_array($getStdCountQuery)) {
			$code["standard_usage"] = $getStdUsageArray["c"];
		}
	}else {
		throw new Exception("the specified code does not exist");
	}
	return $code;
}

function getJsonResponse($success, $dataArray) {
	$retVal = '{"success":'.($success?"true":"false");
	if(is_array($dataArray) && sizeof($dataArray > 0)) {
		$retVal .= ',"data":{';
		$delim = '';
		foreach($dataArray as $k => $v) {
			$retVal .= $delim.'"'.$k.'":"'.$v.'"';
			$delim = ',';
		}
		$retVal .= "}";
	}
	$retVal .= "}";
	return $retVal;
}

function queryRecords($sql) {
	$selectQuery = db_query($sql);
	if(!$selectQuery) { 
		throw new Exception('Query failed: ' . db_error());
	}
	return $selectQuery;
}

function checkRecords($sql) {
	$checkQuery = db_query($sql);
	if(!$checkQuery) { 
		throw new Exception('Check DB failed: ' . db_error());
	}
	if(db_num_rows($checkQuery) > 0) {
		return true;
	}
	return false;
}

function insertRecords($sql) {
	$updateQuery = db_query($sql);
	if(!$updateQuery) { 
		throw new Exception('Insert failed: ' . db_error());
	}
	$newId = db_insert_id();
	return $newId;
}

function updateRecords($sql) {
	$updateQuery = db_query($sql);
	if(!$updateQuery) { 
		throw new Exception('Update failed: ' . db_error());
	}
	return $updateQuery;
}

function deleteRecords($sql) {
	$deleteQuery = db_query($sql);
	if(!$deleteQuery) {
		throw new Exception('Delete failed: ' . db_error());
	}
	return $deleteQuery;
}

function logEvent($project, $field, $code, $action) {
	$sql = "insert into redcap_standard_map_audit(project_id, field_name, standard_code, action_id, user, timestamp) ";
	$sql .= "values($project,'$field',$code,$action,'".USERID."','".NOW."')";
	try {
		$audit_id = insertRecords($sql);
	}catch(Exception $e) {
		error_log("error updating standard mapping audit log\n$sql");
	}
}
