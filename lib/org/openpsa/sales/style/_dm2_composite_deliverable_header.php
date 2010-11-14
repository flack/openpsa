<?php
$view_data =& $_MIDCOM->get_custom_context_data('midcom_helper_datamanager2_widget_composite');
if ($view_data['item_total'] > 0)
{
    ?>
    <table>
        <thead>
            <tr>
                <th><?php echo $data['l10n']->get('product'); ?></th>
                <th><?php echo $data['l10n']->get('supplier'); ?></th>
                <th><?php echo $data['l10n']->get('price'); ?></th>
                <th><?php echo $data['l10n']->get('cost'); ?></th>
                <th><?php echo $data['l10n']->get('units'); ?></th>
                <th><?php echo $data['l10n']->get('total'); ?></th>
                <th><?php echo $data['l10n']->get('total cost'); ?></th>
                <th><?php echo $data['l10n']->get('purchase invoice'); ?></th>
            </tr>
        </thead>
        <tbody>
    <?php
    }
?>