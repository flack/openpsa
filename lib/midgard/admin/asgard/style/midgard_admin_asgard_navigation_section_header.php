<?php
$classes = 'section';
if ($data['expanded']) {
    $classes .= ' expanded';
}
?>
                    <div class="&(classes);">
                        <h3><?php if (!empty($data['section_url'])) {
                            ?><a href="&(data['section_url']:h);">
                            <?php } ?>&(data['section_name']:h);<?php if (!empty($data['section_url'])) {
                            ?></a><?php } ?></h3>
<?php if ($data['expanded']) {
    ?>
                        <div class="section_content">
<?php
} ?>
