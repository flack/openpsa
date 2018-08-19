<?php
$sales_l10n = midcom::get('i18n')->get_l10n('org.openpsa.sales');
$invoices_l10n = midcom::get('i18n')->get_l10n('org.openpsa.invoices');
$grid = $data['grid'];
$grid_id = $grid->get_identifier();
$footer_data = ['invoice' => $data['l10n']->get('totals')];
$grid->set_option('loadonce', true)
->set_option('grouping', true)
->set_option('groupingView', [
    'groupField' => ['salesproject'],
    'groupColumnShow' => [false],
    'groupText' => ['<strong>{0}</strong> ({1})'],
    'groupOrder' => ['asc'],
    'groupSummary' => [true],
    'showSummaryOnHide' => true
]);

$grid->set_column('invoice', $invoices_l10n->get('invoice number'), 'align: "center", fixed: true', 'integer');
if ($data['handler_id'] != 'deliverable_report') {
    $grid->set_column('owner', $sales_l10n->get('owner'), '', 'string');
}
$grid->set_column('customer', $sales_l10n->get('customer'));
$grid->set_column('salesproject', $sales_l10n->get('salesproject'));
$grid->set_column('product', $sales_l10n->get('product'));
$grid->set_column('item', $invoices_l10n->get('invoice items'));
$grid->set_column('amount', $invoices_l10n->get('sum'), 'width: 70, template: "number", summaryType: "sum", fixed: true');

$grid->set_footer_data($footer_data);
?>
<div class="grid-controls">
<?php
echo ' ' . midcom::get()->i18n->get_string('group by', 'org.openpsa.core') . ': ';
echo '<select id="chgrouping_' . $grid_id . '">';
if ($data['handler_id'] != 'deliverable_report') {
    echo '<option value="owner">' . $sales_l10n->get('owner') . "</option>\n";
}

echo '<option value="customer">' . $sales_l10n->get('customer') . "</option>\n";
echo '<option value="salesproject">' . $sales_l10n->get('salesproject') . "</option>\n";
echo '<option value="product">' . $sales_l10n->get('product') . "</option>\n";
echo '<option value="clear">' . midcom::get()->i18n->get_string('no grouping', 'org.openpsa.core') . "</option>\n";
echo '</select>';
?>
</div>

<div class="org_openpsa_reports full-width fill-height">

<?php $grid->render(); ?>

</div>

<script type="text/javascript">
midcom_grid_helper.bind_grouping_switch('&(grid_id);');

midcom_grid_footer.set_field('&(grid_id);', 'amount', 'sum');
</script>
