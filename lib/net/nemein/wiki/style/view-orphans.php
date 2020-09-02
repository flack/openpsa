<h1><?php echo $data['view_title']; ?></h1>

<?php
if (!empty($data['orphans'])) {  ?>
    <ul>
    <?php
    foreach ($data['orphans'] as $orphan) {
        $orphan_link = midcom::get()->permalinks->create_permalink($orphan->guid); ?>
        <li><a href="&(orphan_link);">&(orphan.title);</a></li>
        <?php

    } ?>
    </ul>
    <?php
} else { ?>
    <p><?php echo $data['l10n']->get('no orphans'); ?></p>
    <?php
}
?>