<?php
$url = $data['router']->generate('list_json_type', ['type' => $data['list_type']]);

$grid = $data['grid'];
$classes = $data['list_type'];

if (in_array($data['list_type'], ['overdue', 'default'])) {
    $classes .= ' bad';
} elseif ($data['list_type'] == 'paid') {
    $classes .= ' good';
}
?>
<script type="text/javascript">
function calculate_total()
{
    var grid = $('#<?php echo $grid->get_identifier(); ?>'),
    total = grid.jqGrid('getCol', 'index_sum', false, 'sum'),
    separator_expression = /([0-9]+)([0-9]{3})/,
    l10n = $.jgrid.locales[$.jgrid.defaults.locale].formatter.number;

    total = total.toFixed(2).replace(/\./, l10n.decimalSeparator);

    while (separator_expression.test(total)) {
        total = total.replace(separator_expression, '$1' + l10n.thousandsSeparator + '$2');
    }

    grid.jqGrid("footerData", "set", {"sum": total});
}
</script>
<?php
$grid->set_option('scroll', 1);
$grid->set_option('rowNum', 6);
$grid->set_option('height', 120);
$grid->set_option('viewrecords', true);
$grid->set_option('url', $url);
$grid->set_option('caption', $data['list_label']);
$grid->set_option('footerrow', true);
$grid->set_option('loadComplete', 'calculate_total', false);

$grid->set_column('number', $data['l10n']->get('invoice'), 'width: 80, align: "center", fixed: true, classes: "title"', 'string');

if (!($data['customer'] instanceof org_openpsa_contacts_group_dba)) {
    $grid->set_column('customer', $data['l10n']->get('customer'), 'sortable: false, classes: "ui-ellipsis"');
}

if (!($data['customer'] instanceof org_openpsa_contacts_person_dba)) {
    $grid->set_column('contact', $data['l10n']->get('customer contact'), 'sortable: false, classes: "ui-ellipsis"');
}

$grid->set_column('due', $data['l10n']->get('due'), 'width: 80, fixed: true, align: "right", formatter: "date"')
    ->set_column('sum', $data['l10n']->get('amount'), 'width: 80, fixed: true, align: "right"', 'number');
if ($data['list_type'] != 'paid') {
    $grid->set_column('action', $data['l10n']->get('next action'), 'width: 80, align: "center"');
} else {
    $grid->set_column('paid', $data['l10n']->get('paid date'), 'width: 80, align: "right", formatter: "date"');
}

$footer_data = [
    'customer' => $data['l10n']->get('totals')
];

$grid->set_footer_data($footer_data);
?>

<div class="org_openpsa_invoices <?php echo $classes ?> full-width">
<?php $grid->render(); ?>
</div>
