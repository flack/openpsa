<?php
$document = $data['document'];
$score = round($document->score * 100);

try {
    $topic = midcom_db_topic::get_cached($document->topic_guid);
} catch (midcom_error $e) {
    $e->log();
}
?>
<div class="midcom_helper_search_result">
  <h3><a href='&(document.document_url);'>&(document.title);</a></h3>
  <div class="midcom_helper_search_result_abstract">
      &(document.abstract:h);
  </div>
  <div class="midcom_helper_search_result_metadata">
    <?php if (isset($topic)) {
    ?>
        <span class="midcom_helper_search_result_topic"><?php echo $data['l10n_midcom']->get('topic') ?>: &(topic.extra);</span>,
    <?php 
} ?>
    <span class="midcom_helper_search_result_score"><?php echo $data['l10n']->get('score') ?>: &(score); %</span>
  </div>
</div>