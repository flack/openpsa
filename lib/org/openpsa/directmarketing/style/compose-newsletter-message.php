<?php
echo $data['message_array']['content'];

if ($blog_node = midcom_helper_misc::find_node_by_component('net.nehmer.blog')) {
    midcom::get()->dynamic_load("{$blog_node[MIDCOM_NAV_RELATIVEURL]}latest/{$data['message_array']['newsitems']}");
}
