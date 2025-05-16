<?php
$action = $data['router']->generate('deliverable_add', ['guid' => $data['salesproject']->guid]);
?>
<div class="deliverables">
    <h2><?php echo $data['l10n']->get('deliverables'); ?></h2>
    <?php
    if ($data['salesproject']->can_do('midgard:create')) { ?>
        <form method="post" action="&(action);" target="datamanager-dialog" data-dialog-cancel-label="<?php echo $data['l10n_midcom']->get('cancel'); ?>">
            <label><?php echo $data['l10n']->get('add item'); ?></label>
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
                        $desc .= ', ' . $data['l10n']->get_formatter()->amount($product['price']);
                        if ($unit = org_openpsa_sales_viewer::get_unit_option($product['unit'])) {
                            $desc .= ' ' . sprintf($data['l10n']->get('per %s'), $unit);
                        }

                        echo "<option value=\"{$product_id}\" data-description=\"" . $desc . "\">{$product['code']}: {$product['title']}</option>\n";
                    } ?>
            </select>
        </form>
    <?php } ?>
