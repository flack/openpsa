<form method="post" action="" id="midcom_admin_folder_order_form_sort_type" class="datamanager datamanager2 midcom_admin_folder sort_folder">
    <div class="wrapper">
        <label for="midcom_admin_folder_order_navorder">
        <?php echo $data['l10n']->get('select the ordering method first to sort the pages'); ?>
            <select name="f_navorder" id="midcom_admin_folder_order_navorder">
<?php
foreach ($data['navorder_list'] as $key => $value) {
    $selected = ($key == $data['navorder']) ? ' selected="selected"' : ''; ?>
                <option value="&(key);"&(selected:h);>&(value:h);</option>
<?php
}
?>
            </select>
        </label>
    </div>
</form>
<form method="post" action="" id="midcom_admin_folder_order_form" class="datamanager datamanager2 midcom_admin_folder sort_items">
    <div id="midcom_admin_folder_order_form_wrapper">
