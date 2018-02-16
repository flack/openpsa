<?php
midcom::get()->auth->require_admin_user();

$qb = net_nehmer_comments_comment::new_query_builder();
$qb->add_constraint('metadata.creator', '<>', '');
$qb->begin_group('OR');
    $qb->add_constraint('metadata.authors', '=', '');
    $qb->add_constraint('author', '=', '');
$qb->end_group();

foreach ($qb->execute() as $comment) {
    $author = midcom::get()->auth->get_user($comment->metadata->creator);
    if (!$author) {
        continue;
    }

    $comment->metadata->authors = "|{$author->guid}|";

    if ($author->name) {
        $comment->author = $author->name;
    }

    echo "Updating comment {$comment->guid} to author {$author->name} (#{$author->id})... ";
    $comment->update();
    echo midcom_connection::get_error_string() . "<br />\n";
}
