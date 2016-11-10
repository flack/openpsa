<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . "__ais/imagepopup/";
?>
<div id="top_navigation">
    <ul>
    <?php
    foreach ($data['navlinks'] as $link) {
        echo '<li' . ($link['selected'] ? ' class="selected"' : '') . '>';
        echo '<a href="' . $link['url'] . '/">' . $link['label'] . '</a></li>';
    }
   ?>
   </ul>
</div>