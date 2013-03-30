<?php
$l10n = $data['l10n'];
$report =& $data['report'];
$link_data =& $data['use_link_data'];
if (!isset($data['form_suffix']))
{
    $data['form_suffix'] = '';
}
$form_suffix = $data['form_suffix'];
?>
<form method="post">
    <table class="link_statistics" id="org_openpsa_directmarketing_messagelinks&(form_suffix);">
        <tr>
            <th>&nbsp;</th>
            <th><?php echo $l10n->get('link'); ?></th>
            <th>&nbsp;</th>
            <th><?php echo $l10n->get('total clicks'); ?></th>
            <th><?php echo $l10n->get('% of clicks'); ?></th>
            <th><?php echo $l10n->get('% of recipients'); ?></th>
        </tr>
        <?php
            $total = 0;
            foreach ($link_data['counts'] as $target => $count)
            {
                $total += $count;
                $of_clicks = $link_data['percentages']['of_links'][$target];
                $of_recipients = $link_data['percentages']['of_recipients'][$target];
                $rule_ser = org_openpsa_helpers::array2code($link_data['rules'][$target]);
                $visual_width = round($of_clicks*5);
                $target_label = $target;
                // Fetch target url and look for a heading to use
                //TODO: Make a HEAD request and check the content-type instead of trying to guess here
                if (preg_match('/(\.html?|\/|\.com|\.net|\.org|\.fi|\.info)$/', trim(urldecode($target))))
                {
                    debug_add("Trying to fetch '{$target}' and read title from there");
                    $remote_data = false;
                    $fp = @fopen(trim(urldecode($target)), 'r');
                    if ($fp)
                    {
                        while (!feof($fp))
                        {
                            $remote_data .= fread($fp, 4096);
                        }
                        fclose($fp);

                        $regexs = array
                        (
                            /* The parentheses are funny because we need to always have the same key for the label */
                            "/(<h([1-3])>)(.*?)(<\/h\\2>)/msi",
                            "/(<meta name=['\"].*?title['\"] content=(['\"]))(.*?)\\2(\/>)/msi",
                            "/((<title>))(.*?)(<\/title>)/msi",
                        );
                        foreach ($regexs as $regex)
                        {
                            if (preg_match($regex, $remote_data, $title_matches))
                            {
                                debug_print_r("Got title_matches", $title_matches);
                                if (!empty($title_matches[3]))
                                {
                                    $target_label = strip_tags($title_matches[3]);
                                    break;
                                }
                            }
                        }
                        unset($remote_data);
                    }
                }
                $target_label_parts = preg_split("/\s+/", $target_label);
                $target_label_new = '';
                // Mangle long words to avoid them blowing up the report
                foreach ($target_label_parts as $part)
                {
                    if (empty($part))
                    {
                        continue;
                    }
                    if (strlen($part) > 30)
                    {
                        $part = "<span title='{$part}'>" . substr($part, 0, 12) . '...' . substr($part, -12) . '</span>';
                    }
                    $target_label_new .= $part . ' ';
                }
                $target_label = trim($target_label_new);
                ?>
            <tr>
                <textarea name="org_openpsa_directmarketing_campaign_rule_<?php echo md5($target . $form_suffix); ?>" style="display: none;"><?php echo $rule_ser; ?></textarea>
                <input type="hidden" name="org_openpsa_directmarketing_campaign_label_<?php echo md5($target . $form_suffix); ?>" value="<?php echo $target_label; ?>" />
                <td><input type="radio" name="org_openpsa_directmarketing_campaign_userule" value="<?php echo md5($target . $form_suffix); ?>" /></td>
                <td><a href="<?php echo trim(urldecode($target)); ?>" target="_blank" title="<?php echo $target; ?>"><?php echo $target_label; ?></a></td>
                <td class="bargraph"><div style="width: <?php echo $visual_width; ?>px;" class="link_count_visualization">&nbsp;</div></td>
                <td class="numeric"><?php echo $count; ?></td>
                <td class="numeric"><?php echo round($of_clicks, 2); ?></td>
                <td class="numeric"><?php echo round($of_recipients, 2); ?></td>
            </tr>
                <?php
            }
        ?>
            <tr class="totals">
                <td colspan=3>&nbsp;</td>
                <td class="numeric"><?php echo $total; ?></td>
                <td>&nbsp;</td>
                <td class="numeric"><?php echo round(($total / ($report['receipt_data']['sent'] - $report['receipt_data']['bounced'])) * 100, 2); ?></td>
            </tr>
    </table>
    <input type="submit" class="button create_campaign" value="<?php echo $l10n->get('create campaign from link'); ?>"/>
</form>
<?php
        $reports_node = midcom_helper_misc::find_node_by_component('org.openpsa.reports');
        if (!empty($reports_node))
        {
            $reports_prefix = $reports_node[MIDCOM_NAV_ABSOLUTEURL];
            $filename = 'org_openpsa_directmarketing_' . date('Ymd_Hi');
    ?>
<form method="post" action="&(reports_prefix);csv/&(filename);.csv" onSubmit="return table2csv('org_openpsa_directmarketing_messagelinks&(form_suffix);');">
    <input type="hidden" id="csvdata" name="org_openpsa_reports_csv" value="" />
    <input class="button" type="submit" value="<?php echo midcom::get('i18n')->get_string('download as CSV', 'org.openpsa.core'); ?>" />
</form>
    <?php
        }
?>