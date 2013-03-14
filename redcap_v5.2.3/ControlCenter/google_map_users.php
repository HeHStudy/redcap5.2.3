<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

include 'header.php';

//If user is not a super user, go back to Home page
if (!$super_user) { redirect(APP_PATH_WEBROOT); }


## SET TIME PERIODS TO VIEW
// Default time period: Past day
if (!isset($_GET['plottime'])) $_GET['plottime'] = "1d";
// Past day
if ($_GET['plottime'] == "1d") {
	$date_label = $lang['dashboard_89'];
	$timeWindowHours = 24;
// Past week
} elseif ($_GET['plottime'] == "1w") {
	$date_label = $lang['dashboard_07'];
	$timeWindowHours = 24*7;
// Past month
} elseif ($_GET['plottime'] == "1m") {
	$date_label = $lang['dashboard_08'];
	$timeWindowHours = 24*30;
// Past three months
} elseif ($_GET['plottime'] == "3m") {
	$date_label = $lang['dashboard_09'];
	$timeWindowHours = 24*30*3;
// Past six months
} elseif ($_GET['plottime'] == "6m") {
	$date_label = $lang['dashboard_10'];
	$timeWindowHours = 24*30*6;
// Past year
} elseif ($_GET['plottime'] == "12m") {
	$date_label = $lang['dashboard_11'];
	$timeWindowHours = 24*30*6;
// All
} elseif ($_GET['plottime'] == "all") {
	$date_label = $lang['dashboard_12'];
	$timeWindowHours = 24*365*10;
}


// Check if web services have been contacted before (i.e. are tables empty)
$sql = "select 1 from redcap_dashboard_ip_location_cache limit 1";
$ip_table_count = db_num_rows(db_query($sql));
$promptToPingWebService = ($ip_table_count == 0 && $googlemap_key == "");


?>
<style type="text/css">
.data, .label { padding: 3px 6px; font-weight: normal; }
.blue, .yellow, .red { max-width:750px; }
</style> 


<h3 style="margin-top: 0;"><?php echo $lang['control_center_386'] ?></h3>


<!-- Instructions -->
<p>
	<?php echo $lang['control_center_387'] ?>
	<span style="color:#800000;"><b><?php echo $lang['global_02'].$lang['colon'] ?></b> 
	<?php echo $lang['control_center_388'] ?></span>
</p>


<?php
// If page has never been used, perform initial test to contact web services
if ($promptToPingWebService && !isset($_GET['ping']))
{
	?>
	<div class="green" style="margin:30px 0 50px;">
		<b><?php echo $lang['control_center_389'] ?></b><br>
		<?php echo $lang['control_center_390'] ?>
		<br><br>
		<button onclick="this.disabled=true;$('#ping_progress').show();pingIpService();"><?php echo $lang['control_center_391'] ?></button> &nbsp;
		<img id="ping_progress" style="display:none;" src="<?php echo APP_PATH_IMAGES ?>progress_circle.gif" class="imgfix">
		<span id="ping_notice" style="display:none;font-weight:bold;"><?php echo $lang['control_center_392'] ?></span>
	</div>
	<script type="text/javascript">
	// Ping the IP web service to determine if
	function pingIpService() {
		var thisAjax = $.get(app_path_webroot+'ControlCenter/dashboard_ip_location.php', { ip_action: 'ping' }, function(data){
			$('#ping_progress').hide();
			simpleDialog('<?php echo cleanHtml($lang['control_center_393']) ?>',null,null,null,"window.location.href = app_path_webroot+page+'?ping=success';");
		});
		// After X seconds, check response in input field (in case the call to web service hangs)
		setTimeout(function(){
			if (thisAjax.readyState == 1) {
				thisAjax.abort();
				$('#ping_progress').hide();
				$('#ping_notice').show();
				simpleDialog('<?php echo cleanHtml($lang['control_center_394']) ?>');
			}
		}, 15000);
	}
	</script>
	<?php
	// End page
	$objHtmlPage->PrintFooter();
	exit;
}

// Google Map
$_GET['ip_action']		 = 'getAllMarkers';
$_GET['timeWindowHours'] = $timeWindowHours;
include APP_PATH_DOCROOT . "ControlCenter/dashboard_ip_location.php";

if ($googlemap_key != "") 
{
	?>
	<!-- Google Map -->
	<script src="<?php echo (SSL ? "https" : "http") ?>://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&key=<?php echo GOOGLE_MAP_KEY ?>" type="text/javascript"></script>
	<script type='text/javascript'>
	var infowindow;
	var map;
	// Function to initialize map
	function initializeMap() {
		var mapOptions = {
			zoom: 1,
			center: new google.maps.LatLng(30, 1),
			mapTypeId: google.maps.MapTypeId.ROADMAP,
			streetViewControl: false
		} 
		map = new google.maps.Map(document.getElementById('gps_map'), mapOptions);
		// Add all markers from global variable "myMarkers"
		setMarkers(myMarkers);
	}
	// Add all markers from global variable "myMarkers" which has 0) lat, 1) long, 2) mouseover title, 3) infoWindow content
	function setMarkers(locations) {
		// Set marker attributes
		image = new google.maps.MarkerImage("<?php echo APP_PATH_IMAGES ?>mm_20_blue.png",
			new google.maps.Size(12, 20),
			new google.maps.Point(0,0),
			new google.maps.Point(0, 20));
		shadow = new google.maps.MarkerImage("<?php echo APP_PATH_IMAGES ?>mm_20_shadow.png",
			new google.maps.Size(22, 20),
			new google.maps.Point(0,0),
			new google.maps.Point(0, 20));
		// Loop through all locations
		for (var i = 0; i < locations.length; i++) {
			// Get this marker's lat, long, and display text
			var location = locations[i];
			// Add marker
			var marker = new google.maps.Marker({
				map: map,
				icon: image,
				shadow: shadow,
				position: new google.maps.LatLng(location[0], location[1]),
				title: location[2]
			});
			// Set marker's click event
			if (location[3] != null) {
				clickMarker(marker, location[3]);
			}
		}
	}
	// Display pop-up info window when a marker is clicked
	function clickMarker(marker, content) {
		if (infowindow) {
			infowindow.close();
		} else {
			infowindow = new google.maps.InfoWindow();
		}
		google.maps.event.addListener(marker, 'click', function() {
			infowindow.setContent(content);
			infowindow.open(map, marker);
		});
	}
	// Add map markers
	<?php 	
	$myMarkers = array();
	foreach ($gps as $ip=>$attr) { 
		if ($attr[0] != 38 && $attr[1] != -97) { // Ignore generic US location (in Kansas) 
			$myMarkers[] = "[{$attr[0]}, {$attr[1]}, '', '".cleanHtml($attr[2])."']";
		}
	}
	print "var myMarkers = [\n   " . implode(",\n   ", $myMarkers) . "\n];\n";
	?>
	// Initialize map
	google.maps.event.addDomListener(window, 'load', initializeMap);
	// Get next batch of markers via ajax
	function getMarkersNextBatch() {
		$('#ip_progress_update').css('visibility','visible');
		var timeWindowHours = <?php echo $timeWindowHours ?>;
		$.get(app_path_webroot+'ControlCenter/dashboard_ip_location.php', { timeWindowHours: timeWindowHours, ip_action: 'getNextBatch' }, function(data){
			eval(data);
			// Increment number of IPs listed on map
			numIPsProcessed = parseFloat(numIPsProcessed);
			if (numIPsProcessed != '' && numIPsProcessed != '0' && numIPsProcessed > 0) {
				$('#ip_count').html( parseFloat($('#ip_count').text())*1 + 1*numIPsProcessed );
			}
			// If more need to be processed then do again, else hide the progress bar
			if (numIPsNotProcessed > 0) {
				$('#numIpsNotProcessed').html(numIPsNotProcessed);
				getMarkersNextBatch(timeWindowHours);
			} else {
				$('#ip_progress_update_div').hide();
			}
		});
	}	
	// Add single marker on map
	function addMapMarker(latitude,longitude,label) {
		var point = new GLatLng(latitude,longitude);	
		var marker = new GMarker(point);
		GEvent.addListener(marker, "click", function() {
			marker.openInfoWindowHtml(label);
		});
		var myPlacemark = new GMarker(point, markerOptions);
		map.addOverlay(myPlacemark);
		myPlacemark.bindInfoWindowHtml(label);
	}	
	</script>
<?php } else { ?>
	<script type='text/javascript'>
	$('#gps_map').hide();
	</script>
<?php } ?>

<script type='text/javascript'>
// Reload page with new time window set
function setTimeWindow(plottime) {
	$('#ip_progress').css('visibility','visible');
	window.location.href = app_path_webroot+page+'?plottime='+plottime+'<?php if (isset($_GET['ping'])) echo "&ping=".$_GET['ping']; ?>';
}
$(function(){
	<?php if ($ip_table_count > 0 && $numIPsNotProcessed > 0) { ?>
	// If ip location service has already been used once AND some IPs need to be processed, 
	// then auto update the map upon pageload.
	$('#updatemap_btn').click();
	<?php } ?>
});
</script>


<?php 
include 'footer.php';
