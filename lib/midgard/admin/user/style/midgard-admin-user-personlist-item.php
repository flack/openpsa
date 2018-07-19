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
            $value = '<a href="' . $data['router']->generate('user_edit', ['guid' => $data['person']->guid]) . '">' . $value . '</a>';
            $linked++;
        }
        echo "<td>{$value}</td>\n";
    }

    $qb = midcom_db_group::new_query_builder();
    $qb->get_doctrine()
        ->leftJoin('midgard_member', 'm', Join::WITH, 'm.gid = c.id')
        ->where('m.uid = :person')
        ->setParameter('person', $data['person']->id);

    $groups = [];
    foreach ($qb->execute() as $group) {
        $value = $group->get_label();
        if ($group->can_do('midgard:update')) {
            $value = '<a href="' . $data['router']->generate('group_edit', ['guid' => $group->guid]) . '">' . $value . '</a>';
        }
        $groups[] = $value;
    }
    echo "<td>" . implode(', ', $groups) . "</td>\n";
    ?>
</tr>