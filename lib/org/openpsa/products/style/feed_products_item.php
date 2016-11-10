<?php
$product = $data['product'];
$view_product = $data['view_product'];

$author = new midcom_db_person($product->metadata->revisor);
$item = new FeedItem();
$item->title = $view_product['title'];
$item->link = $data['view_product_url'];
$item->guid = midcom::get()->permalinks->create_permalink($product->guid);
$item->date = $product->metadata->published;
$item->author = $author->name;

// TODO: Add more info to the description
$item->description = $view_product['description'];

if ($product->productGroup)
{
    $group = new org_openpsa_products_product_group_dba($product->productGroup);
    $item->category = $group->title;
}

$data['rss_creator']->addItem($item);