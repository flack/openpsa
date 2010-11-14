<?php
$view = $data['view_group'];
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<h1>&(view['code']:h); &(view['title']:h);</h1>

<?php $data['controller']->display_form (); ?>