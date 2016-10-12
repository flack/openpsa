<h1><?php printf($data['l10n']->get('edit feed %s'), $data['feed']->title); ?></h1>

<?php
$data['controller']->display_form();
?>