/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

/**
 * RedCapUtil is a collection of JavaScript classes and functions used to
 * perform a variety tasks. Examples:
 * 
 * RedCapUtil.someFunction(); // calls someFunction()
 * var obj = new RedcapUtil.SomeClass(); // creates a new instance of SomeClass
 *
 * @requires jQuery
 * @requires Underscore.js
 * @requires Backbone.js
 */

// wrap all the functionality in RedCapUtil to avoid naming collisons
var RedCapUtil = {
	
	/**
	 * SyncCollection is a queue of AJAX calls that will execute in FIFO order
	 * with none of the calls executing simultaneously. This allows you to ensure
	 * that AJAX calls will happen in a given order (e.g., for order-dependent
	 * DB operations).
	 * 
	 * Each model in the collection is expected to have attributes as defined for
	 * a jQuery ajax() call: http://api.jquery.com/jQuery.ajax/
	 * 
	 * EXAMPLE:
	 * 
	 * mySyncCollection.add(new Backbone.Model({
	 *	url: foo.php,
	 *	data: {action: "doSomething"}
	 * }));
	 *
	 * SPECIAL AJAX SETTINGS:
	 * 
	 * error: SyncCollection will automatically attempt to rerun any AJAX request
	 * that fails. If you define an error function, then it will be called prior
	 * to SyncCollection resubmitting the request.
	 * checkSuccess: even when an AJAX request returns success, it doesn't
	 * necessarily mean that the server returned the data you expected. If you
	 * define this function, then you can validate the success() data to see
	 * if it is _really_ a success. Return true for success; return false to
	 * automatically call error().
	 * success: SyncCollection will automatically run checkSuccess if it exists,
	 * then move on to the next AJAX request. If you define a success function,
	 * then it will be called before the next request.
	 * 
	 * EVENTS:
	 * 
	 * onStartSync: will be called whenever the SyncCollection begins syncing itself.
	 * onComplete: will be called whenever the SyncCollection empties itself.
	 * 
	 * EXAMPLE:
	 * 
	 * var mySyncCollection = new RedCapUtil.SyncCollection([], {
	 *	onStartSync: function() { alert("I'm starting to save data!"); },
	 *	onComplete: function() { alert("All done!"); }
	 * });
	 */
	SyncCollection: Backbone.Collection.extend({
		syncing: false,
		erroring: false,
		onStartSync: function() {},
		onComplete: function() {},
		initialize: function(models, options) {
			if (options) {
				var events = ["onStartSync", "onComplete"];
				for (var i = 0; i < events.length; i++) {
					if (options.hasOwnProperty(events[i]))
						this[events[i]] = options[events[i]];
				}
			}
			// start syncing whenever anything is added to the queue
			this.bind("add", function() { this.startSync(); }, this);
		},
		startSync: function() {
			if (this.syncing) { return false; }
			this.onStartSync();
			this.syncing = true;
			var that = this;
			_.defer(function() { that._runSync() });
			return true;
		},
		// run one request at a time in the order they came in to avoid any out-of-order ops
		_runSync: function() {
			var that = this;
			if (this.length > 0) {
				var ajaxSettings = that.first();
				that.remove(ajaxSettings);
				var origError = ajaxSettings.get("error") ?
					ajaxSettings.get("error") : null;
				var origSuccess = ajaxSettings.get("success") ?
					ajaxSettings.get("success") : null;
				ajaxSettings.set({error: function(jqXHR, textStatus) {
					if (origError) origError(jqXHR, textStatus);
					// put the failed AJAX call back in the queue and try again
					if (origError) ajaxSettings.set({error: origError}, {silent: true});
					else ajaxSettings.unset("error", {silent: true});
					if (origSuccess) ajaxSettings.set({success: origSuccess}, {silent: true});
					else ajaxSettings.unset("success", {silent: true});
					that.add(ajaxSettings, {at: 0});
					that.erroring = true;
					that._runSync();
				}}, {silent: true});
				ajaxSettings.set({success: function(data, textStatus, jqXHR) {
					// Depending on server config, Apache can return status 200
					// even when there is a PHP fatal error. Check the returned
					// data and start erroring if it is not what we expect
					if (ajaxSettings.get("checkSuccess") &&
						!ajaxSettings.get("checkSuccess")(data, textStatus, jqXHR))
					{
						textStatus += " (false success)";
						return ajaxSettings.get("error")(jqXHR, textStatus);
					}
					if (origSuccess) origSuccess(jqXHR, textStatus);
					that.erroring = false;
					that._runSync();
				}}, {silent: true});
				// http://documentcloud.github.com/backbone/#Model-toJSON
				$.ajax(ajaxSettings.toJSON());
			}
			else { // shouldn't happen until queue is emptied and all AJAX calls succeed
				that.syncing = false;
				that.onComplete();
			}
		}
	}),
	
	/**
	 * openLoader and closeLoader are used to (un)cover a target jQuery element
	 * with a loading overlay. Resizing/positioning is done automatically.
	 * 
	 * EXAMPLE:
	 * 
	 * RedCapUtil.openLoader($("#container"));
	 * $.ajax(
	 *	url: foo.php
	 *	complete: function() {
	 *		RedCapUtil.closeLoader($("#container"));
	 *	}
	 * );
	 */
	openLoader: function(target) {
		// create the overlay layer
		var overlay = $("<div></div>");
		overlay.addClass("redcapLoading");
		// insert the overlay into the target
		target.prepend(overlay);
		// make the overlay cover the target
		overlay.height(target.height());
		overlay.width(target.width());
		// create the loading spinner
		var spinner = $('<img src="' + app_path_images + 'loader.gif" />');
		var spinnerWidth = 220; // having trouble getting this dynamically
		spinner.addClass("redcapLoading");
		// insert the spinner into the overlay
		overlay.append(spinner);
		// position the spinner 30% down the overlay and in the center
		spinner.css({
			top: Math.floor(overlay.height() * 0.3),
			left: Math.floor((overlay.width() - spinnerWidth) * 0.5)
		});
		overlay.show();
	},
	closeLoader: function(target) {
		target.children(".redcapLoading").first().remove();
	}
};