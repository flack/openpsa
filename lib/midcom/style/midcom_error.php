<?php
$title = $this->data['error_title'];
$message = $this->data['error_message'];
$code = $this->data['error_code'];
?>
<!DOCTYPE html>
<html lang="<?php echo midcom::get()->i18n->get_current_language(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $title; ?></title>
    <style type="text/css">
        body { color: #000000; background-color: #FFFFFF; }
        a:link { color: #0000CC; }
        p, address {margin-left: 3em;}
        address {font-size: smaller;}
    </style>
</head>

<body>
<h1><?php echo $title; ?></h1>

<p>
<?php echo $message; ?>
</p>
<?php if ($this->data['error_exception']) {
    $e = $this->data['error_exception'];
    echo '<p>in ' . $e->getFile() . ', line ' . $e->getLine() . "</p>";
}
?>
<h2>Error <?php echo $code; ?></h2>
<address>
  <a href="/"><?php echo $_SERVER['SERVER_NAME']; ?></a><br />
  <?php echo date('r'); ?><br />
  <?php echo $_SERVER['SERVER_SOFTWARE']; ?>
</address>

<?php
$stacktrace = $this->data['error_handler']->get_function_stack();
if (!empty($stacktrace)) {
    echo "<pre>Stacktrace:\n" . implode("\n", $stacktrace);
    if ($prev = $this->data['error_exception']->getPrevious()) {
        echo "\n\nCaused by:\n";
        echo "\n" . $prev::class . ' in ' . $prev->getFile() . ', line ' . $prev->getLine() . "\n";
        echo "\n" . implode("\n", $this->data['error_handler']->get_function_stack($prev));
    }
    echo "</pre>\n";
}
?>
</body>
</html>
