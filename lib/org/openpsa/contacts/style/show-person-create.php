<div class="main">
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