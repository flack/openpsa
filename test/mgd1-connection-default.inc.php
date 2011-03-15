<?php
$config_name = 'midgard';
$midgard = new midgard_connection();
if (!$midgard->open($config_name))
{
    throw new Exception("Can not connect to database with given configuration " . $config_name);
}
$midgard->set_sitegroup('sg0');
$_MIDGARD['host'] = 0;
?>