<div class="main">
<h1><?php echo sprintf($data['l10n_midcom']->get('delete %s'), $data['salesproject']->title); ?></h1>
<h2><?php echo midcom::get('i18n')->get_string('confirm delete', 'org.openpsa.core'); ?></h2>
<p><?php echo midcom::get('i18n')->get_string('use the buttons below or in toolbar', 'org.openpsa.core'); ?></p>

<?php
$data['datamanager']->display_view();
?>
<p style="font-weight: bold; color: red;">
<?php
     echo $data['l10n_midcom']->get('all descendants will be deleted');
?>
</p>
<?php
midcom_admin_folder_handler_delete::list_children($data['salesproject']);
$data['controller']->display_form();
?>
</div>