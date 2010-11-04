<?php
//$data =& $_MIDCOM->get_custom_context_data('request_data');
$sums_all = $data['sums_all'];
?>
                </tbody>
                <tfoot>
                    <?php
                    $colspan = 4;
                    if ($data['handler_id'] != 'deliverable_report')
                    {
                        $colspan++;
                        foreach ($data['sums_per_person'] as $person_id => $sums)
                        {
                            $owner_card = org_openpsa_contactwidget::get($person_id);
                            ?>
                            <tr>
                                <td colspan="&(colspan);"><?php echo $owner_card->show_inline(); ?></td>
                                <td class="numeric"><?php echo sprintf("%01.2f", $sums['price']); ?></td>
                                <td class="numeric"><?php echo sprintf("%01.2f", $sums['cost']); ?></td>
                                <td class="numeric"><?php echo sprintf("%01.2f", $sums['profit']); ?></td>
                                <td></td>
                            </tr>
                            <?php
                        }
                    }

                    ?>
                    <tr>
                        <td colspan="&(colspan);"><?php echo $data['l10n']->get('total'); ?></td>
                        <td class="numeric"><?php echo sprintf("%01.2f", $sums_all['price']); ?></td>
                        <td class="numeric"><?php echo sprintf("%01.2f", $sums_all['cost']); ?></td>
                        <td class="numeric"><?php echo sprintf("%01.2f", $sums_all['profit']); ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>