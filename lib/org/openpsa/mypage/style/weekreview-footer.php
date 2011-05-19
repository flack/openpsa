</table>
<p>
<?php
echo $data['l10n']->get('totals') . ': ' . sprintf($data['l10n']->get('%02.2f hours (%02.2f invoiceable) reported'), $data['week_hours_total'], $data['week_hours_invoiceable']);
?>
</p>