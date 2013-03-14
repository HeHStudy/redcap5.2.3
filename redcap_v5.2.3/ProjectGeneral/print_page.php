<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

?>
<html>
<head>
	<meta http-equiv='content-type' content='text/html; charset=UTF-8'>
	<title>REDCap</title>
	<meta name="googlebot" content="noindex, noarchive, nofollow, nosnippet">
	<meta name="robots" content="noindex, noarchive, nofollow">
	<meta name="slurp" content="noindex, noarchive, nofollow, noodp, noydir">
	<meta name="msnbot" content="noindex, noarchive, nofollow, noodp">
	<meta http-equiv="Cache-Control" content="no-cache">
	<meta http-equiv="Pragma" content="no-cache">
	<meta http-equiv="expires" content="0">
	<link rel='shortcut icon' href='<?php print APP_PATH_IMAGES ?>favicon.ico' type='image/x-icon'>
	<link rel='stylesheet' type='text/css' href='<?php print APP_PATH_CSS ?>style.css' media='screen,print'>
	<script type='text/javascript' src='<?php print APP_PATH_JS ?>base.js'></script>
</head>
<body>
<div style='text-align:right;padding:0 10px;'>
	<img src='<?php print APP_PATH_IMAGES ?>printer.png' class='imgfix'>
	<a href='javascript:;' onclick='window.print();' style='text-decoration:underline;'><?php echo $lang['graphical_view_15'] ?></a>
</div>
<script type='text/javascript'>
window.print();
</script>
<?php


// Display report
if (isset($_GET['query_id'])) {
	?>
	<div style='padding:1px 5px;color:#999;font-size:13px;border-bottom:1px solid #ddd;margin-bottom:15px;'>
		<i><?php echo $app_title ?></i>
	</div>
	<style type='text/css'>
	body { padding:10px; }
	</style>
	<?php
	include_once APP_PATH_DOCROOT . "Reports/report.php";
}


// Display schedule for a newly scheduled participant
elseif (isset($_GET['schedule']) && isset($_GET['idnumber']) && isset($_GET['display_only'])) {
	?>
	<div style='padding:1px 5px;color:#999;font-size:13px;border-bottom:1px solid #ddd;margin-bottom:15px;'>
		<i><?php echo $app_title ?></i>
	</div>
	<style type='text/css'>
	body { padding:10px; }
	</style>
	<?php
	include_once APP_PATH_DOCROOT . "Calendar/scheduling_ajax.php";
}


// Display schedule for an already scheduled participant
elseif (isset($_GET['schedule']) && isset($_GET['record']) && $_GET['record'] != "") {
	?>
	<div style='padding:1px 5px;color:#999;font-size:13px;border-bottom:1px solid #ddd;margin-bottom:15px;'>
		<i><?php echo $app_title ?></i>
	</div>
	<style type='text/css'>
	.blue { border:0; background:#fff; }
	#new_ad_hoc { display:none; }
	#view_edit_instr { display:none; }
	#view_edit_title { color:#000; text-align:center; }
	#sched_frow { display:none; }
	.rangetext {display:none;}
	</style>
	<script type='text/javascript'>
	$(function(){
		$('#view_edit_title').html('Schedule for "<?php echo strip_tags(label_decode($_GET['record'])) ?>"');
	});
	</script>
	<?php
	$_GET['arm'] = getArm();
	include_once APP_PATH_DOCROOT . "Calendar/scheduling_ajax.php";
}


// Display calendar/agenda
elseif (isset($_GET['printcalendar'])) {
	?>
	<style type='text/css'>
	td.dayboxes { border:1px solid #888; }
	#month_year_select {display: none; }
	</style>
	<script type='text/javascript'>
	$(function(){
		for (var i=1; i<=31; i++) {		
			$('#link'+i).html('');
			$('#new'+i).prop('onmouseout','');
			$('#new'+i).prop('onmouseover','');
			$('#new'+i).prop('onclick','');
		}
		// Expand any day's collapsed events in order to have all displayed
		$('.showEv').each(function(index) {
			showEv($(this).prop('ev'));
		});
	});
	</script>
	<div style='padding:1px 5px;color:#999;font-size:13px;border-bottom:1px solid #ddd;margin-bottom:15px;'>
		<i><?php echo $app_title ?></i>
	</div>
	<div style='text-align:center;font-weight:bold;padding:5px;color:#555;font-size:14px;max-width:800px;'>
		<?php 
		$_GET['month'] = (int)$_GET['month'];
		$_GET['day'] = (int)$_GET['day'];
		$_GET['year'] = (int)$_GET['year'];
		echo date ("F", mktime(0,0,0,($_GET['month']-1),1,$_GET['year'])) . 
			 ($_GET['view'] == 'day' ? " " . $_GET['day'] . ", " : " ") . 
			 $_GET['year'] 
		?>
	</div>
	<?php
	include_once APP_PATH_DOCROOT . "Calendar/calendar_table.php";	
}


print  "
</body>
</html>";
