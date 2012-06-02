<?php
$i18n = midcom::get('i18n');
$grid = $data['grid'];
$footer_data = array
(
    'customer' => $data['l10n']->get('totals'),
    'sum' => $grid->get_provider()->get_column_total('sum')
);

$grid->set_option('loadonce', true);

$grid->set_column('time', $data['l10n_midcom']->get('date'), 'width: 80, fixed: true, align: "center", formatter: "date"')
->set_column('customer', $data['l10n']->get('customer'), 'width: 100, classes: "ui-ellipsis"', 'string')
->set_column('customerContact', $data['l10n']->get('customer contact'), 'width: 100, classes: "ui-ellipsis"', 'string')
->set_column('deliverable', $i18n->get_l10n('org.openpsa.sales')->get('deliverable'), 'width: 100, classes: "ui-ellipsis"')
->set_column('type', $i18n->get_l10n('midgard.admin.asgard')->get('type'), 'width: 80, classes: "ui-ellipsis"')
->set_column('sum', $data['l10n']->get('amount'), 'width: 80, fixed: true, align: "right", title: false, classes: "sum", sorttype: "number", formatter: "number"');

$grid->set_footer_data($footer_data);
?>

<div class="org_openpsa_invoices scheduled full-width crop-height">
<?php $grid->render(); ?>
</div>
