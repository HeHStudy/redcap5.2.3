<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

// Only show links to Vanderbilt public projects if logged in
if (isset($userid) && $userid != "" && $auth_meth != "none") 
{
	if (SERVER_NAME == 'redcap.vanderbilt.edu')
	{
		$vanderbilt_prefix  = APP_PATH_WEBROOT . "index.php?pid=";
		$trad_db 			= $vanderbilt_prefix . "341";
		$long_db 			= $vanderbilt_prefix . "557";
		$long_sched_db  	= $vanderbilt_prefix . "559";
		$sched_db 			= $vanderbilt_prefix . "560";
		$oper_db 			= $vanderbilt_prefix . "643";
		$survey_proj		= $vanderbilt_prefix . "5911";
	}
	else
	{
		$vanderbilt_prefix  = "https://www.mc.vanderbilt.edu/victr/dcc/redcap/demos/redcap_v4.0.0/index.php?pid=";
		$trad_db 			= $vanderbilt_prefix . "1";
		$long_db 			= $vanderbilt_prefix . "2";
		$long_sched_db  	= $vanderbilt_prefix . "3";
		$sched_db 			= $vanderbilt_prefix . "4";
		$oper_db 			= $vanderbilt_prefix . "5";
		$survey_proj		= $vanderbilt_prefix . "286";
	}
}
else
{
	$trad_db = $long_db = $long_sched_db = $sched_db = $oper_db = $survey_proj = "#\" onclick=\"alert('".cleanHtml($lang['training_res_01'])."');return false;";
}

?>

<style type="text/css">
.smTitle { font-weight:normal;font-size:12px; }
.bigTitle { color:#800000;font-weight:bold;width:200px;font-size:14px;text-align:center;padding:8px; }
.descrip { text-align:left;font-family:tahoma;font-size:11px;padding:6px 9px; }
.trnHdr { font-weight:bold;background-color:#ddd;border:1px solid #888;text-align:center;padding:4px; }
td.exvid { width:80px;text-align:center;padding:5px; }
</style>


<div style="font-size:18px;font-weight:bold;">
	<img src='<?php echo APP_PATH_IMAGES ?>video_small.png' style='position:relative;top:2px;'> 
	<?php echo $lang['training_res_02'] ?>
</div>



<!-- PRELIMIARY VIDEOS -->
<p style='padding-top:20px;'>
	<span style="font-size:14px;font-weight:bold;"><?php echo $lang['training_res_03'] ?></span><br>
	<?php echo $lang['training_res_04'] ?>
</p>
<table border=1 cellpadding=4 cellspacing=0 style='border-collapse:collapse;border:1px solid #888;width:100%;text-align:center;'>
	<tr>
		<td class='trnHdr'>
			<?php echo $lang['training_res_05'] ?>
		</td>
		<td class='trnHdr'>
			<?php echo $lang['global_20'] ?>
		</td>
		<td class='trnHdr'>
			<?php echo $lang['training_res_07'] ?>
		</td>
	</tr>
	<tr>
		<td class='bigTitle'>
			<?php echo $lang['bottom_58'] ?>
		</td>
		<td class='descrip'>
			<?php echo $lang['training_res_66'] ?>
		</td>
		<td class='exvid'>
			<a onclick="popupvid('redcap_overview_brief01.flv','A Brief Overview of REDCap')" href="javascript:;" 
				style="font-size:12px;text-decoration:underline;"><img src='<?php echo APP_PATH_IMAGES ?>video.png'></a>
			<div style='color:#555;font-size:11px;'>4 <?php echo $lang['config_functions_72'] ?></div>
		</td>
	</tr>
	<tr>
		<td class='bigTitle'>
			<?php echo $lang['bottom_57'] ?>
		</td>
		<td class='descrip'>
			<?php echo $lang['training_res_09'] ?>
		</td>
		<td class='exvid'>
			<a onclick="popupvid('redcap_overview02.flv','A General Overview of REDCap')" href="javascript:;" 
				style="font-size:12px;text-decoration:underline;"><img src='<?php echo APP_PATH_IMAGES ?>video.png'></a>
			<div style='color:#555;font-size:11px;'><?php echo $lang['training_res_64'] ?></div>
		</td>
	</tr>
	<tr>
		<td class='bigTitle' rowspan='3'>
			<?php echo $lang['training_res_11'] ?>
		</td>
		<td class='descrip'>
			<b><?php echo $lang['training_res_12'] ?></b><br>
			<?php echo $lang['training_res_13'] ?>			
		</td>
		<td class='exvid'>
			<a onclick="popupvid('form_editor_fields01.flv','The Online Form Editor')" href="javascript:;" 
				style="font-size:12px;text-decoration:underline;"><img src='<?php echo APP_PATH_IMAGES ?>video.png'></a>
			<div style='color:#555;font-size:11px;'><?php echo $lang['training_res_14'] ?></div>
		</td>
	</tr>
	<tr>
		<td class='descrip'>
			<b><?php echo $lang['training_res_15'] ?></b><br>
			<?php echo $lang['training_res_16'] ?> 
			<a href="<?php print APP_PATH_WEBROOT ?>Design/data_dictionary_demo_download.php" 
				style="text-decoration:underline;font-family:tahoma;font-size:11px;"><?php echo $lang['training_res_17'] ?></a>.
		</td>
		<td class='exvid'>
			<a onclick="popupvid('redcap_data_dictionary01.flv','The Data Dictionary')" href="javascript:;" 
				style="font-size:12px;text-decoration:underline;"><img src='<?php echo APP_PATH_IMAGES ?>video.png'></a>
			<div style='color:#555;font-size:11px;'><?php echo $lang['training_res_18'] ?></div>
		</td>
	</tr>
	<tr>
		<td class='descrip'>
			<b><?php echo $lang['bottom_36'] ?></b><br>
			<?php echo $lang['training_res_67'] ?>
		</td>
		<td class='exvid'>
			<a onclick="popupvid('field_types02.flv','<?php echo cleanHtml($lang['bottom_36']) ?>')" href="javascript:;" 
				style="font-size:12px;text-decoration:underline;"><img src='<?php echo APP_PATH_IMAGES ?>video.png'></a>
			<div style='color:#555;font-size:11px;'>4 <?php echo $lang['config_functions_72'] ?></div>
		</td>
	</tr>
	
	<tr>
		<td class='bigTitle'>
			<?php echo $lang['bottom_56'] ?>
		</td>
		<td class='descrip'>
			<?php echo $lang['training_res_65'] ?>
		</td>
		<td class='exvid'>
			<a onclick="popupvid('data_entry_overview_01.flv','An Overview of Basic Data Entry in REDCap')" href="javascript:;" 
			   style="font-size:12px;text-decoration:underline;font-weight:normal;"><img src='<?php echo APP_PATH_IMAGES ?>video.png'></a>
			<div style='color:#555;font-size:11px;'>
				16 <?php echo $lang['config_functions_72'] ?>
			</div>
		</td>
	</tr>
	<tr>
		<td class='bigTitle'>
			<?php echo $lang['training_res_19'] ?>
		</td>
		<td class='descrip'>
			<?php echo $lang['training_res_20'] ?>
		</td>
		<td class='exvid'>
			<a onclick="popupvid('scheduling01.flv','The REDCap Scheduling Module')" href="javascript:;" 
			   style="font-size:12px;text-decoration:underline;font-weight:normal;"><img src='<?php echo APP_PATH_IMAGES ?>video.png'></a>
			<div style='color:#555;font-size:11px;'><?php echo $lang['training_res_21'] ?></div>
		</td>
	</tr>
</table>




<!-- Types of REDCap Projects -->

<p id='db_types' style='padding-top:40px;'>
	<span style="font-size:14px;font-weight:bold;"><?php echo $lang['training_res_22'] ?></span><br>
	<?php echo $lang['training_res_23'] ?>
</p>
<table border=1 cellpadding=4 cellspacing=0 style='border-collapse:collapse;border:1px solid #888;width:100%;text-align:center;'>
	<tr>
		<td class='trnHdr'>
			<?php echo $lang['training_res_24'] ?>
		</td>
		<td class='trnHdr'>
			<?php echo $lang['global_20'] ?>
		</td>
		<td class='trnHdr'>
			<?php echo $lang['training_res_25'] ?>
		</td>
		<td class='trnHdr'>
			<?php echo $lang['training_res_07'] ?>
		</td>
	</tr>
	<tr>
		<td class='bigTitle'>
			<?php echo $lang['training_res_26'] ?>
			<div class="smTitle"><?php echo $lang['training_res_27'] ?></div>
		</td>
		<td class='descrip'>
			<?php echo $lang['training_res_28'] ?>
		</td>
		<td class='exvid'>
			<a href="<?php echo $trad_db ?>" target='_blank'><img src='<?php echo APP_PATH_IMAGES ?>search.png'></a>
		</td>
		<td class='exvid'>
			<a onclick="popupvid('traditional_db01.flv','The Traditional REDCap Project')" href="javascript:;" 
			   style="font-size:12px;text-decoration:underline;font-weight:normal;"><img src='<?php echo APP_PATH_IMAGES ?>video.png'></a>
			<div style='color:#555;font-size:11px;'><?php echo $lang['training_res_29'] ?></div>
		</td>
	</tr>
	<tr>
		<td class='bigTitle'>
			<?php echo $lang['training_res_60'] ?>
		</td>
		<td class='descrip'>
			<?php echo $lang['training_res_61'] ?>
		</td>
		<td class='exvid'>
			<a href="<?php echo $survey_proj ?>" target='_blank'><img src='<?php echo APP_PATH_IMAGES ?>search.png'></a>
		</td>
		<td class='exvid'>
			<a onclick="popupvid('redcap_survey_basics02.flv','Single Survey Project in REDCap')" href="javascript:;" 
			   style="font-size:12px;text-decoration:underline;font-weight:normal;"><img src='<?php echo APP_PATH_IMAGES ?>video.png'></a>
			<div style='color:#555;font-size:11px;'><?php echo $lang['training_res_29'] ?></div>
		</td>
	</tr>
	<tr>
		<td class='bigTitle'>
			<?php echo $lang['training_res_30'] ?>
			<div class="smTitle"><?php echo $lang['training_res_31'] ?></div>
		</td>
		<td class='descrip'>
			<?php echo $lang['training_res_32'] ?>
		</td>
		<td class='exvid'>
			<a href="<?php echo $long_db ?>" target='_blank'><img src='<?php echo APP_PATH_IMAGES ?>search.png'></a>
		</td>
		<td class='exvid'>
			<a onclick="popupvid('longitudinal_db01.flv','The Longitudinal REDCap Project')" href="javascript:;" 
			   style="font-size:12px;text-decoration:underline;font-weight:normal;"><img src='<?php echo APP_PATH_IMAGES ?>video.png'></a>
			<div style='color:#555;font-size:11px;'><?php echo $lang['training_res_33'] ?></div>
		</td>
	</tr>
	<tr>
		<td class='bigTitle'>
			<?php echo $lang['training_res_34'] ?>
			<div class="smTitle"><?php echo $lang['training_res_35'] ?></div>
		</td>
		<td class='descrip'>
			<?php echo $lang['training_res_36'] ?>
		</td>
		<td class='exvid'>
			<a href="<?php echo $long_sched_db ?>" target='_blank'><img src='<?php echo APP_PATH_IMAGES ?>search.png'></a>
		</td>
		<td class='exvid'>
			<a onclick="popupvid('longitudinal_sched_db01.flv','The Longitudinal REDCap Project with Scheduling')" href="javascript:;" 
			   style="font-size:12px;text-decoration:underline;font-weight:normal;"><img src='<?php echo APP_PATH_IMAGES ?>video.png'></a>
			<div style='color:#555;font-size:11px;'><?php echo $lang['training_res_37'] ?></div>
		</td>
	</tr>
	<tr>
		<td class='bigTitle'>
			<?php echo $lang['training_res_46'] ?>
			<div class="smTitle"><?php echo $lang['training_res_47'] ?></div>
		</td>
		<td class='descrip'>
			<?php echo $lang['training_res_48'] ?>
		</td>
		<td class='exvid'>
			<a href="<?php echo $oper_db ?>" target='_blank'><img src='<?php echo APP_PATH_IMAGES ?>search.png'></a>
		</td>
		<td class='exvid'>
			<a onclick="popupvid('operational_db01.flv','Using REDCap for Operational Use and Non-clinical Data Collection')" href="javascript:;" 
			   style="font-size:12px;text-decoration:underline;font-weight:normal;"><img src='<?php echo APP_PATH_IMAGES ?>video.png'></a>
			<div style='color:#555;font-size:11px;'><?php echo $lang['training_res_58'] ?></div>
		</td>
	</tr>
</table>


<p style='padding-top:40px;'>
	<span style="font-size:14px;font-weight:bold;"><?php echo $lang['training_res_49'] ?></span><br>
	<?php echo $lang['training_res_50'] ?>
</p>
<table border=1 cellpadding=4 cellspacing=0 style='border-collapse:collapse;border:1px solid #888;width:100%;text-align:center;'>
	<tr>
		<td class='trnHdr'>
			<?php echo $lang['training_res_51'] ?>
		</td>
		<td class='trnHdr'>
			<?php echo $lang['global_20'] ?>
		</td>
		<td class='trnHdr'>
			<?php echo $lang['training_res_07'] ?>
		</td>
	</tr>
	<tr>
		<td class='bigTitle'>
			<?php echo $lang['global_22'] . "<br>" . $lang['training_res_52'] ?>
		</td>
		<td class='descrip'>
			<?php echo $lang['training_res_53'] ?>
		</td>
		<td class='exvid'>
			<a onclick="popupvid('data_access_groups01.flv','Data Access Groups')" href="javascript:;" 
			   style="font-size:12px;text-decoration:underline;font-weight:normal;"><img src='<?php echo APP_PATH_IMAGES ?>video.png'></a>
			<div style='color:#555;font-size:11px;'><?php echo $lang['training_res_33'] ?></div>
		</td>
	</tr>

	<tr>
		<td class='bigTitle'>
			<?php echo $lang['bottom_33'] ?>
		</td>
		<td class='descrip'>
			<?php echo $lang['training_res_68'] ?>
		</td>
		<td class='exvid'>
			<a onclick="popupvid('calendar_and_cal_data_entry01.flv','<?php echo cleanHtml($lang['bottom_33']) ?>')" href="javascript:;" 
			   style="font-size:12px;text-decoration:underline;font-weight:normal;"><img src='<?php echo APP_PATH_IMAGES ?>video.png'></a>
			<div style='color:#555;font-size:11px;'>5 <?php echo $lang['config_functions_72'] ?></div>
		</td>
	</tr>
	
	<tr>
		<td class='bigTitle'>
			<?php echo $lang['training_res_56'] ?>
		</td>
		<td class='descrip'>
			<?php echo $lang['training_res_57'] ?>
		</td>
		<td class='exvid'>
			<a onclick="popupvid('locking01.flv','Record Locking Functionality')" href="javascript:;" 
			   style="font-size:12px;text-decoration:underline;font-weight:normal;"><img src='<?php echo APP_PATH_IMAGES ?>video.png'></a>
			<div style='color:#555;font-size:11px;'><?php echo $lang['training_res_33'] ?></div>
		</td>
	</tr
	
	<tr>
		<td class='bigTitle'>
			<?php echo $lang['training_res_69'] ?>
		</td>
		<td class='descrip'>
			<?php echo $lang['training_res_70'] ?>
		</td>
		<td class='exvid'>
			<a onclick="popupvid('define_events01.flv','<?php echo cleanHtml($lang['training_res_69']) ?>')" href="javascript:;" 
			   style="font-size:12px;text-decoration:underline;font-weight:normal;"><img src='<?php echo APP_PATH_IMAGES ?>video.png'></a>
			<div style='color:#555;font-size:11px;'>4 <?php echo $lang['config_functions_72'] ?></div>
		</td>
	</tr>
	
</table>

<br><br>

</div>
<?php
