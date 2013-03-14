<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . "/Config/init_global.php";

?>

<p> 
	<b><?php echo $lang['setup_59'] ?></b> <?php echo $lang['setup_60'] ?>
	<a target="_blank" style="text-decoration:underline;" href="<?php echo SHARED_LIB_PATH ?>">REDCap Shared Library</a>. 
	<?php echo $lang['setup_61'] ?>
	<?php echo $lang['setup_63'] ?> <a href='mailto:<?php echo $project_contact_email ?>' style='text-decoration:underline;'><?php echo $lang['bottom_39'] ?></a><?php echo $lang['period'] ?>
	<br><br>
	<button onclick="this.disabled=true;$('#lib_legal_div').show('blind');$('#sharedLibLegal').dialog('option','height',400);"><?php echo $lang['setup_62'] ?></button>
</p>

<div id="lib_legal_div" style="display:none;border-top:1px dashed #aaa;margin-top:20px;padding-top:10px;">
	<h3 style="color:#800000;">Terms of Use</h3>
	<p>Please read all sections of this License Agreement very carefully. It contains information 
	regarding what you may do and what you may not do with the REDCap Shared Data Instrument 
	Library (SDIL) and the instruments developed for the library. The library consists of 
	standardized assessment instruments for various research studies. The instruments in the 
	SDIL will be used only by members of the REDCap Consortium exclusively for research and 
	non-commercial purposes.</p><p><b>PRICING</b><br>You may use the SDIL at no cost subject to the terms of this License Agreement.</p><p><b>LICENSE</b><br>Vanderbilt University grants to you a non-exclusive, revocable license to use the SDIL if 
	you follow each and every restriction set forth in this License Agreement.</p><p><b>SCOPE OF GRANT</b><br>You may:<br>
	</p><ul>
	<li class="column" style="list-style-type:initial;background:white;margin-left:20px;"><i>
		Make copies of the SDIL electronic instruments for the internal, non-commercial use of 
		user's organization or institution only and for no other purpose.
	</i></li>
	<li class="column" style="list-style-type:initial;background:white;margin-left:20px;"><i>
		Customize the appearance of the SDIL electronic instrument using the REDCap application to 
		address the internal needs of user's organization or institution. This includes modifications 
		of the spelling of certain works to reflect local spelling.
	</i></li>
	<li class="column" style="list-style-type:initial;background:white;margin-left:20px;"><i>
		Automate the administration, scoring, and reporting of the SDIL electronic instruments using 
		the REDCap application.
	</i></li>
	</ul>

	<br>You may NOT:<br>
	<ul>
	<li class="column" style="list-style-type:initial;background:white;margin-left:20px;"><i>
		Permit others (including user's agents and contractors) to use the SDIL electronic instruments.
	</i></li>
	<li class="column" style="list-style-type:initial;background:white;margin-left:20px;"><i>
		Charge others for the use of the SDIL electronic instruments.
	</i></li>
	<li class="column" style="list-style-type:initial;background:white;margin-left:20px;"><i>
		Create derivative works based on the SDIL electronic instruments for distribution or usage outside 
		of user's organization or institution.
	</i></li>
	<li class="column" style="list-style-type:initial;background:white;margin-left:20px;"><i>
		Modify or change the content, wording, or organization of the SDIL electronic instruments except 
		to customize its appearance as noted above.
	</i></li>
	<li class="column" style="list-style-type:initial;background:white;margin-left:20px;"><i>
		Use the SDIL electronic instruments in such a way as to condone, encourage, promote or provide 
		pirated copies of the assessment product.
	</i></li>
	<li class="column" style="list-style-type:initial;background:white;margin-left:20px;"><i>
		Modify and/or remove any copyright notices or labels on the SDIL electronic instruments, its measures 
		or in any header of a script source file.
	</i></li>
	<li class="column" style="list-style-type:initial;background:white;margin-left:20px;"><i>
		Distribute the SDIL electronic instruments to others outside of user's organization or institution.
	</i></li>
	</ul><p></p><p><b>DISCLAIMER OF WARRANTY</b><br>THE SDIL IS PROVIDED ON AN "AS IS" BASIS, WITHOUT WARRANTY OF ANY KIND, INCLUDING, WITHOUT LIMITATION, 
	THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NON-INFRINGEMENT. THE ENTIRE 
	RISK AS TO THE QUALITY AND PERFORMANCE OF THE SDIL IS BORNE BY YOU. SHOULD THE ASSESSMENT PRODUCT PROVE 
	DEFECTIVE, YOU AND NOT VANDERBILT UNIVERSITY ASSUME THE ENTIRE COST OF ANY REMEDIATION. THIS DISCLAIMER 
	OF WARRANTY CONSTITUTES AN ESSENTIAL PART OF THIS LICENSE AGREEMENT.</p><p>Vanderbilt University requires agreement to the terms and conditions of this License Agreement as a 
	condition precedent to use of the SDIL. No license is granted unless and until potential licensees have 
	agreed to these terms.</p><p><b>TITLE</b><br>Title, ownership rights, and intellectual property rights in and to the SDIL shall remain with Vanderbilt 
	University. The SDIL is protected by copyright laws and treaties.<br></p><p><b>COPIES, NOTICES, AND CREDITS</b><br>All copies of the SDIL made by Licensee shall include the copyright notice and other notices and credits 
	in the SDIL. Such notices may not be deleted or changed by Licensee.</p><p><b>TERMINATION</b><br>This Agreement will terminate automatically if you fail to comply with the limitations described herein. On 
	termination, you must destroy all copies from the SDIL within 48 hours of such termination.</p><p><b>MISCELLANEOUS</b><br>Vanderbilt University reserves the right to publish a selected list of users of the SDIL. Vanderbilt 
	University reserves the right to change the terms of this License Agreement at any time. Failure to receive 
	notification of a change of this License Agreement does not make those changes invalid. This License 
	Agreement shall be governed by the laws of the State of the Tennessee and may not be transferred or assigned 
	by you. No technical support will be provided.</p><p><b>CREATOR</b><br>Vanderbilt University, Nashville, Tennessee<br>
	"Copyright <?php echo date("Y") ?> Vanderbilt University. All rights reserved." Permission is hereby granted to copy and 
	distribute this License Agreement without modification. This License Agreement may not be modified without 
	the express written permission of its copyright owner.</p>
</div>
