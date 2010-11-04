<?php
//$data =& $_MIDCOM->get_custom_context_data('request_data');
?>
<div class="area">
    <h2><?php echo sprintf($data['l10n']->get('%s projects'), $data['l10n']->get($data['view'])); ?></h2>
    <?php
        echo sprintf($data['l10n']->get('%d %s projects'), count($data['project_list_results'][$data['view']]), $data['l10n']->get($data['view']));
    ?>
    <table>
        <thead>
            <tr>
                <th><?php echo $data['l10n']->get('project'); ?></th>
                <th><?php echo $data['l10n']->get('manager'); ?></th>
                <th><?php echo $data['l10n']->get('customer'); ?></th>
                <th><?php echo $data['l10n']->get('priority'); ?></th>
                <th><?php echo $data['l10n']->get('start'); ?></th>
                <th><?php echo $data['l10n']->get('end'); ?></th>
                <th><?php echo $data['l10n']->get('status'); ?></th>
            </tr>
        </thead>
        <tbody>