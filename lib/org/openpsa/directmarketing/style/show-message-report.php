<?php
$l10n =& $data['l10n'];
$report =& $data['report']
?>
<h2><?php printf($l10n->get('report for message %s'), $data['message']->title); ?></h2>
<?php
if ($report['receipt_data']['first_send'] == 0)
{
    echo '<p>' . $l10n->get('nothing sent yet') . '</p>';
}
else
{
?>
<h3><?php echo $l10n->get('message statistics'); ?></h3>
<table class="message_statistics">
    <tr>
        <th><?php echo $l10n->get('first message sent on'); ?></th>
        <td class="time"><?php echo strftime('%x %H:%M', $report['receipt_data']['first_send']); ?></td>
    </tr>
    <tr>
        <th><?php echo $l10n->get('last message sent on'); ?></th>
        <td class="time"><?php echo strftime('%x %H:%M', $report['receipt_data']['last_send']); ?></td>
    </tr>
    <tr>
        <th><?php echo $l10n->get('total recipients'); ?></th>
        <td class="numeric"><?php echo round($report['receipt_data']['sent'], 2); ?></td>
    </tr>
    <!-- TODO: check that campaign has bounce detection enabled -->
    <tr>
        <th><?php echo $l10n->get('bounced recipients'); ?></th>
        <td class="numeric"><?php echo round($report['receipt_data']['bounced'], 2); ?></td>
    </tr>
    <tr>
        <th><?php echo $l10n->get('send failures'); ?></th>
        <td class="numeric"><?php echo round($report['receipt_data']['failed'], 2); ?></td>
    </tr>
    <tr>
    <?php
        if ($report['campaign_data']['next_message'])
        {
    ?>
        <th><?php echo sprintf($l10n->get('unsubscribed between %s - %s'), strftime('%x %H:%M', $report['receipt_data']['first_send']), strftime('%x %H:%M', $report['campaign_data']['next_message']->sendStarted)); ?></th>
    <?php
        }
        else
        {
    ?>
        <th><?php echo sprintf($l10n->get('unsubscribed since %s'), strftime('%x %H:%M', $report['receipt_data']['first_send'])); ?></th>
    <?php
        }
    ?>
        <td class="numeric"><?php echo round($report['campaign_data']['unsubscribed'], 2); ?></td>
    </tr>
</table>
<?php
    if (count($report['link_data']['counts']) > 0)
    {
        echo "\n<h3>" . $l10n->get('link statistics') . "</h3>\n";
        midcom_show_style('show-message-report-links-header');
        $data['use_link_data'] =& $report['link_data'];
        $data['body_class'] = 'all';
        midcom_show_style('show-message-report-links-body');
        if (   isset($report['link_data']['segments'])
            && is_array($report['link_data']['segments']))
        {
            foreach ($report['link_data']['segments'] as $segment => $segment_data)
            {
                unset($data['use_link_data']);
                $data['use_link_data'] =& $segment_data;
                $data['body_class'] = 'segment';
                $data['body_title'] = sprintf($l10n->get('segment "%s" link statistics'), $segment);
                midcom_show_style('show-message-report-links-body');
            }
        }
        midcom_show_style('show-message-report-links-footer');
    }
}
?>