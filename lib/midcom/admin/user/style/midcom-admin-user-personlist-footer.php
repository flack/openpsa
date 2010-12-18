<?php
if (count($data['persons']) > 0)
{
    if ($data['enabled'] == 0)
    {
        $disabled = ' disabled="disabled"';
    }
    else
    {
        $disabled = '';
    }
    ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="<?php echo count($data['list_fields']) + 1; ?>">
                    <label for="select_all">
                        <input type="checkbox" name="select_all" id="select_all" value="" onclick="jQuery(this).check_all('#midcom_admin_user_batch_process table tbody');" /> <?php echo $_MIDCOM->i18n->get_string('select all', 'midcom.admin.user'); ?>
                    </label>
                    <label for="invert_selection">
                        <input type="checkbox" name="invert_selection" id="invert_selection" value="" onclick="jQuery(this).invert_selection('#midcom_admin_user_batch_process table tbody');" /> <?php echo $_MIDCOM->i18n->get_string('invert selection', 'midcom.admin.user'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td colspan="<?php echo count($data['list_fields']); ?>">
                    <select id="midcom_admin_user_action" name="midcom_admin_user_action"<?php echo $disabled; ?>>
                        <option value=""><?php echo $_MIDCOM->i18n->get_string('choose action', 'midcom.admin.user'); ?></option>
                        <?php
                        if ($data['config']->get('allow_manage_accounts'))
                        {
                            ?>
                            <option value="removeaccount"><?php echo $_MIDCOM->i18n->get_string('remove account', 'midcom.admin.user'); ?></option>
                            <?php
                        }
                        ?>
                        <option value="groupadd"><?php echo $_MIDCOM->i18n->get_string('add to group', 'midcom.admin.user'); ?></option>
                        <option value="passwords"><?php echo $_MIDCOM->i18n->get_string('generate new passwords', 'midcom.admin.user'); ?></option>
                    </select>
                    <select name="midcom_admin_user_group" id="midcom_admin_user_group" style="display: none;"<?php echo $disabled; ?>>
                        <?php
                        foreach ($data['groups_for_select'] as $group)
                        {
                            if (!is_array($group))
                            {
                                continue;
                            }

                            $level_indent = '';
                            for($i = 0;$i < $group['level']; $i++)
                            {
                                $level_indent = $level_indent . '-';
                            }

                            if ($level_indent != '')
                            {
                                $title = $level_indent . '> ' . $group['title'];
                            }
                            else
                            {
                                $title = $group['title'];
                            }


                            echo "<option value=\"" . $group['id'] . "\">" . $title . "</option>\n";
                        }
                        ?>
                    </select>
                    <input type="submit" value="<?php echo $_MIDCOM->i18n->get_string('apply to selected', 'midcom.admin.user'); ?>"<?php echo $disabled; ?> />
                </td>
            </tr>
        </tfoot>
    </table>
    </form>
    <?php
}
?>