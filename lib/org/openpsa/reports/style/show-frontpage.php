<div class="wide">
    <div class="area">
        <h1><?php echo $data['l10n']->get('org.openpsa.reports'); ?></h1>
    <?php
        foreach ($data['available_components'] as $component => $loc) {
            $parts = explode('.', $component);
            $last = array_pop($parts);
            echo "            <h2>{$loc}</h2>\n";
            midcom_show_style("show-{$component}-quick_reports");
            if ($saved_reports = org_openpsa_reports_query_dba::get_saved($component)) {
                echo '<ul>';
                foreach ($saved_reports as $report) {
                    $report_url = $data['router']->generate($last . '_report_guid', ['guid' => $report->guid]);
                    $delete_url = $data['router']->generate('delete_report', ['guid' => $report->guid]);
                    $edit_url = $data['router']->generate($last . '_edit_report_guid', ['guid' => $report->guid]);
                    ?>
                  <li>
                    <a href="&(report_url);" target="_blank">&(report.title);</a>
                    <a class="actions" href="&(delete_url);"><i class="fa fa-trash" title="<?php echo $data['l10n_midcom']->get('delete'); ?>"></i></a>
                    <a class="actions" href="&(edit_url);"><i class="fa fa-pencil" title="<?php echo $data['l10n_midcom']->get('edit'); ?>"></i></a>
                  </li>
                <?php
                }
                echo '</ul>';
            }
        }
    ?>
    </div>
</div>