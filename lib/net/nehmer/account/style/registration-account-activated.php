<?php
// Bind the view data, remember the reference assignment.
//
// Available keys: return_url
//$data =& $_MIDCOM->get_custom_context_data('request_data');
?>

<h2><?php $data['l10n']->show('activation successful'); ?></h2>

<?php if ($data['processing_msg']) { ?>
<p style='color: red;'>&(data['processing_msg']);</p>
<?php } ?>

<?php
if (!$data['logged_in'])
{
?>
<p><a href="&(data['return_url']);"><?php $data['l10n']->show('you may now log in to the system.'); ?></a></p>
<?php
}
?>