<?php
$view = $data['wikipage_view'];
?>

<h1><?php echo sprintf($data['l10n']->get('pages linking to %s'), $view['title']); ?></h1>

<?php
if (count($data['wikilinks']) > 0)
{
    ?>
    <ul>
    <?php
    foreach ($data['wikilinks'] as $link)
    {
        $page = new net_nemein_wiki_wikipage($link->frompage);
        $page_link = $_MIDCOM->permalinks->create_permalink($page->guid);
        ?>
        <li><a href="&(page_link);">&(page.title);</a></li>
        <?php
    }
    ?>
    </ul>
    <?php
}
else
{
    ?>
    <p><?php echo $data['l10n']->get('no links to page'); ?></p>
    <?php
}
?>