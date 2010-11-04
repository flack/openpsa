<?php
$grid_id = $data['list_title'] . '_salesprojects_grid';

$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);

$rows = array();

foreach ($data['salesprojects'] as $salesproject)
{
    $salesproject_url = "{$prefix}salesproject/{$salesproject->guid}/";

    $row = array();

    $row['id'] = $salesproject->id;

    $row['index_title'] = $salesproject->title;
    $row['title'] = '<a href="' . $salesproject_url . '">' . $salesproject->title . '</a>';

    $row['index_customer'] = '';
    $row['customer'] = '';
    if (array_key_exists($salesproject->customer, $data['customers']))
    {
        $customer = $data['customers'][$salesproject->customer];
        $row['index_customer'] = $customer->official;
        if ($data['contacts_url'])
        {
            $row['customer'] = "<a href=\"{$data['contacts_url']}group/{$customer->guid}/\">{$customer->official}</a>";
        }
        else
        {
            $row['customer'] = $customer->official;
        }
    }

    $row['index_owner'] = '';
    $row['owner'] = '';
    if (array_key_exists($salesproject->owner, $data['owners']))
    {
        $owner = $data['owners'][$salesproject->owner];

        $owner_widget = org_openpsa_contactwidget::get($salesproject->owner);
        $row['index_owner'] = $owner->rname;
        $row['owner'] = $owner_widget->show_inline();
    }

    $row['index_closeest'] = '';
    $row['closeest'] = '';

    if ($salesproject->closeEst)
    {
        $row['index_closeest'] = $salesproject->closeEst;
        $row['closeest'] = strftime("%x", $salesproject->closeEst);
    }

    $row['index_value'] = $salesproject->value;
    $row['value'] = org_openpsa_helpers::format_number($salesproject->value);

    if ($data['list_title'] == 'active')
    {
        $row['probability'] = $salesproject->probability . '%';

        $row['index_weightedvalue'] = $salesproject->value / 100 * $salesproject->probability;
        $row['weightedvalue'] = org_openpsa_helpers::format_number($salesproject->value / 100 * $salesproject->probability);
    }

    $row['index_profit'] = $salesproject->profit;
    $row['profit'] = org_openpsa_helpers::format_number($salesproject->profit);

    $row['prev_action'] = '';

    $action = $salesproject->prev_action;
    switch ($action['type'])
    {
        case 'noaction':
            break;
        case 'event':
            $datelabel = strftime('%x %X', $action['time']);
            $row['prev_action'] = "<a href=\"{$salesproject_url}#{$action['obj']->guid}\" class=\"event\">{$datelabel}: {$action['obj']->title}</a>";
            break;
        case 'task':
            $datelabel = strftime('%x', $action['time']);
            $row['prev_action'] = "<a href=\"{$salesproject_url}#{$action['obj']->guid}\" class=\"task\">{$datelabel}: {$action['obj']->title}</a>";
            break;
    }

    $row['next_action'] = '';

    $action = $salesproject->next_action;
    switch ($action['type'])
    {
        case 'noaction':
            break;
        case 'event':
            $datelabel = strftime('%x %X', $action['time']);
            $row['next_action'] = "<a href=\"{$salesproject_url}#{$action['obj']->guid}\" class=\"event\">{$datelabel}: {$action['obj']->title}</a>";
            break;
        case 'task':
            $datelabel = strftime('%x', $action['time']);
            $row['next_action'] = "<a href=\"{$salesproject_url}#{$action['obj']->guid}\" class=\"task\">{$datelabel}: {$action['obj']->title}</a>";
            break;
    }
    $rows[] = $row;
}

echo '<script type="text/javascript">//<![CDATA[';
echo "\nvar " . $grid_id . '_entries = ' . json_encode($rows);
echo "\n//]]></script>";
?>
<div class="org_openpsa_sales <?php echo $data['list_title']; ?>">

<table id="&(grid_id);"></table>
<div id="p_&(grid_id);"></div>

</div>

<script type="text/javascript">
function jqgrid_&(grid_id);_resize()
{
    var new_width = jQuery("#gbox_&(grid_id);").parent().attr('clientWidth') - 5;
    var new_height = jQuery("#content").attr('clientHeight') - 220;

    try 
    {
        jQuery("#&(grid_id);").jqGrid().setGridWidth(new_width);
        jQuery("#&(grid_id);").jqGrid().setGridHeight(new_height);
    }
    catch (e){};
}

jQuery("#&(grid_id);").jqGrid({
      datatype: "local",
      data: &(grid_id);_entries,
      colNames: ['id', <?php
                 echo '"index_title", "' . $data['l10n']->get('title') . '",'; 
                 echo '"index_customer", "' . $data['l10n']->get('customer') . '",'; 
                 echo '"index_owner", "' . $data['l10n']->get('owner') . '",'; 
                 echo '"index_closeest", "' . $data['l10n']->get('estimated closing date') . '",'; 
                 echo '"index_value", "' . $data['l10n']->get('value') . '",'; 
                 if ($data['list_title'] == 'active')
                 {
                     echo '"' . $data['l10n']->get('probability') . '",';
                     echo '"index_weightedvalue", "' . $data['l10n']->get('weighted value') . '",';
                 }
                 echo '"index_profit", "' . $data['l10n']->get('profit') . '",'; 
                 echo '"' . $data['l10n']->get('previous action') . '",'; 
                 echo '"' . $data['l10n']->get('next action') . '"'; 
                ?>],
      colModel:[
          {name:'id', index:'id', hidden:true, key:true},
          {name:'index_title',index:'index_title', hidden:true},
          {name:'title', index: 'index_title', width: 100, classes: 'a-ellipsis title'},
          {name:'index_customer', index:'index_customer', hidden:true},
          {name:'customer', index: 'index_customer', width: 80, classes: 'a-ellipsis'},
          {name:'index_owner', index:'index_owner', hidden:true},
          {name:'owner', index: 'index_owner', width: 70},
          {name:'index_closeest',index:'index_closeest', sorttype: "integer", hidden: true},
          {name:'closeest', index: 'index_closeest', width: 65, align: 'center', fixed: true},
          {name:'index_value', index: 'index_value', sorttype: "float", hidden: true },
          {name:'value', index: 'index_value', width: 55, align: 'right', fixed: true},
          <?php if ($data['list_title'] == 'active') 
          { ?>
              {name:'probability', index: 'probability', width: 55, align: 'right'},
              {name:'index_weightedvalue', index: 'index_weightedvalue', sorttype: 'float', hidden: true},
              {name:'weightedvalue', index: 'index_weightedvalue', width: 55, align: 'right', fixed: true},
          <?php } ?>
          {name:'index_profit', index: 'index_profit', sorttype: "float", hidden: true },
          {name:'profit', index: 'index_profit', width: 55, align: 'right', fixed: true},
          {name:'prev_action', width: 75, align: 'center'},
          {name:'next_action', width: 75, align: 'center'}
      ],
      loadonce: true,
      rowNum: <?php echo sizeof($rows); ?>,
      scroll: 1
});

jqgrid_&(grid_id);_resize();

jQuery(window).resize(function()
{
    jqgrid_&(grid_id);_resize();
});

</script>

<?php
$host_prefix = $_MIDCOM->get_host_prefix();

$filename = $data['l10n']->get('salesprojects ' . $data['list_title']);
$filename .= '_' . date('Y_m_d');
$filename = preg_replace('/[^a-z0-9-]/i', '_', $filename);
?>

<form id="&(grid_id);_export" class="tab_escape" method="post" action="&(host_prefix);midcom-exec-org.openpsa.core/csv_export.php">
<input id="&(grid_id);_csvdata" type="hidden" value="" name="org_openpsa_export_csv_data" />
<input type="hidden" value="&(filename);.csv" name="org_openpsa_export_csv_filename" />
<input class="button tab_escape" type="submit" value="<?php echo $_MIDCOM->i18n->get_string('download as CSV', 'org.openpsa.core'); ?>" />
</form>

<script type="text/javascript">

org_openpsa_export_csv.add({
      id: '&(grid_id);',
      fields: {
          index_title: '<?php echo $data['l10n']->get('title'); ?>',
          index_customer: '<?php echo $data['l10n']->get('customer'); ?>',
          index_owner: '<?php echo $data['l10n']->get('owner'); ?>',
          closeest: '<?php echo $data['l10n']->get('estimated closing date'); ?>',
          index_value: '<?php echo $data['l10n']->get('value'); ?>',
          <?php if ($data['list_title'] == 'active')
          { ?>
              probability: '<?php echo $data['l10n']->get('probability'); ?>',
              index_weightedvalue: '<?php echo $data['l10n']->get('weighted value'); ?>',
          <?php } ?>
          index_profit: '<?php echo $data['l10n']->get('profit'); ?>',
          prev_action: '<?php echo $data['l10n']->get('previous action'); ?>',
          next_action: '<?php echo $data['l10n']->get('next action'); ?>'
        }
});

</script>
