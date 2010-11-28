<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
$entries = array();

$grid_id = 'documents_grid';

foreach ($data['documents'] as $document)
{
    $entry = array();

    $entry['id'] = $document->id;
    $entry['index_title'] = $document->title;

    $att = $document->load_attachment();

    $icon = MIDCOM_STATIC_URL . '/stock-icons/mime/gnome-text-blank.png';
    if ($att)
    {
        $icon = midcom_helper_get_mime_icon($att->mimetype);
    }

    $title = '<a class="tab_escape" href="' .$prefix . 'document/' . $document->guid .'/"><img src="' . $icon . '"';
    if ($att)
    {
        $title .= 'alt="' . $att->name . '"';
    }
    $title .= 'style="border: 0px; height: 16px; vertical-align: middle" /> ' . $document->title;

    $entry['title'] = $title;

    $entry['index_filesize'] = 0;
    $entry['filesize'] = '';
    $entry['mimetype'] = '';
    if ($att)
    {
        $stats = $att->stat();
        $entry['index_filesize'] = $stats[7];
        $entry['filesize'] = midcom_helper_filesize_to_string($stats[7]);
        $entry['mimetype'] = org_openpsa_documents_document_dba::get_file_type($att->mimetype);
    }

    $entry['index_created'] = $document->metadata->created;
    $entry['created'] = strftime('%x %X', $document->metadata->created);

    $entry['index_author'] = '';
    $entry['author'] = '';

    if ($document->author)
    {
        $author = org_openpsa_contacts_person_dba::get_cached($document->author);
        $entry['index_author'] = $author->rname;
        $author_card = org_openpsa_contactwidget::get($author->guid);
        $entry['author'] = $author_card->show_inline();
    }

    $entries[] = $entry;
}
echo '<script type="text/javascript">//<![CDATA[';
echo "\nvar " . $grid_id . '_entries = ' . json_encode($entries);
echo "\n//]]></script>";
?>

<div class="org_openpsa_documents full-width">

<table id="&(grid_id);"></table>
<div id="p_&(grid_id);"></div>

</div>

<script type="text/javascript">
jQuery("#&(grid_id);").jqGrid({
      datatype: "local",
      data: &(grid_id);_entries,
      colNames: ['id', 'index_title', <?php
                 echo '"' . $data['l10n']->get('title') . '",';
                 echo '"index_filesize", "' . $_MIDCOM->i18n->get_string('size', 'midcom.admin.folder') . '",';
                 echo '"' . $_MIDCOM->i18n->get_string('mimetype', 'midgard.admin.asgard') . '",';
                 echo '"index_created", "' . $data['l10n_midcom']->get('created on') . '",';
                 echo '"index_author", "' . $data['l10n']->get('author') . '"';
      ?>],
      colModel:[
          {name:'id', index:'id', hidden:true, key:true},
          {name:'index_title',index:'index_title', hidden:true},
          {name:'title', index: 'index_title', width: 80},
          {name:'index_filesize', index: 'index_filesize', sorttype: "number", hidden:true},
          {name:'filesize', index: 'index_filesize', width: 60, fixed: true, align: 'right'},
          {name:'mimetype', index: 'mimetype', width: 60},
          {name:'index_created', index: 'index_created', sorttype: "number", hidden:true},
          {name:'created', index: 'index_created', width: 135, fixed: true, align: 'center'},
          {name:'index_author', index: 'index_author', sorttype: "integer", hidden:true },
          {name:'author', index: 'index_author', width: 70}
      ],
      loadonce: true
});
</script>
