<?php
$nap = new midcom_helper_nav();
$node = $nap->get_node($nap->get_current_node());
?>
<div class="wide">
    <?php
    if ($recurrence = $data['recurrence']) { ?>
        <div class="recurrence">
            <p>
                <strong><?= $data['l10n']->get('recurring event') ?></strong><?php
                if ($recurrence['summary']) {
                    echo ': ' . $recurrence['summary'];
                }
                if ($recurrence['series_url']) {
                    echo " (<a href=\"{$recurrence['series_url']}\">" . $data['l10n']->get('show series') . '</a>)';
                } ?>
            </p>
            <p class="note"><?= $data['l10n']->get('recurring events can only be edited in a calendar client') ?></p>
        </div>
    <?php
    }
    $data['event_dm']->display_view(true);

    if ($data['event']->can_do('org.openpsa.calendar:read')) {
        echo "<div style=\"clear: both;\"></div>\n";
        midcom::get()->dynamic_load("{$node[MIDCOM_NAV_RELATIVEURL]}__mfa/org.openpsa.relatedto/render/{$data['event']->guid}/both/normal/");
    }
    ?>
</div>
<script>
$('body').on('dialogdeleted', '[data-dialog="delete"]', function(e, message) {
    window.parent.$.midcom_services_uimessage_add(message);

    window.parent.openpsa_calendar_instance.refetchEvents();
});
</script>