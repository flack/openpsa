<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);

$grid = $data['grid'];
$grid->set_option('editurl', $prefix . 'invoice/itemedit/' . $data['invoice']->guid . '/');
$grid->set_option('pager', '#p_' . $grid->get_identifier());

$grid->set_column('description', $_MIDCOM->i18n->get_string('description', 'midcom'), 'editable: true, edittype: "textarea"');
$grid->set_column('price', $data['l10n']->get('price'), 'align: "right", width: 40, formatter: "number", editable: true');
$grid->set_column('quantity', $data['l10n']->get('quantity'), 'align: "right", width: 40, formatter: "number", editable: true');
$grid->set_column('sum', $data['l10n']->get('sum'), 'align: "right", width: 60, formatter: "number", summaryType: "sum"');
$grid->set_column('actions', '');
?>

<div>
    <?php $grid->render($data['entries']); ?>
</div>

<script type="text/javascript">
org_openpsa_grid_editable.enable_inline("<?php echo $grid->get_identifier(); ?>",
{
    aftersavefunc: function (rowid, response)
    {
        var grid = jQuery("#<?php echo $grid->get_identifier(); ?>"),
            price = grid.jqGrid('getCell', rowid, 2),
            quantity = grid.jqGrid('getCell', rowid, 3)
            rows = grid.jqGrid('getRowData'),
            total = 0
            i = 0;

        grid.jqGrid('setRowData', rowid, {sum: parseFloat(price) * parseFloat(quantity)});

        for (i = 0; i < rows.length; i++)
        {
            total += parseFloat(grid.jqGrid('getCell', rowid, 4));
        }
        grid.jqGrid("footerData", "set", {sum: total});
    }
});
</script>
