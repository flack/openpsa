if (self !== parent) {
    /*
     * we need to determine the correct widget_id prefix here, loading from the parent
     * frame breaks when multiple choosers with creation support exist
     */
    var widget_id = window.parent.jQuery('iframe[src^="' + window.location.pathname + '"]:visible').attr("id");
    widget_id = widget_id.replace(/_creation_dialog_content/, '');

    function add_item(data) {
        window.parent.midcom_helper_datamanager2_autocomplete.add_result_item(widget_id, data);
        window.parent.jQuery('#' + widget_id + '_creation_dialog').remove();
    }

    if (window.parent.$('#' + widget_id + '_creation_dialog').length > 0) {
        window.addEventListener('DOMContentLoaded', function() {
            attach_to_parent_dialog(window.parent.$('#' + widget_id + '_creation_dialog'));
        });
    }
}
