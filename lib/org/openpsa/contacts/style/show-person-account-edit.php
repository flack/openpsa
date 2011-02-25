<?php
$data["controller"]->display_form();

midcom_show_style('show-person-account-js');
?>

<script type="text/javascript">
    $("input[name='new_password']").passStrength({
    userid: "#org_openpsa_contacts_username"
    });
</script>
</div>