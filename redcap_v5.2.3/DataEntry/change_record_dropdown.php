<?php

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

// Change number of drop-downs displayed on data entry page
if (isset($_GET['show_which_records']) && ($_GET['show_which_records'] == '1' || $_GET['show_which_records'] == '0' || $_GET['show_which_records'] == '2')) {
	db_query("update redcap_projects set show_which_records = {$_GET['show_which_records']} where project_id = $project_id");
}

// Redirect back
redirect(APP_PATH_WEBROOT . "DataEntry/index.php?pid=$project_id" . (isset($_GET['page']) ? "&page=" . $_GET['page'] : ""));	
