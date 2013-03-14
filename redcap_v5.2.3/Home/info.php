<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


print  "<table border=0 cellpadding=0 cellspacing=0>
		<tr><td valign='top'>";

// Link to consortium public site
if (isVanderbilt())
{
	print  "<p class='blue' style='font-family:arial;margin-bottom:20px;'>
				For more information about the global REDCap software consortium, please visit 
				<a target='_blank' style='text-decoration:underline;color:#800000;font-family:arial;' href='http://www.project-redcap.org'>www.project-redcap.org</a>.
			</p>";
}

// Welcome message and instroduction
print  "<p>
			<b>{$lang['info_01']}</b>
		</p>
		<p>
			{$lang['info_34']}
		</p>
		<p>
			{$lang['info_35']}
		</p>
		<p>
			{$lang['info_36']} 
			<img src='".APP_PATH_IMAGES."video_small.png' class='imgfix'> <a href='javascript:;' onclick=\"popupvid('redcap_overview_brief01','Brief Overview of REDCap')\" style='text-decoration:underline;'>{$lang['info_37']}</a>{$lang['period']}
			{$lang['info_38']}
			<a href='index.php?action=training' style='text-decoration:underline;'>{$lang['info_06']}</a> 
			{$lang['global_14']}{$lang['period']}<br>
		</p>";	

// Show grant name to cite (if exists)
if (trim($homepage_grant_cite) != "") {
	print  "<p>
				{$lang['info_08']} 
				(<b class='notranslate'>$homepage_grant_cite</b>).
			</p>";
}

// Notice about usage for human subject research
?>
<p style='color:#C00000;'>
	<i><?php echo $lang['global_03'] ?>:</i> <?php echo $lang['info_10'] ?>
</p>

<?php

print  "<p>
			{$lang['info_11']} 
			<a class='notranslate' style='font-size:12px;text-decoration:underline;' href='mailto:$homepage_contact_email'>$homepage_contact</a>.
		</p>";

// Show custom text defined by REDCap adminstrator on System Config page
if (trim($homepage_custom_text) != "") {
	$homepage_custom_text = nl2br(filter_tags(label_decode($homepage_custom_text)));
	print "<div class='round notranslate' 
		style='background-color:#E8ECF0;border:1px solid #99B5B7;margin:15px 10px 0 0;padding:5px 5px 5px 10px;'>$homepage_custom_text</div>";
}

print  "</td>";
print  "<td valign='top'>";

// Features of REDCap (right-hand side)
print  '<br>
		<div style="position:relative;left:10px;margin-right:0;padding:0px 0px 0px 0px;background:url('.APP_PATH_IMAGES.'graybox_home.png); 
			background-repeat:no-repeat;background-position:left top;">
			<div style="width:275px;margin-right:0;padding:15px 15px 40px 25px;background:url('.APP_PATH_IMAGES.'graybox_bottom.png); 
				background-repeat:no-repeat;background-position:left bottom;">
				<h2 style="font-weight:bold;font-family:Arial;font-size:13px;text-align:center;padding-bottom:10px;">
					'.$lang['info_12'].'
				</h2>
				<p style="font-size:11px;line-height:1.1em;">
					<b>'.$lang['info_13'].'</b> - '.$lang['info_14'].'
				</p>
				<p style="font-size:11px;line-height:1.1em;">
					<b>'.$lang['info_15'].'</b> - '.$lang['info_16'].'
				</p>
				<p style="font-size:11px;line-height:1.1em;">
					<b>'.$lang['info_19'].'</b> - '.$lang['info_20'].'
				</p>
				<p style="font-size:11px;line-height:1.1em;">
					<b>'.$lang['info_23'].'</b> - '.$lang['info_24'].'
				</p>
				<p style="font-size:11px;line-height:1.1em;">
					<b>'.$lang['global_25'].'</b> - '.$lang['info_18'].'
				</p>
				<p style="font-size:11px;line-height:1.1em;">
					<b>'.$lang['info_32'].'</b> - '.$lang['info_33'].'
				</p>';
					
// Display ability to upload files via Send-It, if enabled
if ($sendit_enabled != 0) {
	print "		<p style='font-size:11px;line-height:1.1em;'>
					<b>{$lang['info_21']}</b> - {$lang['info_22']}
				</p>";
}

print " 		<p style='font-size:11px;line-height:1.1em;'>
					<b>{$lang['info_25']}</b> - {$lang['info_26']}
				</p>
				<p style='font-size:11px;line-height:1.1em;'>
					<b>{$lang['info_27']}</b> - {$lang['info_28']}, ";
					
// Display ability to upload files, if enabled
if ($edoc_field_option_enabled) {
	print "			{$lang['info_29']}, ";
}

print " 			{$lang['info_30']}
				</p>";
					
// Display info about API, if enabled
if ($api_enabled) {
	print "		<p style='font-size:11px;line-height:1.1em;'>
					<b>REDCap API</b> - {$lang['info_31']}
				</p>";
}

print " 		
			</div>
		</div>";

print  "</td></tr>
		</table>";
