<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
        <form method="post" action="&(prefix);csv/&(data['filename']);.csv" onSubmit="return table2csv('org_openpsa_reports_deliverable_reporttable');">
            <input type="hidden" id="csvdata" name="org_openpsa_reports_csv" value="" />
            <input class="button" type="submit" value="<?php echo midcom::get()->i18n->get_string('download as CSV', 'org.openpsa.core'); ?>" />
        </form>
    </body>
</html>