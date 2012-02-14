<?php
// Available request data: comments, objectguid.

if (isset($data['qb_pager'])
    && is_object($data['qb_pager'])
    && method_exists($data['qb_pager'], 'show_pages'))
{
    echo "<div class=\"net_nehmer_comments_pager\">\n";
    $data['qb_pager']->show_pages();
    echo "</div>\n";
}
?>