<?php
$link =& $data['link'];
$document =& $data['other_obj'];
$atts = $document->list_attachments();
?>

<li class="document" id="org_openpsa_relatedto_line_&(link['guid']);">
  <span class="icon">&(data['icon']:h);</span>
  <span class="title"><a href="&(data['document_url']);" target="document_&(document.guid);">&(document.title:h);</a></span>
  <ul class="metadata">
    <li class="time"><?php echo strftime('%x', $document->metadata->created); ?></li>
    <li class="file">
    <?php
    if (count($atts) == 0)
    {
        echo $_MIDCOM->i18n->get_string('no files', 'org.openpsa.documents');
    }
    else
    {
        foreach ($atts as $file)
        {
            // FIXME: This is a messy way of linking into DM-managed files
            if ($file->parameter('midcom.helper.datamanager2.type.blobs', 'fieldname') == 'document')
            {
                echo "<a target=\"document_{$document->guid}\" href=\"{$_MIDGARD['self']}midcom-serveattachmentguid-{$file->guid}/{$file->name}\">{$file->name}</a> (" . sprintf($_MIDCOM->i18n->get_string('%s document', 'org.openpsa.documents'), $_MIDCOM->i18n->get_string($file->mimetype, 'org.openpsa.documents')).")";
            }
        }
    }
    ?>
    </li>
  </ul>

  <div id="org_openpsa_relatedto_details_&(document.guid);" class="details hidden" style="display: none;">
  </div>
  <?php
  //TODO: get correct node and via it then handle details trough AHAH (and when we have node we can use proper link in document_url as well
  org_openpsa_relatedto_handler_relatedto::render_line_controls($link, $data['other_obj']);
  ?>
</li>
