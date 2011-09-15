<?php
if (!defined('MIDCOM_STATIC_URL'))
{
    define('MIDCOM_STATIC_URL', '/midcom-static');
}

$matches = array();
$content_array = array
(
    'head_js' => midcom::get('head')->get_jshead_elements(),
    'head_css' => array()
);

$link_head = midcom::get('head')->get_link_head();

foreach ($link_head as $link)
{
    if (   array_key_exists('type', $link)
        && array_key_exists('href', $link)
        && $link['type'] == 'text/css')
    {
        $content_array['head_css'][] = $link;
    }
}

//write the js/css-tags into js-array
echo "<script type=\"text/javascript\">";
echo "var scripts = " . json_encode($content_array) .";";
echo "</script>";
 ?>

<script type="text/javascript">

//check if array is already present to indicate if js/css-file was loaded already
if (added_js_files == undefined)
{
    var added_js_files = {};
    var added_css_files = {};
}

//add the css & js files
parse_js(scripts["head_js"]);
parse_css(scripts["head_css"]);

</script>
<div class="tab_div">
<div class="org_openpsa_toolbar">
     <(toolbar-bottom)>
  </div>
  <(content)>
</div>
<script type="text/javascript">
modify_content();
</script>
