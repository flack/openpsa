<?php
$nap = new midcom_helper_nav();
$node = $nap->get_node($nap->get_current_node());
?>
<div class="area" id="document_search">
    <h2><?php echo $data['l10n']->get('search title'); ?></h2>
    <form method="get" action="&(node[MIDCOM_NAV_FULLURL]);search/">
        <input type="text" name="query"<?php
        if (array_key_exists('query', $_GET))
        {
            echo " value=\"{$_GET['query']}\"";
        }
        ?> />
        <input type="submit" value="<?php echo $data['l10n']->get("search"); ?>" />
    </form>
</div>