<?php 
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';


if (isset($_GET['id']) && is_numeric($_GET['id'])) {

	$id = (int)$_GET['id'];

	/* we need to determine if the document is in the file system or the database */
	$sql = "SELECT d.docs_size,d.docs_type,d.export_file,d.docs_name,e.docs_id,m.stored_name,d.docs_file
			FROM redcap_docs d
			LEFT JOIN redcap_docs_to_edocs e ON e.docs_id=d.docs_id
			LEFT JOIN redcap_edocs_metadata m ON m.doc_id = e.doc_id
			WHERE d.docs_id = '".$id."' and d.project_id = '".$project_id."';";
	$result = db_query($sql);
	if ($result) 
	{
		$ddata = db_fetch_object($result);
		if ($ddata->docs_id === NULL) {
			/* there is no reference to metadata, so the data lives in the database */
			// Obtain file attributes
			$data = $ddata->docs_file;
		} else {
			if ($edoc_storage_option) 
			{
				//Download using WebDAV
				include APP_PATH_WEBTOOLS . 'webdav/webdav_connection.php';
				//WebDAV method used only by Vanderbilt because of unresolvable server issues with WebDAV method
				if (SERVER_NAME == "www.mc.vanderbilt.edu" || SERVER_NAME == "staging.mc.vanderbilt.edu") {					
					if (extension_loaded("dav")) {
						try {
							webdav_connect("http://$webdav_hostname:$webdav_port", $webdav_username, $webdav_password); 
							$data = webdav_get($webdav_path . $ddata->stored_name);
							webdav_close();
						} catch ( Exception $e ) {
							$data = $e;
						}
					} else {
						exit($lang['file_download_10']);
					}
				//Default WebDAV method included in REDCap
				} else {	
					// Upload using WebDAV
					$wdc = new WebdavClient();
					$wdc->set_server($webdav_hostname);
					$wdc->set_port($webdav_port); $wdc->set_ssl($webdav_ssl);
					$wdc->set_user($webdav_username);
					$wdc->set_pass($webdav_password);
					$wdc->set_protocol(1); // use HTTP/1.1
					$wdc->set_debug(FALSE); // enable debugging?
					if (!$wdc->open()) {
						$error[] = $lang['control_center_206'];
					}
					$data = NULL;
					$http_status = $wdc->get($webdav_path . $ddata->stored_name, $data); /* passed by reference, so value goes to $data */
					$wdc->close();
				}
			} else {
				/* the data lives in the file system */
				$data = file_get_contents(EDOC_PATH . $ddata->stored_name);
			}
		}

		$size = $ddata->docs_size;
		$type = $ddata->docs_type;
		$export_file = $ddata->export_file;
		$name = $docs_name = $ddata->docs_name;
		
		$name = preg_replace("/[^a-zA-Z-._0-9]/", "_", $name);
		$name = str_replace("__","_",$name);
		$name = str_replace("__","_",$name);
		
		// Set header content-type
		$type = 'application/octet-stream';
		if (strtolower(substr($name, -4)) == ".csv") {
			$type = 'application/csv';
		}
		
		// If exporting R data file as UTF-8 encoded, then remove the BOM (causes issues in R)
		if ($export_file && isset($_GET['exporttype']) && $_GET['exporttype'] == 'R') 
		{
			$data = removeBOMfromUTF8($data);
		}
		// If a SAS syntax file, replace beginning text so that even very old files work with the SAS Pathway Mapper (v4.6.3+)
		elseif ($export_file && strtolower(substr($name, -4)) == '.sas')
		{
			// Find the position of "infile '" and cut off all text occurring before it
			$pos = strpos($data, "infile '");
			if ($pos !== false) {
				// Now splice the file back together using the new string that occurs on first line (which will work with Pathway Mapper)
				$prefix = "%macro removeOldFile(bye); %if %sysfunc(exist(&bye.)) %then %do; proc delete data=&bye.; run; %end; %mend removeOldFile; %removeOldFile(work.redcap); data REDCAP; %let _EFIERR_ = 0;\n";
				$data = $prefix . substr($data, $pos);
			}
		}
		
		// Output headers
		header('Pragma: anytextexeptno-cache', true);
		header("Content-type: $type");		
		header("Content-Disposition: attachment; filename=$name");
		
		//File encoding will vary by language module
		if ($project_language == 'Japanese' && function_exists('mb_detect_encoding') && function_exists('mb_convert_encoding') 
			&& mb_detect_encoding($data) == "UTF-8") {
			print mb_convert_encoding($data, "SJIS", "UTF-8");
		} else {	
			print $data;
		}
		
		## Logging	
		// Default logging description
		$descr = "Download file from file repository";
		// Determine type of file
		$file_extension = strtolower(substr($docs_name,strrpos($docs_name,".")+1,strlen($docs_name)));
		if ($export_file) 
		{
			switch ($file_extension) {
				case "r":
					$descr = "Download exported syntax file (R)";					
					break;
				case "do":
					$descr = "Download exported syntax file (Stata)";	
					break;
				case "sas":
					$descr = "Download exported syntax file (SAS)";	
					break;
				case "sps":
					$descr = "Download exported syntax file (SPSS)";	
					break;
				case "csv":
					$descr = (substr($name, 0, 12) == "DATA_LABELS_") ? "Download exported data file (CSV labels)" : "Download exported data file (CSV raw)";
					break;
			}
		}
		// Log it
		log_event($sql,"redcap_docs","MANAGE",$id,"docs_id = $id",$descr);
		
	}
}
