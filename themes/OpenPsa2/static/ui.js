var org_openpsa_jsqueue = {
    actions: [],
    add: function (action)
    {
        this.actions.push(action);
    },
    execute: function()
    {
        for (var i = 0; i < this.actions.length; i++)
        {
            this.actions[i]();
        }
        this.actions = [];
    }
};

var org_openpsa_layout =
{
    clip_toolbar: function()
    {
        var dropdown = '';
        var positionLast = jQuery('#org_openpsa_toolbar .view_toolbar li:last-child').position();
        var toolbarWidth = jQuery('#org_openpsa_toolbar').width();

        if (positionLast && positionLast.left > toolbarWidth)
        {
            var container = jQuery('<div></div>')
                .attr('id', 'toolbar_dropdown')
                .mouseover(function()
                {
                    var self = jQuery(this);
                    if (self.data('timeout'))
                    {
                        clearTimeout(self.data('timeout'));
                        self.removeData('timeout');
                        return;
                    }
                    self.addClass('expanded');
                })
                .mouseout(function()
                {
                    var self = jQuery(this);
                    self.data('timeout', setTimeout(function(){self.removeClass('expanded');self.removeData('timeout')}, 500));
                })
                .appendTo('#org_openpsa_toolbar');
            dropdown = jQuery('<ul></ul>').addClass('midcom_toolbar').appendTo(container);
        }

        var over = false;

        jQuery('#org_openpsa_toolbar .view_toolbar li').each(function(index)
        {
            if (!over && jQuery(this).position().left + jQuery(this).width() > toolbarWidth)
            {
                over = true;
            }
            if (over)
            {
                jQuery(this).detach().appendTo(dropdown);
            }
        });
    },

    resize_content: function()
    {
        var handler = function()
        {
            var content_height = jQuery(window).height() - (jQuery('#content-menu').outerHeight() + jQuery('#org_openpsa_toolbar').outerHeight() + (jQuery('#content-text').outerHeight() - jQuery('#content-text').height()));

            jQuery('#content-text').css('height', content_height + 'px');
        };
        handler();

        jQuery(window).resize(function(){
                handler();
        });
    },

    add_splitter: function()
    {
        jQuery('<div></div>')
            .attr('id', 'template_openpsa2_resizer')
            .css('left', jQuery('#content').css('margin-left'))
            .mouseover(function()
            {
                jQuery(this).addClass('hover');
            })
            .mouseout(function()
            {
                jQuery(this).removeClass('hover');
            })
            .appendTo('#container');

        jQuery('#template_openpsa2_resizer').draggable({
            axis: 'axis-x',
            containment: 'window',
            stop: function()
            {
                var offset = jQuery(this).offset().left;
        
                if (offset < 0)
                {
                    offset = 0;
                }
            
                var navigation_width = offset;
                var content_margin_left = offset;
            
                jQuery('#leftframe').css('width', navigation_width + 'px');
                jQuery('#content').css('margin-left', content_margin_left + 'px');
            
                jQuery.post(MIDGARD_ROOT + '__mfa/asgard/preferences/ajax/', {openpsa2_offset: offset});
                jQuery(window).trigger('resize');
            }
            });
    },

    initialize_search: function(providers, current)
    {
        if (   typeof providers != 'object'
            || providers.length == 0)
        {
            return;
        }

        var field = jQuery('#org_openpsa_search_query');

        var current_provider,
        selector = jQuery('<ul id="org_openpsa_search_providers"></ul>');

        if (    typeof current != 'string'
             || current == '')
        {
            current = providers[0].identifier;
        }

        var li_class = '';
        for (var i = 0; i < providers.length; i++)
        {
            li_class = 'provider';
            if (current == providers[i].identifier)
            {
                current_provider = providers[i];
                li_class += ' current';
            }

            jQuery('<li class="' + li_class + '">' + providers[i].helptext + '</li>')
                .data('provider', providers[i])
                .click(function(event)
                {
                    var target = jQuery(event.target),
                    query = jQuery('#org_openpsa_search_query');

                    jQuery('#org_openpsa_search_providers .current').removeClass('current');
                    target.addClass('current');
                    jQuery('#org_openpsa_search_form').attr('action', target.data('provider').url);
                    jQuery('#org_openpsa_search_trigger').click();

                    if (query.data('helptext') == query.val())
                    {
                        query.val(target.data('provider').helptext)
                    }
                    query.data('helptext', target.data('provider').helptext)
                        .focus();

                    jQuery.post(MIDGARD_ROOT + '__mfa/asgard/preferences/ajax/', {openpsa2_search_provider: target.data('provider').identifier});
                })
                .mouseover(function()
                {
                    jQuery(this).addClass('hover');
                })
                .mouseout(function()
                {
                    jQuery(this).removeClass('hover');
                })
                .appendTo(selector);
        }

        jQuery('#org_openpsa_search_form').attr('action', current_provider.url);
        
        var search = location.search.replace(/^.*?[\?|&]query=([^&]*).*/, '$1');
        if (search != '' && search != location.search)
        {
            field.val(decodeURI(search));
        }
        else
        {
            field.val(current_provider.helptext);
        }

        field.data('helptext', current_provider.helptext);

        if (providers.length > 1)
        {
            selector.insertBefore(field);
            jQuery('<div id="org_openpsa_search_trigger"></div>')
                .click(function()
                {
                    jQuery('#org_openpsa_search_providers').toggle();
                    jQuery(this).toggleClass('focused');
                })
                .insertBefore(field);
        }

        field.show()
        .bind('focus', function()
        {
            field.addClass('focused');
            if (field.data('helptext') == field.val())
            {
                field.val('');
            }
        })
        .bind('blur', function()
        {
            field.removeClass('focused');
            if (!field.val() && field.data('helptext'))
            {
                field.val(field.data('helptext'));
            }
        });
    },
    bind_admin_toolbar_loader: function()
    {
    	jQuery('#org_openpsa_toolbar_trigger').bind('click', function(e)
		{
    	    if (jQuery('#org_openpsa_toolbar_trigger').hasClass('active'))
    	    {
    	        jQuery('body div.midcom_services_toolbars_fancy').hide();
    	        jQuery('#org_openpsa_toolbar_trigger').removeClass('active');
    	        jQuery('#org_openpsa_toolbar_trigger').addClass('inactive');
    	    }
    	    else if (jQuery('#org_openpsa_toolbar_trigger').hasClass('inactive'))
    	    {
    	        jQuery('body div.midcom_services_toolbars_fancy').show();
    	        jQuery('#org_openpsa_toolbar_trigger').removeClass('inactive');
    	        jQuery('#org_openpsa_toolbar_trigger').addClass('active');
    	    }
    	    else
    	    {
    	        var head = document.getElementsByTagName('head')[0];
    	        jQuery(document.createElement('link')).attr({
    	            type: 'text/css',
    	            href: MIDCOM_STATIC_URL + '/midcom.services.toolbars/fancy.css',
    	            rel: 'stylesheet',
    	            media: 'screen, projection'
    	            }).appendTo(head);
    	        jQuery.getScript(MIDCOM_STATIC_URL + '/midcom.services.toolbars/jquery.midcom_services_toolbars.js', function(){
    	            jQuery.getScript(MIDCOM_STATIC_URL + '/jQuery/jquery.easydrag-1.4.js', function(){
    	                jQuery('body div.midcom_services_toolbars_fancy').midcom_services_toolbar({});
    	            })
    	        });
    	        jQuery('#org_openpsa_toolbar_trigger').addClass('active');
    	    }
    	});	
    }
};

jQuery(document).ready(function()
{
    org_openpsa_layout.add_splitter();
    org_openpsa_layout.clip_toolbar();
    org_openpsa_layout.bind_admin_toolbar_loader();
});

//This has to be timed with the jqgrid resizers
org_openpsa_jsqueue.add(org_openpsa_layout.resize_content);
