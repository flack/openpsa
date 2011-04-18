function midcom_helper_datamanager2_autocomplete_query(request, response)
{
    var query_options_var = $('input:focus').attr('id').replace(/_input$/, '') + '_handler_options',
    query_options = window[query_options_var];
    query_options.term = request.term;
    $.ajax({
            url: query_options.handler_url,
            dataType: "json",
            data: query_options,
            success: function( data )
            {
                response(data);
            }
    });
}

function midcom_helper_datamanager2_autocomplete_select(event, ui)
{
    var selection_holder_id = $(event.target).attr('id').replace(/_input$/, '') + '_selection';
    $('#' + selection_holder_id).val(JSON.stringify([ui.item.id]));
}
