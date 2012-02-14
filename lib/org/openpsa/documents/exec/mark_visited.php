<?php
midcom::get('auth')->require_valid_user();

if ($_SERVER['REQUEST_METHOD'] != 'POST')
{
    throw new midcom_error_forbidden('Only POST requests are allowed here.');
}

if (!array_key_exists('guid', $_POST))
{
    throw new midcom_error('No document specified, aborting.');
}

$document = new org_openpsa_documents_document_dba($_POST['guid']);
$person = midcom::get('auth')->user->get_storage();
if ((int) $person->get_parameter('org.openpsa.documents_visited', $document->guid) < (int) $document->metadata->revised)
{
    $person->set_parameter('org.openpsa.documents_visited', $document->guid, time());
}
?>