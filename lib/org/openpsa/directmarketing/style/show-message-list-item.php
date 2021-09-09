<li><?php
$view = $data['message_array'];
$formatter = $data['l10n']->get_formatter();
$link = $data['router']->generate('message_view', ['guid' => $data['message']->guid]);

echo "<div class=\"email\"><i class=\"fa fa-{$data['message_class']}\"></i><a href=\"{$link}\">{$view['title']}</a><br />\n";
printf($data['l10n']->get('created on %s'), $formatter->datetime($data['message']->metadata->created));

if ($data['message']->sendStarted) {
    echo "<br />\n" . sprintf($data['l10n']->get('sent on %s'), $formatter->datetime($data['message']->sendStarted));
}

echo "</div>\n";
?></li>