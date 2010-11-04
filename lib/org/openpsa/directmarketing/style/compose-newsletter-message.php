<?php
//$data =& $_MIDCOM->get_custom_context_data('request_data');
echo $data['message_array']['content'];

$blog_node = midcom_helper_find_node_by_component('net.nehmer.blog');
if ($blog_node)
{
    $_MIDCOM->dynamic_load("{$blog_node[MIDCOM_NAV_RELATIVEURL]}latest/{$data['message_array']['newsitems']}");
}
?>