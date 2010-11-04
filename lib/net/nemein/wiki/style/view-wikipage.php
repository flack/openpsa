<?php
// Bind the view data, remember the reference assignment:
//$data =& $_MIDCOM->get_custom_context_data('request_data');
$view = $data['wikipage_view'];
$nap = new midcom_helper_nav();
$node = $nap->get_node($nap->get_current_node());
?>
<div class="net_nemein_wiki_wikipage">
    <h1>&(view['title']:h);</h1>
    
    <?php 
    if ($view['content'] != '')
    {
        if ($data['autogenerate_toc'])
        {
            // This uses the custom Midgard formatter registered in midcom/helper/misc.php
            ?>
            &(view["content"]:xtoc);
            <?php
        }
        else
        {
            // This displays content as-is
            ?>
            &(view["content"]:h);
            <?php
        }
    } 
    else
    {
        echo "<p class=\"stub\">" . $data['l10n']->get('this page is stub') . "</p>";
    }
    
    // List possible wiki pages tagged with name of this page
    $qb = net_nemein_tag_link_dba::new_query_builder();
    $qb->add_constraint('tag.tag', '=', $data['wikipage']->title);
    $qb->add_constraint('fromClass', '=', 'net_nemein_wiki_wikipage');
    $qb->add_order('context', 'ASC');
    // TODO: $qb->add_order('tag.tag', 'ASC');
    $links = $qb->execute();
    if (count($links) > 0)
    {
        echo "<dl class=\"tagged\">\n";
        $contexts_shown = array();
        foreach ($links as $link)
        {
            $context = $link->context;
            if (!$context)
            {
                $context = $_MIDCOM->i18n->get_string('tagged', 'net.nemein.tag');
            }
            
            if (!array_key_exists($context, $contexts_shown))
            {
                echo "    <dt>" . sprintf($data['l10n']->get('%s for %s'), ucfirst($context), $data['wikipage']->title) . "</dt>\n";
                $contexts_shown[$context] = true;
            }
            $linked_page = new net_nemein_wiki_wikipage($link->fromGuid);
            echo "        <dd><a href=\"{$node[MIDCOM_NAV_FULLURL]}{$linked_page->name}/\">{$linked_page->title}</a></dd>\n";
        }
        echo "</dl>\n";
    }

    // List tags used in this wiki page    
    $tags_by_context = net_nemein_tag_handler::get_object_tags_by_contexts($data['wikipage']);
    if (count($tags_by_context) > 0)
    {
        echo "<dl class=\"tags\">\n";
        foreach ($tags_by_context as $context => $tags)
        {
            if (!$context)
            {
                $context = $_MIDCOM->i18n->get_string('tagged', 'net.nemein.tag');
            }
            echo "    <dt>{$context}</dt>\n";
            foreach ($tags as $tag => $url)
            {
                $link = $data['wikipage']->replace_wikiwords(array('', $tag, ''));
                echo "        <dd class=\"tag\">{$link}</dd>\n";
            }
        }
        echo "</dl>\n";
    }
    ?>
</div>