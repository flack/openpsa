<?php
$view = $data['document_dm'];
$att = $data['document_attachment'];
$stat = $att->stat();

$document_type = midcom_helper_misc::filesize_to_string($stat[7]) . ' ' . org_openpsa_helpers_fileinfo::render_type($att->mimetype);
$link = $data['router']->generate('document-view', ['guid' => $data['document']->guid]);
$score = round($data['document_search']->score * 100);

$url = $data['document_search']->topic_url . 'document/' . $data['document']->guid . '/';

// MIME type
$icon = midcom_helper_misc::get_mime_icon($att->mimetype);
?>
<dt><a href="&(url);"><?php echo $view['title']; ?></a></dt>
<dd>
<?php if ($icon) { ?>
    <div class="icon"><a style="text-decoration: none;" href="&(link);"><img src="&(icon);" <?php
        if ($view['document']) {
            echo 'title="' . $document_type . '" ';
        } ?>style="border: 0;"/></a></div>
    <?php
} ?>

<ul>
    <li><?php printf($data['l10n']->get('score: %d%%'), $score); ?></li>
    <?php if ($att) {
        echo '<li>' . $document_type . "</li>\n";
    }

    if ($data['document_search']->abstract) {
        echo "<li>" . $data['document_search']->abstract . "</li>\n";
    }
    ?>
</ul>
</dd>