<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';

$library_id = $_POST['library_id'];
$list = $_POST['imageList'];
if (!is_numeric($library_id)) exit;


try {
	
	// Chop off first doc_id in the list of doc_id's
	$listArray = explode(",", $list, 2);

	// Upload the first image id in the list
	if (count($listArray) > 0 && is_numeric($listArray[0])) 
	{
		//send the image to the library
		$sql = "select stored_name from redcap_edocs_metadata where doc_id = " . $listArray[0];
		$qry = db_query($sql);
		if ($row = db_fetch_assoc($qry)) 
		{
			// Retrieve the contents of the attachment
			$contents = getEdocContents($row['stored_name']);			
			if ($contents !== false && $contents != '') 
			{
				$params = array(
					'imgData'=> $contents,
					'imgName'=> $row['stored_name'],
					'library_id'=>$library_id
				);
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
				curl_setopt($curl, CURLOPT_VERBOSE, 1);
				curl_setopt($curl, CURLOPT_URL, SHARED_LIB_UPLOAD_ATTACH_URL);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_TIMEOUT, 1000);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
				curl_setopt($curl, CURLOPT_PROXY, PROXY_HOSTNAME); // If using a proxy
				$response = curl_exec($curl);
				error_log('$response for file '.$row['stored_name'].' is '.$response);
				curl_close($curl);
			}
			else 
			{
				error_log('error uploading file '.$row['stored_name'].' - file has no content');
			}
		}
	}

	// Relaunch image loader iteratively for any remaining images
	if (count($listArray) > 1) 
	{
		$list = $listArray[1];
		
		$params = array('library_id'=>$library_id, 'imageList'=>$list);
		
		$imgCurl = curl_init();
		curl_setopt($imgCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($imgCurl, CURLOPT_VERBOSE, 1);
		curl_setopt($imgCurl, CURLOPT_URL, APP_PATH_WEBROOT_FULL . "redcap_v{$redcap_version}/SharedLibrary/image_loader.php");
		curl_setopt($imgCurl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($imgCurl, CURLOPT_POST, true);
		curl_setopt($imgCurl, CURLOPT_TIMEOUT, 1000);
		curl_setopt($imgCurl, CURLOPT_POSTFIELDS, $params);
		curl_setopt($imgCurl, CURLOPT_PROXY, PROXY_HOSTNAME); // If using a proxy
		$response = curl_exec($imgCurl);
		error_log($response);
		curl_close($imgCurl);
	}

} 
catch (Exception $e) 
{
   error_log("error uploading file: ".$e->getMessage());
}
