//Global places for Ajax request handlers
var xmlHttpReq_store = new Array();
var xmlHttpReq_timeout_store = new Array();
var ooAjaxChange_timeout_store = new Array();

// Work around IE stupidity, loosely based on examples from http://developer.apple.com/internet/webcontent/xmlhttpreq.html
function compat_getElementsByTagNameNS(ns, prefix, local, parentElem)
{
    var result = "";
    if (prefix && window.ActiveXObject)
    {
        if (parentElem)
        {
            // IE/Windows way of handling namespaces
            return parentElem.getElementsByTagName(prefix + ":" + local);
        }
    }
    else
    {
        // the namespace versions of this method
        // (getElementsByTagNameNS()) operate
        // differently in Safari and Mozilla, but both
        // return value with just local name, provided
        // there aren't conflicts with non-namespace element
        // names
        //return parentElem.getElementsByTagName(local);
        return parentElem.getElementsByTagNameNS(ns, local);
    }
}


//Removes all children from a node.
function ooRemoveChildNodes(node) {
    if (   node
        && node.hasChildNodes
        && node.removeChild)
    {
        while (node.hasChildNodes())
        {
            node.removeChild(node.firstChild);
        }
    }
}

//Sets element class, prefixing/appending classes as requested.
function ooAjaxSetClass(element, classstr, append)
{
    //Prefixing should always be safe, so we do not check other than existance
    if (document.getElementById(element.id+'_ajaxPrefixClass'))
    {
        element.className = document.getElementById(element.id+'_ajaxPrefixClass').value + ' ' + classstr;
    }
    else
    {
        element.className = classstr;
    }
    if (append == true && document.getElementById(element.id+'_ajaxAppendClass'))
    {
        element.className = element.className + ' ' + document.getElementById(element.id+'_ajaxAppendClass').value;
    }
}

//onFocus for editable Ajax element
function ooAjaxFocus(element)
{
    //Make the style "focused"
    ooAjaxSetClass(element, 'ajax_editable ajax_focused', false);

    // Copy element ID and old value
    active_element = element.id;
    old_value = element.value;

    // Empty the default value
    if (document.getElementById(element.id+'_ajaxDefault') && (old_value == document.getElementById(element.id+'_ajaxDefault').value))
    {
        element.value = '';
    }
}

function ooAjaxDebugWalk(obj)
{
    ooDisplayMessage('---ooAjaxDebugWalk---');
    for (var propName in obj)
    {
        ooDisplayMessage(propName+': '+obj[propName], 'normal');
    }
    ooDisplayMessage('---/ooAjaxDebugWalk---');
}

//Parse a mode string into array
function ooAjaxParseMode(modeStr)
{
    ret = new Array();

    if (!modeStr)
    {
        return ret;
    }

    //explode comma separated values.
    regex = /([^, ]+)/g;
    parsed = modeStr.match(regex);
    for (key in parsed)
    {
        if (typeof(parsed[key]) == "string")
        {
            ret[parsed[key].toLowerCase()] = true;
        }
    }

    //ooAjaxDebugWalk(ret);
    return ret;
}

function ooAjaxChange(element)
{
    //Clear previous timeout
    if (ooAjaxChange_timeout_store[element.id])
    {
        window.clearTimeout(ooAjaxChange_timeout_store[element.id]);
    }

    //Make sure we have our handler set
    if (document.getElementById(element.id+'_ajaxFunction'))
    {
        handlerFunc = document.getElementById(element.id+'_ajaxFunction');
    }
    else
    {
        ooDisplayMessage('ooAjaxChange: Handler function for element id "' + element.id + '" cannot be found', 'error');
        return false;
    }

    //Set new timeout
    ooAjaxChange_timeout_store[element.id] = window.setTimeout(handlerFunc.value+'(document.getElementById("' + element.id + '"));', 500);
    return true;
}

function ooAjaxBlur_noSave(element)
{
    // Make the style "editable"
    ooAjaxSetClass(element, 'ajax_editable', true);
}

//Get the URL for Ajax handler
function ooAjaxUrl(element)
{
    //Configurable handler
    var ajaxUrl = false;
    if (document.getElementById(element.id+'_ajaxUrl'))
    {
        ajaxUrl = document.getElementById(element.id+'_ajaxUrl').value;
    }
    //TODO: Check global element value (in case of shared handler)
    else
    {
        return false;
    }

    return ajaxUrl;
}

//onBlur for editable Ajax element
function ooAjaxBlur(element)
{
    ajaxUrl = ooAjaxUrl(element);
    if (!ajaxUrl)
    {
        //Raise error somewhere so that developer can see it.
        ooDisplayMessage('ooAjaxBlur: Handler URL for element id "'+element.id+'" cannot be found', 'error');
        //We return true, otherwise the we could never get out of focus!
        return true;
    }

    // Make the style "editable"
    ooAjaxBlur_noSave(element);

    // Check if user changed the field
    if (element.value != old_value)
    {
        //POST to given URL
        ooAjaxPost(ajaxUrl, element.name + '=' + element.value, element);
    }

    //If empty return to the default value (if one is provided as _ajaxDefault element...)
    if (element.value == '' && document.getElementById(element.id+'_ajaxDefault'))
    {
        element.value = document.getElementById(element.id+'_ajaxDefault').value;
    }
}

//onChange for editable Ajax <select /> element
function ooAjaxSelect(element)
{
    ajaxUrl = ooAjaxUrl(element);
    if (!ajaxUrl)
    {
        //Raise error somewhere so that developer can see it.
        ooDisplayMessage('ooAjaxOnChange: Handler URL for element id "'+element.id+'" cannot be found', 'error');
        //We return true, otherwise the we could never get out of focus!
        return true;
    }

    //POST to given URL
    ooAjaxPost(ajaxUrl, element.name + '=' + element.options[element.selectedIndex].value, element, true);
}

//Cross-browser way to get the correct object
function ooAjaxRequestor()
{
    var xmlHttpReq = false;

    // IE
    if (window.ActiveXObject)
    {
        xmlHttpReq = new ActiveXObject('Microsoft.XMLHTTP');
    }
    // Mozilla/Safari
    else if (window.XMLHttpRequest)
    {
        xmlHttpReq = new XMLHttpRequest();
        xmlHttpReq.overrideMimeType('text/xml');
    }
    return xmlHttpReq;
}

//GET handler for ajax
function ooAjaxGet(strURL, strQuery, element, callback, timeout, type)
{
    if (   !timeout
        && timeout !== false)
    {
        //Default to 10s timeout
        timeout = 10000;
    }


    if (!type)
    {
        // Default to XML
        type = 'xml';
    }

    xmlHttpReq = ooAjaxRequestor();
    if (xmlHttpReq)
    {
        if (timeout)
        {
            xmlHttpReq_timeout_store[element.id] = window.setTimeout("ooDisplayMessage('ooAjaxGet: Request timed out', 'error'); ooAjaxSetClass(document.getElementById('"+element.id+"'), 'ajax_editable ajax_save_failed');", timeout);
        }
        xmlHttpReq_store[element.id] = xmlHttpReq;
        try
        {
            if (strQuery)
            {
                xmlHttpReq_store[element.id].open('GET', strURL + '?' + strQuery, true);
            }
            else
            {
                xmlHttpReq_store[element.id].open('GET', strURL, true);
            }
        }
        catch (e)
        {
            window.clearTimeout(xmlHttpReq_timeout_store[element.id]);
            ooDisplayMessage('ooAjaxGet: Request error: ' + e, 'error');
            ooAjaxSetClass(element, 'ajax_editable ajax_save_failed', false);
        }
        xmlHttpReq_store[element.id].onreadystatechange = function()
        {
            // Status 4 = response received
            if (xmlHttpReq_store[element.id].readyState == 4)
            {
                if (xmlHttpReq_store[element.id].status == 200)
                {
                    window.clearTimeout(xmlHttpReq_timeout_store[element.id]);

                    // Uncomment to display raw XML return output:
                    // ooDisplayMessage(xmlHttpReq.responseText);

                    // Read XML response
                    if (type == 'xml')
                    {
                        response = xmlHttpReq_store[element.id].responseXML.documentElement;
                        eval(callback + "(xmlHttpReq_store['" + element.id + "'].responseXML.documentElement, document.getElementById('"+element.id+"'))");
                    }
                    else
                    {
                        response = xmlHttpReq_store[element.id].responseText;
                        eval(callback + "(xmlHttpReq_store['" + element.id + "'].responseText, document.getElementById('"+element.id+"'))");
                    }
                    //put response to callback
                    //alert(callback+ "(xmlHttpReq_store['" + element.id + "'].responseXML.documentElement, document.getElementById('"+element.id+"'))");
                    //window.setTimeout(callback + "(xmlHttpReq_store['" + element.id + "'].responseXML.documentElement, document.getElementById('"+element.id+"'))", 1);
                }
                //Request failed
                else
                {
                    window.clearTimeout(xmlHttpReq_timeout_store[element.id]);
                    ooDisplayMessage('ooAjaxGet: Request error: ' + xmlHttpReq.status + ', ' + xmlHttpReq.statusText, 'error');
                    ooAjaxSetClass(element, 'ajax_editable ajax_save_failed', false);
                }
            }
            else if (xmlHttpReq_store[element.id].readyState < 4)
            {
                // Loading...
                ooAjaxSetClass(element, 'ajax_editable ajax_focused ajax_busy', false);
            }
        };
        try
        {
            xmlHttpReq_store[element.id].send(null);
        }
        catch (e)
        {
            ooDisplayMessage('ooAjaxGet: Failed to send request: ' + e, 'error');
        }
    }
}

//POST handler for Ajax editable element
function ooAjaxPost(strURL, strSubmit, element, refreshWindow, callback)
{
    xmlHttpReq = ooAjaxRequestor();
    if (xmlHttpReq)
    {
        xmlHttpReq_timeout_store[element.id] = window.setTimeout("ooDisplayMessage('ooAjaxPost: Request timed out', 'error'); ooAjaxSetClass(document.getElementById('"+element.id+"'), 'ajax_editable ajax_save_failed');", 10000); //10 second timeout
        xmlHttpReq_store[element.id] = xmlHttpReq;
        xmlHttpReq_store[element.id].open('POST', strURL, true);
        xmlHttpReq_store[element.id].setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xmlHttpReq_store[element.id].onreadystatechange = function()
        {
            // Status 4 = response received
            if (xmlHttpReq_store[element.id].readyState == 4)
            {
                if (xmlHttpReq_store[element.id].status == 200)
                {
                    window.clearTimeout(xmlHttpReq_timeout_store[element.id]);
                    // Read XML response
                    response = xmlHttpReq_store[element.id].responseXML.documentElement;

                    // Uncomment to display raw XML return output:
                    //ooDisplayMessage(xmlHttpReq_store[element.id].responseText);

                    //Check result tag
                    if (!response.getElementsByTagName('result')[0])
                    {
                        ooAjaxSetClass(element, 'ajax_editable ajax_save_failed', false);
                        ooDisplayMessage('ooAjaxPost: result tag not found or invalid', 'error');
                        ooAjaxDebugWalk(xmlHttpReq_store[element.id].responseXML.documentElement);
                        return false;
                    }
                    else
                    {
                        result = response.getElementsByTagName('result')[0].firstChild.data;
                    }

                    //Check status tag
                    if (!response.getElementsByTagName('status')[0])
                    {
                        ooAjaxSetClass(element, 'ajax_editable ajax_save_failed', false);
                        ooDisplayMessage('ooAjaxPost: status tag not found or invalid', 'error');
                        return false;
                    }
                    else
                    {
                        status = response.getElementsByTagName('status')[0].firstChild.data;
                    }

                    //If element valueoverride is present, override local value.
                    if (response.getElementsByTagName('valueoverride')[0])
                    {
                        element.value = response.getElementsByTagName('valueoverride')[0].firstChild.data;
                    }
                    //Handler returned success
                    if (result == 1)
                    {
                        ooAjaxSetClass(element, 'ajax_editable ajax_saved', false);
                        //We must access the element globally since setTimeout registers an event to global context (also we might have other saves as well in short succession)
                        setTimeout("ooAjaxSetClass(document.getElementById('"+element.id+"'), 'ajax_editable ajax_saved_fade', true);", 1000); //1 sec timeout
                        setTimeout("ooAjaxSetClass(document.getElementById('"+element.id+"'), 'ajax_editable', true);", 2000); //2 sec timeout

                        if (refreshWindow == true)
                        {
                            window.location.reload();
                        }
                        // ooDisplayMessage('Save ok', 'ok');

                        if (callback)
                        {
                            //eval(callback+'();');
                            eval(callback + "(xmlHttpReq_store['" + element.id + "'].responseXML.documentElement, document.getElementById('"+element.id+"'))");
                        }

                        return true;
                    }
                    //Handler returned failure
                    else
                    {
                        ooAjaxSetClass(element, 'ajax_editable ajax_save_failed', false);
                        if (document.getElementById(element.id+'_ajaxMessageName'))
                        {
                            ooDisplayMessage('Saving '+document.getElementById(element.id+'_ajaxMessageName').value+' failed, reason: ' + status, 'error');
                        }
                        else
                        {
                            ooDisplayMessage('Saving failed, reason: ' + status, 'error');
                        }
                        return false;
                    }
                }
                //Request failed
                else
                {
                    window.clearTimeout(xmlHttpReq_timeout_store[element.id]);
                    ooDisplayMessage('ooAjaxPost: Request error: ' + xmlHttpReq.status + ', ' + xmlHttpReq.statusText, 'error');
                    ooAjaxSetClass(element, 'ajax_editable ajax_save_failed', false);
                    return false;
                }
            }
            else if (xmlHttpReq_store[element.id].readyState < 4)
            {
                // Loading...
                ooAjaxSetClass(element, 'ajax_editable ajax_saving', false);
            }
        };
        xmlHttpReq_store[element.id].send(strSubmit);
    }
}