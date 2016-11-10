<?php
$grid = $data['grid'];
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
$rows = array();
$formatter = $data['l10n']->get_formatter();

$state_labels = array
(
    org_openpsa_sales_salesproject_dba::STATE_LOST => 'lost',
    org_openpsa_sales_salesproject_dba::STATE_CANCELED => 'canceled',
    org_openpsa_sales_salesproject_dba::STATE_ACTIVE => 'active',
    org_openpsa_sales_salesproject_dba::STATE_WON => 'won',
    org_openpsa_sales_salesproject_dba::STATE_DELIVERED => 'delivered',
    org_openpsa_sales_salesproject_dba::STATE_INVOICED => 'invoiced'
);

foreach ($data['salesprojects'] as $salesproject) {
    $salesproject_url = "{$prefix}salesproject/{$salesproject->guid}/";

    $row = array();

    $row['id'] = $salesproject->id;

    $row['index_title'] = $salesproject->title;
    $row['title'] = '<a href="' . $salesproject_url . '">' . $salesproject->title . '</a>';

    if ($data['mode'] != 'customer') {
        $row['index_customer'] = '';
        $row['customer'] = '';
        if ($customer = $salesproject->get_customer()) {
            $label = $customer->get_label();
            $row['index_customer'] = $label;
            if ($data['contacts_url']) {
                $type = 'group';
                if (is_a($customer, 'org_openpsa_contacts_person_dba')) {
                    $type = 'person';
                }
                $row['customer'] = "<a href=\"{$data['contacts_url']}{$type}/{$customer->guid}/\">{$label}</a>";
            } else {
                $row['customer'] = $label;
            }
        }
    } else {
        $row['index_state'] = $salesproject->state;
        $row['state'] = $data['l10n']->get($state_labels[$salesproject->state]);
    }

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

    $row['prev_action'] = '';

    $action = $salesproject->prev_action;
    if ($action['type'] != 'noaction') {
        $format = ($action['type'] == 'event') ? 'datetime' : 'date';
        $row['prev_action'] = "<a href=\"{$salesproject_url}#{$action['obj']->guid}\" class=\"{$action['type']}\">" . $formatter->{$format}($action['time']) . ": {$action['obj']->title}</a>";
    }

    $row['next_action'] = '';

    $action = $salesproject->next_action;
    if ($action['type'] != 'noaction') {
        $format = ($action['type'] == 'event') ? 'datetime' : 'date';
        $row['next_action'] = "<a href=\"{$salesproject_url}#{$action['obj']->guid}\" class=\"{$action['type']}\">" . $formatter->{$format}($action['time']) . ": {$action['obj']->title}</a>";
    }
    $rows[] = $row;
}
?>
<div class="org_openpsa_sales full-width crop-height <?php echo $data['mode']; ?>">

<?php
$grid->set_column('title', $data['l10n']->get('title'), 'width: 100, classes: "ui-ellipsis"', 'string');
if ($data['mode'] != 'customer') {
    $grid->set_column('customer', $data['l10n']->get('customer'), 'width: 80, classes: "ui-ellipsis"', 'string');
} else {
    $grid->set_column('state', $data['l10n']->get('state'), 'hidden: true', 'number');
}
$grid->set_column('owner', $data['l10n']->get('owner'), 'width: 70, classes: "ui-ellipsis"', 'string')
->set_column('closeest', $data['l10n']->get('estimated closing date'), 'width: 85, align: "right", formatter: "date", fixed: true')
->set_column('value', $data['l10n']->get('value'), 'width: 60, summaryType: "sum", template: "number"');
if ($data['mode'] == 'active') {
    $grid->set_column('probability', $data['l10n']->get('probability'), 'width: 55, fixed: true, align: "right"')
    ->set_column('weightedvalue', $data['l10n']->get('weighted value'), 'width: 55, template: "number"');
}
$grid->set_column('profit', $data['l10n']->get('profit'), 'width: 60, summaryType: "sum", template: "number"')
->set_column('prev_action', $data['l10n']->get('previous action'), 'width: 75, align: "center", classes: "ui-ellipsis"')
->set_column('next_action', $data['l10n']->get('next action'), 'width: 75, align: "center", classes: "ui-ellipsis"');

$grid->set_option('scroll', 1)
->set_option('loadonce', true)
->set_option('sortname', 'index_title');

$grid->set_option('grouping', true)
    ->set_option('groupingView', array
    (
        'groupField' => ($data['mode'] != 'customer') ? array('customer') : array('state'),
        'groupColumnShow' => array(false),
        'groupText' => array('<strong>{0}</strong> ({1})'),
        'groupOrder' => array('asc'),
        'groupSummary' => array(true),
        'showSummaryOnHide' => true
    ));

$grid->render($rows);
?>

</div>
<?php
$grid_id = $grid->get_identifier();
$host_prefix = midcom::get()->get_host_prefix();

$filename = $data['list_title'];
$filename .= '_' . date('Y_m_d');
$filename = preg_replace('/[^a-z0-9-]/i', '_', $filename);
?>

<form id="&(grid_id);_export" class="tab_escape" method="post" action="&(host_prefix);midcom-exec-org.openpsa.core/csv_export.php">
<input id="&(grid_id);_csvdata" type="hidden" value="" name="org_openpsa_export_csv_data" />
<input type="hidden" value="&(filename);.csv" name="org_openpsa_export_csv_filename" />
<input class="button tab_escape" type="submit" value="<?php echo midcom::get()->i18n->get_string('download as CSV', 'org.openpsa.core'); ?>" />
</form>

<script type="text/javascript">

org_openpsa_export_csv.add({
      id: '&(grid_id);',
      fields: {
          index_title: '<?php echo $data['l10n']->get('title'); ?>',
          <?php if ($data['mode'] != 'customer') {
    ?>
            index_customer: '<?php echo $data['l10n']->get('customer'); ?>',
          <?php 
} ?>
          index_owner: '<?php echo $data['l10n']->get('owner'); ?>',
          closeest: '<?php echo $data['l10n']->get('estimated closing date'); ?>',
          index_value: '<?php echo $data['l10n']->get('value'); ?>',
          <?php if ($data['mode'] == 'active') {
    ?>
              probability: '<?php echo $data['l10n']->get('probability'); ?>',
              index_weightedvalue: '<?php echo $data['l10n']->get('weighted value'); ?>',
          <?php 
} ?>
          index_profit: '<?php echo $data['l10n']->get('profit'); ?>',
          prev_action: '<?php echo $data['l10n']->get('previous action'); ?>',
          next_action: '<?php echo $data['l10n']->get('next action'); ?>'
        }
});

</script>
