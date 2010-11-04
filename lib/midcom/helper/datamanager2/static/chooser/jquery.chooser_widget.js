/** 
 * midcom_helper_datamanager2_widget_chooser_widget - jQuery plugin 1.0
 * 
 * Jerry Jalava <jerry.jalava@gmail.com>
 * Arttu Manninen <arttu@kaktus.cc>
 */

/** 
 * @name midcom_helper_datamanager2_widget_chooser_widget
 * @cat Plugins/Midcom
 * @type jQuery
 * @param String|Object url an URL to data backend
 * @param Map options Optional settings
 * @option String widget_id The id of the widget instance. Default: chooser_widget
 * @option Number result_limit Limit the number of items in the results box. Is also send as a 'limit' parameter to backend on request. Default: 10
 * @option Boolean renderer_callback If this is set to true, then when user presses tab it creates a tag from current input value if we don't have any results. Default: false
 * @option String id_field Name of the objects field to be used as identifier. Default: guid
 * @option String creation_handler Url which to load when creating new object. Default: null
 * @option String creation_default_key key which will be used when sending the query value to the creation handler. Default: 'title'
 * @option Boolean creation_mode If this is set to true, a create new -button is showed. Default: false
 * @option Object format_items Map of key value pairs of formatters to be applied to items. Default: null
 */
 


jQuery.fn.extend(
{
    midcom_helper_datamanager2_widget_chooser_widget: function(url, options)
    {
        options = jQuery.extend({}, jQuery.midcom_helper_datamanager2_widget_chooser.defaults, {
            url: url || null
        }, options);
        // return this.each(function(){
        return new jQuery.midcom_helper_datamanager2_widget_chooser(this, options);
        // });
    },
    midcom_helper_datamanager2_widget_chooser_add_result_item: function(data, item)
    {
        return this.trigger('add_result_item',[data, item]);
    },
    midcom_helper_datamanager2_widget_chooser_remove_result_item: function(item_id)
    {
        return this.trigger('remove_result_item',[item_id]);
    },
    midcom_helper_datamanager2_widget_chooser_adjust_height: function()
    {
        return this.trigger('adjust_height');
    }
});

jQuery.midcom_helper_datamanager2_widget_chooser = function(input, options)
{
    var KEY =
    {
        UP: 38,
        DOWN: 40,
        DEL: 46,
        TAB: 9,
        RETURN: 13,
        ESC: 27,
        COMMA: 188
    };

    var CLASSES =
    {
        LOADING: 'chooser_widget_search_loading',
        IDLE: 'chooser_widget_search_idle',
        FAILED: 'chooser_widget_search_failed'
    };
    
    /** 
     * Timeout or when the results will be automatically hidden
     * 
     * @var int timeout
     */
    var timeout;
    
    /** 
     * Time when the unused results will be hidden after the mouse out
     * 
     * @var int blurTimeout
     */
    var blurTimeout;
    
    /** 
     * 
     * 
     * @var String previousValue
     */
    var previousValue = '';
    
    /** 
     * Switch to determine if the focus is in this chooser field
     * 
     * @var int hasFocus
     */
    var hasFocus = 0;
    
    /** 
     * Code of the last key press
     * 
     * @var String lastKeyPressCode
     */
    var lastKeyPressCode;
    
    /** 
     * Last used term
     * 
     * @var String last_term
     */
    var last_term;
    
    /** 
     * Create button for the creation mode
     * 
     * @var mixed create_button
     */
    var create_button = null;
    var creation_dialog = null;
    var input_element = jQuery(input).attr('autocomplete', 'off').addClass(CLASSES.IDLE);

    var isFrozen = (input_element.attr('type') == 'hidden');
    
    options.is_frozen = isFrozen;

    /** 
     * Object to determine which is the last list item. New items will be inserted after this.
     * 
     * @var DOM insert_after
     */
    var insert_after = input_element;
        
    if (options.creation_mode)
    {
        enable_creation_mode();
        insert_after = creation_dialog;
    }

    var results_holder = jQuery.midcom_helper_datamanager2_widget_chooser.ResultsHolder(options, input, insert_after);
    
    if (!isFrozen)
    {
        input_element.show();
        hideResultsNow();
    }
    
    jQuery('.widget_chooser_static_items_table').hide();
    jQuery('.chooser_widget_existing_item_static_input').each(function()
    {
        this.checked = false;
    });

    if (options.default_search)
    {
        hasFocus++;
        onChange(0, false);
    }
    
    /** 
     * On the change event of the chooser search field
     * 
     * @param mixed crap             Not used for anything at the moment
     * @param mixed skipPrevCheck    Not used for anything at the moment
     */
    function onChange(crap, skipPrevCheck)
    {
        if (skipPrevCheck)
        {
            if (lastKeyPressCode == KEY.DEL)
            {
                return;
            }
        }
        
        var currentValue = input_element.val();
        if (skipPrevCheck)
        {
            if (currentValue == previousValue)
            {
                return;
            }
        }
        
        previousValue = currentValue;
        
        if (currentValue.length >= options.min_chars)
        {
            currentValue = currentValue.toLowerCase();
            request(currentValue, receiveData, stopLoading);
        }
        else
        {
            stopLoading();
        }
    };
    
    /** 
     * Hide the result set after the determined timeout
     */
    function hideResults()
    {
        clearTimeout(timeout);
        timeout = setTimeout(hideResultsNow, 200);
    };
    
    /** 
     * Hide the results immediately
     */
    function hideResultsNow()
    {
        results_holder.hide();
        clearTimeout(timeout);
    };
    
    /** 
     * Handle the received data
     * 
     * @param String q      Query
     * @param Array data    Returned DOM objects
     */
    function receiveData(q, data)
    {
        if (   data
            && data.length
            && hasFocus)
        {
            stopLoading();
            results_holder.display(data, q);
            results_holder.show();
        }
        
        input_element.removeClass(CLASSES.LOADING);
        input_element.removeClass(CLASSES.FAILED);
        input_element.addClass(CLASSES.IDLE);
    };
    
    /** 
     * Request the results
     * 
     * @param String term       Search term
     * @param Function success  Function to be triggered upon success
     * @param Function failure  Function to be triggered upon failure
     * 
     */
    function request(term, success, failure)
    {
        last_term = term;
        
        input_element.removeClass(CLASSES.IDLE);
        input_element.removeClass(CLASSES.FAILED);
        input_element.addClass(CLASSES.LOADING);
        
        var data = false;
        
        term = term.toLowerCase();
        
        jQuery.ajax(
        {
            type: 'POST',
            url: options.url,
            dataType: 'xml',
            data: jQuery.extend(
            {
                query: term,
                limit: options.result_limit
            }, {extra_params: options.extra_params}),
            error: function(obj, type, expobj)
            {
                failure(type, expobj);
            },
            success: function(data)
            {
                var parsed = parse(data);
                success(term, parsed);
            }
        });
    }
    
    /** 
     * Called upon loading stop
     * 
     * @param Function type      Function that will be triggered upon loading stop
     * @param Function expobj    Function that will be used to export objects
     */
    function stopLoading(type, expobj)
    {
        input_element.removeClass(CLASSES.IDLE);
        input_element.removeClass(CLASSES.FAILED);
        input_element.removeClass(CLASSES.LOADING);
        if (type == 'undefined')
        {
            input_element.addClass(CLASSES.IDLE);
        }
        else
        {
            input_element.addClass(CLASSES.FAILED);
        }
    }
    
    /**
     * Parse the loaded results
     * 
     * @param Array data    Result data
     * @return Array        Containing parsed data
     */
    function parse(data)
    {
        var results = [];
        jQuery('result', data).each(function(idx) {
            var rel_this = jQuery(this);
            
            results[idx] = {
                id:rel_this.find('id').text(),
                guid:rel_this.find('guid').text()
            };
            
            jQuery.each(options.result_headers,function(i,h)
            {
                results[idx][h.name] = rel_this.find(h.name).text();
            });
        });

        var parsed = [];
        jQuery(results).each(function(idx)
        {
            var item = results[idx];
            if (item)
            {
                parsed[parsed.length] =
                {
                    id: item.id,
                    data: item
                };
            }
        });

        return parsed;
    }
    
    // Input field events
    input_element
        .keydown(function(event)
        {
            // track last key pressed
            lastKeyPressCode = event.keyCode;
            switch(event.keyCode)
            {
                case KEY.UP:
                    event.preventDefault();
                    if ( results_holder.visible() )
                    {
                        results_holder.prev();
                    }
                    else
                    {
                        onChange(0, true);
                    }
                    break;
                    
                case KEY.DOWN:
                    event.preventDefault();
                    if ( results_holder.visible() )
                    {
                        results_holder.next();
                    }
                    else
                    {
                        onChange(0, true);
                    }
                    break;
                    
                case KEY.RETURN:
                    event.preventDefault();
                    results_holder.select_current();
                    break;
                    
                case KEY.ESC:
                    break;
                    
                default:
                    clearTimeout(timeout);
                    timeout = setTimeout(onChange, options.delay);
                    break;
            }
        })
        .keypress(function(event)
        {
            // having fun with opera - remove this binding and Opera submits the form when we select an entry via return
            switch(event.keyCode)
            {
                case KEY.RETURN:
                    event.preventDefault();
                    break;
            }
        })
        .focus(function()
        {
            // track whether the field has focus, we shouldn't process any
            // results if the field no longer has focus
            clearTimeout(blurTimeout);
            hasFocus++;
        })
        .blur(function()
        {
            hasFocus = 0;
            input_element.removeClass(CLASSES.LOADING);
            input_element.removeClass(CLASSES.FAILED);
            input_element.addClass(CLASSES.IDLE);
            clearTimeout(blurTimeout);
            blurTimeout = setTimeout(results_holder.clear_unselected, options.delay);
        })
        .click(function()
        {
            // show results when clicking in a focused field
            if ( hasFocus++ > 1 && !results_holder.visible() )
            {
                onChange(0, true);
            }
        })
        .bind('activate', function(event, data)
        {
            input_element.focus();
            results_holder.activate_item(data);
        })
        .bind('add_result_item', function(event, data, item)
        {
            results_holder.add_item(data, item);
        })
        .bind('remove_result_item', function(event, item_id)
        {
            results_holder.del_item();
        })
        .bind('adjust_height', function()
        {
            results_holder.adjust_height();
        });
    
    /**
     * Enable the creation mode
     */
    function enable_creation_mode()
    {
        input.css({float: 'left'});
        
        creation_dialog = jQuery('#' + options.widget_id + '_creation_dialog');
        create_button = jQuery('#' + options.widget_id + '_create_button');
        create_button.css('display', 'block');
        create_button.bind('click', function()
        {
            if (jQuery('#' + options.widget_id + '_creation_dialog').css('display') == 'block')
            {
               jQuery('#' + options.widget_id + '_creation_dialog').hide();
               return;
            }
            var creation_url = options.creation_handler;
            
            creation_url += '?chooser_widget_id=' + options.widget_id;
            if (last_term != 'undefined')
            {
                creation_url += '&defaults[' + options.creation_default_key + ']=' + last_term;
            }
            
            var iframe = ['<iframe src="' + creation_url + '"'];
            iframe.push('id="' + options.widget_id + 'chooser_widget_creation_dialog_content"');
            iframe.push('class="chooser_widget_creation_dialog_content"');
            iframe.push('frameborder="0"');
            iframe.push('marginwidth="0"');
            iframe.push('marginheight="0"');
            iframe.push('width="600"');
            iframe.push('height="450"');
            iframe.push('scrolling="auto"');
            iframe.push('/>');

            var iframe_html = iframe.join(' ');
            jQuery('.chooser_widget_creation_dialog_content_holder', creation_dialog).html(iframe_html);
            
            //jQuery('#' + options.widget_id + '_creation_dialog').jqmShow();
            jQuery('#' + options.widget_id + '_creation_dialog').show();
        });
    }
};

jQuery.midcom_helper_datamanager2_widget_chooser.defaults =
{
    widget_id: 'chooser_widget',
    delay: 400,
    result_limit: 10,
    renderer_callback: false,
    allow_multiple: true,
    id_field: 'guid',
    creation_mode: false,
    creation_handler: null,
    creation_default_key: null,
    sortable: false,
    format_items: null,
    is_frozen: false
};

jQuery.midcom_helper_datamanager2_widget_chooser.ResultsHolder = function(options, input, insert_after)
{
    var CLASSES =
    {
        HOVER: 'chooser_widget_result_item_hover',
        ACTIVE: 'chooser_widget_result_item_active',
        INACTIVE: 'chooser_widget_result_item_inactive',
        DELETED: 'chooser_widget_result_item_deleted'
    };

    // @todo Find a way to define them only once
    var INPUTCLASSES =
    {
        LOADING: 'chooser_widget_search_loading',
        IDLE: 'chooser_widget_search_idle',
        SAVED: 'chooser_widget_search_saved',
        DELETED: 'chooser_widget_search_deleted'
    };


    // Create results element
    var element = jQuery('<div id="' + options.widget_id + '_results"></div>')
        .addClass('chooser_widget_results_holder');
    
    jQuery(insert_after).after( element );

    var headers = jQuery('<ul class="chooser_widget_headers"></ul>').appendTo(element);
    var list = jQuery('<ul class="chooser_widget_results"></ul>').appendTo(element);
    
    var has_content = false,
        list_items = [],
        selected_items = [],
        list_jq_items,
        active = -1,
        data,
        block_width = 90;

    function target(event)
    {
        var element = event.target;
        if (element)
        {
            if (element.tagName == 'UL')
            {
                element = jQuery(element).find('li').eq(0);
                return element;
            }
            
            while(element.tagName != 'LI')
            {
                element = element.parentNode;
            }
        }
        return element;
    }
    
    create_headers();
    
    function create_headers()
    {
        if (options.result_headers.length > 1)
        {
            block_width = (100 / options.result_headers.length) - options.result_headers.length - 2;
        }
        
        jQuery.each( options.result_headers, function(i,n)
        {
            n.name = n.name.replace(/\./, '_');
            
            var li_elem = jQuery('<li>')
                .addClass('chooser_widget_header_item')
                .css(
                {
                    width: block_width + '%'
                })
            var item_content = jQuery('<div>')
                .addClass(n.name)
                .attr('search', n.name)
                .click(function()
                {
                    if (!options.sortable)
                    {
                        return false;
                    }
                    
                    if (jQuery(this).attr('reverse') == 'reverse')
                    {
                        var reverse = true;
                    }
                    else
                    {
                        var reverse = false;
                    }
                    
                    // Sort the entries
                    jQuery(this).parents('div.chooser_widget_results_holder').find('ul.chooser_widget_results li div.' + jQuery(this).attr('search')).sort(reverse);
                    jQuery(this).parents('ul li').find('div[reverse="reverse"]').attr('reverse', '');
                    
                    if (!reverse)
                    {
                        jQuery(this).attr('reverse', 'reverse');
                    }
                })
                .html( n.title )
                .appendTo(li_elem);
            li_elem.appendTo(headers);
        });
    }
    
    function dataToDom()
    {
        for (var i = 0; i < data.length; i++)
        {
            if (!data[i])
                continue;
            
            add(data[i].data);
        }
        list_jq_items = list.find('li');
        if ( options.select_first )
        {
            list_jq_items.eq(0).addClass(CLASSES.ACTIVE);
            active = 0;
            var active_id = list_items[0];

            jQuery('#' + options.widget_id + '_result_item_' + active_id + '_input', list).attr({ value: active_id });
            jQuery('#' + options.widget_id + '_result_item_' + active_id).attr('keep_on_list','true');
            selected_items.push(active_id);
        }
    }
    
    function can_add(id)
    {
        //console.log('can_add id: ' + id);
        
        var existing = false;
        existing = jQuery.grep( list_items, function(n,i)
       {
           return n == id;
        });
        if (existing == id)
        {
            // jQuery('#' + options.widget_id + '_result_item_' + id,list).hide('fast',function(){
            //    jQuery('#' + options.widget_id + '_result_item_' + id,list).show('fast');
            //  });
            return false;
        }
        
        return true;
    }
    
    function add(data, item)
    {
        // console.log('ResultsHolder add');
        // console.log(data);
        //console.log('data.id: ' + data.id);
        //console.log('data.guid: ' + data.guid);
        
        var n = null;
        
        var item_id = data[options.id_field];
        //console.log('options.id_field: ' + options.id_field);
        //console.log('item_id: ' + item_id);
        
        // var static_row = jQuery('#' + options.widget_id + '_existing_item_' + item_id + '_row');
        // if (typeof static_row[0] != 'undefined')
        // {
        //     var static_input = jQuery('#' + options.widget_id + '_existing_item_' + item_id + '_input');
        //     static_input.delete();
        //     static_row.delete();
        // }
        
        if (! can_add(item_id))
        {
            //console.log("Can't add!");
            return false;
        }
        
        //console.log('Can add!');
        
        if (! has_content)
        {
            has_content = true;
            element.show();
        }
        
        
        var input_elem_name = options.widget_id + '_selections[' + item_id + ']';
        
        var li_elem = jQuery('<li>')
            .attr({ id: options.widget_id + '_result_item_' + item_id })
            .attr('deleted','false')
            .attr('keep_on_list','false')
            .attr('pre_selected','false')
            .addClass('chooser_widget_result_item')
            .click(function(event)
            {
                if (options.is_frozen)
                {
                    return;
                }
                var li_element = target(event);
                jQuery('#' + options.widget_id + '_search_input').focus();
                var current_keep_status = jQuery(li_element).attr('keep_on_list');
                var current_delete_status = jQuery(li_element).attr('deleted');
                var current_presel_status = jQuery(li_element).attr('pre_selected');
                
                if (current_keep_status == 'true')
                {
                    if (current_delete_status == 'false')
                    {
                        jQuery(li_element).removeClass(CLASSES.ACTIVE);
                        if (current_presel_status == 'true')
                        {
                            remove(item_id);
                        }
                        else
                        {
                            inactivate(item_id);
                        }
                    }
                    else
                    {
                        if(options.allow_multiple)
                        {
                            restore(item_id);
                        }
                        else
                        {
                            activate(item_id);
                        }
                    }
    
                    return false;
                }
                else
                {
                    activate(item_id);
                }
            })
            .mouseover( function(event)
            {
                if (jQuery(target(event)).attr('modified') != 'true')
                {
                    var jq_elem = jQuery(target(event)).addClass(CLASSES.HOVER);
                }
            })
            .mouseout( function(event)
            {
                if (jQuery(target(event)).attr('modified') == 'true')
                {
                    jQuery(target(event)).attr('modified', 'false')
                }
                jQuery(target(event)).removeClass(CLASSES.HOVER);
            });
        
        if (data['pre_selected'])
        {
            li_elem.attr('pre_selected','true');
        }
        
        if (   options.renderer_callback
            && typeof item != 'undefined')
        {
            //console.log('use renderer');
            // PONDER:  How should we really handle the renderer_callback rendering?
            //          We could use custom javascript function, or require the data
            //          object to contain a content field which is already formatted html...
            var item_content = jQuery('<div>')
                .html(item)
                .appendTo(li_elem);
            
            var input_elem = jQuery('<input type="hidden" />')
                .attr({ name: input_elem_name, value: 0, id: options.widget_id + '_result_item_' + item_id + '_input' })
                .appendTo(li_elem);
        }
        else
        {
            var item_content = midcom_helper_datamanager2_widget_chooser_format_item(data,options,block_width)
                .appendTo(li_elem);
            
            var input_elem = jQuery('<input type="hidden" />')
                .attr({ name: input_elem_name, value: 0, id: options.widget_id + '_result_item_' + item_id + '_input' })
                .appendTo(li_elem);
        }
        
        // Add drag bar
        jQuery('<div>')
            .addClass('dragbar')
            .prependTo(li_elem);
        
        if (options.sortable)
        {
            jQuery('<input type="hidden" />')
                .attr('name', options.widget_id + '[sortable][]')
                .attr('value', item_id)
                .appendTo(li_elem);
        }
        
        li_elem.appendTo(list);
        list_items.push(item_id);
        
        if (options.sortable)
        {
            jQuery(list).create_sortable();
        }
    }

    function adjust_height(cleanup)
    {
	if(!cleanup)
        {
            cleanup = false
        }
        var elem_offset= list.offset().top;
        var elem_height= list.height();
        var viewport_top = jQuery(window).scrollTop();
        var viewport_height = jQuery(window).height();
        var viewport_bottom = viewport_top + jQuery(window).height();

        /*
        console.log('elem_offset: ' + elem_offset);
        console.log('elem_height: ' + elem_height);
        console.log('viewport_top ' + viewport_top);
        console.log('viewport_height ' + viewport_height);
        console.log('viewport_bottom: ' + viewport_bottom);
        */
        if (   elem_offset < viewport_bottom 
            && elem_offset + elem_height > viewport_bottom)
        {
            var height = viewport_bottom - elem_offset;
            if (height < jQuery('li:first-child', list).height())
            {
                height = jQuery('li:first-child', list).height();
            }

            list.height(height);
        }
        else if (elem_height > viewport_height)
        {
            list.height(viewport_height - 20);
        }
        else if (cleanup)
        {
            list.css('height', 'auto');
            adjust_height();
        }
    }

    /**
     * 
     * 
     * @param int step
     */
    function moveSelect(step)
    {
        active += step;
        wrapSelection();
        list_jq_items.removeClass(CLASSES.HOVER);

        var jq_elem = list_jq_items.eq(active);
        if (jq_elem.attr('class') != CLASSES.ACTIVE)
        {
            jq_elem.addClass(CLASSES.HOVER);
        }

        var elem_offset= jq_elem.offset().top;
        var elem_height = jq_elem.height();
        var elem_bottom = elem_offset + elem_height;
        var list_offset= list.offset().top;
        var list_scrolltop = list.scrollTop();
        var list_height = list.height();
        var list_bottom = list_offset + list_height;
        /*
        console.log('elem_offset: ' + elem_offset);
        console.log('elem_height: ' + elem_height);
        console.log('elem_bottom: ' + elem_bottom);
        console.log('list_offset ' + list_offset);
        console.log('list_scrolltop ' + list_scrolltop);
        console.log('list_height ' + list_height);
        console.log('list_bottom: ' + list_bottom);
        */
        if (elem_bottom > list_bottom)
	{
	    list.scrollTop((elem_bottom - list_bottom) + list_scrolltop);
	}
        else if (elem_offset <= list_offset)
        {
            list.scrollTop(list_scrolltop - (list_offset - elem_offset));
        }
    };
    
    /**
     * 
     */
    function wrapSelection()
    {
        if (active < 0)
        {
            active = list_jq_items.size() - 1;
        }
        else if (active >= list_jq_items.size())
        {
            active = 0;
        }
    }
    
    /**
     *
     *
     * @param String id     ID of the chooser widget
     */
    function remove(id)
    {
        jQuery('#' + options.widget_id + '_result_item_' + id + '_input', list).attr({ value: 0 });
        
        jQuery('#' + options.widget_id + '_result_item_' + id)
            .removeClass(CLASSES.ACTIVE)
            .removeClass(CLASSES.INACTIVE)
            .addClass(CLASSES.DELETED)
            .attr('deleted','true')
            .attr('modified', 'true');        
        selected_items = jQuery.grep(selected_items, function(n,i)
        {
            return n != id;
        });

        if (!options.allow_multiple)
        {
            // Indicate the status in the input field
            jQuery(input)
                .removeClass(INPUTCLASSES.LOADING)
                .removeClass(INPUTCLASSES.FAILED)
                .removeClass(INPUTCLASSES.IDLE)
                .removeClass(INPUTCLASSES.SAVED)
                .addClass(INPUTCLASSES.DELETED);
            
            // Hide results list
            jQuery('#' + options.widget_id + '_results').hide();
        }

    }
    
    /**
     * 
     * 
     * @param String id     ID of the chooser widget
     */
    function restore(id)
    {
        jQuery('#' + options.widget_id + '_result_item_' + id + '_input', list).attr({ value: id });
        
        jQuery('#' + options.widget_id + '_result_item_' + id)
            .removeClass(CLASSES.DELETED)
            .removeClass(CLASSES.INACTIVE)
            .addClass(CLASSES.ACTIVE)
            .attr('deleted','false')
            .attr('modified', 'true');        
        selected_items.push(id);
    }

    /**
     * Activate an item
     * 
     * @param String id     ID of the chooser widget
     */
    function activate(id, ignore_modification)
    {
        if (!options.allow_multiple)
        {
            // Remove activation from the previously selected list element
            var previous_selection = jQuery('#' + options.widget_id + '_results li.' + CLASSES.ACTIVE)
            previous_selection.click();
            previous_selection.attr('modified', 'false');
        }
        
        jQuery('#' + options.widget_id + '_result_item_' + id + '_input', list).attr({ value: id });

        jQuery('#' + options.widget_id + '_result_item_' + id)
            .removeClass(CLASSES.DELETED)
            .removeClass(CLASSES.INACTIVE)
            .removeClass(CLASSES.HOVER)
            .addClass(CLASSES.ACTIVE)
            .attr('keep_on_list','true')
            .attr('deleted','false');
        if (!ignore_modification)
        {
            jQuery('#' + options.widget_id + '_result_item_' + id).attr('modified', 'true');
        }
        selected_items.push(id);

        if (!options.allow_multiple)
        {
            // Copy the text value of the selected entry into the search field
            var text = "";

            jQuery('#' + options.widget_id + '_result_item_' + id + ' .chooser_widget_item_part:not(.internal)').each( function (i)
            {
                text += jQuery(this).text() + ", ";
            });
            
            text = text.substr(0, text.length - 2);
            text = text.replace(/^, /, "");

            jQuery(input).val(text);

            // increase the field size if necessary
            var field_length = jQuery(input).width();
            var text_length = text.length * 7;
            if (field_length < text_length)
            {
                jQuery(input).width(text_length)
            }

            // Indicate the status in the input field
            jQuery(input)
                .removeClass(INPUTCLASSES.LOADING)
                .removeClass(INPUTCLASSES.FAILED)
                .removeClass(INPUTCLASSES.IDLE)
                .removeClass(INPUTCLASSES.DELETED)
                .addClass(INPUTCLASSES.SAVED);
            
            // Hide results list (if widget isn't frozen)
            if (!options.is_frozen)
            {
                jQuery('#' + options.widget_id + '_results').hide();
            }
        }

    }
    
    /**
     * Inactivate an item
     *
     * @param String id     ID of the chooser widget
     */
    function inactivate(id)
    {
        jQuery('#' + options.widget_id + '_result_item_' + id + '_input', list).attr({ value: 0 });
        
        jQuery('#' + options.widget_id + '_result_item_' + id)
            .removeClass(CLASSES.DELETED)
            .removeClass(CLASSES.ACTIVE)
            .addClass(CLASSES.INACTIVE)
            .attr('keep_on_list','false')
            .attr('deleted','false')
            .attr('modified', 'true');        
        //selected_items.push(id);

        selected_items = jQuery.grep( selected_items, function(n,i)
        {
            return n != id;
        });
    }
    
    /**
     * Delete unselected items from the list
     */
    function delete_unseleted_from_list()
    {
        list_jq_items = list.find('li');
        var removed_items = [];
        jQuery.each( list_items, function(i,n)
        {
            if (n != undefined)
            {
                if (jQuery('#' + options.widget_id + '_result_item_' + n).attr('keep_on_list') == 'false')
                {
                    jQuery('#' + options.widget_id + '_result_item_' + n).remove();
                    removed_items.push(n);
                }
            }
        });
        jQuery.each( removed_items, function(i,n)
        {
            list_items = unset(list_items, n, false, true);
        });
        jQuery('#' + options.widget_id + '_results li.' + CLASSES.HOVER).removeClass(CLASSES.HOVER);
        active = -1;
        
        if (   options.sortable
            && jQuery(list).attr('sortable'))
        {
            // Get rid of the cached positions and create the sortable again
            jQuery(list).sortable('destroy');
            jQuery(list).create_sortable();
        }
        if (jQuery(list_items).size() == 0)
        {
            jQuery('#' + options.widget_id + '_results').hide();
        }
    }
    
    /**
     * 
     * 
     * @param Array array
     * @param String valueToUnset
     */
    function unset(array, valueToUnset, valueOrIndex, isHash)
    {
        var output = new Array(0);
        for (var i in array)
        {
            if (! valueOrIndex)
            {
                if (array[i] == valueToUnset)
                {
                    continue;
                };
                if (!isHash)
                {
                    output[++output.length-1] = array[i];
                }
                else
                {
                    output[i] = array[i];
                }
            }
            else
            {
                if (i == valueToUnset)
                {
                    continue;
                };
                if (! isHash)
                {
                    output[++output.length-1] = array[i];
                }
                else
                {
                    output[i] = array[i];
                }
            }
        }

        return output;
    }
    
    return {
        display: function(d)
        {
            delete_unseleted_from_list();
            data = d;
            dataToDom();
        },
        add_item: function(data, item)
        {
            add(data, item);
            var item_id = data[options.id_field];
            activate(item_id, 'true');
        },
        del_item: function(item)
        {
            var item_id = item[options.id_field];
            remove(item_id);
        },
        activate_item: function(item)
        {
            if(item)
            {
                var item_id = item[options.id_field];
                activate(item_id);
            }
        },
        visible : function()
        {
            return element.is(':visible');
        },
        next: function()
        {
            moveSelect(1);
        },
        prev: function()
        {
            moveSelect(-1);
        },
        hide: function()
        {
            element.hide();
            active = -1;
        },
        current: function()
        {
            return this.visible() && (list_jq_items.filter('.' + CLASSES.ACTIVE)[0] || options.select_first && list_jq_items[0]);
        },
        show: function()
        {
            element.show();
            adjust_height();
        },
        select_current: function()
        {
            jQuery('.' + CLASSES.HOVER).click();
        },
        clear_unselected: function()
        {
            delete_unseleted_from_list();
            adjust_height(true);
        },
        adjust_height: function()
        {
            adjust_height();
        }
    };
};

jQuery.midcom_helper_datamanager2_widget_chooser.MoveSelection = function(field, start, end)
{
    if( field.createTextRange )
    {
        var selRange = field.createTextRange();
        selRange.collapse(true);
        selRange.moveStart('character', start);
        selRange.moveEnd('character', end);
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

function midcom_helper_datamanager2_widget_chooser_format_item(item, options, block_width)
{
    var formatted = '';
    var item_parts = jQuery('<div>')
        .attr({ id: options.widget_id + '_result_item_parts_' + item.id })
        .addClass('chooser_widget_result_item_parts');
    
    var item_dragger = jQuery('<div>')
        .attr({id: options.widget_id + '_result_item_dragger_' + item.id})
        .addClass('chooser_widget_results_item_dragger')
        .appendTo(item_parts);

    var item_content = jQuery('<div>')
        .addClass('chooser_widget_item_part')
        .addClass('internal')
        .html( item.id )
        .appendTo(item_parts);

    jQuery.each(options.result_headers, function(i,n)
    {
        var value = null;
        
        if (   options.format_items != null
            && typeof(options.format_items[n.name]) != 'undefined')
        {
            value = midcom_helper_datamanager2_widget_chooser_format_value(options.format_items[n.name], item[n.name]);
        }

        value = midcom_helper_datamanager2_widget_chooser_format_value('unescape', item[n.name]);
        
        item_content = jQuery('<div>')
            .addClass('chooser_widget_item_part')
            .addClass(n.name)
            .attr(
            {
                title: midcom_helper_datamanager2_widget_chooser_format_value('html2text', value)
            })
            .css(
            {
                width: block_width + '%'
            })
            .html( value )
            .appendTo(item_parts);
    });

    item_content = jQuery('<div>')
        .addClass('chooser_widget_item_part_status')
        .html( '&nbsp;' )
        .appendTo(item_parts);
    
    return item_parts;
}

function midcom_helper_datamanager2_widget_chooser_format_value(format, value)
{
    var format = format || 'unescape';
    var formated = null;
    
    switch(format)
    {
        case 'html2text':
            formatted = value;
            formatted = formatted.toString().replace(/(\&gt\;)/g, '/');
            break;
        case 'unescape':
            formatted = unescape(value);
            break;
        case 'datetime':
            var date = new Date();
            date.setTime((value*1000));
            var date_str = date.getFullYear() + '-' + date.getMonth() + '-' + date.getDate() + ' ' + date.getHours() + ':' + date.getMinutes();
            
            formatted = date_str;
            break;
        default:
            formatted = value;
            break;
    }
    
    if (formatted == null)
    {
        formatted = value;
    }
    if (formatted == '')
    {
        formatted = '&nbsp;';
    }
        
    return formatted;
}

/**
 * Create the sortable list
 */
jQuery.fn.create_sortable = function()
{
    // If there is less than two items in the list, sortable shouldn't be created
    if (jQuery(this).find('li').size() <= 1)
    {
        if (jQuery(this).attr('sortable'))
        {
            jQuery(this).sortable('destroy');
            jQuery(this).parent().find('ul.chooser_widget_headers').removeClass('ui-sortable');
        }
        return;
    }
    
    if (jQuery(this).attr('sortable'))
    {
        jQuery(this).sortable('destroy');
    }
    
    jQuery(this).sortable(
    {
        handle: 'div.chooser_widget_results_item_dragger',
        containment: jQuery(this)
    });
    
    if (!jQuery(this).attr('sortable'))
    {
        jQuery(this).attr('sortable');
    }
    
    // Add the sortable class also to the result headers to maintain consistency
    jQuery(this).parent().find('ul.chooser_widget_headers').addClass('ui-sortable');
}

/**
 * Sorting function.
 * 
 * @param String reversed    Should the direction be reversed
 */
jQuery.fn.sort = function(reversed)
{
    // This in the future will sort the results
}
