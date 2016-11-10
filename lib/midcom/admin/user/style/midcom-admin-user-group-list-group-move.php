<?php
$constraint = '';
$disabled = false;

if ($data['disabled']) {
    $constraint = ' disabled="disabled"';
    $disabled = true;
}

if ($data['id'] === $data['group']->owner) {
    $constraint = ' selected="selected"';
}
?>
    <li>
        <label for="midcom_admin_user_move_group_&(data['guid']);">
            <input type="radio" id="midcom_admin_user_move_group_&(data['guid']);" name="midcom_admin_user_move_group" value="&(data['id']:h);"&(constraint:h); />
            &(data['title']);
        </label>
<?php
midcom_admin_user_handler_group_list::list_groups($data['id'], $data, true);
?>
    </li>
