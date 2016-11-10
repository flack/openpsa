<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<h1><?php echo $data['topic']->extra; ?></h1>
<?php if (!empty($data['has_subfolders'])) {
    midcom::get()->dynamic_load($prefix . 'subfolders/');
} else {
    ?>
    <p><?php $data['l10n']->show('no images found'); ?></p>
<?php 
} ?>