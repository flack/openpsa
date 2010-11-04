<?php
//$data =& $_MIDCOM->get_custom_context_data('request_data');
?>

<h1><?php echo $data['view_title']; ?></h1>

<?php $data['controller']->display_form(); ?>