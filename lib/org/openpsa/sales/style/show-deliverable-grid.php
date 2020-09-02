<div class="org_openpsa_sales full-width crop-height">

<?php
$state_options = [
    org_openpsa_sales_salesproject_deliverable_dba::STATE_NEW => $data['l10n']->get('proposed'),
    org_openpsa_sales_salesproject_deliverable_dba::STATE_DECLINED => $data['l10n']->get('declined'),
    org_openpsa_sales_salesproject_deliverable_dba::STATE_ORDERED => $data['l10n']->get('ordered'),
    org_openpsa_sales_salesproject_deliverable_dba::STATE_STARTED => $data['l10n']->get('started'),
    org_openpsa_sales_salesproject_deliverable_dba::STATE_DELIVERED => $data['l10n']->get('delivered'),
    org_openpsa_sales_salesproject_deliverable_dba::STATE_INVOICED => $data['l10n']->get('invoiced')
];
$grid = $data['grid'];
$grid->set_column('title', $data['l10n']->get('title'), 'classes: "ui-ellipsis"', 'string');
$grid->set_column('salesproject', $data['l10n']->get('salesproject'), 'width: 100, classes: "ui-ellipsis"', 'string');
$grid->set_select_column('state', $data['l10n']->get('state'), 'width: 60', $state_options);
if ($data['product']->delivery == org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION) {
    $grid->set_column('unit', $data['l10n']->get('invoicing period'), 'width: 60');
}
$grid->set_column('pricePerUnit', $data['l10n']->get('price per unit'), 'width: 50, template: "number"');
$grid->set_column('units', $data['l10n']->get('units'), 'width: 40, template: "number"');
$grid->set_column('fixedPrice', midcom::get()->i18n->get_string('fixed price', 'org.openpsa.reports'), 'width: 40, align: "center", formatter: "checkbox"');
$grid->set_column('invoiced', $data['l10n']->get('invoiced'), 'width: 50, template: "number", summaryType: "sum"');

$grid->set_option('loadonce', true)
    ->set_option('grouping', true)
    ->set_option('groupingView', [
        'groupField' => ['state'],
        'groupColumnShow' => [false],
        'groupText' => ['<strong>{0}</strong> ({1})'],
        'groupOrder' => ['asc'],
        'groupSummary' => [true],
        'showSummaryOnHide' => true
    ]);

$grid->render();
?>
</div>
