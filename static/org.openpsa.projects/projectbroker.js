(function(jQuery) {
    jQuery.fn.extend(
    {
        project_prospects_renderer: function(config) 
        {
            var seen_prospects = new Array();
            var ajax_request = false;
            var ahah_requests = new Array();
            
            var defaults = 
            {
                base_url: '',
                task_guid: false
            };
    
            var config = jQuery.extend(defaults, config);
    
            /* TODO: Make interval */
            get_prospect_list();
    
            function get_prospect_list()
            {
                var url = config.base_url + 'task/resourcing/prospects/' + config.task_guid + '/';
                /* Set the class which should give us a "loading" icon */
                jQuery('#prospects_list').addClass('project_prospects_renderer_searching');
                jQuery.ajax(
                {
                    url: url,
                    success: ajax_success,
                    error: ajax_failure,
                });
            }
    
            function ajax_success(data)
            {
                //alert('ajax_success called');
                jQuery('#prospects_list').removeClass('project_prospects_renderer_searching');
                jQuery('#prospects_list').addClass('project_prospects_renderer_search_ok');
                /* Display lines in result */
                jQuery('person', data).each(function(idx) 
                {
                    prospect = jQuery('prospect', this).text();
                    label = jQuery('label', this).text();
                    add_result(prospect, label);
                });
            }
    
            function add_result(prospect, label)
            {
                url = config.base_url + 'task/resourcing/prospect/' + prospect + '/';
                jQuery('#prospects_list').append('<li id="prospect_' + prospect + '" class="project_prospects_renderer_searching">' + label + '</li>');
                /* new Insertion.Bottom(this.element, '<li id="prospect_' + prospect + '" class="project_prospects_renderer_searching" style="display: none;">' + label + '</li>'); */
                /* todo: use blinddown, etc to make the ui less jumpy */
                jQuery('#prospect_' + prospect).load(url);
                
                jQuery('#prospect_' + prospect).removeClass('project_prospects_renderer_searching');
                jQuery('#prospect_' + prospect).addClass('project_prospects_renderer_search_ok');
            }
    
            function ajax_failure(obj, type, expobj)
            {
                /* This is called on xmlHttpRequest level failure,
                 MidCOM level errors are reported via the XML returned */
                jQuery(this).removeClass('project_prospects_renderer_searching');
                jQuery(this).addClass('project_prospects_renderer_search_fail');
                jQuery.midcom_services_uimessage_add(
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
    slot_cb = $(id + '_checkbox');
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
    jQuery("#" + id).addClass('selected');
}

function project_prospects_unchoose_slot(id)
{
    /* alert('project_prospects_unchoose_slot called:' + id); */
    /* todo: do something more useful */
    jQuery("#" + id).removeClass('selected');
}
