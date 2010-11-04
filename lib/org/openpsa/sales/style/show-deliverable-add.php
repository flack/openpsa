<?php
//$data =& $_MIDCOM->get_custom_context_data('request_data');
$view =& $data['view_product'];

function org_openpsa_sales_salesproject_deliverable_add_product($product, $pieces, &$data)
{
    echo "<li><label><input type=\"checkbox\" name=\"components[{$product->id}][add]\" value=\"1\" /> {$product->code}: {$product->title}</label>\n";
    echo " <label><input type=\"text\" name=\"components[{$product->id}][pieces]\" value=\"{$pieces}\" size=\"3\" /> " . $data['l10n']->get('pcs') . "</label></li>\n";
    $component_qb = org_openpsa_products_product_member_dba::new_query_builder();
    $component_qb->add_constraint('product', '=', $product->id);
    $components = $component_qb->execute();
    if (count($components) > 0)
    {
        echo "<ul>\n";
        foreach ($components as $component)
        {
            if ($component->componentGroup)
            {
                // This component links to a whole group, list all
                $product_qb = org_openpsa_products_product_dba::new_query_builder();
                $product_qb->add_constraint('productGroup', '=', $component->componentGroup);
                $product_qb->add_order('code');
                $product_qb->add_order('title');
                $product_qb->add_constraint('start', '<=', time());
                $product_qb->begin_group('OR');
                    /*
                     * List products that either have no defined end-of-market dates
                     * or are still in market
                     */
                    $product_qb->add_constraint('end', '=', 0);
                    $product_qb->add_constraint('end', '>=', time());
                $product_qb->end_group();
                $products = $product_qb->execute();
                foreach ($products as $component_product)
                {
                    org_openpsa_sales_salesproject_deliverable_add_product($component_product, $component->pieces, $data);
                }
            }
            else
            {
                // Straight link to a product
                $component_product = new org_openpsa_products_product_dba($component->component);
                org_openpsa_sales_salesproject_deliverable_add_product($component_product, $component->pieces, $data);
            }
        }
        echo "</ul>\n";
    }
}
?>
<div class="org_openpsa_sales_salesproject_deliverable_add">
    <h1><?php echo sprintf($data['l10n']->get('add products to %s'), $data['salesproject']->title); ?></h1>
    <form method="post">
        <input type="hidden" name="product" value="<?php echo $data['product']->guid; ?>" />
        <ul>
            <?php
            org_openpsa_sales_salesproject_deliverable_add_product($data['product'], 1, $data);
            ?>
        </ul>
        <div class="form_toolbar">
            <input type="submit" value="<?php echo $data['l10n_midcom']->get('save'); ?>" />
        </div>
    </form>
</div>