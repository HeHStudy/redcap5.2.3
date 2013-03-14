<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


// This page displays the "Returning?" box at the top left of a public survey page

?>

<div id="return_corner" class="trigger" style="padding-left:5px;white-space:nowrap;">
	<img src="<?php echo APP_PATH_IMAGES ?>arrow_circle_315.png" class="imgfix">&nbsp;<a href="<?php echo APP_PATH_SURVEY_FULL ?>?s=<?php echo $_GET['s'] ?>&__return=1" style="color:#3089D4;"><b><?php echo $lang['survey_22'] ?></b></a>
</div>

<table id="dpop" class="popup">
	<tr>
		<td class="left"></td>
		<td>
			<table class="popup-contents">
				<tr>							
					<td style="padding:5px;">
					<img src="<?php echo APP_PATH_IMAGES ?>arrow_circle_315.png" class="imgfix">
					<span style="color:#3089D4;"><b><?php echo $lang['survey_22'] ?></b> <?php echo $lang['survey_23'] ?></span>
					<br><br>
					<?php echo $lang['survey_24'] ?>
					<div style="text-align:center;padding:10px;">
						<button class="jqbuttonmed" style="color:#800000;" onclick="window.location.href='<?php echo APP_PATH_SURVEY_FULL ?>?s=<?php echo $_GET['s'] ?>&__return=1';"><?php echo $lang['survey_25'] ?></button>
					</div>
					</td>
				</tr>
			</table>
		</td>
		<td class="right"></td>    
	</tr>
</table>
