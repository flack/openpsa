<?php
// Available request data: comments, objectguid, comment, display_datamanager
//$data =& $_MIDCOM->get_custom_context_data('request_data');
$comment = $data['comment'];

$creator = $comment->metadata->creator;
$created = $comment->metadata->created;

$user = $_MIDCOM->auth->get_user($creator);
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