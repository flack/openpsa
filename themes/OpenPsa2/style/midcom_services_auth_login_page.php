<?php
$message = $_MIDCOM->i18n->get_string('login message - please enter credentials', 'midcom');
$login_warning = '';
$title = $_MIDCOM->i18n->get_string('login', 'midcom');

if (isset($GLOBALS['midcom_services_auth_access_denied_message']))
{
    $message = $GLOBALS['midcom_services_auth_access_denied_message'];
    $title = $GLOBALS['midcom_services_auth_access_denied_title'];
    $login_warning = $GLOBALS['midcom_services_auth_access_denied_login_warning'];
}
else
{
    $login_warning = $GLOBALS['midcom_services_auth_show_login_page_login_warning'];
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN""http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <title><(title)> OpenPSA</title>
  <?php
$_MIDCOM->add_link_head(array('rel' => 'stylesheet',  'type' => 'text/css', 'href' => MIDCOM_STATIC_URL . '/OpenPsa2/style.css', 'media' => 'screen,projection'));
$_MIDCOM->add_link_head(array('rel' => 'stylesheet',  'type' => 'text/css', 'href' => MIDCOM_STATIC_URL . '/OpenPsa2/content.css', 'media' => 'all'));
$_MIDCOM->add_link_head(array('rel' => 'stylesheet',  'type' => 'text/css', 'href' => MIDCOM_STATIC_URL . '/OpenPsa2/print.css', 'media' => 'print'));
$_MIDCOM->add_link_head(array('rel' => 'stylesheet',  'type' => 'text/css', 'href' => MIDCOM_STATIC_URL . '/OpenPsa2/login.css', 'media' => 'all'));
$_MIDCOM->print_head_elements();
?>

  <link rel="shortcut icon" href="<?php echo MIDCOM_STATIC_URL; ?>/org.openpsa.core/openpsa-16x16.png" />

</head>

<body>
  <div class="login-header">
    <h1><(title)> OpenPSA</h1>
  </div>

  <p class='login_message'>
    <?php echo $message ?>
  </p>

  <?php
  if ($login_warning)
  {
      echo '<p class="login_warning">' . $login_warning . "</p>\n";
  } ?>

  <?php $_MIDCOM->auth->show_login_form(); ?>

  <script type="text/javascript">
    document.getElementById('username').focus();
  </script>
  <div class="org_openpsa_softwareinfo">
      <a href="http://www.openpsa.org/">OpenPSA <?php
      $_MIDCOM->componentloader->load('org.openpsa.core');
      echo org_openpsa_core_version::get_version_both();
      ?></a>,
      <a href="http://www.midgard-project.org/">Midgard <?php echo mgd_version(); ?></a>
  </div>
</body>
</html>