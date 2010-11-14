<?php
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