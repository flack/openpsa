<?php
$grid = $data['grid'];
$rows = [];
$formatter = $data['l10n']->get_formatter();

$state_labels = [
    org_openpsa_sales_salesproject_dba::STATE_LOST => $data['l10n']->get('lost'),
    org_openpsa_sales_salesproject_dba::STATE_CANCELED => $data['l10n']->get('canceled'),
    org_openpsa_sales_salesproject_dba::STATE_ACTIVE => $data['l10n']->get('active'),
    org_openpsa_sales_salesproject_dba::STATE_WON => $data['l10n']->get('won'),
    org_openpsa_sales_salesproject_dba::STATE_DELIVERED => $data['l10n']->get('delivered'),
    org_openpsa_sales_salesproject_dba::STATE_INVOICED => $data['l10n']->get('invoiced')
];

foreach ($data['salesprojects'] as $salesproject) {
    $salesproject_url = $data['router']->generate('salesproject_view', ['guid' => $salesproject->guid]);

    $row = [];

    $row['id'] = $salesproject->id;

    $row['index_title'] = $salesproject->title;
    $row['title'] = '<a href="' . $salesproject_url . '">' . $salesproject->title . '</a>';

    if ($data['mode'] != 'customer') {
        $row['index_customer'] = '';
        $row['customer'] = '';
        if ($salesproject->customer) {
            try {
                $customer = org_openpsa_contacts_group_dba::get_cached($salesproject->customer);
                $label = $customer->get_label();
                $row['index_customer'] = $label;
                $row['customer'] = $label;
                if ($data['contacts_url']) {
                    $row['customer'] = "<a href=\"{$data['contacts_url']}group/{$customer->guid}/\">{$label}</a>";
                }
            } catch (midcom_error $e) {
                $e->log();
            }
        }
    }
    $row['index_customerContact'] = '';
    $row['customerContact'] = '';
    if ($salesproject->customerContact) {
        try {
            $customer = org_openpsa_contacts_person_dba::get_cached($salesproject->customerContact);
            $label = $customer->get_label();
            $row['index_customerContact'] = $label;
            $row['customerContact'] = $label;
            if ($data['contacts_url']) {
                $row['customerContact'] = "<a href=\"{$data['contacts_url']}person/{$customer->guid}/\">{$label}</a>";
            }
        } catch (midcom_error $e) {
            $e->log();
        }
    }

    $row['state'] = $salesproject->state;

    try {
        $owner = org_openpsa_contacts_person_dba::get_cached($salesproject->owner);
        $owner_widget = org_openpsa_widgets_contact::get($salesproject->owner);
        $row['index_owner'] = $owner->rname;
        $row['owner'] = $owner_widget->show_inline();
    } catch (midcom_error $e) {
        $row['index_owner'] = '';
        $row['owner'] = '';
    }

    $row['closeest'] = '';

    if ($salesproject->closeEst) {
        $row['closeest'] = date("Y-m-d", $salesproject->closeEst);
    }

    $row['value'] = $salesproject->value;

    if ($data['mode'] == 'active') {
        $row['probability'] = $salesproject->probability . '%';
        $row['weightedvalue'] = $salesproject->value / 100 * $salesproject->probability;
    }
    $row['profit'] = $salesproject->profit;
    $rows[] = $row;
}
?>
<div class="org_openpsa_sales full-width crop-height <?php echo $data['mode']; ?>">

<?php
$grid->set_column('title', $data['l10n']->get('title'), 'width: 100, classes: "ui-ellipsis"', 'string');
if ($data['mode'] != 'customer') {
    $grid->set_column('customer', $data['l10n']->get('customer'), 'width: 80, classes: "ui-ellipsis"', 'string');
}
$grid->set_column('customerContact', $data['l10n']->get('customer contact'), 'width: 80, classes: "ui-ellipsis"', 'string');
$grid->set_select_column('state', $data['l10n']->get('state'), 'hidden: true', $state_labels);
$grid->set_column('owner', $data['l10n']->get('owner'), 'width: 70, classes: "ui-ellipsis"', 'string')
    ->set_column('closeest', $data['l10n']->get('estimated closing date'), 'width: 95, align: "right", formatter: "date", fixed: true')
    ->set_column('value', $data['l10n']->get('value'), 'width: 60, summaryType: "sum", template: "number"');
if ($data['mode'] == 'active') {
    $grid->set_column('probability', $data['l10n']->get('probability'), 'width: 55, fixed: true, align: "right"')
    ->set_column('weightedvalue', $data['l10n']->get('weighted value'), 'width: 55, template: "number"');
}
$grid->set_column('profit', $data['l10n']->get('profit'), 'width: 60, summaryType: "sum", template: "number"');

$grid->set_option('scroll', 1)
    ->set_option('loadonce', true)
    ->set_option('sortname', 'index_title');

$grid->set_option('grouping', true)
    ->set_option('groupingView', [
        'groupField' => ($data['mode'] != 'customer') ? ['customer'] : ['state'],
        'groupColumnShow' => [false],
        'groupText' => ['<strong>{0}</strong> ({1})'],
        'groupOrder' => ['asc'],
        'groupSummary' => [true],
        'showSummaryOnHide' => true
    ]);

$grid->render($rows);
?>

</div>
<?php
$grid_id = $grid->get_identifier();

$filename = $data['list_title'];
$filename .= '_' . date('Y_m_d');
$filename = preg_replace('/[^a-z0-9-]/i', '_', $filename);
?>

<button id="&(grid_id);_export">
	   <i class="fa fa-download"></i>
	   <?php echo midcom::get()->i18n->get_string('download as CSV', 'org.openpsa.core'); ?>
</button>

<script type="text/javascript">

$('#&(grid_id);').jqGrid('filterToolbar');

midcom_grid_csv.add({
      id: '&(grid_id);',
      filename: '&(filename);',
      fields: {
          index_title: '<?php echo $data['l10n']->get('title'); ?>',
          <?php
          if ($data['mode'] != 'customer') {
    ?>
            index_customer: '<?php echo $data['l10n']->get('customer'); ?>',
          <?php
} ?>
          index_owner: '<?php echo $data['l10n']->get('owner'); ?>',
          closeest: '<?php echo $data['l10n']->get('estimated closing date'); ?>',
          value: '<?php echo $data['l10n']->get('value'); ?>',
          <?php if ($data['mode'] == 'active') {
    ?>
              probability: '<?php echo $data['l10n']->get('probability'); ?>',
              weightedvalue: '<?php echo $data['l10n']->get('weighted value'); ?>',
          <?php
} ?>
          index_profit: '<?php echo $data['l10n']->get('profit'); ?>'
        }
});

</script>
