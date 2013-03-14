<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . "/Config/init_project.php";

// Get form list for drop-down
$sharedLibForms = "";
foreach ($Proj->forms as $form=>$attr)
{
	$sharedLibForms .= "<option value='$form'>{$attr['menu']}</option>";
}





?>
<p>
	<?php echo $lang['design_252'] ?>
</p>
<p>
	<?php echo $lang['design_251'] ?>
</p>
<?php if ($status > 0 && $draft_mode == 0) { ?>
	<button style="font-family:arial;" onclick="
		if ($('#form_names').val().length < 1){ 
			alert('Please select an instrument'); 
		} else {
			window.location.href = app_path_webroot+'SharedLibrary/index.php?pid='+pid+'&page='+$('#form_names').val(); 
		}
	"><?php echo $lang["design_174"] ?></button>
	<?php echo $lang['design_208'] ?>&nbsp;
	<select id="form_names" class="x-form-text x-form-field notranslate" style="padding-right:0;height:22px;">
		<option value="">-- <?php echo $lang["shared_library_59"] ?> --</option>
		<?php echo $sharedLibForms ?>
	</select>&nbsp;
	<?php echo $lang['design_209'] ?>
<?php } ?>