<?php
$grid = $data['grid'];
$classes = $data['list_type'];
$invoices_url = org_openpsa_core_siteconfig::get_instance()->get_node_full_url('org.openpsa.invoices');
$formatter = $data['l10n']->get_formatter();
if ($data['list_type'] == 'overdue') {
    $classes .= ' bad';
} elseif ($data['list_type'] == 'paid') {
    $classes .= ' good';
} elseif ($data['list_type'] == 'open') {
    $classes .= ' normal';
}

$footer_data = [
    'sum' => $formatter->amount($grid->get_provider()->get_column_total('index_sum'))
];

$grid->set_option('loadonce', true);

if (!array_key_exists('deliverable', $data)) {
    $grid->set_option('caption', $data['list_label']);
}

$grid->set_column('number', $data['l10n']->get('invoice'), 'width: 80, align: "center", fixed: true, classes: "title"', 'string');

if (!($data['customer'] instanceof org_openpsa_contacts_group_dba)) {
    $grid->set_column('customer', $data['l10n']->get('customer'), 'classes: "ui-ellipsis"', 'string');
}
if (!($data['customer'] instanceof org_openpsa_contacts_person_dba)) {
    $grid->set_column('contact', $data['l10n']->get('customer contact'), 'classes: "ui-ellipsis"', 'string');
}

if (array_key_exists('deliverable', $data)) {
    $grid->set_column('item_sum', $data['deliverable']->title, 'width: 80, fixed: true, align: "right"', 'number');
    $footer_data['item_sum'] = $formatter->amount($data['totals']['deliverable']);
}
$grid->set_column('due', $data['l10n']->get('due'), 'width: 80, fixed: true, align: "right", formatter: "date"')
->set_column('sum', $data['l10n']->get('amount'), 'width: 80, fixed: true, align: "right", title: false, classes: "sum"', 'number');

if ($data['list_type'] != 'paid') {
    $grid->set_column('action', $data['l10n']->get('next action'), 'width: 80, align: "center", title: false, sortable: false');
} else {
    $grid->set_column('paid', $data['l10n']->get('paid date'), 'width: 80, align: "right", formatter: "date"');
}
$grid->set_footer_data($footer_data);
?>

<div class="org_openpsa_invoices <?php echo $classes ?> full-width crop-height row-actions">
<?php $grid->render(); ?>
</div>

<script>
midcom_grid_row_actions.init({
    identifier: '<?= $grid->get_identifier(); ?>',
    url: '<?= $invoices_url; ?>invoice/action/',
    actions: ['mark_sent', 'mark_paid'],
    totals_field: 'sum'
});
</script>
