<?php
// Available request keys:
// entry, processing_msg_raw, processing_msg, return_url
//
// Available entry fields, see net_nehmer_buddylist_handler_pending::_pending documentation

$entry =& $data['entry'];
?>

<h2><?php echo $data['topic']->extra . ': ' . $data['l10n']->get('buddy requests'); ?></h2>

<p>&(data['processing_msg']);</p>

<p><a href="&(data['return_url']);"><?php $data['l10n_midcom']->show('back'); ?></a></p>