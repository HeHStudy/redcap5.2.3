<?php 
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require dirname(dirname(__FILE__)) . "/Config/init_global.php";

// Initialize page display object
$objHtmlPage = new HtmlPage();
$objHtmlPage->addExternalJS(APP_PATH_JS . "base.js");
$objHtmlPage->PrintHeaderExt();

# check if the API is enabled first
if (!$api_enabled) {
	print RCView::disabledAPINote();
	exit;
}

?>

<script type="text/javascript" src="<?php echo dirname(dirname(__FILE__)) ?>/resources/js/base.js"></script>
<script type="text/javascript">
jQuery(document).ready(function() {
	//toggle the component with class msg_body
	jQuery(".heading").click(function()
	{
		var img = (jQuery(this).next(".content").css("display") == "none") ? "minus.png" : "plus.png";
		jQuery(this).css("background","#eee url(<?php echo APP_PATH_IMAGES ?>"+img+") no-repeat left center");
		jQuery(this).next(".content").slideToggle(500);
	});
});
</script>

<style>
h2 {
	font-size: 17px;
	margin: 0; 
	padding: 5px 5px 5px 25px;
	font-weight: normal;
}
div.content h3 {
	-moz-border-radius:15px 15px 15px 15px;
	background:none repeat scroll 0 0 #E7F3F8;
	clear:both;
	color:#447AA4;
	display:block;
	font-size:15px;
	margin-top:12px;
	padding:5px 10px;
	text-shadow:0 1px #FFFFFF;
}
div.section h3 {
	margin-left: 15px;
}
div.content p, div.content pre {
	padding-left: 20px;
}
div.content ul {
	list-style-type: disc;
	margin:10px 0 10px 20px;
	line-height:20px;
}
div.content li ul {
	margin: 0;
}
div.subitem {
	margin-left: 15px;
}
div.section div {
	padding-left: 20px;
}
.heading {
	background: #eee url('<?php echo APP_PATH_IMAGES ?>plus.png') no-repeat left center;
	position: relative;
	cursor: pointer;
	width: 716px;
}
.cat {
	background: #ccc; 
	position: relative;
	font-size: 17px;
	margin: 0; 
	padding: 5px 5px 5px 10px;
	border:1px solid #555;
	font-weight: bold;
	width: 700px;
}
.content {
	background: #fafafa; 
	border:1px solid #ccc; 
	padding:0 10px 10px;
	display:none;
	width: 694px;
}
</style>

<div style="padding:0px 10px; font-family:Arial; font-size:13px; width: 800px;">
	<h1 style="margin:20px 0 0px;font-size:24px;color:#800000">REDCap API Help Page</h1>
	
	<p style="padding-bottom:10px;">
		<a style="text-decoration:underline;" href="<?php echo APP_PATH_WEBROOT_PARENT ?>">Return to REDCap</a> &nbsp;|&nbsp;
		<a style="text-decoration:underline;" href="<?php echo str_replace("?", "", $_SERVER['PHP_SELF']) ?>?logout=1">Logout</a>
	</p>

	<p style="padding-bottom:10px;">
		This page may be used for obtaining information for constructing or modifying REDCap API requests. Click any of the
		categories in the table below to expand its section.<br><br>
		<b>What is an API?</b><br>
		The acronym "API" stands for "Application Programming Interface". An API is just a defined way for a program to accomplish a task, 
		usually retrieving or modifying data. In REDCap's case, we provide an API method for both exporting and importing data in and out
		of REDCap (more functionality will come in the future). 
		Once we expand the REDCap API's abilities to a more comprehensive feature set in the future, programmers may then use the REDCap API to 
		make applications, websites, widgets, and other projects that interact with REDCap. 
		Programs talk to the REDCap API over HTTP, the same protocol that your browser uses to visit and interact with web pages.
	</p>
	
	<div class="cat">
		Basic API Info:
	</div>
	
	<div class="heading">
		<h2>Obtaining Tokens for API Requests</h2>
	</div>
	<div class="content">
		<p>
		In order to use the REDCap API for a given REDCap project, you must first be given a token that is specific to your username for that
		particular project. Rather than using username/passwords, the REDCap API uses tokens as a means of secure authentication, in which a token
		must be included in every API request. 
		Please note that each user will have a different token for each REDCap project to which they have access.
		Thus, multiple tokens will be required for making API requests to multiple projects.
		<br><br>
		To obtain an API token for a project, navigate to that project, then click the API link in the Applications sidebar.
		On that page you will be able to request an API token for the project from your REDCap administrator, and that page
		will also display your API token if one has already been assigned. If you do not see a link for the API page on your
		project's left-hand menu, then someone must first give you API privileges within the project (via the project's 
		User Rights page).
		</p>
	</div>
	
	<div class="heading">
		<h2>Error Codes & Responses</h2>
	</div>
	<div id="responseCodesBox" class="content">
		<h3>HTTP Status Codes:</h3>
		<p>The REDCap API attempts to return appropriate <a href="http://en.wikipedia.org/wiki/List_of_HTTP_status_codes">HTTP status codes</a> for every request.</p>
		<ul>
			<li><strong>200 OK:</strong> Success!</li>
			<li><strong>400 Bad Request:</strong> The request was invalid. An accompanying message will explain why.</li>
			<li><strong>401 Unauthorized:</strong> API token was missing or incorrect.</li>
			<li><strong>403 Forbidden:</strong> You do not have permissions to use the API.</li>
			<li><strong>404 Not Found:</strong> The URI you requested is invalid or the resource does not exist.</li>
			<li><strong>406 Not Acceptable:</strong> The data being imported was formatted incorrectly.</li>
			<li><strong>500 Internal Server Error:</strong> The server encountered an error processing your request.</li>
			<li><strong>501 Not Implemented:</strong> The requested method is not implemented.</li>
		</ul>

		<h3>Error Messages:</h3>
		<p>When the API returns error messages, it does so in your requested format. You can specify the format you
		want using the <b>returnFormat</b> parameter. For example, an error from an XML method might look like this:</p>
<pre>
&lt;?xml version="1.0" encoding="UTF-8" ?&gt;
&lt;hash&gt;
   &lt;error&gt;detailed error message&lt;/error&gt;
&lt;/hash&gt;
</pre>
	</div>
	
	<div class="heading">
		<h2>API Examples</h2>
	</div>
	<div id="examplesBox" class="content">
		<p>
			The REDCap API can be called from a variety of clients using any popular client-side or web development language
			that you are able to implement (e.g .NET, Python, PHP, Java). Below you may download a ZIP file containing
			several examples of how to call the API using various software languages. The files contained therein
			may be modified however you wish.<br><br>
			<b>NOTE: The files included in the ZIP file below are *not* officially sanctioned REDCap files</b> but are merely 
			examples of how one might make API requests using specific software languages. Please be aware that the 
			files in the ZIP could potentially change from one REDCap version to the next.<br><br>
			<button class="jqbuttonmed" onclick="window.location.href='<?php echo APP_PATH_WEBROOT ?>API/redcap_api_examples.zip';">Download API examples (.zip)</button>
		</p>
	</div>
	
	<div class="heading">
		<h2>Unique Event Names</h2>
	</div>
	<div id="eventNamesBox" class="content">
		<p>
			Event names are frequently used in API calls to longitudinal projects. To obtain a list of the event
			names available to a given project, navigate to the project, then click the API link in the Applications sidebar.
		</p>
	</div>
	
	<br/>
	
	<div class="cat">
		Supported Actions:
	</div>
	
	<div class="heading">
		<h2>Export Records</h2>
	</div>
	<div id="recordExportBox" class="content">
		<h3>Description</h3>
		<p>This function allows you to export a set of records for a project</p>
		<p style="color:#555;">NOTE: While this *does* work for Parent/Child projects, please note that it will export the 
		Parent's records or the Child's records separately rather than together. So if accessing the Parent via API, it will only
		return the Parent's records, and if accessing the Child via API, it will only return the Child's records.</p>

		<h3>URL</h3>
		<p><strong><?php echo APP_PATH_WEBROOT_FULL ?>api/</strong></p>
		
		<h3>Supported Request Methods</h3>
		<p><strong>POST</strong></p>
		
		<h3>Parameters (case sensitive)</h3>
		<div class="section">
			<h3>Required</h3>
			<ul>
				<li>
					<strong>token</strong><br/>
					<div>the API token specific to your REDCap project and username (each token is unique to each user for each project)
					- See the section above for obtaining a token for a given project</div>
				</li>
				<li><strong>content</strong><br/><div>record</div>
				</li>
				<li><strong>format</strong><br/><div>csv, json, xml [default]</div></li>
				<li><strong>type:</strong>
					<ul>
						<li style="margin-left:30px;">flat - output as one record per row [default]</li>
						<li style="margin-left:30px;">eav - output as one data point per row
							<ul>
								<li style="margin-left:30px;">Non-longitudinal: Will have the fields - record*, field_name, value</li>
								<li style="margin-left:30px;">Longitudinal: Will have the fields - record*, field_name, value, redcap_event_name</li>
							</ul>
						</li>
					</ul>
					<p>* Record refers to the study id or whatever the primary key is for the project</p>
				</li>
			</ul>
			<h3>Optional</h3>
			<ul>
				<li><strong>records</strong><br/> <div>an array of record names specifying specific records you wish to pull (by default, all records are pulled)</div></li>
				<li><strong>fields</strong><br/> <div>an array of field names specifying specific fields you wish to pull (by default, all fields are pulled)</div></li>
				<li><strong>forms</strong><br/> <div>an array of form names you wish to pull records for.  If the form name has a space in 
				it, replace the space with an underscore (by default, all records are pulled)</div></li>
				<li><strong>events</strong><br/> <div>an array of unique event names that you wish to pull records for - only for longitudinal projects</div></li>
				<li><strong>rawOrLabel</strong><br/> <div>raw [default], label, both - export the raw coded values or labels for the options of multiple choice fields</div></li>
				<li><strong>eventName</strong><br/> <div>unique, label - export the unique event name or the event label.  
					If you do not pass in this flag, it will select the default value for you passed based on the "rawOrLabel" 
					flag you passed in, or if no "rawOrLabel" flag was passed in, it will default to "unique".</div></li>
				<li><strong>returnFormat</strong><br/> <div>csv, json, xml - specifies the format of error messages.
				If you do not pass in this flag, it will select the default format for you passed based on the
				"format" flag you passed in or if no format flag was passed in, it will default to "xml".</div></li>
				<li><strong>exportSurveyFields</strong><br/> <div>true, false - specifies whether or not to export the survey identifier
				field (e.g. "redcap_survey_identifier") or survey timestamp fields (e.g. form_name+"_timestamp") when surveys 
				are utilized in the project. If you do not pass in this flag, it will default to "false". If set to "true",
				it will return the redcap_survey_identifier field and also the survey timestamp field for a particular survey 
				when at least one field from that survey is being exported. NOTE: If the survey identifier
				field or survey timestamp fields are imported via API data import, they will simply be ignored since they are not
				real fields in the project but rather are pseudo-fields.</div></li>
				<li><strong>exportDataAccessGroups</strong><br/> <div>true, false - specifies whether or not to export the "redcap_data_access_group"
				field when data access groups are utilized in the project. If you do not pass in this flag, it will default to "false".
				NOTE: This flag is only viable if the user whose token is being used to make the API request is *not* in a 
				data access group. If the user is in a group, then this flag will revert to its default value.</div></li>
			</ul>
		</div>
		
		<h3>Returns:</h3>
		<p>Data from the project in the format and type specified ordered by the record (primary key of project) and then by event id</p>
		
<pre>
EAV XML:
&lt;?xml version="1.0" encoding="UTF-8" ?&gt;
&lt;records&gt;
   &lt;item&gt;
      &lt;record&gt;&lt;/record&gt;
      &lt;field_name&gt;&lt;/field_name&gt;
      &lt;value&gt;&lt;/value&gt;
      &lt;redcap_event_name&gt;&lt;/redcap_event_name&gt;
   &lt;/item&gt;
&lt;/records&gt;

Flat XML:
&lt;?xml version="1.0" encoding="UTF-8" ?&gt;
&lt;records&gt;
   &lt;item&gt;
      each data point as an element
      ...
   &lt;/item&gt;
&lt;/records&gt;
</pre>
	</div>
	
	<div class="heading">
		<h2>Import Records</h2>
	</div>
	<div id="recordImportBox" class="content">
		<h3>Description</h3>
		<p>This function allows you to import a set of records for a project</p>
		<p style="color:#555;">NOTE: While this *does* work for Parent/Child projects, please note that it will import the records
		only to the specific project you are accessing via the API (i.e. the Parent or the Child project) and not to both.
		Additionally, if importing new records into a Child project, those records must also already exist in the Parent project, or
		else the API will return an error.</p>

		<h3>URL</h3>
		<p><strong><?php echo APP_PATH_WEBROOT_FULL ?>api/</strong></p>
		
		<h3>Supported Request Methods</h3>
		<p><strong>POST</strong></p>
		
		<h3>Parameters (case sensitive)</h3>
		<div class="section">
			<h3>Required</h3>
			<ul>
				<li>
					<strong>token</strong><br/>
					<div>the API token specific to your REDCap project and username (each token is unique to each user for each project)
					- See the section above for obtaining a token for a given project</div>
				</li>
				<li><strong>content</strong><br/><div>record</div>
				</li>
				<li><strong>format</strong><br/><div>csv, json, xml [default]</div></li>
				<li><strong>type</strong>
					<ul>
						<li style="margin-left:30px;">flat - input as one record per row [default]</li>
						<li style="margin-left:30px;">eav - input as one data point per row
							<ul>
								<li style="margin-left:30px;">Non-longitudinal: Must have the fields - record*, field_name, value</li>
								<li style="margin-left:30px;">Longitudinal: Must have the fields - record*, field_name, value, redcap_event_name**</li>
							</ul>
						</li>
					</ul>
					<div>
					<br/>* Record refers to the study id or whatever the primary key is for the project<br/>
					** Event name is the unique name for an event, not the event label
					</div>
				</li>
                <li><strong>overwriteBehavior</strong>
                    <ul>
                        <li style="margin-left:30px;">normal - blank/empty values will be ignored [default]</li>
                        <li style="margin-left:30px;">overwrite - blank/empty values are valid and will overwrite data</li>
                    </ul>
				<li><strong>data</strong><br/> <div>the formatted data to be imported</div>

<pre>
EAV XML:
&lt;?xml version="1.0" encoding="UTF-8" ?&gt;
&lt;records&gt;
   &lt;item&gt;
      &lt;record&gt;&lt;/record&gt;
      &lt;field_name&gt;&lt;/field_name&gt;
      &lt;value&gt;&lt;/value&gt;
      &lt;redcap_event_name&gt;&lt;/redcap_event_name&gt;
   &lt;/item&gt;
&lt;/records&gt;

Flat XML:
&lt;?xml version="1.0" encoding="UTF-8" ?&gt;
&lt;records&gt;
   &lt;item&gt;
      each data point as an element
      ...
   &lt;/item&gt;
&lt;/records&gt;
</pre></li>
			</ul>
			<h3>Optional</h3>
			<ul>
				<li><strong>returnContent</strong><br/> <div>ids - a list of all study IDs that were imported, count [default] - the number of records imported, nothing - no text, just the HTTP status code</div></li>
				<li><strong>returnFormat</strong><br/> <div>csv, json, xml - specifies the format of returned content or error messages.
				If you do not pass in this flag, it will select the default format for you passed based on the
				"format" flag you passed in or if no format flag was passed in, it will default to "xml".</div></li>
			</ul>
		</div>
		
		<h3>Returns:</h3>
		<p>the content specified by <b>returnContent</b></p>
	</div>
	
	<div class="heading">
		<h2>Export Metadata (i.e. Data Dictionary)</h2>
	</div>
	<div id="metadataExportBox" class="content">
		<h3>Description</h3>
		<p>This function allows you to export the metadata for a project</p>
		<p style="color:#555;">NOTE: While this *does* work for Parent/Child projects, please note that it will export the 
		Parent's metadata or the Child's metadata separately rather than together. So if accessing the Parent via API, it will only
		return the Parent's metadata, and if accessing the Child via API, it will only return the Child's metadata.</p>

		<h3>URL</h3>
		<p><strong><?php echo APP_PATH_WEBROOT_FULL ?>api/</strong></p>
		
		<h3>Supported Request Methods</h3>
		<p><strong>POST</strong></p>
		
		<h3>Parameters (case sensitive)</h3>
		<div class="section">
			<h3>Required</h3>
			<ul>
				<li>
					<strong>token</strong><br/>
					<div>the API token specific to your REDCap project and username (each token is unique to each user for each project)
					- See the section above for obtaining a token for a given project</div>
				</li>
				<li><strong>content</strong><br/><div>metadata</div>
				</li>
				<li><strong>format</strong><br/><div>csv, json, xml [default]</div></li>
			</ul>
			<h3>Optional</h3>
			<ul>
				<li><strong>fields</strong><br/> <div>an array of field names specifying specific fields you wish to pull (by default, all metadata is pulled)</div></li>
				<li><strong>forms</strong><br/> <div>
					an array of form names specifying specific data collection instruments for which you wish 
					to pull metadata (by default, all metadata is pulled). NOTE: These "forms" are not the form label values that are seen on the webpages, 
					but instead they are the unique form names seen in Column B of the data dictionary.
				</div></li>
				<li><strong>returnFormat</strong><br/> <div>csv, json, xml - specifies the format of error messages.
				If you do not pass in this flag, it will select the default format for you passed based on the
				"format" flag you passed in or if no format flag was passed in, it will default to "xml".</div></li>
			</ul>
		</div>
		
		<h3>Returns:</h3>
		<p>Metadata from the project (i.e. Data Dictionary values) in the format specified ordered by the field order</p>
	</div>
	
	<div class="heading">
		<h2>Export a File</h2>
	</div>
	<div id="fileExportBox" class="content">
		<h3>Description</h3>
		<p>This function allows you to download a document that has been attached to an individual record</p>

		<h3>URL</h3>
		<p><strong><?php echo APP_PATH_WEBROOT_FULL ?>api/</strong></p>
		
		<h3>Supported Request Methods</h3>
		<p><strong>POST</strong></p>
		
		<h3>Parameters (case sensitive)</h3>
		<div class="section">
			<h3>Required</h3>
			<ul>
				<li>
					<strong>token</strong><br/>
					<div>the API token specific to your REDCap project and username (each token is unique to each user for each project)
					- See the section above for obtaining a token for a given project</div>
				</li>
				<li><strong>content</strong><br/><div>file</div></li>
				<li><strong>action</strong><br/><div>export</div></li>
				<li><strong>record</strong><br/><div>the subject ID</div></li>
				<li><strong>field</strong><br/><div>the name of the field that contains the file</div></li>
				<li><strong>event</strong><br/><div>the unique event name - only for longitudinal projects</div></li>
			</ul>
			<h3>Optional</h3>
			<ul>
				<li><strong>returnFormat</strong><br/> <div>csv, json, xml - specifies the format of error messages.
				If you do not pass in this flag, it will select the default format for you passed based on the
				"format" flag you passed in or if no format flag was passed in, it will default to "xml".</div></li>
			</ul>
		</div>
		
		<h3>Returns:</h3>
		<p>the contents of the file</p>
		<p>
			<strong>How to obtain the filename of the file:</strong><br/>
			The MIME type of the file, along with the name of the file and its extension, can be found in the header of
			the returned response. Thus in order to determine these attributes of the file being exported, you will need to 
			parse the response header. Example: <br/>
			content-type = application/vnd.openxmlformats-officedocument.wordprocessingml.document; name="FILE_NAME.docx"
		</p>
	</div>
	
	<div class="heading">
		<h2>Import a File</h2>
	</div>
	<div id="fileImportBox" class="content">
		<h3>Description</h3>
		<p>This function allows you to upload a document that will be attached to an individual record</p>

		<h3>URL</h3>
		<p><strong><?php echo APP_PATH_WEBROOT_FULL ?>api/</strong></p>
		
		<h3>Supported Request Methods</h3>
		<p><strong>POST</strong></p>
		
		<h3>Parameters (case sensitive)</h3>
		<div class="section">
			<h3>Required</h3>
			<ul>
				<li>
					<strong>token</strong><br/>
					<div>the API token specific to your REDCap project and username (each token is unique to each user for each project)
					- See the section above for obtaining a token for a given project</div>
				</li>
				<li><strong>content</strong><br/><div>file</div></li>
				<li><strong>action</strong><br/><div>import</div></li>
				<li><strong>record</strong><br/><div>the subject ID</div></li>
				<li><strong>field</strong><br/><div>the name of the field that contains the file</div></li>
				<li><strong>event</strong><br/><div>the unique event name - only for longitudinal projects</div></li>
				<li><strong>file</strong><br/><div>the contents of the file</div></li>
			</ul>
			<h3>Optional</h3>
			<ul>
				<li><strong>returnFormat</strong><br/> <div>csv, json, xml - specifies the format of error messages.
				If you do not pass in this flag, it will select the default format for you passed based on the
				"format" flag you passed in or if no format flag was passed in, it will default to "xml".</div></li>
			</ul>
		</div>
	</div>
	
	<div class="heading">
		<h2>Delete a File</h2>
	</div>
	<div id="fileDeleteBox" class="content">
		<h3>Description</h3>
		<p>This function allows you to remove a document that has been attached to an individual record</p>

		<h3>URL</h3>
		<p><strong><?php echo APP_PATH_WEBROOT_FULL ?>api/</strong></p>
		
		<h3>Supported Request Methods</h3>
		<p><strong>POST</strong></p>
		
		<h3>Parameters (case sensitive)</h3>
		<div class="section">
			<h3>Required</h3>
			<ul>
				<li>
					<strong>token</strong><br/>
					<div>the API token specific to your REDCap project and username (each token is unique to each user for each project)
					- See the section above for obtaining a token for a given project</div>
				</li>
				<li><strong>content</strong><br/><div>file</div></li>
				<li><strong>action</strong><br/><div>delete</div></li>
				<li><strong>record</strong><br/><div>the subject ID</div></li>
				<li><strong>field</strong><br/><div>the name of the field that contains the file</div></li>
				<li><strong>event</strong><br/><div>the unique event name - only for longitudinal projects</div></li>
			</ul>
			<h3>Optional</h3>
			<ul>
				<li><strong>returnFormat</strong><br/> <div>csv, json, xml - specifies the format of error messages.
				If you do not pass in this flag, it will select the default format for you passed based on the
				"format" flag you passed in or if no format flag was passed in, it will default to "xml".</div></li>
			</ul>
		</div>
	</div>

	<div class="heading">
		<h2>Export Events</h2>
	</div>
	<div id="eventExportBox" class="content">
		<h3>Description</h3>
		<p>This function allows you to export the events for a project</p>
		<p>NOTE: this only works for longitudinal projects</p>

		<h3>URL</h3>
		<p><strong><?php echo APP_PATH_WEBROOT_FULL ?>api/</strong></p>

		<h3>Supported Request Methods</h3>
		<p><strong>POST</strong></p>

		<h3>Parameters (case sensitive)</h3>
		<div class="section">
			<h3>Required</h3>
			<ul>
				<li>
					<strong>token</strong><br/>
					<div>the API token specific to your REDCap project and username (each token is unique to each user for each project)
					- See the section above for obtaining a token for a given project</div>
				</li>
				<li><strong>content</strong><br/><div>event</div>
				</li>
				<li><strong>format</strong><br/><div>csv, json, xml [default]</div></li>
			</ul>
			<h3>Optional</h3>
			<ul>
				<li><strong>arms</strong><br/> <div>an array of arm numbers that you wish to pull events for (by default, all events are pulled)</div></li>
				<li><strong>returnFormat</strong><br/> <div>csv, json, xml - specifies the format of error messages.
				If you do not pass in this flag, it will select the default format for you passed based on the
				"format" flag you passed in or if no format flag was passed in, it will default to "xml".</div></li>
			</ul>
		</div>

		<h3>Returns:</h3>
		<p>Events for the project in the format specified</p>
	</div>

	<div class="heading">
		<h2>Export Arms</h2>
	</div>
	<div id="armExportBox" class="content">
		<h3>Description</h3>
		<p>This function allows you to export the Arms for a project</p>
		<p>NOTE: this only works for longitudinal projects</p>

		<h3>URL</h3>
		<p><strong><?php echo APP_PATH_WEBROOT_FULL ?>api/</strong></p>

		<h3>Supported Request Methods</h3>
		<p><strong>POST</strong></p>

		<h3>Parameters (case sensitive)</h3>
		<div class="section">
			<h3>Required</h3>
			<ul>
				<li>
					<strong>token</strong><br/>
					<div>the API token specific to your REDCap project and username (each token is unique to each user for each project)
					- See the section above for obtaining a token for a given project</div>
				</li>
				<li><strong>content</strong><br/><div>arm</div>
				</li>
				<li><strong>format</strong><br/><div>csv, json, xml [default]</div></li>
			</ul>
			<h3>Optional</h3>
			<ul>
				<li><strong>arms</strong><br/> <div>an array of arm numbers that you wish to pull events for (by default, all events are pulled)</div></li>
				<li><strong>returnFormat</strong><br/> <div>csv, json, xml - specifies the format of error messages.
				If you do not pass in this flag, it will select the default format for you passed based on the
				"format" flag you passed in or if no format flag was passed in, it will default to "xml".</div></li>
			</ul>
		</div>

		<h3>Returns:</h3>
		<p>Arms for the project in the format specified</p>
	</div>

	<div class="heading">
		<h2>Export Form-Event Mappings</h2>
	</div>
	<div id="formEventExportBox" class="content">
		<h3>Description</h3>
		<p>This function allows you to export the form-event mappings for a project</p>
		<p>NOTE: this only works for longitudinal projects</p>

		<h3>URL</h3>
		<p><strong><?php echo APP_PATH_WEBROOT_FULL ?>api/</strong></p>

		<h3>Supported Request Methods</h3>
		<p><strong>POST</strong></p>

		<h3>Parameters (case sensitive)</h3>
		<div class="section">
			<h3>Required</h3>
			<ul>
				<li>
					<strong>token</strong><br/>
					<div>the API token specific to your REDCap project and username (each token is unique to each user for each project)
					- See the section above for obtaining a token for a given project</div>
				</li>
				<li><strong>content</strong><br/><div>formEventMapping</div>
				</li>
				<li><strong>format</strong><br/><div>csv, json, xml [default]</div></li>
			</ul>
			<h3>Optional</h3>
			<ul>
				<li><strong>arms</strong><br/> <div>an array of arm numbers that you wish to pull events for (by default, all events are pulled)</div></li>
				<li><strong>returnFormat</strong><br/> <div>csv, json, xml - specifies the format of error messages.
				If you do not pass in this flag, it will select the default format for you passed based on the
				"format" flag you passed in or if no format flag was passed in, it will default to "xml".</div></li>
			</ul>
		</div>

		<h3>Returns:</h3>
		<p>Form-Event mappings for the project in the format specified</p>
	</div>

	<div class="heading">
		<h2>Export Users</h2>
	</div>
	<div id="userExportBox" class="content">
		<h3>Description</h3>
		<p>This function allows you to export the users for a project</p>

		<h3>URL</h3>
		<p><strong><?php echo APP_PATH_WEBROOT_FULL ?>api/</strong></p>

		<h3>Supported Request Methods</h3>
		<p><strong>POST</strong></p>

		<h3>Parameters (case sensitive)</h3>
		<div class="section">
			<h3>Required</h3>
			<ul>
				<li>
					<strong>token</strong><br/>
					<div>the API token specific to your REDCap project and username (each token is unique to each user for each project)
					- See the section above for obtaining a token for a given project</div>
				</li>
				<li><strong>content</strong><br/><div>user</div>
				</li>
				<li><strong>format</strong><br/><div>csv, json, xml [default]</div></li>
			</ul>
			<h3>Optional</h3>
			<ul>
				<li><strong>returnFormat</strong><br/> <div>csv, json, xml - specifies the format of error messages.
				If you do not pass in this flag, it will select the default format for you passed based on the
				"format" flag you passed in or if no format flag was passed in, it will default to "xml".</div></li>
			</ul>
		</div>

		<h3>Returns:</h3>
		<p>User information for the project in the format specified</p>
		<p>
			Data Export: 0=no access, 2=De-Identified, 1=Full Data Set<br/>
			Form Rights: 0=no access, 2=read only, 1=view records/responses and edit records (survey responses are read-only),
				3 = edit survey responses
		</p>
		<p>
			(NOTE: At this time, only a limited amount of rights-related info will be exported (expiration, data access group ID, data export rights, and form-level rights.
			However, more info about a user's rights will eventually be added to the Export Users API functionality in future versions of REDCap.)
		</p>
	</div>
</div>

<?php

$objHtmlPage->PrintFooterExt();
