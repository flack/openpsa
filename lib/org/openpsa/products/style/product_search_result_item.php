<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
$view = $data['datamanager']->get_content_html();
$product = $data['product'];
?>
<li>
    <a href="&(prefix);product/&(product.guid);/">&(view['code']); &(view['title']);</a>
</li>