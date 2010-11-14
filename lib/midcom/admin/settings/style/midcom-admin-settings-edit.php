<h1><?php echo $_MIDCOM->i18n->get_string('host configuration', 'midcom.admin.settings'); ?>: &(data['hostname']);</h1>
<div id="column">
<?php
$data['controller']->display_form();
?>
</div>