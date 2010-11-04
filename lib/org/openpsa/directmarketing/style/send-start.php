<?php
if (!$data['message_obj']->test_mode)
{
    midcom_show_style('send-status');
?>
<script type="text/javascript">
repeater = setInterval('org_openpsa_directmarketing_get_send_status()', 10000);
</script>
<?php
}
else
{
//TODO: Send test mode (or different style ??)
}
?>