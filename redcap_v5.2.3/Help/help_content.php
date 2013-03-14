<div class="wikipage searchable">
        
          <h1><font color="#600000">REDCap Help &amp; FAQ</font></h1>
<ul>
<li><a href="#general" style="font-family:Verdana">General</a></li>
<li style="margin-left:30px"><a href="#mobile" style="font-family:Verdana">Mobile Devices</a></li>
<li style="margin-left:30px"><a href="#language" style="font-family:Verdana">Language Modules</a></li>
<li><a href="#projectsetup" style="font-family:Verdana">Project Setup / Design</a></li>
<li style="margin-left:30px"><a href="#surveys" style="font-family:Verdana">Surveys</a></li>
<li style="margin-left:30px"><a href="#longitudinal" style="font-family:Verdana">Longitudinal</a></li>
<li style="margin-left:30px"><a href="#projectlink" style="font-family:Verdana">Project Linking</a></li>
<li style="margin-left:30px"><a href="#copyproject" style="font-family:Verdana">Copy A Project</a></li>
<li><a href="#dci" style="font-family:Verdana">Data Collect Instrument (DCI) Design</a></li>
<li style="margin-left:30px"><a href="#onlinedesign" style="font-family:Verdana">Online Designer / Data Dictionary</a></li>
<li style="margin-left:30px"><a href="#fieldtypes" style="font-family:Verdana">Field Types</a></li>
<li style="margin-left:60px"><a href="#txtval" style="font-family:Verdana">Text Validation Types</a></li>
<li style="margin-left:60px"><a href="#dates" style="font-family:Verdana">Dates</a></li>
<li style="margin-left:60px"><a href="#CalculatedFields" style="font-family:Verdana">Calculated Fields</a></li>
<li style="margin-left:60px"><a href="#BranchingLogic" style="font-family:Verdana">Branching Logic</a></li>
<li style="margin-left:30px"><a href="#matrix" style="font-family:Verdana">Matrix Fields</a></li>
<li style="margin-left:30px"><a href="#copydci" style="font-family:Verdana">Copy / Share DCIs</a></li>
<li><a href="#dataentry" style="font-family:Verdana">Data Entry / Collection</a></li>
<li style="margin-left:30px"><a href="#survey" style="font-family:Verdana">Surveys: Invite Participants</a></li>
<li style="margin-left:60px"><a href="#AutomatedInvitations" style="font-family:Verdana">Automated Survey Invitations</a></li>
<li style="margin-left:30px"><a href="#survey_prefill" style="font-family:Verdana">Surveys: How to pre-fill survey questions</a></li>
<li style="margin-left:30px"><a href="#doubledataentry" style="font-family:Verdana">Double Data Entry</a></li>
<li><a href="#applications" style="font-family:Verdana">Applications</a></li>
<li style="margin-left:30px"><a href="#export" style="font-family:Verdana">Data Export Tool</a></li>
<li style="margin-left:30px"><a href="#import" style="font-family:Verdana">Data Import Tool / API</a></li>
<li style="margin-left:30px"><a href="#file" style="font-family:Verdana">File Repository</a></li>
<li style="margin-left:30px"><a href="#userrights" style="font-family:Verdana">User Rights</a></li>
<li style="margin-left:30px"><a href="#dag" style="font-family:Verdana">Data Access Groups</a></li>
<li style="margin-left:30px"><a href="#graph" style="font-family:Verdana">Graphical View &amp; Stats</a></li>
<li style="margin-left:30px"><a href="#report" style="font-family:Verdana">Report Builder</a></li>
<li><a href="#library" style="font-family:Verdana">Shared Library</a></li>
<li><a href="#postproduction" style="font-family:Verdana">Making Production Changes</a></li>
<li><a href="#DataQuality" style="font-family:Verdana">Data Quality Module</a></li>
</ul>
<p></p><h1 id="general"><font color="#600000">General</font><a class="anchor" href="#general" title="Link to this section"> ¶</a></h1>
<font color="#600000"><b>Q: How much experience with programming, networking and/or database construction is required to use REDCap?</b></font><p></p>
<p>
No programming, networking or database experience is needed to use REDCap. Simple design interfaces within REDCap handle all of these details automatically.
</p>
<p>
It is recommended that once designed, you have a statistician review your project.  It is important to consider the planned statistical analysis before collecting any data.  A statistician can help assure that you are collecting the appropriate fields, in the appropriate format necessary to perform the needed analysis.
</p>
<b><font color="#600000">Q: Can I still maintain a paper trail for my study, even if I use REDCap? </font></b>
<p>
You can use paper forms to collect data first and then enter into REDCap.  All REDCap data collection instruments can also be downloaded and printed with data entered as a universal PDF file.  
</p>
<b><font color="#600000">Q: Can I transition data collected in other applications (ex: MS Access or Excel) into REDCap? </font></b>
<p>
It depends on the project design and application you are transitioning from.  
</p>
<p>
For example, there are a few options to get metadata out of MS Access to facilitate the creation of a REDCap data dictionary:
</p>
<p>
For Access 2003 or earlier, there is a third-party software (CSD Tools) that can export field names, types, and descriptions to MS Excel. You can also extract this information yourself using MS Access. Table names can be queried from the hidden system table "MSysObjects", and a table's metadata can be accessed in VBA using the Fields collection of a DAO Recordset, ADO Recordset, or DAO TableDef.
</p>
<p>
The extracted metadata won't give you a complete REDCap data dictionary, but at least it's a start.
</p>
<p>
Once you have a REDCap project programmed, data can be imported using the Data Import Tool
</p>
<p>
For additional details, contact your local REDCap Administrator.  
</p>
<b id="mobile"><font color="#600000">Q: Can I access REDCap on Android devices, e.g. HTC phones, Galaxy tab etc.? </font></b>
<p>
REDCap can be used on mobile devices in a limited way.  It displays a special format for data entry forms when these are accessed on mobile devices.  You can select a project, select a form and do data entry or review data that was previously entered.  To see what it looks like on a smart phone add "/Mobile" after the version number in the URL as follows (substituting your redcap URL and version number): <a class="ext-link" href="https://your.redcap.url/redcap_v4.2.2/Mobile"><span class="icon">&nbsp;</span>https://your.redcap.url/redcap_v4.2.2/Mobile</a>.
</p>
<b><font color="#600000">Q: Why can’t I pinch in/pinch out when I view REDCap on an iphone? </font></b>
<p>
With the iPhone and iPad, pinch/zoom works correctly on regular REDCap pages.  There are two modes where pinch/zoom does not work.  These are the survey page on the iphone or the mobile REDCap view.  However the Desktop Site view of REDCap pages should render normally and allow zoom. 
</p>
<h2 id="language"><font color="#600000">Language Modules</font><a class="anchor" href="#language" title="Link to this section"> ¶</a></h2>
<b><font color="#600000">Q: Can I create projects/forms in languages other than English?</font></b>
<p>
The label text displayed for individual fields on a survey or data entry form can be in any language. Setting the text label for non-English languages is the same with English text, in which you will set the text for that field in either the Data Dictionary or Online Designer. If you wish to view all the static instructional text in REDCap in a different language, this can be done if that language is supported in REDCap at your local institution. 
</p>
<p>
If you wish to utilize the Language Modules, contact your local REDCap Administrator about which languages are available.  They can switch a REDCap project over so that it will display the new language text instead of English.
</p>
<b><font color="#600000">Q: Can the survey buttons at the bottom of the page: 'Next Page', 'Submit' and the other descriptors: '*these fields are required', 'reset value', etc. appear in a different language (ex: Chinese) in a project with non-English language survey questions?
</font></b>
<p>
In a project with Chinese or other non-English language enabled there are some things (e.g. 'Next Page' and 'Submit' buttons) that for technical reasons cannot be translated.  The researcher may add descriptive text fields at the end of each page to translate 'Next Page', 'Previous', and 'Submit' buttons as needed.
</p>
<b><font color="#600000">Q: Can I use a language translation file to change the wording in the calendar widget to display a different language (ex: Spanish "Hoy" instead of "Today")?
</font></b>
<p>
Some features in REDCap, such as the calendar widget, are hard-coded javascript and/or 3rd party code and cannot be abstracted.
</p>
<h1 id="projectsetup"><font color="#600000">Project Setup / Design</font><a class="anchor" href="#projectsetup" title="Link to this section"> ¶</a></h1>
<b><font color="#600000">Q: What types of projects can I create?</font></b>
<p>
<strong>Starting with v5.0</strong>, you will be prompted to create or request a new project.  Once create, on the Project Setup page you will be able to “enable” the longitudinal feature (repeating forms) and/or multiple surveys for data collection.  In a longitudinal project, for each instrument which is designated a survey, data  may now be collected for every event in the project.
</p>
<p><b><font color="#600000">Q: After my project is created, can I change the name and/or purpose of my project?  </font></b></p>
<p>
Yes. After your project is created, you can navigate to the Project Setup page. Click on the “Modify project title, purpose, etc.”. Here you can update Project Title and Purpose during any project status. 
</p>
<b><font color="#600000">Q: What steps do I have to complete to set up a project?
</font></b><p>
<strong>Starting with v5.0</strong>, depending on your the project settings enabled, you will have the following steps/modules to complete on the Project Set-up page:
</p>
<table class="wiki">
<tbody><tr><td>  </td><td><strong>Surveys</strong></td><td><strong>Classic Database</strong></td><td><strong>Longitudinal Database</strong>
</td></tr><tr><td>Main Project Settings</td><td>  Yes</td><td>   Yes</td><td>   Yes
</td></tr><tr><td>Design Data Collection Instruments</td><td>   Yes</td><td>   Yes</td><td>   Yes
</td></tr><tr><td>Define Events and Designate Instruments</td><td> </td><td> </td><td>   Yes
</td></tr><tr><td>Enable optional modules and customizations</td><td>   Yes</td><td>Yes </td><td>Yes 
</td></tr><tr><td>User Rights and Permissions </td><td> Yes </td><td> Yes</td><td> Yes
</td></tr><tr><td>Move to Production</td><td>   Yes </td><td>   Yes</td><td>   Yes
</td></tr></tbody></table>
<p><b><font color="#600000">Q: Are there specific requirements to set up a project? </font></b>
</p><p>
For projects with surveys, you must complete the "Set up my survey" step in order to activate the Survey URL.  If this step is not complete, the following message will appear to participants instead of your survey:  "It appears that this survey has not been set up yet. You will first need to set up the survey before you can view it."  
</p>
<p>
<strong>Starting in v5.0,</strong> the survey-related options (Survey Settings and Notifications have been moved to the Online Designer page.
</p>
<p>
For ALL projects, you must define a <strong>unique identifier</strong> as the first field on your first data entry form.  The data values entered into this field must be unique.  The system will not allow for duplicate entries. If you do not have a specific unique identifier, you can enable the option “Auto-numbering for records”.
 
</p>
<p>
<strong>Examples of Unique Identifiers:</strong>  Study-assigned ID
</p>
<p>
<strong>Examples of Non-Unique Identifiers:</strong>  Names, Dates of Birth, Consent Dates
</p>
<p>
The unique identifier must be a 'text' field. In addition, please note that unique identifier values will be visible at the end of the URL -- and likely cached in web browsers -- as individual records are viewed or entered. (Example URL: <a class="ext-link" href="https://xxx.xxx.xxx/redcap/redcap_vx.x.x/data_entry.php?pid=xxx&amp;page=xxx&amp;id=ID_VARIABLE_VALUE"><span class="icon">&nbsp;</span>https://xxx.xxx.xxx/redcap/redcap_vx.x.x/data_entry.php?pid=xxx&amp;page=xxx&amp;id=ID_VARIABLE_VALUE</a>.) 
</p>
<p>
<strong>It is strongly recommended that you do not use Protected Health Information (PHI) Identifiers such as MRN or DOB+initials as the unique identifier</strong>.  This is an additional precaution to preserve research participant confidentiality from displaying in the URL and becoming cached.
</p>
<b><font color="#600000">Q: If the unique identifier is arbitrary to me, can the system auto-assign a unique value to each of my records? </font></b>
<p>
Yes.  You can enable auto-numbering for naming new project records on the Project Setup &gt; Enable optional modules and customizations page.  This option will remove the ability for users to name new records manually and will instead provide a link that will auto-generate a new unique record value.  The value is numeric and increments from the highest numeric record value in the project. If no records exist, it will begin with '1'. 
</p>
<b><font color="#600000">Q: How can I set the default auto-numbering to start at a particular number such as 2000?
</font></b>
<p>
You can disable auto-numbering and add the first record using the ID number as the start value.  Once this record is saved, you can enable the auto-numbering customization.  
</p>
<p><b><font color="#600000">Q: What’s the difference between the unique identifier, secondary unique identifier and the redcap_survey_identifier?
</font></b></p><p>
The first variable listed in your project is the <strong>unique identifier</strong> which links all your data.
</p>
<table class="wiki">
<tbody><tr><td>  </td><td><strong>Survey</strong></td><td><strong>Classic Database</strong></td><td><strong>Longitudinal Database</strong>
</td></tr><tr><td>Unique Identifier</td><td>  Participant ID (participant_id) <strong>v5.0+</strong> can be defined as First field of First Form.</td><td> First field of first form </td><td> First field of first form 
</td></tr><tr><td>Secondary Unique Field (optional)</td><td>  Define a Secondary Unique Field  </td><td>Define Auto-numbering Enabled </td><td> Define Auto-numbering Enabled 
</td></tr></tbody></table>
<p>
In Data Entry projects, you must define the unique identifier field.  In Survey and Survey + Data Entry projects, it is automatically defined as the Participant ID.  The Participant ID value is numeric and auto-increments starting with the highest value in the project. If no records exist, it will begin with '1'. 
</p>
<p>
<strong>Starting with v5.0</strong>, users can define the unique ID for projects with surveys instead of using the participant_id.
</p>
<p>
The <strong>secondary unique field</strong> may be defined as any field on the data collection instruments. The value for the field you specify will be displayed next to the Participant ID (for surveys) or next to your unique identifier when choosing an existing record/response.  It will also appear at the top of the data entry page when viewing a record/response. Unlike the value of the primary unique identifier field, it will not be visible in the URL.
</p>
<p>
The data values entered into the secondary unique field must also be unique.  The system will not allow for duplicate entries and checks values entered in real time.  If a duplicate value is entered, an error message will appear and the value must be changed to save/submit data entered on the data entry instrument.
</p>
<p>
The <strong>redcap_survey_identifier</strong> is the identifier defined for surveys when utilizing the Participant Email Contact List and sending survey invitations from the system.  The “Participant Identifier” is an optional field you can use to identify individual survey responses so that the participant doesn’t have to enter any identifying information into the actual survey.  This field is exported in the data set; the email address of the participant is not. 
</p>
<b><font color="#600000">Q:  What are Project Statuses? </font></b>
<p>
All projects when first created start in <strong>Development</strong>.  In Development, you can design, build, and test your REDCap projects.  All design decisions can be made in real time and are implemented immediately to your project.  All survey and data entry features/functions can and should be tested.
</p>
<p>
From Development, you will move your project to <strong>Production</strong> by clicking the button on the Project Setup page.  All survey and data entry features/functions will be exactly the same as they are in development with the exception of certain Project Setup features.  Some project and form design updates will require contacting a REDCap Admin and/or submitting data collection instrument changes in Draft Mode.  Changes to data collection instruments in Draft Mode are not made to your project in real time.  After making updates, you must submit the changes for review.  Review and approval time will vary and are institution specific. 
</p>
<p>
From Production, you can move the projects to the following statuses on the Project Setup &gt; Other Functionality page:
</p>
<p>
<strong>Inactive</strong>:  Move the project to inactive status if data collection is complete. This will disable most project functionality, but data will remain available for export. Once inactive, the project can be moved back to production status at any time.
</p>
<p>
<strong>Archive</strong>: Move the project to archive status if data collection is complete and/or you no longer wish to view on My Projects List. Similar to Inactive status, this will disable most project functionality, but data will not be available for export. The project can only be accessed again by clicking the Show Archived Projects link at the bottom of the My Projects page. Once archived, the project can be moved back to production status at any time. 
</p>
<b><font color="#600000">Q:  Why do I have to "move" my project to production? </font></b>
<p>
Moving your project to Production once you start collecting study data ensures you're maintaining data accuracy and integrity.  The post-production change control process provides an additional check to ensure that data which has already been collected is not deleted, re-coded or overwritten unintentionally.  See FAQ topic "Making Production Changes" for additional details.
</p>
<b><font color="#600000">Q:  If I enter data while I am testing my forms in Development, will it remain when I move to Production? </font></b>
<p>
<strong>It is strongly recommended that you test your projects prior to moving to Production</strong>, either by entering test data or real study data. Entering and saving data is the only way to test that the branching logic and calculated fields are working properly. 
</p>
<p>
When you click the "Move project to Production" button on the Project Setup page, a pop-up will prompt you to "Delete ALL data, calendar events, documents uploaded for records/responses, and (if applicable) survey responses?".  Check the option to delete data.  Uncheck the option to keep all data.
</p>
<p></p><h2 id="surveys"><font color="#600000">Survey Design</font><a class="anchor" href="#surveys" title="Link to this section"> ¶</a></h2>
<b><font color="#600000">Q: Can I create multiple surveys in the same project?</font></b><p></p>
<p>
Currently, a REDCap project can only contain one survey. If the project combines a survey and data entry forms, the survey must be the first instrument so that new records are initiated with a survey response and additional data can be added to the response on follow-up data entry forms. If you have multiple surveys, you will need to create separate projects. However, because you can upload the survey data dictionary, it’s now easier to combine the survey data you receive into a single database.
</p>
<p>
<strong>Starting in v5.0</strong>, you can have multiple surveys in the same project.
</p>
<b><font color="#600000">Q: For Survey + Data Entry Projects, is it possible to start entering data on a data entry form for an individual prior to their completion of the survey? </font></b>
<p>
No.  The survey must be listed as the first instrument in the project and must be initiated by the participant.  The participant's partial or completed response creates a record in the project.  Once created, a project admin can select the record and enter data on the data entry forms.
</p>
<p>
<strong>Starting in v5.0</strong>, you can have multiple surveys and data entry forms in the same project.  You can start with a data entry form and enable the second instrument to be a survey.
</p>
<b><font color="#600000">Q: Starting in v5.0, what is “Designate an email field to use for invitations to survey participants” option?  </font></b>
<p>
Now users may capture a participant's email address by designating a field in their project to be the survey participant email field for capturing the email address to be used.
</p>
<p>
The field can be designated in the "Enable optional modules and customizations" section of the Project Setup page. 
</p>
<p>
Once designated, if an email address is entered into that field for any given record, users will then be able to use that email address for any survey in the project to send survey invitations.  
</p>
<b><font color="#600000">Q: Can I collect anonymous survey data from participants?</font></b>
<p>
Responses can be collected anonymously using either the Public Survey Link or the Participant Contact List.  For either method, the survey questionnaire must not contain any questions asking the participants for identifying data (ex: What is your email? name? address?). 
</p>
<p>
If using the Participant Contact List, you must not enter unique values into the "Participant Identifier (optional)" field.  The Participant Identifier field links the email address to the survey responses.  It is also recommended to keep access to the Invite Participants tool restricted since a small number of respondents would be easily identifiable from the Contact List and the View Responses pages.
</p>
<b><font color="#600000">Q: If my survey is really long, can I create page breaks?</font></b>
<p>
Navigate to Project Setup &gt; Modify Survey Settings.  Make sure to set "Display Questions" = "One section per page".  Then on your questionnaires, you can create page breaks by adding in fields (field type = section header) where you would like those breaks to occur.
</p>
<b><font color="#600000">Q: My survey has matrix fields and it’s creating undesirable page breaks.  What is causing this?</font></b>
<p>
 
Matrix fields contain a “Matrix Header Text” which is actually a Section Header.  Using this field will cause a survey page break. To avoid this, instead of entering text into the header field, add a new “descriptive text field” above your matrix question and enter your text there. 
</p>
<b><font color="#600000">Q: If I enable "Display Questions" = "One section per page", do answers get saved after the respondent hits "next" to go on to the next page?</font></b>
<p>
Yes. Answers are committed to the database as you hit “Next”. So if responders quit the survey before finishing, you’ll have all the data up to that point (partial responses).
</p>
<b><font color="#600000">Q: When "Display Questions" = "One section per page" is enabled and entire sections/questions are hidden due to branching logic, only a blank screen with the "Previous" and "Next" buttons display to the participant taking the survey.  It seems confusing, how can I fix this?</font></b>
<p>
This is the way REDCap functions.  It is not possible to skip the display of a blank screen whenever an entire section is hidden because of branching logic.  Researchers can add descriptive text to these pages. For example, they can display text which says "This section is intentionally blank, please click NEXT PAGE".  Also, to be more informative, question numbers can be added to the field labels to guide users and prevent them from concluding that the blank page was reached in error.
</p>
<b><font color="#600000">Q: For surveys with multiple pages, is there a progress indicator on the survey?</font></b>
<p>
Yes. There is a “Page # of #” at the top right of the survey, so respondents know how many pages they have left.  The progress bar is not a feature of REDCap. 
</p>
<b><font color="#600000">Q: For surveys with multiple pages, can participants go back to a previous page to change answers?</font></b>
<p>
Yes.  Participants can go back to a previous section to change answers by clicking the “Previous Page” button at the bottom of the survey screen. Participants should <strong>only click the “Previous Page” button</strong> and not the web-browser’s back button.
</p>
<b><font color="#600000">Q: For surveys with multiple pages, can the "Previous Page" button be disabled?</font></b>
<p>
No.  The "Previous Page" button is hard coded and displays for all surveys.
</p>
<b><font color="#600000">Q: Can survey respondents save, leave, and then go back to a survey to complete the questions?</font></b>
<p>
Yes. You must enable the <strong>"Save and Return Later"</strong> option in the Modify Survey Settings section of the Project Setup tab. This option allows participants to save their progress and return where they left off any time in the future. They will be given a validation code, which they will be required to enter in order to continue the survey. 
</p>
<p>
<strong>If participants forget their validation code</strong> and contact you, you have access to participants codes on their Survey Results page. You will only be able to distribute lost codes if the survey responses capture identifiers.  If the survey is "anonymous" you will not be able to recover the validation code for the participant.
</p>
<b><font color="#600000">Q: Can I receive a notification when a survey has been completed?</font></b>
<p>
Yes. You can indicate 'Triggers and Notifications' for your survey on the Project Setup page. Currently, you may set your survey to notify you via email when any participant completes it. In the future, other kinds of triggers and notifications will become available for use.
</p>
<b><font color="#600000">Q: Why am I getting duplicate notifications when a survey has been completed?</font></b>
<p>
REDCap specifically checks to ensure it doesn't send a double email to someone.  However duplicate notifications can be sent if another user on that project has a slightly different version of your email address on their REDCap account e.g. <a class="mail-link" href="mailto:jane.J.doe@vanderbilt.edu"><span class="icon">&nbsp;</span>jane.J.doe@vanderbilt.edu</a> vs <a class="mail-link" href="mailto:jane.doe@vanderbilt.edu"><span class="icon">&nbsp;</span>jane.doe@vanderbilt.edu</a>.  There is another possibility.  After a survey participant finishes a survey he or she may refresh the acknowledgement page.  This could result in another batch of emails being sent.  
</p>
<b><font color="#600000">Q: If a participant answers a question in a certain way, can they be taken to the end of the survey if the rest of the questions are not applicable? </font></b>
<p>
Yes, you can indicate "Stop Actions" for survey fields only.  The survey participant will be prompted to end the survey when programmed criteria are selected.  Stop Actions will not be enabled on the data entry form when viewing as an authenticated user.  Stop Actions can only be enabled for certain field types. 
</p>
<b><font color="#600000">Q: When a "stop action" condition is met, can I customize text to display to participants prior to the survey closing?</font></b>
<p>
Customized text cannot be incorporated into the standard REDCap message that displays to the participant. 
</p>
<p>
Another method instead of using the stop action feature, is to hide all other questions with branching logic.  A descriptive text field can be used to display instructions for those who meet the "end of survey" criteria.  These participants can then submit the survey as usual.
</p>
<b><font color="#600000">Q: In REDCap is there a way to automatically display the current date/time on a survey?</font></b>
<p>
Every survey that is submitted is date/time stamped.  This completion date and time are available in the data export and data entry forms.  However it’s not possible to display the current date on the survey while it’s being taken by participants.
</p>
<p>
You can add a question oto your survey to indicate "Today's Date".  The calendar pick list will have a button for "Today" or "Now" that the participant can easily click.
</p>
<b><font color="#600000">Q: What happens when I take the survey "offline" What does the participant see if they click on the link?</font></b>
<p>
When a survey is "offline" participants will no longer be able to view your survey.  They will navigate to a page that displays "Thank you for your interest, but this survey is not currently active."  Project users will still have access to the project, all the applications and survey data.
</p>
<b><font color="#600000">Q: Starting in v5.0, what happened to the “Preview survey" feature?</font></b>
<p>
This feature is no longer available because branching logic or calculated fields would not always work correctly.  To preview your surveys, it is recommended to view the survey as a test participant when testing the project while in development status.
</p>
<p></p><h2 id="longitudinal"><font color="#600000">Longitudinal</font><a class="anchor" href="#longitudinal" title="Link to this section"> ¶</a></h2>
<b><font color="#600000">Q: What is a Longitudinal project?</font></b>
<p></p><p>
A longitudinal project is similar to a traditional data collection project in that multiple data entry forms are defined. However unlike the traditional model, forms in a longitudinal project can be completed repeatedly for a single record. The longitudinal model allows any data entry page to be repeated any given number of times across pre-defined time-points, which are specified by the user before data is collected. So rather than repeating a data entry form multiple times in the Data Dictionary, it can exist only once in the Data Dictionary but be repeated N number of times using the longitudinal model.
</p>
<p>
The longitudinal project lets you define “events” for your project that allow the utilization of data collection forms multiple times for any given database record. An “event” may be a temporal event in the course of your project such as a participant visit or a task to be performed. After events have been defined, you will need to designate the data entry forms that you wish to utilize for any or all events, thus allowing you to use a form for multiple events for the same database record. You may group your events into “arms” in which you may have one or more arms/groups for your project. Each arm can have as many events as you wish. You may use the table provided to create new events and/or arms, or modify existing ones. (One arm and one event will be initially defined as the default for all databases).
</p>
<b><font color="#600000">Q: How is longitudinal data stored?</font></b>
<p>
In the traditional data collection model and for surveys, each project record is stored independently as a separate row of data, which can be seen when exported. But for longitudinal projects, each row of data actually represents that particular time-point (event) per database record. 
</p>
<p>
For example, if four events are defined for the project, one record will have four separate rows of data when exported.  The data export will include a column "redcap_event_name" indicating the unique event name for each row.
</p>
<p>
Longitudinal projects are most commonly created for clinical and research data. A longitudinal project is created by selecting the "Longitudinal / repeating forms" collection format for data entry forms when creating or requesting a new project.   
</p>
<b><font color="#600000">Q: In longitudinal project, how can I set up linkages between events and data entry forms?  </font></b>
<p>
You can use the Designate Forms for my Events page to create linkages between events and data entry forms. In the Designate Forms for my Events page each arm of your study has its own tab. Choose an arm and click the Begin Editing button to link data entry forms to events. Check off boxes to indicate the forms which should be completed for any given event and then click the Save button. You will see a grid that displays the data entry forms that are assigned for completion during each event. Take care to designate forms for your events while the project is in development mode. These associations can only be changed by the REDCap Administrator after the project is in production and should be made with caution to ensure existing data are not corrupted.
</p>
<b><font color="#600000">Q: How can I establish the events and scheduling intervals for my project?  </font></b>
<p>
The Define My Events page allows you to establish the events and scheduling intervals for your project. An “event” may be a temporal visit in the course of your project such as a participant visit or a task to be performed. After events have been defined, you may use them and their Days Offset value to generate schedules. For data collection purposes, you will additionally need to designate the data entry forms that you wish to utilize for any or all events, thus allowing you to use a form for multiple events for the same database record. You may group your events into “arms” in which you may have one or more arms/groups for your project. Each arm can have as many events as you wish. To add new events provide an Event Name and Date Offset for that event and click the Add New Event button.
</p>
<p>
If you will be performing data collection on this project then once you have defined events in the Define My Events page, you may navigate to the Designate Instruments For My Events page where you may select the data collection instruments that you with to utilize for each event that you defined. 
</p>
<b><font color="#600000">Q:  How can I register a subject in a multi-arm study before the determination as to which arm they belong in can be made? </font></b>
<p>
You can set up an arm as a "screening and enrollment" arm. Once a subject becomes enrolled he or she can be added to an "active" arm.
</p>
<h2 id="projectlink"><font color="#600000">Linking Multiple Projects</font><a class="anchor" href="#projectlink" title="Link to this section"> ¶</a></h2>
<b id="projectlinking"><font color="#600000">Q: Can I link multiple projects that share common data?</font></b>
<p>
When building multiple REDCap projects that have some fields in common, such as demographics information, it becomes redundant to repeat all those same fields in each project. However, it is possible in REDCap to link together multiple projects so that one or more projects can be linked to a single shared project (e.g. a project with only demographics information). 
</p>
<p>
There is no limit to how many 'child' projects can be linked to a shared 'parent' project, but a 'child' project can only be linked to one 'parent'. Once a 'child' has been linked to a 'parent' REDCap project, the parent's data entry pages will appear in the child's left-hand menu, and users will be able to access all the data from the 'parent' seamlessly (supposing the user has access rights to both the 'parent' and 'child'). In this way, the 'child' project can only use records from the shared 'parent' project when adding new records to the 'child'.
</p>
<p>
<strong>NOTE: The parent or child data entry collection cannot be longitudinal</strong> (i.e. have multiple events) or have Double Data Entry enabled.  The Project Linking feature must be enabled before any data is collected in the project.
</p>
<p>
If you wish to utilize the Project Linking feature for a REDCap database, contact your REDCap Administrator. 
</p>
<b><font color="#600000">Q: If the link parent/child is removed when both projects are in production and contain data, is there any potential data loss? </font></b>
<p>
No there is no data loss.
</p>
<p></p><h2 id="copyproject"><font color="#600000">Copy a Project</font><a class="anchor" href="#copyproject" title="Link to this section"> ¶</a></h2><p></p>
<p><b><font color="#600000">Q: Can I create a copy of my project? </font></b></p>
<p>
Yes.  If you have the right to create/request new projects, you can navigate to the Project Setup &gt; Other Functionality page and request a "Copy" of the project.
</p>
<p>
<br>
</p>
<h1 id="dci"><font color="#600000">Data Collection Instrument Design</font><a class="anchor" href="#dci" title="Link to this section"> ¶</a></h1>
<p><b><font color="#600000">Q: What is the difference between a data entry form and a survey? </font></b></p>
<p>
REDCap defines Data Collection Instruments as "data entry forms" and "surveys".  
</p>
<p>
With "surveys" you can collect data directly from participants.  Participants will access your questions via a secure webpage.  No authentication is needed.
</p>
<p>
With "data entry forms", data is entered by authorized REDCap project users.  REDCap log-in access and project rights are required to view and edit the data entry forms.  
</p>
<p><b><font color="#600000">Q: Are there restrictions to the number of data collection instruments you can have in one project? </font></b></p>
<p>
Currently, there are no restrictions on the number of data collection instruments per project. 
</p>
<p><b><font color="#600000">Q: Are there restrictions to the number of fields you can have in one instrument? </font></b></p>
<p>
No. There are no restrictions on the length or number of fields per instrument. The best practice is to keep instruments fairly short for easier data entry, and to ensure that you're saving data to the server more frequently.  
</p>
<p>
For long surveys, you can use section headers and enable the feature "Display Questions" = One Section Per Page.  This will allow participants to save each section when they click "next page".  
</p>
<b><font color="#600000">Q: Are there any restrictions to what you can name a data collection instrument? </font></b>
<p>
Naming instruments using the Online Designer do not have restrictions.  Naming instruments using the Data Dictionary is restricted to lowercase and may contain only letters, numbers, and underscores.
</p>
<b><font color="#600000">Q: Are there any restrictions to what you can name a variable/field?</font></b>
<p>
Variables names cannot be duplicated and should always start with a letter. Field names must be lowercase and may contain only letters, numbers, and underscores. In general, field names should be as short in length as possible while maintaining meaning.
</p>
<b><font color="#600000">Q: Is it possible to change the format (colors, text) of the form, field or text display? </font></b>
<p>
The general survey and data entry templates are static and cannot be changed.  REDCap does allow the use of some HTML and CSS in the Field Label and Field Notes.  Please note that HTML tags print as text on the pdf exports/forms and do not print the formats created with the HTML tags.
</p>
<p>
For example, to format text in a Field Label or Field Note to superscript/subscript:
</p>
<p>
You can use the string &lt;span style=""&gt;&lt;/span&gt; to manipulate visual attributes.  For instance, for
superscript the style may be "font-size:75%;vertical-align:super;".  For subscript the relevant code is  “vertical-align:sub”.
</p>
<p>
So the whole label may be the following:
</p>
<p>
Plain text &lt;span style="font-size:75%;vertical-align:super;"&gt;superscript text&lt;/span&gt;
another plain text &lt;span style="font-size:75%;vertical-align:sub;"&gt;subscript text&lt;/span&gt;
</p>

<h2 id="onlinedesign"><font color="#600000">Online Designer / Data Dictionary</font><a class="anchor" href="#onlinedesign" title="Link to this section"> ¶</a></h2>
<b><font color="#600000">Q: What is the Online Designer? </font></b>
<p>
The Online Designer will allow you to make create/modify/delete data collection instruments and fields (questions) very easily using your web browser.  Changes are made in real time and available immediately for review and testing. 
</p>
<b><font color="#600000">Q: What is the Data Dictionary? </font></b>
<p>
The Data Dictionary is a specifically formatted spreadsheet in CSV (comma separated format) containing the metadata used to construct data collection instruments and fields. The changes you make with the data dictionary are not made in real time to the project (off-line method).  The modified file must first be uploaded successfully before changes are committed to the project.
</p>
<p>
Note: As of v4.0, Field Units column is no longer used and has been removed from the data dictionary.
</p>
<b><font color="#600000">Q: I get an error message when I attempt to upload a really large data dictionary.  Does REDCap set a limit on the file size of an imported data dictionary?</font></b>
<p>
REDCap can be configured to allow large files to be uploaded.  You'll need to contact your local REDCap Administrator about your institution's file upload limits.
</p>
<h2 id="fieldtypes"><font color="#600000">Field Types</font><a class="anchor" href="#fieldtypes" title="Link to this section"> ¶</a></h2>
<b><font color="#600000">Q: What are the field types? </font></b>
<p>
The Field Type dictates how the field will be shown on the data entry form. 
</p>
<p>
Options include:
</p>
<table class="wiki">
<tbody><tr><td><strong>TEXT</strong> </td><td>- single-line text box (for text and numbers)
</td></tr><tr><td><strong>NOTES</strong> </td><td>- large text box for lots of text
</td></tr><tr><td><strong>DROPDOWN</strong> </td><td>- dropdown menu with options
</td></tr><tr><td><strong>RADIO</strong> </td><td>- radio buttons with options
</td></tr><tr><td><strong>CHECKBOX</strong> </td><td>- checkboxes to allow selection of more than one option
</td></tr><tr><td><strong>FILE</strong> </td><td>- upload a document
</td></tr><tr><td><strong>CALC</strong> </td><td>- perform real-time calculations
</td></tr><tr><td><strong>SQL</strong> </td><td>- select query statement to populate dropdown choices
</td></tr><tr><td><strong>DESCRIPTIVE</strong> </td><td>- text displayed with no data entry and optional image/file attachment
</td></tr><tr><td><strong>SLIDER</strong> </td><td>- visual analogue scale; coded as 0-100
</td></tr><tr><td><strong>YESNO</strong> </td><td>- radio buttons with yes and no options; coded as 1, Yes | 0, No
</td></tr><tr><td><strong>TRUEFALSE</strong> </td><td>- radio buttons with true and false options; coded as 1, True | 0, False
</td></tr></tbody></table>
<p>
<br>
</p>
<b><font color="#600000">Q: Can I upload files to attach to individual subject records? </font></b>
<p>
Yes, you can upload documents for individual records.
</p>
<p>
To create a new document upload field in the Data Dictionary for any given REDCap project, set the <strong>Field Type = ‘file’</strong>. You may add as many 'file' fields as needed to your data collection instruments.  
</p>
<p>
Documents can be uploaded and downloaded by navigating to the record’s data entry page and clicking the file link. A document can be deleted at any time, and there is no limit to how many times the document can be replaced by uploading another file to that record’s file upload field.
</p>
<p>
Contact your REDCap Administrator to confirm if this field type is available and what the maximum upload file size is at your institution.
</p>
<b><font color="#600000">Q: Is there a question type that is a radio button with a text box for "Other, specify"? </font></b>
<p>
No, this specific question type is not available. You can add a text field after the question and use branching logic so that if "Other" is selected, a text box appears to capture the data.
</p>
<b><font color="#600000">Q: Can I shorten an instrument by grouping related questions together using a columnar format?
</font></b>
<p>
It is not possible to build survey or data entry forms in a columnar format in REDCap.  You can use a combination of branching logic, section headers and descriptive text to shorten the instrument and group related questions.
</p>
<b><font color="#600000">Q: How do I create a set of variables for an unknown number of possible responses for the same question? </font></b>
<p>
For a question with an unknown number of answers, such as how many medications someone is taking, you may want to display the fields only as they are needed. REDCap currently is not able to dynamically create fields; however, there is a way to use branching logic to approximate this.
</p>
<p>
If you can estimate the maximum number of fields you will need, you can create that many copies of your field to hide and display as needed using branching logic. 
</p>
<p>
<strong>Example 1</strong>: If you think 15 is a good maximum, you would create 15 copies of the field. Then, in order to only show the fields that are needed, you could create a "count" variable.  Your branching logic would look like this: 
</p>
<p>
field1: [count]&gt;0 
</p>
<p>
field2: [count]&gt;1 
</p>
<p>
field3: [count]&gt;2 
</p>
<p>
and so on.
</p>
<p>
If your variable is medications, and the respondent takes 2 medications, you enter 2 in [count] variable, then the med1 and med2 fields appear. If they take 3, you enter that, and meds1 to med3 fields appear.
</p>
<p>
<strong>Example 2:</strong> Another method is to first create the maximum number of fields that you estimate will be needed, as above, and then hide and display each field as the previous field receives data. Using this method will cause each field to show up as needed. Your branching logic would look like: 
</p>
<p>
field2: [field1] &lt;&gt; "" 
</p>
<p>
field3: [field1] &lt;&gt; "" and [field2] &lt;&gt; "" 
</p>
<p>
field4: [field1] &lt;&gt; "" and [field2] &lt;&gt; "" and [field3] &lt;&gt; "" 
</p>
<p>
and so on.
</p>
<p>
The fields in this example are text fields.  If field1 "does not equal blank" (aka if data is entered for field1), then field2 will display.
</p>
<p>
<strong>NOTE:</strong> In both of the above examples it is possible for one to enter data into one of the fields but for that field to later not be displayed to the user. This is a general issue whenever using branching/skip logic.
</p>
<b><font color="#600000">Q: Can I populate radio buttons, dropdowns and checkbox field choices using an "if then" statement? </font></b>
<p>
There is currently no way of populating field choices dynamically.  You can create multiple fields and response option lists and hide or display them using branching logic.  In certain circumstances, you may be able to populate a dropdown list from another REDCap field, but this is a very specific use case and requires contacting a REDCap Admin.     
</p>
<p>
<br>
</p>
<h2 id="txtval"><font color="#600000">Text Validation Types</font><a class="anchor" href="#txtval" title="Link to this section"> ¶</a></h2>
<b><font color="#600000">Q: What are the possible Text Validation Types? </font></b>
<p>
Used for data validation for TEXT fields. Appropriate categories and examples are given below:
</p>
<table class="wiki">
<tbody><tr><td><strong>DATE_YMD</strong> </td><td>- (2008-12-31)
</td></tr><tr><td><strong>DATE_MDY</strong> </td><td>- (12-31-2008)
</td></tr><tr><td><strong>DATE_DMY</strong> </td><td>- (31-12-2008)
</td></tr><tr><td><strong>TIME</strong> </td><td>- (19:30, 04:15) - Military time
</td></tr><tr><td><strong>DATETIME_YMD</strong> </td><td>- (2011-02-16 17:45)
</td></tr><tr><td><strong>DATETIME_MDY</strong> </td><td>- (02-16-2011 17:45)
</td></tr><tr><td><strong>DATETIME_DMY</strong> </td><td>- (16-02-2011 17:45)
</td></tr><tr><td><strong>DATETIME_SECONDS_YMD</strong> </td><td>- (2011-02-16 17:45:23)
</td></tr><tr><td><strong>DATETIME_SECONDS_MDY</strong> </td><td>- (02-16-2011 17:45:23)
</td></tr><tr><td><strong>DATETIME_SECONDS_DMY</strong> </td><td>- (16-02-2011 17:45:23)
</td></tr><tr><td><strong>PHONE</strong> </td><td>- (615-322-2222)*
</td></tr><tr><td><strong>EMAIL</strong> </td><td>- (<a class="mail-link" href="mailto:john.doe@vanderbilt.edu"><span class="icon">&nbsp;</span>john.doe@vanderbilt.edu</a>)
</td></tr><tr><td><strong>NUMBER</strong> </td><td>- (1.3, 22, -6.28) a general number
</td></tr><tr><td><strong>INTEGER</strong> </td><td>- (1, 4, -10) whole number with no decimal
</td></tr><tr><td><strong>ZIPCODE</strong> </td><td>- (37212, 90210) 5-digit zipcode
</td></tr></tbody></table>
<p>
*Note: Data entered in fields with phone validation must meet the following criteria:
</p>
<blockquote>
<p>
•Area codes start with a number from 2–9, followed by 0–8, and then any third digit. <br>
•The second group of three digits, known as the central office or exchange code, starts with a number from 2–9, followed by any two digits. <br>
•The final four digits, known as the station code, have no restrictions.
</p>
</blockquote>
<p>
<br>
The following validation types are optional, and may not be activated at your instition:
</p>
<table class="wiki">
<tbody><tr><td><strong>Letters only</strong> </td><td>- (name)
</td></tr><tr><td><strong>MRN</strong> </td><td>- (0123456789)10 digits
</td></tr><tr><td><strong>Number (1 decimal place)</strong> </td><td>- (1.2)
</td></tr><tr><td><strong>Number (2 decimal places)</strong> </td><td>- (1.23)
</td></tr><tr><td><strong>Number (3 decimal places)</strong> </td><td>- (1.234)
</td></tr><tr><td><strong>Number (4 decimal places)</strong> </td><td>- (1.2345)
</td></tr><tr><td><strong>Phone (Australia)</strong> </td><td>- ((03) 1234 1234)
</td></tr><tr><td><strong>Postal Code (Australia)</strong> </td><td>- (2150,7799)4-digit number
</td></tr><tr><td><strong>Postal Code (Canada)</strong> </td><td>- (K1A 0B1, K0H 9Z0) format: A0A 0A0 where A is a letter and 0 is a digit
</td></tr><tr><td><strong>Social Security Number (US)</strong> </td><td>- (111-11-1111)
</td></tr><tr><td><strong>Time (MM:SS)</strong> </td><td>- (31:22) time in minutes and seconds
</td></tr></tbody></table>
<p>
<br>
</p>
<b><font color="#600000">Q: Is it possible to restrict text inputs to a defined length or digit/character combination?
</font></b>
<p>
You can restrict text inputs by using custom field validation types.  Custom field validation types must be created by the REDCap Development team.  Your REDCap Administrator will be able to submit requests for new custom field validation types.  The request will be evaluated by the concerned team and approved requests will be fulfilled.  However it is not possible to specify a deadline for meeting the request.
</p>
<p>
<br>
</p>
<b><font color="#600000">Q: Can I set minimum and maximum ranges for certain fields? </font></b>
<p>
If validation is employed for text fields, min and max values may be utilized. Min, max, neither or both can be used for each individual field. The following text validation types may utilize min and/or max values:
</p>
<table class="wiki">
<tbody><tr><td><strong>DATE_YMD</strong> 
</td></tr><tr><td><strong>DATE_MDY</strong> 
</td></tr><tr><td><strong>DATE_DMY</strong> 
</td></tr><tr><td><strong>TIME</strong> 
</td></tr><tr><td><strong>DATETIME_YMD</strong> 
</td></tr><tr><td><strong>DATETIME_MDY</strong> 
</td></tr><tr><td><strong>DATETIME_DMY</strong> 
</td></tr><tr><td><strong>DATETIME_SECONDS_YMD</strong> 
</td></tr><tr><td><strong>DATETIME_SECONDS_MDY</strong> 
</td></tr><tr><td><strong>DATETIME_SECONDS_DMY</strong> 
</td></tr><tr><td><strong>NUMBER</strong>
</td></tr><tr><td><strong>INTEGER</strong>
</td></tr></tbody></table>
<b><font color="#600000">Q: What are the Custom Alignment codes for the data dictionary? </font></b>
<p>
<br>
</p>
<table class="wiki">
<tbody><tr><td><strong>RV</strong> </td><td>– right vertical
</td></tr><tr><td><strong>RH</strong> </td><td>– right horizontal
</td></tr><tr><td><strong>LV</strong> </td><td>– left vertical
</td></tr><tr><td><strong>LH</strong> </td><td>– left horizontal
</td></tr></tbody></table>
<p>
<br>
</p>
<b><font color="#600000">Q: What is the Question Number (surveys only) column in the data dictionary? </font></b>
<p>
For surveys, you can use this column to enter number of the survey question for screen display.
</p>
<b><font color="#600000">Q: What are “identifiers”? </font></b>
<p>
There are 18 pieces of information that are considered identifiers (also called protected health information, or PHI) for the purposes of HIPAA compliance. When you indicate a variable as an Identifier, you have the option to <strong>“de-identify” your data on data exports</strong>. In the Data Export Tool, the identifier variables appear in red and there are de-identification options you can select prior to exporting the data.
</p>
<p>
The 18 HIPAA identifiers are:
</p>
<table class="wiki">
<tbody><tr><td>1.        </td><td>Name
</td></tr><tr><td>2.        </td><td>Fax number
</td></tr><tr><td>3.        </td><td>Phone number
</td></tr><tr><td>4.        </td><td>E-mail address
</td></tr><tr><td>5.        </td><td>Account numbers
</td></tr><tr><td>6.        </td><td>Social Security number
</td></tr><tr><td>7.        </td><td>Medical Record number
</td></tr><tr><td>8.        </td><td>Health Plan number
</td></tr><tr><td>9.        </td><td>Certificate/license numbers
</td></tr><tr><td>10.        </td><td>URL
</td></tr><tr><td>11.        </td><td>IP address
</td></tr><tr><td>12.        </td><td>Vehicle identifiers
</td></tr><tr><td>13.        </td><td>Device ID
</td></tr><tr><td>14.        </td><td>Biometric ID
</td></tr><tr><td>15.        </td><td>Full face/identifying photo
</td></tr><tr><td>16.        </td><td>Other unique identifying number, characteristic, or code
</td></tr><tr><td>17.        </td><td>Postal address (geographic subdivisions smaller than state)
</td></tr><tr><td>18.        </td><td>Date precision beyond year
</td></tr></tbody></table>
<p>
<br>
</p>
<h2 id="dates"><font color="#600000">Dates</font><a class="anchor" href="#dates" title="Link to this section"> ¶</a></h2>
<b><font color="#600000">Q: How are dates formatted?  Can I change the date format? </font></b>
<p>
Prior to v4.1, date format was YYYY-MM-DD.  As of v4.1, dates can be formatted as <strong>mm-dd-yyyy</strong>, <strong>dd-mm-yyyy</strong>, and <strong>yyyy-mm-dd</strong>.  These formats cannot be modified.  <strong>It is recommended to always use the field label or field note to specify the required date format.</strong>
</p>
<b><font color="#600000">Q: How do I indicate “dates” in the data dictionary?</font></b>
<p>
The current date format names used in the Data Dictionary are backward compatible; therefore, you can still enter "date" as the validation type for YYYY-MM-DD in the Data Dictionary, even though its new name in v4.1+ will instead be "date_ymd" 
</p>
<p>
v4.1+ Text Validation Types: Use for text field data validation
</p>
<table class="wiki">
<tbody><tr><td><strong>DATE_DMY</strong> </td><td>Example:    16-02-2011 
</td></tr><tr><td><strong>DATE_MDY</strong> </td><td>Example:    02-16-2011 
</td></tr><tr><td><strong>DATE_YMD</strong> </td><td>Example:    2011-02-16 
</td></tr><tr><td><strong>DATETIME_DMY</strong> </td><td>Example:    16-02-2011 17:45
</td></tr><tr><td><strong>DATETIME_MDY</strong> </td><td>Example:    02-16-2011 17:45
</td></tr><tr><td><strong>DATETIME_YMD</strong> </td><td>Example:    2011-02-16 17:45
</td></tr><tr><td><strong>DATETIME_SECONDS_DMY</strong> </td><td>Example:  16-02-2011 17:45:23
</td></tr><tr><td><strong>DATETIME_SECONDS_MDY</strong> </td><td>Example:  02-16-2011 17:45:23
</td></tr><tr><td><strong>DATETIME_SECONDS_YMD</strong> </td><td>Example:  2011-02-16 17:45:23
</td></tr></tbody></table>
<p>
Users uploading Data Dictionaries containing the previous validation names prior to v4.1 will not notice any difference on the user interface; however, if you download your Data Dictionary, you'll see the v4.1 text validation names. 
</p>
<b><font color="#600000">Q: Can I change date formats if I've already entered data?</font></b>
<p>
Any date fields that already exist in a REDCap project can be easily converted to other formats without affecting the stored data value.  After altering the format of the existing date fields, dates stored in the project will display in the new date format when viewed on the survey/form. Therefore, you change the date format of a field without compromising the stored data.
</p>
<b><font color="#600000">Q: Can I enter dates without dashes or slashes?</font></b>
<p>
Date values can be entered using several delimiters (period, dash, slash, or even a lack of delimiter) but will be reformatted to dashes before saving it (e.g. 05.31.09 or 05312009 will automatically be reformatted to 05-31-2009 for MM-DD-YYYY format). 
</p>
<b><font color="#600000">Q:  Why can’t I see the different date formats in the Online Designer?</font></b>
<p>
When REDCap is upgraded to version 4.1, the new validation types are not automatically available. They will only be available if your REDCap administrator enables the feature. Once enabled, they'll appear in the text validation drop-down list in the Online Designer. However, you can still use these formats via the Data Dictionary.
</p>
<b><font color="#600000">Q: How are the different date formats imported?</font></b>
<p>
While the different date formats allow users to enter and view dates in those formats on a survey/form, dates must still only be imported either in YYYY-MM-DD or MM/DD/YYYY format. 
</p>
<b><font color="#600000">Q: How are the different date formats exported?</font></b>
<p>
The Data Export Tool will only export dates, datetimes, and datetime_seconds in YYYY-MM-DD format. Previously in 3.X-4.0, datetimes were exported as YYYY-MM-DD HH:MM, while dates were exported as MM/DD/YYYY.  By exporting only in YYYY-MM-DD format it is more consistent across the date validation field types.
</p>
<p>
If exporting data to a stats package, such as SPSS, SAS, etc., it will still import the same since the syntax code has been modified for the stats package syntax files to accommodate the new YMD format for exported dates. The change in exported date format should not be a problem with regard to opening/viewing data in Excel or stats packages. 
</p>
<b><font color="#600000">Q: How do I display unknown dates?  What’s the best way to format MM-YYYY? </font></b>
<p>
When you set a text field validation type = date, the date entered must be a valid completed date. To include options for unknown or other date formats, you may need to break the date field into multiple fields. For Days and Months, you can create dropdown choices to include numbers (1-31, 1-12) and UNK value. For Year, you can define a text field with validation = number and set a min and max value (ex: 1920 – 2015). 
</p>
<p>
The advantage of the multi-field format is that you can include unknown value codes. The disadvantages are that you may need to validate date fields after data entry (i.e. ensure no Feb 31st) and there will be additional formatting steps required to analyze your data fields.
</p>
<h3 id="CalculatedFields">Calculated Fields<a class="anchor" href="#CalculatedFields" title="Link to this section"> ¶</a></h3>
<b><font color="#600000">Q: What are calculated fields? </font></b>
<p>
REDCap has the ability to make real-time calculations on data entry forms. <strong>It is recommended that 'calc' field types are not excessively utilized on REDCap data collection instruments.</strong>  Calc fields should only be used when it is necessary to know the calculated value while on that page or the following pages, or when the result of the calculation affects data entry workflow. 
</p>
<p>
The method by which REDCap performs calculations (using JavaScript, a common technology found in all web browsers) is known in rare cases to have calculation precision issues (e.g. the result of a complex calculation should be 0.06 but instead results as 0.06000000000000000005). However, such precision errors are very uncommon and are very seldom ever experienced. This can often be remedied by strategically using the REDCap round() function within your calculation; however it is not 100% preventative for all cases.
</p>
<p>
Because of this and various other reasons, it is recommended that calculations be performed in a statistical analysis package after exporting the data from REDCap.
</p>
<b><font color="#600000">Q: How do I format calculated fields? </font></b>
<p>
In order for the calculated field to function, it will need to be formatted in a particular way. This is somewhat similar to constructing equations in Excel or with certain scientific calculators.
</p>
<p>
The variable names/field names used in the project's Data Dictionary can be used as variables in the equation, but you must place <strong>[ ]</strong> brackets around each variable. Please be sure that you follow the mathematical order of operations when constructing the equation or else your calculated results might end up being incorrect.
</p>
<b><font color="#600000">Q: What are some common examples of calculated fields? </font></b>
<p>
To calculate BMI (body mass index) from height and weight, you can create 'BMI' as a calculated field, as seen below. When values for height and weight are entered, REDCap will calculate the ‘BMI’ field. The data for a calculated field are saved to the database when the form is saved and can be exported just like all other fields.
 
To create a calculated field, you will need to do two things:
</p>
<p>
1) Set the Field Type of the new field as Calculated Field in the Online Designer, or 'calc' if you are working in the data dictionary spreadsheet.
</p>
<p>
2) Provide the equation for the calculation in the Calculation Equation section of the Online Designer or the 'Choices OR Calculations' column in the data dictionary spreadsheet.
Below is an example equation for the BMI field above in which the fields named 'height' and 'weight' are used as variables.
</p>
<p>
<strong>[weight]*10000/([height]*[height])</strong>for units in kilograms and centimeters
</p>
<p>
<strong>([weight]/([height]*[height]))*703 </strong>for units in pounds and inches
</p>
<p>
A more complex example for another calculated field might be as follows:
</p>
<p>
(([this]+525)/34)+(([this]/([that]-1000))*9.4)
</p>
<b><font color="#600000">Q: Can fields from different FORMS be used in calculated fields? </font></b>
<p>
Yes, a calculated field's equation may utilize fields either on the current data entry form OR on other forms. The equation format is the same, so no special formatting is required.
</p>
<b><font color="#600000">Q: Can fields from different EVENTS be used in calculated fields (longitudinal only)? </font></b>
<p>
Yes, for longitudinal projects (i.e. with multiple events defined), a calculated field's equation may utilize fields from other events (i.e. visits, time-points). The equation format is somewhat different from the normal format because the unique event name must be specified in the equation for the target event. The unique event name must be prepended (in square brackets) to the beginning of the variable name (in square brackets), i.e. [unique_event_name][variable_name]. Unique event names can be found listed on the project's Define My Event's page on the right-hand side of the events table, in which the unique name is automatically generated from the event name that you have defined. 
</p>
<p>
For example, if the first event in the project is named "Enrollment", in which the unique event name for it is "enrollment_arm_1", then we can set up the equation as follows to perform a calculation utilizing the "weight" field from the Enrollment event: [enrollment_arm_1][weight]/[visit_weight]. Thus, presuming that this calculated field exists on a form that is utilized on multiple events, it will always perform the calculation using the value of weight from the Enrollment event while using the value of visit_weight for the current event the user is on.
</p>
<b><font color="#600000">Q: Can calculated fields be referenced or nested in other calculated fields?</font></b>
<p>
<strong>It is strongly recommended that you do not reference calc fields within calc fields.</strong>  When multiple calculations are performed, the order of execution is determined by the alphabetical order of the associated field names. 
</p>
<p>
Therefore, if you have nested calc fields, it may not be possible to set up the equation so it evaluates in the desired order.   Instead of using calc fields based off of other calc fields, incorporate the original calculations using the mathematical distributive property:
 
</p>
<table class="wiki">
<tbody><tr><td>calc1 = 3 + [age]
</td></tr><tr><td>calc2 = 7 * (3 + [age])
</td></tr></tbody></table>
<p>
Instead of programming calc2 = 7 * [calc1]
</p>
<b><font color="#600000">Q: How can I calculate the difference between two date or time fields (this includes datetime and datetime_seconds fields)? </font></b>
<p>
You can calculate the difference between two dates or times by using the function:
</p>
<p>
<strong>datediff([date1], [date2], "units", "dateformat", returnSignedValue)</strong>
</p>
<p>
date1 and date2 are variables in your project
</p>
<p>
<strong>units</strong> 
</p>
<table class="wiki">
<tbody><tr><td><strong>"y"</strong> </td><td> years </td><td>  1 year = 365.2425 days 
</td></tr><tr><td><strong>"M"</strong> </td><td> months </td><td> 1 month = 30.44 days 
</td></tr><tr><td><strong>"d"</strong> </td><td>days </td><td> 
</td></tr><tr><td><strong>"h"</strong> </td><td>hours </td><td> 
</td></tr><tr><td><strong>"m"</strong> </td><td>minutes </td><td> 
</td></tr><tr><td><strong>"s"</strong> </td><td>seconds </td><td> 
</td></tr></tbody></table>
<p>
 
<strong>dateformat</strong> 
</p>
<table class="wiki">
<tbody><tr><td><strong>"ymd"</strong> </td><td> Y-M-D (default)
</td></tr><tr><td><strong>"mdy"</strong> </td><td> M-D-Y 
</td></tr><tr><td><strong>"dmy"</strong> </td><td> D-M-Y 
</td></tr></tbody></table>
<ul><li>If the dateformat is not provided, it will default to "ymd". 
</li><li>Both dates MUST be in the format specified in order to work.
</li></ul><p>
<strong>returnSignedValue</strong> 
</p>
<table class="wiki">
<tbody><tr><td><strong>false</strong> </td><td>(default) 
</td></tr><tr><td><strong>true</strong> </td><td> 
</td></tr></tbody></table>
<ul><li>The parameter returnSignedValue denotes the result to be signed or unsigned (absolute value), in which the default value is "false", which returns the absolute value of the difference. For example, if [date1] is larger than [date2], then the result will be negative if returnSignedValue is set to true. If returnSignedValue is not set or is set to false, then the result will ALWAYS be a positive number. If returnSignedValue is set to false or not set, then the order of the dates in the equation does not matter because the resulting value will always be positive (although the + sign is not displayed but implied). 
</li></ul><p>
Examples:
</p>
<table class="wiki">
<tbody><tr><td><strong>datediff([dob],[date_enrolled],"d")</strong> </td><td>Yields the number of days between the dates for the date_enrolled and dob fields, which must be in Y-M-D format 
</td></tr><tr><td><strong>datediff([dob],"05-31-2007","h","mdy",true)</strong> </td><td> Yields the number of hours between May 31, 2007, and the date for the dob field, which must be in M-D-Y format. Because returnSignedValue is set to true, the value will be negative if the dob field value is more recent than May 31, 2007. 
</td></tr></tbody></table>
<b><font color="#600000">Q: Can I base my datediff calculation off of today? </font></b>
<p>
Yes, for example, you can indicate "age" as: datediff("today",[dob],"y"). NOTE: The "today" variable can ONLY be used with date fields and NOT with time, datetime, or datetime_seconds fields.
</p>
<p>
<strong>It is strongly recommended that you do not use "today" in calc fields.</strong> This is because every time you access and save the form, the calculation will run. So if you calculate the age as of today, then a year later you access the form to review or make updates, the age as of "today" will also be updated (+1 yr). Most users calculate ages off of another field (e.g. screening date, enrollment date).
</p>
<b><font color="#600000">Q: Can I calculate a new date by adding days / months / years to a date entered (Example: [visit1_dt] + 30days)? </font></b>
<p>
No.  Calculations can only display numbers.  
</p>
<b><font color="#600000">Q: What mathematical operations are available for calc fields? </font></b>
<table class="wiki">
<tbody><tr><td>+        </td><td>Add
</td></tr><tr><td>-        </td><td>Subtract
</td></tr><tr><td>*        </td><td>Multiple
</td></tr><tr><td>/        </td><td>Divide
</td></tr></tbody></table>
<p>
<strong>Null</strong> or <strong>blank</strong> values can be referred to as <strong>""</strong> or <strong>"NaN"</strong>
</p>
<b id="calcAdvFunc"><font color="#600000">Q: Can REDCap perform advanced functions in calculated fields? </font></b>
<p>
Yes, it can perform many, which are listed below. NOTE: All function names (e.g. roundup, abs) listed below are case sensitive.
</p>
<table class="wiki">
<tbody><tr><td> <strong>Function</strong> </td><td> <strong>Type of calculation</strong> </td><td> <strong>Notes / examples</strong>
</td></tr><tr><td> round(number,decimal places) </td><td> <strong>Round</strong> </td><td> If the "decimal places" parameter is not provided, it defaults to 0. E.g. To round 14.384 to one decimal place:  round(14.384,1) will yield 14.4 
</td></tr><tr><td> roundup(number,decimal places) </td><td> <strong>Round Up</strong> </td><td> If the "decimal places" parameter is not provided, it defaults to 0. E.g. To round up 14.384 to one decimal place:  roundup(14.384,1) will yield 14.4
</td></tr><tr><td> rounddown(number,decimal places) </td><td> <strong>Round Down</strong> </td><td> If the "decimal places" parameter is not provided, it defaults to 0. E.g. To round down 14.384 to one decimal place:  rounddown(14.384,1) will yield 14.3
</td></tr><tr><td> sqrt(number) </td><td> <strong>Square Root</strong> </td><td> E.g. sqrt([height]) or sqrt(([value1]*34)/98.3)
</td></tr><tr><td> (number)^(exponent) </td><td><strong>Exponents</strong> </td><td> Use caret ^ character and place both the number and its exponent inside parentheses:  For example, (4)^(3) or ([weight]+43)^(2)
</td></tr><tr><td> abs(number) </td><td> <strong>Absolute Value</strong> </td><td> Returns the absolute value (i.e. the magnitude of a real number without regard to its sign). E.g. abs(-7.1) will return 7.1 and abs(45) will return 45. 
</td></tr><tr><td> min(number,number,...) </td><td> <strong>Minimum</strong> </td><td> Returns the minimum value of a set of values in the format min([num1],[num2],[num3],...). NOTE: All blank values will be ignored and thus will only return the lowest numerical value. There is no limit to the amount of numbers used in this function.
</td></tr><tr><td> max(number,number,...) </td><td> <strong>Maximum</strong> </td><td> Returns the maximum value of a set of values in the format max([num1],[num2],[num3],...). NOTE: All blank values will be ignored and thus will only return the highest numerical value. There is no limit to the amount of numbers used in this function. 
</td></tr><tr><td> mean(number,number,...) </td><td> <strong>Mean</strong> </td><td> Returns the mean (i.e. average) value of a set of values in the format mean([num1],[num2],[num3],...). NOTE: All blank values will be ignored and thus will only return the mean value computed from all numerical, non-blank values. There is no limit to the amount of numbers used in this function. 
</td></tr><tr><td> median(number,number,...) </td><td> <strong>Median</strong> </td><td> Returns the median value of a set of values in the format median([num1],[num2],[num3],...). NOTE: All blank values will be ignored and thus will only return the median value computed from all numerical, non-blank values. There is no limit to the amount of numbers used in this function.  
</td></tr><tr><td> sum(number,number,...) </td><td> <strong>Sum</strong> </td><td> Returns the sum total of a set of values in the format sum([num1],[num2],[num3],...). NOTE: All blank values will be ignored and thus will only return the sum total computed from all numerical, non-blank values. There is no limit to the amount of numbers used in this function.  
</td></tr><tr><td> stdev(number,number,...) </td><td> <strong>Standard Deviation</strong> </td><td> Returns the standard deviation of a set of values in the format stdev([num1],[num2],[num3],...). NOTE: All blank values will be ignored and thus will only return the standard deviation computed from all numerical, non-blank values. There is no limit to the amount of numbers used in this function.  
</td></tr></tbody></table>
<p>
<br>
</p>
<b><font color="#600000">Q: Can I use conditional logic in a calculated field? </font></b>
<p>
Yes. You may use conditional logic (i.e. an IF/THEN/ELSE statement) by using the function <strong>if (CONDITION, value if condition is TRUE, value if condition is FALSE)</strong>
</p>
<p>
This construction is similar to IF statements in Microsoft Excel. Provide the condition first (e.g. [weight]=4), then give the resulting value if it is true, and lastly give the resulting value if the condition is false.  For example:
</p>
<p>
<strong> if([weight] &gt; 100, 44, 11)</strong>
</p>
<p>
In this example, if the value of the field 'weight' is greater than 100, then it will give a value of 44, but if 'weight' is less than or equal to 100, it will give 11 as the result.
</p>
<p>
IF statements may be used inside other IF statements (“nested”). Other advanced functions (described above) may also be used inside IF statements.
</p>
<b><font color="#600000">Q: I created a calculated field after I entered data on a form, and it doesn’t look like it’s working. Why not? </font></b>
<p>
If you add a calculated field where data already exist in a form, you must resave the form for each existing record for the calculation to be performed.
</p>
<b><font color="#600000">Q: Why is my advanced calculation not working? </font></b>
<p>
The equation may not be formatted correctly. You may try troubleshooting the equation by simplifying the equation first and then add functionality in steps as you test.
</p>
<p>
Another way to troubleshoot is to click “view equation”. All the variables you are referencing will be listed. If they are not, you will need to check and confirm the variable names.
</p>
<b><font color="#600000">Q: Can I create a calculation that returns text as a result (Ex: "True" or "False")?</font></b>
<p>
No.  Calculations can only result in numbers.  You could indicate "1" = True and "0" = False.
</p>
<b><font color="#600000">Q: Can I create calculations and use branching logic to hide the values to the data entry personnel and/or the survey participants?</font></b>
<p>
If the calculations result in a value (including "0"), the field will display regardless of branching logic.  You can only hide calc fields if you include conditional logic and enter the "false" statement to result in null:  " " or "NaN".  For example:  if([weight] &gt; 100, 44, "NaN")   Then the field will remain hidden (depending on branching logic) unless the calculation results in a value.
</p>
<h3 id="BranchingLogic">Branching Logic<a class="anchor" href="#BranchingLogic" title="Link to this section"> ¶</a></h3>
<b><font color="#600000">Q: What is branching logic? </font></b>
<p>
Branching Logic may be employed when fields in the database need to be hidden during certain circumstances. For instance, it may be best to hide fields related to pregnancy if the subject in the database is male. If you wish to make a field visible ONLY when the values of other fields meet certain conditions (and keep it invisible otherwise), you may provide these conditions in the Branching Logic section in the Online Designer (shown by the double green arrow icon), or the Branching Logic column in the Data Dictionary.
</p>
<p>
For basic branching, you can simply drag and drop field names as needed in the Branching Logic dialog box in the Online Designer. If your branching logic is more complex, or if you are working in the Data Dictionary, you will create equations using the syntax described below.
</p>
<p>
In the equation you must use the project variable names surrounded by <strong>[ ]</strong> brackets. You may use mathematical operators (=,&lt;,&gt;,&lt;=,&gt;=,&lt;&gt;) and Boolean logic (and/or).  You may nest within many parenthetical levels for more complex logic. 
</p>
<p>
You must <strong>ALWAYS</strong> put single or double quotes around the values in the equation UNLESS you are using &gt; or &lt; with numerical values.
</p>
<p>
The field for which you are constructing the Branching Logic will ONLY be displayed when its equation has been evaluated as TRUE. Please note that for items that are coded numerically, such as dropdowns and radio buttons, you will need to provide the coded numerical value in the equation (rather than the displayed text label). See the examples below.
</p>
<table class="wiki">
<tbody><tr><td>[sex] = "0"</td><td> display question if sex = female; Female is coded as 0, Female
</td></tr><tr><td>[sex] = "0" and [given_birth] = "1" </td><td> display question if sex = female and given birth = yes; Yes is codesd as 1, Yes
</td></tr><tr><td>([height] &gt;= 170 or [weight] &lt; 65) and [sex] = "1" </td><td> display question if (height is greater than or equal to 170 OR weight is less than 65) AND sex = male; Male is coded as 1, Male
</td></tr><tr><td>[last_name] &lt;&gt; "" </td><td> display question if last name is not null (aka if last name field has data)
</td></tr></tbody></table>
<p>
<br>
</p>
<b><font color="#600000">Q: Is branching logic for checkboxes different? </font></b>
<p>
Yes, special formatting is needed for the branching logic syntax in 'checkbox' field types. For checkboxes, simply add the coded numerical value inside () parentheses after the variable name:  
</p>
<p>
<strong>[variablename(code)]</strong>
</p>
<p>
To check the value of the checkboxes:
</p>
<p>
'1' = checked 
</p>
<p>
'0' = unchecked
</p>
<p>
 
See the examples below, in which the 'race' field has two options coded as '2' (Asian) and '4' (Caucasian):
</p>
<table class="wiki">
<tbody><tr><td>[race(2)] = "1" </td><td>display question if Asian is checked
</td></tr><tr><td>[race(4)] = "0" </td><td>display question if Caucasian is unchecked
</td></tr><tr><td>[height] &gt;= 170 and ([race(2)] = "1" or [race(4)] = "1") </td><td> display question if height is greater than or equal to 170cm and Asian or Caucasian is checked
</td></tr></tbody></table>
<p>
<br>
</p>
<b><font color="#600000">Q: Can you program branching logic using dates? </font></b>
<p>
The &gt; and &lt; operators were really only intended to be used for numerical comparison. They can sometimes be used for comparing dates, but it only works for YMD formatted dates (because YMD works well with ordering because of the order of the year, month, and day units in the value - very similar to numerical ordering). It's more of a hack to use it this way though, but it does work. So it will not work successfully with DMY or MDY date fields.
</p>
<p>
For example, if you program Advanced Branching Logic as: 
</p>
<p>
[date] &gt; "06-30-2012" 
</p>
<p>
If you enter a month after June (Ex: 07-01-2013, 07-01-2000) the field will display. If you enter a month prior to June (Ex: 01-01-2013, 01-01-2000) the field will not display. 
</p>
<p>
The format MDY will branch incorrectly because it's only comparing Day and Month and not the Year.  Reformat the [date] field to YMD and update the logic to:
</p>
<p>
[date] &gt; "2012-06-30"
</p>
<p>
And the field will hide/display as expected.
</p>
<p>
<br>
</p>
<b><font color="#600000">Q: Can fields from different FORMS be used in branching logic? </font></b>
<p>
Yes, branching logic may utilize fields either on the current data entry form OR on other forms. The equation format is the same, so no special formatting is required.
</p>
<b><font color="#600000">Q: Can fields from different EVENTS be used in branching logic (longitudinal only)? </font></b>
<p>
Yes, for longitudinal projects (i.e. with multiple events defined), branching logic may utilize fields from other events (i.e. visits, time-points). The branching logic format is somewhat different from the normal format because the unique event name must be specified in the logic for the target event. The unique event name must be prepended (in square brackets) to the beginning of the variable name (in square brackets), i.e. [unique_event_name][variable_name]. Unique event names can be found listed on the project's Define My Event's page on the right-hand side of the events table, in which the unique name is automatically generated from the event name that you have defined. 
</p>
<p>
For example, if the first event in the project is named "Enrollment", in which the unique event name for it is "enrollment_arm_1", then we can set up the branching logic utilizing the "weight" field from the Enrollment event: [enrollment_arm_1][weight]/[visit_weight] &gt; 1. Thus, presuming that this field exists on a form that is utilized on multiple events, it will always perform the branching logic using the value of weight from the Enrollment event while using the value of visit_weight for the current event the user is on.
</p>
<b><font color="#600000">Q:  Is it possible to use branching logic to skip an entire section? </font></b>
<p>
Branching logic must be applied to each field. It cannot be applied at the form or section level. Section headers will be hidden *only* if all fields in that section are hidden.
</p>
<b><font color="#600000">Q:  My branching logic is not working when I preview my form. Why not? </font></b>
<p>
Simply previewing a form within the Online Designer will display all questions. In order to test the functionality of your branching logic (and calculated fields), you must enter new records and enter test data directly into your forms.
</p>
<b><font color="#600000">Q: Why does REDCap slow down or freeze and display a message about a javascript problem when I try to use branching logic syntax or Drag-N-Drop Logic builder in a longitudinal project with over 1000 fields? </font></b>
<p>
You are encountering a limitation that stems from having a lot of fields especially multiple choice fields in your project.   If a good number of your fields involve multiple choices then the number of choices that the Drag-N-Drop Logic Builder has to load into the pop-up really gets high. So just having a lot of fields with several choices each can slow down the system.  The performance is further affected because REDCap uses javascript (powered by the user's browser) to do the drag-n-drop and also to process the conversion of the advanced syntax to the drag-n-drop method (if you decide to switch methods within the pop-up). 
</p>
<p>
The slower your computer and the slower your browser (Internet Explorer is the worst, especially versions 6 and 7), then the slower the drag-n-drop method will be.  Chrome is much faster at handling Javascript than other browsers and is recommended.  The only other option is to use the data dictionary for building your branching logic. 
</p>
<b><font color="#600000">In Internet Explorer 8, why is the branching logic in a REDCap survey project adversely affected by variable names in which words like return and continue have been used?</font></b>
<p>
Words like case, class, continue, new, return, submit, and enum are used in javascript. An error will be returned if branching logic is applied to a field with a variable name in which one or more of these words is present. 
</p>
<p>
From REDCap 4.3.0 onward, warnings have been added to alert users who use any of the IE-reserved field names such as return.  "New" and "return" have been added as reserved variable names in 4.3.0.  In 4.3.1 the words "continue", "case", "class", and "enum" have been added.  So if the user tries to create a variable name that uses one of those words, REDCap will require him or her to change it.  The words "catch" and "throw" may also cause errors with some versions of Internet explorer.
</p>
<p></p><h2 id="matrix"><font color="#600000">Matrix Fields</font><a class="anchor" href="#matrix" title="Link to this section"> ¶</a></h2>
<b><font color="#600000">Q: What is a matrix of fields in REDCap?</font></b>
<p></p><p>
REDCap can display a matrix group of fields in either Single Answer format (i.e. radio buttons) or Multiple Answer format (i.e. checkboxes). A matrix allows you to display a group of similar multiple choice fields in a very compact area on a page. This makes data entry forms and surveys much shorter looking. Using matrix fields is especially desirable on surveys because survey respondents are much less likely to leave a survey uncompleted if the survey appears shorter, as opposed to looking very long, which can feel daunting to a respondent. So having compact sections of questions can actually improve a survey's response rate. A matrix can have as many rows or columns as needed. Although the more choices you have, the narrower each choice column will be. Any field in a matrix can optionally have its own branching logic and can be set individually as a required field.  A matrix can also optionally have a section header.
</p>
<p>
(Below is a general example of a common matrix layout. A matrix of fields will look slightly different in REDCap than here on the Help page.)
</p>
<p>
<strong>Rate the following ice cream flavors:</strong>
</p>
<table class="wiki">
<tbody><tr><td> </td><td>Hate it</td><td>Dislike it</td><td>Indifferent</td><td>Like it</td><td>Love it
</td></tr><tr><td>Chocolate</td><td> </td><td> </td><td> </td><td> </td><td> 
</td></tr><tr><td>Butter Pecan</td><td> </td><td> </td><td> </td><td> </td><td> 
</td></tr><tr><td>Vanilla</td><td> </td><td> </td><td> </td><td> </td><td> 
</td></tr></tbody></table>
<p>
<br>
</p>
<b><font color="#600000">Q: How do I create a matrix of fields using the Online Designer?</font></b>
<p>
Navigate to the Online Designer and click the "Add Matrix of Fields" button that will appear either above or below each field. It will open up a pop-up where you can set up each field in the matrix. You can supply the field label and variable name for each field in the matrix, and you may also designate any as a required field. You have the option to display a section header above the matrix. You will also need to set the answer format for the matrix, either Single Answer (Radio Buttons) or Multiple Answers (Checkboxes), and then the matrix choice columns. Setting up the choices is exactly the same as for any normal multiple choice field in the Online Designer by providing one choice per line in the text box. Lastly, you will need to provide a matrix group name for your matrix of fields. The matrix group name is merely a tag that is used to group all the fields together in a single matrix group. The matrix group name can consist only of lowercase letters, numbers, and underscores, and the group name must not duplicate any other matrix group name in the project. Once you have provided all the requisite information for the matrix, click the Save button and the matrix will be created and displayed there with your other fields in the Online Designer.
</p>
<b><font color="#600000">Q: How do I create a matrix of fields using the Data Dictionary?</font></b>
<p>
In a data dictionary, creating a matrix of fields is as easy as creating any regular radio button field or checkbox field. Create your first field in the matrix as either a radio or checkbox field type (since matrix fields can only be either of these) by adding it as a new row in the data dictionary. You must provide its variable name and form name (as usual), then set its field type as either "radio" or "checkbox". Then set its field label in column E, its multiple choice options in column F, and then lastly in column P you must provide a Matrix Group Name. (The matrix group name is how REDCap knows to display these fields together as a matrix. Without a matrix group name, REDCap will merely display the fields separately as normal radio buttons or checkboxes.) The matrix group name is merely a tag that is used to group all the fields together in a single matrix group. The matrix group name can consist only of lowercase letters, numbers, and underscores, and the group name must not duplicate any other matrix group name in the project. After you have created your first field for the matrix and have given it a matrix group name, you may now create the other fields in the matrix in the rows directly below that field. (To save time, it is probably easiest to simply copy that row and paste it as the next immediate row in the Data Dictionary. Then you only need to modify the variable name and label for the new row.) Once you have created all your fields for the matrix, you can upload your data dictionary on the "Data Dictionary Upload" page in your REDCap project, and those fields will be displayed as a matrix on your data collection instrument. NOTE: All fields in a matrix must follow the following rules: 1) must be either a "radio" or "checkbox" field type, 2) must have the *exact* same choices options in column F, 3) must have the same matrix group name in column P. If these requirements are not met, the "Upload Data Dictionary" page will not allow you to upload your data dictionary until these errors are fixed.
</p>
<b><font color="#600000">Q: How do I convert existing non-matrix multiple choice fields into a matrix of fields?</font></b>
<p>
Any existing group of radio button fields or checkbox fields in a REDCap project might possibly be converted into a matrix of fields. In order for fields to be grouped together into a matrix, the following things are required: 1) those fields must all be a Radio Button field or all be a Checkbox field, 2) they must have the *exact* same multiple choice options (same option label AND same raw coded value), and 3) they must all be adjacent to each other on the same data collection instrument (or if not, they can be moved first so that they are adjacent). A matrix can be created only if those three conditions are met. The conversion of regular checkbox/radio fields into a matrix of fields cannot be done in the Online Designer but only using the Data Dictionary. To accomplish this, you will first need to download the existing data dictionary for the project, which can be done on the "Upload Data Dictionary" page. Secondly, go to column P (i.e. Matrix Group Name) and provide *every* field that you wish to be in the matrix with a matrix group name. The matrix group name is merely a tag that is used to group all the fields together in a single matrix group. The matrix group name can consist only of lowercase letters, numbers, and underscores, and the group name must not duplicate any other matrix group name in the project. The group name is not ever displayed on the form/survey during data entry, but is used only for design and organizational purposes. The matrix group name can be any value (even an arbitrary value), but it may be helpful to name it something related to the fields in the group (e.g. "icecream" if all the matrix fields are about ice cream). Once you have added the matrix group name in column P for each field, you can upload your data dictionary on the "Data Dictionary Upload" page in your REDCap project, and those fields will now be displayed as a matrix on your data collection instrument instead of separate fields. 
</p>
<p></p><h2 id="copydci"><font color="#600000">Copy / Share Data Collection Instruments </font><a class="anchor" href="#copydci" title="Link to this section"> ¶</a></h2>
<b><font color="#600000">Q: How can I copy an instrument within a project? </font></b>
<p></p><p>
There is no "Copy Form" button in REDCap.  You can duplicate the form by downloading the data dictionary, copying the relevant rows, changing the name of the form and the variable names on the new rows, and uploading the form.
</p>
<b><font color="#600000">Q: How can I copy instruments from one project to another?</font></b>
<p>
You can do this by downloading the data dictionary from both projects.  You can then copy and paste the fields in the forms you want from one data dictionary to the other. You can do the same for data.  Just export those fields from one and then import into the other after you have uploaded the revised data dictionary.
</p>
<p>
<br>
</p>
<h1 id="dataentry"><font color="#600000">Data Entry</font><a class="anchor" href="#dataentry" title="Link to this section"> ¶</a></h1>
<b><font color="#600000">Q: Starting in v5.0, what is the Record Status Dashboard?</font></b>
<p>
This is a table listing all existing records/responses and their status for every data collection instrument (and for a longitudinal project, for every event).  When viewing this page, form-level privileges are utilized (i.e. cannot see a form's status if user does not have access to that form), and if the user belongs to a Data Access Group, they will only be able to view the records that belong to their group.
</p>
<p>
Note: Since projects may now have many surveys, REDCap no longer displays the Survey Response Summary on the Project Home page.
</p>
<b><font color="#600000">Q: How do I enter / view my data? </font></b>
<p>
To enter or view individual records, you can navigate to the "Data Collection" section on the left menu bar.  Depending on your project type, you will see "Add or View Survey Responses", a listing of your form names, or a "Data Entry" icon.  These options will navigate you to the drop down record lists so you can select or add a new record/response.  
</p>
<p>
You can also use the <strong>Report Builder</strong> tool to view your data. The Report Builder tool serves as the search engine of a REDCap database. The Report Builder queries the database in real time and displays the resulting data in table format. Variables are listed in columns and individual records are displayed in rows.
</p>
<p>
You can also use the <strong>Graphical View &amp; Stats</strong> tool to view your data.  he Plots tab displays graphical representations for all numerical and categorical variables and provides links for cleaning notable data (missing, highest, lowest values). The Descriptive Stats tab displays descriptive statistics for all variables. 
</p>
<b><font color="#600000">Q: Can I edit survey responses?</font></b>
<p>
Yes, survey responses CAN be edited so long as you have been given user privileges to do so (via the User Rights page). Once you have been given user privileges to edit survey responses, you will see an Edit Response button at the top of the data entry form when viewing the response (the response will be initially read-only). After clicking that button, the response will become editable as normal. (NOTE: Some institutions running REDCap may choose not to enable this feature for their users, so if a checkbox is not seen next to the survey/form rights for that survey on the User Rights page, then this feature has not been enabled and thus cannot be utilized.)
</p>
<b><font color="#600000">Q: Do I need to select the record number again each time I change data entry forms? </font></b>
<p>
No. To navigate between forms within a given record, select the colored dots indicating form status (i.e. incomplete, unverified, and complete) which appear to the left of the form name when a record is open. Note that moving to a new form by selecting the form status indicator will close the current form without saving entries. In order to save entries, select the <strong>“Save and Continue”</strong> button located at the bottom of the form before using the form status indicators to move to a new form. Alternatively, you can select the <strong>“Save and go to Next Form”</strong> button if you wish to move to the next form for the current record.  
</p>
<b><font color="#600000">Q:  Is there a way to delete a record? </font></b>
<p>
Yes. Navigate to the User Rights page and assign yourself the <strong>"Delete Record"</strong> option. The Delete button will appear at the bottom of your forms. This deletes ALL forms (or survey entries) for the record. Once you delete the data, there is no way to get it back!
</p>
<b><font color="#600000">Q: For calculated fields, sometimes the value pops up when you enter data for the questions and sometimes the value may not appear until you save the form. Is there any reason it's doing this?</font></b>
<p>
Depending on which internet browser you are using, sometimes the calc fields are calculated during data entry. However, these are just preliminary calculations. <strong>You must click the save button</strong> for the system to correctly calculate the expression and commit the data to the database.
</p>
<b><font color="#600000">Q: In a longitudinal study where the first form is a demographic data collection form is there any way to force the first form to be completed before proceeding to subsequent forms?
</font></b>
<p>
You can use branching logic to hide the fields on the later forms and add a section header that explains why no fields are present in each form when the branching logic calls for the form to be 'blank'.  The forms that follow the demographic form will still be accessible but fields will be viewable only if a particular field on the demographic form is completed or marked 'Yes'. 
</p>
<h1 id="survey"><font color="#600000">Surveys: Invite Participants</font><a class="anchor" href="#survey" title="Link to this section"> ¶</a></h1>
<b><font color="#600000">Q: How do I administer my survey?</font></b>
<p>
Navigate to the "Invite Participants" page.  You have two options to administer your survey:
</p>
<p>
<strong>Public Survey URL</strong>:  This is a single survey link for your project which all participants will click on.  This link can be copy and pasted into the body of an email message in your own email client. It can also be posted to web pages.  
</p>
<p>
<strong>Participant Contact List</strong>:  This option allows you to send emails and track who responds to your survey. It is also possible to identify an individual's survey answers by providing an Identifier for each participant.
</p>
<b><font color="#600000">Q: Starting in v5.0, How do I manage multiple surveys Participant Contact Lists?</font></b>
<p>
For for projects with multiple surveys, there will be one participant list per survey.  You’ll be able to select the survey specific to survey name and event (longitudinal projects).
</p>
<p>
Participant List may be used to: (1) Send emails to many participants at once (2) Send individual survey invites directly from a data entry form 
</p>
<p>
The Public Survey Link and Participant List have been separated onto different pages within Manage Survey Participants because they each represent a different method for inviting survey participants. 
</p>
<p>
Note: To be able to add participants, the first data collection instrument must be a survey. All participants of all surveys must be added to the first survey of the project.
</p>
<b><font color="#600000">Q: Can email distribution lists or group email accounts be added to the Invite Participants Email Contact List to send survey invitations?  </font></b>
<p>
You should not use REDCap's Participant Email Contact list with group email addresses or distribution lists.  The emailed invitations send only 1 unique
survey link per email address; therefore, only the first person in the distribution group who clicks on the email link will be able to complete the survey.
</p>
<p>
For group distribution lists, you can email the general survey link provided at the top of the "Invite Participants" page directly from your email account.
</p>
<p>
Or you can add each individual email address from the distribution list to the Participant Contact list.  You can copy/paste the emails from a list (word or excel) into REDCap.
</p>
<p>
The advantages of using REDCap's Participant Contact list and the individual emails is that REDCap will track responders and non-responders for you.
You'll be able to email only non-responders if you want to send a reminder.  With the general distribution email, you won't be able to track responses and participants will have the potential to complete the survey more than once.
</p>
<b><font color="#600000">Q: What is the “Start Over” feature for survey participants invited via Participant List?</font></b>
<p>
Starting in v4.1, the survey page now allows participants invited via the Participant List to start over and re-take the entire survey if they return to the survey when they did not complete it fully, but the “Start Over” feature is only available if the Save &amp; Return Later feature is disabled or if it is enabled and the participant did not click the Save &amp; Return Later button. In previous versions, if the participant left the survey and did not use the Save &amp; Return Later feature (whether or not the Save &amp; Return Later feature was enabled), then they would not be allowed to return to the survey at all, in which their response would then be counted as “incomplete”/”partial”.
</p>
<b><font color="#600000">Q: Is there a limit to the time that a participant has to complete a survey once they have clicked on the survey link?</font></b>
<p>
There is a time limit of 24 hours per page.  If a participant selects the "!Save&amp;Return" option, their link is active until the project admin closes/de-activates the survey.
</p>
<b><font color="#600000">Q: If I'm using the Participant Contact List to email survey invites and our mail server fails, REDCap may still return success messages even when no emails have been sent.  Can the error reporting be improved when sending emails?</font></b>
<p>
In general, the error reporting for sending emails probably cannot be improved.  The email sending process is embedded in a chain of events that involves different systems.  The REDCap application is far removed from some of the other systems and therefore cannot always know if a system at the delivery end sent the email.
</p>
<b><font color="#600000">Q: Starting in v5.0, What is the Survey Invitation Log?</font></b>
<p>
This log list participants who (1) Have been scheduled to receive invitation Or (2) Have received invitation Or (3) Have responded to survey.
</p>
<p>
You can filter to review your participants response statuses.
</p>
<p>
<br>
</p>
<h1 id="AutomatedInvitations"><font color="#600000">Automated Survey Invitations</font><a class="anchor" href="#AutomatedInvitations" title="Link to this section"> ¶</a></h1>
<p>
For any survey in your REDCap project, you may define your conditions for Automated Survey Invitations that will be sent out for a specified survey. This is done on the Online Designer page. Automated survey invitations may be understood as a way to have invitations sent to your survey participants, but rather than sending or scheduling them manually via the Participant List, the invitations can be scheduled to be sent automatically (i.e. without a person sending the invitation) based upon specific conditions, such as if the participant completes another survey in your project or if certain data values for a record are fulfilled. 
</p>
<b><font color="#600000">Q: What mathematical operations can be used in the logic for Automated Survey Invitations? </font></b>
<table class="wiki">
<tbody><tr><td>+        </td><td>Add
</td></tr><tr><td>-        </td><td>Subtract
</td></tr><tr><td>*        </td><td>Multiple
</td></tr><tr><td>/        </td><td>Divide
</td></tr></tbody></table>
<p>
<br>
</p>
<b><font color="#600000">Q. What functions can be used in the logic for Automated Survey Invitations?</font></b>
<p>
The Automated Survey Invitations functionality can perform many advanced functions. A complete list is listed below. (NOTE: These functions are very similar - and in some cases identical - to functions that can be used for calculated fields.)
</p>
<table class="wiki">
<tbody><tr><td> <strong>Function</strong> </td><td> <strong>Type of calculation</strong> </td><td> <strong>Notes / examples</strong>
</td></tr><tr><td> round(number,decimal places) </td><td> <strong>Round</strong> </td><td> If the "decimal places" parameter is not provided, it defaults to 0. E.g. To round 14.384 to one decimal place:  round(14.384,1) will yield 14.4 
</td></tr><tr><td> roundup(number,decimal places) </td><td> <strong>Round Up</strong> </td><td> If the "decimal places" parameter is not provided, it defaults to 0. E.g. To round up 14.384 to one decimal place:  roundup(14.384,1) will yield 14.4
</td></tr><tr><td> rounddown(number,decimal places) </td><td> <strong>Round Down</strong> </td><td> If the "decimal places" parameter is not provided, it defaults to 0. E.g. To round down 14.384 to one decimal place:  rounddown(14.384,1) will yield 14.3
</td></tr><tr><td> sqrt(number) </td><td> <strong>Square Root</strong> </td><td> E.g. sqrt([height]) or sqrt(([value1]*34)/98.3)
</td></tr><tr><td> (number)^(exponent) </td><td><strong>Exponents</strong> </td><td> Use caret ^ character and place both the number and its exponent inside parentheses:  For example, (4)^(3) or ([weight]+43)^(2)
</td></tr><tr><td> abs(number) </td><td> <strong>Absolute Value</strong> </td><td> Returns the absolute value (i.e. the magnitude of a real number without regard to its sign). E.g. abs(-7.1) will return 7.1 and abs(45) will return 45. 
</td></tr><tr><td> min(number,number,...) </td><td> <strong>Minimum</strong> </td><td> Returns the minimum value of a set of values in the format min([num1],[num2],[num3],...). NOTE: All blank values will be ignored and thus will only return the lowest numerical value. There is no limit to the amount of numbers used in this function.
</td></tr><tr><td> max(number,number,...) </td><td> <strong>Maximum</strong> </td><td> Returns the maximum value of a set of values in the format max([num1],[num2],[num3],...). NOTE: All blank values will be ignored and thus will only return the highest numerical value. There is no limit to the amount of numbers used in this function. 
</td></tr><tr><td> mean(number,number,...) </td><td> <strong>Mean</strong> </td><td> Returns the mean (i.e. average) value of a set of values in the format mean([num1],[num2],[num3],...). NOTE: All blank values will be ignored and thus will only return the mean value computed from all numerical, non-blank values. There is no limit to the amount of numbers used in this function. 
</td></tr><tr><td> median(number,number,...) </td><td> <strong>Median</strong> </td><td> Returns the median value of a set of values in the format median([num1],[num2],[num3],...). NOTE: All blank values will be ignored and thus will only return the median value computed from all numerical, non-blank values. There is no limit to the amount of numbers used in this function.  
</td></tr><tr><td> sum(number,number,...) </td><td> <strong>Sum</strong> </td><td> Returns the sum total of a set of values in the format sum([num1],[num2],[num3],...). NOTE: All blank values will be ignored and thus will only return the sum total computed from all numerical, non-blank values. There is no limit to the amount of numbers used in this function.  
</td></tr><tr><td> stdev(number,number,...) </td><td> <strong>Standard Deviation</strong> </td><td> Returns the standard deviation of a set of values in the format stdev([num1],[num2],[num3],...). NOTE: All blank values will be ignored and thus will only return the standard deviation computed from all numerical, non-blank values. There is no limit to the amount of numbers used in this function.  
</td></tr><tr><td> isnumber(value) </td><td> <strong>Is value a number?</strong> </td><td> Returns a boolean (true or false) for if the value is an integer OR floating point decimal number. 
</td></tr><tr><td> isinteger(value) </td><td> <strong>Is value an integer?</strong> </td><td> Returns a boolean (true or false) for if the value is an integer (whole number without decimals). 
</td></tr></tbody></table>
<p>
<br>
</p>
<b><font color="#600000">Q: Can I use conditional IF statements in the logic for Automated Survey Invitations? </font></b>
<p>
Yes. You may use IF statements (i.e. IF/THEN/ELSE statements) by using the function <strong>if (CONDITION, value if condition is TRUE, value if condition is FALSE)</strong>
</p>
<p>
This construction is similar to IF statements in Microsoft Excel. Provide the condition first (e.g. [weight]=4), then give the resulting value if it is true, and lastly give the resulting value if the condition is false.  For example:
</p>
<p>
<strong> if([weight] &gt; 100, 44, 11) &lt; [other_field]</strong>
</p>
<p>
In this example, if the value of the field 'weight' is greater than 100, then it will give a value of 44, but if 'weight' is less than or equal to 100, it will give 11 as the result.
</p>
<p>
IF statements may be used inside other IF statements (“nested”). Other advanced functions (described above) may also be used inside IF statements.
</p>
<b><font color="#600000">Q: How can I calculate the difference between two date or time fields in my conditional logic for Automated Survey Invitations (this includes time, datetime, and datetime_seconds fields)? </font></b>
<p>
You can calculate the difference between two dates or times by using the datediff function shown below. <strong>IMPORTANT NOTE:</strong> The Automated Survey Invitation datediff function differs from the datediff function used in calculated fields in that the ASI datediff does NOT have a "dateformat" parameter. This is due to the fact that the date format of the dates used in the ASI datediff function does not have to be specified, whereas it does for calc fields.
</p>
<p>
<strong>datediff([date1], [date2], "units", returnSignedValue)</strong>
</p>
<p>
date1 and date2 are variables in your project
</p>
<p>
<strong>units</strong> 
</p>
<table class="wiki">
<tbody><tr><td><strong>"y"</strong> </td><td> years </td><td>  1 year = 365.2425 days 
</td></tr><tr><td><strong>"M"</strong> </td><td> months </td><td> 1 month = 30.44 days 
</td></tr><tr><td><strong>"d"</strong> </td><td>days </td><td> 
</td></tr><tr><td><strong>"h"</strong> </td><td>hours </td><td> 
</td></tr><tr><td><strong>"m"</strong> </td><td>minutes </td><td> 
</td></tr><tr><td><strong>"s"</strong> </td><td>seconds </td><td> 
</td></tr></tbody></table>
<p>
<strong>returnSignedValue</strong> 
</p>
<table class="wiki">
<tbody><tr><td><strong>false</strong> </td><td>(default) 
</td></tr><tr><td><strong>true</strong> </td><td> 
</td></tr></tbody></table>
<ul><li>The parameter returnSignedValue denotes the result to be signed or unsigned (absolute value), in which the default value is "false", which returns the absolute value of the difference. For example, if [date1] is larger than [date2], then the result will be negative if returnSignedValue is set to true. If returnSignedValue is not set or is set to false, then the result will ALWAYS be a positive number. If returnSignedValue is set to false or not set, then the order of the dates in the equation does not matter because the resulting value will always be positive (although the + sign is not displayed but implied). 
</li></ul><p>
Examples:
</p>
<table class="wiki">
<tbody><tr><td><strong>datediff([dob],[date_enrolled],"d")</strong> </td><td>Yields the number of days between the dates for the date_enrolled and dob fields 
</td></tr><tr><td><strong>datediff([dob],"05-31-2007","h",true)</strong> </td><td> Yields the number of hours between May 31, 2007, and the date for the dob field. Because returnSignedValue is set to true, the value will be negative if the dob field value is more recent than May 31, 2007. 
</td></tr></tbody></table>
<p>
<br>
</p>
<b><font color="#600000">Q: Do the two date fields used in the datediff function both have to be in the same date format (YMD, MDY, DMY)? </font></b>
<p>
No, they do not. Thus, an MDY-formatted date field can be used inside a datediff function with a YMD-formatted date field, and so on.
</p>
<b><font color="#600000">Q: Can a date field be used in the datediff function with a datetime or datetime_seconds field? </font></b>
<p>
Yes. If a date field is used with a datetime or datetime_seconds field, it will calculate the difference by assuming that the time for the date field is 00:00 or 00:00:00, respectively. Consequently, this also means that, for example, an MDY-formatted DATE field can be used inside a datediff function with a YMD-formatted DATETIME field.
</p>
<b><font color="#600000">Q: Can I base my Data Quality datediff function off of today's date? </font></b>
<p>
Yes, for example, you can indicate "age" as: rounddown(datediff("today",[dob],"y")). NOTE: The "today" variable CAN be used with date, datetime, and datetime_seconds fields, but NOT with time fields. (This is different from datediff in calc fields, in which the "today" variable can ONLY be used with date fields and NOT with time, datetime, or datetime_seconds fields.)
</p>
<h2 id="survey_prefill"><font color="#600000">How to pre-fill survey questions</font><a class="anchor" href="#survey_prefill" title="Link to this section"> ¶</a></h2>
<b><font color="#600000">Q: Can I pre-fill survey questions so that some questions already have values when the survey initially loads?</font></b>
<p>
Yes, this can be done so that when a survey participant initially loads the survey page, it already has questions pre-filled with values. This can be done two different ways as seen below. Please note that even if a survey is a multi-page survey, questions on later pages can also be pre-filled using these methods. <i>NOTE: These two methods are likely to be only used for public survey links (as opposed to using the Participants List). This is because there is not a real opportunity to modify the survey links sent to participants via the Participants List because REDCap automatically sends them out as-is.</i>
</p>
<p>
<strong>1) Append values to the survey link:</strong>
The first method is for pre-filling survey questions by simply appending URL parameters to a survey link. The format for adding URL parameters is to add an ampersand (&amp;) to the end of the survey link, followed by the REDCap variable name of the question you wish to pre-fill, followed by an equals sign (=), then followed by the value you wish to pre-fill in that question. For example, if the survey URL is <i>https://redcap.vanderbilt.edu/surveys/?s=dA78HM</i>, then the URL below would pre-fill "Jon" for the first name question, "Doe" for last name, set the multiple choice field named "gender" to "Male" (whose raw/coded value is "1"), and it would check off options 2 and 3 for the "race" checkbox. <strong>WARNING: This method is not considered secure for transmitting confidential or identifying information (e.g. SSN, name), even when using over SSL/HTTPS. If you wish to pre-fill such information, it is highly recommended to use method 2 below.</strong>
</p>
<pre class="wiki">https://redcap.vanderbilt.edu/surveys/?s=dA78HM&amp;first_name=Jon&amp;last_name=Doe&amp;gender=1&amp;race___2=1&amp;race___3=1
</pre><p>
<strong>2) Submit an HTML form to a REDCap survey from another webpage:</strong>
The second method is for pre-filling survey questions by posting the values from another webpage using an HTML form. This webpage can be *any* webpage on *any* server. See the example below. The form's "method" must be "post" and its "action" must be the survey link URL. The form's submit button must have the name "__prefill" (its value does not matter). Each question you wish to pre-fill will be represented as a field in the form, in which the field's "name" attribute is the REDCap variable name and its value is the question value you wish to pre-fill on the survey page. The form field may be an input, textarea, or select field. (The example below shows them all as hidden input fields, which could presumably have been loaded dynamically, and thus do not need to display their value.) If submitted, the form below would pre-fill "Jon" for the first name question, "Doe" for last name, set the multiple choice field named "gender" to "Male" (whose raw/coded value is "1"), and it would check off options 2 and 3 for the "race" checkbox. In this example, the only thing that would be seen on the webpage is the "Pre-fill Survey" button.
</p>
<pre class="wiki">&lt;!-- Other webpage content goes here --&gt;
&lt;form method="post" action="https://redcap.vanderbilt.edu/surveys/?s=dA78HM"&gt;
&lt;input type="hidden" name="first_name" value="Jon"&gt;
&lt;input type="hidden" name="last_name" value="Doe"&gt;
&lt;input type="hidden" name="gender" value="1"&gt;
&lt;input type="hidden" name="race___2" value="1"&gt;
&lt;input type="hidden" name="race___3" value="1"&gt;
&lt;input type="submit" name="__prefill" value="Pre-fill Survey"&gt;
&lt;/form&gt;
&lt;!-- Other webpage content goes here --&gt;
</pre><h2 id="doubledataentry"><font color="#600000">Double Data Entry</font><a class="anchor" href="#doubledataentry" title="Link to this section"> ¶</a></h2>
<b><font color="#600000">Q: What is the Double Data Entry module?</font></b>
<p>
As a preventive measure, REDCap prevents users from entering duplicate records. However, some projects may need to enter data twice for each record as a means of ensuring quality data collection by later comparing the records. This can be done using the Double Data Entry Module. When the module is enabled, REDCap collects data differently than normal. It allows you to designate any two database users as Data Entry Person 1 and Data Entry Person 2, which is done on the User Rights page. Once designated, either of these two users can begin entering data independently, and they will be allowed to create duplicate records. They will not be able to access each other's data, and only normal users (called Reviewers) will be able to see all the data in the database. Once each designated data entry person has created an instance of the same record, both instances can then be compared side by side on the Data Comparison Tool page and merged into a third instance.
</p>
<b><font color="#600000">Q: How do you set up Double Data Entry?</font></b>
<p>
The Double Data Entry (DDE) module that needs to be enabled by a REDCap administrator prior to any data is collected in the database. This module allows two database users to be set as Data Entry Person 1 and Data Entry Person 2 (using User Rights page), and allows them to create records with the same name and enter data for the same record without seeing one another's data. Only one person at a time can be set as person 1 or 2. All other users are considered Reviewers. Reviewers have the ability to merge a record created by person 1 and 2 after viewing differences and adjudicating those differences using the Data Comparison Tool, thus creating a third record in the set.
</p>
<p>
<strong>It is sometimes recommended to use the Data Access Groups over the actual DDE module to implement a form of double data entry.</strong> The advantages of using DAGs include allowing an unlimited number of users to be in a group and enter data, to utilize the Data Import Tool, and to access all Applications pages. Discrepancies between double-entered records can be resolved by a “reviewer” (i.e. someone not in a group) using the Data Comparison Tool. However, two records can ONLY be merged together when using the DDE module. So if it is necessary for a third party "reviewer" to merge the two records into a third record, then in that case the DDE module would be advantageous over using DAGs.
</p>
<b><font color="#600000">Q: In a project using the double data entry module, can I make changes in one of the merged records?</font></b>
<p>
A record can be merged only once. For example records "AA--1" and "AA--2" merge to create record "AA".
</p>
<p>
After merging, the user in role Data Entry Person One can still make changes and only record "AA--1" will be changed.
</p>
<p>
The person in role Data Entry Person Two can make changes and only record "AA--2" will be changed.
</p>
<p>
A person in role Reviewer can view all three records that can be edited like any record in a database. The reviewer can use the Data Comparison Tool to see discrepancies in the three versions. The reviewer may then access the merged record and add data. What she adds in the "AA" record will not be added to either "AA--1" or "AA--2" unless she opens them and makes the addition. She can see, and make manual changes, but cannot use "merge" again.
</p>
<p>
An alternative is to delete the merged version "AA", let the Data Entry people make changes themselves and then merge the records. 
</p>
<b><font color="#600000">Q: As a double data entry Reviewer, how can I make sure the Data Entry personnel do not modify their records after I create a final merged record?</font></b>
<p>
If you do not want data entry personnel to update records after a review and merge, you can enable the User Right &gt; "Lock/Unlock Records" for the Reviewers.  The Reviewers can then lock any records prior to a merge.  The data entry personnel without this right will not be able to make updates to the locked record without first contacting the Reviewer.
</p>
<p>
<br>
</p>
<h1 id="applications"><font align="center" color="#600000">Applications</font><a class="anchor" href="#applications" title="Link to this section"> ¶</a></h1>
<h2 id="export"><font color="#600000">Data Export Tool</font><a class="anchor" href="#export" title="Link to this section"> ¶</a></h2>
<b><font color="#600000">Q: Can I export data in Development to practice this function?</font></b>
<p>
Yes. <strong>It is recommended that you export your test data for review prior to moving your project into Production</strong>. In development, all the applications function like they would in Production; however changes in Production cannot be made in real time. So it's best to make sure your database is tested thoroughly, including the data export.
</p>
<b><font color="#600000">Q:  When exporting data from redcap into SPSS, will the variable codes that you've defined be automatically imported into SPSS (for ex 1, Female  2, Male)? </font></b>
<p>
Yes. REDCap uses the metadata you have defined in your data dictionary to create syntax files for SPSS, SAS, R, and Stata. The Data Export tool includes instructions for linking the exported syntax and data files. Note that SPSS has several variable naming conventions:
</p>
<blockquote>
<p>
•The name MUST begin with a letter.  The remaining characters may be any later, digit, a period or the symbols #, @, _, or $<br>
•Variable names cannot end with a period<br>
•The length of the name cannot exceed 64 bytes (64 characters)<br>
•Spaces and special characters other than the symbols above cannot be used<br>
•No duplicate names are acceptable; each character must be unique<br>
•Reserved keywords cannot be used as variable names (ALL, AND, BY, EQ, GE, GT, LE, LT, NE, NOT, OR, TO, and WITH)<br>
</p>
</blockquote>
<b><font color="#600000">Q: Can I export all my data as PDFs or do I have to download each subject’s PDF individually?</font></b>
<p>
You may export data for all records in a project into a single PDF file.  This option is on the Data Export Tool page.  The file will contain the actual page format as you would see it on the data entry page or survey and includes all data for all records for all data collection instruments.
</p>
<b><font color="#600000">Q: When I increase the font size on my data collection instruments using HTML tags it is not reflected when I print a pdf. Is there any way to increase the font size in the pdf?</font></b>
<p>
No. The pdf prints in standard format and does not print the formats created with the HTML tags.
</p>
<b><font color="#600000">Q: My REDCap project contains non-English/non-Latin characters, but when I export, why aren’t the characters rendering correctly?</font></b>
<p>
 
If you’re using MS Excel, it does not render all languages and characters unless multi-language updates are purchased. The use of OpenOffice.org CALC (free download) application enables you to build the data dictionary, save as .csv and upload to REDCap. CALC will ask you for a character set every time you open a .csv file. Choose "unicode (utf-8)" from the options listed. REDCap does not render UTF8 characters to the PDFs. 
</p>
<b><font color="#600000">Q: How can I ensure that the leading zeros of the id numbers in a database where this data is stored in a text field are retained when the data is exported?</font></b>
<p>
Excel will discard the leading zeros if you open your export file in Excel.  The leading zeros will be retained if you open the file in Notepad.  Rather than opening the file directly in Excel you should open the data into Excel and specify that the column with the leading zeros is a text column.
</p>
<b><font color="#600000">Q:  Is there a way to specify variable lengths for different variable types for example when reading in the csv file into the SAS editor?</font></b>
<p>
When exporting data, the format statements in REDCap's SAS editor specify that text fields have a length of 500 and numeric fields are set to BEST32.  However once you read the data set into SAS you can run a macro that will specify the "best" length for character variables and numeric variables.
 
</p>
<h2 id="import"><font color="#600000">Data Import Tool / API</font><a class="anchor" href="#import" title="Link to this section"> ¶</a></h2>
<b><font color="#600000">Q:  How do I import data from another source? </font></b>
<p>
Data from another source can be imported using the Data Import tool or the API (Application Programming Interface).
</p>
<p>
The Data Import Tool requires that data to be imported is in CSV (comma separated variables) format. The order of the fields or the number of fields being imported does not matter, except that the record identifier (e.g. Subject ID) must be the first field.
</p>
<p>
For more information on the API, contact your REDCap Administrator.
</p>
<b><font color="#600000">Q: What is the REDCap API (Application Programming Interface)?</font></b>
<p>
The REDCap API is an interface that allows external applications to connect to REDCap remotely, and is used for programmatically retrieving or modifying data or settings within REDCap, such as performing automated data imports/exports from a specified REDCap project.  More information about the API can be found on the Project Setup &gt; Other Functionality page.
</p>
<b><font color="#600000">Q: How do I import longitudinal data?</font></b>
<p>
If you are using the Data Import Tool to import longitudinal data, you must divide your data into separate CSV files for each event, and import them individually. REDCap will ask you to specify the event you are importing.
</p>
<p>
<strong>NEW for v4.10.1+</strong> The Data Import Tool now requires you to use the "redcap_event_name" column when importing data. Previously, you selected the event *after* initially uploading the data file, but now you can specify the event beforehand in the file using the unique "redcap_event_name".  You can now also upload multiple event data per subject.
</p>
<p>
The unique "redcap_event_name"s are listed on each project's Define My Events page. 
</p>
<p>
You can insert this field after the unique identifier as the second column or you can add it to the end of your import spreadsheet (last column).
</p>
<p>
  
</p>
<b><font color="#600000">Q: How do I import data for calculated fields?</font></b>
<p>
Data cannot be directly imported into calculated fields. If you are importing data to a field you have set up to calculate a value, follow these steps:
</p>
<p>
1. Temporarily change the field type to text
</p>
<p>
2. Import data
</p>
<p>
3. Change the field type back to a calculated field
</p>
<b><font color="#600000">Q. How do I import form status (Incomplete, Unverified, Complete)?</font></b>
<p>
Form status can be imported into variables named <i>form_name</i>_complete.  The data import template, available on the Data Import Tool page, will contain the appropriate form status variable name for your project forms.  Form status is imported as dropdown field type coded as 
</p>
<table class="wiki">
<tbody><tr><td><strong>0</strong></td><td> Incomplete 
</td></tr><tr><td><strong>1</strong> </td><td> Unverified 
</td></tr><tr><td><strong>2</strong> </td><td> Complete 
</td></tr></tbody></table>
<p>
<br>
</p>
<b><font color="#600000">Q: Why does REDCap display an out of memory message and ask me to break up my file into smaller pieces when I try to upload a 700 KB file using the Import Tool?  Will it help to increase the server's memory limit?
</font></b>
<p>
Memory will always be a limit for the Data Import Tool.  A lot depends on how much data resides in the uploaded CSV file because the Data Import Tool does the validation checking and data processing in memory.  So a 500KB CSV file may be too big to process even though the server memory limit for REDCap might be 256 MB.  A csv file can be pretty small and yet cause a lot of memory to be used if you keep the columns (or rows) for all of the variables, but are only providing data for a few of the variables.  So you'll still have to follow the solution that REDCap gives you.
</p>
<p>
<br>
</p>
<h2 id="file"><font color="#600000">File Repository</font><a class="anchor" href="#file" title="Link to this section"> ¶</a></h2>
<b><font color="#600000">Q:  What is the File Repository? </font></b>
<p>
The File Repository can be used for storing and retrieving project files and documents (ex: protocols, instructions, announcements).  In addition, it stores all data and syntax files when data is export using the Data Export Tool.
</p>
<b><font color="#600000">Q:  Is there any way to organize files in the file repository, such as a folder tree or section headers? </font></b>
<p>
No.  The only way to organize the files is by an alphabetical naming convention.
</p>
<p>
<br>
</p>
<h2 id="userrights"><font color="#600000">User Rights</font><a class="anchor" href="#userrights" title="Link to this section"> ¶</a></h2>
<b><p><font color="#600000">Q: How can I give someone access to my project?</font></p></b>
<p>
If you have rights to the User Rights application, add a new user by entering their user name in the <strong>“New User name”</strong> text box and hit the Tab key. Assign permissions and save changes.
</p>
<p>
<br>
</p>
<b><font color="#600000">Q: What are the User Rights that can be granted/restricted?</font></b>
<table class="wiki">
<tbody><tr><td><strong>User Right</strong></td><td>        <strong>Access</strong></td><td>        <strong>Notes</strong></td><td>        <strong>Potential to Access Protected Health Info (PHI)?</strong>
</td></tr><tr><td>Data Entry Rights        </td><td>Grants user “No Access”, “Read Only”, “View&amp;Edit”, “Edit Survey Responses” rights to the project’s data collection instruments.        </td><td><strong>WARNING:</strong> The data entry rights only pertain to a user's ability to view or edit data on the web page. It has NO effect on what data is included in data exports or downloaded to a device*.          </td><td>YES. If access to a form with PHI is “Read Only” or “View&amp;Edit”, user will be able to view PHI.
</td></tr><tr><td>Manage Survey Participants        </td><td>Grants user access to manage the public survey URLs, participant contact lists, and survey invitation log.</td><td>                </td><td>YES.  Email addresses (PHI) may be listed for the participant contact lists and invitation logs.  Emails can be downloaded to a device.
</td></tr><tr><td>Calendar</td><td>        Grants user access to track study progress and allows user to update calendar events, such as mark milestones, enter ad hoc meetings.        </td><td>In combination with the scheduling module the calendar tool can be used to add, view and update project records which are due for manipulation.          </td><td>YES.  PHI can be entered and viewed in the “notes” field.  Data entered can be printed to PDF and downloaded to a device.
</td></tr><tr><td>Data Export Tool        </td><td>Grants user  “No Access”, “De-identified Only” and “Full Data Set” access  to export all or selected data fields to one of the 5 default programs in REDCap (SAS, SPSS, R, Stata, Excel).  Default Access:  De-Identified;   De-identified access shifts all dates even if they are not marked as identifiers.  Non-validated text fields and note fields (free text) are also automatically removed from export. </td><td> <strong>WARNING:</strong> The de-identified option is contingent upon correctly flagging identifiers in each field.   It is advised to mark all PHI fields as identifiers and restrict export access to “de-identified”. </td><td>YES. PHI can be exported and downloaded to a device. Exporting data is NOT linked to Data Entry Rights.  User with Full Export Rights can export ALL data from all data collection instruments. Please see “Data Export Tool” FAQ for additional info.
</td></tr><tr><td>Data Import Tool </td><td>        Grants user access to download and modify import templates for uploading data directly into the project bypassing data entry forms.  </td><td>        <strong>WARNING:</strong> This will give the user the capability to overwrite existing data.  Blank cells in the data import spreadsheet do not overwrite fields with data.        </td><td> 
</td></tr><tr><td>Data Comparison Tool        </td><td> Grants user access to see two selected records side by side for comparison.</td><td>Extremely helpful when using double data entry.</td><td>YES. PHI can be viewed.  Data can be printed and downloaded to a device.  ALL data discrepancies for all fields in project are displayed and can be downloaded to user with access to this module – NOT linked to Data Entry Rights or Data Export Tool Rights.
</td></tr><tr><td>Logging</td><td>Grants user access to view log of all occurrences of data exports, design changes, record creation, updating &amp; deletion, user creation, record locking, and page views.  This is the audit trail for the project.        </td><td>Useful for audit capability.        </td><td>YES.  ALL data entered, modified and changed is listed in module, can be viewed and downloaded to a device.  
</td></tr><tr><td>File Repository</td><td>        Grants user access to upload, view, and retrieve project files and documents (ex: protocols, instructions, announcements).  In addition, it stores all data and syntax files when data is exported using the Data Export Tool.         </td><td><strong>WARNING:</strong> While users with restricted data export rights will not be able to access saved identified exports, they will be able to view any other sensitive information stored in the file repository such as photos or scanned documents. Limit this privilege to those who should have access to PHI.           </td><td>YES. Depending on Data Export Tool rights, PHI can be downloaded to a device.
</td></tr><tr><td>User Rights        </td><td>Grants user access to change the rights and privileges of all users on a particular project, including themselves.        </td><td><strong>WARNING:</strong> Granting User Rights privileges gives the user the ability to control other users’ project access.  This user should be very trusted and knowledgeable about the project and REDCap.  Giving user rights to team members should be a carefully thought out decision.  The consequences of poor user rights assignments could be damaging to both the security and integrity of your project.  For instance, giving record deletion or project design rights to an unqualified person could result in data loss or database integrity issues.        </td><td>YES.  User can change own User Rights and grant access to any module where PHI can be viewed or downloaded to a device.
</td></tr><tr><td>Data Access Groups        </td><td>Grants user access to create and add users to data access groups.  User should not assign their self to a data access group or they will lose their access to update other users to data access groups.  Therefore, user with this privilege should be able to see all project data regardless of group.  </td><td>For multisite studies this allows the ability to place barriers between sites' data (i.e. group A cannot see, export, or edit group B's data).  </td><td> 
</td></tr><tr><td> Graphical Data View &amp; Stats        </td><td>Grants user access to view simple statistics on each field in the project in real time.  If user does not have access to a data collection instrument, that instrument will not be listed on the page.        </td><td>Outliers can be identified and clicked on which will take you immediately to the record, form and field of the individual with the outlier data.        </td><td>YES. Depending on Data Entry Rights, PHI can be viewed.
</td></tr><tr><td>Data Quality</td><td>Grants user access to find data discrepancies or errors in project data by allowing user to create &amp; edit rules; and execute data quality rules.  If user does not have access to a data collection instrument that the query is referencing, access will be denied for query results.</td><td> </td><td>YES.  Depending on Data Entry Rights, PHI can be viewed.
</td></tr><tr><td>Reports &amp; Report Builder</td><td>        Grants user access to build simple queries within the project. If user does not have access to a data collection instrument that the report is pulling data from, access will be denied for report.</td><td>        For complex querying of data, best results are acquired by exporting data to a statistical package.        </td><td>YES. Depending on Data Entry Rights, PHI can be viewed.
</td></tr><tr><td>Project Design and Setup        </td><td>Grants user access to add, update or delete any forms within the project.  Also allows user to enable and disable project features and modules.          </td><td>This should be allocated only to trained study members and should be limited to a very few number of users per study.  </td><td> 
</td></tr><tr><td>Lock/Unlock Records        </td><td>Grants user access to lock/unlock a record from editing.  Users without this right will not be able to edit a locked record.  User will need “Read Only” or “View&amp;Edit” to lock/unlock a data collection instrument.        </td><td>A good tool for a staff member who has verified the integrity of a record to ensure that the data will not be manipulated further.  Works best if few team members have this right.        </td><td>Yes. Depending on Data Entry Rights, PHI can be viewed.  
</td></tr><tr><td>Record Locking Customization        </td><td>Grants user access to customize record locking text.        </td><td>Will only be applicable to users with Lock/Unlock rights.  Sometimes used for regulatory projects to provide “meaning” to the locking action.</td><td>  
</td></tr><tr><td>Create Records</td><td>        Grants user access to add record and data to database.          </td><td> Basic tool and need of data entry personnel.  </td><td> 
</td></tr><tr><td>Rename Records</td><td>        Grants user access to change key id of record.        </td><td><strong>WARNING:</strong> Should only be given to trained staff - can cause problems in data integrity.        </td><td> 
</td></tr><tr><td>Delete Records</td><td>        Grants user access to remove an entire record.        </td><td><strong>WARNING:</strong> Records deleted are records lost.  Few, if any, team members should have this right.        </td><td> 
</td></tr><tr><td>Expiration Date</td><td>        Automatically terminates project access for the user on date entered.</td><td>        </td><td> 
</td></tr></tbody></table>
<p>
<strong>*Please Note:</strong> REDCap is a web-based system.  Once data is downloaded from REDCap to a device (ex: computer, laptop, mobile device), the user is responsible for that data.  If the data being downloaded is protected health information (PHI), the user must be trained and knowledgeable as to which devices are secure and in compliance with your institution’s standards (ex: HIPAA) for securing PHI. 
</p>
<p>
<br>
</p>
<b><font color="#600000">Q: Can I restrict a user from viewing certain fields? </font></b>
<p>
To restrict a user from viewing sensitive fields, you must group all of the sensitive fields on one form and set the user’s data entry rights to “None” for that form. This will prevent the user from viewing the entire form. <strong>You cannot selectively prevent a user from viewing certain fields within a form.</strong>
</p>
<b><font color="#600000">Q: Who can unlock a record? </font></b>
<p>
Any user with Locking/Unlocking privileges can unlock a record, regardless of who originally locked the record.
</p>
<p>
<br>
</p>
<b><font color="#600000">Q: How can I differentiate between the Data Access Groups  and User Rights applications since both control the user’s access to data? </font></b>
<p>
The User Rights page can be used to determine the roles that a user can play within a REDCap database.  The Data Access group on the other hand determines the data visibility of a user within a REDCap database.  
</p>
<p>
The following example will illustrate the distinction that was made above.  Let's say that users 1 and 2 have identical data entry roles.  In this situation the Create and Edit Record rights would be assigned to both users.  However a particular project may require that they should have the ability to perform data entries on the same set of forms without seeing each other’s entries.  This can be done by assigning User1 into the access group1 and User2 to the access group2.  
</p>
<h2 id="dag"><font color="#600000">Data Access Groups</font><a class="anchor" href="#dag" title="Link to this section"> ¶</a></h2>
<b><p><font color="#600000">Q: What are Data Access Groups? </font></p></b>
<p>
Data Access Groups restrict viewing of data within a database. A typical use of Data Access Groups is a multi-site study where users at each site should only be able to view data from their site but not any other sites. Users at each site are assigned to a group, and will only be able to see records created by users within their group.
</p>
<b><font color="#600000">Q: Can I export a list of all subjects and their assigned Data Access group?</font></b>
<p>
<strong>NEW for v4.10.1+</strong> Yes, you can now export Data Access Group names. For projects containing Data Access Groups, both the Data Export Tool and API data export now automatically export the unique group name in the CSV Raw data file, and they export the Data Access Group label in the CSV Labels data file. The unique group names for DAGs are listed on each project's Data Access Groups page and API page. 
</p>
<p>
NOTE: The DAG name will only be exported if the current user is *not* in a DAG. And as it was previously, if the user is in a DAG, it is still true that it will export *only* the records that belong to that user's DAG.   
</p>
<b><font color="#600000">Q: How do you assign specific subjects to a Data Access group?</font></b>
<p>
If you have User Rights to the Data Access Group (DAG) tool, then for every record at the top of the forms, you should see a drop down list that says "Assign this record to a Data Access Group". Here you can add the record to a DAG.
</p>
<p>
<strong>NEW for v4.10.1+</strong> You can now assign/re-assign records to Data Access Groups via the Data Import Tool or API data import. For projects containing Data Access Groups, the Data Import Tool and API data import allow users who are *not* in a DAG to assign or re-assign records to DAGs using a field named "redcap_data_access_group" in their import data. For this field, one must specify the unique group name for a given record in order to assign/re-assign that record. 
</p>
<p>
The unique group names for DAGs are listed on each project's Data Access Groups page and API page. 
</p>
<b><font color="#600000">Q: Is there a way of separating data collected by various users so that a normal user can see only the records that he or she has completed?</font></b>
<p>
You can use Data Access Groups and assign each user to a specific group.  This will isolate records 
to specific groups.  Anyone not assigned to a group can see all records.
</p>
<p>
<br>
</p>
<h2 id="graph"><font color="#600000">Graphical View &amp; Stats</font><a class="anchor" href="#graph" title="Link to this section"> ¶</a></h2>
<p><b><font color="#600000">Q: How can I export the graphs and charts to use in presentations?</font></b>
</p><p>
You can "Print page" link at the top of the page and print to Adobe (tested with Adobe Acrobat Pro). Once you have an Adobe file, right click on the graphs and “save image as”. You can then paste into MS Word and Power Point.
</p>
<p>
You can also “Print Screen” (Alt-Print Screen in Windows or Ctl+Cmd+Shift+4 in Mac) to copy to the clipboard and paste in MS Word and Power Point. The graphs can be manipulated as images.
</p>
<h2 id="report"><font color="#600000">Report Builder</font><a class="anchor" href="#report" title="Link to this section"> ¶</a></h2>
<p><b><font color="#600000">Q: What is the Report Builder?</font></b>
</p><p>
The <strong>Report Builder</strong> is a tool to view all your data in a spreadsheet format without having to export data from the system. The Report Builder tool serves as the search engine of a REDCap project. The Report Builder queries the database in real time and displays the resulting data in table format. Variables are listed in columns and individual records are displayed in rows.
</p>
<p>
To create a report simply assign a name and choose the variables that you want to report on. You can set limiters for the variables you select. Note that when you save the report you are saving the combination of variables that you queried. The actual data is not affected. 
</p>
<p>
Every time you click on a defined report, the resulting data displayed is the most up-to-date data entered. 
</p>
<h1 id="library"><font color="#600000">Shared Library</font><a class="anchor" href="#library" title="Link to this section"> ¶</a></h1>
<p><b><font color="#600000">Q:  Once uploaded, is an instrument immediately available for download either for the consortium or the institution depending on the sharing selection or is it reviewed by REDLOC before being available? </font></b>
</p><p>
An initial review is done and a confirmation obtained from the submitter that they do want to share the instrument in the library.  A REDCap Administrator then approves the submission prior to its being added to the library.  The instrument is taken to REDLOC for review only if there are issues that the committee needs to discuss.
</p>
<p><b><font color="#600000">Q:  If one of our users uploads an instrument and accidentally shares it with the consortium, instead of just their institution, how can the instrument be updated to only be shared within the institution? </font></b>
</p><p>
The submitter can choose Share the instrument again and will be given an option to delete the instrument or resubmit.  The submitter can then resubmit/share again and choose the correct option.
</p>
<b><font color="#600000">Q: How are updates to the instruments that have been shared handled?  Is there any versioning? </font></b>
<p>
New versions will not replace old versions, but if more than one version is submitted it will be annotated.
<br>
</p>
<h1 id="postproduction"><font color="#600000">Making Production Changes</font><a class="anchor" href="#postproduction" title="Link to this section"> ¶</a></h1>
<b><font color="#600000">Q: How do I make changes after I have moved my project to Production? </font></b>
<p>
To make changes after you have moved your project to Production, select “Enter Draft Mode” on the Online Designer or Data Dictionary page. After making your changes, you can review them by clicking on "view a detailed summary of all drafted changes" hyperlink at the top of the page.
</p>
<p>
REDCap will flag any changes that may negatively impact your data with the following critical warnings in red:
</p>
<font color="red">
 *Possible label mismatch <br>
 *Possible data loss <br>
 *Data WILL be lost
</font>
<p>
After making and reviewing changes, you can click “Submit Changes for Review.” The REDCap Administrator will review your changes to make sure there is nothing that could negatively impact data you’ve already collected. If anything is questionable or flagged as critical, you may receive an email from the Administrator with this information to confirm that you really want to make the change.
</p>
<p>
Certain changes to the structure of the database, such as designating instruments to events or modifying events in a longitudinal project can only be done by the REDCap Administrator.
</p>
<b><font color="#600000">Q: What are the risks of modifying a database that is already in Production? </font></b>
<p>
Altering a database that is in Production can cause data loss. If a Production database must be modified, follow these rules to protect your data:
</p>
<p>
(a) Do not change existing variable names, or data stored for those variables will be lost. To restore data that has been lost in this way, revert to previous variable name(s).
</p>
<p>
(b) Do not change existing form names via a data dictionary upload, or form completeness data will be lost. Form names may be changed within the Online Designer without data loss.
</p>
<p>
(c) Do not modify the codes and answers for existing dropdown, radio, or checkbox variables; or existing data will be lost or confused. It is only acceptable to add choices to a dropdown, radio, or checkbox field.
</p>
<b><font color="#600000">Q: For radiobutton, checkbox and dropdown fields, can I add response options without impacting my data? </font></b>
<p>
Yes. Adding new choices has no data impact. New choices will be added and display on all records. 
</p>
<b><font color="#600000">Q: For radiobutton, checkbox and dropdown fields, can I delete response options? </font></b>
<p>
Deleting radiobutton or dropdown choices does not change the data saved to the database, but it deletes the ability to select that option.  
</p>
<p>
Deleting a checkbox option deletes the data saved for that option (0=unchecked, 1=checked), and it deletes the ability to select that option.
</p>
<p>
REDCap will flag this as:
</p>
<font color="red">
 *Data WILL be lost</font>
<p><b><font color="#600000">Q: For radiobutton, checkbox and dropdown fields, can I modify / re-order my response options? </font></b>
</p><p>
Modifying / recoding field choices does not change the data saved to the database, it only updates the labels.  This will change the meaning of the data already entered and you will have to re-enter responses for those records to ensure accuracy.  REDCap will flag this as:
</p>
<font color="red">
 *Possible label mismatch</font>
<p>
The best thing to do when making field choice changes for radiobuttons, checkboxes or dropdowns is to leave the current response choices as is and start with the next available code.  The coded choices do not have to be in order, so you can insert/list choices as you want them displayed.
</p>
<p>
For example, if your current codes are:
</p>
<p>
1, red  |  2, yellow  |  3, blue
</p>
<p>
and you want to add "green", "orange" and re-order alphabetically, <strong>DO NOT</strong> update to:
</p>
<p>
1, blue  |  2, green  |  3, orange   |  4, red  |  5, yellow
</p>
<p>
If you re-code like this, after the changes are committed any options selected for "1, red" will change to "1, blue";  "2, yellow" to  "2, green"; "3, blue" to  "3, orange".
</p>
<p>
That will completely change the meaning of the data entered.  Instead you will want to update to:
</p>
<p>
3, blue   |  4, green  |  5, orange |  1, red  |  2, yellow  
</p>
<b><font color="#600000">Q: Does the project go offline until the changes are approved? Can new surveys and records still be added to the project? </font></b>
<p>
The project does not go offline during the change request process.  All the functionality remains the same so you can continue adding and updating records as needed while the changes are pending. 
</p>
<b><font color="#600000">Q: What happens to the data in an ongoing longitudinal project if I delete some of the events? </font></b>
<p>
The data which was tied to the deleted events will not be erased.  It remains in the system but in “orphaned” form.
</p>
<b><font color="#600000">Q: If I delete events from an ongoing longitudinal project is the data that is unconnected with these events affected in any way?  </font></b>
<p>
In general you can assume that only the data that is tied to the deleted events is affected and that there will be no adverse impact on the data that has been entered for the remaining events.  However there could be an impact on this data if you are using branching logic or calculations across events.
</p>
<b><font color="#600000">Q: Are the numbers of the remaining events reordered if I delete some of the events in an ongoing longitudinal project? </font></b>
<p>
The original numbering is retained for the remaining events.
</p>
<p>
<br>
</p>
<h1 id="DataQuality"><font color="#600000">Data Quality Module</font><a class="anchor" href="#DataQuality" title="Link to this section"> ¶</a></h1>
<p>
The Data Quality module allows you to find discrepancies in your project data. You can create your own custom rules that REDCap will execute to determine if a specific data value is discrepant or not. Your custom rules can include mathematical operations and also advanced functions (listed below) to provide you with a great amount of power for validating your project data. NOTE: Although setting up a Data Quality custom rule may at times be very similar to constructed an equation for a calculated field, calc fields will ALWAYS have to result in a number, whereas the <strong>DQ custom rule must ALWAYS result with a TRUE or FALSE condition and NEVER a value</strong>.
</p>
<b><font color="#600000">Q: What mathematical operations can be used in the logic for Data Quality rules? </font></b>
<table class="wiki">
<tbody><tr><td>+        </td><td>Add
</td></tr><tr><td>-        </td><td>Subtract
</td></tr><tr><td>*        </td><td>Multiple
</td></tr><tr><td>/        </td><td>Divide
</td></tr></tbody></table>
<p>
<br>
</p>
<b><font color="#600000">Q. What functions can be used in Data Quality custom rules?</font></b>
<p>
The Data Quality module can perform many advanced functions, if defined in custom rules. A complete list is listed below. (NOTE: These functions are very similar - and in some cases identical - to functions that can be used for calculated fields.)
</p>
<table class="wiki">
<tbody><tr><td> <strong>Function</strong> </td><td> <strong>Type of calculation</strong> </td><td> <strong>Notes / examples</strong>
</td></tr><tr><td> round(number,decimal places) </td><td> <strong>Round</strong> </td><td> If the "decimal places" parameter is not provided, it defaults to 0. E.g. To round 14.384 to one decimal place:  round(14.384,1) will yield 14.4 
</td></tr><tr><td> roundup(number,decimal places) </td><td> <strong>Round Up</strong> </td><td> If the "decimal places" parameter is not provided, it defaults to 0. E.g. To round up 14.384 to one decimal place:  roundup(14.384,1) will yield 14.4
</td></tr><tr><td> rounddown(number,decimal places) </td><td> <strong>Round Down</strong> </td><td> If the "decimal places" parameter is not provided, it defaults to 0. E.g. To round down 14.384 to one decimal place:  rounddown(14.384,1) will yield 14.3
</td></tr><tr><td> sqrt(number) </td><td> <strong>Square Root</strong> </td><td> E.g. sqrt([height]) or sqrt(([value1]*34)/98.3)
</td></tr><tr><td> (number)^(exponent) </td><td><strong>Exponents</strong> </td><td> Use caret ^ character and place both the number and its exponent inside parentheses:  For example, (4)^(3) or ([weight]+43)^(2)
</td></tr><tr><td> abs(number) </td><td> <strong>Absolute Value</strong> </td><td> Returns the absolute value (i.e. the magnitude of a real number without regard to its sign). E.g. abs(-7.1) will return 7.1 and abs(45) will return 45. 
</td></tr><tr><td> min(number,number,...) </td><td> <strong>Minimum</strong> </td><td> Returns the minimum value of a set of values in the format min([num1],[num2],[num3],...). NOTE: All blank values will be ignored and thus will only return the lowest numerical value. There is no limit to the amount of numbers used in this function.
</td></tr><tr><td> max(number,number,...) </td><td> <strong>Maximum</strong> </td><td> Returns the maximum value of a set of values in the format max([num1],[num2],[num3],...). NOTE: All blank values will be ignored and thus will only return the highest numerical value. There is no limit to the amount of numbers used in this function. 
</td></tr><tr><td> mean(number,number,...) </td><td> <strong>Mean</strong> </td><td> Returns the mean (i.e. average) value of a set of values in the format mean([num1],[num2],[num3],...). NOTE: All blank values will be ignored and thus will only return the mean value computed from all numerical, non-blank values. There is no limit to the amount of numbers used in this function. 
</td></tr><tr><td> median(number,number,...) </td><td> <strong>Median</strong> </td><td> Returns the median value of a set of values in the format median([num1],[num2],[num3],...). NOTE: All blank values will be ignored and thus will only return the median value computed from all numerical, non-blank values. There is no limit to the amount of numbers used in this function.  
</td></tr><tr><td> sum(number,number,...) </td><td> <strong>Sum</strong> </td><td> Returns the sum total of a set of values in the format sum([num1],[num2],[num3],...). NOTE: All blank values will be ignored and thus will only return the sum total computed from all numerical, non-blank values. There is no limit to the amount of numbers used in this function.  
</td></tr><tr><td> stdev(number,number,...) </td><td> <strong>Standard Deviation</strong> </td><td> Returns the standard deviation of a set of values in the format stdev([num1],[num2],[num3],...). NOTE: All blank values will be ignored and thus will only return the standard deviation computed from all numerical, non-blank values. There is no limit to the amount of numbers used in this function.  
</td></tr><tr><td> isnumber(value) </td><td> <strong>Is value a number?</strong> </td><td> Returns a boolean (true or false) for if the value is an integer OR floating point decimal number. 
</td></tr><tr><td> isinteger(value) </td><td> <strong>Is value an integer?</strong> </td><td> Returns a boolean (true or false) for if the value is an integer (whole number without decimals). 
</td></tr></tbody></table>
<p>
<br>
</p>
<b><font color="#600000">Q: Can I use conditional logic in a Data Quality custom rule? </font></b>
<p>
Yes. You may use conditional logic (i.e. an IF/THEN/ELSE statement) by using the function <strong>if (CONDITION, value if condition is TRUE, value if condition is FALSE)</strong>
</p>
<p>
This construction is similar to IF statements in Microsoft Excel. Provide the condition first (e.g. [weight]=4), then give the resulting value if it is true, and lastly give the resulting value if the condition is false.  For example:
</p>
<p>
<strong> if([weight] &gt; 100, 44, 11) &lt; [other_field]</strong>
</p>
<p>
In this example, if the value of the field 'weight' is greater than 100, then it will give a value of 44, but if 'weight' is less than or equal to 100, it will give 11 as the result.
</p>
<p>
IF statements may be used inside other IF statements (“nested”). Other advanced functions (described above) may also be used inside IF statements.
</p>
<b><font color="#600000">Q: Why is my Data Quality custom rule returning an error message or not returning any discrepancies when I know some exist? </font></b>
<p>
The rule logic may not be formatted correctly. You may try troubleshooting the logic by simplifying the logic first and then add functionality in steps as you test. Remember that the logic must ALWAYS return as TRUE or FALSE after being evaluated (and never result with a value).
</p>
<b><font color="#600000">Q: How can I calculate the difference between two date or time fields in my logic (this includes time, datetime, and datetime_seconds fields)? </font></b>
<p>
You can calculate the difference between two dates or times by using the datediff function shown below. <strong>IMPORTANT NOTE:</strong> The Data Quality datediff function differs from the datediff function used in calculated fields in that the DQ datediff does NOT have a "dateformat" parameter. This is due to the fact that the date format of the dates used in the DQ datediff function does not have to be specified, whereas it does for calc fields.
</p>
<p>
<strong>datediff([date1], [date2], "units", returnSignedValue)</strong>
</p>
<p>
date1 and date2 are variables in your project
</p>
<p>
<strong>units</strong> 
</p>
<table class="wiki">
<tbody><tr><td><strong>"y"</strong> </td><td> years </td><td>  1 year = 365.2425 days 
</td></tr><tr><td><strong>"M"</strong> </td><td> months </td><td> 1 month = 30.44 days 
</td></tr><tr><td><strong>"d"</strong> </td><td>days </td><td> 
</td></tr><tr><td><strong>"h"</strong> </td><td>hours </td><td> 
</td></tr><tr><td><strong>"m"</strong> </td><td>minutes </td><td> 
</td></tr><tr><td><strong>"s"</strong> </td><td>seconds </td><td> 
</td></tr></tbody></table>
<p>
<strong>returnSignedValue</strong> 
</p>
<table class="wiki">
<tbody><tr><td><strong>false</strong> </td><td>(default) 
</td></tr><tr><td><strong>true</strong> </td><td> 
</td></tr></tbody></table>
<ul><li>The parameter returnSignedValue denotes the result to be signed or unsigned (absolute value), in which the default value is "false", which returns the absolute value of the difference. For example, if [date1] is larger than [date2], then the result will be negative if returnSignedValue is set to true. If returnSignedValue is not set or is set to false, then the result will ALWAYS be a positive number. If returnSignedValue is set to false or not set, then the order of the dates in the equation does not matter because the resulting value will always be positive (although the + sign is not displayed but implied). 
</li></ul><p>
Examples:
</p>
<table class="wiki">
<tbody><tr><td><strong>datediff([dob],[date_enrolled],"d")</strong> </td><td>Yields the number of days between the dates for the date_enrolled and dob fields 
</td></tr><tr><td><strong>datediff([dob],"05-31-2007","h",true)</strong> </td><td> Yields the number of hours between May 31, 2007, and the date for the dob field. Because returnSignedValue is set to true, the value will be negative if the dob field value is more recent than May 31, 2007. 
</td></tr></tbody></table>
<p>
<br>
</p>
<b><font color="#600000">Q: Do the two date fields used in the datediff function both have to be in the same date format (YMD, MDY, DMY)? </font></b>
<p>
No, they do not. Thus, an MDY-formatted date field can be used inside a datediff function with a YMD-formatted date field, and so on.
</p>
<b><font color="#600000">Q: Can a date field be used in the datediff function with a datetime or datetime_seconds field? </font></b>
<p>
Yes. If a date field is used with a datetime or datetime_seconds field, it will calculate the difference by assuming that the time for the date field is 00:00 or 00:00:00, respectively. Consequently, this also means that, for example, an MDY-formatted DATE field can be used inside a datediff function with a YMD-formatted DATETIME field.
</p>
<b><font color="#600000">Q: Can I base my Data Quality datediff function off of today's date? </font></b>
<p>
Yes, for example, you can indicate "age" as: rounddown(datediff("today",[dob],"y")). NOTE: The "today" variable CAN be used with date, datetime, and datetime_seconds fields, but NOT with time fields. (This is different from datediff in calc fields, in which the "today" variable can ONLY be used with date fields and NOT with time, datetime, or datetime_seconds fields.)
</p>

        
        
      </div>