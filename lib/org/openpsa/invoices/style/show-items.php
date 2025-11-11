<?php
$position_url = $data['router']->generate('invoice_item_position');

$grid = $data['grid'];
$grid->set_option('editurl', $data['router']->generate('invoice_item_edit', ['guid' => $data['invoice']->guid]));
$grid->set_option('pager', '#p_' . $grid->get_identifier());
$grid->set_option('pgbuttons', false);
$grid->set_option('pginput', false);

$grid->set_column('position', $data['l10n']->get('position'), 'hidden: true');
$grid->set_column('deliverable', midcom::get()->i18n->get_string('agreement', 'org.openpsa.projects'), 'fixed: true, align: "center", width: 40, sortable: false');
$grid->set_column('task', midcom::get()->i18n->get_string('task', 'org.openpsa.projects'), 'fixed: true, align: "center", width: 40, sortable: false');
$grid->set_column('description', $data['l10n_midcom']->get('description'), 'editable: true, edittype: "textarea", sortable: false');
$grid->set_column('price', $data['l10n']->get('price'), 'align: "right", width: 40, formatter: "number", sortable: false, editable: true');
$grid->set_column('quantity', $data['l10n']->get('quantity'), 'align: "right", width: 30, formatter: "number", sortable: false, editable: true');
$grid->set_column('sum', $data['l10n']->get('sum'), 'align: "right", width: 60, formatter: "number", sortable: false, summaryType: "sum"');
$grid->set_column('actions', '', 'width: 65, fixed: true, sortable: false, title: false');

$grid_id = $grid->get_identifier();
?>

<div class="full-width">
    <?php $grid->render($data['entries']); ?>
</div>

<script type="text/javascript">
    jQuery("#<?= $grid_id ?>")
        .on("keyup", '[aria-describedby="<?= $grid_id ?>_price"] input, [aria-describedby="<?= $grid_id ?>_quantity"] input', function() {
            var rowid = $(this).closest('tr').attr('id'),
                grid = jQuery("#<?= $grid_id ?>"),
                price = grid.jqGrid('getCell', rowid, 5),
                quantity = grid.jqGrid('getCell', rowid, 6);

            grid.jqGrid('setRowData', rowid, {sum: parseFloat(price) * parseFloat(quantity)});
            update_totals();
        });

    function update_totals() {
        let grid = jQuery("#<?= $grid_id ?>"),
            total = grid.jqGrid('getRowData').reduce(function(accumulator, value) {
                return accumulator + (parseFloat(value.sum) || 0);
            }, 0);

        grid.jqGrid("footerData", "set", {sum: total});
    }

    midcom_grid_editable.enable_inline("<?= $grid_id ?>", {
        afterdeletefunc: update_totals,
        enable_sorting: true,
        position_url: '&(position_url);',
        button_label: "<?php echo $data['l10n']->get('add new invoice item'); ?>"
    });
</script>
