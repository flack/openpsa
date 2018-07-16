<?php
$message_dm = $data['datamanager'];
$link = $data['router']->generate('view_campaign', ['guid' => $data['campaign']->guid]);
?>
<div class="content-with-sidebar">
    <div class="main">
        <?php $message_dm->display_view(); ?>
    </div>
    <aside>
        <div class="area">
            <h2><?php echo $data['l10n']->get("recipients"); ?></h2>
            <dl>
                <dt><?php echo "<a href=\"{$link}\">{$data['campaign']->title}</a>"; ?></dt>
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