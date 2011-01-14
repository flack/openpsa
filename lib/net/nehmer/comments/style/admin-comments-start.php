<?php
// Available request data: comments, objectguid.
//$data =& $_MIDCOM->get_custom_context_data('request_data');
?>
<h2><?php $data['l10n']->show($data['status_to_show']); ?>:</h2>
<?php
if (isset($data['qb_pager'])
    && is_object($data['qb_pager'])
    && method_exists($data['qb_pager'], 'show_pages'))
{
    echo "<div class=\"net_nehmer_comments_pager\">\n";
    $data['qb_pager']->show_pages();
    echo "</div>\n";
}
?>