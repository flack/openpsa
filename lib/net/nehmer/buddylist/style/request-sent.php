<?php
// Available request keys:
// entry, processing_msg_raw, processing_msg, return_url
//
// Available entry fields, see net_nehmer_buddylist_handler_pending::_pending documentation

$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
$buddy_user =& $data['buddy_user'];
?>

<h2><?php echo $data['topic']->extra . ': ' . $data['l10n']->get('buddy request'); ?></h2>

<p>&(data['processing_msg']);</p>

<p><?php $data['l10n_midcom']->show('username'); ?>: &(buddy_user.username);</p>

<p><a href="&(prefix);"><?php $data['l10n_midcom']->show('back'); ?></a></p>