<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<div class="main">
    <h1><?php printf($data['l10n']->get('import subscribers to "%s"'), $data['campaign']->title); ?></h1>

    <ul>
        <li><a href="&(prefix);campaign/import/vcards/<?php echo $data['campaign']->guid; ?>/"><?php echo $data['l10n']->get('import vcards'); ?></a></li>
        <li><a href="&(prefix);campaign/import/csv/<?php echo $data['campaign']->guid; ?>/"><?php echo $data['l10n']->get('import csv'); ?></a></li>
        <li><a href="&(prefix);campaign/import/simpleemails/<?php echo $data['campaign']->guid; ?>/"><?php echo $data['l10n']->get('import email addresses'); ?></a></li>
    </ul>
</div>