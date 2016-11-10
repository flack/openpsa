<?php
$data = $this->data;
?>
<form name="midcom_services_auth_frontend_form" method='post' id="midcom_services_auth_frontend_form">
    <p>
        <label for="username">
            <span class="field_label"><?php echo midcom::get()->i18n->get_string('username', 'midcom'); ?></span><br />
            <input name="username" id="username" type="text" class="input" />
        </label>
    </p>
    <p>
        <label for="password">
            <span class="field_label"><?php echo midcom::get()->i18n->get_string('password', 'midcom'); ?></span><br />
            <input name="password" id="password" type="password" class="input" />
        </label>
    </p>
<?php
if (!empty($data['restored_form_data'])) {
    foreach ($data['restored_form_data'] as $key => $value) {
        echo "                <input type=\"hidden\" name=\"restored_form_data[{$key}]\" value=\"{$value}\" />\n";
    }

    echo "                <p>\n";
    echo "                    <label for=\"restore_form_data\" class=\"checkbox\">\n";
    echo "                        <input name=\"restore_form_data\" id=\"restore_form_data\" type=\"checkbox\" value=\"1\" checked=\"checked\" class=\"checkbox\" />\n";
    echo "                        " . midcom::get()->i18n->get_string('restore submitted form data', 'midcom') . "?\n";
    echo "                    </label>\n";
    echo "                </p>\n";
}
?>
    <div class="clear">
      <input type="submit" name="midcom_services_auth_frontend_form_submit" id="midcom_services_auth_frontend_form_submit" value="<?php
        echo midcom::get()->i18n->get_string('login', 'midcom'); ?>" />
    </div>
</form>
<?php
if (midcom::get()->config->get('auth_openid_enable')) {
            midcom::get()->componentloader->load_library('net.nemein.openid');
            $url = midcom::get()->get_host_prefix() . 'midcom-exec-net.nemein.openid/initiate.php'; ?>
    <!--<h3><?php echo midcom::get()->i18n->get_string('login using openid', 'net.nemein.openid'); ?></h3>-->

    <div id="open_id_form">
        <form action="<?php echo $url; ?>" method="post">
            <label for="openid_url">
                <p><?php echo midcom::get()->i18n->get_string('openid url', 'net.nemein.openid'); ?></p>
                <input name="openid_url" id="openid_url" type="text" class="input" value="http://" />
            </label>
            <!--
            <p class="helptext">
              OpenID lets you safely sign in to different websites with a single password. <a href="https://www.myopenid.com/affiliate_signup?affiliate_id=17">Get an OpenID</a>.
            </p>
            -->
            <input type="submit" name="midcom_services_auth_frontend_form_submit" id="openid_submit" value="<?php
                echo midcom::get()->i18n->get_string('login', 'midcom'); ?>" />
        </form>
    </div>
<?php 
        } ?>