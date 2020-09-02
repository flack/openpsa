<?php
$comment = $data['comment'];
$formatter = $data['l10n']->get_formatter();

if ($user = midcom::get()->auth->get_user($comment->metadata->creator)) {
    $username = "{$user->name} ({$user->username})";
} else {
    $username = $data['l10n_midcom']->get('anonymous');
}
$ip = $comment->ip ?: '?.?.?.?';
$metadata = sprintf($data['l10n']->get('creator: %s, created %s, source ip %s.'),
    $username, $formatter->datetime($comment->metadata->created), $ip);
?>
<p class="audit">
    &(metadata);
</p>