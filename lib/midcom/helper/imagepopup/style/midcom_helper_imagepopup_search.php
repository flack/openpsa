<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . "__ais/imagepopup/";
$query = htmlspecialchars($data['query'], ENT_QUOTES);

$url = $prefix . 'unified/' . $data['filetype'] . '/';
if ($data['object']) {
    $url .= $data['object']->guid;
}
?>
<div class="midcom_helper_imagepopup">

    <?php midcom_show_style('midcom_helper_imagepopup_navigation'); ?>

    <div id="search">

    <div class="search-form">
        <form method='get' name='midcom_helper_imagepopup_search_form' action='&(url);' class='midcom.helper.imagepopup'>
            <label for="midcom_helper_imagepopup_query">
                <?php echo $data['l10n']->get('query');?>:
                <input type='text' size='60' name='query' id='midcom_helper_imagepopup_query' value='&(query);' />
            </label>
            <input type='submit' name='submit' value='<?php echo $data['l10n']->get('search');?>' />
        </form>
    </div>