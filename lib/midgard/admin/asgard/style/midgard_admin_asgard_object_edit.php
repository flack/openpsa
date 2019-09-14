<?php
$data['style_helper']->render_help();
?>
<div class="object_edit">
    <?php $data['controller']->display_form(); ?>
</div>
<script type="text/javascript">
     $('form.datamanager2 input:visible:enabled:first').focus();
</script>
