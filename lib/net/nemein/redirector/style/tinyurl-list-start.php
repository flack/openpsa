<h1><?php echo $data['l10n']->get('list of tinyurls'); ?></h1>
<p>
    <?php echo $data['l10n']->get('this page links to'); ?> <a href="<?php echo $data['url']; ?>"><?php echo $data['url']; ?></a>.
    <a href="<?= $data['router']->generate('config') ?>" <?php echo $data['workflow']->render_attributes(); ?>><?php echo $data['l10n_midcom']->get('edit'); ?>?</a>
</p>
<table>
    <thead>
        <tr>
            <th><?php echo $data['l10n']->get($data['l10n_midcom']->get('tinyurl')); ?></th>
            <th><?php echo $data['l10n']->get($data['l10n_midcom']->get('title')); ?></th>
            <th><?php echo $data['l10n']->get($data['l10n_midcom']->get('description')); ?></th>
            <th><?php echo $data['l10n']->get($data['l10n_midcom']->get('location')); ?></th>
        </tr>
    </thead>
    <tbody>
