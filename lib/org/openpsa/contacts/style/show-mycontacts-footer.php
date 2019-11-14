<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
</dl>
</div>

<script type="text/javascript">
$('i.delete').on('click', function(){
    var guid = this.id.substr(38),
    				loading = "<i class='fa fa-spinner fa-spin'></i>";
    $('#org_openpsa_widgets_contact-' + guid).css('text-align', 'center');
    $('#org_openpsa_widgets_contact-' + guid).html(loading);
    $.ajax({
        url: "<?php echo $prefix; ?>mycontacts/remove/" + guid + "/",
        success: function() {
            $('#org_openpsa_widgets_contact-' + guid).fadeOut('fast', function() {
                $('#org_openpsa_widgets_contact-' + guid).remove();
            });
        }
    });
});
</script>

