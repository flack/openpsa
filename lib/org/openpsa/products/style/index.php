<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);

$unit_options = array();
foreach ($data['config']->get('unit_options') as $key => $value) {
    $unit_options[$key] = $data['l10n']->get($value);
}

$delivery_options = array(
    org_openpsa_products_product_dba::DELIVERY_SINGLE       => $data['l10n']->get('single delivery'),
    org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION => $data['l10n']->get('subscription'),
);

$type_options = array(
    org_openpsa_products_product_dba::TYPE_SERVICE   => $data['l10n']->get('service'),
    org_openpsa_products_product_dba::TYPE_GOODS     => $data['l10n']->get('material goods'),
    org_openpsa_products_product_dba::TYPE_SOLUTION  => $data['l10n']->get('solution'),
);

$grid = $data['grid'];
$grid->set_option('scroll', 1)
    ->set_option('rowNum', 40)
    ->set_option('height', 600)
    ->set_option('viewrecords', true)
    ->set_option('url', $prefix . 'json/')
    ->set_option('sortname', 'index_lastname');

$grid->set_column('code', $data['l10n']->get('code'), 'width: 80, fixed: true', 'string')
    ->set_column('title', $data['l10n_midcom']->get('title'), 'classes: "title ui-ellipsis"', 'string')
    ->set_select_column('orgOpenpsaObtype', $data['l10n']->get('type'), 'width: 130, fixed: true', $type_options)
    ->set_select_column('delivery', $data['l10n']->get('delivery type'), 'width: 130, fixed: true', $delivery_options)
    ->set_column('price', $data['l10n']->get('price'), 'width: 70, fixed: true, template: "number"')
    ->set_select_column('unit', $data['l10n']->get('unit'), 'width: 70, fixed: true', $unit_options);
?>
<div class="sidebar">
<div class="area org_openpsa_helper_box">
<h3><?php echo $data['l10n']->get('groups'); ?></h3>
<?php
$data['tree']->render();
?>
  </div>
</div>
<div class="main">
<div class="org_openpsa_user full-width fill-height">
<?php $grid->render(); ?>
<script type="text/javascript">
$('#<?php echo $grid->get_identifier(); ?>').jqGrid('filterToolbar');
</script>
</div>
</div>