<?php
$query = htmlspecialchars($data['query'], ENT_QUOTES);

if ($data['object']) {
    $url = $data['router']->generate('list_unified', ['filetype' => $data['filetype'], 'guid' => $data['object']->guid]);
} else {
    $url = $data['router']->generate('list_unified_noobject', ['filetype' => $data['filetype']]);
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