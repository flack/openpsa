<?php
//$data =& $_MIDCOM->get_custom_context_data('request_data');
?>
<div class="sidebar">
    <?php midcom_show_style("show-search-form-simple"); ?>
    <?php midcom_show_style("show-directory-navigation"); ?>
</div>
<div class="main">
     <h1><?php echo $data['l10n']->get('edit directory'); ?></h1>
    <?php $data['controller']->display_form(); ?>
</div>
