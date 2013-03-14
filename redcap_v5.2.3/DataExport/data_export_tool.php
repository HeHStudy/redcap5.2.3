<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';
// Required files
require_once APP_PATH_DOCROOT . 'ProjectGeneral/form_renderer_functions.php';
require_once APP_PATH_DOCROOT . 'DataExport/functions.php';


// Set the max number of metadata fields to display on page. If exceed, show form names rather than fields.
$max_metadata = 1000;
// Set the max number of data points a database can have before the page reverts to limited export options (data CSV only - direct pull).
$dataPtLimitForExport = 2000000;

// Set Standards Mapping flags
$is_data_conversion_error = false;
$is_data_conversion_error_msg = "";




/**
 * GIVE TWO OPTIONS OF EXPORT: SIMPLE VS. ADVANCED(= classic look)
 */
if (isset($_GET['view']) && $_GET['view'] == 'simple_advanced')
{
	## Set up field list of ALL fields used in Export All Data Now
	// First add all project fields (exclude descriptive fields) - initially add flags to export DAG field and export survey fields
	$all_fields = array('flag-exportSurveyFields', 'flag-exportDataAccessGroups');
	foreach ($Proj->metadata as $this_field=>$field_attr)
	{
		if ($field_attr['element_type'] != 'descriptive') {
			$all_fields[] = $this_field;
		}
	}
	// If a child project (of a parent/child), then give parent fields too
	if ($is_child) {
		$sql = "select field_name from redcap_metadata where project_id = $project_id_parent";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q)) {
			// Add field with prepended string to designate the parent fields
			$all_fields[] = "parent____" . $row['field_name']; 
		}
		// Now remove the PK field from the child (to prevent duplication)
		$pk_key = array_search($table_pk, $all_fields);
		unset($all_fields[$pk_key]);
	}
	// Header
	include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';	
	renderPageTitle("<img src='".APP_PATH_IMAGES."application_go.png'> {$lang['app_03']}");	
	?>
	
	<p>
		<b><?php echo $lang['data_export_tool_109'] ?></b>
		<?php echo $lang['data_export_tool_110'] ?>
	</p>
	
	<?php if ($user_rights['data_quality_execute']) { 
		echo "<p>
				<img src='".APP_PATH_IMAGES."star.png' class='imgfix'> {$lang['dataqueries_102']} 
				<a style='text-decoration:underline;' href='".APP_PATH_WEBROOT."DataQuality/index.php?pid=$project_id'>{$lang['dataqueries_103']}</a> 
				{$lang['dataqueries_104']}
			  </p>";
	} ?>
	
	<?php if ($user_rights['data_export_tool'] == '2') { ?>
		<p class="yellow" style="font-family:arial;">
			<?php echo $lang['data_export_tool_111'] ?>
		</p>
	<?php } ?>
	
	<div style="max-width:700px;">	
		<table cellspacing=0 width=100%>
			<tr>
				<td valign="top" width=40%>
				
					<!-- Simple Export box -->
					<div id="simple_export" class="export_box chklist shadow" style="">
						<div class="export_hdr">
							<img src="<?php echo APP_PATH_IMAGES ?>wand.png">
							<?php echo $lang['data_export_tool_112'] ?>
						</div>
						<p>
							<?php echo $lang['data_export_tool_113'] ?>
						</p>
						<form action="<?php echo PAGE_FULL ?>?pid=<?php echo $project_id ?>" enctype="multipart/form-data" target="_self" method="post" name="form" id="form">
							<?php foreach ($all_fields as $this_field) { ?>
								<input style="display:none;" type="checkbox" name="<?php echo $this_field ?>" checked>
							<?php } ?>
							<div style="text-align:center;padding:5px 0 15px;">
								<input onclick="setTimeout(function(){$('#submit').prop('disabled',true);},10);" class="jqbutton" type="submit" id="submit" name="submit" value="<?php echo $lang['data_export_tool_114'] ?>" style="padding: 4px 8px !important;">
							</div>
						</form>
					</div>
					
				</td>
				
				<td valign="top" width=10% style="padding:100px 5px 0;color:#666;text-align:center;">
					&mdash; <?php echo $lang['global_46'] ?> &mdash;
				</td>
				
				<td valign="top" width=50%>
				
					<!-- Advanced Export box -->
					<div id="advanced_export" class="export_box chklist shadow" style="">
						<div class="export_hdr">
							<img src="<?php echo APP_PATH_IMAGES ?>cog_go.png">
							<?php echo $lang['data_export_tool_115'] ?>
						</div>
						<p>
							<?php echo $lang['data_export_tool_116'] ?>
						</p>
						<div style="text-align:center;padding:5px 0 15px;">
							<input class="jqbutton" type="button" value="<?php echo $lang['data_export_tool_117'] ?>" style="padding: 4px 8px !important;" onclick="window.location.href = app_path_webroot+page+'?pid='+pid;">
						</div>
					</div>
					
				</td>
			</tr>
		</table>
	</div>
	
	<!-- Other export options -->
	<div id="simple_export" class="export_box chklist shadow" style="margin-top:40px;">
		<div class="export_hdr" style="border-color:#ccc;">
			<?php echo $lang['data_export_tool_121'] ?>
		</div>	
		<!-- PDF data export -->
		<table cellspacing="0" width="100%">
			<tr>
				<td valign="top" style="padding-left:30px;padding-right:10px;border-right:1px solid #eee;">
					<img src="<?php echo APP_PATH_IMAGES ?>pdf.gif" class="imgfix"> <b><?php echo $lang['data_export_tool_122'] ?></b><br>
					<?php echo $lang['data_export_tool_123'] ?><br><br>
					<i><?php echo $lang['data_export_tool_124'] ?></i>
				</td>
				<td valign="top" style="padding-top:5px;width:70px;text-align:center;">
					<a href="<?php echo APP_PATH_WEBROOT . "PDF/index.php?pid=$project_id&allrecords" ?>" title="Download PDF with all data"
					><img src="<?php echo APP_PATH_IMAGES ?>download_pdf.gif"></a>
				</td>
			</tr>
		</table>
		
		<?php
		// Determine if any surveys exist with Save and Return Later feature enabled
		$saveAndReturnEnabled = false;
		if (!empty($Proj->surveys))
		{
			foreach ($Proj->surveys as $attr) {
				if ($attr['save_and_return']) {
					$saveAndReturnEnabled = true;
				}
			}
		}
		?>		
		
		<?php if ($saveAndReturnEnabled) { ?>
		<div class="spacer" style="border-color:#ccc;"></div>		
		<!-- Survey return codes, if any surveys exist with Save & Return enabled -->
		<table cellspacing="0" width="100%">
			<tr>
				<td valign="top" style="padding-left:30px;padding-right:10px;border-right:1px solid #eee;">
					<img src="<?php echo APP_PATH_IMAGES ?>arrow_circle_315.png" class="imgfix"> 
					<b><?php echo $lang['data_export_tool_125'] ?></b><br>
					<?php echo $lang['data_export_tool_126'] ?><br><br>
					<i><?php echo $lang['data_export_tool_127'] ?></i>
				</td>
				<td valign="top" style="padding-top:5px;width:70px;text-align:center;">
					<a href="<?php echo APP_PATH_WEBROOT ?>DataExport/data_export_csv.php?pid=<?php echo $project_id ?>&type=return_codes" title="Download all data with Return Codes"><img src="<?php echo APP_PATH_IMAGES ?>download_return_codes.gif"></a>
				</td>
			</tr>
		</table>
		<?php } ?>
		
	</div>
	
	<style type="text/css">
	.shadow {
		-moz-box-shadow: 3px 3px 3px #ddd;
		-webkit-box-shadow: 3px 3px 3px #ddd;
		box-shadow: 3px 3px 3px #ddd;
	}
	.export_box {
		border-bottom-left-radius:10px 10px;
		border-bottom-right-radius:10px 10px;
		border-top-left-radius:10px 10px;
		border-top-right-radius:10px 10px;
	}
	.export_hdr {
		font-weight: bold;
		font-size: 16px;
		border-bottom: 1px solid #eee;
		margin: 2px 0 8px;
	}
	</style>
	
	<?php
	// Footer
	include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
	exit;
}





################################################################################
//Check amount of data: if too many records/data points, then give direct download because app will run out of RAM
$countDataPts = db_result(db_query("select count(1) from redcap_data where project_id = $project_id"), 0);
if ($countDataPts > $dataPtLimitForExport) 
{
	include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
	
	renderPageTitle("<img src='".APP_PATH_IMAGES."application_go.png'> {$lang['app_03']}");
	
	print  "<p>" . $lang['data_export_tool_58'] . number_format($dataPtLimitForExport,0,'.',',') . " " . $lang['data_export_tool_59'] . "</p>";

	print  "<div style='max-width:700px;'>";
	print  "<table style='border: 1px solid #DODODO; border-collapse: collapse; width: 100%; max-width:700px;'>
			<tr class='grp2'>
				<td colspan='2' style='font-family:Verdana;font-size:12px;text-align:right;'>			
				</td>
				<td style='font-family:Verdana;font-size:12px;text-align:center;'>
					{$lang['data_export_tool_51']}
				</td>
			</tr>";
	print  '<tr class="odd">
				<td valign="top" style="text-align:center;width:60px;padding-top:10px;border:0px;border-left:1px solid #D0D0D0;border-bottom:1px solid #D0D0D0;">
					<img src="'.APP_PATH_IMAGES.'excelicon.gif" title="'.$lang['data_export_tool_15'].'" alt="'.$lang['data_export_tool_15'].'" />
				</td>
			    <td style="font-family:Verdana;font-size:11px;padding:10px;border-bottom:1px solid #D0D0D0;" valign="top">
					<b>'.$lang['data_export_tool_15'].'</b><br /><i>'.$lang['global_02'].': '.$lang['data_export_tool_17'].'</i>
				</td>
				<td valign="top" style="text-align:right;width:100px;padding-top:10px;border-bottom:1px solid #D0D0D0;">
					<a href="'.APP_PATH_WEBROOT.'DataExport/data_export_csv.php?pid='.$project_id.'">	
						<img src="'.APP_PATH_IMAGES.'download_csvexcel.gif" title="'.$lang['data_export_tool_60'].'" alt="'.$lang['data_export_tool_60'].'"></a>
				</td>
			</tr>
			</table>';
	print  "</div>";
			
	include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
	exit;	
}



################################################################################
//Sort through submitted fields to export and create query to pull data

if (isset($_POST['submit'])) 
{
	## STANDARDS MAPPING
	$useFieldNames = true;
	$useStandardCodes = false; // Flag if any standard mapping is being used
	$useStandardCodeDataConversion = false;
	$standardCodeLookup = array();
	$standardId = -1;
	if (isset($_POST['stdmap-use-standards']) && $_POST['stdmap-use-standards'] == 'true') 
	{		
		$useStandardCodes = true;
		if (isset($_POST['stdmap-field-names']) && $_POST['stdmap-field-names'] == 'false') {
			$useFieldNames = false;
		}		
		if (isset($_POST['stdmap-data-conversion']) && $_POST['stdmap-data-conversion'] == 'yes') {
			$useStandardCodeDataConversion = true;
		}
	}
	unset($_POST['stdmap-use-standards']);
	unset($_POST['stdmap-field-names']);
	unset($_POST['stdmap-data-conversion']);
	foreach($_POST as $fieldName=>$fieldValue) 
	{
		$formName = $Proj->metadata[$fieldName]['form_name'];
		if (isset($_POST['stdmapselect-'.$formName]))
		{
			$tempStdId = $_POST['stdmapselect-'.$formName];
			unset($_POST['stdmapselect-'.$formName]);
			if (is_numeric($tempStdId) && $tempStdId > 0) {
				$standardId = $tempStdId;
				$sql = "select code.standard_code 
						from redcap_standard_map map 
							left join redcap_standard_code code on map.standard_code_id = code.standard_code_id 
						where map.project_id = $project_id and map.field_name = '$fieldName' and code.standard_id = $standardId";
				$qry = db_query($sql);
				$arr = db_fetch_array($qry);
				if ($arr['standard_code'] && trim($arr['standard_code']) != '') {
					$standardCodeLookup[$fieldName] = html_entity_decode($arr['standard_code'], ENT_QUOTES);
				} else {
					$standardCodeLookup[$fieldName] = $fieldName;
				}
			} else {
				$standardCodeLookup[$fieldName] = $fieldName;
			}
		}
	}
	
	// If DAGs exist, get unique group name and label IF user specified
	$dagLabels = $Proj->getGroups();
	$exportDags = (!empty($dagLabels) && $user_rights['group_id'] == "" && isset($_POST['flag-exportDataAccessGroups']));
	if ($exportDags) {
		$dagUniqueNames = $Proj->getUniqueGroupNames();
		// Create enum for DAGs with unique name as coded value
		$dagEnumArray = array();
		foreach (array_combine($dagUniqueNames, $dagLabels) as $group_id=>$group_label) {
			$dagEnumArray[] = "$group_id, " . label_decode($group_label);
		}
		$dagEnum = implode(" \\n ", $dagEnumArray);;
	}
	
	// Set flag if exporting survey fields
	$exportSurveyFields = (!empty($Proj->surveys) && isset($_POST['flag-exportSurveyFields']));
	
	// Remove DAG field and survey fields flags from Post to prevent confusion with real fields
	unset($_POST['flag-exportSurveyFields'], $_POST['flag-exportDataAccessGroups']);	
	
	
	//Get a list of all fields for which to export data
	$chkd_flds = "";
	$parent_chkd_flds = "";
	
	/**
	 * FORMS SUBMITTED
	 */
	//Forms were submitted (not fields). Now retrieve all fields on the submitted forms.
	if (isset($_POST['__forms_only__'])) {
		$chkd_forms = "";
		$parent_chkd_forms = "";
		//Loop through all to build SQL string
		foreach ($_POST as $key=>$value) {
			//Add to exported field list if submitted checkbox is "on"
			if ($value == "on") {
				//If field begins with "parent____", segregate these fields to pull from shared "parents" project table
				if (substr($key,0,10) == "parent____") {
					$parent_chkd_forms .= "'$key', ";
				//Normal project (or child project, if linked to parent)
				} else {
					$chkd_forms .= "'$key', ";
				}
			}	
		}
		$chkd_forms = substr($chkd_forms,0,-2);	
		if ($parent_chkd_forms != "") {
			//Reformat string for query
			$parent_chkd_forms = str_replace("parent____","",substr($parent_chkd_forms,0,-2));		
		}	
		//If the record id is an Identifier and the user has de-id rights access, do an MD5 hash on the record id
		if ($user_rights['data_export_tool'] == 1) $include_phi = ""; else $include_phi = "and field_phi is null";
		//Build query
		if (!$is_child) {
			//Normal
			$sql = "select field_name from redcap_metadata where project_id = $project_id and (form_name in ($chkd_forms) or field_name = '$table_pk') $include_phi order by field_order";
			$offset = 0;
		} else {
			//If parent/child linking exists
			if ($chkd_forms == "") $chkd_forms = "''";
			$sql = "select field_name, field_order, tbl from (
					(select field_name, field_order, 1 as tbl from redcap_metadata where project_id = $project_id_parent and form_name in ($parent_chkd_forms) $include_phi) 
					UNION (select field_name, field_order, 2 as tbl from redcap_metadata where project_id = $project_id and form_name in ($chkd_forms) $include_phi and field_order != '1')
					) as x order by tbl, field_order";
			$offset = db_result(db_query("select count(1) from redcap_metadata where project_id = $project_id_parent and form_name in ($parent_chkd_forms) $include_phi"),0);
		}
		//Get fields from selected forms
		$q = db_query($sql);
		//Normal database
		if (!$is_child) {
			while ($row = db_fetch_assoc($q)) {
				$chkd_flds .= "'".$row['field_name']."', ";
			}
		//Parent/child database linking
		} elseif ($is_child) {
			$i = 1;
			while ($row = db_fetch_assoc($q)) {
				if ($i <= $offset) {
					$parent_chkd_flds .= "'".$row['field_name']."', ";
				} else {
					$chkd_flds .= "'".$row['field_name']."', ";
				}
				$i++;
			}
		}
	}
	
	
	/**
	 * FIELDS SUBMITTED
	 */
	//Individual fields were submitted	
	if (!isset($_POST['__forms_only__'])) {
		
		//Add record id field to list of checked fields if not checked	
		if (!isset($_POST[$table_pk]) && !$is_child)			  $_POST[$table_pk] = "on";
		if (!isset($_POST['parent____'.$table_pk]) && $is_child)  $_POST['parent____'.$table_pk] = "on";		
				
		//Loop through all to build SQL string
		foreach ($_POST as $key=>$value) {
			//Add to exported field list if submitted checkbox is "on"
			if ($value == "on") {
				//If field begins with "parent____", segregate these fields to pull from shared "parents" project table
				if (substr($key,0,10) == "parent____") {
					$parent_chkd_flds .= "'$key', ";
				//Normal project (or child project, if linked to parent)
				} else {
					$chkd_flds .= "'$key', ";
				}
			}	
		}
	}
	//Now we have all the fields to be exported
	$chkd_flds = substr($chkd_flds,0,-2);
	if ($parent_chkd_flds != "") {
		//Reformat string for query
		$parent_chkd_flds = str_replace("parent____","",substr($parent_chkd_flds,0,-2));		
	}
	
	
	
	/**
	 * DE-ID PROCESS
	 */
	// Post-processing security: Make sure that appropriate checkboxes were checked if user has de-id rights 
	// (in case they've manipulated the page to uncheck them illegally)
	if ($user_rights['data_export_tool'] == 2) 
	{
		if (!isset($_POST['deid-remove-identifiers'])) $_POST['deid-remove-identifiers'] = "on";
		if (!isset($_POST['deid-remove-text'])) $_POST['deid-remove-text'] = "on";
		if (!isset($_POST['deid-remove-notes'])) $_POST['deid-remove-notes'] = "on";
		if (!isset($_POST['deid-dates-shift']) && !isset($_POST['deid-dates-remove'])) $_POST['deid-dates-shift'] = "on";
		if ($table_pk_phi && !isset($_POST['deid-hashid'])) $_POST['deid-hashid'] = "on";
	}
	// Remove free-form text fields
	if (isset($_POST['deid-remove-text']) || isset($_POST['deid-remove-notes'])) 
	{
		//If removing ALL unvalidated text fields and notes fields
		if (isset($_POST['deid-remove-text']) && isset($_POST['deid-remove-notes'])) {
			$sql = "select field_name from redcap_metadata where project_id = $project_id and 
					(element_type = 'textarea' or (element_type = 'text' and element_validation_type is null and field_name != '$table_pk'))";
			if ($is_child) {
				$sql .= " union select field_name from redcap_metadata where project_id = $project_id_parent and 
						(element_type = 'textarea' or (element_type = 'text' and element_validation_type is null and field_name != '$table_pk'))";
			}
		} elseif (isset($_POST['deid-remove-notes'])) {
			$sql = "select field_name from redcap_metadata where project_id = $project_id and element_type = 'textarea'";
			if ($is_child) {
				$sql .= " union select field_name from redcap_metadata where project_id = $project_id_parent and element_type = 'textarea'";
			}
		} elseif (isset($_POST['deid-remove-text'])) {
			$sql = "select field_name from redcap_metadata where project_id = $project_id and element_type = 'text' and element_validation_type is null and field_name != '$table_pk'";
			if ($is_child) {
				$sql .= " union select field_name from redcap_metadata where project_id = $project_id_parent and element_type = 'text' and element_validation_type is null and field_name != '$table_pk'";
			}
		}
		// Retrieve list of all pertinent fields
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q)) {				
			//Remove the field from field string
			if (strpos($chkd_flds, "'{$row['field_name']}', ") !== false || strpos($parent_chkd_flds, "'{$row['field_name']}', ") !== false) {			
				$chkd_flds = str_replace("'{$row['field_name']}', ", "", $chkd_flds);
				if ($parent_chkd_flds != "") {
					$parent_chkd_flds = str_replace("'{$row['field_name']}', ", "", $parent_chkd_flds);		
				}
			}
		}
	}
	//Remove all Identifier fields, if requested
	$do_hash = false;
	$do_remove_identifiers = false;
	if (isset($_POST['deid-remove-identifiers'])) 
	{
		$do_remove_identifiers = true;
		//Retrieve list of all identifier fields
		$sql = "select field_name from redcap_metadata where project_id = $project_id and field_phi is not null";
		if ($is_child) {
			$sql .= " union select field_name from redcap_metadata where project_id = $project_id_parent and field_phi is not null";
		}		
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q)) {				
			//Remove the Identifer field from field string
			if (strpos($chkd_flds, "'{$row['field_name']}', ") !== false || strpos($parent_chkd_flds, "'{$row['field_name']}', ") !== false) {
				//Hash the Study ID rather than removing it
				if ($row['field_name'] == $table_pk) {
					$do_hash = true;
				//Remove this field from field string
				} else {
					$chkd_flds = str_replace("'{$row['field_name']}', ", "", $chkd_flds);
					if ($parent_chkd_flds != "") {
						$parent_chkd_flds = str_replace("'{$row['field_name']}', ", "", $parent_chkd_flds);	
					}
				}
			}
		}	
	}
	//Hash the Study Id number
	if (isset($_POST['deid-hashid'])) {
		$do_hash = true;
	}	
	//Remove all date fields, if requested
	if (isset($_POST['deid-dates-remove'])) {
		//Retrieve list of all date fields
		$sql = "select field_name from redcap_metadata where project_id = $project_id and element_type = 'text' 
				and element_validation_type like 'date%'";
		if ($is_child) {
			$sql .= " union select field_name from redcap_metadata where project_id = $project_id_parent and element_type = 'text' 
					 and element_validation_type like 'date%'";
		}		
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q)) {				
			//Remove the date field from field string
			if (strpos($chkd_flds, "'{$row['field_name']}', ") !== false || strpos($parent_chkd_flds, "'{$row['field_name']}', ") !== false) {
				$chkd_flds = str_replace("'{$row['field_name']}', ", "", $chkd_flds);
				if ($parent_chkd_flds != "") {
					$parent_chkd_flds = str_replace("'{$row['field_name']}', ", "", $parent_chkd_flds);
				}
			}
		}
	}
	
	//Check for date shifting option
	$do_date_shift = (isset($_POST['deid-dates-shift']));
	
	//Check for date shifting option for survey completion timestamps
	$do_surveytimestamp_shift = (isset($_POST['deid-surveytimestamps-shift']));
	unset($_POST['deid-surveytimestamps-shift']);
	
	//Remove checkbox fields from $_POST array and field string that are from De-Id options section (so as to not mix with real fields during processing)
	$deid_options = array('deid-remove-identifiers', 'deid-hashid', 'deid-remove-text', 'deid-remove-notes', 'deid-dates-remove', 'deid-dates-shift');
	foreach ($deid_options as $value) 
	{
		if (isset($_POST[$value])) {
			//Remove from $_POST
			unset($_POST[$value]);
			//Remove from field list
			$chkd_flds = str_replace("'$value', ", "", "$chkd_flds, ");
			$chkd_flds = substr($chkd_flds,0,-2);
			if ($parent_chkd_flds != "") {
				$parent_chkd_flds = str_replace("'$value', ", "", "$parent_chkd_flds, ");
				$parent_chkd_flds = str_replace("parent____","",substr($parent_chkd_flds,0,-2));	
			}
		}
	}
	
	## NOTE: $chkd_flds and $parent_chkd_flds are comma-delimited lists of fields that we're exporting
	## with each field surrounded by single quotes (gets used in query).
	
	// Retrieve project data (raw & labels) and headers in CSV format
	list ($headers, $headers_labels, $data_csv, $data_csv_labels, $field_names) 
		= fetchDataCsv($chkd_flds,$parent_chkd_flds,false,$do_hash,$do_remove_identifiers,$useStandardCodes,$useStandardCodeDataConversion,$standardId,$standardCodeLookup,$useFieldNames,$exportDags,$exportSurveyFields,$exportSurveyFields);
	// Log the event	
	log_event("","redcap_data","data_export","",str_replace("'","",$chkd_flds).(($parent_chkd_flds == "") ? "" : ", ".str_replace("'","",$parent_chkd_flds)),"Export data");
	
	############################################################
	## PREPARE SYNTAX FILES FOR STATS PACKAGES

    # Initializing the syntax file strings
    $spss_string = "FILE HANDLE data1 NAME='data_place_holder_name' LRECL=90000.\n";
    $spss_string .= "DATA LIST FREE" . "\n\t";
    $spss_string .= "FILE = data1\n\t/";
    $sas_string = "DATA " . $app_name . ";\nINPUT ";
    $sas_format_string = "data redcap;\n\tset redcap;\n";
    $stata_string = "clear\n\n";
    $R_string = "#Clear existing data and graphics\nrm(list=ls())\n";
    $R_string .= "graphics.off()\n";
    $R_string .= "#Load Hmisc library\nlibrary(Hmisc)\n";
    $R_label_string = "#Setting Labels\n";
    $R_units_string = "\n#Setting Units\n" ;
    $R_factors_string = "\n\n#Setting Factors(will create new variable for factors)";
    $R_levels_string = "";
	$value_labels_spss = "VALUE LABELS ";
	
	
	// Get relevant metadata to use for syntax files
	if (!$is_child) {
		// Normal
		$syntaxfile_sql = "SELECT field_name, element_validation_type, element_enum, element_type, element_label, field_units 
						   FROM redcap_metadata where project_id = $project_id and field_name in ($chkd_flds) order by field_order";
	} else {
		//If parent/child linking exists		
		$syntaxfile_sql = "SELECT field_name, element_validation_type, element_enum, element_type, element_label, field_units,
						   field_order, tbl FROM (
						   (SELECT field_name, element_validation_type, element_enum, element_type, element_label, field_units, 
						   field_order, 1 as tbl FROM redcap_metadata where project_id = $project_id_parent and field_name in ($parent_chkd_flds)) UNION 
						   (SELECT field_name, element_validation_type, element_enum, element_type, element_label, field_units, 
						   field_order, 2 as tbl FROM redcap_metadata where project_id = $project_id and field_name in ($chkd_flds))
						   ) as x order by tbl, field_order";
	}
	
	// Array that is prepended to $field_names array if fields need to be added, such as redcap_event_name or survey timestamp
	$field_names_prepend = array();
	$prev_form = "";
	$prev_field = "";
	
	// Loop through all fields that were exported
	$q = db_query($syntaxfile_sql);
	while ($row = db_fetch_assoc($q)) 
	{
		// Create object for each field we loop through
		$ob = new stdClass();
		foreach ($row as $col=>$val) {
			$col = strtoupper($col);
			$ob->$col = $val;
		}
		
		// Set values for this loop
		$this_form = $Proj->metadata[$ob->FIELD_NAME]['form_name'];		
		
		// If surveys exist, as timestamp and identifier fields
		if ($exportSurveyFields && $prev_form != $this_form && $ob->FIELD_NAME != $table_pk && isset($Proj->forms[$this_form]['survey_id']))
		{
			// Alter $meta_array
			$ob2 = new stdClass();
			$ob2->ELEMENT_TYPE = 'text';
			$ob2->FIELD_NAME = $this_form.'_timestamp';
			$ob2->ELEMENT_LABEL = 'Survey Timestamp';
			$ob2->ELEMENT_ENUM = '';
			$ob2->FIELD_UNITS = '';
			$ob2->ELEMENT_VALIDATION_TYPE = '';
			$meta_array[$ob2->FIELD_NAME] = (Object)$ob2;
		}
		
		
		if ($ob->ELEMENT_TYPE != 'checkbox') {			
			// For non-checkboxes, add to $meta_array
			$meta_array[$ob->FIELD_NAME] = (Object)$ob;
		} else {
			// For checkboxes, loop through each choice to add to $meta_array
			$orig_fieldname = $ob->FIELD_NAME;
			$orig_fieldlabel = $ob->ELEMENT_LABEL;
			$orig_elementenum = $ob->ELEMENT_ENUM;
			foreach (parseEnum($orig_elementenum) as $this_value=>$this_label) {
				unset($ob);
				// $ob = $meta_set->FetchObject();
				$ob = new stdClass();
				// If coded value is not numeric, then format to work correct in variable name (no spaces, caps, etc)
				if (!is_numeric($this_value)) {
					$this_value = preg_replace("/[^a-z0-9]/", "", strtolower($this_value));
				}
				// Convert each checkbox choice to a advcheckbox field (because advcheckbox has equivalent processing we need)
				// Append triple underscore + coded value
				$ob->FIELD_NAME = $orig_fieldname . '___' . $this_value;
				$ob->ELEMENT_ENUM = "0, Unchecked \\n 1, Checked";
				$ob->ELEMENT_TYPE = "advcheckbox";
				$ob->ELEMENT_LABEL = "$orig_fieldlabel (choice=".str_replace(array("'","\""),array("",""),$this_label).")";
				$meta_array[$ob->FIELD_NAME] = (Object)$ob;
			}
		}
		
		
		if ($ob->FIELD_NAME == $table_pk)
		{
			// If project has multiple Events (i.e. Longitudinal), add new column for Event name
			if ($longitudinal) 
			{
				// Put unique event names and labels into array to convert to enum format
				$evtEnumArray = array();
				$evtLabels = array();
				foreach ($Proj->eventInfo as $event_id=>$attr) {
					$evtLabels[$event_id] = label_decode($attr['name_ext']);
				}
				foreach ($evtLabels as $event_id=>$event_label) {
					$evtEnumArray[] = $Proj->getUniqueEventNames($event_id) . ", " . label_decode($event_label);
				}
				$evtEnum = implode(" \\n ", $evtEnumArray);
				// Alter $meta_array
				$ob2 = new stdClass();
				$ob2->ELEMENT_TYPE = 'select';
				$ob2->FIELD_NAME = 'redcap_event_name';
				$ob2->ELEMENT_LABEL = 'Event Name';
				$ob2->ELEMENT_ENUM = $evtEnum;
				$ob2->FIELD_UNITS = '';
				$ob2->ELEMENT_VALIDATION_TYPE = '';
				$meta_array[$ob2->FIELD_NAME] = (Object)$ob2;
				// Add pseudo-field to array
				$field_names_prepend[] = $ob2->FIELD_NAME;
			}
			// If project has DAGs, add new column for group name
			if ($exportDags) 
			{
				// Alter $meta_array
				$ob2 = new stdClass();
				$ob2->ELEMENT_TYPE = 'select';
				$ob2->FIELD_NAME = 'redcap_data_access_group';
				$ob2->ELEMENT_LABEL = 'Data Access Group';
				$ob2->ELEMENT_ENUM = $dagEnum;
				$ob2->FIELD_UNITS = '';
				$ob2->ELEMENT_VALIDATION_TYPE = '';
				$meta_array[$ob2->FIELD_NAME] = (Object)$ob2;
				// Add pseudo-field to array
				$field_names_prepend[] = $ob2->FIELD_NAME;
			}
			
			// Add survey identifier (unless we've set it to remove all identifiers - treat survey identifier same as field identifier)
			if ($exportSurveyFields && !$do_remove_identifiers) {
				// Alter $meta_array
				$ob2 = new stdClass();
				$ob2->ELEMENT_TYPE = 'text';
				$ob2->FIELD_NAME = 'redcap_survey_identifier';
				$ob2->ELEMENT_LABEL = 'Survey Identifier';
				$ob2->ELEMENT_ENUM = '';
				$ob2->FIELD_UNITS = '';
				$ob2->ELEMENT_VALIDATION_TYPE = '';
				$meta_array[$ob2->FIELD_NAME] = (Object)$ob2;
				// Add pseudo-field to array
				$field_names_prepend[] = $ob2->FIELD_NAME;
			}		
		
			// If surveys exist, as timestamp and identifier fields
			if ($exportSurveyFields && $prev_form != $this_form && isset($Proj->forms[$this_form]['survey_id']))
			{
				// Alter $meta_array
				$ob2 = new stdClass();
				$ob2->ELEMENT_TYPE = 'text';
				$ob2->FIELD_NAME = $this_form.'_timestamp';
				$ob2->ELEMENT_LABEL = 'Survey Timestamp';
				$ob2->ELEMENT_ENUM = '';
				$ob2->FIELD_UNITS = '';
				$ob2->ELEMENT_VALIDATION_TYPE = '';
				$meta_array[$ob2->FIELD_NAME] = (Object)$ob2;
			}
		}
		
		// Set values for next loop
		$prev_form = $this_form;
		$prev_field = $ob->FIELD_NAME;
	}
	
	// Now reset field_names array
	$field_names = array_keys($meta_array);
	
	
	// $spss_data_type_array = "";
	$spss_format_dates   = "";
	$spss_variable_label = "VARIABLE LABEL ";
	$spss_variable_level = array();
	$sas_label_section = "\ndata redcap;\n\tset redcap;\n";
	$sas_value_label = "proc format;\n";
	$sas_input = "input\n";
	$sas_informat = "";
	$sas_format = "";
	$stata_insheet = "insheet ";
	$stata_var_label = "";
	$stata_inf_label = "";
	$stata_value_label = "";
	$stata_date_format = "";
	
	$first_label = true;
	$large_name_counter = 0;
	$large_name = false;
	
	
	// Use arrays for string replacement
	$orig = array("'", "\"", "\r\n", "\r", "\n", "&lt;", "<=");
	$repl = array("", "", " ", " ", " ", "<", "< =");
	
	//print_array($meta_array);print_array($field_names);exit;
	
	
	// Loop through all metadata fields
	for ($x = 0; $x <= count($field_names) + 1; $x++) 
	{
				
		if (($x % 5)== 0 && $x != 0) {
			$spss_string .=  "\n\t";
		}
		$large_name = false;
		
		// Set field object for this loop
		$ob = $meta_array[$field_names[$x]];
		
		// Remove any . or - in the field name (as a result of checkbox raw values containing . or -)
		// $ob->FIELD_NAME = str_replace(array("-", "."), array("_", "_"), (string)$ob->FIELD_NAME);
		
		// Convert "sql" field types to "select" field types so that their Select Choices come out correctly in the syntax files.
		if ($ob->ELEMENT_TYPE == "sql")
		{
			// Change to select
			$ob->ELEMENT_TYPE = "select";
			// Now populate it's choices by running the query
			$ob->ELEMENT_ENUM = getSqlFieldEnum($ob->ELEMENT_ENUM);
		}
		elseif ($ob->ELEMENT_TYPE == "yesno")
		{
			$ob->ELEMENT_ENUM = YN_ENUM;
		} 
		elseif ($ob->ELEMENT_TYPE == "truefalse")
		{
			$ob->ELEMENT_ENUM = TF_ENUM;
		}	
		
		//Remove any offending characters from label
		$ob->ELEMENT_LABEL = str_replace($orig, $repl, label_decode(html_entity_decode($ob->ELEMENT_LABEL, ENT_QUOTES)));
		
		if ($field_names[$x] != "") {
			if (strlen($field_names[$x]) >= 31) {
				$short_name = substr($field_names[$x],0,20) . "_v_" . $large_name_counter;
				$sas_label_section .= "\tlabel " . $short_name ."='" . $ob->ELEMENT_LABEL . "';\n";
				$stata_var_label .= "label variable " . $short_name . ' "' . $ob->ELEMENT_LABEL . '"' . "\n";
				$stata_insheet .= $short_name . " ";
				$large_name_counter++;
				$large_name = true;
			}
			if (!$large_name) {
				$sas_label_section .= "\tlabel " . $field_names[$x] ."='" . $ob->ELEMENT_LABEL . "';\n";
				$stata_var_label .= "label variable " . $field_names[$x] . ' "' . $ob->ELEMENT_LABEL . '"' . "\n";
				$stata_insheet .= $field_names[$x] . " ";
			}
			$spss_variable_label .= $field_names[$x] . " '" . $ob->ELEMENT_LABEL . "'\n\t/" ;
			$R_label_string .= "\nlabel(data$" . $field_names[$x] . ")=" . '"' . $ob->ELEMENT_LABEL . '"';
			if (($ob->FIELD_UNITS != Null) || ($ob->FIELD_UNITS != "")) {
				$R_units_string .= "\nunits(data$" . $field_names[$x] . ")=" . '"' .  $ob->FIELD_UNITS . '"';
			}
		}

		# Checking for single element enum (i.e. if it is coded with a number or letter)
		$single_element_enum = true;
		if (substr_count(((string)$ob->ELEMENT_ENUM),",") > 0) {
			$single_element_enum = false;
		}
		
		# Select value labels are created
		if (($ob->ELEMENT_TYPE == "yesno" || $ob->ELEMENT_TYPE == "truefalse" || $ob->ELEMENT_TYPE == "select" || $ob->ELEMENT_TYPE == "advcheckbox" || $ob->ELEMENT_TYPE == "radio") && !preg_match("/\+\+SQL\+\+/",(string)$ob->ELEMENT_ENUM)) {
			
			//Remove any apostrophes from the Choice Labels
			$ob->ELEMENT_ENUM = str_replace($orig, $repl, label_decode($ob->ELEMENT_ENUM));
			
			//Place $ in front of SAS value if using non-numeric coded values for dropdowns/radios
			$sas_val_enum_num = ""; //default
			$numericChoices = true;
			foreach (array_keys(parseEnum($ob->ELEMENT_ENUM)) as $key) {
				if (!is_numeric($key)) {
					// If at least one key is not numeric, then stop looping because we have all we need.
					$sas_val_enum_num = "$";
					$numericChoices = false;
					break;
				}
			}
			
			if ($first_label) {
				if (!$single_element_enum) {
					$value_labels_spss .=  "\n" . (string)$ob->FIELD_NAME . " ";
				}
				$R_factors_string .= "\ndata$" . (string)$ob->FIELD_NAME . ".factor = factor(data$" . (string)$ob->FIELD_NAME . ",levels=c(";
				$R_levels_string .=  "\nlevels(data$" . (string)$ob->FIELD_NAME . ".factor)=c(";
				$first_label = false;
				if (!$large_name && !$single_element_enum) {
					$sas_value_label .= "\tvalue $sas_val_enum_num" . (string)$ob->FIELD_NAME . "_ ";
					$sas_format_string .= "\n\tformat " . (string)$ob->FIELD_NAME . " " . (string)$ob->FIELD_NAME . "_.;\n";
					if ($numericChoices) {
						$stata_inf_label .= "\nlabel values " . (string)$ob->FIELD_NAME . " " . (string)$ob->FIELD_NAME . "_\n";
						$stata_value_label = "label define " . (string)$ob->FIELD_NAME . "_ ";
					}
				} else if ($large_name && !$single_element_enum) {
					$sas_value_label .= "\tvalue $sas_val_enum_num" . $short_name . "_ ";
					$sas_format_string .= "\n\tformat " . $short_name . " " . $short_name . "_.;\n";
					if ($numericChoices) {
						$stata_value_label .= "label define " . $short_name . "_ ";
						$stata_inf_label .= "\nlabel values " . $short_name . " " . $short_name . "_\n";
					}
				}
			} else if(!$first_label) {
				if (!$single_element_enum) {
					$value_labels_spss .= "\n/" . (string)$ob->FIELD_NAME . " ";
					if (!$large_name) {
						$sas_value_label .= "\n\tvalue $sas_val_enum_num" . (string)$ob->FIELD_NAME . "_ ";
						$sas_format_string .= "\tformat " . (string)$ob->FIELD_NAME . " " . (string)$ob->FIELD_NAME . "_.;\n";
						if ($numericChoices) {
							$stata_value_label .= "\nlabel define " . (string)$ob->FIELD_NAME . "_ ";
							$stata_inf_label .= "label values " . (string)$ob->FIELD_NAME . " " . (string)$ob->FIELD_NAME . "_\n";
						}
					}
				}
				$R_factors_string .= "data$" . (string)$ob->FIELD_NAME . ".factor = factor(data$" . (string)$ob->FIELD_NAME . ",levels=c(";
				$R_levels_string .=  "levels(data$" . (string)$ob->FIELD_NAME . ".factor)=c(";
				if ($large_name && !$single_element_enum) {
					$sas_value_label .= "\n\tvalue $sas_val_enum_num" . $short_name . "_ ";
					$sas_format_string .= "\tformat " . $short_name . " " . $short_name . "_.;\n";
					if ($numericChoices) {
						$stata_value_label .= "\nlabel define " . $short_name . "_ "; //LS inserted this line 24-Feb-2012
						$stata_inf_label .= "label values " . $short_name . " " . $short_name . "_\n";
					}
				}
			}

			$first_new_line_explode_array = explode("\\n",(string)$ob->ELEMENT_ENUM);
			
			// Loop through multiple choice options
			$select_is_text = false;
			$select_determining_array = array();
			for ($counter = 0;$counter < count($first_new_line_explode_array);$counter++) {
				if (!$single_element_enum) {
		
					// SAS: Add line break after 2 multiple choice options
					if (($counter % 2) == 0 && $counter != 0) {
						$sas_value_label   .= "\n\t\t";
						$value_labels_spss .= "\n\t";
					}	
		
					$second_comma_explode = explode(",",$first_new_line_explode_array[$counter],2);
					$value_labels_spss .= "'" . trim($second_comma_explode[0]) . "' ";
					$value_labels_spss .= "'" . trim($second_comma_explode[1]) . "' ";
					if (!is_numeric(trim($second_comma_explode[0])) && is_numeric(substr(trim($second_comma_explode[0]), 0, 1))) {
						// if enum raw value is not a number BUT begins with a number, add quotes around it for SAS only (parsing issue)
						$sas_value_label .= "'" . trim($second_comma_explode[0]) . "'=";
					} else {
						$sas_value_label .= trim($second_comma_explode[0]) . "=";
					}
					$sas_value_label .= "'" . trim($second_comma_explode[1]) . "' ";
					if ($numericChoices) {
						$stata_value_label .= trim($second_comma_explode[0]) . " ";
						$stata_value_label .= "\"" . trim($second_comma_explode[1]) . "\" ";
					}
					$select_determining_array[] = $second_comma_explode[0];
					$R_factors_string .= '"' . trim($second_comma_explode[0]) . '",'; 
					$R_levels_string .= '"' . trim($second_comma_explode[1]) . '",';
				} else {
					$select_determining_array[] = $second_comma_explode[0];
					$R_factors_string .= '"' . trim($first_new_line_explode_array[$counter]) . '",'; 
					$R_levels_string .= '"' . trim($first_new_line_explode_array[$counter]) . '",';
				}
			}
			$R_factors_string = rtrim($R_factors_string,",");
			$R_factors_string .= "))\n";   //pharris 09/28/05
			$R_levels_string = rtrim($R_levels_string,",");
			$R_levels_string .=  ")\n";
			if (!$single_element_enum) {
				$sas_value_label = rtrim($sas_value_label," ");
				$sas_value_label .= ";";
			}	    
			if (!$single_element_enum) {
				foreach ($select_determining_array as $value) {
					if (preg_match("/([A-Za-z])/",$value)) {
						$select_is_text = true;
					}
				}
			} else {
				foreach ($first_new_line_explode_array as $value) {
					if (preg_match("/([A-Za-z])/",$value)) {
						$select_is_text = true;
					}
				}
			}
		
		
		} else if (preg_match("/\+\+SQL\+\+/",(string)$ob->ELEMENT_ENUM)) {
		
			$select_is_text = true;
			
		}
		################################################################################
		################################################################################    
		  
		# If the ELEMENT_VALIDATION_TYPE is a float the data is define as a Number
		if ($ob->ELEMENT_VALIDATION_TYPE == "float" || $ob->ELEMENT_TYPE == "calc") {
			$spss_string  .= $ob->FIELD_NAME . " (F8.2) ";
			if (!$large_name) {
				$sas_informat .= "\tinformat " . $ob->FIELD_NAME . " best32. ;\n";
				$sas_format .= "\tformat " . $ob->FIELD_NAME . " best12. ;\n";
				$sas_input .= "\t\t" . $ob->FIELD_NAME . "\n";
			} elseif ($large_name) {
				$sas_informat .= "\tinformat " .  $short_name . " best32. ;\n";
				$sas_format .= "\tformat " .  $short_name . " best12. ;\n";
				$sas_input .= "\t\t" .  $short_name . "\n";
			}
			// $spss_data_type_array[$x] = "NUMBER";
			$spss_variable_level[] = $ob->FIELD_NAME . " (SCALE)";
			
		} elseif ($ob->ELEMENT_TYPE == "slider" || $ob->ELEMENT_VALIDATION_TYPE == "int") {
			$spss_string  .= $ob->FIELD_NAME . " (F8) ";
			if(!$large_name) {
				$sas_informat .= "\tinformat " . $ob->FIELD_NAME . " best32. ;\n";
				$sas_format .= "\tformat " . $ob->FIELD_NAME . " best12. ;\n";
				$sas_input .= "\t\t" . $ob->FIELD_NAME . "\n";
			} elseif ($large_name) {
				$sas_informat .= "\tinformat " .  $short_name . " best32. ;\n";
				$sas_format .= "\tformat " .  $short_name . " best12. ;\n";
				$sas_input .= "\t\t" .  $short_name . "\n";
			}
			// $spss_data_type_array[$x] = "NUMBER";
			$spss_variable_level[] = $ob->FIELD_NAME . " (SCALE)";
		  
		# If the ELEMENT_VALIDATION_TYPE is a DATE a treat the data as a date 
		} elseif ($ob->ELEMENT_VALIDATION_TYPE == "date" || $ob->ELEMENT_VALIDATION_TYPE == "date_ymd" || $ob->ELEMENT_VALIDATION_TYPE == "date_mdy" || $ob->ELEMENT_VALIDATION_TYPE == "date_dmy") {
			$spss_string  .= $ob->FIELD_NAME . " (SDATE10) ";
			$spss_format_dates .= "FORMATS " . $ob->FIELD_NAME . "(ADATE10).\n";
			if (!$large_name) {
				$sas_informat .= "\tinformat " . $ob->FIELD_NAME . " yymmdd10. ;\n";
				$sas_format .= "\tformat " . $ob->FIELD_NAME . " yymmdd10. ;\n";
				$sas_input .= "\t\t" . $ob->FIELD_NAME . "\n";
				$stata_date_format .= "\ntostring " . $ob->FIELD_NAME . ", replace";
				$stata_date_format .= "\ngen _date_ = date(" .  $ob->FIELD_NAME . ",\"YMD\")\n";
				$stata_date_format .= "drop " . $ob->FIELD_NAME . "\n";
				$stata_date_format .= "rename _date_ " . $ob->FIELD_NAME . "\n";
				$stata_date_format .= "format " . $ob->FIELD_NAME . " %dM_d,_CY\n"; 
			} elseif ($large_name) {
				$sas_informat .= "\tinformat " . $short_name . " yymmdd10. ;\n";
				$sas_format .= "\tformat " . $short_name . " yymmdd10. ;\n";
				$sas_input .= "\t\t" . $short_name . "\n";
				$stata_date_format .= "\ntostring " . $short_name . ", replace";
				$stata_date_format .= "\ngen _date_ = date(" .   $short_name . ",\"YMD\")\n";
				$stata_date_format .= "drop " .  $short_name . "\n";
				$stata_date_format .= "rename _date_ " .  $short_name . "\n";
				$stata_date_format .= "format " . $short_name . " %dM_d,_CY\n"; 
			}
			
		# If the ELEMENT_VALIDATION_TYPE is TIME (military)
		} elseif ($ob->ELEMENT_VALIDATION_TYPE == "time") {
		
			$spss_string .= $ob->FIELD_NAME . " (A500) ";	
			if (!$large_name) {
				$sas_informat .= "\tinformat " . $ob->FIELD_NAME . " time5. ;\n";
				$sas_format .= "\tformat " . $ob->FIELD_NAME . " time5. ;\n";
				$sas_input .= "\t\t" . $ob->FIELD_NAME . "\n"; 
			} elseif ($large_name) {
				$sas_informat .= "\tinformat " . $short_name . " time5. ;\n";
				$sas_format .= "\tformat " . $short_name . " time5. ;\n";
				$sas_input .= "\t\t" . $short_name . "\n";
			}
			
		# If the ELEMENT_VALIDATION_TYPE is DATETIME or DATETIME_SECONDS
		// } elseif (substr($ob->ELEMENT_VALIDATION_TYPE, 0, 8) == "datetime") {
		
			
		
		# If the object type is select then the variable $select_is_text is checked to
		# see if it is a TEXT or a NUMBER and treated accordanly.
		} elseif($ob->ELEMENT_TYPE == "yesno" || $ob->ELEMENT_TYPE == "truefalse" || $ob->ELEMENT_TYPE == "select" || $ob->ELEMENT_TYPE == "advcheckbox" || $ob->ELEMENT_TYPE == "radio") {
			if ($select_is_text) {
				$temp_trim = rtrim("varchar(500)",")");
				# Divides the string to get the number of caracters
				$temp_explode_number = explode("(",$temp_trim);
				$spss_string  .= $ob->FIELD_NAME . " (A" . $temp_explode_number[1] . ") ";
				if (!$large_name) {
					$sas_informat .= "\tinformat " . $ob->FIELD_NAME . " \$". $temp_explode_number[1] .". ;\n";
					$sas_format .= "\tformat " . $ob->FIELD_NAME . " \$". $temp_explode_number[1] .". ;\n";
					$sas_input .= "\t\t" . $ob->FIELD_NAME . " \$\n";
				} elseif($large_name) {
					$sas_informat .= "\tinformat " . $short_name . " \$". $temp_explode_number[1] .". ;\n";
					$sas_format .= "\tformat " . $short_name . " \$". $temp_explode_number[1] .". ;\n";
					$sas_input .= "\t\t" . $short_name . " \$\n";
				}
				// $spss_data_type_array[$x] = "TEXT";
			} else {
				$spss_string .= $ob->FIELD_NAME . " (F3) ";
				if (!$large_name) {
					$sas_informat .= "\tinformat " . $ob->FIELD_NAME . " best32. ;\n";
					$sas_format .= "\tformat " . $ob->FIELD_NAME . " best12. ;\n";
					$sas_input .= "\t\t" . $ob->FIELD_NAME . "\n";
				} elseif ($large_name) {
					$sas_informat .= "\tinformat " . $short_name . " best32. ;\n";
					$sas_format .= "\tformat " . $short_name . " best12. ;\n";
					$sas_input .= "\t\t" . $short_name . "\n";
				}
				// $spss_data_type_array[$x] = "NUMBER";
			}
			

		# If the object type is text a treat the data like a text and look for the length
		# that is specified in the database
		} elseif ($ob->ELEMENT_TYPE == "text" || $ob->ELEMENT_TYPE == "calc" || $ob->ELEMENT_TYPE == "file") {
			
			$spss_string .= $ob->FIELD_NAME . " (A500) ";		
			if (!$large_name) {
				$sas_informat .= "\tinformat " . $ob->FIELD_NAME . " \$500. ;\n";
				$sas_format .= "\tformat " . $ob->FIELD_NAME . " \$500. ;\n";
				$sas_input .= "\t\t" . $ob->FIELD_NAME . " \$\n";
			} elseif ($large_name) {
				$sas_informat .= "\tinformat " . $short_name . " \$500. ;\n";
				$sas_format .= "\tformat " . $short_name . " \$500. ;\n";
				$sas_input .= "\t\t" . $short_name . " \$\n";
			}
			
			
		# If the object type is textarea a treat the data like a text and specify a large
		# string size.
		} elseif ($ob->ELEMENT_TYPE == "textarea") {
			$spss_string .= $ob->FIELD_NAME . " (A30000) ";
			if (!$large_name) {
				$sas_informat .= "\tinformat " . $ob->FIELD_NAME . " \$5000. ;\n";
				$sas_format .= "\tformat " . $ob->FIELD_NAME . " \$5000. ;\n";
				$sas_input .= "\t\t" . $ob->FIELD_NAME . " \$\n";
			} elseif ($large_name) {
				$sas_informat .= "\tinformat " . $short_name . " \$5000. ;\n";
				$sas_format .= "\tformat " . $short_name . " \$5000. ;\n";
				$sas_input .= "\t\t" . $short_name . " \$\n";
			}
			// $spss_data_type_array[$x] = "TEXT";
		}

	}
	
	// File names	
	$today = date("Y-m-d_Hi");
	$projTitleShort = substr(str_replace(" ", "", ucwords(preg_replace("/[^a-zA-Z0-9 ]/", "", html_entity_decode($app_title, ENT_QUOTES)))), 0, 20);
	$data_file_name = $projTitleShort . "_DATA_NOHDRS_" .$today. ".csv";
	$data_file_name_WH = $projTitleShort . "_DATA_" .$today. ".csv";
	$data_file_name_labels = $projTitleShort ."_DATA_LABELS_" .$today. ".csv";
    $export_sps_file_name = $projTitleShort ."_SPSS_" .$today. ".sps";
    $export_sas_file_name = $projTitleShort ."_SAS_" .$today. ".sas";
    $export_R_file_name = $projTitleShort ."_R_" .$today. ".r";  
    $export_stata_file_name = $projTitleShort ."_STATA_" .$today. ".do";
	
	//Finish up syntax files
	$spss_string = rtrim($spss_string);
	$spss_string .= ".\n";
	$spss_string .= "\nVARIABLE LEVEL " . implode("\n\t/", $spss_variable_level) . ".\n";
	$spss_string .= "\n" . substr_replace($spss_variable_label,".",-3) . "\n\n";
	$spss_string .= rtrim($value_labels_spss) ;
	$spss_string .= ".\n\n$spss_format_dates\nSET LOCALE=en_us.\nEXECUTE.\n";
	
    $spss_string = str_replace("data_place_holder_name",$data_file_name,$spss_string);	
    
	$sas_read_string .= "%macro removeOldFile(bye); %if %sysfunc(exist(&bye.)) %then %do; proc delete data=&bye.; run; "
					 .  "%end; %mend removeOldFile; %removeOldFile(work.redcap); data REDCAP; "; // Suggested change by Ray Balise
    //$sas_read_string .= "proc delete data=REDCAP;\nrun;\n\ndata REDCAP;"; // Added to prevent deleting all temp files
    //$sas_read_string .= "proc delete data=_ALL_;\nrun;\n\ndata REDCAP;";
    $sas_read_string .= "%let _EFIERR_ = 0; ";
    $sas_read_string .= "infile '" . $data_file_name . "'";
    $sas_read_string .= " delimiter = ',' MISSOVER DSD lrecl=32767 firstobs=1 ; ";
    $sas_read_string .= "\n" . $sas_informat ;
    $sas_read_string .= "\n" . $sas_format;
    $sas_read_string .= "\n" . $sas_input;
    $sas_read_string .= ";\n";
    $sas_read_string .= "if _ERROR_ then call symput('_EFIERR_',\"1\");\n";
    $sas_read_string .= "run;\n\nproc contents;run;\n\n";
    $sas_read_string .= $sas_label_section . "\trun;\n";
    $sas_value_label .= "\n\trun;\n";
    $sas_format_string .= "\trun;\n";
    $sas_read_string .= "\n" . $sas_value_label;
    $sas_read_string .= "\n" . $sas_format_string;
    $sas_read_string .= "\nproc contents data=redcap;";
    $sas_read_string .= "\nproc print data=redcap;";
    $sas_read_string .= "\nrun;\nquit;";
	
	$stata_order = "order " . substr($stata_insheet, 8);
	$stata_insheet .= "using " . "\"" . $data_file_name . "\", nonames";

    $stata_string .= $stata_insheet . "\n\n";
    $stata_string .= "label data " . "\"" . $data_file_name  . "\"" . "\n\n";
    $stata_string .= $stata_value_label . "\n";
    $stata_string .= $stata_inf_label. "\n\n";
    $stata_string .= $stata_date_format . "\n";
    $stata_string .= $stata_var_label . "\n";
    $stata_string .= $stata_order . "\n";
	$stata_string .= "describe\n";

    $R_string .= "#Read Data\ndata=read.csv('" . $data_file_name_WH . "')\n";
    $R_string .= $R_label_string;
    $R_string .= $R_units_string;
    $R_string .= $R_factors_string;
    $R_string .= $R_levels_string;  

		
	$today = date("Y-m-d-H-i-s");
	$docs_comment = $docs_comment_WH = "Data export file created by $userid on $today";
    $spss_docs_comment = "Spss syntax file created by $userid on $today";
    $sas_docs_comment = "Sas syntax file created by $userid on $today";
    $stata_docs_comment = "Stata syntax file created by $userid on $today";
    $R_docs_comment = "R syntax file created by $userid on $today";
    $data = prep($data);




	#########################################
	
	// Replace any MS Word chacters in the data
	$data_csv 		 = replaceMSchars($data_csv);
	$data_csv_labels = replaceMSchars($data_csv_labels);	
	
	//Add comment in last field if these are date shifted
	$doc_rights = $do_date_shift ? "'DATE_SHIFT'" : "NULL";
	
	// Set flag for checking if error occurs during saving of files to docs table
	$is_export_error = false;
	
	### Creates the STATA syntax file
	$stata_string = strip_tags($stata_string); // Do NOT use addBOMtoUTF8() on Stata because BOM causes issues in syntax file
	$docs_size = strlen($stata_string);
	$export_sql  = "INSERT INTO redcap_docs (project_id,docs_name,docs_file,docs_date,docs_size,docs_comment,docs_type,docs_rights,export_file) "
				 . "VALUES ($project_id, '" . $export_stata_file_name . "', NULL, '" . TODAY . "','$docs_size','" .$stata_docs_comment . "','application/octet-stream',$doc_rights,1)";
	if (!db_query($export_sql)) {
		$is_export_error = true;
	} else {
		// Get insert id
		$stata_doc_id = db_insert_id();
		// Store the file in the file system
		if (!storeExportFile($export_stata_file_name, $stata_string, $stata_doc_id, $docs_size)) {
			$is_export_error = true;
		}
	}

	### Creates the R syntax file
	$R_string = addBOMtoUTF8(strip_tags($R_string));
	$docs_size = strlen($R_string);
	$export_sql  = "INSERT INTO redcap_docs (project_id,docs_name,docs_file,docs_date,docs_size,docs_comment,docs_type,docs_rights,export_file) " 
				 . "VALUES ($project_id, '" . $export_R_file_name . "', NULL, '" . TODAY . "','$docs_size','" .$R_docs_comment . "','application/octet-stream',$doc_rights,1)";
	if (!db_query($export_sql)) {
		$is_export_error = true;
	} else {
		// Get insert id
		$r_doc_id = db_insert_id();
		// Store the file in the file system
		if (!storeExportFile($export_R_file_name, $R_string, $r_doc_id, $docs_size)) {
			$is_export_error = true;
		}
	}

	### Creates the SAS syntax file
	$sas_read_string = addBOMtoUTF8(strip_tags($sas_read_string));
	$docs_size = strlen($sas_read_string);
	$export_sql  = "INSERT INTO redcap_docs (project_id,docs_name,docs_file,docs_date,docs_size,docs_comment,docs_type,docs_rights,export_file) "
				 . "VALUES ($project_id, '" . $export_sas_file_name . "', NULL, '" . TODAY . "','$docs_size','" .$sas_docs_comment . "','application/octet-stream',$doc_rights,1)";
	if (!db_query($export_sql)) {
		$is_export_error = true;
	} else {
		// Get insert id
		$sas_doc_id = db_insert_id();
		// Store the file in the file system
		if (!storeExportFile($export_sas_file_name, $sas_read_string, $sas_doc_id, $docs_size)) {
			$is_export_error = true;
		}
	}

	### Creates the data comma separeted value file WITHOUT headers    
	$data_csv_temp = addBOMtoUTF8($data_csv);
	$docs_size = strlen($data_csv_temp);
	$export_sql  = "INSERT INTO redcap_docs (project_id,docs_name,docs_file,docs_date,docs_size,docs_comment,docs_type,docs_rights,export_file) "
				 . "VALUES ($project_id, '" . $data_file_name . "', NULL, '" . TODAY . "','$docs_size','" .$docs_comment . "','application/csv',$doc_rights,1)";
	if (!db_query($export_sql)) {
		$is_export_error = true;
	} else {
		// Get insert id
		$data_wo_hdr_doc_id = db_insert_id();
		// Store the file in the file system
		if (!storeExportFile($data_file_name, $data_csv_temp, $data_wo_hdr_doc_id, $docs_size)) {
			$is_export_error = true;
		}
	}
	unset($data_csv_temp);

	### Creates the data comma separeted value file WITH header
	$data_csv = addBOMtoUTF8($headers . $data_csv);
	$docs_size = strlen($data_csv);
	$export_sql  = "INSERT INTO redcap_docs (project_id,docs_name,docs_file,docs_date,docs_size,docs_comment,docs_type,docs_rights,export_file) "
				 . "VALUES ($project_id, '" . $data_file_name_WH . "', NULL, '" . TODAY . "','$docs_size','" .$docs_comment_WH . "','application/csv',$doc_rights,1)";
	if (!db_query($export_sql)) {
		$is_export_error = true;
	} else {
		// Get insert id
		$data_doc_id = db_insert_id();
		// Store the file in the file system
		if (!storeExportFile($data_file_name_WH, $data_csv, $data_doc_id, $docs_size)) {
			$is_export_error = true;
		}
	}
	unset($data_csv);

	### Creates the SPSS syntax file
	$spss_string = addBOMtoUTF8(strip_tags($spss_string));
	$docs_size = strlen($spss_string);
	$export_sql  = "INSERT INTO redcap_docs (project_id,docs_name,docs_file,docs_date,docs_size,docs_comment,docs_type,docs_rights,export_file) "
				 . "VALUES ($project_id, '" . $export_sps_file_name . "', NULL, '" . TODAY . "','$docs_size','" .$spss_docs_comment . "','application/octet-stream',$doc_rights,1)";
	if (!db_query($export_sql)) {
		$is_export_error = true;
	} else {
		// Get insert id
		$spss_doc_id = db_insert_id();
		// Store the file in the file system
		if (!storeExportFile($export_sps_file_name, $spss_string, $spss_doc_id, $docs_size)) {
			$is_export_error = true;
		}
	}

	### Creates the data comma separeted value file WITH LABELS
	$data_csv_labels = addBOMtoUTF8($headers_labels . $data_csv_labels);
	$docs_size = strlen($data_csv_labels);
	$export_sql  = "INSERT INTO redcap_docs (project_id,docs_name,docs_file,docs_date,docs_size,docs_comment,docs_type,docs_rights,export_file) "
				 . "VALUES ($project_id, '" . $data_file_name_labels . "', NULL, '" . TODAY . "','$docs_size','" .$docs_comment . "','application/csv',$doc_rights,1)";
	if (!db_query($export_sql)) {
		$is_export_error = true;
	} else {
		// Get insert id
		$data_labels_doc_id = db_insert_id();
		// Store the file in the file system
		if (!storeExportFile($data_file_name_labels, $data_csv_labels, $data_labels_doc_id, $docs_size)) {
			$is_export_error = true;
		}
	}

	#########################################

	//Catch the error if the CSV data file is too large for MySQL to handle
	if ($is_export_error) 
	{	
		include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';		
		renderPageTitle("<img src='".APP_PATH_IMAGES."application_go.png'> {$lang['app_03']}");		
		print  "<div class='red' style='margin:20px 0;'><img src='".APP_PATH_IMAGES."exclamation.png'> 
					<b>{$lang['global_01']}:</b><br/>{$lang['data_export_tool_62']}";
		if ($super_user) {
			if ($edoc_storage_option) {
				print $lang['data_export_tool_136'];
			} else {
				print $lang['data_export_tool_135'] . " (<b>" . EDOC_PATH . "</b>)" . $lang['period'];
			}
			print " " . $lang['data_export_tool_137'];
		} else {
			print "{$lang['data_export_tool_64']} <a href='mailto:$project_contact_email' style='font-family:Verdana;'>$project_contact_name</a> 
				   {$lang['data_export_tool_65']}";
		}
		print  "</div>";
		renderPrevPageLink(PAGE);
		include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
		exit;
	}

	//Catch the error if there were data conversion problems
	if ($is_data_conversion_error) 
	{	
		include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';		
		renderPageTitle("<img src='".APP_PATH_IMAGES."application_go.png'> {$lang['app_03']}");		
		print  "<div class='red' style='margin:20px 0;'><img src='".APP_PATH_IMAGES."exclamation.png'> 
					<b>{$lang['global_01']}:</b><br/>{$lang['data_export_tool_62']}";
		if ($super_user) {
			print  $lang['data_export_tool_63'];
		} else {
			print  "{$lang['data_export_tool_64']} <a href='mailto:$project_contact_email' style='font-family:Verdana;'>$project_contact_name</a> 
				{$lang['data_export_tool_65']}";
		}
		print $is_data_conversion_error_msg;
		print  "</div>";
		renderPrevPageLink(PAGE);
		include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
		exit;
	}

	
	
	
	// Header
	include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

	renderPageTitle("<img src='".APP_PATH_IMAGES."application_go.png'> {$lang['app_03']}");
	
	print  "<div style='text-align:center;padding-top:10px;max-width:700px;'>
				<span class='darkgreen' style='padding:8px 80px;'>
				<img src='".APP_PATH_IMAGES."tick.png' class='imgfix'> {$lang['data_export_tool_05']}
				</span>
			</div>
			<p><br>{$lang['data_export_tool_06']}<br><br>";
	
	// Button back to previous page
	$prevPage = (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : PAGE_FULL."?pid=$project_id";
	print  "<button class='jqbutton' onclick=\"window.location.href='$prevPage';\">
				<img src='" . APP_PATH_IMAGES . "arrow_left.png' class='imgfix'> 
				{$lang['config_functions_40']}
			</button>";
				
	//Set the CSV icon to date-shifted look if the data in these files were date shifted
	if ($do_date_shift) {
		$csv_img = "download_csvdata_ds.gif";
		$csvexcel_img = "download_csvexcel_raw_ds.gif";
		$csvexcellabels_img = "download_csvexcel_labels_ds.gif";
	} else {
		$csv_img = "download_csvdata.gif";
		$csvexcel_img = "download_csvexcel_raw.gif";
		$csvexcellabels_img = "download_csvexcel_labels.gif";
	}
	
	// If Send-It is not enabled for Data Export and File Repository, then hide the link to utilize Send-It
	$sendItLinkDisplay = ($sendit_enabled == '1' || $sendit_enabled == '3') ? "" : "display:none;";
	
	//Table header
	print  "<div style='max-width:700px;'>";
	print  "<table style='border: 1px solid #DODODO; border-collapse: collapse; width: 100%'>
			<tr class='grp2'>
				<td colspan='2' style='font-family:Verdana;font-size:12px;text-align:right;'>			
				</td>
				<td style='font-family:Verdana;font-size:12px;text-align:center;'>
					{$lang['docs_58']}<br>{$lang['data_export_tool_51']}
				</td>
			</tr>";
	//Excel
	print  '<tr class="odd">
				<td valign="top" style="text-align:center;width:60px;padding-top:10px;border:0px;border-left:1px solid #D0D0D0;">
					<img src="'.APP_PATH_IMAGES.'excelicon.gif" title="'.$lang['data_export_tool_15'].'" alt="'.$lang['data_export_tool_15'].'" />
				</td>
			    <td style="font-family:Verdana;font-size:11px;padding:10px;" valign="top">
					<b>'.$lang['data_export_tool_15'].'</b><br>
					'.$lang['data_export_tool_118'].'<br><br>
					<i>'.$lang['global_02'].': '.$lang['data_export_tool_17'].'</i>
				</td>
				<td valign="top" style="text-align:right;width:100px;padding-top:10px;">
					<a href="' . APP_PATH_WEBROOT . 'FileRepository/file_download.php?pid='.$project_id.'&id=' . $data_labels_doc_id .'">	
						<img src="'.APP_PATH_IMAGES.$csvexcellabels_img.'" title="'.$lang['data_export_tool_60'].'" alt="'.$lang['data_export_tool_60'].'"></a> &nbsp;
					<a href="' . APP_PATH_WEBROOT . 'FileRepository/file_download.php?pid='.$project_id.'&id=' . $data_doc_id .'">	
						<img src="'.APP_PATH_IMAGES.$csvexcel_img.'" title="'.$lang['data_export_tool_60'].'" alt="'.$lang['data_export_tool_60'].'"></a>
					<div style="text-align:left;padding:5px 0 1px;'.$sendItLinkDisplay.'">
						<div style="line-height:5px;">
							<img src="'.APP_PATH_IMAGES.'mail_small.png" style="position: relative; top: 5px;"><a 
								href="javascript:;" style="color:#666;font-size:10px;text-decoration:underline;" 
								onclick=\'$("#sendit_' . $data_doc_id .'").toggle("blind",{},"fast");\'>'.$lang['data_export_tool_66'].'</a>
						</div>
						<div id="sendit_' . $data_doc_id .'" style="display:none;padding:4px 0 4px 6px;">
							<div>
								&bull; <a href="javascript:;" onclick="popupSendIt(' . $data_labels_doc_id .',2);" style="font-size:10px;">'.$lang['data_export_tool_120'].'</a>
							</div>
							<div>
								&bull; <a href="javascript:;" onclick="popupSendIt(' . $data_doc_id .',2);" style="font-size:10px;">'.$lang['data_export_tool_119'].'</a>
							</div>
						</div>
					</div>
				</td>
			</tr>';
	//SPSS
	print '<tr class="even noncsv">
				<td valign="top" style="text-align:center;width:60px;padding-top:10px;border:0px;border-left:1px solid #D0D0D0;">
					<img src="'.APP_PATH_IMAGES.'spsslogo_small.png" title="'.$lang['data_export_tool_07'].'" alt="'.$lang['data_export_tool_07'].'" />
				</td>
				<td style="font-family:Verdana;font-size:11px;padding:10px;" valign="top">
					<b>'.$lang['data_export_tool_07'].'</b><br />'.$lang['global_24'].$lang['colon']." ".$lang['data_export_tool_08'].'<br>
					<a href="javascript:;" style="text-decoration:underline;font-size:11px;" onclick=\'$("#spss_detail").toggle("fade");\'>'.$lang['data_export_tool_08b'].'</a>
					<div style="display:none;border-top:1px solid #aaa;margin-top:5px;padding-top:3px;" id="spss_detail">
						<b>'.$lang['data_export_tool_01'].'</b><br>'.
						$lang['data_export_tool_08c'].' <font color="green">/folder/subfolder/</font> (e.g. /Users/administrator/documents/)<br><br>'.
						$lang['data_export_tool_08d'].'
						<br><font color=green>FILE HANDLE data1 NAME=\'DATA.CSV\' LRECL=90000.</font><br><br>'.
						$lang['data_export_tool_08e'].'<br>
						<font color=green>FILE HANDLE data1 NAME=\'<font color=red>/folder/subfolder/</font>DATA.CSV\' LRECL=90000.</font><br><br>'.
						$lang['data_export_tool_08f'].'
					</div>
				</td>
				<td valign="top" style="text-align:right;width:100px;padding-top:10px;">
					<a href="' . APP_PATH_WEBROOT . 'FileRepository/file_download.php?pid='.$project_id.'&id=' . $spss_doc_id . '">
						<img src="'.APP_PATH_IMAGES.'download_spss.gif" title="'.$lang['data_export_tool_68'].'" alt="'.$lang['data_export_tool_68'].'">
					</a> &nbsp; 
					<a href="' . APP_PATH_WEBROOT . 'FileRepository/file_download.php?pid='.$project_id.'&id=' . $data_wo_hdr_doc_id .'">	
						<img src="'.APP_PATH_IMAGES.$csv_img.'" title="'.$lang['data_export_tool_69'].'" alt="'.$lang['data_export_tool_69'].'"></a>
					<div style="padding-left:11px;text-align:left;">
						<a href="'.APP_PATH_WEBROOT.'DataExport/spss_pathway_mapper.php?pid='.$project_id.'"
						><img src="'.APP_PATH_IMAGES.'download_pathway_mapper.gif" title="'.$lang['data_export_tool_70'].'" alt="'.$lang['data_export_tool_70'].'"></a> &nbsp; 
					</div>
					<div style="text-align:left;padding:5px 0 1px;'.$sendItLinkDisplay.'">
						<div style="line-height:5px;">
							<img src="'.APP_PATH_IMAGES.'mail_small.png" style="position: relative; top: 5px;"><a 
								href="javascript:;" style="color:#666;font-size:10px;text-decoration:underline;" onclick=\'
									$("#sendit_' . $spss_doc_id . '").toggle("blind",{},"fast");
								\'>'.$lang['data_export_tool_66'].'</a>
						</div>
						<div id="sendit_' . $spss_doc_id . '" style="display:none;padding:4px 0 4px 6px;">
							<div>
								&bull; <a href="javascript:;" onclick="popupSendIt(' . $spss_doc_id . ',2);" style="font-size:10px;">'.$lang['data_export_tool_71'].'</a>
							</div>
							<div>
								&bull; <a href="javascript:;" onclick="popupSendIt(' . $data_wo_hdr_doc_id .',2);" style="font-size:10px;">'.$lang['data_export_tool_72'].'</a>
							</div>
						</div>
					</div>
				</td>
			</tr>';
	//SAS
	print '<tr class="odd noncsv">
				<td valign="top" style="text-align:center;width:60px;padding-top:10px;border:0px;border-left:1px solid #D0D0D0;">
					<img src="'.APP_PATH_IMAGES.'saslogo_small.png" title="'.$lang['data_export_tool_11'].'" alt="'.$lang['data_export_tool_11'].'" />
				</td>
				<td style="font-family:Verdana;font-size:11px;padding:10px;" valign="top">
					<b>'.$lang['data_export_tool_11'].'</b><br />'.$lang['global_24'].$lang['colon']." ".$lang['data_export_tool_130'].'<br>
					<a href="javascript:;" style="text-decoration:underline;font-size:11px;" onclick=\'$("#sas_detail").toggle("fade");\'>'.$lang['data_export_tool_08b'].'</a>
					<div style="display:none;border-top:1px solid #aaa;margin-top:5px;padding-top:3px;" id="sas_detail">
						<b>'.$lang['data_export_tool_131'].'</b><br>'.
						$lang['data_export_tool_132'].' <font color="green">/folder/subfolder/</font> (e.g. /Users/administrator/documents/)<br><br>'.
						$lang['data_export_tool_133'].'
						<br>... <font color=green>infile \'DATA.CSV\' delimiter = \',\' MISSOVER DSD lrecl=32767 firstobs=1 ;</font><br><br>'.
						$lang['data_export_tool_08e'].'<br>
						... <font color=green>infile \'<font color=red>/folder/subfolder/</font>DATA.CSV\' delimiter = \',\' MISSOVER DSD lrecl=32767 firstobs=1 ;</font><br><br>'.
						$lang['data_export_tool_134'].'
					</div>
				</td>
				<td valign="top" style="text-align:right;width:100px;padding-top:10px;">
					<a href="' . APP_PATH_WEBROOT . 'FileRepository/file_download.php?pid='.$project_id.'&id=' . $sas_doc_id .'">
						<img src="'.APP_PATH_IMAGES.'download_sas.gif" title="'.$lang['data_export_tool_74'].'" alt="'.$lang['data_export_tool_74'].'">
					</a> &nbsp; 
					<a href="' . APP_PATH_WEBROOT . 'FileRepository/file_download.php?pid='.$project_id.'&id=' . $data_wo_hdr_doc_id .'">	
						<img src="'.APP_PATH_IMAGES.$csv_img.'" title="'.$lang['data_export_tool_69'].'" alt="'.$lang['data_export_tool_69'].'"></a>
					<div style="padding-left:11px;text-align:left;">
						<a href="'.APP_PATH_WEBROOT.'DataExport/sas_pathway_mapper.php?pid='.$project_id.'"
						><img src="'.APP_PATH_IMAGES.'download_pathway_mapper.gif"></a> &nbsp; 
					</div>
					<div style="text-align:left;padding:5px 0 1px;'.$sendItLinkDisplay.'">
						<div style="line-height:5px;">
							<img src="'.APP_PATH_IMAGES.'mail_small.png" style="position: relative; top: 5px;"><a 
								href="javascript:;" style="color:#666;font-size:10px;text-decoration:underline;" onclick=\'
									$("#sendit_' . $sas_doc_id .'").toggle("blind",{},"fast");
								\'>'.$lang['data_export_tool_66'].'</a>
						</div>
						<div id="sendit_' . $sas_doc_id .'" style="display:none;padding:4px 0 4px 6px;">
							<div>
								&bull; <a href="javascript:;" onclick="popupSendIt(' . $sas_doc_id .',2);" style="font-size:10px;">'.$lang['data_export_tool_71'].'</a>
							</div>
							<div>
								&bull; <a href="javascript:;" onclick="popupSendIt(' . $data_wo_hdr_doc_id .',2);" style="font-size:10px;">'.$lang['data_export_tool_72'].'</a>
							</div>
						</div>
					</div>
				</td>
			</tr>';
	//R
	print '<tr class="even noncsv">
				<td valign="top" style="text-align:center;width:60px;padding-top:10px;border:0px;border-left:1px solid #D0D0D0;">
					<img src="'.APP_PATH_IMAGES.'rlogo_small.png" title="'.$lang['data_export_tool_09'].'" alt="'.$lang['data_export_tool_09'].'" />
				</td>
				<td style="font-family:Verdana;font-size:11px;padding:10px;" valign="top">
					<b>'.$lang['data_export_tool_09'].'</b><br />'.$lang['data_export_tool_10'].'
				</td>
				<td valign="top" style="text-align:right;width:100px;padding-top:10px;">
					<a href="' . APP_PATH_WEBROOT . 'FileRepository/file_download.php?pid='.$project_id.'&id=' . $r_doc_id .'">
						<img src="'.APP_PATH_IMAGES.'download_r.gif" title="'.$lang['data_export_tool_75'].'" alt="'.$lang['data_export_tool_75'].'">
					</a> &nbsp; 
					<a href="' . APP_PATH_WEBROOT . 'FileRepository/file_download.php?pid='.$project_id.'&id=' . $data_doc_id .'&exporttype=R">	
						<img src="'.APP_PATH_IMAGES.$csv_img.'" title="'.$lang['data_export_tool_69'].'" alt="'.$lang['data_export_tool_69'].'"></a>
					<div style="text-align:left;padding:5px 0 1px;'.$sendItLinkDisplay.'">
						<div style="line-height:5px;">
							<img src="'.APP_PATH_IMAGES.'mail_small.png" style="position: relative; top: 5px;"><a 
								href="javascript:;" style="color:#666;font-size:10px;text-decoration:underline;" onclick=\'
									$("#sendit_' . $r_doc_id .'").toggle("blind",{},"fast");
								\'>'.$lang['data_export_tool_66'].'</a>
						</div>
						<div id="sendit_' . $r_doc_id .'" style="display:none;padding:4px 0 4px 6px;">
							<div>
								&bull; <a href="javascript:;" onclick="popupSendIt(' . $r_doc_id .',2);" style="font-size:10px;">'.$lang['data_export_tool_71'].'</a>
							</div>
							<div>
								&bull; <a href="javascript:;" onclick="popupSendIt(' . $data_doc_id .',2);" style="font-size:10px;">'.$lang['data_export_tool_72'].'</a>
							</div>
						</div>
					</div>
				</td>
			</tr>';
	//STATA
	print '<tr class="odd noncsv">
				<td valign="top" style="text-align:center;width:60px;padding-top:10px;border:0px;border-bottom:1px solid #D0D0D0;border-left:1px solid #D0D0D0;">
					<img src="'.APP_PATH_IMAGES.'statalogo_small.png" title="'.$lang['data_export_tool_13'].'" alt="'.$lang['data_export_tool_13'].'" />
				</td>
				<td style="font-family:Verdana;font-size:11px;padding:10px;border-bottom:1px solid #D0D0D0;" valign="top">
					<b>'.$lang['data_export_tool_13'].'</b><br />'.$lang['data_export_tool_14'].'
				</td>
				<td valign="top" style="text-align:right;width:100px;padding-top:10px;border-bottom:1px solid #D0D0D0;">
					<a href="' . APP_PATH_WEBROOT . 'FileRepository/file_download.php?pid='.$project_id.'&id=' . $stata_doc_id .'">
						<img src="'.APP_PATH_IMAGES.'download_stata.gif" title="'.$lang['data_export_tool_76'].'" alt="'.$lang['data_export_tool_76'].'">
					</a> &nbsp; 
					<a href="' . APP_PATH_WEBROOT . 'FileRepository/file_download.php?pid='.$project_id.'&id=' . $data_wo_hdr_doc_id .'">	
						<img src="'.APP_PATH_IMAGES.$csv_img.'" title="'.$lang['data_export_tool_69'].'" alt="'.$lang['data_export_tool_69'].'"></a>
					<div style="text-align:left;padding:5px 0 1px;'.$sendItLinkDisplay.'">
						<div style="line-height:5px;">
							<img src="'.APP_PATH_IMAGES.'mail_small.png" style="position: relative; top: 5px;"><a 
								href="javascript:;" style="color:#666;font-size:10px;text-decoration:underline;" onclick=\'
									$("#sendit_' . $stata_doc_id .'").toggle("blind",{},"fast");
								\'>'.$lang['data_export_tool_66'].'</a>
						</div>
						<div id="sendit_' . $stata_doc_id .'" style="display:none;padding:4px 0 4px 6px;">
							<div>
								&bull; <a href="javascript:;" onclick="popupSendIt(' . $stata_doc_id .',2);" style="font-size:10px;">'.$lang['data_export_tool_71'].'</a>
							</div>
							<div>
								&bull; <a href="javascript:;" onclick="popupSendIt(' . $data_wo_hdr_doc_id .',2);" style="font-size:10px;">'.$lang['data_export_tool_72'].'</a>
							</div>
						</div>
					</div>
				</td>
			</tr>';
	  
	print '</table>';
	print '</div><br><br><br><br>';
	
	
	//Dialog box used for displaying popup for citations
	print  "<div id='popup' style='display:none;padding:15px;'>";
	//Do not display grant statement unless $grant_cite has been set for this project.
	if ($grant_cite != "") {
		print "{$lang['data_export_tool_77']} $site_org_type {$lang['data_export_tool_78']} <b>($grant_cite)</b> {$lang['data_export_tool_79']}<br/><br/>
			{$lang['data_export_tool_80']}";
	} else {
		print $lang['data_export_tool_81'];
	}
	print  $lang['data_export_tool_82']."
			<a href='http://www.project-redcap.org/cite.php' target='_blank' style='text-decoration:underline;'>{$lang['data_export_tool_83']}</a>).<br/>";
	// If instruments have been downloaded from the Shared Library, provide citatation
	if ($Proj->formsFromLibrary()) {
		print  "<div style='padding:10px 0 0;'>
					{$lang['data_export_tool_144']}
					<a href='javascript:;' style='text-decoration:underline;' onclick=\"simpleDialog(null,null,'rsl_cite',550);\">{$lang['data_export_tool_145']}</a>
				</div>";
	}
	// If dates were date-shifted, give note of that.
	if ($do_date_shift) {
		print  "<div class='red' style='margin-top:20px;'>
					<b>{$lang['global_03']}:</b><br/>
					{$lang['data_export_tool_85']} $date_shift_max {$lang['data_export_tool_86']}
				</div>";
	}
	print  "</div>
			<!-- Hidden citation for Shared Library manuscript -->
			<div class='simpleDialog' style='font-size:13px;' id='rsl_cite' title='".cleanHtml($lang['data_export_tool_146'])."'>
				Jihad S. Obeid, Catherine A. McGraw, Brenda L. Minor, Jos&eacute; G. Conde, Robert Pawluk, Michael Lin, Janey Wang, Sean R. Banks, Sheree A. Hemphill, Rob Taylor, Paul A. Harris, 
				<b>Procurement of shared data instruments for Research Electronic Data Capture (REDCap)</b>, Journal of Biomedical Informatics, Available online 10 November 2012, ISSN 1532-0464, 10.1016/j.jbi.2012.10.006.
				(<a target='_blank' style='text-decoration:underline;' href='http://www.sciencedirect.com/science/article/pii/S1532046412001608'>http://www.sciencedirect.com/science/article/pii/S1532046412001608</a>)
			</div>";
	
	
	// STANDARDS MAPPING: Only allow to export to Excel/CSV
	if ($useStandardCodes) 
	{
		?>
		<style type="text/css">
		.noncsv { display:none; }
		.odd { border-bottom: 1px solid #bbb; }
		</style>
		<?php 
	}
	
	
	// CITATION NOTICE dialog
	?>
	<script type="text/javascript">
	$(function(){
		$('#popup').dialog({ bgiframe: true, modal: true, width: 450, title: '<?php echo cleanHtml($lang['data_export_tool_147']) ?>', 
			buttons: {'Okay': function() { $(this).dialog('close'); } } });
	});
	</script>
	<?php
			
	include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
	
	exit;
	
}























###########################################################################
## BEGIN INITIAL PAGE

//Count metadata fields
$metadata_count = db_result(db_query("SELECT count(1) FROM redcap_metadata where project_id = $project_id"), 0);

/**
 * De-Identification Options box
 */
if ($user_rights['data_export_tool'] != 1) {	
	// User has limited rights, so check off everything and disable options
	$deid_msg = "<font color=red>{$lang['data_export_tool_87']}</font>";
	$deid_disable2 = "onclick=\"this.checked=false;\"";
	$deid_disable = "checked onclick=\"this.checked=true;\"";
	$deid_disable_date2 =  "onclick=\"
							var thisfld = this.getAttribute('id');
							var thisfldId = thisfld;
							if (thisfld == 'deid-dates-remove'){
								var thatfld = document.getElementById('deid-dates-shift');
								thisfld = document.getElementById('deid-dates-remove');
							} else {
								var thatfld = document.getElementById('deid-dates-remove');
								thisfld = document.getElementById('deid-dates-shift');
							};
							if (thisfld.checked==true) {
								thatfld.checked=false;
								thisfld.checked=true;
								if (thisfldId == 'deid-dates-remove'){
									$('#deid-surveytimestamps-shift').prop('disabled',true).prop('checked',false);
								} else {
									$('#deid-surveytimestamps-shift').prop('disabled',false);
								}				
							} else {
								thisfld.checked=false;
								thatfld.checked=true;				
								if (thisfldId == 'deid-dates-remove'){
									$('#deid-surveytimestamps-shift').prop('disabled',false);
								} else {
									$('#deid-surveytimestamps-shift').prop('disabled',true).prop('checked',false);
								}
							}\"";
	$deid_disable_date = "checked $deid_disable_date2";
	$deid_deselect = "";
	// Determine if id field is an Identifier. If so, auto-check it
	$idFieldIsIdentifier = db_result(db_query("select count(1) from {$app_name}_metadata where field_name = '$table_pk' and field_phi = '1'"), 0);
	$deid_hashid = $idFieldIsIdentifier ? $deid_disable : $deid_disable2;
} else {
	// User has full export rights
	$deid_msg = "";
	$deid_disable  = "";
	$deid_disable2 = "";
	$deid_disable_date = "onclick=\"$('#deid-surveytimestamps-shift').prop('disabled', !this.checked);\"";
	$deid_disable_date2 =  "onclick=\"
							var shiftfld = document.getElementById('deid-dates-shift');
							if (this.checked == true) {
								shiftfld.checked = false;
								shiftfld.disabled = true;
								$('#deid-surveytimestamps-shift').prop('disabled',true).prop('checked',false);
							} else {
								shiftfld.disabled = false;
								$('#deid-surveytimestamps-shift').prop('disabled',false);
							}\"";
	$deid_deselect =   "<br><br>
						<a href='javascript:;' style='font-size:8pt;text-decoration:underline;' onclick=\"
							document.getElementById('deid-remove-identifiers').checked = false;
							document.getElementById('deid-hashid').checked = false;
							document.getElementById('deid-remove-text').checked = false;
							document.getElementById('deid-remove-notes').checked = false;
							document.getElementById('deid-dates-remove').checked = false;
							document.getElementById('deid-dates-shift').checked = false;
							document.getElementById('deid-dates-shift').disabled = false;
						\">{$lang['data_export_tool_88']}</a><br>";
	$deid_hashid = "";
}
$deid_option_box = "<div class='red' style='font-family:Verdana,Arial;font-size:11px;color:#000;' id='deidbox'>
						<div style='margin:6px 0 3px 0;font-weight:bold;font-size:13px;'><u>{$lang['data_export_tool_89']}
						<span style='font-weight:normal;font-size:11px;'>{$lang['global_06']}</span></u></div>
						{$lang['data_export_tool_91']} $deid_msg<br/><br/>
						
						<div style='margin-top:7px;font-weight:bold;'>{$lang['data_export_tool_92']}</div>
						<div style='margin-left:2.3em;text-indent:-2.3em;'>
							<input type='checkbox' $deid_disable id='deid-remove-identifiers' name='deid-remove-identifiers' class='imgfix2'> 
							{$lang['data_export_tool_93']} <i style='color:#555;font-size:10px;'>{$lang['data_export_tool_94']}</i><br>
						</div>
						<div style='margin-left:2.3em;text-indent:-2.3em;'>
							<input type='checkbox' $deid_hashid id='deid-hashid' name='deid-hashid' class='imgfix2'> {$lang['data_export_tool_95']} $table_pk_label 
							<i style='color:#555;font-size:10px;'>{$lang['data_export_tool_96']}</i><br>
						</div>
						
						<div style='margin-top:12px;font-weight:bold;'>{$lang['data_export_tool_97']}</div>
						<div style='margin-left:2.3em;text-indent:-2.3em;'>
							<input type='checkbox' $deid_disable id='deid-remove-text' name='deid-remove-text' class='imgfix2'> 
							{$lang['data_export_tool_98']} <i style='color:#555;font-size:10px;'>{$lang['data_export_tool_99']}</i><br>
						</div>
						<div style='margin-left:2.3em;text-indent:-2.3em;'>
							<input type='checkbox' $deid_disable id='deid-remove-notes' name='deid-remove-notes' class='imgfix2'> 
							{$lang['data_export_tool_100']}<br>
						</div>
						
						<div style='margin-top:12px;font-weight:bold;'>{$lang['data_export_tool_129']}</div>
						<div style='margin-left:2.3em;text-indent:-2.3em;'>
							<input type='checkbox' $deid_disable_date2 id='deid-dates-remove' name='deid-dates-remove' class='imgfix2'> 
							{$lang['data_export_tool_128']}
						</div>
						<div style='padding:6px 0 3px;color:#555;'>
							&mdash; {$lang['global_46']} &mdash; 
						</div>
						<div style='margin-left:2.3em;text-indent:-2.3em;'>
							<input type='checkbox' $deid_disable_date id='deid-dates-shift' name='deid-dates-shift' class='imgfix2'> 
							{$lang['data_export_tool_103']} $date_shift_max {$lang['data_export_tool_104']}
							<i style='color:#555;font-size:10px;'>{$lang['data_export_tool_105']}</i><br> 
							<a href='javascript:;' style='font-size:8pt;text-decoration:underline;' onclick=\"
								window.open('".APP_PATH_WEBROOT."DataExport/date_shift_popup.php?pid=$project_id','myWin','width=600, height=500, toolbar=0, menubar=0, location=0, status=0, scrollbars=1, resizable=1');
							\">{$lang['data_export_tool_106']}</a>
						</div>
						".(($surveys_enabled && !empty($Proj->surveys)) ?
							"<div style='margin-left:4em;text-indent:-2em;padding-top:2px;'>
								<input type='checkbox' id='deid-surveytimestamps-shift' name='deid-surveytimestamps-shift' class='imgfix2' ".($user_rights['data_export_tool'] == 1 ? "disabled" : "")."> 
								{$lang['data_export_tool_143']} $date_shift_max {$lang['data_export_tool_104']}<br>
								<i style='color:#555;font-size:10px;'>{$lang['data_export_tool_105']}</i>
							</div>"
							: ""
						)."
						$deid_deselect
					</div>";





//HTML for select all buttons for all fields
$button_text_select_all   = '<input type="button" value="'.$lang['data_export_tool_52'].'" onclick="';
$button_text_deselect_all = '<input type="button" value="'.$lang['data_export_tool_53'].'" onclick="';

	
// Options to remove DAG field and survey-related fields
$dags = $Proj->getUniqueGroupNames();
$exportDagOption = "";
$exportSurveyFieldsOptions = "";
if (!empty($dags) && $user_rights['group_id'] == "") {
	$exportDagOption = RCView::checkbox(array('name'=>'flag-exportDataAccessGroups','checked'=>'checked','class'=>'imgfix2')) . 
					   $lang['data_export_tool_138'];
}
if ($surveys_enabled) {
	$exportSurveyFieldsOptions = RCView::checkbox(array('name'=>'flag-exportSurveyFields','checked'=>'checked','class'=>'imgfix2')) . 
								 $lang['data_export_tool_139'];
}
$exportDagSurveyFieldsOptions = RCView::div(array('class'=>'chklist','style'=>'margin-bottom:20px;padding:10px;'), 
									RCView::b($lang['data_export_tool_140']) . RCView::br() .
									$exportDagOption . RCView::br() .
									$exportSurveyFieldsOptions
								);
if ($exportDagOption != "" || $exportSurveyFieldsOptions != "") {
	$elements1[] = array('rr_type'=>'header', 
						 'css_element_class'=>'',
						 'style'=>'padding:0px;',
						 'value'=> $exportDagSurveyFieldsOptions);
}			 

//Determine if there are too many fields to show. If so, just show form names to check off.
if (($metadata_count > $max_metadata) && (!isset($_GET['showfields']))) {
	
	//DISPLAYING FORMS ONLY
	
	//Render the table with form checkboxes normally
	if (!$is_child) {
		$sql = "select form_name, form_menu_description from redcap_metadata where project_id = $project_id and form_menu_description is not null order by field_order";
		$offset = 0;
	//Render the table with form checkboxes for a shared parent database and child (if applicable)
	} else {
		$sql = "select form_name, form_menu_description, field_order, tbl from (
				(select form_name, form_menu_description, field_order, 1 as tbl from redcap_metadata where project_id = $project_id_parent and form_menu_description is not null)
				UNION
				(select form_name, form_menu_description, field_order, 2 as tbl from redcap_metadata where project_id = $project_id and form_menu_description is not null)
				) as x order by tbl, field_order";
		//Count forms in parent metadata table in order to help apply "parent____" prefix to all parent form checkboxes
		$offset = db_result(db_query("select count(distinct(form_name)) from redcap_metadata where project_id = $project_id_parent"),0);
	}	
	//Query metadata table for rendering the table with checkboxes
	$i = 1; //Counter
	$q = db_query($sql);
	while ($row = db_fetch_assoc($q)) {
		//Set defaults
		$link_img = "";		
		//If project is linked to shared parent database, display link icon by parent forms
		if ($is_child && $i <= $offset) {
			$row['form_name'] = "parent____" . $row['form_name'];
			$link_img = " <img src='".APP_PATH_IMAGES."link.png' class='imgfix' title='".$lang['data_export_tool_107']."' alt='".$lang['data_export_tool_107']."'>";
		}
		//Add element as table row
		$elements1[] = array('rr_type'=>'checkbox_single', 
							 'name'=>$row['form_name'], 
							 'label'=>$row['form_menu_description'] . $link_img
							);		
		//Add javascript for each form select all buttons
		$button_text_select_all   .= 'document.form.'.$row['form_name'].'.checked=true;';
		$button_text_deselect_all .= 'document.form.'.$row['form_name'].'.checked=false;';
		//Add to counter
		$i++;
	}
	//Add javascript for each form select all buttons
	$button_text_select_all   .= '">';
	$button_text_deselect_all .= '">';
	
	//De-Identification Options box
	$elements1[] = array('rr_type'=>'header', 
						 'css_element_class'=>'header',
						 'style'=>'padding:0px;',
						 'value'=> $deid_option_box);
						 
	//Hidden field to denote that forms were submitted (rather than fields) - will be used in post processing
	$elements1[] = array('rr_type'=>'hidden', 
						 'name'=>'__forms_only__',
						 'value'=>'1'
						);

} else {

	//DISPLAY ALL FIELDS
	
	//First get onclick javascript for Duplicate Last Export button
	$lastexport_onclick = "";
	$q = db_query("select data_values from redcap_log_event where project_id = $project_id and event = 'DATA_EXPORT' and legacy = '0' order by log_event_id desc limit 1");
	if (db_num_rows($q) == 1) {
		$csv_exported_fields = str_replace(" ","",db_result($q,0));
		$array_exported_fields = explode(",",$csv_exported_fields);
		//Normal
		if (!$is_child) {
			foreach ($array_exported_fields as $value) {
				$lastexport_onclick .= "document.form.$value.checked=true;";
			}
		//If project is child of shared parent database
		} elseif ($is_child) {
			//Get list of all field_names in parent in order to render javascript properly (parent's must have "parent____" prefix)
			$parent_fields = array();
			$q = db_query("select field_name from redcap_metadata where project_id = $project_id_parent");
			while ($row = db_fetch_assoc($q)) {
				$parent_fields[] = $row['field_name'];
			}
			foreach ($array_exported_fields as $value) {
				if (in_array($value,$parent_fields) || $value == $table_pk) $value = "parent____" . $value;
				$lastexport_onclick .= "document.form.$value.checked=true;";
			}
		}
	}
	
	//Prefix for each form's select all buttons
	$button_text_selectall_prefix   = '<div class="normal"><input type="button" value="'.$lang['data_export_tool_52'].'" onclick="';
	$button_text_deselectall_prefix = '<input type="button" value="'.$lang['data_export_tool_53'].'" onclick="';	
	//HTML for select all buttons for each forms
	if ($lastexport_onclick == "") {
		//Don't show Duplicate Last Export button
		$button_text = "";
	} else {
		//Show Duplicate Last Export button
		$button_text = "<input type='button' value='{$lang['data_export_tool_54']}' onclick='$lastexport_onclick'> 
						{$lang['data_export_tool_141']} <span style='font-weight:normal;'>{$lang['data_export_tool_142']}</span><br><br>";
	}
	
	// Render the table
	if (!$is_child) 
	{
		$sql = "select field_name, form_name, form_menu_description, element_type, element_label, field_phi, element_preceding_header 
				from redcap_metadata where project_id = $project_id order by field_order";
		$offset = 0;
	}
	// Render the table with checkboxes for a shared parent database and child (if applicable)
	else 
	{
		$sql = "select field_name, form_name, form_menu_description, element_type, element_label, field_phi, element_preceding_header, field_order, tbl 
				from (
				(select field_name, form_name, form_menu_description, element_type, element_label, field_phi, element_preceding_header, field_order, 1 as tbl 
				from redcap_metadata where project_id = $project_id_parent and element_type != 'descriptive' order by field_order) UNION
				(select field_name, form_name, form_menu_description, element_type, element_label, field_phi, element_preceding_header, field_order, 2 as tbl 
				from redcap_metadata where project_id = $project_id order by field_order)
				) as x order by tbl, field_order";
		//Count fields in parent metadata table in order to help apply "parent____" prefix to all parent fields
		$offset = db_result(db_query("select count(1) from redcap_metadata where project_id = $project_id_parent"),0);
	}
		
	//Render the table with checkboxes	
	$i = 1; //Counter
	//Query metadata table for rendering the table with checkboxes
	$q = db_query($sql);
	$stdSelectListElements = "";
	while ($row = db_fetch_assoc($q)) 
	{
		
		$display_fieldname = $row['field_name'];
		$link_img = "";
		
		//If project is linked to shared parent database, display link icon by parent forms
		if ($is_child && $i < $offset) {
			$row['field_name'] = "parent____" . $row['field_name'];
			$link_img = " <img src='".APP_PATH_IMAGES."link.png' class='imgfix' title='".$lang['data_export_tool_107']."' alt='".$lang['data_export_tool_107']."'>";
		}
		
		//If this is the beginning of a form, render the form name
		if ($row['form_menu_description'] != "") 
		{
			// STANDARDS MAPPING: Check if this form has any fields mapped
			$rs = db_query("select distinct std.standard_id, std.standard_name, std.standard_version
								from redcap_metadata meta, redcap_standard_map map, redcap_standard_code code, redcap_standard std
								where
  									meta.project_id = $project_id
  									and meta.form_name = '{$row['form_name']}'
  									and meta.project_id = map.project_id
  									and meta.field_name = map.field_name
  									and map.standard_code_id = code.standard_code_id
  									and code.standard_id = std.standard_id");
			
			$formHeaderText = " <span style='color:#800000;font-size:17px;font-weight:bold;'>{$row['form_menu_description']} </span>";
			if (db_num_rows($rs) > 0) {
				$stdSelectListElements .= "'stdmapselect-{$row['form_name']}',";
				$formHeaderText .= "<span style='float:right;'>Standard: ";
				$formHeaderText .= "<select class='x-form-text x-form-field notranslate' style='padding-right: 0pt; height: 22px;' name='stdmapselect-{$row['form_name']}' id='stdmapselect-{$row['form_name']}' onchange='updateStdMapSelect();'>";
				$formHeaderText .= "<option name='none' value='0'>none</option>";
				while($std_entry = db_fetch_array($rs)) {
					$s_name = html_entity_decode($std_entry['standard_name'], ENT_QUOTES);
					$s_version = html_entity_decode($std_entry['standard_version'], ENT_QUOTES);
					$formHeaderText .= "<option value='{$std_entry['standard_id']}'>$s_name $s_version</option>";
				}
				$formHeaderText .= "</select></span>";
			}
			$elements1[] = array('rr_type'=>'header', 
								 'css_element_class'=>'header', 
								 'value'=>'<div class=\'yellow\' style=\'margin:-5px;padding:15px 10px;\'>'
										. $lang['data_export_tool_04']
										. $formHeaderText
										. $link_img.'</div>'
								);
			//Add javascript for each form select all buttons for previous form
			if ($i != 1) {
				$button_text .= "$button_text_selectall_thisform\"> $button_text_deselectall_thisform\"> {$lang['data_export_tool_04']} 
								 <font style='color:#800000'>$prev_form_menu_description</font></div>";
			}
			//Reset javascript for this form's select all buttons
			$button_text_selectall_thisform   = $button_text_selectall_prefix;
			$button_text_deselectall_thisform = $button_text_deselectall_prefix;
		}
		
		//If project is linked to shared parent database, prevent Study ID field from showing a second time in child field listings
		if ($is_child) {
			if (($i == $offset+1 || $i == $offset) && $row['field_name'] == $table_pk) {
				//Record this form_menu_description for the next loop
				$prev_form_menu_description = $row['form_menu_description'];
				//Add to counter
				$i++;
				continue;
			}
		}
		
		// Add javascript for select all buttons
		if ($row['element_type'] != 'descriptive' && (!$row['field_phi'] || ($row['field_phi'] && $user_rights['data_export_tool'] == '1'))) 
		{
			$button_text_selectall_thisform   .= 'document.form.'.$row['field_name'].'.checked=true;';
			$button_text_deselectall_thisform .= 'document.form.'.$row['field_name'].'.checked=false;';
			$button_text_select_all   		  .= 'document.form.'.$row['field_name'].'.checked=true;';
			$button_text_deselect_all 		  .= 'document.form.'.$row['field_name'].'.checked=false;';
		}
		
		//Check if Section Header exists for this field. If so, render it before its associated field.
		if ($row['element_preceding_header'] != "") {
			$elements1[] = array('rr_type'=>'header', 
								 'css_element_class'=>'header', 
								 'value'=>filter_tags($row['element_preceding_header'])
								);
		}
		
		// Remove harmful tags from label
		$row['element_label'] = filter_tags($row['element_label']);
		
		//Render field
		if ($row['element_type'] != 'descriptive')
		{
			if ($row['field_phi']) {
				//This field is an identifier		
				if ($user_rights['data_export_tool'] == 1) {
					//User has full export rights
					//Show checkbox with red lettering
					$elements1[] = array('rr_type'=>'checkbox_single', 
										 'name'=>$row['field_name'], 
										 'label'=>'<font style="color:red">'.$row['element_label'].'</font> <i style="color:#888">('.$display_fieldname.')</i>'
										);
				} else {
					//User has de-id export rights
					//Don't show checkbox
					$elements1[] = array('rr_type'=>'image', 
										 'src'=>APP_PATH_IMAGES.'cross.png',
										 'onclick'=>'javascript:return false;',
										 'label'=>'<font style="color:red">'.$row['element_label'].'</font> <i style="color:#888">('.$display_fieldname.')</i>'
										);
				}
			} else {
				//Show checkbox as normal (regardless of rights access)
				$elements1[] = array('rr_type'=>'checkbox_single', 
									 'name'=>$row['field_name'], 
									 'label'=>$row['element_label'].' <i style="color:#888">('.$display_fieldname.')</i>'
									);
			}
		}
		
		//Record this form_menu_description for the next loop (if next loop begins new form)
		if ($row['form_menu_description'] != "") $prev_form_menu_description = $row['form_menu_description'];
		//Add to counter
		$i++;
	}
	$stdSelectListElements = substr($stdSelectListElements, 0, strlen($stdSelectListElements)-1);

	// Add javascript for form select all buttons for last form
	$button_text .= "$button_text_selectall_thisform\"> $button_text_deselectall_thisform\"> {$lang['data_export_tool_04']} 
					 <font style='color:#800000'>$prev_form_menu_description</font></div>";
	
		
	//Finalize select all buttons for every field
	$button_text_select_all   .= '">';
	$button_text_deselect_all .= '">';
	
	//De-Identification Options box
	$elements1[] = array('rr_type'=>'header', 
						 'css_element_class'=>'header',
						 'style'=>'padding:0px;',
						 'value'=> $deid_option_box);

}

// STANDARDS MAPPING OPTIONS: Hidden until further notice
$standards_mapping_box = "<div class='blue' style='display:none;font-family:Verdana,Arial;font-size:11px;color:#000;' id='stdmap_options_box'>
						<div style='margin:6px 0 3px 0;font-weight:bold;font-size:13px;'><u>Standards Mapping Options
						<span style='font-weight:normal;font-size:11px;'>{$lang['global_06']}</span></u></div>
						<input type='hidden' name='stdmap-use-standards' id='stdmap-use-standards' value='false'>
						The options below allow you to use alternative field names and data conversion based on data standards.<br/><br/>
						<div style='margin-top:10px;font-weight:bold;'>Field Names</div>
						<div style='margin-left:2.3em;text-indent:-2.3em;'>
							<input type='radio' id='stdmap-field-names' name='stdmap-field-names' value='false' checked> 
							Substitute standard field names<br>
						</div>
						<div style='margin-left:2.3em;text-indent:-2.3em;'>
							<input type='radio' id='stdmap-field-names1' name='stdmap-field-names' value='true'>
							Include both REDCap field names and standard field names<br>
						</div>

						<div style='margin-top:10px;font-weight:bold;'>Data Conversion</div>
						<div style='margin-left:2.3em;text-indent:-2.3em;'>
							<input type='radio' id='stdmap-data-conversion' name='stdmap-data-conversion' value='yes'> 
							Use conversion formula (if any) defined for selected standard<br>
						</div>
						<div style='margin-left:2.3em;text-indent:-2.3em;'>
							<input type='radio' id='stdmap-data-conversion1' name='stdmap-data-conversion' value='no' checked> 
							Leave data as is<br>
						</div>
						<br/>
						</div>";
$elements1[] = array('rr_type'=>'header',
                     'css_element_class'=>'header',
                     'style'=>'display:none;',
                     'value'=> $standards_mapping_box);

//Submit button
$elements1[] = array('rr_type'=>'header', 
					 'css_element_class'=>'header',
					 'style'=>'padding:0px;',
					 'value'=> "<div style='background-color:#f0f0f0;padding:10px;text-align:center;'><input type='submit' value='Submit' name='submit'></div>");















################################################################################
################################################################################
#If form has not been submitted, display user interface HTML code
include APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

renderPageTitle("<img src='".APP_PATH_IMAGES."application_go.png'> {$lang['app_03']}");

// If users have performed previous exports, provide JS to pre-select fields of last export
if (isset($stdSelectListElements)) {
	echo "<script type='text/javascript'>var stdSelectListElements = new Array($stdSelectListElements);</script>";
}

/**
 * Instructions (if showing ONLY the forms)
 */
if (($metadata_count > $max_metadata) && (!isset($_GET['showfields']))) {
	print  "<p>{$lang['data_export_tool_42']}</p>
			<p>{$lang['data_export_tool_43']}</p>
			<p>{$lang['data_export_tool_44']} $max_metadata {$lang['data_export_tool_45']}
			<a href=\"".PAGE_FULL."?pid=$project_id&showfields\" style='text-decoration:underline;'>{$lang['data_export_tool_46']}</a>".$lang['period']." 
			{$lang['data_export_tool_47']}</p>";
	
/**
 * Instructions (if showing all the fields)
 */	
} else {
	print  "<p>{$lang['data_export_tool_19']}</p>
			<p>{$lang['data_export_tool_20']} <font color=red>{$lang['data_export_tool_31']}</font>.</p>
			<p>{$lang['data_export_tool_21']}</p>";
}


//If user is in DAG, only show info from that DAG and give note of that
if ($user_rights['group_id'] != "") {
	print  "<p style='color:#800000;'>{$lang['global_02']}: {$lang['data_export_tool_108']}</p>";
}

print "<hr size=1>";

//Display the buttons for mass checking of checkboxes
if (($metadata_count > $max_metadata) && !isset($_GET['showfields'])) {
	//displaying forms 
	print "<div class='normal'>$button_text_select_all $button_text_deselect_all {$lang['data_export_tool_37']}<br><br></div>";
} else {
	//displaying fields
	print "<div class='normal'>$button_text_select_all $button_text_deselect_all {$lang['data_export_tool_28']}<br><br>";
	print "$button_text<br><br>";
	print "</div>";
}



################################################################################
##Rendering - Finishing form Creation
form_renderer($elements1);


?>
<script type="text/javascript">
// STANDARDS MAPPING: functions for toggling options
function updateStdMapSelect() {
	var selectedFlag = false;
	for(var i in stdSelectListElements) {
		if(document.getElementById(stdSelectListElements[i]).selectedIndex > 0) {
			selectedFlag = true;
			break;
		}
	}
	if (selectedFlag) {
		//$('#stdmap_options_box').show();
		$('#stdmap-use-standards').val('true');
		$('#stdmap-data-conversion1').prop('checked',false);
		$('#stdmap-data-conversion').prop('checked',true);
	}else {
		//$('#stdmap_options_box').hide();
		$('#stdmap-use-standards').val('false');
		$('#stdmap-data-conversion').prop('checked',false);
		$('#stdmap-data-conversion1').prop('checked',true);
	}
}
</script>
<?php

include APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
