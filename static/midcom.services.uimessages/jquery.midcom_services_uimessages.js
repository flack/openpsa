var MIDCOM_SERVICES_UIMESSAGES_TYPE_INFO = 'info';
var MIDCOM_SERVICES_UIMESSAGES_TYPE_OK = 'ok';
var MIDCOM_SERVICES_UIMESSAGES_TYPE_WARNING = 'warning';
var MIDCOM_SERVICES_UIMESSAGES_TYPE_ERROR = 'error';
var MIDCOM_SERVICES_UIMESSAGES_TYPE_DEBUG = 'ok';

var MIDCOM_SERVICES_UIMESSAGES_POSITION = 'top right';
var MIDCOM_SERVICES_UIMESSAGES_REMOVE = true;
var MIDCOM_SERVICES_UIMESSAGES_ERROR_HIGHLIGHT = true;
var MIDCOM_SERVICES_UIMESSAGES_SLIDE_SPEED = 1000;
var MIDCOM_SERVICES_UIMESSAGES_SLIDE_DELAY = 3000;

var MIDCOM_SERVICES_UIMESSAGES_INDEX = 0;

jQuery(document).ready(function()
{
    if (MIDCOM_SERVICES_UIMESSAGES_POSITION.match('bottom'))
    {
        jQuery('#midcom_services_uimessages_wrapper')
            .css({
                position: 'fixed',
                top: 'auto',
                bottom: 0
            });
    }
    
    if (MIDCOM_SERVICES_UIMESSAGES_POSITION.match('left'))
    {
        jQuery('#midcom_services_uimessages_wrapper')
            .css({
                right: 'auto',
                left: 0
            });
    }
});

jQuery.midcom_services_uimessage_add = function(options) {
    jQuery('#midcom_services_uimessages_wrapper').midcom_services_uimessage(options);
}

jQuery.fn.midcom_services_uimessage = function(options)
{
    var id = 'midcom_services_uimessages_' + MIDCOM_SERVICES_UIMESSAGES_INDEX;
    
    jQuery('<div></div>')
        .attr('id', id)
        .addClass('midcom_services_uimessages')
        .addClass(options.type)
        .appendTo('#midcom_services_uimessages_wrapper');
    
    jQuery('<h3></h3>')
        .html(options.title)
        .appendTo('#' + id);
        
    jQuery('<div></div>')
        .html(options.message)
        .addClass('message')
        .appendTo('#' + id);
    
    jQuery('<img />')
        .attr({
            src: MIDCOM_STATIC_URL + '/stock-icons/16x16/cancel.png',
            alt: 'X'
        })
        .addClass('close')
        .click(function()
        {
            jQuery(this).parent().slideUp(MIDCOM_SERVICES_UIMESSAGES_SLIDE_SPEED);
            jQuery(this).parent().unbind(jQuery(this).attr('id') + '_timer');
            
            // Return without removing the object
            if (!MIDCOM_SERVICES_UIMESSAGES_REMOVE)
            {
                return;
            }
            
            // Remove the element after some safety margin
            jQuery(this).parent().oneTime(MIDCOM_SERVICES_UIMESSAGES_SLIDE_DELAY + 500, jQuery(this).attr('id') + '_timer', function()
            {
                jQuery(this).remove();
            });
        })
        .prependTo('#' + id);
    
    switch (options.type)
    {
        case MIDCOM_SERVICES_UIMESSAGES_TYPE_INFO:
        case MIDCOM_SERVICES_UIMESSAGES_TYPE_OK:
            jQuery('#' + id).oneTime(MIDCOM_SERVICES_UIMESSAGES_SLIDE_DELAY, id + '_timer', function()
            {
                jQuery(this).slideUp(MIDCOM_SERVICES_UIMESSAGES_SLIDE_SPEED);
                
                // Return without removing the object
                if (!MIDCOM_SERVICES_UIMESSAGES_REMOVE)
                {
                    return;
                }
                
                // Remove the element after some safety margin
                jQuery(this).oneTime(MIDCOM_SERVICES_UIMESSAGES_SLIDE_DELAY + 500, jQuery(this).attr('id') + '_timer', function()
                {
                    jQuery(this).remove();
                });
            });
            
            break;
        
        case MIDCOM_SERVICES_UIMESSAGES_TYPE_ERROR:
            if (MIDCOM_SERVICES_UIMESSAGES_ERROR_HIGHLIGHT)
            {
                jQuery('#' + id).everyTime(7000, id + '_shake', function()
                {
                    jQuery(this).effect('pulsate', { times: 1}, 500);
                });
            }
            
        
        case MIDCOM_SERVICES_UIMESSAGES_TYPE_WARNING:
        default:
            // Do nothing?
            break;
        
    }
        
    MIDCOM_SERVICES_UIMESSAGES_INDEX++;
}

