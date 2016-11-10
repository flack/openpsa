<?php
if (count($data['persons']) > 0) {
    ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="<?php echo count($data['list_fields']) + 1; ?>">
                    <label for="select_all">
                        <input type="checkbox" name="select_all" id="select_all" value="" /> <?php echo $data['l10n']->get('select all'); ?>
                    </label>
                    <label for="invert_selection">
                        <input type="checkbox" name="invert_selection" id="invert_selection" value="" /> <?php echo $data['l10n']->get('invert selection'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td colspan="<?php echo count($data['list_fields']); ?>">
                    <select id="midcom_admin_user_action" name="midcom_admin_user_action">
                        <option value=""><?php echo $data['l10n']->get('choose action'); ?></option>
                        <?php
                        if ($data['config']->get('allow_manage_accounts')) {
                            ?>
                            <option value="removeaccount"><?php echo $data['l10n']->get('remove account'); ?></option>
                            <?php

                        } ?>
                        <option value="groupadd"><?php echo $data['l10n']->get('add to group'); ?></option>
                        <option value="passwords"><?php echo $data['l10n']->get('generate new passwords'); ?></option>
                    </select>
                    <select name="midcom_admin_user_group" id="midcom_admin_user_group" style="display: none;">
                        <?php
                        foreach ($data['groups_for_select'] as $group) {
                            $title = $group['title'];

                            if ($group['level'] > 0) {
                                $title = str_repeat('-', $group['level']) . '> ' . $group['title'];
                            }

                            echo "<option value=\"" . $group['id'] . "\">" . $title . "</option>\n";
                        } ?>
                    </select>
                    <input type="submit" value="<?php echo $data['l10n']->get('apply to selected'); ?>"/>
                </td>
            </tr>
        </tfoot>
    </table>
    </form>
    <?php

}
?>