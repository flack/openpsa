<?php
    $siteconfig = new org_openpsa_core_siteconfig();

    if (!$siteconfig->create_ui_page())
    {
        echo "Couldn't create necessary page for jquery_ui_tab \n";
        echo "See debug-log for details";
    }
    else
    {
        echo "Page was created or already exists";
    }
?>
