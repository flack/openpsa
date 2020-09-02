<?php
$view = $data['display_datamanager']->get_content_html();
$created = $data['comment']->metadata->published;
$formatter = $data['l10n']->get_formatter();
$published = sprintf(
    $data['l10n']->get('published by %s on %s.'),
    $view['author'],
    $formatter->datetime($created)
);

$rating = '';
if ($data['comment']->rating > 0) {
    $rating = ', ' . sprintf($data['l10n']->get('rated %s'), $data['comment']->rating);
}
?>

<div class="net_nehmer_comments_comment" id="net_nehmer_comments_<?php echo $data['comment']->guid; ?>">
    <h3 class="headline">&(view['title']);&(rating);</h3>
    <div class="published">&(published);</div>

    <div class="content">&(view['content']:h);</div>
    <div class="net_nehmer_comments_comment_toolbar">
        <?php echo $data['comment_toolbar']->render(); ?>
    </div>
</div>