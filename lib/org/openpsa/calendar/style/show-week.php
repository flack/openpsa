<?php
//$data =& $_MIDCOM->get_custom_context_data('request_data');
?>
<div id="org_openpsa_calendar_calendarwidget"></div>
<div class="wide">
    <h1><?php echo sprintf($data['l10n']->get("week #%s %s"), strftime("%W", $data['selected_time']), strftime("%Y", $data['selected_time'])); ?></h1>
    <?php
    $data['calendar']->show();
    ?>
</div>