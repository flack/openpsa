<h1><?php echo $data['topic']->extra; ?></h1>
<?php if (!empty($data['has_subfolders'])) {
    midcom::get()->dynamic_load($data['router']->generate('index_subfolders'));
} else { ?>
    <p><?php $data['l10n']->show('no images found'); ?></p>
<?php } ?>