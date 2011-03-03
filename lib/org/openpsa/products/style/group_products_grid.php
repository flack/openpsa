<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
$entries = array();

$grid_id = 'group_products_grid';

foreach ($data['products'] as $product)
{
    if (! $data['datamanager_product']->autoset_storage($product))
    {
        debug_add("The datamanager for product #{$product->id} could not be initialized, skipping it.");
        debug_print_r('Object was:', $product);
        continue;
    }

    $view_product = $data['datamanager_product']->get_content_html();

    $entry = array();

    $path = $product->get_path();
    $link_html = "<a href='{$prefix}product/{$path}/'>";
    $next_marker = false;

    $entry['id'] = $product->id;
    $entry['index_code'] = $product->code;
    $entry['code'] = $link_html . $product->code . '</a>';
    $entry['index_title'] = $product->title;
    $entry['title'] = $link_html . $product->title . '</a>';
    $entry['orgOpenpsaObtype'] = $view_product['orgOpenpsaObtype'];
    $entry['delivery'] = $view_product['delivery'];
    $entry['index_price'] = $product->price;
    $entry['price'] = org_openpsa_helpers::format_number($product->price);
    $entry['unit'] = $view_product['unit'];


    $entries[] = $entry;
}
echo '<script type="text/javascript">//<![CDATA[';
echo "\nvar " . $grid_id . '_entries = ' . json_encode($entries);
echo "\n//]]></script>";
?>

<div class="org_openpsa_products <?php echo $classes ?> full-width">

<table id="&(grid_id);"></table>
<div id="p_&(grid_id);"></div>

</div>

<script type="text/javascript">
jQuery("#&(grid_id);").jqGrid({
      datatype: "local",
      data: &(grid_id);_entries,
      colNames: ['id', 'index_code', <?php
                 echo '"' . $data['l10n']->get('code') . '",';
                 echo '"index_title", "' . $data['l10n_midcom']->get('title') . '",';
                 echo '"' . $data['l10n']->get('type') . '",';
                 echo '"' . $data['l10n']->get('delivery type') . '",';
                 echo '"index_price", "' . $data['l10n']->get('price') . '",';
                 echo '"' . $data['l10n']->get('unit') . '"';
      ?>],
      colModel:[
          {name:'id', index:'id', hidden:true, key:true},
          {name:'index_code', index:'index_code', hidden:true},
          {name:'code', index: 'index_code', width: 80, fixed: true},
          {name:'index_title', index:'index_title', hidden:true},
          {name:'title', index: 'index_title', classes: 'title'},
          {name:'orgOpenpsaObtype', index:'orgOpenpsaObtype', width: 130, fixed: true},
          {name:'delivery', index:'delivery', width: 130, fixed: true},
          {name:'index_price', index:'index_price', sorttype: 'number', hidden: true},
          {name:'price', index: 'index_price', align: 'right', width: 70, fixed: true},
          {name:'unit', index:'unit', width: 70, fixed: true}
      ],
      loadonce: true,
      rowNum: <?php echo sizeof($entries); ?>,
});

jQuery("#&(grid_id);").jqGrid('footerData', 'set', <?php echo json_encode($footer_data); ?>);

</script>
<?php
if (method_exists($data['products_qb'], 'show_pages'))
{
    $data['products_qb']->show_pages();
}
?>