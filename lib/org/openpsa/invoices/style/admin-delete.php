<?php
$view = $data['datamanager'];

echo "<h1>" . $data['l10n']->get('delete invoice') . ": " . $data['object']->get_label() . "</h1>\n";
?>
<h2><?php echo midcom::get('i18n')->get_string('confirm delete', 'org.openpsa.core'); ?></h2>
<p><?php echo midcom::get('i18n')->get_string('use the buttons below or in toolbar', 'org.openpsa.core'); ?></p>

<?php
$data['datamanager']->display_view();
$data['controller']->display_form();
?>