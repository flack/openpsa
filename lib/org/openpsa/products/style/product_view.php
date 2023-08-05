<?php
$view = $data['view_product'];
?>
<div class="content-with-sidebar">
    <div class="main">
        <h1>&(view['code']:h); &(view['title']:h);</h1>
        <?php
        $data['datamanager']->display_view(true);

        $tabs = [];
        $siteconfig = org_openpsa_core_siteconfig::get_instance();

        if ($sales_url = $siteconfig->get_node_relative_url('org.openpsa.sales')) {
            $tabs[] = [
                'url' => $sales_url . "deliverable/list/product/{$data['product']->guid}/",
                'title' => midcom::get()->i18n->get_string('deliverables', 'org.openpsa.sales'),
            ];
        }

        org_openpsa_widgets_ui::render_tabs($data['product']->guid, $tabs);
        ?>
    </div>
    <aside>
   	 	<?php midcom_show_style('group-tree'); ?>
    </aside>
</div>
