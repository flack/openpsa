var current_ui = null;

//need this to append to the sent data if the form was saved or cancelled
if ( form_append === undefined)
{
    var form_append = "&midcom_helper_datamanager2_cancel=Cancel";
}

//check if array is already present to indicate if js/css-file was loaded already
if (added_js_files == undefined)
{
    var added_js_files = {};
    var added_css_files = {};
}

function intercept_clicks(event)
{
    if ($(event.currentTarget).attr('onclick'))
    {
        return true;
    }

    var href = $(event.currentTarget).attr('href');

    if (!href.match(/\/uitab\//))
    {
        return true;
    }
    //for now, only links to plugins will be displayed inside the tab
    if (   !href.match(/\/__mfa\/org\.openpsa/)
        || $(event.currentTarget).hasClass('tab_escape'))
    {
        location.href = href.replace(/\/uitab\/+/, '/');
        event.preventDefault();
        return false;
    }

    if (href.slice(href.length - 1, href.length) != '#')
    {
        jQuery(":not(.ui-tabs-hide) > .tab_div").load(href);

        event.preventDefault();
        return false;
    }
}

//function to add the passed javascript-files
function parse_js(javascripts)
{
    var url = "";
    for (var i = 0 ; i < javascripts.length ; i++)
    {
        url = javascripts[i].match(/src="(.+?)"/)[1];
        if (    added_js_files[javascripts[i]] == undefined
             && (   typeof url == 'undefined'
                 || url == ''
                 || $('script[src="' + url + '"]').length == 0))
        {
            $("head").append(javascripts[i]);
            added_js_files[javascripts[i]] = true;
        }
    }
}
//function to add the passed css_tags
function parse_css(css_tags)
{
    var url = "",
    insertion_point = $('link[rel="stylesheet"]:first');

    for (var i = 0; i < css_tags.length; i++)
    {
        url = css_tags[i].match(/href="(.+?)"/)[1];

        //check if css_file is already loaded
        if (   added_css_files[css_tags[i]] == undefined
            && (   typeof url == 'undefined'
                || url == ''
                || $('link[href="' + url + '"]').length == 0))
        {
            insertion_point.after(css_tags[i]);
            added_css_files[css_tags[i]] = true;
            insertion_point = insertion_point.next();
        }
        else
        {
            insertion_point = $('link[href="' + url + '"]');
        }
   }
}

function modify_content()
{
    //bind click functions so the request can pass if it should saved or cancelled
    $("#tabs input[type=submit]:not(.tab_escape)").bind('click', function(event)
    {
        form_append = "&" + $(event.currentTarget).attr('name') + "=" + $(event.currentTarget).attr('value');
        return true;
    });

    //since this is loaded in a tab - change the submit-function of
    // an occuring form - so the result will be loaded in the tab also
    $("#tabs form:not(.tab_escape)").live("submit", function(event)
    {
        if ($(event.currentTarget).attr('onsubmit'))
        {
            return;
        }
        var send_data = $(this).serialize() + form_append;

        jQuery.ajax(
        {
            data: send_data,
            type: $(this).attr("method"),
            url: $(this).attr("action"),
            success: function(response)
            {
                //write into the visible panel
                $(".ui-tabs-panel:not(.ui-tabs-hide) > .tab_div").replaceWith(response);

                //we have to rebind the click-events so they'll be loaded in the tabs (live() sometimes fails)
                $(".ui-tabs-panel:not(.ui-tabs-hide) > .tab_div a").bind('click', function(event){intercept_clicks(event);})
            }
        });
        return false;
    });
}
