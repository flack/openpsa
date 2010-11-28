<?php
$url = 'http://bergie.iki.fi/blog/';
$meta_name = 'icbm';
$link_relation = 'alternate';
$anchor_relation = 'tag';

$client = new org_openpsa_httplib();
$html = $client->get($url);
$meta_value = org_openpsa_httplib_helpers::get_meta_value($html, $meta_name);
$link_values = org_openpsa_httplib_helpers::get_link_values($html, $link_relation);
$anchor_values = org_openpsa_httplib_helpers::get_anchor_values($html, $anchor_relation);

echo "<p>\n";
echo "  url '{$url}'<br>\n";
echo "  value for meta tag {$meta_name}: {$meta_value}<br>\n";
echo "  values for link rel '{$link_relation}'<pre>\n";
print_r($link_values);
echo "  </pre>\n";
echo "  values for anchor rel '{$anchor_relation}'<pre>\n";
print_r($anchor_values);
echo "  </pre>\n";
echo "</p>\n";
?>