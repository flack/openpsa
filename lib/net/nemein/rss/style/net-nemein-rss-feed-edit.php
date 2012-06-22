<h1><?php echo sprintf(midcom::get('i18n')->get_string('edit feed %s', 'net.nemein.rss'), $data['feed']->title); ?></h1>

<?php
$data['controller']->display_form();
?>