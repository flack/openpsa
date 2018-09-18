<?php
$rows = [];

foreach ($data['deliverables'] as $deliverable) {
    $process_link = $data['router']->generate('deliverable_process', ['guid' => $deliverable->guid]);
    $actions = '<form method="post" action="' . $process_link . '">';

    switch ($data['state']) {
        case 'proposed':
            $actions .= '<input type="submit" name="order" value="' . $data['l10n']->get('mark ordered') . '" /> ';
            $actions .= '<input type="submit" name="decline" value="' . $data['l10n']->get('mark declined') . '" />';
            break;
        case 'started':
        case 'ordered':
            if ($deliverable->orgOpenpsaObtype == org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION) {
                $entries = $deliverable->get_at_entries();
                if (   $entries
                    && $entries[0]->status == midcom_services_at_entry_dba::SCHEDULED
                    && midcom::get()->auth->can_user_do('midgard:create', null, org_openpsa_invoices_invoice_dba::class)) {
                    $actions .= '<input type="submit" name="run_cycle" value="' . $data['l10n']->get('generate now') . '" />';
                }
            } elseif ($deliverable->state == org_openpsa_sales_salesproject_deliverable_dba::STATE_ORDERED) {
                $actions .= '<input type="submit" name="deliver" value="' . $data['l10n']->get('mark delivered') . '" />';
            }
            break;
        case 'delivered':
            if (   $deliverable->orgOpenpsaObtype != org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION
                && midcom::get()->auth->can_user_do('midgard:create', null, org_openpsa_invoices_invoice_dba::class)) {
                $client_class = $data['config']->get('calculator');
                $client = new $client_class();
                $client->run($deliverable);

                if ($client->get_price() > 0) {
                    $actions .= '<input type="submit" name="invoice" value="' . sprintf($data['l10n_midcom']->get('create %s'), $data['l10n']->get('invoice')) . '" />';
                }
            }
            break;
    }
    $actions .= '</form>';

    $rows[] = [
        'id' => $deliverable->id,
        'title' => '<a href="' . $data['router']->generate('deliverable_view', ['guid' => $deliverable->guid]) . '">' . $deliverable->title . '</a>',
        'index_title' => $deliverable->title,
        'price' => $deliverable->get_state() == 'invoiced' ? $deliverable->invoiced : $deliverable->price,
        'created' => strftime('%Y-%m-%d %H:%i:%s', $deliverable->metadata->created),
        'subscription' => $deliverable->orgOpenpsaObtype == org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION,
        'actions' => $actions
    ];
}

$provider = new midcom\grid\provider($rows, 'local');
$grid = $provider->get_grid('deliverables_' . $data['state']);

$classes = $data['state'];
if ($data['state'] == 'declined') {
    $classes .= ' bad';
} elseif ($data['state'] == 'started' || $data['state'] == 'invoiced') {
    $classes .= ' good';
} elseif ($data['state'] == 'delivered' || $data['state'] == 'ordered') {
    $classes .= ' normal';
}

$footer_data = [
    'price' => $provider->get_column_total('price')
];
$grid->set_option('loadonce', true);
$grid->set_option('caption', $data['l10n']->get($data['state']));

$grid->set_column('created', $data['l10n']->get('created'), 'width: 80, fixed: true, align: "center", formatter: "date"');
$grid->set_column('title', $data['l10n']->get('title'), 'width: 120', 'string');
$grid->set_column('price', $data['l10n']->get('price'), 'width: 80, fixed: true, formatter: "number", align: "right", title: false, classes: "sum"', 'number');
if ($data['state'] !== 'declined' && $data['state'] !== 'invoiced') {
    $grid->set_column('actions', $data['l10n']->get('actions'), 'width: 150, fixed: true, sortable: false, align: "center"');
}
$grid->set_column('subscription', $data['l10n']->get('subscription'), 'width: 30, align: "center", fixed: true, formatter: "checkbox"');

$grid->set_footer_data($footer_data);
?>

<div class="org_openpsa_sales <?php echo $classes ?> full-width">
<?php $grid->render(); ?>
</div>
