<div class="area org_openpsa_helper_box">
    <h3><?php echo $data['l10n']->get("user account"); ?></h3>
    <?php
    if ($username = $data['account']->get_username()) {
        echo "<p>{$username}</p>\n";
        $user = new midcom_core_user($data['person']);
        if ($user->is_online() == 'online') {
            echo '<p>' . $data['l10n']->get('user is online') . "</p>\n";
        } elseif ($lastlogin = $user->get_last_login()) {
            $formatter = $data['l10n']->get_formatter();
            echo '<p>' . $data['l10n']->get('last login') . ': ' . $formatter->datetime($lastlogin) . "</p>\n";
        }

        $account_helper = new org_openpsa_user_accounthelper($data['person']);
        if ($account_helper->is_blocked()) {
            echo '<p>' . sprintf($data['l10n']->get('account blocked %s minutes'), $data['config']->get('password_block_timeframe_min')) . '</p>';
        }

        if (   $data['person']->guid == midcom::get()->auth->user->guid
            || midcom::get()->auth->can_user_do('org.openpsa.user:manage', class: org_openpsa_user_interface::class)) {
            $workflow = new midcom\workflow\datamanager;
            echo '<ul class="area_toolbar">';
            echo '<li><a class="button" href="' . $data['router']->generate('account_edit', ['guid' => $data['person']->guid]) . '" ' . $workflow->render_attributes() . '>' . $data['l10n_midcom']->get('edit') . "</a></li>\n";
            $workflow = new midcom\workflow\delete([
                'object' => $data['person'],
                'label' => $data['l10n']->get('account')
            ]);
            echo '<li><a href="' . $data['router']->generate('account_delete', ['guid' => $data['person']->guid]). '" ' . $workflow->render_attributes() . ' class="button">';
            echo '<span class="toolbar_label">' . $data['l10n_midcom']->get('delete') . '</span></a></li>';
            if (    midcom::get()->config->get('auth_allow_trusted') === true
                 && $data['person']->can_do('org.openpsa.user:su')) {
                 echo '<li><a class="button" href="' . $data['router']->generate('account_su', ['guid' => $data['person']->guid]) . '">' . $data['l10n']->get('switch to user') . "</a></li>\n";
            }
            echo "</ul>\n";
        }
    } else {
        echo '<p><span class="metadata">' . $data['l10n']->get("no account") . '</span></p>';
        if (   $data['person']->guid == midcom::get()->auth->user->guid
            || midcom::get()->auth->can_user_do('org.openpsa.user:manage', class: org_openpsa_user_interface::class)) {
            $workflow = new midcom\workflow\datamanager;
            echo '<ul class="area_toolbar">';
            echo '<li><a class="button" href="' . $data['router']->generate('account_create', ['guid' => $data['person']->guid]) . '" ' . $workflow->render_attributes() . '>' . $data['l10n']->get('create account') . "</a></li>\n";
            echo "</ul>\n";
        }
    }
    ?>
</div>