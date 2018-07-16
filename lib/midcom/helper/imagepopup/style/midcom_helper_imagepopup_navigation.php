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