<?php
//$data =& $_MIDCOM->get_custom_context_data('request_data');
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
        <form method="post" action="&(prefix);csv/&(data['filename']);.csv" onSubmit="return table2csv('org_openpsa_reports_basic_reporttable');">
            <input type="hidden" id="csvdata" name="org_openpsa_reports_csv" value="" />
            <input class="button" type="submit" value="<?php echo $_MIDCOM->i18n->get_string('download as CSV', 'org.openpsa.core'); ?>" />
        </form>
    </body>
</html>