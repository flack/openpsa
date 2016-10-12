<?php
$view = $data['document_dm'];
$att = $data['document_attachment'];

$document_type = midcom_helper_misc::filesize_to_string($att['filesize']) . ' ' . org_openpsa_documents_document_dba::get_file_type($att['mimetype']);
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
$score = round($data['document_search']->score * 100);

$url = $data['document_search']->topic_url . 'document/' . $data['document']->guid . '/';

// MIME type
$icon = MIDCOM_STATIC_URL . '/stock-icons/mime/gnome-text-blank.png';
if ($att)
{
    $icon = midcom_helper_misc::get_mime_icon($att['mimetype']);
}
?>
<dt><a href="&(url);"><?php echo $view['title']; ?></a></dt>
<dd>
<?php if ($icon)
{
    ?>
    <div class="icon"><a style="text-decoration: none;" href="&(prefix);document/<?php echo $data['document']->guid; ?>/"><img src="&(icon);" <?php
        if ($view['document'])
        {
            echo 'title="' . $document_type . '" ';
        }
    ?>style="border: 0px;"/></a></div>
    <?php
} ?>

<ul>
    <li><?php printf($data['l10n']->get('score: %d%%'), $score); ?></li>
    <?php if ($att)
    {
        echo '<li>' . $document_type . "</li>\n";
    }

    if ($data['document_search']->abstract)
    {
        echo "<li>" . $data['document_search']->abstract . "</li>\n";
    }
    ?>
</ul>
</dd>