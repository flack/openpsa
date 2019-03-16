var openpsa_calendar_widget = {
    popstate: false,
    refresh: function() {
        if (window.opener.openpsa_calendar_instance) {
            window.opener.openpsa_calendar_instance.fullCalendar('refetchEvents');
        }
    },
    prepare_toolbar_buttons: function(selector, prefix) {
        $('#openpsa_calendar_add_event').on('click', function() {
            var date = $(selector).fullCalendar('getDate');
            $(this).attr('href', prefix + 'event/new/?start=' + date.add(1, 's').format('YYYY-MM-DD HH:mm:ss'));
        });
        $("#date-navigation").parent().on("click", function(event) {
            event.preventDefault();
            if (   $(event.target).parent().attr('id') !== 'date-navigation'
                && $(event.target).attr('id') !== 'date-navigation') {
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
                var default_date = $(selector).fullCalendar('getDate').toDate();
                $("#date-navigation-widget").datepicker({
                    dateFormat: "yy-mm-dd",
                    defaultDate: default_date,
                    prevText: "",
                    nextText: "",
                    onSelect: function(dateText) {
                        var date = $.fullCalendar.moment(dateText);
                        $(selector).fullCalendar('gotoDate', date);

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
                case 'month':
                case 'basicDay':
                case 'basicWeek':
                case 'agendaDay':
                case 'agendaWeek':
                    settings.defaultView = args[0];
                    break;
            }
        }
        if (args[1] !== undefined) {
            settings.defaultDate = args[1];
        }
        return settings;
    },
    update_url: function(selector, prefix) {
        var last_state = history.state,
            view = $(selector).fullCalendar('getView'),
            state_data = {
                date: $.fullCalendar.moment($(selector).fullCalendar('getDate')).format('YYYY-MM-DD'),
                view: view.name
            },
            new_url = prefix + view.name + '/' + state_data.date + '/';
        // skip if the last state was same as current, or if we were triggered via a popstate event
        if (!openpsa_calendar_widget.popstate && (!last_state || prefix + last_state.name + '/' + last_state.view + '/' !== new_url)) {
            history.pushState(state_data, view.title + ' ' + $('body').data('title'), new_url);
        }
    },
    set_height: function(selector) {
        var new_height = $('#content-text').height();

        if (new_height !== $(selector).height()) {
            $(selector).fullCalendar('option', 'height', new_height);
        }
    },
    initialize: function(selector, prefix, settings, embedded) {
        function save_event(event, delta, revertFunc) {
            var params = {
                start: event.start.add(1, 's').format('X')
            };
            //workaround for https://github.com/fullcalendar/fullcalendar/issues/3037
            if (event.end) {
                params.end = event.end.format('X');
            }
            $.post(prefix + 'event/move/' + event.id + '/', params)
                .fail(function() {
                    revertFunc();
                });
        }
        $('body').data('title', document.title);

        var defaults = {
            theme: true,
            defaultView: "month",
            weekNumbers: true,
            weekMode: 'liquid',
            firstHour: 8,
            ignoreTimezone: false,
            nowIndicator: true,
            editable: true,
            navLinks: true,
            header: {
                left: 'month,agendaWeek,agendaDay',
                center: 'title',
                right: 'today prev,next'
            },
            events: function (start, end, timezone, callback) {
                $.ajax({
                    url: prefix + 'json/',
                    dataType: 'json',
                    data: {
                        start: start.format('X'),
                        end: end.format('X')
                    },
                    success: function (events) {
                        callback(events);
                    }
                });
            },
            viewRender: function() {
                if (!embedded) {
                    openpsa_calendar_widget.update_url(selector, prefix);
                }
            },
            eventRender: function (event, element) {
                if (event.participants) {
                    element.find('.fc-content, .fc-list-item-title a').append('<span class="participants">(' + event.participants.join(', ') + ')</span>');
                }
            },
            eventClick: function (calEvent, jsEvent) {
                jsEvent.preventDefault();
                create_dialog($(jsEvent.target), '', prefix + 'event/' + calEvent.id + '/');
            },
            selectable: true,
            selectHelper: true,
            select: function(start, end) {
                var url = prefix + 'event/new/?start=' + start.add(1, 's').format('YYYY-MM-DD HH:mm:ss') + '&end=' + end.format('YYYY-MM-DD HH:mm:ss');
                create_dialog($('#openpsa_calendar_add_event'), '', url);
            },
            eventDrop: save_event,
            eventResize: save_event
        };

        settings = $.extend({}, defaults, openpsa_calendar_widget.parse_url(prefix), settings || {});

        window.openpsa_calendar_instance = $(selector).fullCalendar(settings);
        openpsa_calendar_widget.prepare_toolbar_buttons(selector, prefix);

        if (!embedded) {
            if (window.hasOwnProperty('history')) {
                window.onpopstate = function(event) {
                    if (event.state) {
                        openpsa_calendar_widget.popstate = true;
                        $(selector).fullCalendar('gotoDate', $.fullCalendar.moment(event.state.date));
                        $(selector).fullCalendar('changeView', event.state.view);
                        openpsa_calendar_widget.popstate = false;
                    }
                };
            }

            org_openpsa_resizers.append_handler('calendar', function() {
                openpsa_calendar_widget.set_height(selector);
            });
        }
    }
};
