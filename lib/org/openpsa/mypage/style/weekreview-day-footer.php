    <tr class="subtotal">
        <td colspan="2">&nbsp;</td>
        <td class="hours" colspan="2">
            <?php
            printf($data['l10n']->get('%02.2f hours (%02.2f invoiceable) reported'), $data['day_hours_total'], $data['day_hours_invoiceable']);
            ?>
        </td>
    </tr>
