<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<div class="wide">
    <div class="area">
        <h1><?php echo $data['l10n']->get('org.openpsa.reports'); ?></h1>
    <?php
        foreach ($data['available_components'] as $component => $loc) {
            $parts = explode('.', $component);
            $last = array_pop($parts);
            $data['report_prefix'] = "{$prefix}{$last}/";
            echo "            <h2>{$loc}</h2>\n";
            midcom_show_style("show-{$component}-quick_reports");
            if ($saved_reports = org_openpsa_reports_query_dba::get_saved($component)) {
                echo '<ul>';
                foreach ($saved_reports as $report) {
                    ?>
                  <li>
                    <a href="&(data['report_prefix']);&(report.guid);/" target="_blank">
                      &(report.title);
                    </a>
                    <a class="actions" href="delete/&(report.guid);/"><i class="fa fa-trash" title="<?php echo $data['l10n_midcom']->get('delete'); ?>"></i></a>
                    <a class="actions" href="&(data['report_prefix']);edit/&(report.guid);/"><i class="fa fa-pencil" title="<?php echo $data['l10n_midcom']->get('edit'); ?>"></i></a>
                  </li>
                <?php
                }
                echo '</ul>';
            }
        }
    ?>
    </div>
</div>