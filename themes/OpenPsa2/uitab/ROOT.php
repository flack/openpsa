<?php
if (!defined('MIDCOM_STATIC_URL'))
{
    define('MIDCOM_STATIC_URL', '/midcom-static');
}

$matches = array();
$content_array = array();
$css_pattern = "/<link rel=\"stylesheet\"  type=\"text\/css\"[^\>]+>/";
$js_pattern = "/<script type=\"text\/javascript\" src[^>]+><\/script>/";
$js_pattern_content = "/<script type=\"text\/javascript\">[^<]+<\/script>/";

ob_clean();
//get the head_elements
ob_start();
$_MIDCOM->print_head_elements();
$head_elements = ob_get_contents();
ob_end_clean();
//check for css/js
$hits = preg_match_all($js_pattern, $head_elements , $matches);
$head_javascript = $matches[0];
$content_array["head_js"] = $matches[0];
$hits = preg_match_all($css_pattern, $head_elements , $matches);
$head_css = $matches[0];
$content_array["head_css"] = $matches[0];


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
<?php
$_MIDCOM->content();
?>

</div>
<script type="text/javascript">
modify_content();
</script>
