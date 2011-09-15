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

//function to add the passed javascript elements
function parse_js(javascripts)
{
    $.each(javascripts, function(index, jscall)
    {
        if (   typeof jscall.url !== 'undefined'
            && $('script[src="' + jscall.url + '"]').length == 0)
        {
            $("head").append('<script type="text/javascript" src="' + jscall.url + '"></script>');
        }
        else
        {
            $('head').append('<script type="text/javascript">' + jscall.content + '</script>');
        }
    });
}
//function to add the passed css_tags
function parse_css(css_tags)
{
    var insertion_point = $('link[rel="stylesheet"]:first');
    $.each(css_tags, function(index, data)
    {
        if (   typeof data.type === 'undefined'
            || typeof data.href === 'undefined'
            || data.type !== 'text/css')
        {
            return;
        }
        if ($('link[href="' + data.href + '"]').length != 0)
        {
            insertion_point = $('link[href="' + data.href + '"]');
        }
        else
        {
            var tag = '<link';
            $.each(data, function(key, value)
            {
                tag += ' ' + key + '="' + value + '"';
            });
            tag += ' />';
            insertion_point.after(tag);
            insertion_point = insertion_point.next();
        }
    });
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
