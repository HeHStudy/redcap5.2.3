<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . "/Config/init_project.php";
require_once APP_PATH_DOCROOT . 'ProjectGeneral/form_renderer_functions.php';
require_once APP_PATH_CLASSES . 'HtmlPage.php';

// Skip this page if project is not longitudinal (requires different workflow for selecting records)
if (!$longitudinal) 
{
	redirect(APP_PATH_WEBROOT . "Mobile/choose_form.php?pid=$project_id");
	exit;
}

// Initialize page display object
$objHtmlPage = new HtmlPage();
$objHtmlPage->addStylesheet("smoothness/jquery-ui-".JQUERYUI_VERSION.".custom.css", 'screen,print');
$objHtmlPage->addStylesheet("jqtouch.min.css", 'screen,print');
$objHtmlPage->addStylesheet("jqtouch_themes/apple/theme.min.css", 'screen,print');
$objHtmlPage->addStylesheet("mobile_data_entry.css", 'screen,print');
$objHtmlPage->addExternalJS(APP_PATH_JS . "base.js");
$objHtmlPage->addExternalJS(APP_PATH_JS . "jqtouch.min.js");
$objHtmlPage->PrintHeader();
		
// Get current arm
$arm = getArm(); 

// Check if record ID field should have validation
$text_val_string = "";
if ($Proj->metadata[$table_pk]['element_type'] == 'text' && $Proj->metadata[$table_pk]['element_validation_type'] != '') 
{
	// Apply validation function to field
	$text_val_string = "if(redcap_validate(this,'{$Proj->metadata[$table_pk]['element_validation_min']}','{$Proj->metadata[$table_pk]['element_validation_max']}','hard','".convertLegacyValidationType($Proj->metadata[$table_pk]['element_validation_type'])."',1)) ";
}

?>

<style type="text/css">
#footer { font-size: 11px; text-align: center; }
</style>

<script type="text/javascript">
var jQT = new $.jQTouch();
// Enable redirecting if hit Go or change value for text field
$(function(){
	$('#inputString').keypress(function(e) {
		if (e.which == 13) {
			 $('#inputString').trigger('blur');
			return false;
		}
	});
	$('#inputString').blur(function() {
		var refocus = false;
		var idval = trim($('#inputString').val()); 
		if (idval.length < 1) {
			refocus = true;
			$('#inputString').val('');
		}
		if (idval.length > 50) {
			refocus = true;
			alert('<?php echo cleanHtml($lang['data_entry_44']) ?>'); 
		}
		if (refocus) {
			setTimeout(function(){document.getElementById('inputString').focus();},10);
		} else {
			$('#inputString').val(idval);
			<?php echo $text_val_string ?>
			setTimeout(function(){
				idval = $('#inputString').val();
				idval = idval.replace(/&quot;/g,''); // HTML char code of double quote
				// Don't allow pound signs in record names
				if (/#/g.test(idval)) {
					$('#inputString').val('');
					alert("Pound signs (#) are not allowed in record names! Please enter another record name.");
					$('#inputString').focus();
					return false;
				}
				// Don't allow apostrophes in record names
				if (/'/g.test(idval)) {
					$('#inputString').val('');
					alert("Apostrophes are not allowed in record names! Please enter another record name.");
					$('#inputString').focus();
					return false;
				}
				// Don't allow ampersands in record names
				if (/&/g.test(idval)) {
					$('#inputString').val('');
					alert("Ampersands (&) are not allowed in record names! Please enter another record name.");
					$('#inputString').focus();
					return false;
				}
				// Don't allow plus signs in record names
				if (/\+/g.test(idval)) {
					$('#inputString').val('');
					alert("Plus signs (+) are not allowed in record names! Please enter another record name.");
					$('#inputString').focus();
					return false;
				}
				window.location.href='<?php echo APP_PATH_WEBROOT ?>Mobile/choose_record.php?pid=<?php echo "$project_id&arm=$arm&id=" ?>'+idval;
			},200);
		}
	});
});
</script>	

<div id="back"></div>

<div id="home" class="current">
	<div class="toolbar">
		<a class="back" style="max-width:85px;" href="javascript:;" onclick="window.location.href='<?php echo APP_PATH_WEBROOT ?>Mobile/';"><?php echo $lang['mobile_site_03'] ?></a>
		<h1>REDCap</h1>
	</div>
	<h1 style="font-size:14px;color:#fff;text-shadow:0 1px 1px #000000;"><?php echo $app_title ?></h1>
	<?php	
	// Set up context messages to users for actions performed in longitudinal projects (Save button redirects back here for longitudinals)
	if ($longitudinal && isset($_GET['msg']))
	{
		if ($_GET['msg'] == 'edit') {
			print "<div class='darkgreen'><img src='".APP_PATH_IMAGES."tick.png' class='imgfix'> " . strip_tags(label_decode($table_pk_label)) . " <b>{$_GET['id']}</b> {$lang['data_entry_08']}</div>";
		} elseif ($_GET['msg'] == 'add') {
			print "<div class='darkgreen'><img src='".APP_PATH_IMAGES."tick.png' class='imgfix'> " . strip_tags(label_decode($table_pk_label)) . " <b>{$_GET['id']}</b> {$lang['data_entry_09']}</div>";
		} elseif ($_GET['msg'] == 'delete') {
			print "<div class='red'><img src='".APP_PATH_IMAGES."exclamation.png' class='imgfix'> " . strip_tags(label_decode($table_pk_label)) . " <b>{$_GET['iddelete']}</b> {$lang['data_entry_10']}</div>";
		} elseif ($_GET['msg'] == 'cancel') {
			print "<div class='red'><img src='".APP_PATH_IMAGES."exclamation.png' class='imgfix'> " . strip_tags(label_decode($table_pk_label)) . " <b>$fetched</b> {$lang['data_entry_11']}</div>";
		}
	}
	?>
	<form>
	
		<!-- Select arm (if multiple arms) -->
		<?php if ($multiple_arms) { ?>
		<h1><?php echo $lang['global_08'].$lang['colon'] ?></h1>
		<ul class="edit rounded" style="margin-bottom:0px;">
			<li>
				<select id="arms" onchange="if (this.value.length>0) window.location.href='<?php echo PAGE_FULL . "?pid=$project_id&arm=" ?>'+this.value;">
					<option value=""><?php echo $lang['mobile_site_07'] ?></option>
					<?php foreach ($Proj->events as $this_arm_num=>$row) { ?>
						<option value="<?php echo $this_arm_num ?>" <?php if (isset($_GET['arm']) && $this_arm_num == $_GET['arm']) echo "selected"; ?>><?php echo $row['name'] ?></option>
					<?php } ?>
				</select>
			</li>
		</ul>
		<?php 
		}
		
		// If record was entered/selected, check to see if exists so as to hide one of the record input options
		$recordExists = 0;
		if (isset($_GET['id']))
		{
			$recordExists = db_num_rows(db_query("select 1 from redcap_data where project_id = $project_id and record = '".prep($_GET['id'])."' limit 1"));
		}
		?>
		
		<?php if (isset($_GET['arm']) || !$multiple_arms) { ?>
			<h1 style="margin-top:5px;">
				<?php 
				echo $lang['global_49'].$lang['colon'];
				if (isset($_GET['id']) && !$recordExists) {
					echo "<span class='darkgreen' style='color:green;padding:3px 5px;margin-left:10px;font-size:13px;'><img src='".APP_PATH_IMAGES."add.png' class='imgfix'> " . $lang['data_entry_14'] . " $table_pk_label</span>";
				} 
				?>
			</h1>
		<?php } ?>
		
		<!-- Select record -->
		<?php if (((isset($_GET['arm']) || !$multiple_arms) && !isset($_GET['id'])) || (isset($_GET['id']) && $recordExists)) { ?>
			<?php if (!isset($_GET['id'])) { ?>
				<h1 style="font-size:14px;"><?php echo $lang['data_entry_24'] . " " . $table_pk_label ?></h1>
			<?php } ?>
			<ul class="edit rounded" style="margin-bottom:0px;">
				<li>
					<select id="records" onchange="if (this.value.length>0) window.location.href='<?php echo PAGE_FULL . "?pid=$project_id&arm=$arm&id=" ?>'+this.value;">
						<option value=""><?php echo $lang['mobile_site_06'] ?></option>
						<?php
						$sql = "select distinct record from redcap_data where project_id = $project_id and field_name = '$table_pk'					
								and event_id in (" . implode(", ", array_keys($Proj->events[$arm]['events'])) . ") order by abs(record), record";
						$q = db_query($sql);
						while ($row = db_fetch_array($q)) { 
						?>
							<option value="<?php echo $row['record'] ?>" <?php if (isset($_GET['id']) && $row['record'] == $_GET['id']) echo "selected"; ?>><?php echo $row['record'] ?></option>
						<?php } ?>
					</select>
				</li>
			</ul>
		<?php } ?>
		
		<!-- Create new record -->
		<?php if (((isset($_GET['arm']) || !$multiple_arms) && !isset($_GET['id'])) || (isset($_GET['id']) && !$recordExists)) { ?>
			<?php if (!isset($_GET['id']) && !$auto_inc_set) { ?>
				<h1 style="font-size:14px;">
					<?php echo $lang['data_entry_31'] . " " . $table_pk_label ?>
				</h1>
			<?php } ?>
			<ul class="edit rounded" style="margin-bottom:0px;">
				<?php if (!isset($_GET['id']) && $auto_inc_set) { ?>
					<li><a class="whiteButton" href="javascript:;" onclick="$(this).css('color','#800000');$(this).css('background','red');window.location.href='<?php echo APP_PATH_WEBROOT ?>Mobile/choose_record.php?pid=<?php echo "$project_id{$child}&arm=".getArm()."&id=".getAutoId()."&auto" ?>';"><?php echo $lang['data_entry_46'] ?></a></li>
				<?php } else { ?>
					<li><input type="text" <?php echo ((isset($recordExists) && !$recordExists) ? 'value="'.$_GET['id'].'"' : 'placeholder="Enter new record"') ?> name="inputString" id="inputString"></li>
				<?php } ?>
			</ul>
		<?php } ?>
		
		<!-- Select event -->
		<?php if ((isset($_GET['arm']) || !$multiple_arms) && isset($_GET['id'])) { ?>
		<h1 style="margin-top:5px;"><?php echo $lang['bottom_23'] ?></h1>
		<ul class="edit rounded" style="margin-bottom:0px;">
			<li>
				<select id="events" onchange="if (this.value.length>0) window.location.href='<?php echo PAGE_FULL . "?pid=$project_id&arm=$arm&id={$_GET['id']}&event_id=" ?>'+this.value;">
					<option value=""><?php echo $lang['mobile_site_05'] ?></option>
					<?php foreach ($Proj->events[$arm]['events'] as $this_event_id=>$row) { ?>
						<option value="<?php echo $this_event_id ?>" <?php if (isset($_GET['event_id']) && $this_event_id == $_GET['event_id']) echo "selected"; ?>><?php echo $row['descrip'] ?></option>
					<?php } ?>
				</select>
			</li>
		</ul>
		<?php } ?>
		
		<!-- Select form -->
		<?php if ((isset($_GET['arm']) || !$multiple_arms) && isset($_GET['id']) && isset($_GET['event_id'])) { ?>
		<h1 style="margin-top:5px;"><?php echo $lang['global_54'].$lang['colon'] ?></h1>
		<ul class="edit rounded">
			<li>
				<select id="events" onchange="if (this.value.length>0) window.location.href='<?php echo APP_PATH_WEBROOT . "Mobile/data_entry.php?pid=$project_id&id={$_GET['id']}&event_id={$_GET['event_id']}&page=" ?>'+this.value;">
					<option value=""><?php echo $lang['mobile_site_04'] ?></option>
					<?php foreach ($Proj->eventsForms[$_GET['event_id']] as $this_form_name) { ?>
						<option value="<?php echo $this_form_name ?>"><?php echo $Proj->forms[$this_form_name]['menu'] ?></option>
					<?php } ?>
				</select>
			</li>
		</ul>
		<?php } ?>
		
	</form>
</div>

<?php

$objHtmlPage->PrintFooter();