<?php
// This form is usually handled by a remote transporter that Basic authenticates with some local user account
$_MIDCOM->auth->require_valid_user('basic');
?>
<h1>Got _GET</h1>
<pre>
<?php
print_r($_GET);
?>
</pre>
<h1>Got _POST</h1>
<pre>
<?php
print_r($_POST);
?>
</pre>
<h1>Got _COOKIE</h1>
<pre>
<?php
print_r($_COOKIE);
?>
</pre>
