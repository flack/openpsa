<?php
// The available request keys can be found in the components' API documentation
// of net_nehmer_account_handler_publish
//
// Bind the view data, remember the reference assignment:
//$data =& $_MIDCOM->get_custom_context_data('request_data');
if ($data['linked_mode'])
{
    $field =& $data['linked_field'];
}
else
{
    $field =& $data['current_field'];
}

switch ($field['mode'])
{
    case 'always':
        echo "<img src='{$data['view_imgurl_always']}' />";
        break;

    case 'never':
        echo "<img src='{$data['view_imgurl_never']}' />";
        break;

    case 'user':
        if ($field['visible'])
        {
            $checked = " checked='checked'";
        }
        else
        {
            $checked = '';
        }
        echo "<input type='checkbox' name='{$field['name']}'{$checked} />";
        break;
}
?>