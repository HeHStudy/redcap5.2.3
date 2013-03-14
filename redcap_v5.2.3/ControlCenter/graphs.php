<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

include 'header.php';

/**
 * Render graphs
 */

// Past week
if (!isset($_GET['plottime']) || $_GET['plottime'] == "1w" || $_GET['plottime'] == "") {
	if (!isset($_GET['plottime'])) $_GET['plottime'] = "1w";
	$date_label = $lang['dashboard_07'];
// Past day
} elseif ($_GET['plottime'] == "1d") {
	$date_label = $lang['dashboard_89'];
// Past month
} elseif ($_GET['plottime'] == "1m") {
	$date_label = $lang['dashboard_08'];
// Past three months
} elseif ($_GET['plottime'] == "3m") {
	$date_label = $lang['dashboard_09'];
// Past six months
} elseif ($_GET['plottime'] == "6m") {
	$date_label = $lang['dashboard_10'];
// Past year
} elseif ($_GET['plottime'] == "12m") {
	$date_label = $lang['dashboard_11'];
// All
} elseif ($_GET['plottime'] == "all") {
	$date_label = $lang['dashboard_12'];
}

// Select time interval for plots
print  "<div id='plots' style='padding-top:0px;padding-bottom:30px;'>
		<div style='font-size:14px;font-weight:bold;padding-bottom:4px;'>{$lang['dashboard_06']}</div>";
if ($_GET['plottime'] == "1d") print $lang['dashboard_89']; else print "<a href='{$_SERVER['PHP_SELF']}?plottime=1d' style='text-decoration:underline;font-size:12px;'>{$lang['dashboard_89']}</a>";
print  "&nbsp;|&nbsp; ";
if ($_GET['plottime'] == "1w") print $lang['dashboard_07']; else print "<a href='{$_SERVER['PHP_SELF']}?plottime=1w' style='text-decoration:underline;font-size:12px;'>{$lang['dashboard_07']}</a>";
print  "&nbsp;|&nbsp; ";
if ($_GET['plottime'] == "1m") print $lang['dashboard_08']; else print "<a href='{$_SERVER['PHP_SELF']}?plottime=1m' style='text-decoration:underline;font-size:12px;'>{$lang['dashboard_08']}</a>";
print  " &nbsp;|&nbsp; ";
if ($_GET['plottime'] == "3m") print $lang['dashboard_09']; else print "<a href='{$_SERVER['PHP_SELF']}?plottime=3m' style='text-decoration:underline;font-size:12px;'>{$lang['dashboard_09']}</a>";
print  " &nbsp;|&nbsp; ";
if ($_GET['plottime'] == "6m") print $lang['dashboard_10']; else print "<a href='{$_SERVER['PHP_SELF']}?plottime=6m' style='text-decoration:underline;font-size:12px;'>{$lang['dashboard_10']}</a>";
print  " &nbsp;|&nbsp; ";
if ($_GET['plottime'] == "12m") print $lang['dashboard_11']; else print "<a href='{$_SERVER['PHP_SELF']}?plottime=12m' style='text-decoration:underline;font-size:12px;'>{$lang['dashboard_11']}</a>";
print  "&nbsp;|&nbsp; ";
if ($_GET['plottime'] == "all") print $lang['dashboard_12']; else print "<a href='{$_SERVER['PHP_SELF']}?plottime=all' style='text-decoration:underline;font-size:12px;'>{$lang['dashboard_12']}</a>";
print  "</div>";

// Concurrent Users
print  '<div style="width:500px;border:1px solid #000000;background-color:#f7f7f7;margin-bottom:30px;">
			<div style="text-align:center;font-weight:bold;font-size:14px;padding:5px;">
				'.$lang['dashboard_87'].'
				<span style="font-size:11px;font-weight:normal;color:#555">&nbsp;(' . $date_label . ')</span>
			</div>
			<div id="chart7" style="width:500px;height:250px;">
				&nbsp;&nbsp;
				<img src="' . APP_PATH_IMAGES . 'progress_circle.gif" class="imgfix">
				'.$lang['dashboard_14'].'...
			</div>
		</div>';

// Projects Created
print  '<div style="width:500px;border:1px solid #000000;background-color:#f7f7f7;margin-bottom:30px;">
			<div style="text-align:center;font-weight:bold;font-size:14px;padding:5px;">
				'.$lang['dashboard_15'].'
				<span style="font-size:11px;font-weight:normal;color:#555">&nbsp;(' . $date_label . ')</span>
			</div>
			<div id="chart4" style="width:500px;height:250px;">
				&nbsp;&nbsp;
				<img src="' . APP_PATH_IMAGES . 'progress_circle.gif" class="imgfix">
				'.$lang['dashboard_14'].'...
			</div>
		</div>';
		
// Projects Moved to Production
print  '<div style="width:500px;border:1px solid #000000;background-color:#f7f7f7;margin-bottom:30px;">
			<div style="text-align:center;font-weight:bold;font-size:14px;padding:5px;">
				'.$lang['dashboard_88'].'
				<span style="font-size:11px;font-weight:normal;color:#555">&nbsp;(' . $date_label . ')</span>
			</div>
			<div id="chart8" style="width:500px;height:250px;">
				&nbsp;&nbsp;
				<img src="' . APP_PATH_IMAGES . 'progress_circle.gif" class="imgfix">
				'.$lang['dashboard_14'].'...
			</div>
		</div>';

// Active Users
print  '<div style="width:500px;border:1px solid #000000;background-color:#f7f7f7;margin-bottom:30px;">
			<div style="text-align:center;font-weight:bold;font-size:14px;padding:5px;">
				'.$lang['dashboard_17'].'
				<span style="font-size:11px;font-weight:normal;color:#555">&nbsp;(' . $date_label . ')</span>
			</div>
			<div id="chart5" style="width:500px;height:250px;">
				&nbsp;&nbsp;
				<img src="' . APP_PATH_IMAGES . 'progress_circle.gif" class="imgfix">
				'.$lang['dashboard_14'].'...
			</div>
		</div>';

// First time accessing REDCap
print  '<div style="width:500px;border:1px solid #000000;background-color:#f7f7f7;margin-bottom:30px;">
			<div style="text-align:center;font-weight:bold;font-size:14px;padding:5px;">
				'.$lang['dashboard_18'].'
				<span style="font-size:11px;font-weight:normal;color:#555">&nbsp;(' . $date_label . ')</span>
			</div>
			<div id="chart6" style="width:500px;height:250px;">
				&nbsp;&nbsp;
				<img src="' . APP_PATH_IMAGES . 'progress_circle.gif" class="imgfix">
				'.$lang['dashboard_14'].'...
			</div>
		</div>';

// Logged Events
print  '<div style="width:500px;border:1px solid #000000;background-color:#f7f7f7;margin-bottom:30px;">
			<div style="text-align:center;font-weight:bold;font-size:14px;padding:5px;">
				'.$lang['dashboard_16'].'
				<span style="font-size:11px;font-weight:normal;color:#555">&nbsp;(' . $date_label . ')</span>
			</div>
			<div id="chart2" style="width:500px;height:250px;">
				&nbsp;&nbsp;
				<img src="' . APP_PATH_IMAGES . 'progress_circle.gif" class="imgfix">
				'.$lang['dashboard_14'].'...
			</div>
		</div>';
		
// Page Hits
print  '<div style="width:500px;border:1px solid #000000;background-color:#f7f7f7;margin-bottom:30px;">
			<div style="text-align:center;font-weight:bold;font-size:14px;padding:5px;">
				'.$lang['dashboard_13'].'
				<span style="font-size:11px;font-weight:normal;color:#555">&nbsp;(' . $date_label . ')</span>
			</div>
			<div id="chart1" style="width:500px;height:250px;">
				&nbsp;&nbsp;
				<img src="' . APP_PATH_IMAGES . 'progress_circle.gif" class="imgfix">
				'.$lang['dashboard_14'].'...
			</div>
		</div>';

// AJAX requests to load the stats table and graphs (much faster than if running inline)
?>
<script type="text/javascript">
$(function() {
	// Chain all ajax events so that they are fired sequentially
	var ccstats  = app_path_webroot + 'ControlCenter/stats_ajax.php';
	var plottime = getParameterByName('plottime');
	// Chart 4
	$.get(ccstats, { plottime: plottime, chartid: 'chart4'}, function(data) { $('#chart4').html(''); eval(data);
		// Chart 8
		$.get(ccstats, { plottime: plottime, chartid: 'chart8'}, function(data) { $('#chart8').html(''); eval(data);
			// Chart 1
			$.get(ccstats, { plottime: plottime, chartid: 'chart1'}, function(data) { $('#chart1').html(''); eval(data);
				// Chart 5
				$.get(ccstats, { plottime: plottime, chartid: 'chart5'}, function(data) { $('#chart5').html(''); eval(data);
					// Chart 6
					$.get(ccstats, { plottime: plottime, chartid: 'chart6'}, function(data) { $('#chart6').html(''); eval(data);
						// Chart 2
						$.get(ccstats, { plottime: plottime, chartid: 'chart2'}, function(data) { $('#chart2').html(''); eval(data); 
							// Chart 7
							$.get(ccstats, { plottime: plottime, chartid: 'chart7'}, function(data) { $('#chart7').html(''); eval(data);
							} );
						} );
					} );
				} );
			} );
		} );
	} );
});
</script>

<?php include 'footer.php'; ?>