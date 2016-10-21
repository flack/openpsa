(function($) {
    $.fn.extend(
    {
        project_prospects_renderer: function(config)
        {
            var defaults =
            {
                base_url: '',
                task_guid: false
            };

            config = $.extend(defaults, config);

            /* TODO: Make interval */
            get_prospect_list();

            function get_prospect_list()
            {
                var url = config.base_url + 'task/resourcing/prospects/' + config.task_guid + '/';
                /* Set the class which should give us a "loading" icon */
                $('#prospects_list').addClass('project_prospects_renderer_searching');
                $.ajax(
                {
                    url: url,
                    success: ajax_success,
                    error: ajax_failure
                });
            }

            function ajax_success(data)
            {
                var label, prospect;
                $('#prospects_list').removeClass('project_prospects_renderer_searching');
                $('#prospects_list').addClass('project_prospects_renderer_search_ok');
                /* Display lines in result */
                $('person', data).each(function()
                {
                    prospect = $('prospect', this).text();
                    label = $('label', this).text();
                    add_result(prospect, label);
                });
            }

            function add_result(prospect, label)
            {
                var url = config.base_url + 'task/resourcing/prospect/' + prospect + '/';
                $('#prospects_list').append('<li id="prospect_' + prospect + '" class="project_prospects_renderer_searching">' + label + '</li>');
                /* new Insertion.Bottom(this.element, '<li id="prospect_' + prospect + '" class="project_prospects_renderer_searching" style="display: none;">' + label + '</li>'); */
                /* todo: use blinddown, etc to make the ui less jumpy */
                $('#prospect_' + prospect).load(url);

                $('#prospect_' + prospect).removeClass('project_prospects_renderer_searching');
                $('#prospect_' + prospect).addClass('project_prospects_renderer_search_ok');
            }

            function ajax_failure(obj, type)
            {
                /* This is called on xmlHttpRequest level failure,
                 MidCOM level errors are reported via the XML returned */
                $(this).removeClass('project_prospects_renderer_searching');
                $(this).addClass('project_prospects_renderer_search_fail');
                $.midcom_services_uimessage_add(
                {
                    type: 'error',
                    title: 'Project prospects',
                    message: 'Ajax request level failure'
                });
                /* TODO: Some kind of error handling ?? */
                return true;
            }

        }
    });
})(jQuery);

function project_prospects_slot_changed(id)
{
    var slot_cb = $(id + '_checkbox');
    if (slot_cb.checked)
    {
        project_prospects_choose_slot(id);
    }
    else
    {
        project_prospects_unchoose_slot(id);
    }
}

function project_prospects_choose_slot(id)
{
    /* alert('project_prospects_choose_slot called:' + id); */
    /* todo: do something more useful */
    $("#" + id).addClass('selected');
}

function project_prospects_unchoose_slot(id)
{
    /* alert('project_prospects_unchoose_slot called:' + id); */
    /* todo: do something more useful */
    $("#" + id).removeClass('selected');
}
