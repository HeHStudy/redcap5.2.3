/** Keep hold of the current table being dragged */
var currenttable = null;

/** Capture the onmousemove so that we can see if a row from the current
 *  table if any is being dragged.
 * @param ev the event (for Firefox and Safari, otherwise we use window.event for IE)
 */
document.onmousemove = function(ev){
    if (currenttable && currenttable.dragObject) {
        ev   = ev || window.event;
        var mousePos = currenttable.mouseCoords(ev);
        var y = mousePos.y - currenttable.mouseOffset.y;
        if (y != currenttable.oldY) {
            // work out if we're going up or down...
            var movingDown = y > currenttable.oldY;
            // update the old value
            currenttable.oldY = y;
            // update the style to show we're dragging
            currenttable.dragObject.style.backgroundColor = "#eee";
			$(currenttable.dragObject).fadeTo(0,0.5);
            // If we're over a row then move the dragged row to there so that the user sees the
            // effect dynamically
            var currentRow = currenttable.findDropTargetRow(y);
            if (currentRow) {
                if (movingDown && currenttable.dragObject != currentRow) {
                    currenttable.dragObject.parentNode.insertBefore(currenttable.dragObject, currentRow.nextSibling);
                } else if (! movingDown && currenttable.dragObject != currentRow) {
                    currenttable.dragObject.parentNode.insertBefore(currenttable.dragObject, currentRow);
                }
            }
        }

        return false;
    }
}

// Similarly for the mouseup
document.onmouseup   = function(ev){
    if (currenttable && currenttable.dragObject) {
        var droppedRow = currenttable.dragObject;
        // If we have a dragObject, then we need to release it,
        // The row will already have been moved to the right place so we just reset stuff
        droppedRow.style.backgroundColor = 'transparent';
		$(droppedRow).fadeTo(0,1);
        currenttable.dragObject   = null;
        // And then call the onDrop method in case anyone wants to do any post processing
		 var target = getEventSource(ev);
		 //alert(target.getAttribute("numbers"));
         if (target.getAttribute("ignore") == 'Yes') return true;
        currenttable.onDrop(currenttable.table, droppedRow);
        currenttable = null; // let go of the table too
    }
}


/** get the source element from an event in a way that works for IE and Firefox and Safari
 * @param evt the source event for Firefox (but not IE--IE uses window.event) */
function getEventSource(evt) {
    if (window.event) {
        evt = window.event; // For IE
        return evt.srcElement;
    } else {
        return evt.target; // For Firefox
    }
}

/**
 * Encapsulate table Drag and Drop in a class. We'll have this as a Singleton
 * so we don't get scoping problems.
 */
function TableDnD() {
    /** Keep hold of the current drag object if any */
    this.dragObject = null;
    /** The current mouse offset */
    this.mouseOffset = null;
    /** The current table */
    this.table = null;
    /** Remember the old value of Y so that we don't do too much processing */
    this.oldY = 0;

    /** Initialise the drag and drop by capturing mouse move events */
    this.init = function(table) {
        this.table = table;
        var rows = table.tBodies[0].rows; //getElementsByTagName("tr")
        for (var i=0; i<rows.length; i++) {
			// John Tarr: added to ignore rows that I've added the NoDnD attribute to (Category and Header rows)
			var nodrag = rows[i].getAttribute("NoDrag")
			if (nodrag == null || nodrag == "undefined") { //There is no NoDnD attribute on rows I want to drag
				this.makeDraggable(rows[i]);
			}
        }
    }

    /** This function is called when you drop a row, so redefine it in your code
        to do whatever you want, for example use Ajax to update the server */
    this.onDrop = function(table, droppedRow) {
		var field_name = droppedRow.getAttribute("sq_id");
		highlightTable('design-'+field_name,2000);
		//if ($('#matrix-'+field_name).length) highlightTable('matrix-'+field_name,2000);
		//Section Header
		if (field_name.substring(field_name.length-3) == "-sh") {
			var secthdr = field_name.substring(0, field_name.length-3);
		//Regular Field
		} else {
			var secthdr = "";
		}		
		this.table = table;
		var rows = table.tBodies[0].rows; //getElementsByTagName("tr")
		var sq_id = new Array();
		for (var i=0; i<rows.length; i++) {
			sq_id[i] = rows[i].getAttribute("sq_id");
		}
		var field_names = sq_id.join(',');
		var reloadTable = false;
		
		//AJAX call to make back-end change (if moving a section header, show loading progress while table reloads)
		if (secthdr != "") {
			//Make sure section header was not moved to very end of form (not allowable)			
			var revflds = field_names.split("").reverse().join("");
			if (revflds.substring(0,4) == ",hs-" || revflds.substring(0,3) == "hs-") {
				alert("Sorry, but data collection instruments cannot end with a Section Header. The Section Header will be moved back where it was.");
			}
			//If section header did not change location, then do nothing
			if (field_names.indexOf(secthdr+"-sh,"+secthdr+",") >= 0) {
				return;
			}
		}
		// On certain conditions, reload the whole Online Designer table
		if (
			//If field moved TO directly beneath section header or if section header was moved, then set flag to reload whole table (to fix SH discrepancies)
			(secthdr != "" || field_names.indexOf("-sh,"+field_name+",") >= 0)
			//If field moved FROM directly beneath section header , then set flag to reload whole table (to fix SH discrepancies)
			|| (secthdr == "" && field_names.indexOf(field_name+"-sh,") >= 0 && field_names.indexOf(field_name+"-sh,"+field_name+",") < 0)
			// If the field moved is a matrix radio/checkbox, then set flag to reload whole table (to reformat matrix group headers, if needed)
			|| ($('table#design-'+field_name+' tr td.label_matrix').length) 
		) {
			//Progress display
			showProgress(1);
			//Set flag
			reloadTable = true;			
		}
		// AJAX request
		$.post(app_path_webroot+'Design/update_field_order.php?pid='+pid, {field_name: field_name, form_name: form_name, section_header: secthdr, field_names: field_names },
			function(data) {
				if (data == "0") {
					alert("ERROR:\nThe action of reordering the fields could not complete. The form will be reloaded to undo the changes. We apologize for the inconvenience.");
					reloadTable = true;	
				} else if (data == "2") {
					//If a section header, reload table via AJAX to clear out inconsistencies from moving the section header
					alert("NOTICE:\nSince it is not allowed to have two Section Headers adjacent to each other, the Section Headers will be merged instead.");
					reloadTable = true;	
				} else if (data == "3") {
					// Table_pk was changed
					showPkNoDisplayMsg(); // For survey projects, display message that primary key field will not be displayed on survey page
					update_pk_msg(false,'field');
				}
				if (reloadTable) {
					reloadDesignTable(form_name);
				}
			}
		);
		return false;
    }
	
	function handleResponse() {
		if(req.readyState == 4 && req.status == 200){
		}
	}
	

	/** Get the position of an element by going up the DOM tree and adding up all the offsets */
    this.getPosition = function(e){
        var left = 0;
        var top  = 0;
		/** Safari fix -- thanks to Luis Chato for this! */
		if (e.offsetHeight == 0) {
			/** Safari 2 doesn't correctly grab the offsetTop of a table row
			    this is detailed here:
			    http://jacob.peargrove.com/blog/2006/technical/table-row-offsettop-bug-in-safari/
			    the solution is likewise noted there, grab the offset of a table cell in the row - the firstChild.
			    note that firefox will return a text node as a first child, so designing a more thorough
			    solution may need to take that into account, for now this seems to work in firefox, safari, ie */
			e = e.firstChild; // a table cell
		}

        while (e.offsetParent){
            left += e.offsetLeft;
            top  += e.offsetTop;
            e     = e.offsetParent;
        }

        left += e.offsetLeft;
        top  += e.offsetTop;

        return {x:left, y:top};
    }

	/** Get the mouse coordinates from the event (allowing for browser differences) */
    this.mouseCoords = function(ev){
        if(ev.pageX || ev.pageY){
            return {x:ev.pageX, y:ev.pageY};
        }
        return {
            x:ev.clientX + document.body.scrollLeft - document.body.clientLeft,
            y:ev.clientY + document.body.scrollTop  - document.body.clientTop
        };
    }

	/** Given a target element and a mouse event, get the mouse offset from that element.
		To do this we need the element's position and the mouse position */
    this.getMouseOffset = function(target, ev){
        ev = ev || window.event;

        var docPos    = this.getPosition(target);
        var mousePos  = this.mouseCoords(ev);
        return {x:mousePos.x - docPos.x, y:mousePos.y - docPos.y};
    }

	/** Take an item and add an onmousedown method so that we can make it draggable */
    this.makeDraggable = function(item) {
        if(!item) return;
        var self = this; // Keep the context of the TableDnd inside the function
        item.onmousedown = function(ev) {
            // Need to check to see if we are an input or not, if we are an input, then
            // return true to allow normal processing
            var target = getEventSource(ev);
            if (target.tagName == 'INPUT' || target.tagName == 'SELECT' || target.tagName == 'TEXTAREA') return true;
			//if (target.getAttribute("numbers") == 'Yes') return true;
            currenttable = self;
            self.dragObject  = this;
            self.mouseOffset = self.getMouseOffset(this, ev);
            return false;
        }
        item.style.cursor = "move";
    }

    /** We're only worried about the y position really, because we can only move rows up and down */
    this.findDropTargetRow = function(y) {
        var rows = this.table.tBodies[0].rows;
		for (var i=0; i<rows.length; i++) {
			var row = rows[i];
			// John Tarr added to ignore rows that I've added the NoDnD attribute to (Header rows)
			var nodrop = row.getAttribute("NoDrop");
			if (nodrop == null || nodrop == "undefined") {  //There is no NoDnD attribute on rows I want to drag
				var rowY    = this.getPosition(row).y;
				var rowHeight = parseInt(row.offsetHeight)/2;
				if (row.offsetHeight == 0) {
					rowY = this.getPosition(row.firstChild).y;
					rowHeight = parseInt(row.firstChild.offsetHeight)/2;
				}
				// Because we always have to insert before, we need to offset the height a bit
				if ((y > rowY - rowHeight) && (y < (rowY + rowHeight))) {
					// that's the row we're over
					return row;
				}
			}
		}
		return null;
	}
}