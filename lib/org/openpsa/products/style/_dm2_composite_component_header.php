<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<h2><?php echo $data['l10n']->get('product components'); ?></h2>

<table>
    <thead>
        <tr>
            <th><?php echo $data['l10n']->get('product'); ?></th>
            <th><?php echo $data['l10n']->get('pieces'); ?></th>
            <th><?php echo $data['l10n_midcom']->get('description'); ?></th>
        </tr>
    </thead>
    <tbody>