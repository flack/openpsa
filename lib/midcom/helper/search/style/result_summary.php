<?php
$found_docs = sprintf($data['l10n']->get('found %s documents, showing %s documents.'),
    $data['document_count'], $data['shown_documents']);
if ($data['shown_documents'] > 1) {
    $shown_docs = sprintf($data['l10n']->get('showing %s to %s.'),
        $data['first_document_number'], $data['last_document_number']);
} else {
    $shown_docs = sprintf($data['l10n']->get('showing %s.'),
        $data['first_document_number']);
}
?>
<div class="midcom_helper_search_results_summary">
  <p>
    &(found_docs);<br />
    &(shown_docs);
  </p>
</div>