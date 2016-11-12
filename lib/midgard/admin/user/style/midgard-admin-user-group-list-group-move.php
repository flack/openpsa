<?php
$constraint = '';

if ($data['disabled']) {
    $constraint = ' disabled="disabled"';
}

if ($data['id'] === $data['group']->owner) {
    $constraint = ' selected="selected"';
}
?>
    <li>
        <label for="midgard_admin_user_move_group_&(data['guid']);">
            <input type="radio" id="midgard_admin_user_move_group_&(data['guid']);" name="midgard_admin_user_move_group" value="&(data['id']:h);"&(constraint:h); />
            &(data['title']);
        </label>
<?php
midgard_admin_user_handler_group_list::list_groups($data['id'], $data, true);
?>
    </li>
