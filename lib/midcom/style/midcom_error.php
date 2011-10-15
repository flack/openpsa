<?php
$title = $this->data['error_title'];
$message = $this->data['error_message'];
$code = $this->data['error_code'];

echo '<?'.'xml version="1.0" encoding="UTF-8"?'.">\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
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
<?php if ($this->data['error_exception'])
{
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
if (!empty($stacktrace))
{
    echo "<pre>{$stacktrace}</pre>\n";
}
?>
</body>
</html>
