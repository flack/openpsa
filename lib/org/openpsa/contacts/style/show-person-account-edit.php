<div class="main">
     <h1><?php echo sprintf($data['l10n']->get("edit user account: %s %s"), $data['person']->firstname, $data['person']->lastname); ?></h1>

    <form method="post" action="&(_MIDGARD['uri']);" class="datamanager2">
        <label for="org_openpsa_contacts_person_account_username">
            <span class="field_text"><?php echo $data['l10n']->get("username"); ?></span>
            <input type="text" name="org_openpsa_contacts_person_account_username" id="org_openpsa_contacts_person_account_username" class="shorttext" value="<?php echo $data['person']->username; ?>" />
        </label>
        <label for="org_openpsa_contacts_person_account_current_password">
            <span class="field_text"><?php echo $data['l10n']->get("current password"); ?></span>
            <input type="password" name="org_openpsa_contacts_person_account_current_password" id="org_openpsa_contacts_person_account_current_password" class="shorttext" maxlength="<?php echo $data['max_length'];?>" />
        </label>
        <label for="org_openpsa_contacts_person_account_newpassword">
            <span class="field_text" style="display:block;"><?php echo $data['l10n']->get("new password"); ?></span>
            <input type="password" name="org_openpsa_contacts_person_account_newpassword" id="org_openpsa_contacts_person_account_newpassword" class="shorttext" style="display:inline;" maxlength="<?php echo $data['max_length'];?>" />
        </label>
        <label for="org_openpsa_contacts_person_account_newpassword2">
            <span class="field_text"><?php echo $data['l10n']->get("new password repeat"); ?></span>
            <input type="password" name="org_openpsa_contacts_person_account_newpassword2" id="org_openpsa_contacts_person_account_newpassword2" class="shorttext" maxlength="<?php echo $data['max_length'];?>" />
        </label>
        <label for="org_openpsa_contacts_person_account_encrypt">
            <span>
            <input id="org_openpsa_contacts_person_account_encrypt" type="checkbox" name="org_openpsa_contacts_person_account_encrypt" value="true"/> <?php echo $data['l10n']->get("new password encrypt"); ?>
            </span>
        </label>
        <div class="form_toolbar">
            <input type="submit" id="submit_account" value="<?php echo $data['l10n']->get('save'); ?>" name="midcom_helper_datamanager2_save" accesskey="s" class="save" />
            <input type="submit" value="<?php echo $data['l10n']->get('cancel'); ?>" name="midcom_helper_datamanager2_cancel" accesskey="c" class="cancel" />
        </div>
    </form>

<?php
midcom_show_style('show-person-account-js');
?>

<script type="text/javascript">

    $("#org_openpsa_contacts_person_account_newpassword").passStrength({
    userid: "#org_openpsa_contacts_person_account_username"
    });
</script>
</div>