<?php
$grid = $data['grid'];
$classes = $data['list_type'];

if ($data['list_type'] == 'overdue')
{
    $classes .= ' bad';
}
else if ($data['list_type'] == 'paid')
{
    $classes .= ' good';
}

$footer_data = array
(
    'customer' => $data['l10n']->get('totals'),
    'sum' => org_openpsa_helpers::format_number($data['totals']['totals'])
);

$grid->set_option('loadonce', true);

if (!array_key_exists('deliverable', $data))
{
    $grid->set_option('caption', $data['list_label']);
}

$grid->set_column('number', $data['l10n']->get('invoice'), 'width: 80, align: "center", fixed: true, classes: "title"', 'string');

if (!is_a($data['customer'], 'org_openpsa_contacts_group_dba'))
{
    $grid->set_column('customer', $data['l10n']->get('customer'));
}
if (!is_a($data['customer'], 'org_openpsa_contacts_person_dba'))
{
    $grid->set_column('contact', $data['l10n']->get('customer contact'));
}

if (array_key_exists('deliverable', $data))
{
    $grid->set_column('item_sum', $data['deliverable']->title, 'width: 80, fixed: true, align: "right"', 'number');
    $footer_data['item_sum'] = org_openpsa_helpers::format_number($data['totals']['deliverable']);
}
$grid->set_column('due', $data['l10n']->get('due'), 'width: 80, align: "center", formatter: "date"')
    ->set_column('sum', $data['l10n']->get('amount'), 'width: 80, fixed: true, align: "right"', 'number');

if ($data['list_type'] != 'paid')
{
    $grid->set_column('action', $data['l10n']->get('next action'), 'width: 80, align: "center"');
}
else
{
    $grid->set_column('action', $data['l10n']->get('paid date'), 'width: 80, align: "center"');
}

$grid->set_footer_data($footer_data);
?>

<div class="org_openpsa_invoices <?php echo $classes ?> full-width crop-height">
<?php $grid->render($data['entries']); ?>
</div>
