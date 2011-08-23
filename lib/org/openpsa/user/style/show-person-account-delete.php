<div class="main">
    <p><?php echo sprintf($data['l10n']->get("delete account %s %s"), $data['person']->firstname, $data['person']->lastname); ?></p>

    <?php $data['controller']->display_form(); ?>
</div>