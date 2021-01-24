<?php
$auth = midcom::get()->auth;
$logout_label = midcom::get()->i18n->get_string('logout', 'midcom');

if ($auth->user) {
    $siteconf = org_openpsa_core_siteconfig::get_instance();
    if ($user_url = $siteconf->get_node_full_url('org.openpsa.user')) {
        $person_string = '<a href="' . $user_url . 'view/' . $auth->user->guid . '/">' . $auth->user->name . "</a>";
    } else {
        $person_string = $auth->user->name;
    } ?>
    <ul>
        <li class="user">&(person_string:h);</li>
        <li class="logout"><a href="<?= midcom_connection::get_url('self') ?>midcom-logout-" title="&(logout_label);"><i class="fa fa-sign-out"></i></a></li>
        <li class="midgard"><div id="midcom_services_toolbars_minimizer"></div></li>
    </ul>
<?php
}
?>
