<?php
$ajax_url = $data['router']->generate('message_send_status', ['guid' => $data['message']->guid]);
?>
<div id="org_openpsa_directmarketing_send_uimessages">
    <?php echo $data['l10n']->get('messages sent'); ?>: <div id="org_openpsa_directmarketing_send_uimessages_sent" style="display: inline">??</div> / <div id="org_openpsa_directmarketing_send_uimessages_total" style="display: inline">??</div>
</div>
<script type="text/javascript">
function update_sent_status()
{
    $('#org_openpsa_directmarketing_send_uimessages').data('repeater', window.setTimeout('update_sent_status()', 10000));
    $.get('&(ajax_url);', function(data)
    {
        if (data.status) {
            $.fn.midcom_services_uimessage({
                title: 'Request failed',
                message: data.status,
                type: MIDCOM_SERVICES_UIMESSAGES_TYPE_ERROR
            });
            return;
        }
        $('#org_openpsa_directmarketing_send_uimessages_sent').html(data.receipts);
        $('#org_openpsa_directmarketing_send_uimessages_total').html(data.members);
    });
}
update_sent_status();
</script>