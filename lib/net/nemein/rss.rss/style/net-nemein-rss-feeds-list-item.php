<?php
//$data =& $_MIDCOM->get_custom_context_data('request_data');
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);

echo "<li><a href=\"{$data['feed']->url}\"><img src=\"" . MIDCOM_STATIC_URL . "/net.nemein.rss/feed-icon-14x14.png\" alt=\"{$data['feed']->url}\" title=\"{$data['feed']->url}\" /></a>";
if ($data['feed']->can_do('midgard:update'))
{
    echo "<a href=\"{$prefix}feeds/edit/{$data['feed']->guid}/\">{$data['feed']->title}</a>\n";
}
else
{
    echo "{$data['feed']->title}\n";
}
echo "    <ul class=\"details\">\n";
echo "        <li></li>\n";

switch ($data['topic']->component)
{
    case 'net.nehmer.blog':
        $qb = midcom_db_article::new_query_builder();
        $qb->add_constraint('topic', '=', $data['topic']->id);
        $qb->add_constraint('extra1', 'LIKE', "%|{$data['feed_category']}|%");
        $data['feed_items'] = $qb->count_unchecked();
        echo "        <li><a href=\"{$prefix}category/{$data['feed_category']}/\">" . sprintf($_MIDCOM->i18n->get_string('%s items', 'net.nemein.rss'), $data['feed_items']) . "</a></li>\n";
        break;
}

if ($data['feed']->latestupdate)
{
    echo "        <li>" . sprintf($_MIDCOM->i18n->get_string('latest item from %s', 'net.nemein.rss'), strftime('%x %X', $data['feed']->latestupdate)) . "</li>\n";
}
if ($data['feed']->latestfetch)
{
    echo "        <li>" . sprintf($_MIDCOM->i18n->get_string('latest fetch %s', 'net.nemein.rss'), strftime('%x %X', $data['feed']->latestfetch)) . "</li>\n";
}
echo "    </ul>\n";

$data['feed_toolbar'] = new midcom_helper_toolbar();
if ($data['feed']->can_do('midgard:update'))
{
    $data['feed_toolbar']->add_item
    (
        array
        (
            MIDCOM_TOOLBAR_URL => "feeds/edit/{$data['feed']->guid}/",
            MIDCOM_TOOLBAR_LABEL => $data['l10n_midcom']->get('edit'),
            MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
        )
    );
}

if ($data['topic']->can_do('midgard:create'))
{
    $data['feed_toolbar']->add_item
    (
        array
        (
            MIDCOM_TOOLBAR_URL => "feeds/fetch/{$data['feed']->guid}/",
            MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('refresh feed', 'net.nemein.rss'),
            MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_refresh.png',
        )
    );
}

if ($data['feed']->can_do('midgard:delete'))
{
    $data['feed_toolbar']->add_item
    (
        array
        (
            MIDCOM_TOOLBAR_URL => "feeds/delete/{$data['feed']->guid}/",
            MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('delete feed', 'net.nemein.rss'),
            MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
        )
    );
}
echo $data['feed_toolbar']->render();
echo "</li>\n";
?>