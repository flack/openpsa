<?php
/**
 * This file declares shortcuts like debug_add (see below).
 *
 * @package midcom.compat
 */

/**
 * MidCOM Ragnaroek compatibility function
 *
 * @package midcom.compat
 */
function _midcom_stop_request(string $message = '')
{
    midcom_compat_environment::get()->stop_request($message);
}

/**
 * Global shortcut.
 *
 * @see midcom_helper_style::show()
 */
function midcom_show_style(string $param)
{
    midcom::get()->style->show($param);
}


class_alias(midcom_baseclasses_components_viewer::class, 'midcom_baseclasses_components_request');


/**
 * Shortcut: Log a message
 *
 * @param string $message    The message to be logged
 * @param int $loglevel        The log level
 */
function debug_add(string $message, int $loglevel = MIDCOM_LOG_DEBUG)
{
    midcom::get()->debug->log($message, $loglevel);
}

/**
 * Shortcut: Dump a variable
 *
 * @param string $message    The message to be logged
 * @param mixed $variable    The variable to be logged
 * @param int $loglevel        The log level
 */
function debug_print_r(string $message, $variable, int $loglevel = MIDCOM_LOG_DEBUG)
{
    midcom::get()->debug->print_r($message, $variable, $loglevel);
}

/**
 * Shortcut: Create a stack trace and dump it.
 *
 * @param string $message    The message to be logged
 * @param int $loglevel        The log level
 */
function debug_print_function_stack(string $message, int $loglevel = MIDCOM_LOG_DEBUG)
{
    midcom::get()->debug->print_function_stack($message, $loglevel);
}
