<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

if (!$api_enabled) redirectHome();

$db = new RedCapDB();
$token = $db->getAPIToken($userid, $project_id);

// API help
$instr = RCView::p(array('style' => 'margin-top:20px;'),
				$lang['system_config_114'] . ' ' .
				RCView::a(array('href' => APP_PATH_WEBROOT_PARENT . 'api/help/', 'style' => 'text-decoration:underline;', 'target' => '_blank'),
								$lang['edit_project_57']) .
				$lang['period'] . ' ');

$h = ''; // will hold the HTML to display in API div (all JS is included inline at the bottom)
$h .= RCView::span(array('id' => 'apiDialogContainerId', 'style' => 'display: none;'), '');
$h .= RCView::div(array('id' => 'apiDialogRegenId', 'title' => $lang['edit_project_113'], 'style' => 'display: none;'),
				RCView::p(array('style' => 'font-family:arial;'), $lang['edit_project_114']));
$h .= RCView::div(array('id' => 'apiDialogDeleteId', 'title' => $lang['edit_project_111'], 'style' => 'display: none;'),
				RCView::p(array('style' => 'font-family:arial;'), $lang['edit_project_112']));

// dummy container used as a target for a loading overlay
$dummy = '';
// API token for selected project
$tok = '';
$tok .= RCView::div(array('class' => 'chklisthdr'), $lang['api_05'] . ' "' . RCView::escape($app_title) . '"');
$tok .= RCView::div(array('style' => 'margin:10px 0;'), $lang['edit_project_87']);
$tok .= RCView::div(array('style' => 'margin:10px 0 25px;'),
			$lang['control_center_333'] . $lang['colon'] . RCView::br() .
			RCView::span(array('id' => 'apiTokenId', 'style' => 'font-size: 18px; font-weight: bold; color: #347235;'), $token) . ' ');
$tok .= RCView::div(array('style' => 'margin:5px 0 0;'),
					RCView::button(array('id' => 'reqAPIDelId', 'class' => 'jqbuttonmed'), $lang['edit_project_116']). '&nbsp; ' . $lang['edit_project_117']);
$tok .= RCView::div(array('style' => 'margin:5px 0 0;'),
					RCView::button(array('id' => 'reqAPIRegenId', 'class' => 'jqbuttonmed'), $lang['edit_project_118']) . '&nbsp; ' . $lang['edit_project_119']);
$tok .= RCView::div(array('style' => 'margin:20px 0 0;'),
					$lang['edit_project_115'] . '&nbsp; ' .
					RCView::span(array('id' => 'apiTokenUsersId', 'class' => 'code'), ''));
$dummy .= RCView::div(array('id' => 'apiTokenBoxId', 'class' => 'redcapAppCtrl', 'style' => 'display: none;'), $tok);
// API token request
$req = '';
$req .= RCView::div(array('class' => 'chklisthdr'), $lang['api_02'] . ' ' . RCView::escape($app_title));
$req .= RCView::div(array('style' => 'margin:5px 0 0;'), $lang['edit_project_88']);
$reqAPIBtn = RCView::button(array('class' => 'jqbuttonmed', 'id' => 'reqAPIBtnId'), $lang['api_03']);
$req .= RCView::div(array('class' => 'chklistbtn'), $reqAPIBtn);
if ($super_user) {
	$req .= RCView::br();
	$approveLink = APP_PATH_WEBROOT . 'ControlCenter/user_api_tokens.php?action=createToken&api_username=' . $userid .
		'&api_pid=' . $project_id . '&goto_proj=1';
	$req .= RCView::button(array('onclick' =>"window.location.href='$approveLink';", 'class' => 'jqbuttonmed'), RCView::escape($lang['api_08'])) .
	RCView::SP . RCView::span(array('style' => 'color: red;'), $lang['edit_project_77']);
}
$dummy .= RCView::div(array('id' => 'apiReqBoxId', 'class' => 'redcapAppCtrl', 'style' => 'display: none;'), $req);

$h .= RCView::div(array('id' => 'apiDummyContainer'), $dummy);

// API Event names
$e = '';
$eventKeys = Event::getUniqueKeys($project_id);
$events = $db->getEvents($project_id);
// key the events by event ID
$tmp = array();
foreach ($events as $e) $tmp[$e->event_id] = $e;
$events = $tmp;
if ($longitudinal && count($events) > 0) {
	$eventRows = array($lang['edit_project_94'] . ' ' . RCView::span(array('style'=>'color:#800000;'), RCView::escape($app_title)));
	$eventRows[] = array($lang['define_events_65'], $lang['global_10'], $lang['global_08']);
	foreach ($eventKeys as $eventId => $eventKey) {
		$row = array();
		$row[] = RCView::font(array('class' => 'code'), RCView::escape($eventKey));
		$row[] = RCView::escape($events[$eventId]->descrip);
		$row[] = RCView::escape($events[$eventId]->arm_name);
		$eventRows[] = $row;
	}
	$e .= RCView::simpleGrid($eventRows, array(200, 200, 100));
}

// If Data Access Groups exist, display them and their unique names here
$d = '';
$dags = $Proj->getUniqueGroupNames();
if (!empty($dags))
{
	$dagRows = array($lang['data_access_groups_ajax_20'] . ' ' . RCView::span(array('style'=>'color:#800000;'), RCView::escape($app_title)));
	$dagRows[] = array($lang['data_access_groups_ajax_18'], $lang['data_access_groups_ajax_21']);
	foreach (array_combine($dags, $Proj->getGroups()) as $unique=>$label) {
		$dagRows[] = array(RCView::font(array('class' => 'code'), $unique), $label);
	}
	$d .= RCView::div(array('class'=>'space'), '&nbsp;') .
		  RCView::simpleGrid($dagRows, array(200, 300));
}

// display the page
include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
?>
<script type='text/javascript'>
	$(function() {
		<?php if (empty($token)) { ?>
		$("#apiReqBoxId").show();
		<?php } else { ?>
		$("#apiTokenBoxId").show();
		$.get(app_path_webroot + "API/project_api_ajax.php",
			{ action: 'getTokens', pid: '<?php echo $project_id; ?>' },
			function(data) { $("#apiTokenUsersId").html(data); }
		);
		<?php } ?>
		function doDelete() {
			RedCapUtil.openLoader($("#apiDummyContainer"));
			$.post(app_path_webroot + "API/project_api_ajax.php?pid=<?php echo $project_id; ?>",
				{ action: "deleteToken" },
				function (data) {
					$("#apiDialogContainerId").html(data);
					$("#apiDialogContainerId").show();
					$("#apiDialogId").dialog({ bgiframe: true, modal: true, width: 400, 
						buttons: { Close: function() { $(this).dialog('close'); }}
					});
					$.get(app_path_webroot + "API/project_api_ajax.php",
						{ action: 'getToken', pid: '<?php echo $project_id; ?>' },
						function(data) {
							if (data.length == 0) {
								$("#apiReqBoxId").show();
								$("#apiTokenBoxId").hide();
								$("#apiTokenId").html("");
								$("#apiTokenUsersId").html("");
							}
							else {
								$("#apiTokenId").html(data);
							}
							RedCapUtil.closeLoader($("#apiDummyContainer"));
						}
					);
				}
			);
		}
		function doRegen() {
			RedCapUtil.openLoader($("#apiDummyContainer"));
			$.post(app_path_webroot + "API/project_api_ajax.php?pid=<?php echo $project_id; ?>",
				{ action: "regenToken" },
				function (data) {
					$("#apiDialogContainerId").html(data);
					$("#apiDialogContainerId").show();
					$("#apiDialogId").dialog({ bgiframe: true, modal: true, width: 400, 
						buttons: { Close: function() { $(this).dialog('close'); }}
					});
					$.get(app_path_webroot + "API/project_api_ajax.php",
						{ action: 'getToken', pid: '<?php echo $project_id; ?>' },
						function(data) {
							$("#apiTokenId").html(data);
							RedCapUtil.closeLoader($("#apiDummyContainer"));
						}
					);
				}
			);
		}
		$("#reqAPIBtnId").click(function() {
			$.post('<?php echo APP_PATH_WEBROOT; ?>API/project_api_ajax.php?pid=<?php echo $project_id; ?>',
				{ action: 'requestToken' },
				function (data) {
					$("#apiDialogContainerId").html(data);
					$("#apiDialogContainerId").show();
					$("#apiDialogId").dialog({ bgiframe: true, modal: true, width: 400, 
						buttons: { Close: function() { $(this).dialog('close'); }}
					});
				}
			);
		});
		$("#reqAPIDelId").click(function() {
			$("#apiDialogDeleteId").dialog('destroy');
			$("#apiDialogDeleteId").dialog({ bgiframe: true, modal: true, width: 500, buttons: { 
				Cancel: function() { $(this).dialog('close'); }, 
				'<?php echo $lang['edit_project_96']; ?>': function() { $(this).dialog('close'); doDelete(); }}}
			);
			return false;
		});
		$("#reqAPIRegenId").click(function() {
			$("#apiDialogRegenId").dialog('destroy');
			$("#apiDialogRegenId").dialog({ bgiframe: true, modal: true, width: 500, buttons: { 
				Cancel: function() { $(this).dialog('close'); }, 
				'<?php echo $lang['edit_project_97']; ?>': function() { $(this).dialog('close'); doRegen(); }}}
			);
			return false;
		});
	});
</script>
<?php
// Title
renderPageTitle(RCView::img(array('src' => 'computer.png','class'=>'imgfix2')) . $lang['setup_77']);
// Page instructions
echo $instr;
// Tabs to view "my token" or all users' tokens (super users only)
if (SUPER_USER) {
	$tabs = array('API/project_api.php'=>$lang['control_center_340'], 'API/project_api.php?allUserTokens=1'=>$lang['control_center_341']);
	renderTabs($tabs);
}
if (SUPER_USER && isset($_GET['allUserTokens'])) {
	print RCView::div(array('style'=>'max-width:700px;'), $lang['control_center_342']);
	// List table of all users with token (super users only)
	include APP_PATH_DOCROOT . 'ControlCenter/user_api_tokens.php';
} else {
	// Box with user's API token and options
	echo $h;
	// Event and DAG tables
	echo $e . $d;
}
// Footer
include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';