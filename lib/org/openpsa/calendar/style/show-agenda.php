<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
$data['calendar_options']['defaultView'] = 'list';
$data['calendar_options']['header'] = false;
$data['calendar_options']['navLinks'] = false;
?>

<h2><?php printf($data['l10n']->get('events on %s'), $data['l10n']->get_formatter()->date($data['date'], 'full')); ?></h2>

<div id="org_openpsa_calendar_widget">
</div>

<script type="text/javascript">
$(document).ready(function()
{
    openpsa_calendar_widget.initialize('#org_openpsa_calendar_widget', '&(prefix);', <?php echo json_encode($data['calendar_options']); ?>, true);
});
</script>