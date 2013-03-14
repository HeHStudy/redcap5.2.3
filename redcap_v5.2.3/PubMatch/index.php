<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

/**
 * This is the publication adjudication page used by principal investigators.
 * The workflow is like so:
 * 
 * 1) PI receives an email link that allows them password-less access to this page.
 * 2) PI is presented with a list of publications that we think are theirs.
 * 3) For each publication, the PI marks whether it is theirs. If it is their
 *	publication, then they will be further asked to identify which of their
 *	projects are related to the publication. All choices are automatically
 *	saved in the background via AJAX.
 */

// Disable authentication (because person hitting this page may NOT actually be a REDCap user)
define("NOAUTH", true);

// Call config file
require_once dirname(dirname(__FILE__)) . '/Config/init_global.php';
$db = new RedCapDB();

// Check that the user has a valid secret to access this page at all
if (!$db->isValidPubMatchHash($_GET['secret'])) exit(RCL::accessDenied());
$secret = $_GET['secret'];

// display the header
$objHtmlPage = new HtmlPage();
$objHtmlPage->addExternalJS(APP_PATH_JS . "base.js");
$objHtmlPage->addExternalJS(APP_PATH_JS . "underscore-min.js");
$objHtmlPage->addExternalJS(APP_PATH_JS . "backbone-min.js");
$objHtmlPage->addExternalJS(APP_PATH_JS . "RedCapUtil.js");
$objHtmlPage->PrintHeaderExt();

$html = '';
$html .= RCView::h3(array(), RCL::pubPITitle());
$html .= RCView::p(array(), RCL::pubPIInstructions());
$html .= RCView::p(array(),
				RCL::pubPITodo() .
				RCView::span(array('id' => 'piTodoCount', 'class' => 'redcapLoud', 'style' => 'margin-left: 8px;'), ''));
$html .= RCView::span(array('id' => 'matchContent'), '');

?>

<script type="text/javascript">
	var ajaxScript = app_path_webroot + "PubMatch/index_ajax.php";
	// maps article IDs to RedCapUtil.SyncCollections
	var matchSyncMap = new Backbone.Collection();
	// simply keeps track of which articles the PI has clicked on
	var completedArticleMap = {};
	function initMatchSyncMap() {
		matchSyncMap = new Backbone.Collection();
		$("[id^=relatedProjs_]").each(function() {
			var articleId = $(this).attr("data-article-id");
			matchSyncMap.add(new Backbone.Model({
				id: articleId,
				sync: new RedCapUtil.SyncCollection([], {
					onStartSync: function() {
						$("[id^=matchStatus]").filter("[id$=_" + articleId + "]").hide();
						$("#matchStatusSaving_" + articleId).show();
					},
					onComplete: function() {
						$("[id^=matchStatus]").filter("[id$=_" + articleId + "]").hide();
						$("#matchStatusSaved_" + articleId).show();
					}
				})
			}));
		});
	};
	// warn the user about closing the window if we're in the middle of saving
	$(window).bind("beforeunload", function() {
		// check if any of the queues are syncing
		matchSyncMap.each(function(elem) {
			if (elem.get("sync").syncing && !elem.get("sync").erroring) {
				return <?php echo RCView::strToJS(RCL::saveInterrupt()); ?>;
			}
		});
	});
	function matchPubs(articleId, matchIds, isMatch) {
		matchSyncMap.get(articleId).get("sync").add(new Backbone.Model({
				url: ajaxScript,
				type: "POST",
				dataType: "json",
				data: {
					action: "matchPubs",
					secret: "<?php echo $secret; ?>",
					matchIds: matchIds.join(","),
					isMatch: isMatch === null ? null : (isMatch ? 1 : 0)
				},
				checkSuccess: function(data) {
					return data && data.hasOwnProperty("success") && data.success === true;
				},
				error: function() {
					$("[id^=matchStatus]").filter("[id$=_" + articleId + "]").hide();
					$("#matchStatusSaveRetry_" + articleId).show();
				}
			}));
	};
	// DOM is ready
	$(document).ready(function() {
		// initialize the list of pubs for PI adjudication
		RedCapUtil.openLoader($("#container"));
		$.get(ajaxScript, {action: "getMatchDisplay", secret: "<?php echo $secret; ?>"},
				function(json) {
					$("#matchContent").html(json.display);
					$("#piTodoCount").text(json.todoCount);
					initMatchSyncMap();
					RedCapUtil.closeLoader($("#container"));
			}, "json");
		// does the article belong to the PI?
		$(document).delegate("[name^=isMyPub_]", "change", function() {
			var articleId = $(this).attr("data-article-id");
			var isMyPub = $(this).val();
			// decrement the TODO count when the PI first yes/no's an article; increment
			// the count if they subsequently change back to decide later
			if ((isMyPub == 1 || isMyPub == 0) && !completedArticleMap.hasOwnProperty(articleId)) {
				completedArticleMap[articleId] = true;
				$("#piTodoCount").text(parseInt($("#piTodoCount").text()) - 1);
			}
			else if (isMyPub == 99 && completedArticleMap.hasOwnProperty(articleId)) {
				delete completedArticleMap[articleId];
				$("#piTodoCount").text(parseInt($("#piTodoCount").text()) + 1);
			}
			// collect the match IDs for later processing
			var mids = [];
			$("[id^=matchProj_][data-article-id=\"" + articleId + "\"]").each(function() {
				mids.push($(this).attr("data-match-id"));
				$(this).removeAttr("checked");
			});
			// any selection should reinit the matches
			$("#matchNone_" + articleId).removeAttr("checked");
			$("#matchAll_" + articleId).removeAttr("checked");
			if (!$("#relatedProjs_" + articleId).hasClass("yellow")) {
				$("#relatedProjs_" + articleId).removeClass("green");
				$("#relatedProjs_" + articleId).addClass("yellow");
			}
			// yes/no should init the matches to unmatched; decide later should null them
			matchPubs(articleId, mids, (isMyPub == 1 || isMyPub == 0) ? false : null);
			// show or hide the project checkboxes
			if (isMyPub == 1) $("#relatedProjs_" + articleId).show("fast");
			else $("#relatedProjs_" + articleId).hide("fast");
		});
		// is the project related to the article?
		$(document).delegate("[id^=matchProj_]", "click", function() {
			var articleId = $(this).attr("data-article-id");
			var mid = $(this).attr("data-match-id");
			var isMatch = $(this).is(":checked");
			matchPubs(articleId, [mid], isMatch);
			if ($("#relatedProjs_" + articleId).hasClass("yellow")) {
				$("#relatedProjs_" + articleId).removeClass("yellow");
				$("#relatedProjs_" + articleId).addClass("green");
			}
			// upkeep for the None/All checkboxes
			var checkedCount = 0; var totCount = 0;
			$("[id^=matchProj_][data-article-id=\"" + articleId + "\"]").each(function() {
				if ($(this).is(":checked")) checkedCount++;
				totCount++;
			});
			if (checkedCount == 0) {
				$("#matchNone_" + articleId).attr("checked", "checked");
				$("#matchAll_" + articleId).removeAttr("checked");
			}
			if (checkedCount > 0) {
				$("#matchNone_" + articleId).removeAttr("checked");
				if (checkedCount == totCount)
					$("#matchAll_" + articleId).attr("checked", "checked");
				else
					$("#matchAll_" + articleId).removeAttr("checked");
			}
		});
		// PI says "None of these" for an article
		$(document).delegate("[id^=matchNone_]", "click", function() {
			var articleId = $(this).attr("data-article-id");
			var mids = [];
			$("[id^=matchProj_][data-article-id=" + $(this).attr("data-article-id") + "]").each(function() {
				var mid = $(this).attr("data-match-id");
				mids.push(mid);
				$(this).removeAttr("checked");
			});
			matchPubs(articleId, mids, false);
			if ($("#relatedProjs_" + articleId).hasClass("yellow")) {
				$("#relatedProjs_" + articleId).removeClass("yellow");
				$("#relatedProjs_" + articleId).addClass("green");
			}
		});
		// PI says "All of these" for an article
		$(document).delegate("[id^=matchAll_]", "click", function() {
			var articleId = $(this).attr("data-article-id");
			var mids = [];
			$("[id^=matchProj_][data-article-id=" + $(this).attr("data-article-id") + "]").each(function() {
				var mid = $(this).attr("data-match-id");
				mids.push(mid);
				$(this).attr("checked", "checked");
			});
			matchPubs(articleId, mids, true);
			if ($("#relatedProjs_" + articleId).hasClass("yellow")) {
				$("#relatedProjs_" + articleId).removeClass("yellow");
				$("#relatedProjs_" + articleId).addClass("green");
			}
		});
	});
</script>

<?php

// Note at bottom to close window when done
$html .= RCView::p(array('style'=>'color:#800000;margin:40px 0 80px;font-size:16px;font-weight:bold;'), RCL::pubPIInstructionsClose());

echo $html;

$objHtmlPage->PrintFooterExt();