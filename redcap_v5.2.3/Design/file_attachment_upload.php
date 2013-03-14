<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

// Only accept Post submission
if ($_SERVER['REQUEST_METHOD'] != 'POST') exit;

// Call config file
require_once dirname(dirname(__FILE__)) . '/Config/init_project.php';



$doc_name = str_replace("'", "", html_entity_decode(stripslashes($_FILES['myfile']['name']), ENT_QUOTES));
$doc_size = $_FILES['myfile']['size'];

// Upload the file and return the doc_id from the edocs table
$doc_id = uploadFile($_FILES['myfile']);

	
// Check if file is larger than max file upload limit
if ($doc_id < 1 || ($doc_size/1024/1024) > maxUploadSizeEdoc() || $_FILES['file']['error'] != UPLOAD_ERR_OK) 
{
	// Delete temp file
	unlink($_FILES['myfile']['tmp_name']);
	// Set error message
	$msg = "ERROR: CANNOT UPLOAD FILE!";
	if ($doc_id > 0) {
		// If file was too large
		$msg .= "\\n\\nThe uploaded file is ".round_up($doc_size/1024/1024)." MB in size, thus exceeding the maximum file size limit of ".maxUploadSize()." MB.";
	}
	// Give error response
	?>
	<script language="javascript" type="text/javascript">
	window.top.window.document.getElementById('div_attach_doc_in_progress').style.display = 'none';
	window.top.window.document.getElementById('div_attach_doc_fail').style.display = 'block';
	window.top.window.alert('<?php echo $msg ?>');
	</script>
	<?php
	exit;
}

// Do logging of file upload
log_event("","redcap_edocs_metadata","doc_upload",$doc_id,"doc_id = $doc_id","Upload document for image/file attachment field");

// Give response using javascript
?>
<script language="javascript" type="text/javascript">
window.top.window.document.getElementById('edoc_id').value = '<?php echo $doc_id ?>';
window.top.window.document.getElementById('div_attach_doc_in_progress').style.display = 'none';
window.top.window.document.getElementById('div_attach_doc_success').style.display = 'block';
var filename = window.top.window.document.getElementById('attach_download_link').innerHTML = '<?php echo cleanHtml(str_replace("'", "", $_FILES['myfile']['name'])) ?>';
window.top.window.document.getElementById('div_attach_upload_link').style.display = 'none';
window.top.window.document.getElementById('div_attach_download_link').style.display = 'block';
window.top.window.checkAttachImg(filename);
</script>
