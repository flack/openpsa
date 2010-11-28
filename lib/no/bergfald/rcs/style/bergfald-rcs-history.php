<?php
$history = $data['history'];
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
$guid = $data['guid'];

echo "<h1>{$data['view_title']}</h1>\n";

if (count($history) == 0)
{
   echo $data['l10n']->get('No revisions exist.');
}
else
{
    ?>
    <form name="no_bergfald_rcs_history" action="" >
        <table>
            <thead>
                <tr>
                    <th><?php echo $_MIDCOM->i18n->get_string('revision', 'no.bergfald.rcs'); ?></th>
                    <th><?php echo $_MIDCOM->i18n->get_string('date', 'no.bergfald.rcs'); ?></th>
                    <th><?php echo $_MIDCOM->i18n->get_string('user', 'no.bergfald.rcs'); ?></th>
                    <th><?php echo $_MIDCOM->i18n->get_string('lines', 'no.bergfald.rcs'); ?></th>
                    <th><?php echo $_MIDCOM->i18n->get_string('message', 'no.bergfald.rcs'); ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php
            foreach ($history as $rev => $history)
            {
                echo "                <tr>\n";
                echo "                    <td><a href='{$prefix}__ais/rcs/preview/$guid/$rev'>{$rev}</a></td>\n";
                echo "                    <td>".strftime('%x %X Z', $history['date'])."</td>\n";

                if ($history['user'])
                {
                    $user = $_MIDCOM->auth->get_user($history['user']);
                    if(is_object($user))
                    {
                        $person = $user->get_storage();
                        if ($_MIDCOM->load_library('org.openpsa.contactwidget'))
                        {
                            $user_card = new org_openpsa_contactwidget($person);
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
                echo "                    <td></td>\n";
                echo "                </tr>\n";
            }
            ?>
            </tbody>
        </table>
    </form>
    <?php
}
?>