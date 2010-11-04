<?php
midgard_admin_asgard_objecthelper::get_help($data);
?>
<script type="text/javascript">
     jQuery(document).ready(function() {
             jQuery('form.datamanager2 input:visible:enabled:first').focus();
         });
</script>
<div class="object_edit">
    <?php $data['controller']->display_form(); ?>
</div>
