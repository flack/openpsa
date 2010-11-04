//Shortcut to creating child elements with text.
function ooCreateElement(name, attrs, style, text) {
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
}

//Add a message to the messagearea
function ooDisplayMessage(messageText, messageClass)
{
    areaDiv=document.getElementById('org_openpsa_messagearea');
    if (!areaDiv)
    {
        //Could not find the messagearea
        return false;
    }

    //Make sure we have some class for messages
    if(!messageClass)
    {
        messageClass='normal';
    }

    //Make display area invisible (force reflow), should not be necessary
    //areaDiv.style.display = 'none';

    //Create and append a child node to messagearea.
    areaDiv.appendChild(ooCreateElement('div',
                                        {'class': messageClass},
                                        {'display': 'block'},
                                        messageText));

    //Make sure display area  is visible (again)...
    areaDiv.style.display = 'block';
}