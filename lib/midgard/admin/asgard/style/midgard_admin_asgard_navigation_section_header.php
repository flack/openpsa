<?php
$classes = 'section';
if ($data['expanded']) {
    $classes .= ' expanded';
}
?>
                    <div class="&(classes);">
                        <h3><a href="&(data['section_url']:h);">&(data['section_name']:h);</a></h3>
<?php if ($data['expanded']) {
    ?>
                        <div class="section_content">
<?php 
} ?>
