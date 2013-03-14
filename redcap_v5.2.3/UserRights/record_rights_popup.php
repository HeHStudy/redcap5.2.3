<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

?>
<!-- Explanation: Settings pertaining to project records -->
<p>
	<?php echo $lang['rights_124'] ?>
</p>
<p>
	<img class="imgfix" src="<?php echo APP_PATH_IMAGES ?>blog_plus.png">
	<u style="font-weight:bold;"><?php echo $lang['rights_99'] ?></u><br>
	<?php echo $lang['rights_125'] ?> "<i><?php echo $table_pk_label ?></i>" <?php echo $lang['rights_126'] ?>
</p>
<p>
	<img class="imgfix" src="<?php echo APP_PATH_IMAGES ?>blog_pencil.png">
	<u style="font-weight:bold;"><?php echo $lang['rights_100'] ?></u><br>
	<?php echo $lang['rights_127'] ?> "<i><?php echo $table_pk_label ?></i>" <?php echo $lang['rights_128'] ?>
</p>
<p>
	<img class="imgfix" src="<?php echo APP_PATH_IMAGES ?>blog_minus.png">
	<u style="font-weight:bold;"><?php echo $lang['rights_101'] ?></u><br>
	<?php echo $lang['rights_129'] ?>
</p>