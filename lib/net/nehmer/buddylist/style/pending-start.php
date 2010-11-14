<?php
// Available request keys:
// pending
?>

<h2><?php echo $data['topic']->extra . ': ' . $data['l10n']->get('buddy requests'); ?></h2>

<p><?php printf($data['l10n']->get('%u new buddy requests.'), count($data['pending'])); ?></p>