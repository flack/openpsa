<?php
// Available request keys:
// pending

//$data =& $_MIDCOM->get_custom_context_data('request_data');
?>

<h2><?php echo $data['topic']->extra . ': ' . $data['l10n']->get('buddy requests'); ?></h2>

<p><?php printf($data['l10n']->get('%u new buddy requests.'), count($data['pending'])); ?></p>