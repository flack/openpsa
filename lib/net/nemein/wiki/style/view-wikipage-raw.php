<?php
$view = $data['wikipage_view'];

if ($view['content'] != '')
{
    ?>
    &(view["content"]:h);
    <?php
}
else
{
    echo "<p class=\"stub\">".$GLOBALS['request_data']['l10n']->get('this page is stub')."</p>";
}
?>