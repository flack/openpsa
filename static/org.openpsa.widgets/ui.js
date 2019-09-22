const org_openpsa_jsqueue = {
    actions: [],
    add: function (action) {
        this.actions.push(action);
    },
    execute: function() {
        this.actions.forEach(function(action) {
            action();
        });
        this.actions = [];
    }
};

const org_openpsa_resizers = {
    handlers: {},
    queue: [],
    append_handler: function(identifier, callback) {
        if (!org_openpsa_resizers.handlers.hasOwnProperty(identifier)) {
            org_openpsa_resizers.handlers[identifier] = true;
            org_openpsa_resizers.queue.push(callback);
        }
    },
    prepend_handler: function(identifier, callback) {
        if (!org_openpsa_resizers.handlers.hasOwnProperty(identifier)) {
            org_openpsa_resizers.handlers[identifier] = true;
            org_openpsa_resizers.queue.unshift(callback);
        }
    },
    bind_events: function() {
        $(window).resize(function() {
            org_openpsa_resizers.process_queue(true);
        });
        org_openpsa_jsqueue.add(org_openpsa_resizers.process_queue);
    },
    process_queue: function(resizing) {
        if (typeof resizing === 'undefined') {
            resizing = false;
        }
        org_openpsa_resizers.queue.forEach(function(callback) {
            callback(resizing);
        });
    }
};
org_openpsa_resizers.bind_events();

const org_openpsa_layout = {
    clip_toolbar: function() {
        if ($('#org_openpsa_toolbar > ul.view_toolbar').length === 0) {
            // there seem to be no toolbar buttons, so we don't need to do anything
            return;
        }
        var container = $('#toolbar_dropdown').length > 0 ? $('#toolbar_dropdown') : $('<li class="enabled submenu"><a><i class="fa fa-caret-down"></i> <span class="toolbar_label">' + TOOLBAR_MORE_LABEL + '</span></a><ul class="midcom_toolbar"></ul></li>')
            .attr('id', 'toolbar_dropdown')
            .data('event_attached', false)
            .mouseover(function() {
                var self = $(this);
                if (self.data('timeout')) {
                    clearTimeout(self.data('timeout'));
                    self.removeData('timeout');
                    return;
                }
                self.addClass('expanded');
            })
            .mouseout(function() {
                var self = $(this);
                self.data('timeout', setTimeout(function() {
                    self.removeClass('expanded');
                    self.removeData('timeout');
                }, 500));
            })
            .css('display', 'none')
            .appendTo('#org_openpsa_toolbar > ul.view_toolbar'),

            dropdown = container.find('ul.midcom_toolbar'),
            toolbarWidth = $('#org_openpsa_toolbar').width() - ($('#org_openpsa_toolbar .navigation_toolbar').width() || 0),
            over = false;

        $('#org_openpsa_toolbar > .view_toolbar > li:not(#toolbar_dropdown)').each(function() {
            if (!over && ($(this).position().left + $(this).width() + container.width()) > toolbarWidth) {
                over = true;
            }
            if (over) {
                if (!container.data('event_attached')) {
                    dropdown.append($(this).detach());
                } else {
                    dropdown.prepend($(this).detach());
                }
            }
        });
        if (!over) {
            dropdown.children('li:not(#toolbar_dropdown)').each(function() {
                var item = $(this)
                    .clone()
                    .css('visibility', 'hidden')
                    .insertBefore(container),

                    positionLast = $('#org_openpsa_toolbar .view_toolbar li:last-child').position().left + $('#org_openpsa_toolbar .view_toolbar li:last-child').width();

                if (positionLast < toolbarWidth) {
                    $(this).remove();
                    item.css('visibility', 'visible');
                } else {
                    item.remove();
                    return false;
                }
            });
        }

        if (dropdown.children('li').length > 0) {
            container.css('display', 'inline-block');
        } else {
            container.css('display', 'none');
        }
        if (!container.data('event_attached')) {
            $(window).resize(function(){org_openpsa_layout.clip_toolbar();});
            container.data('event_attached', true);
            container.parent().addClass('clip-initialized');
        }
    },

    resize_content: function(containment, margin_bottom) {
        if (typeof margin_bottom === 'undefined') {
            margin_bottom = 0;
        }
        org_openpsa_resizers.prepend_handler('content', function() {
            var content_height = $(window).height() - ($(containment).offset().top + ($(containment).outerHeight() - $(containment).height() + margin_bottom));
            $(containment).css('height', content_height + 'px');
        });
    },

    add_splitter: function() {
        $('<div></div>')
            .attr('id', 'template_openpsa2_resizer')
            .css('left', $('#leftframe').width())
            .appendTo('#container');

        $('#template_openpsa2_resizer').draggable({
            axis: 'x',
            containment: 'window',
            stop: function(event, ui) {
                var offset = Math.max((ui.offset.left), 0),
                navigation_width = offset,
                content_margin_left = offset + 2;

                $('#leftframe').css('width', navigation_width + 'px');
                $('#content').css('margin-left', content_margin_left + 'px');
                //workaround for problem in jquery.ui 1.11.2
                $('#template_openpsa2_resizer').css('width', '');

                $.post(MIDCOM_PAGE_PREFIX + '__mfa/asgard/preferences/ajax/', {openpsa2_offset: offset});
                $(window).trigger('resize');
            }
        });
    },

    initialize_search: function(providers, current) {
        if (typeof providers !== 'object' || providers.length === 0) {
            return;
        }

        var field = $('#org_openpsa_search_query'),
            selector = $('<ul id="org_openpsa_search_providers"></ul>'),
            li_class = '';

        if (typeof current !== 'string' || current === '') {
            current = providers[0].identifier;
        }

        function enable_provider(provider) {
            field
                .attr('placeholder', provider.placeholder || '')
                .focus();
            $('#org_openpsa_search_form').attr('action', provider.url);

            if (provider.autocomplete) {
                field.category_complete({
                    source: function (request, response) {
                        $.ajax({
                            url: provider.url + '/autocomplete/',
                            dataType: 'json',
                            data: {query: request.term},
                            success: response
                        });
                    },
                    select: function (event, ui) {
                        if (ui.item) {
                            location.href = ui.item.url;
                        }
                    },
                    minLength: 2,
                    autoFocus: true
                });
            }
        };

        providers.forEach(function(provider) {
            li_class = 'provider';
            if (current === provider.identifier) {
                li_class += ' current';
                enable_provider(provider);
            }

            $('<li class="' + li_class + '">' + provider.placeholder + '</li>')
                .data('provider', provider)
                .click(function() {
                    var old_item = $('#org_openpsa_search_providers .current'),
                        query = $('#org_openpsa_search_query');

                    if (old_item.data('provider').autocomplete) {
                        query.category_complete('destroy');
                    }

                    old_item.removeClass('current');
                    $(this).addClass('current');

                    enable_provider(provider);

                    $('#org_openpsa_search_trigger').click();

                    $.post(MIDCOM_PAGE_PREFIX + '__mfa/asgard/preferences/ajax/', {openpsa2_search_provider: provider.identifier});
                })
                .appendTo(selector);
        });

        var search = location.search.replace(/^.*?[\?|&]query=([^&]*).*/, '$1');
        if (search !== '' && search !== location.search) {
            field.val(decodeURIComponent(search));
        }

        selector.insertBefore(field);

        $('<div id="org_openpsa_search_trigger"><i class="fa fa-search"></i></div>')
            .click(function() {
                $('#org_openpsa_search_providers').toggle();
                $(this).toggleClass('focused');
            })
            .insertBefore(field);

        field.show();
    }
};
