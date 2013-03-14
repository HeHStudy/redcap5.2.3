<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

// Give link to go back to previous page if coming from a project page
$prevPageLink = "";
if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], "pid=") !== false) {
	$prevPageLink = "<div style='margin:0 0 20px;'>
						<img src='" . APP_PATH_IMAGES . "arrow_skip_180.png' class='imgfix'> 
						<a href='{$_SERVER['HTTP_REFERER']}' style='color:#2E87D2;font-weight:bold;'>{$lang['help_01']}</a>
					 </div>";
}

// If site has set custom text to be displayed at top of page, then display it
$helpfaq_custom_html = '';
if (trim($helpfaq_custom_text) != '') 
{
	// Set html for div
	$helpfaq_custom_html = "<div class='blue' style='max-width:800px;font-family:arial;margin:0px 10px 15px 0;padding:10px;'>".nl2br(filter_tags(label_decode($helpfaq_custom_text)))."</div>";
}

?>

<!-- Use javascript to further adjust CSS -->
<script type="text/javascript">
$(function(){
	// Increment margin of LI lists
	var marginIncrementValue = 30, thisMarginLeft;
	$('.wikipage li').each(function(){
		thisMarginLeft = marginIncrementValue + ($(this).css('margin-left').replace('px','')*1);
		$(this).css('margin-left',thisMarginLeft+'px');
	});
});
</script>

<!-- Wiki-specific CSS -->
<style type="text/css">
.wikipage li { list-style-type:initial; background:white; font-family: Arial,Verdana,'Bitstream Vera Sans',Helvetica,sans-serif; }
th, td {
 font: normal 13px Verdana,Arial,'Bitstream Vera Sans',Helvetica,sans-serif;
}
h1, h2, h3, h4 {
 font-family: Arial,Verdana,'Bitstream Vera Sans',Helvetica,sans-serif;
 font-weight: bold;
 letter-spacing: -0.018em;
 page-break-after: avoid;
}
h1 { font-size: 19px; margin: .15em 1em 0.5em 0 }
h2 { font-size: 16px }
h3 { font-size: 14px }
hr { border: none;  border-top: 1px solid #ccb; margin: 2em 0 }
address { font-style: normal }
img { border: none }

.underline { text-decoration: underline }
ol.loweralpha { list-style-type: lower-alpha }
ol.upperalpha { list-style-type: upper-alpha }
ol.lowerroman { list-style-type: lower-roman }
ol.upperroman { list-style-type: upper-roman }
ol.arabic     { list-style-type: decimal }

/* Link styles */
:link, :visited {
 text-decoration: none;
 color: #b00;
}
:link:hover, :visited:hover { background-color: #eee; color: #555 }
h1 :link, h1 :visited ,h2 :link, h2 :visited, h3 :link, h3 :visited,
h4 :link, h4 :visited, h5 :link, h5 :visited, h6 :link, h6 :visited {
 color: inherit;
}
.trac-rawlink { border-bottom: none }

/* Heading anchors */
.anchor:link, .anchor:visited {
 border: none;
 color: #d7d7d7;
 font-size: .8em;
 vertical-align: text-top;
}
* > .anchor:link, * > .anchor:visited {
 visibility: hidden;
}
h1:hover .anchor, h2:hover .anchor, h3:hover .anchor,
h4:hover .anchor, h5:hover .anchor, h6:hover .anchor {
 visibility: visible;
}

/* Forms */
input, textarea, select { margin: 2px }
input, select { vertical-align: middle }
input[type=button], input[type=submit], input[type=reset] {
 background: #eee;
 color: #222;
 border: 1px outset #ccc;
 padding: .1em .5em;
}
input[type=button]:hover, input[type=submit]:hover, input[type=reset]:hover {
 background: #ccb;
}
input[type=button][disabled], input[type=submit][disabled],
input[type=reset][disabled] {
 background: #f6f6f6;
 border-style: solid;
 color: #999;
}
input[type=text], input.textwidget, textarea { border: 1px solid #d7d7d7 }
input[type=text], input.textwidget { padding: .25em .5em }
input[type=text]:focus, input.textwidget:focus, textarea:focus {
 border: 1px solid #886;
}
option { border-bottom: 1px dotted #d7d7d7 }
fieldset { border: 1px solid #d7d7d7; padding: .5em; margin: 1em 0 }
form p.hint, form span.hint { color: #666; font-size: 85%; font-style: italic; margin: .5em 0;
  padding-left: 1em;
}
fieldset.iefix {
  background: transparent;
  border: none;
  padding: 0;
  margin: 0;
}
* html fieldset.iefix { width: 98% }
fieldset.iefix p { margin: 0 }
legend { color: #999; padding: 0 .25em; font-size: 90%; font-weight: bold }
label.disabled { color: #d7d7d7 }
.buttons { margin: .5em .5em .5em 0 }
.buttons form, .buttons form div { display: inline }
.buttons input { margin: 1em .5em .1em 0 }
.inlinebuttons input { 
 font-size: 70%;
 border-width: 1px;
 border-style: dotted;
 margin: 0 .1em;
 padding: 0.1em;
 background: none;
}

/* Header */
#header hr { display: none }
#header h1 { margin: 1.5em 0 -1.5em; }
#header img { border: none; margin: 0 0 -3em }
#header :link, #header :visited, #header :link:hover, #header :visited:hover {
 background: transparent;
 color: #555;
 margin-bottom: 2px;
 border: none;
}
#header h1 :link:hover, #header h1 :visited:hover { color: #000 }


#content { padding-bottom: 2em; position: relative }

#help {
 clear: both;
 color: #999;
 font-size: 90%;
 margin: 1em;
 text-align: right;
}
#help :link, #help :visited { cursor: help }
#help hr { display: none }

/* Wiki */
.wikipage { padding-left: 18px; width:750px; }
.wikipage h1, .wikipage h2, .wikipage h3 { margin-left: -18px }

a.missing:link, a.missing:visited, a.missing, span.missing,
a.forbidden, span.forbidden { color: #998 }
a.missing:hover { color: #000 }
a.closed:link, a.closed:visited, span.closed { text-decoration: line-through }

/* User-selectable styles for blocks */
.important {
 background: #fcb;
 border: 1px dotted #d00;
 color: #500;
 padding: 0 .5em 0 .5em;
 margin: .5em;
}

dl.wiki dt { font-weight: bold }
dl.compact dt { float: left; padding-right: .5em }
dl.compact dd { margin: 0; padding: 0 }

pre.wiki, pre.literal-block {
 background: #f7f7f7;
 border: 1px solid #d7d7d7;
 margin: 1em 1.75em;
 padding: .25em;
 font-size:11px;
}

blockquote.citation { 
 margin: -0.6em 0;
 border-style: solid; 
 border-width: 0 0 0 2px; 
 padding-left: .5em;
 border-color: #b44; 
}
.citation blockquote.citation { border-color: #4b4; }
.citation .citation blockquote.citation { border-color: #44b; }
.citation .citation .citation blockquote.citation { border-color: #c55; }

table.wiki {
 border: 2px solid #ccc;
 border-collapse: collapse;
 border-spacing: 0;
}
table.wiki td { border: 1px solid #ccc;  padding: .1em .25em; }

/* Styles for tabular listings such as those used for displaying directory
   contents and report results. */
table.listing {
 clear: both;
 border-bottom: 1px solid #d7d7d7;
 border-collapse: collapse;
 border-spacing: 0;
 margin-top: 1em;
 width: 100%;
}
table.listing th { text-align: left; padding: 0 1em .1em 0; font-size: 12px }
table.listing thead { background: #f7f7f0 }
table.listing thead th {
 border: 1px solid #d7d7d7;
 border-bottom-color: #999;
 font-size: 11px;
 font-weight: bold;
 padding: 2px .5em;
 vertical-align: bottom;
}
table.listing thead th :link:hover, table.listing thead th :visited:hover {
 background-color: transparent;
}
table.listing thead th a { border: none; padding-right: 12px }
table.listing th.asc a, table.listing th.desc a { font-weight: bold }
table.listing th.asc a, table.listing th.desc a {
 background-position: 100% 50%;
 background-repeat: no-repeat;
}
table.listing th.asc a { background-image: url(../asc.png) }
table.listing th.desc a { background-image: url(../desc.png) }
table.listing tbody td, table.listing tbody th {
 border: 1px dotted #ddd;
 padding: .3em .5em;
 vertical-align: top;
}
table.listing tbody td a:hover, table.listing tbody th a:hover {
 background-color: transparent;
}
table.listing tbody tr { border-top: 1px solid #ddd }
table.listing tbody tr.even { background-color: #fcfcfc }
table.listing tbody tr.odd { background-color: #f7f7f7 }
table.listing tbody tr:hover { background: #eed !important }
table.listing tbody tr.focus { background: #ddf !important }

p b { font-family: verdana; }
</style>

<?php

print $helpfaq_custom_html;

print $prevPageLink;

// Include help content scraped from End-User FAQ wiki page
include APP_PATH_DOCROOT . 'Help/help_content.php';

print $prevPageLink;
