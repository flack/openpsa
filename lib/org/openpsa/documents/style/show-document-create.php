<div class="sidebar">
    <?php midcom_show_style("show-directory-navigation"); ?>
</div>
<div class="main">
     <h1><?php echo $data['l10n']->get('create document'); ?></h1>
    <?php $data['controller']->display_form(); ?>
</div>