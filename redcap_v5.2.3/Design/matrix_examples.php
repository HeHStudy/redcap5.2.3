<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

// Disable authentication to allow this page to be used as a general linkable example for people
define("NOAUTH", true);
require_once dirname(dirname(__FILE__)) . "/Config/init_global.php";
// Display text and images
print   RCView::div(array('style'=>'padding:10px 0;font-family:arial;max-width:800px;'),
			($isAjax ? '' : RCView::h3('',$lang['design_359'])) . 
			$lang['design_357'] . RCView::br() . RCView::br() . 
			RCView::img(array('src'=>'matrix_example2.png')) . RCView::br() . RCView::br() . 
			RCView::img(array('src'=>'matrix_example1.png'))
		);