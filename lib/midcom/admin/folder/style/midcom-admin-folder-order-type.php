<div id="midcom_admin_folder_order_type_&(data['navigation_type']);">
    <ul id="midcom_admin_folder_order_type_list_&(data['navigation_type']);" class="sortable &(data['navigation_type']);">
<?php
$count = count($data['navigation_items']);

foreach ($data['navigation_items'] as $i => $item) {
    if (   isset($item[MIDCOM_NAV_SORTABLE])
        && !$item[MIDCOM_NAV_SORTABLE]) {
        continue;
    }

    $identifier = $item[MIDCOM_NAV_GUID] ?: $item[MIDCOM_NAV_ID];

    $index = $count - $i;
    $style = '';

    // Skip all components that return the default icon
    if (   isset($item[MIDCOM_NAV_COMPONENT])
        && ($tmp = midcom::get()->componentloader->get_component_icon($item[MIDCOM_NAV_COMPONENT], false))) {
        $icon = '<i class="fa fa-' . $tmp . '"></i>';
    } elseif (!$item[MIDCOM_NAV_GUID]) {
        $icon = '<i class="fa fa-code"></i>';
    } else {
        // Get the icon from corresponding reflector class
        $icon = midcom_helper_reflector::get_object_icon($item[MIDCOM_NAV_OBJECT]);
    }

    echo "        <li class=\"sortable {$item[MIDCOM_NAV_TYPE]}\">\n";
    echo "            <input type=\"hidden\" name=\"sortable[{$item[MIDCOM_NAV_TYPE]}][{$identifier}]\" value=\"{$index}\" />\n";
    echo "            {$icon} {$item[MIDCOM_NAV_NAME]}\n";
    echo "        </li>\n";
}
?>
    </ul>
</div>
<input type="hidden" name="f_navorder" value="&(data['navorder']);">
<script type="text/javascript">
    // <!--
        jQuery('#midcom_admin_folder_order_type_list_&(data['navigation_type']);')
            .sortable({
                containment: '#midcom_admin_folder_order_type_list_&(data['navigation_type']);'
            });
    // -->
</script>
