<?php
$query_data = $data['query_data'];
?>
        <div class="org_openpsa_reports_report org_openpsa_reports_basic">
            <div class="header">
<?php midcom_show_style('projects_report-basic-header-logo'); ?>
                <h1>&(data['title']);</h1>
            </div>
            <table class="report" id="org_openpsa_reports_basic_reporttable">
                <thead>
                    <tr>
<?php   switch ($data['grouping']) {
            case 'date': ?>
                        <th><?php echo $data['l10n']->get('person'); ?></th>
<?php           break;
            case 'person': ?>
                        <th><?php echo $data['l10n_midcom']->get('date'); ?></th>
<?php           break;
        } ?>
                        <th><?php echo midcom::get()->i18n->get_string('task', 'org.openpsa.projects'); ?></th>
<?php   if (array_key_exists('hour_type_filter', $query_data)) {
            ?>
                        <th><?php echo $data['l10n']->get('type'); ?></th>
<?php
        }   ?>
<?php   if (   array_key_exists('invoiceable_filter', $query_data)) {
            ?>
                        <th><?php echo midcom::get()->i18n->get_string('invoiceable', 'org.openpsa.projects'); ?></th>
<?php
        }   ?>
                        <th><?php echo $data['l10n_midcom']->get('description'); ?></th>
                        <th><?php echo midcom::get()->i18n->get_string('hours', 'org.openpsa.projects'); ?></th>
                    </tr>
                </thead>