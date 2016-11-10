<?php
$pages = $data['pages'];
//Skip the header in case we only have one page
if (count($pages) > 1) {
    //TODO: "showing results (offset)-(offset+limit)

    echo '<div class="org_openpsa_qbpager_pages">';

    foreach ($pages as $page) {
        if ($page['href'] === false) {
            echo "\n<span class=\"" . $page['class'] . "_page\">{$page['label']}</span>";
        } else {
            $rel = '';
            if ($page['rel'] !== false) {
                $rel = ' rel="' . $page['rel'] . '"';
            }
            echo "\n<a class=\"{$page['class']}_page\" href=\"" . $page['href'] . "\"{$rel}>" . $page['label'] . "</a>";
        }
    }

    echo "\n</div>\n";
}
