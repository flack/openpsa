<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
$unit_options = midcom_baseclasses_components_configuration::get('org.openpsa.products', 'config')->get('unit_options');
?>
<div class="deliverables">
    <h2><?php echo $data['l10n']->get('deliverables'); ?></h2>
    <?php
    if ($data['salesproject']->can_do('midgard:create')) {
        ?>
        <form method="post" action="&(prefix);deliverable/add/<?php echo $data['salesproject']->guid; ?>/" target="datamanager-dialog">
            <label><?php echo $data['l10n']->get('add offer'); ?></label>
            <select name="product" id="org_openpsa_sales_salesproject_deliverable_add" data-placeholder="<?php echo $data['l10n']->get('select product'); ?>">
                <option value=""></option>
                <?php
                    foreach ($data['products'] as $product_id => $product) {
                        $desc = '';
                        try {
                            $group = org_openpsa_products_product_group_dba::get_cached($product['productGroup']);
                            $desc .= $group->title . ', ';
                        } catch (midcom_error $e) {
                            $e->log();
                        }
                        if ($product['delivery'] == org_openpsa_products_product_dba::DELIVERY_SINGLE) {
                            $desc .= $data['l10n']->get('single delivery');
                        } else {
                            $desc .= $data['l10n']->get('subscription');
                        }
                        $desc .= ', ' . $data['l10n']->get_formatter()->number($product['price']);
                        if (array_key_exists($product['unit'], $unit_options)) {
                            $unit = midcom::get()->i18n->get_string($unit_options[$product['unit']], 'org.openpsa.products');
                            $desc .= ' ' . sprintf($data['l10n']->get('per %s'), $unit);
                        }

                        echo "<option value=\"{$product_id}\" data-description=\"" . $desc . "\">{$product['code']}: {$product['title']}</option>\n";
                    } ?>
            </select>
        </form>
        <?php

    }
    ?>
    <ol class="deliverable_list">