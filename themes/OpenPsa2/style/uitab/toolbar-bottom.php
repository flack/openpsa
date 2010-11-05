<?php
$context_id = $_MIDCOM->get_current_context();
$back_button_name = $_MIDCOM->i18n->get_string("back" , "midcom");
//remove the back-button
//TODO: any better way to identify the back button ?
foreach($_MIDCOM->toolbars->_toolbars[$context_id][MIDCOM_TOOLBAR_VIEW]->items as $key => $item)
{
   if (   $item[1] == $back_button_name
       || strpos($_SERVER['HTTP_REFERER'], $item[0]) !== false)
   {
       unset($_MIDCOM->toolbars->_toolbars[$context_id][MIDCOM_TOOLBAR_VIEW]->items[$key]);
   }
}
$_MIDCOM->toolbars->show_view_toolbar();
?>