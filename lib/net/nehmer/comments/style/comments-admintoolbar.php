<?php
// Available request data: comments, objectguid, comment, display_datamanager
$comment = $data['comment'];
$formatter = $data['l10n']->get_formatter();
$creator = $comment->metadata->creator;
$created = $comment->metadata->created;

$user = midcom::get()->auth->get_user($creator);
if ($user) {
    $username = "{$user->name} ({$user->username})";
} else {
    $username = $data['l10n_midcom']->get('anonymous');
}
$ip = $comment->ip ?: '?.?.?.?';
$metadata = sprintf($data['l10n']->get('creator: %s, created %s, source ip %s.'),
    $username, $formatter->datetime($created), $ip);
?>
<p class="audit">
    &(metadata);
</p>