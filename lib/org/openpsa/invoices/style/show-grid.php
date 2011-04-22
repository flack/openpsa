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
?>

<script type="text/javascript">//<![CDATA[
<?php echo "var " . $grid_id . '_entries = ' . json_encode($data['entries']); ?>
//]]></script>

<div class="org_openpsa_invoices <?php echo $classes ?> full-width">

<table id="&(grid_id);"></table>
<div id="p_&(grid_id);"></div>

</div>

<script type="text/javascript">
jQuery("#&(grid_id);").jqGrid({
      datatype: "local",
      data: &(grid_id);_entries,
      colNames: ['id', 'index_number', <?php
                 echo '"' . $data['l10n']->get('invoice') . '",';
                 echo '"' . $data['l10n']->get('customer contact') . '",';
                 if ($data['show_customer'])
                 {
                     echo '"' . $data['l10n']->get('customer') . '",';
                 }
                 echo '"index_sum", "' . $data['l10n']->get('amount') . '",';
                 echo '"index_due", "' . $data['l10n']->get('due') . '",';

                 if ($data['list_type'] != 'paid')
                 {
                     echo '"' . $data['l10n']->get('next action') . '"';
                 }
                 else
                 {
                     echo '"' . $data['l10n']->get('paid date') . '"';
                 }
      ?>],
      colModel:[
          {name:'id', index:'id', hidden:true, key:true},
          {name:'index_number',index:'index_number', hidden:true},
          {name:'number', index: 'index_number', width: 80, align: 'center', fixed: true, classes: 'title'},
          {name:'contact', index: 'contact'},
          <?php if ($data['show_customer'])
          { ?>
              {name:'customer', index: 'customer'},
          <?php } ?>
          {name:'index_sum', index: 'index_sum', sorttype: "number", hidden:true},
          {name:'sum', index: 'index_sum', width: 80, fixed: true, align: 'right'},
          {name:'index_due', index: 'index_due', sorttype: "integer", hidden:true },
          {name:'due', index: 'index_due', width: 80, align: 'center'},
          {name:'action', index: 'action', width: 80, align: 'center'}
      ],
      loadonce: true,
      rowNum: <?php echo sizeof($data['entries']); ?>,
      <?php
      if (!array_key_exists('deliverable', $data))
      { ?>
          caption: "&(data['list_label']);",
      <?php } ?>
      footerrow: true
});

jQuery("#&(grid_id);").jqGrid('footerData', 'set', <?php echo json_encode($footer_data); ?>);

</script>
