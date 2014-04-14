<?php
$grid = $data['grid'];
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<div class="org_openpsa_sales full-width crop-height">

<?php
$grid->set_column('title', $data['l10n']->get('title'), 'classes: "ui-ellipsis"', 'string');
$grid->set_column('salesproject', $data['l10n']->get('salesproject'), 'width: 100, classes: "ui-ellipsis"', 'string');
$grid->set_column('state', $data['l10n']->get('state'), 'width: 60', 'number');
if ($data['product']->delivery == org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION)
{
    $grid->set_column('unit', $data['l10n']->get('invoicing period'), 'width: 60');
}
$grid->set_column('type', midcom::get('i18n')->get_string('type', 'midgard.admin.asgard'), 'width: 100');
$grid->set_column('pricePerUnit', $data['l10n']->get('price per unit'), 'width: 50, classes: "numeric", sorttype: "number", formatter: "number"');
$grid->set_column('units', $data['l10n']->get('units'), 'width: 40, classes: "numeric", sorttype: "number", formatter: "number"');
$grid->set_column('invoiced', $data['l10n']->get('invoiced'), 'width: 50, classes: "numeric", sorttype: "number", formatter: "number"');

$grid->set_option('loadonce', true)
    ->set_option('grouping', true)
    ->set_option('groupingView', array
    (
        'groupField' => array('salesproject'),
        'groupColumnShow' => array(false),
        'groupText' => array('<strong>{0}</strong> ({1})'),
        'groupOrder' => array('asc'),
        'groupSummary' => array(false),
        'showSummaryOnHide' => false
    ));

$grid->render();
?>
</div>
