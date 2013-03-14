<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


// Config
require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

// Make sure the rule_id is numeric
if (!is_numeric($_POST['rule_id'])) exit('[ERROR!]');

// Instantiate DataQuality object
$dq = new DataQuality();


if ($_POST['rule_id'] == '0')
{
	## REORDER RULES
	if (isset($_POST['rule_ids']))
	{
		// Validation of number of rules submitted
		$rule_ids = array();
		foreach (array_keys($dq->getRules()) as $rule_id)
		{
			// Ignore the pre-defined rules
			if (is_numeric($rule_id)) $rule_ids[] = $rule_id;
		}
		// Loop through the submitted rule_ids and validation them
		$rule_ids_submitted = array();
		foreach (explode(",", trim($_POST['rule_ids'])) as $rule_id)
		{
			// Ensure it's a real rule_id
			if (in_array($rule_id, $rule_ids))
			{
				$rule_ids_submitted[] = $rule_id;
			}
		}
		if (count($rule_ids) != count($rule_ids_submitted)) exit('0');
		// Loop through new order of links passed as CSV string, and save the order
		$counter = 1;
		$sql_all = "";
		foreach ($rule_ids_submitted as $rule_id)
		{
			// Update the table with the new order value
			$sql = "update redcap_data_quality_rules set rule_order = $counter
					where rule_id = $rule_id and project_id = " . PROJECT_ID;
			$q = db_query($sql);
			if (!$q) exit('0');
			$sql_all .= $sql . ";\n";
			$counter++;
		}
		// Log the event
		log_event($sql_all,"redcap_data_quality_rules","MANAGE",PROJECT_ID,"project_id = ".PROJECT_ID,"Reorder data quality rules");
		// Set response
		exit('1');
	}
	
	## ADD NEW RULE
	else
	{
		// Clean the rule name submitted
		$_POST['rule_name'] = html_entity_decode($_POST['rule_name'], ENT_QUOTES);
		
		// Clean the rule logic submitted
		$_POST['rule_logic'] = html_entity_decode($_POST['rule_logic'], ENT_QUOTES);
		
		// Set value of real_time_execute
		$_POST['real_time_execute'] = (isset($_POST['real_time_execute']) && $_POST['real_time_execute'] == '1') ? '1' : '0';
		
		// Get the next order number
		$sql = "select max(rule_order) from redcap_data_quality_rules where project_id = " . PROJECT_ID;
		$q = db_query($sql);
		$max_rule_order = db_result($q, 0);
		$next_rule_order = (is_numeric($max_rule_order) ? $max_rule_order+1 : 1);

		// Insert into table
		$sql = "insert into redcap_data_quality_rules (project_id, rule_order, rule_name, rule_logic, real_time_execute) values
				(" . PROJECT_ID . ", $next_rule_order, '" . prep($_POST['rule_name']) . "', '" . prep($_POST['rule_logic']) . "',
				'{$_POST['real_time_execute']}')";
		$q = db_query($sql);
		if (!$q) exit('[ERROR!]');
		$new_rule_id = db_insert_id();
		
		// Get html for the rules table
		$ruleTableHtml = $dq->displayRulesTable();
		
		// Log the event
		log_event($sql,"redcap_data_quality_rules","MANAGE",$new_rule_id,"rule_id = $new_rule_id","Create data quality rule");
		
		// Send back JSON
		exit('{"new_rule_id":' . $new_rule_id . ',"payload":"' . cleanHtml2($ruleTableHtml) . '"}');
	}
}


## ENABLE REAL-TIME EXECUTION FOR AN EXISTING RULE
elseif (isset($_POST['action']) && $_POST['action'] == 'enableRTE' && isset($_POST['enable']))
{
	// Set value
	$_POST['enable'] = ($_POST['enable'] == '1') ? '1' : '0';
	// Update the table with the new value
	$sql = "update redcap_data_quality_rules set real_time_execute = '" . prep($_POST['enable']) . "'
			where rule_id = {$_POST['rule_id']} and project_id = " . PROJECT_ID;
	$q = db_query($sql);
	if (!$q) exit('[ERROR!]');	
	// Log the event
	log_event($sql,"redcap_data_quality_rules","MANAGE",PROJECT_ID,"project_id = ".PROJECT_ID,"Enable real-time execution for data quality rule");
}


## DELETE RULE
elseif (isset($_POST['action']) && $_POST['action'] == 'delete')
{
	// Delete rule from table
	$sql = "delete from redcap_data_quality_rules where rule_id = {$_POST['rule_id']} and project_id = " . PROJECT_ID;
	$q = db_query($sql);
	if (!$q) exit('0');
	
	// Reorder the rules now that this one is gone
	$dq->reorder();
	
	// Log the event
	log_event($sql,"redcap_data_quality_rules","MANAGE",$_POST['rule_id'],"rule_id = {$_POST['rule_id']}","Delete data quality rule");
			
	// Get html for the rules table
	exit($dq->displayRulesTable());
}


## EDIT RULE NAME
elseif (isset($_POST['rule_name']))
{
	// Clean the rule name submitted
	$_POST['rule_name'] = html_entity_decode($_POST['rule_name'], ENT_QUOTES);

	// Update the table with the new name
	$sql = "update redcap_data_quality_rules set rule_name = '" . prep($_POST['rule_name']) . "'
			where rule_id = {$_POST['rule_id']} and project_id = " . PROJECT_ID;
	$q = db_query($sql);
	if (!$q) exit('[ERROR!]');
	
	// Log the event
	log_event($sql,"redcap_data_quality_rules","MANAGE",PROJECT_ID,"project_id = ".PROJECT_ID,"Edit data quality rule");
}


## EDIT RULE LOGIC
else
{
	// Clean the rule logic submitted
	$_POST['rule_logic'] = html_entity_decode($_POST['rule_logic'], ENT_QUOTES);

	// Update the table with the new logic
	$sql = "update redcap_data_quality_rules set rule_logic = '" . prep($_POST['rule_logic']) . "'
			where rule_id = {$_POST['rule_id']} and project_id = " . PROJECT_ID;
	$q = db_query($sql);
	if (!$q) exit('[ERROR!]');
	
	// Log the event
	log_event($sql,"redcap_data_quality_rules","MANAGE",PROJECT_ID,"project_id = ".PROJECT_ID,"Edit data quality rule");
}

// Get new rule info
$rule_info = $dq->getRule($_POST['rule_id']);

// Return the new name/logic that was just saved
print (isset($_POST['rule_name']) ? $rule_info['name'] : $rule_info['logic']);
