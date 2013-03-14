<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

// Config for non-project pages
require_once dirname(__FILE__) . "/Config/init_global.php";

// Call the real page. This page is just a shell for the index file in the Home directory.
require_once APP_PATH_DOCROOT . "Home/index.php";