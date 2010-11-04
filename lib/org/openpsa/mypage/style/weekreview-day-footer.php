    <tr class="subtotal">
        <td colspan="2">&nbsp;</td>
        <td class="hours" colspan="2">
            <?php
            echo sprintf($data['l10n']->get('%d hours (%d invoiceable) reported'), $data['day_hours_total'], $data['day_hours_invoiceable']);
            ?>
        </td>
    </tr>
