<?php

/*****************************************************************************************
**  REDCap is only available through ACADMEMIC USER LICENSE with Vanderbilt University
******************************************************************************************/

/**
 * This class provides convenience static methods that call the global log_event() function.
 */
class RCLog 
{
	/**#@+ Values suitable for redcap_log_event.description */
	const DESC_MODPROJ = 'Modify project settings';
	const DESC_SYSCON = 'Modify system configuration';
	const DESC_PUBMATCH = 'Match publication to project(s)';
	const DESC_PARTIDENT_ENABLE = 'Enable participant identifiers for project';
	const DESC_PARTIDENT_DISABLE = 'Disable participant identifiers for project';
	/**#@-*/
	
	/**#@+ Values suitable for redcap_log_event.event */
	const EVENT_DATA_EXP = 'DATA_EXPORT';
	const EVENT_DELETE = 'DELETE';
	const EVENT_DOC_DEL = 'DOC_DELETE';
	const EVENT_DOC_UP = 'DOC_UPLOAD';
	const EVENT_ESIGN = 'ESIGNATURE';
	const EVENT_INSERT = 'INSERT';
	const EVENT_LOCK = 'LOCK_RECORD';
	const EVENT_MANAGE = 'MANAGE';
	const EVENT_OTHER = 'OTHER';
	const EVENT_SELECT = 'SELECT';
	const EVENT_UPDATE = 'UPDATE';
	/**#@-*/
	
	/**
	 * Logs an update to system configuration.
	 * @param string $sqlUpdate a single SQL update statement.
	 * @return type the output of log_event().
	 */
	static function sysConfig($sqlUpdate) {
		$parser = new PHPSQLParser($sqlUpdate);
		$table = $parser->parsed['UPDATE'][0]['table'];
		$changed = array();
		foreach ($parser->parsed['SET'] as $set)
			$changed[] = $set['column'] . ' = ' . $set['expr'];
		$changeLog = implode(",\n", $changed);
		return log_event($sqlUpdate, $table, self::EVENT_MANAGE, '', $changeLog,
						self::DESC_SYSCON);
	}
	
	/**
	 * Logs an update to a project.
	 * @param string $sqlUpdate a single SQL update statement.
	 * @return type the output of log_event().
	 */
	static function modifyProject($sqlUpdate) {
		$parser = new PHPSQLParser($sqlUpdate);
		$table = $parser->parsed['UPDATE'][0]['table'];
		$project_id = -1;
		for ($i = 0; $i < count($parser->parsed['WHERE']); $i++) {
			$arr = $parser->parsed['WHERE'][$i];
			if ($arr['expr_type'] == 'colref' && $arr['base_expr'] == 'project_id') {
				$project_id = $parser->parsed['WHERE'][$i+2]['base_expr'];
				break;
			}
		}
		return log_event($sqlUpdate, $table, self::EVENT_MANAGE, $project_id,
						"project_id = $project_id", self::DESC_MODPROJ);
	}
}