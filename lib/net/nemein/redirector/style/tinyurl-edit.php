<?php
$url = $data['tinyurl']->url;
?>
<h1><?php echo $data['l10n']->get('edit tinyurl'); ?></h1>
<p>
    <?php echo $data['l10n']->get('this page links to'); ?> <a href="<?php echo $url; ?>" class="target_blank"><?php echo $url; ?></a>.
</p>
<?php
$data['controller']->display_form();
?>