<?php
$saved_reports = org_openpsa_reports_query_dba::get_saved('org.openpsa.invoices');
?>
<ul>
    <li>
        <a href="&(data['report_prefix']);">
            <?php echo $data['l10n']->get('define custom report'); ?>
        </a>
    </li>
    <?php
    foreach ($saved_reports as $report)
    { ?>
      <li>
        <a href="&(data['report_prefix']);&(report.guid);/" target="_blank">
          &(report.title);
        </a>
        <a class="actions" href="delete/&(report.guid);/"><img style="border:0px" src="<?php echo MIDCOM_STATIC_URL . '/stock-icons/16x16/trash.png'?>" title="<?php echo $data['l10n_midcom']->get('delete'); ?>" alt="<?php echo $data['l10n_midcom']->get('delete'); ?>"/></a>
        <a class="actions" href="&(data['report_prefix']);edit/&(report.guid);/"><img style="border:0px" src="<?php echo MIDCOM_STATIC_URL . '/stock-icons/16x16/edit.png'?>" title="<?php echo $data['l10n_midcom']->get('edit'); ?>" alt="<?php echo $data['l10n_midcom']->get('edit'); ?>"/></a>
      </li>
    <?php }
    ?>
</ul>