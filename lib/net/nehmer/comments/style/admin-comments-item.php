<?php
// Available request data: comments, objectguid, comment, display_datamanager
$view = $data['display_datamanager']->get_content_html();
$created = $data['comment']->metadata->published;

$published = sprintf(
    $data['l10n']->get('published by %s on %s.'),
    $view['author'],
    strftime('%x %X', $created)
);

$rating = '';
if ($data['comment']->rating > 0)
{
    $rating = ', ' . sprintf('rated %s', $data['comment']->rating);
}
$object_link = midcom::get('permalinks')->create_permalink($data['comment']->objectguid).'#net_nehmer_comments_'.$data['comment']->objectguid;

?>

<div style="clear:right;" class="net_nehmer_comments_comment">
    <div style="float:right;" class="net_nehmer_comments_comment_toolbar">
        <a href="&(object_link);"><?php echo $data['l10n']->get('go to parent object'); ?></a>
        <?php echo $data['comment_toolbar']->render(); ?>
    </div>
    <h3 class="headline">&(view['title']);&(rating);</h3>
    <div class="published">&(published);</div>

    <div class="content">&(view['content']:h);</div>
</div>