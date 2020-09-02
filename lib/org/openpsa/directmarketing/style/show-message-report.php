<?php
$l10n = $data['l10n'];
$report = $data['report'];
$formatter = $l10n->get_formatter();
?>
<h1><?php printf($l10n->get('report for message %s'), $data['message']->title); ?></h1>
<?php
if ($data['message']->sendStarted == 0) {
    echo '<p>' . $l10n->get('nothing sent yet') . '</p>';
} else {
    $first_send = $l10n->get('nothing sent yet');
    $last_send = $l10n->get('nothing sent yet');
    if ($report['receipt_data']['sent'] > 0) {
        $first_send = $formatter->datetime($report['receipt_data']['first_send']);
        $last_send = $formatter->datetime($report['receipt_data']['last_send']);
    } ?>
<div class="midcom_helper_datamanager2_view">
  <div class="field">
    <div class="title"><?php echo $l10n->get('first message sent on'); ?></div>
    <div class="value">&(first_send);</div>
  </div>
  <div class="field">
    <div class="title"><?php echo $l10n->get('last message sent on'); ?></div>
    <div class="value">&(last_send);</div>
  </div>
</div>

<table class="list message_statistics">
  <thead>
    <tr>
        <th colspan="2"><?php echo $l10n->get('message statistics'); ?></th>
    </tr>
  </thead>
  <tbody>
    <tr>
        <td class="title"><?php echo $l10n->get('total recipients'); ?></td>
        <td class="numeric"><?php echo round($report['receipt_data']['sent'], 2); ?></td>
    </tr>
    <!-- TODO: check that campaign has bounce detection enabled -->
    <tr>
        <td class="title"><?php echo $l10n->get('bounced recipients'); ?></td>
        <td class="numeric"><?php echo round($report['receipt_data']['bounced'], 2); ?></td>
    </tr>
    <tr>
        <td class="title"><?php echo $l10n->get('send failures'); ?></td>
        <td class="numeric"><?php echo round($report['receipt_data']['failed'], 2); ?></td>
    </tr>
    <tr>
    <?php
        if ($report['campaign_data']['next_message']) {
            ?>
        <td class="title"><?php printf($l10n->get('unsubscribed between %s - %s'), $first_send, $formatter->datetime($report['campaign_data']['next_message']->sendStarted)); ?></td>
    <?php
        } else {
            ?>
        <td class="title"><?php printf($l10n->get('unsubscribed since %s'), $first_send); ?></td>
    <?php
        } ?>
        <td class="numeric"><?php echo round($report['campaign_data']['unsubscribed'], 2); ?></td>
    </tr>
  </tbody>
</table>
<?php
    if (!empty($report['link_data']['counts'])) {
        echo "\n<h3>" . $l10n->get('link statistics') . "</h3>\n";
        midcom_show_style('show-message-report-links-header');
        $data['use_link_data'] = $report['link_data'];
        $data['body_class'] = 'all';
        midcom_show_style('show-message-report-links-body');
        if (!empty($report['link_data']['segments'])) {
            foreach ($report['link_data']['segments'] as $segment => $segment_data) {
                unset($data['use_link_data']);
                $data['use_link_data'] = $segment_data;
                $data['body_class'] = 'segment';
                $data['body_title'] = sprintf($l10n->get('segment "%s" link statistics'), $segment);
                midcom_show_style('show-message-report-links-body');
            }
        }
        midcom_show_style('show-message-report-links-footer');
    }
}
?>