<?php
$import_vcards = $data['router']->generate('import_vcards', ['guid' => $data['campaign']->guid]);
$import_csv = $data['router']->generate('import_csv_file_select', ['guid' => $data['campaign']->guid]);
$import_mails = $data['router']->generate('import_simpleemails', ['guid' => $data['campaign']->guid]);
?>
<div class="main">
    <h1><?php printf($data['l10n']->get('import subscribers to "%s"'), $data['campaign']->title); ?></h1>

    <ul>
        <li><a href="&(import_vcards);"><?php echo $data['l10n']->get('import vcards'); ?></a></li>
        <li><a href="&(import_csv);"><?php echo $data['l10n']->get('import csv'); ?></a></li>
        <li><a href="&(import_mails);"><?php echo $data['l10n']->get('import email addresses'); ?></a></li>
    </ul>
</div>