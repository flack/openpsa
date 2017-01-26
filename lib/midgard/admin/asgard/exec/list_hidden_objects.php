<?php
midcom::get()->auth->require_valid_user();
error_reporting(E_ALL);
ini_set('max_execution_time', 0);

// TODO: Make reflected

// Now just Q'n'D topic/article support

function render_breadcrumb(&$crumbs)
{
    while (current($crumbs) !== false) {
        $crumb = current($crumbs);
        if (next($crumbs) === false) {
            // last item
            echo "<a href='{$crumb['napobject'][MIDCOM_NAV_FULLURL]}'>{$crumb[MIDCOM_NAV_NAME]}</a>";
        } else {
            echo "{$crumb[MIDCOM_NAV_NAME]} &gt; ";
        }
    }
}

$site_root_id = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ROOTTOPICID);
$host_prefix = midcom::get()->get_host_prefix();
$nap = new midcom_helper_nav();

$qb = midcom_db_topic::new_query_builder();
$qb->add_constraint('up', 'INTREE', $site_root_id);
$qb->begin_group('OR');
    $qb->add_constraint('metadata.hidden', '=', 1);
    $qb->add_constraint('metadata.navnoentry', '=', 1);
$qb->end_group();
$qb->add_order('name');
$topics = $qb->execute();
echo "<h2>Topics</h2>\n";
foreach ($topics as $topic) {
    $node = $nap->get_node($topic->id);
    $crumbs = $nap->get_breadcrumb_data($node[MIDCOM_NAV_ID]);
    render_breadcrumb($crumbs);
    echo " (<a href='{$host_prefix}__mfa/asgard/object/view/{$topic->guid}'>in Asgard</a>)<br/>\n";
}

echo "<h2>Articles</h2>\n";
$qb = midcom_db_article::new_query_builder();
$qb->add_constraint('topic', 'INTREE', $site_root_id);
$qb->begin_group('OR');
    $qb->add_constraint('metadata.hidden', '=', 1);
    $qb->add_constraint('metadata.navnoentry', '=', 1);
$qb->end_group();
$qb->add_order('name');
$articles = $qb->execute();
foreach ($articles as $article) {
    $node = $nap->get_node($article->topic);
    /* FIXME correct way to figure out leaf id ?
    $leaf = $nap->get_leaf($article->id);
    $crumbs = $nap->get_breadcrumb_data($leaf[MIDCOM_NAV_ID]);
    render_breadcrumb($crumbs);
    */
    $node_crumbs = $nap->get_breadcrumb_data($node[MIDCOM_NAV_ID]);
    foreach ($node_crumbs as $crumb) {
        echo "{$crumb[MIDCOM_NAV_NAME]} &gt; ";
    }
    echo "<a href='{$crumb['napobject'][MIDCOM_NAV_FULLURL]}{$article->name}/'>{$article->title}</a>";
    echo " (<a href='{$host_prefix}__mfa/asgard/object/view/{$article->guid}'>in Asgard</a>)<br/>\n";
}
