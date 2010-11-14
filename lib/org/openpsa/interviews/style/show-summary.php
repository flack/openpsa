<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<div class="main">
    <h1><?php echo sprintf($data['l10n']->get('interview summary for "%s"'), $data['campaign']->title); ?></h1>

    <table>
        <thead>
            <tr>
                <th><?php echo $data['l10n']->get('status'); ?></th>
                <th><?php echo $data['l10n']->get('contacts'); ?></th>
            </tr>
        </thead>

        <tbody>
            <tr>
                <td><?php echo $data['l10n']->get('waiting'); ?></td>
                <td><?php echo count($data['members_waiting']); ?></td>
            </tr>
            <tr>
                <td><?php echo $data['l10n']->get('locked'); ?></td>
                <td><?php echo count($data['members_locked']); ?></td>
            </tr>
            <tr>
                <td><?php echo $data['l10n']->get('suspended'); ?></td>
                <td><?php echo count($data['members_suspended']); ?></td>
            </tr>
            <tr>
                <td><?php echo $data['l10n']->get('interviewed'); ?></td>
                <td><?php echo count($data['members_interviewed']); ?></td>
            </tr>
            <tr>
                <td><?php echo $data['l10n']->get('unsubscribed'); ?></td>
                <td><?php echo count($data['members_unsubscribed']); ?></td>
            </tr>
        </tbody>
    </table>
</div>