<p><?php echo $data['l10n']->get('passwords will be sent to the following users'); ?>:</p>
<table>
    <thead>
        <tr>
            <th><?php echo $data['l10n']->get('firstname'); ?></th>
            <th><?php echo $data['l10n']->get('lastname'); ?></th>
            <th><?php echo $data['l10n']->get('email'); ?></th>
        </tr>
    </thead>
    <tbody>
<?php
if (isset($_REQUEST['midcom_admin_user']))
{
    foreach ($_REQUEST['midcom_admin_user'] as $id)
    {
        try
        {
            $person = new midcom_db_person($id);
        }
        catch (midcom_error $e)
        {
            continue;
        }

        echo "        <tr>\n";
        echo "            <td>{$person->firstname}</td>\n";
        echo "            <td>{$person->lastname}</td>\n";
        echo "            <td>{$person->email} <input type=\"hidden\" name=\"midcom_admin_user[]\" value=\"{$id}\" /></td>\n";
        echo "        </tr>\n";
    }
}
?>
    </tbody>
</table>
</div>
<div class="form_toolbar">
    <input type="submit" name="f_process" value="<?php echo $data['l10n']->get('change passwords'); ?>" class="save" />
    <input type="submit" name="f_cancel" value="<?php echo $_MIDCOM->i18n->get_string('cancel', 'midcom'); ?>" class="cancel" />
</div>
</form>