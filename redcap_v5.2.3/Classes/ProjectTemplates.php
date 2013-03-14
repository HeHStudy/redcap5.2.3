<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/


/**
 * ProjectTemplates Class
 */
class ProjectTemplates
{
	// Set PAGE for template setup page in the Control Center
	const CC_TEMPLATE_PAGE = 'ControlCenter/project_templates.php';
	
	// Output the drop-down list for choosing a project template on the Create New Project page
	static public function getTemplateList($project_id=null)
	{
		global $randomization_global;
		// Get list of templates in order to populate the array (set project_id as key)
		$templateList = array();
		$sql = "select t.* from redcap_projects_templates t, redcap_projects p where t.project_id = p.project_id";
		// Don't show templates with randomization enabled if global randomization setting is desabled
		$sql .= " and p.randomization in " . ($randomization_global ? "(0,1)" : "(0)");
		// If project_id was provided, then only return that single project
		$sql .= (is_numeric($project_id) ? " and t.project_id = $project_id" : " order by t.title");
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q))
		{
			$templateList[$row['project_id']] = array('title'=>$row['title'], 'description'=>$row['description'],
													  'enabled'=>$row['enabled']);
		}
		// Return array of templates
		return $templateList;		
	}
	
	// Output the html table of template project list
	static public function buildTemplateTable()
	{
		global $lang;
		// Check if we're on the setup page in the Control Center
		$isSetupPage = (PAGE == self::CC_TEMPLATE_PAGE);
		// Store template projects in array
		$templateList = self::getTemplateList();
		// Initialize varrs
		$row_data = array();
		$headers = array();
		$i = 0;
		$textLengthTruncate = 230;
		// Loop through array of templates
		foreach ($templateList as $this_pid=>$attr)
		{
			// If not enabled yet, then do not display on Create Project page
			if (!$isSetupPage && !$attr['enabled']) continue;		
			// If description is very long, truncate what is visible initially and add link to view entire text
			if (strlen($attr['description']) > $textLengthTruncate) {
				$textCutoffPosition = strrpos(substr($attr['description'], 0, $textLengthTruncate), " ");
				if ($textCutoffPosition === false) $textCutoffPosition = $textLengthTruncate;
				$descr1 = substr($attr['description'], 0, $textCutoffPosition);
				$descr2 = substr($attr['description'], $textCutoffPosition);
				$attr['description'] =  $descr1 . RCView::span('', "... ") . 
										RCView::a(array('href'=>'javascript:;','style'=>'text-decoration:underline;font-size:10px;','onclick'=>"$(this).prev('span').hide();$(this).hide().next('span').show();"), 
											$lang['create_project_94']
										) .
										RCView::span(array('style'=>'display:none;'), $descr2);
			}
			// Set radio button (create project page) OR edit/delete icons (control center)
			if ($isSetupPage) {
				$actionItem = 	RCView::a(array('href'=>'javascript:;','onclick'=>"projectTemplateAction('prompt_addedit',$this_pid);"), 
									RCView::img(array('src'=>'pencil.png','title'=>$lang['create_project_90']))
								) .
								RCView::a(array('style'=>'margin-left:3px;','href'=>'javascript:;','onclick'=>"projectTemplateAction('prompt_delete',$this_pid);"), 
									RCView::img(array('src'=>'cross.png','title'=>$lang['create_project_93']))
								);
			} else {
				$actionItem = RCView::radio(array('name'=>'copyof','value'=>$this_pid));
			}
			// Add this project as a row
			$row_data[$i][] = $actionItem;
			if ($isSetupPage) {
				$row_data[$i][] = RCView::a(array('href'=>'javascript:;','onclick'=>"projectTemplateAction('prompt_addedit',$this_pid);"), 
									RCView::img(array('src'=>($attr['enabled'] ? 'star.png' : 'star_empty.png'),'title'=>$lang['create_project_90']))
								  );
			}
			$row_data[$i][] = RCView::div(array('style'=>'color:#800000;padding:0;white-space:normal;word-wrap:normal;line-height:12px;'), $attr['title']);
			$row_data[$i][] = RCView::div(array('style'=>'padding:0;white-space:normal;word-wrap:normal;line-height:12px;'), $attr['description']);
			// Increment counter
			$i++;
		}
		// If no templates exist, then give message 
		if (empty($row_data))
		{
			$row_data[$i][] = "";
			if ($isSetupPage) $row_data[$i][] = "";
			$row_data[$i][] = RCView::div(array('style'=>'padding:0;white-space:normal;word-wrap:normal;'), $lang['create_project_77']);
			$row_data[$i][] = "";
		}
		// "Add templates" button
		$addTemplatesBtn = ((SUPER_USER && !$isSetupPage)
							? 	// Create New Project page
								RCView::div(array('style'=>'float:right;padding:5px 0 0;'),
									RCView::button(array('class'=>'jqbuttonsm','style'=>'color:green;','onclick'=>"window.location.href=app_path_webroot+'".self::CC_TEMPLATE_PAGE."';return false;"), 
										RCView::img(array('src'=>'plus_small2.png')) . $lang['create_project_78']
									)
								)
							: 
								(!$isSetupPage ? "" : 
									// Control Center
									RCView::div(array('style'=>'float:right;padding:0 200px 5px 0;'),
										RCView::button(array('class'=>'jqbuttonmed','style'=>'color:green;','onclick'=>"projectTemplateAction('prompt_addedit')"), 
											RCView::img(array('src'=>'add.png','style'=>'vertical-align:middle')) . 
											RCView::span(array('style'=>'vertical-align:middle'), $lang['create_project_83'])
										)
									)
								)
							);
		// Width & height
		$width = 720;
		$height = ($isSetupPage) ? 500 : 200;
		// Set table headers and attributes
		// First column (radios or edit/delete icons)
		$headers[] = array(42, ($isSetupPage ? "" : RCView::div(array('style'=>'font-size:10px;padding:0;white-space:normal;word-wrap:normal;color:#777777;font-family:tahoma;line-height:10px;'), $lang['create_project_74'])), "center");
		if ($isSetupPage) {
			// Column for Enabled stars
			$headers[] = array(43, $lang['create_project_104'], 'center');
		}
		// Title column
		$headers[] = array(163, RCView::b($lang['create_project_73']) . RCView::SP . RCView::SP . RCView::SP . $lang['create_project_103']);
		// Discription column
		$headers[] = array(461 - ($isSetupPage ? 55 : 0), RCView::b($lang['create_project_69']));
		// Title
		$title = RCView::div(array('style'=>'float:left;padding:1px 0 12px 5px;font-weight:bold;'),
					RCView::span(array('style'=>'font-size:12px;color:#800000;'), 
						($isSetupPage ? $lang['create_project_81'] : RCView::img(array('src'=>'star.png','class'=>'imgfix')) . $lang['create_project_66'])
					) . 
					($isSetupPage ? '' : RCView::span(array('style'=>'font-weight:normal;margin-left:10px;'), $lang['create_project_65']))
				 ) . 
				 $addTemplatesBtn;
		// Render table and return its html
		return renderGrid("template_projects_list", $title, $width, $height, $headers, $row_data, true, true, false);
	}
}
