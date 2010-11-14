<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<h2><?php echo $data['l10n']->get('hours'); ?></h2>

<table>
    <thead>
        <tr>
            <th><?php echo $data['l10n']->get('date'); ?></th>
            <th><?php echo $data['l10n']->get('hours'); ?></th>
            <th><?php echo $data['l10n']->get('invoiceable'); ?></th>
            <th><?php echo $data['l10n']->get('approved'); ?></th>
            <th><?php echo $data['l10n']->get('invoiced'); ?></th>
            <th><?php echo $data['l10n']->get('reporter'); ?></th>
            <th><?php echo $data['l10n_midcom']->get('description'); ?></th>
        </tr>
    </thead>
    <tbody>