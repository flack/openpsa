<h1><?php echo sprintf($_MIDCOM->i18n->get_string('edit feed %s', 'net.nemein.rss'), $data['feed']->title); ?></h1>

<?php
$data['controller']->display_form();
?>