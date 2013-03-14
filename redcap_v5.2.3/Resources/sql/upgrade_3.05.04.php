<?php

print "-- Fix logged for any deleted projects
update redcap_log_view set project_id = null where project_id not in (" . pre_query("select project_id from redcap_projects") . ");
update redcap_log_event set project_id = 0 where project_id not in (" . pre_query("select project_id from redcap_projects") . ");
";



## Find all projects with messed up edoc fields
$sql0 = "select distinct m.project_id from redcap_data d, redcap_metadata m where m.project_id = d.project_id 
		and m.field_name = d.field_name and m.element_type = 'file' and d.value = '[document]'";
$q0 = db_query($sql0);
if (db_num_rows($q0) < 1)
{
	//print "<br>-- GOOD NEWS! Did not find any messed up edoc fields for any REDCap project!";
}
else
{
	print "-- Fix for any 'file' upload fields whose file has been overwritten accidentally during a data import\n";
	$all_projects_sql = 0;
	while ($row0 = db_fetch_assoc($q0))
	{
		$this_project_id = $row0['project_id'];
		$file_fields = array();
		$sql = "select distinct field_name from redcap_data where project_id = $this_project_id and value = '[document]'";
		$q = db_query($sql);
		if (db_num_rows($q) < 1)
		{
			//print "<br>-- Did not find any messed up edoc fields for project_id $this_project_id.";
		}
		else
		{
			while ($row = db_fetch_assoc($q))
			{
				$file_fields[] = $row['field_name'];
			}

			// Loop through logging records to get exact record, event_id and field_name
			$logging_info = array();
			$sql = "select pk as record, event_id, data_values from redcap_log_event where project_id = $this_project_id 
					and data_values like '% = \'[document]\'%' and description = 'Update record (import)' order by abs(pk), pk, event_id;";
			$q = db_query($sql);
			while ($row = db_fetch_assoc($q))
			{
				foreach ($file_fields as $this_field)
				{
					if (strpos($row['data_values'], "$this_field = '[document]'") !== false)
					{
						// Get latest logging of where the file was uploaded
						$sql2 = "select sql_log from redcap_log_event where project_id = $this_project_id and pk = '" . prep($row['record']) . "'
								and event_id = {$row['event_id']} and data_values = '$this_field' and description = 'Upload document' 
								and event = 'DOC_UPLOAD' order by log_event_id desc limit 1";
						$q2 = db_query($sql2);
						$sql_string = db_result($q2, 0);
						//print "<br><br>-- $this_project_id, '{$row['record']}', {$row['event_id']}, '$this_field'<br>-- $sql_string<br>";
						// Get doc_id from the query (parse it!)
						$doc_id = substr($sql_string, strpos($sql_string, "'$this_field',")+strlen("'$this_field',"));
						$doc_id = preg_replace("/[^0-9]/", '', $doc_id);
						// Make sure it's a number
						if (is_numeric($doc_id))
						{
							// Make sure it actually exists in edocs table and has not been deleted
							$sql3 = "select 1 from redcap_edocs_metadata where doc_id = $doc_id and delete_date is null";
							$q3 = db_query($sql3);
							if (db_num_rows($q3) > 0)
							{
								// Add to array
								$logging_info[] = array('project_id'=>$this_project_id, 'record'=>$row['record'], 'event_id'=>$row['event_id'], 
														'field_name'=>$this_field, 'value'=>$doc_id);
							}
							else
							{
								//print "-- NO ACTION: FILE HAS ALREADY BEEN DELETED BY USER";
							}
						}
						else
						{
							//print "-- ERROR: NOT NUMERIC!";
						}
					}
				}
			}

			// Now loop through all our logging info and update the data table if value = '[document]' for any of our 'file' fields
			$sql_all = array();
			foreach ($logging_info as $attr)
			{
				$sql = "UPDATE redcap_data SET value = '{$attr['value']}' WHERE project_id = $this_project_id "
					. "AND record = '" . prep($attr['record']) . "' AND event_id = {$attr['event_id']} AND field_name = '{$attr['field_name']}' "
					. "AND value = '[document]'";
				print "$sql;\n";
				$sql_all[] = $sql;
			}
			if (!empty($sql_all))
			{
				$all_projects_sql++;
				print "INSERT INTO redcap_log_event 
						(project_id, ts, user, ip, page, event, object_type, sql_log, pk, event_id, data_values, description, change_reason) 
						VALUES 
						($this_project_id, ".date('YmdHis').", 'ADMIN', '".getIpAddress()."', '".PAGE."', 'OTHER', 'redcap_data', ".checkNull(implode(";\n", $sql_all)).", 
						'', NULL, NULL, NULL, NULL);\n";
			} else {
				//print "<br>-- Did not find any messed up edoc fields for project_id $this_project_id.";
			}
			
		}
		
	}
	
}
