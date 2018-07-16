<?php
$action = $data['router']->generate('search');
?>
<div class="search_input">
    <form method="get" action="&(action);">
       <label for="query"><?php echo $data['l10n']->get("search title"); ?>: </label>

        <input type="text" name="query" value="&(data['query_string']);" />
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