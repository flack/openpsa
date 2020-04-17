<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
$data['calendar_options']['defaultView'] = 'listWeek';
$data['calendar_options']['navLinks'] = false;
$data['calendar_options']['header'] = false;
$nap = new midcom_helper_nav;
$node = $nap->get_node($nap->get_current_node());
$url = $node[MIDCOM_NAV_ABSOLUTEURL] . 'timeGridWeek/' . $data['calendar_options']['defaultDate'] . '/';
?>
<h2><a href="&(url);">&(node[MIDCOM_NAV_NAME]);</a></h2>
<div id="org_openpsa_calendar_widget">
</div>

<script type="text/javascript">
    openpsa_calendar_widget.initialize('#org_openpsa_calendar_widget', '&(prefix);', <?php echo json_encode($data['calendar_options']); ?>, true);
</script>