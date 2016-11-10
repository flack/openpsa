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
                    <a class="actions" href="delete/&(report.guid);/"><img style="border:0px" src="<?php echo MIDCOM_STATIC_URL . '/stock-icons/16x16/trash.png'?>" title="<?php echo $data['l10n_midcom']->get('delete'); ?>" alt="<?php echo $data['l10n_midcom']->get('delete'); ?>"/></a>
                    <a class="actions" href="&(data['report_prefix']);edit/&(report.guid);/"><img style="border:0px" src="<?php echo MIDCOM_STATIC_URL . '/stock-icons/16x16/edit.png'?>" title="<?php echo $data['l10n_midcom']->get('edit'); ?>" alt="<?php echo $data['l10n_midcom']->get('edit'); ?>"/></a>
                  </li>
                <?php 
                }
                echo '</ul>';
            }
        }
    ?>
    </div>
</div>