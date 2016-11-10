<h1><?php echo $data['title']; ?></h1>
<form method="post" action="<?php echo midcom_connection::get_url('uri'); ?>" id="midcom_admin_folder_order_form_sort_type" class="datamanager datamanager2 midcom_admin_folder sort_folder">
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
        <div class="form_toolbar">
            <input class="save" type="submit" accesskey="s" name="f_submit" value="<?php echo $data['l10n_midcom']->get('save'); ?>" />
            <input class="cancel" type="submit" accesskey="c" name="f_cancel" value="<?php echo $data['l10n_midcom']->get('cancel'); ?>" />
        </div>
    </div>
</form>
<form method="post" action="<?php echo midcom_connection::get_url('uri'); ?>" id="midcom_admin_folder_order_form" class="datamanager datamanager2 midcom_admin_folder sort_items">
    <div id="midcom_admin_folder_order_form_wrapper">
