<?php
$nap = new midcom_helper_nav();
$node = $nap->get_node($nap->get_current_node());
?>
<div class="net_nemein_wiki_notfound">
    <h1>&(data['wikiword']);</h1>

    <p>
    <?php
    printf($data['l10n']->get('page %s not found in wiki %s'), $data['wikiword'], $data['wiki_name']);
    ?>
    </p>

    <?php
    echo $data['wiki_tools']->render();
    ?>
</div>