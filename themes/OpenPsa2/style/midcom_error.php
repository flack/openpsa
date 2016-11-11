<?php
$head = midcom::get()->head;
$message = $this->data['error_message'];
$title = $this->data['error_title'];
$exception = $this->data['error_exception'];
?>
<!DOCTYPE html>
<html lang="<?php echo midcom::get()->i18n->get_current_language(); ?>">
<head>
  <meta charset="UTF-8">
  <title><(title)> OpenPSA</title>
  <?php
    $head->add_stylesheet(MIDCOM_STATIC_URL . '/OpenPsa2/style.css', 'screen');
    $head->add_stylesheet(MIDCOM_STATIC_URL . '/OpenPsa2/content.css', 'all');
    $head->add_stylesheet(MIDCOM_STATIC_URL . '/OpenPsa2/print.css', 'print');
    $head->add_stylesheet(MIDCOM_STATIC_URL . '/OpenPsa2/error.css', 'all');
    $head->print_head_elements();
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
<?php if (!empty($this->data['error_exception'])) {
    $e = $this->data['error_exception'];
    echo '<p>' . get_class($e) . ' in ' . $e->getFile() . ', line ' . $e->getLine() . "</p>";
}
?>
<?php

$stacktrace = $this->data['error_handler']->get_function_stack();

if (!empty($stacktrace)) {
    echo '<h3>Stacktrace:</h3>';
    echo "<pre>" . implode("\n", $stacktrace) . "</pre>\n";
}
?>
  </div>

  <div class="org_openpsa_softwareinfo">
      <a href="http://www.openpsa.org/">OpenPSA <?php
      echo org_openpsa_core_version::get_version_both();
      ?></a>,
      <a href="http://www.midgard-project.org/">Midgard <?php echo mgd_version(); ?></a>
  </div>

</body>
</html>