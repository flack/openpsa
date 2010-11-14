<?php
// Available request keys: NONE

$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>

<h2><?php echo $data['topic']->extra; ?></h2>

<p><?php $data['l10n']->show('the buddies have been deleted.');?></p>

<p><a href="&(prefix);"><?php $data['l10n']->show('back to the buddy list.');?></a></p>