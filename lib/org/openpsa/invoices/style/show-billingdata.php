<div class="main">
    <?php $data['controller']->display_form(); ?>
</div>

<script type="text/javascript">

function hide_invoice_address()
{
    if($('#org_openpsa_invoices_use_contact_address').is(':checked'))
    {
        $(".invoice_adress").hide();
    }
    else
    {
        $(".invoice_adress").show();
    }
}

$(document).ready(function()
{
    hide_invoice_address();
    $('#org_openpsa_invoices_use_contact_address').change(function()
    {
        hide_invoice_address();
    })
});

</script>