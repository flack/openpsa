<?php
$node = $data['node'];
?>
<h1>&(node.extra:h);</h1>

&(node.description:h);

<?php
if ($data['config']->get('enable_folder_code_execution')) {
    // Run code in folder's code field
    ?>
    &(node.code:p);
    <?php
}
?>