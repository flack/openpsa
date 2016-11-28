<?php
$tree = new org_openpsa_widgets_tree('org_openpsa_products_product_group_dba', 'up');
$tree->title_fields = array('title', 'code');
$tree->link_callback = function($guid) {
    $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
    return $prefix . $guid . '/';
};
?>
<div class="area org_openpsa_helper_box">
<h3><?php echo $data['l10n']->get('groups'); ?></h3>
<?php
$tree->render();
?>
</div>
