<?php
$message = $this->data['midcom_services_auth_access_denied_message'];
$title = $this->data['midcom_services_auth_access_denied_title'];
$login_warning = $this->data['midcom_services_auth_access_denied_login_warning'];

$_MIDCOM->add_stylesheet(MIDCOM_STATIC_URL.'/midcom.services.auth/style.css');
echo '<?'.'xml version="1.0" encoding="ISO-8859-1"?'.">\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
    <head>
        <title><?php echo $title; ?></title>
        <?php echo $_MIDCOM->print_head_elements(); ?>
    </head>

    <body onload="self.focus();document.midcom_services_auth_frontend_form.username.focus();">
        <div id="container">
            <div id="branding">
                <div id="title"><h1>Midgard CMS</h1><h2><?php echo $title; ?></h2></div>
                <div id="grouplogo"><a href="http://www.midgard-project.org/"><img src="<?php echo MIDCOM_STATIC_URL; ?>/stock-icons/logos/midgard-bubble-104x104.gif" width="104" height="104" /></a></div>
            </div>
            <div class="clear"></div>
            <div id="content">
                <div id="login">
                    <?php
                    midcom::get('auth')->show_login_form();
                    ?>
                    <div class="clear"></div>
                </div>

                <div id="error"><?php echo "<div>{$login_warning}</div><div>{$message}</div>"; ?></div>
            </div>

            <div id="bottom">
                <div id="version">Midgard <?php echo substr(mgd_version(), 0, 4); ?></div>
            </div>

            <div id="footer">
                <div class="midgard">
                    Copyright &copy; 1998-2008 <a href="http://www.midgard-project.org/">The Midgard Project</a>. Midgard is <a href="http://en.wikipedia.org/wiki/Free_software">free software</a> available under <a href="http://www.gnu.org/licenses/lgpl.html">GNU Lesser General Public License</a>.
                </div>
            </div>
    </body>
</html>
