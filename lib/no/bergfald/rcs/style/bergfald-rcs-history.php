<?php
$history = $data['history'];
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
$guid = $data['guid'];

echo "<h1>{$data['view_title']}</h1>\n";

if (count($history) == 0)
{
   echo $data['l10n']->get('no revisions exist');
}
else
{
    ?>
    <form name="no_bergfald_rcs_history" action="" >
        <table>
            <thead>
                <tr>
                    <th><?php echo $data['l10n']->get('revision'); ?></th>
                    <th><?php echo $data['l10n']->get('date'); ?></th>
                    <th><?php echo $data['l10n']->get('user'); ?></th>
                    <th><?php echo $data['l10n']->get('lines'); ?></th>
                    <th><?php echo $data['l10n']->get('message'); ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php
            $formatter = $data['l10n']->get_formatter();
            foreach ($history as $rev => $history)
            {
                echo "                <tr>\n";
                echo "                    <td><a href='{$prefix}__ais/rcs/preview/$guid/$rev'>{$rev}</a></td>\n";
                echo "                    <td>" . $formatter->datetime($history['date']) . "</td>\n";

                if ($history['user'])
                {
                    $user = midcom::get()->auth->get_user($history['user']);
                    if (is_object($user))
                    {
                        $person_label = $user->get_storage()->name;
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