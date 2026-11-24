const org_openpsa_widgets_tabs = {
    loaded_scripts: [],
    initialize: function() {
        org_openpsa_widgets_tabs.bind_events();

        var tabs = $('#tabs').tabs({
            active: org_openpsa_widgets_tabs.get_tab_from_url(),
            beforeLoad: function(event, ui) {
                if (ui.tab.data("loaded")) {
                    event.preventDefault();
                    return;
                }

                ui.jqXHR.done(function() {
                    ui.tab.data("loaded", true);
                });
                ui.ajaxSettings.url = ui.tab.find('.tabs_link').data('uitab');
                ui.ajaxSettings.dataFilter = function(data, type) {
                    return org_openpsa_widgets_tabs.load_head_elements(data, type, event);
                };
            },
            activate: function() {
                const tab_id = tabs.tabs('option', 'active');

                // skip if the URL already points to this tab (i.e. on back/forward navigation)
                if (org_openpsa_widgets_tabs.get_tab_from_url() !== tab_id) {
                    history.pushState({tab_id: tab_id}, $('body').data('title'), '?tab-' + tab_id);
                }
            }
        });
        window.onpopstate = org_openpsa_widgets_tabs.history_loader;
    },
    get_tab_from_url: function() {
        const match = location.search.match(/[?&]tab-(\d+)/);
        return match ? parseInt(match[1], 10) : 0;
    },
    history_loader: function() {
        const tab_id = org_openpsa_widgets_tabs.get_tab_from_url();

        if ($('#tabs').tabs('option', 'active') !== tab_id) {
            $('#tabs').tabs('option', 'active', tab_id);
        }
    },
    modifier_active: function(event) {
        return event.ctrlKey || event.metaKey || event.shiftKey;
    },
    bind_events: function() {
        $('#tabs')[0].addEventListener('click', function(event) {
            if (org_openpsa_widgets_tabs.modifier_active(event) && event.target.closest('.ui-tabs-nav a')) {
                // leave modified clicks (open in new tab/window) to the browser
                event.stopPropagation();
            }
        }, true); // <= capture phase, because bubble is preventDefault()ed in jquery.ui 

        $('#tabs')
            .on('mousedown', '.ui-tabs-nav .ui-state-active a', function(event) {
                if (event.which !== 1 || org_openpsa_widgets_tabs.modifier_active(event)) {
                    return;
                }
                location.href = this.href;
            })
            // clean up links before they are used, so that "open in new tab" etc. get the full page
            .on('pointerdown focusin click', '.ui-tabs-panel a', org_openpsa_widgets_tabs.strip_prefix)
            .on('click', '.ui-tabs-panel a', org_openpsa_widgets_tabs.intercept_clicks)

            //since this is loaded in a tab - change the submit-function of
            // an occurring form - so the result will be loaded in the tab also
            .on("submit", 'form:not(.tab_escape)', function(event) {
                if ($(event.currentTarget).attr('onsubmit')) {
                    return;
                }
                var send_data = new FormData(this, event.originalEvent?.submitter),
                    panel = $(this).closest('.ui-tabs-panel');

                $.ajax({
                    data: send_data,
                    processData: false,
                    contentType: false,
                    dataFilter: org_openpsa_widgets_tabs.load_head_elements,
                    type: this.method,
                    url: this.getAttribute('action'),
                    success: function(data) {
                        panel.html(data);
                    },
                    error: org_openpsa_widgets_tabs.show_error
                });
                event.preventDefault();
            });
    },
    show_error: function(response) {
        if ($('#midcom_services_uimessages_wrapper').length > 0) {
            const page = new DOMParser().parseFromString(response.responseText || '', 'text/html'),
                title = page.querySelector('h1'),
                message = page.querySelector('.error-message, p'),
                escape = text => $('<div>').text(text).html();

            $.midcom_services_uimessage_add({
                type: 'error',
                title: escape(title?.textContent.trim() || response.statusText),
                message: escape(message?.textContent.trim() || 'Request failed')
            });
        }
    },
    load_head_elements: function(data, type, event) {
        data = data.replace(/<\/HEAD_ELEMENTS>[\s\S]+?/m, '</HEAD_ELEMENTS>');
        var regex = /<HEAD_ELEMENTS>(.+?)<\/HEAD_ELEMENTS>/m;
        regex.exec(data);
        var head_elements = JSON.parse(RegExp.$1),
            inserted = [];

        data = data.slice(0, -(RegExp.$1.length + 31));
        head_elements.head_js.forEach(function(jscall) {
            if (   typeof jscall.url !== 'undefined'
                && $('script[src="' + jscall.url + '"]').length === 0
                && org_openpsa_widgets_tabs.loaded_scripts.indexOf(jscall.url) === -1) {
                org_openpsa_widgets_tabs.loaded_scripts.push(jscall.url);
                inserted.push(new Promise(function(resolve) {
                    var tag = document.createElement('script');
                    tag.src = jscall.url;
                    tag.async = false;
                    tag.onload = resolve;
                    document.head.appendChild(tag);
                }));
            }
            else if (   typeof jscall.content !== 'undefined'
                     && jscall.content.length > 0) {
                $('<script type="text/javascript">' + jscall.content + '</script>');
            }
        });

        var insertion_point = $('link[rel="stylesheet"]').first();
        head_elements.head_css.forEach(function(data) {
            if (   typeof data.type === 'undefined'
                || typeof data.href === 'undefined'
                || data.type !== 'text/css') {
                return;
            }
            if ($('link[href="' + data.href + '"]').length !== 0) {
                insertion_point = $('link[href="' + data.href + '"]');
            } else {
                var tag = '<link';
                $.each(data, function(key, value) {
                    tag += ' ' + key + '="' + value + '"';
                });
                tag += ' />';

                inserted.push(new Promise(function(resolve) {
                    tag = $(tag);
                    tag[0].onload = resolve;
                    insertion_point.after(tag);
                }));

                insertion_point = insertion_point.next();
            }
        });

        if (inserted.length > 0) {
            var active = $('#tabs > [aria-labelledby="key_' + $('#tabs').tabs('option', 'active') + '"]');
            Promise.all(inserted).then(function() {
                active.html(data);
            });
            if (event) {
                event.preventDefault();
            }
            return;
        }

        return data;
    },
    strip_prefix: function(event) {
        const link = event.currentTarget,
            href = link.href;

        if (link.getAttribute('onclick') || !href.match(/\/uitab\//)) {
            return;
        }
        //for now, only links to plugins will be displayed inside the tab
        if (   href.match(/\/__mfa\/org\.openpsa/)
            && !link.classList.contains('tab_escape')
            && !href.endsWith('#')) {
            link.dataset.uitab = href;
        }
        link.href = href.replace(/\/uitab\/+/, '/');
    },
    intercept_clicks: function(event) {
        const panel = $(event.currentTarget).closest('.ui-tabs-panel'),
            url = event.currentTarget.dataset.uitab;

        if (url && !org_openpsa_widgets_tabs.modifier_active(event)) {
            $.ajax({
                url: url,
                dataFilter: org_openpsa_widgets_tabs.load_head_elements,
                success: function(data) {
                    panel.html(data);
                },
                error: org_openpsa_widgets_tabs.show_error
            });

            event.preventDefault();
        }
    }
};
