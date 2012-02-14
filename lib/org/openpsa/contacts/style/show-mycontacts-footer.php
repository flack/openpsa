<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
</dl>
</div>

<script type="text/javascript">
jQuery('input.delete').bind('click', function(){
    var guid = this.id.substr(38);
    var loading = "<img src='" + MIDCOM_STATIC_URL + "/midcom.helper.datamanager2/ajax-loading.gif' alt='loading' />";
    jQuery('#org_openpsa_widgets_contact-' + guid).css('text-align', 'center');
    jQuery('#org_openpsa_widgets_contact-' + guid).html(loading);
    jQuery.ajax({
        url: "<?php echo $prefix; ?>mycontacts/remove/" + guid + "/",
        success: function(){
            jQuery('#org_openpsa_widgets_contact-' + guid).fadeOut('fast', function() {
                jQuery('#org_openpsa_widgets_contact-' + guid).remove();
            });
        }
    });
});
</script>

