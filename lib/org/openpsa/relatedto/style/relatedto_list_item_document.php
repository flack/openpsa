<?php
$link = $data['link'];
$document = $data['other_obj'];
$atts = org_openpsa_helpers::get_dm2_attachments($document, 'document');
?>

<li class="document" id="org_openpsa_relatedto_line_&(link['guid']);">
  <span class="icon">&(data['icon']:h);</span>
  <span class="title"><a href="&(data['document_url']);" target="document_&(document.guid);">&(document.title:h);</a></span>
  <ul class="metadata">
    <li class="time"><?php echo strftime('%x', $document->metadata->created); ?></li>
    <li class="file">
    <?php
    if (empty($atts))
    {
        echo midcom::get('i18n')->get_string('no files', 'org.openpsa.documents');
    }
    else
    {
        $prefix = midcom_connection::get_url('self');
        foreach ($atts as $file)
        {
            echo "<a target=\"document_{$document->guid}\" href=\"{$prefix}midcom-serveattachmentguid-{$file->guid}/{$file->name}\">{$file->name}</a> (" . sprintf(midcom::get('i18n')->get_string('%s document', 'org.openpsa.documents'), midcom::get('i18n')->get_string($file->mimetype, 'org.openpsa.documents')).")";
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
