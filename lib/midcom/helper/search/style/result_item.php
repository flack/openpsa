<?php
$document = $data['document'];
$score = round($document->score * 100);
?>
<div class="midcom_helper_search_result">
  <h3><a href='&(document.document_url);'>&(document.title);</a></h3>
  <div class="midcom_helper_search_result_metadata">
    <?php echo $data['l10n']->get('score') ?>: &(score); %
    &(document.abstract:h);
  </div>
</div>