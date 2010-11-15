<div class="main">
    <h1><?php echo sprintf($data['l10n']->get("user account for %s"), $data['person']->name); ?></h1>
    <form method="post" action="<?php echo midcom_connection::get_url('uri'); ?>" class="datamanager2">
        <label for="org_openpsa_contacts_person_account_username">
            <span class="field_text"><?php echo $data['l10n_midcom']->get("username"); ?></span>
            <input type="text" name="org_openpsa_contacts_person_account_username" id="org_openpsa_contacts_person_account_username" class="shorttext" value="&(data["default_username"]);" />
        </label>
        <label for="org_openpsa_contacts_person_account_password">
            <span class="field_text" style="display:block;"><?php echo $data['l10n_midcom']->get("password"); ?></span>
            <input type="text" name="org_openpsa_contacts_person_account_password" id="org_openpsa_contacts_person_account_password" class="shorttext" style="display:inline;" maxlength="<?php echo $data['max_length'];?>" />
        </label>
        <label for="org_openpsa_contacts_person_account_encrypt">
            <span>
            <input id="org_openpsa_contacts_person_account_encrypt" type="checkbox" name="org_openpsa_contacts_person_account_encrypt" value="true"/> <?php echo $data['l10n']->get("new password encrypt"); ?>
            </span>
        </label>
        <div class="form_toolbar">
            <input type="submit" value="<?php echo $data['l10n_midcom']->get('save'); ?>" name="midcom_helper_datamanager2_save" accesskey="s" class="save" />
            <input type="submit" value="<?php echo $data['l10n_midcom']->get('cancel'); ?>" name="midcom_helper_datamanager2_cancel" accesskey="c" class="cancel" />
        </div>
    </form>
<?php
midcom_show_style('show-person-account-js');
?>
<script type="text/javascript">

    $("#org_openpsa_contacts_person_account_password").passStrength({
    userid: "#org_openpsa_contacts_person_account_username"
    });
</script>
</div>