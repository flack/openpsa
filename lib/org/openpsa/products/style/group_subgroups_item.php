<?php
//$data =& $_MIDCOM->get_custom_context_data('request_data');
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);

$group = $data['group'];
$view_group = $data['view_group'];
?>
<li><a href="<?php echo $data['view_group_url']; ?>">&(view_group['code']:h);: &(view_group['title']:h);</a></li>