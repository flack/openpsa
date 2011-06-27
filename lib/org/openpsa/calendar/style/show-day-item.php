<?php
$nap = new midcom_helper_nav();
$node = $nap->get_node($nap->get_current_node());

$event = new org_openpsa_widgets_calendar_event($data['event']);
$event->link = '#';
$event->onclick = org_openpsa_calendar_interface::calendar_editevent_js($data['event']->guid, $node);
echo $event->render('li');
?>