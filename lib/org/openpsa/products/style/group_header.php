<?php
if (array_key_exists('view_group', $data)) {
    $view = $data['view_group']; ?>
    <h1>&(view['code']:h); &(view['title']:h);</h1>

    <?php if (!empty($data['group']->up)) {
        ?>
    <div class="parent_group">
        <span class="label"><?php echo $data['l10n']->get('parent group'); ?>: </span>
        <span class="parent">&(view['up']:h);</span>
    </div>
    <?php 
    } ?>
    &(view['description']:h);
    <?php

} else {
    echo "<h1>{$data['view_title']}</h1>\n";
}
?>