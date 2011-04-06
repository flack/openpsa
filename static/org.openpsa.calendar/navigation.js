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
