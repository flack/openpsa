<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
$query =& $data['query_data'];

//TODO: See report start about output depending on context
if (empty($query['skip_html_headings']))
{
?>
        <form method="post" action="&(prefix);csv/&(data['filename']);.csv" onSubmit="return table2csv('org_openpsa_reports_weekly_reporttable');">
            <input type="hidden" id="csvdata" name="org_openpsa_reports_csv" value="" />
            <input class="button" type="submit" value="<?php echo midcom::get('i18n')->get_string('download as CSV', 'org.openpsa.core'); ?>" />
        </form>
    </body>
</html>
<?php
}
?>