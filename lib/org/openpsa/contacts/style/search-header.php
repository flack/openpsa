<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<div class="search_input">
    <form method="get" action="&(prefix);search/">
       <label for="query"><?php echo $data['l10n']->get("search title"); ?>: </label>

        <input type="text" name="query"<?php
        if (array_key_exists('query', $_GET))
        {
            echo " value=\"{$_GET['query']}\"";
        }
        ?> />
        <input type="submit" value="<?php echo $data['l10n']->get("search"); ?>" />
    </form>
</div>
<div class="search_results">