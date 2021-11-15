<?php
$view = $data['document_dm'];
$icon = $document_type = '';
if ($att = $data['document_attachment']) {
    $stat = $att->stat();
    $document_type = midcom_helper_misc::filesize_to_string($stat[7] ?? 0) . ' ' . org_openpsa_helpers_fileinfo::render_type($att->mimetype);
    $icon = midcom_helper_misc::get_mime_icon($att->mimetype);
}
$link = $data['router']->generate('document-view', ['guid' => $data['document']->guid]);
$score = round($data['document_search']->score * 100);

$url = $data['document_search']->topic_url . 'document/' . $data['document']->guid . '/';
?>
<dt><a href="&(url);"><?php echo $view['title']; ?></a></dt>
<dd>
<?php if ($icon) { ?>
    <div class="icon"><a href="&(link);"><img src="&(icon);" title="&(document_type);" /></a></div>
<?php } ?>

<ul>
    <li><?php printf($data['l10n']->get('score: %d%%'), $score); ?></li>
    <?php if ($att) {
        echo '<li>' . $document_type . "</li>\n";
    }

    if ($data['document_search']->abstract) {
        echo "<li>" . $data['document_search']->abstract . "</li>";
    }
    ?>
</ul>
</dd>