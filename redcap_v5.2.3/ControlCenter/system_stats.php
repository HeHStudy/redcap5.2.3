<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

include 'header.php';
?>

<h3 style="margin-top: 0;"><?php echo $lang['dashboard_48'] ?></h3>

<div id='controlcenter_stats' style='width:280px;'>
	<img src="<?php echo APP_PATH_IMAGES ?>progress_circle.gif" class='imgfix'>
	<b><?php echo $lang['dashboard_01'] ?>...</b>
</div>

<script type="text/javascript">
$(function() {
	// Chain all ajax events so that they are fired sequentially
	var ccstats  = app_path_webroot + 'ControlCenter/stats_ajax.php';
	// Statistics table
	$.get(ccstats, {}, function(data) {		
		// Create table on page
		$('#controlcenter_stats').html(data);
		// Multiple ajax calls for stats that take a long time
		$.get(ccstats, { logged_events: 1}, function(data) { 
			var le = data.split("|");
			$('#logged_events').html(le[0]); 
			$('#logged_events_30min').html(le[1]);
			$('#logged_events_today').html(le[2]);
			$('#logged_events_week').html(le[3]);
			$('#logged_events_month').html(le[4]);
		} );		
		$.get(ccstats, { total_fields: 1}, function(data) { $('#total_fields').html(data); } );		
		$.get(ccstats, { mysql_space: 1}, function(data) { $('#mysql_space').html(data); } );		
		$.get(ccstats, { webserver_space: 1}, function(data) { $('#webserver_space').html(data); } );		
		$.get(ccstats, { survey_participants: 1}, function(data) { $('#survey_participants').html(data); } );		
		$.get(ccstats, { survey_invitations: 1}, function(data) { 
			var si = data.split("|");
			$('#survey_invitations_sent').html(si[0]); 
			$('#survey_invitations_responded').html(si[1]);
			$('#survey_invitations_unresponded').html(si[2]);
		} );
		$.get(ccstats, { total_records: 1}, function(data) { $('#total_records').html(data); } );		
	} );
});
</script>

<?php include 'footer.php';