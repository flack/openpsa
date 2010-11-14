<h2><?php $data['l10n']->show('lost password'); ?></h2>

<?php
if ($data['config']->get('lostpassword_email_reset')) {
?>
<p><?php $data['l10n']->show('lost password reset helptext'); ?></p>
<?php
} else {
?>
<p><?php $data['l10n']->show('lost password helptext'); ?></p>
<?php
}
?>

<?php if ($data['processing_msg']) { ?>
<p>&(data['processing_msg']);</p>
<?php } ?>

<?php $data['formmanager']->display_form(); ?>

<p><a href="&(data['profile_url']);"><?php $data['l10n_midcom']->show('back'); ?></a></p>