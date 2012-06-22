<?php
$view = $data['view_group'];
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<h1>&(view['code']:h); &(view['title']:h);</h1>

<?php $data['controller']->display_form (); ?>