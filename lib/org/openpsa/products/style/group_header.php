<div class="sidebar">
<?php
midcom_show_style('group-tree');
?>
</div>
<div class="main">
<?php
if (array_key_exists('view_group', $data)) {
    $view = $data['view_group']; ?>
    <h1>&(view['code']:h); &(view['title']:h);</h1>

    &(view['description']:h);
    <?php

} else {
    echo "<h1>{$data['view_title']}</h1>\n";
}
?>