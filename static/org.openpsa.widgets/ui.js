const org_openpsa_layout = {
    clip_toolbar: function() {
        const toolbar = document.querySelector('#org_openpsa_toolbar > ul.view_toolbar');
        if (!toolbar) {
            // there seem to be no toolbar buttons, so we don't need to do anything
            return;
        }
        let more = document.getElementById('toolbar_dropdown');
        if (!more) {
            more = $('<li class="enabled submenu" id="toolbar_dropdown"><a><i class="fa fa-caret-down"></i> <span class="toolbar_label">' + TOOLBAR_MORE_LABEL + '</span></a><ul class="midcom_toolbar"></ul></li>')
                .appendTo(toolbar)[0];
            toolbar.classList.add('clip-initialized');
            new ResizeObserver(org_openpsa_layout.clip_toolbar).observe(toolbar);
        }
        const dropdown = more.querySelector('ul'),
            limit = toolbar.getBoundingClientRect().right - parseFloat(getComputedStyle(toolbar).paddingRight),
            overflows = el => el.getBoundingClientRect().right > limit;

        // Start from the unclipped state
        more.before(...dropdown.children);
        more.style.display = 'none';

        const items = [...toolbar.children].filter(li => li !== more && li.offsetParent !== null);
        if (items.length === 0 || !overflows(items[items.length - 1])) {
            return;
        }

        more.style.display = '';
        while (items.length > 0 && overflows(more)) {
            dropdown.prepend(items.pop());
        }
        // Make sure the menu stays inside the viewport
        dropdown.style.left = '';
        const dd_rect = dropdown.getBoundingClientRect(),
            dd_overflow = dd_rect.right - document.documentElement.clientWidth;
        if (dd_overflow > 0) {
            dropdown.style.left = -Math.min(dd_overflow, dd_rect.left) + 'px';
        }
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
                var offset = Math.max((ui.offset.left), 0);

                $('#leftframe').css('width', offset + 'px');

                $.post(MIDCOM_PAGE_PREFIX + '__mfa/asgard/preferences/ajax/', {openpsa2_offset: offset + 2});
            }
        });
    },

    initialize_search: function(providers, config) {
        if (typeof providers !== 'object' || providers.length === 0) {
            return;
        }
        
        let defaults = {
            field: '#org_openpsa_search_query',
            current: providers[0].identifier
        };

        if (typeof config === 'string'){
            config = {field: defaults.field, current: config};
        }

        config = Object.assign(defaults, config);

        var field = $(config.field),
            form = field.closest('form'), 
            selector = $('<ul class="org_openpsa_search_providers"></ul>'),
            li_class = '';

        function enable_provider(provider) {
            field
                .attr('placeholder', provider.placeholder || '')
                .focus();
            form.attr('action', provider.url);

            if (provider.autocomplete) {
                field.category_complete({
                    source: function (request, response) {
                        $.ajax({
                            url: provider.url + '/autocomplete/',
                            dataType: 'json',
                            data: {query: request.term},
                            success: response,
                            error: function(jqXHR, textStatus, errorThrown) {
                                field.trigger('autocompleteerror', [jqXHR, textStatus, errorThrown]);
                                response([]);
                            }
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
        }

        providers.forEach(function(provider) {
            li_class = 'provider';
            if (config.current === provider.identifier) {
                li_class += ' current';
                enable_provider(provider);
            }

            $('<li class="' + li_class + '">' + provider.placeholder + '</li>')
                .data('provider', provider)
                .click(function() {
                    var old_item = form.find('.org_openpsa_search_providers .current');

                    if (old_item.data('provider').autocomplete) {
                        field.category_complete('destroy');
                    }

                    old_item.removeClass('current');
                    $(this).addClass('current');

                    enable_provider(provider);

                    form.find('.org_openpsa_search_trigger').click();

                    $.post(MIDCOM_PAGE_PREFIX + '__mfa/asgard/preferences/ajax/', {openpsa2_search_provider: provider.identifier});
                })
                .appendTo(selector);
        });

        var search = location.search.replace(/^.*?[\?|&]query=([^&]*).*/, '$1');
        if (search !== '' && search !== location.search) {
            field.val(decodeURIComponent(search));
        }

        selector.insertBefore(field);

        $('<div class="org_openpsa_search_trigger"><i class="fa fa-search"></i></div>')
            .click(function() {
                form.find('.org_openpsa_search_providers').toggle();
                $(this).toggleClass('focused');
            })
            .insertBefore(field);

        field.show();
    }
};
