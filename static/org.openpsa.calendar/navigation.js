$(document).ready(function()
{
    $("#date-navigation").parent().bind("click", function(event)
    {
        if (   $(event.target).parent().attr('id') != 'date-navigation'
            && $(event.target).attr('id') != 'date-navigation')
        {
            //don't fire on datepicker navigation clicks
            return;
        }
        if ($(this).hasClass("active"))
        {
            $(this).removeClass("active");
            $("#date-navigation-widget").hide();
        }
        else if ($(this).hasClass("initialized"))
        {
            $("#date-navigation-widget").show();
            $(this).addClass("active");
        }
        else
        {
            $("#date-navigation").append("<div id=\"date-navigation-widget\"></div>");
            $("#date-navigation-widget").css("position", "absolute");
            $("#date-navigation-widget").css("z-index", "1000");
            $("#date-navigation-widget").datepicker(
            {
                dateFormat: "yy-mm-dd",
                defaultDate: org_openpsa_calendar_default_date,
                prevText: "",
                nextText: "",
                onSelect: function(dateText, inst)
                {
                    window.location = org_openpsa_calendar_prefix + '/' + dateText + "/";
                }
            });
            $(this).addClass("active");
            $(this).addClass("initialized");
        }
    });
});

var openpsa_calendar =
{
    initialize: function(settings)
    {
        $('.calendarwidget .free-slot').on('click', function()
        {
            var resource = $(this).data('resource'),
            start = $(this).data('start'),
            url = settings.prefix + 'new/',
            window_options = "toolbar=0,location=0,status=0,height=" + settings.height + ",width=" + settings.width + ",dependent=1,alwaysRaised=1,scrollbars=1,resizable=1";

            if (   resource
                && resource.length > 0)
            {
                url += resource + '/';
            }
            if (   start
                && start > 0)
            {
                url += start + '/';
            }

            window.open(url, 'newevent', window_options);
        });
    }
};