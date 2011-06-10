<?php
$nap = new midcom_helper_nav();
$node = $nap->get_node($nap->get_current_node());
?>
<div class="sidebar">
    <?php
    $_MIDCOM->dynamic_load($node[MIDCOM_NAV_RELATIVEURL] . "person/memberships/{$data['person']->guid}/");

    // Try to find campaigns component
    $campaigns_node = midcom_helper_misc::find_node_by_component('org.openpsa.directmarketing');
    if ($campaigns_node)
    {
        $_MIDCOM->dynamic_load($campaigns_node[MIDCOM_NAV_RELATIVEURL] . "campaign/list/{$data['person']->guid}/");
    }
    ?>
</div>

<div class="main">
<?php
    $data['datamanager']->display_view();
    //add tabs
    $siteconfig = org_openpsa_core_siteconfig::get_instance();
    $invoices_url = $siteconfig->get_node_relative_url('org.openpsa.invoices');
    if ($invoices_url)
    {
        //TODO: Check for privileges somehow
        $qb = org_openpsa_invoices_invoice_dba::new_query_builder();
        $qb->add_constraint('customerContact', '=', $data['person']->id);
        $qb->set_limit(1);
        if ($qb->count() > 0)
        {
            $tabs[] = array
            (
                'url' => $invoices_url . "list/customer/all/{$data['person']->guid}/",
                'title' => $_MIDCOM->i18n->get_string('invoices', 'org.openpsa.invoices'),
            );
        }
    }
    org_openpsa_core_ui::render_tabs($data['person']->guid, $tabs);
?>
</div>