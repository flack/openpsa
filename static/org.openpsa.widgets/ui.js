var org_openpsa_jsqueue =
{
    actions: [],
    add: function (action)
    {
        this.actions.push(action);
    },
    execute: function()
    {
        var i;
        for (i = 0; i < this.actions.length; i++)
        {
            this.actions[i]();
        }
        this.actions = [];
    }
};

var org_openpsa_resizers =
{
    handlers: {},
    queue: [],
    append_handler: function(identifier, callback)
    {
        if (typeof org_openpsa_resizers.handlers[identifier] !== 'undefined')
        {
            return;
        }
        org_openpsa_resizers.handlers[identifier] = true;
        org_openpsa_resizers.queue.push(callback);
    },
    prepend_handler: function(identifier, callback)
    {
        if (typeof org_openpsa_resizers.handlers[identifier] !== 'undefined')
        {
            return;
        }
        org_openpsa_resizers.handlers[identifier] = true;
        org_openpsa_resizers.queue.unshift(callback);
    },
    bind_events: function()
    {
        $(window).resize(function()
        {
            org_openpsa_resizers.process_queue(true);
        });
        org_openpsa_jsqueue.add(org_openpsa_resizers.process_queue);
    },
    process_queue: function(resizing)
    {
        if (typeof resizing === 'undefined')
        {
            resizing = false;
        }
        $.each(org_openpsa_resizers.queue, function(index, callback)
        {
            callback(resizing);
        });
    }
};
org_openpsa_resizers.bind_events();

var org_openpsa_layout =
{
    clip_toolbar: function()
    {
        var container = $('#toolbar_dropdown').length > 0 ? $('#toolbar_dropdown') : $('<li class="enabled submenu"><a><img src="' + MIDCOM_STATIC_URL + '/stock-icons/16x16/preferences-desktop.png"/> <span class="toolbar_label">' + TOOLBAR_MORE_LABEL + '</span></a><ul class="midcom_toolbar"></ul></li>')
            .attr('id', 'toolbar_dropdown')
            .data('event_attached', false)
            .mouseover(function()
            {
                var self = $(this);
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
                var self = $(this);
                self.data('timeout', setTimeout(function()
                {
                    self.removeClass('expanded');
                    self.removeData('timeout');
                }, 500));
            })
            .css('display', 'none')
            .appendTo('#org_openpsa_toolbar > ul.view_toolbar'),
        dropdown = container.find('ul.midcom_toolbar'),
        toolbarWidth = $('#org_openpsa_toolbar').width(),
        lastchild = $('#org_openpsa_toolbar .view_toolbar li:last-child'),
        positionLast = lastchild.length > 0 ? (lastchild.position().left + lastchild.width()) : 0,
        over = false;

        $('#org_openpsa_toolbar > .view_toolbar > li:not(#toolbar_dropdown)').each(function()
        {
            if (!over && ($(this).position().left + $(this).width() + container.width()) > toolbarWidth)
            {
                over = true;
            }
            if (over)
            {
                dropdown.prepend($(this).detach());
            }
        });
        if (!over)
        {
            dropdown.children('li:not(#toolbar_dropdown)').each(function()
            {
                var item = $(this)
                    .clone()
                    .css('visibility', 'hidden')
                    .insertBefore(container);
                positionLast = $('#org_openpsa_toolbar .view_toolbar li:last-child').position().left + $('#org_openpsa_toolbar .view_toolbar li:last-child').width();

                if (positionLast < toolbarWidth)
                {
                    $(this).remove();
                    item.css('visibility', 'visible');
                }
                else
                {
                    item.remove();
                    return false;
                }
            });
        }

        if (dropdown.children('li').length > 0)
        {
            container.css('display', 'inline-block');
        }
        else
        {
            container.css('display', 'none');
        }
        if (!container.data('event_attached'))
        {
            $(window).resize(function(){org_openpsa_layout.clip_toolbar();});
            container.data('event_attached', true);
        }
    },

    resize_content: function(containment, margin_bottom)
    {
        if (typeof margin_bottom === 'undefined')
        {
            margin_bottom = 0;
        }
        org_openpsa_resizers.prepend_handler('content', function()
        {
            var content_height = $(window).height() - ($(containment).offset().top + ($(containment).outerHeight() - $(containment).height() + margin_bottom));
            $(containment).css('height', content_height + 'px');
        });
    },

    add_splitter: function()
    {
        $('<div></div>')
            .attr('id', 'template_openpsa2_resizer')
            .css('left', $('#leftframe').width())
            .mouseover(function()
            {
                $(this).addClass('hover');
            })
            .mouseout(function()
            {
                $(this).removeClass('hover');
            })
            .appendTo('#container');

        $('#template_openpsa2_resizer').draggable({
            axis: 'axis-x',
            containment: 'window',
            stop: function()
            {
                var offset = Math.max($(this).offset().left, 0),
                navigation_width = offset,
                content_margin_left = offset + 2;

                $('#leftframe').css('width', navigation_width + 'px');
                $('#content').css('margin-left', content_margin_left + 'px');

                $.post(MIDGARD_ROOT + '__mfa/asgard/preferences/ajax/', {openpsa2_offset: offset});
                org_openpsa_resizers.process_queue();
            }
        });
    },

    initialize_search: function(providers, current)
    {
        if (   typeof providers !== 'object'
            || providers.length === 0)
        {
            return;
        }

        var field = $('#org_openpsa_search_query'),
        current_provider,
        selector = $('<ul id="org_openpsa_search_providers"></ul>'),
        li_class = '',
        i;

        if (    typeof current !== 'string'
             || current === '')
        {
            current = providers[0].identifier;
        }

        for (i = 0; i < providers.length; i++)
        {
            li_class = 'provider';
            if (current === providers[i].identifier)
            {
                current_provider = providers[i];
                li_class += ' current';
            }

            $('<li class="' + li_class + '">' + providers[i].helptext + '</li>')
                .data('provider', providers[i])
                .click(function(event)
                {
                    var target = $(event.target),
                    query = $('#org_openpsa_search_query');

                    $('#org_openpsa_search_providers .current').removeClass('current');
                    target.addClass('current');
                    $('#org_openpsa_search_form').attr('action', target.data('provider').url);
                    $('#org_openpsa_search_trigger').click();

                    if (query.data('helptext') === query.val())
                    {
                        query.val(target.data('provider').helptext);
                    }
                    query.data('helptext', target.data('provider').helptext)
                        .focus();

                    $.post(MIDGARD_ROOT + '__mfa/asgard/preferences/ajax/', {openpsa2_search_provider: target.data('provider').identifier});
                })
                .mouseover(function()
                {
                    $(this).addClass('hover');
                })
                .mouseout(function()
                {
                    $(this).removeClass('hover');
                })
                .appendTo(selector);
        }

        $('#org_openpsa_search_form').attr('action', current_provider.url);

        var search = location.search.replace(/^.*?[\?|&]query=([^&]*).*/, '$1');
        if (   search !== ''
            && search !== location.search)
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
            $('<div id="org_openpsa_search_trigger"></div>')
                .click(function()
                {
                    $('#org_openpsa_search_providers').toggle();
                    $(this).toggleClass('focused');
                })
                .insertBefore(field);
        }

        field.show()
        .bind('focus', function()
        {
            field.addClass('focused');
            if (field.data('helptext') === field.val())
            {
                field.val('');
            }
        })
        .bind('blur', function()
        {
            field.removeClass('focused');
            if (   !field.val()
                && field.data('helptext'))
            {
                field.val(field.data('helptext'));
            }
        });
    },
    bind_admin_toolbar_loader: function()
    {
        $('#org_openpsa_toolbar_trigger').bind('click', function(e)
        {
            if ($('#org_openpsa_toolbar_trigger').hasClass('active'))
            {
                $('body div.midcom_services_toolbars_fancy').hide();
                $('#org_openpsa_toolbar_trigger').removeClass('active');
                $('#org_openpsa_toolbar_trigger').addClass('inactive');
            }
            else if ($('#org_openpsa_toolbar_trigger').hasClass('inactive'))
            {
                $('body div.midcom_services_toolbars_fancy').show();
                $('#org_openpsa_toolbar_trigger').removeClass('inactive');
                $('#org_openpsa_toolbar_trigger').addClass('active');
            }
            else
            {
                if (typeof document.createStyleSheet === 'object')
                {
                    //Compatibility for IE
                    document.createStyleSheet(MIDCOM_STATIC_URL + '/midcom.services.toolbars/fancy.css');
                }
                else
                {
                    var head = document.getElementsByTagName('head')[0];
                    $(document.createElement('link')).attr({
                        type: 'text/css',
                        href: MIDCOM_STATIC_URL + '/midcom.services.toolbars/fancy.css',
                        rel: 'stylesheet',
                        media: 'screen, projection'
                    }).appendTo(head);
                }
                $.getScript(MIDCOM_STATIC_URL + '/midcom.services.toolbars/jquery.midcom_services_toolbars.js', function(){
                    $('body div.midcom_services_toolbars_fancy').midcom_services_toolbar({});
                });
                $('#org_openpsa_toolbar_trigger').addClass('active');
            }
        });
    }
};