<h1><?php echo $data['l10n']->get('delete task') . ': ' . $data['object']->title; ?></h1>
<h2><?php echo midcom::get()->i18n->get_string('confirm delete', 'org.openpsa.core'); ?></h2>
<p><?php echo midcom::get()->i18n->get_string('use the buttons below or in toolbar', 'org.openpsa.core'); ?></p>

<?php
$data['datamanager']->display_view();
$data['controller']->display_form();
?>