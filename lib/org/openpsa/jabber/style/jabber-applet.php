<?php
$user = $_MIDCOM->auth->user->get_storage();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN""http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="" lang="">
<html>
    <head>
        <title><?php echo $data['l10n']->get("instant messaging"); ?></title>
    </head>
    <body>
        <applet archive="<?php echo MIDCOM_STATIC_URL; ?>/org.openpsa.jabber/JabberApplet.jar" code="org/jabber/applet/JabberApplet.class" height="200" width="200" viewastext="viewastext">
            <param name="xmlhostname" value="<?php echo $_SERVER['SERVER_NAME']; ?>" />
            <param name="user" value="<?php echo $user->username; ?>" />
        </applet>
    </body>
</html>