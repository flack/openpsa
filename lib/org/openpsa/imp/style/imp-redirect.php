<?php
//$data =& $_MIDCOM->get_custom_context_data('request_data');

if ($data['login_form_html'])
{
    //We have HTML for a pre-filled login form, display and submit it
    echo "<div style=\"display: none;\">\n";
    echo $data['login_form_html'];
    echo "<script language=\"javascript\">\n";
    echo "    form=document.getElementById('org_openpsa_imp_autoSubmit');\n";
    echo "    form.submit();\n";
    echo "</script>\n";
    echo "</div>\n";
}
else
{
    /* We should not ever hit this, the handler aborts
       if anything critical is missing */
}

?>