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
function _midcom_stop_request($message = '')
{
    midcom_compat_environment::get()->stop_request($message);
}

/**
 * Global shortcut.
 *
 * @see midcom_helper__styleloader::show()
 */
function midcom_show_style($param)
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
function debug_add($message, $loglevel = MIDCOM_LOG_DEBUG)
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
function debug_print_r($message, $variable, $loglevel = MIDCOM_LOG_DEBUG)
{
    midcom::get()->debug->print_r($message, $variable, $loglevel);
}

/**
 * Shortcut: Create a stack trace and dump it.
 *
 * @param string $message    The message to be logged
 * @param int $loglevel        The log level
 */
function debug_print_function_stack($message, $loglevel = MIDCOM_LOG_DEBUG)
{
    midcom::get()->debug->print_function_stack($message, $loglevel);
}

/**
 * Shortcut: Dump a variable's type
 *
 * @param string $message    The message to be logged
 * @param mixed $variable    The variable of which the type should be logged
 * @param int $loglevel        The log level
 */
function debug_print_type($message, $variable, $loglevel = MIDCOM_LOG_DEBUG)
{
    midcom::get()->debug->print_type($message, $variable, $loglevel);
}