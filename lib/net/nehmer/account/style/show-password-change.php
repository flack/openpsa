<h2><?php $data['l10n']->show('change password'); ?></h2>

<?php if ($data['processing_msg']) { ?>
<p>&(data['processing_msg']);</p>
<?php } ?>

<?php $data['formmanager']->display_form(); ?>

<p><a href="&(data['profile_url']);"><?php $data['l10n_midcom']->show('back'); ?></a></p>