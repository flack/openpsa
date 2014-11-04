<?php
$l10n = $data['l10n'];
if (!isset($data['form_suffix']))
{
    $data['form_suffix'] = '';
}
$form_suffix = $data['form_suffix'];
?>
<style type="text/css">
table.link_statistics th.title
{
    text-align: left;
}
</style>
<form method="post">
    <table class="link_statistics" id="org_openpsa_directmarketing_messagelinks&(form_suffix);">
        <thead>
            <tr>
                <th>&nbsp;</th>
                <th><?php echo $l10n->get('link'); ?></th>
                <th>&nbsp;</th>
                <th><?php echo $l10n->get('total clicks'); ?></th>
                <th><?php echo $l10n->get('% of clicks'); ?></th>
                <th><?php echo $l10n->get('unique clickers'); ?></th>
                <th><?php echo $l10n->get('% of recipients'); ?></th>
            </tr>
        </thead>
