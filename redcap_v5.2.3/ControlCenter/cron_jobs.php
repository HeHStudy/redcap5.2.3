<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

// Header
include 'header.php';

// Convert phpinfo HTML table into array
function phpinfo_array()
{
    ob_start();
    phpinfo();
    $info_arr = array();
    $info_lines = explode("\n", strip_tags(ob_get_clean(), "<tr><td><h2>"));
    $cat = "General";
    foreach($info_lines as $line)
    {
        // new cat?
        preg_match("~<h2>(.*)</h2>~", $line, $title) ? $cat = $title[1] : null;
        if(preg_match("~<tr><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td></tr>~", $line, $val))
        {
            $info_arr[trim($cat)][trim($val[1])] = trim($val[2]);
        }
        elseif(preg_match("~<tr><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td></tr>~", $line, $val))
        {
            $info_arr[trim($cat)][trim($val[1])] = array("local" => trim($val[2]), "master" => trim($val[3]));
        }
    }
    return $info_arr;
}

// Searches a directory (and all sub-directories) for a specific filename
function searchForFile($dir, $searchfile)
{
	// Trim $dir and make sure it ends with directory separator
	$dir = rtrim(trim($dir), DS) . DS;
	// Loop through all files and subdirectories
	foreach (getDirFiles($dir) as $thisFileOrDir) 
	{
		// Set full path of file/dir we're looking at
		$fullPath = $dir.$thisFileOrDir;
		// If it's a file, check if it's the one we're looking for
		if (isFile($fullPath)) {
			// Found the file! Return the current directory.
			if ($thisFileOrDir == $searchfile) return $dir;
		}
		// If it's a directory, then recursively check all files in that subdirectory
		elseif (is_dir($fullPath)) {
			// Get return value for this directory
			$returnedDir = searchForFile($fullPath, $searchfile);
			// If returned a filename and it matches teh search file, return the returned directory
			if ($returnedDir !== false) return $returnedDir;
		}
	}
	// If didn't find it, return false
	return false;
}

// Check if really a file on web server
function isFile($file=null)
{
	return (!empty($file) && file_exists($file) && is_file($file));
}

// Use various methods to get path of PHP executable
function getPhpExecutable()
{
	global $isWindowsServer;
	// Set filename of actual php executable file
	$phpexe = "php" . ($isWindowsServer ? ".exe" : "");
	// LINUX ONLY (use "exec")
	if (!$isWindowsServer) 
	{
		// Use "which php"
		$php_executable = @exec("which php");
		if (isFile($php_executable)) return $php_executable;
		// Gets the PID of the current executable
		if (function_exists('posix_getpid')) {
			$pid = @posix_getpid();
			$php_executable = @exec("readlink -f /proc/$pid/exe");
			if (isFile($php_executable)) return $php_executable;
		}
	}
	// Try paths found in PATH
	foreach (explode(PATH_SEPARATOR, getenv('PATH')) as $path) {
		//print "CHECK:  $php_executable<br>";
		$php_executable = trim($path) . DS . $phpexe;
		if (isFile($php_executable)) return $php_executable;
	}
	// Use extension directory location to find it
	$php_executable = str_replace(DS . 'ext', '', ini_get('extension_dir')) . DS . $phpexe;
	if (isFile($php_executable)) return $php_executable;
	// Search directory containing PHP.INI (use "loaded config file" location from phpinfo()) 
	$phpinfo_array = phpinfo_array();
	if (isset($phpinfo_array['General']['Loaded Configuration File'])) {
		// First try in this same directory
		$php_executable = dirname($phpinfo_array['General']['Loaded Configuration File']) . DS . $phpexe;
		if (isFile($php_executable)) return $php_executable;
		// Now try all subdirectories
		$php_ini_dir = dirname($phpinfo_array['General']['Loaded Configuration File']);
		$php_executable = searchForFile($php_ini_dir, $phpexe) . $phpexe;
		if (isFile($php_executable)) return $php_executable;
	}
	// Try ALL paths listed in phpinfo that contain "/php"
	$pathsPhpInfo = array();
	foreach ($phpinfo_array as $array) {
		foreach ($array as $val) {
			// If we found a match for "/php"
			if (!is_array($val) && strpos($val, DS."php") !== false) {
				// Trim the value
				$val = trim(label_decode($val));
				// Check if value is a file
				if (isFile($val)) {
					$pathsPhpInfo[] = dirname($val);
				} 
				// Check if a directory
				elseif (is_dir($val)) {
					$pathsPhpInfo[] = $val;
				}
				// Check if has PATH_SEPARATOR. If so, break up into chunks to find filenames/dirs
				// elseif (strpos($val, PATH_SEPARATOR) !== false) {
					// foreach (explode(PATH_SEPARATOR, $val) as $valsub) {
						// $valsub = trim($valsub);						
						// print "<br> - $valsub";
						// preg_match('/\/.*\/([^\/]+)/', $valsub, $matches);
						// print "<br> - MATCH: ".htmlspecialchars(str_replace('"', '', $matches[0]), ENT_QUOTES);
						
						// if (isFile($valsub)) {
							// $pathsPhpInfo[] = dirname($valsub);
						// } elseif (is_dir($valsub)) {
							// $pathsPhpInfo[] = $valsub;
						// } 
					// }
				// }
			}
		}
	}
	// Loop through all directories collected that are listed in phpinfo that contain "/php" and search ALL their subdirectories for PHP executable file
	$pathsParentPhpInfo = array();
	foreach (array_unique($pathsPhpInfo) as $path) 
	{
		$php_executable = searchForFile($path, $phpexe) . $phpexe;
		if (isFile($php_executable)) return $php_executable;
		$pathsParentPhpInfo[] = dirname($path);
	}
	// If didn't find it in $pathsPhpInfo, now try parent folders of each in $pathsPhpInfo
	foreach (array_unique($pathsParentPhpInfo) as $path) 
	{
		$php_executable = searchForFile($path, $phpexe) . $phpexe;
		if (isFile($php_executable)) return $php_executable;
	}
	// Couldn't find it
	return false;
}


// PHP file path to be called by the cron job
$cronFilePath = dirname(APP_PATH_DOCROOT) . DIRECTORY_SEPARATOR . 'cron.php';
// Get the URL that the cron job should use for calling the PubMed web service
$cronUrl = APP_PATH_WEBROOT_FULL . "cron.php";

## Generate contents for cron job file
// Desperately try to find the path of the php executable file (i.e. php.exe)
$php_executable = getPhpExecutable();
// If couldn't find php executable, then give path with question marks for admins to fill in, and
// also give note that admin needs to determine its path.
$php_executable_not_found_text = "";
if ($php_executable === false) {
	$php_executable = DS . "???" . DS . "???" . DS . "php" . ($isWindowsServer ? ".exe" : "");
	$php_executable_not_found_text = "<div style='padding:0 0 10px;color:#800000;'><b>{$lang['global_02']}{$lang['colon']}</b> 
		{$lang['control_center_291']} \"$php_executable\" {$lang['control_center_292']}</div>";
}
// Generate Windows command to set cron
$scheduleCronWindows = 'schtasks /create /tn "REDCap Cron Job" /tr "'.$php_executable.' '.$cronFilePath.'" /sc MINUTE /ru SYSTEM';
// Generate Linux/Unix command to set cron
$scheduleCronLinux = "# REDCap Cron Job (runs every minute)\n* * * * * $php_executable $cronFilePath > /dev/null";
// Last start time of most recently run cron job, and format its text
$lastStartTime = Cron::getLastCronStartTime();
$lastStartTime = (empty($lastStartTime)) ? RCView::span(array('style'=>'color:red;'), $lang['dashboard_54']) : format_ts_mysql($lastStartTime);
// Check if cron has run in the past hour
$cronStatusText = (Cron::checkIfCronsActive()) ? RCView::span(array('style'=>'font-size:14px;color:green;font-weight:bold;'), $lang['control_center_312']) : RCView::span(array('style'=>'color:red;'), "<b style='font-size:14px;'>{$lang['control_center_313']}</b> {$lang['control_center_314']}");

?>
<!-- Page title and instructions -->
<h3 style="margin-top: 0;"><?php echo $lang['control_center_285'] ?></h3>
<p><?php echo $lang['control_center_286'] ?></p>


<!-- Check if cron.php exists -->
<?php 
if (!file_exists($cronFilePath)) 
{ 
	?>
	<div class="red" style="font-family:arial;margin:20px 0;">
		<b><?php echo $lang['global_01'] . $lang['colon'] ?></b> <?php echo $lang['control_center_293'] ?>  
		<a href="https://iwg.devguard.com/trac/redcap/browser/tags_redcap/4.12.0/cron.php?format=raw" style="font-family:arial;"><?php echo $lang['control_center_294'] ?></a>. 
		<?php echo $lang['control_center_295'] ?> <b><?php echo dirname(APP_PATH_DOCROOT).DS ?></b> <?php echo $lang['control_center_316'] ?>
	</div>
	<br/>
	<?php
	// Footer
	include 'footer.php';
	exit;
} 
?>


<!-- Display time last cron -->
<div id="" style="margin:20px 0;">
	<div style="font-size:14px;color:#800000;font-weight:bold;">
		<?php echo $lang['control_center_306'] ?>
	</div>
	<?php echo $lang['control_center_308'] ?> <b style="font-size:14px;"><?php echo ($isWindowsServer ? $lang['control_center_309'] : $lang['control_center_310'] ) ?></b><br>
	<?php echo $lang['control_center_307'] ?> <b style="font-size:14px;"><?php echo $lastStartTime ?></b><br>
	<?php echo $lang['control_center_311'] ?> <?php echo $cronStatusText ?><br>
</div>


<!-- Set up cron on Windows -->
<div id="cronWindows" style="<?php if (!$isWindowsServer) echo "display:none;" ?>border: 1px solid #ccc; background-color: #f0f0f0;padding:10px;margin:20px 0;">
	<div style="font-size:14px;color:#800000;font-weight:bold;">
		<?php echo $lang['control_center_297'] ?>
	</div>
	<div style="padding:10px 0;">
		<?php echo $lang['control_center_298'] ?>
		<i style="color:#666;font-family:verdana;font-size:11px;">[/u [domain\]user /p password]] [/ru {[Domain\]User | "System"} [/rp Password]]</i><?php echo $lang['period'] ?>
		<?php echo $lang['control_center_299'] ?> <a style="text-decoration:underline;" href="http://support.microsoft.com/kb/814596"><?php echo $lang['control_center_300'] ?></a><?php echo $lang['period'] ?>
	</div>
	<?php echo $php_executable_not_found_text ?>
	<b><?php echo $lang['control_center_301'] ?></b>
	<textarea style="color:#444;font-size:11px;width:500px;height:40px;" onclick="this.select();" readonly><?php echo $scheduleCronWindows ?></textarea>
</div>
	

<!-- Set up cron on Linux/Unix -->
<div id="cronLinux" style="<?php if ($isWindowsServer) echo "display:none;" ?>border: 1px solid #ccc; background-color: #f0f0f0;padding:10px;margin:20px 0;">
	<div style="font-size:14px;color:#800000;font-weight:bold;">
		<?php echo $lang['control_center_302'] ?>
	</div>
	<div style="padding:10px 0;"><?php echo $lang['control_center_303'] ?></div>
	<?php echo $php_executable_not_found_text ?>
	<b><?php echo $lang['control_center_304'] ?></b>
	<textarea style="color:#444;font-size:11px;width:500px;height:40px;" onclick="this.select();" readonly><?php echo $scheduleCronLinux ?></textarea>
</div>
	

<!-- Link to simulate cron in web browser -->
<div style="border: 1px solid #ccc; background-color: #f0f0f0;padding:10px;margin:20px 0;">
	<div style="font-size:14px;color:#800000;font-weight:bold;">
		<?php echo $lang['control_center_305'] ?>
	</div>
	<div style="padding:10px 0;">
		<?php echo $lang['control_center_214'] ?> 
		<a href="<?php echo $cronUrl ?>" target="_blank" style="text-decoration:underline;"><?php echo $cronUrl ?></a>
	</div>
</div>
<?php 


// Footer
include 'footer.php';
