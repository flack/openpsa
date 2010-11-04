<?php
set_time_limit(50000);
ini_set('memory_limit', "800M");
while(@ob_end_flush());
echo "<pre>\n";

$qb = org_openpsa_documents_document_dba::new_query_builder();
$results = $qb->execute();

foreach($results as $document)
{
    foreach($document->list_attachments() as $attachment)
    {
        foreach($attachment->list_parameters() as $domain => $values);
        {
            if ($domain != 'midcom.helper.datamanager.datatype.blob')
            {
                continue;
            }
            foreach ($values as $name => $value)
            {
                $attachment->set_parameter('midcom.helper.datamanager2.type.blobs', $name, $value);
                //delete the old value
                $attachment->set_parameter('midcom.helper.datamanager.datatype.blob', $name, '');
            }
        }
        $identifier = md5(time() . $attachment->name);
        $attachment->set_parameter('midcom.helper.datamanager2.type.blobs', 'identifier', $identifier);

        $document->set_parameter('midcom.helper.datamanager2.type.blobs', 'guids_document', $identifier . ":" . $attachment->guid);

        echo "Attachment for document #{$document->title} updated\n";
        flush();
    }

}
echo "</pre>";
ob_start();
?>