<?php
$view = $data['view_product'];
?>
<h1>&(view['code']:h); &(view['title']:h);</h1>
<div class="sidebar">
<?php midcom_show_style('group-tree'); ?>
</div>
<div class="main">
<?php
$data['datamanager']->schema->remove_field('id');
$data['datamanager']->display_view();

$tabs = array();
$siteconfig = org_openpsa_core_siteconfig::get_instance();

if ($sales_url = $siteconfig->get_node_relative_url('org.openpsa.sales')) {
    $tabs[] = array(
        'url' => $sales_url . "deliverable/list/product/{$data['product']->guid}/",
        'title' => midcom::get()->i18n->get_string('deliverables', 'org.openpsa.sales'),
    );
}

org_openpsa_widgets_ui::render_tabs($data['product']->guid, $tabs);
?>
</div>