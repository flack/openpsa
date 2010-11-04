<?php
//$data =& $_MIDCOM->get_custom_context_data('request_data');
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
$view = $data['datamanager']->get_content_html();
$product = $data['product'];
?>
<li>
    <a href="&(prefix);product/&(product.guid);/">&(view['code']); &(view['title']);</a>
</li>