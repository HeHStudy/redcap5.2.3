<?php


## Merge the "Context Detail" and "Record Selection Drop-down Customization" features (the latter trumps the former)

// Parse context detail (return array of strings to separate field names from static text)
function parse_context_detail($context_detail)
{
	$context_detail = stripslashes($context_detail); //Remove any possible slashes (slashes were included in legacy format)
	$context_detail = str_replace("'","\"",substr($context_detail,1,-1)); //Remove parentheses on ends and replace single quotes with double
	//Parse context_detail to use as query. Separate by commas, but identify any commas inside quotes which are static strings.
	$context_detail_pieces = explode(",", $context_detail);
	$within_quote = false; $i = 0;
	foreach ($context_detail_pieces as $this_piece) {
		$quote_count = substr_count($this_piece,'"');
		if ($quote_count > 0) {
			if ($within_quote) {
				//End quote
				$context_detail_array[$i] .= ",$this_piece";
				$context_detail_array[$i] = trim($context_detail_array[$i]);
				$within_quote = false;
				$i++;									
			} else {
				$context_detail_array[$i] = $this_piece;
				if ($quote_count == 2) {
					//Begin AND End quote
					$i++;	
				} else {
					//Begin quote
					$within_quote = true;
				}
			}	
		} else {
			if ($within_quote) {
				//In middle of quote
				$context_detail_array[$i] .= ",$this_piece";				
			} else {
				//Not in quote
				$context_detail_array[$i] = $this_piece;
				$context_detail_array[$i] = trim($context_detail_array[$i]);
				$i++;	
			}
		}
	}
	// Parse array and transform to new format
	$context = '';
	foreach ($context_detail_array as $this_field) 
	{
		if (substr_count($this_field, '"') > 0) {
			$context .= str_replace('"', '', $this_field); //Remove quotes for display
		} else {
			$context .= "[$this_field]"; // Add square brackets around field names
		}
	}
	return $context;
}

// Pre-set text strings
$pulldown_concat_item = array();
$pulldown_concat_item[0] = " ("; 
$pulldown_concat_item[1] = " : ";
$pulldown_concat_item[2] = " - ";
$pulldown_concat_item[3] = "&#044; ";
$pulldown_concat_item[4] = ") ";
$pulldown_concat_item[5] = ") (";
$pulldown_concat_item[6] = ")&#044; (";	
$pulldown_concat_item[7] = " ";

// Add field first
print "
-- Add secondary ID field
ALTER TABLE `redcap_projects` ADD `secondary_pk` VARCHAR( 100 ) NULL COMMENT 'field_name of seconary identifier';
-- Add field to redcap_projects
ALTER TABLE  `redcap_projects` ADD  `custom_record_label` TEXT NULL;\n";

// Get all values for each project and transform
$sql = "select * from redcap_projects order by project_id";
$q = db_query($sql);
while ($row = db_fetch_assoc($q))
{
	$this_context_detail = trim(html_entity_decode($row['context_detail'], ENT_QUOTES));
	$this_enable_alter_record_pulldown = $row['enable_alter_record_pulldown'];
	// Context detail but NOT custom drop-down
	if ($this_context_detail != "" && !$this_enable_alter_record_pulldown)
	{
		print "update redcap_projects set custom_record_label = '" . prep(parse_context_detail($this_context_detail)) . "' where project_id = {$row['project_id']};\n";
	}
	// Custom drop-down is enabled
	elseif ($this_enable_alter_record_pulldown)
	{
		print "update redcap_projects set custom_record_label = '" . prep(trim(html_entity_decode("{$pulldown_concat_item[$row['pulldown_concat_item1']]}{$row['custom_text1']}[{$row['record_select1']}]{$pulldown_concat_item[$row['pulldown_concat_item2']]}{$row['custom_text2']}[{$row['record_select2']}]{$pulldown_concat_item[$row['pulldown_concat_item3']]}", ENT_QUOTES))) . "' where project_id = {$row['project_id']};\n";
	}
}






// Add FK
print "-- Add foreign key to metadata_archive
update redcap_metadata_archive set pr_id = null where pr_id not in (".pre_query("select pr_id from redcap_metadata_prod_revisions").");
ALTER TABLE  `redcap_metadata_archive` DROP PRIMARY KEY;
ALTER TABLE  `redcap_metadata_archive` CHANGE  `pr_id`  `pr_id` INT( 10 ) NULL;
ALTER TABLE  `redcap_metadata_archive` ADD UNIQUE  `project_field_prid` (  `project_id` ,  `field_name` ,  `pr_id` );
ALTER TABLE  `redcap_metadata_archive` ADD INDEX (  `pr_id` );
ALTER TABLE  `redcap_metadata_archive` ADD FOREIGN KEY (  `pr_id` ) 
	REFERENCES  `redcap_metadata_prod_revisions` (`pr_id`) ON DELETE SET NULL ON UPDATE CASCADE ;
";
