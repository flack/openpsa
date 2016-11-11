<?php
$back_button_name = midcom::get()->i18n->get_string("back", "midcom");
//remove the back-button
//TODO: any better way to identify the back button ?

$view_toolbar = midcom::get()->toolbars->get_view_toolbar();
foreach ($view_toolbar->items as $key => $item) {
    if (   $item[1] == $back_button_name
       || (    array_key_exists('HTTP_REFERER', $_SERVER)
            && strpos($_SERVER['HTTP_REFERER'], $item[0]) !== false)) {
        $view_toolbar->hide_item($key);
    }
}
echo $view_toolbar->render();
?>