<?php

class HtmlPage
{	
	
    /*
    * PRIVATE PROPERTIES
    */
    
    // @var header string
    // @access private
    var $header;
    
    // @var footer string
    // @access private
    var $footer;
    
    
    /*
    * PUBLIC PROPERTIES
    */
    
    // @var htmltitle string
    // @access public
    var $htmltitle;

    // @var pagetitle string
    // @access public
    var $pagetitle;
    
    // @var stylesheets array
    // @access public
    var $stylesheets;
    
    // @var internalJS array
    // @access public
    var $internalJS;

    // @var externalJS array
    // @access public
    var $externalJS;
    
    // @var externalJS array
    // @access public
    var $breadcrumbs;
    
    // @var bodyOnLoad array
    // @access public
    var $bodyOnLoad;
    
    // @var topnav array
    // @access public
    var $topnav;
    
    // @var pagenav array
    // @access public
    var $pagenav;
    
    // @var titletext string
    // @access public
    var $titletext;
    
    /*
    * PRIVATE FUNCITONS
    */
    
    // @return HtmlPage
    // @access private
    function __construct()
    {
        // Default page title
        $this->htmltitle    = 'REDCap';
        // Array of stylesheets
        $this->stylesheets  = array();
        // Array Internal/inline javascript
        $this->internalJS   = array();
        // Array external javascript files
        $this->externalJS   = array();
        // Array body onLoad javascript commands
        $this->bodyOnLoad   = array();
        // Array of breadcrumbs
        $this->breadcrumbs  = array();
        // Array of top navigation elements
        $this->topnav       = array();
        // Array of page navigation elements
        $this->pagenav      = array();
        // Default titletext to a nonbreaking space
        $this->titletext    = '&nbsp;';
        // Default hovertext to a nonbreaking space. An empty string will result in display errors
        $this->hovertext    = '&nbsp;';
    }
    
	/**
     * PUBLIC FUNCITONS
     */
    
    // @return void
    // @access public
    function PrintHeader() {
		
		global $isIE, $isMobileDevice, $isIOS;
        
        $this->header = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">' . "\n" .
                        '<html>' . "\n" .
                        '<head>' . "\n" .
						'<meta name="googlebot" content="noindex, noarchive, nofollow, nosnippet">' . "\n" .
						'<meta name="robots" content="noindex, noarchive, nofollow">' . "\n" .
						'<meta name="slurp" content="noindex, noarchive, nofollow, noodp, noydir">' . "\n" .
						'<meta name="msnbot" content="noindex, noarchive, nofollow, noodp">' . "\n" .
                        '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />' . "\n" .
                        '<meta http-equiv="Content-Language" content="en-us" />' . "\n" .
                        '<meta http-equiv="Last-Modified" content="' . gmdate("D, d M Y H:i:s") . ' GMT"/>' .  "\n" .
                        // Mobile - fix viewport so content forced to display in device screen, with no resize of content
						((isset($isMobileDevice) && $isMobileDevice && PAGE == "surveys/index.php") ? '<meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0, initial-scale=1.0, user-scalable=no">'."\n" : '') .
                        '<meta http-equiv="X-UA-Compatible" content="chrome=1">' .
						'<title>' . $this->htmltitle . '</title>' .  "\n" .
						'<link rel="shortcut icon" href="' . APP_PATH_IMAGES . 'favicon.ico">' . "\n" .
						(!$isIOS ? '' : '<link rel="apple-touch-icon-precomposed" href="' . APP_PATH_IMAGES . 'apple-touch-icon.png">' . "\n");
        
        // Add all stylesheets
        // -------------------
        foreach($this->stylesheets AS $tag) {
            $this->header .= $tag;
        }
        // Add all external javascript file
        // --------------------------------
        foreach($this->externalJS AS $path) {
            $this->header .= '<script type="text/javascript" src="' . $path . '"></script>' . "\n";
        }
        
        // Add any internal javascript code (if it exists)
        // -----------------------------------------------
        if(count($this->internalJS)) {
            $this->header .= '<script type="text/javascript">';
            
            foreach($this->internalJS AS $js) {
                $this->header .= $js;
            }
            
            $this->header .= '</script>';
        }
        
        $this->header .= '</head>' . "\n";

        // if there are no onload javascript events to fire
        // ------------------------------------------------
        if(!count($this->bodyOnLoad)) {
            // open body tag
            // -------------
            $this->header .= '<body>';
        } else {
            // begin open body tag
            // -------------------
            $this->header .= '<body onload="';
            
            foreach($this->bodyOnLoad AS $js) {
                // add all javascript on load events
                // ---------------------------------
                $this->header .= $js;
            }
            // end open body tag
            // -----------------
            $this->header .= '">';
        }
		
		// IE CSS Hack - Render the following CSS if using IE
		if ($isIE) 
		{
			$this->header .= '<style type="text/css">input[type="radio"], input[type="checkbox"] {margin: 0}</style>';
		}
		
        $this->header .= '<div id="outer">';
				
		// Catch if JavaScript is disabled in browser
		$this->header .=   '
		<noscript>
			<div class="red">
				<img src="'.APP_PATH_IMAGES.'exclamation.png" class="imgfix"> <b>WARNING: JavaScript Disabled</b><br><br>
				It has been determined that your web browser currently does not have JavaScript enabled, 
				which prevents this webpage from functioning correctly. You CANNOT use this page until JavaScript is enabled. 
				You will find instructions for enabling JavaScript for your web browser by 
				<a href="http://www.google.com/support/bin/answer.py?answer=23852" target="_blank" style="text-decoration:underline;">clicking here</a>. 
				Once you have enabled JavaScript, you may refresh this page or return back here to begin using this page.
			</div>
		</noscript>
		';
		
        print $this->header;

		// Do CSRF token check (using PHP with jQuery)
		createCsrfToken();
		
		// Render Javascript variables needed on all pages for various JS functions
		renderJsVars();
		
        print(					'<div id="container">' .
                                    '<div id="pagecontent">'); 

    }

    // @return void
    // @access public
    function PrintHeaderExt() {
		$this->addExternalJS(APP_PATH_JS . "base.js");
		$this->addStylesheet("smoothness/jquery-ui-".JQUERYUI_VERSION.".custom.css", 'screen,print');
		$this->addStylesheet("style.css", 'screen,print');
		$this->addStylesheet("home.css", 'screen,print');
		$this->PrintHeader();
		// Adjust some CSS
		print  "<style type='text/css'>
				#pagecontent { margin: 0; }
				#outer #footer { display:none; }
				</style>";
	}

    // @return void
    // @access public
    function PrintFooterExt() {
		$this->PrintFooter();
	}

    // @return void
    // @access public
    function PrintFooter() {
	
		global $redcap_version;
	
		print   		'</div>' .
					'</div>';
		// Display REDCap copyright (but not in Mobile Site view)
		if (strpos(PAGE, 'Mobile/') === false) {
			print 	'<div class="notranslate" id="footer">' .
						'REDCap Software - Version ' . $redcap_version . ' - &copy; ' . date("Y") . ' Vanderbilt University' .
					'</div>';
		}
		print	'</div>';
				
		// Initialize auto-logout popup timer and logout reset timer listener
		initAutoLogout();
		
		// Render hidden divs used by showProgress() javascript function
		renderShowProgressDivs();
	
		// Render divs holding javascript form-validation text (when error occurs), so they get translated on the page
		renderValidationTextDivs();

		// Display notice that password will expire soon (if utilizing $password_reset_duration for Table-based authentication)
		Authentication::displayPasswordExpireWarningPopup();

		// Check if need to display pop-up dialog to SET UP SECURITY QUESTION for table-based users
		Authentication::checkSetUpSecurityQuestion();
		
		// Initialize windows-resize and other basic javascript
		?><script type="text/javascript">$(function(){ initHomePage(); }); </script><?php
		//print '<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>';  //todd: For heh customization. This has been commented out.  Christa wanted to include it, but it breaks the submit errors popup. So I commented it out and left this as a reminder.
		//todd: For heh customization: added the next following javascript files to enable popover tips on survey.
		print '<script type="text/javascript" src="' . APP_PATH_JS . 'bootstrap.min.js"></script>'. "\n";
		print '<script type="text/javascript" src="' . APP_PATH_JS . 'bootstrap-popover.js"></script>'. "\n";
		
		print '</body></html>';
        
    }
    
    // @return void
    // @access public
    function addStylesheet($file, $media)
    {
        $tag = '<link rel="stylesheet" type="text/css" media="' . $media . '" href="' . APP_PATH_CSS . $file . '"/>' . "\n";
        array_push($this->stylesheets, $tag);
    }
    
    // @return void
    // @access public
    function addStylesheet2($file, $media)
    {
        $tag = '<link rel="stylesheet" type="text/css" media="' . $media . '" href="' . $file . '"/>' . "\n";
        array_push($this->stylesheets, $tag);
    }
    
    // @return void
    // @access public
    function addInternalJS($js)
    {
        array_push($this->internalJS, $js);
    }
    
    // @return void
    // @access public
    function addExternalJS($path)
    {
        array_push($this->externalJS, $path);
    }
    
    function setPageTitle($var)
    {
        //$this->pagetitle = $var;
		$this->htmltitle = $var;
    }

}   
