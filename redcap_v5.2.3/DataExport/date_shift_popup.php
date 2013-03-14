<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';


print "<html>
<head>
<title>{$lang['date_shift_01']}</title>
<link rel='stylesheet' type='text/css' href='" . APP_PATH_CSS . "style.css' media='screen,print'>
</head>
<body>

<div style='font-family:Verdana,Arial;font-size:18px;padding-bottom:10px;'><b>{$lang['date_shift_01']}</b></div>

<p><b>{$lang['date_shift_02']}</b><br>
{$lang['date_shift_03']} $date_shift_max {$lang['date_shift_04']}</p>

<p>{$lang['date_shift_05']} $date_shift_max {$lang['date_shift_06']} $table_pk_label {$lang['date_shift_07']}</p>

<p><b>{$lang['date_shift_08']}</b><br>
{$lang['date_shift_09']}
</p>

<br>
<a href='javascript:self.close();' style='font-family:Arial;font-size:11px;color:#000066;text-decoration:underline;'>{$lang['date_shift_10']}</a></div>
</body>
</html>";
	
?>