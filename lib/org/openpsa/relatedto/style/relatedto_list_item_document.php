<?php
use midcom\datamanager\storage\blobs;

$link = $data['link'];
$document = $data['other_obj'];
$atts = blobs::get_attachments($document, 'document');
?>

<li class="document" id="org_openpsa_relatedto_line_&(link['guid']);">
  <span class="icon">&(data['icon']:h);</span>
  <span class="title"><a href="&(data['document_url']);" target="document_&(document.guid);">&(document.title:h);</a></span>
  <ul class="metadata">
    <li class="time"><?php echo $data['l10n']->get_formatter()->date($document->metadata->created); ?></li>
    <li class="file">
    <?php
    if (empty($atts)) {
        echo midcom::get()->i18n->get_string('no files', 'org.openpsa.documents');
    } else {
        foreach ($atts as $file) {
            $type = org_openpsa_documents_document_dba::get_file_type($file->mimetype);
            $url = midcom_db_attachment::get_url($file);
            echo "<a target=\"document_{$document->guid}\" href=\"{$url}\">{$file->name}</a> (" . $type . ")";
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
