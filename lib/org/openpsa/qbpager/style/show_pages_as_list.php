<?php
$pages = $data['pages'];
//Skip the header in case we only have one page
$total_links = count($pages);
if ($total_links <= 1)
{
    return;
}

//TODO: "showing results (offset)-(offset+limit)
echo '<div class="org_openpsa_qbpager_pages">';
echo "\n    <ul>\n";
foreach ($pages as $i => $page)
{
    if ($page['class'] == 'next')
    {
        echo "\n<li class=\"separator\"></li>";
        echo "\n<li class=\"page splitter\">...</li>";
    }
    if (   $i > 0
            && $i < $total_links)
    {
        echo "\n<li class=\"separator\"></li>";
    }
    if ($page['href'] === false)
    {
        echo "\n<li class=\"page {$page['class']}\">{$page['label']}</li>";
    }
    else
    {
        echo "\n<li class=\"page {$page['class']}\" onclick=\"window.location='{$page['href']}';\">{$page['label']}</li>";
    }
    if ($page['class'] == 'previous')
    {
        echo "\n<li class=\"page splitter\">...</li>";
        echo "\n<li class=\"separator\"></li>";
    }
}

echo "\n    </ul>\n";
echo "</div>\n";