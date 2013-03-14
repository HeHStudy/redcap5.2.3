
/* 
// Make sure participants don't close the window without saving their responses first
window.onbeforeunload = confirmExit;
function confirmExit(obj) {
	// The default value for #submit-action is 'Save Record' and is changed when clicking button to save response, 
	// so we can use it to determine if simply closing the window/tab.
	return ($('table#form_table').length && $('#submit-action').val() != 'Save Record') ? false : 
	"SAVE YOUR RESPONSES\n\nAre you sure you want to leave the page without saving your responses first? YOUR RESPONSES WILL BE LOST unless you click the button at the bottom of the page.";
} 
*/

// For IE8, even though large images are resized, table spills off page, so manually resize picture, which fixes table.
function resizeImgIE() {
	$('#form image').each(function(){
		if ($(this).prop('src').indexOf('__passthru=') > -1) {
			var width = $(this).width();
			// For some reason, images may get initially set to 75 pixels and get stuck there, so ignore them so that
			// they display at their native size.
			if (width != 75) $(this).width(width);
		}
	});
}

$(function () 
{
	// Make section headers into toolbar CSS
	$('.header').addClass('toolbar');
	
	// Prevent any auto-filling of text fields by browser methods
	$(':input[type="text"]').prop("autocomplete","off");
	
	// Remove ability to submit form via Enter button on keyboard
	$(':input').keypress(function(e) {
		if (this.type == 'text' && e.which == 13) {
			return false;
		}
	});	

	// Fixes for CSS issues in IE
	if (isIE) {
		if (vIE() > 7) {	
			// For IE8, even though large images are resized, table spills off page, so manually resize picture, which fixes table.
			resizeImgIE();
			// Re-run this again after 2 and 6 seconds (in case images load slowly)
			setTimeout("resizeImgIE()",2000);
			setTimeout("resizeImgIE()",6000);
		} else {
			// For IE6&7, deal with table cell width issues.
			var dtable = document.getElementById('form_table');
			for (var i=0; i<dtable.rows.length; i++) {
				var thistrow = dtable.rows[i];
				if (!$(thistrow).hasClass('hide')) {
					if (thistrow.cells.length < 3) {
						var targetcell = thistrow.cells.length - 1;
						$(thistrow.cells[targetcell]).width(750);
					}
				}
			}
		}
	}
	
	// Bubble pop-up for Return Code widget
	if ($('.bubbleInfo').length) {
		$('.bubbleInfo').each(function () {
			var distance = 10;
			var time = 250;
			var hideDelay = 500;
			var hideDelayTimer = null;
			var beingShown = false;
			var shown = false;
			var trigger = $('.trigger', this);
			var info = $('.popup', this).css('opacity', 0);
			$([trigger.get(0), info.get(0)]).mouseover(function (e) {
				if (hideDelayTimer) clearTimeout(hideDelayTimer);
				if (beingShown || shown) {
					// don't trigger the animation again
					return;
				} else {
					// reset position of info box
					beingShown = true;
					info.css({
						top: 0,
						right: 0,
						width: 300,
						display: 'block'
					}).animate({
						top: '+=' + distance + 'px',
						opacity: 1
					}, time, 'swing', function() {
						beingShown = false;
						shown = true;
					});
				}
				return false;
			}).mouseout(function () {
				if (hideDelayTimer) clearTimeout(hideDelayTimer);
				hideDelayTimer = setTimeout(function () {
					hideDelayTimer = null;
					info.animate({
						top: '-=' + distance + 'px',
						opacity: 0
					}, time, 'swing', function () {
						shown = false;
						info.css('display', 'none');
					});

				}, hideDelay);

				return false;
			});
		});
	}
});