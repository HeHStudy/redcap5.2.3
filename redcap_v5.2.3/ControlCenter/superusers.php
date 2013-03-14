<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

include 'header.php';
?>

<?php
if (isset($_GET['saved']))
{
	// Show user message that values were changed
	print  "<div class='yellow' style='margin-bottom: 20px; text-align:center'>
			<img src='".APP_PATH_IMAGES."exclamation_orange.png' class='imgfix'>
			{$lang['setup_09']}
			</div>
			<script type='text/javascript'>
			$(function(){
				setTimeout(function(){
					$('.yellow').hide();
				},2500);
			});
			</script>";
}
?>

<h3 style="margin-top: 0;"><?php echo $lang['control_center_35'] ?></h3>

<p><?php echo $lang['control_center_36'] ?></p>

<select id='new_super_user' class='x-form-text x-form-field' style='padding-right:0;height:22px;'>
	<option value=''>--- <?php echo $lang['control_center_22'] ?> ---</option>
	<?php
	$query = "select username from redcap_user_information where super_user = 0 and username != '' order by trim(lower(username))";
	$q = db_query($query);
	while ($row = db_fetch_assoc($q)) {
		$row['username'] = strtolower(trim($row['username']));
		print  "	<option class='notranslate' value='{$row['username']}'>{$row['username']}</option>";
	}
	?>
</select>
&nbsp;<input type='button' id='add_super_btn' value='Add' style='vertical-align:middle;' onclick="add_super_user();" />

<br/><br/><br/>
<table cellpadding=0 cellspacing=0 style='border:0;border-collapse:collapse;'>
<tr>
	<td class='label' style='background-color:#eee;' colspan='2'>
		<?php echo $lang['control_center_69'] ?>
	</td>
</tr>
<?php
$q = db_query("select username from redcap_user_information where super_user = 1 order by username");
while ($row = db_fetch_assoc($q)) {
	print  "<tr id='su-{$row['username']}'>
				<td class='data2 notranslate'>
					{$row['username']}
				</td>
				<td class='data2' style='padding:0 4px;text-align:center;'>
					<a href='javascript:;' onclick=\"remove_super_user('{$row['username']}');\"
					><img src='" . APP_PATH_IMAGES . "cross.png' class='imgfix2' alt='{$lang['control_center_70']}'
						title='{$lang['control_center_70']}'></a>
				</td>
			</tr>";
}
?>
</table>

<?php include 'footer.php'; ?>