<tr<?php if ($data['even']) { echo ' class="even"'; } ?>>
    <?php
    $checked = '';
    if (isset($_POST['midcom_admin_user'])
        && is_array($_POST['midcom_admin_user'])
        && in_array($data['person']->id, $_POST['midcom_admin_user']))
    {
        $checked = ' checked="checked"';
    }

    if (!$data['person']->can_do('midgard:update'))
    {
        $checked .= ' disabled="disabled"';
    }
    else
    {
        $data['enabled']++;
    }
    ?>
    <td><input type="checkbox" name="midcom_admin_user[]" value="<?php echo $data['person']->id; ?>" <?php echo $checked; ?>/></td>
    <?php
    $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
    $linked = 0;
    foreach ($data['list_fields'] as $field)
    {
        $value = $data['person']->$field;
        if (   $linked < 2
            && $data['person']->can_do('midgard:update'))
        {
            if (!$value)
            {
                $value = "&lt;{$field}&gt;";
            }
            $value = "<a href=\"{$prefix}__mfa/asgard_midcom.admin.user/edit/{$data['person']->guid}/\">{$value}</a>";
            $linked++;
        }
        echo "<td>{$value}</td>\n";
    }

    $qb = midcom_db_member::new_query_builder();
    $qb->add_constraint('uid', '=', $data['person']->id);
    $memberships = $qb->execute();
    $groups = array();
    foreach ($memberships as $member)
    {
        // Quick and dirty on-demand group-loading
        if (   $member->gid != 0
            && (   !isset($data['groups'][$member->gid])
                || !is_object($data['groups'][$member->gid]))
            )
        {
            $data['groups'][$member->gid] = new midcom_db_group((int)$member->gid);
        }
        if (   !isset($data['groups'][$member->gid])
            || !is_object($data['groups'][$member->gid]))
        {
            if ($member->gid == 0)
            {
                $groups[] = 'Midgard Administrators';
            }
            else
            {
                $groups[] = "#{$member->gid}";
            }
            continue;
        }

        $value = $data['groups'][$member->gid]->official;
        if ($data['groups'][$member->gid]->can_do('midgard:update'))
        {
            $value = "<a href=\"{$prefix}__mfa/asgard_midcom.admin.user/group/edit/{$data['groups'][$member->gid]->guid}/\">{$value}</a>";
        }
        $groups[] = $value;
    }
    echo "<td>" . implode(', ', $groups) . "</td>\n";
    ?>
</tr>