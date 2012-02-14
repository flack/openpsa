<?php
midcom::get('auth')->require_admin_user();

$qb = midcom_db_topic::new_query_builder();
$qb->add_constraint('component', '=', 'net.nemein.rss');
$nodes = $qb->execute();

foreach ($nodes as $node)
{
    $node->component = 'net.nehmer.blog';
    $node->parameter('net.nehmer.blog', 'rss_subscription_enable', 1);
    $node->update();

    // TODO: Move articles to subscriptions
}
?>