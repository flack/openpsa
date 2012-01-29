<?php
$message = $this->data['error_message'];
$title = $this->data['error_title'];
$exception = $this->data['error_exception'];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN""http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <title><(title)> OpenPSA</title>
  <?php
$_MIDCOM->add_link_head(array('rel' => 'stylesheet',  'type' => 'text/css', 'href' => MIDCOM_STATIC_URL . '/OpenPsa2/style.css', 'media' => 'screen,projection'));
$_MIDCOM->add_link_head(array('rel' => 'stylesheet',  'type' => 'text/css', 'href' => MIDCOM_STATIC_URL . '/OpenPsa2/content.css', 'media' => 'all'));
$_MIDCOM->add_link_head(array('rel' => 'stylesheet',  'type' => 'text/css', 'href' => MIDCOM_STATIC_URL . '/OpenPsa2/print.css', 'media' => 'print'));
$_MIDCOM->add_link_head(array('rel' => 'stylesheet',  'type' => 'text/css', 'href' => MIDCOM_STATIC_URL . '/OpenPsa2/error.css', 'media' => 'all'));
$_MIDCOM->print_head_elements();
?>

  <link rel="shortcut icon" href="<?php echo MIDCOM_STATIC_URL; ?>/org.openpsa.core/openpsa-16x16.png" />

</head>

<body>
  <div class="error-header">
    <h1><?php echo $title; ?></h1>
  </div>

  <p class='error-message'>
    <?php echo $message ?>
  </p>

  <div class="error-exception">
<?php
$stacktrace = $this->data['error_handler']->get_function_stack();

if (!empty($stacktrace))
{
    echo '<h3>Stacktrace:</h3>';
    echo "<pre>" . implode("\n", $stacktrace) . "</pre>\n";
}
?>
  </div>

  <div class="org_openpsa_softwareinfo">
      <a href="http://www.openpsa.org/">OpenPSA <?php
      $_MIDCOM->componentloader->load('org.openpsa.core');
      echo org_openpsa_core_version::get_version_both();
      ?></a>,
      <a href="http://www.midgard-project.org/">Midgard <?php echo mgd_version(); ?></a>
  </div>

</body>
</html>