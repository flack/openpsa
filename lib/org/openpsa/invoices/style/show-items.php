<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);

$grid = $data['grid'];
$grid->set_option('editurl', $prefix . 'invoice/itemedit/' . $data['invoice']->guid . '/');
$grid->set_option('pager', '#p_' . $grid->get_identifier());

$grid->set_column('position', $data['l10n']->get('position'),  'align: "right", width: 40, formatter: "integer", sortable: false');
$grid->set_column('deliverable', midcom::get()->i18n->get_string('agreement', 'org.openpsa.projects'), 'width: 80, sortable: false');
$grid->set_column('task', midcom::get()->i18n->get_string('task', 'org.openpsa.projects'), 'width: 80, sortable: false');
$grid->set_column('description', $data['l10n_midcom']->get('description'), 'editable: true, edittype: "textarea", sortable: false');
$grid->set_column('price', $data['l10n']->get('price'), 'align: "right", width: 40, formatter: "number", sortable: false, editable: true');
$grid->set_column('quantity', $data['l10n']->get('quantity'), 'align: "right", width: 30, formatter: "number", sortable: false, editable: true');
$grid->set_column('sum', $data['l10n']->get('sum'), 'align: "right", width: 60, formatter: "number", sortable: false, summaryType: "sum"');

$grid->set_column('actions', '',  'width: 65, fixed: true, sortable: false, title: false');

?>

<div class="full-width">
    <?php $grid->render($data['entries']); ?>
</div>

<script type="text/javascript">
    function getNextPosition()
    {
        return $('#<?php echo $grid->get_identifier(); ?> td[aria-describedby="invoice_items_position"]').length + 1;
    }

    //trigger the drag&drop-sortability of this (current) grid
    jQuery("#<?php echo $grid->get_identifier(); ?>").jqGrid("sortableRows", {helper: "clone"});

    //Event that is triggered after Drop
    jQuery( "#<?php echo $grid->get_identifier(); ?>" ).bind("sortstop",
        function(event, ui)
        {
            refreshItemPositions();
            //Refresh the rows alternately with the style from the class even
            $(this).find("tbody tr.jqgrow").removeClass('even');
            $(this).find("tbody tr.jqgrow:visible:odd").addClass('even');
        });

    function saveSingleItemPosition(id, pos)
    {
        $.ajax(
        {
            type: 'POST',
            url: '&(prefix);invoice/itemposition/',
            data: {id:id, position:pos}
        });
    }

    function refreshItemPositions()
    {
        //foreach position-cell (or position td)
        $('#<?php echo $grid->get_identifier(); ?> td[aria-describedby="invoice_items_position"]').each(
            function(index)
            {
                var idx = index + 1,
                    oldPos = parseInt($(this).html()),
                    //Get the id of the tr in witch this(=td) is
                    trId = $(this).parent().attr('id');

                if (idx !== oldPos) {
                    // Set new Position-Number in this td
                    $(this).html(idx);

                    if (trId.substring(0,4) !== 'new_') {
                        saveSingleItemPosition(trId, idx);
                    }
                }
            });
    }

    org_openpsa_grid_editable.enable_inline("<?php echo $grid->get_identifier(); ?>",
    {
        aftersavefunc: function (rowid, response)
        {
            var grid = jQuery("#<?php echo $grid->get_identifier(); ?>"),
                price = grid.jqGrid('getCell', rowid, 5),
                quantity = grid.jqGrid('getCell', rowid, 6),
                rows = grid.jqGrid('getRowData'),
                total = 0,
                i = 0;

            grid.jqGrid('setRowData', rowid, {sum: parseFloat(price) * parseFloat(quantity)});

            //if saved row was new_... then refresh tr-id
            if (response.responseText !== undefined) {
                var return_values = $.parseJSON(response.responseText),
                oldId = return_values.oldid;
                if (oldId.substring(0,4) === 'new_') {
                    var pos = $("#<?php echo $grid->get_identifier(); ?> tr[id='" + oldId + "']").prevAll().length;
                    rowid = return_values.id;

                    saveSingleItemPosition(rowid, pos);

                    $('#'+oldId).attr('id', rowid);
                    $('#edit_button_' + oldId).attr('id', 'edit_button_' + rowid);
                    $('#save_button_' + oldId).attr('id', 'save_button_' + rowid);
                    $('#cancel_button_' + oldId).attr('id', 'cancel_button_' + rowid);
                    $('#delete_button_' + oldId).attr('id', 'delete_button_' + rowid);
                }
            }

            for (i = 0; i < rows.length; i++) {
                total += parseFloat(grid.jqGrid('getCell', rows[i].id, 7));
            }

            grid.jqGrid("footerData", "set", {sum: total});
            org_openpsa_grid_editable.toggle(rowid, false);

            //Specially for the case that a row was deleted
            refreshItemPositions();
        },
        enable_sorting: true
    });
</script>
