<?php
$history = $data['history'];
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
$guid = $data['guid'];

if (count($history) == 0)
{
   echo $data['l10n']->get('no revisions exist');
   return;
}
?>
<div class="rcs_navigation">
<?php
echo $data['rcs_toolbar']->render();
?>
</div>
<form method="get" action="<?php echo midcom_connection::get_url('uri'); ?>" id="midgard_admin_asgard_rcs_version_compare">
    <div>
        <table>
            <thead>
                <tr>
                    <th></th>
                    <th><?php echo $data['l10n']->get('revision'); ?></th>
                    <th><?php echo $data['l10n']->get('date'); ?></th>
                    <th><?php echo $data['l10n']->get('user'); ?></th>
                    <th><?php echo $data['l10n']->get('lines'); ?></th>
                    <th><?php echo $data['l10n']->get('message'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php
            $i = 0;

            foreach ($history as $rev => $history)
            {
                $i++;
                echo "                <tr id=\"midgard_admin_asgard_rcs_version_compare_{$i}_row\">\n";
                echo "                    <td><input id=\"midgard_admin_asgard_rcs_version_compare_{$i}\" type=\"checkbox\" name=\"compare[]\" value=\"{$rev}\" />\n";
                echo "                    <td><span style=\"display: none;\">". substr($rev, 2) ."</span><a href='{$prefix}__mfa/asgard/object/rcs/preview/$guid/$rev'>{$rev}</a></td>\n";
                echo "                    <td><span style=\"display: none;\">{$history['date']}</span>".strftime('%x %X Z', $history['date'])."</td>\n";

                if ($history['user'])
                {
                    $user = midcom::get('auth')->get_user($history['user']);
                    if(is_object($user))
                    {
                        $person = $user->get_storage();
                        if ($_MIDCOM->load_library('org.openpsa.widgets'))
                        {
                            $user_card = new org_openpsa_widgets_contact($person);
                            $person_label = $user_card->show_inline();
                        }
                        else
                        {
                            $person_label = $person->name;
                        }
                        echo "                    <td>{$person_label}</td>\n";
                    }
                    elseif ($history['ip'])
                    {
                        echo "                    <td>{$history['ip']}</td>\n";
                    }
                    else
                    {
                        echo "                    <td></td>\n";
                    }
                }
                elseif ($history['ip'])
                {
                    echo "                    <td>{$history['ip']}</td>\n";
                }
                else
                {
                    echo "                    <td></td>\n";
                }
                echo "                    <td>{$history['lines']}</td>\n";
                echo "                    <td>{$history['message']}</td>\n";
                echo "                </tr>\n";
            }
            ?>
            </tbody>
        </table>
        <input type="submit" name="f_compare" value="<?php echo $data['l10n']->get('compare'); ?>" />
    </div>
</form>
<script type="text/javascript">
    var _l10n_select_two = '<?php echo $data['l10n']->get('select exactly two choices'); ?>';
</script>