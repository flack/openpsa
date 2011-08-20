<?php
$view = $data['view_deliverable'];
$status = $data['deliverable']->get_status();
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);

$per_unit = $data['l10n']->get('per unit');
try
{
    $product = org_openpsa_products_product_dba::get_cached($data['deliverable']->product);
    $unit_options = midcom_baseclasses_components_configuration::get('org.openpsa.products', 'config')->get('unit_options');
    if (array_key_exists($product->unit, $unit_options))
    {
        $unit = $_MIDCOM->i18n->get_string($unit_options[$product->unit], 'org.openpsa.products');
        $per_unit = sprintf($data['l10n']->get('per %s'), $unit);
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
            if ($customer = $data['salesproject']->get_customer())
            {
                echo "<h2>" . $data['l10n']->get('customer') . "</h2>\n";
                echo "<dl>\n<dt>\n" . $customer->render_link() . "</dl>\n</dt>\n";
            }
            $contacts = $data['salesproject']->contacts;
            foreach ($contacts as $contact_id => $active)
            {
                $person_card = org_openpsa_widgets_contact::get($contact_id);
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

        <div class="at area">
        <?php
        $at_entries = $data['deliverable']->get_at_entries();
        if (count($at_entries) > 0)
        {
            echo "<h2>" . $_MIDCOM->i18n->get_string('next billing run', 'midcom.services.at') . "</h2>\n";
            echo "<table>\n";
            echo "    <thead>\n";
            echo "        <tr>\n";
            echo "            <th>" . $_MIDCOM->i18n->get_string('time', 'midcom.services.at') . "</th>\n";
            echo "            <th>" . $_MIDCOM->i18n->get_string('status', 'midcom.services.at') . "</th>\n";
            echo "        </tr>\n";
            echo "    </thead>\n";
            echo "    <tbody>\n";

            foreach ($at_entries as $entry)
            {
                echo "        <tr>\n";
                echo "            <td>" . strftime('%x %X', $entry->start) . "</td>\n";

                echo "            <td>";
                switch ($entry->status)
                {
                    case MIDCOM_SERVICES_AT_STATUS_SCHEDULED:
                        echo $_MIDCOM->i18n->get_string('scheduled', 'midcom.services.at');
                        break;
                    case MIDCOM_SERVICES_AT_STATUS_RUNNING:
                        echo $_MIDCOM->i18n->get_string('running', 'midcom.services.at');
                        break;
                    case MIDCOM_SERVICES_AT_STATUS_FAILED:
                        echo $_MIDCOM->i18n->get_string('failed', 'midcom.services.at');
                        break;
                }
                echo "</td>\n";

                echo "        </tr>\n";
            }
            echo "    </tbody>\n";
            echo "</table>\n";
        }
        ?>
        </div>
    </div>

    <div class="main">
        <div class="tags">&(view['tags']:h);</div>
        <?php
        echo "<h1>" . $data['l10n']->get('subscription') . ": {$data['deliverable']->title}</h1>\n";
        ?>
        &(view['description']:h);
        <table class="agreement">
            <tbody>
                <tr>
                    <th><?php echo $data['l10n']->get('status'); ?></th>
                    <td><?php echo $data['l10n']->get($status); ?></td>
                </tr>
                <tr>
                    <th><?php echo $data['l10n']->get('subscription begins'); ?></th>
                    <td>&(view['start']:h);</td>
                </tr>
                <?php
                if (!$data['deliverable']->continuous)
                {
                    ?>
                    <tr>
                        <th><?php echo $data['l10n']->get('subscription ends'); ?></th>
                        <td>&(view['end']:h);</td>
                    </tr>
                    <?php
                }
                else
                {
                    ?>
                    <tr>
                        <td colspan="2">
                            <ul>
                                <li><?php echo $data['l10n']->get('continuous subscription'); ?></li>
                            </ul>
                        </td>
                    </tr>
                    <?php
                }
                if ($data['deliverable']->notify)
                {
                    ?>
                    <tr>
                        <th><?php echo $data['l10n']->get('notify date'); ?></th>
                        <td><?php echo date('d.m.Y', $data['deliverable']->notify); ?></td>
                    </tr>
                    <?php
                }
                if ($data['deliverable']->supplier)
                {
                    ?>
                    <tr>
                        <th><?php echo $data['l10n']->get('supplier'); ?></th>
                        <td>&(view['supplier']:h);</td>
                    </tr>
                    <?php
                }
                ?>
                <tr>
                    <th><?php echo $data['l10n']->get('invoicing period'); ?></th>
                    <td>&(view['unit']:h);</td>
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
                <tr>
                    <th colspan="2" class="area"><?php echo $data['l10n']->get('pricing'); ?></th>
                </tr>
                <tr>
                    <td colspan="2" class="area">
                    <table class="list">
                      <thead>
                        <tr>
                          <th>&nbsp;</th>
                          <th><?php echo $per_unit ?></th>
                          <th><?php echo $data['l10n']->get('plan'); ?></th>
                          <th><?php echo $data['l10n']->get('is'); ?></th>
                          <th><?php echo $_MIDCOM->i18n->get_string('sum', 'org.openpsa.invoices'); ?></th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td class="title"><?php echo $data['l10n']->get('price'); ?></td>
                          <td class="numeric"><?php echo org_openpsa_helpers::format_number($view['pricePerUnit']); ?></td>
                          <td class="numeric"><?php echo $view['plannedUnits']; ?></td>
                          <td class="numeric"><?php echo $view['units']; ?></td>
                          <td class="numeric"><?php echo org_openpsa_helpers::format_number($view['price']); ?></td>
                        </tr>
                        <tr>
                          <td class="title"><?php echo $data['l10n']->get('cost'); ?></td>
                          <?php
                          if ($data['deliverable']->costType == 'm')
                          { ?>
                              <td class="numeric"><?php echo org_openpsa_helpers::format_number($view['costPerUnit']); ?></td>
                              <td class="numeric"><?php echo $view['plannedUnits']; ?></td>
                              <td class="numeric"><?php echo $view['units']; ?></td>
                              <td class="numeric"><?php echo org_openpsa_helpers::format_number($view['cost']); ?></td>
                          <?php }
                          else
                          { ?>
                              <td class="numeric"><?php echo $view['costPerUnit']; ?> %</td>
                              <td class="numeric">&nbsp;</td>
                              <td class="numeric">&nbsp;</td>
                              <td class="numeric"><?php echo org_openpsa_helpers::format_number($view['cost']); ?></td>
                          <?php } ?>
                        </tr>
                      </tbody>
                    </table>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="wide">
    <?php
    $tabs = array();
    if (   $data['invoices_url']
        && $data['deliverable']->invoiced > 0)
    {
        $tabs[] = array
        (
            'url' => $data['invoices_url'] . "list/deliverable/{$data['deliverable']->guid}/",
            'title' => $_MIDCOM->i18n->get_string('invoices', 'org.openpsa.invoices'),
        );
    }

    if (   $data['projects_url']
        && $data['deliverable']->state >= org_openpsa_sales_salesproject_deliverable_dba::STATUS_ORDERED)
    {
        if (   $product
            && $product->orgOpenpsaObtype == ORG_OPENPSA_PRODUCTS_PRODUCT_TYPE_SERVICE)
        {
            $tabs[] = array
            (
                'url' => $data['projects_url'] . "task/list/all/agreement/{$data['deliverable']->id}/",
                'title' => $_MIDCOM->i18n->get_string('tasks', 'org.openpsa.projects'),
            );
        }
    }
    org_openpsa_widgets_ui::render_tabs($data['deliverable']->guid, $tabs);
    ?>
    </div>
</div>