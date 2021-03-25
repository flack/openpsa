<div class="rcs_navigation">
<?php
echo $data['rcs_toolbar']->render();
echo $data['rcs_toolbar_2']->render();
?>
</div>
<div class="object_view">
    <?php echo $data['datamanager']->display_view(true); ?>
</div>
