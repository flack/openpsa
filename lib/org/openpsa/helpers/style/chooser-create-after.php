<script type="text/javascript">
   /*
    * we need to determine the correct widget_id prefix here, loading from the parent
    * frame breaks when multiple choosers with creation support exist
    */
   var widget_id = window.parent.jQuery('iframe[src^="' + window.location.pathname + '"]:visible').attr("id");
   widget_id = widget_id.replace(/_creation_dialog_content/, '');

   function close_dialog()
   {
       window.parent.jQuery('#' + widget_id + '_creation_dialog').remove();
   }
   function add_item(data)
   {
       window.parent.midcom_helper_datamanager2_autocomplete.add_result_item(widget_id, data);
   }
    <?php
    if ($data['action'] == 'save') {
        echo "add_item({$data['jsdata']});";
    }
    ?>
      close_dialog();
</script>