<div class="sidebar">
  <div class="area org_openpsa_helper_box">
    <h3><?php echo $data['l10n']->get('groups'); ?></h3>
<?php
$data['tree']->render();
?>
  </div>
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