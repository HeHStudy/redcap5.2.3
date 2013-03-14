<?php

/**
 * MATHEMATICAL FUNCTIONS
 */
 

// Definitions used by these functions (in case we aren't including bootstrap.php - i.e. for unit testing)
defined("NOW") 	 or define("NOW", date('Y-m-d H:i:s'));
defined("TODAY") or define("TODAY", date('Y-m-d'));
defined("today") or define("today", TODAY); // The lower-case version of the TODAY constant allows for use in Data Quality rules (e.g. datediff)


 
// Determine if value is a number. If user uses is_number instead of is_numeric.
function isnumber($val)
{
	return is_numeric(trim($val));
}

// Determine if value is an integer
function isinteger($val)
{
	$val = trim($val);
	$regex = "/^[-+]?\b\d+\b$/";
	return ($val == (int)$val && preg_match($regex, $val));
}
 
// Round numbers up to a given decimal point
function roundup($number=null,$precision=0) 
{
	if ($number == null) return false;	
	$factor = pow(10, -1 * $precision);
	return ceil($number / $factor) * $factor;
}
	
// Round numbers down to a given decimal point
function rounddown($number=null,$precision=0) 
{
	if ($number == null) return false;	
	$factor = pow(10, -1 * $precision);
	return floor($number / $factor) * $factor;
}

// Find sum of numbers (each used as parameter)
function sum()
{
	$arg_list = func_get_args();
	foreach ($arg_list as $argnum=>$arg)
	{
		// Trim it first
		$arg_list[$argnum] = $arg = trim($arg);
		// Make sure it's a number, else remove it
		if (!is_numeric($arg)) unset($arg_list[$argnum]);
	}
	return array_sum($arg_list);
}

// Find mean/average of numbers (each used as parameter)
function mean()
{
	$arg_list = func_get_args();
	foreach ($arg_list as $argnum=>$arg)
	{
		// Trim it first
		$arg_list[$argnum] = $arg = trim($arg);
		// Make sure it's a number, else remove it
		if (!is_numeric($arg)) unset($arg_list[$argnum]);
	}
	return array_sum($arg_list) / count($arg_list);
}

/**
 * Median
 * number median ( number arg1, number arg2 [, number ...] )
 * number median ( array numbers )
 */ 
function median()
{
    $args = func_get_args();
    switch (func_num_args())
    {
        case 0:
            //trigger_error('median() requires at least one parameter',E_USER_WARNING);
            return false;
            break;
        case 1:
            $args = array_pop($args);
            // fallthrough
        default:
            if(!is_array($args)) {
                //trigger_error('median() requires a list of numbers to operate on or an array of numbers',E_USER_NOTICE);
                return false;
            }
			// Make sure all are numbers
			foreach ($args as $argnum=>$arg)
			{
				// Trim it first
				$args[$argnum] = $arg = trim($arg);
				// Make sure it's a number, else remove it
				if (!is_numeric($arg)) unset($args[$argnum]);
			}
			// Sort the args
            sort($args);
            $n = count($args);
            $h = intval($n / 2);
			// Determine the median
            if($n % 2 == 0) { 
                $median = ($args[$h] + $args[$h-1]) / 2; 
            } else { 
                $median = $args[$h]; 
            }
            break;
    }    
    return $median;
}

// Calculate standard deviation from an array of numerical values
function stdev()  
{ 
	$std = func_get_args();
	switch (func_num_args())
    {
        case 0:
            //trigger_error('median() requires at least one parameter',E_USER_WARNING);
            return false;
            break;
        case 1:
            $std = array_pop($std);
            // fallthrough
        default:
            if(!is_array($std)) {
                return false;
            }
			// Make sure all are numbers
			foreach ($std as $argnum=>$arg)
			{
				// Trim it first
				$std[$argnum] = $arg = trim($arg);
				// Make sure it's a number, else remove it
				if (!is_numeric($arg)) unset($std[$argnum]);
			}
			sort($std);
			$total = 0;
			// Count array elements
			$count_std = count($std);
			while(list($key,$val) = each($std)) 
			{
				$total += $val; 
			} 
			reset($std); 
			$mean = $total/$count_std;	
			$sum = 0;
			while(list($key,$val) = each($std)) 
			{  
				$sum += pow(($val-$mean),2); 
			}  
			$var = sqrt($sum/($count_std-1)); 
			return $var; 
            break;
    }  
} 

// Calculate the percentile of numerical array (array must already be numerically sorted)
function percentile($data, $p) 
{
	$count = count($data);

	$obsidx = $p * ($count + 1) / 100;

	if (intval($obsidx) == $obsidx) {

		return $data[($obsidx - 1)];

	} elseif ($obsidx < 1) {

		return $data[0];

	} elseif ($obsidx > $count) {

		return $data[($count - 1)];

	} else {

		$left = floor($obsidx - 1);

		$right = ceil($obsidx - 1);

		return ($data[$left] + $data[$right]) / 2;

	}

}




// Date Differencing Functions
function datediff($d1,$d2,$unit=null,$returnSigned=false) 
{
	// Make sure Units are provided
	if ($unit == null) {
		throw new Exception;
	}
	// Initialize parameters first
	$d1isToday = ($d1 === "today" || $d1 == TODAY);
	$returnSigned = ($returnSigned === true || $returnSigned === 'true') ? true : false;
	// Determine data type of field ("date", "time", "datetime", or "datetime_seconds")
	$format_checkfield = ($d1isToday ? $d2 : $d1);
	$numcolons = substr_count($format_checkfield, ":");
	if ($numcolons == 1) {
		if (strpos($format_checkfield, "-") !== false) {
			$datatype = "datetime";
		} else {
			$datatype = "time";
		}
	} else if ($numcolons > 1) {
		$datatype = "datetime_seconds";
	} else {
		$datatype = "date";
	}
	// TIME
	if ($datatype == "time") {
		// Return in specified units
		return secondDiff(timeToSeconds($d1),timeToSeconds($d2),$unit,$returnSigned);
	}
	// If using 'today' for either date, then set format accordingly
	if ($d1isToday) {
		if ($datatype == "date") {
			$d1 = TODAY;
		} elseif ($datatype == "datetime") {
			$d1 = TODAY." 00:00";
		} elseif ($datatype == "datetime_seconds") {
			$d1 = TODAY." 00:00:00";
		}
	} else if ($d2 === "today" || $d2 == TODAY) {
		if ($datatype == "date") {
			$d2 = TODAY;
		} elseif ($datatype == "datetime") {
			$d2 = TODAY." 00:00";
		} elseif ($datatype == "datetime_seconds") {
			$d2 = TODAY." 00:00:00";
		}
	}
	// If a date[time][_seconds] field, then ensure it has dashes
	if (substr($datatype, 0, 4) == "date" && (strpos($d1, "-") === false || strpos($d2, "-") === false)) {
		throw new Exception;
	}
	// Make sure the date/time values aren't empty
	if ($d1 == "" || $d2 == "" || $d1 == null || $d2 == null) throw new Exception;
	// DATE, DATETIME, or DATETIME_SECONDS
	$d1sec = 0;
	$d2sec = 0;
	// Separate time if datetime or datetime_seconds
	if ($datatype != "date") {
		$d1b = explode(" ", $d1);
		$d2b = explode(" ", $d2);
		// Split into date and time (in seconds)
		$d1 = $d1b[0];
		$d2 = $d2b[0];
		$d1sec = timeToSeconds($d1b[1]);
		$d2sec = timeToSeconds($d2b[1]);
	}
	$dt1 = explode("-", $d1);
	$dt2 = explode("-", $d2);
	// Convert the dates to seconds (conversion varies due to dateformat)
	$dat1 = mktime(0,0,0,$dt1[1],$dt1[2],$dt1[0]) + $d1sec;
	$dat2 = mktime(0,0,0,$dt2[1],$dt2[2],$dt2[0]) + $d2sec;
	// Get the difference in seconds
	$sec = $dat2 - $dat1;
	if (!$returnSigned) $sec = abs($sec);
	// Return in specified units
	if ($unit == "s") {
		return $sec;
	} else if ($unit == "m") {	
		return $sec/60;
	} else if ($unit == "h") {	
		return $sec/3600;
	} else if ($unit == "d") {	
		return ($datatype == "date" ? round($sec/86400) : $sec/86400);
	} else if ($unit == "M") {
		return $sec/2630016; // Use 1 month = 30.44 days
	} else if ($unit == "y") {
		return $sec/31556952; // Use 1 year = 365.2425 days
	}
	throw new Exception;
}
// Convert military time to seconds (i.e. number of seconds since midnight)
function timeToSeconds($time) {
	if (strpos($time, ":") === false) throw new Exception;
	$timearray = explode(":", $time);
	return ($timearray[0]*3600) + ($timearray[1]*60) + (!isset($timearray[2]) ? 0 : $timearray[2]*1);
}
// Return the difference of two number values in desired units converted from seconds
function secondDiff($time1,$time2,$unit,$returnSigned) {
	$sec = $time2-$time1;
	if (!$returnSigned) $sec = abs($sec);
	// Return in specified units
	if ($unit == "s") {
		return $sec;
	} else if ($unit == "m") {	
		return $sec/60;
	} else if ($unit == "h") {	
		return $sec/3600;
	} else if ($unit == "d") {	
		return $sec/86400;
	} else if ($unit == "M") {
		return $sec/2630016; // Use 1 month = 30.44 days
	} else if ($unit == "y") {
		return $sec/31556952; // Use 1 year = 365.2425 days
	}
	throw new Exception;
}