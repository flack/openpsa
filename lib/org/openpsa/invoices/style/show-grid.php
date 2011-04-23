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

$grid->set_option('loadonce', true);

if (!array_key_exists('deliverable', $data))
{
    $grid->set_option('caption', $data['list_label']);
}

$grid->set_column('number', $data['l10n']->get('invoice'), 'width: 80, align: "center", fixed: true, classes: "title"', 'string')
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

$grid->set_footer_data($footer_data);
?>

<div class="org_openpsa_invoices <?php echo $classes ?> full-width">
<?php $grid->render($data['entries']); ?>
</div>

<script type="text/javascript">
</script>
