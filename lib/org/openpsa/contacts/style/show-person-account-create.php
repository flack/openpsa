<?php
/*
<div class="main">
    <h1><?php echo sprintf($data['l10n']->get("user account for %s"), $data['person']->name); ?></h1>
    <form method="post" action="<?php echo midcom_connection::get_url('uri'); ?>" class="datamanager2">

        <div class="element element_text">
	        <label for="org_openpsa_contacts_person_account_username">
	            <span class="field_text"><?php echo $data['l10n_midcom']->get("username"); ?></span>
	        </label>
	        <div class="input">
	            <input type="text" name="org_openpsa_contacts_person_account_username" id="org_openpsa_contacts_person_account_username" class="shorttext" value="&(data["default_username"]);" />
			</div>
	    </div>

        <div class="element element_text">
	        <div class="input">
	        	<input type="radio" name="org_openpsa_contacts_person_account_password_switch" value="0" onclick="setPasswordRowDisplay('none')" checked="checked"/> <?php echo $data["l10n"]->get("generate_password"); ?>
	        </div>
        	<div class="input">
            	<input type="radio" name="org_openpsa_contacts_person_account_password_switch" value="1" onclick="setPasswordRowDisplay('table-row')"/> <?php echo $data["l10n"]->get("own_password"); ?>
        	</div>
        </div>


        <div class="element element_text" id="password_row" style="display:none">
	        <label for="org_openpsa_contacts_person_account_password">
	            <span class="field_text"><?php echo $data['l10n_midcom']->get("password"); ?></span>
	        </label>
        	<div class="input">
            	<input type="text" name="org_openpsa_contacts_person_account_password" id="org_openpsa_contacts_person_account_password" class="shorttext" style="display:inline" maxlength="<?php echo $data['max_length'];?>" />
        	</div>
        </div>

        <div class="element element_text">
	        <label for="org_openpsa_contacts_person_account_send_welcome_mail">
	            <span class="field_text" style="display:block;"><?php echo $data['l10n']->get("send_welcome_mail"); ?></span>
	        </label>
        	<div class="input">
            	<input type="checkbox" name="org_openpsa_contacts_person_account_send_welcome_mail" id="org_openpsa_contacts_person_account_send_welcome_mail" />
        	</div>
        </div>



        <div class="form_toolbar">
            <input type="submit" value="<?php echo $data['l10n_midcom']->get('save'); ?>" name="midcom_helper_datamanager2_save" accesskey="s" class="save" />
            <input type="submit" value="<?php echo $data['l10n_midcom']->get('cancel'); ?>" name="midcom_helper_datamanager2_cancel" accesskey="c" class="cancel" />
        </div>
    </form>
    */
?>

<?php

midcom_show_style("show-account-insert-password-menu");

$data['controller']->display_form();

midcom_show_style('show-person-account-js');
?>
<script type="text/javascript">

    $("#org_openpsa_contacts_person_account_password").passStrength({
    userid: "#org_openpsa_contacts_username"
    });

    function setPasswordRowDisplay(display){
        $("#password_row").css("display",display);
    }

</script>
</div>