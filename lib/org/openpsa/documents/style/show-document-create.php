<?php
//$data =& $_MIDCOM->get_custom_context_data('request_data');
?>
<div class="main">
     <h1><?php echo $data['l10n']->get('create document'); ?></h1>
    <?php $data['controller']->display_form(); ?>
</div>