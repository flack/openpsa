<?php

if (! isset($_REQUEST['config_str']))
{
    _midcom_stop_request();
}

$config_str = $_REQUEST['config_str'];

$config_arr = @unserialize(base64_decode($config_str));

//var_dump($config_arr);

$js_config = "";

function array_to_js_array($array)
{
    $str = "[";
    $i = 0;
    $max = count($array);
    foreach ($array as $key => $value)
    {
        $i++;
        if (is_array($value))
        {
            $str .= array_to_js_array($value). " ";
        }
        else
        {
            $str .= "'{$value}' ";
        }
        
        if ($i < $max)
        {
            $str .= ",";
        }
    }
    $str .= "]";
    return $str;
}

$skip_keys = array( 'toolbar_content' );
foreach ($config_arr as $key => $value)
{
    if ($key == 'toolbar_set')
    {
        $set_content = array_to_js_array($config_arr['toolbar_content']);
        $js_config = "FCKConfig.ToolbarSets['{$value}'] = {$set_content};\n";
    }
    else if (! in_array($key, $skip_keys))
    {
        if (is_array($value))
        {
            $value = array_to_js_array($value);
        }
        $js_config .= "FCKConfig.{$key} = {$value};";
    }
}

header("content-type: application/x-javascript");
echo $js_config;

?>