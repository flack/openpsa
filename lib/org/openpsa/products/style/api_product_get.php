<?php
// This is a style element so the XML output can easily be modified to whatever is needed: DOAP, ...
$mapper = new midcom_helper_xml_objectmapper();
$label = $data['datamanager']->schema->name;
if ($label == 'default')
{
    $label = 'product';
}

$extradata = array();
if (midcom::get('componentloader')->is_installed('net.nehmer.comments'))
{
    // We have comments component installed, add comments about the product to the view
    midcom::get('componentloader')->load('net.nehmer.comments');

    $comments_db = net_nehmer_comments_comment::list_by_objectguid_filter_anonymous($data['product']->guid);

    foreach ($comments_db as $i => $comment)
    {
        $extradata['comments'][] = array
        (
            'guid' => $comment->guid,
            'author' => $comment->author,
            'title' => $comment->title,
            'content' => $comment->content,
            'published' => $comment->metadata->published,
            'rating' => $comment->rating,
        );
    }
}
echo $mapper->dm2data($data['datamanager'], $label, $extradata);
?>
