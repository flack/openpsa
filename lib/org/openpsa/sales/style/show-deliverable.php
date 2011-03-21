<?php
$view =& $data['view_deliverable'];
$status = $data['deliverable']->get_status();

$costType = $view['costType'];
try
{
    $product = org_openpsa_products_product_dba::get_cached($data['deliverable']->product);
    $unit_options = midcom_baseclasses_components_configuration::get('org.openpsa.products', 'config')->get('unit_options');
    $unit = $_MIDCOM->i18n->get_string($unit_options[$product->unit], 'org.openpsa.products');
    if ($data['deliverable']->costType == 'm')
    {
        $costType = sprintf($data['l10n']->get('per %s'), $unit);
    }
}
catch (midcom_error $e)
{
    $product = false;
    $unit = $data['l10n']->get('unit');
}
?>
<div class="org_openpsa_sales_salesproject_deliverable &(status);">
    <div class="sidebar">
        <div class="contacts area">
            <?php
            echo "<h2>" . $data['l10n']->get('customer') . "</h2>\n";
            $customer = $data['salesproject']->get_customer();
            echo "<dl>\n<dt>\n" . $customer->render_link() . "</dl>\n</dt>\n";

            $contacts = $data['salesproject']->contacts;
            foreach ($contacts as $contact_id => $active)
            {
                $person_card = org_openpsa_contactwidget::get($contact_id);
                $person_card->show();
            }
            ?>
        </div>

        <?php if ($product)
        { ?>
        <div class="products area">
            <?php
            echo "<h2>" . $data['l10n']->get('product') . "</h2>\n";
            echo $product->render_link() . "\n";
            ?>
        </div>
        <?php } ?>
    </div>

    <div class="main">
        <div class="tags">&(view['tags']:h);</div>
        <?php
        echo "<h1>" . $data['l10n']->get('single delivery') . ": {$data['deliverable']->title}</h1>\n";
        ?>
        &(view['description']:h);

        <table class="agreement">
            <tbody>
                <tr>
                    <th><?php echo $data['l10n']->get('status'); ?></th>
                    <td><?php echo $data['l10n']->get($status); ?></td>
                </tr>
                <tr>
                    <th><?php echo $data['l10n']->get('estimated delivery'); ?></th>
                    <td>&(view['end']:h);</td>
                </tr>
                <?php
                if ($data['deliverable']->supplier)
                {
                    ?>
                    <tr>
                        <th><?php echo $data['l10n']->get('supplier'); ?></th>
                        <td>&(view['supplier']:h);</td>
                    </tr>
                    <?php
                }
                if($data['deliverable']->notify)
                {
                    ?>
                    <tr>
                        <th><?php echo $data['l10n']->get('notify date'); ?></th>
                        <td><?php echo date('d.m.Y', $data['deliverable']->notify); ?></td>
                    </tr>
                    <?php
                }
                ?>
                <tr>
                    <th colspan="2" class="area"><?php echo $data['l10n']->get('pricing information'); ?></th>
                </tr>
                <tr>
                    <th><?php echo $data['l10n']->get('pricing'); ?></th>
                   <td><?php
                        echo org_openpsa_helpers::format_number($view['pricePerUnit']) . ' ';
                        echo sprintf($data['l10n']->get('per %s'), $unit); ?></td>
                 </tr>
                <tr>
                    <th><?php echo $data['l10n']->get('cost structure'); ?></th>
                    <td><?php echo org_openpsa_helpers::format_number($view['costPerUnit']); ?> &(costType);</td>
                </tr>
                <tr>
                    <th><?php echo $data['l10n']->get('units'); ?></th>
                    <td>&(view['units']:h);<?php
                        if ($data['deliverable']->plannedUnits)
                        {
                            echo ' (' . sprintf($data['l10n']->get('%s planned'), $view['plannedUnits']) . ')';
                        }
                        ?></td>
                </tr>
                <?php
                if ($data['deliverable']->invoiceByActualUnits)
                {
                    ?>
                    <tr>
                        <td colspan="2">
                            <ul>
                                <li><?php echo $data['l10n']->get('invoice by actual units'); ?></li>
                                <?php
                                if ($data['deliverable']->invoiceApprovedOnly)
                                {
                                    echo "<li>" . $data['l10n']->get('invoice approved only') . "</li>\n";
                                }
                                ?>
                            </ul>
                        </td>
                    </tr>
                    <?php
                }
                ?>
                <tr>
                    <th colspan="2" class="area"><?php echo $data['l10n']->get('invoicing information'); ?></th>
                </tr>
                <tr>
                    <th><?php echo $data['l10n']->get('price'); ?></th>
                    <td><?php echo org_openpsa_helpers::format_number($view['price']); ?></td>
                </tr>
                <tr>
                    <th><?php echo $data['l10n']->get('cost'); ?></th>
                    <td><?php echo org_openpsa_helpers::format_number($view['cost']); ?></td>
                </tr>
                <?php
                if ($data['deliverable']->invoiced > 0)
                {
                    ?>
                    <tr>
                        <th><?php echo $data['l10n']->get('invoiced'); ?></th>
                        <td><?php echo org_openpsa_helpers::format_number($data['deliverable']->invoiced); ?></td>
                    </tr>
                    <?php
                }
                ?>
            <tbody>
        </table>
    </div>

    <div class="wide">
        &(view['components']:h);

        <div class="tasks">
            <?php
            if (   $data['projects_url']
                && $data['deliverable']->orgOpenpsaObtype == ORG_OPENPSA_PRODUCTS_PRODUCT_TYPE_SERVICE
                && $data['deliverable']->state >= ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_ORDERED)
            {
                $_MIDCOM->dynamic_load($data['projects_url'] . "task/list/all/agreement/{$data['deliverable']->id}");
                // FIXME: This is a rather ugly hack
                $_MIDCOM->style->enter_context(0);
            }
            ?>
        </div>
        <div class="invoices">
            <?php
            if (   $data['invoices_url']
                && $data['deliverable']->invoiced > 0)
            {
                $_MIDCOM->dynamic_load($data['invoices_url'] . "list/deliverable/{$data['deliverable']->guid}");
                // FIXME: This is a rather ugly hack
                $_MIDCOM->style->enter_context(0);
            }
            ?>
        </div>
    </div>
</div>