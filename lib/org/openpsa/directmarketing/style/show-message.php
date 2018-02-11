<?php
$message_dm = $data['datamanager'];
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<div class="content-with-sidebar">
    <div class="main">
        <?php $message_dm->display_view(); ?>
    </div>
    <aside>
        <div class="area">
            <h2><?php echo $data['l10n']->get("recipients"); ?></h2>
            <dl>
                <dt><?php echo "<a href=\"{$prefix}campaign/{$data['campaign']->guid}/\">{$data['campaign']->title}</a>"; ?></dt>
                <!--<dd>
                    TODO: List recipients
                </dd>-->
            </dl>
        </div>
        <div class="area">
            <?php midcom_show_style('send-status'); ?>
        </div>
    </aside>
</div>