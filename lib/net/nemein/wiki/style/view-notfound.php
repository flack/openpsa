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