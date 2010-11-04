<div class="main">
    <h1><?php echo sprintf($data['l10n']->get('edit project %s'), $data['object']->title); ?></h1>
    <?php $data['controller']->display_form(); ?>
</div>