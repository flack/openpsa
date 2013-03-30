<?php
$view = $data['view_product'];
?>
<h1>&(view['code']:h); &(view['title']:h);</h1>

<?php $data['controller']->display_form(); ?>
<?php
if ($data['config']->get('enable_productlinks'))
{
    $qb_productlinks = org_openpsa_products_product_link_dba::new_query_builder();
    $qb_productlinks->add_constraint('product', '=', $data['object']->id);
    $productlinks = $qb_productlinks->execute();
    if (count($productlinks) > 0)
    {
        echo "\t<ul>\n";
        foreach ($productlinks as $productlink)
        {
            $product_group = new org_openpsa_products_product_group_dba($productlink->productGroup);
            echo "\t\t<li><a href=\"/midcom-permalink-" . $productlink->guid . "/\">" . $product_group->title . "</a></li>\n";
        }
        echo "\t</ul>\n";
    }
}
?>