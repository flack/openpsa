const openpsa_calendar_widget = {
    popstate: false,
    refresh: function() {
        if (window.opener.openpsa_calendar_instance) {
            window.opener.openpsa_calendar_instance.refetchEvents();
        }
    },
    prepare_toolbar_buttons: function(selector, prefix) {
        $('#openpsa_calendar_add_event').on('click', function() {
            var date = window.openpsa_calendar_instance.getDate();
            this.href = prefix + 'event/new/?start=' + window.openpsa_calendar_instance.formatIso(date, true);
        });
        $("#date-navigation").parent().on("click", function(event) {
            event.preventDefault();
            if (   event.target.parentNode.id !== 'date-navigation'
                && event.target.id !== 'date-navigation') {
                //don't fire on datepicker navigation clicks
                return;
            }
            if ($(this).hasClass("active")) {
                $(this).removeClass("active");
                $("#date-navigation-widget").hide();
            } else if ($(this).hasClass("initialized")) {
                $("#date-navigation-widget").show();
                $(this).addClass("active");
            } else {
                $("#date-navigation").append("<div id=\"date-navigation-widget\"></div>");
                $("#date-navigation-widget").css("position", "absolute");
                $("#date-navigation-widget").css("z-index", "1000");
                var default_date = window.openpsa_calendar_instance.getDate();
                $("#date-navigation-widget").datepicker({
                    dateFormat: "yy-mm-dd",
                    initialDate: default_date,
                    prevText: "",
                    nextText: "",
                    onSelect: function(dateText) {
                        window.openpsa_calendar_instance.gotoDate(new Date(dateText));

                        $("#date-navigation").parent().removeClass("active");
                        $("#date-navigation-widget").hide();
                    }
                });
                $(this).addClass("active");
                $(this).addClass("initialized");
            }
        });
    },
    parse_url: function(prefix) {
        var args = location.pathname.substr(prefix.length).split('/'),
        settings = {};
        if (args[0] !== undefined) {
            switch (args[0]) {
                case 'dayGridMonth':
                case 'dayGridDay':
                case 'dayGridWeek':
                case 'timeGridDay':
                case 'timeGridWeek':
                    settings.initialView = args[0];
                    break;
            }
        }
        if (args[1] !== undefined) {
            settings.initialDate = args[1];
        }
        return settings;
    },
    update_url: function(selector, prefix) {
        var last_state = history.state,
            view = window.openpsa_calendar_instance.view,
            state_data = {
                date: window.openpsa_calendar_instance.formatIso(window.openpsa_calendar_instance.getDate(), true),
                view: view.type
            },
            new_url = prefix + view.type + '/' + state_data.date + '/';
        // skip if the last state was same as current, or if we were triggered via a popstate event
        if (!openpsa_calendar_widget.popstate && (!last_state || prefix + last_state.name + '/' + last_state.view + '/' !== new_url)) {
            history.pushState(state_data, view.title + ' ' + $('body').data('title'), new_url);
        }
    },
    initialize: function(selector, prefix, settings, embedded) {
        function save_event(info) {
            var params = {
                start: ((info.event.start.getTime() / 1000) - info.event.start.getTimezoneOffset() * 60) + 1
            };
            //workaround for https://github.com/fullcalendar/fullcalendar/issues/3037
            if (info.event.end) {
                params.end = (info.event.end.getTime() / 1000) - info.event.end.getTimezoneOffset() * 60;
            }
            $.post(prefix + 'event/move/' + info.event.id + '/', params)
                .fail(function() {
                    info.revert();
                });
        }
        $('body').data('title', document.title);

        var defaults = {
            theme: true,
            initialView: "dayGridMonth",
            weekNumbers: true,
            weekMode: 'liquid',
            firstHour: 8,
            ignoreTimezone: false,
            nowIndicator: true,
            editable: true,
            navLinks: true,
            height: '100%',
            headerToolbar: {
                left: 'dayGridMonth,timeGridWeek,timeGridDay',
                center: 'title',
                right: 'today prev,next'
            },
            events: function (fetch_info, success_callback, failure_callback) {
                $.ajax({
                    url: prefix + 'json/',
                    dataType: 'json',
                    data: {
                        start: fetch_info.start.getTime() / 1000,
                        end: fetch_info.end.getTime() / 1000
                    },
                    success: success_callback,
                    error: failure_callback
                });
            },
            datesSet: function() {
                if (!embedded) {
                    openpsa_calendar_widget.update_url(selector, prefix);
                }
            },
            eventContent: function (info) {
                if (info.event.extendedProps.participants) {
                    return {
                        html: '<div class="fc-event-time">' + info.timeText + '</div><div class="fc-event-title-container"><div class="fc-event-title fc-sticky">' + info.event.title + '</div></div><span class="participants">(' + info.event.extendedProps.participants.join(', ') + ')</span>'
                    };
                }
            },
            eventClick: function (info) {
                info.jsEvent.preventDefault();
                info.jsEvent.target.dataset.dialogCancelLabel = settings.l10n.cancel;
                create_dialog($(info.jsEvent.target), '', prefix + 'event/' + info.event.id + '/');
            },
            selectable: true,
            selectHelper: true,
            select: function(info) {
                var url = prefix + 'event/new/?start=',
                    start = window.openpsa_calendar_instance.formatIso(info.start),
                    end = window.openpsa_calendar_instance.formatIso(info.end);
                create_dialog($('#openpsa_calendar_add_event'), '', url + encodeURIComponent(start) + '&end=' + encodeURIComponent(end));
            },
            eventDrop: save_event,
            eventResize: save_event
        };

        settings = $.extend({}, defaults, openpsa_calendar_widget.parse_url(prefix), settings || {});

        window.openpsa_calendar_instance = new FullCalendar.Calendar($(selector)[0], settings);
        window.openpsa_calendar_instance.render();

        openpsa_calendar_widget.prepare_toolbar_buttons(selector, prefix);

        if (!embedded) {
            if (window.hasOwnProperty('history')) {
                window.onpopstate = function(event) {
                    if (event.state) {
                        openpsa_calendar_widget.popstate = true;
                        window.openpsa_calendar_instance.gotoDate(event.state.date);
                        window.openpsa_calendar_instance.changeView(event.state.view);
                        openpsa_calendar_widget.popstate = false;
                    }
                };
            }
        }
    }
};
