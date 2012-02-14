<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . "__ais/imagepopup/";
$query = htmlspecialchars($data['query'], ENT_QUOTES);
$schema_name = $data['schema_name'];

$url = "{$prefix}unified/{$schema_name}/";
if ($data['object'])
{
    $url .= $data['object']->guid;
}
?>
<div class="midcom_helper_imagepopup">
    <h1><?php echo $data['list_title']; ?></h1>

    <?php midcom_show_style("midcom_helper_imagepopup_navigation"); ?>

    <div id="search">

    <div class="search-form">
        <form method='get' name='midcom_helper_imagepopup_search_form' action='&(url);' class='midcom.helper.imagepopup'>
            <label for="midcom_helper_imagepopup_query">
                <?php echo midcom::get('i18n')->get_string('query', 'midcom.helper.imagepopup');?>:
                <input type='text' size='60' name='query' id='midcom_helper_imagepopup_query' value='&(query);' />
            </label>
            <input type='submit' name='submit' value='<?php echo midcom::get('i18n')->get_string('search', 'midcom.helper.imagepopup');?>' />
        </form>
    </div>


