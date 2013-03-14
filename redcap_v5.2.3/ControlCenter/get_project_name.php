<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . "/Config/init_project.php";

print  filter_tags(label_decode($app_title)) .
	   "<div style='padding:5px 0 0;font-size:11px;color:#666;'>
			{$lang['control_center_108']}
		</div>";