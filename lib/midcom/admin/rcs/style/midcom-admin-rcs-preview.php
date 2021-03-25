<h1>&(data['view_title']:h);</h1>
<div class="rcs_navigation">
<?php
echo $data['rcs_toolbar']->render();
echo $data['rcs_toolbar_2']->render();
?>
</div>
<?php echo $data['datamanager']->display_view(true); ?>
