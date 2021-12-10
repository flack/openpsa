<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
$rows = [];

foreach ($data['deliverables'] as $entry) {
    $deliverable = $entry['object'];

    $rows[] = [
        'id' => $deliverable->id,
        'title' => '<a href="' . $data['router']->generate('deliverable_view', ['guid' => $deliverable->guid]) . '">' . $deliverable->title . '</a>',
        'index_title' => $deliverable->title,
        'index_price' => $deliverable->get_state() == 'invoiced' ? $deliverable->invoiced : $deliverable->price,
        'price' => $deliverable->get_state() == 'invoiced' ? $deliverable->invoiced : $deliverable->price,
        'created' => date('Y-m-d H:i:s', $deliverable->metadata->created),
        'subscription' => $deliverable->orgOpenpsaObtype == org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION,
        'action' => $entry['actions']
    ];
}

$provider = new midcom\grid\provider($rows, 'local');
$grid = $provider->get_grid('deliverables_' . $data['state']);

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
$grid->set_column('title', $data['l10n']->get('title'), 'width: 120, classes: "multiline"', 'string');
$grid->set_column('price', $data['l10n']->get('price'), 'width: 80, fixed: true, formatter: "number", align: "right", title: false, classes: "sum"', 'number');
if (!in_array($data['state'], ['declined', 'invoiced'], true)) {
    $grid->set_column('action', $data['l10n']->get('actions'), 'width: 150, fixed: true, sortable: false, align: "center"');
}
$grid->set_column('subscription', $data['l10n']->get('subscription'), 'width: 30, align: "center", fixed: true, formatter: "checkbox"');

$grid->set_footer_data($footer_data);
?>

<div class="org_openpsa_sales <?php echo $classes ?> full-width row-actions">
<?php $grid->render(); ?>
</div>
<script>
midcom_grid_row_actions.init({
    identifier: '<?= $grid->get_identifier(); ?>',
    url: '&(prefix);salesproject/action/',
    actions: ['decline', 'order', 'deliver', 'invoice', 'run_cycle'],
    totals_field: 'sum'
});
</script>