<h1><?php echo $data['view_title']; ?></h1>

<ul>
<?php
foreach ($data['wikipages'] as $wikipage) {
    $wikipage_link = midcom::get()->permalinks->create_permalink($wikipage->guid); ?>
    <li><a href="&(wikipage_link);">&(wikipage.title);</a></li>
    <?php

}
?>
</ul>
