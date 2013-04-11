$(document).ready(function()
{
    var continuous = $('form.datamanager2 #org_openpsa_sales_continuous');
    if (continuous.length > 0)
    {
        if (continuous.on('change', function()
        {
            $('#end_container').toggle(!$(this).is(':checked'));
        }));
        $('#end_container').toggle(!continuous.is(':checked'));
    }
});