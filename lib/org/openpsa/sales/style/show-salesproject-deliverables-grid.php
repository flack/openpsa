<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
$rows = [];

foreach ($data['deliverables'] as $entry) {
    $deliverable = $entry['object'];

    $rows[] = [
        'id' => $deliverable->id,
        'guid' => $deliverable->guid,
        'url' => $data['router']->generate('deliverable_view', ['guid' => $deliverable->guid]),
        'title' => $deliverable->title,
        'pricePerUnit' => $deliverable->pricePerUnit,
        'plannedUnits' => $deliverable->plannedUnits,
        'index_price' => $deliverable->get_state() == 'invoiced' ? $deliverable->invoiced : $deliverable->price,
        'price' => $deliverable->get_state() == 'invoiced' ? $deliverable->invoiced : $deliverable->price,
        'created' => date('Y-m-d H:i:s', $deliverable->metadata->created),
        'subscription' => $deliverable->orgOpenpsaObtype == org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION,
        'workflow' => $entry['actions'],
        'actions' => ''
    ];
}

$provider = new midcom\grid\provider($rows, 'local');
$grid = $provider->get_grid('deliverables_' . $data['state']);
$grid_id = $grid->get_identifier();

$classes = $data['state'];
if ($data['state'] == 'declined') {
    $classes .= ' bad';
} elseif (in_array($data['state'], ['started', 'invoiced'])) {
    $classes .= ' good';
} elseif (in_array($data['state'], ['delivered', 'ordered'])) {
    $classes .= ' normal';
}

$footer_data = [
    'price' => $provider->get_column_total('price')
];
$grid->set_option('loadonce', true);
$grid->set_option('caption', $data['l10n']->get($data['state']));

$grid->set_column('created', $data['l10n']->get('created'), 'width: 80, fixed: true, align: "center", formatter: "date"');
$grid->set_column('title', $data['l10n']->get('title'), 'width: 120, classes: "multiline", editable: true, edittype: "textarea", formatter: viewlink, unformat: extractlabel');
if ($data['state'] == 'proposed') {
    $grid->set_column('pricePerUnit', $data['l10n']->get('price per unit'), 'width: 70, fixed: true, formatter: "number", align: "right", title: false, classes: "sum", editable: true');
    $grid->set_column('plannedUnits', $data['l10n']->get('units'), 'width: 50, fixed: true, formatter: "number", align: "right", title: false, classes: "sum", editable: true');
    $grid->set_column('price', $data['l10n']->get('price'), 'width: 80, fixed: true, formatter: "number", align: "right", title: false, classes: "sum"', 'number');
    $grid->set_option('editurl', $data['router']->generate('salesproject_itemedit'));
} else {
    $grid->set_column('price', $data['l10n']->get('price'), 'width: 80, fixed: true, formatter: "number", align: "right", title: false, classes: "sum", editable: true', 'number');
}
if (!in_array($data['state'], ['declined', 'invoiced'], true)) {
    $grid->set_column('workflow', $data['l10n']->get('actions'), 'width: 150, fixed: true, sortable: false, align: "center"');
}
$grid->set_column('subscription', $data['l10n']->get('subscription'), 'width: 30, align: "center", fixed: true, formatter: "checkbox"');
$grid->set_column('url', '', 'hidden: true');

$grid->set_footer_data($footer_data);
?>

<script>
	function viewlink(cellval, options, rowdata) {
		let url = rowdata.url;
		if (!url) {
			url = jQuery("#<?= $grid_id ?>").jqGrid('getRowData', options.rowId).url;
		}
		return '<a href="' + url + '">' + cellval + '</a>';
	}
	function extractlabel(cellval) {
		return cellval;
	}
</script>
<div class="org_openpsa_sales <?php echo $classes ?> full-width row-actions">
<?php $grid->render(); ?>
</div>
<script>
midcom_grid_row_actions.init({
    identifier: '<?= $grid_id; ?>',
    url: '&(prefix);salesproject/action/',
    actions: ['decline', 'order', 'deliver', 'invoice', 'run_cycle'],
    totals_field: 'sum'
});
</script>

<?php if ($data['state'] == 'proposed') { ?>
<script>
    $("#<?= $grid_id ?>")
        .on("keyup", '.sum input', function() {
            var rowid = $(this).closest('tr').attr('id'),
                grid = jQuery("#<?= $grid_id ?>"),
                price = grid.jqGrid('getCell', rowid, 3),
                quantity = grid.jqGrid('getCell', rowid, 4);
            grid.jqGrid('setRowData', rowid, {price: parseFloat(price) * parseFloat(quantity)});
            update_totals();
        });

    function update_totals() {
        let grid = jQuery("#<?= $grid_id ?>"),
            total = grid.jqGrid('getRowData').reduce(function(accumulator, value) {
                return accumulator + (parseFloat(value.price) || 0);
            }, 0);

        grid.jqGrid("footerData", "set", {price: total});
    }

    midcom_grid_editable.enable_inline("<?= $grid_id ?>", {
        afterdeletefunc: update_totals,
        enable_create: false
    });
</script>
<?php } ?>
