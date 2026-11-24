<?php
$invoice = $data['object'];
$editable = $data['items_editable'];
$edit_options = $editable ? ', editable: true' : '';

$grid = $data['grid'];
$grid->set_option('caption', $data['l10n']->get('invoice items'));

$grid->set_column('position', $data['l10n']->get('position'), 'hidden: true');
$grid->set_column('deliverable', midcom::get()->i18n->get_string('agreement', 'org.openpsa.projects'), 'fixed: true, align: "center", width: 40, sortable: false');
$grid->set_column('task', midcom::get()->i18n->get_string('task', 'org.openpsa.projects'), 'fixed: true, align: "center", width: 40, sortable: false');
$grid->set_column('description', $data['l10n_midcom']->get('description'), 'classes: "multiline", edittype: "textarea", sortable: false' . $edit_options);
$grid->set_column('price', $data['l10n']->get('price'), 'align: "right", width: 40, formatter: "number", sortable: false' . $edit_options);
$grid->set_column('quantity', $data['l10n']->get('quantity'), 'align: "right", width: 30, formatter: "number", sortable: false' . $edit_options);
$grid->set_column('sum', $data['l10n']->get('sum'), 'align: "right", width: 60, formatter: "number", sortable: false');
if ($editable) {
    $grid->set_option('editurl', $data['router']->generate('invoice_item_edit', ['guid' => $invoice->guid]));
    $grid->set_column('actions', '', 'width: 65, fixed: true, sortable: false, title: false');
}

$formatter = $data['l10n']->get_formatter();
$sum = array_sum(array_column($data['entries'], 'sum'));
$vat_sum = $sum / 100 * $invoice->vat;
$to_lines = fn(string ...$lines) => '<div class="total-line">' . implode('</div><div class="total-line">', $lines) . '</div>';

// start with empty lines, so that the border spans the entire table
$footer = array_fill_keys(['deliverable', 'task', 'price', 'quantity', 'actions'], $to_lines('&nbsp;', '&nbsp;', '&nbsp;'));
$footer['description'] = $to_lines(
    $data['l10n']->get('sum excluding vat'),
    $data['l10n']->get('vat sum') . ' (' . $data['object_view']['vat'] . ')',
    $data['l10n']->get('sum including vat')
);
$footer['sum'] = $to_lines($formatter->amount($sum), $formatter->amount($vat_sum), $formatter->amount($sum + $vat_sum));
$grid->set_footer_data($footer, formatted: false);
?>

<div class="invoice-items full-width">
    <?php $grid->render($data['entries']); ?>
</div>

<?php if ($editable) { ?>
<script type="text/javascript">
    init_invoice_items("<?= $grid->get_identifier() ?>", <?= json_encode((float) $invoice->vat) ?>, {
        position_url: <?= json_encode($data['router']->generate('invoice_item_position')) ?>,
        button_label: <?= json_encode($data['l10n']->get('add new invoice item')) ?>
    });
</script>
<?php } ?>
