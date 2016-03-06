<?php
$product = $data['product'];
$view_product = $data['view_product'];

$updated = sprintf($data['l10n']->get('updated %s'), $data['l10n']->get_formatter()->datetime($product->metadata->revised));
?>
<li><a href="<?php echo $data['view_product_url']; ?>">&(view_product['code']:h);: &(view_product['title']:h);</a> (&(updated:h);)</li>