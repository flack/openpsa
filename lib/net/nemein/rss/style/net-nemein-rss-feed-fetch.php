<?php
if (isset($data['feed'])) { ?>
    <h1><?php printf($data['l10n']->get('fetch feed %s'), $data['feed']->title); ?></h1>
    <?php
} else { ?>
    <h1><?php echo $data['l10n']->get('fetch feeds'); ?></h1>
    <?php
}

if (empty($data['items'])) {
    echo '<p>' . $data['l10n']->get('no items found in feed') . "</p>\n";
    if (!empty($data['error'])) {
        echo "<p class=\"error\">{$data['error']}</p>\n";
    }
} else {
    echo "<table>\n";
    echo "    <thead>\n";
    echo "        <tr>\n";
    echo "            <th>" . $data['l10n_midcom']->get('date') . "</th>\n";
    echo "            <th>" . $data['l10n']->get('remote item') . "</th>\n";
    echo "            <th>" . $data['l10n']->get('local item') . "</th>\n";
    echo "        </tr>\n";
    echo "    </thead>\n";
    echo "    <tbody>\n";
    $formatter = $data['l10n']->get_formatter();
    foreach ($data['items'] as $item) {
        echo "<tr>\n";
        if ($date = (int) $item->get_date('U')) {
            echo "    <td>" . $formatter->datetime($date) . "</td>\n";
        } else {
            echo "    <td>" . $data['l10n']->get('n/a') . "</td>\n";
        }
        echo '    <td><a href="' . $item->get_link() . '">' . $item->get_title() . "</a></td>\n";

        if (!$item->get_local_guid()) {
            echo "    <td>" . $data['l10n']->get('not in local database') . "</td>\n";
        } elseif (midcom_core_context::get()->get_key(MIDCOM_CONTEXT_COMPONENT) === 'net.nehmer.blog') {
            $local_article = new midcom_db_article($item->get_local_guid());
            $local_link = midcom::get()->permalinks->create_permalink($item->get_local_guid());
            echo "    <td><a href=\"{$local_link}\">{$local_article->title}</a></td>\n";
        }

        echo "</tr>\n";
    }

    echo "    </tbody>\n";
    echo "</table>\n";
}
?>