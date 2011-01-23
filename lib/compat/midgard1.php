<?php
/**
 * Register PHP function as string formatter to the Midgard formatting engine.
 * @see http://www.midgard-project.org/documentation/reference-other-mgd_register_filter/
 */
function mgd_register_filter($name, $function)
{
    midcom_helper_misc::register_filter($name, $function);
}

/**
 * Return a string as formatted by a Midgard formatter
 * @see http://www.midgard-project.org/documentation/reference-other-mgd_format/
 */
function mgd_format($content, $name)
{
    return midcom_helper_misc::format_variable($content, $name);
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
 * Show a variable
 */
function mgd_variable($variable)
{
    return midcom_helper_misc::expand_variable($variable);
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
