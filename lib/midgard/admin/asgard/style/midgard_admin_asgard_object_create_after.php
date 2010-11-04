<script type="text/javascript">
    <?php
    /*
     * we need to determine the correct widget_id prefix here, loading from the parent
     * frame breaks when multiple choosers with creation support exist
     */
    ?>
    var widget_id = window.parent.jQuery("iframe[src^=" + window.location.pathname + "]").attr("id");
    widget_id = widget_id.replace('chooser_widget_creation_dialog_content','');

    function close_dialog(){window.parent.jQuery('#' + widget_id + '_creation_dialog').hide();};
    function add_item(data){window.parent.jQuery('#' + widget_id + '_search_input').midcom_helper_datamanager2_widget_chooser_add_result_item(data);};
    <?php
    if (! isset($data['cancelled']))
    {
        echo "add_item({$data['jsdata']});";
    }
    ?>
    close_dialog();
</script>
