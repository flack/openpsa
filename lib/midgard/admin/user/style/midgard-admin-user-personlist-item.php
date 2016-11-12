<tr>
    <?php
    $checked = '';
    if (isset($_POST['midgard_admin_user'])
        && is_array($_POST['midgard_admin_user'])
        && in_array($data['person']->guid, $_POST['midgard_admin_user'])) {
        $checked = ' checked="checked"';
    }

    if (!$data['person']->can_do('midgard:update')) {
        $checked .= ' disabled="disabled"';
    }
    ?>
    <td><input type="checkbox" name="midgard_admin_user[]" value="<?php echo $data['person']->guid; ?>" <?php echo $checked; ?>/></td>
    <?php
    $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
    $linked = 0;
    foreach ($data['list_fields'] as $field) {
        $value = $data['person']->$field;
        if ($field == 'username') {
            $account = new midcom_core_account($data['person']);
            $value = $account->get_username();
        }
        if ($linked < 2 && $data['person']->can_do('midgard:update')) {
            if (!$value) {
                $value = '&lt;' . $data['l10n']->get($field) . '&gt;';
            }
            $value = "<a href=\"{$prefix}__mfa/asgard_midgard.admin.user/edit/{$data['person']->guid}/\">{$value}</a>";
            $linked++;
        }
        echo "<td>{$value}</td>\n";
    }

    $qb = midcom_db_member::new_query_builder();
    $qb->add_constraint('uid', '=', $data['person']->id);
    $memberships = $qb->execute();
    $groups = array();
    foreach ($memberships as $member) {
        if ($member->gid == 0) {
            $groups[] = 'Midgard Administrators';
            continue;
        }
        try {
            $group = midcom_db_group::get_cached($member->gid);
            $value = $group->get_label();
            if ($group->can_do('midgard:update')) {
                $value = "<a href=\"{$prefix}__mfa/asgard_midgard.admin.user/group/edit/{$group->guid}/\">{$value}</a>";
            }
            $groups[] = $value;
        } catch (midcom_error $e) {
            $groups[] = "#{$member->gid}";
        }
    }
    echo "<td>" . implode(', ', $groups) . "</td>\n";
    ?>
</tr>