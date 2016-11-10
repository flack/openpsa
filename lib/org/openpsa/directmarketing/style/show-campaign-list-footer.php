<?php
$nap = new midcom_helper_nav();
$node = $nap->get_node($nap->get_current_node());
?>
    </dl>
<?php
if ($data['campaigns_all']) {
    // TODO: Maybe this should be done via AJAX
    ?>
    <form method="post" action="<?php echo midcom_connection::get_url('uri'); ?>">
        <label for="org_openpsa_campaign_selector">
            <?php echo $data['l10n']->get('add to campaign'); ?>
            <select name="add_to_campaign" id="org_openpsa_campaign_selector">
                <?php
                foreach ($data['campaigns_all'] as $campaign) {
                    echo "<option value=\"{$campaign->guid}\">{$campaign->title}</option>\n";
                } ?>
            </select>
        </label>
        <input type="submit" value="<?php echo $data['l10n']->get('add'); ?>" />
    </form>
    <?php

}
?>
</div>