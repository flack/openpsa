<?php
// Bind the view data, remember the reference assignment.
//
// Available keys: return_url
//$data =& $_MIDCOM->get_custom_context_data('request_data');
?>

<h2><?php $data['l10n']->show('activation pending'); ?></h2>
<p><?php echo $data['l10n']->get('your account has not yet been activated'); ?></p>
