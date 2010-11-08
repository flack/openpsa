<h1><?php echo $data['l10n']->get('copy message'); ?></h1>
<p>
    <?php echo $data['l10n']->get('choose the target campaign'); ?>
</p>
<?php
$data['controller']->display_form();
?>