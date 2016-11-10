<h2><?php $data['l10n']->show('lost password'); ?></h2>

<p><?php $data['l10n']->show('lost password helptext'); ?></p>

<?php if ($data['processing_msg']) {
    ?>
    <p>&(data['processing_msg']);</p>
<?php 
} ?>

<?php $data['controller']->display_form(); ?>