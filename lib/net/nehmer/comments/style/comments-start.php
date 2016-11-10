<h2><?php $data['l10n']->show('comments'); ?>:</h2>
<?php
if (isset($data['qb_pager'])) {
    echo "<div class=\"net_nehmer_comments_pager\">\n";
    $data['qb_pager']->show_pages();
    echo "</div>\n";
}
?>