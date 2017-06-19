<?php
if (!defined('MIDCOM_STATIC_URL')) {
    define('MIDCOM_STATIC_URL', '/midcom-static');
}

$matches = [];
$content_array = [
    'head_js' => midcom::get()->head->get_jshead_elements(),
    'head_css' => []
];

$link_head = midcom::get()->head->get_link_head();

foreach ($link_head as $link) {
    if (   array_key_exists('type', $link)
        && array_key_exists('href', $link)
        && $link['type'] == 'text/css') {
        $content_array['head_css'][] = $link;
    }
}

//write the js/css-tags into js-array
echo "<HEAD_ELEMENTS>" . json_encode($content_array) . "</HEAD_ELEMENTS>";
?>

<div class="tab_div">
<div class="org_openpsa_toolbar">
     <(toolbar-bottom)>
  </div>
  <(content)>
</div>
