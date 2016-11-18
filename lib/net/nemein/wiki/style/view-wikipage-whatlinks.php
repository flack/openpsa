<?php
$view = $data['wikipage_view'];
?>

<h1><?php printf($data['l10n']->get('pages linking to %s'), $view['title']); ?></h1>

<?php
if (count($data['wikilinks']) > 0) {
    ?>
    <ul>
    <?php
    foreach ($data['wikilinks'] as $link) {
        try {
            $page = new net_nemein_wiki_wikipage($link->frompage);
        } catch (midcom_error $e) {
            $e->log();
            continue;
        }

        $page_link = midcom::get()->permalinks->create_permalink($page->guid); ?>
        <li><a href="&(page_link);">&(page.title);</a></li>
        <?php

    } ?>
    </ul>
    <?php

} else {
    ?>
    <p><?php echo $data['l10n']->get('no links to page'); ?></p>
    <?php
}
?>