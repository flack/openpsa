<?php
$item = new FeedItem();
$item->descriptionHtmlSyndicated = true;
$authors = explode('|', substr($data['article']->metadata->authors, 1, -1));
if ($authors)
{
    $author_user = midcom::get('auth')->get_user($authors[0]);
    if ($author_user)
    {
        $author = $author_user->get_storage();

        if (empty($author->email))
        {
            $author->email = "webmaster@{$_SERVER['SERVER_NAME']}";
        }

        $item->author = trim("{$author->email} ({$author->name})");
    }
}

$item->title = $data['article']->title;
$arg = $data['article']->name ? $data['article']->name : $data['article']->guid;

if (   $data['config']->get('link_to_external_url')
    && !empty($data['article']->url))
{
    $item->link = $data['article']->url;
}
else
{
    if ($data['config']->get('view_in_url'))
    {
        $item->link = $_MIDCOM->get_host_name() . $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . "view/{$arg}/";
    }
    else
    {
        $item->link = $_MIDCOM->get_host_name() . $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . "{$arg}/";
    }
}

$item->guid = midcom::get('permalinks')->create_permalink($data['article']->guid);
$item->date = (int) $data['article']->metadata->published;
$item->description = '';

if ($data['article']->abstract != '')
{
    $item->description .= '<div class="abstract">' .  $data['datamanager']->types['abstract']->convert_to_html() . '</div>';
}

if (   array_key_exists('image', $data['datamanager']->types)
    && $data['config']->get('rss_use_image'))
{
    $item->description .= "\n<div class=\"image\">" . $data['datamanager']->types['image']->convert_to_html() .'</div>';
}

if ($data['config']->get('rss_use_content'))
{
    $item->description .= "\n" . $data['datamanager']->types['content']->convert_to_html();
}

// Replace links
$item->description = preg_replace(',<(a|link|img|script|form|input)([^>]+)(href|src|action)="/([^>"\s]+)",ie', '"<\1\2\3=\"' . $_MIDCOM->get_host_name() . '/\4\""', $item->description);

// TODO: Figure out the RSS multi-category support for real
$categories = explode('|', $data['article']->extra1);
if (count($categories) > 1)
{
    $item->category = $categories[1];
}

if ($GLOBALS['midcom_config']['positioning_enable'])
{
    // Attach coordinates to the item if available
    $object_position = new org_routamc_positioning_object($data['article']);
    $coordinates = $object_position->get_coordinates();
    if (!is_null($coordinates))
    {
        $item->lat = $coordinates['latitude'];
        $item->long = $coordinates['longitude'];
    }
}

$data['feedcreator']->addItem($item);
?>