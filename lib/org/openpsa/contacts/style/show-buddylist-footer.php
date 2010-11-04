<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
</dl>
</div>

<script type="text/javascript">
jQuery('input.delete').bind('click', function(){
    var guid = this.id.substr(33);
    var loading = "<img src='" + MIDCOM_STATIC_URL + "/midcom.helper.datamanager2/ajax-loading.gif' alt='loading' />";
    jQuery('#org_openpsa_contactwidget-' + guid).css('text-align', 'center');
    jQuery('#org_openpsa_contactwidget-' + guid).html(loading);
    jQuery.ajax({
        url: "<?php echo $prefix; ?>buddylist/remove/" + guid + "/",
        success: function(){
            jQuery('#org_openpsa_contactwidget-' + guid).fadeOut('fast', function() {
                jQuery('#org_openpsa_contactwidget-' + guid).remove();
            });
        }
    });
});
</script>

