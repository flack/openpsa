<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<h1><?php echo $data['l10n']->get('copy message'); ?></h1>
<p>
    <?php echo $data['l10n']->get('message was copied to the following campaigns'); ?>
</p>
<dl>
<?php
foreach ($data['targets'] as $message)
{
    $campaign = new org_openpsa_directmarketing_campaign_dba($message->campaign);
    echo "<dt><a href=\"{$prefix}campaign/{$campaign->guid}/\">{$campaign->title}</a></dt>\n";
    echo "    <dd><a href=\"{$prefix}message/{$message->guid}/\">{$message->title}</a></dd>\n";
}
?>
</dl>
