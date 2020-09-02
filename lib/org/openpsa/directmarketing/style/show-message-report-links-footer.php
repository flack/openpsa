    </table>
    <input type="submit" class="button create_campaign" value="<?php echo $data['l10n']->get('create campaign from link'); ?>"/>
</form>
<?php
        $reports_node = midcom_helper_misc::find_node_by_component('org.openpsa.reports');
        if (!empty($reports_node)) {
            $reports_prefix = $reports_node[MIDCOM_NAV_ABSOLUTEURL];
            $filename = 'org_openpsa_directmarketing_' . date('Ymd_Hi'); ?>
<script type="text/javascript" src="<?php echo MIDCOM_STATIC_URL . '/org.openpsa.core/table2csv.js'; ?>"></script>
<form method="post" action="&(reports_prefix);csv/&(filename);.csv" onSubmit="return table2csv('org_openpsa_directmarketing_messagelinks');">
    <input type="hidden" id="csvdata" name="org_openpsa_reports_csv" value="" />
    <input class="button" type="submit" value="<?php echo midcom::get()->i18n->get_string('download as CSV', 'org.openpsa.core'); ?>" />
</form>
    <?php
        }
?>