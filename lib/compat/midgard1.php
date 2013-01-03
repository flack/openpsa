<?php
/**
 * Compat wrappers for deprecated mgd1 functionality
 *
 * @package midcom
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Register PHP function as string formatter to the Midgard formatting engine.
 * @see http://www.midgard-project.org/documentation/reference-other-mgd_register_filter/
 */
function mgd_register_filter($name, $function)
{
    midcom_helper_formatter::register($name, $function);
}

/**
 * Return a string as formatted by a Midgard formatter
 * @see http://www.midgard-project.org/documentation/reference-other-mgd_format/
 */
function mgd_format($content, $name)
{
    return midcom_helper_formatter::format($content, $name);
}

/**
 * Show a variable
 */
function mgd_variable($variable)
{
    return midcom_helper_formatter::convert_to_php($variable);
}

/**
 * Invalidate Midgard's element cache
 *
 * @todo Caching the elements found by midcom_helper_misc::include_element() might be a good idea
 */
function mgd_cache_invalidate()
{
}

function mgd_template($var)
{
    return midcom_helper_misc::include_element($var);
}

/**
 * Include an element
 */
function mgd_element($name)
{
    return midcom_helper_misc::include_element($name);
}

/**
 * Preparse a string to handle element inclusion and variable
 *
 * @see mgd_preparse
 */
function mgd_preparse($code)
{
    return midcom_helper_misc::preparse($code);
}
?>
