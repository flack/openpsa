<?php
//$data =& $_MIDCOM->get_custom_context_data('request_data');
$group =& $data['current_group'];
// Weekly report is always grouped by person so this should be safe
$person =& $data['current_row']['person'];
$query_data =& $data['query_data'];
$weekly_data_group =& $group['weekly_report_data'];

$weekly_data_group['person_workhours'] = $person->parameter('org.openpsa.reports.projects', 'weekly_workhours');
$weekly_data_group['person_target'] = $person->parameter('org.openpsa.reports.projects', 'invoiceable_target');

$weekly_data_group['total'] = $weekly_data_group['invoiceable_total']+$weekly_data_group['uninvoiceable_total'];
?>
                    <tr class="item">
                        <td><?php echo $data['l10n']->get('invoiceable hours'); ?></td>
                        <td class="numeric"><?php printf('%01.2f', $weekly_data_group['invoiceable_total']); ?></td>
                        <td><?php
                            if (   is_array($weekly_data_group['invoiceable_total_by_customer'])
                                && count($weekly_data_group['invoiceable_total_by_customer']) > 0)
                            {
                                echo ' (';
                                $i = 0;
                                $groups_total = 0;
                                arsort($weekly_data_group['invoiceable_total_by_customer']);
                                foreach ($weekly_data_group['invoiceable_total_by_customer'] as $id => $sum)
                                {
                                    if ($i > 0)
                                    {
                                        echo ', ';
                                    }
                                    $i++;
                                    $groups_total += $sum;
                                    $group =& $weekly_data_group['invoiceable_customers'][$id];
                                    echo "{$group->official} " . sprintf('%01.2f', $sum);
                                }
                                if ($groups_total != $weekly_data_group['invoiceable_total'])
                                {
                                    echo ', ' . $data['l10n']->get('no customer') . ' ' . sprintf('%01.2f', $weekly_data_group['invoiceable_total'] - $groups_total);
                                }
                                echo ')';
                            }
                            else
                            {
                                echo "&nbsp;";
                            }
                            ?></td>
                    </tr>
                    <tr class="item">
                        <td><?php echo $data['l10n']->get('uninvoiceable hours'); ?></td>
                        <?php
                        $extra_classes = false;
                        if (   isset($weekly_data_group['person_target'])
                            && $weekly_data_group['person_target']
                            // Avoid divisions by zero...
                            && $weekly_data_group['uninvoiceable_total'])
                        {
                            // Just to make sure lack of this key won't ruin anything.
                            if (!isset($weekly_data_group['person_workhours']))
                            {
                                $weekly_data_group['person_workhours'] = 0;
                            }
                            /* Person has a target percentage for invoiceable hours, here we invert that and check if uninvoiceable hours occopy
                               more time than they should, either from persons specified or actual accumulated hours (whichever is greater:
                               to avoid getting alert view if one does internal work for the first day of the week and also to avoid alerts when
                               the invoiceable/uninvoiceable relation is sound but there's just so much work done) */
                            if ($weekly_data_group['person_workhours'] > $weekly_data_group['total'])
                            {
                                $base = $weekly_data_group['person_workhours'];
                            }
                            else
                            {
                                $base = $weekly_data_group['total'];
                            }
                            $percent = round(($weekly_data_group['uninvoiceable_total'] / $base) * 100);
                            if ($percent > (100 - $weekly_data_group['person_target']))
                            {
                                $extra_classes = ' alert';
                            }
                            echo "<!-- DEBUG: percentage {$percent}% -->\n";
                        }
                        ?>
                        <td class="numeric&(extra_classes);"><?php printf('%01.2f', $weekly_data_group['uninvoiceable_total']); ?></td>
                        <td><?php
                            if (   is_array($weekly_data_group['uninvoiceable_total_by_customer'])
                                && count($weekly_data_group['uninvoiceable_total_by_customer']) > 0)
                            {
                                echo ' (';
                                $i = 0;
                                $groups_total = 0;
                                arsort($weekly_data_group['uninvoiceable_total_by_customer']);
                                foreach ($weekly_data_group['uninvoiceable_total_by_customer'] as $id => $sum)
                                {
                                    if ($i > 0)
                                    {
                                        echo ', ';
                                    }
                                    $i++;
                                    $groups_total += $sum;
                                    $group =& $weekly_data_group['uninvoiceable_customers'][$id];
                                    echo "{$group->official} " . sprintf('%01.2f', $sum);
                                }
                                if ($groups_total != $weekly_data_group['uninvoiceable_total'])
                                {
                                    echo ', ' . $data['l10n']->get('no customer') . ' ' . sprintf('%01.2f', $weekly_data_group['uninvoiceable_total'] - $groups_total);
                                }
                                echo ')';
                            }
                            else
                            {
                                echo "&nbsp;";
                            }
                            ?></td>
                    </tr>
                    <tr class="totals">
                        <td><?php echo $data['l10n']->get('total'); ?></td>
                        <td class="numeric"><?php printf('%01.2f', $weekly_data_group['total']); ?></td>
                        <!-- TODO: display slash (or 'of') persons workhours -->
                        <td class="numeric"><?php
                            if (   isset($weekly_data_group['person_workhours'])
                                && !empty($weekly_data_group['person_workhours']))
                            {
                                echo sprintf($data['l10n']->get('of %01.2f'), $weekly_data_group['person_workhours']);
                            }
                            else
                            {
                                echo '&nbsp;';
                            }
                            ?></td>
                    </tr>