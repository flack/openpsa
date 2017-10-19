<?php
/**
 * MidCOM Ragnaroek compatibility functions
 *
 * @package midcom.compat
 */
function _midcom_header($string, $replace = true, $http_response_code = null)
{
    midcom_compat_environment::get()->header($string, $replace, $http_response_code);
}

/**
 * MidCOM Ragnaroek compatibility functions
 *
 * @package midcom.compat
 */
function _midcom_stop_request($message = '')
{
    midcom_compat_environment::get()->stop_request($message);
}

/**
 * MidCOM Ragnaroek compatibility functions
 *
 * @package midcom.compat
 */
function _midcom_headers_sent()
{
    return midcom_compat_environment::get()->headers_sent();
}

/**
 * Global shortcut.
 *
 * @see midcom_helper__styleloader::show()
 */
function midcom_show_style($param)
{
    return midcom::get()->style->show($param);
}
