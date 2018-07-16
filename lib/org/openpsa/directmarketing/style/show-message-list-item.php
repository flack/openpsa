<li><?php
$view = $data['message_array'];
$formatter = $data['l10n']->get_formatter();
$link = $data['router']->generate('message_view', ['guid' => $data['message']->guid]);

echo "<div class=\"{$data['message_class']}\"><a href=\"{$link}\">{$view['title']}</a>\n";
echo "<br />" . sprintf($data['l10n']->get('created on %s'), $formatter->datetime($data['message']->metadata->created)) . "\n";

if ($data['message']->sendStarted) {
    echo ", " . sprintf($data['l10n']->get('sent on %s'), $formatter->datetime($data['message']->sendStarted)) . "\n";
}

echo "</div>\n";
?></li>