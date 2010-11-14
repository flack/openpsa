<?php
$nap = new midcom_helper_nav();
$node = $nap->get_node($nap->get_current_node());
?>
<div class="area">
    <?php echo "<h2><a href=\"{$node[MIDCOM_NAV_FULLURL]}project/list/{$data['project_list_status']}/\">" . sprintf($data['l10n']->get('%s projects'), $data['l10n']->get($data['project_list_status'])) . "</a></h2>\n"; ?>
    <?php
    echo sprintf($data['l10n']->get('%d %s projects'), count($data['project_list_items']), $data['l10n']->get($data['project_list_status']));
    ?>
</div>