<?php
$search_config = org_openpsa_widgets_ui::get_search_providers();
if (   array_key_exists('org.openpsa.documents', $search_config)
    && midcom::get()->indexer->enabled()) {
    $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX); ?>
<div class="area" id="document_search">
    <h2><?php echo $data['l10n']->get('search title'); ?></h2>
    <form method="get" action="&(prefix);search/">
        <input type="text" name="query"<?php
        if (array_key_exists('query', $_GET)) {
            echo " value=\"{$_GET['query']}\"";
        } ?> />
        <input type="submit" value="<?php echo $data['l10n']->get("search"); ?>" />
    </form>
</div>
<?php 
} ?>