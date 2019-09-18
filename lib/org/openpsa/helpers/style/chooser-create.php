<?php $data['controller']->display_form(); ?>
<script type="text/javascript">
if (self !== parent && $('#container header').length > 0) {
    /*
     * we need to determine the correct widget_id prefix here, loading from the parent
     * frame breaks when multiple choosers with creation support exist
     */
    var widget_id = window.parent.jQuery('iframe[src^="' + window.location.pathname + '"]:visible').attr("id");
    widget_id = widget_id.replace(/_creation_dialog_content/, '');

    var title = $('#container h1'),
        header_height = 12;
    if ($('#org_openpsa_toolbar').length > 0) {
        header_height += $('#org_openpsa_toolbar').height();
    }
    $('header').detach();
    $('#content').addClass('no-header');
    window.parent.jQuery('#' + widget_id + '_creation_dialog')
        .dialog('option', 'title', title.text());
}
</script>