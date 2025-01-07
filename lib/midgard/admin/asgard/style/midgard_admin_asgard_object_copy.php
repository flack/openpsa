<?php
// Get the form with output buffering for modifications
ob_start();
$data['tree']->view_link = true;
$data['tree']->draw();
$tree_select = ob_get_clean();

if ($tree_select) {
    $tree_select = '<div id="midgard_admin_asgard_copytree" class="midgard_admin_asgard_tree">'
        . '<h2>' . $data['l10n']->get('copy tree') . '</h2>'
        . $tree_select .
        '</div>';
}
?>
<?php

?>


<h1><?php echo $data['page_title']; ?></h1>
<?php
// Get the form with output buffering for modifications
ob_start();
$data['controller']->display_form();
$form = ob_get_clean();

// Inject the tree to the form
echo preg_replace('/(<form.*?>)/i', '\1' . $tree_select, $form);
?>
<script type="text/javascript">
    $("#midgard_admin_asgard_copytree").tree_checker();
</script>
