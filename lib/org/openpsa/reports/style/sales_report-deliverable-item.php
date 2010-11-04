<?php
//$data =& $_MIDCOM->get_custom_context_data('request_data');
$customer = $data['customer'];
$salesproject = $data['salesproject'];
$deliverable = $data['deliverable'];
?>
    <tr&(data['row_class']:h);>
        <td class="invoices">
            <ul>
                &(data['invoice_string']:h);
            </ul>
        </td>

        <?php
        if ($data['handler_id'] != 'deliverable_report')
        {
            $owner_card = org_openpsa_contactwidget::get($salesproject->owner);
            ?>
            <td><?php echo $owner_card->show_inline(); ?></td>
            <?php
        }
        ?>
        <td>&(customer.official);</td>
        <td>&(salesproject.title);</td>
        <td>&(deliverable.title);</td>
        <td class="numeric"><?php echo sprintf("%01.2f", $data['price']); ?></td>
        <td class="numeric"><?php echo sprintf("%01.2f", $data['cost']); ?></td>
        <td class="numeric"><?php echo sprintf("%01.2f", $data['profit']); ?></td>
        <td>&(data['calculation_basis']);</td>
    </tr>