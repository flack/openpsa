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
        <label><?php echo $data['l10n']->get("search in"); ?>: </label>
        <label>
           <input type="radio" name="query_mode" value="person" <?php echo ($data['mode'] == 'person') ? 'checked="checked"' : ''; ?> /> <?php echo $data['l10n']->get('persons'); ?>
        </label>
        <label>
           <input type="radio" name="query_mode" value="group" <?php echo ($data['mode'] == 'group') ? 'checked="checked"' : ''; ?> /> <?php echo $data['l10n']->get('organizations'); ?>
        </label>
        <label>
           <input type="radio" name="query_mode" value="both" <?php echo ($data['mode'] == 'both') ? 'checked="checked"' : ''; ?> /> <?php echo $data['l10n']->get('both'); ?>
        </label>
        <input type="submit" value="<?php echo $data['l10n']->get("search"); ?>" />

    </form>
</div>
<div class="search_results">