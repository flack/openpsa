<?php
$history = $data['history'];
$guid = $data['guid'];

echo "<h1>{$data['view_title']}</h1>\n";

if (count($history) == 0) {
    echo $data['l10n']->get('no revisions exist');
} else {
    ?>
    <form name="midcom_admin_rcs_history" action="" >
        <table>
            <thead>
                <tr>
                    <th colspan="2"></th>
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
    foreach ($history as $rev => $history) {
        echo "                <tr>\n";
        echo "                    <td><input type=\"radio\" name=\"first\" value=\"{$rev}\" />\n";
        echo "                    <td><input type=\"radio\" name=\"last\" value=\"{$rev}\" />\n";
        echo "                    <td><a href='" . $data['router']->generate('preview', ['guid' => $guid, 'revision' => $rev]) . "'>{$rev}</a></td>\n";
        echo "                    <td>" . $formatter->datetime($history['date']) . "</td>\n";

        if (   $history['user']
            && $user = midcom::get()->auth->get_user($history['user'])) {
            $person_label = $user->get_storage()->name;
            echo "                    <td>{$person_label}</td>\n";
        } elseif ($history['ip']) {
            echo "                    <td>{$history['ip']}</td>\n";
        } else {
            echo "                    <td></td>\n";
        }
        echo "                    <td>{$history['lines']}</td>\n";
        echo "                    <td>{$history['message']}</td>\n";
        echo "                    <td></td>\n";
        echo "                </tr>\n";
    } ?>
            </tbody>
        </table>
        <input type="submit" name="f_compare" value="<?php echo $data['l10n']->get('show differences'); ?>" />
    </form>
    <?php
}
?>