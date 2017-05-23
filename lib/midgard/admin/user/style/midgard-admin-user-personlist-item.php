<?php
use Doctrine\ORM\Query\Expr\Join;
?>
<tr>
    <?php
    $disabled = '';
    if (!$data['person']->can_do('midgard:update')) {
        $disabled .= ' disabled="disabled"';
    }
    ?>
    <td><input type="checkbox" name="midgard_admin_user[]" value="<?php echo $data['person']->guid; ?>" <?php echo $disabled; ?>/></td>
    <?php
    $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
    $linked = 0;
    foreach ($data['list_fields'] as $field) {
        if ($field == 'username') {
            $account = new midcom_core_account($data['person']);
            $value = $account->get_username();
        } else {
            $value = $data['person']->$field;
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

    $qb = midcom_db_group::new_query_builder();
    $qb->get_doctrine()
        ->leftJoin('midgard_member', 'm', Join::WITH, 'm.gid = c.id')
        ->where('m.uid = :person')
        ->setParameter('person', $data['person']->id);

    $memberships = $qb->execute();
    $groups = array();
    foreach ($memberships as $group) {
        $value = $group->get_label();
        if ($group->can_do('midgard:update')) {
            $value = "<a href=\"{$prefix}__mfa/asgard_midgard.admin.user/group/edit/{$group->guid}/\">{$value}</a>";
        }
        $groups[] = $value;
    }
    echo "<td>" . implode(', ', $groups) . "</td>\n";
    ?>
</tr>