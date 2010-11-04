/**
 * Datamanager 2 "universal chooser" Ajax code
 * Eero af Heurlin, rambo@iki.fi
 * Henri Bergius, henri.bergius@iki.fi
 */

/* We need this to be able to handle global timeouts sensibly */
var midcom_helper_datamanager2_widget_universalchooser_store = new Array();

/**
 * Class for handling all the Ajax magick related to the universal chooser
 */
midcom_helper_datamanager2_widget_universalchooser_handler = Class.create();
midcom_helper_datamanager2_widget_universalchooser_handler.prototype = 
{
    initialize: function(idsuffix)
    {
        /* Initialize properties */
        this.idsuffix = idsuffix;
        this.countdown_msec = 500; /* TODO: Make configurable somehow ? */
        this.timeout = false;
        this.constraints = false;
        this.mode = false;
        this.search = false;
        this.url = false;
        this.createurl = false;
        this.ajax_request = false;
        this.ahah_request = false;
        this.input_element = false;
        this.results_ul = false;
        this.results_div = false;
        this.fieldname = false;
        
        /* And get them values where applicable */
        this.constraints = $F('widget_universalchooser_search_' + this.idsuffix + '_constraints');
        this.mode = $F('widget_universalchooser_search_' + this.idsuffix + '_mode');
        this.url = $F('widget_universalchooser_search_' + this.idsuffix + '_url');
        this.fieldname = $F('widget_universalchooser_search_' + this.idsuffix + '_fieldname');
        this.input_element = $('widget_universalchooser_search_' + this.idsuffix);
        if (createurl_element = $('widget_universalchooser_search_' + this.idsuffix + '_createurl'))
        {
            this.createurl = createurl_element.value;
        }

        /* Create our results div */        
        label_id = $F('widget_universalchooser_search_' + this.idsuffix + '_labelid');
        new Insertion.After(label_id, '<div id="widget_universalchooser_search_resultscontainer_' + this.idsuffix +'" class="universalchooser_search_resultscontainer hidden"><ul id="widget_universalchooser_search_results_' + this.idsuffix +'" style="display: block;" class="universalchooser_search_results"></ul></div>');
        new Insertion.After(this.input_element, '<div id="widget_universalchooser_search_resultscontainer_' + this.idsuffix +'" class="universalchooser_search_resultscontainer hidden"><ul id="widget_universalchooser_search_results_' + this.idsuffix +'" style="display: block;" class="universalchooser_search_results"></ul></div>');
        this.results_ul = $('widget_universalchooser_search_results_' + this.idsuffix);
        this.results_div = $('widget_universalchooser_search_resultscontainer_' + this.idsuffix); 
    },

    create_element: function(name, attrs, style, text)
    {
        /* Shortcut to creating elements, may be redundant with some Prototype functionality, use Prototype where simpler */
        var e = document.createElement(name);
        if (attrs) {
            for (key in attrs) {
                if (key == 'class') {
                    e.className = attrs[key];
                } else if (key == 'id') {
                    e.id = attrs[key];
                } else {
                    e.setAttribute(key, attrs[key]);
                }
            }
        }
        if (style) {
            for (key in style) {
                e.style[key] = style[key];
            }
        }
        if (text) {
            e.appendChild(document.createTextNode(text));
        }
        return e;
    },

    changed: function()
    {
        /* Clear previous timeout if present */
        if (this.timeout)
        {
            window.clearTimeout(this.timeout);
        }
        /* And set new one referring to this instance via the global storage */
        timeout_code = 'midcom_helper_datamanager2_widget_universalchooser_store["' + this.idsuffix + '"].execute();';
        this.timeout = window.setTimeout(timeout_code, this.countdown_msec);
    },

    execute: function()
    {
        this.search = this.input_element.value;
        if (!this.search)
        {
            /* Do not search for empty strings */
            return;
        }
        /* Make sure these are not set, in case of a new search for example */
        Element.removeClassName(this.input_element, 'universalchooser_search_ok');
        Element.removeClassName(this.input_element, 'universalchooser_search_fail');
        this.clear_old_results();
        /* Set the class which should give us a "loading" icon */
        Element.addClassName(this.input_element, 'universalchooser_searching');
        geturl = this.url + '?' + this.constraints + '&search=' + this.search;
        //alert ('getting url: ' + geturl);
        this.ajax_request = new Ajax.Request(geturl, {
            method: 'get', 
            onSuccess: this.ajax_success.bind(this), 
            onFailure: this.ajax_failure.bind(this), 
            onException: this.ajax_exception.bind(this),
        });
        /* If we have createurl set, fetch it on the background as well */
        if (this.createurl)
        {
            get_createurl = this.createurl + '?' + this.constraints + '&search=' + this.search;
            if (!$('widget_universalchooser_search_createcontainer_' + this.idsuffix))
            {
                new Insertion.Bottom(this.results_div, '<div class="universalchooser_search_createcontainer" id="widget_universalchooser_search_createcontainer_' + this.idsuffix +'" ></div>');
            }
            this.ahah_request = new Ajax.Updater(
                'widget_universalchooser_search_createcontainer_' + this.idsuffix,
                get_createurl,
                {
                    method: 'get',
                });
        }
        // For some reason the DM2 field focus screws our class tricks for changing background, figure out why then remove this
        //this.input_element.blur();
    },
    
    clear_old_results: function()
    {
        /* Element.addClassName(this.results_ul, 'hidden'); */
        Element.addClassName(this.results_div, 'hidden');
        if (   this.results_ul
            && this.results_ul.hasChildNodes
            && this.results_ul.removeChild)
        {
            while (this.results_ul.hasChildNodes())
            {
                this.results_ul.removeChild(this.results_ul.firstChild);
            }
        }
    },
    
    ajax_success: function(request)
    {
        //alert('ajax_success called');
        Element.removeClassName(this.input_element, 'universalchooser_searching');
        if (this.ajax_checkerror(request))
        {
            Element.addClassName(this.input_element, 'universalchooser_search_fail');
            return;
        }

        Element.addClassName(this.input_element, 'universalchooser_search_ok');
        /* Display lines in result */
        results = request.responseXML.getElementsByTagName('line');
        /* Element.removeClassName(this.results_ul, 'hidden'); */
        Element.removeClassName(this.results_div, 'hidden');
        if (results.length < 1)
        {
            no_results = this.create_element('li', null, false, 'no results');
            this.results_ul.appendChild(no_results);
            return;
        }
        for (var i=0; i < results.length; i++)
        {
            line = results[i];
            /* TODO: Make more robust */
            key = line.getElementsByTagName('id')[0].firstChild.data;
            title = line.getElementsByTagName('title')[0].firstChild.data;
            this.add_result(key, title);
        }
    },

    add_result: function(key, title)
    {
        title = unescape(title);
        /* Render a result in the results list */
        jsCall = 'javascript:midcom_helper_datamanager2_widget_universalchooser_add_option(\'' + this.idsuffix.replace("'", "\\'") + '\', \'' + key.replace("'", "\\'") + '\', \'' + title.replace("'", "\\'") + '\');';
        result_li = this.create_element('li', null, false);
        result_link = this.create_element('a', 
                            {
                                //'onClick': jsCall,
                                'href': jsCall
                            }, 
                            {'display': 'block'},
                            ''
                            );
        result_li.appendChild(result_link);
        this.results_ul.appendChild(result_li);
        new Insertion.Top(result_link, title);
    },
    
    input_exists: function(name)
    {
        switch (this.mode)
        {
            case 'single':
                inputs = Form.getInputs(this.input_element.form, 'radio', name);
                break;
            case 'multiple':
                inputs = Form.getInputs(this.input_element.form, 'checkbox', name);
                break;
        }
        inputs_array = inputs.entries();

        if (inputs_array.length > 0)
        {
            return true;
        }
        return false;
    },
    
    add_option: function(key, title)
    {
        /* Add the clicked option to the selection list, remember to check mode ('multiple' vs 'single')
           and act accordingly */
        //alert('add_option called, this.idsuffix: ' + this.idsuffix);
        input_id = 'universalchooser_' + this.idsuffix + '_' + key;
        html = '';
        switch (this.mode)
        {
            case 'single':
                if (input_element = $(input_id))
                {
                    // Input is already there trigger a click and then skip.
                    input_element.click();
                    return;
                }
                input_name = this.fieldname;
                html += '<input type="radio" id="' + input_id + '" value="' + key + '" name="' + input_name + '" class="radiobutton"/>\n';
                break;
            case 'multiple':
                input_name = this.fieldname + '[' + key + ']';
                if (this.input_exists(input_name))
                {
                    // Input is already there skip
                    return;
                }
                html += '<input type="checkbox" id="' + input_id + '" value="1" name="' + input_name + '" class="checkbox" />\n';
                break;
        }
        html += '<label for="' + input_id + '">' + unescape(title) + '</label>\n';
        
        new Insertion.Bottom(this.fieldname + '_fieldset', html)
        input_element = $(input_id);
        input_element.click();
    },
    
    ajax_failure: function(request)
    {
        /* This is called on xmlHttpRequest level failure, 
           MidCOM level errors are reported via the XML returned */
        Element.removeClassName(this.input_element, 'universalchooser_searching');
        Element.addClassName(this.input_element, 'universalchooser_search_fail');
        new protoGrowl({type: 'error', title: 'Universal Chooser', message: 'Ajax request level failure'})
        /* TODO: Some kind of error handling ?? */
        return true;
    },
    
    ajax_exception: function(request, exception)
    {
        /* This is called on xmlHttpRequest level exception */
        /* TODO: Some kind of exception handling ? */
        //alert('ajax_exception called');
        new protoGrowl({type: 'error', title: 'Universal Chooser (Ajax exception)', message: exception});
        return this.ajax_failure(request);
    },
    
    ajax_checkerror: function(request)
    {
        statuses = request.responseXML.getElementsByTagName('status');
        if (   statuses.length < 1
            || !statuses[0])
        {
            new protoGrowl({type: 'error', title: 'Universal Chooser', message: 'Status tag not found'})
            return true;
        }
        messages = request.responseXML.getElementsByTagName('errstr');
        status_value = statuses[0].firstChild.data
        message_str = '';

        if (   messages.length > 0
            && messages[0]
            && messages[0].firstChild)
        {
            message_str = messages[0].firstChild.data;
        }
        if (message_str)
        {
            new protoGrowl({type: 'error', title: 'Universal Chooser', message: message_str});
        }
        if (status_value > 0)
        {
            /* No error, so we return false */
            return false;
        }
        /* Default to returning true (yes, there was an error) */
        return true;
    }
}

function midcom_helper_datamanager2_widget_universalchooser_search_onkeyup(idsuffix)
{
    /* TODO: filter out [ctrl|alt]-<something> keypresses somehow */
    /* Initialize handler if not done alreaydy */
    if (!midcom_helper_datamanager2_widget_universalchooser_store[idsuffix])
    {
        //alert('handler for ' + idsuffix + ' not found, initializing it');
        midcom_helper_datamanager2_widget_universalchooser_store[idsuffix] = new midcom_helper_datamanager2_widget_universalchooser_handler(idsuffix);
    }
    /* Then call the handlers method to get on with it */
    midcom_helper_datamanager2_widget_universalchooser_store[idsuffix].changed();
}

function midcom_helper_datamanager2_widget_universalchooser_add_option(idsuffix, key, title)
{
    /* Initialize handler if not done alreaydy */
    if (!midcom_helper_datamanager2_widget_universalchooser_store[idsuffix])
    {
        //alert('handler for ' + idsuffix + ' not found, initializing it');
        midcom_helper_datamanager2_widget_universalchooser_store[idsuffix] = new midcom_helper_datamanager2_widget_universalchooser_handler(idsuffix);
    }
    /* Then call the handlers method to get on with it */
    midcom_helper_datamanager2_widget_universalchooser_store[idsuffix].add_option(key, title);
}

