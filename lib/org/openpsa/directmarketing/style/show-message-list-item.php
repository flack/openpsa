<li><?php
$view =& $data['message_array'];
$nap = new midcom_helper_nav();
$node = $nap->get_node($nap->get_current_node());

echo "<div class=\"{$data['message_class']}\"><a href=\"{$node[MIDCOM_NAV_FULLURL]}message/{$data['message']->guid}/\">{$view['title']}</a>\n";
echo "<br />" . sprintf($data['l10n']->get('created on %s'), strftime('%x %X', $data['message']->metadata->created)) . "\n";

if ($data['message']->sendStarted)
{
    echo ", " . sprintf($data['l10n']->get('sent on %s'), strftime('%x %X', $data['message']->sendStarted)) . "\n";
}

echo "</div>\n";
?></li>