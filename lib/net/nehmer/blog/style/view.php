<?php
$view = $data['view_article'];

$publish_time = $data['l10n']->get_formatter()->datetime($data['article']->metadata->published, 'full');
$published = sprintf($data['l10n']->get('posted on %s.'), $publish_time);
$permalink = midcom::get()->permalinks->create_permalink($data['article']->guid);
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>

<div class="hentry">
    <h1 class="headline">&(view['title']:h);</h1>

    <p class="published">&(published);</p>
    <p class="excerpt">&(view['abstract']:h);</p>

    <div class="content">
        <?php if (!empty($view['image'])) {
    ?>
            <div style="float: right; padding: 5px;">&(view['image']:h);</div>
        <?php
} ?>

        &(view["content"]:h);
    </div>

    <p class="permalink" style="display: none;"><a href="&(permalink);" rel="bookmark" rev="canonical"><?php $data['l10n_midcom']->show('permalink'); ?></a></p>

    <?php
    $relateds = array_filter(explode('|', $data['article']->extra3));
    if (!empty($relateds)) {
        echo "<h2>{$data['l10n']->get('related stories')}</h2>\n";
        echo "<ul class=\"related\">\n";
        foreach ($relateds as $related) {
            try {
                $article = new midcom_db_article($related);
                echo "<li><a href=\"" . midcom::get()->permalinks->create_permalink($article->guid) . "\">{$article->title}</a></li>\n";
            } catch (midcom_error $e) {
                $e->log();
            }
        }
        echo "</ul>\n";
    }

    if (array_key_exists('comments_url', $data)) {
        midcom::get()->dynamic_load($data['comments_url']);
    }
    ?>
    <p><a href="&(prefix);"><?php $data['l10n_midcom']->show('back'); ?></a></p>
</div>
