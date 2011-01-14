<?php
// Available request data: comments, objectguid, post_controller
//$data =& $_MIDCOM->get_custom_context_data('request_data');
?>
<a name="net_nehmer_comments_post_&(data['objectguid']);"></a>
<h3><?php $data['l10n']->show('post a comment'); ?>:</h3>

<?php $data['post_controller']->display_form(); ?>