<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

include 'header.php';

// If username is in query string, then load that user information upon pageload
if (isset($_GET['username']) && $_GET['username'] != "")
{
	$_GET['username'] = strip_tags(label_decode(urldecode($_GET['username'])));
	if (!preg_match("/^([a-zA-Z0-9_\.\-\@])+$/", $_GET['username'])) redirect(PAGE_FULL);
	// First, ensure that this is a valid username
	$sql = "(select username from redcap_user_rights where username = '" . prep($_GET['username']) . "')
			union (select username from redcap_user_information where username = '" . prep($_GET['username']) . "')";
	$q = db_query($sql);
	if (db_num_rows($q) < 1) {
		redirect(PAGE_FULL);
	}
	?>
	<script type="text/javascript">
	$(function(){
		var user = '<?php echo cleanHtml($_GET['username']) ?>';
		$('#select_username').val(user);
		// Make sure this user is listed in the drop-down first
		if ($('#select_username').val() == user) {
			view_user(user);
		}
	});
	</script>
	<?php
}
?>

<h3 style="margin-top: 0;"><?php echo $lang['control_center_109'] ?></h3>

<div style="margin-bottom:20px;padding:10px 15px 15px;border:1px solid #d0d0d0;background-color:#f5f5f5;">
	<b><?php echo $lang['control_center_33'] ?></b><br /><br />
	<?php echo $lang['control_center_34'] ?>
	<div style="padding:3px 0;color:#777;font-family:tahoma;font-size:9px;"><?php echo $lang['control_center_193'];?></div>
	<p style="padding: 10px 0px 0px; margin: 0px;">
		<select id="activity-level" name="activity_level" class="x-form-text x-form-field" style="padding-right: 0px; height: 22px; ">
		
			<optgroup label="<?php echo cleanHtml2($lang['control_center_360']) ?>">
				<option value="" selected><?php echo $lang['control_center_182'];?></option>
				<option value="I"><?php echo $lang['control_center_183'];?></option>
			</optgroup>	
			
			<optgroup label="<?php echo cleanHtml2($lang['control_center_359']) ?>">
				<option value="CL"><?php echo $lang['control_center_355'];?></option>
				<option value="NCL"><?php echo $lang['control_center_356'];?></option>	
			</optgroup>

			<optgroup label="<?php echo cleanHtml2($lang['control_center_358']) ?>">
				<option value="L-0.0417"><?php echo $lang['control_center_347'];?></option>
				<option value="L-0.5"><?php echo $lang['control_center_345'];?></option>
				<option value="L-1"><?php echo $lang['control_center_343'];?></option>			
				<option value="L-30"><?php echo $lang['control_center_198'];?></option>
				<option value="L-90"><?php echo $lang['control_center_199'];?></option>
				<option value="L-183"><?php echo $lang['control_center_200'];?></option>
				<option value="L-365"><?php echo $lang['control_center_201'];?></option>	

				<option value="NL-0.0417"><?php echo $lang['control_center_348'];?></option>
				<option value="NL-0.5"><?php echo $lang['control_center_346'];?></option>
				<option value="NL-1"><?php echo $lang['control_center_344'];?></option>						
				<option value="NL-30"><?php echo $lang['control_center_202'];?></option>
				<option value="NL-90"><?php echo $lang['control_center_203'];?></option>
				<option value="NL-183"><?php echo $lang['control_center_204'];?></option>
				<option value="NL-365"><?php echo $lang['control_center_205'];?></option>
			</optgroup>
			
			<optgroup label="<?php echo cleanHtml2($lang['control_center_357']) ?>">
				<option value="0.0417"><?php echo $lang['control_center_353'];?></option>
				<option value="0.5"><?php echo $lang['control_center_351'];?></option>
				<option value="1"><?php echo $lang['control_center_349'];?></option>			
				<option value="30"><?php echo $lang['control_center_184'];?></option>
				<option value="90"><?php echo $lang['control_center_186'];?></option>
				<option value="183"><?php echo $lang['control_center_187'];?></option>
				<option value="365"><?php echo $lang['control_center_188'];?></option>
			
				<option value="NA-0.0417"><?php echo $lang['control_center_354'];?></option>
				<option value="NA-0.5"><?php echo $lang['control_center_352'];?></option>
				<option value="NA-1"><?php echo $lang['control_center_350'];?></option>			
				<option value="NA-30"><?php echo $lang['control_center_194'];?></option>
				<option value="NA-90"><?php echo $lang['control_center_195'];?></option>
				<option value="NA-183"><?php echo $lang['control_center_196'];?></option>
				<option value="NA-365"><?php echo $lang['control_center_197'];?></option>	
			</optgroup>
			
		</select>
		<input type="button" value="<?php echo $lang['control_center_181'];?>" onclick="openUserHistoryList();">
	</p>
</div>

<div style="margin-bottom:20px;padding:10px 15px 15px;border:1px solid #d0d0d0;background-color:#f5f5f5;">
	<p>
		<b><?php echo $lang['control_center_37'] ?></b><br><br>
		<?php echo $lang['control_center_38'] ?>
	</p>
	<div id="view_user_div" style="padding-top:10px;">
		<?php
		// Set value for including
		$_GET['user_view'] = "view_user";
		include APP_PATH_DOCROOT . "ControlCenter/user_controls_ajax.php";
		?>
	</div>
</div>

<!-- Dialog Box for Comprehensive User List -->
<div id="userList" style="display:none;" title="<?php echo $lang['control_center_39'] ?>">
	<p>
		<?php echo $lang['control_center_190'] ?>
	</p>
	<div id="userListProgress" style="padding:10px;font-weight:bold;">
		<img src="<?php echo APP_PATH_IMAGES ?>progress_circle.gif" class="imgfix"> <?php echo $lang['control_center_41'] ?>...
	</div>
	<div id="userListTable" style="padding:10px;"></div>
</div>

<?php 
include 'footer.php';