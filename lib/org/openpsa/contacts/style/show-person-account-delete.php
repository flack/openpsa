<div class="main">
     <p><?php echo sprintf($data['l10n']->get("delete account %s %s"), $data['person']->firstname, $data['person']->lastname); ?></p>

    <form method="post" action="<?php echo midcom_connection::get_url('uri'); ?>" class="datamanager2">
        <div class="form_toolbar">
            <input type="submit" id="submit_account" value="<?php echo $data['l10n_midcom']->get('delete'); ?>" name="midcom_helper_datamanager2_save" accesskey="d" class="delete" />
            <input type="submit" value="<?php echo $data['l10n']->get('cancel'); ?>" name="midcom_helper_datamanager2_cancel" accesskey="c" class="cancel" />
        </div>
    </form>

</div>