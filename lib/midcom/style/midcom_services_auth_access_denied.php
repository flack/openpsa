<?php
$message = $this->data['midcom_services_auth_access_denied_message'];
$title = $this->data['midcom_services_auth_access_denied_title'];
$login_warning = $this->data['midcom_services_auth_access_denied_login_warning'];

midcom::get()->head->add_stylesheet(MIDCOM_STATIC_URL.'/midcom.services.auth/style.css');
?>
<!DOCTYPE html>
<html lang="<?php echo midcom::get()->i18n->get_current_language(); ?>">
    <head>
        <meta charset="UTF-8">
        <title><?php echo $title; ?></title>
        <?php midcom::get()->head->print_head_elements(); ?>
    </head>

    <body onload="self.focus();document.midcom_services_auth_frontend_form.username.focus();">
        <div id="container">
            <div id="branding">
                <div id="title"><h1>Midgard CMS</h1><h2><?php echo $title; ?></h2></div>
                <div id="grouplogo"><a href="http://midgard-project.org/"><img src="<?php echo MIDCOM_STATIC_URL; ?>/stock-icons/logos/midgard-bubble-104x104.png" width="104" height="104" /></a></div>
            </div>
            <div class="clear"></div>
            <div id="content">
                <div id="login">
                    <?php
                    midcom::get()->auth->show_login_form();
                    ?>
                    <div class="clear"></div>
                </div>

                <div id="error"><?php echo "<div>{$login_warning}</div><div>{$message}</div>"; ?></div>
            </div>

            <div id="bottom">
            </div>

            <div id="footer">
                <div class="midgard">
                    Copyright &copy; 1998-2012 <a href="http://midgard-project.org/">The Midgard Project</a>. Midgard is <a href="https://en.wikipedia.org/wiki/Free_software">free software</a> available under <a href="https://www.gnu.org/licenses/lgpl.html">GNU Lesser General Public License</a>.
                </div>
            </div>
        </div>
    </body>
</html>
