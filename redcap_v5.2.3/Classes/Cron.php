<?php

/**
 * CRON
 * This class will be instatiated when the REDCap "cron job URL" (e.g. https://MYSERVER/redcap/cron.php) 
 * is called by a cron job. It will build a to-do list to be accomplished and then execute them all.
 */
class Cron
{
	// Array to collect the specific jobs to be done (as defined by user)
	private $jobList = array();
	
	// Array to collect information about each job in redcap_crons table
	private $jobInfo = array();
	
	// Array to collect the specific jobs available to be done that are pre-defined in the Jobs class
	private $jobsDefined = null;
	
	// Array to collect the specific custom non-REDCap jobs defined in the cron table
	private $jobsDefinedCustom = null;
	
	// Constructor
	public function __construct() 
	{
		// Store in array $jobsDefined all jobs pre-defined in this class
		$this->getAllJobsDefined();
		// Store in array all jobs listed in redcap_crons table
		$this->getJobInfo();
	}
	
	// Check if any crons have began running in the past X seconds (return boolean)
	// Default check time = one hour
	static function checkIfCronsActive($seconds=3600)
	{
		// Make sure $seconds is integer
		if (!is_numeric($seconds)) return false;
		// Query cron tables
		$sql = "select 1 from (select max(h.cron_run_start) as last_run 
				from redcap_crons c, redcap_crons_history h where c.cron_id = h.cron_id and c.cron_enabled = 'ENABLED') x 
				where last_run >= DATE_SUB('".NOW."', INTERVAL $seconds SECOND)";
		$q = db_query($sql);
		// Return true if any crons have run in the past X seconds
		return (db_num_rows($q) > 0);
	}
	
	// Get timestamp of the last cron's start time (will return NULL if no crons have ever been run)
	static function getLastCronStartTime()
	{
		$sql = "select max(h.cron_run_start) from redcap_crons c, redcap_crons_history h 
				where c.cron_id = h.cron_id and c.cron_enabled = 'ENABLED'";
		$q = db_query($sql);
		// Return true if any crons have run in the past X seconds
		return db_result($q, 0);
	
	}
	
	// Return the error message to display to REDCap admin if crons aren't running as they should
	static function cronsNotRunningErrorMsg()
	{
		global $lang;
		return	RCView::div(array('class'=>'red','style'=>'margin-top:10px;font-family:arial;'), 
					RCView::img(array('src'=>'exclamation.png','class'=>'imgfix')) . 
					RCView::b($lang['control_center_288']) . RCView::br() . $lang['control_center_289'] . RCView::br() . RCView::br() . 
					RCView::a(array('href'=>'javascript:;','style'=>'font-family:arial;','onclick'=>"window.location.href=app_path_webroot+'ControlCenter/cron_jobs.php';"), $lang['control_center_290'])
				);
	}
	
	// Determine which jobs have been pre-defined within the Jobs class
	private function getAllJobsDefined()
	{
		// Inititalize $jobsDefined as array
		$this->jobsDefined = array();
		// Loop through all methods in Jobs class to find the pre-defined jobs
		foreach (get_class_methods('Jobs') as $thisMethod)
		{
			$this->jobsDefined[] = $thisMethod;
		}
		// Also set any custom non-REDCap jobs that have been added to the cron table
		$this->getCustomJobs();
	}
	
	// Get any custom non-REDCap jobs that have been added to the cron table
	private function getCustomJobs()
	{
		// Get custom job list from table (will have a URL defined)
		$this->jobsDefinedCustom = array();
		// Query redcap_crons table to get a little of available jobs to
		$sql = "select cron_name, cron_external_url from redcap_crons where cron_external_url is not null
				and cron_name not in ('" . implode("', '", $this->jobsDefined) . "')";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q))
		{
			// Add cron name as key and URL as value
			$this->jobsDefinedCustom[$row['cron_name']] = trim($row['cron_external_url']);			
		}
		// Return custom jobs array
		return $this->jobsDefinedCustom;
	}
	
	// Set the jobs to be done
	private function setJobList() 
	{
		// Set array of potential jobs (will be verified afterward)
		$potentialJobs = array();
		// Query redcap_crons table to determine which jobs to run (always run the job if cron_instances_max > 1)
		$sql = "select cron_name from redcap_crons where cron_enabled = 'ENABLED' 
				and cron_instances_current < cron_instances_max 
				and (cron_instances_max > 1 
					or cron_last_run_start is null 
					or DATE_ADD(cron_last_run_start, INTERVAL cron_frequency SECOND) <= '".NOW."')
				order by cron_id";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q))
		{
			$potentialJobs[] = $row['cron_name'];			
		}
		// Validate that all jobs defined by user are real jobs
		$this->jobList = $this->validateJobs($potentialJobs);
	}
	
	// Validate the names of jobs as submitted in a comma-delimited list
	private function validateJobs($potentialJobs) 
	{
		// Store validated jobs in array
		$validatedJobs = array();
		// Loop through the delimited list
		foreach ($potentialJobs as $thisJob)
		{
			if (in_array($thisJob, array_merge($this->jobsDefined, array_keys($this->jobsDefinedCustom)))) {
				// Add to list of validated jobs
				$validatedJobs[] = $thisJob;
			}
		}
		// Return array of validated job names
		return $validatedJobs;
	}
	
	// Get which jobs are to be done
	private function getJobs()
	{
		return $this->jobList;
	}
	
	// Returns job info for ALL jobs in redcap_crons table
	private function getJobInfo()
	{
		// Query redcap_crons table to get info about available jobs
		$sql = "select * from redcap_crons order by cron_id";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q)) 
		{
			// Remove name element to make it as keep for this subarray
			$cron_name = $row['cron_name'];
			unset($row['cron_name']);
			// Add to array
			$this->jobInfo[$cron_name] = $row;
		}
	}
	
	// Log the start of a job
	private function logCronStart($thisJob)
	{
		$right_now = date('Y-m-d H:i:s');
		// Change cron status to Processing
		$sql = "update redcap_crons set cron_last_run_start = '$right_now', cron_last_run_end = null,
				cron_instances_current = cron_instances_current + 1 where cron_name = '".prep($thisJob)."'";
		$q = db_query($sql);
		// Insert row into crons_history table to log this single event
		$sql = "insert into redcap_crons_history (cron_id, cron_run_start, cron_run_status) values
				({$this->jobInfo[$thisJob]['cron_id']}, '$right_now', 'PROCESSING')";
		$q = db_query($sql);
		// Return teh primary key value
		return db_insert_id();
	}
	
	// Log the end of a job
	private function logCronEnd($thisJob, $ch_id)
	{
		$right_now = date('Y-m-d H:i:s');
		// Change cron status to Processing
		$sql = "update redcap_crons set cron_last_run_end = '$right_now',
				cron_instances_current = if(cron_instances_current > 0, cron_instances_current - 1, 0) 
				where cron_name = '".prep($thisJob)."'";
		$q = db_query($sql);
		// Insert row into crons_history table to log this single event
		$sql = "update redcap_crons_history set cron_run_status = 'COMPLETED', cron_run_end = '$right_now' 
				where ch_id = $ch_id";
		$q = db_query($sql);
	}
	
	// Set the return info of a specific job that was executed
	private function setJobReturnMsg($ch_id, $redcapCronJobReturnMsg)
	{
		$sql = "update redcap_crons_history set cron_info = ".checkNull($redcapCronJobReturnMsg)." 
				where ch_id = $ch_id";
		$q = db_query($sql);
	}
	
	// Make sure that cron_instances_current count in redcap_crons table matches up with how many are actually running at that moment.
	// If any stalled and exceeded max run time, then ignore them (not possible to determine if they're still running anyway). 
	private function checkCronInstances()
	{
		// Loop through each job to get count of current instances
		foreach ($this->jobInfo as $jobName=>$jobAttr) 
		{
			// Only check those jobs that are enabled
			if ($jobAttr['cron_enabled'] == 'DISABLED') continue;
			// Get count of instances that have NOT exceeded max_run_time yet
			$sql = "select count(1) from redcap_crons_history
					where cron_id = {$jobAttr['cron_id']} and cron_run_status = 'PROCESSING' 
					and DATE_ADD(cron_run_start, INTERVAL {$jobAttr['cron_max_run_time']} SECOND) >= '".NOW."'
					and cron_run_end is null limit {$jobAttr['cron_instances_max']}";
			$q = db_query($sql);
			$num_current_instances = ($q && db_num_rows($q) > 0) ? db_result($q, 0) : 0;
			// If count of current instances running does not match the count in the crons table, then fix value in crons table.
			// Also reset start/end time of last job to NULL, otherwise it could cause issues when determining when to run next.
			if ($num_current_instances != $jobAttr['cron_instances_current']) 
			{
				$sql = "update redcap_crons set cron_instances_current = $num_current_instances
						where cron_id = {$jobAttr['cron_id']}";
				db_query($sql);
			}
		}
	}
	
	// Execute all jobs that have been set
	public function execute() 
	{
		// Set global variable to catch return messages
		global $redcapCronJobReturnMsg;
		// First, check and fix any crons that have stalled (i.e. exceeded max run time)
		$this->checkCronInstances();
		// Validate and set all jobs to be run
		$this->setJobList();
		// Instatiate the Jobs class
		$Jobs = new Jobs();
		// Get jobs list
		$jobsList = $this->getJobs();
		// Output text for beginning the execution of the jobs
		print "Executing " . count($jobsList) . " jobs: "; 
		// Counter
		$numCron = 0;
		// In order to call each job, we must eval it
		foreach ($jobsList as $thisJob)
		{
			// Set inital value of job's return message as null
			$redcapCronJobReturnMsg = null;
			// Give message of success when done
			print "\r\n" . ++$numCron . ") $thisJob -> ";
			// Set cron_run_time_start
			$ch_id = $this->logCronStart($thisJob);
			// Set default return message
			$returnMsg = "done!";
			// Run the job
			if (isset($this->jobsDefinedCustom[$thisJob])) {
				// Custom non-REDCap job, so simply call URL with http_get
				$redcapCronJobReturnMsg = http_get($this->jobsDefinedCustom[$thisJob]);
			} else {
				// Eval the method
				$evalString = '$Jobs->' . $thisJob . '();';
				eval($evalString);
			}
			// If job sets a return message, then add it to crons_history table
			if ($redcapCronJobReturnMsg != null && $redcapCronJobReturnMsg != "") {
				$this->setJobReturnMsg($ch_id, $redcapCronJobReturnMsg);
			}
			// Set cron_run_time_start
			$this->logCronEnd($thisJob, $ch_id);
			// Give message of success when done
			print $returnMsg;
		}
		print "\r\nCompleted all jobs!";
	}	
	
}
