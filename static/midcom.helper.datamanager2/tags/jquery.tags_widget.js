/*
 * midcom_helper_datamanager2_widget_tags_widget - jQuery plugin 1.0
 *
 * Jerry Jalava <jerry.jalava@gmail.com>
 *
 * Baseline got from jquery.autocomple plugin
 *
 */

/*
 * Depends on jquery.bgiframe plugin to fix IE's problem with selects.
 *
 * @name midcom_helper_datamanager2_widget_tags_widget
 * @cat Plugins/Autocomplete
 * @type jQuery
 * @param String|Object url an URL to data backend
 * @param Map options Optional settings
 * @option Number min_chars The minimum number of characters a user has to type before the autocompleter activates. Default: 1
 * @option Number delay The delay in milliseconds that search waits after a keystroke before activating itself. Default: 400
 * @option Object extra_params Extra parameters for the backend. Default: {}
 * @option Boolean select_first If this is set to true, the first result will be automatically selected on tab/return. Default: true
 * @option Number width Specify a custom width for the select box. Default: width of the input element
 * @option Boolean autofill_enabled Fill the textinput while still selecting a value, replacing the value if more is typed or something else is selected. Default: false
 * @option Number result_limit Limit the number of items in the results box. Is also send as a "limit" parameter to backend on request. Default: 10
 * @option Number width Specify a custom width for the select box. Default: width of the input element
 * @option Boolean allow_create If this is set to true, then when user presses tab it creates a tag from current input value if we don't have any results. Default: false
 */

jQuery.fn.extend({
	midcom_helper_datamanager2_widget_tags_widget: function(url, options) {
		options = jQuery.extend({}, jQuery.midcom_helper_datamanager2_widget_tags.defaults, {
			url: url || null
		}, options);
		return this.each(function() {
			new jQuery.midcom_helper_datamanager2_widget_tags(this, options);
		});
	},
	midcom_helper_datamanager2_widget_tags_add_selection_item: function(item) {
		return this.trigger("add_selection_item",[item]);
	},
	midcom_helper_datamanager2_widget_tags_remove_selection_item: function(item_id) {
		return this.trigger("remove_selection_item",[item_id]);
	}	
});

jQuery.midcom_helper_datamanager2_widget_tags = function(input, options) {

	var KEY = {
		UP: 38,
		DOWN: 40,
		DEL: 46,
		TAB: 9,
		RETURN: 13,
		ESC: 27,
		COMMA: 188
	};

	var input_element = jQuery(input).attr("autocomplete", "off").addClass('tags_widget_input');
    var selection_holder = jQuery.midcom_helper_datamanager2_widget_tags.SelectionHolder(options, input);
        
	var timeout;
	var previousValue = "";
	var hasFocus = 0;
	var lastKeyPressCode;
	var select = jQuery.midcom_helper_datamanager2_widget_tags.Select(options, input, selectCurrent);
    	
	input_element.keydown(function(event) {
		// track last key pressed
		lastKeyPressCode = event.keyCode;
		switch(event.keyCode) {
		
			case KEY.UP:
				event.preventDefault();
				if ( select.visible() ) {
					select.prev();
				} else {
					onChange(0, true);
				}
				break;
				
			case KEY.DOWN:
				event.preventDefault();
				if ( select.visible() ) {
					select.next();
				} else {
					onChange(0, true);
				}
				break;
			
			case KEY.TAB:
			case KEY.RETURN:
				if( selectCurrent() ){
					input_element.focus();
					event.preventDefault();
				}
				else
				{
				    value = input_element.val();
				    if (   options.allow_create == true
				        && value != '')
				    {
				        var data = {
				            id: value,
				            name: value,
				            color: '8596b6'
				        };
				        input_element.trigger("result", [data]);
				    }
				}
				break;
				
			case KEY.ESC:
				select.hide();
				break;
				
			default:
				clearTimeout(timeout);
				timeout = setTimeout(onChange, options.delay);
				break;
		}
	}).keypress(function(event) {
		// having fun with opera - remove this binding and Opera submits the form when we select an entry via return
		switch(event.keyCode) {
		    case KEY.TAB:
		    case KEY:RETURN:
		        event.preventDefault();
		        break;
	    }
	}).focus(function(){
		// track whether the field has focus, we shouldn't process any
		// results if the field no longer has focus
		hasFocus++;
	}).blur(function() {
		hasFocus = 0;
		hideResults();
	}).click(function() {
		// show select when clicking in a focused field
		if ( hasFocus++ > 1 && !select.visible() ) {
			onChange(0, true);
		}
	}).bind("result", function(event, data){
	    input_element.focus();
	    selection_holder.add_item(data);
	}).bind("add_selection_item", function(event, item){
	    selection_holder.add_item(item);
	}).bind("remove_selection_item", function(event, item_id){
	    selection_holder.del_item(item);
	});
	
	hideResultsNow();
	
	function selectCurrent() {
		var selected = select.selected();
		if( !selected )
			return false;
		
		var v = selected.result;
		previousValue = v;
		
		input_element.val('');
		input.focus();
		hideResultsNow();
		input_element.trigger("result", [selected.data]);
		return true;
	}
	
	function onChange(crap, skipPrevCheck) {
		if( lastKeyPressCode == KEY.DEL ) {
			select.hide();
			return;
		}
		
		var currentValue = input_element.val();
		
		if ( !skipPrevCheck && currentValue == previousValue )
			return;
		
		previousValue = currentValue;
		
		currentValue = lastWord(currentValue);
		if ( currentValue.length >= options.min_chars) {
			input_element.addClass('tags_widget_loading');
			currentValue = currentValue.toLowerCase();
			request(currentValue, receiveData, stopLoading);
		} else {
			stopLoading();
			select.hide();
		}
	};
	
	function trimWords(value) {
		if ( !value ) {
			return [""];
		}
		var words = value.split( jQuery.trim( options.multipleSeparator ) );
		var result = [];
		jQuery.each(words, function(i, value) {
			if ( jQuery.trim(value) )
				result[i] = jQuery.trim(value);
		});
		return result;
	}
	
	function lastWord(value) {
		if ( !options.multiple )
			return value;
		var words = trimWords(value);
		return words[words.length - 1];
	}
	
	// fills in the input box w/the first match (assumed to be the best match)
	function autofill(q, sValue){
		// autofill in the complete box w/the first match as long as the user hasn't entered in more data
		// if the last user key pressed was backspace, don't autofill
		if( options.autofill_enabled && (lastWord(input_element.val()).toLowerCase() == q.toLowerCase()) && lastKeyPressCode != 8 ) {
			// fill in the value (keep the case the user has typed)
			input_element.val(input_element.val() + sValue.substring(lastWord(previousValue).length));
			// select the portion of the value not typed by the user (so the next character will erase)
			jQuery.midcom_helper_datamanager2_widget_tags.MoveSelection(input, previousValue.length, previousValue.length + sValue.length);
		}
	};

	function hideResults() {
		clearTimeout(timeout);
		timeout = setTimeout(hideResultsNow, 200);
	};

	function hideResultsNow() {
		select.hide();
		clearTimeout(timeout);
		stopLoading();
	};

	function receiveData(q, data) {
		if ( data && data.length && hasFocus ) {
			stopLoading();
			select.display(data, q);
			autofill(q, data[0].value);
			select.show();
		} else {
			hideResultsNow();
		}
	};

	function request(term, success, failure) {
		var data = false;
		
		term = term.toLowerCase();

		jQuery.ajax({
			url: options.url,
			dataType: 'xml',
			data: jQuery.extend({
				query: lastWord(term),
				limit: options.result_limit
			}, options.extra_params),
            error: function(obj, type, expobj) {
                failure(type, expobj);
            },
			success: function(data) {
				var parsed = parse(data);
				success(term, parsed);
			}
		});
		
		if (!data)
		{
		    failure(term);
		}
	}
	
	function parse(data)
	{
        var results = [];
        jQuery('result',data).each(function(idx) {
            var rel_this = jQuery(this);
    	                
            results[idx] = {         	    
                id:rel_this.find("id").text(), 
                name:rel_this.find("name").text(),
                color:rel_this.find("color").text()
            };
        });

    	var parsed = [];
    	jQuery(results).each(function(idx){
    		var item = results[idx];
    		if (item) {
    			parsed[parsed.length] = {
    				data: item,
    				value: item.id,
    				result: midcom_helper_datamanager2_widget_tags_format_item(item)
    			};
    		}    	    
    	});

    	return parsed;
	}

	function stopLoading() {
		input_element.removeClass('tags_widget_loading');
		input_element.addClass('tags_widget_error');
	}

}

jQuery.midcom_helper_datamanager2_widget_tags.defaults = {
    widget_type_name: 'tags',
	min_chars: 1,
	delay: 400,
	extra_params: {},
	select_first: true,
	result_limit: 10,
	autofill_enabled: false,
	allow_create: false,
	width: 0
};

jQuery.midcom_helper_datamanager2_widget_tags.Select = function (options, input, select) {
	var CLASSES = {
		ACTIVE: "tags_widget_result_item_active"
	};
	
	// Create results
	var element = jQuery("<div>")
		.hide()
		.addClass('tags_widget_results');
	jQuery(input).after( element );

	var list = jQuery("<ul>").appendTo(element).mouseover( function(event) {
		active = jQuery("li", list).removeClass(CLASSES.ACTIVE).index(target(event));
		jQuery(target(event)).addClass(CLASSES.ACTIVE);
	}).mouseout( function(event) {
		jQuery(target(event)).removeClass(CLASSES.ACTIVE);
	}).click(function(event) {
		jQuery(target(event)).addClass(CLASSES.ACTIVE);
		select();
		input.focus();
		return false;
	});
	var listItems,
		active = -1,
		data,
		term = "";
		
	if( options.width > 0 )
		element.css("width", options.width);
		
	function target(event) {
		var element = event.target;
		while(element.tagName != "LI")
			element = element.parentNode;
		return element;
	}

	function moveSelect(step) {
		active += step;
		wrapSelection();
		listItems.removeClass(CLASSES.ACTIVE).eq(active).addClass(CLASSES.ACTIVE);
	};
	
	function wrapSelection() {
		if (active < 0) {
			active = listItems.size() - 1;
		} else if (active >= listItems.size()) {
			active = 0;
		}
	}
	
	function limitNumberOfItems(available) {
		return (options.result_limit > 0) && (options.result_limit < available)
			? options.result_limit
			: available;
	}
	
	function dataToDom() {
		var num = limitNumberOfItems(data.length);
		for (var i=0; i < num; i++) {
			if (!data[i])
				continue;
			function highlight(value) {
				return value.replace(new RegExp("(" + term + ")", "gi"), "<strong>$1</strong>");
			}
			jQuery("<li>").html( highlight(midcom_helper_datamanager2_widget_tags_format_item(data[i].data)) ).appendTo(list);
		}
		listItems = list.find("li");
		if ( options.select_first ) {
			listItems.eq(0).addClass(CLASSES.ACTIVE);
			active = 0;
		}
	}
	
	return {
		display: function(d, q) {
			data = d;
			term = q;
			list.empty();
			dataToDom();
			list.bgiframe();
		},
		next: function() {
			moveSelect(1);
		},
		prev: function() {
			moveSelect(-1);
		},
		hide: function() {
			element.hide();
			active = -1;
		},
		visible : function() {
			return element.is(":visible");
		},
		current: function() {
			return this.visible() && (listItems.filter("." + CLASSES.ACTIVE)[0] || options.select_first && listItems[0]);
		},
		show: function() {
			element.css({
				width: options.width > 0 ? options.width : jQuery(input).width()
			}).show();
		},
		selected: function() {
			return data && data[active];
		}
	};
}

jQuery.midcom_helper_datamanager2_widget_tags.SelectionHolder = function(options, input)
{
	var CLASSES = {
		HOVER: "tags_widget_selection_item_hover",
		DELETED: "tags_widget_selection_item_deleted"
	};

	// Create selection holder element
	var element = jQuery("<div>")
		.hide()
		.addClass('tags_widget_selections');
	jQuery(input).before( element );

	var list = jQuery("<ul>").appendTo(element);

    var list_items = [],
        has_content = false;

	function target(event) {
		var element = event.target;		
		while(element.tagName != "LI")
		{
			element = element.parentNode;		    
		}
		return element;
	}
	
	function can_add(item_id)
	{
	    if (options.selection_limit > 0)
	    {
	        if (list_item.length == options.selection_limit)
	        {
	            return false;
	        }
	    }
	    
	    var existing = false;
        existing = jQuery.grep( list_items, function(n,i){
           return n == item_id;
        });
        
        if (existing == item_id)
        {
            jQuery('#tag_'+item_id,list).Highlight(800, 'yellow');
            return false;
        }
	    
	    return true;
	}
	
	function add(data)
	{
	    //console.log('SelectionHolder add');
	    //console.log('data.id: '+data.id);
	    //console.log('data.name: '+data.name);
	    //console.log('data.color: '+data.color);
	    	    	    
        if (! can_add(data.id))
        {
            return false;
        }
        
        if (!has_content)
        {
            has_content = true;
            element.show();
        }
	    
	    var input_elem_name = options.widget_type_name + "_tags[" + data.id + "]";
        
        data.color = String(data.color).replace("#","");
        
	    var li_elem = jQuery("<li>")
	    .attr({ id: 'tag_'+data.id })
	    .css({background: '#'+data.color})
        .mouseover( function(event) {
    		active = jQuery("li", list).removeClass(CLASSES.HOVER).index(target(event));
    		jQuery(target(event)).addClass(CLASSES.HOVER);
    	}).mouseout( function(event) {
    		jQuery(target(event)).removeClass(CLASSES.HOVER);
    	}).click(function(event) {
    	    var li_element = target(event);
    	    var current_class = jQuery(li_element).attr("deleted");
    	    if (jQuery(li_element).attr("deleted") == "true")
    	    {
        		jQuery(li_element).removeClass(CLASSES.DELETED);
        		restore(data.id);    	        
    	    }
    	    else
    	    {
        		jQuery(li_element).addClass(CLASSES.DELETED);
        		remove(data.id);
    	    }
    		return false;
    	});
	    var span_elem = jQuery("<span>")
	    .html( midcom_helper_datamanager2_widget_tags_format_item(data) )
	    .appendTo(li_elem);
	    var input_elem = jQuery("<input>")
	    .attr({ type: 'hidden', name: input_elem_name, value: 1, id: 'tags-widget_tag_'+data.id })
	    .hide()
	    .appendTo(li_elem);
	    
	    li_elem.appendTo(list);
	    
	    list_items.push(data.id);
	}
	
	function remove(id)
	{
	    jQuery('#tag_'+id+' input', list).attr({ value: 0 });
	    jQuery('#tag_'+id).attr("deleted","true");
	}
	
	function restore(id)
    {
	    jQuery('#tag_'+id+' input', list).attr({ value: id });        
	    jQuery('#tag_'+id).attr("deleted","false");
    }
	
	return {
	    add_item: function(item)
	    {
	        add(item);
	    },
	    del_item: function(item_id)
	    {
	        remove(item_id);
	    }
	}

}

jQuery.midcom_helper_datamanager2_widget_tags.MoveSelection = function(field, start, end)
{
	if( field.createTextRange )
	{
		var selRange = field.createTextRange();
		selRange.collapse(true);
		selRange.moveStart("character", start);
		selRange.moveEnd("character", end);
		selRange.select();
	}
	else if( field.setSelectionRange )
	{
		field.setSelectionRange(start, end);
	}
	else
	{
		if( field.selectionStart )
		{
			field.selectionStart = start;
			field.selectionEnd = end;
		}
	}
	field.focus();
};

function midcom_helper_datamanager2_widget_tags_format_item(item)
{
    var formatted = '';
    
    if (item.name != '')
    {
        formatted = item.name
    }
    else
    {
        formatted = item.id;
    }
    
    // if (   item.name
    //     && (item.name.toLowerCase() != item.id.toLowerCase()))
    // {
    //     formatted = item.name + " (" + item.id + ")";
    // }
    // else if (   item.name
    //          && (item.name.toLowerCase() == item.id.toLowerCase()))
    // {
    //     formatted = item.name;        
    // }
    
    return formatted;
}