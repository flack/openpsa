<h1><?php echo sprintf($data['l10n']->get('translation status for language %s'), $data['l10n']->get_language_name($data['language'])); ?></h1>

<table class="midcom_admin_babel_languages">
    <thead>
        <tr class="header">
            <th><?php echo $data['l10n']->get('component'); ?></th>
            <th><?php echo $data['l10n']->get('translated strings'); ?></th>
            <th><?php echo $data['l10n']->get('strings total'); ?></th>
            <th><?php echo $data['l10n']->get('percentage'); ?></th>
        </tr>
    </thead>
    <tbody>