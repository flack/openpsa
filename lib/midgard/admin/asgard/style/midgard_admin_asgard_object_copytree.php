<?php
// Get the form with output buffering for modifications
ob_start();
?>
<div id="midgard_admin_asgard_copytree" class="midgard_admin_asgard_tree">
<h2><?php echo $data['l10n']->get('copy tree'); ?></h2>
<?php
$data['tree']->view_link = true;
$data['tree']->draw();
?>
</div>
<?php
$tree_select = ob_get_contents();
ob_end_clean();
?>
<h1><?php echo $data['page_title']; ?></h1>
<?php
// Get the form with output buffering for modifications
ob_start();
$data['controller']->display_form();
$form = ob_get_contents();
ob_end_clean();

// Inject the tree to the form
echo preg_replace('/(<form.*?>)/i', '\1' . $tree_select, $form);
?>
<script type="text/javascript">
    $("#midgard_admin_asgard_copytree").tree_checker();
</script>
