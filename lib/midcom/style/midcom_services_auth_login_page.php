<?php
$title = $this->data['midcom_services_auth_show_login_page_title'];
$login_warning = $this->data['midcom_services_auth_show_login_page_login_warning'];

midcom::get()->head->add_stylesheet(MIDCOM_STATIC_URL.'/midcom.services.auth/style.css');
?>
<!DOCTYPE html>
<html lang="<?php echo midcom::get()->i18n->get_current_language(); ?>">
    <head>
        <meta charset="UTF-8">
        <title><?php echo $title; ?></title>
        <?php echo midcom::get()->head->print_head_elements(); ?>
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
                <?php
                if ($login_warning == '') {
                    echo "<div id=\"ok\">" . midcom::get()->i18n->get_string('login message - please enter credentials', 'midcom') . "</div>\n";
                } else {
                    echo "<div id=\"error\">{$login_warning}</div>\n";
                }
                ?>
            </div>

            <div id="bottom">
            </div>

            <div id="footer">
                <div class="midgard">
                    Copyright &copy; 1998-2012 <a href="http://midgard-project.org/">The Midgard Project</a>. Midgard is <a href="http://en.wikipedia.org/wiki/Free_software">free software</a> available under <a href="http://www.gnu.org/licenses/lgpl.html">GNU Lesser General Public License</a>.
                </div>
            </div>
        </div>
    </body>
    <?php
    midcom::get()->uimessages->show();
    ?>
</html>
