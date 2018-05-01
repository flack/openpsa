<?php
$deliverable = $data['deliverable'];
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
$formatter = $data['l10n']->get_formatter();
$price = $formatter->number($data['deliverable_object']->price);
$ppu = $formatter->number($data['deliverable_object']->pricePerUnit);
?>
<li class="deliverable collapsed" id="deliverable_<?php echo $data['deliverable_object']->guid; ?>">
    <span class="icon"><i class="fa fa-chevron-down"></i><i class="fa fa-chevron-up"></i></span>
    <div class="tags">&(deliverable['tags']:h);</div>
    <?php
    echo "<h3><a href=\"{$prefix}deliverable/{$data['deliverable_object']->guid}/\">{$data['deliverable_object']->title}</a></h3>\n";
    ?>
    <div class="information" id="information_<?php echo $data['deliverable_object']->guid; ?>">
    <div class="description">
        &(deliverable['description']:h);
    </div>
    <table class="details">
        <tbody>
            <tr>
                <th><?php echo $data['l10n']->get('supplier'); ?></th>
                <td>&(deliverable['supplier']:h);</td>
            </tr>
            <tr>
                <th><?php echo $data['l10n']->get('estimated delivery'); ?></th>
                <td>&(deliverable['end']:h);</td>
            </tr>
            <tr>
                <th><?php echo $data['l10n']->get('price per unit'); ?></th>
                <td>&(ppu); / &(deliverable['unit']:h);</td>
            </tr>
            <tr>
                <th><?php echo $data['l10n']->get('cost per unit'); ?></th>
                <td>&(deliverable['costPerUnit']:h); / &(deliverable['costType']:h);</td>
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

    </div>

    <div class="toolbar">
        <form method="post" action="&(prefix);deliverable/process/<?php echo $data['deliverable_object']->guid; ?>/">
            <p>
            <?php
            echo $data['deliverable_toolbar']['label'];
            foreach ($data['deliverable_toolbar']['buttons'] as $name => $label) {
                echo "<input type=\"submit\" class=\"$name\" name=\"$name\" value=\"$label\" />\n";
            }
            ?>
            </p>
        </form>
    </div>
</li>