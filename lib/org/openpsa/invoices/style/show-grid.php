<?php
$classes = $data['list_type'];

if ($data['list_type'] == 'overdue')
{
    $classes .= ' bad';
}
else if ($data['list_type'] == 'paid')
{
    $classes .= ' good';
}

$grid_id = $data['list_type'] . '_invoices_grid';

if (array_key_exists('deliverable', $data))
{
    $grid_id = 'd_' . $data['deliverable']->id . $grid_id;
}

$footer_data = array();

foreach ($data['totals'] as $label => $sum)
{
    if (!$sum)
    {
        continue;
    }
    $footer_data = array
    (
        'contact' => $data['l10n']->get($label),
        'sum' => org_openpsa_helpers::format_number($sum)
    );
}

$grid = new org_openpsa_core_ui_jqgrid($grid_id);
$grid->set_option('datatype', 'local')
    ->set_option('loadonce', true)
    ->set_option('rowNum', sizeof($data['entries']))
    ->set_option('footerrow', true);

if (!array_key_exists('deliverable', $data))
{
    $grid->set_option('caption', $data['list_label']);
}

$grid->set_column('id', 'id', 'hidden:true, key:true')
    ->set_column('number', $data['l10n']->get('invoice'), 'width: 80, align: "center", fixed: true, classes: "title"', 'string')
    ->set_column('contact', $data['l10n']->get('customer contact'));

if ($data['show_customer'])
{
    $grid->set_column('customer', $data['l10n']->get('customer'));
}
$grid->set_column('sum', $data['l10n']->get('amount'), 'width: 80, fixed: true, align: "right"', 'number')
    ->set_column('due', $data['l10n']->get('due'), 'width: 80, align: "center"', 'number');

if ($data['list_type'] != 'paid')
{
    $grid->set_column('customer', $data['l10n']->get('next action'), 'width: 80, align: "center"');
}
else
{
    $grid->set_column('customer', $data['l10n']->get('paid date'), 'width: 80, align: "center"');
}
?>

<div class="org_openpsa_invoices <?php echo $classes ?> full-width">
<?php $grid->render($data['entries']); ?>
</div>

<script type="text/javascript">
jQuery("#&(grid_id);").jqGrid('footerData', 'set', <?php echo json_encode($footer_data); ?>);
</script>
