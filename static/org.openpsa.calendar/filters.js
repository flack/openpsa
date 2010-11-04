/**
 * Calendar filter selector checkbox handler
 * Henri Bergius, henri.bergius@iki.fi
 * 
 * @todo This file is unused
 */
function org_openpsa_calendar_filters_makeEditable()
{
    var class = 'org_openpsa_calendar_filters_person';
    subscriptionFields = document.getElementsByClassName(class);

    for (i = 0; i < subscriptionFields.length; i++)
    {
        // Start watching for clicks
        Event.observe(this.subscriptionFields[i], 'click', org_openpsa_calendar_filters_watcher, false);
    }
}

function org_openpsa_calendar_filters_watcher(event)
{
    element = event.target;
    if (element.checked == true)
    {
        // We're now subscribed, remove subscription
        org_openpsa_calendar_filters_post(element, 'add');
    }
    else
    {
        // We're not subscribed, add subscription
        org_openpsa_calendar_filters_post(element, 'remove');
    }
}

function org_openpsa_calendar_filters_post(element, action)
{
    // Send the form values to server for preview rendering
    Element.addClassName(element, 'ajax_saving');
    queryString = 'org_openpsa_calendar_filters_' + action + '=' + element.value;

    var ajaxRequest = new Ajax.Request(
        location.href,
        {
            method: 'post',
            parameters: queryString,
            onComplete: org_openpsa_calendar_filters_onComplete.bind(element),
            onFailure: org_openpsa_calendar_filters_onFailure.bind(element)
        }
    );
}

function org_openpsa_calendar_filters_onComplete(request)
{
    Element.removeClassName(element, 'ajax_saving');

    result = request.responseXML.documentElement.getElementsByTagName('result')[0].firstChild.data;
    status = request.responseXML.documentElement.getElementsByTagName('status')[0].firstChild.data;
    if (result)
    {
        Element.addClassName(element, 'ajax_saved');
        var message = new protoGrowl(
            {
                title: 'Calendar',
                message: 'Subscription status saved: ' + status
            }
        );
        setTimeout("Element.removeClassName(document.getElementById('"+element.id+"'), 'ajax_saved');", 1000); //1 sec timeout
        setTimeout("Element.addClassName(document.getElementById('"+element.id+"'), 'ajax_saved_fade');", 1000); //1 sec timeout
        setTimeout("Element.removeClassName(document.getElementById('"+element.id+"'), 'ajax_saved_fade');", 2000); //2 sec timeout
    }
    else
    {
        ooDisplayMessage('Subscription failed, reason ' + status);
        Element.addClassName(element, 'ajax_save_failed');
    }
}

function org_openpsa_calendar_filters_onFailure(request)
{
    Element.removeClassName(element, 'ajax_saving');
    Element.addClassName(element, 'ajax_save_failed');
    //ooAjaxDebugWalk(request);
}