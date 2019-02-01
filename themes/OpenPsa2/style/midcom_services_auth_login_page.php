<?php
$l10n = midcom::get()->i18n->get_l10n('midcom');
$message = $l10n->get('login message - please enter credentials');
$login_warning = '';
$title = $l10n->get('login');

if (isset($this->data['midcom_services_auth_access_denied_message'])) {
    $message = $this->data['midcom_services_auth_access_denied_message'];
    $title = $this->data['midcom_services_auth_access_denied_title'];
    $login_warning = $this->data['midcom_services_auth_access_denied_login_warning'];
} else {
    $login_warning = $this->data['midcom_services_auth_show_login_page_login_warning'];
}
?>
<!DOCTYPE html>
<html lang="<?php echo midcom::get()->i18n->get_current_language(); ?>">
<head>
  <meta charset="UTF-8">
  <title><(title)> OpenPSA</title>
  <?php
    $head = midcom::get()->head;
    $head->add_stylesheet(MIDCOM_STATIC_URL . '/OpenPsa2/style.css', 'screen');
    $head->add_stylesheet(MIDCOM_STATIC_URL . '/OpenPsa2/content.css', 'all');
    $head->add_stylesheet(MIDCOM_STATIC_URL . '/OpenPsa2/print.css', 'print');
    $head->add_stylesheet(MIDCOM_STATIC_URL . '/OpenPsa2/login.css', 'all');
    $head->add_jscript('var MIDCOM_PAGE_PREFIX = "' . midcom_connection::get_url('self') . '";');
    $head->print_head_elements();
?>

  <link rel="shortcut icon" href="<?php echo MIDCOM_STATIC_URL; ?>/org.openpsa.core/openpsa-16x16.png" />

</head>

<body>
 <div class="login-container">
  <div class="login-header">
    <h1><(title)> OpenPSA</h1>
  </div>

  <p class='login_message'>
    <?php echo $message ?>
  </p>

  <div class="login-form-container">
  <?php midcom::get()->auth->show_login_form(); ?>

  <p class="login_warning" id="cookie_warning" style="display:none">
  <?php echo $l10n->get('cookies must be enabled to log in'); ?>
  </p>

  <noscript>
  <p class="login_warning" id="js_warning">
  <?php echo $l10n->get('javascript must be enabled to use this site'); ?>
  </p>
  </noscript>

  <?php
  if ($login_warning) {
      echo '<p class="login_warning">' . $login_warning . "</p>\n";
  } ?>
  </div>
  <script type="text/javascript">
    document.getElementById('username').focus();
  </script>
  <div class="org_openpsa_softwareinfo">
      <a href="http://www.openpsa.org/">OpenPSA <?php
      echo org_openpsa_core_version::get_version_both();
      ?></a>,
      <a href="http://www.midgard-project.org/">Midgard <?php echo mgd_version(); ?></a>
  </div>
 </div>
<script type="text/javascript">
if (!navigator.cookieEnabled) {
    document.getElementById('cookie_warning').style.display = 'block';
}
</script>

</body>
</html>