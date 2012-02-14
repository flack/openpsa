<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
$view = $data['datamanager']->get_content_html();
$product = $data['product'];
?>
<li>
    <a href="&(prefix);product/&(product.guid);/">&(view['code']); &(view['title']);</a>
</li>