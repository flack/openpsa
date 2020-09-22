const org_openpsa_widgets_tabs = {
    loaded_scripts: [],
    popstate: false,
    initialize: function(uiprefix) {
        org_openpsa_widgets_tabs.bind_events(uiprefix);

        var tabs = $('#tabs').tabs({
            beforeLoad: function(event, ui) {
                if (ui.tab.data("loaded")) {
                    event.preventDefault();
                    return;
                }

                ui.jqXHR.done(function() {
                    ui.tab.data("loaded", true);
                });
                ui.ajaxSettings.dataFilter = function(data, type) {
                    return org_openpsa_widgets_tabs.load_head_elements(data, type, event);
                };
            },
            load: function() {
                $(window).trigger('resize');
            },
            activate: function() {
                $(window).trigger('resize');

                var last_state = history.state,
                    data = {tab_id: tabs.tabs('option', 'active')};

                // skip if the last state was same than current
                if (!org_openpsa_widgets_tabs.popstate && (!last_state || last_state.tab_id !== data.tab_id)) {
                    history.pushState(data, $('body').data('title'), '?tab-' + data.tab_id);
                }
            },
            create: function() {
                if (window.hasOwnProperty('history')) {
                    window.onpopstate = org_openpsa_widgets_tabs.history_loader;
                }
            }
        });
    },
    history_loader: function() {
        var state = history.state,
            tab_id = 0;

        if (state) {
            tab_id = state.tab_id;
        }

        if ($('#tabs').tabs('option', 'active') != tab_id) {
            org_openpsa_widgets_tabs.popstate = true;
            $('#tabs').tabs('option', 'active', tab_id);
            org_openpsa_widgets_tabs.popstate = false;
        }
    },
    bind_events: function(uiprefix) {
        $('#tabs')
            .on('mousedown', '.ui-state-active a', function(event) {
                if (event.which !== 1) {
                    return;
                }
                var url = this.href.replace(new RegExp('/' + uiprefix + '/'), '/');
                location.href = url;
            })
            .on('click', '.ui-tabs-panel a', org_openpsa_widgets_tabs.intercept_clicks)

            //since this is loaded in a tab - change the submit-function of
            // an occurring form - so the result will be loaded in the tab also
            .on("submit", 'form:not(.tab_escape)', function(event) {
                if ($(event.currentTarget).attr('onsubmit')) {
                    return;
                }
                var send_data = new FormData(this);

                $.ajax({
                    data: send_data,
                    processData: false,
                    contentType: false,
                    dataFilter: org_openpsa_widgets_tabs.load_head_elements,
                    type: this.method,
                    url: this.getAttribute('action'),
                    success: function(data) {
                        $(":not(.ui-tabs-hide) > .tab_div").html(data);
                        $(window).trigger('resize');
                    }
                });
                event.preventDefault();
            });
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
                $(window).trigger('resize');
            });
            if (event) {
                event.preventDefault();
            }
            return;
        }

        return data;
    },
    intercept_clicks: function(event) {
        var target = $(event.currentTarget),
            href = event.currentTarget.href;

        if (target.attr('onclick')) {
            return;
        }

        if (!href.match(/\/uitab\//)) {
            return;
        }
        //for now, only links to plugins will be displayed inside the tab
        if (   !href.match(/\/__mfa\/org\.openpsa/)
            || target.hasClass('tab_escape')) {
            target.attr('href', href.replace(/\/uitab\/+/, '/'));
            return;
        }

        if (href.slice(href.length - 1, href.length) !== '#') {
            $.ajax({
                url: href,
                dataFilter: org_openpsa_widgets_tabs.load_head_elements,
                success: function(data) {
                    $(":not(.ui-tabs-hide) > .tab_div").html(data);
                }
            });

            event.preventDefault();
        }
    }
};
