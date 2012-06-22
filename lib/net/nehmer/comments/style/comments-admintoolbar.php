<?php
// Available request data: comments, objectguid, comment, display_datamanager
$comment = $data['comment'];

$creator = $comment->metadata->creator;
$created = $comment->metadata->created;

$user = midcom::get('auth')->get_user($creator);
if ($user)
{
    $username = "{$user->name} ({$user->username})";
}
else
{
    $username = $data['l10n_midcom']->get('anonymous');
}
$ip = $comment->ip ? $comment->ip : '?.?.?.?';
$metadata = sprintf($data['l10n']->get('creator: %s, created %s, source ip %s.'),
    $username, strftime('%x %X', $created), $ip);
?>
<p class="audit">
    &(metadata);
</p>