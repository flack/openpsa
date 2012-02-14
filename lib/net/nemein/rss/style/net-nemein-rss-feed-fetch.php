<?php
if (isset($data['feed']))
{
    ?>
    <h1><?php echo sprintf(midcom::get('i18n')->get_string('fetch feed %s', 'net.nemein.rss'), $data['feed']->title); ?></h1>
    <?php
}
else
{
    ?>
    <h1><?php echo midcom::get('i18n')->get_string('fetch feeds', 'net.nemein.rss'); ?></h1>
    <?php
}

if (count($data['items']) == 0)
{
    echo '<p>' . midcom::get('i18n')->get_string('no items found in feed', 'net.nemein.rss') . "</p>\n";
    echo "<p class=\"error\">{$GLOBALS['MAGPIE_ERROR']}</p>\n";
}
else
{
    echo "<table>\n";
    echo "    <thead>\n";
    echo "        <tr>\n";
    echo "            <th>" . midcom::get('i18n')->get_string('date', 'midcom') . "</th>\n";
    echo "            <th>" . midcom::get('i18n')->get_string('remote item', 'net.nemein.rss') . "</th>\n";
    echo "            <th>" . midcom::get('i18n')->get_string('local item', 'net.nemein.rss') . "</th>\n";
    echo "        </tr>\n";
    echo "    </thead>\n";
    echo "    <tbody>\n";
    foreach ($data['items'] as $item)
    {
        echo "<tr>\n";
        if (!isset($item['date_timestamp']))
        {
            $date = 0;
        }
        else
        {
            $date = $item['date_timestamp'];
        }
        if ($date == 0)
        {
            echo "    <td>" . midcom::get('i18n')->get_string('n/a', 'net.nemein.rss') . "</td>\n";
        }
        else
        {
            echo "    <td>" . strftime('%x %X', $date) . "</td>\n";
        }
        echo "    <td><a href=\"{$item['link']}\">{$item['title']}</a></td>\n";

        if (!$item['local_guid'])
        {
            echo "    <td>" . midcom::get('i18n')->get_string('not in local database', 'net.nemein.rss') . "</td>\n";
        }
        else
        {
            switch ($_MIDCOM->get_context_data(MIDCOM_CONTEXT_COMPONENT))
            {
                case 'net.nehmer.blog':
                    $local_article = new midcom_db_article($item['local_guid']);
                    $local_link = midcom::get('permalinks')->create_permalink($item['local_guid']);
                    echo "    <td><a href=\"{$local_link}\">{$local_article->title}</a></td>\n";
                    break;

                case 'net.nemein.calendar':
                    $local_event = new net_nemein_calendar_event($item['local_guid']);
                    $local_link = midcom::get('permalinks')->create_permalink($item['local_guid']);
                    echo "    <td><a href=\"{$local_link}\">{$local_event->title}</a></td>\n";
                    break;
            }
        }

        echo "</tr>\n";
    }
    echo "    </tbody>\n";
    echo "</table>\n";
}
?>