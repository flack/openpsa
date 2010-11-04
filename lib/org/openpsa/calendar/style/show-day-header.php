<?php
//$data =& $_MIDCOM->get_custom_context_data('request_data');
?>
<h2><?php echo sprintf($data['l10n']->get('events on %s'), strftime('%a %x', $data['calendar']->get_day_start())); ?></h2>

<ul class="events">