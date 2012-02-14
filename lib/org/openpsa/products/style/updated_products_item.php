<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);

$product = $data['product'];
$view_product = $data['view_product'];

$updated = sprintf($data['l10n']->get('updated %s'), strftime('%x %X', $product->metadata->revised));
?>
<li><a href="<?php echo $data['view_product_url']; ?>">&(view_product['code']:h);: &(view_product['title']:h);</a> (&(updated:h);)</li>