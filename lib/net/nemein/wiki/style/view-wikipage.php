<?php
$view = $data['wikipage_view'];
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<div class="net_nemein_wiki_wikipage">
    <h1>&(view['title']:h);</h1>

    <?php
    if ($view['content'] != '') {
        echo $view['content'];
    } else {
        echo "<p class=\"stub\">" . $data['l10n']->get('this page is stub') . "</p>";
    }

    // List possible wiki pages tagged with name of this page
    $tagged_pages = net_nemein_tag_handler::get_objects_with_tags([$data['wikipage']->title], [net_nemein_wiki_wikipage::class]);
    if (count($tagged_pages) > 0) {
        usort($tagged_pages, [net_nemein_wiki_handler_view::class, 'sort_by_title']);
        echo "<dl class=\"tagged\">\n";
        echo "  <dt>" . sprintf($data['l10n']->get('%s for %s'), midcom::get()->i18n->get_string('tagged', 'net.nemein.tag'), $data['wikipage']->title) . "</dt>\n";
        foreach ($tagged_pages as $page) {
            echo "    <dd><a href=\"{$prefix}{$page->name}/\">{$page->title}</a></dd>\n";
        }
        echo "</dl>\n";
    }

    // List tags used in this wiki page
    $tags_by_context = net_nemein_tag_handler::get_object_tags_by_contexts($data['wikipage']);
    if (count($tags_by_context) > 0) {
        $parser = new net_nemein_wiki_parser($data['wikipage']);
        echo "<dl class=\"tags\">\n";
        foreach ($tags_by_context as $context => $tags) {
            if (!$context) {
                $context = midcom::get()->i18n->get_string('tagged', 'net.nemein.tag');
            }
            echo "    <dt>{$context}</dt>\n";
            foreach ($tags as $tag => $url) {
                $link = $parser->render_link($tag);
                echo "        <dd class=\"tag\">{$link}</dd>\n";
            }
        }
        echo "</dl>\n";
    }
    ?>
</div>