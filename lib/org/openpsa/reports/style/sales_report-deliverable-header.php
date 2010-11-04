<?php
//$data =& $_MIDCOM->get_custom_context_data('request_data');
$nap = new midcom_helper_nav();
$node = $nap->get_node($nap->get_current_node());
$report =& $data['report'];

?>
        <div class="org_openpsa_reports_report org_openpsa_reports_deliverable">
            <div class="header">
                <?php midcom_show_style('projects_report-basic-header-logo'); ?>
                <h1>&(report['title']);</h1>
            </div>

            <table class="report list sales_report" id="org_openpsa_reports_deliverable_reporttable">
                <thead>
                    <tr>
                        <th><?php echo $_MIDCOM->i18n->get_string('invoices', 'org.openpsa.sales'); ?></th>
                        <?php
                        if ($data['handler_id'] != 'deliverable_report')
                        {
                            echo "            <th>" .  $_MIDCOM->i18n->get_string('owner', 'org.openpsa.sales') . "</th>\n";
                        }
                        ?>
                        <th><?php echo $_MIDCOM->i18n->get_string('customer', 'org.openpsa.sales'); ?></th>
                        <th><?php echo $_MIDCOM->i18n->get_string('salesproject', 'org.openpsa.sales'); ?></th>
                        <th><?php echo $_MIDCOM->i18n->get_string('product', 'org.openpsa.sales'); ?></th>
                        <th class="numeric"><?php echo $_MIDCOM->i18n->get_string('price', 'org.openpsa.sales'); ?></th>
                        <th class="numeric"><?php echo $_MIDCOM->i18n->get_string('cost', 'org.openpsa.sales'); ?></th>
                        <th class="numeric"><?php echo $_MIDCOM->i18n->get_string('profit', 'org.openpsa.sales'); ?></th>
                        <th><?php echo $_MIDCOM->i18n->get_string('calculation basis', 'org.openpsa.sales'); ?></th>
                    </tr>
                </thead>
                <tbody>