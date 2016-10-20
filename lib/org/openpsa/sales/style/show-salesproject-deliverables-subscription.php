<?php
$deliverable = $data['deliverable'];
$price = $data['l10n']->get_formatter()->number($data['deliverable_object']->price);
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<li class="deliverable subscription collapsed" id="deliverable_<?php echo $data['deliverable_object']->guid; ?>">
    <span class="icon">
    </span>
    <div class="tags">&(deliverable['tags']:h);</div>
    <?php
    echo "<h3><a href=\"{$prefix}deliverable/{$data['deliverable_object']->guid}/\">{$data['deliverable_object']->title}</a></h3>\n";
    ?>
    <div class="information" id="information_<?php echo $data['deliverable_object']->guid; ?>">
    <table class="details">
        <tbody>
            <tr>
                <th><?php echo $data['l10n']->get('supplier'); ?></th>
                <td>&(deliverable['supplier']:h);</td>
            </tr>
            <tr>
                <th><?php echo $data['l10n']->get('subscription starts'); ?></th>
                <td>&(deliverable['start']:h);</td>
            </tr>
            <?php if (!$data['deliverable_object']->continuous)
            { ?>
            <tr>
                <th><?php echo $data['l10n']->get('subscription ends'); ?></th>
                <td>&(deliverable['end']:h);</td>
            </tr>
            <?php } ?>
            <tr>
                <th><?php echo $data['l10n']->get('continuous subscription'); ?></th>
                <td>&(deliverable['continuous']:h);</td>
            </tr>
            <tr>
                <th><?php echo $data['l10n']->get('price per unit'); ?></th>
                <td>&(deliverable['pricePerUnit']:h); / &(deliverable['unit']:h);</td>
            </tr>
            <tr>
                <th><?php echo $data['l10n']->get('cost per unit'); ?></th>
                <td>&(deliverable['costPerUnit']:h); &(deliverable['costType']:h);</td>
            </tr>
            <tr>
                <th><?php echo $data['l10n']->get('units'); ?></th>
                <td>&(deliverable['units']:h); / &(deliverable['plannedUnits']:h);</td>
            </tr>
            <tr>
                <th><?php echo $data['l10n']->get('total'); ?></th>
                <td>&(price:h);</td>
            </tr>
            <tr>
                <th><?php echo $data['l10n']->get('invoice by actual units'); ?></th>
                <td>&(deliverable['invoiceByActualUnits']:h);</td>
            </tr>
            <tr>
                <th><?php echo $data['l10n']->get('invoice approved only'); ?></th>
                <td>&(deliverable['invoiceApprovedOnly']:h);</td>
            </tr>
            <tr>
                <th><?php echo $data['l10n']->get('total cost'); ?></th>
                <td>&(deliverable['cost']:h);</td>
            </tr>
        </tbody>
    </table>

    <div class="description">
        &(deliverable['description']:h);
    </div>

    <div class="tasks">
        <?php
        if (   $data['projects_url']
            && $data['product']
            && $data['product']->orgOpenpsaObtype == org_openpsa_products_product_dba::TYPE_SERVICE
            && $data['deliverable_object']->state >= org_openpsa_sales_salesproject_deliverable_dba::STATE_ORDERED)
        {
            midcom::get()->dynamic_load($data['projects_url'] . "task/list/all/agreement/{$data['deliverable_object']->id}/");
        }
        ?>
    </div>
    <div class="invoices">
        <?php
        if ($data['invoices_url']
            && (   $data['deliverable_object']->state == org_openpsa_sales_salesproject_deliverable_dba::STATE_STARTED
                || $data['deliverable_object']->state == org_openpsa_sales_salesproject_deliverable_dba::STATE_INVOICED))
        {
            midcom::get()->dynamic_load($data['invoices_url'] . "list/deliverable/{$data['deliverable_object']->guid}/");
        }
        ?>
    </div>
    </div>
    <div class="toolbar">
        <form method="post" action="&(prefix);deliverable/process/<?php echo $data['deliverable_object']->guid; ?>/">
            <p>
            <?php
            echo $data['deliverable_toolbar']['label'];
            foreach ($data['deliverable_toolbar']['buttons'] as $name => $label)
            {
                echo " <input type=\"submit\" class=\"$name\" name=\"$name\" value=\"$label\" />\n";
            }
            ?>
            </p>
        </form>
    </div>
</li>