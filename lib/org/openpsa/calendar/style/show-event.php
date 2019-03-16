<?php
$nap = new midcom_helper_nav();
$node = $nap->get_node($nap->get_current_node());
?>
<div class="wide">
    <?php
    $data['event_dm']->display_view();

    if ($data['event']->can_do('org.openpsa.calendar:read')) {
        echo "<div style=\"clear: both;\"></div>\n";
        midcom::get()->dynamic_load("{$node[MIDCOM_NAV_RELATIVEURL]}__mfa/org.openpsa.relatedto/render/{$data['event']->guid}/both/normal/");
    }
    ?>
</div>
<script>
$('body').on('dialogdeleted', '[data-dialog="delete"]', function(e, message) {
    window.parent.$.midcom_services_uimessage_add(message);

    window.parent.openpsa_calendar_instance.fullCalendar('refetchEvents');
});
</script>