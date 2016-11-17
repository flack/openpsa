<?php
$view = $data['datamanager']->get_content_html();
$view_counter = $data['article_counter'];
$article_count = $data['article_count'];
$class_str = midcom::get()->metadata->get_object_classes($data['article']);
if ($view_counter == 0) {
    $class_str = ' first';
} elseif ($view_counter == ($article_count - 1)) {
    $class_str = ' last';
}
$published = $data['l10n']->get_formatter()->datetime($data['article']->metadata->published);
$published = sprintf($data['l10n']->get('posted on %s.'), "<abbr title=\"" . strftime('%Y-%m-%dT%H:%M:%S%z', $data['article']->metadata->published) . "\">" . $published . "</abbr>");

if (array_key_exists('ajax_comments_enable', $data)) {
    $published .= ' <a href="#switch_comments" onClick="showAjaxComments(this, \''.$data['article']->guid.'\'); return false;">'
                    .sprintf($data['l10n']->get('%s comments'), net_nehmer_comments_comment::count_by_objectguid($data['article']->guid))
                    ."</a>.";
} elseif (array_key_exists('comments_enable', $data)) {
    $published .= " <a href=\"{$data['local_view_url']}#net_nehmer_comments_{$data['article']->guid}\">"
                    .sprintf($data['l10n']->get('%s comments'), net_nehmer_comments_comment::count_by_objectguid($data['article']->guid))
                    ."</a>.";
}
?>

<div class="hentry counter_&(view_counter); &(class_str);" style="clear: left;">
    <h2 class="entry-title"><a href="&(data['view_url']);" rel="bookmark">&(view['title']:h);</a></h2>
    <p class="published">
        &(published:h);
            <?php
            if ($data['linked']) {
                echo $data['l10n']->get('to') ." <a href=\"{$data['node'][MIDCOM_NAV_ABSOLUTEURL]}\">{$data['node'][MIDCOM_NAV_NAME]}</a>\n";
            }
            ?>
    </p>
    <?php if (!empty($view['image'])) {
                ?>
        <div style="float: left; padding: 5px;">&(view['image']:h);</div>
    <?php

            }

    if (isset($view['abstract'])) {
        ?>
        <p class="entry-summary">&(view['abstract']:h);</p>
        <?php

    }

    if ($data['index_fulltext']) {
        ?>
        <div class="entry-content">&(view['content']:h);</div>
        <?php

    }

    if (array_key_exists('ajax_comments_enable', $data)) {
        echo '<div class="ajax_comments_container" style="display: none;"></div>';
    }
    ?>
</div>
